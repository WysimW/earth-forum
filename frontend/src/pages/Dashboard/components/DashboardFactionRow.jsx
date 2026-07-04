import React from 'react';
import { Link } from 'react-router-dom';
import styles from '../Dashboard.module.css';

const ALIGNMENT_LABELS = {
  hero: 'Super-héros',
  villain: 'Super-vilain',
  antihero: 'Anti-héros',
  vigilante: 'Vigilante',
  neutral: 'Neutre',
};

const DashboardFactionRow = ({ faction }) => {
  const statusLabel = faction.status === 'open' ? 'Ouverte' : 'Fermée';

  return (
    <Link
      to={faction.universe?.slug ? `/factions/${faction.id}` : '/mes-factions'}
      className={`${styles.factionRow} ${faction.isOwner ? styles.factionRowOwner : ''}`}
    >
      <div className={styles.factionMain}>
        <span className={styles.factionName}>
          {faction.isOwner && <span className={styles.factionCrown} title="Fondateur">♛</span>}
          {faction.name}
        </span>
        <span className={styles.factionMeta}>
          {faction.universe?.name}
          {faction.alignment && (
            <span className={styles.factionAlignment}>
              {ALIGNMENT_LABELS[faction.alignment] || faction.alignment}
            </span>
          )}
        </span>
        {!faction.isOwner && faction.myCharacters?.length > 0 && (
          <span className={styles.factionCharacters}>
            Personnages : {faction.myCharacters.join(', ')}
          </span>
        )}
      </div>
      <div className={styles.factionBadges}>
        <span className={`${styles.badge} ${faction.status === 'open' ? styles.badgeStatusOpen : styles.badgeStatusClosed}`}>
          {statusLabel}
        </span>
        <span className={styles.memberCount}>
          {(faction.membersCount?.characters ?? 0) + (faction.membersCount?.npcs ?? 0)} membre(s)
        </span>
      </div>
    </Link>
  );
};

export default DashboardFactionRow;
