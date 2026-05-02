import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';
import api from '../services/api';
import { useAuth } from './AuthContext';

const STORAGE_KEY = 'admin_universe_context';
const UniverseContext = createContext(null);

export const UniverseProvider = ({ children }) => {
  const { user, isAuthenticated } = useAuth();
  const [universes, setUniverses] = useState([]);
  const [selectedUniverseId, setSelectedUniverseId] = useState(null);

  const isSuperAdmin = useMemo(
    () => !!user?.roles?.includes('ROLE_SUPER_ADMIN'),
    [user]
  );

  useEffect(() => {
    if (!isAuthenticated) {
      setUniverses([]);
      setSelectedUniverseId(null);
      localStorage.removeItem(STORAGE_KEY);
      return;
    }

    const fetchUniverses = async () => {
      try {
        const response = await api.get('/api/universes');
        const allUniverses = response.data?.universes || [];
        const allowedIds = new Set((user?.adminUniverses || []).map((u) => u.id));
        const available = isSuperAdmin
          ? allUniverses
          : allUniverses.filter((u) => allowedIds.has(u.id));

        setUniverses(available);

        const storedValue = localStorage.getItem(STORAGE_KEY);
        if (storedValue && available.some((u) => String(u.id) === String(storedValue))) {
          setSelectedUniverseId(storedValue);
          return;
        }

        setSelectedUniverseId(null);
        localStorage.removeItem(STORAGE_KEY);
      } catch (error) {
        console.error('Error fetching universes for context:', error);
      }
    };

    fetchUniverses();
  }, [isAuthenticated, isSuperAdmin, user]);

  const updateSelectedUniverseId = (value) => {
    const nextValue = value ?? null;
    setSelectedUniverseId(nextValue);
    if (nextValue) {
      localStorage.setItem(STORAGE_KEY, String(nextValue));
    } else {
      localStorage.removeItem(STORAGE_KEY);
    }
  };

  const contextValue = useMemo(() => ({
    universes,
    selectedUniverseId,
    setSelectedUniverseId: updateSelectedUniverseId,
    isSuperAdmin,
  }), [universes, selectedUniverseId, isSuperAdmin]);

  return (
    <UniverseContext.Provider value={contextValue}>
      {children}
    </UniverseContext.Provider>
  );
};

export const useUniverse = () => {
  const context = useContext(UniverseContext);
  if (!context) {
    throw new Error('useUniverse must be used within UniverseProvider');
  }
  return context;
};

