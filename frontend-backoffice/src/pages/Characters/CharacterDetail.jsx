import React, { useState, useEffect } from 'react';
import { Card, Descriptions, Tag, Button, Space, message, Spin, Avatar } from 'antd';
import { EditOutlined, ArrowLeftOutlined, UserOutlined, MergeCellsOutlined, UserAddOutlined } from '@ant-design/icons';
import { useNavigate, useParams } from 'react-router-dom';
import api from '../../services/api';
import AssignUserModal from './AssignUserModal';
import CharacterMergeModal from './CharacterMergeModal';
import './CharacterDetail.css';

const CharacterDetail = () => {
  const navigate = useNavigate();
  const { id } = useParams();
  const [loading, setLoading] = useState(false);
  const [character, setCharacter] = useState(null);
  const [assignModalOpen, setAssignModalOpen] = useState(false);
  const [mergeModalOpen, setMergeModalOpen] = useState(false);

  useEffect(() => {
    fetchCharacterData();
  }, [id]);

  const fetchCharacterData = async () => {
    setLoading(true);
    try {
      const response = await api.get(`/api/admin/characters/${id}`);
      setCharacter(response.data);
    } catch (error) {
      console.error('Error fetching character data:', error);
      message.error('Erreur lors du chargement du personnage');
      navigate('/characters');
    } finally {
      setLoading(false);
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

  const handleMerged = (result) => {
    if (result?.survivorId && String(result.survivorId) !== String(id)) {
      navigate(`/characters/${result.survivorId}`);
      return;
    }
    fetchCharacterData();
  };

  if (loading) {
    return (
      <div style={{ textAlign: 'center', padding: '50px' }}>
        <Spin size="large" />
      </div>
    );
  }

  if (!character) {
    return null;
  }

  return (
    <div className="character-detail-page">
      <Card
        title={
          <Space>
            <Button
              icon={<ArrowLeftOutlined />}
              onClick={() => navigate('/characters')}
            >
              Retour
            </Button>
            <span>Détails du personnage</span>
          </Space>
        }
        extra={
          <Space>
            <Button
              icon={<UserAddOutlined />}
              onClick={() => setAssignModalOpen(true)}
            >
              Assigner à un utilisateur
            </Button>
            <Button
              icon={<MergeCellsOutlined />}
              onClick={() => setMergeModalOpen(true)}
            >
              Fusionner
            </Button>
            <Button
              type="primary"
              icon={<EditOutlined />}
              onClick={() => navigate(`/characters/edit/${id}`)}
            >
              Modifier
            </Button>
          </Space>
        }
      >
        <div style={{ marginBottom: 24, textAlign: 'center' }}>
          <Avatar
            src={character.avatar}
            icon={<UserOutlined />}
            size={100}
            style={{ marginBottom: 16 }}
          />
          <h2>{character.name}</h2>
          {character.actualPseudo && (
            <p style={{ color: '#666', marginTop: 8 }}>Pseudo: {character.actualPseudo}</p>
          )}
        </div>

        <Descriptions bordered column={2}>
          <Descriptions.Item label="ID">{character.id}</Descriptions.Item>
          <Descriptions.Item label="Nom">{character.name}</Descriptions.Item>
          {character.firstName && (
            <Descriptions.Item label="Prénom">{character.firstName}</Descriptions.Item>
          )}
          {character.lastName && (
            <Descriptions.Item label="Nom de famille">{character.lastName}</Descriptions.Item>
          )}
          <Descriptions.Item label="Pseudo">{character.actualPseudo || '-'}</Descriptions.Item>
          <Descriptions.Item label="Statut">
            <Tag color={getStatusColor(character.status)}>
              {getStatusLabel(character.status)}
            </Tag>
          </Descriptions.Item>
          <Descriptions.Item label="Univers">
            {character.universe_name ? (
              <Tag color="blue">{character.universe_name}</Tag>
            ) : (
              <Tag>-</Tag>
            )}
          </Descriptions.Item>
          <Descriptions.Item label="Utilisateur" span={2}>
            <div>
              <div>{character.user_pseudo || 'Aucun (sans propriétaire)'}</div>
              {character.user_email && (
                <div style={{ fontSize: '12px', color: '#999' }}>{character.user_email}</div>
              )}
            </div>
          </Descriptions.Item>
          <Descriptions.Item label="Date de création" span={2}>
            {character.createdAt ? new Date(character.createdAt).toLocaleString('fr-FR') : '-'}
          </Descriptions.Item>
          {character.validatedAt && (
            <Descriptions.Item label="Date de validation" span={2}>
              {new Date(character.validatedAt).toLocaleString('fr-FR')}
            </Descriptions.Item>
          )}
          {character.biography && (
            <Descriptions.Item label="Biographie" span={2}>
              <div style={{ whiteSpace: 'pre-wrap' }}>{character.biography}</div>
            </Descriptions.Item>
          )}
        </Descriptions>
      </Card>

      <AssignUserModal
        open={assignModalOpen}
        character={character}
        onClose={() => setAssignModalOpen(false)}
        onAssigned={fetchCharacterData}
      />

      <CharacterMergeModal
        open={mergeModalOpen}
        character={character}
        onClose={() => setMergeModalOpen(false)}
        onMerged={handleMerged}
      />
    </div>
  );
};

export default CharacterDetail;
