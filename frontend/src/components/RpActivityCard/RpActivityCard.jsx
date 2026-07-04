import React, { useMemo } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import RpActivityRegisterButton from '../RpActivityRegisterButton/RpActivityRegisterButton';
import { sanitizeHtml } from '../../utils/sanitize';
import styles from './RpActivityCard.module.css';

const formatReminderDate = (dateValue) => {
  if (!dateValue) return 'Aucune relance planifiée';
  return new Date(dateValue).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const formatPostDate = (dateValue) => {
  if (!dateValue) return '';
  return new Date(dateValue).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const RpActivityCard = ({
  activity,
  selectableCharacters = [],
  onRegistrationChanged,
  showRegistration = true,
}) => {
  const navigate = useNavigate();
  const kindLabel = activity.kind === 'mission' ? 'Mission' : 'Event';
  const statusLabel = activity.status === 'closed' ? 'Fermé' : activity.status === 'draft' ? 'Brouillon' : 'Ouvert';
  const isUnread = Boolean(activity?.isUnread);
  const firstLinkedThread = activity.linkedThreads?.[0];
  const detailLink = `/rp-activities/${activity.id}`;
  const registrationsCount = activity.registrationsCount || 0;
  const avatarLimit = 4;
  const reminderLabel = formatReminderDate(activity.reminderAt);
  const registrationsData = Array.isArray(activity.registrations) && activity.registrations.length > 0
    ? activity.registrations
    : (activity.registrationsPreview || []);
  const registrationAvatars = registrationsData
    .map((registration) => registration?.character)
    .filter((character) => Boolean(character?.avatar))
    .slice(0, avatarLimit);
  const hiddenRegistrations = Math.max(0, registrationsCount - registrationAvatars.length);
  const cardDescription = activity.description || activity.openingSpeech || '';
  const cardDescriptionHtml = useMemo(() => {
    const source = cardDescription.trim();
    if (!source) return '';
    const hasHtml = /<\/?[a-z][\s\S]*>/i.test(source);
    const normalized = hasHtml ? source : source.replace(/\n/g, '<br />');
    return sanitizeHtml(normalized);
  }, [cardDescription]);
  const lastPost = firstLinkedThread?.lastPost;
  const sidebarLink = firstLinkedThread
    ? `/threads/${firstLinkedThread.slug || firstLinkedThread.id}`
    : null;
  const handleCardBodyClick = (event) => {
    const target = event.target;
    if (target instanceof Element) {
      const interactiveParent = target.closest('a, button, input, select, textarea, [role="button"]');
      if (interactiveParent && interactiveParent !== event.currentTarget) {
        return;
      }
    }
    navigate(detailLink);
  };
  const handleCardBodyKeyDown = (event) => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      navigate(detailLink);
    }
  };

  const registrationsSummary = (
    <div className={styles.headerRegistrations}>
      <span className={styles.count}>{registrationsCount} inscrit(s)</span>
      {(registrationAvatars.length > 0 || hiddenRegistrations > 0) && (
        <div className={styles.countAvatars}>
          {registrationAvatars.map((character) => (
            <span key={character.id} className={styles.countAvatarItem}>
              <img
                src={character.avatar}
                alt={character.name || 'Inscrit'}
                className={styles.countAvatar}
              />
              <span className={styles.countTooltip}>{character.name || 'Inscrit'}</span>
            </span>
          ))}
          {hiddenRegistrations > 0 && (
            <span className={styles.countMore}>+{hiddenRegistrations}</span>
          )}
        </div>
      )}
    </div>
  );

  return (
    <article className={`${styles.card} ${isUnread ? styles.cardUnread : ''}`}>
      {activity.illustrationUrl && (
        <div className={styles.illustration}>
          <img src={activity.illustrationUrl} alt={activity.title} />
        </div>
      )}
      <div className={styles.cardContent}>
        <div
          className={styles.cardBody}
          role="button"
          tabIndex={0}
          onClick={handleCardBodyClick}
          onKeyDown={handleCardBodyKeyDown}
        >
          <header className={styles.header}>
            <div className={styles.badges}>
              <div className={styles.badgesGroup}>
                <span className={`${styles.badge} ${styles.kind} ${activity.kind === 'mission' ? styles.kindMission : styles.kindEvent}`}>
                  {kindLabel}
                </span>
                <span className={`${styles.badge} ${styles.status}`}>{statusLabel}</span>
                {isUnread && <span className={`${styles.badge} ${styles.unreadBadge}`}>Nouveau</span>}
              </div>
            </div>
            {registrationsSummary}
          </header>

          <div className={styles.contentBlock}>
            <h3 className={styles.title}>{activity.title}</h3>

            {activity.faction?.name && (
              <div className={styles.metaRow}>
                <span className={styles.metaValue}>{activity.faction.name}</span>
              </div>
            )}

            {cardDescriptionHtml && (
              <div className={styles.speech} dangerouslySetInnerHTML={{ __html: cardDescriptionHtml }} />
            )}
          </div>

          {showRegistration && (
            <RpActivityRegisterButton
              activity={activity}
              selectableCharacters={selectableCharacters}
              onRegistered={onRegistrationChanged}
              onUnregistered={onRegistrationChanged}
            />
          )}
        </div>

        {sidebarLink ? (
          <Link className={styles.cardSidebar} to={sidebarLink}>
            <div className={styles.sidebarHeader}>
              <div className={styles.sidebarRelance}>
                <span className={styles.sidebarRelancePrefix}>Relance :</span>
                <span className={styles.sidebarReminder}>{reminderLabel}</span>
              </div>
              <div className={styles.sidebarLabel}>Dernier message</div>
            </div>
            <div className={styles.sidebarThreadTitle}>
              {lastPost?.threadTitle || firstLinkedThread.title || 'Sujet principal'}
            </div>
            {lastPost?.avatar ? (
              <img src={lastPost.avatar} alt="" className={styles.sidebarAvatar} />
            ) : (
              <div className={styles.sidebarAvatarPlaceholder}>RP</div>
            )}
            <div className={styles.sidebarAuthor}>
              {lastPost?.character || lastPost?.author || 'Aucun message'}
            </div>
            <div className={styles.sidebarDate}>
              {lastPost?.date ? formatPostDate(lastPost.date) : 'Pas encore de réponse'}
            </div>
          </Link>
        ) : (
          <div className={styles.cardSidebar}>
            <div className={styles.sidebarHeader}>
              <div className={styles.sidebarRelance}>
                <span className={styles.sidebarRelancePrefix}>Relance :</span>
                <span className={styles.sidebarReminder}>{reminderLabel}</span>
              </div>
              <div className={styles.sidebarLabel}>Activité</div>
            </div>
            <div className={styles.sidebarThreadTitle}>Sujet principal</div>
            <div className={styles.sidebarAvatarPlaceholder}>RP</div>
            <div className={styles.sidebarAuthor}>Aucun sujet lié</div>
          </div>
        )}
      </div>
    </article>
  );
};

export default RpActivityCard;
