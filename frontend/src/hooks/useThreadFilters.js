import { useMemo, useCallback, useRef, useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';

/**
 * Hook personnalisé pour gérer les filtres de threads
 * Utilise l'URL comme source unique de vérité
 */
const useThreadFilters = (userId = null) => {
  const [searchParams, setSearchParams] = useSearchParams();
  
  // État local pour la recherche (debounce)
  const [searchInput, setSearchInput] = useState(searchParams.get('search') || '');
  const debounceRef = useRef(null);

  // Dériver les filtres depuis l'URL (source unique de vérité)
  const filters = useMemo(() => ({
    status: searchParams.get('status') || 'all',
    search: searchParams.get('search') || '',
    myParticipations: searchParams.get('myParticipations') === 'true',
  }), [searchParams]);

  // Page courante
  const currentPage = useMemo(() => {
    return parseInt(searchParams.get('page')) || 1;
  }, [searchParams]);

  // Fonction utilitaire pour mettre à jour les paramètres URL
  const updateSearchParams = useCallback((updates) => {
    setSearchParams(prev => {
      const newParams = new URLSearchParams(prev);
      
      Object.entries(updates).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '' || value === 'all' || value === false) {
          newParams.delete(key);
        } else if (value === true) {
          newParams.set(key, 'true');
        } else {
          newParams.set(key, String(value));
        }
      });

      // Reset page à 1 si on change un filtre (sauf si on change la page elle-même)
      if (!('page' in updates)) {
        newParams.delete('page');
      }

      return newParams;
    }, { replace: true, preventScrollReset: true });
  }, [setSearchParams]);

  // Handler pour changer le statut
  const setStatus = useCallback((status) => {
    updateSearchParams({ status });
  }, [updateSearchParams]);

  // Handler pour la recherche (avec debounce)
  const setSearch = useCallback((value) => {
    setSearchInput(value);
    
    // Clear previous timeout
    if (debounceRef.current) {
      clearTimeout(debounceRef.current);
    }
    
    // Debounce de 400ms pour la recherche
    debounceRef.current = setTimeout(() => {
      updateSearchParams({ search: value });
    }, 400);
  }, [updateSearchParams]);

  // Handler pour le toggle "mes participations"
  const setMyParticipations = useCallback((value) => {
    updateSearchParams({ myParticipations: value });
  }, [updateSearchParams]);

  // Handler pour changer de page
  const setPage = useCallback((page) => {
    updateSearchParams({ page: page > 1 ? page : null });
  }, [updateSearchParams]);

  // Handler pour reset tous les filtres
  const resetFilters = useCallback(() => {
    setSearchInput('');
    setSearchParams(new URLSearchParams(), { replace: true, preventScrollReset: true });
  }, [setSearchParams]);

  // Synchroniser l'input de recherche avec l'URL au montage
  useEffect(() => {
    const urlSearch = searchParams.get('search') || '';
    if (searchInput !== urlSearch) {
      setSearchInput(urlSearch);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // Cleanup du timeout au démontage
  useEffect(() => {
    return () => {
      if (debounceRef.current) {
        clearTimeout(debounceRef.current);
      }
    };
  }, []);

  // Préparer les filtres pour l'API (memoized pour éviter les re-renders)
  const apiFilters = useMemo(() => {
    const result = {
      status: filters.status,
      search: filters.search,
      userId: filters.myParticipations && userId ? userId : null,
    };
    return result;
  }, [filters.status, filters.search, filters.myParticipations, userId]);

  // Vérifier si des filtres sont actifs
  const hasActiveFilters = useMemo(() => {
    return filters.status !== 'all' || filters.search !== '' || filters.myParticipations;
  }, [filters]);

  return {
    // État des filtres (depuis l'URL)
    filters,
    currentPage,
    searchInput, // Pour l'input de recherche (non débounced)
    hasActiveFilters,
    
    // Filtres prêts pour l'API
    apiFilters,
    
    // Setters
    setStatus,
    setSearch,
    setMyParticipations,
    setPage,
    resetFilters,
  };
};

export default useThreadFilters;


