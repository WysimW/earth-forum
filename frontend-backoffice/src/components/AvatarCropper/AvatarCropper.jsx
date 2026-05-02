import React, { useState, useCallback } from 'react';
import { Modal, Button, Slider, Space, message } from 'antd';
import Cropper from 'react-easy-crop';
import { getCroppedImg, blobToDataURL } from './utils';
import MediaLibrary from '../MediaLibrary/MediaLibrary';
import api from '../../services/api';
import 'react-easy-crop/react-easy-crop.css';
import './AvatarCropper.css';

const AvatarCropper = ({ open, onClose, onSelect, value }) => {
  const [mediaLibraryOpen, setMediaLibraryOpen] = useState(false);
  const [selectedImage, setSelectedImage] = useState(null);
  const [crop, setCrop] = useState({ x: 0, y: 0 });
  const [zoom, setZoom] = useState(1);
  const [croppedAreaPixels, setCroppedAreaPixels] = useState(null);
  const [cropping, setCropping] = useState(false);

  const onCropComplete = useCallback((croppedArea, croppedAreaPixels) => {
    setCroppedAreaPixels(croppedAreaPixels);
  }, []);

  const handleImageSelect = (media) => {
    setSelectedImage(media.url);
    setMediaLibraryOpen(false);
    setCrop({ x: 0, y: 0 });
    setZoom(1);
  };

  const handleCrop = async () => {
    if (!selectedImage || !croppedAreaPixels) {
      message.error('Veuillez sélectionner une image');
      return;
    }

    setCropping(true);
    try {
      console.log('Starting crop with image:', selectedImage);
      const croppedBlob = await getCroppedImg(selectedImage, croppedAreaPixels);
      
      if (!croppedBlob) {
        message.error('Erreur lors du recadrage : aucune image générée');
        setCropping(false);
        return;
      }

      console.log('Image cropped successfully, uploading...');
      // Uploader l'image recadrée vers S3
      const formData = new FormData();
      formData.append('file', croppedBlob, 'avatar-cropped.png');

      const uploadResponse = await api.post('/api/admin/media/upload', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });

      console.log('Upload successful:', uploadResponse.data);
      // Créer un objet similaire à celui de la médiathèque
      const croppedMedia = {
        url: uploadResponse.data.url,
        originalFilename: uploadResponse.data.originalFilename || 'Avatar recadré',
      };

      onSelect(croppedMedia);
      handleClose();
      message.success('Avatar recadré et uploadé avec succès');
    } catch (error) {
      console.error('Error cropping/uploading image:', error);
      const errorMessage = error.response?.data?.error || error.message || 'Erreur inconnue';
      console.error('Error details:', errorMessage);
      
      // En cas d'erreur d'upload, utiliser le blob local comme fallback
      try {
        console.log('Attempting fallback with local blob...');
        const croppedBlob = await getCroppedImg(selectedImage, croppedAreaPixels);
        
        if (!croppedBlob) {
          throw new Error('Impossible de générer le blob local');
        }
        
        const dataUrl = await blobToDataURL(croppedBlob);
        
        const croppedMedia = {
          url: dataUrl,
          originalFilename: 'Avatar recadré (local)',
        };

        onSelect(croppedMedia);
        handleClose();
        message.warning('Avatar recadré localement (non uploadé). Erreur: ' + errorMessage);
      } catch (fallbackError) {
        console.error('Fallback error:', fallbackError);
        message.error('Erreur lors du recadrage de l\'image : ' + (fallbackError.message || 'Erreur inconnue'));
      }
    } finally {
      setCropping(false);
    }
  };

  const handleClose = () => {
    setSelectedImage(null);
    setCrop({ x: 0, y: 0 });
    setZoom(1);
    setCroppedAreaPixels(null);
    onClose();
  };

  const handleOpenMediaLibrary = () => {
    setMediaLibraryOpen(true);
  };

  return (
    <>
      <Modal
        title="Sélectionner et recadrer l'avatar"
        open={open}
        onCancel={handleClose}
        width={600}
        footer={[
          <Button key="cancel" onClick={handleClose}>
            Annuler
          </Button>,
          <Button key="select" onClick={handleOpenMediaLibrary}>
            Choisir une image
          </Button>,
          <Button
            key="crop"
            type="primary"
            onClick={handleCrop}
            disabled={!selectedImage || cropping}
            loading={cropping}
          >
            Valider le recadrage
          </Button>,
        ]}
      >
        <div className="avatar-cropper-container">
          {!selectedImage ? (
            <div className="avatar-cropper-placeholder">
              <p>Aucune image sélectionnée</p>
              <Button type="primary" onClick={handleOpenMediaLibrary}>
                Choisir une image depuis la médiathèque
              </Button>
            </div>
          ) : (
            <>
              <div className="avatar-cropper-wrapper">
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
              <div className="avatar-cropper-controls">
                <Space direction="vertical" style={{ width: '100%' }}>
                  <div>
                    <label>Zoom</label>
                    <Slider
                      min={1}
                      max={3}
                      step={0.1}
                      value={zoom}
                      onChange={setZoom}
                    />
                  </div>
                  <Button onClick={handleOpenMediaLibrary} style={{ width: '100%' }}>
                    Changer d'image
                  </Button>
                </Space>
              </div>
            </>
          )}
        </div>
      </Modal>

      <MediaLibrary
        open={mediaLibraryOpen}
        onClose={() => setMediaLibraryOpen(false)}
        onSelect={handleImageSelect}
        value={value}
      />
    </>
  );
};

export default AvatarCropper;

