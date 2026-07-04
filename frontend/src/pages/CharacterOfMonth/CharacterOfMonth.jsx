import React, { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import Layout from '../../components/Layout/Layout';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import { useUniverseTheme } from '../../contexts/UniverseThemeContext';
import { useAuth } from '../../contexts/AuthContext';
import importantService from '../../services/importantService';
import styles from './CharacterOfMonth.module.css';

const ALIGNMENT_META = {
  hero: { label: 'Super-heros', tone: 'hero' },
  villain: { label: 'Super-vilain', tone: 'villain' },
  antihero: { label: 'Anti-heros', tone: 'antihero' },
  vigilante: { label: 'Vigilante', tone: 'vigilante' },
  neutral: { label: 'Neutre', tone: 'neutral' },
  other: { label: 'Autre', tone: 'other' },
};

const getAlignmentMeta = (rawAlignment) => {
  const key = String(rawAlignment || '').toLowerCase().trim();
  if (ALIGNMENT_META[key]) {
    return ALIGNMENT_META[key];
  }

  return {
    label: rawAlignment || 'Non defini',
    tone: 'other',
  };
};

const CharacterOfMonth = () => {
  const { universeSlug: routeUniverseSlug } = useParams();
  const { currentUniverse } = useUniverseTheme();
  const { isAuthenticated } = useAuth();
  const universeSlug = routeUniverseSlug || (currentUniverse !== 'portal' ? currentUniverse : '');

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [entry, setEntry] = useState(null);
  const [history, setHistory] = useState([]);

  const loadData = async () => {
    if (!universeSlug) {
      setLoading(false);
      return;
    }

    try {
      setLoading(true);
      setError('');
      const [latest, historyResponse] = await Promise.all([
        importantService.getCharacterOfMonth(universeSlug),
        importantService.getCharacterOfMonthHistory(universeSlug),
      ]);
      setEntry(latest?.entry || null);
      setHistory(Array.isArray(historyResponse?.items) ? historyResponse.items : []);
    } catch (err) {
      setError('Erreur lors du chargement du personnage du mois');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [universeSlug]);

  useEffect(() => {
    if (!universeSlug || !isAuthenticated) {
      return;
    }

    importantService.markCharacterOfMonthSeen(universeSlug).catch(() => {});
  }, [universeSlug, isAuthenticated]);

  if (loading) {
    return (
      <Layout>
        <Loading message="Chargement du personnage du mois..." />
      </Layout>
    );
  }

  if (error) {
    return (
      <Layout>
        <ErrorMessage message={error} onRetry={loadData} />
      </Layout>
    );
  }

  if (!universeSlug) {
    return (
      <Layout>
        <ErrorMessage message="Sélectionnez un univers pour consulter cette page." />
      </Layout>
    );
  }

  const quickPayload = entry?.quickCreatePayload || {};
  const prefill = encodeURIComponent(JSON.stringify(quickPayload));
  const alignmentMeta = getAlignmentMeta(entry?.alignment);
  const hasImage = Boolean(entry?.imageUrl);

  return (
    <Layout>
      <div className={styles.page}>
        <h1 className={styles.title}>Personnage du mois</h1>
        {entry ? (
          <article className={styles.featured}>
            {hasImage && (
              <div className={styles.mediaColumn}>
                <img src={entry.imageUrl} alt={entry.nickname} className={styles.image} />
                <p className={styles.imageDate}>{entry.month}/{entry.year}</p>
              </div>
            )}
            <div className={styles.content}>
              <h2 className={styles.name}>{entry.firstName} {entry.lastName} ({entry.nickname})</h2>
              <p className={styles.meta}>
                {!hasImage && <span>{entry.month}/{entry.year}</span>}
                <span className={`${styles.alignmentBadge} ${styles[`alignment${alignmentMeta.tone}`]}`}>
                  {alignmentMeta.label}
                </span>
              </p>
              <h3>Qui est-ce ?</h3>
              <p>{entry.whoIsText}</p>
              <h3>Pourquoi l'incarner ?</h3>
              <p>{entry.whyPlayText}</p>
              <h3>Pouvoirs</h3>
              <p>{entry.powers}</p>
              <h3>Faiblesses</h3>
              <p>{entry.weaknesses}</p>
              <Link to={`/characters/new?prefill=${prefill}`} className={styles.cta}>
                Créer un personnage inspiré
              </Link>
            </div>
          </article>
        ) : (
          <p className={styles.empty}>Aucun personnage du mois sélectionné pour cet univers.</p>
        )}

        <section>
          <h3>Historique</h3>
          <ul className={styles.history}>
            {history.map((item) => (
              <li key={item.id}>
                {item.month}/{item.year} - {item.firstName} {item.lastName} ({item.nickname})
              </li>
            ))}
          </ul>
        </section>
      </div>
    </Layout>
  );
};

export default CharacterOfMonth;

