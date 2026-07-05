import styles from './EditeurBraise.module.css';

/* Option 1a — "Braise" · Éditeur WYSIWYG dark, Comics Earth Forum.
   Composant de maquette statique : le contenu est illustratif,
   branchez votre moteur d'édition (contentEditable, ProseMirror, etc.) dessus. */

const Icon = ({ d, filled = false, size = 15 }) => (
  <svg
    width={size}
    height={size}
    viewBox="0 0 16 16"
    fill={filled ? 'currentColor' : 'none'}
    stroke={filled ? 'none' : 'currentColor'}
    strokeWidth="1.5"
    strokeLinecap="round"
    strokeLinejoin="round"
  >
    {Array.isArray(d) ? d.map((p, i) => <path key={i} d={p} />) : <path d={d} />}
  </svg>
);

const icons = {
  undo: ['M6 4 2.5 7.5 6 11', 'M2.5 7.5H10a3.5 3.5 0 0 1 0 7H8'],
  redo: ['M10 4l3.5 3.5L10 11', 'M13.5 7.5H6a3.5 3.5 0 0 0 0 7h2'],
  chevron: 'M2 3.5 5 6.5 8 3.5',
  quote:
    'M3 9.5C3 6.5 5 4.5 7 4l.4 1c-1.2.5-2 1.4-2.1 2.5H7v4H3v-2zm6 0c0-3 2-5 4-5.5l.4 1c-1.2.5-2 1.4-2.1 2.5H13v4H9v-2z',
  sceneBreak: 'M2 8h3M6.5 8h3M11 8h3',
  bubble:
    'M14 7.5c0 2.8-2.7 5-6 5-.7 0-1.4-.1-2-.3L3 13.5l.7-2.2C2.6 10.3 2 9 2 7.5c0-2.8 2.7-5 6-5s6 2.2 6 5z',
  share: ['M14 6.5v6A1.5 1.5 0 0 1 12.5 14h-9A1.5 1.5 0 0 1 2 12.5v-6', 'M8 10V1.5M4.5 5 8 1.5 11.5 5'],
  comment: [
    'M13 8.8V12a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 2 12V4a1.5 1.5 0 0 1 1.5-1.5h3.2',
    'M11 2.5 13.5 5 8 10.5H5.5V8L11 2.5z',
  ],
};

const ImageGlyph = ({ size = 15, color = 'currentColor' }) => (
  <svg width={size} height={size} viewBox="0 0 16 16" fill="none" stroke={color} strokeWidth="1.2" strokeLinejoin="round">
    <rect x="2" y="3" width="12" height="10" rx="1.5" />
    <circle cx="5.5" cy="6.5" r="1.2" />
    <path d="M2 11l3.5-3 3 2.5L11 8l3 3" />
  </svg>
);

const ListGlyph = () => (
  <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round">
    <path d="M6 4h8M6 8h8M6 12h8" />
    <circle cx="2.8" cy="4" r="1" fill="currentColor" stroke="none" />
    <circle cx="2.8" cy="8" r="1" fill="currentColor" stroke="none" />
    <circle cx="2.8" cy="12" r="1" fill="currentColor" stroke="none" />
  </svg>
);

const ColumnsGlyph = () => (
  <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5">
    <rect x="2" y="3" width="5" height="10" rx="1" />
    <rect x="9" y="3" width="5" height="10" rx="1" />
  </svg>
);

const GalleryGlyph = () => (
  <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinejoin="round">
    <rect x="2" y="2" width="5.5" height="5.5" rx="1" />
    <rect x="8.5" y="2" width="5.5" height="5.5" rx="1" />
    <rect x="2" y="8.5" width="5.5" height="5.5" rx="1" />
    <rect x="8.5" y="8.5" width="5.5" height="5.5" rx="1" />
  </svg>
);

const ThoughtGlyph = () => (
  <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5">
    <ellipse cx="8" cy="6.5" rx="5.5" ry="4" />
    <circle cx="4.5" cy="12" r="1.1" />
    <circle cx="2.8" cy="14.2" r="0.7" />
  </svg>
);

const CharactersGlyph = () => (
  <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round">
    <circle cx="6" cy="5.5" r="2.5" />
    <path d="M1.5 13.5c.6-2.3 2.4-3.5 4.5-3.5s3.9 1.2 4.5 3.5" />
    <circle cx="11.5" cy="6" r="2" />
    <path d="M11 10.2c1.9.1 3.2 1.2 3.7 3.3" />
  </svg>
);

export default function EditeurBraise() {
  return (
    <div className={styles.root}>
      {/* Barre supérieure */}
      <header className={styles.topbar}>
        <div className={styles.brand}>
          <div className={styles.brandMark}>CE</div>
          <span className={styles.brandName}>Comics Earth Forum</span>
        </div>
        <div className={styles.divider} />
        <div className={styles.docMeta}>
          <span className={styles.docTitle}>Chapitre 3 — La Faille</span>
          <span className={styles.saveState}>Enregistré · il y a 12 s</span>
        </div>
        <div className={styles.spacer} />
        <div className={styles.avatars}>
          <div className={`${styles.avatar} ${styles.avatarPurple}`}>MA</div>
          <div className={`${styles.avatar} ${styles.avatarGreen}`}>JD</div>
          <div className={`${styles.avatar} ${styles.avatarMore}`}>+2</div>
        </div>
        <button className={styles.shareBtn} type="button">
          <Icon d={icons.share} size={14} />
          <span>Partager</span>
        </button>
      </header>

      {/* Barre d'outils — groupes : historique | texte | mise en page | image | dialogue */}
      <div className={styles.toolbar} role="toolbar" aria-label="Outils d'édition">
        <button className={styles.tool} type="button" title="Annuler">
          <Icon d={icons.undo} />
        </button>
        <button className={styles.tool} type="button" title="Rétablir" disabled>
          <Icon d={icons.redo} />
        </button>

        <div className={styles.divider} />

        <button className={styles.blockPicker} type="button">
          Narration
          <Icon d={icons.chevron} size={10} />
        </button>
        <button className={`${styles.tool} ${styles.toolBold}`} type="button" title="Gras">B</button>
        <button className={`${styles.tool} ${styles.toolItalic}`} type="button" title="Italique">I</button>
        <button className={`${styles.tool} ${styles.toolUnderline}`} type="button" title="Souligné">U</button>
        <button className={`${styles.tool} ${styles.toolStrike}`} type="button" title="Barré">S</button>

        <div className={styles.divider} />

        <button className={styles.tool} type="button" title="Liste"><ListGlyph /></button>
        <button className={styles.tool} type="button" title="Citation"><Icon d={icons.quote} filled /></button>
        <button className={styles.tool} type="button" title="Séparateur de scène"><Icon d={icons.sceneBreak} /></button>
        <button className={styles.tool} type="button" title="Colonnes"><ColumnsGlyph /></button>

        <div className={styles.divider} />

        <button className={styles.tool} type="button" title="Insérer une image"><ImageGlyph /></button>
        <button className={styles.tool} type="button" title="Galerie"><GalleryGlyph /></button>

        <div className={styles.divider} />

        <button className={styles.toolAccent} type="button" title="Bulle de dialogue">
          <Icon d={icons.bubble} />
          Bulle
        </button>
        <button className={styles.tool} type="button" title="Pensée"><ThoughtGlyph /></button>
        <button className={styles.tool} type="button" title="Personnages"><CharactersGlyph /></button>

        <div className={styles.spacer} />

        <button className={styles.commentBtn} type="button">
          <Icon d={icons.comment} size={14} />
          Commenter
        </button>
      </div>

      {/* Zone d'édition */}
      <main className={styles.canvas}>
        <article className={styles.page}>
          <div className={styles.sceneHead}>
            <span className={styles.kicker}>Scène 7 · Extérieur nuit</span>
            <h1 className={styles.title}>La Faille s'ouvre</h1>
          </div>

          <p className={styles.prose}>
            La pluie martèle les toits du vieux quartier. Au-dessus de la place, une lueur verte
            fend le ciel en deux — la Faille, exactement là où Nara l'avait prédite.{' '}
            <span className={styles.selection}>Kel resserre sa capuche et lève les yeux.</span>
          </p>

          {/* Bulle — personnage à gauche */}
          <div className={styles.speech}>
            <div className={styles.speechAvatar} style={{ background: 'var(--green)' }}>K</div>
            <div className={styles.speechBody}>
              <span className={styles.speechName}>Kel</span>
              <div className={styles.bubble}>Tu la vois, cette lumière ? C'est pas un orage, ça.</div>
            </div>
          </div>

          {/* Bulle — personnage à droite */}
          <div className={`${styles.speech} ${styles.speechRight}`}>
            <div className={styles.speechAvatar} style={{ background: 'var(--purple)' }}>N</div>
            <div className={styles.speechBody}>
              <span className={styles.speechName}>Nara</span>
              <div className={`${styles.bubble} ${styles.bubbleAccent}`}>
                Trois jours d'avance sur mes calculs. On n'a plus le temps, Kel.
              </div>
            </div>
          </div>

          {/* Image sélectionnée, avec poignées et popover de taille */}
          <figure className={styles.figure} style={{ margin: 0 }}>
            <div className={styles.figureFrame}>
              <div className={styles.figurePlaceholder}>
                <ImageGlyph size={34} color="#3d4656" />
                <span>planche-faille-v2.png</span>
              </div>
            </div>
            <span className={`${styles.handle} ${styles.handleTL}`} />
            <span className={`${styles.handle} ${styles.handleTR}`} />
            <span className={`${styles.handle} ${styles.handleBL}`} />
            <span className={`${styles.handle} ${styles.handleBR}`} />
            <figcaption className={styles.caption}>
              La Faille au-dessus de la place — croquis de Mara
            </figcaption>
            <div className={styles.sizePopover}>
              <button className={styles.sizeOption} type="button">Petit</button>
              <button className={`${styles.sizeOption} ${styles.sizeOptionActive}`} type="button">Moyen</button>
              <button className={styles.sizeOption} type="button">Pleine largeur</button>
            </div>
          </figure>

          <p className={styles.prose}>
            Un grondement sourd traverse la place. Les volets claquent. Quelque part, un chien
            hurle à la mort.
            <span className={styles.caret} />
          </p>

          {/* Commentaire flottant (masqué < 1360px) */}
          <aside className={styles.commentCard}>
            <div className={styles.commentHead}>
              <div className={styles.commentAvatar}>MA</div>
              <span className={styles.commentAuthor}>Mara</span>
              <span className={styles.commentTime}>14:02</span>
            </div>
            <p className={styles.commentText}>
              La réplique de Nara arrive trop tôt, non ? On garde le suspense un panneau de plus ?
            </p>
            <span className={styles.commentReply}>Répondre</span>
          </aside>
        </article>
      </main>

      {/* Barre de statut */}
      <footer className={styles.statusbar}>
        <span>1 248 mots</span>
        <span>·</span>
        <span>12 bulles</span>
        <span>·</span>
        <span>3 images</span>
        <div className={styles.spacer} />
        <span className={styles.syncOk}>
          <span className={styles.syncDot} />
          Synchronisé
        </span>
        <span>100 %</span>
      </footer>
    </div>
  );
}
