import React, { useState, useEffect } from 'react';
import { Card, Descriptions, Tag, Button, Space, message, Spin, Avatar } from 'antd';
import { EditOutlined, ArrowLeftOutlined } from '@ant-design/icons';
import { useNavigate, useParams } from 'react-router-dom';
import api from '../../services/api';
import './UserDetail.css';

const UserDetail = () => {
  const navigate = useNavigate();
  const { id } = useParams();
  const [loading, setLoading] = useState(false);
  const [user, setUser] = useState(null);

  useEffect(() => {
    if (id) {
      fetchUserData();
    }
  }, [id]);

  const fetchUserData = async () => {
    setLoading(true);
    try {
      const response = await api.get(`/api/admin/users/${id}`);
      setUser(response.data);
    } catch (error) {
      console.error('Error fetching user data:', error);
      message.error('Erreur lors du chargement de l\'utilisateur');
      navigate('/users');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div style={{ textAlign: 'center', padding: '50px' }}>
        <Spin size="large" />
      </div>
    );
  }

  if (!user) {
    return null;
  }

  return (
    <div className="user-detail-page">
      <Card
        title={
          <Space>
            <Button
              icon={<ArrowLeftOutlined />}
              onClick={() => navigate('/users')}
            >
              Retour
            </Button>
            <span>Détails de l'utilisateur</span>
          </Space>
        }
        extra={
          <Button
            type="primary"
            icon={<EditOutlined />}
            onClick={() => navigate(`/users/edit/${id}`)}
          >
            Modifier
          </Button>
        }
      >
        <div style={{ marginBottom: 24, textAlign: 'center' }}>
          <Avatar
            src={user.avatar}
            size={100}
            style={{ marginBottom: 16 }}
          />
          <h2>{user.pseudo}</h2>
        </div>

        <Descriptions bordered column={2}>
          <Descriptions.Item label="ID">{user.id}</Descriptions.Item>
          <Descriptions.Item label="Pseudo">{user.pseudo}</Descriptions.Item>
          <Descriptions.Item label="Email">{user.email}</Descriptions.Item>
          <Descriptions.Item label="Statut">
            <Tag color={user.status === 'active' ? 'green' : 'default'}>
              {user.status === 'active' ? 'Actif' : 'Inactif'}
            </Tag>
          </Descriptions.Item>
          <Descriptions.Item label="Rôle">
            {user.roles.map((role) => (
              <Tag key={role} color={role === 'ROLE_SUPER_ADMIN' ? 'gold' : (role === 'ROLE_ADMIN' ? 'red' : (role === 'ROLE_MODERATOR' ? 'purple' : 'blue'))}>
                {role === 'ROLE_SUPER_ADMIN' ? 'Super administrateur' : (role === 'ROLE_ADMIN' ? 'Administrateur' : (role === 'ROLE_MODERATOR' ? 'Modérateur' : 'Utilisateur'))}
              </Tag>
            ))}
          </Descriptions.Item>
          <Descriptions.Item label="Date de création">
            {user.createdAt ? new Date(user.createdAt).toLocaleString('fr-FR') : '-'}
          </Descriptions.Item>
          <Descriptions.Item label="Création de factions">
            <Tag color={user.canCreateFaction ? 'green' : 'default'}>
              {user.canCreateFaction ? 'Autorisé' : 'Non autorisé'}
            </Tag>
          </Descriptions.Item>
          <Descriptions.Item label="Univers administrés" span={2}>
            {(user.adminUniverses || []).length > 0
              ? user.adminUniverses.map((universe) => (
                <Tag key={universe.id} color="blue">{universe.name}</Tag>
              ))
              : '-'}
          </Descriptions.Item>
          <Descriptions.Item label="Dernière connexion" span={2}>
            {user.lastLogin ? new Date(user.lastLogin).toLocaleString('fr-FR') : 'Jamais connecté'}
          </Descriptions.Item>
        </Descriptions>
      </Card>
    </div>
  );
};

export default UserDetail;

