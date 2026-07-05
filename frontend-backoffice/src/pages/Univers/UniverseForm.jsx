import React, { useEffect, useState } from 'react';
import { Alert, Button, Card, Form, Image, Input, Space, message } from 'antd';
import { PictureOutlined } from '@ant-design/icons';
import { useNavigate, useParams } from 'react-router-dom';
import api from '../../services/api';
import MediaLibrary, { getMediaStorageUrl } from '../../components/MediaLibrary/MediaLibrary';
import UniversePreview from './UniversePreview';

const { TextArea } = Input;

const BannerField = ({
  label,
  selectedMedia,
  onOpenLibrary,
  onClear,
}) => (
  <Form.Item label={label}>
    <div>
      {selectedMedia ? (
        <div style={{ marginBottom: 8 }}>
          <Image
            src={selectedMedia.url}
            alt={selectedMedia.originalFilename}
            style={{ maxWidth: '100%', maxHeight: 140, objectFit: 'cover' }}
          />
          <div style={{ marginTop: 4, fontSize: 12, color: '#666' }}>
            {selectedMedia.originalFilename}
          </div>
        </div>
      ) : null}
      <Space>
        <Button icon={<PictureOutlined />} onClick={onOpenLibrary}>
          {selectedMedia ? 'Changer l\'image' : 'Sélectionner une image'}
        </Button>
        {selectedMedia && (
          <Button onClick={onClear}>Supprimer</Button>
        )}
      </Space>
    </div>
  </Form.Item>
);

const UniverseForm = () => {
  const [form] = Form.useForm();
  const navigate = useNavigate();
  const { id } = useParams();
  const [loading, setLoading] = useState(false);
  const [universe, setUniverse] = useState(null);
  const [portalBannerMedia, setPortalBannerMedia] = useState(null);
  const [forumsHeaderBannerMedia, setForumsHeaderBannerMedia] = useState(null);
  const [mediaLibraryOpen, setMediaLibraryOpen] = useState(false);
  const [activeBannerField, setActiveBannerField] = useState(null);

  const description = Form.useWatch('description', form);
  const forumsTitle = Form.useWatch('forumsTitle', form);
  const name = Form.useWatch('name', form);

  useEffect(() => {
    fetchUniverse();
  }, [id]);

  const fetchUniverse = async () => {
    setLoading(true);
    try {
      const response = await api.get(`/api/admin/universes/${id}`);
      const data = response.data;
      setUniverse(data);
      form.setFieldsValue({
        name: data.name,
        slug: data.slug,
        description: data.description || '',
        forumsTitle: data.forumsTitle || '',
      });

      if (data.portalBanner) {
        setPortalBannerMedia({ url: data.portalBanner, originalFilename: 'Bannière portail' });
      }
      if (data.forumsHeaderBanner) {
        setForumsHeaderBannerMedia({ url: data.forumsHeaderBanner, originalFilename: 'Bannière header forums' });
      }
    } catch (error) {
      message.error(error.response?.data?.error || 'Erreur lors du chargement de l\'univers');
      navigate('/univers');
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      const payload = {
        description: values.description || null,
        forumsTitle: values.forumsTitle || null,
        portalBanner: getMediaStorageUrl(portalBannerMedia) || null,
        forumsHeaderBanner: getMediaStorageUrl(forumsHeaderBannerMedia) || null,
      };

      if (!universe?.isSlugLocked) {
        payload.name = values.name;
        payload.slug = values.slug;
      }

      await api.put(`/api/admin/universes/${id}`, payload);
      message.success('Univers mis à jour avec succès');
      navigate('/univers');
    } catch (error) {
      message.error(error.response?.data?.error || 'Erreur lors de l\'enregistrement');
    } finally {
      setLoading(false);
    }
  };

  const openMediaLibrary = (field) => {
    setActiveBannerField(field);
    setMediaLibraryOpen(true);
  };

  const handleMediaSelect = (media) => {
    if (activeBannerField === 'portalBanner') {
      setPortalBannerMedia(media);
    } else if (activeBannerField === 'forumsHeaderBanner') {
      setForumsHeaderBannerMedia(media);
    }
    setMediaLibraryOpen(false);
  };

  const activeMediaValue = activeBannerField === 'portalBanner'
    ? portalBannerMedia
    : forumsHeaderBannerMedia;

  const displayName = name || universe?.name;

  return (
    <div>
      <Card title={`Modifier l'univers${displayName ? ` : ${displayName}` : ''}`} loading={loading && !universe}>
        {universe?.isSlugLocked && (
          <Alert
            type="info"
            showIcon
            style={{ marginBottom: 16 }}
            message="Nom et slug verrouillés"
            description="Pour les univers DC et Marvel, seuls la description, le titre de la page forums et les bannières sont modifiables."
          />
        )}

        <Form form={form} layout="vertical" onFinish={handleSubmit}>
          <Form.Item
            name="name"
            label="Nom"
            rules={[{ required: true, message: 'Le nom est requis' }]}
          >
            <Input disabled={universe?.isSlugLocked} />
          </Form.Item>

          <Form.Item
            name="slug"
            label="Slug"
            rules={[{ required: true, message: 'Le slug est requis' }]}
          >
            <Input disabled={universe?.isSlugLocked} />
          </Form.Item>

          <Form.Item name="description" label="Description">
            <TextArea rows={4} placeholder="Description affichée sur le portail et la page forums" />
          </Form.Item>

          <Form.Item
            name="forumsTitle"
            label="Titre de la page forums"
            tooltip="Ex. « Forums de DC Comics ». Laissez vide pour utiliser « Forums de {nom} »."
          >
            <Input placeholder="Forums de DC Comics" />
          </Form.Item>

          <BannerField
            label="Bannière portail (carte sur la page d'accueil)"
            selectedMedia={portalBannerMedia}
            onOpenLibrary={() => openMediaLibrary('portalBanner')}
            onClear={() => setPortalBannerMedia(null)}
          />

          <BannerField
            label="Bannière header (page forums de l'univers)"
            selectedMedia={forumsHeaderBannerMedia}
            onOpenLibrary={() => openMediaLibrary('forumsHeaderBanner')}
            onClear={() => setForumsHeaderBannerMedia(null)}
          />

          <UniversePreview
            name={displayName}
            description={description}
            forumsTitle={forumsTitle}
            portalBanner={portalBannerMedia?.url}
            forumsHeaderBanner={forumsHeaderBannerMedia?.url}
          />

          <Form.Item style={{ marginTop: 24 }}>
            <Space>
              <Button type="primary" htmlType="submit" loading={loading}>
                Enregistrer
              </Button>
              <Button onClick={() => navigate('/univers')}>
                Annuler
              </Button>
            </Space>
          </Form.Item>
        </Form>
      </Card>

      <MediaLibrary
        open={mediaLibraryOpen}
        onClose={() => setMediaLibraryOpen(false)}
        onSelect={handleMediaSelect}
        value={activeMediaValue}
      />
    </div>
  );
};

export default UniverseForm;
