import { useState, useEffect } from 'react';

const FAVORITES_STORAGE_KEY = 'earth-forum-favorite-universe';

export const useFavorites = () => {
  const [favoriteUniverseId, setFavoriteUniverseId] = useState(null);

  useEffect(() => {
    // Charger le favori depuis le localStorage au montage
    const savedFavorite = localStorage.getItem(FAVORITES_STORAGE_KEY);
    if (savedFavorite) {
      try {
        setFavoriteUniverseId(parseInt(savedFavorite, 10));
      } catch (e) {
        console.error('Error parsing favorite universe:', e);
      }
    }
  }, []);

  const setFavorite = (universeId) => {
    if (universeId === null) {
      localStorage.removeItem(FAVORITES_STORAGE_KEY);
      setFavoriteUniverseId(null);
    } else {
      localStorage.setItem(FAVORITES_STORAGE_KEY, universeId.toString());
      setFavoriteUniverseId(universeId);
    }
  };

  const toggleFavorite = (universeId) => {
    if (favoriteUniverseId === universeId) {
      setFavorite(null);
    } else {
      setFavorite(universeId);
    }
  };

  const isFavorite = (universeId) => {
    return favoriteUniverseId === universeId;
  };

  return {
    favoriteUniverseId,
    setFavorite,
    toggleFavorite,
    isFavorite,
  };
};

