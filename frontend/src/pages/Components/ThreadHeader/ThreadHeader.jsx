import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import Layout from '../../../components/Layout/Layout';
import Breadcrumb from '../../../components/Breadcrumb/Breadcrumb';
import styles from './ThreadHeader.module.css';

const ThreadHeader = () => {
  const [selectedStyle, setSelectedStyle] = useState('style1');

  // Données de test pour un thread
  const mockThread = {
    threadId: 1,
    slug: 'test-thread',
    title: 'Discussion sur les événements récents à Gotham',
    author: 'BruceW',
    authorId: 53,
    authorAvatar: 'https://via.placeholder.com/40',
    createdAt: '2024-01-15 14:30:00',
    type: 'discussion',
    status: 'published',
    forum: {
      id: 1,
      name: 'Gotham City',
      slug: 'gotham-city'
    },
    posts: []
  };

  // Données de test pour une fiche de personnage
  const mockCharacter = {
    id: 1,
    name: 'Batman',
    firstName: 'Bruce',
    lastName: 'Wayne',
    alias: 'Le Chevalier Noir',
    avatar: 'https://lignecreator.com/cdn/shop/files/eeca0f0b709320b72ad0148ba58361cb_3be2729c-7a5d-4af3-9ec9-eb5a0f8e34e5.png?v=1727606525&width=1445',
    universe: {
      id: 1,
      name: 'DC Comics',
      slug: 'dc-comics'
    },
    elseworld: null,
    moralAffiliation: 'Héros',
    status: 'validated',
    statusMessage: null
  };

  const breadcrumbItems = [
    { name: 'Accueil', url: '/' },
    { name: 'Forums', url: '/forums' },
    { name: mockThread.forum.name, url: null },
    { name: mockThread.title, url: null }
  ];

  const stylesList = [
    { id: 'style1', name: 'Style 1 - Minimaliste' },
    { id: 'style2', name: 'Style 2 - Avec avatar' },
    { id: 'style3', name: 'Style 3 - Card moderne' },
    { id: 'style4', name: 'Style 4 - Compact' },
    { id: 'style5', name: 'Style 5 - Élégant' },
  ];

  const renderStyle1 = () => (
    <div className={styles.style1}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.header}>
        <h1 className={styles.title}>{mockThread.title}</h1>
        <div className={styles.meta}>
          <span className={styles.author}>Par {mockThread.author}</span>
          <span className={styles.separator}>•</span>
          <span className={styles.date}>
            {new Date(mockThread.createdAt).toLocaleDateString('fr-FR', { 
              day: 'numeric', 
              month: 'long', 
              year: 'numeric'
            })}
          </span>
        </div>
      </div>
    </div>
  );

  const renderStyle2 = () => (
    <div className={styles.style2}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.header}>
        <div className={styles.headerContent}>
          <div className={styles.headerMain}>
            <h1 className={styles.title}>{mockThread.title}</h1>
            <div className={styles.meta}>
              <div className={styles.authorSection}>
                <img src={mockThread.authorAvatar} alt={mockThread.author} className={styles.authorAvatar} />
                <div>
                  <span className={styles.authorLabel}>Créé par</span>
                  <span className={styles.authorName}>{mockThread.author}</span>
                </div>
              </div>
              <div className={styles.dateSection}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <circle cx="12" cy="12" r="10" />
                  <polyline points="12 6 12 12 16 14" />
                </svg>
                <span>{new Date(mockThread.createdAt).toLocaleDateString('fr-FR', { 
                  day: 'numeric', 
                  month: 'long', 
                  year: 'numeric',
                  hour: '2-digit',
                  minute: '2-digit'
                })}</span>
              </div>
            </div>
          </div>
          <div className={styles.forumBadge}>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
            </svg>
            <span>{mockThread.forum.name}</span>
          </div>
        </div>
      </div>
    </div>
  );

  const renderStyle3 = () => (
    <div className={styles.style3}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.header}>
        <div className={styles.headerTop}>
          <div className={styles.headerContent}>
            <div className={styles.headerMain}>
              <h1 className={styles.title}>{mockThread.title}</h1>
              <div className={styles.meta}>
                <div className={styles.authorInfo}>
                  <img src={mockThread.authorAvatar} alt={mockThread.author} className={styles.authorAvatar} />
                  <div>
                    <span className={styles.authorLabel}>Par</span>
                    <span className={styles.authorName}>{mockThread.author}</span>
                  </div>
                </div>
                <div className={styles.dateInfo}>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <circle cx="12" cy="12" r="10" />
                    <polyline points="12 6 12 12 16 14" />
                  </svg>
                  <span>{new Date(mockThread.createdAt).toLocaleDateString('fr-FR', { 
                    day: 'numeric', 
                    month: 'long', 
                    year: 'numeric'
                  })}</span>
                </div>
              </div>
            </div>
            <div className={styles.actions}>
              <button className={styles.actionBtn} title="Modifier">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
              </button>
            </div>
          </div>
          <div className={styles.forumLink}>
            <Link to="/forums">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
              </svg>
              <span>{mockThread.forum.name}</span>
            </Link>
          </div>
        </div>
      </div>
    </div>
  );

  const renderStyle4 = () => (
    <div className={styles.style4}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.header}>
        <div className={styles.headerRow}>
          <h1 className={styles.title}>{mockThread.title}</h1>
          <div className={styles.headerActions}>
            <div className={styles.forumTag}>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
              </svg>
              {mockThread.forum.name}
            </div>
            <button className={styles.actionBtn} title="Modifier">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
              </svg>
            </button>
          </div>
          <div className={styles.metaRow}>
            <div className={styles.authorCompact}>
              <img src={mockThread.authorAvatar} alt={mockThread.author} className={styles.authorAvatarSmall} />
              <span>{mockThread.author}</span>
            </div>
            <span className={styles.dateCompact}>
              {new Date(mockThread.createdAt).toLocaleDateString('fr-FR', { 
                day: 'numeric', 
                month: 'short', 
                year: 'numeric'
              })}
            </span>
          </div>
        </div>
      </div>
    </div>
  );

  const renderStyle5 = () => (
    <div className={styles.style5}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.header}>
        <div className={styles.headerElegant}>
          <div className={styles.titleSection}>
            <div className={styles.titleWrapper}>
              <h1 className={styles.title}>{mockThread.title}</h1>
              <div className={styles.titleUnderline}></div>
            </div>
            <div className={styles.forumElegant}>
              <Link to="/forums" className={styles.forumLinkElegant}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                </svg>
                <span>{mockThread.forum.name}</span>
              </Link>
            </div>
          </div>
          <div className={styles.metaElegant}>
            <div className={styles.authorElegant}>
              <div className={styles.authorAvatarWrapper}>
                <img src={mockThread.authorAvatar} alt={mockThread.author} className={styles.authorAvatarElegant} />
              </div>
              <div className={styles.authorDetails}>
                <span className={styles.authorLabelElegant}>Auteur</span>
                <span className={styles.authorNameElegant}>{mockThread.author}</span>
              </div>
            </div>
            <div className={styles.dateElegant}>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
              </svg>
              <div>
                <span className={styles.dateLabelElegant}>Créé le</span>
                <span className={styles.dateValueElegant}>
                  {new Date(mockThread.createdAt).toLocaleDateString('fr-FR', { 
                    day: 'numeric', 
                    month: 'long', 
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                  })}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );

  const renderCharacterStyle1 = () => (
    <div className={styles.characterStyle1}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.header}>
        <div className={styles.characterHeader}>
          <div className={styles.characterTitleSection}>
            <h1 className={styles.characterTitle}>{mockCharacter.name}</h1>
            {(mockCharacter.firstName || mockCharacter.lastName) && (
              <p className={styles.characterSubtitle}>
                {[mockCharacter.firstName, mockCharacter.lastName].filter(Boolean).join(' ')}
              </p>
            )}
          </div>
          <div className={styles.characterMeta}>
            {mockCharacter.universe && (
              <div className={styles.metaItem}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <circle cx="12" cy="12" r="10" />
                  <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                </svg>
                <span>{mockCharacter.universe.name}</span>
              </div>
            )}
            {mockCharacter.moralAffiliation && (
              <div className={styles.metaItem}>
                <span className={styles.metaLabel}>Affiliation:</span>
                <span>{mockCharacter.moralAffiliation}</span>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );

  const renderCharacterStyle2 = () => (
    <div className={styles.characterStyle2}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.header}>
        <div className={styles.characterHeaderCard}>
          <div className={styles.characterHeaderContent}>
            <div className={styles.characterTitleWrapper}>
              <h1 className={styles.characterTitle}>{mockCharacter.name}</h1>
              {(mockCharacter.firstName || mockCharacter.lastName) && (
                <p className={styles.characterSubtitle}>
                  {[mockCharacter.firstName, mockCharacter.lastName].filter(Boolean).join(' ')}
                </p>
              )}
              {mockCharacter.alias && (
                <div className={styles.characterAlias}>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                  </svg>
                  <span>{mockCharacter.alias}</span>
                </div>
              )}
            </div>
            <div className={styles.characterMetaGrid}>
              {mockCharacter.universe && (
                <div className={styles.metaCard}>
                  <span className={styles.metaCardLabel}>Univers</span>
                  <span className={styles.metaCardValue}>{mockCharacter.universe.name}</span>
                </div>
              )}
              {mockCharacter.moralAffiliation && (
                <div className={styles.metaCard}>
                  <span className={styles.metaCardLabel}>Affiliation</span>
                  <span className={styles.metaCardValue}>{mockCharacter.moralAffiliation}</span>
                </div>
              )}
            </div>
          </div>
          <div className={styles.characterActions}>
            <button className={styles.actionBtn} title="Modifier">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  );

  const renderCurrentStyle = () => {
    switch (selectedStyle) {
      case 'style1':
        return renderStyle1();
      case 'style2':
        return renderStyle2();
      case 'style3':
        return renderStyle3();
      case 'style4':
        return renderStyle4();
      case 'style5':
        return renderStyle5();
      case 'character1':
        return renderCharacterStyle1();
      case 'character2':
        return renderCharacterStyle2();
      default:
        return renderStyle1();
    }
  };

  return (
    <Layout>
      <div className={styles.container}>
        <div className={styles.controls}>
          <h1 className={styles.pageTitle}>Styles de Headers de Thread</h1>
          <div className={styles.styleSelector}>
            <h2 className={styles.sectionTitle}>Threads normaux</h2>
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
            <h2 className={styles.sectionTitle}>Fiches de personnage</h2>
            <div className={styles.buttons}>
              <button
                className={`${styles.styleBtn} ${selectedStyle === 'character1' ? styles.active : ''}`}
                onClick={() => setSelectedStyle('character1')}
              >
                Style 1 - Simple
              </button>
              <button
                className={`${styles.styleBtn} ${selectedStyle === 'character2' ? styles.active : ''}`}
                onClick={() => setSelectedStyle('character2')}
              >
                Style 2 - Card moderne
              </button>
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

export default ThreadHeader;



