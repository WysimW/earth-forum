import React, { useState, useEffect, useMemo } from 'react';
import universeService from '../../services/universeService';
import seoService from '../../services/seoService';
import Layout from '../../components/Layout/Layout';
import SeoHead from '../../components/Seo/SeoHead';
import UniverseCard from '../../components/UniverseCard/UniverseCard';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import { useFavorites } from '../../hooks/useFavorites';
import styles from './Home.module.css';

const Home = () => {
  const [universes, setUniverses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [seo, setSeo] = useState(null);
  const { favoriteUniverseId, toggleFavorite, isFavorite } = useFavorites();

  useEffect(() => {
    seoService.getPortal().then(setSeo).catch(() => setSeo(null));
  }, []);

  useEffect(() => {
    const fetchUniverses = async () => {
      try {
        const data = await universeService.getUniverses();
        setUniverses(data);
      } catch (err) {
        setError('Erreur lors du chargement des univers');
        console.error(err);
      } finally {
        setLoading(false);
      }
    };

    fetchUniverses();
  }, []);

  // Trier les univers pour mettre le favori en premier
  const sortedUniverses = useMemo(() => {
    if (!favoriteUniverseId) return universes;
    
    const favorite = universes.find(u => u.id === favoriteUniverseId);
    const others = universes.filter(u => u.id !== favoriteUniverseId);
    
    return favorite ? [favorite, ...others] : universes;
  }, [universes, favoriteUniverseId]);


  if (loading) {
    return (
      <Layout>
        <Loading message="Chargement des univers..." />
      </Layout>
    );
  }

  if (error) {
    return (
      <Layout>
        <ErrorMessage
          message={error}
          onRetry={() => {
            setError(null);
            setLoading(true);
            universeService.getUniverses()
              .then(setUniverses)
              .catch((err) => setError('Erreur lors du chargement des univers'))
              .finally(() => setLoading(false));
          }}
        />
      </Layout>
    );
  }

  return (
    <Layout>
      <SeoHead seo={seo} />
      <div className={styles.content}>
        <header className={styles.header}>
          <h1 className={styles.title}>Choisissez votre univers</h1>
          <p className={styles.subtitle}>
            Explorez les différents mondes et commencez votre aventure
          </p>
        </header>

        {sortedUniverses.length === 0 ? (
          <div className={styles.emptyState}>
            <p>Aucun univers disponible pour le moment.</p>
          </div>
        ) : (
          <div className={styles.universesGrid}>
            {sortedUniverses.map((universe, index) => {
              const backgroundImage = universe.portalBanner
                || universe.banner
                || universe.backgroundImage
                || null;

              const isUniverseFavorite = isFavorite(universe.id);
              const isFullWidth = isUniverseFavorite && index === 0;

              return (
                <UniverseCard
                  key={universe.id}
                  universe={{
                    id: universe.id,
                    name: universe.name,
                    slug: universe.slug,
                    description: universe.description,
                    banner: backgroundImage,
                    backgroundImage: backgroundImage,
                    url: '/forums',
                  }}
                  forumCount={universe.forumCount}
                  threadCount={universe.threadCount}
                  isFavorite={isUniverseFavorite}
                  onToggleFavorite={toggleFavorite}
                  isFullWidth={isFullWidth}
                />
              );
            })}
          </div>
        )}
      </div>
    </Layout>
  );
};

export default Home;

