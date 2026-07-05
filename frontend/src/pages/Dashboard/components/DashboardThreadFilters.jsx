import React from 'react';
import FilterToggle from '../../../components/FilterToggle/FilterToggle';
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
      <FilterToggle options={TYPE_OPTIONS} value={threadType} onChange={onTypeChange} ariaLabel="Type de thread" />
    </div>

    <div className={styles.filtersGroup}>
      <span className={styles.filtersGroupLabel}>Statut</span>
      <FilterToggle options={STATUS_OPTIONS} value={threadStatus} onChange={onStatusChange} ariaLabel="Statut du thread" />
    </div>

    {universeLabel && (
      <p className={styles.universeHint}>
        Filtré pour l&apos;univers <strong>{universeLabel}</strong>
      </p>
    )}
  </div>
);

export default DashboardThreadFilters;
