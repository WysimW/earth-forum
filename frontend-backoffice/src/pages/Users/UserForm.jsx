import React, { useState, useEffect } from 'react';
import { Form, Input, Select, Button, message, Card, Space, Switch, Image, Avatar } from 'antd';
import { UserOutlined, PictureOutlined } from '@ant-design/icons';
import { useNavigate, useParams } from 'react-router-dom';
import api from '../../services/api';
import AvatarCropper from '../../components/AvatarCropper/AvatarCropper';
import './UserForm.css';

const UserForm = () => {
  const [form] = Form.useForm();
  const navigate = useNavigate();
  const { id } = useParams();
  const [loading, setLoading] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [userData, setUserData] = useState(null);
  const [avatarCropperOpen, setAvatarCropperOpen] = useState(false);
  const [selectedAvatar, setSelectedAvatar] = useState(null);
  const [universes, setUniverses] = useState([]);

  useEffect(() => {
    fetchUniverses();
    if (id) {
      setIsEditing(true);
      fetchUserData(id);
    }
  }, [id]);

  const fetchUniverses = async () => {
    try {
      const response = await api.get('/api/universes');
      setUniverses(response.data?.universes || []);
    } catch (error) {
      console.error('Error fetching universes:', error);
    }
  };

  const fetchUserData = async (userId) => {
    try {
      const response = await api.get(`/api/admin/users/${userId}`);
      const user = response.data;
      setUserData(user);
      
      form.setFieldsValue({
        pseudo: user.pseudo,
        email: user.email,
        avatar: user.avatar,
        roles: user.roles,
        isActive: user.isActive,
        canCreateFaction: user.canCreateFaction,
        adminUniverseIds: user.adminUniverseIds || [],
      });
      
      // Charger l'avatar si il existe
      if (user.avatar) {
        setSelectedAvatar({ url: user.avatar, originalFilename: 'Avatar actuel' });
      }
    } catch (error) {
      console.error('Error fetching user data:', error);
      message.error('Erreur lors du chargement de l\'utilisateur');
      navigate('/users');
    }
  };

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      const data = {
        pseudo: values.pseudo,
        email: values.email,
        avatar: selectedAvatar?.url || values.avatar || 'https://sbcf.fr/wp-content/uploads/2018/03/sbcf-default-avatar.png',
        roles: values.roles || ['ROLE_USER'],
        isActive: values.isActive !== undefined ? values.isActive : true,
        canCreateFaction: values.canCreateFaction ?? false,
        adminUniverseIds: values.adminUniverseIds || [],
      };

      if (values.password && values.password.trim() !== '') {
        data.password = values.password;
      }

      if (isEditing) {
        await api.put(`/api/admin/users/${id}`, data);
        message.success('Utilisateur mis à jour avec succès');
      } else {
        if (!values.password || values.password.trim() === '') {
          message.error('Le mot de passe est requis pour créer un utilisateur');
          setLoading(false);
          return;
        }
        data.password = values.password;
        await api.post('/api/admin/users', data);
        message.success('Utilisateur créé avec succès');
      }
      navigate('/users');
    } catch (error) {
      console.error('Error submitting user:', error);
      const errorMessage = error.response?.data?.error || 'Erreur lors de la soumission';
      message.error(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const handleCancel = () => {
    navigate('/users');
  };

  return (
    <div className="user-form-page">
      <Card title={isEditing ? 'Modifier l\'utilisateur' : 'Créer un utilisateur'}>
        <Form
          form={form}
          layout="vertical"
          onFinish={handleSubmit}
          initialValues={{
            roles: ['ROLE_USER'],
            isActive: true,
            canCreateFaction: false,
            adminUniverseIds: [],
          }}
        >
          <Form.Item
            name="pseudo"
            label="Pseudo"
            rules={[{ required: true, message: 'Le pseudo est requis' }]}
          >
            <Input placeholder="Entrez le pseudo" />
          </Form.Item>

          <Form.Item
            name="email"
            label="Email"
            rules={[
              { required: true, message: 'L\'email est requis' },
              { type: 'email', message: 'Email invalide' },
            ]}
          >
            <Input placeholder="Entrez l'email" />
          </Form.Item>

          <Form.Item
            name="password"
            label={isEditing ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe'}
            rules={!isEditing ? [{ required: true, message: 'Le mot de passe est requis' }] : []}
          >
            <Input.Password placeholder={isEditing ? 'Laisser vide pour ne pas changer' : 'Entrez le mot de passe'} />
          </Form.Item>

          <Form.Item
            name="avatar"
            label="Avatar"
          >
            <div>
              {selectedAvatar ? (
                <div style={{ marginBottom: 16 }}>
                  <Avatar
                    src={selectedAvatar.url}
                    size={100}
                    icon={<UserOutlined />}
                    style={{ marginBottom: 8 }}
                  />
                  <div style={{ marginTop: 8, fontSize: '12px', color: '#666' }}>
                    {selectedAvatar.originalFilename}
                  </div>
                </div>
              ) : (
                <Avatar
                  size={100}
                  icon={<UserOutlined />}
                  style={{ marginBottom: 8 }}
                />
              )}
              <Space>
                <Button
                  icon={<PictureOutlined />}
                  onClick={() => setAvatarCropperOpen(true)}
                >
                  {selectedAvatar ? 'Changer l\'avatar' : 'Sélectionner un avatar'}
                </Button>
                {selectedAvatar && (
                  <Button
                    onClick={() => {
                      setSelectedAvatar(null);
                      form.setFieldsValue({ avatar: null });
                    }}
                  >
                    Supprimer
                  </Button>
                )}
              </Space>
            </div>
          </Form.Item>

          <Form.Item
            name="roles"
            label="Rôles"
            rules={[{ required: true, message: 'Les rôles sont requis' }]}
          >
            <Select mode="multiple" placeholder="Sélectionnez les rôles">
              <Select.Option value="ROLE_USER">Utilisateur</Select.Option>
              <Select.Option value="ROLE_MODERATOR">Modérateur</Select.Option>
              <Select.Option value="ROLE_ADMIN">Administrateur</Select.Option>
              <Select.Option value="ROLE_SUPER_ADMIN">Super administrateur</Select.Option>
            </Select>
          </Form.Item>

          <Form.Item
            noStyle
            shouldUpdate={(prev, curr) => prev.roles !== curr.roles}
          >
            {({ getFieldValue }) => {
              const roles = getFieldValue('roles') || [];
              const needsUniverseScope = roles.includes('ROLE_ADMIN') || roles.includes('ROLE_MODERATOR') || roles.includes('ROLE_SUPER_ADMIN');
              return needsUniverseScope ? (
                <Form.Item
                  name="adminUniverseIds"
                  label="Univers administrés"
                  tooltip="Limite les univers pour les rôles admin/modérateur (SUPER_ADMIN n'est pas limité par cette liste)."
                >
                  <Select
                    mode="multiple"
                    placeholder="Sélectionnez les univers autorisés"
                    options={universes.map((universe) => ({
                      value: universe.id,
                      label: universe.name,
                    }))}
                  />
                </Form.Item>
              ) : null;
            }}
          </Form.Item>

          <Form.Item
            name="isActive"
            label="Statut"
            valuePropName="checked"
          >
            <Switch checkedChildren="Actif" unCheckedChildren="Inactif" />
          </Form.Item>

          <Form.Item
            name="canCreateFaction"
            label="Autoriser la création de factions"
            valuePropName="checked"
          >
            <Switch checkedChildren="Autorisé" unCheckedChildren="Interdit" />
          </Form.Item>

          <Form.Item>
            <Space>
              <Button type="primary" htmlType="submit" loading={loading}>
                {isEditing ? 'Mettre à jour l\'utilisateur' : 'Créer l\'utilisateur'}
              </Button>
              <Button onClick={handleCancel}>
                Annuler
              </Button>
            </Space>
          </Form.Item>
        </Form>
      </Card>

      <AvatarCropper
        open={avatarCropperOpen}
        onClose={() => setAvatarCropperOpen(false)}
        onSelect={(media) => {
          setSelectedAvatar(media);
          form.setFieldsValue({ avatar: media.url });
        }}
        value={selectedAvatar}
      />
    </div>
  );
};

export default UserForm;

