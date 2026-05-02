import React, { useState, useEffect, useMemo, useRef } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import universeService from '../../services/universeService';
import forumService from '../../services/forumService';
import threadService from '../../services/threadService';
import { characterApi } from '../../services/characterApi';
import factionService from '../../services/factionService';
import dialogueThemeService from '../../services/dialogueThemeService';
import RichTextComposer from '../../components/RichTextComposer/RichTextComposer';
import { useUniverseTheme } from '../../contexts/UniverseThemeContext';
import { useAuth } from '../../contexts/AuthContext';
import useThreadFilters from '../../hooks/useThreadFilters';
import Layout from '../../components/Layout/Layout';
import Breadcrumb from '../../components/Breadcrumb/Breadcrumb';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import ForumCardV3 from '../../components/ForumCardV3/ForumCardV3';
import Pagination from '../../components/Pagination/Pagination';
import ThreadFilters from '../../components/ThreadFilters/ThreadFilters';
import { normalizeForum } from '../../utils/forumUtils';
import styles from './ForumDetail.module.css';

const FILTER_ALL = 'all';
const FILTER_IMPORTANT = 'important';
const FILTER_PLAYER_PLATFORM = 'player_platform';
const FILTER_RP = 'roleplay';
const FILTER_HRP = 'hrp';
const FORUM_LAYOUT_SINGLE = 'single';
const FORUM_LAYOUT_DOUBLE = 'double';
const THREAD_READ_STORAGE_PREFIX = 'thread:last-read:';
const FORUM_READ_STORAGE_PREFIX = 'forum:last-read:';

const MultiSelectField = ({
  label,
  options = [],
  selectedValues = [],
  onChange,
  placeholder = 'Sélectionner...',
  emptyText = 'Aucune option',
  getOptionLabel = (option) => option.label || option.name || String(option.id),
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [search, setSearch] = useState('');
  const rootRef = useRef(null);

  useEffect(() => {
    const onDocumentClick = (event) => {
      if (!rootRef.current?.contains(event.target)) {
        setIsOpen(false);
      }
    };
    document.addEventListener('mousedown', onDocumentClick);
    return () => document.removeEventListener('mousedown', onDocumentClick);
  }, []);

  const selectedSet = useMemo(() => new Set(selectedValues.map((value) => String(value))), [selectedValues]);

  const selectedOptions = useMemo(
    () => options.filter((option) => selectedSet.has(String(option.id))),
    [options, selectedSet]
  );

  const filteredOptions = useMemo(() => {
    const query = search.trim().toLowerCase();
    if (!query) return options;
    return options.filter((option) => getOptionLabel(option).toLowerCase().includes(query));
  }, [options, search, getOptionLabel]);

  const toggleValue = (value) => {
    const normalized = String(value);
    if (selectedSet.has(normalized)) {
      onChange(selectedValues.filter((item) => String(item) !== normalized));
    } else {
      onChange([...selectedValues, normalized]);
    }
  };

  return (
    <div className={styles.multiSelectField} ref={rootRef}>
      <label className={styles.createThreadLabel}>{label}</label>
      <button type="button" className={styles.multiSelectControl} onClick={() => setIsOpen((prev) => !prev)}>
        <span className={styles.multiSelectValueText}>
          {selectedOptions.length > 0
            ? `${selectedOptions.length} sélectionné(s)`
            : placeholder}
        </span>
        <span className={`${styles.multiSelectChevron} ${isOpen ? styles.multiSelectChevronOpen : ''}`}>▾</span>
      </button>
      {selectedOptions.length > 0 && (
        <div className={styles.multiSelectTags}>
          {selectedOptions.map((option) => (
            <span key={option.id} className={styles.multiSelectTag}>
              {getOptionLabel(option)}
            </span>
          ))}
        </div>
      )}
      {isOpen && (
        <div className={styles.multiSelectDropdown}>
          <input
            type="text"
            className={styles.multiSelectSearch}
            placeholder="Rechercher..."
            value={search}
            onChange={(event) => setSearch(event.target.value)}
          />
          <div className={styles.multiSelectOptions}>
            {filteredOptions.length > 0 ? filteredOptions.map((option) => {
              const optionLabel = getOptionLabel(option);
              const isSelected = selectedSet.has(String(option.id));
              return (
                <label key={option.id} className={styles.multiSelectOption}>
                  <input
                    type="checkbox"
                    checked={isSelected}
                    onChange={() => toggleValue(option.id)}
                  />
                  <span>{optionLabel}</span>
                </label>
              );
            }) : (
              <span className={styles.createThreadHint}>{emptyText}</span>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

const ForumDetail = () => {
  const { id, slug } = useParams();
  const { currentUniverse } = useUniverseTheme();
  const { user, isAuthenticated } = useAuth();
  const navigate = useNavigate();
  
  // États pour les données
  const [forumData, setForumData] = useState(null);
  const [singleForumData, setSingleForumData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [activeFilter, setActiveFilter] = useState(FILTER_ALL);
  const [searchQuery, setSearchQuery] = useState('');
  const [isSingleForum, setIsSingleForum] = useState(false);
  const [forumLayout, setForumLayout] = useState(FORUM_LAYOUT_SINGLE);
  const [isCreateThreadOpen, setIsCreateThreadOpen] = useState(false);
  const [creatingThread, setCreatingThread] = useState(false);
  const [createThreadError, setCreateThreadError] = useState('');
  const [newThreadTitle, setNewThreadTitle] = useState('');
  const [newThreadContent, setNewThreadContent] = useState('');
  const [newThreadCharacterId, setNewThreadCharacterId] = useState('');
  const [ownedThreadCharacters, setOwnedThreadCharacters] = useState([]);
  const [universeThreadCharacters, setUniverseThreadCharacters] = useState([]);
  const [threadFactions, setThreadFactions] = useState([]);
  const [newThreadParticipantIds, setNewThreadParticipantIds] = useState([]);
  const [newThreadFactionIds, setNewThreadFactionIds] = useState([]);
  const [dialogueThemes, setDialogueThemes] = useState([]);
  const [activeDialogueThemeId, setActiveDialogueThemeId] = useState('');
  const [readSyncToken, setReadSyncToken] = useState(0);
  const isRoleplayForum = Boolean(singleForumData?.isRoleplay || singleForumData?.type === 'roleplay');
  
  // Hook personnalisé pour les filtres de threads (utilise l'URL comme source de vérité)
  const {
    filters: threadFilters,
    currentPage,
    searchInput,
    apiFilters,
    setStatus,
    setSearch,
    setMyParticipations,
    setPage,
    hasActiveFilters,
  } = useThreadFilters(user?.id);

  // Charger les données du forum une seule fois (sans filtres)
  useEffect(() => {
    const fetchData = async () => {
      try {
        setError(null);
        setLoading(true);

        // Si on a un id ou slug dans l'URL, c'est un forum spécifique
        if (id || slug) {
          setIsSingleForum(true);
          const forumSlug = slug || id;
          
          const data = await forumService.getForumBySlug(forumSlug, {
            page: currentPage,
            limit: 10,
            filters: apiFilters,
          });
          setSingleForumData(data);
        } else {
          // Sinon, afficher la liste des forums de l'univers
          setIsSingleForum(false);
          const universeSlug = currentUniverse !== 'portal' ? currentUniverse : null;
          
          if (!universeSlug) {
            setError('Veuillez sélectionner un univers pour voir ses forums');
            setLoading(false);
            return;
          }

          const data = await universeService.getUniversForums(universeSlug);
          setForumData(data);
        }
      } catch (err) {
        setError('Erreur lors du chargement des données');
        console.error(err);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [id, slug, currentUniverse, currentPage, apiFilters]);

  useEffect(() => {
    setIsCreateThreadOpen(false);
    setCreatingThread(false);
    setCreateThreadError('');
    setNewThreadTitle('');
    setNewThreadContent('');
    setNewThreadCharacterId('');
    setOwnedThreadCharacters([]);
    setUniverseThreadCharacters([]);
    setThreadFactions([]);
    setNewThreadParticipantIds([]);
    setNewThreadFactionIds([]);
    setDialogueThemes([]);
    setActiveDialogueThemeId('');
  }, [singleForumData?.forumId]);

  useEffect(() => {
    const fetchDialogueThemes = async () => {
      if (!isSingleForum || !isRoleplayForum || !isCreateThreadOpen || !isAuthenticated || !user) {
        setDialogueThemes([]);
        setActiveDialogueThemeId('');
        return;
      }

      try {
        const data = await dialogueThemeService.listThemes();
        const items = Array.isArray(data?.items) ? data.items : [];
        setDialogueThemes(items);
        const defaultThemeId = data?.defaultThemeId
          || items.find((theme) => theme.isDefault)?.id
          || items[0]?.id
          || '';
        setActiveDialogueThemeId(defaultThemeId ? String(defaultThemeId) : '');
      } catch (err) {
        console.error(err);
        setDialogueThemes([]);
        setActiveDialogueThemeId('');
      }
    };

    fetchDialogueThemes();
  }, [isSingleForum, isRoleplayForum, isCreateThreadOpen, isAuthenticated, user]);

  useEffect(() => {
    const fetchCharacters = async () => {
      if (!isSingleForum || !isRoleplayForum || !isCreateThreadOpen || !isAuthenticated || !user) {
        return;
      }

      try {
        const universeSlug = singleForumData?.universe?.slug;
        if (!universeSlug) {
          setThreadCharacters([]);
          return;
        }

        const ownData = await characterApi.getUniverseSelectableCharacters(universeSlug);
        const ownItems = Array.isArray(ownData?.items) ? ownData.items : [];
        setOwnedThreadCharacters(ownItems);
        if (ownItems.length > 0 && !newThreadCharacterId) {
          const firstId = String(ownItems[0].id);
          setNewThreadCharacterId(firstId);
          setNewThreadParticipantIds([firstId]);
        }

        const universeData = await characterApi.getUniverseValidatedCharacters(universeSlug);
        const universeItems = Array.isArray(universeData?.items) ? universeData.items : [];
        setUniverseThreadCharacters(universeItems);
      } catch (err) {
        console.error(err);
        setOwnedThreadCharacters([]);
        setUniverseThreadCharacters([]);
      }
    };

    fetchCharacters();
  }, [
    isSingleForum,
    isRoleplayForum,
    singleForumData?.universe?.slug,
    isCreateThreadOpen,
    isAuthenticated,
    user,
    newThreadCharacterId,
  ]);

  useEffect(() => {
    const fetchFactions = async () => {
      if (!isSingleForum || !isRoleplayForum || !isCreateThreadOpen || !isAuthenticated || !user) {
        return;
      }

      try {
        const universeSlug = singleForumData?.universe?.slug;
        if (!universeSlug) {
          setThreadFactions([]);
          return;
        }

        const data = await factionService.getFactions({ universe: universeSlug });
        const factions = Array.isArray(data?.factions) ? data.factions : [];
        setThreadFactions(factions);
      } catch (err) {
        console.error(err);
        setThreadFactions([]);
      }
    };

    fetchFactions();
  }, [isSingleForum, isRoleplayForum, singleForumData?.universe?.slug, isCreateThreadOpen, isAuthenticated, user]);

  useEffect(() => {
    if (!newThreadCharacterId) return;
    setNewThreadParticipantIds((prev) => (
      prev.includes(newThreadCharacterId) ? prev : [newThreadCharacterId, ...prev]
    ));
  }, [newThreadCharacterId]);

  const handleRetry = () => {
    setError(null);
    setLoading(true);
    const universeSlug = slug || (currentUniverse !== 'portal' ? currentUniverse : null);
    if (universeSlug) {
      universeService.getUniversForums(universeSlug)
        .then((data) => {
          setForumData(data);
        })
        .catch((err) => setError('Erreur lors du chargement des données'))
        .finally(() => setLoading(false));
    }
  };

  const handleCreateThread = async () => {
    if (!singleForumData?.forumId || !user) return;

    const title = newThreadTitle.trim();
    const contentContainer = document.createElement('div');
    contentContainer.innerHTML = newThreadContent || '';
    const plainContent = (contentContainer.textContent || '').replace(/\u00a0/g, ' ').trim();
    if (!title || !plainContent) {
      setCreateThreadError('Le titre et le contenu sont requis.');
      return;
    }

    if (isRoleplayForum && !newThreadCharacterId) {
      setCreateThreadError('Sélectionne un personnage pour créer un thread RP.');
      return;
    }

    setCreatingThread(true);
    setCreateThreadError('');
    try {
      const payload = {
        forum_id: singleForumData.forumId,
        author_id: user.id,
        title,
        content: newThreadContent,
        type: isRoleplayForum ? 'roleplay' : 'hrp',
      };

      if (isRoleplayForum) {
        payload.characterId = Number(newThreadCharacterId);
        payload.participantCharacterIds = Array.from(new Set(
          [newThreadCharacterId, ...newThreadParticipantIds].filter(Boolean).map((value) => Number(value))
        ));
        payload.factionIds = newThreadFactionIds.map((value) => Number(value));
      }

      const created = await threadService.createThread(payload);
      const target = created?.slug || created?.threadId;
      if (target) {
        navigate(`/threads/${target}`);
        return;
      }

      setCreateThreadError('Thread créé, mais impossible de déterminer sa destination.');
    } catch (err) {
      setCreateThreadError(err.response?.data?.error || 'Erreur lors de la création du thread.');
    } finally {
      setCreatingThread(false);
    }
  };

  // Filtrer les forums selon le filtre actif et la recherche
  const filteredForums = useMemo(() => {
    if (!forumData?.forums) return { important: [], playerPlatform: [], roleplay: [], hrp: [] };

    const filterByType = (forums, type) => {
      if (activeFilter !== FILTER_ALL && activeFilter !== type) {
        return [];
      }

      if (!searchQuery.trim()) {
        return forums;
      }

      const query = searchQuery.toLowerCase();
      return forums.filter(forum => {
        const name = (forum.name || '').toLowerCase();
        const description = (forum.description || '').toLowerCase();
        return name.includes(query) || description.includes(query);
      });
    };

    return {
      important: filterByType(forumData.forums.important || [], 'important'),
      playerPlatform: filterByType(forumData.forums.player_platform || [], 'player_platform'),
      roleplay: filterByType(forumData.forums.roleplay || [], 'roleplay'),
      hrp: filterByType(forumData.forums.hrp || [], 'hrp'),
    };
  }, [forumData, activeFilter, searchQuery]);

  const filteredElseworlds = useMemo(() => {
    if (!forumData?.elseworlds) return [];

    return forumData.elseworlds.map(elseworld => ({
      ...elseworld,
      forums: elseworld.forums.filter(forum => {
        if (activeFilter !== FILTER_ALL && activeFilter !== forum.type) {
          return false;
        }
        if (searchQuery.trim()) {
          const query = searchQuery.toLowerCase();
          const name = (forum.name || '').toLowerCase();
          const description = (forum.description || '').toLowerCase();
          return name.includes(query) || description.includes(query);
        }
        return true;
      })
    })).filter(elseworld => elseworld.forums.length > 0);
  }, [forumData, activeFilter, searchQuery]);

  const forumTypeMeta = useMemo(() => {
    const type = (singleForumData?.type || '').toLowerCase();
    if (type === 'important') {
      return { label: 'Important', className: styles.forumTypeImportant };
    }
    if (type === 'roleplay') {
      return { label: 'RP', className: styles.forumTypeRoleplay };
    }
    if (type === 'player_platform') {
      return { label: 'Plateforme', className: styles.forumTypePlatform };
    }
    if (type === 'hrp') {
      return { label: 'HRP', className: styles.forumTypeHrp };
    }
    if (isRoleplayForum) {
      return { label: 'RP', className: styles.forumTypeRoleplay };
    }
    return { label: 'HRP', className: styles.forumTypeHrp };
  }, [singleForumData?.type, isRoleplayForum]);

  const threadVisualStateById = useMemo(() => {
    const toTimestamp = (value) => {
      if (!value) return null;
      const parsed = new Date(value).getTime();
      return Number.isNaN(parsed) ? null : parsed;
    };

    const isParticipantThread = (thread) => {
      if (!thread || !user?.id) return false;
      if (thread.isParticipant || thread.userIsParticipant || thread.isUserParticipant) {
        return true;
      }
      if (thread.authorId === user.id || thread.characterCreatorId === user.id) {
        return true;
      }
      if (Array.isArray(thread.participants)) {
        return thread.participants.some((participant) => (
          participant?.userId === user.id || participant?.id === user.id
        ));
      }
      return false;
    };

    const computeUnread = (thread, latestTimestamp) => {
      if (!isAuthenticated || !latestTimestamp) return false;
      try {
        const raw = localStorage.getItem(`${THREAD_READ_STORAGE_PREFIX}${thread.threadId}`);
        if (!raw) return true;
        const readTimestamp = Number(raw);
        if (Number.isNaN(readTimestamp)) return true;
        return readTimestamp < latestTimestamp;
      } catch {
        return false;
      }
    };

    const state = new Map();
    (singleForumData?.threads || []).forEach((thread) => {
      const latestTimestamp = toTimestamp(thread.lastPost?.date) || toTimestamp(thread.createdAt);
      const isUnread = computeUnread(thread, latestTimestamp);
      const isParticipating = isParticipantThread(thread);
      state.set(thread.threadId, { isUnread, isParticipating, latestTimestamp });
    });
    return state;
  }, [singleForumData?.threads, isAuthenticated, user?.id, readSyncToken]);

  const markThreadAsRead = (threadId, latestTimestamp) => {
    if (!threadId || !latestTimestamp) return;
    try {
      localStorage.setItem(`${THREAD_READ_STORAGE_PREFIX}${threadId}`, String(latestTimestamp));
    } catch {
      // noop
    }
  };

  const markAllAsRead = () => {
    const toTimestamp = (value) => {
      if (!value) return null;
      const parsed = new Date(value).getTime();
      return Number.isNaN(parsed) ? null : parsed;
    };
    const markByKey = (key, dateValue) => {
      const timestamp = toTimestamp(dateValue);
      if (!key || !timestamp) return;
      try {
        localStorage.setItem(key, String(timestamp));
      } catch {
        // noop
      }
    };

    if (singleForumData?.threads?.length) {
      singleForumData.threads.forEach((thread) => {
        markByKey(
          `${THREAD_READ_STORAGE_PREFIX}${thread.threadId}`,
          thread.lastPost?.date || thread.createdAt
        );
      });
    }

    const visibleForums = [
      ...filteredForums.important,
      ...filteredForums.playerPlatform,
      ...filteredForums.roleplay,
      ...filteredForums.hrp,
      ...filteredElseworlds.flatMap((elseworld) => elseworld.forums || []),
    ];
    visibleForums.forEach((forum) => {
      const forumKeyPart = forum.id ?? forum.slug ?? forum.name;
      markByKey(`${FORUM_READ_STORAGE_PREFIX}${forumKeyPart}`, forum.lastPost?.date);
    });

    setReadSyncToken((previous) => previous + 1);
  };

  if (loading) {
    return (
      <Layout>
        <Loading message="Chargement du forum..." />
      </Layout>
    );
  }

  if (error) {
    return (
      <Layout>
        <ErrorMessage
          message={error || 'Forum introuvable'}
          onRetry={handleRetry}
        />
      </Layout>
    );
  }

  // Affichage d'un forum spécifique avec ses sous-forums et threads
  if (isSingleForum && singleForumData) {
    const breadcrumbItems = Array.isArray(singleForumData.breadcrumb) && singleForumData.breadcrumb.length > 0
      ? singleForumData.breadcrumb.map((item, index) => {
          const isLegacyHome = index === 0 && typeof item?.name === 'string' && item.name.toLowerCase() === 'home';
          return {
            ...item,
            name: isLegacyHome ? 'FORUMS' : item.name,
          };
        })
      : [
          { name: 'FORUMS', url: '/forums' },
          { name: singleForumData.forumName, url: null },
        ];

    return (
      <Layout>
        <div className={styles.content}>
          <Breadcrumb items={breadcrumbItems} />

          <header className={styles.header}>
            <div className={styles.headerContent}>
              <div className={styles.headerTop}>
                <div className={styles.titleSection}>
                  <div className={styles.titleRow}>
                    <h1 className={styles.title}>{singleForumData.forumName}</h1>
                    <span className={`${styles.forumTypeBadge} ${forumTypeMeta.className}`}>
                      {forumTypeMeta.label}
                    </span>
                  </div>
                  {singleForumData.description && (
                    <p className={styles.description}>{singleForumData.description}</p>
                  )}
                </div>
                {singleForumData.stats && (
                  <div className={styles.statsSection}>
                    <div className={styles.statItem}>
                      <div className={styles.statValue}>{singleForumData.stats.totalThreads || 0}</div>
                      <div className={styles.statLabel}>Discussions</div>
                    </div>
                    <div className={styles.statItem}>
                      <div className={styles.statValue}>{singleForumData.stats.totalPosts || 0}</div>
                      <div className={styles.statLabel}>Messages</div>
                    </div>
                    {singleForumData.subForums && singleForumData.subForums.length > 0 && (
                      <div className={styles.statItem}>
                        <div className={styles.statValue}>{singleForumData.subForums.length}</div>
                        <div className={styles.statLabel}>Sous-forums</div>
                      </div>
                    )}
                  </div>
                )}
              </div>
            </div>
          </header>

          {/* Sous-forums */}
          {singleForumData.subForums && singleForumData.subForums.length > 0 && (
            <section className={styles.subForumsSection}>
              <h2 className={styles.sectionTitle}>Sous-forums</h2>
              <div className={styles.forumsList}>
                {singleForumData.subForums.map((subForum) => (
                  <ForumCardV3 
                    key={subForum.id} 
                  readSyncToken={readSyncToken}
                    forum={normalizeForum({
                      id: subForum.id,
                      slug: subForum.slug,
                      name: subForum.name,
                      description: subForum.description,
                      banner: subForum.banner,
                      type: subForum.type,
                      lastPost: subForum.lastPost ? {
                        threadId: subForum.lastPost.threadId,
                        threadSlug: subForum.lastPost.threadSlug,
                        threadTitle: subForum.lastPost.threadTitle,
                        author: subForum.lastPost.author,
                        character: subForum.lastPost.character,
                        avatar: subForum.lastPost.avatar,
                        date: subForum.lastPost.date,
                      } : null,
                      stats: subForum.stats,
                      subForums: [],
                    })} 
                  />
                ))}
              </div>
            </section>
          )}

          {/* Threads */}
          <section className={styles.threadsSection}>
            <div className={styles.sectionHeader}>
              <h2 className={styles.sectionTitle}>Threads</h2>
              <div className={styles.sectionHeaderActions}>
                {singleForumData.pagination && (
                  <span className={styles.threadsCount}>
                    {singleForumData.pagination.total} {singleForumData.pagination.total > 1 ? 'threads' : 'thread'}
                    {singleForumData.pagination.totalPages > 1 && ` • Page ${singleForumData.pagination.page}/${singleForumData.pagination.totalPages}`}
                  </span>
                )}
                {isAuthenticated && (
                  <button
                    type="button"
                    className={styles.addThreadButton}
                    onClick={() => setIsCreateThreadOpen((prev) => !prev)}
                  >
                    {isCreateThreadOpen ? 'Fermer' : 'Ajouter un thread'}
                  </button>
                )}
              </div>
            </div>

            {isAuthenticated && isCreateThreadOpen && (
              <div className={styles.createThreadPanel}>
                <div className={styles.createThreadForm}>
                  <input
                    type="text"
                    className={styles.createThreadInput}
                    placeholder="Titre du thread"
                    value={newThreadTitle}
                    onChange={(event) => setNewThreadTitle(event.target.value)}
                  />
                  {isRoleplayForum && (
                    <>
                      <div className={styles.createThreadField}>
                        <label className={styles.createThreadLabel} htmlFor="create-thread-character">
                          Personnage principal
                        </label>
                        <select
                          id="create-thread-character"
                          className={styles.createThreadSelect}
                          value={newThreadCharacterId}
                          onChange={(event) => setNewThreadCharacterId(event.target.value)}
                        >
                          <option value="">Sélectionner un personnage</option>
                          {ownedThreadCharacters.map((character) => (
                            <option key={character.id} value={character.id}>
                              {character.name}
                            </option>
                          ))}
                        </select>
                      </div>
                      <MultiSelectField
                        label="Participants RP"
                        options={universeThreadCharacters}
                        selectedValues={newThreadParticipantIds}
                        onChange={setNewThreadParticipantIds}
                        placeholder="Sélectionner des participants"
                        emptyText="Aucun personnage validé dans cet univers."
                        getOptionLabel={(character) => character.user?.pseudo
                          ? `${character.name} (@${character.user.pseudo})`
                          : character.name}
                      />
                      <MultiSelectField
                        label="Factions impliquées"
                        options={threadFactions}
                        selectedValues={newThreadFactionIds}
                        onChange={setNewThreadFactionIds}
                        placeholder="Sélectionner des factions"
                        emptyText="Aucune faction disponible dans cet univers."
                        getOptionLabel={(faction) => faction.name}
                      />
                    </>
                  )}
                  <div className={styles.createThreadField}>
                    <label className={styles.createThreadLabel}>Premier message</label>
                    <RichTextComposer
                      value={newThreadContent}
                      onChange={setNewThreadContent}
                      dialogueThemes={dialogueThemes}
                      activeDialogueThemeId={activeDialogueThemeId}
                      onActiveDialogueThemeIdChange={setActiveDialogueThemeId}
                    />
                  </div>
                  {createThreadError && <p className={styles.createThreadError}>{createThreadError}</p>}
                  <div className={styles.createThreadActions}>
                    <button
                      type="button"
                      className={styles.addThreadButton}
                      onClick={handleCreateThread}
                      disabled={creatingThread}
                    >
                      {creatingThread ? 'Création...' : 'Publier le thread'}
                    </button>
                  </div>
                </div>
              </div>
            )}
            
            {/* Filtres */}
            {isSingleForum && (
              <ThreadFilters
                status={threadFilters.status}
                searchInput={searchInput}
                myParticipations={threadFilters.myParticipations}
                isAuthenticated={isAuthenticated}
                onStatusChange={setStatus}
                onSearchChange={setSearch}
                onMyParticipationsChange={setMyParticipations}
              />
            )}
            {singleForumData.threads && singleForumData.threads.length > 0 ? (
              <div className={styles.threadsList}>
                {singleForumData.threads.map((thread) => (
                  (() => {
                    const threadState = threadVisualStateById.get(thread.threadId) || {
                      isUnread: false,
                      isParticipating: false,
                      latestTimestamp: null,
                    };
                    const isParticipatingUnread = threadState.isUnread && threadState.isParticipating;
                    return (
                  <Link 
                    key={thread.threadId} 
                    to={`/threads/${thread.threadSlug || thread.threadId}`}
                    className={`${styles.threadItem} ${
                      threadState.isUnread ? styles.threadItemUnread : ''
                    } ${isParticipatingUnread ? styles.threadItemParticipatingUnread : ''}`}
                    onClick={() => markThreadAsRead(thread.threadId, threadState.latestTimestamp)}
                  >
                    <div className={styles.threadMainContent}>
                      {thread.authorAvatar && (
                        <img src={thread.authorAvatar} alt={thread.author} className={styles.threadAvatar} />
                      )}
                      <div className={styles.threadContent}>
                        <div className={styles.threadHeader}>
                          <h3 className={styles.threadTitle}>{thread.title}</h3>
                          {(threadState.isUnread || isParticipatingUnread) && (
                            <div className={styles.threadStateBadges}>
                              {threadState.isUnread && (
                                <span className={styles.badgeUnread}>Non lu</span>
                              )}
                              {isParticipatingUnread && (
                                <span className={styles.badgeParticipating}>Ta participation</span>
                              )}
                            </div>
                          )}
                          <div className={styles.threadBadges}>
                            {thread.pinned && (
                              <span className={styles.badgePinned}>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                  <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                </svg>
                              </span>
                            )}
                            {thread.locked && (
                              <span className={styles.badgeLocked}>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                  <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                              </span>
                            )}
                          </div>
                        </div>
                        <div className={styles.threadAuthorInfo}>
                          <span className={styles.threadAuthorName}>{thread.author}</span>
                          <span className={styles.separator}>•</span>
                          <span className={styles.threadDate}>{thread.createdAt}</span>
                          {thread.replies !== undefined && (
                            <>
                              <span className={styles.separator}>•</span>
                              <span>{thread.replies} réponses</span>
                            </>
                          )}
                        </div>
                      </div>
                    </div>
                    {thread.lastPost && (
                      <div className={styles.lastPostSection}>
                        <div className={styles.lastPostContent}>
                          {thread.lastPost.avatar && (
                            <img 
                              src={thread.lastPost.avatar} 
                              alt={thread.lastPost.character || thread.lastPost.author}
                              className={styles.lastPostAvatar}
                            />
                          )}
                          <div className={styles.lastPostInfo}>
                            <div className={styles.lastPostLabel}>Dernier message</div>
                            <div className={styles.lastPostAuthor}>
                              {thread.lastPost.character || thread.lastPost.author}
                            </div>
                            {thread.lastPost.date && (
                              <div className={styles.lastPostDate}>
                                {new Date(thread.lastPost.date).toLocaleDateString('fr-FR', {
                                  day: '2-digit',
                                  month: '2-digit',
                                  year: 'numeric',
                                  hour: '2-digit',
                                  minute: '2-digit'
                                })}
                              </div>
                            )}
                          </div>
                        </div>
                        <div className={styles.lastPostIndicator}>
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <polyline points="9 18 15 12 9 6" />
                          </svg>
                        </div>
                      </div>
                    )}
                  </Link>
                    );
                  })()
                ))}
              </div>
            ) : (
              <div className={styles.emptyState}>
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                </svg>
                <p>
                  {hasActiveFilters
                    ? 'Aucun thread ne correspond à vos critères de recherche.'
                    : 'Aucun thread dans ce forum pour le moment.'}
                </p>
              </div>
            )}
            
            {/* Pagination */}
            {singleForumData.pagination && singleForumData.pagination.totalPages > 1 && (
              <Pagination
                currentPage={currentPage}
                totalPages={singleForumData.pagination.totalPages}
                onPageChange={setPage}
              />
            )}
          </section>
        </div>
      </Layout>
    );
  }

  // Affichage de la liste des forums de l'univers (comportement existant)
  if (!forumData) {
    return (
      <Layout>
        <ErrorMessage
          message="Forum introuvable"
          onRetry={handleRetry}
        />
      </Layout>
    );
  }

  const breadcrumbItems = [
    { name: 'PORTAIL', url: '/', icon: 'home' },
    { name: forumData.universe.name, url: null, icon: 'forum' },
  ];

  const hasImportantForums = filteredForums.important.length > 0;
  const hasPlayerPlatformForums = filteredForums.playerPlatform.length > 0;
  const hasRoleplayForums = filteredForums.roleplay.length > 0;
  const hasHrpForums = filteredForums.hrp.length > 0;
  const hasElseworlds = filteredElseworlds.length > 0;
  const hasAnyContent = hasImportantForums || hasPlayerPlatformForums || hasRoleplayForums || hasHrpForums || hasElseworlds;

  return (
    <Layout>
      <div className={styles.content}>
        {/* Breadcrumb amélioré */}
        <Breadcrumb items={breadcrumbItems} />

        {/* Header */}
        <header className={styles.header}>
          <div className={styles.headerContent}>
            <h1 className={styles.title}>Forums de {forumData.universe.name}</h1>
            {forumData.universe.description && (
              <p className={styles.description}>{forumData.universe.description}</p>
            )}
          </div>
        </header>

        {/* Système de filtres */}
        <div className={styles.filtersSection}>
          <div className={styles.filtersToolbar}>
            <div className={styles.filtersControls}>
              <div className={styles.controlGroup}>
                <span className={styles.controlLabel}>Type</span>
                <div className={styles.filterButtons}>
                  <button
                    className={`${styles.filterButton} ${activeFilter === FILTER_ALL ? styles.active : ''}`}
                    onClick={() => setActiveFilter(FILTER_ALL)}
                    type="button"
                  >
                    Tous
                  </button>
                  <button
                    className={`${styles.filterButton} ${activeFilter === FILTER_IMPORTANT ? styles.active : ''}`}
                    onClick={() => setActiveFilter(FILTER_IMPORTANT)}
                    type="button"
                  >
                    Importants
                  </button>
                  <button
                    className={`${styles.filterButton} ${activeFilter === FILTER_PLAYER_PLATFORM ? styles.active : ''}`}
                    onClick={() => setActiveFilter(FILTER_PLAYER_PLATFORM)}
                    type="button"
                  >
                    Plateforme joueur
                  </button>
                  <button
                    className={`${styles.filterButton} ${activeFilter === FILTER_RP ? styles.active : ''}`}
                    onClick={() => setActiveFilter(FILTER_RP)}
                    type="button"
                  >
                    Roleplay
                  </button>
                  <button
                    className={`${styles.filterButton} ${activeFilter === FILTER_HRP ? styles.active : ''}`}
                    onClick={() => setActiveFilter(FILTER_HRP)}
                    type="button"
                  >
                    Hors RP
                  </button>
                </div>
              </div>
              <div className={styles.controlGroup}>
                <span className={styles.controlLabel}>Vue</span>
                <div className={styles.viewButtons}>
                  <button
                    className={`${styles.filterButton} ${forumLayout === FORUM_LAYOUT_SINGLE ? styles.active : ''}`}
                    onClick={() => setForumLayout(FORUM_LAYOUT_SINGLE)}
                    type="button"
                  >
                    1 colonne
                  </button>
                  <button
                    className={`${styles.filterButton} ${forumLayout === FORUM_LAYOUT_DOUBLE ? styles.active : ''}`}
                    onClick={() => setForumLayout(FORUM_LAYOUT_DOUBLE)}
                    type="button"
                  >
                    2 colonnes
                  </button>
                </div>
              </div>
            </div>
            <div className={styles.toolbarActions}>
              <div className={styles.searchBox}>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <circle cx="11" cy="11" r="8" />
                  <path d="m21 21-4.35-4.35" />
                </svg>
                <input
                  type="text"
                  placeholder="Rechercher un forum..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className={styles.searchInput}
                />
                {searchQuery && (
                  <button
                    onClick={() => setSearchQuery('')}
                    className={styles.clearButton}
                    aria-label="Effacer la recherche"
                    type="button"
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <line x1="18" y1="6" x2="6" y2="18" />
                      <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                  </button>
                )}
              </div>
              <button
                type="button"
                className={styles.markAllReadButton}
                onClick={markAllAsRead}
              >
                Tout marquer comme lu
              </button>
            </div>
          </div>
        </div>

        {/* Forums Importants */}
        {hasImportantForums && (
          <section className={styles.forumTypeSection} id="important-forums">
            <div className={styles.sectionHeader}>
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
              </svg>
              <div>
                <h2 className={styles.sectionTitle}>Forums Importants</h2>
                <p className={styles.sectionSubtitle}>Annonces et informations essentielles de {forumData.universe.name}</p>
              </div>
            </div>
            <div
              className={`${styles.forumsList} ${
                forumLayout === FORUM_LAYOUT_DOUBLE ? styles.forumsListTwoColumns : ''
              }`}
            >
              {filteredForums.important.map((forum) => (
                <ForumCardV3 
                  key={forum.id} 
                  compactSubforums={forumLayout === FORUM_LAYOUT_DOUBLE}
                  readSyncToken={readSyncToken}
                  forum={normalizeForum({
                    ...forum,
                    lastThread: forum.lastPost ? {
                      id: forum.lastPost.threadId,
                      title: forum.lastPost.threadTitle,
                      author: forum.lastPost.author || forum.lastPost.character,
                      date: forum.lastPost.date,
                    } : null,
                    stats: forum.stats,
                    subForums: forum.subforums || [],
                  })} 
                />
              ))}
            </div>
          </section>
        )}

        {/* Plateforme joueur */}
        {hasPlayerPlatformForums && (
          <section className={styles.forumTypeSection} id="platform-forums">
            <div className={styles.sectionHeader}>
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M3 12h18" />
                <path d="M12 3v18" />
                <path d="m8 8 8 8" />
                <path d="m16 8-8 8" />
              </svg>
              <div>
                <h2 className={styles.sectionTitle}>Plateforme joueur</h2>
                <p className={styles.sectionSubtitle}>Espaces joueurs et ressources hors RP de {forumData.universe.name}</p>
              </div>
            </div>
            <div
              className={`${styles.forumsList} ${
                forumLayout === FORUM_LAYOUT_DOUBLE ? styles.forumsListTwoColumns : ''
              }`}
            >
              {filteredForums.playerPlatform.map((forum) => (
                <ForumCardV3
                  key={forum.id}
                  compactSubforums={forumLayout === FORUM_LAYOUT_DOUBLE}
                  readSyncToken={readSyncToken}
                  forum={normalizeForum({
                    ...forum,
                    lastThread: forum.lastPost ? {
                      id: forum.lastPost.threadId,
                      title: forum.lastPost.threadTitle,
                      author: forum.lastPost.author || forum.lastPost.character,
                      date: forum.lastPost.date,
                    } : null,
                    stats: forum.stats,
                    subForums: forum.subforums || [],
                  })}
                />
              ))}
            </div>
          </section>
        )}

        {/* Forums Roleplay */}
        {hasRoleplayForums && (
          <section className={styles.forumTypeSection} id="rp-forums">
            <div className={styles.sectionHeader}>
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
              </svg>
              <div>
                <h2 className={styles.sectionTitle}>Forums Roleplay</h2>
                <p className={styles.sectionSubtitle}>Incarnez vos personnages dans l'univers {forumData.universe.name}</p>
              </div>
            </div>
            <div
              className={`${styles.forumsList} ${
                forumLayout === FORUM_LAYOUT_DOUBLE ? styles.forumsListTwoColumns : ''
              }`}
            >
              {filteredForums.roleplay.map((forum) => (
                <ForumCardV3 
                  key={forum.id} 
                  compactSubforums={forumLayout === FORUM_LAYOUT_DOUBLE}
                  readSyncToken={readSyncToken}
                  forum={normalizeForum({
                    ...forum,
                    lastThread: forum.lastPost ? {
                      id: forum.lastPost.threadId,
                      title: forum.lastPost.threadTitle,
                      author: forum.lastPost.author || forum.lastPost.character,
                      date: forum.lastPost.date,
                    } : null,
                    stats: forum.stats,
                    subForums: forum.subforums || [],
                  })} 
                />
              ))}
            </div>
          </section>
        )}

        {/* Forums Hors-Roleplay */}
        {hasHrpForums && (
          <section className={styles.forumTypeSection} id="hrp-forums">
            <div className={styles.sectionHeader}>
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
              </svg>
              <div>
                <h2 className={styles.sectionTitle}>Forums Hors-Roleplay</h2>
                <p className={styles.sectionSubtitle}>Discussions et informations globales du site</p>
              </div>
            </div>
            <div
              className={`${styles.forumsList} ${
                forumLayout === FORUM_LAYOUT_DOUBLE ? styles.forumsListTwoColumns : ''
              }`}
            >
              {filteredForums.hrp.map((forum) => (
                <ForumCardV3 
                  key={forum.id} 
                  compactSubforums={forumLayout === FORUM_LAYOUT_DOUBLE}
                  readSyncToken={readSyncToken}
                  forum={normalizeForum({
                    ...forum,
                    lastThread: forum.lastPost ? {
                      id: forum.lastPost.threadId,
                      title: forum.lastPost.threadTitle,
                      author: forum.lastPost.author || forum.lastPost.character,
                      date: forum.lastPost.date,
                    } : null,
                    stats: forum.stats,
                    subForums: forum.subforums || [],
                  })} 
                />
              ))}
            </div>
          </section>
        )}

        {/* Elseworlds */}
        {hasElseworlds && (
          <section className={styles.elseworldsSection}>
            <div className={styles.sectionHeader}>
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="12" r="10" />
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
              </svg>
              <h2 className={styles.sectionTitle}>Forums des Elseworlds</h2>
            </div>
            {filteredElseworlds.length === 0 ? (
              <div className={styles.emptyState}>
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <circle cx="12" cy="12" r="10" />
                  <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                </svg>
                <p>Aucun forum n'est disponible dans les Elseworlds de cet univers.</p>
              </div>
            ) : (
              filteredElseworlds.map((elseworld) => (
                <div key={elseworld.id} className={styles.elseworldBlock}>
                  <div className={styles.elseworldHeader}>
                    {elseworld.logo && (
                      <img src={elseworld.logo} alt={elseworld.name} className={styles.elseworldLogo} />
                    )}
                    <h3 className={styles.elseworldTitle}>{elseworld.name}</h3>
                  </div>
                  <div
                    className={`${styles.forumsList} ${
                      forumLayout === FORUM_LAYOUT_DOUBLE ? styles.forumsListTwoColumns : ''
                    }`}
                  >
                    {elseworld.forums.map((forum) => (
                      <ForumCardV3 
                        key={forum.id} 
                        compactSubforums={forumLayout === FORUM_LAYOUT_DOUBLE}
                        readSyncToken={readSyncToken}
                        forum={normalizeForum({
                          ...forum,
                          lastThread: forum.lastPost ? {
                            id: forum.lastPost.threadId,
                            title: forum.lastPost.threadTitle,
                            author: forum.lastPost.author || forum.lastPost.character,
                            date: forum.lastPost.date,
                          } : null,
                          stats: forum.stats,
                          subForums: forum.subforums || [],
                        })} 
                      />
                    ))}
                  </div>
                </div>
              ))
            )}
          </section>
        )}

        {/* Message si aucun résultat */}
        {!hasAnyContent && (
          <div className={styles.emptyState}>
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.35-4.35" />
            </svg>
            <p>Aucun forum trouvé avec ces critères de recherche.</p>
            <button
              onClick={() => {
                setActiveFilter(FILTER_ALL);
                setSearchQuery('');
              }}
              className={styles.resetButton}
            >
              Réinitialiser les filtres
            </button>
          </div>
        )}
      </div>
    </Layout>
  );
};

export default ForumDetail;
