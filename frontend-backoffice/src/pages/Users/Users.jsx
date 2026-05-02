import React, { useState, useEffect } from 'react';
import { Table, Tag, Button, Space, message, Popconfirm, Dropdown, Modal, Form, Select, Tooltip } from 'antd';
import { EditOutlined, DeleteOutlined, EyeOutlined, SafetyCertificateOutlined } from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';
import ListPageLayout from '../../components/ListPage/ListPageLayout';
import api from '../../services/api';
import { useAuth } from '../../contexts/AuthContext';
import { useUniverse } from '../../contexts/UniverseContext';

const Users = () => {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(false);
  const [searchValue, setSearchValue] = useState('');
  const [statusFilter, setStatusFilter] = useState(null);
  const [roleFilter, setRoleFilter] = useState(null);
  const [rolesModalOpen, setRolesModalOpen] = useState(false);
  const [rolesSaving, setRolesSaving] = useState(false);
  const [selectedUser, setSelectedUser] = useState(null);
  const [rolesForm] = Form.useForm();
  const navigate = useNavigate();
  const { user: currentUser } = useAuth();
  const { universes } = useUniverse();
  const isCurrentUserSuperAdmin = currentUser?.roles?.includes('ROLE_SUPER_ADMIN');

  useEffect(() => {
    fetchUsers();
  }, []);

  const fetchUsers = async () => {
    setLoading(true);
    try {
      const params = new URLSearchParams();
      if (searchValue) params.append('search', searchValue);
      if (roleFilter) params.append('role', roleFilter);
      if (statusFilter) params.append('status', statusFilter);

      const response = await api.get(`/api/admin/users?${params.toString()}`);
      setUsers(response.data || []);
    } catch (error) {
      console.error('Error fetching users:', error);
      message.error('Erreur lors du chargement des utilisateurs');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchUsers();
  }, [searchValue, roleFilter, statusFilter]);

  const handleDelete = async (userId) => {
    try {
      await api.delete(`/api/admin/users/${userId}`);
      message.success('Utilisateur supprimé avec succès');
      fetchUsers();
    } catch (error) {
      console.error('Error deleting user:', error);
      const errorMessage = error.response?.data?.error || 'Erreur lors de la suppression';
      message.error(errorMessage);
    }
  };

  const handleStatusChange = async (userId, newStatus) => {
    try {
      await api.put(`/api/admin/users/${userId}`, { isActive: newStatus === 'active' });
      message.success('Statut mis à jour avec succès');
      fetchUsers();
    } catch (error) {
      console.error('Error updating status:', error);
      const errorMessage = error.response?.data?.error || 'Erreur lors de la mise à jour du statut';
      message.error(errorMessage);
    }
  };

  const openRolesModal = (record) => {
    setSelectedUser(record);
    setRolesModalOpen(true);
    rolesForm.setFieldsValue({
      roles: (record.roles || []).filter((role) => role !== 'ROLE_USER'),
      adminUniverseIds: record.adminUniverseIds || [],
    });
  };

  const closeRolesModal = () => {
    setRolesModalOpen(false);
    setSelectedUser(null);
    rolesForm.resetFields();
  };

  const handleRolesSubmit = async () => {
    if (!selectedUser) {
      return;
    }

    try {
      const values = await rolesForm.validateFields();
      setRolesSaving(true);
      await api.put(`/api/admin/users/${selectedUser.id}`, {
        roles: values.roles || [],
        adminUniverseIds: values.adminUniverseIds || [],
      });
      message.success('Rôles mis à jour avec succès');
      closeRolesModal();
      fetchUsers();
    } catch (error) {
      if (error?.errorFields) {
        return;
      }
      message.error(error.response?.data?.error || 'Erreur lors de la mise à jour des rôles');
    } finally {
      setRolesSaving(false);
    }
  };

  const canEditPrivilegedRoles = (record) => {
    const hasPrivilegedRole = record.roles?.includes('ROLE_ADMIN') || record.roles?.includes('ROLE_SUPER_ADMIN');
    if (isCurrentUserSuperAdmin) {
      return true;
    }
    return !hasPrivilegedRole;
  };

  const columns = [
    {
      title: 'ID',
      dataIndex: 'id',
      key: 'id',
      width: 80,
    },
    {
      title: 'Pseudo',
      dataIndex: 'pseudo',
      key: 'pseudo',
    },
    {
      title: 'Email',
      dataIndex: 'email',
      key: 'email',
    },
    {
      title: 'Rôle',
      dataIndex: 'role',
      key: 'role',
      render: (role) => {
        const colors = {
          'super_admin': 'gold',
          'admin': 'red',
          'moderator': 'purple',
          'user': 'blue',
        };
        const labels = {
          'super_admin': 'Super administrateur',
          'admin': 'Administrateur',
          'moderator': 'Modérateur',
          'user': 'Utilisateur',
        };
        return <Tag color={colors[role] || 'default'}>{labels[role] || role}</Tag>;
      },
    },
    {
      title: 'Statut',
      dataIndex: 'status',
      key: 'status',
      render: (status, record) => {
        const menuItems = [
          {
            key: 'active',
            label: 'Actif',
            onClick: () => handleStatusChange(record.id, 'active'),
          },
          {
            key: 'inactive',
            label: 'Inactif',
            onClick: () => handleStatusChange(record.id, 'inactive'),
          },
        ];

        return (
          <Dropdown menu={{ items: menuItems }} trigger={['click']}>
            <Tag
              color={status === 'active' ? 'green' : 'default'}
              style={{ cursor: 'pointer' }}
            >
              {status === 'active' ? 'Actif' : 'Inactif'}
            </Tag>
          </Dropdown>
        );
      },
    },
    {
      title: 'Création factions',
      dataIndex: 'canCreateFaction',
      key: 'canCreateFaction',
      render: (canCreateFaction, record) => (
        <Dropdown
          menu={{
            items: [
              {
                key: 'allowed',
                label: 'Autorisé',
                onClick: async () => {
                  try {
                    await api.put(`/api/admin/users/${record.id}`, { canCreateFaction: true });
                    message.success('Permission faction activée');
                    fetchUsers();
                  } catch (error) {
                    message.error(error.response?.data?.error || 'Erreur lors de la mise à jour');
                  }
                },
              },
              {
                key: 'blocked',
                label: 'Non autorisé',
                onClick: async () => {
                  try {
                    await api.put(`/api/admin/users/${record.id}`, { canCreateFaction: false });
                    message.success('Permission faction retirée');
                    fetchUsers();
                  } catch (error) {
                    message.error(error.response?.data?.error || 'Erreur lors de la mise à jour');
                  }
                },
              },
            ],
          }}
          trigger={['click']}
        >
          <Tag color={canCreateFaction ? 'green' : 'default'} style={{ cursor: 'pointer' }}>
            {canCreateFaction ? 'Autorisé' : 'Non autorisé'}
          </Tag>
        </Dropdown>
      ),
    },
    {
      title: 'Date de création',
      dataIndex: 'createdAt',
      key: 'createdAt',
      render: (date) => date ? new Date(date).toLocaleDateString('fr-FR') : '-',
    },
    {
      title: 'Dernière connexion',
      dataIndex: 'lastLogin',
      key: 'lastLogin',
      render: (date) => date ? new Date(date).toLocaleDateString('fr-FR') : '-',
    },
    {
      title: 'Actions',
      key: 'actions',
      width: 220,
      render: (_, record) => (
        <Space>
          <Button
            type="text"
            icon={<EyeOutlined />}
            onClick={() => navigate(`/users/${record.id}`)}
            title="Détails"
          />
          <Button
            type="text"
            icon={<EditOutlined />}
            onClick={() => navigate(`/users/edit/${record.id}`)}
            title="Modifier"
          />
          <Tooltip title={canEditPrivilegedRoles(record) ? 'Gérer les rôles' : 'Seul un super admin peut modifier admin/super admin'}>
            <Button
              type="text"
              icon={<SafetyCertificateOutlined />}
              onClick={() => openRolesModal(record)}
              title="Rôles"
              disabled={!canEditPrivilegedRoles(record)}
            />
          </Tooltip>
          <Popconfirm
            title="Êtes-vous sûr de vouloir supprimer cet utilisateur ?"
            onConfirm={() => handleDelete(record.id)}
            okText="Oui"
            cancelText="Non"
          >
            <Button
              type="text"
              danger
              icon={<DeleteOutlined />}
              title="Supprimer"
            />
          </Popconfirm>
        </Space>
      ),
    },
  ];

  const handleSearch = (value) => {
    setSearchValue(value);
  };

  const handleResetFilters = () => {
    setSearchValue('');
    setStatusFilter(null);
    setRoleFilter(null);
  };

  const filters = [
    {
      placeholder: 'Statut',
      value: statusFilter,
      onChange: setStatusFilter,
      options: [
        { value: 'active', label: 'Actif' },
        { value: 'inactive', label: 'Inactif' },
      ],
    },
    {
      placeholder: 'Rôle',
      value: roleFilter,
      onChange: setRoleFilter,
      options: [
        { value: 'super_admin', label: 'Super administrateur' },
        { value: 'admin', label: 'Administrateur' },
        { value: 'moderator', label: 'Modérateur' },
        { value: 'user', label: 'Utilisateur' },
      ],
    },
  ];

  return (
    <ListPageLayout
      breadcrumbs={[{ label: 'Utilisateurs' }]}
      actions={[
        {
          label: 'Créer un utilisateur',
          onClick: () => navigate('/users/new'),
        },
      ]}
      searchPlaceholder="Rechercher un utilisateur (pseudo, email)..."
      searchValue={searchValue}
      onSearchChange={setSearchValue}
      onSearch={handleSearch}
      filters={filters}
      onResetFilters={handleResetFilters}
    >
      <Table
        dataSource={users}
        columns={columns}
        loading={loading}
        rowKey="id"
        pagination={{
          pageSize: 20,
          showSizeChanger: true,
          showTotal: (total) => `Total: ${total} utilisateurs`,
        }}
      />

      <Modal
        title={selectedUser ? `Rôles de ${selectedUser.pseudo}` : 'Gérer les rôles'}
        open={rolesModalOpen}
        onCancel={closeRolesModal}
        onOk={handleRolesSubmit}
        confirmLoading={rolesSaving}
        okText="Enregistrer"
        cancelText="Annuler"
      >
        <Form
          form={rolesForm}
          layout="vertical"
        >
          <Form.Item
            name="roles"
            label="Rôles"
            extra={!isCurrentUserSuperAdmin ? 'Les rôles admin/super admin sont réservés aux super administrateurs.' : undefined}
          >
            <Select
              mode="multiple"
              options={[
                { value: 'ROLE_MODERATOR', label: 'Modérateur' },
                ...(isCurrentUserSuperAdmin
                  ? [
                    { value: 'ROLE_ADMIN', label: 'Administrateur' },
                    { value: 'ROLE_SUPER_ADMIN', label: 'Super administrateur' },
                  ]
                  : []),
              ]}
            />
          </Form.Item>

          <Form.Item
            noStyle
            shouldUpdate={(prevValues, currentValues) =>
              prevValues.roles !== currentValues.roles
            }
          >
            {({ getFieldValue }) => {
              const selectedRoles = getFieldValue('roles') || [];
              const needsUniverses = selectedRoles.includes('ROLE_ADMIN') || selectedRoles.includes('ROLE_MODERATOR');
              if (!needsUniverses) {
                return null;
              }

              return (
                <Form.Item
                  name="adminUniverseIds"
                  label="Univers associés (admin / modérateur)"
                  tooltip="Cette liste limite les univers sur lesquels le rôle admin ou modérateur s'applique."
                >
                  <Select
                    mode="multiple"
                    options={universes.map((universe) => ({
                      value: universe.id,
                      label: universe.name,
                    }))}
                    placeholder="Sélectionnez les univers"
                  />
                </Form.Item>
              );
            }}
          </Form.Item>
        </Form>
      </Modal>
    </ListPageLayout>
  );
};

export default Users;
