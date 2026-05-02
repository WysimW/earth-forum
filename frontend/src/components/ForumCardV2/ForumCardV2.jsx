import React from 'react';
import { Link } from 'react-router-dom';
import { getForumName } from '../../utils/forumUtils';
import styles from './ForumCardV2.module.css';

const ForumCardV2 = ({ forum }) => {
  const forumName = getForumName(forum);
  const forumType = forum.type || 'hrp';
  const subforums = forum.subforums || forum.subForums || [];
  const subforumCount = forum.stats?.subforumCount || subforums.length;
  
  const getTypeLabel = () => {
    if (forumType === 'important') return 'Important';
    if (forumType === 'roleplay') return 'RP';
    return 'HRP';
  };

  const getTypeIcon = () => {
    if (forumType === 'important') {
      return (
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
          <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
        </svg>
      );
    }
    if (forumType === 'roleplay') {
      return (
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
          <circle cx="9" cy="7" r="4" />
          <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
          <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
      );
    }
    return (
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
      </svg>
    );
  };

  const formatDate = (dateString) => {
    if (!dateString) return '';
    try {
      const date = new Date(dateString);
      return date.toLocaleDateString('fr-FR', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    } catch {
      return dateString;
    }
  };

  return (
    <Link to={`/forums/${forum.id}`} className={`${styles.forumCard} ${styles[forumType]}`}>
      {/* Header avec badge de type */}
      <div className={styles.cardHeader}>
        <div className={styles.typeBadge}>
          {getTypeIcon()}
          <span>{getTypeLabel()}</span>
        </div>
        {forum.banner && (
          <div className={styles.banner}>
            <img src={forum.banner} alt={forumName} className={styles.bannerImage} />
            <div className={styles.bannerOverlay} />
          </div>
        )}
      </div>

      {/* Contenu principal */}
      <div className={styles.cardContent}>
        <h3 className={styles.title}>{forumName}</h3>
        
        {forum.description && (
          <p className={styles.description}>{forum.description}</p>
        )}

        {/* Statistiques */}
        {forum.stats && (
          <div className={styles.stats}>
            {subforumCount > 0 && (
              <div className={styles.statItem}>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                </svg>
                <span>{subforumCount} {subforumCount > 1 ? 'sous-forums' : 'sous-forum'}</span>
              </div>
            )}
            <div className={styles.statItem}>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
              </svg>
              <span>{forum.stats.totalThreads || 0} {forum.stats.totalThreads > 1 ? 'discussions' : 'discussion'}</span>
            </div>
            <div className={styles.statItem}>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
              </svg>
              <span>{forum.stats.totalPosts || 0} {forum.stats.totalPosts > 1 ? 'messages' : 'message'}</span>
            </div>
          </div>
        )}

        {/* Sous-forums */}
        {subforums.length > 0 && (
          <div className={styles.subforums}>
            <div className={styles.subforumsHeader}>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
              </svg>
              <span>Sous-forums</span>
            </div>
            <div className={styles.subforumsList}>
              {subforums.slice(0, 4).map((subforum) => (
                <Link
                  key={subforum.id}
                  to={`/forums/${subforum.id}`}
                  className={styles.subforumLink}
                  onClick={(e) => e.stopPropagation()}
                >
                  {subforum.name}
                </Link>
              ))}
              {subforums.length > 4 && (
                <span className={styles.subforumMore}>
                  +{subforums.length - 4} autres
                </span>
              )}
            </div>
          </div>
        )}

        {/* Dernier post */}
        {forum.lastPost && (
          <div className={styles.lastPost}>
            <div className={styles.lastPostHeader}>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
              </svg>
              <span>Dernière activité</span>
            </div>
            <div className={styles.lastPostContent}>
              <div className={styles.lastPostRow}>
                <span className={styles.lastPostLabel}>Dans :</span>
                <span className={styles.lastPostTitle}>
                  {forum.lastPost.threadTitle || forum.lastThread?.title}
                </span>
              </div>
              <div className={styles.lastPostRow}>
                <span className={styles.lastPostLabel}>Par :</span>
                <span className={styles.lastPostAuthor}>
                  {forum.lastPost.character || forum.lastPost.author || forum.lastThread?.author}
                </span>
              </div>
              <div className={styles.lastPostRow}>
                <span className={styles.lastPostLabel}>Le :</span>
                <span className={styles.lastPostDate}>
                  {formatDate(forum.lastPost.date)}
                </span>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Footer avec action */}
      <div className={styles.cardFooter}>
        <span className={styles.footerText}>Voir le forum</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
          <polyline points="9 18 15 12 9 6" />
        </svg>
      </div>
    </Link>
  );
};

export default ForumCardV2;






