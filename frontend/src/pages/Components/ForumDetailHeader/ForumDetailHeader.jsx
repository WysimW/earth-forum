import React, { useState } from 'react';
import Layout from '../../../components/Layout/Layout';
import Breadcrumb from '../../../components/Breadcrumb/Breadcrumb';
import styles from './ForumDetailHeader.module.css';

const ForumDetailHeader = () => {
  const [selectedStyle, setSelectedStyle] = useState('style1');

  const mockForum = {
    name: 'Gotham City',
    description: 'La ville sombre de Batman, rongée par le crime',
    banner: 'https://cdn.midjourney.com/1685b7a5-56a3-4b2e-b79a-821b8bc548bc/0_2.png',
    type: 'roleplay',
    stats: {
      totalThreads: 45,
      totalPosts: 234,
      subforumCount: 4
    }
  };

  const breadcrumbItems = [
    { name: 'Accueil', url: '/' },
    { name: 'Forums', url: '/forums' },
    { name: 'Gotham City', url: null }
  ];

  const stylesList = [
    { id: 'style1', name: 'Simple avec bannière' },
    { id: 'style2', name: 'Bannière hero full' },
    { id: 'style3', name: 'Minimaliste centré' },
    { id: 'style4', name: 'Avec statistiques' },
    { id: 'style5', name: 'Overlay sombre' },
    { id: 'style6', name: 'Gradient moderne' },
  ];

  const renderStyle1 = () => (
    <div className={styles.style1}>
      <Breadcrumb items={breadcrumbItems} />
      {mockForum.banner && (
        <div className={styles.banner}>
          <img src={mockForum.banner} alt={mockForum.name} />
        </div>
      )}
      <div className={styles.headerContent}>
        <h1 className={styles.title}>{mockForum.name}</h1>
        <p className={styles.description}>{mockForum.description}</p>
      </div>
    </div>
  );

  const renderStyle2 = () => (
    <div className={styles.style2}>
      <Breadcrumb items={breadcrumbItems} />
      <div 
        className={styles.heroBanner}
        style={{ backgroundImage: `url("${mockForum.banner}")` }}
      >
        <div className={styles.heroOverlay} />
        <div className={styles.headerContent}>
          <h1 className={styles.title}>{mockForum.name}</h1>
          <p className={styles.description}>{mockForum.description}</p>
        </div>
      </div>
    </div>
  );

  const renderStyle3 = () => (
    <div className={styles.style3}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.headerContent}>
        <div className={styles.titleWrapper}>
          <h1 className={styles.title}>{mockForum.name}</h1>
          <div className={styles.titleUnderline} />
        </div>
        <p className={styles.description}>{mockForum.description}</p>
      </div>
    </div>
  );

  const renderStyle4 = () => (
    <div className={styles.style4}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.headerContent}>
        <div className={styles.headerTop}>
          <div className={styles.titleSection}>
            <h1 className={styles.title}>{mockForum.name}</h1>
            <p className={styles.description}>{mockForum.description}</p>
          </div>
          <div className={styles.statsSection}>
            <div className={styles.statItem}>
              <div className={styles.statValue}>{mockForum.stats.totalThreads}</div>
              <div className={styles.statLabel}>Discussions</div>
            </div>
            <div className={styles.statItem}>
              <div className={styles.statValue}>{mockForum.stats.totalPosts}</div>
              <div className={styles.statLabel}>Messages</div>
            </div>
            <div className={styles.statItem}>
              <div className={styles.statValue}>{mockForum.stats.subforumCount}</div>
              <div className={styles.statLabel}>Sous-forums</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );

  const renderStyle5 = () => (
    <div className={styles.style5}>
      <Breadcrumb items={breadcrumbItems} />
      <div 
        className={styles.bannerContainer}
        style={{ backgroundImage: `url("${mockForum.banner}")` }}
      >
        <div className={styles.darkOverlay} />
        <div className={styles.headerContent}>
          <h1 className={styles.title}>{mockForum.name}</h1>
          <p className={styles.description}>{mockForum.description}</p>
        </div>
      </div>
    </div>
  );

  const renderStyle6 = () => (
    <div className={styles.style6}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.headerContent}>
        <div className={styles.gradientBackground} />
        <div className={styles.contentWrapper}>
          <h1 className={styles.title}>{mockForum.name}</h1>
          <p className={styles.description}>{mockForum.description}</p>
        </div>
      </div>
    </div>
  );

  const renderSelectedStyle = () => {
    switch (selectedStyle) {
      case 'style1': return renderStyle1();
      case 'style2': return renderStyle2();
      case 'style3': return renderStyle3();
      case 'style4': return renderStyle4();
      case 'style5': return renderStyle5();
      case 'style6': return renderStyle6();
      default: return renderStyle1();
    }
  };

  return (
    <Layout>
      <div className={styles.container}>
        <div className={styles.controls}>
          <h2 className={styles.controlsTitle}>Styles de header de forum</h2>
          <div className={styles.styleSelector}>
            {stylesList.map((style) => (
              <button
                key={style.id}
                className={`${styles.styleButton} ${selectedStyle === style.id ? styles.styleButtonActive : ''}`}
                onClick={() => setSelectedStyle(style.id)}
              >
                {style.name}
              </button>
            ))}
          </div>
        </div>
        {renderSelectedStyle()}
      </div>
    </Layout>
  );
};

export default ForumDetailHeader;



