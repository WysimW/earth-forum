import React, { useState, useCallback, useEffect } from 'react';
import Cropper from 'react-easy-crop';
import { getCroppedImg, blobToDataURL } from './utils';
import api from '../../services/api';
import 'react-easy-crop/react-easy-crop.css';
import styles from './AvatarCropper.module.css';

const AvatarCropper = ({ open, onClose, onSelect, value }) => {
  const [activeTab, setActiveTab] = useState('library'); // 'library' ou 'upload'
  const [selectedImage, setSelectedImage] = useState(null);
  const [crop, setCrop] = useState({ x: 0, y: 0 });
  const [zoom, setZoom] = useState(1);
  const [croppedAreaPixels, setCroppedAreaPixels] = useState(null);
  const [cropping, setCropping] = useState(false);
  const [media, setMedia] = useState([]);
  const [filteredMedia, setFilteredMedia] = useState([]);
  const [loading, setLoading] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [searchValue, setSearchValue] = useState('');
  const [selectedMedia, setSelectedMedia] = useState(null);
  const [selectedOriginalMedia, setSelectedOriginalMedia] = useState(null);
  const [croppedVersions, setCroppedVersions] = useState([]);

  const onCropComplete = useCallback((croppedArea, croppedAreaPixels) => {
    setCroppedAreaPixels(croppedAreaPixels);
  }, []);

  useEffect(() => {
    if (open && activeTab === 'library') {
      fetchMedia();
    }
  }, [open, activeTab]);

  useEffect(() => {
    filterMedia();
  }, [searchValue, media]);

  useEffect(() => {
    if (selectedOriginalMedia && media.length > 0) {
      loadCroppedVersions(selectedOriginalMedia.id);
    }
  }, [selectedOriginalMedia, media]);

  const fetchMedia = async () => {
    setLoading(true);
    try {
      // Récupérer uniquement les images originales (sans parentMedia)
      const response = await api.get('/api/media?type=image&originalsOnly=true');
      const mediaList = response.data?.media || response.data || [];
      setMedia(Array.isArray(mediaList) ? mediaList : []);
    } catch (error) {
      console.error('Error fetching media:', error);
      setMedia([]);
    } finally {
      setLoading(false);
    }
  };

  const filterMedia = () => {
    // Les images sont déjà filtrées côté backend (originalsOnly=true)
    // On filtre uniquement par recherche
    if (!searchValue) {
      setFilteredMedia(media);
      return;
    }
    const filtered = media.filter((item) =>
      item.originalFilename?.toLowerCase().includes(searchValue.toLowerCase())
    );
    setFilteredMedia(filtered);
  };

  const loadCroppedVersions = async (originalMediaId) => {
    try {
      // Utiliser le nouvel endpoint API pour récupérer les versions croppées
      const response = await api.get(`/api/media/${originalMediaId}/cropped`);
      const versions = response.data?.media || [];
      setCroppedVersions(versions);
    } catch (error) {
      console.error('Error loading cropped versions:', error);
      setCroppedVersions([]);
    }
  };

  const handleOriginalImageClick = (mediaItem) => {
    setSelectedOriginalMedia(mediaItem);
    loadCroppedVersions(mediaItem.id);
  };

  const handleSelectCroppedVersion = (croppedMedia) => {
    onSelect(croppedMedia);
    handleClose();
  };

  const handleCreateNewCrop = () => {
    if (selectedOriginalMedia) {
      setSelectedImage(selectedOriginalMedia.url);
      setSelectedMedia(selectedOriginalMedia);
      setSelectedOriginalMedia(null);
      setCroppedVersions([]);
      setActiveTab(null);
      setCrop({ x: 0, y: 0 });
      setZoom(1);
    }
  };

  const handleImageSelect = (mediaItem) => {
    setSelectedImage(mediaItem.url);
    setSelectedMedia(mediaItem);
    setActiveTab(null); // Fermer les onglets pour afficher le cropper
    setCrop({ x: 0, y: 0 });
    setZoom(1);
  };

  const handleDeleteMedia = async (mediaId, e) => {
    e.stopPropagation();
    
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
      return;
    }

    try {
      await api.delete(`/api/media/${mediaId}`);
      await fetchMedia();
      // Si l'image supprimée était sélectionnée, réinitialiser
      if (selectedMedia?.id === mediaId) {
        setSelectedImage(null);
        setSelectedMedia(null);
      }
      // Si l'image originale supprimée était affichée, revenir à la liste
      if (selectedOriginalMedia?.id === mediaId) {
        setSelectedOriginalMedia(null);
        setCroppedVersions([]);
      }
      // Si une version croppée était supprimée, recharger les versions
      if (selectedOriginalMedia && croppedVersions.some(v => v.id === mediaId)) {
        loadCroppedVersions(selectedOriginalMedia.id);
      }
    } catch (error) {
      console.error('Error deleting media:', error);
      alert('Erreur lors de la suppression de l\'image');
    }
  };

  const handleUpload = async (file) => {
    setUploading(true);
    const formData = new FormData();
    formData.append('file', file);

    try {
      const response = await api.post('/api/media/upload', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });

      await fetchMedia();
      const uploadedMedia = response.data;
      handleImageSelect(uploadedMedia);
    } catch (error) {
      console.error('Error uploading file:', error);
      alert(error.response?.data?.error || 'Erreur lors de l\'upload');
    } finally {
      setUploading(false);
    }
  };

  const handleFileSelect = (e) => {
    const file = e.target.files[0];
    if (file && file.type.startsWith('image/')) {
      handleUpload(file);
    } else {
      alert('Veuillez sélectionner un fichier image');
    }
  };

  const handleCrop = async () => {
    if (!selectedImage || !croppedAreaPixels) {
      alert('Veuillez sélectionner une image');
      return;
    }

    setCropping(true);
    try {
      const croppedBlob = await getCroppedImg(selectedImage, croppedAreaPixels);
      
      if (!croppedBlob) {
        alert('Erreur lors du recadrage : aucune image générée');
        setCropping(false);
        return;
      }

      // Uploader l'image recadrée vers S3
      const formData = new FormData();
      formData.append('file', croppedBlob, 'avatar-cropped.png');
      // Envoyer parentMediaId si on a une image source sélectionnée
      if (selectedMedia?.id) {
        formData.append('parentMediaId', selectedMedia.id);
      }

      try {
        const uploadResponse = await api.post('/api/media/upload', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        });

        const croppedMedia = {
          id: uploadResponse.data.id,
          url: uploadResponse.data.url,
          originalFilename: uploadResponse.data.originalFilename || 'Avatar recadré',
          parentMediaId: uploadResponse.data.parentMediaId,
        };

        onSelect(croppedMedia);
        handleClose();
      } catch (uploadError) {
        console.error('Error uploading:', uploadError);
        const dataUrl = await blobToDataURL(croppedBlob);
        
        const croppedMedia = {
          url: dataUrl,
          originalFilename: 'Avatar recadré (local)',
        };

        onSelect(croppedMedia);
        handleClose();
        alert('Avatar recadré localement (non uploadé). Erreur: ' + (uploadError.response?.data?.error || uploadError.message));
      }
    } catch (error) {
      console.error('Error cropping image:', error);
      alert('Erreur lors du recadrage de l\'image : ' + (error.message || 'Erreur inconnue'));
    } finally {
      setCropping(false);
    }
  };

  useEffect(() => {
    if (open) {
      // Réinitialiser à l'état initial quand le modal s'ouvre
      setSelectedImage(null);
      setSelectedMedia(null);
      setSelectedOriginalMedia(null);
      setCroppedVersions([]);
      setCrop({ x: 0, y: 0 });
      setZoom(1);
      setCroppedAreaPixels(null);
      setActiveTab('library');
      setSearchValue('');
    }
  }, [open]);

  const handleClose = () => {
    setSelectedImage(null);
    setSelectedMedia(null);
    setSelectedOriginalMedia(null);
    setCroppedVersions([]);
    setCrop({ x: 0, y: 0 });
    setZoom(1);
    setCroppedAreaPixels(null);
    setActiveTab('library');
    setSearchValue('');
    onClose();
  };

  const handleBackToSelection = () => {
    setSelectedImage(null);
    setSelectedMedia(null);
    setSelectedOriginalMedia(null);
    setCroppedVersions([]);
    setActiveTab('library');
  };

  if (!open) return null;

  return (
    <div className={styles.overlay} onClick={handleClose}>
      <div className={styles.modal} onClick={(e) => e.stopPropagation()}>
        <div className={styles.modalHeader}>
          <h2 className={styles.modalTitle}>Sélectionner et recadrer l'avatar</h2>
          <button className={styles.closeButton} onClick={handleClose} aria-label="Fermer">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <line x1="18" y1="6" x2="6" y2="18" />
              <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </button>
        </div>

        <div className={styles.modalBody}>
          {!selectedImage ? (
            <>
              {/* Onglets */}
              <div className={styles.tabs}>
                <button
                  className={`${styles.tab} ${activeTab === 'library' ? styles.tabActive : ''}`}
                  onClick={() => setActiveTab('library')}
                >
                  Médiathèque
                </button>
                <button
                  className={`${styles.tab} ${activeTab === 'upload' ? styles.tabActive : ''}`}
                  onClick={() => setActiveTab('upload')}
                >
                  Upload
                </button>
              </div>

              {/* Contenu des onglets */}
              <div className={styles.tabContent}>
                {activeTab === 'library' && (
                  <div className={styles.mediaLibrary}>
                    {selectedOriginalMedia ? (
                      <>
                        {/* Vue détaillée avec versions croppées */}
                        <div className={styles.originalImageHeader}>
                          <button
                            className={styles.backButton}
                            onClick={() => {
                              setSelectedOriginalMedia(null);
                              setCroppedVersions([]);
                            }}
                          >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                              <polyline points="15 18 9 12 15 6" />
                            </svg>
                            Retour
                          </button>
                          <h3 className={styles.originalImageTitle}>
                            {selectedOriginalMedia.originalFilename}
                          </h3>
                        </div>
                        <div className={styles.originalImagePreview}>
                          <img src={selectedOriginalMedia.url} alt={selectedOriginalMedia.originalFilename} />
                        </div>
                        <div className={styles.croppedVersionsSection}>
                          <div className={styles.sectionHeader}>
                            <h4>Versions recadrées</h4>
                            <button
                              className={styles.btnCreateCrop}
                              onClick={handleCreateNewCrop}
                            >
                              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                                <circle cx="8.5" cy="8.5" r="1.5" />
                                <polyline points="21 15 16 10 5 21" />
                              </svg>
                              Créer une nouvelle version
                            </button>
                          </div>
                          {croppedVersions.length === 0 ? (
                            <div className={styles.emptyState}>
                              <p>Aucune version recadrée</p>
                              <p className={styles.emptyStateHint}>Cliquez sur "Créer une nouvelle version" pour recadrer cette image</p>
                            </div>
                          ) : (
                            <div className={styles.croppedVersionsGrid}>
                              {croppedVersions.map((version) => (
                                <div
                                  key={version.id}
                                  className={styles.croppedVersionItem}
                                  onClick={() => handleSelectCroppedVersion(version)}
                                >
                                  <img src={version.url} alt={version.originalFilename} />
                                  <div className={styles.croppedVersionOverlay}>
                                    <span className={styles.selectLabel}>Sélectionner</span>
                                  </div>
                                  <button
                                    className={styles.deleteButton}
                                    onClick={(e) => handleDeleteMedia(version.id, e)}
                                    title="Supprimer"
                                    aria-label="Supprimer cette version"
                                  >
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                      <polyline points="3 6 5 6 21 6" />
                                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                    </svg>
                                  </button>
                                </div>
                              ))}
                            </div>
                          )}
                        </div>
                      </>
                    ) : (
                      <>
                        {/* Liste des images originales */}
                        <div className={styles.searchBar}>
                          <input
                            type="text"
                            placeholder="Rechercher..."
                            value={searchValue}
                            onChange={(e) => setSearchValue(e.target.value)}
                            className={styles.searchInput}
                          />
                        </div>
                        {loading ? (
                          <div className={styles.loading}>Chargement...</div>
                        ) : filteredMedia.length === 0 ? (
                          <div className={styles.emptyState}>
                            <p>Aucune image trouvée</p>
                          </div>
                        ) : (
                          <div className={styles.mediaGrid}>
                            {filteredMedia.map((item) => (
                              <div
                                key={item.id}
                                className={styles.mediaItem}
                                onClick={() => handleOriginalImageClick(item)}
                              >
                                <img src={item.url} alt={item.originalFilename} />
                                <button
                                  className={styles.deleteButton}
                                  onClick={(e) => handleDeleteMedia(item.id, e)}
                                  title="Supprimer"
                                  aria-label="Supprimer cette image"
                                >
                                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <polyline points="3 6 5 6 21 6" />
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                  </svg>
                                </button>
                              </div>
                            ))}
                          </div>
                        )}
                      </>
                    )}
                  </div>
                )}

                {activeTab === 'upload' && (
                  <div className={styles.uploadArea}>
                    <div className={styles.uploadZone}>
                      <input
                        type="file"
                        id="file-upload"
                        accept="image/*"
                        onChange={handleFileSelect}
                        className={styles.fileInput}
                        disabled={uploading}
                      />
                      <label htmlFor="file-upload" className={styles.uploadLabel}>
                        {uploading ? (
                          <div className={styles.uploading}>
                            <span>Upload en cours...</span>
                          </div>
                        ) : (
                          <>
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                              <polyline points="17 8 12 3 7 8" />
                              <line x1="12" y1="3" x2="12" y2="15" />
                            </svg>
                            <span>Cliquez pour télécharger ou glissez-déposez</span>
                            <span className={styles.uploadHint}>PNG, JPG, GIF jusqu'à 10MB</span>
                          </>
                        )}
                      </label>
                    </div>
                  </div>
                )}
              </div>
            </>
          ) : (
            <>
              {/* Mode recadrage */}
              <div className={styles.cropperWrapper}>
                <Cropper
                  image={selectedImage}
                  crop={crop}
                  zoom={zoom}
                  aspect={1}
                  onCropChange={setCrop}
                  onZoomChange={setZoom}
                  onCropComplete={onCropComplete}
                  cropShape="round"
                  showGrid={false}
                />
              </div>
              <div className={styles.cropperControls}>
                <div className={styles.sliderContainer}>
                  <label className={styles.sliderLabel}>Zoom</label>
                  <input
                    type="range"
                    className={styles.slider}
                    min={1}
                    max={3}
                    step={0.1}
                    value={zoom}
                    onChange={(e) => setZoom(parseFloat(e.target.value))}
                  />
                </div>
                <button className={styles.btnSecondary} onClick={handleBackToSelection}>
                  Changer d'image
                </button>
              </div>
            </>
          )}
        </div>

        <div className={styles.modalFooter}>
          <button className={styles.btnCancel} onClick={handleClose}>
            Annuler
          </button>
          {selectedImage && (
            <button
              className={styles.btnPrimary}
              onClick={handleCrop}
              disabled={!selectedImage || cropping}
            >
              {cropping ? 'Recadrage...' : 'Valider le recadrage'}
            </button>
          )}
        </div>
      </div>
    </div>
  );
};

export default AvatarCropper;
