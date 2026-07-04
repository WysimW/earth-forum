import React, { useMemo, useState } from 'react';
import { useAuth } from '../../contexts/AuthContext';
import rpActivityService from '../../services/rpActivityService';
import styles from './RpActivityRegisterButton.module.css';

const RpActivityRegisterButton = ({
  activity,
  selectableCharacters = [],
  onRegistered,
  onUnregistered,
}) => {
  const { isAuthenticated } = useAuth();
  const [selectedCharacterId, setSelectedCharacterId] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [actionLoadingId, setActionLoadingId] = useState(null);

  const approvedCharacterIds = useMemo(
    () => new Set(activity?.userRegistration?.characterIds || []),
    [activity?.userRegistration?.characterIds]
  );
  const pendingCharacterIds = useMemo(
    () => new Set(activity?.userRegistration?.pendingCharacterIds || []),
    [activity?.userRegistration?.pendingCharacterIds]
  );
  const isRegistrationOpen = useMemo(() => {
    const statusOpen = activity?.status === 'open';
    if (!statusOpen) return false;
    if (!activity?.registrationEndAt) return true;
    return new Date(activity.registrationEndAt).getTime() >= Date.now();
  }, [activity?.status, activity?.registrationEndAt]);

  const registerableCharacters = useMemo(
    () => selectableCharacters.filter(
      (character) => character.entityType !== 'npc'
        && character.kind !== 'event'
        && !approvedCharacterIds.has(character.id)
        && !pendingCharacterIds.has(character.id)
    ),
    [selectableCharacters, approvedCharacterIds, pendingCharacterIds]
  );
  const registrationsData = useMemo(() => {
    if (Array.isArray(activity?.registrations) && activity.registrations.length > 0) {
      return activity.registrations;
    }
    return Array.isArray(activity?.registrationsPreview) ? activity.registrationsPreview : [];
  }, [activity?.registrations, activity?.registrationsPreview]);

  const handleRegister = async () => {
    if (!selectedCharacterId || !activity?.id) return;
    setLoading(true);
    setError('');
    try {
      await rpActivityService.registerCharacter(activity.id, Number(selectedCharacterId));
      setSelectedCharacterId('');
      onRegistered?.();
    } catch (err) {
      setError(err.response?.data?.error || 'Inscription impossible');
    } finally {
      setLoading(false);
    }
  };

  const handleUnregister = async (characterId) => {
    if (!activity?.id) return;
    setActionLoadingId(characterId);
    setLoading(true);
    setError('');
    try {
      await rpActivityService.unregisterCharacter(activity.id, Number(characterId));
      onUnregistered?.();
    } catch (err) {
      setError(err.response?.data?.error || 'Désinscription impossible');
    } finally {
      setLoading(false);
      setActionLoadingId(null);
    }
  };

  const handleApprove = async (registrationId) => {
    if (!activity?.id || !registrationId) return;
    setActionLoadingId(registrationId);
    setError('');
    try {
      await rpActivityService.approveRegistration(activity.id, Number(registrationId));
      onRegistered?.();
    } catch (err) {
      setError(err.response?.data?.error || 'Validation impossible');
    } finally {
      setActionLoadingId(null);
    }
  };

  if (!isAuthenticated) {
    return <p className={styles.authHint}>Connecte-toi pour t’inscrire avec un personnage.</p>;
  }

  const groupedRegistrations = useMemo(() => {
    const approved = [];
    const pending = [];
    registrationsData.forEach((registration) => {
      if (registration?.status === 'pending') {
        pending.push(registration);
      } else {
        approved.push(registration);
      }
    });
    return { approved, pending };
  }, [registrationsData]);

  return (
    <div className={styles.wrapper}>
      {groupedRegistrations.approved.length > 0 && (
        <div className={styles.registeredBlock}>
          <p className={styles.label}>Inscriptions validées</p>
          <div className={styles.registeredList}>
            {groupedRegistrations.approved.map((registration) => {
              const character = registration?.character;
              const characterId = Number(character?.id || 0);
              const characterName = character?.name || 'Personnage';
              const isCurrentUserCharacter = approvedCharacterIds.has(characterId);

              return (
                <div
                  key={registration?.id || `${characterId}-${characterName}`}
                  className={styles.registeredItem}
                >
                  <span className={styles.registeredChipMain}>
                    {character?.avatar ? (
                      <img
                        src={character.avatar}
                        alt=""
                        className={styles.registeredAvatar}
                      />
                    ) : (
                      <span className={styles.registeredAvatarFallback} aria-hidden="true">
                        {characterName.charAt(0).toUpperCase()}
                      </span>
                    )}
                    <span className={styles.registeredName}>{characterName}</span>
                  </span>
                  {isCurrentUserCharacter ? (
                    <button
                      type="button"
                      className={styles.unregisterButton}
                      onClick={() => handleUnregister(characterId)}
                      disabled={loading || actionLoadingId === characterId}
                      aria-label={`Retirer ${characterName}`}
                    >
                      <span className={styles.unregisterIcon} aria-hidden="true">×</span>
                    </button>
                  ) : (
                    <span className={styles.registeredRole}>Validé</span>
                  )}
                </div>
              );
            })}
          </div>
        </div>
      )}

      {groupedRegistrations.pending.length > 0 && (
        <div className={styles.registeredBlock}>
          <p className={styles.label}>Inscriptions en attente</p>
          <div className={styles.registeredList}>
            {groupedRegistrations.pending.map((registration) => {
              const character = registration?.character;
              const characterId = Number(character?.id || 0);
              const characterName = character?.name || 'Personnage';
              const isCurrentUserCharacter = pendingCharacterIds.has(characterId);
              const canManage = Boolean(activity?.permissions?.canManageRegistrations);
              const registrationId = Number(registration?.id || 0);

              return (
                <div
                  key={registration?.id || `${characterId}-${characterName}`}
                  className={styles.registeredItem}
                >
                  <span className={styles.registeredChipMain}>
                    {character?.avatar ? (
                      <img
                        src={character.avatar}
                        alt=""
                        className={styles.registeredAvatar}
                      />
                    ) : (
                      <span className={styles.registeredAvatarFallback} aria-hidden="true">
                        {characterName.charAt(0).toUpperCase()}
                      </span>
                    )}
                    <span className={styles.registeredName}>{characterName}</span>
                  </span>
                  <div className={styles.pendingActions}>
                    {canManage && (
                      <button
                        type="button"
                        className={`${styles.approveButton} ${styles.iconOnlyButton}`}
                        onClick={() => handleApprove(registrationId)}
                        disabled={actionLoadingId === registrationId}
                        aria-label="Valider l'inscription"
                        title="Valider"
                      >
                        {actionLoadingId === registrationId ? '...' : (
                          <>
                            <svg className={styles.actionIcon} width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                              <polyline points="20 6 9 17 4 12" />
                            </svg>
                          </>
                        )}
                      </button>
                    )}
                    {isCurrentUserCharacter && (
                      <button
                        type="button"
                        className={styles.unregisterButton}
                        onClick={() => handleUnregister(characterId)}
                        disabled={loading || actionLoadingId === characterId}
                        aria-label={`Annuler la demande de ${characterName}`}
                      >
                        <span className={styles.unregisterIcon} aria-hidden="true">×</span>
                      </button>
                    )}
                    {!canManage && !isCurrentUserCharacter && (
                      <span className={styles.registeredRole}>En attente</span>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {!isRegistrationOpen && (
        <p className={styles.authHint}>Les inscriptions sont actuellement fermées.</p>
      )}

      {registerableCharacters.length > 0 && isRegistrationOpen && (
        <div className={styles.registerBlock}>
          <select
            className={styles.select}
            value={selectedCharacterId}
            onChange={(event) => setSelectedCharacterId(event.target.value)}
            disabled={loading}
          >
            <option value="">Choisir un personnage</option>
            {registerableCharacters.map((character) => (
              <option key={character.id} value={character.id}>
                {character.name}
              </option>
            ))}
          </select>
          <button
            type="button"
            className={styles.button}
            onClick={handleRegister}
            disabled={loading || !selectedCharacterId}
          >
            {loading ? '...' : 'Soumettre'}
          </button>
        </div>
      )}

      {error && <p className={styles.error}>{error}</p>}
    </div>
  );
};

export default RpActivityRegisterButton;
