import React, { useState, useEffect } from 'react';
import {
  Table,
  Tag,
  Button,
  Space,
  message,
  Switch,
  Alert,
  Popconfirm,
} from 'antd';
import {
  EditOutlined,
  DeleteOutlined,
  HolderOutlined,
  EditFilled,
} from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';
import {
  DndContext,
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
} from '@dnd-kit/core';
import {
  arrayMove,
  SortableContext,
  sortableKeyboardCoordinates,
  useSortable,
  verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import ListPageLayout from '../../components/ListPage/ListPageLayout';
import api from '../../services/api';
import './Categories.css';

const SortableRow = ({ children, ...props }) => {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({
    id: props['data-row-key'],
  });

  const style = {
    ...props.style,
    transform: CSS.Transform.toString(transform),
    transition,
    cursor: 'move',
    ...(isDragging ? { position: 'relative', zIndex: 9999 } : {}),
  };

  return (
    <tr {...props} ref={setNodeRef} style={style} {...attributes}>
      {React.Children.map(children, (child) => {
        if (child.key === 'sort') {
          return React.cloneElement(child, {
            children: (
              <div {...listeners} style={{ cursor: 'grab', display: 'inline-block' }}>
                <HolderOutlined style={{ color: '#999' }} />
              </div>
            ),
          });
        }
        return child;
      })}
    </tr>
  );
};

const Categories = () => {
  const navigate = useNavigate();
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(false);
  const [searchValue, setSearchValue] = useState('');
  const [editMode, setEditMode] = useState(false);

  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  const fetchCategories = async () => {
    setLoading(true);
    try {
      const response = await api.get('/api/admin/categories');
      setCategories(response.data?.categories || []);
    } catch (error) {
      message.error(error.response?.data?.error || 'Erreur lors du chargement des catégories');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCategories();
  }, []);

  const filteredCategories = React.useMemo(() => {
    if (!searchValue) {
      return categories;
    }

    const query = searchValue.toLowerCase();
    return categories.filter((category) =>
      category.name?.toLowerCase().includes(query) ||
      category.description?.toLowerCase().includes(query) ||
      category.type_name?.toLowerCase().includes(query)
    );
  }, [categories, searchValue]);

  const saveHomeOrders = async (items) => {
    try {
      const payload = items.map((item, index) => ({
        id: item.id,
        home_order: index,
      }));
      await api.post('/api/admin/categories/update-home-orders', payload);
      message.success('Ordre d\'affichage sauvegardé');
      await fetchCategories();
    } catch (error) {
      message.error(error.response?.data?.error || 'Erreur lors de la sauvegarde de l\'ordre');
      fetchCategories();
    }
  };

  const handleDelete = async (categoryId) => {
    try {
      await api.delete(`/api/admin/categories/${categoryId}`);
      message.success('Catégorie supprimée avec succès');
      fetchCategories();
    } catch (error) {
      message.error(error.response?.data?.error || 'Erreur lors de la suppression');
    }
  };

  const handleDragEnd = async (event) => {
    const { active, over } = event;
    if (!over || active.id === over.id) {
      return;
    }

    const oldIndex = filteredCategories.findIndex((item) => item.id === active.id);
    const newIndex = filteredCategories.findIndex((item) => item.id === over.id);

    if (oldIndex === -1 || newIndex === -1) {
      return;
    }

    const reordered = arrayMove(filteredCategories, oldIndex, newIndex);
    setCategories((prev) => {
      const ids = new Set(reordered.map((item) => item.id));
      const others = prev.filter((item) => !ids.has(item.id));
      return [...reordered, ...others];
    });
    await saveHomeOrders(reordered);
  };

  const columns = [
    {
      key: 'sort',
      width: 50,
      render: () => <HolderOutlined style={{ cursor: 'grab', color: '#999' }} />,
    },
    {
      title: 'Home order',
      dataIndex: 'home_order',
      key: 'home_order',
      width: 110,
      render: (homeOrder) => homeOrder ?? '-',
    },
    {
      title: 'Nom',
      dataIndex: 'name',
      key: 'name',
      render: (name) => <strong>{name}</strong>,
    },
    {
      title: 'Description',
      dataIndex: 'description',
      key: 'description',
      ellipsis: true,
      render: (description) => description || '-',
    },
    {
      title: 'Type',
      dataIndex: 'type_name',
      key: 'type_name',
      render: (typeName) => (typeName ? <Tag color="blue">{typeName}</Tag> : '-'),
    },
    {
      title: 'Forums',
      dataIndex: 'forums_count',
      key: 'forums_count',
      width: 90,
      render: (count) => count ?? 0,
    },
    {
      title: 'Slug',
      dataIndex: 'slug',
      key: 'slug',
      ellipsis: true,
      render: (slug) => <Tag>{slug}</Tag>,
    },
    {
      title: 'Actions',
      key: 'actions',
      width: 120,
      render: (_, record) => (
        <Space>
          <Button
            type="text"
            icon={<EditOutlined />}
            onClick={() => navigate(`/forum-categories/edit/${record.id}`)}
            title="Modifier"
          />
          <Popconfirm
            title="Supprimer cette catégorie ?"
            description={
              record.forums_count > 0
                ? 'Cette catégorie contient des forums et ne peut pas être supprimée.'
                : 'Cette action est irréversible.'
            }
            onConfirm={() => handleDelete(record.id)}
            okText="Oui"
            cancelText="Non"
            disabled={record.forums_count > 0}
          >
            <Button
              type="text"
              danger
              icon={<DeleteOutlined />}
              title="Supprimer"
              disabled={record.forums_count > 0}
            />
          </Popconfirm>
        </Space>
      ),
    },
  ];

  const displayColumns = editMode
    ? columns
    : columns.filter((column) => column.key !== 'sort');

  return (
    <ListPageLayout
      breadcrumbs={[
        { label: 'Forums', link: '/forums' },
        { label: 'Catégories' },
      ]}
      actions={[
        {
          label: 'Créer une catégorie',
          onClick: () => navigate('/forum-categories/new'),
        },
      ]}
      searchPlaceholder="Rechercher une catégorie..."
      searchValue={searchValue}
      onSearchChange={setSearchValue}
      onSearch={setSearchValue}
      filters={[]}
      onResetFilters={() => setSearchValue('')}
      extraContent={
        <Space>
          <span>Mode édition (ordre) :</span>
          <Switch
            checked={editMode}
            onChange={setEditMode}
            checkedChildren={<EditFilled />}
            unCheckedChildren="OFF"
          />
        </Space>
      }
    >
      {editMode ? (
        <>
          <Alert
            type="info"
            showIcon
            style={{ marginBottom: 12 }}
            message="Mode édition actif"
            description="Glissez-déposez les lignes pour modifier le home order affiché sur la page d'accueil du forum."
          />
          <DndContext
            sensors={sensors}
            collisionDetection={closestCenter}
            onDragEnd={handleDragEnd}
          >
            <SortableContext
              items={filteredCategories.map((category) => category.id)}
              strategy={verticalListSortingStrategy}
            >
              <Table
                components={{
                  body: {
                    row: SortableRow,
                  },
                }}
                dataSource={filteredCategories}
                columns={displayColumns}
                loading={loading}
                rowKey="id"
                pagination={false}
                scroll={{ y: 'calc(100vh - 420px)' }}
              />
            </SortableContext>
          </DndContext>
        </>
      ) : (
        <Table
          dataSource={filteredCategories}
          columns={displayColumns}
          loading={loading}
          rowKey="id"
          pagination={{
            pageSize: 20,
            showTotal: (total) => `Total: ${total} catégories`,
          }}
        />
      )}
    </ListPageLayout>
  );
};

export default Categories;
