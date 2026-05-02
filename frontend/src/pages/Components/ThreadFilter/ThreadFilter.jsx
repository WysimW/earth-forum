import React, { useState } from 'react';
import Layout from '../../../components/Layout/Layout';
import Breadcrumb from '../../../components/Breadcrumb/Breadcrumb';
import styles from './ThreadFilter.module.css';

const ThreadFilter = () => {
  const [selectedStyle, setSelectedStyle] = useState('style1');
  const [filters, setFilters] = useState({
    status: 'all',
    search: '',
    myParticipations: false,
  });

  const handleFiltersChange = (newFilters) => {
    setFilters(newFilters);
    console.log('Filtres changés:', newFilters);
  };

  const handleReset = () => {
    setFilters({
      status: 'all',
      search: '',
      myParticipations: false,
    });
  };

  const breadcrumbItems = [
    { name: 'Accueil', url: '/' },
    { name: 'Composants', url: '/components' },
    { name: 'Filtres de Threads', url: null }
  ];

  const stylesList = [
    { id: 'style1', name: 'Style 1 - Horizontal compact' },
    { id: 'style2', name: 'Style 2 - Vertical empilé' },
    { id: 'style3', name: 'Style 3 - Barre avec badges' },
    { id: 'style4', name: 'Style 4 - Onglets' },
    { id: 'style5', name: 'Style 5 - Dropdown groupé' },
    { id: 'style6', name: 'Style 6 - Minimaliste moderne' },
    { id: 'style7', name: 'Style 7 - Onglets moderne' },
  ];

  // Style 1 - Horizontal compact (actuel)
  const renderStyle1 = () => (
    <div className={styles.style1}>
      <div className={styles.filtersContainer}>
        <div className={styles.filtersRow}>
          <div className={styles.searchBox}>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.35-4.35" />
            </svg>
            <input
              type="text"
              placeholder="Rechercher un thread..."
              value={filters.search}
              onChange={(e) => handleFiltersChange({ ...filters, search: e.target.value })}
              className={styles.searchInput}
            />
            {filters.search && (
              <button
                onClick={() => handleFiltersChange({ ...filters, search: '' })}
                className={styles.clearButton}
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <line x1="18" y1="6" x2="6" y2="18" />
                  <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
              </button>
            )}
          </div>
          <div className={styles.filterGroup}>
            <select
              value={filters.status}
              onChange={(e) => handleFiltersChange({ ...filters, status: e.target.value })}
              className={styles.filterSelect}
            >
              <option value="all">Tous les statuts</option>
              <option value="open">Ouverts</option>
              <option value="closed">Fermés</option>
              <option value="archived">Archivés</option>
            </select>
            <label className={styles.toggleLabel}>
              <input
                type="checkbox"
                checked={filters.myParticipations}
                onChange={() => handleFiltersChange({ ...filters, myParticipations: !filters.myParticipations })}
                className={styles.toggleInput}
              />
              <span className={styles.toggleSwitch}>
                <span className={styles.toggleSlider} />
              </span>
              <span className={styles.toggleText}>Mes participations</span>
            </label>
          </div>
          {(filters.status !== 'all' || filters.search || filters.myParticipations) && (
            <button onClick={handleReset} className={styles.resetButton}>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <polyline points="1 4 1 10 7 10" />
                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10" />
              </svg>
              Réinitialiser
            </button>
          )}
        </div>
      </div>
    </div>
  );

  // Style 2 - Vertical empilé
  const renderStyle2 = () => (
    <div className={styles.style2}>
      <div className={styles.filtersContainer}>
        <div className={styles.searchBox}>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
          <input
            type="text"
            placeholder="Rechercher un thread..."
            value={filters.search}
            onChange={(e) => handleFiltersChange({ ...filters, search: e.target.value })}
            className={styles.searchInput}
          />
          {filters.search && (
            <button
              onClick={() => handleFiltersChange({ ...filters, search: '' })}
              className={styles.clearButton}
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>
          )}
        </div>
        <div className={styles.filtersRow}>
          <select
            value={filters.status}
            onChange={(e) => handleFiltersChange({ ...filters, status: e.target.value })}
            className={styles.filterSelect}
          >
            <option value="all">Tous les statuts</option>
            <option value="open">Ouverts</option>
            <option value="closed">Fermés</option>
            <option value="archived">Archivés</option>
          </select>
          <label className={styles.toggleLabel}>
            <input
              type="checkbox"
              checked={filters.myParticipations}
              onChange={() => handleFiltersChange({ ...filters, myParticipations: !filters.myParticipations })}
              className={styles.toggleInput}
            />
            <span className={styles.toggleSwitch}>
              <span className={styles.toggleSlider} />
            </span>
            <span className={styles.toggleText}>Mes participations</span>
          </label>
          {(filters.status !== 'all' || filters.search || filters.myParticipations) && (
            <button onClick={handleReset} className={styles.resetButton}>
              Réinitialiser
            </button>
          )}
        </div>
      </div>
    </div>
  );

  // Style 3 - Barre avec badges
  const renderStyle3 = () => (
    <div className={styles.style3}>
      <div className={styles.filtersContainer}>
        <div className={styles.searchBox}>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
          <input
            type="text"
            placeholder="Rechercher..."
            value={filters.search}
            onChange={(e) => handleFiltersChange({ ...filters, search: e.target.value })}
            className={styles.searchInput}
          />
        </div>
        <div className={styles.badgesRow}>
          <button
            className={`${styles.badge} ${filters.status === 'all' ? styles.active : ''}`}
            onClick={() => handleFiltersChange({ ...filters, status: 'all' })}
          >
            Tous
          </button>
          <button
            className={`${styles.badge} ${filters.status === 'open' ? styles.active : ''}`}
            onClick={() => handleFiltersChange({ ...filters, status: 'open' })}
          >
            Ouverts
          </button>
          <button
            className={`${styles.badge} ${filters.status === 'closed' ? styles.active : ''}`}
            onClick={() => handleFiltersChange({ ...filters, status: 'closed' })}
          >
            Fermés
          </button>
          <label className={`${styles.badge} ${styles.toggleBadge} ${filters.myParticipations ? styles.active : ''}`}>
            <input
              type="checkbox"
              checked={filters.myParticipations}
              onChange={() => handleFiltersChange({ ...filters, myParticipations: !filters.myParticipations })}
              className={styles.toggleInput}
            />
            Mes participations
          </label>
        </div>
      </div>
    </div>
  );

  // Style 4 - Onglets
  const renderStyle4 = () => (
    <div className={styles.style4}>
      <div className={styles.filtersContainer}>
        <div className={styles.tabsRow}>
          <button
            className={`${styles.tab} ${filters.status === 'all' ? styles.active : ''}`}
            onClick={() => handleFiltersChange({ ...filters, status: 'all' })}
          >
            Tous
          </button>
          <button
            className={`${styles.tab} ${filters.status === 'open' ? styles.active : ''}`}
            onClick={() => handleFiltersChange({ ...filters, status: 'open' })}
          >
            Ouverts
          </button>
          <button
            className={`${styles.tab} ${filters.status === 'closed' ? styles.active : ''}`}
            onClick={() => handleFiltersChange({ ...filters, status: 'closed' })}
          >
            Fermés
          </button>
          <div className={styles.tabSpacer} />
          <div className={styles.searchBox}>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.35-4.35" />
            </svg>
            <input
              type="text"
              placeholder="Rechercher..."
              value={filters.search}
              onChange={(e) => handleFiltersChange({ ...filters, search: e.target.value })}
              className={styles.searchInput}
            />
          </div>
          <label className={styles.toggleLabel}>
            <input
              type="checkbox"
              checked={filters.myParticipations}
              onChange={() => handleFiltersChange({ ...filters, myParticipations: !filters.myParticipations })}
              className={styles.toggleInput}
            />
            <span className={styles.toggleSwitch}>
              <span className={styles.toggleSlider} />
            </span>
            <span className={styles.toggleText}>Mes participations</span>
          </label>
        </div>
      </div>
    </div>
  );

  // Style 5 - Dropdown groupé
  const renderStyle5 = () => (
    <div className={styles.style5}>
      <div className={styles.filtersContainer}>
        <div className={styles.filtersRow}>
          <div className={styles.searchBox}>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.35-4.35" />
            </svg>
            <input
              type="text"
              placeholder="Rechercher un thread..."
              value={filters.search}
              onChange={(e) => handleFiltersChange({ ...filters, search: e.target.value })}
              className={styles.searchInput}
            />
          </div>
          <div className={styles.filterDropdown}>
            <button className={styles.dropdownButton}>
              <span>Filtres</span>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <polyline points="6 9 12 15 18 9" />
              </svg>
            </button>
            <div className={styles.dropdownContent}>
              <div className={styles.dropdownItem}>
                <label>Statut</label>
                <select
                  value={filters.status}
                  onChange={(e) => handleFiltersChange({ ...filters, status: e.target.value })}
                  className={styles.filterSelect}
                >
                  <option value="all">Tous</option>
                  <option value="open">Ouverts</option>
                  <option value="closed">Fermés</option>
                  <option value="archived">Archivés</option>
                </select>
              </div>
              <div className={styles.dropdownItem}>
                <label className={styles.toggleLabel}>
                  <input
                    type="checkbox"
                    checked={filters.myParticipations}
                    onChange={() => handleFiltersChange({ ...filters, myParticipations: !filters.myParticipations })}
                    className={styles.toggleInput}
                  />
                  <span className={styles.toggleSwitch}>
                    <span className={styles.toggleSlider} />
                  </span>
                  <span className={styles.toggleText}>Mes participations</span>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );

  // Style 6 - Minimaliste moderne
  const renderStyle6 = () => (
    <div className={styles.style6}>
      <div className={styles.filtersContainer}>
        <div className={styles.filtersRow}>
          <div className={styles.searchBox}>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.35-4.35" />
            </svg>
            <input
              type="text"
              placeholder="Rechercher..."
              value={filters.search}
              onChange={(e) => handleFiltersChange({ ...filters, search: e.target.value })}
              className={styles.searchInput}
            />
          </div>
          <div className={styles.filterGroup}>
            <select
              value={filters.status}
              onChange={(e) => handleFiltersChange({ ...filters, status: e.target.value })}
              className={styles.filterSelect}
            >
              <option value="all">Statut</option>
              <option value="open">Ouverts</option>
              <option value="closed">Fermés</option>
              <option value="archived">Archivés</option>
            </select>
            <label className={styles.toggleLabel}>
              <input
                type="checkbox"
                checked={filters.myParticipations}
                onChange={() => handleFiltersChange({ ...filters, myParticipations: !filters.myParticipations })}
                className={styles.toggleInput}
              />
              <span className={styles.toggleSwitch}>
                <span className={styles.toggleSlider} />
              </span>
              <span className={styles.toggleText}>Mes participations</span>
            </label>
          </div>
        </div>
      </div>
    </div>
  );

  // Style 7 - Onglets moderne avec couleurs cohérentes
  const renderStyle7 = () => (
    <div className={styles.style7}>
      <div className={styles.filtersContainer}>
        <div className={styles.tabsContainer}>
          <div className={styles.tabsRow}>
            <button
              className={`${styles.tab} ${filters.status === 'all' ? styles.active : ''}`}
              onClick={() => handleFiltersChange({ ...filters, status: 'all' })}
            >
              Tous
            </button>
            <button
              className={`${styles.tab} ${filters.status === 'open' ? styles.active : ''}`}
              onClick={() => handleFiltersChange({ ...filters, status: 'open' })}
            >
              Ouverts
            </button>
            <button
              className={`${styles.tab} ${filters.status === 'closed' ? styles.active : ''}`}
              onClick={() => handleFiltersChange({ ...filters, status: 'closed' })}
            >
              Fermés
            </button>
            <button
              className={`${styles.tab} ${filters.status === 'archived' ? styles.active : ''}`}
              onClick={() => handleFiltersChange({ ...filters, status: 'archived' })}
            >
              Archivés
            </button>
          </div>
          <div className={styles.toolsRow}>
            <div className={styles.searchBox}>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.35-4.35" />
              </svg>
              <input
                type="text"
                placeholder="Rechercher..."
                value={filters.search}
                onChange={(e) => handleFiltersChange({ ...filters, search: e.target.value })}
                className={styles.searchInput}
              />
              {filters.search && (
                <button
                  onClick={() => handleFiltersChange({ ...filters, search: '' })}
                  className={styles.clearButton}
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                  </svg>
                </button>
              )}
            </div>
            <label className={styles.toggleLabel}>
              <input
                type="checkbox"
                checked={filters.myParticipations}
                onChange={() => handleFiltersChange({ ...filters, myParticipations: !filters.myParticipations })}
                className={styles.toggleInput}
              />
              <span className={styles.toggleSwitch}>
                <span className={styles.toggleSlider} />
              </span>
              <span className={styles.toggleText}>Mes participations</span>
            </label>
          </div>
        </div>
      </div>
    </div>
  );

  const renderCurrentStyle = () => {
    switch (selectedStyle) {
      case 'style1':
        return renderStyle1();
      case 'style2':
        return renderStyle2();
      case 'style3':
        return renderStyle3();
      case 'style4':
        return renderStyle4();
      case 'style5':
        return renderStyle5();
      case 'style6':
        return renderStyle6();
      case 'style7':
        return renderStyle7();
      default:
        return renderStyle1();
    }
  };

  return (
    <Layout>
      <div className={styles.container}>
        <Breadcrumb items={breadcrumbItems} />
        <div className={styles.controls}>
          <h1 className={styles.pageTitle}>Styles de Filtres de Threads</h1>
          <div className={styles.styleSelector}>
            <div className={styles.buttons}>
              {stylesList.map((style) => (
                <button
                  key={style.id}
                  className={`${styles.styleBtn} ${selectedStyle === style.id ? styles.active : ''}`}
                  onClick={() => setSelectedStyle(style.id)}
                >
                  {style.name}
                </button>
              ))}
            </div>
          </div>
        </div>

        <div className={styles.preview}>
          <h2 className={styles.previewTitle}>Aperçu</h2>
          {renderCurrentStyle()}
        </div>
      </div>
    </Layout>
  );
};

export default ThreadFilter;

