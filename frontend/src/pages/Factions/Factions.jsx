import React, { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Layout from '../../components/Layout/Layout';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import Breadcrumb from '../../components/Breadcrumb/Breadcrumb';
import { useUniverseTheme } from '../../contexts/UniverseThemeContext';
import { useAuth } from '../../contexts/AuthContext';
import factionService from '../../services/factionService';
import styles from './Factions.module.css';

const Factions = ({ mode = 'browse' }) => {
  const navigate = useNavigate();
  const { user } = useAuth();
  const { currentUniverse } = useUniverseTheme();
  const [factions, setFactions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [search, setSearch] = useState('');

  const fetchFactions = async () => {
    try {
      setLoading(true);
      setError(null);
      const params = {};
      if (search.trim()) params.search = search.trim();
      const response = await factionService.getFactions(params);
      setFactions(response.factions || []);
    } catch (err) {
      setError('Erreur lors du chargement des factions');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchFactions();
  }, []);

  const filteredFactions = useMemo(() => {
    let scoped = factions;

    if (currentUniverse !== 'portal') {
      scoped = scoped.filter((faction) => faction.universe?.slug === currentUniverse);
    }

    if (mode === 'mine') {
      scoped = scoped.filter((faction) => faction.isMine);
    }

    return scoped;
  }, [factions, currentUniverse, mode]);

  const handleDelete = async (id) => {
    if (!window.confirm('Supprimer cette faction ?')) return;
    try {
      await factionService.deleteFaction(id);
      await fetchFactions();
    } catch (err) {
      alert(err.response?.data?.error || 'Erreur lors de la suppression');
    }
  };

  const getStatusLabel = (status) => {
    if (status === 'open') return 'Ouverte';
    if (status === 'closed') return 'Fermée';
    return status || 'Inconnu';
  };

  if (loading) {
    return (
      <Layout>
        <Loading message="Chargement des factions..." />
      </Layout>
    );
  }

  if (error) {
    return (
      <Layout>
        <ErrorMessage message={error} onRetry={fetchFactions} />
      </Layout>
    );
  }

  return (
    <Layout>
      <div className={styles.content}>
        <Breadcrumb
          items={[
            { name: 'Accueil', url: '/', icon: 'home' },
            { name: mode === 'mine' ? 'Mes factions' : 'Factions', url: null },
          ]}
        />

        <header className={styles.header}>
          <h1>{mode === 'mine' ? 'Mes factions' : 'Factions'}</h1>
          {user?.canCreateFaction ? (
            <button className={styles.primaryBtn} onClick={() => navigate('/factions/new')}>
              Créer une faction
            </button>
          ) : (
            <p className={styles.infoText}>La création de faction nécessite une autorisation admin.</p>
          )}
        </header>

        <div className={styles.toolbar}>
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Rechercher une faction..."
            className={styles.input}
          />
          <button className={styles.secondaryBtn} onClick={fetchFactions}>Rechercher</button>
        </div>

        {filteredFactions.length === 0 ? (
          <div className={styles.empty}>
            {mode === 'mine'
              ? 'Aucune faction liée à votre compte (chef ou personnage membre).'
              : 'Aucune faction trouvée.'}
          </div>
        ) : (
          <div className={styles.grid}>
            {filteredFactions.map((faction) => (
              <article key={faction.id} className={styles.card}>
                <div className={styles.cardImageContainer}>
                  {faction.logo ? (
                    <img src={faction.logo} alt={faction.name} className={styles.cardImage} />
                  ) : (
                    <div className={styles.cardImagePlaceholder}>
                      {faction.icon ? (
                        <img src={faction.icon} alt={`Icône ${faction.name}`} className={styles.cardIcon} />
                      ) : (
                        <span>{faction.name?.slice(0, 2).toUpperCase()}</span>
                      )}
                    </div>
                  )}
                  <div className={styles.imageGradient} />
                  <div className={styles.statusBadgeContainer}>
                    <span className={`${styles.statusBadge} ${styles[faction.status]}`}>
                      {getStatusLabel(faction.status)}
                    </span>
                  </div>
                </div>

                <div className={styles.cardBody}>
                  <div className={styles.cardHeader}>
                    <h2 className={styles.cardTitle}>{faction.name}</h2>
                    <p className={styles.cardSubtitle}>
                      {faction.alignment || 'Alignement non défini'}
                    </p>
                  </div>

                  <div className={styles.cardInfo}>
                    <div className={styles.infoItem}>
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                      </svg>
                      <span>{faction.universe?.name || 'Sans univers'}</span>
                    </div>
                    <div className={styles.infoItem}>
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <path d="M3 6h18M3 12h18M3 18h18" />
                      </svg>
                      <span>Portée: {faction.scope || 'Non définie'}</span>
                    </div>
                    <div className={styles.infoItem}>
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                      </svg>
                      <span>
                        {faction.membersCount?.characters || 0} persos, {faction.membersCount?.npcs || 0} PNJ
                      </span>
                    </div>
                    <p className={styles.description}>{faction.description || 'Aucune description'}</p>
                  </div>

                  <div className={styles.cardActions}>
                    <button
                      className={styles.btnIcon}
                      onClick={() => navigate(`/factions/${faction.id}`)}
                      title="Voir"
                      aria-label="Voir la faction"
                    >
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                        <circle cx="12" cy="12" r="3" />
                      </svg>
                    </button>
                    {!faction.canEdit && (
                      <button
                        className={styles.btnCardActionText}
                        onClick={() => navigate(`/factions/${faction.id}#postuler`)}
                      >
                        Postuler
                      </button>
                    )}
                    {faction.canEdit && (
                      <>
                        <button
                          className={styles.btnIcon}
                          onClick={() => navigate(`/factions/${faction.id}/edit`)}
                          title="Modifier"
                          aria-label="Modifier la faction"
                        >
                          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                          </svg>
                        </button>
                        <button
                          className={`${styles.btnIcon} ${styles.btnDanger}`}
                          onClick={() => handleDelete(faction.id)}
                          title="Supprimer"
                          aria-label="Supprimer la faction"
                        >
                          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <polyline points="3 6 5 6 21 6" />
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                          </svg>
                        </button>
                      </>
                    )}
                  </div>
                </div>
              </article>
            ))}
          </div>
        )}
      </div>
    </Layout>
  );
};

export default Factions;

