import React, { useState, useEffect } from 'react';
import api from '../../services/api';
import './MediaLibrary.css';

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
      const mediaList = response.data?.media || response.data || [];
      setMedia(Array.isArray(mediaList) ? mediaList : []);
    } catch (error) {
      console.error('Error fetching media:', error);
      alert('Erreur lors du chargement de la médiathèque');
      setMedia([]);
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

      await fetchMedia();
      
      // Sélectionner automatiquement l'image uploadée
      const uploadedMedia = response.data;
      setSelectedMedia(uploadedMedia);
      setActiveTab('library');
    } catch (error) {
      console.error('Error uploading file:', error);
      const errorMessage = error.response?.data?.error || 'Erreur lors de l\'upload';
      alert(errorMessage);
    } finally {
      setUploading(false);
    }
  };

  const handleFileSelect = (e) => {
    const file = e.target.files[0];
    if (file) {
      handleUpload(file);
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
      alert('Veuillez sélectionner une image');
    }
  };

  const handleDelete = async (mediaId, e) => {
    e.stopPropagation();
    
    try {
      await api.delete(`/api/admin/media/${mediaId}`);
      await fetchMedia();
      if (selectedMedia?.id === mediaId) {
        setSelectedMedia(null);
      }
    } catch (error) {
      console.error('Error deleting media:', error);
      alert('Erreur lors de la suppression');
    }
  };

  if (!open) return null;

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-content" onClick={(e) => e.stopPropagation()}>
        <div className="modal-header">
          <h2 className="modal-title">Médiathèque</h2>
          <button className="modal-close" onClick={onClose}>×</button>
        </div>

        <div className="tabs">
          <button
            className={`tab ${activeTab === 'library' ? 'active' : ''}`}
            onClick={() => setActiveTab('library')}
          >
            Bibliothèque
          </button>
          <button
            className={`tab ${activeTab === 'upload' ? 'active' : ''}`}
            onClick={() => setActiveTab('upload')}
          >
            Uploader
          </button>
        </div>

        {activeTab === 'library' && (
          <div className="media-library-content">
            <div className="media-library-header">
              <input
                type="text"
                className="search-input"
                placeholder="Rechercher une image..."
                value={searchValue}
                onChange={(e) => setSearchValue(e.target.value)}
              />
            </div>

            {loading ? (
              <div className="loading-state">Chargement...</div>
            ) : filteredMedia.length === 0 ? (
              <div className="empty-state">Aucune image trouvée</div>
            ) : (
              <div className="media-grid">
                {filteredMedia.map((item) => (
                  <div
                    key={item.id}
                    className={`media-item ${selectedMedia?.id === item.id ? 'selected' : ''}`}
                    onClick={() => handleSelect(item)}
                  >
                    <div className="media-item-image">
                      <img src={item.url} alt={item.originalFilename} />
                      <div className="media-item-overlay">
                        <button
                          className="media-item-delete"
                          onClick={(e) => handleDelete(item.id, e)}
                        >
                          Supprimer
                        </button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}

        {activeTab === 'upload' && (
          <div className="media-upload-content">
            <input
              type="file"
              id="file-upload"
              accept="image/*"
              onChange={handleFileSelect}
              disabled={uploading}
            />
            <label htmlFor="file-upload" style={{ cursor: 'pointer' }}>
              {uploading ? (
                <div>Upload en cours...</div>
              ) : (
                <>
                  <div style={{ fontSize: '24px', marginBottom: '8px' }}>📤</div>
                  <div>Cliquez ou glissez une image ici pour l'uploader</div>
                  <div style={{ fontSize: '12px', color: '#999', marginTop: '8px' }}>
                    Formats supportés: JPG, PNG, GIF, WebP, SVG. Taille maximale: 20MB
                  </div>
                </>
              )}
            </label>
          </div>
        )}

        {selectedMedia && (
          <div className="media-selected-preview">
            <strong>Image sélectionnée:</strong>
            <div style={{ marginTop: 8 }}>
              <img
                src={selectedMedia.url}
                alt={selectedMedia.originalFilename}
              />
              <div style={{ marginTop: 4, fontSize: '12px', color: '#666' }}>
                {selectedMedia.originalFilename}
              </div>
            </div>
          </div>
        )}

        <div className="modal-footer">
          <button className="btn btn-secondary" onClick={onClose}>
            Annuler
          </button>
          <button className="btn btn-primary" onClick={handleConfirm} disabled={!selectedMedia}>
            Utiliser cette image
          </button>
        </div>
      </div>
    </div>
  );
};

export default MediaLibrary;






