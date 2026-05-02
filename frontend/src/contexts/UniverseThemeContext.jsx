import React, { createContext, useContext, useState, useEffect } from 'react';

const UniverseThemeContext = createContext(null);

export const UniverseThemeProvider = ({ children }) => {
  // Initialiser directement depuis localStorage de manière synchrone
  const getInitialUniverse = () => {
    if (typeof window !== 'undefined') {
      const savedTheme = localStorage.getItem('universe-theme');
      return savedTheme || 'portal';
    }
    return 'portal';
  };

  const [currentUniverse, setCurrentUniverse] = useState(getInitialUniverse);

  const applyTheme = (universeSlug) => {
    // Appliquer l'attribut data-universe sur le body
    if (typeof window !== 'undefined') {
      document.body.setAttribute('data-universe', universeSlug);
      // Sauvegarder dans localStorage
      localStorage.setItem('universe-theme', universeSlug);
    }
    
    setCurrentUniverse(universeSlug);
  };

  useEffect(() => {
    // Appliquer le thème initial au montage
    const initialUniverse = getInitialUniverse();
    applyTheme(initialUniverse);
  }, []);

  const setUniverseTheme = (universeSlug) => {
    applyTheme(universeSlug);
  };

  const value = {
    currentUniverse,
    setUniverseTheme,
  };

  return (
    <UniverseThemeContext.Provider value={value}>
      {children}
    </UniverseThemeContext.Provider>
  );
};

export const useUniverseTheme = () => {
  const context = useContext(UniverseThemeContext);
  if (!context) {
    throw new Error('useUniverseTheme must be used within an UniverseThemeProvider');
  }
  return context;
};

export default UniverseThemeContext;

