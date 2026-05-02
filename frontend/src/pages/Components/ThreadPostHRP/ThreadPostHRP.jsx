import React, { useState } from 'react';
import Layout from '../../../components/Layout/Layout';
import Breadcrumb from '../../../components/Breadcrumb/Breadcrumb';
import styles from './ThreadPostHRP.module.css';

const ThreadPostHRP = () => {
  const [selectedStyle, setSelectedStyle] = useState('style1');

  // Données de test pour des posts HRP
  const mockPosts = [
    {
      postId: 1,
      author: 'BruceW',
      authorId: 1,
      avatar: 'https://via.placeholder.com/64',
      date: 'Il y a 2 heures',
      content: 'Je pense que nous devrions examiner cette affaire plus en détail. Les indices sont nombreux mais dispersés.',
      isAdmin: false,
    },
    {
      postId: 2,
      author: 'Admin',
      authorId: 2,
      avatar: 'https://via.placeholder.com/64',
      date: 'Il y a 1 heure',
      content: '⚠️ **Message d\'administration** : Ce thread a été déplacé dans la section appropriée. Merci de respecter les règles du forum.',
      isAdmin: true,
    },
    {
      postId: 3,
      author: 'SelinaK',
      authorId: 3,
      avatar: 'https://via.placeholder.com/64',
      date: 'Il y a 30 minutes',
      content: 'D\'accord avec toi Bruce. On devrait peut-être créer un thread dédié pour cette enquête ?',
      isAdmin: false,
    },
    {
      postId: 4,
      author: 'Moderator',
      authorId: 4,
      avatar: 'https://via.placeholder.com/64',
      date: 'Il y a 15 minutes',
      content: '🔒 **Thread verrouillé temporairement** pour modération. Réouverture prévue sous peu.',
      isAdmin: true,
    },
  ];

  const stylesList = [
    { id: 'style1', name: 'Style 1 - Classique' },
    { id: 'style2', name: 'Style 2 - Compact' },
    { id: 'style3', name: 'Style 3 - Moderne avec sidebar' },
    { id: 'style4', name: 'Style 4 - Card avec ombre' },
    { id: 'style5', name: 'Style 5 - Minimaliste' },
    { id: 'style6', name: 'Style 6 - Avec badges' },
  ];

  const renderPost = (post, styleId) => {
    switch (styleId) {
      case 'style1':
        return (
          <div key={post.postId} className={styles.style1Post}>
            <div className={styles.postHeader}>
              <div className={styles.authorSection}>
                <img src={post.avatar} alt={post.author} className={styles.avatar} />
                <div className={styles.authorInfo}>
                  <span className={styles.authorName}>{post.author}</span>
                  <span className={styles.postDate}>{post.date}</span>
                </div>
              </div>
              <div className={styles.postActions}>
                <button className={styles.actionBtn}>Citer</button>
                <button className={styles.actionBtn}>Éditer</button>
              </div>
            </div>
            <div className={styles.postContent}>
              {post.content}
            </div>
          </div>
        );

      case 'style2':
        return (
          <div key={post.postId} className={styles.style2Post}>
            <div className={styles.postCompact}>
              <img src={post.avatar} alt={post.author} className={styles.avatarSmall} />
              <div className={styles.postBody}>
                <div className={styles.postMeta}>
                  <span className={styles.authorName}>{post.author}</span>
                  <span className={styles.postDate}>{post.date}</span>
                </div>
                <div className={styles.postContent}>{post.content}</div>
              </div>
            </div>
          </div>
        );

      case 'style3':
        return (
          <div key={post.postId} className={styles.style3Post}>
            <div className={styles.postSidebar}>
              <img src={post.avatar} alt={post.author} className={styles.avatarLarge} />
              <div className={styles.authorMeta}>
                <div className={styles.authorName}>{post.author}</div>
                <div className={styles.postDate}>{post.date}</div>
              </div>
            </div>
            <div className={styles.postMain}>
              <div className={styles.postContent}>{post.content}</div>
              <div className={styles.postActions}>
                <button className={styles.actionBtn}>Citer</button>
                <button className={styles.actionBtn}>Éditer</button>
              </div>
            </div>
          </div>
        );

      case 'style4':
        return (
          <div key={post.postId} className={styles.style4Post}>
            <div className={styles.postHeader}>
              <div className={styles.authorSection}>
                <img src={post.avatar} alt={post.author} className={styles.avatar} />
                <div>
                  <span className={styles.authorName}>{post.author}</span>
                  <span className={styles.postDate}>{post.date}</span>
                </div>
              </div>
            </div>
            <div className={styles.postContent}>{post.content}</div>
          </div>
        );

      case 'style5':
        return (
          <div key={post.postId} className={styles.style5Post}>
            <div className={styles.postMinimal}>
              <span className={styles.authorName}>{post.author}</span>
              <span className={styles.postDate}>{post.date}</span>
              <div className={styles.postContent}>{post.content}</div>
            </div>
          </div>
        );

      case 'style6':
        return (
          <div key={post.postId} className={styles.style6Post}>
            <div className={styles.postHeader}>
              <div className={styles.authorSection}>
                <img src={post.avatar} alt={post.author} className={styles.avatar} />
                <div className={styles.authorInfo}>
                  <div className={styles.authorRow}>
                    <span className={styles.authorName}>{post.author}</span>
                    {post.isAdmin && <span className={styles.badgeAdmin}>Admin</span>}
                  </div>
                  <span className={styles.postDate}>{post.date}</span>
                </div>
              </div>
              <div className={styles.postActions}>
                <button className={styles.actionBtn}>Citer</button>
                <button className={styles.actionBtn}>Éditer</button>
              </div>
            </div>
            <div className={styles.postContent}>{post.content}</div>
          </div>
        );

      default:
        return null;
    }
  };

  const breadcrumbItems = [
    { name: 'Accueil', url: '/' },
    { name: 'Composants', url: '/components' },
    { name: 'Posts HRP', url: null }
  ];

  return (
    <Layout>
      <div className={styles.container}>
        <Breadcrumb items={breadcrumbItems} />
        
        <div className={styles.controls}>
          <h1 className={styles.pageTitle}>Styles de Posts HRP</h1>
          <div className={styles.styleSelector}>
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

        <div className={styles.preview}>
          <h2 className={styles.previewTitle}>Aperçu - {stylesList.find(s => s.id === selectedStyle)?.name}</h2>
          <div className={styles.postsContainer}>
            {mockPosts.map((post) => renderPost(post, selectedStyle))}
          </div>
        </div>
      </div>
    </Layout>
  );
};

export default ThreadPostHRP;


