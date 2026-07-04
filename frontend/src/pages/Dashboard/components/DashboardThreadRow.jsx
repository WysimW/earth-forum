import React from 'react';
import { Link } from 'react-router-dom';
import styles from '../Dashboard.module.css';

const TYPE_LABELS = {
  roleplay: 'RP',
  hrp: 'HRP',
};

const STATUS_LABELS = {
  open: 'Ouvert',
  closed: 'Fermé',
  archived: 'Archivé',
};

const STATUS_BADGE_CLASS = {
  open: 'badgeStatusOpen',
  closed: 'badgeStatusClosed',
  archived: 'badgeStatusArchived',
};

const DashboardThreadRow = ({ thread }) => {
  const typeLabel = thread.isRoleplay || thread.type === 'roleplay' ? 'RP' : (TYPE_LABELS[thread.type] || 'HRP');
  const statusLabel = STATUS_LABELS[thread.status] || thread.status;

  return (
    <Link to={`/threads/${thread.slug || thread.id}`} className={styles.threadRow}>
      <div className={styles.threadRowMain}>
        <span className={styles.threadTitle}>{thread.title}</span>
        <div className={styles.threadMeta}>
          {thread.author?.pseudo && <span>{thread.author.pseudo}</span>}
          {thread.updatedAt && (
            <span>{new Date(thread.updatedAt).toLocaleDateString('fr-FR')}</span>
          )}
          {thread.universe?.name && <span>{thread.universe.name}</span>}
        </div>
      </div>
      <div className={styles.threadRowBadges}>
        <span className={`${styles.badge} ${styles.badgeType}`}>{typeLabel}</span>
        <span className={`${styles.badge} ${styles[STATUS_BADGE_CLASS[thread.status] || 'badgeStatusOpen']}`}>
          {statusLabel}
        </span>
        {thread.postCount != null && (
          <span className={styles.postCount}>{thread.postCount} posts</span>
        )}
      </div>
    </Link>
  );
};

export default DashboardThreadRow;
