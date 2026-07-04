import React from 'react';
import styles from '../Dashboard.module.css';

const TYPE_OPTIONS = [
  { value: 'all', label: 'Tous' },
  { value: 'roleplay', label: 'RP' },
  { value: 'hrp', label: 'HRP' },
];

const STATUS_OPTIONS = [
  { value: 'all', label: 'Tous' },
  { value: 'open', label: 'Ouverts' },
  { value: 'closed', label: 'Fermés' },
  { value: 'archived', label: 'Archivés' },
];

const DashboardThreadFilters = ({ threadType, threadStatus, onTypeChange, onStatusChange, universeLabel }) => (
  <div className={styles.filtersContainer}>
    <div className={styles.filtersGroup}>
      <span className={styles.filtersGroupLabel}>Type</span>
      <div className={styles.tabsRow}>
        {TYPE_OPTIONS.map((option) => (
          <button
            key={option.value}
            type="button"
            className={`${styles.tab} ${threadType === option.value ? styles.tabActive : ''}`}
            onClick={() => onTypeChange(option.value)}
          >
            {option.label}
          </button>
        ))}
      </div>
    </div>

    <div className={styles.filtersGroup}>
      <span className={styles.filtersGroupLabel}>Statut</span>
      <div className={styles.tabsRow}>
        {STATUS_OPTIONS.map((option) => (
          <button
            key={option.value}
            type="button"
            className={`${styles.tab} ${threadStatus === option.value ? styles.tabActive : ''}`}
            onClick={() => onStatusChange(option.value)}
          >
            {option.label}
          </button>
        ))}
      </div>
    </div>

    {universeLabel && (
      <p className={styles.universeHint}>
        Filtré pour l&apos;univers <strong>{universeLabel}</strong>
      </p>
    )}
  </div>
);

export default DashboardThreadFilters;
