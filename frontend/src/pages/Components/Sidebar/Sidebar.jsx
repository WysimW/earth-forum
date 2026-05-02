import React, { useState } from 'react';
import Layout from '../../../components/Layout/Layout';
import styles from './Sidebar.module.css';

const sidebarItems = [
  {
    key: 'reglement',
    label: 'Règlement',
    description: 'Règles globales du forum',
    icon: (
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M6 3h9l3 3v15H6z" />
        <path d="M14 3v4h4" />
        <path d="M9 11h6M9 15h6" />
      </svg>
    ),
  },
  {
    key: 'member_of_month',
    label: 'Membre du mois',
    description: 'Mise en avant par univers',
    icon: (
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 3l2.6 5.3 5.8.8-4.2 4.1 1 5.8-5.2-2.7L6.8 19l1-5.8-4.2-4.1 5.8-.8z" />
      </svg>
    ),
  },
  {
    key: 'character_of_month',
    label: 'Personnage du mois',
    description: 'Concept jouable en vedette',
    icon: (
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 3l2 4 4 .6-3 2.9.8 4.1L12 12.7 8.2 14.6l.8-4.1-3-2.9 4-.6z" />
        <path d="M5 21a7 7 0 0 1 14 0" />
      </svg>
    ),
  },
  {
    key: 'vote',
    label: 'Votez pour nous',
    description: 'Lien externe global',
    icon: (
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M5 11l4 4L19 5" />
        <path d="M4 14v5h16v-7" />
        <path d="M8 19v2h8v-2" />
      </svg>
    ),
  },
];

const designOptions = [
  {
    id: 'command',
    label: 'Command Center',
    description: 'Dense, sombre, très lisible pour navigation quotidienne.',
  },
  {
    id: 'glass',
    label: 'Glass Panel',
    description: 'Plus doux, aéré, avec cartes translucides.',
  },
  {
    id: 'comic',
    label: 'Comic Rail',
    description: 'Plus marqué, punchy, proche d’une interface comics.',
  },
  {
    id: 'minimal',
    label: 'Minimal Dock',
    description: 'Très épuré, rail simple, focus sur les icônes.',
  },
];

const sizeOptions = [
  { id: 'expanded', label: 'Version étendue' },
  { id: 'collapsed', label: 'Version réduite' },
];

const SidebarPreview = ({ collapsed, design }) => (
  <aside
    className={`${styles.sidebarPreview} ${styles[`variant_${design}`]} ${
      collapsed ? styles.sidebarCollapsed : ''
    }`}
  >
    <div className={styles.sidebarHeader}>
      <div className={styles.brand}>
        <span className={styles.brandIcon}>EF</span>
        {!collapsed && (
          <div>
            <span className={styles.kicker}>Univers</span>
            <h3 className={styles.sidebarTitle}>Important</h3>
          </div>
        )}
      </div>
      <button type="button" className={styles.collapseButton} aria-label="Exemple de bouton collapse">
        <svg viewBox="0 0 24 24" aria-hidden="true" className={collapsed ? styles.chevronOpen : ''}>
          <path d="M15 6l-6 6 6 6" />
        </svg>
      </button>
    </div>

    <div className={styles.links}>
      {sidebarItems.map((item, index) => (
        <a
          key={item.key}
          href="#sidebar-preview"
          className={`${styles.link} ${index === 1 ? styles.linkActive : ''}`}
          title={item.label}
        >
          <span className={styles.icon}>{item.icon}</span>
          {!collapsed && (
            <span className={styles.linkText}>
              <span className={styles.label}>{item.label}</span>
              <span className={styles.description}>{item.description}</span>
            </span>
          )}
        </a>
      ))}
    </div>
  </aside>
);

const Sidebar = () => {
  const [selectedDesign, setSelectedDesign] = useState('command');
  const [selectedSize, setSelectedSize] = useState('expanded');

  const collapsed = selectedSize === 'collapsed';
  const selectedDesignMeta = designOptions.find((option) => option.id === selectedDesign);

  return (
    <Layout>
      <div className={styles.container}>
        <header className={styles.header}>
          <h1 className={styles.title}>Sidebar univers</h1>
          <p className={styles.subtitle}>
            Prévisualisation du rail latéral Important. Choisis une direction visuelle, puis teste l'état étendu
            ou compact.
          </p>
        </header>

        <div className={styles.controlsStack}>
          <div className={styles.controlGroup}>
            <span className={styles.controlLabel}>Design</span>
            <div className={styles.designGrid}>
              {designOptions.map((option) => (
                <button
                  key={option.id}
                  type="button"
                  className={`${styles.designButton} ${selectedDesign === option.id ? styles.active : ''}`}
                  onClick={() => setSelectedDesign(option.id)}
                >
                  <span>{option.label}</span>
                  <small>{option.description}</small>
                </button>
              ))}
            </div>
          </div>

          <div className={styles.controlGroup}>
            <span className={styles.controlLabel}>État</span>
            <div className={styles.controls}>
              {sizeOptions.map((option) => (
                <button
                  key={option.id}
                  type="button"
                  className={`${styles.styleButton} ${selectedSize === option.id ? styles.active : ''}`}
                  onClick={() => setSelectedSize(option.id)}
                >
                  {option.label}
                </button>
              ))}
            </div>
          </div>
        </div>

        <section className={styles.preview} id="sidebar-preview">
          <div className={styles.stage}>
            <SidebarPreview collapsed={collapsed} design={selectedDesign} />
            <div className={styles.fakeContent}>
              <span className={styles.fakeEyebrow}>Aperçu page</span>
              <h2>{selectedDesignMeta?.label}</h2>
              <p>
                {selectedDesignMeta?.description} La sidebar reste collée à gauche sous le header dans l'application,
                tandis que le contenu se décale selon l'état étendu ou réduit.
              </p>
              <div className={styles.fakeGrid}>
                <div />
                <div />
                <div />
              </div>
            </div>
          </div>
        </section>
      </div>
    </Layout>
  );
};

export default Sidebar;

