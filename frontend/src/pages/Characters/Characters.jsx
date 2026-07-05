import React, { useState, useEffect, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import { useUniverseTheme } from '../../contexts/UniverseThemeContext';
import Layout from '../../components/Layout/Layout';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import Breadcrumb from '../../components/Breadcrumb/Breadcrumb';
import CharacterCard from '../../components/CharacterCard/CharacterCard';
import AvatarEditor from '../../components/AvatarEditor/AvatarEditor';
import { characterApi, npcApi } from '../../services/characterApi';
import { getCharacterSheetPath } from '../../utils/characterPaths';
import styles from './Characters.module.css';

const Characters = () => {
  const { currentUniverse } = useUniverseTheme();
  const [charactersData, setCharactersData] = useState(null);
  const [npcsData, setNpcsData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [avatarEditorOpen, setAvatarEditorOpen] = useState(false);
  const [editingCharacter, setEditingCharacter] = useState(null);
  const [isNpc, setIsNpc] = useState(false);
  const [savingAvatar, setSavingAvatar] = useState(false);
  const navigate = useNavigate();

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    try {
      setLoading(true);
      const [charactersResponse, npcsResponse] = await Promise.all([
        characterApi.getCharacters(),
        npcApi.getNpcs(),
      ]);
      setCharactersData(charactersResponse);
      setNpcsData(npcsResponse);
    } catch (err) {
      setError('Erreur lors du chargement des personnages');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  // Filtrer les données selon l'univers actuel
  const filteredCharactersData = useMemo(() => {
    if (!charactersData) return null;
    
    // Si portal, afficher tout
    if (currentUniverse === 'portal') {
      return charactersData;
    }

    // Filtrer par univers
    const filteredByUniverse = charactersData.contentByUniverse?.filter(
      (universeData) => universeData.universe.slug === currentUniverse
    ) || [];

    return {
      contentByUniverse: filteredByUniverse,
      contentWithoutUniverse: currentUniverse === 'portal' 
        ? charactersData.contentWithoutUniverse 
        : { characters: [], total: 0 }
    };
  }, [charactersData, currentUniverse]);

  const filteredNpcsData = useMemo(() => {
    if (!npcsData) return null;
    
    // Si portal, afficher tout
    if (currentUniverse === 'portal') {
      return npcsData;
    }

    // Filtrer par univers
    const filteredByUniverse = npcsData.contentByUniverse?.filter(
      (universeData) => universeData.universe.slug === currentUniverse
    ) || [];

    return {
      contentByUniverse: filteredByUniverse,
      contentWithoutUniverse: currentUniverse === 'portal' 
        ? npcsData.contentWithoutUniverse 
        : { npcs: [], total: 0 }
    };
  }, [npcsData, currentUniverse]);

  const charactersCount = useMemo(() => {
    const byUniverse = filteredCharactersData?.contentByUniverse?.reduce((sum, u) => sum + u.total, 0) || 0;
    const without = filteredCharactersData?.contentWithoutUniverse?.total || 0;
    return byUniverse + without;
  }, [filteredCharactersData]);

  const npcsCount = useMemo(() => {
    const byUniverse = filteredNpcsData?.contentByUniverse?.reduce((sum, u) => sum + u.total, 0) || 0;
    const without = filteredNpcsData?.contentWithoutUniverse?.total || 0;
    return byUniverse + without;
  }, [filteredNpcsData]);

  const getTotalCount = () => {
    const charactersCount = filteredCharactersData?.contentByUniverse?.reduce((sum, universe) => sum + universe.total, 0) || 0;
    const charactersWithoutUniverse = filteredCharactersData?.contentWithoutUniverse?.total || 0;
    const npcsCount = filteredNpcsData?.contentByUniverse?.reduce((sum, universe) => sum + universe.total, 0) || 0;
    const npcsWithoutUniverse = filteredNpcsData?.contentWithoutUniverse?.total || 0;
    return charactersCount + charactersWithoutUniverse + npcsCount + npcsWithoutUniverse;
  };

  const handleAbandonCharacter = async (id) => {
    if (window.confirm(
      'Abandonner ce personnage ? Vous perdrez le contrôle du personnage, mais son historique RP sera conservé. Cette action est irréversible pour vous.'
    )) {
      try {
        await characterApi.abandonCharacter(id);
        fetchData();
      } catch (err) {
        alert(err.response?.data?.error || 'Erreur lors de l\'abandon du personnage');
        console.error(err);
      }
    }
  };

  const handleDeleteNpc = async (id) => {
    if (window.confirm('Êtes-vous sûr de vouloir supprimer ce PNJ ?')) {
      try {
        await npcApi.deleteNpc(id);
        fetchData();
      } catch (err) {
        alert('Erreur lors de la suppression');
        console.error(err);
      }
    }
  };

  const handleEditAvatar = (character, isNpcCharacter = false) => {
    setEditingCharacter(character);
    setIsNpc(isNpcCharacter);
    setAvatarEditorOpen(true);
  };

  const handleAvatarSave = async (avatarUrl) => {
    if (!editingCharacter) return;

    setSavingAvatar(true);
    try {
      if (isNpc) {
        await npcApi.updateNpc(editingCharacter.id, {
          avatar: avatarUrl
        });
      } else {
        await characterApi.updateCharacter(editingCharacter.id, {
          avatar: avatarUrl
        });
      }
      
      setAvatarEditorOpen(false);
      setEditingCharacter(null);
      setIsNpc(false);
      setSavingAvatar(false);
      fetchData();
    } catch (err) {
      alert('Erreur lors de la mise à jour de l\'avatar');
      console.error(err);
      setSavingAvatar(false);
    }
  };

  const handleAvatarEditorClose = () => {
    setAvatarEditorOpen(false);
    setEditingCharacter(null);
    setIsNpc(false);
    setSavingAvatar(false);
  };

  const handleThemeChange = async (characterId, theme) => {
    try {
      console.log('Mise à jour du thème:', { characterId, theme });
      const response = await characterApi.updateCharacter(characterId, { sheetTheme: theme });
      console.log('Réponse API:', response);
      await fetchData();
      console.log('Données rafraîchies');
    } catch (err) {
      console.error('Erreur lors de la mise à jour du thème:', err);
      alert('Erreur lors de la mise à jour du thème: ' + (err.response?.data?.error || err.message));
    }
  };

  if (loading) {
    return (
      <Layout>
        <Loading message="Chargement de vos personnages..." />
      </Layout>
    );
  }

  if (error) {
    return (
      <Layout>
        <ErrorMessage
          message={error}
          onRetry={fetchData}
        />
      </Layout>
    );
  }

  const totalCount = getTotalCount();

  const cardProps = {
    variant: 'row',
    onAbandon: handleAbandonCharacter,
    onEditAvatar: (char) => handleEditAvatar(char, false),
    onThemeChange: handleThemeChange,
  };

  const npcCardProps = {
    variant: 'row',
    onDelete: handleDeleteNpc,
    onEditAvatar: (char) => handleEditAvatar(char, true),
  };

  const breadcrumbItems = [
    { name: 'Accueil', url: '/', icon: 'home' },
    { name: 'Mes Personnages', url: null, icon: 'character' },
  ];

  return (
    <Layout>
      <div className={styles.content}>
        <Breadcrumb items={breadcrumbItems} />
        <header className={styles.header}>
          <div className={styles.headerMain}>
            <h1 className={styles.title}>Mes Personnages</h1>
            <p className={styles.subtitle}>Gérez vos personnages joueurs et vos PNJ</p>
            {totalCount > 0 && (
              <div className={styles.stats}>
                {charactersCount > 0 && (
                  <span className={styles.statPill}>
                    <strong>{charactersCount}</strong> personnage{charactersCount > 1 ? 's' : ''}
                  </span>
                )}
                {npcsCount > 0 && (
                  <span className={styles.statPill}>
                    <strong>{npcsCount}</strong> PNJ
                  </span>
                )}
              </div>
            )}
          </div>
          <div className={styles.actions}>
            <button
              className={styles.btnPrimary}
              onClick={() => navigate('/characters/new')}
            >
              Créer un personnage
            </button>
            <button
              className={styles.btnSecondary}
              onClick={() => navigate('/characters/npc/new')}
            >
              Créer un PNJ
            </button>
          </div>
        </header>

        {totalCount === 0 ? (
          <div className={styles.emptyState}>
            <div className={styles.emptyStateIcon}>
              <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
              </svg>
            </div>
            <h2>Vous n'avez pas encore créé de personnages ni de PNJ</h2>
            <p>Créez votre premier personnage pour participer aux scènes de roleplay</p>
            <button
              className={styles.btnPrimary}
              onClick={() => navigate('/characters/new')}
            >
              Créer votre premier personnage
            </button>
          </div>
        ) : (
          <>
            {/* Personnages par univers */}
            {filteredCharactersData?.contentByUniverse?.map((universeData) => {
              const sectionCount = universeData.total;
              return (
              <div key={universeData.universe.id} className={styles.universeSection}>
                <div className={styles.universeSectionHeader}>
                  <h2 className={styles.universeTitle}>{universeData.universe.name}</h2>
                  <span className={styles.universeCount}>{sectionCount} personnage{sectionCount > 1 ? 's' : ''}</span>
                </div>

                {universeData.mainContent.characters.length > 0 && (
                  <div className={styles.section}>
                    <h3 className={styles.sectionTitle}>Personnages</h3>
                    <div className={styles.list}>
                      {universeData.mainContent.characters.map((character) => (
                        <CharacterCard
                          key={character.id}
                          character={character}
                          viewPath={getCharacterSheetPath(character)}
                          editPath={`/characters/${character.id}/edit`}
                          showUniverse={false}
                          {...cardProps}
                        />
                      ))}
                    </div>
                  </div>
                )}

                {Object.values(universeData.elseworlds || {}).map((elseworldData) => (
                  <div key={elseworldData.elseworld.id} className={styles.elseworldSection}>
                    <h3 className={styles.elseworldTitle}>
                      Elseworld · {elseworldData.elseworld.name}
                    </h3>
                    {elseworldData.characters.length > 0 && (
                      <div className={styles.list}>
                        {elseworldData.characters.map((character) => (
                          <CharacterCard
                            key={character.id}
                            character={character}
                            viewPath={getCharacterSheetPath(character)}
                            editPath={`/characters/${character.id}/edit`}
                            showUniverse={false}
                            {...cardProps}
                          />
                        ))}
                      </div>
                    )}
                  </div>
                ))}
              </div>
            );})}

            {/* Personnages sans univers */}
            {filteredCharactersData?.contentWithoutUniverse?.characters?.length > 0 && (
              <div className={styles.universeSection}>
                <div className={styles.universeSectionHeader}>
                  <h2 className={styles.universeTitle}>Sans univers</h2>
                  <span className={styles.universeCount}>
                    {filteredCharactersData.contentWithoutUniverse.total} personnage
                    {filteredCharactersData.contentWithoutUniverse.total > 1 ? 's' : ''}
                  </span>
                </div>
                <div className={styles.list}>
                  {filteredCharactersData.contentWithoutUniverse.characters.map((character) => (
                    <CharacterCard
                      key={character.id}
                      character={character}
                      viewPath={getCharacterSheetPath(character)}
                      editPath={`/characters/${character.id}/edit`}
                      showUniverse={false}
                      {...cardProps}
                    />
                  ))}
                </div>
              </div>
            )}

            {filteredNpcsData?.contentByUniverse?.map((universeData) => {
              const sectionCount = universeData.total;
              return (
              <div key={`npc-${universeData.universe.id}`} className={styles.universeSection}>
                <div className={styles.universeSectionHeader}>
                  <h2 className={styles.universeTitle}>PNJ · {universeData.universe.name}</h2>
                  <span className={styles.universeCount}>{sectionCount} PNJ</span>
                </div>

                {universeData.mainContent.npcs.length > 0 && (
                  <div className={styles.list}>
                    {universeData.mainContent.npcs.map((npc) => (
                      <CharacterCard
                        key={npc.id}
                        character={npc}
                        viewPath={undefined}
                        editPath={`/characters/npc/${npc.id}/edit`}
                        showUniverse={false}
                        {...npcCardProps}
                      />
                    ))}
                  </div>
                )}
              </div>
            );})}

            {filteredNpcsData?.contentWithoutUniverse?.npcs?.length > 0 && (
              <div className={styles.universeSection}>
                <div className={styles.universeSectionHeader}>
                  <h2 className={styles.universeTitle}>PNJ · Sans univers</h2>
                  <span className={styles.universeCount}>
                    {filteredNpcsData.contentWithoutUniverse.total} PNJ
                  </span>
                </div>
                <div className={styles.list}>
                  {filteredNpcsData.contentWithoutUniverse.npcs.map((npc) => (
                    <CharacterCard
                      key={npc.id}
                      character={npc}
                      viewPath={undefined}
                      editPath={`/characters/npc/${npc.id}/edit`}
                      showUniverse={false}
                      {...npcCardProps}
                    />
                  ))}
                </div>
              </div>
            )}
          </>
        )}
      </div>

      <AvatarEditor
        open={avatarEditorOpen}
        onClose={handleAvatarEditorClose}
        currentAvatar={editingCharacter?.avatar || null}
        characterName={editingCharacter?.name}
        onSave={handleAvatarSave}
        saving={savingAvatar}
      />
    </Layout>
  );
};

export default Characters;

