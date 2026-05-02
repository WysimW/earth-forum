import React from 'react';
import { Link } from 'react-router-dom';
import { useUniverseTheme } from '../../contexts/UniverseThemeContext';
import styles from './UniverseCard.module.css';

const UniverseCard = ({ universe, forumCount, threadCount, onUniverseClick, isFavorite, onToggleFavorite, isFullWidth }) => {
  const { setUniverseTheme } = useUniverseTheme();

  const handleClick = (e) => {
    // Ne pas naviguer si on clique sur le bouton favori
    if (e.target.closest(`.${styles.favoriteButton}`)) {
      e.preventDefault();
      return;
    }

    if (universe.slug) {
      setUniverseTheme(universe.slug);
    }
    if (onUniverseClick) {
      onUniverseClick();
    }
  };

  const handleFavoriteClick = (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (onToggleFavorite) {
      onToggleFavorite(universe.id);
    }
  };

  const backgroundImage = universe.banner || universe.backgroundImage;
  const cardClassName = `${styles.universeCard} ${isFullWidth ? styles.fullWidth : ''}`;

  return (
    <Link
      to={universe.url || `/univers/${universe.slug}`}
      className={cardClassName}
      onClick={handleClick}
      data-universe={universe.slug}
    >
      {backgroundImage && (
        <div 
          className={styles.backgroundImage}
          style={{ backgroundImage: `url("${backgroundImage}")` }}
        />
      )}
      <div className={styles.overlay} />
      <button
        className={styles.favoriteButton}
        onClick={handleFavoriteClick}
        aria-label={isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'}
        title={isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'}
      >
        <svg
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill={isFavorite ? 'currentColor' : 'none'}
          stroke="currentColor"
          strokeWidth="2"
          strokeLinecap="round"
          strokeLinejoin="round"
        >
          <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
        </svg>
      </button>
      <div className={styles.content}>
        <h3 className={styles.title}>{universe.name}</h3>
        {universe.description && (
          <p className={styles.description}>{universe.description}</p>
        )}
        <div className={styles.stats}>
          {forumCount !== undefined && (
            <span className={styles.statItem}>
              {forumCount} {forumCount > 1 ? 'forums' : 'forum'}
            </span>
          )}
          {threadCount !== undefined && (
            <span className={styles.statItem}>
              {threadCount} {threadCount > 1 ? 'discussions' : 'discussion'}
            </span>
          )}
        </div>
      </div>
    </Link>
  );
};

export default UniverseCard;
