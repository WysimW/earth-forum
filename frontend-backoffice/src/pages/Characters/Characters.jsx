import React, { useState, useEffect, useRef, useCallback } from 'react';
import { Table, Tag, Button, Space, message, Popconfirm, Dropdown, Avatar } from 'antd';
import { EditOutlined, DeleteOutlined, EyeOutlined, UserOutlined } from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';
import ListPageLayout from '../../components/ListPage/ListPageLayout';
import api from '../../services/api';
import { useUniverse } from '../../contexts/UniverseContext';

const Characters = () => {
  const [characters, setCharacters] = useState([]);
  const [universes, setUniverses] = useState([]);
  const [loading, setLoading] = useState(false);
  const [searchValue, setSearchValue] = useState('');
  const [statusFilter, setStatusFilter] = useState(null);
  const [universeFilter, setUniverseFilter] = useState(null);
  const lastFetchIdRef = useRef(0);
  const navigate = useNavigate();
  const { selectedUniverseId, universes: availableUniverses } = useUniverse();

  const fetchCharacters = useCallback(async () => {
    const fetchId = ++lastFetchIdRef.current;
    setLoading(true);
    try {
      const params = new URLSearchParams();
      if (searchValue) params.append('search', searchValue);
      if (statusFilter) params.append('status', statusFilter);
      if (universeFilter) params.append('universe_id', universeFilter);

      const response = await api.get(`/api/admin/characters?${params.toString()}`);
      if (fetchId !== lastFetchIdRef.current) {
        return;
      }
      setCharacters(response.data || []);
    } catch (error) {
      if (fetchId !== lastFetchIdRef.current) {
        return;
      }
      console.error('Error fetching characters:', error);
      message.error('Erreur lors du chargement des personnages');
    } finally {
      if (fetchId === lastFetchIdRef.current) {
        setLoading(false);
      }
    }
  }, [searchValue, statusFilter, universeFilter, selectedUniverseId]);

  useEffect(() => {
    setUniverses(availableUniverses);
  }, [availableUniverses]);

  useEffect(() => {
    fetchCharacters();
  }, [fetchCharacters]);

  const handleDelete = async (characterId) => {
    try {
      await api.delete(`/api/admin/characters/${characterId}`);
      message.success('Personnage supprimé avec succès');
      fetchCharacters();
    } catch (error) {
      console.error('Error deleting character:', error);
      const errorMessage = error.response?.data?.error || 'Erreur lors de la suppression';
      message.error(errorMessage);
    }
  };

  const handleStatusChange = async (characterId, newStatus) => {
    try {
      await api.put(`/api/admin/characters/${characterId}`, { status: newStatus });
      message.success('Statut mis à jour avec succès');
      fetchCharacters();
    } catch (error) {
      console.error('Error updating status:', error);
      const errorMessage = error.response?.data?.error || 'Erreur lors de la mise à jour du statut';
      message.error(errorMessage);
    }
  };

  const getStatusColor = (status) => {
    const colors = {
      'draft': 'default',
      'pending': 'orange',
      'validated': 'green',
      'rejected': 'red',
      'abandoned': 'gray',
      'editing': 'blue',
    };
    return colors[status] || 'default';
  };

  const getStatusLabel = (status) => {
    const labels = {
      'draft': 'Brouillon',
      'pending': 'En attente',
      'validated': 'Validé',
      'rejected': 'Rejeté',
      'abandoned': 'Abandonné',
      'editing': 'En édition',
    };
    return labels[status] || status;
  };

  const columns = [
    {
      title: 'ID',
      dataIndex: 'id',
      key: 'id',
      width: 80,
    },
    {
      title: 'Avatar',
      dataIndex: 'avatar',
      key: 'avatar',
      width: 80,
      render: (avatar) => (
        <Avatar
          src={avatar}
          icon={<UserOutlined />}
          size={40}
        />
      ),
    },
    {
      title: 'Nom',
      dataIndex: 'name',
      key: 'name',
    },
    {
      title: 'Pseudo',
      dataIndex: 'actualPseudo',
      key: 'actualPseudo',
      render: (pseudo) => pseudo || '-',
    },
    {
      title: 'Utilisateur',
      dataIndex: 'user_pseudo',
      key: 'user_pseudo',
      render: (pseudo, record) => (
        <div>
          <div>{pseudo || '-'}</div>
          {record.user_email && (
            <div style={{ fontSize: '12px', color: '#999' }}>{record.user_email}</div>
          )}
        </div>
      ),
    },
    {
      title: 'Univers',
      dataIndex: 'universe_name',
      key: 'universe_name',
      render: (name) => name ? <Tag color="blue">{name}</Tag> : <Tag>-</Tag>,
    },
    {
      title: 'Statut',
      dataIndex: 'status',
      key: 'status',
      render: (status, record) => {
        const menuItems = [
          {
            key: 'draft',
            label: 'Brouillon',
            onClick: () => handleStatusChange(record.id, 'draft'),
          },
          {
            key: 'pending',
            label: 'En attente',
            onClick: () => handleStatusChange(record.id, 'pending'),
          },
          {
            key: 'validated',
            label: 'Validé',
            onClick: () => handleStatusChange(record.id, 'validated'),
          },
          {
            key: 'rejected',
            label: 'Rejeté',
            onClick: () => handleStatusChange(record.id, 'rejected'),
          },
          {
            key: 'abandoned',
            label: 'Abandonné',
            onClick: () => handleStatusChange(record.id, 'abandoned'),
          },
          {
            key: 'editing',
            label: 'En édition',
            onClick: () => handleStatusChange(record.id, 'editing'),
          },
        ];

        return (
          <Dropdown menu={{ items: menuItems }} trigger={['click']}>
            <Tag
              color={getStatusColor(status)}
              style={{ cursor: 'pointer' }}
            >
              {getStatusLabel(status)}
            </Tag>
          </Dropdown>
        );
      },
    },
    {
      title: 'Date de création',
      dataIndex: 'createdAt',
      key: 'createdAt',
      render: (date) => date ? new Date(date).toLocaleDateString('fr-FR') : '-',
    },
    {
      title: 'Date de validation',
      dataIndex: 'validatedAt',
      key: 'validatedAt',
      render: (date) => date ? new Date(date).toLocaleDateString('fr-FR') : '-',
    },
    {
      title: 'Actions',
      key: 'actions',
      width: 150,
      render: (_, record) => (
        <Space>
          <Button
            type="text"
            icon={<EyeOutlined />}
            onClick={() => navigate(`/characters/detail/${record.id}`)}
            title="Détails"
          />
          <Button
            type="text"
            icon={<EditOutlined />}
            onClick={() => navigate(`/characters/edit/${record.id}`)}
            title="Modifier"
          />
          <Popconfirm
            title="Êtes-vous sûr de vouloir supprimer ce personnage ?"
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
    setUniverseFilter(null);
  };

  const filters = [
    {
      placeholder: 'Statut',
      value: statusFilter,
      onChange: setStatusFilter,
      options: [
        { value: 'draft', label: 'Brouillon' },
        { value: 'pending', label: 'En attente' },
        { value: 'validated', label: 'Validé' },
        { value: 'rejected', label: 'Rejeté' },
        { value: 'abandoned', label: 'Abandonné' },
        { value: 'editing', label: 'En édition' },
      ],
    },
    {
      placeholder: 'Univers',
      value: universeFilter,
      onChange: setUniverseFilter,
      options: universes.map((universe) => ({
        value: universe.id.toString(),
        label: universe.name,
      })),
    },
  ];

  return (
    <ListPageLayout
      breadcrumbs={[{ label: 'Personnages' }]}
      actions={[
        {
          label: 'Créer un personnage',
          onClick: () => navigate('/characters/new'),
        },
      ]}
      searchPlaceholder="Rechercher un personnage (nom, pseudo, utilisateur)..."
      searchValue={searchValue}
      onSearchChange={setSearchValue}
      onSearch={handleSearch}
      filters={filters}
      onResetFilters={handleResetFilters}
    >
      <Table
        dataSource={characters}
        columns={columns}
        loading={loading}
        rowKey="id"
        pagination={{
          pageSize: 20,
          showSizeChanger: true,
          showTotal: (total) => `Total: ${total} personnages`,
        }}
      />
    </ListPageLayout>
  );
};

export default Characters;

