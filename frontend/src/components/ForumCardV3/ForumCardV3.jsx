import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { getForumName } from '../../utils/forumUtils';
import styles from './ForumCardV3.module.css';

const ForumCardV3 = ({
  forum,
  compactSubforums = false,
  hasUnread = false,
  hasParticipatingHighlight = false,
  variant = 'default',
}) => {
  const navigate = useNavigate();
  const forumName = getForumName(forum);
  const subforums = forum.subforums || forum.subForums || [];
  const [showAllSubforums, setShowAllSubforums] = useState(false);

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

  const getForumTarget = (entry) => entry?.slug || entry?.id || entry?.forumId;
  const forumSlug = forum.slug || forum.id;
  const lastPost = forum.lastPost;
  const maxSubforumsToDisplay = compactSubforums ? 2 : 4;
  const hasHiddenSubforums = subforums.length > maxSubforumsToDisplay;
  const displayedSubforums = showAllSubforums ? subforums : subforums.slice(0, maxSubforumsToDisplay);

  const subforumsRow = subforums.length > 0 ? (
    <div className={`${styles.subforumsRow} ${compactSubforums ? styles.subforumsRowCompact : ''}`}>
      {displayedSubforums.map((sub) => (
        <div
          key={sub.id}
          className={`${styles.subforumChip} ${compactSubforums ? styles.subforumChipCompact : ''}`}
          role="button"
          tabIndex={0}
          onClick={(e) => {
            const target = getForumTarget(sub);
            if (!target) return;
            e.preventDefault();
            e.stopPropagation();
            navigate(`/forums/${target}`);
          }}
          onKeyDown={(e) => {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            const target = getForumTarget(sub);
            if (!target) return;
            e.preventDefault();
            e.stopPropagation();
            navigate(`/forums/${target}`);
          }}
        >
          <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
            <path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z" />
          </svg>
          {sub.name}
        </div>
      ))}
      {hasHiddenSubforums && !showAllSubforums && (
        <button
          type="button"
          className={`${styles.subforumMore} ${compactSubforums ? styles.subforumMoreCompact : ''}`}
          onClick={(e) => {
            e.preventDefault();
            e.stopPropagation();
            setShowAllSubforums(true);
          }}
        >
          +{subforums.length - maxSubforumsToDisplay}
        </button>
      )}
      {hasHiddenSubforums && showAllSubforums && (
        <button
          type="button"
          className={`${styles.subforumMore} ${compactSubforums ? styles.subforumMoreCompact : ''}`}
          onClick={(e) => {
            e.preventDefault();
            e.stopPropagation();
            setShowAllSubforums(false);
          }}
        >
          Voir moins
        </button>
      )}
    </div>
  ) : null;

  if (variant === 'compact') {
    const threadTarget = lastPost && (lastPost.threadSlug || lastPost.threadId);
    return (
      <Link
        to={`/forums/${forumSlug}`}
        className={`${styles.forumCard} ${styles.forumCardCompact} ${hasUnread ? styles.unread : ''} ${hasParticipatingHighlight ? styles.participatingHighlight : ''}`}
      >
        <div className={styles.compactRow}>
          <div className={styles.compactMain}>
            <div className={styles.compactHeaderRow}>
              <h3 className={styles.compactTitle}>{forumName}</h3>
            </div>
            {forum.description && (
              <p className={styles.compactDesc}>{forum.description}</p>
            )}
            {subforumsRow}
            <div className={styles.compactMeta}>
              <span>{forum.stats?.totalThreads || 0} discussions</span>
              <span className={styles.metaDot}>•</span>
              <span>{forum.stats?.totalPosts || 0} messages</span>
              {(hasUnread || hasParticipatingHighlight) && (
                <div className={styles.metaBadges}>
                  {hasUnread && <span className={styles.unreadPill}>Nouveau</span>}
                  {hasParticipatingHighlight && <span className={styles.participatingPill}>Participation</span>}
                </div>
              )}
            </div>
          </div>
          {threadTarget ? (
            <div
              className={styles.compactLastPost}
              onClick={(e) => {
                e.stopPropagation();
                e.preventDefault();
                navigate(`/threads/${lastPost.threadSlug || lastPost.threadId}`);
              }}
              role="button"
              tabIndex={0}
              onKeyDown={(e) => {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                e.stopPropagation();
                e.preventDefault();
                navigate(`/threads/${lastPost.threadSlug || lastPost.threadId}`);
              }}
            >
              {lastPost.avatar && (
                <img
                  src={lastPost.avatar}
                  alt=""
                  className={styles.compactLastAvatar}
                />
              )}
              <div className={styles.compactLastInfo}>
                <div className={styles.compactLastLabel}>Dernier message</div>
                <div className={styles.compactLastAuthor}>
                  {lastPost.character || lastPost.author}
                </div>
                {lastPost.date && (
                  <div className={styles.compactLastDate}>{formatDate(lastPost.date)}</div>
                )}
              </div>
            </div>
          ) : (
            <div className={styles.compactLastPostMuted}>
              <span className={styles.compactLastMutedText}>Aucune activité</span>
            </div>
          )}
        </div>
      </Link>
    );
  }

  return (
    <Link 
      to={`/forums/${forumSlug}`} 
      className={`${styles.forumCard} ${hasUnread ? styles.unread : ''} ${hasParticipatingHighlight ? styles.participatingHighlight : ''}`}
    >
      {/* Background banner */}
      {forum.banner && (
        <div 
          className={styles.cardBanner}
          style={{ backgroundImage: `url("${forum.banner}")` }}
        />
      )}
      <div className={styles.cardOverlay} />
      
      {/* Main content */}
      <div className={styles.cardContent}>
        {/* Left: Info */}
        <div className={styles.cardInfo}>
          <div className={styles.cardHeader}>
            <h3 className={styles.cardTitle}>{forumName}</h3>
          </div>
          
          {forum.description && (
            <p className={styles.cardDesc}>{forum.description}</p>
          )}
          
          {subforumsRow}
          
          <div className={styles.cardMeta}>
            <span>{forum.stats?.totalThreads || 0} discussions</span>
            <span className={styles.metaDot}>•</span>
            <span>{forum.stats?.totalPosts || 0} messages</span>
            {(hasUnread || hasParticipatingHighlight) && (
              <div className={styles.metaBadges}>
                {hasUnread && <span className={styles.unreadPill}>Nouveau</span>}
                {hasParticipatingHighlight && <span className={styles.participatingPill}>Participation</span>}
              </div>
            )}
          </div>
        </div>
        
        {/* Right: Last post sidebar */}
        {lastPost && (lastPost.threadSlug || lastPost.threadId) ? (
          <div 
            className={styles.cardSidebar}
            onClick={(e) => {
              e.stopPropagation();
              e.preventDefault();
              navigate(`/threads/${lastPost.threadSlug || lastPost.threadId}`);
            }}
            style={{ cursor: 'pointer' }}
            role="button"
            tabIndex={0}
            onKeyDown={(e) => {
              if (e.key === 'Enter' || e.key === ' ') {
                e.stopPropagation();
                e.preventDefault();
                navigate(`/threads/${lastPost.threadSlug || lastPost.threadId}`);
              }
            }}
          >
            <div className={styles.sidebarLabel}>Dernier message</div>
            {lastPost.threadTitle && (
              <div className={styles.sidebarThreadTitle}>{lastPost.threadTitle}</div>
            )}
            {lastPost.avatar && (
              <img 
                src={lastPost.avatar} 
                alt="" 
                className={styles.sidebarAvatar}
              />
            )}
            <div className={styles.sidebarAuthor}>
              {lastPost.character || lastPost.author}
            </div>
            <div className={styles.sidebarDate}>
              {formatDate(lastPost.date)}
            </div>
          </div>
        ) : (
          <div className={styles.cardSidebar}>
            <div className={styles.sidebarLabel}>Activité</div>
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" className={styles.noActivityIcon}>
              <circle cx="12" cy="12" r="10" />
              <line x1="12" y1="8" x2="12" y2="12" />
              <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
            <div className={styles.sidebarAuthor}>Aucune activité</div>
          </div>
        )}
      </div>
    </Link>
  );
};

export default ForumCardV3;
