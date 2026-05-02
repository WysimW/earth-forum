import React, { useEffect, useMemo, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useUniverseTheme } from '../../contexts/UniverseThemeContext';
import importantService from '../../services/importantService';
import styles from './UniverseSidebar.module.css';

const MEMBER_OF_MONTH_SEEN_KEY = 'member-of-month-seen';
const CHARACTER_OF_MONTH_SEEN_KEY = 'character-of-month-seen';

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
  vote: (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M5 11l4 4L19 5" />
      <path d="M4 14v5h16v-7" />
      <path d="M8 19v2h8v-2" />
    </svg>
  ),
};

const UniverseSidebar = ({ collapsed = false, onToggleCollapse }) => {
  const location = useLocation();
  const { currentUniverse } = useUniverseTheme();
  const [items, setItems] = useState([]);
  const [openMobile, setOpenMobile] = useState(false);
  const [memberOfMonthUnread, setMemberOfMonthUnread] = useState(false);
  const [characterOfMonthUnread, setCharacterOfMonthUnread] = useState(false);

  const universeSlug = useMemo(() => (currentUniverse !== 'portal' ? currentUniverse : ''), [currentUniverse]);

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
    setOpenMobile(false);
  }, [location.pathname]);

  useEffect(() => {
    const syncMemberOfMonthUnread = async () => {
      if (!universeSlug || typeof window === 'undefined') {
        setMemberOfMonthUnread(false);
        return;
      }

      try {
        const response = await importantService.getMemberOfMonth(universeSlug);
        const latestEntryId = response?.entry?.id ? String(response.entry.id) : '';
        const storageKey = `${MEMBER_OF_MONTH_SEEN_KEY}:${universeSlug}`;

        if (!latestEntryId) {
          setMemberOfMonthUnread(false);
          return;
        }

        const onMemberPage = location.pathname.startsWith('/member-of-month');
        if (onMemberPage) {
          localStorage.setItem(storageKey, latestEntryId);
          setMemberOfMonthUnread(false);
          return;
        }

        const seenEntryId = localStorage.getItem(storageKey);
        setMemberOfMonthUnread(seenEntryId !== latestEntryId);
      } catch (error) {
        setMemberOfMonthUnread(false);
      }
    };

    syncMemberOfMonthUnread();
  }, [location.pathname, universeSlug]);

  useEffect(() => {
    const syncCharacterOfMonthUnread = async () => {
      if (!universeSlug || typeof window === 'undefined') {
        setCharacterOfMonthUnread(false);
        return;
      }

      try {
        const response = await importantService.getCharacterOfMonth(universeSlug);
        const latestEntryId = response?.entry?.id ? String(response.entry.id) : '';
        const storageKey = `${CHARACTER_OF_MONTH_SEEN_KEY}:${universeSlug}`;

        if (!latestEntryId) {
          setCharacterOfMonthUnread(false);
          return;
        }

        const onCharacterPage = location.pathname.startsWith('/character-of-month');
        if (onCharacterPage) {
          localStorage.setItem(storageKey, latestEntryId);
          setCharacterOfMonthUnread(false);
          return;
        }

        const seenEntryId = localStorage.getItem(storageKey);
        setCharacterOfMonthUnread(seenEntryId !== latestEntryId);
      } catch (error) {
        setCharacterOfMonthUnread(false);
      }
    };

    syncCharacterOfMonthUnread();
  }, [location.pathname, universeSlug]);

  const renderLink = (item) => {
    const disabled = !universeSlug && (item.key === 'member_of_month' || item.key === 'character_of_month');
    const unread = (
      (item.key === 'member_of_month' && memberOfMonthUnread)
      || (item.key === 'character_of_month' && characterOfMonthUnread)
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
        {!collapsed && <span className={styles.label}>{item.label}</span>}
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
        className={`${styles.link} ${unread ? styles.linkUnread : ''} ${location.pathname === item.url ? styles.linkActive : ''} ${disabled ? styles.linkDisabled : ''}`}
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
      <button
        type="button"
        className={styles.mobileToggle}
        onClick={() => setOpenMobile((prev) => !prev)}
      >
        Important
      </button>

      <aside className={`${styles.sidebar} ${collapsed ? styles.sidebarCollapsed : ''} ${openMobile ? styles.sidebarMobileOpen : ''}`}>
        <div className={styles.header}>
          {!collapsed && (
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
        </div>
        <div className={styles.links}>
          {items.map((item) => (
            <div key={item.key}>{renderLink(item)}</div>
          ))}
        </div>
      </aside>
    </>
  );
};

export default UniverseSidebar;

