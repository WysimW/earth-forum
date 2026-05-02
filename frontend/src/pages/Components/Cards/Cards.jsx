import React, { useState } from 'react';
import Layout from '../../../components/Layout/Layout';
import styles from './Cards.module.css';

const Cards = () => {
  const [selectedStyle, setSelectedStyle] = useState('style1');

  const batmanData = {
    id: 1,
    name: 'Batman',
    firstName: 'Bruce',
    lastName: 'Wayne',
    avatar: 'https://lignecreator.com/cdn/shop/files/eeca0f0b709320b72ad0148ba58361cb_3be2729c-7a5d-4af3-9ec9-eb5a0f8e34e5.png?v=1727606525&width=1445',
    status: 'validated',
    statusMessage: 'Validé',
    universe: {
      id: 1,
      name: 'DC Comics',
      slug: 'dc'
    },
    moralAffiliation: 'Super-héros',
    age: '35',
    occupation: 'Homme d\'affaires / Vengeur masqué'
  };

  const CardStyle1 = ({ character }) => (
    <div className={styles.cardStyle1}>
      <div className={styles.cardHeader}>
        {character.avatar && (
          <img src={character.avatar} alt={character.name} className={styles.avatar} />
        )}
        <div className={styles.cardHeaderContent}>
          <h3 className={styles.cardTitle}>{character.name}</h3>
          {(character.firstName || character.lastName) && (
            <p className={styles.cardSubtitle}>
              {[character.firstName, character.lastName].filter(Boolean).join(' ')}
            </p>
          )}
        </div>
      </div>
      <div className={styles.cardBody}>
        <div className={styles.cardInfo}>
          {character.universe && (
            <div className={styles.infoItem}>
              <span className={styles.infoLabel}>Univers:</span>
              <span className={styles.infoValue}>{character.universe.name}</span>
            </div>
          )}
          {character.moralAffiliation && (
            <div className={styles.infoItem}>
              <span className={styles.infoLabel}>Affiliation:</span>
              <span className={styles.infoValue}>{character.moralAffiliation}</span>
            </div>
          )}
          {character.age && (
            <div className={styles.infoItem}>
              <span className={styles.infoLabel}>Âge:</span>
              <span className={styles.infoValue}>{character.age}</span>
            </div>
          )}
          {character.occupation && (
            <div className={styles.infoItem}>
              <span className={styles.infoLabel}>Occupation:</span>
              <span className={styles.infoValue}>{character.occupation}</span>
            </div>
          )}
        </div>
        <div className={styles.cardStatus}>
          <span className={`${styles.statusBadge} ${styles[character.status]}`}>
            {character.statusMessage || character.status}
          </span>
        </div>
      </div>
      <div className={styles.cardActions}>
        <button className={styles.btnPrimary}>Modifier</button>
        <button className={styles.btnDanger}>Supprimer</button>
      </div>
    </div>
  );

  const CardStyle2 = ({ character }) => (
    <div className={styles.cardStyle2}>
      {character.avatar && (
        <div className={styles.cardImageWrapper}>
          <img src={character.avatar} alt={character.name} className={styles.cardImage} />
          <div className={styles.cardOverlay}>
            <span className={`${styles.statusBadge2} ${styles[character.status]}`}>
              {character.statusMessage || character.status}
            </span>
          </div>
        </div>
      )}
      <div className={styles.cardContent}>
        <h3 className={styles.cardTitle2}>{character.name}</h3>
        {(character.firstName || character.lastName) && (
          <p className={styles.cardSubtitle2}>
            {[character.firstName, character.lastName].filter(Boolean).join(' ')}
          </p>
        )}
        <div className={styles.cardMeta}>
          {character.universe && (
            <div className={styles.metaItem}>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="12" r="10" />
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
              </svg>
              <span>{character.universe.name}</span>
            </div>
          )}
          {character.moralAffiliation && (
            <div className={styles.metaItem}>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M12 2L2 7l10 5 10-5-10-5z" />
                <path d="M2 17l10 5 10-5" />
                <path d="M2 12l10 5 10-5" />
              </svg>
              <span>{character.moralAffiliation}</span>
            </div>
          )}
        </div>
        <div className={styles.cardActions2}>
          <button className={styles.btnSecondary2}>Modifier</button>
          <button className={styles.btnDanger2}>Supprimer</button>
        </div>
      </div>
    </div>
  );

  const CardStyle3 = ({ character }) => (
    <div className={styles.cardStyle3}>
      <div className={styles.cardTop}>
        {character.avatar && (
          <div className={styles.avatarWrapper}>
            <img src={character.avatar} alt={character.name} className={styles.avatar3} />
            <div className={styles.avatarBadge}>
              <span className={`${styles.statusDot} ${styles[character.status]}`}></span>
            </div>
          </div>
        )}
        <div className={styles.cardTopContent}>
          <h3 className={styles.cardTitle3}>{character.name}</h3>
          {(character.firstName || character.lastName) && (
            <p className={styles.cardSubtitle3}>
              {[character.firstName, character.lastName].filter(Boolean).join(' ')}
            </p>
          )}
        </div>
      </div>
      <div className={styles.cardDetails}>
        {character.universe && (
          <div className={styles.detailRow}>
            <span className={styles.detailIcon}>🌍</span>
            <span className={styles.detailText}>{character.universe.name}</span>
          </div>
        )}
        {character.moralAffiliation && (
          <div className={styles.detailRow}>
            <span className={styles.detailIcon}>⚖️</span>
            <span className={styles.detailText}>{character.moralAffiliation}</span>
          </div>
        )}
        {character.age && (
          <div className={styles.detailRow}>
            <span className={styles.detailIcon}>🎂</span>
            <span className={styles.detailText}>{character.age} ans</span>
          </div>
        )}
        {character.occupation && (
          <div className={styles.detailRow}>
            <span className={styles.detailIcon}>💼</span>
            <span className={styles.detailText}>{character.occupation}</span>
          </div>
        )}
      </div>
      <div className={styles.cardFooter}>
        <span className={`${styles.statusBadge3} ${styles[character.status]}`}>
          {character.statusMessage || character.status}
        </span>
        <div className={styles.cardActions3}>
          <button className={styles.btnIcon} title="Modifier">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
            </svg>
          </button>
          <button className={`${styles.btnIcon} ${styles.btnIconDanger}`} title="Supprimer">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <polyline points="3 6 5 6 21 6" />
              <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  );

  const CardStyle2Bis = ({ character }) => (
    <div className={styles.cardStyle2Bis}>
      {character.avatar && (
        <div className={styles.cardImageContainer2Bis}>
          <img src={character.avatar} alt={character.name} className={styles.cardImage2Bis} />
          <div className={styles.imageGradient2Bis}></div>
          <div className={styles.statusBadgeContainer2Bis}>
            <span className={`${styles.statusBadge2Bis} ${styles[character.status]}`}>
              {character.statusMessage || character.status}
            </span>
          </div>
        </div>
      )}
      <div className={styles.cardBody2Bis}>
        <div className={styles.cardHeader2Bis}>
          <h3 className={styles.cardTitle2Bis}>{character.name}</h3>
          {(character.firstName || character.lastName) && (
            <p className={styles.cardSubtitle2Bis}>
              {[character.firstName, character.lastName].filter(Boolean).join(' ')}
            </p>
          )}
        </div>
        
        <div className={styles.cardInfo2Bis}>
          {character.universe && (
            <div className={styles.infoItem2Bis}>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="12" r="10" />
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
              </svg>
              <span>{character.universe.name}</span>
            </div>
          )}
          {character.moralAffiliation && (
            <div className={styles.infoItem2Bis}>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M12 2L2 7l10 5 10-5-10-5z" />
                <path d="M2 17l10 5 10-5" />
                <path d="M2 12l10 5 10-5" />
              </svg>
              <span>{character.moralAffiliation}</span>
            </div>
          )}
        </div>

        <div className={styles.cardActions2Bis}>
          <button className={styles.btnAction2Bis}>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
            </svg>
            Modifier
          </button>
          <button className={`${styles.btnAction2Bis} ${styles.btnDanger2Bis}`}>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <polyline points="3 6 5 6 21 6" />
              <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
            </svg>
            Supprimer
          </button>
        </div>
      </div>
    </div>
  );

  const CardStyle4 = ({ character }) => (
    <div className={styles.cardStyle4}>
      {character.avatar && (
        <div className={styles.cardImageContainer}>
          <img src={character.avatar} alt={character.name} className={styles.cardImage4} />
        </div>
      )}
      <div className={styles.cardBody4}>
        <div className={styles.cardHeader4}>
          <div>
            <h3 className={styles.cardTitle4}>{character.name}</h3>
            {(character.firstName || character.lastName) && (
              <p className={styles.cardSubtitle4}>
                {[character.firstName, character.lastName].filter(Boolean).join(' ')}
              </p>
            )}
          </div>
          <span className={`${styles.statusBadge4} ${styles[character.status]}`}>
            {character.statusMessage || character.status}
          </span>
        </div>
        <div className={styles.cardInfo4}>
          {character.universe && (
            <div className={styles.infoRow}>
              <span className={styles.infoLabel4}>Univers</span>
              <span className={styles.infoValue4}>{character.universe.name}</span>
            </div>
          )}
          {character.moralAffiliation && (
            <div className={styles.infoRow}>
              <span className={styles.infoLabel4}>Affiliation</span>
              <span className={styles.infoValue4}>{character.moralAffiliation}</span>
            </div>
          )}
        </div>
        <div className={styles.cardActions4}>
          <button className={styles.btnOutline}>Modifier</button>
          <button className={styles.btnOutlineDanger}>Supprimer</button>
        </div>
      </div>
    </div>
  );

  const renderCard = () => {
    switch (selectedStyle) {
      case 'style1':
        return <CardStyle1 character={batmanData} />;
      case 'style2':
        return <CardStyle2 character={batmanData} />;
      case 'style2bis':
        return <CardStyle2Bis character={batmanData} />;
      case 'style3':
        return <CardStyle3 character={batmanData} />;
      case 'style4':
        return <CardStyle4 character={batmanData} />;
      default:
        return <CardStyle1 character={batmanData} />;
    }
  };

  return (
    <Layout>
      <div className={styles.container}>
        <header className={styles.header}>
          <h1 className={styles.title}>Styles de Cards de Personnages</h1>
          <p className={styles.subtitle}>Testez différents styles pour les cards de personnages</p>
        </header>

        <div className={styles.controls}>
          <button
            className={`${styles.styleButton} ${selectedStyle === 'style1' ? styles.active : ''}`}
            onClick={() => setSelectedStyle('style1')}
          >
            Style 1 - Classique
          </button>
          <button
            className={`${styles.styleButton} ${selectedStyle === 'style2' ? styles.active : ''}`}
            onClick={() => setSelectedStyle('style2')}
          >
            Style 2 - Image en avant
          </button>
          <button
            className={`${styles.styleButton} ${selectedStyle === 'style2bis' ? styles.active : ''}`}
            onClick={() => setSelectedStyle('style2bis')}
          >
            Style 2 Bis - Dark Mode
          </button>
          <button
            className={`${styles.styleButton} ${selectedStyle === 'style3' ? styles.active : ''}`}
            onClick={() => setSelectedStyle('style3')}
          >
            Style 3 - Minimaliste
          </button>
          <button
            className={`${styles.styleButton} ${selectedStyle === 'style4' ? styles.active : ''}`}
            onClick={() => setSelectedStyle('style4')}
          >
            Style 4 - Moderne
          </button>
        </div>

        <div className={styles.preview}>
          <h2 className={styles.previewTitle}>Aperçu - Batman / Bruce Wayne</h2>
          <div className={styles.cardWrapper}>
            {renderCard()}
          </div>
        </div>

        <div className={styles.gridPreview}>
          <h2 className={styles.previewTitle}>Aperçu en grille (3 colonnes)</h2>
          <div className={styles.grid}>
            {renderCard()}
            {renderCard()}
            {renderCard()}
          </div>
        </div>
      </div>
    </Layout>
  );
};

export default Cards;

