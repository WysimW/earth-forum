import React from 'react';
import { Link } from 'react-router-dom';
import styles from '../Dashboard.module.css';

const DashboardQuickActions = ({ canCreateFaction, universeSlug }) => {
  const forumsLink = universeSlug ? `/univers/${universeSlug}` : '/forums';

  return (
    <section className={styles.section}>
      <header className={`${styles.sectionHeader} ${styles.sectionHeaderMuted}`}>
        <h2 className={styles.sectionTitle}>Actions rapides</h2>
      </header>
      <div className={styles.quickActions}>
        <Link to="/characters/new" className={styles.quickAction}>Créer un personnage</Link>
        <Link to={forumsLink} className={styles.quickAction}>Créer une scène</Link>
        {canCreateFaction && (
          <Link to="/factions/new" className={styles.quickAction}>Créer une faction</Link>
        )}
        <Link to="/factions" className={styles.quickAction}>Voir les factions</Link>
        <Link to={forumsLink} className={styles.quickAction}>Toutes les scènes</Link>
        <Link to="/messagerie" className={styles.quickAction}>Messagerie</Link>
      </div>
    </section>
  );
};

export default DashboardQuickActions;
