import React, { useState } from 'react';
import AvatarCropper from '../AvatarCropper/AvatarCropper';
import styles from './AvatarEditor.module.css';

const AvatarEditor = ({ 
  open, 
  onClose, 
  currentAvatar,
  characterName,
  onSave,
  saving = false
}) => {
  const [selectedAvatar, setSelectedAvatar] = useState(currentAvatar);
  const [avatarCropperOpen, setAvatarCropperOpen] = useState(false);

  const handleAvatarSelect = (croppedMedia) => {
    setSelectedAvatar(croppedMedia.url);
    setAvatarCropperOpen(false);
  };

  const handleOpenCropper = () => {
    setAvatarCropperOpen(true);
  };

  const handleSave = () => {
    if (onSave) {
      onSave(selectedAvatar ?? null);
    }
  };

  const handleCancel = () => {
    setSelectedAvatar(currentAvatar);
    onClose();
  };

  const handleRemoveAvatar = () => {
    setSelectedAvatar(null);
  };

  if (!open) return null;

  return (
    <>
      <div className={styles.overlay} onClick={handleCancel}></div>
      <div className={styles.modal}>
        <div className={styles.modalHeader}>
          <h2 className={styles.modalTitle}>Modifier l'avatar</h2>
          {characterName && (
            <p className={styles.modalSubtitle}>{characterName}</p>
          )}
          <button
            className={styles.closeButton}
            onClick={handleCancel}
            aria-label="Fermer"
          >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <line x1="18" y1="6" x2="6" y2="18" />
              <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </button>
        </div>

        <div className={styles.modalBody}>
          <div className={styles.avatarPreview}>
            {selectedAvatar ? (
              <img 
                src={selectedAvatar} 
                alt="Avatar" 
                className={styles.avatarImage}
              />
            ) : (
              <div className={styles.avatarPlaceholder}>
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                  <circle cx="12" cy="7" r="4" />
                </svg>
                <span>Aucun avatar</span>
              </div>
            )}
          </div>

          <div className={styles.actions}>
            <button
              className={styles.btnSecondary}
              onClick={handleOpenCropper}
            >
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                <circle cx="8.5" cy="8.5" r="1.5" />
                <polyline points="21 15 16 10 5 21" />
              </svg>
              {selectedAvatar ? 'Changer l\'avatar' : 'Choisir un avatar'}
            </button>
            {selectedAvatar && (
              <button
                className={styles.btnDanger}
                onClick={handleRemoveAvatar}
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <polyline points="3 6 5 6 21 6" />
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                </svg>
                Supprimer l'avatar
              </button>
            )}
          </div>
        </div>

        <div className={styles.modalFooter}>
          <button
            className={styles.btnCancel}
            onClick={handleCancel}
            disabled={saving}
          >
            Annuler
          </button>
          <button
            className={styles.btnSave}
            onClick={handleSave}
            disabled={saving || selectedAvatar === currentAvatar}
          >
            {saving ? 'Sauvegarde...' : 'Enregistrer'}
          </button>
        </div>
      </div>

      <AvatarCropper
        open={avatarCropperOpen}
        onClose={() => setAvatarCropperOpen(false)}
        onSelect={handleAvatarSelect}
        value={selectedAvatar ? { url: selectedAvatar } : null}
      />
    </>
  );
};

export default AvatarEditor;

