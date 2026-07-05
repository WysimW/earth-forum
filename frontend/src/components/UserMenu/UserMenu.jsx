import React, { useState, useRef, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import messagingService, { MESSAGING_UNREAD_REFRESH_EVENT } from '../../services/messagingService';
import styles from './UserMenu.module.css';

const UserMenu = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [isOpen, setIsOpen] = useState(false);
  const [messagingUnread, setMessagingUnread] = useState(0);
  const menuRef = useRef(null);

  useEffect(() => {
    if (!user) {
      setMessagingUnread(0);
      return undefined;
    }
    let cancelled = false;
    const fetchUnread = async () => {
      try {
        const data = await messagingService.getUnreadCount();
        const total = typeof data?.total === 'number' ? data.total : 0;
        if (!cancelled) setMessagingUnread(total);
      } catch {
        if (!cancelled) setMessagingUnread(0);
      }
    };
    fetchUnread();
    const t = setInterval(fetchUnread, 45000);
    const onRefresh = () => {
      fetchUnread();
    };
    window.addEventListener(MESSAGING_UNREAD_REFRESH_EVENT, onRefresh);
    return () => {
      cancelled = true;
      clearInterval(t);
      window.removeEventListener(MESSAGING_UNREAD_REFRESH_EVENT, onRefresh);
    };
  }, [user]);

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (menuRef.current && !menuRef.current.contains(event.target)) {
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

  useEffect(() => {
    if (!isOpen || typeof window === 'undefined') {
      return undefined;
    }

    const isMobile = window.matchMedia('(max-width: 768px)').matches;
    if (!isMobile) {
      return undefined;
    }

    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    return () => {
      document.body.style.overflow = previousOverflow;
    };
  }, [isOpen]);

  const handleLogout = async () => {
    await logout();
    navigate('/');
    setIsOpen(false);
  };

  const isAdmin = user?.roles?.includes('ROLE_ADMIN')
    || user?.roles?.includes('ROLE_MODERATOR')
    || user?.roles?.includes('ROLE_SUPER_ADMIN');
  const backofficeUrl = process.env.REACT_APP_BACKOFFICE_URL || 'http://localhost:3004';

  if (!user) return null;

  return (
    <div className={styles.userMenu} ref={menuRef}>
      <button
        className={styles.userButton}
        onClick={() => setIsOpen(!isOpen)}
        aria-label="Menu utilisateur"
        aria-expanded={isOpen}
      >
        <span className={styles.userName}>{user.pseudo}</span>
        <svg
          className={`${styles.chevron} ${isOpen ? styles.chevronOpen : ''}`}
          width="12"
          height="12"
          viewBox="0 0 12 12"
          fill="none"
          stroke="currentColor"
          strokeWidth="2"
          strokeLinecap="round"
          strokeLinejoin="round"
        >
          <path d="M3 4.5L6 7.5L9 4.5" />
        </svg>
      </button>

      {isOpen && (
        <>
          <button
            type="button"
            className={styles.backdrop}
            aria-label="Fermer le menu utilisateur"
            onClick={() => setIsOpen(false)}
          />
          <div className={styles.dropdown} role="menu">
          <Link
            to="/tableau-de-bord"
            className={styles.menuItem}
            onClick={() => setIsOpen(false)}
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <rect x="3" y="3" width="7" height="7" />
              <rect x="14" y="3" width="7" height="7" />
              <rect x="14" y="14" width="7" height="7" />
              <rect x="3" y="14" width="7" height="7" />
            </svg>
            Mon tableau de bord
          </Link>
          
          <Link
            to="/messagerie"
            className={styles.menuItem}
            onClick={() => setIsOpen(false)}
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z" />
            </svg>
            <span className={styles.menuItemLabel}>Messagerie</span>
            {messagingUnread > 0 && (
              <span className={styles.menuBadge} aria-label={`${messagingUnread} messages non lus`}>
                {messagingUnread > 99 ? '99+' : messagingUnread}
              </span>
            )}
          </Link>

          <Link
            to="/characters"
            className={styles.menuItem}
            onClick={() => setIsOpen(false)}
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
              <circle cx="12" cy="7" r="4" />
            </svg>
            Mes personnages
          </Link>

          <Link
            to="/profil"
            className={styles.menuItem}
            onClick={() => setIsOpen(false)}
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="12" cy="8" r="4" />
              <path d="M4 20a8 8 0 0 1 16 0" />
            </svg>
            Mon profil
          </Link>
          
          <Link
            to="/mes-factions"
            className={styles.menuItem}
            onClick={() => setIsOpen(false)}
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
              <circle cx="9" cy="7" r="4" />
              <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
              <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            </svg>
            Mes factions
          </Link>

          {isAdmin && (
            <a
              href={backofficeUrl}
              className={styles.menuItem}
              onClick={() => setIsOpen(false)}
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
              </svg>
              Administration
            </a>
          )}

          <div className={styles.divider} />

          <button
            className={`${styles.menuItem} ${styles.menuItemDanger}`}
            onClick={handleLogout}
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
              <polyline points="16 17 21 12 16 7" />
              <line x1="21" y1="12" x2="9" y2="12" />
            </svg>
            Déconnexion
          </button>
          </div>
        </>
      )}
    </div>
  );
};

export default UserMenu;






