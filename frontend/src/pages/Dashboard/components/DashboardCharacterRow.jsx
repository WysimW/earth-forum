import React from 'react';
import { Link } from 'react-router-dom';
import styles from '../Dashboard.module.css';

const STATUS_LABELS = {
  pending: 'En attente',
  validated: 'Validé',
  rejected: 'Refusé',
  draft: 'Brouillon',
  editing: 'En édition',
  abandoned: 'Abandonné',
};

const DashboardCharacterRow = ({ character }) => (
  <Link to="/characters" className={styles.characterRow}>
    {character.avatar ? (
      <img src={character.avatar} alt="" className={styles.characterAvatar} />
    ) : (
      <div className={styles.characterAvatarPlaceholder} aria-hidden="true" />
    )}
    <div className={styles.characterInfo}>
      <span className={styles.characterName}>{character.name}</span>
      <span className={`${styles.characterStatus} ${styles[`characterStatus${character.status}`] || ''}`}>
        {STATUS_LABELS[character.status] || character.status}
      </span>
      {character.universe?.name && (
        <span className={styles.characterUniverse}>{character.universe.name}</span>
      )}
    </div>
  </Link>
);

export default DashboardCharacterRow;
