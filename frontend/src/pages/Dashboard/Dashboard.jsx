import React, { useCallback, useEffect, useMemo, useState } from 'react';
import Layout from '../../components/Layout/Layout';
import Breadcrumb from '../../components/Breadcrumb/Breadcrumb';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import { useAuth } from '../../contexts/AuthContext';
import { useUniverseTheme } from '../../contexts/UniverseThemeContext';
import dashboardService from '../../services/dashboardService';
import DashboardStats from './components/DashboardStats';
import DashboardThreadFilters from './components/DashboardThreadFilters';
import DashboardSection from './components/DashboardSection';
import DashboardThreadRow from './components/DashboardThreadRow';
import DashboardCharacterRow from './components/DashboardCharacterRow';
import DashboardFactionRow from './components/DashboardFactionRow';
import DashboardQuickActions from './components/DashboardQuickActions';
import styles from './Dashboard.module.css';

const Dashboard = () => {
  const { user } = useAuth();
  const { currentUniverse } = useUniverseTheme();
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [threadType, setThreadType] = useState('all');
  const [threadStatus, setThreadStatus] = useState('all');

  const universeSlug = useMemo(
    () => (currentUniverse && currentUniverse !== 'portal' ? currentUniverse : null),
    [currentUniverse]
  );

  const fetchDashboard = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await dashboardService.getDashboard({
        universe: universeSlug,
        threadType: threadType === 'all' ? undefined : threadType,
        threadStatus: threadStatus === 'all' ? undefined : threadStatus,
        limit: 5,
      });
      setData(response);
    } catch (err) {
      setError(err.response?.data?.error || 'Impossible de charger le tableau de bord.');
      setData(null);
    } finally {
      setLoading(false);
    }
  }, [universeSlug, threadType, threadStatus]);

  useEffect(() => {
    fetchDashboard();
  }, [fetchDashboard]);

  const breadcrumbItems = [
    { name: 'Accueil', url: '/', icon: 'home' },
    { name: 'Tableau de bord', url: null, icon: 'category' },
  ];

  const universeLabel = useMemo(() => {
    if (!universeSlug || !data?.characters?.length) {
      if (!universeSlug) return null;
      const fromThread = data?.participatingThreads?.[0]?.universe?.name
        || data?.createdThreads?.[0]?.universe?.name
        || data?.factions?.[0]?.universe?.name;
      return fromThread || universeSlug;
    }
    const match = data.characters.find((c) => c.universe?.slug === universeSlug);
    return match?.universe?.name || universeSlug;
  }, [universeSlug, data]);

  if (loading && !data) {
    return (
      <Layout>
        <Loading message="Chargement du tableau de bord..." />
      </Layout>
    );
  }

  if (error && !data) {
    return (
      <Layout>
        <ErrorMessage message={error} onRetry={fetchDashboard} />
      </Layout>
    );
  }

  const canCreateFaction = data?.permissions?.canCreateFaction ?? user?.canCreateFaction ?? false;

  return (
    <Layout>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.wrap}>
        <header>
          <h1 className={styles.title}>Mon tableau de bord</h1>
          <p className={styles.lead}>
            {user?.pseudo ? `Bonjour ${user.pseudo}, ` : ''}
            voici un aperçu de votre activité sur le forum.
          </p>
        </header>

        <DashboardStats stats={data?.stats} />

        <DashboardThreadFilters
          threadType={threadType}
          threadStatus={threadStatus}
          onTypeChange={setThreadType}
          onStatusChange={setThreadStatus}
          universeLabel={universeLabel}
        />

        {loading && data && (
          <p className={styles.universeHint}>Mise à jour des filtres…</p>
        )}

        <div className={styles.layout}>
          <div className={styles.column}>
            <DashboardSection
              title="Mes personnages"
              actionLabel="+ Nouveau"
              actionTo="/characters/new"
              isEmpty={!data?.characters?.length}
              emptyTitle="Vous n'avez pas encore créé de personnage"
              emptyMessage="Créez votre premier personnage pour participer aux scènes de roleplay."
              emptyActionLabel="Créer mon premier personnage"
              emptyActionTo="/characters/new"
            >
              <div className={styles.list}>
                {data?.characters?.map((character) => (
                  <DashboardCharacterRow key={character.id} character={character} />
                ))}
              </div>
            </DashboardSection>

            <DashboardSection
              title="Mes participations récentes"
              actionLabel="Explorer"
              actionTo={universeSlug ? `/univers/${universeSlug}` : '/forums'}
              isEmpty={!data?.participatingThreads?.length}
              emptyTitle="Vous ne participez à aucune scène"
              emptyMessage="Rejoignez une scène existante ou créez-en une nouvelle."
              emptyActionLabel="Explorer les scènes"
              emptyActionTo={universeSlug ? `/univers/${universeSlug}` : '/forums'}
            >
              <div className={styles.list}>
                {data?.participatingThreads?.map((thread) => (
                  <DashboardThreadRow key={thread.id} thread={thread} />
                ))}
              </div>
            </DashboardSection>

            <DashboardSection
              title="Mes factions"
              actionLabel={canCreateFaction ? '+ Nouvelle' : 'Tout voir'}
              actionTo={canCreateFaction ? '/factions/new' : '/factions'}
              isEmpty={!data?.factions?.length}
              emptyTitle="Vous n'appartenez à aucune faction"
              emptyMessage="Rejoignez une faction existante ou créez la vôtre."
              emptyActionLabel="Découvrir les factions"
              emptyActionTo="/factions"
            >
              <div className={styles.list}>
                {data?.factions?.map((faction) => (
                  <DashboardFactionRow key={faction.id} faction={faction} />
                ))}
              </div>
            </DashboardSection>
          </div>

          <div className={styles.column}>
            <DashboardSection
              title="Mes créations"
              actionLabel="Nouvelle scène"
              actionTo={universeSlug ? `/univers/${universeSlug}` : '/forums'}
              isEmpty={!data?.createdThreads?.length}
              emptyTitle="Vous n'avez pas encore créé de scène"
              emptyMessage="Lancez votre première histoire."
              emptyActionLabel="Créer une scène"
              emptyActionTo={universeSlug ? `/univers/${universeSlug}` : '/forums'}
            >
              <div className={styles.list}>
                {data?.createdThreads?.map((thread) => (
                  <DashboardThreadRow key={thread.id} thread={thread} />
                ))}
              </div>
            </DashboardSection>

            <DashboardSection
              title="Scènes récentes"
              actionLabel="Tout voir"
              actionTo={universeSlug ? `/univers/${universeSlug}` : '/forums'}
              isEmpty={!data?.recentThreads?.length}
              emptyTitle="Aucune scène récente"
              emptyMessage="Soyez le premier à créer une scène."
              emptyActionLabel="Créer une scène"
              emptyActionTo={universeSlug ? `/univers/${universeSlug}` : '/forums'}
            >
              <div className={styles.list}>
                {data?.recentThreads?.map((thread) => (
                  <DashboardThreadRow key={thread.id} thread={thread} />
                ))}
              </div>
            </DashboardSection>

            <DashboardQuickActions
              canCreateFaction={canCreateFaction}
              universeSlug={universeSlug}
            />
          </div>
        </div>
      </div>
    </Layout>
  );
};

export default Dashboard;
