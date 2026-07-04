import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { FaCircleExclamation, FaFlag, FaPen, FaTrash } from 'react-icons/fa6';
import Layout from '../../components/Layout/Layout';
import Breadcrumb from '../../components/Breadcrumb/Breadcrumb';
import { useAuth } from '../../contexts/AuthContext';
import MessagingComposer from '../../components/MessagingComposer/MessagingComposer';
import messagingService, { emitMessagingUnreadRefresh } from '../../services/messagingService';
import { formatMessagingDateTime, messagingApiDateToIsoLocal } from '../../utils/messagingDateFormat';
import { isSubstantialMessagingHtml, sanitizeMessagingHtml } from '../../utils/messagingContent';
import { sanitizeHtml } from '../../utils/sanitize';
import styles from './Messaging.module.css';

function counterpartLetter(title) {
  const t = (title || '?').trim();
  return t ? t.charAt(0).toUpperCase() : '?';
}

const MessagingThread = () => {
  const { id: idParam } = useParams();
  const id = Number(idParam);
  const listRef = useRef(null);
  const { user } = useAuth();

  const [meta, setMeta] = useState(null);
  const [messages, setMessages] = useState([]);
  const [hasMore, setHasMore] = useState(false);
  const [loadingMeta, setLoadingMeta] = useState(true);
  const [loadingMessages, setLoadingMessages] = useState(true);
  const [loadingOlder, setLoadingOlder] = useState(false);
  const [sending, setSending] = useState(false);
  const [draft, setDraft] = useState('');
  const [error, setError] = useState('');
  const [editingMessageId, setEditingMessageId] = useState(null);
  const [editDraft, setEditDraft] = useState('');
  const [editSaving, setEditSaving] = useState(false);
  const [reportingMessageId, setReportingMessageId] = useState(null);
  const [reportReason, setReportReason] = useState('inappropriate');
  const [reportDetails, setReportDetails] = useState('');
  const [reportSubmitting, setReportSubmitting] = useState(false);

  const counterpart = useMemo(() => {
    const participants = meta?.participants;
    if (!Array.isArray(participants) || !user?.id) return null;
    return participants.find((p) => p.id !== user.id) || null;
  }, [meta, user?.id]);

  const isGlobalModerator = Array.isArray(user?.roles) && user.roles.includes('ROLE_MODERATOR');
  const showModerationEntry = Boolean(isGlobalModerator || meta?.canModerate);

  const scrollToBottom = useCallback(() => {
    const el = listRef.current;
    if (el) {
      el.scrollTop = el.scrollHeight;
    }
  }, []);

  const loadMeta = useCallback(async () => {
    if (!Number.isFinite(id) || id <= 0) return;
    setLoadingMeta(true);
    setError('');
    try {
      const data = await messagingService.getConversation(id);
      setMeta(data?.conversation || null);
    } catch (err) {
      setMeta(null);
      setError(err.response?.data?.error || 'Conversation introuvable ou accès refusé.');
    } finally {
      setLoadingMeta(false);
    }
  }, [id]);

  const loadInitialMessages = useCallback(async () => {
    if (!Number.isFinite(id) || id <= 0) return;
    setLoadingMessages(true);
    setError('');
    try {
      const data = await messagingService.getMessages(id, { limit: 40 });
      setMessages(Array.isArray(data?.messages) ? data.messages : []);
      setHasMore(Boolean(data?.hasMore));
      requestAnimationFrame(() => scrollToBottom());
      emitMessagingUnreadRefresh();
    } catch (err) {
      setMessages([]);
      setError(err.response?.data?.error || 'Impossible de charger les messages.');
    } finally {
      setLoadingMessages(false);
    }
  }, [id, scrollToBottom]);

  useEffect(() => {
    if (!Number.isFinite(id) || id <= 0) {
      setError('Identifiant de conversation invalide.');
      setLoadingMeta(false);
      setLoadingMessages(false);
      return;
    }
    loadMeta();
    loadInitialMessages();
  }, [id, loadMeta, loadInitialMessages]);

  const loadOlder = async () => {
    if (!messages.length || loadingOlder || !hasMore) return;
    const oldest = messages[0];
    if (!oldest?.id) return;
    setLoadingOlder(true);
    try {
      const data = await messagingService.getMessages(id, { limit: 40, before: oldest.id });
      const older = Array.isArray(data?.messages) ? data.messages : [];
      setMessages((prev) => [...older, ...prev]);
      setHasMore(Boolean(data?.hasMore));
      emitMessagingUnreadRefresh();
    } catch (err) {
      setError(err.response?.data?.error || 'Impossible de charger les messages plus anciens.');
    } finally {
      setLoadingOlder(false);
    }
  };

  const send = async (e) => {
    e.preventDefault();
    const html = sanitizeMessagingHtml(draft);
    if (!isSubstantialMessagingHtml(html) || sending) return;
    setSending(true);
    setError('');
    try {
      const data = await messagingService.sendMessage(id, { content: html });
      const msg = data?.message;
      if (msg) {
        setMessages((prev) => [...prev, msg]);
        setDraft('');
        requestAnimationFrame(() => scrollToBottom());
      }
    } catch (err) {
      setError(err.response?.data?.error || 'Envoi impossible.');
    } finally {
      setSending(false);
    }
  };

  const startEdit = (m) => {
    setReportingMessageId(null);
    setReportDetails('');
    setEditingMessageId(m.id);
    setEditDraft(m.content || '');
  };

  const cancelEdit = () => {
    setEditingMessageId(null);
    setEditDraft('');
  };

  const saveEdit = async () => {
    if (!editingMessageId) return;
    const html = sanitizeMessagingHtml(editDraft);
    if (!isSubstantialMessagingHtml(html) || editSaving) return;
    setEditSaving(true);
    setError('');
    try {
      const data = await messagingService.updateMessage(id, editingMessageId, { content: html });
      const updated = data?.message;
      if (updated) {
        setMessages((prev) => prev.map((x) => (x.id === updated.id ? { ...x, ...updated } : x)));
        cancelEdit();
      }
    } catch (err) {
      setError(err.response?.data?.error || 'Modification impossible.');
    } finally {
      setEditSaving(false);
    }
  };

  const startReport = (m) => {
    setEditingMessageId(null);
    setEditDraft('');
    setReportingMessageId(m.id);
    setReportReason('inappropriate');
    setReportDetails('');
  };

  const cancelReport = () => {
    setReportingMessageId(null);
    setReportDetails('');
  };

  const submitReport = async () => {
    if (!reportingMessageId || reportSubmitting) return;
    setReportSubmitting(true);
    setError('');
    try {
      const data = await messagingService.reportMessage(id, reportingMessageId, {
        reason: reportReason,
        details: reportDetails.trim() ? reportDetails.trim() : undefined,
      });
      const updated = data?.message;
      if (updated) {
        setMessages((prev) => prev.map((x) => (x.id === updated.id ? { ...x, ...updated } : x)));
        cancelReport();
      }
    } catch (err) {
      setError(err.response?.data?.error || 'Signalement impossible.');
    } finally {
      setReportSubmitting(false);
    }
  };

  const confirmDeleteMessage = async (m) => {
    if (!m?.id) return;
    if (
      !window.confirm(
        'Supprimer ce message ? Il restera dans la conversation sous forme de « [message supprimé] ».',
      )
    ) {
      return;
    }
    setError('');
    try {
      const data = await messagingService.deleteMessage(id, m.id);
      const updated = data?.message;
      if (updated) {
        setMessages((prev) => prev.map((x) => (x.id === updated.id ? { ...x, ...updated } : x)));
        if (editingMessageId === m.id) {
          cancelEdit();
        }
      }
    } catch (err) {
      setError(err.response?.data?.error || 'Suppression impossible.');
    }
  };

  const breadcrumbItems = [
    { name: 'PORTAIL', url: '/', icon: 'home' },
    { name: 'Messagerie', url: '/messagerie', icon: 'forum' },
    { name: meta?.title || 'Conversation', url: null, icon: 'character' },
  ];

  if (!Number.isFinite(id) || id <= 0) {
    return (
      <Layout>
        <div className={styles.content}>
          <p className={styles.error}>Identifiant invalide.</p>
          <Link to="/messagerie" className={styles.btnGhost}>
            Retour à la messagerie
          </Link>
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
      <div className={styles.content}>
        <Breadcrumb items={breadcrumbItems} />

        <div className={styles.threadShell}>
          <header className={styles.threadHeader}>
            <div className={styles.threadHeaderMain}>
              {counterpart?.avatar ? (
                <div className={styles.convAvatarWrap}>
                  <img
                    className={styles.convAvatarImg}
                    src={counterpart.avatar}
                    alt=""
                  />
                </div>
              ) : (
                <div className={styles.convAvatarWrap} aria-hidden>
                  <span className={styles.convAvatarFallback}>
                    {counterpartLetter(counterpart?.pseudo || meta?.title)}
                  </span>
                </div>
              )}
              <div className={styles.threadTitleBlock}>
                <h1 className={styles.threadTitle}>
                  {loadingMeta ? '…' : meta?.title || 'Conversation'}
                </h1>
                <p className={styles.threadSubtitle}>Conversation privée</p>
              </div>
            </div>
            <div className={styles.threadHeaderActions}>
              {showModerationEntry ? (
                <Link
                  to={isGlobalModerator ? '/messagerie/moderation' : `/messagerie/moderation?conversation=${id}`}
                  className={styles.btnGhost}
                >
                  Signalements
                </Link>
              ) : null}
              <Link to="/messagerie" className={styles.btnGhost}>
                ← Toutes les conversations
              </Link>
            </div>
          </header>

          {error && <p className={styles.error}>{error}</p>}

          <div className={styles.main}>
            <div className={styles.messages} ref={listRef}>
              {hasMore && (
                <div className={styles.loadMore}>
                  <button
                    type="button"
                    className={styles.loadMoreBtn}
                    onClick={loadOlder}
                    disabled={loadingOlder || loadingMessages}
                  >
                    {loadingOlder ? 'Chargement…' : 'Messages plus anciens'}
                  </button>
                </div>
              )}
              {loadingMessages && (
                <div className={styles.loadingRow}>
                  <span className={styles.spinner} aria-hidden />
                  <span>Chargement des messages…</span>
                </div>
              )}
              {!loadingMessages && messages.length === 0 && (
                <p className={styles.empty}>Pas encore de message. Dites bonjour !</p>
              )}
              {messages.map((m) => {
                const mine = Boolean(m.isSentByCurrentUser);
                const label = m.character?.name || m.author?.username || 'Membre';
                const avatarUrl =
                  (m.character?.avatar || m.author?.avatar || (mine ? user?.avatar : null) || '').trim() || null;

                let avatarBlock = null;
                if (avatarUrl) {
                  avatarBlock = <img className={styles.bubbleAvatar} src={avatarUrl} alt="" />;
                } else if (mine && user?.pseudo) {
                  avatarBlock = (
                    <span className={styles.bubbleAvatarFallback} aria-hidden>
                      {(user.pseudo || '?').trim().charAt(0).toUpperCase()}
                    </span>
                  );
                } else if (!mine) {
                  avatarBlock = (
                    <span className={styles.bubbleAvatarFallback} aria-hidden>
                      {(label || '?').trim().charAt(0).toUpperCase()}
                    </span>
                  );
                }

                const canEdit = Boolean(m.canEdit);
                const canDelete = Boolean(m.canDelete ?? m.canEdit);
                const canReport = Boolean(m.canReport);
                const hasReported = Boolean(m.hasReportedByViewer);

                const rawContentTrim = String(m.content || '').trim();
                const userSelfDeleted =
                  Boolean(m.userSelfDeleted) ||
                  (m.isDeleted && rawContentTrim.toLowerCase() === '[message supprimé]');
                const moderationDeleted =
                  Boolean(m.moderationDeleted) ||
                  (m.isDeleted &&
                    !userSelfDeleted &&
                    (rawContentTrim === '[message modéré]' ||
                      String(m.content || '').includes('supprimé par un modérateur')));

                const hasActionChrome = hasReported || canReport || canEdit || canDelete;

                return (
                  <div
                    key={m.id}
                    className={`${styles.bubbleRow} ${mine ? styles.bubbleRowMine : ''}`}
                  >
                    <div
                      className={`${styles.bubble} ${mine ? styles.bubbleMine : ''} ${
                        hasActionChrome ? styles.bubbleWithActions : ''
                      }`}
                    >
                      {hasActionChrome ? (
                        <div
                          className={styles.bubbleHoverDock}
                          onClick={(ev) => {
                            ev.stopPropagation();
                          }}
                        >
                          <div className={styles.bubbleActionBar} role="group" aria-label="Actions sur ce message">
                            {hasReported ? (
                              <span
                                className={styles.bubbleReportedMark}
                                title="Déjà signalé"
                                aria-label="Déjà signalé"
                              >
                                <FaCircleExclamation className={styles.bubbleActionIcon} aria-hidden />
                              </span>
                            ) : null}
                            {canReport ? (
                              <button
                                type="button"
                                className={styles.bubbleActionBtn}
                                title="Signaler à la modération"
                                aria-label="Signaler à la modération"
                                onClick={() => startReport(m)}
                              >
                                <FaFlag className={styles.bubbleActionIcon} aria-hidden />
                              </button>
                            ) : null}
                            {canEdit ? (
                              <button
                                type="button"
                                className={styles.bubbleActionBtn}
                                title="Modifier ce message"
                                aria-label="Modifier ce message"
                                onClick={() => startEdit(m)}
                              >
                                <FaPen className={styles.bubbleActionIcon} aria-hidden />
                              </button>
                            ) : null}
                            {canDelete ? (
                              <button
                                type="button"
                                className={styles.bubbleActionBtn}
                                title="Supprimer ce message"
                                aria-label="Supprimer ce message"
                                onClick={() => confirmDeleteMessage(m)}
                              >
                                <FaTrash className={styles.bubbleActionIcon} aria-hidden />
                              </button>
                            ) : null}
                          </div>
                        </div>
                      ) : null}
                      <div className={`${styles.bubbleMeta} ${mine ? styles.bubbleMetaMine : ''}`}>
                        {avatarBlock}
                        <span className={styles.bubbleAuthor}>{label}</span>
                        <time dateTime={messagingApiDateToIsoLocal(m.createdAt) || undefined}>
                          {formatMessagingDateTime(m.createdAt)}
                        </time>
                        {m.isEdited ? (
                          <span className={styles.bubbleEdited} title="Message modifié">
                            (modifié)
                          </span>
                        ) : null}
                      </div>
                      {userSelfDeleted ? (
                        <p className={styles.bubbleUserDeleted}>[message supprimé]</p>
                      ) : moderationDeleted ? (
                        <p className={styles.bubbleModeratedDeleted}>[message modéré]</p>
                      ) : m.isDeleted ? (
                        <div className={styles.bubbleDeleted}>
                          <div
                            className={styles.bubbleBody}
                            dangerouslySetInnerHTML={{ __html: sanitizeHtml(m.content || '') }}
                          />
                        </div>
                      ) : (
                        <div
                          className={styles.bubbleBody}
                          dangerouslySetInnerHTML={{ __html: sanitizeHtml(m.content || '') }}
                        />
                      )}
                    </div>
                  </div>
                );
              })}
            </div>

            {editingMessageId != null && (
              <div className={styles.editPanel}>
                <div className={styles.editPanelHead}>
                  <strong>Modifier votre message</strong>
                  <button type="button" className={styles.btnGhost} onClick={cancelEdit} disabled={editSaving}>
                    Annuler
                  </button>
                </div>
                <MessagingComposer
                  value={editDraft}
                  onChange={setEditDraft}
                  disabled={editSaving}
                  placeholder="Votre message…"
                />
                <div className={styles.panelActions}>
                  <button
                    type="button"
                    className={styles.sendBtn}
                    disabled={editSaving || !isSubstantialMessagingHtml(editDraft)}
                    onClick={saveEdit}
                  >
                    {editSaving ? 'Enregistrement…' : 'Enregistrer'}
                  </button>
                </div>
              </div>
            )}

            {reportingMessageId != null && (
              <div className={styles.reportPanel}>
                <div className={styles.reportPanelHead}>
                  <strong>Signaler ce message à la modération</strong>
                  <button type="button" className={styles.btnGhost} onClick={cancelReport} disabled={reportSubmitting}>
                    Annuler
                  </button>
                </div>
                <label className={styles.reportLabel}>
                  Motif
                  <select
                    className={styles.reportSelect}
                    value={reportReason}
                    onChange={(ev) => setReportReason(ev.target.value)}
                    disabled={reportSubmitting}
                  >
                    {messagingService.getReportReasons().map((opt) => (
                      <option key={opt.value} value={opt.value}>
                        {opt.label}
                      </option>
                    ))}
                  </select>
                </label>
                <label className={styles.reportLabel}>
                  Précisions (optionnel)
                  <textarea
                    className={styles.reportTextarea}
                    rows={3}
                    value={reportDetails}
                    onChange={(ev) => setReportDetails(ev.target.value)}
                    disabled={reportSubmitting}
                    placeholder="Contexte utile pour les modérateurs…"
                    maxLength={4000}
                  />
                </label>
                <div className={styles.panelActions}>
                  <button
                    type="button"
                    className={styles.sendBtn}
                    disabled={reportSubmitting}
                    onClick={submitReport}
                  >
                    {reportSubmitting ? 'Envoi…' : 'Envoyer le signalement'}
                  </button>
                </div>
              </div>
            )}

            {editingMessageId == null && reportingMessageId == null ? (
              <form className={styles.composer} onSubmit={send}>
                <MessagingComposer
                  value={draft}
                  onChange={setDraft}
                  disabled={sending || meta?.canSendMessage === false}
                  placeholder={
                    meta?.canSendMessage === false
                      ? 'Envoi désactivé (sanction ou restriction).'
                      : 'Votre message…'
                  }
                />
                <div className={styles.sendRow}>
                  <button
                    type="submit"
                    className={styles.sendBtn}
                    disabled={
                      sending || !isSubstantialMessagingHtml(draft) || meta?.canSendMessage === false
                    }
                  >
                    {sending ? 'Envoi…' : 'Envoyer'}
                  </button>
                </div>
              </form>
            ) : null}
          </div>
        </div>
      </div>
    </Layout>
  );
};

export default MessagingThread;
