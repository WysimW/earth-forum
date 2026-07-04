import React, { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import Layout from '../../components/Layout/Layout';
import Breadcrumb from '../../components/Breadcrumb/Breadcrumb';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import RpActivityRegisterButton from '../../components/RpActivityRegisterButton/RpActivityRegisterButton';
import AvatarEditor from '../../components/AvatarEditor/AvatarEditor';
import rpActivityService from '../../services/rpActivityService';
import { useAuth } from '../../contexts/AuthContext';
import { sanitizeHtml } from '../../utils/sanitize';
import styles from './RpActivityDetail.module.css';

const RpActivityDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const { isAuthenticated } = useAuth();
  const [activity, setActivity] = useState(null);
  const [selectableCharacters, setSelectableCharacters] = useState([]);
  const [eventCharactersContext, setEventCharactersContext] = useState(null);
  const [eventCharacterName, setEventCharacterName] = useState('');
  const [eventCharacterAvatar, setEventCharacterAvatar] = useState('');
  const [eventAvatarEditorOpen, setEventAvatarEditorOpen] = useState(false);
  const [createCharacterOpen, setCreateCharacterOpen] = useState(false);
  const [editingEventCharacter, setEditingEventCharacter] = useState(null);
  const [eventPanelLoading, setEventPanelLoading] = useState(false);
  const [eventPanelMessage, setEventPanelMessage] = useState('');
  const [reminderEditorOpen, setReminderEditorOpen] = useState(false);
  const [reminderDraft, setReminderDraft] = useState('');
  const [reminderSaving, setReminderSaving] = useState(false);
  const [reminderMessage, setReminderMessage] = useState('');
  const [deletingActivity, setDeletingActivity] = useState(false);
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const kindLabel = activity?.kind === 'mission' ? 'Mission' : 'Event';
  const statusLabel = activity?.status === 'closed' ? 'Fermé' : activity?.status === 'draft' ? 'Brouillon' : 'Ouvert';
  const reminderLabel = activity?.reminderAt
    ? new Date(activity.reminderAt).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
      })
    : 'non définie';
  const registrationEndLabel = activity?.registrationEndAt
    ? new Date(activity.registrationEndAt).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
      })
    : 'non définie';
  const speechHtml = useMemo(() => {
    const speech = activity?.openingSpeech || '';
    if (!speech.trim()) return '';
    const hasHtml = /<\/?[a-z][\s\S]*>/i.test(speech);
    const normalized = hasHtml ? speech : speech.replace(/\n/g, '<br />');
    return sanitizeHtml(normalized);
  }, [activity?.openingSpeech]);
  const descriptionHtml = useMemo(() => {
    const description = activity?.description || '';
    if (!description.trim()) return '';
    const hasHtml = /<\/?[a-z][\s\S]*>/i.test(description);
    const normalized = hasHtml ? description : description.replace(/\n/g, '<br />');
    return sanitizeHtml(normalized);
  }, [activity?.description]);
  const hasDescription = Boolean(activity?.description?.trim());
  const hasOpeningSpeech = Boolean(activity?.openingSpeech?.trim());
  const linkedThreads = useMemo(() => {
    const threads = activity?.linkedThreads || [];
    return threads.filter((thread) => {
      const threadType = String(thread?.type || '').toLowerCase();
      const forumType = String(thread?.forum?.type || '').toLowerCase();
      const forumName = String(thread?.forum?.name || '').trim().toLowerCase();
      const isAutoHrpDiscussionForum = forumName === 'mission et évènements' || forumName === 'mission et evenements';
      return threadType !== 'hrp' && forumType !== 'hrp' && !isAutoHrpDiscussionForum;
    });
  }, [activity?.linkedThreads]);

  const canManageEventCharacters = Boolean(activity?.permissions?.canManageEventCharacters);
  const canEditActivity = Boolean(activity?.permissions?.canEdit);

  const syncEventContextState = (context) => {
    setEventCharactersContext(context);
  };

  const fetchEventCharactersContext = async (activityId) => {
    const data = await rpActivityService.getEventCharacters(activityId);
    syncEventContextState(data);
  };

  const fetchDetail = async () => {
    setLoading(true);
    setError('');
    try {
      if (isAuthenticated && id) {
        await rpActivityService.markActivitySeen(id);
      }
      const data = await rpActivityService.getActivity(id);
      setActivity(data);

      if (isAuthenticated) {
        const charactersData = await rpActivityService.getSelectableCharacters(id);
        setSelectableCharacters(Array.isArray(charactersData?.items) ? charactersData.items : []);

        if (data?.permissions?.canManageEventCharacters) {
          await fetchEventCharactersContext(id);
        } else {
          setEventCharactersContext(null);
        }
      } else {
        setSelectableCharacters([]);
        setEventCharactersContext(null);
      }
    } catch (err) {
      setError(err.response?.data?.error || 'Impossible de charger cette activité RP.');
      setActivity(null);
      setSelectableCharacters([]);
    } finally {
      setLoading(false);
    }
  };

  const handleCreateEventCharacter = async (event) => {
    event.preventDefault();
    const name = eventCharacterName.trim();
    if (!name) return;

    setEventPanelLoading(true);
    setEventPanelMessage('');
    try {
      const payload = {
        name,
        avatar: eventCharacterAvatar.trim() || null,
      };
      const data = editingEventCharacter
        ? await rpActivityService.updateEventCharacter(activity.id, editingEventCharacter.id, payload)
        : await rpActivityService.createEventCharacter(activity.id, payload);
      setEventCharacterName('');
      setEventCharacterAvatar('');
      setEditingEventCharacter(null);
      setCreateCharacterOpen(false);
      if (data?.context) {
        syncEventContextState(data.context);
      } else {
        await fetchEventCharactersContext(activity.id);
      }
      setEventPanelMessage(editingEventCharacter ? 'Personnage event mis à jour.' : 'Personnage event créé.');
    } catch (err) {
      setEventPanelMessage(err.response?.data?.error || 'Impossible de sauvegarder le personnage event.');
    } finally {
      setEventPanelLoading(false);
    }
  };

  const handleEventAvatarSave = (avatarUrl) => {
    setEventCharacterAvatar(avatarUrl || '');
    setEventAvatarEditorOpen(false);
  };

  const handleOpenCreateCharacterModal = () => {
    setEditingEventCharacter(null);
    setEventCharacterName('');
    setEventCharacterAvatar('');
    setCreateCharacterOpen(true);
  };

  const handleOpenEditCharacterModal = (character) => {
    setEditingEventCharacter(character);
    setEventCharacterName(character?.name || '');
    setEventCharacterAvatar(character?.avatar || '');
    setCreateCharacterOpen(true);
  };

  const handleCloseCharacterModal = () => {
    setCreateCharacterOpen(false);
    setEditingEventCharacter(null);
    setEventCharacterName('');
    setEventCharacterAvatar('');
  };

  const handleDeleteEventCharacter = async (character) => {
    if (!activity?.id || !character?.id) return;

    const confirmed = window.confirm(`Supprimer le personnage event "${character.name || 'Sans nom'}" ?`);
    if (!confirmed) return;

    setEventPanelLoading(true);
    setEventPanelMessage('');
    try {
      const data = await rpActivityService.deleteEventCharacter(activity.id, character.id);
      if (data?.context) {
        syncEventContextState(data.context);
      } else {
        await fetchEventCharactersContext(activity.id);
      }
      setEventPanelMessage('Personnage event supprimé.');
    } catch (err) {
      setEventPanelMessage(err.response?.data?.error || 'Impossible de supprimer ce personnage event.');
    } finally {
      setEventPanelLoading(false);
    }
  };

  const handleSaveReminder = async () => {
    if (!activity?.id) return;

    setReminderSaving(true);
    setReminderMessage('');
    try {
      const response = await rpActivityService.updateActivity(activity.id, {
        reminderAt: reminderDraft || null,
      });
      const updatedActivity = response?.item || activity;
      setActivity(updatedActivity);
      setReminderEditorOpen(false);
      setReminderDraft(updatedActivity?.reminderAt ? String(updatedActivity.reminderAt).slice(0, 10) : '');
      setReminderMessage('Relance mise à jour.');
    } catch (err) {
      setReminderMessage(err.response?.data?.error || 'Impossible de mettre à jour la relance.');
    } finally {
      setReminderSaving(false);
    }
  };

  const handleDeleteActivity = () => {
    if (!activity?.id || deletingActivity) return;
    setDeleteConfirmOpen(true);
  };

  const handleConfirmDeleteActivity = async () => {
    if (!activity?.id || deletingActivity) return;

    setDeletingActivity(true);
    setError('');
    try {
      await rpActivityService.deleteActivity(activity.id);
      navigate('/rp-activities');
    } catch (err) {
      setError(err.response?.data?.error || 'Impossible de supprimer cette activité.');
      setDeletingActivity(false);
    }
  };

  useEffect(() => {
    setReminderDraft(activity?.reminderAt ? String(activity.reminderAt).slice(0, 10) : '');
  }, [activity?.reminderAt]);

  useEffect(() => {
    fetchDetail();
  }, [id, isAuthenticated]);

  if (loading) {
    return (
      <Layout>
        <Loading message="Chargement de l’activité RP..." />
      </Layout>
    );
  }

  if (error || !activity) {
    return (
      <Layout>
        <ErrorMessage message={error || 'Activité introuvable'} onRetry={fetchDetail} />
      </Layout>
    );
  }

  return (
    <Layout>
      <div className={styles.content}>
        <Breadcrumb
          items={[
            { name: 'FORUMS', url: '/forums' },
            { name: 'Events & Missions', url: '/rp-activities' },
            { name: activity.title, url: null },
          ]}
        />

        <header className={styles.header}>
          {activity.illustrationUrl && (
            <div className={styles.heroIllustration}>
              <img src={activity.illustrationUrl} alt={activity.title} />
            </div>
          )}
          <div className={styles.headerInner}>
            <div className={styles.heroContent}>
              <div className={styles.heroTopRow}>
                <div className={styles.badges}>
                  <span className={styles.kind}>{kindLabel}</span>
                  <span className={styles.status}>{statusLabel}</span>
                </div>
                {activity.permissions?.canEdit && (
                  <div className={styles.heroActions}>
                    <button
                      type="button"
                      className={`${styles.editButton} ${styles.iconOnlyButton}`}
                      onClick={() => navigate(`/rp-activities/${activity.id}/edit`)}
                      aria-label="Éditer l’activité"
                      title="Éditer l’activité"
                    >
                      <svg className={styles.buttonIcon} width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                      </svg>
                    </button>
                    <button
                      type="button"
                      className={`${styles.editButton} ${styles.iconOnlyButton}`}
                      onClick={() => navigate(`/rp-activities/${activity.id}/create-thread`)}
                      aria-label="Créer un sujet RP"
                      title="Créer un sujet RP"
                    >
                      <svg className={styles.buttonIcon} width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                      </svg>
                    </button>
                    <button
                      type="button"
                      className={`${styles.editButton} ${styles.iconOnlyButton} ${styles.dangerButton}`}
                      onClick={handleDeleteActivity}
                      aria-label="Supprimer l’activité"
                      title="Supprimer l’activité"
                      disabled={deletingActivity}
                    >
                      <svg className={styles.buttonIcon} width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                      </svg>
                    </button>
                  </div>
                )}
              </div>
              <h1 className={styles.title}>{activity.title}</h1>
              {hasDescription && (
                <div className={styles.heroDescription} dangerouslySetInnerHTML={{ __html: descriptionHtml }} />
              )}
            </div>

            <div className={styles.metaPanel}>
              <div className={styles.metaItem}>
                <div className={styles.metaItemHeader}>
                  <span className={styles.metaLabel}>Relance</span>
                  {canEditActivity && (
                    <button
                      type="button"
                      className={`${styles.metaActionButton} ${styles.metaActionButtonInline} ${styles.iconOnlyButton}`}
                      onClick={() => {
                        setReminderEditorOpen((prev) => !prev);
                        setReminderMessage('');
                      }}
                      aria-label={reminderEditorOpen ? 'Fermer l’éditeur de relance' : (activity?.reminderAt ? 'Modifier la relance' : 'Définir la relance')}
                      title={reminderEditorOpen ? 'Fermer' : (activity?.reminderAt ? 'Modifier' : 'Définir')}
                    >
                      {reminderEditorOpen ? (
                        <svg className={styles.buttonIcon} width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                          <line x1="18" y1="6" x2="6" y2="18" />
                          <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                      ) : (
                        <svg className={styles.buttonIcon} width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                          <path d="M12 20h9" />
                          <path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4Z" />
                        </svg>
                      )}
                    </button>
                  )}
                </div>
                <span className={styles.metaValue}>{reminderLabel}</span>
                {canEditActivity && (
                  <div className={styles.reminderInlineActions}>
                    {reminderEditorOpen && (
                      <div className={styles.reminderEditor}>
                        <input
                          className={styles.formInput}
                          type="date"
                          value={reminderDraft}
                          onChange={(e) => setReminderDraft(e.target.value)}
                        />
                        <div className={styles.reminderEditorActions}>
                          <button
                            type="button"
                            className={styles.metaActionButton}
                            onClick={handleSaveReminder}
                            disabled={reminderSaving}
                          >
                            {reminderSaving ? 'Enregistrement...' : 'Enregistrer'}
                          </button>
                          <button
                            type="button"
                            className={styles.metaActionButton}
                            onClick={() => setReminderDraft('')}
                            disabled={reminderSaving}
                          >
                            Retirer
                          </button>
                        </div>
                      </div>
                    )}
                  </div>
                )}
                {reminderMessage && <span className={styles.metaFeedback}>{reminderMessage}</span>}
              </div>
              <div className={styles.metaItem}>
                <span className={styles.metaLabel}>Inscriptions</span>
                <span className={styles.metaValue}>{activity.registrationsCount || 0}</span>
              </div>
              <div className={styles.metaItem}>
                <span className={styles.metaLabel}>Clôture inscriptions</span>
                <span className={styles.metaValue}>{registrationEndLabel}</span>
              </div>
            </div>
          </div>
        </header>

        <div className={styles.bodyGrid}>
          <section className={`${styles.section} ${styles.storySection}`}>
            {hasOpeningSpeech && (
              <article className={styles.textBlock}>
                <div className={styles.speech} dangerouslySetInnerHTML={{ __html: speechHtml }} />
              </article>
            )}

            {!hasOpeningSpeech && (
              <p className={styles.meta}>Aucun speech d’ouverture fourni pour le moment.</p>
            )}
          </section>

          <aside className={`${styles.section} ${styles.sideSection}`}>
            <div className={styles.sectionHeader}>
              <h2 className={styles.sectionTitle}>Inscription</h2>
              <p className={`${styles.countBadge} ${styles.registrationCount}`}>
                {activity.registrationsCount || 0} inscrit(s)
              </p>
            </div>
            <p className={styles.registrationHint}>
              Soumettez un personnage: l’organisateur valide ensuite votre inscription.
              {activity.pendingRegistrationsCount > 0 ? ` ${activity.pendingRegistrationsCount} demande(s) en attente.` : ''}
            </p>
            <RpActivityRegisterButton
              activity={activity}
              selectableCharacters={selectableCharacters}
              onRegistered={fetchDetail}
              onUnregistered={fetchDetail}
            />

            <div className={styles.sideDivider} />

            <h3 className={styles.blockTitle}>Sujets RP liés</h3>
            {linkedThreads.length ? (
              <div className={styles.threadList}>
                {linkedThreads.map((thread) => (
                  <Link key={thread.id} className={styles.threadLink} to={`/threads/${thread.slug || thread.id}`}>
                    <span>{thread.title}</span>
                    <span className={styles.threadArrow}>↗</span>
                  </Link>
                ))}
              </div>
            ) : (
              <p className={styles.meta}>Aucun sujet lié pour le moment.</p>
            )}
          </aside>
        </div>

        {canManageEventCharacters && (
          <section className={`${styles.section} ${styles.eventCharactersSection}`}>
            <div className={styles.sectionHeader}>
              <h2 className={styles.sectionTitle}>Personnages Event</h2>
              <div className={styles.eventCharactersHeaderActions}>
                <button
                  type="button"
                  className={styles.editButton}
                  onClick={handleOpenCreateCharacterModal}
                >
                  Créer un personnage event
                </button>
              </div>
            </div>

            <div className={styles.eventCharactersPanel}>
              {eventCharactersContext?.items?.length ? (
                <div className={styles.eventCharacterList}>
                  {eventCharactersContext.items.map((character) => (
                    <div key={character.id} className={styles.eventCharacterItem}>
                      <div className={styles.eventCharacterMain}>
                        {character.avatar ? (
                          <img
                            className={styles.eventCharacterItemAvatar}
                            src={character.avatar}
                            alt={character.name}
                          />
                        ) : (
                          <span className={styles.eventCharacterItemAvatarFallback}>
                            {(character.name || '?').charAt(0).toUpperCase()}
                          </span>
                        )}
                        <span>{character.name}</span>
                      </div>
                      <div className={styles.eventCharacterMeta}>
                        <button
                          type="button"
                          className={styles.eventCharacterIconButton}
                          onClick={() => handleOpenEditCharacterModal(character)}
                          aria-label={`Éditer ${character.name}`}
                          title="Éditer"
                        >
                          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                          </svg>
                        </button>
                        <button
                          type="button"
                          className={`${styles.eventCharacterIconButton} ${styles.eventCharacterIconButtonDanger}`}
                          onClick={() => handleDeleteEventCharacter(character)}
                          aria-label={`Supprimer ${character.name}`}
                          title="Supprimer"
                        >
                          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <polyline points="3 6 5 6 21 6" />
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                          </svg>
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <p className={styles.meta}>Aucun personnage event créé.</p>
              )}

              {eventPanelMessage && <p className={styles.meta}>{eventPanelMessage}</p>}
            </div>
          </section>
        )}
        {deleteConfirmOpen && (
          <div
            className={styles.modalOverlay}
            role="presentation"
            onClick={() => !deletingActivity && setDeleteConfirmOpen(false)}
          >
            <div
              className={styles.modalDialog}
              role="dialog"
              aria-modal="true"
              aria-label="Confirmation de suppression"
              onClick={(event) => event.stopPropagation()}
            >
              <div className={styles.modalHeader}>
                <h3 className={styles.modalTitle}>Supprimer cette activité ?</h3>
                <button
                  type="button"
                  className={styles.modalCloseButton}
                  onClick={() => setDeleteConfirmOpen(false)}
                  aria-label="Fermer la fenêtre de confirmation"
                  disabled={deletingActivity}
                >
                  ×
                </button>
              </div>
              <p className={styles.modalWarningText}>
                Vous allez supprimer définitivement {kindLabel.toLowerCase()} "{activity.title}".
              </p>
              <p className={styles.modalWarningText}>
                Cette action est irréversible (sujets liés, inscriptions et personnages event associés seront perdus).
              </p>
              <div className={styles.modalActions}>
                <button
                  className={styles.editButton}
                  type="button"
                  onClick={() => setDeleteConfirmOpen(false)}
                  disabled={deletingActivity}
                >
                  Annuler
                </button>
                <button
                  className={`${styles.editButton} ${styles.dangerButton} ${styles.dangerPrimaryButton}`}
                  type="button"
                  onClick={handleConfirmDeleteActivity}
                  disabled={deletingActivity}
                >
                  {deletingActivity ? 'Suppression...' : 'Supprimer définitivement'}
                </button>
              </div>
            </div>
          </div>
        )}
        {createCharacterOpen && (
          <div
            className={styles.modalOverlay}
            role="presentation"
            onClick={handleCloseCharacterModal}
          >
            <div
              className={styles.modalDialog}
              role="dialog"
              aria-modal="true"
              aria-label={editingEventCharacter ? 'Éditer un personnage event' : 'Créer un personnage event'}
              onClick={(event) => event.stopPropagation()}
            >
              <div className={styles.modalHeader}>
                <h3 className={styles.modalTitle}>
                  {editingEventCharacter ? 'Éditer le personnage event' : 'Créer un personnage event'}
                </h3>
                <button
                  type="button"
                  className={styles.modalCloseButton}
                  onClick={handleCloseCharacterModal}
                  aria-label="Fermer la fenêtre de création"
                >
                  ×
                </button>
              </div>
              <form className={styles.eventCharacterForm} onSubmit={handleCreateEventCharacter}>
                <input
                  className={styles.formInput}
                  type="text"
                  value={eventCharacterName}
                  onChange={(e) => setEventCharacterName(e.target.value)}
                  placeholder="Nom du personnage event"
                  required
                />
                <div className={styles.avatarPickerRow}>
                  {eventCharacterAvatar ? (
                    <img
                      className={styles.eventCharacterAvatarPreview}
                      src={eventCharacterAvatar}
                      alt="Avatar event"
                    />
                  ) : (
                    <div className={styles.eventCharacterAvatarPlaceholder}>Aucun avatar</div>
                  )}
                  <button
                    className={styles.editButton}
                    type="button"
                    onClick={() => setEventAvatarEditorOpen(true)}
                  >
                    {eventCharacterAvatar ? 'Changer l’avatar' : 'Choisir un avatar'}
                  </button>
                </div>
                <div className={styles.modalActions}>
                  <button
                    className={styles.editButton}
                    type="button"
                    onClick={handleCloseCharacterModal}
                  >
                    Annuler
                  </button>
                  <button className={styles.editButton} type="submit" disabled={eventPanelLoading}>
                    {eventPanelLoading ? 'Enregistrement...' : (editingEventCharacter ? 'Enregistrer' : 'Créer le personnage')}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}
        <AvatarEditor
          open={eventAvatarEditorOpen}
          onClose={() => setEventAvatarEditorOpen(false)}
          currentAvatar={eventCharacterAvatar || null}
          characterName={eventCharacterName || 'Personnage event'}
          onSave={handleEventAvatarSave}
          saving={eventPanelLoading}
        />
      </div>
    </Layout>
  );
};

export default RpActivityDetail;
