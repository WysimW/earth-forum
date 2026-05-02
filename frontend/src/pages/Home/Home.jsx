import React, { useState, useEffect, useMemo } from 'react';
import universeService from '../../services/universeService';
import Layout from '../../components/Layout/Layout';
import UniverseCard from '../../components/UniverseCard/UniverseCard';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import { useFavorites } from '../../hooks/useFavorites';
import styles from './Home.module.css';

const Home = () => {
  const [universes, setUniverses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const { favoriteUniverseId, toggleFavorite, isFavorite } = useFavorites();

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
              // Exemple d'image de fond selon le slug (à remplacer par les vraies images de l'API)
              let backgroundImage = universe.banner || universe.backgroundImage;
              
              // Images par défaut selon le slug pour démonstration
              const defaultImages = {
                'dc': 'https://static0.srcdn.com/wordpress/wp-content/uploads/2024/04/batman-and-the-justice-league-vs-amazos-featured.jpg',
                'marvel': 'https://cdn.mos.cms.futurecdn.net/3CXE6xN4tqV3LrVv2VEvUV.jpg',
                'dc-absolute': 'https://static0.cbrimages.com/wordpress/wp-content/uploads/2024/08/dc-s-absolute-comics-explained.jpg?w=1200&h=675&fit=crop',
                'star-wars-earth': 'https://images.unsplash.com/photo-1533616688419-b7a585564566?w=1200&h=600&fit=crop',
              };
              
              if (!backgroundImage && universe.slug) {
                backgroundImage = defaultImages[universe.slug] || null;
              }

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

