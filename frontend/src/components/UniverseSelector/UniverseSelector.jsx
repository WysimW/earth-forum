import React, { useState, useEffect, useRef } from 'react';
import { Link } from 'react-router-dom';
import { useUniverseTheme } from '../../contexts/UniverseThemeContext';
import universeService from '../../services/universeService';
import styles from './UniverseSelector.module.css';

const UniverseSelector = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [universes, setUniverses] = useState([]);
  const [loading, setLoading] = useState(true);
  const { currentUniverse, setUniverseTheme } = useUniverseTheme();
  const dropdownRef = useRef(null);

  useEffect(() => {
    const fetchUniverses = async () => {
      try {
        const data = await universeService.getUniverses();
        setUniverses(data);
      } catch (err) {
        console.error('Erreur lors du chargement des univers:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchUniverses();
  }, []);

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [isOpen]);

  const currentUniverseData = universes.find(u => u.slug === currentUniverse) || null;

  const handleUniverseSelect = (universe) => {
    setUniverseTheme(universe.slug);
    setIsOpen(false);
  };

  const handlePortalClick = () => {
    setUniverseTheme('portal');
    setIsOpen(false);
  };

  if (loading || universes.length === 0) {
    return null;
  }

  return (
    <div className={styles.selector} ref={dropdownRef}>
      <button
        className={styles.selectorButton}
        onClick={() => setIsOpen(!isOpen)}
        aria-expanded={isOpen}
        aria-haspopup="true"
      >
        <span className={styles.selectorValue}>
          {currentUniverseData?.name || 'Portail'}
        </span>
        <svg
          className={`${styles.arrow} ${isOpen ? styles.arrowOpen : ''}`}
          width="16"
          height="16"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="2"
          strokeLinecap="round"
          strokeLinejoin="round"
        >
          <polyline points="6 9 12 15 18 9" />
        </svg>
      </button>

      {isOpen && (
        <div className={styles.dropdown}>
          <button
            className={`${styles.dropdownItem} ${
              currentUniverse === 'portal' ? styles.active : ''
            }`}
            onClick={handlePortalClick}
          >
            <span className={styles.itemName}>Portail</span>
            <span className={styles.itemDescription}>Vue d'ensemble</span>
          </button>
          {universes.map((universe) => (
            <button
              key={universe.id}
              className={`${styles.dropdownItem} ${
                currentUniverse === universe.slug ? styles.active : ''
              }`}
              onClick={() => handleUniverseSelect(universe)}
            >
              <span className={styles.itemName}>{universe.name}</span>
              {universe.description && (
                <span className={styles.itemDescription}>
                  {universe.description.length > 50
                    ? `${universe.description.substring(0, 50)}...`
                    : universe.description}
                </span>
              )}
              {universe.forumCount !== undefined && (
                <span className={styles.itemStats}>
                  {universe.forumCount} forums
                </span>
              )}
            </button>
          ))}
        </div>
      )}
    </div>
  );
};

export default UniverseSelector;
