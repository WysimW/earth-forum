import React, { useEffect, useState } from 'react';
import { Button, Space, Table, Tag, message } from 'antd';
import { EditOutlined, LockOutlined } from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';
import ListPageLayout from '../../components/ListPage/ListPageLayout';
import api from '../../services/api';

const Univers = () => {
  const navigate = useNavigate();
  const [universes, setUniverses] = useState([]);
  const [loading, setLoading] = useState(false);
  const [searchValue, setSearchValue] = useState('');

  const fetchUniverses = async () => {
    setLoading(true);
    try {
      const response = await api.get('/api/admin/universes');
      setUniverses(response.data?.universes || []);
    } catch (error) {
      message.error(error.response?.data?.error || 'Erreur lors du chargement des univers');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchUniverses();
  }, []);

  const filteredUniverses = universes.filter((universe) => {
    if (!searchValue.trim()) {
      return true;
    }
    const query = searchValue.toLowerCase();
    return (
      universe.name?.toLowerCase().includes(query)
      || universe.slug?.toLowerCase().includes(query)
      || universe.description?.toLowerCase().includes(query)
    );
  });

  const columns = [
    { title: 'ID', dataIndex: 'id', key: 'id', width: 80 },
    {
      title: 'Nom',
      dataIndex: 'name',
      key: 'name',
      render: (name, record) => (
        <Space>
          <span>{name}</span>
          {record.isSlugLocked && (
            <Tag icon={<LockOutlined />} color="default">Verrouillé</Tag>
          )}
        </Space>
      ),
    },
    { title: 'Slug', dataIndex: 'slug', key: 'slug' },
    {
      title: 'Titre forums',
      dataIndex: 'forumsTitle',
      key: 'forumsTitle',
      render: (value, record) => value || `Forums de ${record.name}`,
    },
    {
      title: 'Description',
      dataIndex: 'description',
      key: 'description',
      ellipsis: true,
      render: (value) => value || '-',
    },
    {
      title: 'Actions',
      key: 'actions',
      width: 100,
      render: (_, record) => (
        <Button
          type="text"
          icon={<EditOutlined />}
          onClick={() => navigate(`/univers/edit/${record.id}`)}
          title="Modifier"
        />
      ),
    },
  ];

  return (
    <ListPageLayout
      breadcrumbs={[{ label: 'Univers' }]}
      actions={[]}
      searchPlaceholder="Rechercher un univers..."
      searchValue={searchValue}
      onSearchChange={setSearchValue}
      onSearch={setSearchValue}
      filters={[]}
      onResetFilters={() => setSearchValue('')}
    >
      <Table
        rowKey="id"
        dataSource={filteredUniverses}
        columns={columns}
        loading={loading}
        pagination={{
          pageSize: 20,
          showTotal: (total) => `Total: ${total} univers`,
        }}
      />
    </ListPageLayout>
  );
};

export default Univers;
