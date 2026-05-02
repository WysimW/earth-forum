import React from 'react';
import { Link } from 'react-router-dom';
import { getForumName } from '../../utils/forumUtils';
import styles from './ForumCard.module.css';

const ForumCard = ({ forum }) => {
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
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
          <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
        </svg>
      );
    }
    if (forumType === 'roleplay') {
      return (
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
          <circle cx="9" cy="7" r="4" />
          <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
          <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
      );
    }
    return (
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
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
    <div className={`${styles.forumCard} ${styles[forumType]}`}>
      <div className={styles.typeBadge}>
        {getTypeIcon()}
        {getTypeLabel()}
      </div>
      
      <Link to={`/forums/${forum.id}`} className={styles.cardLink}>
        {forum.banner && (
          <div className={styles.banner}>
            <img src={forum.banner} alt={forumName} className={styles.bannerImage} />
          </div>
        )}
        <div className={styles.content}>
          <h3 className={styles.title}>{forumName}</h3>
          {forum.description && (
            <p className={styles.description}>{forum.description}</p>
          )}
          
          {forum.stats && (
            <div className={styles.stats}>
              {subforumCount > 0 && (
                <span className={styles.statItem}>
                  {subforumCount} {subforumCount > 1 ? 'sous-forums' : 'sous-forum'}
                </span>
              )}
              <span className={styles.statItem}>
                {forum.stats.totalThreads || 0} {forum.stats.totalThreads > 1 ? 'discussions' : 'discussion'}
              </span>
              <span className={styles.statItem}>
                {forum.stats.totalPosts || 0} {forum.stats.totalPosts > 1 ? 'messages' : 'message'}
              </span>
            </div>
          )}

          {subforums.length > 0 && (
            <div className={styles.subforums}>
              <small className={styles.subforumsLabel}>Sous-forums :</small>
              <div className={styles.subforumsList}>
                {subforums.slice(0, 5).map((subforum) => (
                  <Link
                    key={subforum.id}
                    to={`/forums/${subforum.id}`}
                    className={styles.subforumBadge}
                    onClick={(e) => e.stopPropagation()}
                  >
                    {subforum.name}
                  </Link>
                ))}
                {subforums.length > 5 && (
                  <span className={styles.subforumBadge}>
                    +{subforums.length - 5} autres
                  </span>
                )}
              </div>
            </div>
          )}

          {forum.lastPost && (
            <div className={styles.lastPost}>
              <div className={styles.lastPostRow}>
                <strong>Dernière activité :</strong>
                <span>{formatDate(forum.lastPost.date)}</span>
              </div>
              <div className={styles.lastPostRow}>
                <strong>Dans :</strong>
                <span className={styles.lastPostTitle}>{forum.lastPost.threadTitle || forum.lastThread?.title}</span>
              </div>
              <div className={styles.lastPostRow}>
                <strong>Par :</strong>
                <span>{forum.lastPost.character || forum.lastPost.author || forum.lastThread?.author}</span>
              </div>
            </div>
          )}
        </div>
      </Link>
    </div>
  );
};

export default ForumCard;

