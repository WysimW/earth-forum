import React, { useState, useEffect } from 'react';
import { Modal, Upload, message, Image, Button, Space, Input, Tabs, Card, Empty } from 'antd';
import { UploadOutlined, DeleteOutlined, SearchOutlined } from '@ant-design/icons';
import api from '../../services/api';
import './MediaLibrary.css';

const { Search } = Input;

/** URL stable pour enregistrement en base (sans signature S3). */
export const getMediaStorageUrl = (media) => media?.storageUrl || media?.url;

const MediaLibrary = ({ open, onClose, onSelect, value }) => {
  const [media, setMedia] = useState([]);
  const [filteredMedia, setFilteredMedia] = useState([]);
  const [loading, setLoading] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [searchValue, setSearchValue] = useState('');
  const [selectedMedia, setSelectedMedia] = useState(value || null);
  const [activeTab, setActiveTab] = useState('library');

  useEffect(() => {
    if (open) {
      fetchMedia();
      setSelectedMedia(value || null);
    }
  }, [open, value]);

  useEffect(() => {
    filterMedia();
  }, [searchValue, media]);

  const fetchMedia = async () => {
    setLoading(true);
    try {
      const response = await api.get('/api/admin/media?type=image');
      console.log('Media response:', response.data); // Debug log
      const mediaList = response.data?.media || response.data || [];
      console.log('Media list:', mediaList); // Debug log
      setMedia(Array.isArray(mediaList) ? mediaList : []);
    } catch (error) {
      console.error('Error fetching media:', error);
      console.error('Error response:', error.response?.data); // Debug log
      message.error('Erreur lors du chargement de la médiathèque');
      setMedia([]); // S'assurer que media est un tableau vide en cas d'erreur
    } finally {
      setLoading(false);
    }
  };

  const filterMedia = () => {
    if (!searchValue) {
      setFilteredMedia(media);
      return;
    }

    const filtered = media.filter((item) =>
      item.originalFilename.toLowerCase().includes(searchValue.toLowerCase())
    );
    setFilteredMedia(filtered);
  };

  const handleUpload = async (file) => {
    setUploading(true);
    const formData = new FormData();
    formData.append('file', file);

    try {
      const response = await api.post('/api/admin/media/upload', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });

      message.success('Image uploadée avec succès');
      await fetchMedia();
      
      // Sélectionner automatiquement l'image uploadée
      const uploadedMedia = response.data;
      setSelectedMedia(uploadedMedia);
      setActiveTab('library');
      
      return false; // Empêcher l'upload automatique
    } catch (error) {
      console.error('Error uploading file:', error);
      const errorMessage = error.response?.data?.error || 'Erreur lors de l\'upload';
      message.error(errorMessage);
      return false;
    } finally {
      setUploading(false);
    }
  };

  const handleSelect = (mediaItem) => {
    setSelectedMedia(mediaItem);
  };

  const handleConfirm = () => {
    if (selectedMedia) {
      onSelect(selectedMedia);
      onClose();
    } else {
      message.warning('Veuillez sélectionner une image');
    }
  };

  const handleDelete = async (mediaId, e) => {
    e.stopPropagation();
    
    try {
      await api.delete(`/api/admin/media/${mediaId}`);
      message.success('Image supprimée');
      await fetchMedia();
      if (selectedMedia?.id === mediaId) {
        setSelectedMedia(null);
      }
    } catch (error) {
      console.error('Error deleting media:', error);
      message.error('Erreur lors de la suppression');
    }
  };

  const uploadProps = {
    beforeUpload: handleUpload,
    showUploadList: false,
    accept: 'image/*',
    multiple: false,
  };

  return (
    <Modal
      title="Médiathèque"
      open={open}
      onCancel={onClose}
      width={900}
      footer={[
        <Button key="cancel" onClick={onClose}>
          Annuler
        </Button>,
        <Button key="confirm" type="primary" onClick={handleConfirm} disabled={!selectedMedia}>
          Utiliser cette image
        </Button>,
      ]}
    >
      <Tabs 
        activeKey={activeTab} 
        onChange={setActiveTab}
        items={[
          {
            key: 'library',
            label: (
              <span>
                <SearchOutlined />
                Bibliothèque
              </span>
            ),
            children: (
              <div className="media-library-content">
                <div className="media-library-header">
                  <Search
                    placeholder="Rechercher une image..."
                    value={searchValue}
                    onChange={(e) => setSearchValue(e.target.value)}
                    style={{ marginBottom: 16 }}
                  />
                </div>

                {loading ? (
                  <div style={{ textAlign: 'center', padding: '40px' }}>
                    Chargement...
                  </div>
                ) : filteredMedia.length === 0 ? (
                  <Empty description="Aucune image trouvée" />
                ) : (
                  <div className="media-grid">
                    {filteredMedia.map((item) => (
                      <Card
                        key={item.id}
                        hoverable
                        className={`media-item ${selectedMedia?.id === item.id ? 'selected' : ''}`}
                        onClick={() => handleSelect(item)}
                        cover={
                          <div className="media-item-image">
                            <Image
                              src={item.url}
                              alt={item.originalFilename}
                              preview={false}
                              style={{ width: '100%', height: '150px', objectFit: 'cover' }}
                            />
                            <div className="media-item-overlay">
                              <Button
                                type="text"
                                danger
                                icon={<DeleteOutlined />}
                                onClick={(e) => handleDelete(item.id, e)}
                                className="media-item-delete"
                              />
                            </div>
                          </div>
                        }
                      >
                        <Card.Meta
                          title={
                            <div style={{ fontSize: '12px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                              {item.originalFilename}
                            </div>
                          }
                        />
                      </Card>
                    ))}
                  </div>
                )}
              </div>
            ),
          },
          {
            key: 'upload',
            label: (
              <span>
                <UploadOutlined />
                Uploader
              </span>
            ),
            children: (
              <div className="media-upload-content">
                <Upload.Dragger {...uploadProps} disabled={uploading}>
                  <p className="ant-upload-drag-icon">
                    <UploadOutlined />
                  </p>
                  <p className="ant-upload-text">Cliquez ou glissez une image ici pour l'uploader</p>
                  <p className="ant-upload-hint">
                    Formats supportés: JPG, PNG, GIF, WebP, SVG. Taille maximale: 20MB
                  </p>
                </Upload.Dragger>
                {uploading && (
                  <div style={{ textAlign: 'center', marginTop: 16 }}>
                    Upload en cours...
                  </div>
                )}
              </div>
            ),
          },
        ]}
      />

      {selectedMedia && (
        <div className="media-selected-preview">
          <strong>Image sélectionnée:</strong>
          <div style={{ marginTop: 8 }}>
            <Image
              src={selectedMedia.url}
              alt={selectedMedia.originalFilename}
              style={{ maxWidth: '200px', maxHeight: '100px', objectFit: 'contain' }}
            />
            <div style={{ marginTop: 4, fontSize: '12px', color: '#666' }}>
              {selectedMedia.originalFilename}
            </div>
          </div>
        </div>
      )}
    </Modal>
  );
};

export default MediaLibrary;

