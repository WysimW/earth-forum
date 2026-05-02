import React, { useState, useEffect } from 'react';
import { Form, Input, Select, Button, message, Card, Space, Avatar } from 'antd';
import { UserOutlined, PictureOutlined } from '@ant-design/icons';
import { useNavigate, useParams } from 'react-router-dom';
import api from '../../services/api';
import AvatarCropper from '../../components/AvatarCropper/AvatarCropper';
import './CharacterForm.css';

const { TextArea } = Input;

const CharacterForm = () => {
  const [form] = Form.useForm();
  const navigate = useNavigate();
  const { id } = useParams();
  const [loading, setLoading] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [characterData, setCharacterData] = useState(null);
  const [universes, setUniverses] = useState([]);
  const [users, setUsers] = useState([]);
  const [avatarCropperOpen, setAvatarCropperOpen] = useState(false);
  const [selectedAvatar, setSelectedAvatar] = useState(null);

  useEffect(() => {
    fetchUniverses();
    fetchUsers();
    
    if (id) {
      setIsEditing(true);
      fetchCharacterData(id);
    }
  }, [id]);

  const fetchCharacterData = async (characterId) => {
    try {
      const response = await api.get(`/api/admin/characters/${characterId}`);
      const character = response.data;
      setCharacterData(character);
      
      form.setFieldsValue({
        name: character.name,
        actualPseudo: character.actualPseudo,
        biography: character.biography,
        status: character.status,
        universe_id: character.universe_id,
        user_id: character.user_id,
      });
      
      // Charger l'avatar si il existe
      if (character.avatar) {
        setSelectedAvatar({ url: character.avatar, originalFilename: 'Avatar actuel' });
      }
    } catch (error) {
      console.error('Error fetching character data:', error);
      message.error('Erreur lors du chargement du personnage');
      navigate('/characters');
    }
  };

  const fetchUniverses = async () => {
    try {
      const response = await api.get('/api/universes');
      setUniverses(response.data?.universes || []);
    } catch (error) {
      console.error('Error fetching universes:', error);
    }
  };

  const fetchUsers = async () => {
    try {
      const response = await api.get('/api/admin/users');
      setUsers(response.data || []);
    } catch (error) {
      console.error('Error fetching users:', error);
    }
  };

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      const data = {
        name: values.name,
        actualPseudo: values.actualPseudo || null,
        biography: values.biography || null,
        status: values.status || 'draft',
        avatar: selectedAvatar?.url || null,
        universe_id: values.universe_id || null,
        user_id: values.user_id || null,
      };

      if (isEditing) {
        await api.put(`/api/admin/characters/${id}`, data);
        message.success('Personnage mis à jour avec succès');
      } else {
        await api.post('/api/admin/characters', data);
        message.success('Personnage créé avec succès');
      }
      navigate('/characters');
    } catch (error) {
      console.error('Error submitting character:', error);
      const errorMessage = error.response?.data?.error || 'Erreur lors de la soumission';
      message.error(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const handleCancel = () => {
    navigate('/characters');
  };

  return (
    <div className="character-form-page">
      <Card title={isEditing ? 'Modifier le personnage' : 'Créer un personnage'}>
        <Form
          form={form}
          layout="vertical"
          onFinish={handleSubmit}
          initialValues={{
            status: 'draft',
          }}
        >
          <Form.Item
            name="name"
            label="Nom du personnage"
            rules={[{ required: true, message: 'Le nom est requis' }]}
          >
            <Input placeholder="Entrez le nom du personnage" />
          </Form.Item>

          <Form.Item
            name="actualPseudo"
            label="Pseudo"
          >
            <Input placeholder="Entrez le pseudo (optionnel)" />
          </Form.Item>

          <Form.Item
            name="biography"
            label="Biographie"
          >
            <TextArea
              rows={6}
              placeholder="Entrez la biographie du personnage (optionnel)"
            />
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
            name="status"
            label="Statut"
            rules={[{ required: true, message: 'Le statut est requis' }]}
          >
            <Select>
              <Select.Option value="draft">Brouillon</Select.Option>
              <Select.Option value="pending">En attente</Select.Option>
              <Select.Option value="validated">Validé</Select.Option>
              <Select.Option value="rejected">Rejeté</Select.Option>
              <Select.Option value="abandoned">Abandonné</Select.Option>
              <Select.Option value="editing">En édition</Select.Option>
            </Select>
          </Form.Item>

          <Form.Item
            name="universe_id"
            label="Univers"
          >
            <Select 
              placeholder="Sélectionnez un univers (optionnel)"
              allowClear
            >
              {universes.map((universe) => (
                <Select.Option key={universe.id} value={universe.id}>
                  {universe.name}
                </Select.Option>
              ))}
            </Select>
          </Form.Item>

          <Form.Item
            name="user_id"
            label="Utilisateur"
            rules={[{ required: true, message: 'L\'utilisateur est requis' }]}
          >
            <Select 
              placeholder="Sélectionnez un utilisateur"
              showSearch
              filterOption={(input, option) =>
                option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
              }
            >
              {users.map((user) => (
                <Select.Option key={user.id} value={user.id}>
                  {user.pseudo} ({user.email})
                </Select.Option>
              ))}
            </Select>
          </Form.Item>

          <Form.Item>
            <Space>
              <Button type="primary" htmlType="submit" loading={loading}>
                {isEditing ? 'Mettre à jour le personnage' : 'Créer le personnage'}
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

export default CharacterForm;






