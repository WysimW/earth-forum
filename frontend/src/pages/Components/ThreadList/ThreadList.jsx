import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import Layout from '../../../components/Layout/Layout';
import Breadcrumb from '../../../components/Breadcrumb/Breadcrumb';
import styles from './ThreadList.module.css';

const ThreadList = () => {
  const [selectedStyle, setSelectedStyle] = useState('style5f');

  // Données de test pour des threads
  const mockThreads = [
    {
      threadId: 1,
      threadSlug: 'discussion-sur-les-evenements-recents',
      title: 'Discussion sur les événements récents à Gotham',
      author: 'BruceW',
      authorAvatar: 'https://via.placeholder.com/40',
      createdAt: '15/01/24',
      lastPost: {
        author: 'SelinaK',
        avatar: 'https://via.placeholder.com/40',
        date: 'Il y a 2 heures',
        excerpt: 'Je pense que nous devrions examiner cette affaire plus en détail...'
      },
      replies: 12,
      views: 145
    },
    {
      threadId: 2,
      threadSlug: 'nouvelle-mission-batman',
      title: 'Nouvelle mission pour Batman',
      author: 'AlfredP',
      authorAvatar: 'https://via.placeholder.com/40',
      createdAt: '14/01/24',
      lastPost: {
        author: 'BruceW',
        avatar: 'https://via.placeholder.com/40',
        date: 'Il y a 5 heures',
        excerpt: 'La situation est sous contrôle maintenant.'
      },
      replies: 8,
      views: 89,
      pinned: true
    },
    {
      threadId: 3,
      threadSlug: 'rencontre-avec-le-joker',
      title: 'Rencontre avec le Joker',
      author: 'HarleyQ',
      authorAvatar: 'https://via.placeholder.com/40',
      createdAt: '13/01/24',
      lastPost: {
        author: 'Joker',
        avatar: 'https://via.placeholder.com/40',
        date: 'Hier',
        excerpt: 'Haha, quelle surprise !'
      },
      replies: 25,
      views: 312,
      locked: true
    },
    {
      threadId: 4,
      threadSlug: 'strategie-pour-la-nuit',
      title: 'Stratégie pour la nuit',
      author: 'Nightwing',
      authorAvatar: 'https://via.placeholder.com/40',
      createdAt: '12/01/24',
      lastPost: {
        author: 'Batgirl',
        avatar: 'https://via.placeholder.com/40',
        date: 'Il y a 2 jours',
        excerpt: 'Je suis d\'accord avec cette approche.'
      },
      replies: 5,
      views: 67
    }
  ];

  const breadcrumbItems = [
    { name: 'Accueil', url: '/' },
    { name: 'Forums', url: '/forums' },
    { name: 'Gotham City', url: null }
  ];

  const stylesList = [
    { id: 'style5', name: 'Minimaliste - Bordure gauche' },
    { id: 'style5a', name: 'Minimaliste - Point coloré' },
    { id: 'style5b', name: 'Minimaliste - Ligne séparation' },
    { id: 'style5c', name: 'Minimaliste - Fond hover' },
    { id: 'style5d', name: 'Minimaliste - Avatar compact' },
    { id: 'style5e', name: 'Minimaliste - Badge statut' },
    { id: 'style5f', name: 'Minimaliste - Avatar + Badge statut' },
  ];


  const renderStyle5 = () => (
    <div className={styles.style5}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.forumHeader}>
        <h1 className={styles.forumTitle}>Gotham City</h1>
      </div>
      <div className={styles.threadsList}>
        {mockThreads.map((thread) => (
          <Link 
            key={thread.threadId} 
            to={`/threads/${thread.threadSlug}`}
            className={styles.threadItem}
          >
            <div className={styles.threadContent}>
              <h3 className={styles.threadTitle}>{thread.title}</h3>
              <div className={styles.threadInfo}>
                <span>{thread.author}</span>
                <span className={styles.separator}>•</span>
                <span>{thread.createdAt}</span>
                <span className={styles.separator}>•</span>
                <span>{thread.replies} réponses</span>
                {thread.lastPost && (
                  <>
                    <span className={styles.separator}>•</span>
                    <span>Dernier: {thread.lastPost.author}</span>
                  </>
                )}
              </div>
            </div>
            {thread.lastPost && (
              <div className={styles.lastPostIndicator}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <polyline points="9 18 15 12 9 6" />
                </svg>
              </div>
            )}
          </Link>
        ))}
      </div>
    </div>
  );

  const renderStyle5a = () => (
    <div className={styles.style5a}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.forumHeader}>
        <h1 className={styles.forumTitle}>Gotham City</h1>
      </div>
      <div className={styles.threadsList}>
        {mockThreads.map((thread) => (
          <Link 
            key={thread.threadId} 
            to={`/threads/${thread.threadSlug}`}
            className={styles.threadItem}
          >
            <div className={styles.threadDot}></div>
            <div className={styles.threadContent}>
              <h3 className={styles.threadTitle}>{thread.title}</h3>
              <div className={styles.threadInfo}>
                <span>{thread.author}</span>
                <span className={styles.separator}>•</span>
                <span>{thread.createdAt}</span>
                <span className={styles.separator}>•</span>
                <span>{thread.replies} réponses</span>
                {thread.lastPost && (
                  <>
                    <span className={styles.separator}>•</span>
                    <span>Dernier: {thread.lastPost.author}</span>
                  </>
                )}
              </div>
            </div>
            {thread.lastPost && (
              <div className={styles.lastPostIndicator}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <polyline points="9 18 15 12 9 6" />
                </svg>
              </div>
            )}
          </Link>
        ))}
      </div>
    </div>
  );

  const renderStyle5b = () => (
    <div className={styles.style5b}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.forumHeader}>
        <h1 className={styles.forumTitle}>Gotham City</h1>
      </div>
      <div className={styles.threadsList}>
        {mockThreads.map((thread) => (
          <Link 
            key={thread.threadId} 
            to={`/threads/${thread.threadSlug}`}
            className={styles.threadItem}
          >
            <div className={styles.threadContent}>
              <h3 className={styles.threadTitle}>{thread.title}</h3>
              <div className={styles.threadInfo}>
                <span>{thread.author}</span>
                <span className={styles.separator}>•</span>
                <span>{thread.createdAt}</span>
                <span className={styles.separator}>•</span>
                <span>{thread.replies} réponses</span>
                {thread.lastPost && (
                  <>
                    <span className={styles.separator}>•</span>
                    <span>Dernier: {thread.lastPost.author}</span>
                  </>
                )}
              </div>
            </div>
            {thread.lastPost && (
              <div className={styles.lastPostIndicator}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <polyline points="9 18 15 12 9 6" />
                </svg>
              </div>
            )}
          </Link>
        ))}
      </div>
    </div>
  );

  const renderStyle5c = () => (
    <div className={styles.style5c}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.forumHeader}>
        <h1 className={styles.forumTitle}>Gotham City</h1>
      </div>
      <div className={styles.threadsList}>
        {mockThreads.map((thread) => (
          <Link 
            key={thread.threadId} 
            to={`/threads/${thread.threadSlug}`}
            className={styles.threadItem}
          >
            <div className={styles.threadContent}>
              <h3 className={styles.threadTitle}>{thread.title}</h3>
              <div className={styles.threadInfo}>
                <span>{thread.author}</span>
                <span className={styles.separator}>•</span>
                <span>{thread.createdAt}</span>
                <span className={styles.separator}>•</span>
                <span>{thread.replies} réponses</span>
                {thread.lastPost && (
                  <>
                    <span className={styles.separator}>•</span>
                    <span>Dernier: {thread.lastPost.author}</span>
                  </>
                )}
              </div>
            </div>
            {thread.lastPost && (
              <div className={styles.lastPostIndicator}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <polyline points="9 18 15 12 9 6" />
                </svg>
              </div>
            )}
          </Link>
        ))}
      </div>
    </div>
  );

  const renderStyle5d = () => (
    <div className={styles.style5d}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.forumHeader}>
        <h1 className={styles.forumTitle}>Gotham City</h1>
      </div>
      <div className={styles.threadsList}>
        {mockThreads.map((thread) => (
          <Link 
            key={thread.threadId} 
            to={`/threads/${thread.threadSlug}`}
            className={styles.threadItem}
          >
            {thread.authorAvatar && (
              <img src={thread.authorAvatar} alt={thread.author} className={styles.threadAvatar} />
            )}
            <div className={styles.threadContent}>
              <h3 className={styles.threadTitle}>{thread.title}</h3>
              <div className={styles.threadInfo}>
                <span>{thread.author}</span>
                <span className={styles.separator}>•</span>
                <span>{thread.createdAt}</span>
                <span className={styles.separator}>•</span>
                <span>{thread.replies} réponses</span>
                {thread.lastPost && (
                  <>
                    <span className={styles.separator}>•</span>
                    <span>Dernier: {thread.lastPost.author}</span>
                  </>
                )}
              </div>
            </div>
            {thread.lastPost && (
              <div className={styles.lastPostIndicator}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <polyline points="9 18 15 12 9 6" />
                </svg>
              </div>
            )}
          </Link>
        ))}
      </div>
    </div>
  );

  const renderStyle5e = () => (
    <div className={styles.style5e}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.forumHeader}>
        <h1 className={styles.forumTitle}>Gotham City</h1>
      </div>
      <div className={styles.threadsList}>
        {mockThreads.map((thread) => (
          <Link 
            key={thread.threadId} 
            to={`/threads/${thread.threadSlug}`}
            className={styles.threadItem}
          >
            <div className={styles.threadContent}>
              <div className={styles.threadHeader}>
                <h3 className={styles.threadTitle}>{thread.title}</h3>
                <div className={styles.threadBadges}>
                  {thread.pinned && (
                    <span className={styles.badgePinned}>
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                      </svg>
                    </span>
                  )}
                  {thread.locked && (
                    <span className={styles.badgeLocked}>
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                      </svg>
                    </span>
                  )}
                </div>
              </div>
              <div className={styles.threadInfo}>
                <span>{thread.author}</span>
                <span className={styles.separator}>•</span>
                <span>{thread.createdAt}</span>
                <span className={styles.separator}>•</span>
                <span>{thread.replies} réponses</span>
                {thread.lastPost && (
                  <>
                    <span className={styles.separator}>•</span>
                    <span>Dernier: {thread.lastPost.author}</span>
                  </>
                )}
              </div>
            </div>
            {thread.lastPost && (
              <div className={styles.lastPostIndicator}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <polyline points="9 18 15 12 9 6" />
                </svg>
              </div>
            )}
          </Link>
        ))}
      </div>
    </div>
  );

  const renderStyle5f = () => (
    <div className={styles.style5f}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.forumHeader}>
        <h1 className={styles.forumTitle}>Gotham City</h1>
      </div>
      <div className={styles.threadsList}>
        {mockThreads.map((thread) => (
          <Link 
            key={thread.threadId} 
            to={`/threads/${thread.threadSlug}`}
            className={styles.threadItem}
          >
            {thread.authorAvatar && (
              <img src={thread.authorAvatar} alt={thread.author} className={styles.threadAvatar} />
            )}
            <div className={styles.threadContent}>
              <div className={styles.threadHeader}>
                <h3 className={styles.threadTitle}>{thread.title}</h3>
                <div className={styles.threadBadges}>
                  {thread.pinned && (
                    <span className={styles.badgePinned}>
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                      </svg>
                    </span>
                  )}
                  {thread.locked && (
                    <span className={styles.badgeLocked}>
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                      </svg>
                    </span>
                  )}
                </div>
              </div>
              <div className={styles.threadInfo}>
                <span>{thread.author}</span>
                <span className={styles.separator}>•</span>
                <span>{thread.createdAt}</span>
                <span className={styles.separator}>•</span>
                <span>{thread.replies} réponses</span>
                {thread.lastPost && (
                  <>
                    <span className={styles.separator}>•</span>
                    <span>Dernier: {thread.lastPost.author}</span>
                  </>
                )}
              </div>
            </div>
            {thread.lastPost && (
              <div className={styles.lastPostIndicator}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <polyline points="9 18 15 12 9 6" />
                </svg>
              </div>
            )}
          </Link>
        ))}
      </div>
    </div>
  );

  const renderCurrentStyle = () => {
    switch (selectedStyle) {
      case 'style5':
        return renderStyle5();
      case 'style5a':
        return renderStyle5a();
      case 'style5b':
        return renderStyle5b();
      case 'style5c':
        return renderStyle5c();
      case 'style5d':
        return renderStyle5d();
      case 'style5e':
        return renderStyle5e();
      case 'style5f':
        return renderStyle5f();
      default:
        return renderStyle5();
    }
  };

  return (
    <Layout>
      <div className={styles.container}>
        <div className={styles.controls}>
          <h1 className={styles.pageTitle}>Styles de Listes de Threads</h1>
          <div className={styles.styleSelector}>
            <div className={styles.buttons}>
              {stylesList.map((style) => (
                <button
                  key={style.id}
                  className={`${styles.styleBtn} ${selectedStyle === style.id ? styles.active : ''}`}
                  onClick={() => setSelectedStyle(style.id)}
                >
                  {style.name}
                </button>
              ))}
            </div>
          </div>
        </div>

        <div className={styles.preview}>
          <h2 className={styles.previewTitle}>Aperçu</h2>
          {renderCurrentStyle()}
        </div>
      </div>
    </Layout>
  );
};

export default ThreadList;

