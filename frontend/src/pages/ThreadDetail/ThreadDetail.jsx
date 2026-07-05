import React, { useState, useEffect, useMemo, useRef } from 'react';
import { Link, useParams } from 'react-router-dom';
import threadService from '../../services/threadService';
import { characterApi } from '../../services/characterApi';
import postService from '../../services/postService';
import dialogueThemeService from '../../services/dialogueThemeService';
import rpActivityService from '../../services/rpActivityService';
import Layout from '../../components/Layout/Layout';
import SeoHead from '../../components/Seo/SeoHead';
import Breadcrumb from '../../components/Breadcrumb/Breadcrumb';
import seoService from '../../services/seoService';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import PostCard from '../../components/PostCard/PostCard';
import RichTextComposer from '../../components/RichTextComposer/RichTextComposerTiptap';
import RpActivityCard from '../../components/RpActivityCard/RpActivityCard';
import { useAuth } from '../../contexts/AuthContext';
import styles from './ThreadDetail.module.css';
import './themes.css';

const THREAD_PAGE_SIZE = 15;

const ACTOR_KEY_CHAR = 'c:';
const ACTOR_KEY_NPC = 'n:';

function actorKeyFromPostCharacter(character) {
  if (!character?.id) return '';
  if (character.entityType === 'npc') return `${ACTOR_KEY_NPC}${character.id}`;
  return `${ACTOR_KEY_CHAR}${character.id}`;
}

function normalizeDraftActorKey(raw) {
  if (raw === undefined || raw === null || raw === '') return '';
  const s = String(raw);
  if (s.startsWith(ACTOR_KEY_CHAR) || s.startsWith(ACTOR_KEY_NPC)) return s;
  if (/^\d+$/.test(s)) return `${ACTOR_KEY_CHAR}${s}`;
  return s;
}

function parseActorKeyForPost(actorKey) {
  if (!actorKey) return { characterId: null, npcId: null };
  const s = String(actorKey);
  if (s.startsWith(ACTOR_KEY_NPC)) {
    const id = Number(s.slice(ACTOR_KEY_NPC.length));
    return { characterId: null, npcId: Number.isFinite(id) && id > 0 ? id : null };
  }
  if (s.startsWith(ACTOR_KEY_CHAR)) {
    const id = Number(s.slice(ACTOR_KEY_CHAR.length));
    return { characterId: Number.isFinite(id) && id > 0 ? id : null, npcId: null };
  }
  const legacy = Number(s);
  return { characterId: Number.isFinite(legacy) && legacy > 0 ? legacy : null, npcId: null };
}

const ThreadDetail = () => {
  const { slug } = useParams();
  const { user } = useAuth();
  const [thread, setThread] = useState(null);
  const [posts, setPosts] = useState([]);
  const [postsPagination, setPostsPagination] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [seo, setSeo] = useState(null);
  const [posting, setPosting] = useState(false);
  const [editingPostId, setEditingPostId] = useState(null);
  const [inlineEditingPostId, setInlineEditingPostId] = useState(null);
  const [inlineEditingParagraphIndex, setInlineEditingParagraphIndex] = useState(null);
  const [composerContent, setComposerContent] = useState('');
  const [quotedPostId, setQuotedPostId] = useState(null);
  const [selectedCharacterId, setSelectedCharacterId] = useState('');
  const [availableCharacters, setAvailableCharacters] = useState([]);
  const [draftSavedAt, setDraftSavedAt] = useState(null);
  const [draftMessage, setDraftMessage] = useState('');
  const composerSectionRef = useRef(null);
  const [moderatingCharacter, setModeratingCharacter] = useState(false);
  const [moderationAction, setModerationAction] = useState(null);
  const [moderationMessage, setModerationMessage] = useState('');
  const [dialogueThemes, setDialogueThemes] = useState([]);
  const [activeDialogueThemeId, setActiveDialogueThemeId] = useState('');
  const [dialogueThemeMessage, setDialogueThemeMessage] = useState('');
  const [creatingDialogueTheme, setCreatingDialogueTheme] = useState(false);
  const [newDialogueThemeName, setNewDialogueThemeName] = useState('');
  const [newDialogueThemeColor, setNewDialogueThemeColor] = useState('#339999');
  const [newDialogueThemeFontFamily, setNewDialogueThemeFontFamily] = useState('');
  const [newDialogueThemeBold, setNewDialogueThemeBold] = useState(false);
  const [newDialogueThemeItalic, setNewDialogueThemeItalic] = useState(false);
  const [isDialogueThemesOpen, setIsDialogueThemesOpen] = useState(false);
  const [autoCharacterPrefillDone, setAutoCharacterPrefillDone] = useState(false);

  const isRoleplayThread = thread?.isRoleplay || thread?.type === 'roleplay';
  const draftStorageKey = `thread-draft-${slug}`;
  const [activeTab, setActiveTab] = useState('general');

  const fetchThread = async (pageToLoad = 1, reset = false) => {
    try {
      const data = await threadService.getThread(slug, { page: pageToLoad, limit: THREAD_PAGE_SIZE });
      setThread(data);
      setPostsPagination(data.postsPagination || null);
      setCurrentPage(pageToLoad);
      setPosts((prev) => (reset ? data.posts : [...prev, ...data.posts]));
    } catch (err) {
      setError('Erreur lors du chargement de la discussion');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    setLoading(true);
    setPosts([]);
    setAutoCharacterPrefillDone(false);
    fetchThread(1, true);
  }, [slug]);

  useEffect(() => {
    if (!slug) return;
    seoService.getThread(slug).then(setSeo).catch(() => setSeo(null));
  }, [slug]);

  useEffect(() => {
    const saved = window.localStorage.getItem(draftStorageKey);
    if (!saved) return;
    try {
      const parsed = JSON.parse(saved);
      if (typeof parsed === 'string') {
        setComposerContent(parsed);
        return;
      }
      setComposerContent(parsed.content || '');
      setSelectedCharacterId(normalizeDraftActorKey(parsed.selectedCharacterId));
      setDraftSavedAt(parsed.updatedAt || null);
      setDraftMessage('Brouillon restauré');
    } catch (error) {
      setComposerContent(saved);
    }
  }, [draftStorageKey]);

  const saveDraft = (isAutomatic = false) => {
    const payload = {
      content: composerContent,
      selectedCharacterId: selectedCharacterId || null,
      updatedAt: new Date().toISOString(),
    };
    window.localStorage.setItem(draftStorageKey, JSON.stringify(payload));
    setDraftSavedAt(payload.updatedAt);
    setDraftMessage(isAutomatic ? 'Brouillon auto-enregistré' : 'Brouillon enregistré');
  };

  useEffect(() => {
    const interval = window.setInterval(() => {
      if (!composerContent.trim()) return;
      saveDraft(true);
    }, 20000);

    return () => window.clearInterval(interval);
  }, [composerContent, selectedCharacterId, draftStorageKey]);

  useEffect(() => {
    const fetchCharacters = async () => {
      if (!user || !isRoleplayThread) {
        setAvailableCharacters([]);
        return;
      }
      try {
        const linkedActivityId = Array.isArray(thread?.rpActivities) && thread.rpActivities.length > 0
          ? Number(thread.rpActivities[0]?.id || 0)
          : 0;
        if (linkedActivityId > 0) {
          const data = await rpActivityService.getSelectableCharacters(linkedActivityId);
          const items = Array.isArray(data?.items) ? data.items : [];
          const npcs = Array.isArray(data?.npcs) ? data.npcs : [];
          setAvailableCharacters([
            ...items.map((c) => ({ ...c, _actorKey: `${ACTOR_KEY_CHAR}${c.id}` })),
            ...npcs.map((n) => ({ ...n, _actorKey: `${ACTOR_KEY_NPC}${n.id}` })),
          ]);
          return;
        }

        const universeSlug = thread?.universe?.slug;
        if (universeSlug) {
          const data = await characterApi.getUniverseSelectableCharacters(universeSlug);
          const items = Array.isArray(data?.items) ? data.items : [];
          setAvailableCharacters(items.map((c) => ({ ...c, _actorKey: `${ACTOR_KEY_CHAR}${c.id}` })));
          return;
        }

        // Fallback de sécurité si l'univers n'est pas disponible
        const data = await characterApi.getCharacters();
        const byUniverse = Array.isArray(data?.contentByUniverse) ? data.contentByUniverse : [];
        const flattenCharacters = byUniverse.flatMap((universeBlock) => [
          ...(universeBlock?.mainContent?.characters || []),
          ...Object.values(universeBlock?.elseworlds || {}).flatMap((e) => e?.characters || []),
        ]);
        setAvailableCharacters(flattenCharacters.map((c) => ({ ...c, _actorKey: `${ACTOR_KEY_CHAR}${c.id}` })));
      } catch (err) {
        console.error(err);
        setAvailableCharacters([]);
      }
    };
    fetchCharacters();
  }, [user, isRoleplayThread, thread?.universe?.slug, thread?.rpActivities]);

  useEffect(() => {
    const fetchDialogueThemes = async () => {
      if (!user || !isRoleplayThread) {
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
  }, [user, isRoleplayThread, thread?.threadId]);

  const participantsLabel = useMemo(() => {
    if (!thread?.participants?.length) return 'Aucun participant renseigne';
    return `${thread.participants.length} participant(s)`;
  }, [thread?.participants]);

  const selectedCharacter = useMemo(() => {
    if (!selectedCharacterId) return null;
    const byKey = availableCharacters.find((a) => a._actorKey === selectedCharacterId);
    if (byKey) return byKey;
    return availableCharacters.find((a) => String(a.id) === String(selectedCharacterId)) || null;
  }, [availableCharacters, selectedCharacterId]);

  const selectedDialogueTheme = useMemo(() => {
    if (!activeDialogueThemeId) return null;
    return dialogueThemes.find((theme) => String(theme.id) === String(activeDialogueThemeId)) || null;
  }, [dialogueThemes, activeDialogueThemeId]);

  useEffect(() => {
    if (!user || !isRoleplayThread || autoCharacterPrefillDone) {
      return;
    }

    if (!Array.isArray(availableCharacters) || availableCharacters.length === 0) {
      return;
    }

    if (selectedCharacterId) {
      setAutoCharacterPrefillDone(true);
      return;
    }

    const participantIds = new Set((thread?.participants || []).map((participant) => String(participant.id)));
    const participantOwnedCharacters = availableCharacters.filter(
      (character) => character.entityType !== 'npc' && participantIds.has(String(character.id)),
    );
    const candidates = participantOwnedCharacters.length > 0 ? participantOwnedCharacters : availableCharacters;

    const latestPostByActorKey = new Map();
    posts.forEach((post) => {
      const key = actorKeyFromPostCharacter(post?.character);
      if (!key) return;
      const timestamp = post?.date ? new Date(post.date).getTime() : 0;
      if (!Number.isFinite(timestamp)) return;
      const current = latestPostByActorKey.get(key) ?? 0;
      if (timestamp > current) {
        latestPostByActorKey.set(key, timestamp);
      }
    });

    const sortedCandidates = [...candidates].sort((a, b) => {
      const aKey = a._actorKey || `${ACTOR_KEY_CHAR}${a.id}`;
      const bKey = b._actorKey || `${ACTOR_KEY_CHAR}${b.id}`;
      const aLatest = latestPostByActorKey.get(aKey) ?? -1;
      const bLatest = latestPostByActorKey.get(bKey) ?? -1;
      if (aLatest === bLatest) {
        return String(a.name || '').localeCompare(String(b.name || ''), 'fr', { sensitivity: 'base' });
      }
      return aLatest - bLatest;
    });

    if (sortedCandidates[0]) {
      const first = sortedCandidates[0];
      setSelectedCharacterId(first._actorKey || `${ACTOR_KEY_CHAR}${first.id}`);
    }

    setAutoCharacterPrefillDone(true);
  }, [
    user,
    isRoleplayThread,
    autoCharacterPrefillDone,
    availableCharacters,
    thread?.participants,
    posts,
    selectedCharacterId,
  ]);

  const selectedCharacterIdentity = useMemo(() => {
    if (!selectedCharacter) return '';

    if (selectedCharacter.entityType === 'npc') {
      const fullName = [selectedCharacter.firstName, selectedCharacter.lastName]
        .filter(Boolean)
        .join(' ')
        .trim();
      return fullName || '';
    }

    const fullName = [selectedCharacter.firstName, selectedCharacter.lastName]
      .filter(Boolean)
      .join(' ')
      .trim();
    const pseudo = selectedCharacter.actualPseudo ? `@${selectedCharacter.actualPseudo}` : '';

    if (fullName && pseudo) {
      return `${fullName} / ${pseudo}`;
    }
    return fullName || pseudo || '';
  }, [selectedCharacter]);

  const canModerateCharacterSheet = useMemo(() => {
    if (!user || !thread?.character) return false;

    const roles = Array.isArray(user.roles) ? user.roles : [];
    return (
      roles.includes('ROLE_SUPER_ADMIN')
      || roles.includes('ROLE_ADMIN')
      || roles.includes('ROLE_MODERATOR')
    );
  }, [user, thread]);

  const canEditPost = (post) => Boolean(user && post && String(user.id) === String(post.authorId));
  const canDeletePost = (post) => Boolean(
    user
    && post
    && (
      String(user.id) === String(post.authorId)
      || user.roles?.includes('ROLE_MODERATOR')
      || user.roles?.includes('ROLE_ADMIN')
    )
  );

  if (loading) {
    return (
      <Layout wide>
        <Loading message="Chargement de la discussion..." />
      </Layout>
    );
  }

  if (error || !thread) {
    return (
      <Layout wide>
        <ErrorMessage
          message={error || 'Discussion introuvable'}
          onRetry={() => {
            setError(null);
            setLoading(true);
            setPosts([]);
            fetchThread(1, true);
          }}
        />
      </Layout>
    );
  }

  const breadcrumbItems = thread.breadcrumb || [];

  const handleCreateOrUpdatePost = async () => {
    if (!composerContent.trim()) return;
    setPosting(true);
    try {
      if (editingPostId) {
        await postService.updatePost(editingPostId, { content: composerContent });
      } else {
        const { characterId, npcId } = parseActorKeyForPost(selectedCharacterId);
        await postService.createPost({
          threadId: thread.threadId,
          content: composerContent,
          characterId: isRoleplayThread ? characterId : null,
          npcId: isRoleplayThread ? npcId : null,
          quotedPostId: quotedPostId || null,
        });
      }
      setComposerContent('');
      setQuotedPostId(null);
      setEditingPostId(null);
      window.localStorage.removeItem(draftStorageKey);
      setDraftSavedAt(null);
      setDraftMessage('');
      await fetchThread(1, true);
    } catch (err) {
      setError(err.response?.data?.error || 'Erreur lors de la publication');
    } finally {
      setPosting(false);
    }
  };

  const handleDeletePost = async (post) => {
    if (!window.confirm('Supprimer ce post ?')) return;
    await postService.deletePost(post.postId);
    await fetchThread(1, true);
  };

  const handleQuotePost = async (post) => {
    const data = await postService.quotePost(post.postId);
    setComposerContent((prev) => `${prev}${data.content}`);
    setQuotedPostId(data.quotedPostId);
  };

  const handleEditPost = (post, paragraphIndex = null) => {
    if (typeof paragraphIndex === 'number') {
      setInlineEditingPostId(post.postId);
      setInlineEditingParagraphIndex(paragraphIndex);
      return;
    }

    setInlineEditingPostId(null);
    setInlineEditingParagraphIndex(null);
    setEditingPostId(post.postId);
    setComposerContent(post.content || '');
    setQuotedPostId(null);
    if (post.character?.id) {
      setSelectedCharacterId(actorKeyFromPostCharacter(post.character));
    }
    setDraftMessage('Edition complète du post dans le composer');
    window.requestAnimationFrame(() => {
      composerSectionRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  };

  const handleInlineSavePost = async (post, updatedContent) => {
    await postService.updatePost(post.postId, { content: updatedContent });
    setInlineEditingPostId(null);
    setInlineEditingParagraphIndex(null);
    await fetchThread(1, true);
  };

  const handleInlineCancelPost = () => {
    setInlineEditingPostId(null);
    setInlineEditingParagraphIndex(null);
  };

  const handleCreateDialogueTheme = async () => {
    const name = newDialogueThemeName.trim();
    if (!name) {
      setDialogueThemeMessage('Le nom du thème est requis');
      return;
    }

    setCreatingDialogueTheme(true);
    setDialogueThemeMessage('');

    try {
      const payload = {
        name,
        color: newDialogueThemeColor,
        fontFamily: newDialogueThemeFontFamily.trim() || null,
        isBold: newDialogueThemeBold,
        isItalic: newDialogueThemeItalic,
        isDefault: dialogueThemes.length === 0,
      };
      const response = await dialogueThemeService.createTheme(payload);
      const createdTheme = response?.item;

      if (createdTheme) {
        setDialogueThemes((prev) => {
          const next = payload.isDefault
            ? prev.map((theme) => ({ ...theme, isDefault: false }))
            : prev;
          return [...next, createdTheme];
        });
        setActiveDialogueThemeId(String(createdTheme.id));
        setNewDialogueThemeName('');
        setNewDialogueThemeFontFamily('');
        setNewDialogueThemeBold(false);
        setNewDialogueThemeItalic(false);
        setDialogueThemeMessage('Thème dialogue enregistré');
      }
    } catch (err) {
      setDialogueThemeMessage(err.response?.data?.error || 'Impossible d enregistrer le thème');
    } finally {
      setCreatingDialogueTheme(false);
    }
  };

  const handleDeleteDialogueTheme = async (themeId) => {
    try {
      await dialogueThemeService.deleteTheme(themeId);
      setDialogueThemes((prev) => prev.filter((theme) => theme.id !== themeId));
      setActiveDialogueThemeId((prev) => (String(prev) === String(themeId) ? '' : prev));
      setDialogueThemeMessage('Thème supprimé');
    } catch (err) {
      setDialogueThemeMessage(err.response?.data?.error || 'Impossible de supprimer le thème');
    }
  };

  const handleSetDefaultDialogueTheme = async (theme) => {
    try {
      const response = await dialogueThemeService.updateTheme(theme.id, {
        name: theme.name,
        color: theme.color,
        fontFamily: theme.fontFamily || null,
        isBold: !!theme.isBold,
        isItalic: !!theme.isItalic,
        isDefault: true,
      });
      const updatedTheme = response?.item;
      if (updatedTheme) {
        setDialogueThemes((prev) => prev.map((item) => ({
          ...item,
          isDefault: item.id === updatedTheme.id,
        })));
        setActiveDialogueThemeId(String(updatedTheme.id));
        setDialogueThemeMessage('Thème par défaut mis à jour');
      }
    } catch (err) {
      setDialogueThemeMessage(err.response?.data?.error || 'Impossible de changer le thème par défaut');
    }
  };

  const openModerationForm = (action) => {
    setModerationAction(action);
    setModerationMessage('');
  };

  const cancelModerationForm = () => {
    setModerationAction(null);
    setModerationMessage('');
  };

  const handleCharacterModeration = async (action, message = '') => {
    if (!thread?.character?.id || moderatingCharacter) return;

    const characterId = thread.character.id;
    setModeratingCharacter(true);

    try {
      if (action === 'validate') {
        await characterApi.validateCharacter(characterId, message.trim() || '');
      } else if (action === 'reject') {
        await characterApi.rejectCharacter(
          characterId,
          message.trim() || 'Fiche refusée par la modération',
          ''
        );
      } else if (action === 'needsRevision') {
        await characterApi.needsRevision(
          characterId,
          message.trim() || 'Merci de corriger les points signalés par la modération.',
          [],
          {}
        );
      }

      cancelModerationForm();
      await fetchThread(1, true);
    } catch (err) {
      setError(err.response?.data?.error || 'Erreur lors de la modération de la fiche');
    } finally {
      setModeratingCharacter(false);
    }
  };

  // Affichage spécifique pour les fiches de personnage
  if (thread.isCharacterSheet && thread.character) {
    const character = thread.character;
    const currentTheme = character.sheetTheme || 'default';
    
    // Définir les onglets disponibles selon le contenu (sans les informations générales)
    const tabs = [
      { id: 'biography', label: 'Biographie', hasContent: !!character.biography },
      { id: 'personality', label: 'Personnalité', hasContent: !!character.personality },
      { id: 'appearance', label: 'Apparence', hasContent: !!character.appearance },
      { id: 'abilities', label: 'Capacités', hasContent: !!character.abilities },
      { id: 'equipment', label: 'Équipements', hasContent: !!character.equipment },
      { id: 'weaknesses', label: 'Faiblesses', hasContent: !!character.weaknesses },
    ].filter(tab => tab.hasContent);
    
    // Définir l'onglet actif par défaut (premier onglet disponible ou 'biography')
    const defaultTab = tabs.length > 0 ? tabs[0].id : 'biography';
    const currentActiveTab = tabs.some(tab => tab.id === activeTab) ? activeTab : defaultTab;
    
    const canEditCharacter = user && (user.id === thread.authorId || user.roles?.includes('ROLE_MODERATOR') || user.roles?.includes('ROLE_ADMIN'));

    return (
      <Layout wide>
        <SeoHead seo={seo} />
        <div className={styles.content}>
          <Breadcrumb items={breadcrumbItems} />

          <div className={styles.characterHeader}>
            <div className={styles.characterHeaderWithAvatar}>
              <div className={styles.characterHeaderAvatar}>
                {character.avatar ? (
                  <img src={character.avatar} alt={character.name} />
                ) : (
                  <div className={styles.characterHeaderAvatarPlaceholder} aria-hidden="true">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                      <circle cx="12" cy="7" r="4" />
                    </svg>
                  </div>
                )}
              </div>
              <div className={styles.characterHeaderBody}>
                <div className={styles.characterHeaderTop}>
                  <div className={styles.characterTitleSection}>
                    <h1 className={styles.characterTitle}>{character.name}</h1>
                    {(character.firstName || character.lastName) && (
                      <p className={styles.characterSubtitle}>
                        {[character.firstName, character.lastName].filter(Boolean).join(' ')}
                      </p>
                    )}
                    {character.alias && (
                      <p className={styles.characterAlias}>{character.alias}</p>
                    )}
                  </div>
                  <div className={styles.characterHeaderActions}>
                    {character.status && (
                      <span className={`${styles.statusTag} ${styles[`status${character.status.charAt(0).toUpperCase() + character.status.slice(1)}`]}`}>
                        {character.status === 'pending' ? 'En attente' :
                         character.status === 'editing' ? 'Points à modifier' :
                         character.status === 'rejected' ? 'Refusée' :
                         character.status === 'draft' ? 'Brouillon' :
                         character.status === 'validated' ? 'Validée' : character.statusMessage || character.status}
                      </span>
                    )}
                    {canEditCharacter && (
                      <Link
                        to={`/characters/${character.id}/edit`}
                        className={styles.actionBtnCompact}
                        title="Modifier la fiche"
                      >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                        </svg>
                      </Link>
                    )}
                    {canModerateCharacterSheet && (
                      <>
                        <button
                          type="button"
                          className={`${styles.moderationBtn} ${styles.moderationBtnValidate}`}
                          title="Valider la fiche"
                          onClick={() => openModerationForm('validate')}
                          disabled={moderatingCharacter}
                        >
                          Valider
                        </button>
                        <button
                          type="button"
                          className={styles.moderationBtn}
                          title="Demander des modifications"
                          onClick={() => openModerationForm('needsRevision')}
                          disabled={moderatingCharacter}
                        >
                          Corriger
                        </button>
                        <button
                          type="button"
                          className={`${styles.moderationBtn} ${styles.moderationBtnReject}`}
                          title="Refuser la fiche"
                          onClick={() => openModerationForm('reject')}
                          disabled={moderatingCharacter}
                        >
                          Refuser
                        </button>
                      </>
                    )}
                  </div>
                </div>
                {(character.universe || character.moralAffiliation || character.elseworld) && (
                  <div className={styles.characterMetaRow}>
                    {character.universe && (
                      <span className={styles.metaTag}>{character.universe.name}</span>
                    )}
                    {character.moralAffiliation && (
                      <span className={styles.metaTag}>{character.moralAffiliation}</span>
                    )}
                    {character.elseworld && (
                      <span className={styles.metaTag}>{character.elseworld.name}</span>
                    )}
                  </div>
                )}
              </div>
            </div>
          </div>

          {canModerateCharacterSheet && moderationAction && (
            <div className={styles.moderationPanel}>
              <h3 className={styles.moderationPanelTitle}>
                {moderationAction === 'validate'
                  ? 'Valider la fiche'
                  : moderationAction === 'reject'
                    ? 'Refuser la fiche'
                    : 'Demander des corrections'}
              </h3>
              <div className={styles.formGroup}>
                <label className={styles.formLabel} htmlFor="moderation-message">
                  {moderationAction === 'validate'
                    ? 'Message de validation (optionnel)'
                    : moderationAction === 'reject'
                      ? 'Raison du refus'
                      : 'Points à corriger'}
                </label>
                <textarea
                  id="moderation-message"
                  className={styles.formTextarea}
                  rows={4}
                  value={moderationMessage}
                  onChange={(event) => setModerationMessage(event.target.value)}
                  placeholder={
                    moderationAction === 'validate'
                      ? 'Ex: Validation OK, bienvenue sur le forum...'
                      : moderationAction === 'reject'
                        ? 'Explique pourquoi la fiche est refusée...'
                        : 'Liste les modifications attendues...'
                  }
                />
              </div>
              <div className={styles.composerActions}>
                <button
                  type="button"
                  className={styles.modalBtnCancel}
                  onClick={cancelModerationForm}
                  disabled={moderatingCharacter}
                >
                  Annuler
                </button>
                <button
                  type="button"
                  className={`${styles.moderationActionBtn} ${
                    moderationAction === 'validate'
                      ? styles.moderationActionBtnValidate
                      : moderationAction === 'reject'
                        ? styles.moderationActionBtnReject
                        : styles.moderationActionBtnNeedsRevision
                  }`}
                  onClick={() => handleCharacterModeration(moderationAction, moderationMessage)}
                  disabled={moderatingCharacter}
                >
                  {moderatingCharacter ? 'Envoi...' : 'Confirmer'}
                </button>
              </div>
            </div>
          )}

          <div className={styles.characterSheet} data-theme={currentTheme}>
            <div className={styles.characterInfo}>
              {(() => {
                const generalInfoFields = [
                  character.universe && { label: 'Univers', value: character.universe.name },
                  character.elseworld && { label: 'Elseworld', value: character.elseworld.name },
                  character.age && { label: 'Âge', value: character.age },
                  character.gender && { label: 'Genre', value: character.gender },
                  character.moralAffiliation && { label: 'Affiliation morale', value: character.moralAffiliation },
                  character.occupation && { label: 'Occupation', value: character.occupation },
                  character.sexualOrientation && { label: 'Orientation sexuelle', value: character.sexualOrientation },
                  character.civilStatus && { label: 'Statut civil', value: character.civilStatus },
                  character.factions && { label: 'Factions', value: character.factions },
                  character.actualPseudo && { label: 'Pseudonyme actuel', value: character.actualPseudo },
                  character.pseudonyms && { label: 'Autres alias', value: character.pseudonyms },
                  character.alias && { label: 'Alias', value: character.alias },
                ].filter(Boolean);

                if (generalInfoFields.length === 0) return null;

                return (
                  <section className={styles.generalInfoSection}>
                    <h2 className={styles.generalInfoTitle}>Informations générales</h2>
                    <dl className={styles.infoList}>
                      {generalInfoFields.map((field) => (
                        <div key={field.label} className={styles.infoListRow}>
                          <dt className={styles.infoLabel}>{field.label}</dt>
                          <dd className={styles.infoValue}>{field.value}</dd>
                        </div>
                      ))}
                    </dl>
                  </section>
                );
              })()}

              {tabs.length > 0 && (
                <>
                  <div className={styles.tabsWrapper}>
                    <div className={styles.tabs} role="tablist">
                      {tabs.map((tab) => (
                        <button
                          key={tab.id}
                          type="button"
                          role="tab"
                          aria-selected={currentActiveTab === tab.id}
                          className={`${styles.tab} ${currentActiveTab === tab.id ? styles.tabActive : ''}`}
                          onClick={() => setActiveTab(tab.id)}
                        >
                          {tab.label}
                        </button>
                      ))}
                    </div>
                  </div>

                  <div className={styles.tabContent} role="tabpanel">
                    {currentActiveTab === 'biography' && character.biography && (
                      <div className={styles.characterSection}>
                        <div className={styles.sectionContent} dangerouslySetInnerHTML={{ __html: character.biography }} />
                      </div>
                    )}

                    {currentActiveTab === 'personality' && character.personality && (
                      <div className={styles.characterSection}>
                        <div className={styles.sectionContent} dangerouslySetInnerHTML={{ __html: character.personality }} />
                      </div>
                    )}

                    {currentActiveTab === 'appearance' && character.appearance && (
                      <div className={styles.characterSection}>
                        <div className={styles.sectionContent} dangerouslySetInnerHTML={{ __html: character.appearance }} />
                      </div>
                    )}

                    {currentActiveTab === 'abilities' && character.abilities && (
                      <div className={styles.characterSection}>
                        <div className={styles.sectionContent} dangerouslySetInnerHTML={{ __html: character.abilities }} />
                      </div>
                    )}

                    {currentActiveTab === 'equipment' && character.equipment && (
                      <div className={styles.characterSection}>
                        <div className={styles.sectionContent} dangerouslySetInnerHTML={{ __html: character.equipment }} />
                      </div>
                    )}

                    {currentActiveTab === 'weaknesses' && character.weaknesses && (
                      <div className={styles.characterSection}>
                        <div className={styles.sectionContent} dangerouslySetInnerHTML={{ __html: character.weaknesses }} />
                      </div>
                    )}
                  </div>
                </>
              )}
            </div>
          </div>

          {posts && posts.length > 1 && (
            <section className={styles.posts}>
              <h2 className={styles.sectionTitle}>Commentaires</h2>
              <div className={styles.postsList}>
                {posts.slice(1).map((post) => {
                  // Formater la date pour l'affichage
                  const formattedPost = {
                    ...post,
                    date: post.date ? new Date(post.date).toLocaleDateString('fr-FR', {
                      day: '2-digit',
                      month: '2-digit',
                      year: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit'
                    }) : post.date
                  };
                  
                  return (
                    <PostCard
                      key={post.postId}
                      post={formattedPost}
                      isRoleplay={thread.isRoleplay || thread.type === 'roleplay'}
                      character={post.character}
                      canEdit={canEditPost(post)}
                      canDelete={canDeletePost(post)}
                      isInlineEditing={inlineEditingPostId === post.postId}
                      inlineParagraphIndex={inlineEditingPostId === post.postId ? inlineEditingParagraphIndex : null}
                      onInlineSave={handleInlineSavePost}
                      onInlineCancel={handleInlineCancelPost}
                    />
                  );
                })}
              </div>
            </section>
          )}
        </div>
      </Layout>
    );
  }

  return (
    <Layout wide>
      <SeoHead seo={seo} />
      <div className={styles.content}>
        <Breadcrumb items={breadcrumbItems} />

        <div className={styles.threadHeader}>
          <div className={styles.threadHeaderContent}>
            <div className={styles.threadHeaderMain}>
              <h1 className={styles.threadTitle}>{thread.title}</h1>
          <div className={styles.threadMeta}>
                <div className={styles.authorSection}>
                  {thread.authorAvatar && (
                    <img src={thread.authorAvatar} alt={thread.author} className={styles.authorAvatar} />
                  )}
                  <div>
                    <span className={styles.authorLabel}>Créé par</span>
                    <span className={styles.authorName}>{thread.author}</span>
                  </div>
                </div>
                <div className={styles.dateSection}>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <circle cx="12" cy="12" r="10" />
                    <polyline points="12 6 12 12 16 14" />
                  </svg>
                  <span>{new Date(thread.createdAt).toLocaleDateString('fr-FR', { 
                    day: 'numeric', 
                    month: 'long', 
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                  })}</span>
                </div>
              </div>
            </div>
            <div className={styles.forumBadge}>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
              </svg>
              <span>{thread.forum?.name || 'Forum'}</span>
            </div>
          </div>
        </div>

        {Array.isArray(thread.rpActivities) && thread.rpActivities.length > 0 && thread.rpActivities.map((activity) => (
          <RpActivityCard
            key={activity.id}
            activity={activity}
            selectableCharacters={availableCharacters.filter((actor) => actor.entityType !== 'npc')}
            onRegistrationChanged={() => { void fetchThread(1, true); }}
            showRegistration={false}
          />
        ))}

        {isRoleplayThread && (
          <section className={styles.participantsPanel}>
            <div className={styles.participantsHeader}>
              <h2 className={styles.participantsTitle}>Participants</h2>
              <span className={styles.participantsCount}>{participantsLabel}</span>
            </div>

            <div className={styles.participantsList}>
              {thread.participants?.length > 0 ? thread.participants.map((participant) => (
                <article key={participant.id} className={styles.participantItem}>
                  {participant.avatar ? (
                    <img src={participant.avatar} alt={participant.name} className={styles.participantAvatar} />
                  ) : (
                    <span className={styles.participantAvatarFallback}>
                      {(participant.name || '?').charAt(0).toUpperCase()}
                    </span>
                  )}
                  <span className={styles.participantName}>{participant.name}</span>
                </article>
              )) : (
                <p className={styles.participantsEmpty}>Aucun participant renseigné.</p>
              )}
            </div>

          </section>
        )}

        <div className={styles.posts}>
            <div className={styles.postsList}>
              {posts.map((post) => {
                // Formater la date pour l'affichage
                const formattedPost = {
                  ...post,
                  date: post.date ? new Date(post.date).toLocaleDateString('fr-FR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                  }) : post.date
                };
                
                return (
                  <PostCard
                    key={post.postId}
                    post={formattedPost}
                    isRoleplay={thread.isRoleplay || thread.type === 'roleplay'}
                    character={post.character}
                    canEdit={canEditPost(post)}
                    canDelete={canDeletePost(post)}
                    onQuote={user ? handleQuotePost : null}
                    onEdit={user ? handleEditPost : null}
                    onDelete={user ? handleDeletePost : null}
                    isInlineEditing={inlineEditingPostId === post.postId}
                    inlineParagraphIndex={inlineEditingPostId === post.postId ? inlineEditingParagraphIndex : null}
                    onInlineSave={handleInlineSavePost}
                    onInlineCancel={handleInlineCancelPost}
                  />
                );
              })}
            </div>
        </div>

        {postsPagination?.hasNext && (
          <button className={styles.loadMoreButton} onClick={() => fetchThread(currentPage + 1, false)}>
            Charger plus de posts
          </button>
        )}

        {user && (
          <section ref={composerSectionRef} className={styles.composerSection}>
            <h2 className={styles.sectionTitle}>{editingPostId ? 'Modifier le post' : 'Repondre'}</h2>
            {isRoleplayThread && (
              <select
                value={selectedCharacterId}
                onChange={(event) => {
                  setSelectedCharacterId(event.target.value);
                  setAutoCharacterPrefillDone(true);
                }}
                className={styles.actorSelect}
              >
                <option value="">Selectionner un personnage</option>
                {availableCharacters.map((character) => (
                  <option key={character._actorKey || character.id} value={character._actorKey || `${ACTOR_KEY_CHAR}${character.id}`}>
                    {character.entityType === 'npc'
                      ? `[PNJ] ${character.name}`
                      : character.kind === 'event'
                        ? `[Event] ${character.name}`
                        : character.name}
                  </option>
                ))}
              </select>
            )}
            {isRoleplayThread && selectedCharacter && (
              <div className={styles.selectedCharacterHeader}>
                <div className={styles.selectedCharacterMain}>
                  {selectedCharacter.avatar ? (
                    <img
                      src={selectedCharacter.avatar}
                      alt={selectedCharacter.name}
                      className={styles.selectedCharacterAvatar}
                    />
                  ) : (
                    <div className={styles.selectedCharacterAvatarFallback}>
                      {selectedCharacter.name?.slice(0, 1) || '?'}
                    </div>
                  )}
                  <div className={styles.selectedCharacterInfo}>
                    <p className={styles.selectedCharacterName}>{selectedCharacter.name}</p>
                    {selectedCharacterIdentity && (
                      <p className={styles.selectedCharacterIdentity}>{selectedCharacterIdentity}</p>
                    )}
                    {selectedCharacter.entityType !== 'npc' && selectedCharacter.alias && (
                      <p className={styles.selectedCharacterAlias}>{selectedCharacter.alias}</p>
                    )}
                  </div>
                </div>
                <div className={styles.selectedCharacterMeta}>
                  {selectedCharacter.entityType === 'npc' && (
                    <span className={styles.selectedCharacterTag}>PNJ (faction)</span>
                  )}
                  {(selectedCharacter.moralAffiliation || selectedCharacter.moralAlignment) && (
                    <span className={styles.selectedCharacterTag}>{selectedCharacter.moralAffiliation || selectedCharacter.moralAlignment}</span>
                  )}
                  {selectedCharacter.occupation && (
                    <span className={styles.selectedCharacterTag}>{selectedCharacter.occupation}</span>
                  )}
                  {selectedCharacter.age && (
                    <span className={styles.selectedCharacterTag}>{selectedCharacter.age} ans</span>
                  )}
                  {selectedCharacter.gender && (
                    <span className={styles.selectedCharacterTag}>{selectedCharacter.gender}</span>
                  )}
                  {selectedCharacter.factions && (
                    <span className={styles.selectedCharacterTag}>
                      {Array.isArray(selectedCharacter.factions)
                        ? selectedCharacter.factions.join(', ')
                        : selectedCharacter.factions}
                    </span>
                  )}
                </div>
              </div>
            )}
            {isRoleplayThread && (
              <div className={styles.dialogueThemesPanel}>
                <div className={styles.dialogueThemesHeader}>
                  <button
                    type="button"
                    className={styles.dialogueThemesToggle}
                    onClick={() => setIsDialogueThemesOpen((prev) => !prev)}
                    aria-expanded={isDialogueThemesOpen}
                    aria-controls="dialogue-themes-content"
                  >
                    <span>
                      <span className={styles.dialogueThemesTitle}>Thèmes de dialogue</span>
                      <span className={styles.dialogueThemesActiveLine}>
                        Thème sélectionné:{' '}
                        {selectedDialogueTheme ? (
                          <strong
                            className={styles.dialogueThemesActiveName}
                            style={{
                              color: selectedDialogueTheme.color,
                              fontFamily: selectedDialogueTheme.fontFamily || undefined,
                              fontWeight: selectedDialogueTheme.isBold ? 700 : 400,
                              fontStyle: selectedDialogueTheme.isItalic ? 'italic' : 'normal',
                            }}
                          >
                            {selectedDialogueTheme.name}
                          </strong>
                        ) : (
                          <strong className={styles.dialogueThemesActiveName}>Aucun</strong>
                        )}
                      </span>
                    </span>
                    <span className={`${styles.dialogueThemesChevron} ${isDialogueThemesOpen ? styles.dialogueThemesChevronOpen : ''}`}>
                      ▾
                    </span>
                  </button>
                </div>
                {isDialogueThemesOpen && (
                  <div id="dialogue-themes-content" className={styles.dialogueThemesContent}>
                    {dialogueThemes.length > 0 ? (
                      <div className={styles.dialogueThemeSelectorBlock}>
                        <label className={styles.dialogueThemeFieldLabel} htmlFor="dialogue-theme-existing">
                          Thème existant
                        </label>
                        <div className={styles.dialogueThemeSelectorRow}>
                          <select
                            id="dialogue-theme-existing"
                            className={styles.dialogueThemeSelect}
                            value={activeDialogueThemeId || ''}
                            onChange={(event) => setActiveDialogueThemeId(event.target.value)}
                          >
                            {dialogueThemes.map((theme) => (
                              <option key={theme.id} value={theme.id}>
                                {theme.name}{theme.isDefault ? ' (defaut)' : ''}
                              </option>
                            ))}
                          </select>
                          <button
                            type="button"
                            className={styles.dialogueThemeAction}
                            onClick={() => selectedDialogueTheme && handleSetDefaultDialogueTheme(selectedDialogueTheme)}
                            disabled={!selectedDialogueTheme || selectedDialogueTheme.isDefault}
                          >
                            Defaut
                          </button>
                          <button
                            type="button"
                            className={styles.dialogueThemeAction}
                            onClick={() => selectedDialogueTheme && handleDeleteDialogueTheme(selectedDialogueTheme.id)}
                            disabled={!selectedDialogueTheme}
                          >
                            Supprimer
                          </button>
                        </div>
                        {selectedDialogueTheme && (
                          <p
                            className={styles.dialogueThemePreviewLine}
                            style={{
                              color: selectedDialogueTheme.color,
                              fontFamily: selectedDialogueTheme.fontFamily || undefined,
                              fontWeight: selectedDialogueTheme.isBold ? 700 : 400,
                              fontStyle: selectedDialogueTheme.isItalic ? 'italic' : 'normal',
                            }}
                          >
                            Aperçu: "{selectedDialogueTheme.name}" actif
                          </p>
                        )}
                      </div>
                    ) : (
                      <p className={styles.dialogueThemesEmpty}>
                        Aucun thème enregistré. Crée ton premier thème de dialogue.
                      </p>
                    )}
                    <div className={styles.dialogueThemeForm}>
                      <div className={styles.dialogueThemeField}>
                        <label className={styles.dialogueThemeFieldLabel} htmlFor="dialogue-theme-name">
                          Nom du thème
                        </label>
                        <input
                          id="dialogue-theme-name"
                          type="text"
                          className={styles.dialogueThemeInput}
                          value={newDialogueThemeName}
                          onChange={(event) => setNewDialogueThemeName(event.target.value)}
                          placeholder="Ex: Damian, Mera, Alfred..."
                        />
                      </div>
                      <div className={styles.dialogueThemeField}>
                        <label className={styles.dialogueThemeFieldLabel} htmlFor="dialogue-theme-color">
                          Couleur
                        </label>
                        <input
                          id="dialogue-theme-color"
                          type="color"
                          className={styles.dialogueThemeColor}
                          value={newDialogueThemeColor}
                          onChange={(event) => setNewDialogueThemeColor(event.target.value)}
                          aria-label="Couleur du thème"
                        />
                      </div>
                      <div className={styles.dialogueThemeField}>
                        <label className={styles.dialogueThemeFieldLabel} htmlFor="dialogue-theme-font">
                          Police (optionnel)
                        </label>
                        <input
                          id="dialogue-theme-font"
                          type="text"
                          className={styles.dialogueThemeInput}
                          value={newDialogueThemeFontFamily}
                          onChange={(event) => setNewDialogueThemeFontFamily(event.target.value)}
                          placeholder="Ex: Georgia, Impact, Times New Roman"
                        />
                      </div>
                      <div className={styles.dialogueThemeChecks}>
                        <label className={styles.dialogueThemeCheckbox}>
                          <input
                            type="checkbox"
                            checked={newDialogueThemeBold}
                            onChange={(event) => setNewDialogueThemeBold(event.target.checked)}
                          />
                          <span>Gras</span>
                        </label>
                        <label className={styles.dialogueThemeCheckbox}>
                          <input
                            type="checkbox"
                            checked={newDialogueThemeItalic}
                            onChange={(event) => setNewDialogueThemeItalic(event.target.checked)}
                          />
                          <span>Italique</span>
                        </label>
                      </div>
                      <div className={styles.dialogueThemeSubmitRow}>
                        <button
                          type="button"
                          className={styles.dialogueThemeCreateButton}
                          onClick={handleCreateDialogueTheme}
                          disabled={creatingDialogueTheme}
                        >
                          {creatingDialogueTheme ? 'Ajout...' : 'Ajouter le thème'}
                        </button>
                      </div>
                    </div>
                    {dialogueThemeMessage && <p className={styles.dialogueThemeMessage}>{dialogueThemeMessage}</p>}
                  </div>
                )}
              </div>
            )}
            <RichTextComposer
              value={composerContent}
              onChange={setComposerContent}
              onQuote={() => setComposerContent((prev) => `${prev}<blockquote><p>Citation...</p></blockquote><p><br></p>`)}
              dialogueThemes={dialogueThemes}
              activeDialogueThemeId={activeDialogueThemeId}
              onActiveDialogueThemeIdChange={setActiveDialogueThemeId}
            />
            {isRoleplayThread && !selectedCharacterId && !editingPostId && (
              <p className={styles.publishHint}>
                Selectionne un personnage pour publier dans ce thread RP.
              </p>
            )}
            {draftMessage && (
              <p className={styles.draftStatus}>
                {draftMessage}
                {draftSavedAt ? ` - ${new Date(draftSavedAt).toLocaleTimeString('fr-FR')}` : ''}
              </p>
            )}
            <div className={styles.composerActions}>
              <button className={styles.draftButton} onClick={() => saveDraft(false)} type="button">
                Enregistrer le brouillon
              </button>
              <button className={styles.submitButton} onClick={handleCreateOrUpdatePost} disabled={posting || (isRoleplayThread && !selectedCharacterId && !editingPostId)}>
                {posting ? 'Publication...' : editingPostId ? 'Enregistrer' : 'Publier'}
              </button>
            </div>
          </section>
        )}
      </div>
    </Layout>
  );
};

export default ThreadDetail;

