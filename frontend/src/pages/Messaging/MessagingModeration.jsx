import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import Layout from '../../components/Layout/Layout';
import Breadcrumb from '../../components/Breadcrumb/Breadcrumb';
import { useAuth } from '../../contexts/AuthContext';
import messagingService from '../../services/messagingService';
import { formatMessagingDateTime } from '../../utils/messagingDateFormat';
import styles from './Messaging.module.css';

function userIsGlobalModerator(user) {
  return Array.isArray(user?.roles) && user.roles.includes('ROLE_MODERATOR');
}

const MessagingModeration = () => {
  const { user } = useAuth();
  const [searchParams] = useSearchParams();
  const conversationParam = searchParams.get('conversation');
  const conversationId = conversationParam != null && String(conversationParam).match(/^\d+$/) ? Number(conversationParam) : null;

  const isGlobalModerator = useMemo(() => userIsGlobalModerator(user), [user]);

  const [reports, setReports] = useState([]);
  const [total, setTotal] = useState(0);
  const [offset, setOffset] = useState(0);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState('');
  const [notesById, setNotesById] = useState({});
  const [busyId, setBusyId] = useState(null);

  const limit = 20;
  const mode = conversationId != null ? 'conversation' : 'global';

  const loadPage = useCallback(
    async (append) => {
      if (conversationId != null) {
        if (!append) {
          setLoading(true);
        }
        setError('');
        try {
          const data = await messagingService.getConversationModerationPendingReports(conversationId);
          const list = Array.isArray(data?.reports) ? data.reports : [];
          setReports(list);
          setTotal(list.length);
          setOffset(0);
        } catch (err) {
          setReports([]);
          setTotal(0);
          setError(err.response?.data?.error || 'Impossible de charger les signalements.');
        } finally {
          setLoading(false);
          setLoadingMore(false);
        }
        return;
      }

      if (!isGlobalModerator) {
        setReports([]);
        setTotal(0);
        setError('');
        setLoading(false);
        setLoadingMore(false);
        return;
      }

      const nextOffset = append ? offset : 0;
      if (append) {
        setLoadingMore(true);
      } else {
        setLoading(true);
      }
      setError('');
      try {
        const data = await messagingService.getModerationPendingReports({
          limit,
          offset: nextOffset,
        });
        const list = Array.isArray(data?.reports) ? data.reports : [];
        const t = typeof data?.total === 'number' ? data.total : list.length;
        if (append) {
          setReports((prev) => [...prev, ...list]);
        } else {
          setReports(list);
        }
        setTotal(t);
        setOffset(nextOffset + list.length);
      } catch (err) {
          if (!append) {
            setReports([]);
            setTotal(0);
          }
          setError(err.response?.data?.error || 'Impossible de charger les signalements.');
      } finally {
        setLoading(false);
        setLoadingMore(false);
      }
    },
    [conversationId, isGlobalModerator, limit, offset],
  );

  useEffect(() => {
    if (conversationId == null && !isGlobalModerator) {
      setLoading(false);
      setReports([]);
      setTotal(0);
      return;
    }
    setOffset(0);
    loadPage(false);
    // eslint-disable-next-line react-hooks/exhaustive-deps -- remount when conversation / role mode changes
  }, [conversationId, isGlobalModerator]);

  const canAccess = conversationId != null || isGlobalModerator;

  const handleResolve = async (reportId, action) => {
    const notes = notesById[reportId] ?? '';
    const label = action === 'approve' ? 'masquer le message ([message modéré])' : 'rejeter ce signalement';
    if (!window.confirm(`Confirmer : ${label} ?`)) {
      return;
    }
    setBusyId(reportId);
    setError('');
    try {
      await messagingService.resolveMessagingReport(reportId, {
        action,
        moderationNotes: notes.trim() || undefined,
      });
      setReports((prev) => prev.filter((r) => r.id !== reportId));
      setTotal((prev) => Math.max(0, prev - 1));
      setNotesById((prev) => {
        const next = { ...prev };
        delete next[reportId];
        return next;
      });
    } catch (err) {
      setError(err.response?.data?.error || 'Action impossible.');
    } finally {
      setBusyId(null);
    }
  };

  const hasMoreGlobal = mode === 'global' && reports.length < total;

  const breadcrumbItems = [
    { name: 'PORTAIL', url: '/', icon: 'home' },
    { name: 'Messagerie', url: '/messagerie', icon: 'forum' },
    { name: 'Signalements', url: null, icon: 'forum' },
  ];

  return (
    <Layout>
      <div className={styles.content}>
        <Breadcrumb items={breadcrumbItems} />

        <header className={styles.header}>
          <div className={styles.headerContent}>
            <h1 className={styles.title}>Signalements — messagerie</h1>
            <p className={styles.description}>
              {conversationId != null
                ? 'Signalements en attente pour cette conversation. Approuver masque le message côté participants ; rejeter classe le signalement sans supprimer le message.'
                : 'Tous les signalements privés en attente (vue modérateur global).'}
            </p>
          </div>
        </header>

        {!canAccess && (
          <section className={styles.panel} aria-labelledby="mod-no-access-heading">
            <h2 id="mod-no-access-heading" className={styles.panelHeaderTitle}>
              Accès
            </h2>
            <p className={styles.moderationHint}>
              Cette page est réservée aux modérateurs globaux, ou ouvrez-la depuis une conversation dont vous êtes
              modérateur ou administrateur avec le lien « Signalements » (paramètre{' '}
              <code className={styles.moderationCode}>?conversation=</code>).
            </p>
            <Link to="/messagerie" className={styles.btnGhost}>
              Retour à la messagerie
            </Link>
          </section>
        )}

        {canAccess && (
          <section className={styles.panel} aria-labelledby="mod-reports-heading">
            <div className={styles.panelHeader}>
              <h2 id="mod-reports-heading" className={styles.panelHeaderTitle}>
                En attente
                {total > 0 ? ` (${reports.length}${mode === 'global' ? ` / ${total}` : ''})` : ''}
              </h2>
              <div className={styles.moderationHeaderActions}>
                {conversationId != null ? (
                  <Link to={`/messagerie/${conversationId}`} className={styles.btnGhost}>
                    Ouvrir la conversation
                  </Link>
                ) : null}
                <Link to="/messagerie" className={styles.btnGhost}>
                  Boîte de réception
                </Link>
              </div>
            </div>

            {error && <p className={styles.error}>{error}</p>}

            {loading && (
              <div className={styles.loadingRow}>
                <span className={styles.spinner} aria-hidden />
                <span>Chargement…</span>
              </div>
            )}

            {!loading && reports.length === 0 && (
              <p className={styles.empty}>Aucun signalement en attente.</p>
            )}

            <ul className={styles.moderationReportList}>
              {reports.map((r) => (
                <li key={r.id} className={styles.moderationReportCard}>
                  <div className={styles.moderationReportMeta}>
                    <span className={styles.moderationReportReason}>{r.reasonLabel || r.reason}</span>
                    <time dateTime={r.createdAt || undefined}>{r.createdAt ? formatMessagingDateTime(r.createdAt) : ''}</time>
                  </div>
                  <p className={styles.moderationReportConv}>
                    Conversation :{' '}
                    {r.conversation?.id != null ? (
                      <Link to={`/messagerie/${r.conversation.id}`}>{r.conversation.title || `#${r.conversation.id}`}</Link>
                    ) : (
                      '—'
                    )}
                  </p>
                  <p className={styles.moderationReportLine}>
                    <strong>Message</strong> #{r.message?.id} {r.message?.authorPseudo ? `— ${r.message.authorPseudo}` : ''}
                  </p>
                  <p className={styles.moderationReportLine}>
                    <strong>Signalé par</strong> {r.reporter?.pseudo || '—'}
                  </p>
                  {r.details ? (
                    <p className={styles.moderationReportDetails}>
                      <strong>Détails :</strong> {r.details}
                    </p>
                  ) : null}
                  {r.bodyPreview ? (
                    <blockquote className={styles.moderationReportExcerpt}>{r.bodyPreview}</blockquote>
                  ) : null}
                  <label className={styles.moderationNotesLabel} htmlFor={`mod-notes-${r.id}`}>
                    Notes internes (optionnel)
                  </label>
                  <textarea
                    id={`mod-notes-${r.id}`}
                    className={styles.moderationNotesInput}
                    rows={2}
                    value={notesById[r.id] ?? ''}
                    onChange={(e) =>
                      setNotesById((prev) => ({
                        ...prev,
                        [r.id]: e.target.value,
                      }))
                    }
                    disabled={busyId === r.id}
                  />
                  <div className={styles.moderationReportActions}>
                    <button
                      type="button"
                      className={styles.btnDangerSoft}
                      disabled={busyId === r.id}
                      onClick={() => handleResolve(r.id, 'approve')}
                    >
                      Approuver (masquer le message)
                    </button>
                    <button
                      type="button"
                      className={styles.btnGhost}
                      disabled={busyId === r.id}
                      onClick={() => handleResolve(r.id, 'reject')}
                    >
                      Rejeter le signalement
                    </button>
                  </div>
                </li>
              ))}
            </ul>

            {mode === 'global' && hasMoreGlobal && (
              <div className={styles.loadMore}>
                <button
                  type="button"
                  className={styles.loadMoreBtn}
                  disabled={loadingMore}
                  onClick={() => loadPage(true)}
                >
                  {loadingMore ? 'Chargement…' : 'Charger plus'}
                </button>
              </div>
            )}
          </section>
        )}
      </div>
    </Layout>
  );
};

export default MessagingModeration;
