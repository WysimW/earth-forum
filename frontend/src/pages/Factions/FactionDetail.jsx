import React, { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import Layout from '../../components/Layout/Layout';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import Breadcrumb from '../../components/Breadcrumb/Breadcrumb';
import CharacterCard from '../../components/CharacterCard/CharacterCard';
import { useAuth } from '../../contexts/AuthContext';
import factionService from '../../services/factionService';
import { npcApi } from '../../services/characterApi';
import { getCharacterSheetPath } from '../../utils/characterPaths';
import api from '../../services/api';
import styles from './Factions.module.css';

const flattenNpcs = (data) => {
  if (!data) return [];
  const items = [];
  (data.contentByUniverse || []).forEach((universeData) => {
    items.push(...(universeData.mainContent?.npcs || []));
    Object.values(universeData.elseworlds || {}).forEach((elseworld) => {
      items.push(...(elseworld.npcs || []));
    });
  });
  items.push(...(data.contentWithoutUniverse?.npcs || []));
  return items;
};

const FactionDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const [faction, setFaction] = useState(null);
  const [characters, setCharacters] = useState([]);
  const [myApplicableCharacters, setMyApplicableCharacters] = useState([]);
  const [npcs, setNpcs] = useState([]);
  const [selectedCharacter, setSelectedCharacter] = useState('');
  const [addCharacterSearch, setAddCharacterSearch] = useState('');
  const [selectedApplicantCharacter, setSelectedApplicantCharacter] = useState('');
  const [applicantSearch, setApplicantSearch] = useState('');
  const [applications, setApplications] = useState([]);
  const [selectedNpc, setSelectedNpc] = useState('');
  const [newNpcName, setNewNpcName] = useState('');
  const [newNpcOccupation, setNewNpcOccupation] = useState('');
  const [newNpcAvatarUrl, setNewNpcAvatarUrl] = useState('');
  const [uploadingNpcAvatar, setUploadingNpcAvatar] = useState(false);
  const createNpcAvatarInputRef = useRef(null);
  const [createNpcModalOpen, setCreateNpcModalOpen] = useState(false);
  const [characterSearch, setCharacterSearch] = useState('');
  const [npcSearch, setNpcSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [actionLoading, setActionLoading] = useState(false);
  const [feedback, setFeedback] = useState('');
  const [editingRoleFor, setEditingRoleFor] = useState(null);
  const [roleDraft, setRoleDraft] = useState('');

  const loadData = async () => {
    try {
      setLoading(true);
      setError(null);
      const factionData = await factionService.getFaction(id);
      const [npcsData, myApplicableCharactersData, charactersData, applicationsData] = await Promise.all([
        npcApi.getNpcs(),
        factionService.getMyApplicableCharacters(id),
        factionData.canEdit ? factionService.getAvailableCharacters(id) : Promise.resolve({ characters: [] }),
        factionData.canEdit ? factionService.getApplications(id) : Promise.resolve({ applications: [] }),
      ]);
      setFaction(factionData);
      setCharacters(charactersData?.characters || []);
      setMyApplicableCharacters(myApplicableCharactersData?.characters || []);
      setNpcs(flattenNpcs(npcsData));
      setApplications(applicationsData?.applications || factionData.applications || []);
    } catch (err) {
      setError('Erreur lors du chargement de la faction');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [id]);

  useEffect(() => {
    if (!createNpcModalOpen) {
      return undefined;
    }
    const onKey = (e) => {
      if (e.key === 'Escape') {
        setCreateNpcModalOpen(false);
      }
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [createNpcModalOpen]);

  const openCreateNpcModal = () => {
    setNewNpcName('');
    setNewNpcOccupation('');
    setNewNpcAvatarUrl('');
    setCreateNpcModalOpen(true);
  };

  const handleNpcAvatarFile = async (file) => {
    if (!file || !file.type.startsWith('image/')) {
      return;
    }
    setUploadingNpcAvatar(true);
    setFeedback('');
    const formData = new FormData();
    formData.append('file', file);
    try {
      const response = await api.post('/api/media/upload', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      if (response.data?.url) {
        setNewNpcAvatarUrl(response.data.url);
      }
    } catch (err) {
      setFeedback(err.response?.data?.error || 'Erreur lors de l’upload de l’image.');
    } finally {
      setUploadingNpcAvatar(false);
      if (createNpcAvatarInputRef.current) {
        createNpcAvatarInputRef.current.value = '';
      }
    }
  };

  useEffect(() => {
    if (loading) return;
    if (window.location.hash !== '#postuler') return;
    const applySection = document.getElementById('faction-apply-section');
    if (applySection) {
      applySection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }, [loading]);

  const availableCharacters = useMemo(() => {
    const existingIds = new Set((faction?.characters || []).map((character) => character.id));
    const factionUniverseId = faction?.universe?.id ?? null;
    return characters
      .filter((character) => {
        if (!factionUniverseId) return true;
        return character.universe?.id === factionUniverseId;
      })
      .filter((character) => !existingIds.has(character.id))
      .filter((character) => character.name.toLowerCase().includes(addCharacterSearch.toLowerCase()));
  }, [characters, faction, addCharacterSearch]);

  const availableNpcs = useMemo(() => {
    const existingIds = new Set((faction?.npcs || []).map((npc) => npc.id));
    return npcs
      .filter((npc) => !existingIds.has(npc.id))
      .filter((npc) => npc.name.toLowerCase().includes(npcSearch.toLowerCase()));
  }, [npcs, faction, npcSearch]);

  const applicableCharacters = useMemo(() => {
    const factionUId = faction?.universe?.id ?? null;
    return (myApplicableCharacters || [])
      .filter((character) => character.kind !== 'event')
      .filter((character) => {
        if (factionUId == null) return true;
        const uid = character.universe?.id;
        return uid != null && Number(uid) === Number(factionUId);
      })
      .filter((character) =>
        character.name.toLowerCase().includes(applicantSearch.toLowerCase())
      );
  }, [myApplicableCharacters, applicantSearch, faction?.universe?.id]);

  const filteredFactionCharacters = useMemo(() => {
    return (faction?.characters || []).filter((character) =>
      character.name.toLowerCase().includes(characterSearch.toLowerCase())
    );
  }, [faction, characterSearch]);

  const filteredFactionNpcs = useMemo(() => {
    return (faction?.npcs || []).filter((npc) =>
      npc.name.toLowerCase().includes(npcSearch.toLowerCase())
    );
  }, [faction, npcSearch]);

  const getStatusLabel = (status) => {
    if (status === 'open') return 'Ouverte';
    if (status === 'closed') return 'Fermée';
    return status || 'Inconnu';
  };

  const canEditCharacterSheet = (character) => {
    const roles = user?.roles || [];
    const isModerator = roles.includes('ROLE_MODERATOR') || roles.includes('ROLE_ADMIN');
    const isOwner = character?.user?.id === user?.id;
    return isOwner || isModerator;
  };

  const handleAddCharacter = async () => {
    if (!selectedCharacter) return;
    try {
      setActionLoading(true);
      await factionService.addCharacterMember(id, selectedCharacter);
      setSelectedCharacter('');
      setAddCharacterSearch('');
      setFeedback('Personnage ajouté avec succès.');
      await loadData();
    } catch (err) {
      setFeedback(err.response?.data?.error || 'Erreur lors de l’ajout du personnage.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleAddNpc = async () => {
    if (!selectedNpc) return;
    try {
      setActionLoading(true);
      await factionService.addNpcMember(id, selectedNpc);
      setSelectedNpc('');
      setFeedback('PNJ ajouté avec succès.');
      await loadData();
    } catch (err) {
      setFeedback(err.response?.data?.error || 'Erreur lors de l’ajout du PNJ.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleCreateAndAddNpc = async () => {
    const name = newNpcName.trim();
    if (!name) {
      setFeedback('Indique un nom pour le PNJ.');
      return;
    }
    try {
      setActionLoading(true);
      setFeedback('');
      const payload = { name };
      const occ = newNpcOccupation.trim();
      if (occ) {
        payload.occupation = occ;
      }
      const avatar = newNpcAvatarUrl.trim();
      if (avatar) {
        payload.avatar = avatar;
      }
      const created = await factionService.createFactionNpc(id, payload);
      const npcId = created?.id;
      if (!npcId) {
        setFeedback('Réponse serveur inattendue lors de la création du PNJ.');
        return;
      }
      setNewNpcName('');
      setNewNpcOccupation('');
      setNewNpcAvatarUrl('');
      setCreateNpcModalOpen(false);
      setFeedback('PNJ de faction créé et validé (utilisable en mission RP). Il n’apparaît pas dans « Mes PNJ ».');
      await loadData();
    } catch (err) {
      setFeedback(err.response?.data?.error || 'Erreur lors de la création ou de l’ajout du PNJ.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleApplyToFaction = async () => {
    if (!selectedApplicantCharacter) return;
    try {
      setActionLoading(true);
      await factionService.applyToFaction(id, selectedApplicantCharacter);
      setSelectedApplicantCharacter('');
      setApplicantSearch('');
      setFeedback('Candidature envoyée avec succès.');
      await loadData();
    } catch (err) {
      setFeedback(err.response?.data?.error || 'Erreur lors de l’envoi de la candidature.');
    } finally {
      setActionLoading(false);
    }
  };

  const showApplySection = !faction?.canEdit || applicableCharacters.length > 0;

  const handleRemoveCharacter = async (characterId) => {
    try {
      setActionLoading(true);
      await factionService.removeCharacterMember(id, characterId);
      setFeedback('Personnage retiré de la faction.');
      await loadData();
    } catch (err) {
      setFeedback(err.response?.data?.error || 'Erreur lors du retrait du personnage.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleRemoveNpc = async (npcId) => {
    try {
      setActionLoading(true);
      await factionService.removeNpcMember(id, npcId);
      setFeedback('PNJ retiré de la faction.');
      await loadData();
    } catch (err) {
      setFeedback(err.response?.data?.error || 'Erreur lors du retrait du PNJ.');
    } finally {
      setActionLoading(false);
    }
  };

  const startRoleEdition = (characterId, currentRole) => {
    if (!faction?.canEdit) return;
    setEditingRoleFor(characterId);
    setRoleDraft(currentRole || '');
  };

  const cancelRoleEdition = () => {
    setEditingRoleFor(null);
    setRoleDraft('');
  };

  const saveRoleEdition = async (characterId) => {
    const nextRole = roleDraft;
    try {
      setActionLoading(true);
      await factionService.updateCharacterRole(id, characterId, nextRole);
      setFaction((prevFaction) => {
        if (!prevFaction) return prevFaction;
        return {
          ...prevFaction,
          characters: (prevFaction.characters || []).map((member) =>
            member.id === characterId ? { ...member, roleRp: nextRole } : member
          ),
        };
      });
      setFeedback('Rôle RP mis à jour.');
      setEditingRoleFor(null);
      setRoleDraft('');
    } catch (err) {
      setFeedback(err.response?.data?.error || 'Erreur lors de la mise à jour du rôle RP.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleAcceptApplication = async (applicationId) => {
    try {
      setActionLoading(true);
      await factionService.acceptApplication(id, applicationId);
      setFeedback('Candidature acceptée.');
      await loadData();
    } catch (err) {
      setFeedback(err.response?.data?.error || 'Erreur lors de l’acceptation de la candidature.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleRejectApplication = async (applicationId) => {
    try {
      setActionLoading(true);
      await factionService.rejectApplication(id, applicationId);
      setFeedback('Candidature refusée.');
      await loadData();
    } catch (err) {
      setFeedback(err.response?.data?.error || 'Erreur lors du refus de la candidature.');
    } finally {
      setActionLoading(false);
    }
  };

  if (loading) {
    return (
      <Layout>
        <Loading message="Chargement de la faction..." />
      </Layout>
    );
  }

  if (error || !faction) {
    return (
      <Layout>
        <ErrorMessage message={error || 'Faction introuvable'} onRetry={loadData} />
      </Layout>
    );
  }

  return (
    <Layout>
      <div className={styles.content}>
        <Breadcrumb items={[{ name: 'Accueil', url: '/', icon: 'home' }, { name: 'Factions', url: '/factions' }, { name: faction.name, url: null }]} />

        <section className={styles.detailHero}>
          <div className={styles.detailVisual}>
            {faction.logo ? (
              <img src={faction.logo} alt={faction.name} className={styles.detailLogo} />
            ) : (
              <div className={styles.detailLogoPlaceholder}>{faction.name.slice(0, 2).toUpperCase()}</div>
            )}
          </div>

          <div className={styles.detailMain}>
            <div className={styles.detailHeader}>
              <h1>{faction.name}</h1>
              <span className={`${styles.detailStatus} ${styles[faction.status]}`}>{getStatusLabel(faction.status)}</span>
            </div>
            <p className={styles.detailSubtitle}>
              Univers: <strong>{faction.universe?.name || 'N/A'}</strong> - Fondateur: <strong>{faction.founder?.pseudo || 'N/A'}</strong>
            </p>
            <div className={styles.detailTags}>
              <span className={styles.detailTag}>Alignement: {faction.alignment || 'Non défini'}</span>
              <span className={styles.detailTag}>Portée: {faction.scope || 'Non définie'}</span>
              <span className={styles.detailTag}>Slug: {faction.slug}</span>
            </div>
            <div className={styles.detailActions}>
              <button className={styles.secondaryBtn} onClick={() => navigate('/factions')}>Retour</button>
              {faction.canEdit && (
                <button className={styles.primaryBtn} onClick={() => navigate(`/factions/${id}/edit`)}>
                  Modifier la faction
                </button>
              )}
              {showApplySection && (
                <button
                  className={styles.primaryBtn}
                  onClick={() => {
                    const applySection = document.getElementById('faction-apply-section');
                    if (applySection) {
                      applySection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                  }}
                >
                  Postuler
                </button>
              )}
            </div>
          </div>

          {faction.icon && (
            <div className={styles.detailIconWrap}>
              <img src={faction.icon} alt={`Icône ${faction.name}`} className={styles.detailIcon} />
            </div>
          )}
        </section>

        {feedback && <p className={styles.detailFeedback}>{feedback}</p>}

        <section className={styles.detailStats}>
          <article className={styles.statCard}>
            <h3>Personnages</h3>
            <p>{faction.membersCount?.characters || 0}</p>
          </article>
          <article className={styles.statCard}>
            <h3>PNJ</h3>
            <p>{faction.membersCount?.npcs || 0}</p>
          </article>
          <article className={styles.statCard}>
            <h3>Total membres</h3>
            <p>{(faction.membersCount?.characters || 0) + (faction.membersCount?.npcs || 0)}</p>
          </article>
        </section>

        <section className={styles.detailPanels}>
          <article className={styles.detailPanel}>
            <h2>Description</h2>
            <p>{faction.description || 'Aucune description pour le moment.'}</p>
          </article>
          <article className={styles.detailPanel}>
            <h2>Objectifs</h2>
            <p>{faction.objectives || 'Aucun objectif renseigné.'}</p>
          </article>
          <article className={styles.detailPanel}>
            <h2>Quartier général</h2>
            <p>{faction.headquartersDescription || 'Aucune information sur le QG.'}</p>
          </article>
        </section>

        <section className={styles.memberSection}>
          <div className={styles.memberHeader}>
            <h2>Membres personnages</h2>
            <input
              className={styles.input}
              value={characterSearch}
              onChange={(event) => setCharacterSearch(event.target.value)}
              placeholder="Rechercher un personnage..."
            />
          </div>
          {faction.canEdit && (
            <div className={styles.inlineForm}>
              <input
                className={styles.input}
                value={addCharacterSearch}
                onChange={(event) => setAddCharacterSearch(event.target.value)}
                placeholder="Rechercher dans la selection..."
              />
              <select value={selectedCharacter} onChange={(e) => setSelectedCharacter(e.target.value)} className={styles.input}>
                <option value="">Sélectionner un personnage</option>
                {availableCharacters.map((character) => (
                  <option key={character.id} value={character.id}>{character.name}</option>
                ))}
              </select>
              <button className={styles.primaryBtn} onClick={handleAddCharacter} disabled={actionLoading}>
                Ajouter
              </button>
            </div>
          )}
          {filteredFactionCharacters.length === 0 ? (
            <p className={styles.memberMeta}>Aucun personnage membre ne correspond à la recherche.</p>
          ) : (
            <div className={styles.memberCardsGrid}>
              {filteredFactionCharacters.map((character) => (
                <div key={character.id} className={styles.memberCardWrapper}>
                  <CharacterCard
                    character={character}
                    viewPath={getCharacterSheetPath(character)}
                    editPath={canEditCharacterSheet(character) ? `/characters/${character.id}/edit` : undefined}
                    onDelete={faction.canEdit ? handleRemoveCharacter : undefined}
                    showUniverse={false}
                    showRoleRp
                    roleRp={character.roleRp}
                    roleRpPlaceholder={faction.canEdit ? 'Cliquer pour definir' : 'Non defini'}
                    onRoleRpClick={
                      faction.canEdit && editingRoleFor !== character.id
                        ? () => startRoleEdition(character.id, character.roleRp)
                        : undefined
                    }
                    roleRpEditor={
                      editingRoleFor === character.id && faction.canEdit ? (
                        <div className={styles.roleEditor} onClick={(event) => event.stopPropagation()}>
                          <input
                            className={styles.roleInput}
                            value={roleDraft}
                            onChange={(event) => setRoleDraft(event.target.value)}
                            placeholder="Ex: bras droit, eclaireur..."
                          />
                          <button
                            type="button"
                            className={styles.roleAction}
                            onClick={() => saveRoleEdition(character.id)}
                            disabled={actionLoading}
                          >
                            OK
                          </button>
                          <button type="button" className={styles.roleActionMuted} onClick={cancelRoleEdition}>
                            Annuler
                          </button>
                        </div>
                      ) : null
                    }
                  />
                </div>
              ))}
            </div>
          )}
        </section>

        {(faction.canEdit || showApplySection) && (
          <section className={styles.memberSection} id="faction-apply-section">
            <div className={styles.memberHeader}>
              <h2>Candidatures</h2>
            </div>

            {faction.canEdit && applications.length > 0 && (
              <div className={styles.candidatureBlock}>
                <h3 className={styles.candidatureSubTitle}>Demandes en attente</h3>
                <ul className={styles.list}>
                  {applications.map((application) => (
                    <li key={application.id} className={styles.listItem}>
                      <div>
                        <strong>{application.character?.name || 'Personnage inconnu'}</strong>
                        {application.character?.user?.pseudo && (
                          <p className={styles.memberMeta}>Joueur : {application.character.user.pseudo}</p>
                        )}
                      </div>
                      <div className={styles.detailActions}>
                        <button
                          type="button"
                          className={styles.primaryBtn}
                          onClick={() => handleAcceptApplication(application.id)}
                          disabled={actionLoading}
                        >
                          Accepter
                        </button>
                        <button
                          type="button"
                          className={styles.dangerBtn}
                          onClick={() => handleRejectApplication(application.id)}
                          disabled={actionLoading}
                        >
                          Refuser
                        </button>
                      </div>
                    </li>
                  ))}
                </ul>
              </div>
            )}

            {faction.canEdit && applications.length > 0 && showApplySection && <hr className={styles.candidatureDivider} aria-hidden="true" />}

            {showApplySection && (
              <div className={styles.candidatureBlock}>
                {faction.canEdit && <h3 className={styles.candidatureSubTitle}>Envoyer une candidature</h3>}
                {faction.status !== 'open' && (
                  <p className={styles.memberMeta}>Les candidatures sont fermées pour cette faction.</p>
                )}
                <div className={styles.inlineForm}>
                  <input
                    className={styles.input}
                    value={applicantSearch}
                    onChange={(event) => setApplicantSearch(event.target.value)}
                    placeholder="Rechercher mon personnage..."
                    disabled={faction.status !== 'open'}
                  />
                  <select
                    value={selectedApplicantCharacter}
                    onChange={(event) => setSelectedApplicantCharacter(event.target.value)}
                    className={styles.input}
                    disabled={faction.status !== 'open'}
                  >
                    <option value="">Sélectionner un personnage</option>
                    {applicableCharacters.map((character) => (
                      <option key={character.id} value={character.id}>
                        {character.name}
                      </option>
                    ))}
                  </select>
                  <button
                    type="button"
                    className={styles.primaryBtn}
                    onClick={handleApplyToFaction}
                    disabled={actionLoading || faction.status !== 'open' || applicableCharacters.length === 0}
                  >
                    Postuler
                  </button>
                </div>
              </div>
            )}
          </section>
        )}

        <section className={styles.memberSection}>
          <div className={styles.memberHeader}>
            <h2>Membres PNJ</h2>
            <input
              className={styles.input}
              value={npcSearch}
              onChange={(event) => setNpcSearch(event.target.value)}
              placeholder="Rechercher un PNJ..."
            />
          </div>
          {faction.canEdit && (
            <>
              <p className={styles.memberMeta} style={{ marginBottom: '0.5rem' }}>
                Crée un PNJ de faction (dédié à cette fiche, absent de « Mes PNJ ») ou ajoute un PNJ déjà présent dans ton compte.
              </p>
              <div className={`${styles.inlineForm} ${styles.npcInlineForm}`}>
                <button
                  type="button"
                  className={styles.secondaryBtn}
                  onClick={openCreateNpcModal}
                  disabled={actionLoading}
                >
                  Créer un PNJ
                </button>
                <select value={selectedNpc} onChange={(e) => setSelectedNpc(e.target.value)} className={styles.input}>
                  <option value="">Sélectionner un PNJ</option>
                  {availableNpcs.map((npc) => (
                    <option key={npc.id} value={npc.id}>{npc.name}</option>
                  ))}
                </select>
                <button type="button" className={styles.primaryBtn} onClick={handleAddNpc} disabled={actionLoading}>
                  Ajouter
                </button>
              </div>
            </>
          )}
          <ul className={styles.list}>
            {filteredFactionNpcs.map((npc) => (
              <li key={npc.id} className={styles.listItem}>
                <div>
                  <strong>{npc.name}</strong>
                  {npc.user?.pseudo && <p className={styles.memberMeta}>Joueur: {npc.user.pseudo}</p>}
                </div>
                {faction.canEdit && (
                  <button className={styles.dangerBtn} onClick={() => handleRemoveNpc(npc.id)} disabled={actionLoading}>
                    Retirer
                  </button>
                )}
              </li>
            ))}
          </ul>
        </section>
      </div>

      {faction?.canEdit && createNpcModalOpen && (
        <div
          className={styles.modalOverlay}
          onClick={() => !actionLoading && setCreateNpcModalOpen(false)}
          role="presentation"
        >
          <div
            className={styles.modal}
            role="dialog"
            aria-modal="true"
            aria-labelledby="faction-create-npc-title"
            onClick={(e) => e.stopPropagation()}
          >
            <div className={styles.modalHeader}>
              <h2 id="faction-create-npc-title" className={styles.modalTitle}>
                Créer un PNJ pour cette faction
              </h2>
              <button
                type="button"
                className={styles.modalClose}
                onClick={() => !actionLoading && setCreateNpcModalOpen(false)}
                aria-label="Fermer"
              >
                ×
              </button>
            </div>
            <div className={styles.modalBody}>
              <p className={styles.memberMeta}>
                Ce PNJ est rattaché à l’univers de la faction lorsqu’il est défini, enregistré comme PNJ de faction
                (il n’apparaît pas dans « Mes PNJ ») et ajouté aux membres PNJ de cette fiche.
              </p>
              <div className={styles.createNpcModalFields}>
                <label className={styles.modalField}>
                  <span className={styles.modalLabel}>Nom</span>
                  <input
                    className={styles.input}
                    type="text"
                    value={newNpcName}
                    onChange={(e) => setNewNpcName(e.target.value)}
                    placeholder="Nom du PNJ *"
                    disabled={actionLoading}
                    autoComplete="off"
                  />
                </label>
                <label className={styles.modalField}>
                  <span className={styles.modalLabel}>Rôle / fonction</span>
                  <input
                    className={styles.input}
                    type="text"
                    value={newNpcOccupation}
                    onChange={(e) => setNewNpcOccupation(e.target.value)}
                    placeholder="Optionnel"
                    disabled={actionLoading}
                    autoComplete="off"
                  />
                </label>
                <div className={styles.modalField}>
                  <span className={styles.modalLabel}>Portrait</span>
                  <div className={styles.npcAvatarUploadRow}>
                    <input
                      ref={createNpcAvatarInputRef}
                      type="file"
                      accept="image/*"
                      className={styles.npcAvatarFileInput}
                      disabled={actionLoading || uploadingNpcAvatar}
                      onChange={(e) => {
                        const f = e.target.files?.[0];
                        if (f) void handleNpcAvatarFile(f);
                      }}
                    />
                    <button
                      type="button"
                      className={styles.secondaryBtn}
                      disabled={actionLoading || uploadingNpcAvatar}
                      onClick={() => createNpcAvatarInputRef.current?.click()}
                    >
                      {uploadingNpcAvatar ? 'Upload…' : 'Choisir une image'}
                    </button>
                    {newNpcAvatarUrl && (
                      <button
                        type="button"
                        className={styles.modalBtnCancel}
                        disabled={actionLoading || uploadingNpcAvatar}
                        onClick={() => setNewNpcAvatarUrl('')}
                      >
                        Retirer
                      </button>
                    )}
                  </div>
                  {newNpcAvatarUrl && (
                    <div className={styles.npcAvatarPreview}>
                      <img src={newNpcAvatarUrl} alt="" />
                    </div>
                  )}
                </div>
              </div>
            </div>
            <div className={styles.modalFooter}>
              <button
                type="button"
                className={styles.modalBtnCancel}
                onClick={() => setCreateNpcModalOpen(false)}
                disabled={actionLoading}
              >
                Annuler
              </button>
              <button
                type="button"
                className={styles.modalBtnConfirm}
                onClick={handleCreateAndAddNpc}
                disabled={actionLoading || uploadingNpcAvatar || !newNpcName.trim()}
              >
                {actionLoading ? 'Création...' : 'Créer et ajouter'}
              </button>
            </div>
          </div>
        </div>
      )}
    </Layout>
  );
};

export default FactionDetail;

