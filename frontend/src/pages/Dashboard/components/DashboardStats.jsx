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

const DashboardStats = ({ stats }) => {
  if (!stats) return null;

  const items = [
    { label: 'Personnages', value: stats.characters, link: '/characters' },
    {
      label: 'Threads créés',
      value: stats.threadsCreated?.total ?? 0,
      detail: `${stats.threadsCreated?.open ?? 0} ouverts`,
    },
    {
      label: 'Participations',
      value: stats.threadsParticipating?.total ?? 0,
      detail: `${stats.threadsParticipating?.open ?? 0} ouverts`,
    },
    { label: 'Messages postés', value: stats.posts },
    { label: 'Messages non lus', value: stats.unreadMessages, link: '/messagerie' },
    { label: 'Factions', value: stats.factions, link: '/mes-factions' },
  ];

  return (
    <div className={styles.statsGrid}>
      {items.map((item) => {
        const content = (
          <>
            <span className={styles.statValue}>{item.value}</span>
            <span className={styles.statLabel}>{item.label}</span>
            {item.detail && <span className={styles.statDetail}>{item.detail}</span>}
          </>
        );

        return item.link ? (
          <Link key={item.label} to={item.link} className={styles.statCard}>
            {content}
          </Link>
        ) : (
          <div key={item.label} className={styles.statCard}>
            {content}
          </div>
        );
      })}
    </div>
  );
};

export default DashboardStats;
