import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import UniverseSelector from '../UniverseSelector/UniverseSelector';
import UniverseSidebar from '../UniverseSidebar/UniverseSidebar';
import UserMenu from '../UserMenu/UserMenu';
import styles from './Layout.module.css';

const Layout = ({ children }) => {
  const { user } = useAuth();
  const [sidebarTopOffset, setSidebarTopOffset] = useState('calc(40px + var(--header-height))');
  const [sidebarCollapsed, setSidebarCollapsed] = useState(() => {
    if (typeof window === 'undefined') {
      return false;
    }

    return localStorage.getItem('universe-sidebar-collapsed') === '1';
  });

  useEffect(() => {
    if (typeof window !== 'undefined') {
      localStorage.setItem('universe-sidebar-collapsed', sidebarCollapsed ? '1' : '0');
    }
  }, [sidebarCollapsed]);

  useEffect(() => {
    if (typeof window === 'undefined') {
      return undefined;
    }

    const updateSidebarTopOffset = () => {
      const topBarHeight = window.innerWidth <= 768 ? 36 : 40;
      const isTopBarOut = window.scrollY >= topBarHeight;
      setSidebarTopOffset(isTopBarOut ? 'var(--header-height)' : `calc(${topBarHeight}px + var(--header-height))`);
    };

    updateSidebarTopOffset();
    window.addEventListener('scroll', updateSidebarTopOffset, { passive: true });
    window.addEventListener('resize', updateSidebarTopOffset);

    return () => {
      window.removeEventListener('scroll', updateSidebarTopOffset);
      window.removeEventListener('resize', updateSidebarTopOffset);
    };
  }, []);

  const sidebarOffset = sidebarCollapsed ? '84px' : '280px';

  return (
    <div
      className={styles.container}
      style={{
        '--sidebar-offset': sidebarOffset,
        '--sidebar-top-offset': sidebarTopOffset,
      }}
    >
      <UniverseSidebar
        collapsed={sidebarCollapsed}
        onToggleCollapse={() => setSidebarCollapsed((prev) => !prev)}
      />
      <div className={styles.topBar}>
        <div className={styles.topBarContent}>
          <UniverseSelector />
        </div>
      </div>
      <header className={styles.header}>
        <div className={styles.headerContent}>
          <Link to="/" className={styles.logo}>
            <img 
              src={`${process.env.PUBLIC_URL}/images/comics earth logo.png`} 
              alt="Earth Forum" 
              className={styles.logoImage}
            />
          </Link>
          <nav className={styles.nav}>
            <Link to="/forums" className={styles.navLink}>
              Forums
            </Link>
            <Link to="/qui-sommes-nous" className={styles.navLink}>
              Qui sommes-nous
            </Link>
            {user && (
              <Link to="/factions" className={styles.navLink}>
                Factions
              </Link>
            )}
            {process.env.NODE_ENV === 'development' && (
              <Link to="/components" className={styles.navLink}>
                Composants
              </Link>
            )}
            {user ? (
              <UserMenu />
            ) : (
              <>
                <Link to="/login" className={styles.navLink}>
                  Connexion
                </Link>
                <Link to="/register" className={styles.navLink}>
                  Inscription
                </Link>
              </>
            )}
          </nav>
        </div>
      </header>

      <main className={styles.main}>
        <div className={styles.mainContent}>{children}</div>
      </main>

      <footer className={styles.footer}>
        <div className={styles.footerContent}>
          <div className={styles.footerLinks}>
            <Link to="/qui-sommes-nous" className={styles.footerLink}>
              Qui sommes-nous
            </Link>
            <span className={styles.footerSeparator}>•</span>
            <Link to="/" className={styles.footerLink}>
              Accueil
            </Link>
          </div>
          <p className={styles.footerText}>
            © {new Date().getFullYear()} Earth Forum - Forum de roleplay
          </p>
        </div>
      </footer>
    </div>
  );
};

export default Layout;

