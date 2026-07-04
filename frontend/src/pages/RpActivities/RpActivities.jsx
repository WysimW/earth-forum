import React, { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Layout from '../../components/Layout/Layout';
import Breadcrumb from '../../components/Breadcrumb/Breadcrumb';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import RpActivityCard from '../../components/RpActivityCard/RpActivityCard';
import rpActivityService from '../../services/rpActivityService';
import { useUniverseTheme } from '../../contexts/UniverseThemeContext';
import forumFilters from '../ForumDetail/ForumDetail.module.css';
import styles from './RpActivities.module.css';

const FILTER_ALL = 'all';
const FILTER_EVENT = 'event';
const FILTER_MISSION = 'mission';
const STATUS_ALL = 'all';
const STATUS_OPEN = 'open';
const STATUS_CLOSED = 'closed';

const RpActivities = () => {
  const navigate = useNavigate();
  const { currentUniverse } = useUniverseTheme();
  const [activities, setActivities] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [filter, setFilter] = useState(FILTER_ALL);
  const [statusFilter, setStatusFilter] = useState(STATUS_ALL);
  const [columnView, setColumnView] = useState(1);
  const [permissions, setPermissions] = useState({ canCreate: false });

  const universeSlug = useMemo(
    () => (currentUniverse && currentUniverse !== 'portal' ? currentUniverse : ''),
    [currentUniverse]
  );

  const fetchActivities = async () => {
    if (!universeSlug) {
      setActivities([]);
      setLoading(false);
      setError('Sélectionne un univers pour afficher les events et missions.');
      return;
    }

    setLoading(true);
    setError('');
    try {
      const data = await rpActivityService.getUniverseActivities(universeSlug);
      setActivities(Array.isArray(data?.items) ? data.items : []);
      setPermissions(data?.permissions || { canCreate: false });
    } catch (err) {
      setError(err.response?.data?.error || 'Impossible de charger les events et missions.');
      setActivities([]);
      setPermissions({ canCreate: false });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    const loadPage = async () => {
      if (universeSlug) {
        await rpActivityService.markUniverseActivitiesSeen(universeSlug).catch(() => {});
      }
      await fetchActivities();
    };

    loadPage();
  }, [universeSlug]);

  const filteredActivities = useMemo(() => {
    return activities.filter((activity) => {
      const matchKind = filter === FILTER_ALL || activity.kind === filter;
      const matchStatus = statusFilter === STATUS_ALL || activity.status === statusFilter;
      return matchKind && matchStatus;
    });
  }, [activities, filter, statusFilter]);

  if (loading) {
    return (
      <Layout>
        <Loading message="Chargement des events et missions..." />
      </Layout>
    );
  }

  if (error && activities.length === 0) {
    return (
      <Layout>
        <ErrorMessage message={error} onRetry={fetchActivities} />
      </Layout>
    );
  }

  return (
    <Layout>
      <div className={styles.content}>
        <Breadcrumb
          items={[
            { name: 'FORUMS', url: '/forums' },
            { name: 'Events & Missions', url: null },
          ]}
        />

        <header className={styles.header}>
          <div>
            <h1 className={styles.title}>Events & Missions RP</h1>
            <p className={styles.subtitle}>
              Tous les RP spéciaux de l’univers sélectionné, avec relances et sujets liés.
            </p>
          </div>
          <div className={styles.headerActions}>
            {permissions?.canCreate && (
              <button
                className={styles.backButton}
                type="button"
                onClick={() => navigate('/rp-activities/new')}
              >
                Créer un event
              </button>
            )}
            <button className={styles.backButton} type="button" onClick={() => navigate('/forums')}>
              Retour forums
            </button>
          </div>
        </header>

        <div className={forumFilters.filtersSection}>
          <div className={forumFilters.filtersToolbar}>
            <div className={forumFilters.filtersControls}>
              <div className={forumFilters.controlGroup}>
                <span className={forumFilters.controlLabel}>Type</span>
                <div className={forumFilters.filterButtons}>
                  <button
                    type="button"
                    className={`${forumFilters.filterButton} ${filter === FILTER_ALL ? forumFilters.active : ''}`}
                    onClick={() => setFilter(FILTER_ALL)}
                  >
                    Tous
                  </button>
                  <button
                    type="button"
                    className={`${forumFilters.filterButton} ${filter === FILTER_EVENT ? forumFilters.active : ''}`}
                    onClick={() => setFilter(FILTER_EVENT)}
                  >
                    Events
                  </button>
                  <button
                    type="button"
                    className={`${forumFilters.filterButton} ${filter === FILTER_MISSION ? forumFilters.active : ''}`}
                    onClick={() => setFilter(FILTER_MISSION)}
                  >
                    Missions
                  </button>
                </div>
              </div>
              <div className={forumFilters.controlGroup}>
                <span className={forumFilters.controlLabel}>Statut</span>
                <div className={forumFilters.filterButtons}>
                  <button
                    type="button"
                    className={`${forumFilters.filterButton} ${statusFilter === STATUS_ALL ? forumFilters.active : ''}`}
                    onClick={() => setStatusFilter(STATUS_ALL)}
                  >
                    Tous
                  </button>
                  <button
                    type="button"
                    className={`${forumFilters.filterButton} ${statusFilter === STATUS_OPEN ? forumFilters.active : ''}`}
                    onClick={() => setStatusFilter(STATUS_OPEN)}
                  >
                    Ouvert
                  </button>
                  <button
                    type="button"
                    className={`${forumFilters.filterButton} ${statusFilter === STATUS_CLOSED ? forumFilters.active : ''}`}
                    onClick={() => setStatusFilter(STATUS_CLOSED)}
                  >
                    Fermé
                  </button>
                </div>
              </div>
              <div className={forumFilters.controlGroup}>
                <span className={forumFilters.controlLabel}>Vue</span>
                <div className={forumFilters.viewButtons}>
                  <button
                    type="button"
                    className={`${forumFilters.filterButton} ${columnView === 1 ? forumFilters.active : ''}`}
                    onClick={() => setColumnView(1)}
                  >
                    1 colonne
                  </button>
                  <button
                    type="button"
                    className={`${forumFilters.filterButton} ${columnView === 2 ? forumFilters.active : ''}`}
                    onClick={() => setColumnView(2)}
                  >
                    2 colonnes
                  </button>
                </div>
              </div>
            </div>
            <div className={forumFilters.toolbarActions}>
              <p className={styles.filtersCount}>
                {filteredActivities.length} activité(s) affichée(s)
              </p>
            </div>
          </div>
        </div>

        {filteredActivities.length === 0 ? (
          <p className={styles.empty}>Aucune activité RP pour ce filtre.</p>
        ) : (
          <div className={`${styles.grid} ${columnView === 1 ? styles.gridOneColumn : styles.gridTwoColumns}`}>
            {filteredActivities.map((activity) => (
              <RpActivityCard
                key={activity.id}
                activity={activity}
                showRegistration={false}
              />
            ))}
          </div>
        )}
      </div>
    </Layout>
  );
};

export default RpActivities;
