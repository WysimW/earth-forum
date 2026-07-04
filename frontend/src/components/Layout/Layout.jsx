import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import footerStatsService from '../../services/footerStatsService';
import universeService from '../../services/universeService';
import UniverseSelector from '../UniverseSelector/UniverseSelector';
import UniverseSidebar from '../UniverseSidebar/UniverseSidebar';
import UserMenu from '../UserMenu/UserMenu';
import styles from './Layout.module.css';

const Layout = ({ children }) => {
  const { user } = useAuth();
  const [footerStatsLoading, setFooterStatsLoading] = useState(true);
  const [footerStats, setFooterStats] = useState({
    universes: 0,
    threads: 0,
  });
  const [liveStatsLoading, setLiveStatsLoading] = useState(true);
  const [liveStats, setLiveStats] = useState({
    onlineCount: 0,
    active7DaysCount: 0,
    onlineUsers: [],
    active7DaysUsers: [],
    totalPosts: 0,
  });
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
    let mounted = true;

    const loadFooterStats = async () => {
      try {
        const universes = await universeService.getUniverses();
        if (!mounted || !Array.isArray(universes)) {
          return;
        }

        const stats = universes.reduce(
          (accumulator, universe) => ({
            universes: accumulator.universes + 1,
            threads: accumulator.threads + (universe?.threadCount || 0),
          }),
          { universes: 0, threads: 0 },
        );

        setFooterStats(stats);
      } catch (error) {
        if (mounted) {
          setFooterStats({ universes: 0, threads: 0 });
        }
      } finally {
        if (mounted) {
          setFooterStatsLoading(false);
        }
      }
    };

    loadFooterStats();

    return () => {
      mounted = false;
    };
  }, []);

  useEffect(() => {
    let mounted = true;

    const loadLiveStats = async () => {
      try {
        const data = await footerStatsService.getLiveStats();
        if (!mounted) {
          return;
        }

        setLiveStats({
          onlineCount: data?.online?.count || 0,
          active7DaysCount: data?.activeLast7Days?.count || 0,
          onlineUsers: Array.isArray(data?.online?.users) ? data.online.users : [],
          active7DaysUsers: Array.isArray(data?.activeLast7Days?.users) ? data.activeLast7Days.users : [],
          totalPosts: data?.forum?.totalPosts || 0,
        });
      } catch (error) {
        if (mounted) {
          setLiveStats({
            onlineCount: 0,
            active7DaysCount: 0,
            onlineUsers: [],
            active7DaysUsers: [],
            totalPosts: 0,
          });
        }
      } finally {
        if (mounted) {
          setLiveStatsLoading(false);
        }
      }
    };

    loadLiveStats();
    const interval = window.setInterval(loadLiveStats, 30000);

    return () => {
      mounted = false;
      window.clearInterval(interval);
    };
  }, []);

  const sidebarOffset = sidebarCollapsed ? '84px' : '280px';
  const formatStat = (value) => new Intl.NumberFormat('fr-FR').format(value || 0);

  return (
    <div
      className={styles.container}
      style={{
        '--sidebar-offset': sidebarOffset,
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
          <div className={styles.footerGrid}>
            <section className={styles.footerColumn}>
              <div className={styles.footerBrand}>
                <img
                  src={`${process.env.PUBLIC_URL}/images/comics earth logo.png`}
                  alt="Earth Forum"
                  className={styles.footerLogo}
                />
              </div>
              <p className={styles.footerText}>
                Hub communautaire multivers pour le roleplay narratif.
              </p>
              <div className={styles.footerLinks}>
                <Link to="/" className={styles.footerLink}>
                  Accueil
                </Link>
                <Link to="/forums" className={styles.footerLink}>
                  Forums
                </Link>
                <Link to="/qui-sommes-nous" className={styles.footerLink}>
                  Qui sommes-nous
                </Link>
              </div>
            </section>

            <section className={styles.footerColumn}>
              <h4 className={styles.footerTitle}>Stats du forum</h4>
              <div className={styles.footerStats}>
                <article className={styles.footerStatCard}>
                  <span className={styles.footerStatValue}>
                    {footerStatsLoading ? '...' : formatStat(footerStats.universes)}
                  </span>
                  <span className={styles.footerStatLabel}>univers actifs</span>
                </article>
                <article className={styles.footerStatCard}>
                  <span className={styles.footerStatValue}>
                    {footerStatsLoading ? '...' : formatStat(footerStats.threads)}
                  </span>
                  <span className={styles.footerStatLabel}>discussions en cours</span>
                </article>
                <article className={styles.footerStatCard}>
                  <span className={styles.footerStatValue}>
                    {liveStatsLoading ? '...' : formatStat(liveStats.totalPosts)}
                  </span>
                  <span className={styles.footerStatLabel}>posts publies</span>
                </article>
                <article className={styles.footerStatCard}>
                  <span className={styles.footerStatValue}>
                    {liveStatsLoading ? '...' : formatStat(liveStats.active7DaysCount)}
                  </span>
                  <span className={styles.footerStatLabel}>joueurs actifs (7 jours)</span>
                </article>
              </div>
            </section>

            <section className={styles.footerColumn}>
              <h4 className={styles.footerTitle}>Joueurs en ligne</h4>
              <p className={styles.footerText}>
                {liveStatsLoading
                  ? 'Mise a jour des presences...'
                  : `${formatStat(liveStats.onlineCount)} joueur(s) connecte(s) maintenant`}
              </p>
              {liveStats.onlineUsers.length > 0 ? (
                <div className={styles.connectedPlayers}>
                  {liveStats.onlineUsers.map((player) => (
                    <span key={player.id || player.pseudo} className={styles.connectedPlayerTag}>
                      @{player.pseudo}
                      {user?.id === player.id ? ' (vous)' : ''}
                    </span>
                  ))}
                </div>
              ) : (
                <p className={styles.footerText}>
                  Aucun joueur actif recemment.
                </p>
              )}
              {!user && (
                <Link to="/login" className={styles.footerActionLink}>
                  Se connecter pour apparaitre en ligne
                </Link>
              )}

              <div className={styles.activePlayersBlock}>
                <h5 className={styles.footerSubTitle}>Actifs les 7 derniers jours</h5>
                {liveStats.active7DaysUsers.length > 0 ? (
                  <div className={styles.connectedPlayers}>
                    {liveStats.active7DaysUsers.map((player) => (
                      <span key={`active-${player.id || player.pseudo}`} className={styles.connectedPlayerTag}>
                        @{player.pseudo}
                        {user?.id === player.id ? ' (vous)' : ''}
                      </span>
                    ))}
                  </div>
                ) : (
                  <p className={styles.footerText}>
                    Aucun joueur actif sur les 7 derniers jours.
                  </p>
                )}
              </div>
            </section>
          </div>

          <div className={styles.footerBottom}>
            <p className={styles.footerText}>
              © {new Date().getFullYear()} Earth Forum - Forum de roleplay
            </p>
          </div>
        </div>
      </footer>
    </div>
  );
};

export default Layout;

