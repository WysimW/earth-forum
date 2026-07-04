import React, { useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import Layout from '../../components/Layout/Layout';
import Breadcrumb from '../../components/Breadcrumb/Breadcrumb';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import RichTextComposer from '../../components/RichTextComposer/RichTextComposerTiptap';
import rpActivityService from '../../services/rpActivityService';
import dialogueThemeService from '../../services/dialogueThemeService';
import styles from './RpActivityThreadCreate.module.css';

const ACTOR_KEY_CHAR = 'c:';
const ACTOR_KEY_NPC = 'n:';

const RpActivityThreadCreate = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [activity, setActivity] = useState(null);
  const [context, setContext] = useState({ forums: [], characters: [] });
  /** `characters` inclut personnages + PNJ de faction (`entityType`, `_actorKey`) */
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [dialogueThemes, setDialogueThemes] = useState([]);
  const [activeDialogueThemeId, setActiveDialogueThemeId] = useState('');
  const [isDialogueThemesOpen, setIsDialogueThemesOpen] = useState(false);
  const [creatingDialogueTheme, setCreatingDialogueTheme] = useState(false);
  const [dialogueThemeMessage, setDialogueThemeMessage] = useState('');
  const [newDialogueThemeName, setNewDialogueThemeName] = useState('');
  const [newDialogueThemeColor, setNewDialogueThemeColor] = useState('#339999');
  const [newDialogueThemeFontFamily, setNewDialogueThemeFontFamily] = useState('');
  const [newDialogueThemeBold, setNewDialogueThemeBold] = useState(false);
  const [newDialogueThemeItalic, setNewDialogueThemeItalic] = useState(false);
  const [form, setForm] = useState({
    forumId: '',
    authorKey: '',
    title: '',
    content: '',
  });

  const forumTreeOptions = useMemo(() => {
    const forums = Array.isArray(context?.forums) ? context.forums : [];
    if (forums.length === 0) return [];

    const forumsById = new Map(forums.map((forum) => [Number(forum.id), forum]));
    const childrenMap = new Map(forums.map((forum) => [Number(forum.id), []]));
    forums.forEach((forum) => {
      const parentId = Number(forum.parentId || 0);
      if (parentId > 0 && childrenMap.has(parentId)) {
        childrenMap.get(parentId).push(forum);
      }
    });
    const roots = forums.filter((forum) => {
      const parentId = Number(forum.parentId || 0);
      return !(parentId > 0 && forumsById.has(parentId));
    });

    const flattened = [];
    const walk = (forum, depth) => {
      flattened.push({
        id: String(forum.id),
        label: `${depth > 0 ? `${'— '.repeat(depth)}` : ''}${forum.name}`,
      });
      const children = childrenMap.get(Number(forum.id)) || [];
      children.forEach((child) => walk(child, depth + 1));
    };
    roots.forEach((root) => walk(root, 0));
    return flattened;
  }, [context?.forums]);

  const selectedAuthor = useMemo(() => {
    if (!form.authorKey) return null;
    return context.characters.find((c) => c._actorKey === form.authorKey) || null;
  }, [context.characters, form.authorKey]);

  const selectedDialogueTheme = useMemo(() => {
    if (!activeDialogueThemeId) return null;
    return dialogueThemes.find((theme) => String(theme.id) === String(activeDialogueThemeId)) || null;
  }, [dialogueThemes, activeDialogueThemeId]);

  useEffect(() => {
    const loadPage = async () => {
      setLoading(true);
      setError('');
      try {
        const [activityData, contextData, selectableData, themesData] = await Promise.all([
          rpActivityService.getActivity(id),
          rpActivityService.getThreadContext(id),
          rpActivityService.getSelectableCharacters(id),
          dialogueThemeService.listThemes().catch(() => ({ items: [], defaultThemeId: '' })),
        ]);

        if (!activityData?.permissions?.canEdit) {
          setError('Tu ne peux pas créer de sujet pour cet event.');
          setActivity(null);
          return;
        }

        const forums = Array.isArray(contextData?.forums) ? contextData.forums : [];
        const items = Array.isArray(selectableData?.items) ? selectableData.items : [];
        const npcs = Array.isArray(selectableData?.npcs) ? selectableData.npcs : [];
        const characters = [
          ...items.map((c) => ({ ...c, _actorKey: `${ACTOR_KEY_CHAR}${c.id}` })),
          ...npcs.map((n) => ({ ...n, _actorKey: `${ACTOR_KEY_NPC}${n.id}` })),
        ];
        setActivity(activityData);
        setContext({ forums, characters });
        setForm((prev) => ({
          ...prev,
          forumId: forums[0]?.id ? String(forums[0].id) : '',
          authorKey: characters[0]?._actorKey || '',
          title: `${activityData.title} — Sujet RP`,
        }));

        const themes = Array.isArray(themesData?.items) ? themesData.items : [];
        setDialogueThemes(themes);
        const defaultThemeId = themesData?.defaultThemeId || themes.find((theme) => theme.isDefault)?.id || themes[0]?.id || '';
        setActiveDialogueThemeId(defaultThemeId ? String(defaultThemeId) : '');
      } catch (err) {
        setError(err.response?.data?.error || 'Impossible de charger la création de sujet RP.');
      } finally {
        setLoading(false);
      }
    };

    loadPage();
  }, [id]);

  const handleCreateDialogueTheme = async () => {
    const name = newDialogueThemeName.trim();
    if (!name) {
      setDialogueThemeMessage('Le nom du thème est requis.');
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
      if (!createdTheme) return;

      setDialogueThemes((prev) => {
        const next = payload.isDefault ? prev.map((theme) => ({ ...theme, isDefault: false })) : prev;
        return [...next, createdTheme];
      });
      setActiveDialogueThemeId(String(createdTheme.id));
      setNewDialogueThemeName('');
      setNewDialogueThemeFontFamily('');
      setNewDialogueThemeBold(false);
      setNewDialogueThemeItalic(false);
      setDialogueThemeMessage('Thème créé.');
    } catch (err) {
      setDialogueThemeMessage(err.response?.data?.error || 'Impossible de créer le thème.');
    } finally {
      setCreatingDialogueTheme(false);
    }
  };

  const handleDeleteDialogueTheme = async (themeId) => {
    try {
      await dialogueThemeService.deleteTheme(themeId);
      setDialogueThemes((prev) => prev.filter((theme) => theme.id !== themeId));
      setActiveDialogueThemeId((prev) => (String(prev) === String(themeId) ? '' : prev));
      setDialogueThemeMessage('Thème supprimé.');
    } catch (err) {
      setDialogueThemeMessage(err.response?.data?.error || 'Impossible de supprimer le thème.');
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
      if (!updatedTheme) return;

      setDialogueThemes((prev) => prev.map((item) => ({
        ...item,
        isDefault: item.id === updatedTheme.id,
      })));
      setActiveDialogueThemeId(String(updatedTheme.id));
      setDialogueThemeMessage('Thème par défaut mis à jour.');
    } catch (err) {
      setDialogueThemeMessage(err.response?.data?.error || 'Impossible de définir le thème par défaut.');
    }
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    const text = (new DOMParser().parseFromString(form.content || '', 'text/html').body.textContent || '').replace(/\u00a0/g, ' ').trim();
    if (!form.forumId || !form.authorKey || !form.title.trim() || !text) {
      setError('Forum, auteur (personnage ou PNJ), titre et premier message sont requis.');
      return;
    }

    setSaving(true);
    setError('');
    try {
      const base = {
        forumId: Number(form.forumId),
        title: form.title.trim(),
        content: form.content,
      };
      const isNpc = form.authorKey.startsWith(ACTOR_KEY_NPC);
      const idNum = Number(form.authorKey.replace(/^(c:|n:)/, ''));
      const response = await rpActivityService.createThread(id, isNpc
        ? { ...base, npcId: idNum }
        : { ...base, characterId: idNum });
      const slug = response?.thread?.slug || response?.thread?.id;
      if (slug) {
        navigate(`/threads/${slug}`);
        return;
      }
      navigate(`/rp-activities/${id}`);
    } catch (err) {
      setError(err.response?.data?.error || 'Création du sujet impossible.');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <Layout>
        <Loading message="Chargement de la création du sujet RP..." />
      </Layout>
    );
  }

  if (error && !activity) {
    return (
      <Layout>
        <ErrorMessage message={error} onRetry={() => navigate(`/rp-activities/${id}`)} />
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
            { name: activity?.title || 'Event', url: `/rp-activities/${id}` },
            { name: 'Créer un sujet RP', url: null },
          ]}
        />

        <header className={styles.header}>
          <h1 className={styles.title}>Créer un sujet RP</h1>
          <p className={styles.subtitle}>
            Event: {activity?.title}
          </p>
        </header>

        <form className={styles.form} onSubmit={handleSubmit}>
          <label className={styles.field}>
            <span>Forum RP (arborescence)</span>
            <select
              className={styles.input}
              value={form.forumId}
              onChange={(e) => setForm((prev) => ({ ...prev, forumId: e.target.value }))}
            >
              <option value="">Sélectionner</option>
              {forumTreeOptions.map((forum) => (
                <option key={forum.id} value={forum.id}>
                  {forum.label}
                </option>
              ))}
            </select>
          </label>

          <label className={styles.field}>
            <span>Personnage ou PNJ (faction)</span>
            <select
              className={styles.input}
              value={form.authorKey}
              onChange={(e) => setForm((prev) => ({ ...prev, authorKey: e.target.value }))}
            >
              <option value="">Sélectionner</option>
              {context.characters.map((actor) => (
                <option key={actor._actorKey} value={actor._actorKey}>
                  {actor.entityType === 'npc'
                    ? `[PNJ] ${actor.name}`
                    : actor.kind === 'event'
                      ? `[Event] ${actor.name}`
                      : actor.name}
                </option>
              ))}
            </select>
          </label>

          {selectedAuthor && (
            <div className={styles.selectedCharacterHeader}>
              <div className={styles.selectedCharacterMain}>
                {selectedAuthor.avatar ? (
                  <img
                    src={selectedAuthor.avatar}
                    alt={selectedAuthor.name}
                    className={styles.selectedCharacterAvatar}
                  />
                ) : (
                  <div className={styles.selectedCharacterAvatarFallback}>
                    {(selectedAuthor.name || '?').slice(0, 1).toUpperCase()}
                  </div>
                )}
                <div className={styles.selectedCharacterInfo}>
                  <p className={styles.selectedCharacterName}>{selectedAuthor.name}</p>
                  <p className={styles.selectedCharacterMeta}>
                    {selectedAuthor.entityType === 'npc'
                      ? 'PNJ de la faction (mission)'
                      : selectedAuthor.kind === 'event'
                        ? 'Personnage Event'
                        : 'Personnage standard'}
                  </p>
                </div>
              </div>
            </div>
          )}

          <label className={styles.field}>
            <span>Titre du sujet</span>
            <input
              className={styles.input}
              value={form.title}
              onChange={(e) => setForm((prev) => ({ ...prev, title: e.target.value }))}
            />
          </label>

          <div className={styles.field}>
            <span>Premier message RP</span>
            <div className={styles.dialogueThemesPanel}>
              <button
                type="button"
                className={styles.dialogueThemesToggle}
                onClick={() => setIsDialogueThemesOpen((prev) => !prev)}
                aria-expanded={isDialogueThemesOpen}
                aria-controls="rp-dialogue-themes-content"
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

              {isDialogueThemesOpen && (
                <div id="rp-dialogue-themes-content" className={styles.themeEditor}>
                  {dialogueThemes.length > 0 ? (
                    <div className={styles.themeList}>
                      {dialogueThemes.map((theme) => (
                        <div key={theme.id} className={styles.themeItem}>
                          <div className={styles.themeInfo}>
                            <span className={styles.themeColor} style={{ backgroundColor: theme.color || '#666666' }} />
                            <span className={styles.themeName}>{theme.name}</span>
                            {theme.isDefault && <span className={styles.themeBadge}>Défaut</span>}
                          </div>
                          <div className={styles.themeActions}>
                            <button
                              type="button"
                              className={styles.themeActionButton}
                              onClick={() => setActiveDialogueThemeId(String(theme.id))}
                            >
                              Utiliser
                            </button>
                            {!theme.isDefault && (
                              <button
                                type="button"
                                className={styles.themeActionButton}
                                onClick={() => handleSetDefaultDialogueTheme(theme)}
                              >
                                Définir défaut
                              </button>
                            )}
                            <button
                              type="button"
                              className={styles.themeActionDanger}
                              onClick={() => handleDeleteDialogueTheme(theme.id)}
                            >
                              Supprimer
                            </button>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p className={styles.helper}>Aucun thème enregistré.</p>
                  )}

                  <div className={styles.themeCreate}>
                    <input
                      className={styles.input}
                      type="text"
                      placeholder="Nom du thème"
                      value={newDialogueThemeName}
                      onChange={(e) => setNewDialogueThemeName(e.target.value)}
                    />
                    <div className={styles.themeCreateRow}>
                      <input
                        className={styles.colorInput}
                        type="color"
                        value={newDialogueThemeColor}
                        onChange={(e) => setNewDialogueThemeColor(e.target.value)}
                      />
                      <input
                        className={styles.input}
                        type="text"
                        placeholder="Police (optionnel)"
                        value={newDialogueThemeFontFamily}
                        onChange={(e) => setNewDialogueThemeFontFamily(e.target.value)}
                      />
                    </div>
                    <div className={styles.themeCreateChecks}>
                      <label>
                        <input
                          type="checkbox"
                          checked={newDialogueThemeBold}
                          onChange={(e) => setNewDialogueThemeBold(e.target.checked)}
                        />
                        Gras
                      </label>
                      <label>
                        <input
                          type="checkbox"
                          checked={newDialogueThemeItalic}
                          onChange={(e) => setNewDialogueThemeItalic(e.target.checked)}
                        />
                        Italique
                      </label>
                    </div>
                    <button
                      type="button"
                      className={styles.primaryButton}
                      onClick={handleCreateDialogueTheme}
                      disabled={creatingDialogueTheme}
                    >
                      {creatingDialogueTheme ? 'Création...' : 'Créer le thème'}
                    </button>
                    {dialogueThemeMessage && <p className={styles.helper}>{dialogueThemeMessage}</p>}
                  </div>
                </div>
              )}
            </div>
            <RichTextComposer
              value={form.content}
              onChange={(value) => setForm((prev) => ({ ...prev, content: value }))}
              dialogueThemes={dialogueThemes}
              activeDialogueThemeId={activeDialogueThemeId}
              onActiveDialogueThemeIdChange={setActiveDialogueThemeId}
            />
          </div>

          {error && <p className={styles.error}>{error}</p>}

          <div className={styles.actions}>
            <button type="button" className={styles.secondaryButton} onClick={() => navigate(`/rp-activities/${id}`)}>
              Annuler
            </button>
            <button type="submit" className={styles.primaryButton} disabled={saving}>
              {saving ? 'Création...' : 'Créer le sujet RP'}
            </button>
          </div>
        </form>
      </div>
    </Layout>
  );
};

export default RpActivityThreadCreate;
