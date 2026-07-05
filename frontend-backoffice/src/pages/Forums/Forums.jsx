import React, { useState, useEffect } from 'react';
import { Table, Tag, Button, Space, message, Dropdown, Switch, Card, Row, Col, Statistic, Alert, Popconfirm } from 'antd';
import { EditOutlined, DeleteOutlined, HolderOutlined, PlusOutlined, EditFilled, ApartmentOutlined, BranchesOutlined } from '@ant-design/icons';
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
import { useUniverse } from '../../contexts/UniverseContext';
import './Forums.css';

const TYPE_COLORS = {
  important: 'red',
  player_platform: 'geekblue',
  roleplay: 'purple',
  hrp: 'cyan',
};

const TYPE_LABELS = {
  important: 'Important',
  player_platform: 'Plateforme joueur',
  roleplay: 'Roleplay',
  hrp: 'HRP',
};

const STATUS_COLORS = {
  open: 'green',
  closed: 'orange',
  archived: 'default',
};

const STATUS_LABELS = {
  open: 'Ouvert',
  closed: 'Fermé',
  archived: 'Archivé',
};

const TYPE_ORDER = {
  important: 0,
  player_platform: 1,
  roleplay: 2,
  hrp: 3,
};

const SORT_OPTIONS = [
  { value: 'position', label: 'Ordre manuel (Univers > Type > Ordre)' },
  { value: 'universeType', label: 'Univers > Type > Nom' },
  { value: 'name', label: 'Nom (A-Z)' },
];

// Composant de ligne draggable (utilisé en mode édition)
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

const Forums = () => {
  const navigate = useNavigate();
  const { selectedUniverseId, universes: availableUniverses } = useUniverse();
  const [forums, setForums] = useState([]);
  const [universes, setUniverses] = useState([]);
  const [loading, setLoading] = useState(false);
  const [searchValue, setSearchValue] = useState('');
  const [universeFilter, setUniverseFilter] = useState(null);
  const [typeFilter, setTypeFilter] = useState(null);
  const [organizedForums, setOrganizedForums] = useState([]);
  const [treeForums, setTreeForums] = useState([]);
  const [editMode, setEditMode] = useState(false);
  const [hideSubforums, setHideSubforums] = useState(false);
  const [viewMode, setViewMode] = useState('all');
  const [sortMode, setSortMode] = useState('position');

  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  useEffect(() => {
    fetchForums();
  }, []);

  useEffect(() => {
    setUniverses(availableUniverses);
  }, [availableUniverses]);

  useEffect(() => {
    fetchForums();
  }, [selectedUniverseId]);

  const fetchForums = async () => {
    setLoading(true);
    try {
      const response = await api.get('/api/admin/forums');
      setForums(response.data || []);
    } catch (error) {
      console.error('Error fetching forums:', error);
      message.error('Erreur lors du chargement des forums');
    } finally {
      setLoading(false);
    }
  };

  // Résout l'univers effectif d'un forum (direct ou hérité du parent)
  const getEffectiveUniverseId = React.useCallback((forum, forumsById, visited = new Set()) => {
    if (!forum) {
      return null;
    }

    if (forum.universe_id) {
      return forum.universe_id;
    }

    const parentId = forum.parent_forum_id;
    if (!parentId || visited.has(parentId)) {
      return null;
    }

    visited.add(parentId);
    return getEffectiveUniverseId(forumsById.get(parentId), forumsById, visited);
  }, []);

  const getEffectiveUniverseName = React.useCallback((forum, forumsById, universesById) => {
    const effectiveUniverseId = getEffectiveUniverseId(forum, forumsById);
    if (!effectiveUniverseId) {
      return '-';
    }
    return universesById.get(effectiveUniverseId) || forum.universe_name || '-';
  }, [getEffectiveUniverseId]);


  const sortForums = React.useCallback((forumsList, forumsById, mode) => {
    const universesById = new Map(universes.map((universe) => [universe.id, universe.name]));
    const withMetadata = forumsList.map((forum) => ({
      ...forum,
      _effectiveUniverseId: getEffectiveUniverseId(forum, forumsById),
      _effectiveUniverseName: getEffectiveUniverseName(forum, forumsById, universesById),
    }));

    return withMetadata.sort((a, b) => {
      if (mode === 'name') {
        return (a.name || '').localeCompare(b.name || '', 'fr', { sensitivity: 'base' });
      }

      if (mode === 'universeType') {
        const byUniverse = (a._effectiveUniverseName || '-').localeCompare(
          b._effectiveUniverseName || '-',
          'fr',
          { sensitivity: 'base' }
        );
        if (byUniverse !== 0) {
          return byUniverse;
        }

        const byType = (TYPE_ORDER[a.type] ?? 99) - (TYPE_ORDER[b.type] ?? 99);
        if (byType !== 0) {
          return byType;
        }

        return (a.name || '').localeCompare(b.name || '', 'fr', { sensitivity: 'base' });
      }

      const byUniverse = (a._effectiveUniverseName || '-').localeCompare(
        b._effectiveUniverseName || '-',
        'fr',
        { sensitivity: 'base' }
      );
      if (byUniverse !== 0) {
        return byUniverse;
      }

      const byType = (TYPE_ORDER[a.type] ?? 99) - (TYPE_ORDER[b.type] ?? 99);
      if (byType !== 0) {
        return byType;
      }

      const byPosition = (a.position ?? 0) - (b.position ?? 0);
      if (byPosition !== 0) {
        return byPosition;
      }
      return (a.name || '').localeCompare(b.name || '', 'fr', { sensitivity: 'base' });
    });
  }, [getEffectiveUniverseId, getEffectiveUniverseName, universes]);

  const organizeForums = React.useCallback((forumsList, mode = 'position') => {
    const forumsById = new Map(forumsList.map((forum) => [forum.id, forum]));
    const sortedForums = sortForums(forumsList, forumsById, mode);
    const parents = sortedForums.filter((f) => !f.parent_forum_id);
    const children = sortedForums.filter((f) => f.parent_forum_id);
    const organized = [];

    parents.forEach((parent) => {
      organized.push({ ...parent, level: 0 });
      children
        .filter((child) => child.parent_forum_id === parent.id)
        .forEach((subforum) => {
          organized.push({ ...subforum, level: 1 });
        });
    });

    children
      .filter((child) => !parents.some((parent) => parent.id === child.parent_forum_id))
      .forEach((orphan) => {
        organized.push({ ...orphan, level: 1 });
      });

    return organized;
  }, [sortForums]);

  const buildTree = React.useCallback((forumsList, mode = 'position') => {
    const forumsById = new Map(forumsList.map((forum) => [forum.id, forum]));
    const sortedForums = sortForums(forumsList, forumsById, mode);
    const byParent = new Map();

    sortedForums.forEach((forum) => {
      const parentId = forum.parent_forum_id ?? null;
      if (!byParent.has(parentId)) {
        byParent.set(parentId, []);
      }
      byParent.get(parentId).push(forum);
    });

    const attachChildren = (forum, level = 0) => {
      const children = (byParent.get(forum.id) || []).map((child) => attachChildren(child, level + 1));
      return {
        ...forum,
        level,
        key: forum.id,
        children,
      };
    };

    const roots = byParent.get(null) || [];
    const rootNodes = roots.map((root) => attachChildren(root, 0));

    const orphanNodes = sortedForums
      .filter((forum) => forum.parent_forum_id && !forumsById.has(forum.parent_forum_id))
      .map((forum) => attachChildren(forum, 0));

    return [...rootNodes, ...orphanNodes];
  }, [sortForums]);

  const forumStats = React.useMemo(() => {
    const parentCount = forums.filter((forum) => !forum.parent_forum_id).length;
    const subforumCount = forums.length - parentCount;
    return {
      parentCount,
      subforumCount,
    };
  }, [forums]);

  // Filtrer les forums selon les critères
  const filteredForums = React.useMemo(() => {
    const forumsById = new Map(forums.map((forum) => [forum.id, forum]));

    return forums.filter((forum) => {
      // Filtre de recherche
      const matchesSearch = !searchValue || 
        forum.name?.toLowerCase().includes(searchValue.toLowerCase()) ||
        forum.description?.toLowerCase().includes(searchValue.toLowerCase());
      
      // Filtre par univers
      const effectiveUniverseId = getEffectiveUniverseId(forum, forumsById);
      const matchesUniverse = !universeFilter ||
        (effectiveUniverseId && effectiveUniverseId.toString() === universeFilter.toString());
      
      // Filtre par type
      const matchesType = !typeFilter || forum.type === typeFilter;

      // Filtre d'affichage
      const matchesView =
        viewMode === 'all' ||
        (viewMode === 'parents' && !forum.parent_forum_id) ||
        (viewMode === 'subforums' && !!forum.parent_forum_id);

      return matchesSearch && matchesUniverse && matchesType && matchesView;
    });
  }, [forums, searchValue, universeFilter, typeFilter, viewMode, getEffectiveUniverseId]);

  // Organiser les forums filtrés (mode édition = liste aplatie draggable)
  useEffect(() => {
    const organized = organizeForums(filteredForums, sortMode);
    setOrganizedForums(organized);
  }, [filteredForums, organizeForums, sortMode]);

  // Vue lecture: structure hiérarchique
  useEffect(() => {
    setTreeForums(buildTree(filteredForums, sortMode));
  }, [filteredForums, sortMode, buildTree]);

  useEffect(() => {
    if (editMode) {
      setSortMode('position');
    } else {
      setHideSubforums(false);
    }
  }, [editMode]);

  const displayedOrganizedForums = React.useMemo(() => {
    if (!editMode || !hideSubforums) {
      return organizedForums;
    }
    return organizedForums.filter((forum) => forum.level === 0);
  }, [editMode, hideSubforums, organizedForums]);

  const savePositions = async (positions) => {
    try {
      await api.post('/api/admin/forums/update-positions', positions);
      message.success('Ordre des forums sauvegardé');
      // Recharger les forums pour avoir les données à jour
      await fetchForums();
    } catch (error) {
      console.error('Error saving positions:', error);
      message.error('Erreur lors de la sauvegarde de l\'ordre');
      // Recharger quand même pour restaurer l'état précédent
      fetchForums();
    }
  };

  const handleTypeChange = async (forumId, newType) => {
    try {
      await api.put(`/api/admin/forums/${forumId}`, { type: newType });
      message.success('Type du forum mis à jour');
      fetchForums();
    } catch (error) {
      console.error('Error updating forum type:', error);
      message.error('Erreur lors de la mise à jour du type');
    }
  };

  const handleStatusChange = async (forumId, newStatus) => {
    try {
      await api.put(`/api/admin/forums/${forumId}`, { status: newStatus });
      message.success('Statut du forum mis à jour');
      fetchForums();
    } catch (error) {
      console.error('Error updating forum status:', error);
      message.error('Erreur lors de la mise à jour du statut');
    }
  };

  const handleCreateForum = (parentId = null) => {
    if (parentId) {
      navigate(`/forums/new?parentId=${parentId}`);
    } else {
      navigate('/forums/new');
    }
  };

  const handleDelete = async (forumId) => {
    try {
      await api.delete(`/api/admin/forums/${forumId}`);
      message.success('Forum supprimé avec succès');
      fetchForums();
    } catch (error) {
      console.error('Error deleting forum:', error);
      const errorMessage = error.response?.data?.error || error.response?.data?.message || 'Erreur lors de la suppression du forum';
      message.error(errorMessage);
    }
  };

  const handleDragEnd = async (event) => {
    const { active, over } = event;
    if (!over) {
      return;
    }

    if (active.id !== over?.id) {
      if (hideSubforums) {
        const parents = organizedForums.filter((forum) => forum.level === 0);
        const oldIndex = parents.findIndex((item) => item.id === active.id);
        const newIndex = parents.findIndex((item) => item.id === over.id);

        if (oldIndex === -1 || newIndex === -1) {
          return;
        }

        const reorderedParents = arrayMove(parents, oldIndex, newIndex);
        const children = organizedForums.filter((forum) => forum.level > 0);
        const rebuilt = [];

        reorderedParents.forEach((parent) => {
          rebuilt.push(parent);
          children
            .filter((child) => child.parent_forum_id === parent.id)
            .forEach((subforum) => rebuilt.push(subforum));
        });

        children
          .filter((child) => !reorderedParents.some((parent) => parent.id === child.parent_forum_id))
          .forEach((orphan) => rebuilt.push(orphan));

        const updatedPositions = rebuilt.map((item, index) => ({
          id: item.id,
          position: index,
        }));

        await savePositions(updatedPositions);
        setOrganizedForums(rebuilt);
        return;
      }

      const allFiltered = displayedOrganizedForums;
      const oldGlobalIndex = allFiltered.findIndex((item) => item.id === active.id);
      const newGlobalIndex = allFiltered.findIndex((item) => item.id === over.id);
      
      if (oldGlobalIndex === -1 || newGlobalIndex === -1) {
        return;
      }
      
      const reordered = arrayMove(allFiltered, oldGlobalIndex, newGlobalIndex);
      
      const updatedPositions = reordered.map((item, index) => ({
        id: item.id,
        position: index,
      }));
      
      await savePositions(updatedPositions);
      setOrganizedForums(reordered);
    }
  };

  const forumsById = React.useMemo(() => new Map(forums.map((forum) => [forum.id, forum])), [forums]);
  const universesById = React.useMemo(() => new Map(universes.map((universe) => [universe.id, universe.name])), [universes]);

  const columns = [
    {
      key: 'sort',
      width: 50,
      render: () => <HolderOutlined style={{ cursor: 'grab', color: '#999' }} />,
    },
    {
      title: 'Ordre',
      dataIndex: 'position',
      key: 'position',
      width: 110,
      render: (position, _, index) => <span style={{ whiteSpace: 'nowrap' }}>{position ?? index}</span>,
    },
    {
      title: 'Nom',
      dataIndex: 'name',
      key: 'name',
      render: (name, record) => (
        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
          {record.level > 0 ? <BranchesOutlined style={{ color: '#8c8c8c' }} /> : <ApartmentOutlined style={{ color: '#1677ff' }} />}
          <strong>{name}</strong>
          {(record.children?.length > 0 || record.subforums_count > 0) && (
            <Tag color="default" style={{ marginLeft: 8 }}>
              {record.children?.length ?? record.subforums_count} sous-forum{(record.children?.length ?? record.subforums_count) > 1 ? 's' : ''}
            </Tag>
          )}
        </div>
      ),
    },
    {
      title: 'Description',
      dataIndex: 'description',
      key: 'description',
      ellipsis: true,
    },
    {
      title: 'Univers',
      dataIndex: 'universe_name',
      key: 'universe_name',
      render: (name, record) => {
        const universeName = name || getEffectiveUniverseName(record, forumsById, universesById);
        return universeName !== '-' ? <Tag color="blue">{universeName}</Tag> : <Tag>-</Tag>;
      },
    },
    {
      title: 'Type',
      dataIndex: 'type',
      key: 'type',
      render: (type, record) => {
        const menuItems = [
          {
            key: 'important',
            label: 'Important',
            onClick: () => handleTypeChange(record.id, 'important'),
          },
          {
            key: 'player_platform',
            label: 'Plateforme joueur',
            onClick: () => handleTypeChange(record.id, 'player_platform'),
          },
          {
            key: 'roleplay',
            label: 'Roleplay',
            onClick: () => handleTypeChange(record.id, 'roleplay'),
          },
          {
            key: 'hrp',
            label: 'HRP',
            onClick: () => handleTypeChange(record.id, 'hrp'),
          },
        ];
        
        return (
          <Dropdown menu={{ items: menuItems }} trigger={['click']}>
            <Tag 
              color={TYPE_COLORS[type] || 'default'}
              style={{ cursor: 'pointer' }}
            >
              {TYPE_LABELS[type] || type}
            </Tag>
          </Dropdown>
        );
      },
    },
    {
      title: 'Statut',
      dataIndex: 'status',
      key: 'status',
      render: (status, record) => {
        const menuItems = [
          {
            key: 'open',
            label: 'Ouvert',
            onClick: () => handleStatusChange(record.id, 'open'),
          },
          {
            key: 'closed',
            label: 'Fermé',
            onClick: () => handleStatusChange(record.id, 'closed'),
          },
          {
            key: 'archived',
            label: 'Archivé',
            onClick: () => handleStatusChange(record.id, 'archived'),
          },
        ];

        return (
          <Dropdown menu={{ items: menuItems }} trigger={['click']}>
            <Tag
              color={STATUS_COLORS[status] || 'default'}
              style={{ cursor: 'pointer' }}
            >
              {STATUS_LABELS[status] || status || 'Inconnu'}
            </Tag>
          </Dropdown>
        );
      },
    },
    {
      title: 'Actions',
      key: 'actions',
      width: 150,
      render: (_, record) => (
        <Space>
          <Button
            type="text"
            icon={<PlusOutlined />}
            onClick={() => handleCreateForum(record.id)}
            title="Créer un sous-forum"
          />
              <Button
                type="text"
                icon={<EditOutlined />}
                onClick={() => navigate(`/forums/edit/${record.id}`)}
                title="Modifier"
              />
          <Popconfirm
            title="Supprimer ce forum ?"
            description="Cette action est irréversible."
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
    setUniverseFilter(null);
    setTypeFilter(null);
    setViewMode('all');
    setSortMode('position');
  };

  const filters = [
    {
      placeholder: 'Univers',
      value: universeFilter,
      onChange: setUniverseFilter,
      options: universes.map((universe) => ({
        value: universe.id.toString(),
        label: universe.name,
      })),
    },
    {
      placeholder: 'Type',
      value: typeFilter,
      onChange: setTypeFilter,
      options: [
        { value: 'important', label: 'Important' },
        { value: 'player_platform', label: 'Plateforme joueur' },
        { value: 'roleplay', label: 'Roleplay' },
        { value: 'hrp', label: 'HRP' },
      ],
    },
    {
      placeholder: 'Affichage',
      value: viewMode,
      onChange: setViewMode,
      allowClear: false,
      options: [
        { value: 'all', label: 'Tous' },
        { value: 'parents', label: 'Forums parents' },
        { value: 'subforums', label: 'Sous-forums' },
      ],
    },
    {
      placeholder: 'Tri',
      value: sortMode,
      onChange: setSortMode,
      allowClear: false,
      options: SORT_OPTIONS,
      width: 220,
    },
  ];

  const displayColumns = editMode
    ? columns
    : columns.filter((column) => column.key !== 'sort');

  return (
    <ListPageLayout
      breadcrumbs={[{ label: 'Forums' }]}
      actions={[
        {
          label: 'Créer un forum',
          onClick: () => handleCreateForum(null),
        },
      ]}
      searchPlaceholder="Rechercher un forum..."
      searchValue={searchValue}
      onSearchChange={setSearchValue}
      onSearch={handleSearch}
      filters={filters}
      onResetFilters={handleResetFilters}
      extraContent={
        <Space>
          <span>Mode édition:</span>
          <Switch
            checked={editMode}
            onChange={setEditMode}
            checkedChildren={<EditFilled />}
            unCheckedChildren="OFF"
          />
          {editMode && (
            <>
              <span>Masquer sous-forums:</span>
              <Switch
                checked={hideSubforums}
                onChange={setHideSubforums}
                checkedChildren="ON"
                unCheckedChildren="OFF"
              />
            </>
          )}
        </Space>
      }
    >
      <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
        <Col xs={24} md={8}>
          <Card size="small">
            <Statistic title="Total visible" value={filteredForums.length} />
          </Card>
        </Col>
        <Col xs={24} md={8}>
          <Card size="small">
            <Statistic title="Forums parents" value={forumStats.parentCount} />
          </Card>
        </Col>
        <Col xs={24} md={8}>
          <Card size="small">
            <Statistic title="Sous-forums" value={forumStats.subforumCount} />
          </Card>
        </Col>
      </Row>

      {editMode ? (
        <>
          <Alert
            type="info"
            showIcon
            style={{ marginBottom: 12 }}
            message="Mode édition actif"
            description="Glissez-déposez les lignes pour ajuster l'ordre global, puis l'ordre est sauvegardé automatiquement."
          />
          <DndContext
            sensors={sensors}
            collisionDetection={closestCenter}
            onDragEnd={handleDragEnd}
          >
            <SortableContext
              items={displayedOrganizedForums.map((f) => f.id)}
              strategy={verticalListSortingStrategy}
            >
              <Table
                components={{
                  body: {
                    row: SortableRow,
                  },
                }}
                dataSource={displayedOrganizedForums}
                columns={displayColumns}
                loading={loading}
                rowKey="id"
                pagination={false}
                rowClassName={(record) => (record.level > 0 ? 'subforum-row' : '')}
                scroll={{ y: 'calc(100vh - 420px)' }}
              />
            </SortableContext>
          </DndContext>
        </>
      ) : (
        <Table
          dataSource={treeForums}
          columns={displayColumns}
          loading={loading}
          rowKey="id"
          pagination={{
            pageSize: 20,
            showSizeChanger: true,
            showTotal: (total) => `Total: ${total} forums`,
          }}
          expandable={{
            defaultExpandAllRows: true,
            expandIconColumnIndex: 1,
          }}
          rowClassName={(record) => (record.level > 0 ? 'subforum-row' : '')}
        />
      )}
    </ListPageLayout>
  );
};

export default Forums;
