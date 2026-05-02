import React, { useState, useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import styles from './CharacterCard.module.css';

const CharacterCard = ({ 
  character, 
  onDelete, 
  editPath,
  viewPath,
  onEditAvatar,
  onThemeChange,
  showUniverse = true,
  showRoleRp = false,
  roleRp,
  roleRpPlaceholder = 'Non défini',
  onRoleRpClick,
  roleRpEditor = null,
}) => {
  const navigate = useNavigate();
  const [dropdownOpen, setDropdownOpen] = useState(false);
  const [themeModalOpen, setThemeModalOpen] = useState(false);
  const [selectedTheme, setSelectedTheme] = useState(character.sheetTheme || 'default');
  const dropdownRef = useRef(null);

  // Mettre à jour selectedTheme quand character.sheetTheme change
  useEffect(() => {
    setSelectedTheme(character.sheetTheme || 'default');
  }, [character.sheetTheme]);

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setDropdownOpen(false);
      }
    };

    if (dropdownOpen) {
      document.addEventListener('mousedown', handleClickOutside);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [dropdownOpen]);

  const handleView = () => {
    if (viewPath) {
      navigate(viewPath);
    }
  };

  const handleEdit = () => {
    if (editPath) {
      navigate(editPath);
      setDropdownOpen(false);
    }
  };

  const handleEditAvatar = () => {
    if (onEditAvatar) {
      onEditAvatar(character);
      setDropdownOpen(false);
    }
  };

  const handleDelete = () => {
    if (onDelete && window.confirm('Êtes-vous sûr de vouloir supprimer ce personnage ?')) {
      onDelete(character.id);
      setDropdownOpen(false);
    }
  };

  const getStatusMessage = () => {
    if (character.statusMessage) {
      return character.statusMessage;
    }
    const statusMap = {
      draft: 'Brouillon',
      pending: 'En attente',
      validated: 'Validé',
      rejected: 'Rejeté',
      editing: 'En édition'
    };
    return statusMap[character.status] || character.status;
  };

  const shouldShowRoleRp = showRoleRp || roleRp !== undefined || !!roleRpEditor;
  const roleRpText = roleRp && roleRp.trim().length > 0 ? roleRp : roleRpPlaceholder;

  return (
    <div className={styles.card}>
      {character.avatar && (
        <div className={styles.cardImageContainer}>
          <img
            src={character.avatar}
            alt={character.name}
            className={styles.cardImage}
          />
          <div className={styles.imageGradient}></div>
          <div className={styles.statusBadgeContainer}>
            <span className={`${styles.statusBadge} ${styles[character.status]}`}>
              {getStatusMessage()}
            </span>
          </div>
        </div>
      )}
      <div className={styles.cardBody}>
        <div className={styles.cardHeader}>
          <h4 className={styles.cardTitle}>{character.name}</h4>
          {(character.firstName || character.lastName) && (
            <p className={styles.cardSubtitle}>
              {[character.firstName, character.lastName].filter(Boolean).join(' ')}
            </p>
          )}
        </div>
        
        <div className={styles.cardInfo}>
          {showUniverse && character.universe && (
            <div className={styles.infoItem}>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="12" r="10" />
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
              </svg>
              <span>{character.universe.name}</span>
            </div>
          )}
          {character.moralAffiliation && (
            <div className={styles.infoItem}>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M12 2L2 7l10 5 10-5-10-5z" />
                <path d="M2 17l10 5 10-5" />
                <path d="M2 12l10 5 10-5" />
              </svg>
              <span>{character.moralAffiliation}</span>
            </div>
          )}
          {shouldShowRoleRp && (
            <div
              className={`${styles.infoItem} ${onRoleRpClick && !roleRpEditor ? styles.infoItemInteractive : ''}`}
              onClick={() => {
                if (onRoleRpClick && !roleRpEditor) {
                  onRoleRpClick();
                }
              }}
              onKeyDown={(event) => {
                if (!onRoleRpClick || roleRpEditor) return;
                if (event.key === 'Enter' || event.key === ' ') {
                  event.preventDefault();
                  onRoleRpClick();
                }
              }}
              role={onRoleRpClick && !roleRpEditor ? 'button' : undefined}
              tabIndex={onRoleRpClick && !roleRpEditor ? 0 : undefined}
            >
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M7 20h10" />
                <path d="M9 16h6" />
                <path d="M12 2 6 7v4c0 4 2.5 7.5 6 9 3.5-1.5 6-5 6-9V7z" />
              </svg>
              {roleRpEditor ? (
                <div className={styles.infoItemEditor}>{roleRpEditor}</div>
              ) : (
                <span>{roleRpText}</span>
              )}
            </div>
          )}
        </div>

        <div className={styles.cardActions}>
          {viewPath && (
            <button
              className={styles.btnIcon}
              onClick={handleView}
              title="Voir"
              aria-label="Voir le personnage"
            >
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
            </button>
          )}
          {editPath && (
            <button
              className={styles.btnIcon}
              onClick={handleEdit}
              title="Modifier"
              aria-label="Modifier le personnage"
            >
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
              </svg>
            </button>
          )}
          <div className={styles.dropdownContainer} ref={dropdownRef}>
            <button
              className={styles.btnIcon}
              onClick={() => setDropdownOpen(!dropdownOpen)}
              title="Plus d'actions"
              aria-label="Plus d'actions"
              aria-expanded={dropdownOpen}
            >
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="12" r="1" />
                <circle cx="19" cy="12" r="1" />
                <circle cx="5" cy="12" r="1" />
              </svg>
            </button>
            {dropdownOpen && (
              <div className={styles.dropdownMenu}>
                {onEditAvatar && (
                  <button
                    className={styles.dropdownItem}
                    onClick={handleEditAvatar}
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                      <circle cx="8.5" cy="8.5" r="1.5" />
                      <polyline points="21 15 16 10 5 21" />
                    </svg>
                    <span>Modifier l'avatar</span>
                  </button>
                )}
                {onThemeChange && (
                  <button
                    className={styles.dropdownItem}
                    onClick={(e) => {
                      e.stopPropagation();
                      setSelectedTheme(character.sheetTheme || 'default');
                      setThemeModalOpen(true);
                      setDropdownOpen(false);
                    }}
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                      <path d="M9 9h6v6H9z" />
                    </svg>
                    <span>Thème de la fiche</span>
                  </button>
                )}
                {editPath && (
                  <button
                    className={styles.dropdownItem}
                    onClick={handleEdit}
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                    <span>Modifier</span>
                  </button>
                )}
                {onDelete && (
                  <button
                    className={`${styles.dropdownItem} ${styles.dropdownItemDanger}`}
                    onClick={handleDelete}
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <polyline points="3 6 5 6 21 6" />
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                    </svg>
                    <span>Supprimer</span>
                  </button>
                )}
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Modal de sélection de thème */}
      {themeModalOpen && (
        <div className={styles.modalOverlay} onClick={() => setThemeModalOpen(false)}>
          <div className={styles.modal} onClick={(e) => e.stopPropagation()}>
            <div className={styles.modalHeader}>
              <h3 className={styles.modalTitle}>Choisir un thème</h3>
              <button
                className={styles.modalClose}
                onClick={() => setThemeModalOpen(false)}
                aria-label="Fermer"
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <line x1="18" y1="6" x2="6" y2="18" />
                  <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
              </button>
            </div>
            <div className={styles.modalBody}>
              <div className={styles.themeOptions}>
                {[
                  { id: 'default', label: 'Par défaut' },
                  { id: 'dark-elegant', label: 'Sombre élégant' },
                  { id: 'colorful', label: 'Coloré' },
                  { id: 'minimal', label: 'Minimaliste' },
                  { id: 'comics', label: 'Comics' },
                  { id: 'vintage', label: 'Vintage' },
                ].map((theme) => (
                  <label key={theme.id} className={styles.themeOption}>
                    <input
                      type="radio"
                      name="theme"
                      value={theme.id}
                      checked={selectedTheme === theme.id}
                      onChange={() => setSelectedTheme(theme.id)}
                      className={styles.radioInput}
                    />
                    <span className={styles.radioLabel}>{theme.label}</span>
                  </label>
                ))}
              </div>
            </div>
            <div className={styles.modalFooter}>
              <button
                className={styles.modalButtonCancel}
                onClick={() => setThemeModalOpen(false)}
              >
                Annuler
              </button>
              <button
                className={styles.modalButtonConfirm}
                onClick={async () => {
                  if (onThemeChange) {
                    try {
                      await onThemeChange(character.id, selectedTheme);
                      setThemeModalOpen(false);
                    } catch (error) {
                      console.error('Erreur lors de la mise à jour du thème:', error);
                      alert('Erreur lors de la mise à jour du thème');
                    }
                  }
                }}
              >
                Appliquer
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default CharacterCard;

