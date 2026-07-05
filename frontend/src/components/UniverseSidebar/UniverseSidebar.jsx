import React, { useEffect, useMemo, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { useUniverseTheme } from '../../contexts/UniverseThemeContext';
import importantService from '../../services/importantService';
import messagingService, { MESSAGING_UNREAD_REFRESH_EVENT } from '../../services/messagingService';
import rpActivityService from '../../services/rpActivityService';
import styles from './UniverseSidebar.module.css';

const ICONS_BY_KEY = {
  reglement: (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M6 3h9l3 3v15H6z" />
      <path d="M14 3v4h4" />
      <path d="M9 11h6M9 15h6" />
    </svg>
  ),
  mode_emploi: (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <circle cx="12" cy="12" r="9" />
      <path d="M9.5 9a2.5 2.5 0 0 1 5 0c0 2-2.5 2-2.5 4" />
      <path d="M12 17h.01" />
    </svg>
  ),
  member_of_month: (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M12 3l2.6 5.3 5.8.8-4.2 4.1 1 5.8-5.2-2.7L6.8 19l1-5.8-4.2-4.1 5.8-.8z" />
    </svg>
  ),
  character_of_month: (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M12 3l2 4 4 .6-3 2.9.8 4.1L12 12.7 8.2 14.6l.8-4.1-3-2.9 4-.6z" />
      <path d="M5 21a7 7 0 0 1 14 0" />
    </svg>
  ),
  rp_activities: (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M4 5h16v4H4z" />
      <path d="M4 11h10v8H4z" />
      <path d="M16 12h4v7h-4z" />
    </svg>
  ),
  vote: (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M5 11l4 4L19 5" />
      <path d="M4 14v5h16v-7" />
      <path d="M8 19v2h8v-2" />
    </svg>
  ),
  factions: (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M12 3l8 4v6c0 4.5-3.5 9-8 10-4.5-1-8-5.5-8-10V7l8-4z" />
    </svg>
  ),
  dashboard: (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <rect x="3" y="3" width="8" height="8" rx="1" />
      <rect x="13" y="3" width="8" height="5" rx="1" />
      <rect x="13" y="11" width="8" height="10" rx="1" />
      <rect x="3" y="14" width="8" height="7" rx="1" />
    </svg>
  ),
  profile: (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <circle cx="12" cy="8" r="3.5" />
      <path d="M4 20v-1a5 5 0 0 1 5-5h6a5 5 0 0 1 5 5v1" />
    </svg>
  ),
  messaging: (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M4 5h16v10H8l-4 3V5z" />
    </svg>
  ),
  my_characters: (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <circle cx="9" cy="7" r="3.5" />
      <path d="M4 20v-1a4 4 0 0 1 4-4h2a4 4 0 0 1 4 4v1" />
      <circle cx="17" cy="8" r="2.5" />
      <path d="M15 20v-0.5a3 3 0 0 1 3-3h1" />
    </svg>
  ),
  my_factions: (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M6 3h12v18l-6-4-6 4V3z" />
    </svg>
  ),
  forums: (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M4 6h16v12H4z" />
      <path d="M8 10h8M8 14h5" />
    </svg>
  ),
  about: (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <circle cx="12" cy="12" r="9" />
      <path d="M12 10v6M12 7h.01" />
    </svg>
  ),
  login: (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M15 3h4v18h-4" />
      <path d="M10 12H3M7 8l-4 4 4 4" />
    </svg>
  ),
  register: (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
      <circle cx="9" cy="7" r="4" />
      <path d="M19 8v6M22 11h-6" />
    </svg>
  ),
  components: (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <rect x="3" y="3" width="7" height="7" />
      <rect x="14" y="3" width="7" height="7" />
      <rect x="3" y="14" width="7" height="7" />
      <rect x="14" y="14" width="7" height="7" />
    </svg>
  ),
};

const SIDEBAR_PARTITION_KEYS = new Set([
  'reglement',
  'mode_emploi',
  'vote',
  'rp_activities',
  'member_of_month',
  'character_of_month',
]);

const ACCOUNT_LINKS = [
  { key: 'dashboard', url: '/tableau-de-bord', label: 'Mon tableau de bord' },
  { key: 'messaging', url: '/messagerie', label: 'Messagerie' },
  { key: 'profile', url: '/profil', label: 'Mon profil' },
  { key: 'my_characters', url: '/characters', label: 'Mes Personnages' },
  { key: 'my_factions', url: '/mes-factions', label: 'Mes Factions' },
];

const SITE_NAV_LINKS = [
  { key: 'forums', url: '/forums', label: 'Forums' },
  { key: 'about', url: '/qui-sommes-nous', label: 'Qui sommes-nous' },
];

const GUEST_NAV_LINKS = [
  { key: 'login', url: '/login', label: 'Connexion' },
  { key: 'register', url: '/register', label: 'Inscription' },
];

const UniverseSidebar = ({
  collapsed = false,
  onToggleCollapse,
  mobileOpen = false,
  onMobileClose,
}) => {
  const location = useLocation();
  const { user } = useAuth();
  const { currentUniverse } = useUniverseTheme();
  const [items, setItems] = useState([]);
  const [isMobileViewport, setIsMobileViewport] = useState(false);
  const [memberOfMonthUnread, setMemberOfMonthUnread] = useState(false);
  const [characterOfMonthUnread, setCharacterOfMonthUnread] = useState(false);
  const [rpActivitiesUnread, setRpActivitiesUnread] = useState(false);
  const [messagingUnread, setMessagingUnread] = useState(false);

  const universeSlug = useMemo(() => (currentUniverse !== 'portal' ? currentUniverse : ''), [currentUniverse]);

  const itemsByKey = useMemo(() => {
    const map = new Map();
    items.forEach((item) => map.set(item.key, item));
    return map;
  }, [items]);

  const guidanceItems = useMemo(
    () => ['reglement', 'mode_emploi', 'vote'].map((key) => itemsByKey.get(key)).filter(Boolean),
    [itemsByKey],
  );

  const forumOrphanItems = useMemo(
    () => items.filter((item) => !SIDEBAR_PARTITION_KEYS.has(item.key)),
    [items],
  );

  const hasForumSection = useMemo(
    () => Boolean(
      itemsByKey.get('rp_activities')
      || user
      || itemsByKey.get('member_of_month')
      || itemsByKey.get('character_of_month')
      || forumOrphanItems.length > 0,
    ),
    [itemsByKey, user, forumOrphanItems],
  );

  useEffect(() => {
    const loadSidebar = async () => {
      try {
        const data = await importantService.getSidebar(universeSlug);
        setItems(Array.isArray(data?.items) ? data.items : []);
      } catch (error) {
        setItems([]);
      }
    };

    loadSidebar();
  }, [universeSlug]);

  useEffect(() => {
    onMobileClose?.();
  }, [location.pathname, onMobileClose]);

  useEffect(() => {
    if (typeof window === 'undefined') {
      return undefined;
    }

    const mediaQuery = window.matchMedia('(max-width: 1023px)');
    const syncViewport = () => setIsMobileViewport(mediaQuery.matches);

    syncViewport();
    mediaQuery.addEventListener('change', syncViewport);

    return () => mediaQuery.removeEventListener('change', syncViewport);
  }, []);

  const effectiveCollapsed = isMobileViewport ? false : collapsed;

  useEffect(() => {
    const syncMemberOfMonthUnread = async () => {
      if (!universeSlug) {
        setMemberOfMonthUnread(false);
        return;
      }

      try {
        const onMemberPage = location.pathname.startsWith('/member-of-month');
        if (onMemberPage) {
          await importantService.markMemberOfMonthSeen(universeSlug);
          setMemberOfMonthUnread(false);
          return;
        }

        const response = await importantService.getMemberOfMonth(universeSlug);
        setMemberOfMonthUnread(Boolean(response?.isUnread));
      } catch (error) {
        setMemberOfMonthUnread(false);
      }
    };

    syncMemberOfMonthUnread();
  }, [location.pathname, universeSlug]);

  useEffect(() => {
    const syncCharacterOfMonthUnread = async () => {
      if (!universeSlug) {
        setCharacterOfMonthUnread(false);
        return;
      }

      try {
        const onCharacterPage = location.pathname.startsWith('/character-of-month');
        if (onCharacterPage) {
          await importantService.markCharacterOfMonthSeen(universeSlug);
          setCharacterOfMonthUnread(false);
          return;
        }

        const response = await importantService.getCharacterOfMonth(universeSlug);
        setCharacterOfMonthUnread(Boolean(response?.isUnread));
      } catch (error) {
        setCharacterOfMonthUnread(false);
      }
    };

    syncCharacterOfMonthUnread();
  }, [location.pathname, universeSlug]);

  useEffect(() => {
    const syncRpActivitiesUnread = async () => {
      if (!universeSlug) {
        setRpActivitiesUnread(false);
        return;
      }

      try {
        const onActivitiesPage = location.pathname.startsWith('/rp-activities');
        if (onActivitiesPage) {
          await rpActivityService.markUniverseActivitiesSeen(universeSlug);
          setRpActivitiesUnread(false);
          return;
        }

        const response = await rpActivityService.getUniverseActivities(universeSlug);
        const hasUnread = Boolean(response?.hasUnread)
          || (Array.isArray(response?.items) && response.items.some((item) => Boolean(item?.isUnread)));
        setRpActivitiesUnread(hasUnread);
      } catch (error) {
        setRpActivitiesUnread(false);
      }
    };

    syncRpActivitiesUnread();
  }, [location.pathname, universeSlug]);

  useEffect(() => {
    if (!user) {
      setMessagingUnread(false);
      return undefined;
    }
    let cancelled = false;
    const syncMessagingUnread = async () => {
      try {
        const data = await messagingService.getUnreadCount();
        const total = typeof data?.total === 'number' ? data.total : 0;
        if (!cancelled) setMessagingUnread(total > 0);
      } catch {
        if (!cancelled) setMessagingUnread(false);
      }
    };
    syncMessagingUnread();
    const t = setInterval(syncMessagingUnread, 60000);
    const onRefresh = () => {
      syncMessagingUnread();
    };
    window.addEventListener(MESSAGING_UNREAD_REFRESH_EVENT, onRefresh);
    return () => {
      cancelled = true;
      clearInterval(t);
      window.removeEventListener(MESSAGING_UNREAD_REFRESH_EVENT, onRefresh);
    };
  }, [user, location.pathname]);

  const isSiteNavActive = (item) => {
    switch (item.key) {
      case 'forums':
        return location.pathname === '/forums'
          || location.pathname.startsWith('/forums/')
          || location.pathname.startsWith('/univers/');
      case 'about':
        return location.pathname.startsWith('/qui-sommes-nous');
      case 'login':
        return location.pathname.startsWith('/login');
      case 'register':
        return location.pathname.startsWith('/register');
      case 'components':
        return location.pathname.startsWith('/components');
      default:
        return location.pathname === item.url;
    }
  };

  const renderSiteNavLink = (item) => {
    const icon = ICONS_BY_KEY[item.key] || (
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M10 13a5 5 0 0 0 7 0l2-2a5 5 0 0 0-7-7l-1 1" />
        <path d="M14 11a5 5 0 0 0-7 0l-2 2a5 5 0 0 0 7 7l1-1" />
      </svg>
    );

    return (
      <Link
        to={item.url}
        className={`${styles.link} ${isSiteNavActive(item) ? styles.linkActive : ''}`}
        title={item.label}
      >
        <span className={styles.icon} aria-hidden="true">
          {icon}
        </span>
        <span className={styles.label}>{item.label}</span>
      </Link>
    );
  };

  const isLinkActive = (item) => {
    switch (item.key) {
      case 'factions':
        return location.pathname.startsWith('/factions');
      case 'my_factions':
        return location.pathname.startsWith('/mes-factions');
      case 'dashboard':
        return location.pathname.startsWith('/tableau-de-bord');
      case 'messaging':
        return location.pathname.startsWith('/messagerie');
      case 'profile':
        return location.pathname.startsWith('/profil');
      case 'my_characters':
        return location.pathname.startsWith('/characters');
      case 'rp_activities':
        return location.pathname.startsWith('/rp-activities');
      case 'reglement':
        return location.pathname.startsWith('/reglement');
      case 'mode_emploi':
        return location.pathname.startsWith('/mode-emploi');
      case 'member_of_month':
        return location.pathname.startsWith('/member-of-month');
      case 'character_of_month':
        return location.pathname.startsWith('/character-of-month');
      default:
        return location.pathname === item.url;
    }
  };

  const renderLink = (item) => {
    const disabled = !universeSlug && (
      item.key === 'member_of_month'
      || item.key === 'character_of_month'
      || item.key === 'rp_activities'
    );
    const unread = (
      (item.key === 'member_of_month' && memberOfMonthUnread)
      || (item.key === 'character_of_month' && characterOfMonthUnread)
      || (item.key === 'rp_activities' && rpActivitiesUnread)
      || (item.key === 'messaging' && messagingUnread)
    ) && !disabled;
    const icon = ICONS_BY_KEY[item.key] || (
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M10 13a5 5 0 0 0 7 0l2-2a5 5 0 0 0-7-7l-1 1" />
        <path d="M14 11a5 5 0 0 0-7 0l-2 2a5 5 0 0 0 7 7l1-1" />
      </svg>
    );

    const content = (
      <>
        <span className={styles.icon} aria-hidden="true">
          {icon}
          {unread && <span className={styles.unreadDot} />}
        </span>
        {!effectiveCollapsed && <span className={styles.label}>{item.label}</span>}
      </>
    );

    if (item.isExternal) {
      return (
        <a
          href={item.url}
          className={`${styles.link} ${unread ? styles.linkUnread : ''} ${disabled ? styles.linkDisabled : ''}`}
          target="_blank"
          rel="noopener noreferrer"
          title={item.label}
          onClick={(event) => {
            if (disabled) {
              event.preventDefault();
            }
          }}
        >
          {content}
        </a>
      );
    }

    return (
      <Link
        to={item.url}
        className={`${styles.link} ${unread ? styles.linkUnread : ''} ${isLinkActive(item) ? styles.linkActive : ''} ${disabled ? styles.linkDisabled : ''}`}
        title={item.label}
        onClick={(event) => {
          if (disabled) {
            event.preventDefault();
          }
        }}
      >
        {content}
      </Link>
    );
  };

  return (
    <>
      {mobileOpen && (
        <button
          type="button"
          className={styles.backdrop}
          aria-label="Fermer le menu Important"
          onClick={onMobileClose}
        />
      )}

      <aside
        className={`${styles.sidebar} ${collapsed ? styles.sidebarCollapsed : ''} ${mobileOpen ? styles.sidebarMobileOpen : ''}`}
        aria-hidden={isMobileViewport && !mobileOpen}
      >
        <div className={styles.header}>
          {!effectiveCollapsed && (
            <div>
              <span className={styles.kicker}>Univers</span>
              <h3 className={styles.title}>Important</h3>
            </div>
          )}
          <button
            type="button"
            className={styles.collapseButton}
            onClick={onToggleCollapse}
            aria-label={collapsed ? 'Agrandir la sidebar' : 'Réduire la sidebar'}
            title={collapsed ? 'Agrandir' : 'Réduire'}
          >
            <svg viewBox="0 0 24 24" aria-hidden="true" className={collapsed ? styles.chevronOpen : ''}>
              <path d="M15 6l-6 6 6 6" />
            </svg>
          </button>
          {isMobileViewport && (
            <button
              type="button"
              className={styles.mobileCloseButton}
              onClick={onMobileClose}
              aria-label="Fermer le menu Important"
            >
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M6 6l12 12M18 6L6 18" />
              </svg>
            </button>
          )}
        </div>
        <div className={styles.scroll}>
          <div className={styles.links}>
          <section className={`${styles.category} ${styles.siteNav}`} aria-label="Navigation">
            <h4 className={styles.categoryTitle}>Navigation</h4>
            <div className={styles.categoryLinks}>
              {SITE_NAV_LINKS.map((item) => (
                <div key={item.key}>{renderSiteNavLink(item)}</div>
              ))}
              {process.env.NODE_ENV === 'development' && (
                <div key="components">
                  {renderSiteNavLink({ key: 'components', url: '/components', label: 'Composants' })}
                </div>
              )}
              {!user && GUEST_NAV_LINKS.map((item) => (
                <div key={item.key}>{renderSiteNavLink(item)}</div>
              ))}
            </div>
          </section>

          {guidanceItems.length > 0 && (
            <section className={styles.category} aria-label="Règlement et liens">
              <div className={styles.categoryLinks}>
                {guidanceItems.map((item) => (
                  <div key={item.key}>{renderLink(item)}</div>
                ))}
              </div>
            </section>
          )}

          {hasForumSection && (
            <section className={styles.category} aria-label="Forum">
              {!effectiveCollapsed && <h4 className={styles.categoryTitle}>Forum</h4>}
              <div className={styles.categoryLinks}>
                {itemsByKey.get('rp_activities') && (
                  <div key="rp_activities">{renderLink(itemsByKey.get('rp_activities'))}</div>
                )}
                {user && (
                  <div key="factions">
                    {renderLink({ key: 'factions', url: '/factions', label: 'Factions' })}
                  </div>
                )}
                {itemsByKey.get('member_of_month') && (
                  <div key="member_of_month">{renderLink(itemsByKey.get('member_of_month'))}</div>
                )}
                {itemsByKey.get('character_of_month') && (
                  <div key="character_of_month">{renderLink(itemsByKey.get('character_of_month'))}</div>
                )}
                {forumOrphanItems.map((item) => (
                  <div key={item.key}>{renderLink(item)}</div>
                ))}
              </div>
            </section>
          )}

          {user && (
            <section className={styles.category} aria-label="Mon compte">
              {!effectiveCollapsed && <h4 className={styles.categoryTitle}>Mon compte</h4>}
              <div className={styles.categoryLinks}>
                {ACCOUNT_LINKS.map((item) => (
                  <div key={item.key}>{renderLink(item)}</div>
                ))}
              </div>
            </section>
          )}
          </div>
        </div>
      </aside>
    </>
  );
};

export default UniverseSidebar;

