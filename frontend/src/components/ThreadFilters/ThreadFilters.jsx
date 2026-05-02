import React from 'react';
import styles from './ThreadFilters.module.css';

const STATUS_OPTIONS = [
  { value: 'all', label: 'Tous' },
  { value: 'open', label: 'Ouverts' },
  { value: 'closed', label: 'Fermés' },
  { value: 'archived', label: 'Archivés' },
];

const ThreadFilters = ({
  status,
  searchInput,
  myParticipations,
  isAuthenticated,
  onStatusChange,
  onSearchChange,
  onMyParticipationsChange,
}) => {
  return (
    <div className={styles.filtersContainer}>
      <div className={styles.tabsContainer}>
        {/* Onglets de statut */}
        <div className={styles.tabsRow}>
          {STATUS_OPTIONS.map((option) => (
            <button
              key={option.value}
              type="button"
              className={`${styles.tab} ${status === option.value ? styles.active : ''}`}
              onClick={() => onStatusChange(option.value)}
            >
              {option.label}
            </button>
          ))}
        </div>

        {/* Barre d'outils : recherche + toggle */}
        <div className={styles.toolsRow}>
          <div className={styles.searchBox}>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.35-4.35" />
            </svg>
            <input
              type="text"
              placeholder="Rechercher..."
              value={searchInput}
              onChange={(e) => onSearchChange(e.target.value)}
              className={styles.searchInput}
            />
            {searchInput && (
              <button
                type="button"
                onClick={() => onSearchChange('')}
                className={styles.clearButton}
                aria-label="Effacer la recherche"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <line x1="18" y1="6" x2="6" y2="18" />
                  <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
              </button>
            )}
          </div>

          {isAuthenticated && (
            <label className={styles.toggleLabel}>
              <input
                type="checkbox"
                checked={myParticipations}
                onChange={(e) => onMyParticipationsChange(e.target.checked)}
                className={styles.toggleInput}
              />
              <span className={styles.toggleSwitch}>
                <span className={styles.toggleSlider} />
              </span>
              <span className={styles.toggleText}>Mes participations</span>
            </label>
          )}
        </div>
      </div>
    </div>
  );
};

export default ThreadFilters;
