import React, { useEffect, useState } from 'react';
import { Button, Popconfirm, Space, Table, Tag, message } from 'antd';
import { DeleteOutlined, EditOutlined } from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';
import ListPageLayout from '../../components/ListPage/ListPageLayout';
import api from '../../services/api';
import { useUniverse } from '../../contexts/UniverseContext';

const Factions = () => {
  const navigate = useNavigate();
  const [factions, setFactions] = useState([]);
  const [loading, setLoading] = useState(false);
  const [searchValue, setSearchValue] = useState('');
  const { selectedUniverseId } = useUniverse();

  const fetchFactions = async () => {
    setLoading(true);
    try {
      const params = new URLSearchParams();
      if (searchValue) params.append('search', searchValue);
      const response = await api.get(`/api/admin/factions?${params.toString()}`);
      setFactions(response.data?.factions || []);
    } catch (error) {
      message.error(error.response?.data?.error || 'Erreur lors du chargement des factions');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchFactions();
  }, [searchValue, selectedUniverseId]);

  const handleDelete = async (factionId) => {
    try {
      await api.delete(`/api/admin/factions/${factionId}`);
      message.success('Faction supprimée avec succès');
      fetchFactions();
    } catch (error) {
      message.error(error.response?.data?.error || 'Erreur lors de la suppression');
    }
  };

  const columns = [
    { title: 'ID', dataIndex: 'id', key: 'id', width: 80 },
    { title: 'Nom', dataIndex: 'name', key: 'name' },
    {
      title: 'Univers',
      dataIndex: ['universe', 'name'],
      key: 'universe',
      render: (value) => value || '-',
    },
    {
      title: 'Chef de faction',
      dataIndex: ['founder', 'pseudo'],
      key: 'founder',
      render: (value) => value || '-',
    },
    {
      title: 'Statut',
      dataIndex: 'status',
      key: 'status',
      render: (status) => (
        <Tag color={status === 'open' ? 'green' : 'red'}>
          {status === 'open' ? 'Ouverte' : 'Fermée'}
        </Tag>
      ),
    },
    {
      title: 'Membres',
      key: 'members',
      render: (_, record) => `${record.membersCount?.characters || 0} persos / ${record.membersCount?.npcs || 0} PNJ`,
    },
    {
      title: 'Actions',
      key: 'actions',
      render: (_, record) => (
        <Space>
          <Button
            type="text"
            icon={<EditOutlined />}
            onClick={() => navigate(`/factions/edit/${record.id}`)}
            title="Modifier"
          />
          <Popconfirm
            title="Supprimer cette faction ?"
            okText="Oui"
            cancelText="Non"
            onConfirm={() => handleDelete(record.id)}
          >
            <Button type="text" danger icon={<DeleteOutlined />} title="Supprimer" />
          </Popconfirm>
        </Space>
      ),
    },
  ];

  return (
    <ListPageLayout
      breadcrumbs={[{ label: 'Factions' }]}
      actions={[{ label: 'Créer une faction', onClick: () => navigate('/factions/new') }]}
      searchPlaceholder="Rechercher une faction..."
      searchValue={searchValue}
      onSearchChange={setSearchValue}
      onSearch={setSearchValue}
      filters={[]}
      onResetFilters={() => setSearchValue('')}
    >
      <Table
        rowKey="id"
        dataSource={factions}
        columns={columns}
        loading={loading}
        pagination={{
          pageSize: 20,
          showTotal: (total) => `Total: ${total} factions`,
        }}
      />
    </ListPageLayout>
  );
};

export default Factions;

