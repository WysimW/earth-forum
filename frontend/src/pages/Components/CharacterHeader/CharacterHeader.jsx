import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import Layout from '../../../components/Layout/Layout';
import Breadcrumb from '../../../components/Breadcrumb/Breadcrumb';
import styles from './CharacterHeader.module.css';

const CharacterHeader = () => {
  const [selectedStyle, setSelectedStyle] = useState('style1');

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
    elseworld: {
      id: 1,
      name: 'Earth-2',
      slug: 'earth-2'
    },
    moralAffiliation: 'Héros',
    status: 'validated',
    statusMessage: null
  };

  const breadcrumbItems = [
    { name: 'Accueil', url: '/' },
    { name: 'Forums', url: '/forums' },
    { name: 'Fiches validées', url: null },
    { name: mockCharacter.name, url: null }
  ];

  const stylesList = [
    { id: 'style1', name: 'Style 1 - Card avec grille' },
    { id: 'style2', name: 'Style 2 - Avec avatar intégré' },
    { id: 'style3', name: 'Style 3 - Horizontal compact' },
    { id: 'style4', name: 'Style 4 - Élégant avec séparateurs' },
    { id: 'style5', name: 'Style 5 - Badges en ligne' },
    { id: 'style6', name: 'Style 6 - Minimaliste moderne' },
  ];

  const renderStyle1 = () => (
    <div className={styles.style1}>
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
              {mockCharacter.elseworld && (
                <div className={styles.metaCard}>
                  <span className={styles.metaCardLabel}>Elseworld</span>
                  <span className={styles.metaCardValue}>{mockCharacter.elseworld.name}</span>
                </div>
              )}
              {mockCharacter.status && (
                <div className={styles.metaCard}>
                  <span className={styles.metaCardLabel}>Statut</span>
                  <span className={`${styles.metaCardValue} ${styles.statusValue} ${styles[`status${mockCharacter.status.charAt(0).toUpperCase() + mockCharacter.status.slice(1)}`]}`}>
                    {mockCharacter.status === 'validated' ? 'Validée' : mockCharacter.status}
                  </span>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );

  const renderStyle2 = () => (
    <div className={styles.style2}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.header}>
        <div className={styles.characterHeaderCard}>
          <div className={styles.characterHeaderWithAvatar}>
            {mockCharacter.avatar && (
              <div className={styles.avatarSection}>
                <img src={mockCharacter.avatar} alt={mockCharacter.name} className={styles.headerAvatar} />
              </div>
            )}
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
              <div className={styles.characterMetaInline}>
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
                {mockCharacter.status && (
                  <div className={styles.metaItem}>
                    <span className={`${styles.statusBadge} ${styles[`status${mockCharacter.status.charAt(0).toUpperCase() + mockCharacter.status.slice(1)}`]}`}>
                      {mockCharacter.status === 'validated' ? 'Validée' : mockCharacter.status}
                    </span>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );

  const renderStyle3 = () => (
    <div className={styles.style3}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.header}>
        <div className={styles.characterHeaderCompact}>
          <div className={styles.characterHeaderRow}>
            <div className={styles.characterTitleSection}>
              <h1 className={styles.characterTitle}>{mockCharacter.name}</h1>
              {(mockCharacter.firstName || mockCharacter.lastName) && (
                <p className={styles.characterSubtitle}>
                  {[mockCharacter.firstName, mockCharacter.lastName].filter(Boolean).join(' ')}
                </p>
              )}
            </div>
            <div className={styles.characterMetaRow}>
              {mockCharacter.universe && (
                <div className={styles.metaTag}>
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <circle cx="12" cy="12" r="10" />
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                  </svg>
                  {mockCharacter.universe.name}
                </div>
              )}
              {mockCharacter.moralAffiliation && (
                <div className={styles.metaTag}>
                  {mockCharacter.moralAffiliation}
                </div>
              )}
              {mockCharacter.status && (
                <div className={`${styles.statusTag} ${styles[`status${mockCharacter.status.charAt(0).toUpperCase() + mockCharacter.status.slice(1)}`]}`}>
                  {mockCharacter.status === 'validated' ? 'Validée' : mockCharacter.status}
                </div>
              )}
            </div>
          </div>
          {mockCharacter.alias && (
            <div className={styles.aliasRow}>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
              </svg>
              <span>{mockCharacter.alias}</span>
            </div>
          )}
        </div>
      </div>
    </div>
  );

  const renderStyle4 = () => (
    <div className={styles.style4}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.header}>
        <div className={styles.characterHeaderElegant}>
          <div className={styles.characterTitleSection}>
            <div className={styles.titleWrapper}>
              <h1 className={styles.characterTitle}>{mockCharacter.name}</h1>
              {(mockCharacter.firstName || mockCharacter.lastName) && (
                <p className={styles.characterSubtitle}>
                  {[mockCharacter.firstName, mockCharacter.lastName].filter(Boolean).join(' ')}
                </p>
              )}
              {mockCharacter.alias && (
                <div className={styles.characterAlias}>
                  <span className={styles.aliasPrefix}>alias</span>
                  <span className={styles.aliasValue}>{mockCharacter.alias}</span>
                </div>
              )}
            </div>
          </div>
          <div className={styles.separator}></div>
          <div className={styles.characterMetaElegant}>
            {mockCharacter.universe && (
              <div className={styles.metaElegantItem}>
                <span className={styles.metaElegantLabel}>Univers</span>
                <span className={styles.metaElegantValue}>{mockCharacter.universe.name}</span>
              </div>
            )}
            {mockCharacter.moralAffiliation && (
              <div className={styles.metaElegantItem}>
                <span className={styles.metaElegantLabel}>Affiliation</span>
                <span className={styles.metaElegantValue}>{mockCharacter.moralAffiliation}</span>
              </div>
            )}
            {mockCharacter.status && (
              <div className={styles.metaElegantItem}>
                <span className={styles.metaElegantLabel}>Statut</span>
                <span className={`${styles.metaElegantValue} ${styles.statusElegant} ${styles[`status${mockCharacter.status.charAt(0).toUpperCase() + mockCharacter.status.slice(1)}`]}`}>
                  {mockCharacter.status === 'validated' ? 'Validée' : mockCharacter.status}
                </span>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );

  const renderStyle5 = () => (
    <div className={styles.style5}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.header}>
        <div className={styles.characterHeaderBadges}>
          <div className={styles.characterTitleSection}>
            <h1 className={styles.characterTitle}>{mockCharacter.name}</h1>
            {(mockCharacter.firstName || mockCharacter.lastName) && (
              <p className={styles.characterSubtitle}>
                {[mockCharacter.firstName, mockCharacter.lastName].filter(Boolean).join(' ')}
              </p>
            )}
          </div>
          <div className={styles.badgesRow}>
            {mockCharacter.universe && (
              <div className={styles.badge}>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <circle cx="12" cy="12" r="10" />
                  <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                </svg>
                <span>{mockCharacter.universe.name}</span>
              </div>
            )}
            {mockCharacter.moralAffiliation && (
              <div className={styles.badge}>
                {mockCharacter.moralAffiliation}
              </div>
            )}
            {mockCharacter.elseworld && (
              <div className={styles.badge}>
                {mockCharacter.elseworld.name}
              </div>
            )}
            {mockCharacter.status && (
              <div className={`${styles.badge} ${styles.statusBadge} ${styles[`status${mockCharacter.status.charAt(0).toUpperCase() + mockCharacter.status.slice(1)}`]}`}>
                {mockCharacter.status === 'validated' ? 'Validée' : mockCharacter.status}
              </div>
            )}
          </div>
          {mockCharacter.alias && (
            <div className={styles.aliasBadge}>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
              </svg>
              <span>{mockCharacter.alias}</span>
            </div>
          )}
        </div>
      </div>
    </div>
  );

  const renderStyle6 = () => (
    <div className={styles.style6}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.header}>
        <div className={styles.characterHeaderMinimal}>
          <h1 className={styles.characterTitle}>{mockCharacter.name}</h1>
          <div className={styles.characterInfoMinimal}>
            {(mockCharacter.firstName || mockCharacter.lastName) && (
              <span className={styles.infoItem}>
                {[mockCharacter.firstName, mockCharacter.lastName].filter(Boolean).join(' ')}
              </span>
            )}
            {mockCharacter.alias && (
              <>
                <span className={styles.separator}>•</span>
                <span className={styles.infoItem}>{mockCharacter.alias}</span>
              </>
            )}
            {mockCharacter.universe && (
              <>
                <span className={styles.separator}>•</span>
                <span className={styles.infoItem}>{mockCharacter.universe.name}</span>
              </>
            )}
            {mockCharacter.moralAffiliation && (
              <>
                <span className={styles.separator}>•</span>
                <span className={styles.infoItem}>{mockCharacter.moralAffiliation}</span>
              </>
            )}
            {mockCharacter.status && (
              <>
                <span className={styles.separator}>•</span>
                <span className={`${styles.infoItem} ${styles.statusMinimal} ${styles[`status${mockCharacter.status.charAt(0).toUpperCase() + mockCharacter.status.slice(1)}`]}`}>
                  {mockCharacter.status === 'validated' ? 'Validée' : mockCharacter.status}
                </span>
              </>
            )}
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
      case 'style6':
        return renderStyle6();
      default:
        return renderStyle1();
    }
  };

  return (
    <Layout>
      <div className={styles.container}>
        <div className={styles.controls}>
          <h1 className={styles.pageTitle}>Styles de Headers de Fiches de Personnage</h1>
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

export default CharacterHeader;



