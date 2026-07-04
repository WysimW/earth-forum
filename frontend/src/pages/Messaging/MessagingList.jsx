import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import Layout from '../../components/Layout/Layout';
import Breadcrumb from '../../components/Breadcrumb/Breadcrumb';
import { useAuth } from '../../contexts/AuthContext';
import messagingService from '../../services/messagingService';
import { formatMessagingDateTime, messagingApiDateToIsoLocal } from '../../utils/messagingDateFormat';
import styles from './Messaging.module.css';

function conversationInitial(title) {
  const t = (title || '?').trim();
  return t ? t.charAt(0).toUpperCase() : '?';
}

const MessagingList = () => {
  const { user } = useAuth();
  const isGlobalModerator = Array.isArray(user?.roles) && user.roles.includes('ROLE_MODERATOR');
  const [conversations, setConversations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    let cancelled = false;
    const load = async () => {
      setLoading(true);
      setError('');
      try {
        const data = await messagingService.getConversations();
        if (!cancelled) {
          setConversations(Array.isArray(data?.conversations) ? data.conversations : []);
        }
      } catch (err) {
        if (!cancelled) {
          setError(err.response?.data?.error || 'Impossible de charger les conversations.');
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    };
    load();
    return () => {
      cancelled = true;
    };
  }, []);

  const breadcrumbItems = [
    { name: 'PORTAIL', url: '/', icon: 'home' },
    { name: 'Messagerie', url: null, icon: 'forum' },
  ];

  return (
    <Layout>
      <div className={styles.content}>
        <Breadcrumb items={breadcrumbItems} />

        <header className={styles.header}>
          <div className={styles.headerContent}>
            <h1 className={styles.title}>Discussions privées</h1>
            <p className={styles.description}>
              Vos échanges avec les autres membres : les conversations déjà ouvertes sont regroupées dans la boîte de
              réception ci-dessous.
            </p>
          </div>
        </header>

        <section className={styles.panel} aria-labelledby="messaging-inbox-heading">
          <div className={styles.panelHeader}>
            <h2 id="messaging-inbox-heading" className={styles.panelHeaderTitle}>
              Boîte de réception
            </h2>
            <div className={styles.panelHeaderActions}>
              {isGlobalModerator ? (
                <Link to="/messagerie/moderation" className={styles.btnGhost}>
                  Signalements
                </Link>
              ) : null}
              <Link to="/messagerie/nouveau" className={styles.btnPrimary}>
                Nouvelle conversation
              </Link>
            </div>
          </div>

          {error && <p className={styles.error}>{error}</p>}

          {loading && (
            <div className={styles.loadingRow}>
              <span className={styles.spinner} aria-hidden />
              <span>Chargement de vos conversations…</span>
            </div>
          )}

          {!loading && !error && conversations.length === 0 && (
            <div className={styles.emptyState}>
              <h3 className={styles.emptyStateTitle}>Aucun message pour l’instant</h3>
              <p className={styles.emptyStateText}>
                Lancez une conversation en renseignant le pseudo exact d’un membre. Vous pourrez reprendre plus tard
                dans cette liste.
              </p>
              <div className={styles.emptyStateActions}>
                <Link to="/messagerie/nouveau" className={styles.btnPrimary}>
                  Écrire à quelqu’un
                </Link>
              </div>
            </div>
          )}

          {!loading && conversations.length > 0 && (
            <ul className={styles.convList}>
              {conversations.map((c) => {
                const unread = c.unreadCount > 0;
                const previewText = c.lastMessagePreview || '';
                const mutedPreview = /supprimé/i.test(previewText);
                const previewDisplay =
                  previewText && c.lastMessageAuthorIsSelf === true ? `Vous : ${previewText}` : previewText;
                const rawTime = c.lastMessageAt || c.updatedAt || '';
                const timeLabel = formatMessagingDateTime(rawTime);
                const timeIso = messagingApiDateToIsoLocal(rawTime);
                return (
                  <li
                    key={c.id}
                    className={`${styles.convCard} ${unread ? styles.convCardUnread : ''}`}
                  >
                    <Link to={`/messagerie/${c.id}`} className={styles.convLink}>
                <div className={styles.convAvatarWrap} aria-hidden>
                  {c.counterpartAvatar ? (
                    <img src={c.counterpartAvatar} alt="" className={styles.convAvatarImg} />
                  ) : (
                    <span className={styles.convAvatarFallback}>{conversationInitial(c.title)}</span>
                  )}
                </div>
                      <div className={styles.convMain}>
                        <div className={styles.convTop}>
                          <p className={styles.convTitle}>
                            <span className={styles.convTitlePrefix}>Conversation avec </span>
                            {c.title}
                          </p>
                          <div className={styles.convTopRight}>
                            <time className={styles.timeTag} dateTime={timeIso || undefined}>
                              {timeLabel || rawTime || ''}
                            </time>
                            {unread && (
                              <span className={styles.badge} aria-label={`${c.unreadCount} non lus`}>
                                {c.unreadCount > 99 ? '99+' : c.unreadCount}
                              </span>
                            )}
                          </div>
                        </div>
                        {previewText ? (
                          <div className={`${styles.preview} ${mutedPreview ? styles.previewMuted : ''}`}>
                            {previewDisplay}
                          </div>
                        ) : null}
                      </div>
                      <span className={styles.convChevron} aria-hidden>
                        ›
                      </span>
                    </Link>
                  </li>
                );
              })}
            </ul>
          )}
        </section>
      </div>
    </Layout>
  );
};

export default MessagingList;
