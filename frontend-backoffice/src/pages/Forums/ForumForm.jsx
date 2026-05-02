import React, { useState, useEffect } from 'react';
import { Form, Input, Select, Button, message, Card, Space, Image } from 'antd';
import { PictureOutlined } from '@ant-design/icons';
import { useNavigate, useSearchParams, useParams } from 'react-router-dom';
import api from '../../services/api';
import MediaLibrary from '../../components/MediaLibrary/MediaLibrary';
import './ForumForm.css';

const ForumForm = () => {
  const [form] = Form.useForm();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const { id } = useParams(); // ID du forum à éditer depuis l'URL
  const [loading, setLoading] = useState(false);
  const [universes, setUniverses] = useState([]);
  const [categories, setCategories] = useState([]);
  const [forums, setForums] = useState([]);
  const [parentForum, setParentForum] = useState(null);
  const [mediaLibraryOpen, setMediaLibraryOpen] = useState(false);
  const [selectedBanner, setSelectedBanner] = useState(null);
  const [isEditing, setIsEditing] = useState(false);
  const [forumData, setForumData] = useState(null);

  const parentForumId = searchParams.get('parentId');
  const forumId = id || searchParams.get('id'); // ID du forum à éditer

  useEffect(() => {
    fetchUniverses();
    fetchCategories();
    fetchForums();
    
    if (parentForumId) {
      fetchParentForum(parentForumId);
    }
    
    if (forumId) {
      setIsEditing(true);
      fetchForumData(forumId);
    }
  }, [parentForumId, forumId]);

  const fetchUniverses = async () => {
    try {
      const response = await api.get('/api/universes');
      setUniverses(response.data?.universes || []);
    } catch (error) {
      console.error('Error fetching universes:', error);
    }
  };

  const fetchCategories = async () => {
    try {
      const response = await api.get('/api/categories/list');
      setCategories(response.data || []);
    } catch (error) {
      console.error('Error fetching categories:', error);
    }
  };

  const fetchForums = async () => {
    try {
      const response = await api.get('/api/admin/forums');
      setForums(response.data || []);
    } catch (error) {
      console.error('Error fetching forums:', error);
    }
  };

  const fetchParentForum = async (id) => {
    try {
      const response = await api.get(`/api/admin/forums`);
      const allForums = response.data || [];
      const parent = allForums.find(f => f.id === parseInt(id));
      if (parent) {
        setParentForum(parent);
        // Pré-remplir le formulaire avec les données du parent
        form.setFieldsValue({
          parent_forum_id: parseInt(id),
          type: parent.type || 'hrp',
          universe_id: parent.universe_id || undefined,
          category_id: parent.category_id || undefined,
        });
      }
    } catch (error) {
      console.error('Error fetching parent forum:', error);
      message.error('Erreur lors du chargement du forum parent');
    }
  };

  const fetchForumData = async (id) => {
    try {
      const response = await api.get(`/api/admin/forums`);
      const allForums = response.data || [];
      const forum = allForums.find(f => f.id === parseInt(id));
      
      if (forum) {
        setForumData(forum);
        
        // Pré-remplir le formulaire avec les données du forum
        form.setFieldsValue({
          name: forum.name,
          description: forum.description || '',
          type: forum.type || 'hrp',
          status: forum.status || 'open',
          parent_forum_id: forum.parent_forum_id || undefined,
          universe_id: forum.universe_id ?? '__none__',
          category_id: forum.category_id || undefined,
        });
        
        // Charger la bannière si elle existe
        if (forum.banner) {
          setSelectedBanner({ url: forum.banner, originalFilename: 'Bannière actuelle' });
        }
      }
    } catch (error) {
      console.error('Error fetching forum data:', error);
      message.error('Erreur lors du chargement du forum');
    }
  };

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      const data = {
        name: values.name,
        description: values.description || null,
        type: values.type || 'hrp',
        status: values.status || 'open',
        banner: selectedBanner?.url || null,
      };

      if (isEditing) {
        // Mode édition : autoriser le changement d'univers uniquement pour les forums parents
        if (!forumData?.parent_forum_id) {
          data.universe_id = values.universe_id === '__none__'
            ? null
            : (values.universe_id ?? null);
        }

        await api.put(`/api/admin/forums/${forumId}`, data);
        message.success('Forum modifié avec succès');
      } else {
        // Mode création
        // Si un forum parent est sélectionné (depuis le paramètre ou le formulaire)
        if (parentForumId || values.parent_forum_id) {
          data.parent_forum_id = parentForumId || values.parent_forum_id;
        } else if (values.universe_id) {
          data.universe_id = values.universe_id;
        } else if (values.category_id) {
          data.category_id = values.category_id;
        } else {
          message.error('Vous devez sélectionner un forum parent, un univers ou une catégorie');
          setLoading(false);
          return;
        }

        await api.post('/api/admin/forums', data);
        message.success(data.parent_forum_id ? 'Sous-forum créé avec succès' : 'Forum créé avec succès');
      }
      
      navigate('/forums');
    } catch (error) {
      console.error('Error saving forum:', error);
      const errorMessage = error.response?.data?.error || error.response?.data?.message || 
        (isEditing ? 'Erreur lors de la modification du forum' : 'Erreur lors de la création du forum');
      message.error(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const handleCancel = () => {
    navigate('/forums');
  };

  return (
    <div className="forum-form-page">
      <Card title={isEditing ? 'Modifier le forum' : (parentForumId ? 'Créer un sous-forum' : 'Créer un forum')}>
        {parentForum && (
          <div style={{ marginBottom: 24, padding: 12, background: '#f0f2f5', borderRadius: 4 }}>
            <strong>Forum parent :</strong> {parentForum.name}
            {parentForum.type && (
              <span style={{ marginLeft: 12 }}>
                <span style={{ color: '#666' }}>Type :</span> {parentForum.type}
              </span>
            )}
          </div>
        )}
        
        <Form
          form={form}
          layout="vertical"
          onFinish={handleSubmit}
          initialValues={{
            type: parentForum?.type || 'hrp',
            status: 'open',
          }}
          onValuesChange={(changedValues) => {
            // Désactiver univers et catégorie si un forum parent est sélectionné
            if (changedValues.parent_forum_id) {
              form.setFieldsValue({
                universe_id: undefined,
                category_id: undefined,
              });
            }
          }}
        >
          <Form.Item
            name="name"
            label="Nom du forum"
            rules={[{ required: true, message: 'Le nom du forum est requis' }]}
          >
            <Input placeholder="Entrez le nom du forum" />
          </Form.Item>

          <Form.Item
            name="description"
            label="Description"
          >
            <Input.TextArea
              rows={4}
              placeholder="Entrez la description du forum (optionnel)"
            />
          </Form.Item>

          <Form.Item
            name="type"
            label="Type"
            rules={[{ required: true, message: 'Le type est requis' }]}
          >
            <Select>
              <Select.Option value="important">Important</Select.Option>
              <Select.Option value="player_platform">Plateforme joueur</Select.Option>
              <Select.Option value="roleplay">Roleplay</Select.Option>
              <Select.Option value="hrp">HRP</Select.Option>
            </Select>
          </Form.Item>

          <Form.Item
            name="status"
            label="Statut"
            rules={[{ required: true, message: 'Le statut est requis' }]}
          >
            <Select>
              <Select.Option value="open">Ouvert</Select.Option>
              <Select.Option value="closed">Fermé</Select.Option>
              <Select.Option value="archived">Archivé</Select.Option>
            </Select>
          </Form.Item>

          <Form.Item
            name="banner"
            label="Bannière"
          >
            <div>
              {selectedBanner ? (
                <div style={{ marginBottom: 8 }}>
                  <Image
                    src={selectedBanner.url}
                    alt={selectedBanner.originalFilename}
                    style={{ maxWidth: '200px', maxHeight: '100px', objectFit: 'contain' }}
                  />
                  <div style={{ marginTop: 4, fontSize: '12px', color: '#666' }}>
                    {selectedBanner.originalFilename}
                  </div>
                </div>
              ) : null}
              <Space>
                <Button
                  icon={<PictureOutlined />}
                  onClick={() => setMediaLibraryOpen(true)}
                >
                  {selectedBanner ? 'Changer la bannière' : 'Sélectionner une bannière'}
                </Button>
                {selectedBanner && (
                  <Button
                    onClick={() => {
                      setSelectedBanner(null);
                      form.setFieldsValue({ banner: null });
                    }}
                  >
                    Supprimer
                  </Button>
                )}
              </Space>
            </div>
          </Form.Item>

          {!parentForumId && !isEditing && (
            <>
              <Form.Item
                name="parent_forum_id"
                label="Forum parent"
                tooltip="Sélectionnez un forum parent pour créer un sous-forum"
              >
                <Select 
                  placeholder="Sélectionnez un forum parent (optionnel)"
                  allowClear
                  showSearch
                  filterOption={(input, option) =>
                    option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                  }
                >
                  {forums
                    .filter(f => !f.parent_forum_id) // Seulement les forums parents
                    .map((forum) => (
                      <Select.Option key={forum.id} value={forum.id}>
                        {forum.name}
                      </Select.Option>
                    ))}
                </Select>
              </Form.Item>

              <Form.Item
                name="universe_id"
                label="Univers"
                tooltip="Sélectionnez un univers pour ce forum"
                rules={[
                  {
                    validator: (_, value) => {
                      const parentId = form.getFieldValue('parent_forum_id') || parentForumId;
                      const categoryId = form.getFieldValue('category_id');
                      if (!parentId && !value && !categoryId) {
                        return Promise.reject(new Error('Sélectionnez un forum parent, un univers ou une catégorie'));
                      }
                      return Promise.resolve();
                    },
                  },
                ]}
              >
                <Select 
                  placeholder="Sélectionnez un univers"
                  allowClear
                  disabled={!!form.getFieldValue('parent_forum_id') || !!parentForumId}
                >
                  {universes.map((universe) => (
                    <Select.Option key={universe.id} value={universe.id}>
                      {universe.name}
                    </Select.Option>
                  ))}
                </Select>
              </Form.Item>

              <Form.Item
                name="category_id"
                label="Catégorie"
                tooltip="Ou sélectionnez une catégorie pour ce forum"
              >
                <Select 
                  placeholder="Sélectionnez une catégorie (optionnel)"
                  allowClear
                  disabled={!!form.getFieldValue('parent_forum_id') || !!parentForumId}
                >
                  {categories.map((category) => (
                    <Select.Option key={category.id} value={category.id}>
                      {category.name}
                    </Select.Option>
                  ))}
                </Select>
              </Form.Item>
            </>
          )}

          {isEditing && (
            <Form.Item
              name="universe_id"
              label="Univers"
              tooltip={forumData?.parent_forum_id
                ? "L'univers d'un sous-forum est hérité de son forum parent"
                : "Sélectionnez l'univers de ce forum"}
            >
              <Select
                placeholder="Sélectionnez un univers"
                allowClear
                disabled={!!forumData?.parent_forum_id}
              >
                <Select.Option value="__none__">Aucun (global)</Select.Option>
                {universes.map((universe) => (
                  <Select.Option key={universe.id} value={universe.id}>
                    {universe.name}
                  </Select.Option>
                ))}
              </Select>
            </Form.Item>
          )}

          <Form.Item>
            <Space>
              <Button type="primary" htmlType="submit" loading={loading}>
                {isEditing ? 'Enregistrer les modifications' : (parentForumId ? 'Créer le sous-forum' : 'Créer le forum')}
              </Button>
              <Button onClick={handleCancel}>
                Annuler
              </Button>
            </Space>
          </Form.Item>
        </Form>
      </Card>

      <MediaLibrary
        open={mediaLibraryOpen}
        onClose={() => setMediaLibraryOpen(false)}
        onSelect={(media) => {
          setSelectedBanner(media);
          form.setFieldsValue({ banner: media.url });
        }}
        value={selectedBanner}
      />
    </div>
  );
};

export default ForumForm;

