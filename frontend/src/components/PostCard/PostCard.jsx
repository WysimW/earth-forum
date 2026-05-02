import React, { useEffect, useMemo, useState } from 'react';
import { sanitizeHtml } from '../../utils/sanitize';
import { decorateDialogueHtmlForRender } from '../../utils/dialogueTheme';
import styles from './PostCard.module.css';

const parseBlocks = (content) => {
  const html = content || '';
  const container = document.createElement('div');
  container.innerHTML = html;

  const elementChildren = Array.from(container.children);
  if (elementChildren.length === 0) {
    return [{ tag: 'p', html }];
  }

  const blocks = elementChildren
    .map((el) => ({
      tag: el.tagName.toLowerCase(),
      html: el.innerHTML,
      text: (el.textContent || '').replace(/\u00a0/g, ' ').trim(),
      hasRichContent: Boolean(el.querySelector('img, video, iframe, table, ul, ol, blockquote, pre, hr')),
    }))
    .filter((block) => block.text !== '' || block.hasRichContent)
    .map(({ tag, html: blockHtml }) => ({ tag, html: blockHtml }));

  if (blocks.length === 0) {
    return [];
  }

  return blocks;
};

const PostCard = ({
  post,
  onQuote,
  onEdit,
  onDelete,
  canEdit,
  canDelete,
  isRoleplay = false,
  character = null,
  isInlineEditing = false,
  inlineParagraphIndex = null,
  onInlineSave = null,
  onInlineCancel = null,
}) => {
  // Sanitize HTML content to prevent XSS attacks
  const sanitizedContent = decorateDialogueHtmlForRender(sanitizeHtml(post.content));
  const [editableBlocks, setEditableBlocks] = useState([]);

  useEffect(() => {
    if (!isInlineEditing) return;
    setEditableBlocks(parseBlocks(post.content));
  }, [isInlineEditing, post.content]);

  const rebuiltContent = useMemo(
    () =>
      editableBlocks
        .map((block) => `<${block.tag}>${block.html}</${block.tag}>`)
        .join(''),
    [editableBlocks]
  );
  const parsedBlocks = useMemo(() => parseBlocks(post.content), [post.content]);
  const characterIdentity = [
    [character?.firstName, character?.lastName].filter(Boolean).join(' ').trim(),
    character?.actualPseudo ? `@${character.actualPseudo}` : '',
  ].filter(Boolean).join(' / ');
  const normalizeAlignment = (value) => (value || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');
  const alignmentValue = normalizeAlignment(character?.moralAlignment);
  const nameToneClass = alignmentValue.includes('super-vilain') || alignmentValue.includes('vilain')
    ? styles.characterNameVillain
    : alignmentValue.includes('anti-heros') || alignmentValue.includes('antiheros')
      ? styles.characterNameAntiHero
      : alignmentValue.includes('vigilante')
        ? styles.characterNameVigilante
        : alignmentValue.includes('super-heros') || alignmentValue.includes('heros') || alignmentValue.includes('hero')
          ? styles.characterNameHero
          : styles.characterNameNeutral;
  const avatarToneClass = alignmentValue.includes('super-vilain') || alignmentValue.includes('vilain')
    ? styles.avatarToneVillain
    : alignmentValue.includes('anti-heros') || alignmentValue.includes('antiheros')
      ? styles.avatarToneAntiHero
      : alignmentValue.includes('vigilante')
        ? styles.avatarToneVigilante
        : alignmentValue.includes('super-heros') || alignmentValue.includes('heros') || alignmentValue.includes('hero')
          ? styles.avatarToneHero
          : styles.avatarToneNeutral;

  const handleParagraphDoubleClick = (index) => {
    if (!canEdit || !onEdit) return;
    onEdit(post, index);
  };

  // Style RP (Style 2 - Sidebar personnage)
  if (isRoleplay && character) {
    return (
      <div className={styles.postCardRP}>
        <div className={styles.characterSidebar}>
          <img 
            src={character.avatar || post.avatar} 
            alt={character.name || post.author} 
            className={`${styles.avatarLarge} ${avatarToneClass}`} 
          />
          <div className={styles.characterDetails}>
            <div className={`${styles.characterName} ${nameToneClass}`}>{character.name || post.author}</div>
            {characterIdentity && (
              <div className={styles.characterIdentity}>{characterIdentity}</div>
            )}
            {character.alias && (
              <div className={styles.characterAlias}>{character.alias}</div>
            )}
            {character.moralAlignment && (
              <div className={styles.alignmentBadge}>{character.moralAlignment}</div>
            )}
            {character.factions && character.factions.length > 0 && (
              <div className={styles.factionsList}>
                {character.factions.map((faction, idx) => (
                  <span key={idx} className={styles.factionTag}>{faction}</span>
                ))}
              </div>
            )}
            <div className={styles.postDate}>{post.date}</div>
          </div>
        </div>
        <div className={styles.postMain}>
          {isInlineEditing ? (
            <div className={styles.inlineEditor}>
              {editableBlocks
                .map((block, index) => ({ block, index }))
                .filter(({ index }) => inlineParagraphIndex === null || inlineParagraphIndex === index)
                .map(({ block, index }) => (
                <div key={`${block.tag}-${index}`} className={styles.inlineEditorBlock}>
                  <div className={styles.inlineEditorLabel}>Paragraphe {index + 1}</div>
                  <div
                    className={styles.inlineEditorContent}
                    contentEditable
                    suppressContentEditableWarning
                    dangerouslySetInnerHTML={{ __html: block.html }}
                    onInput={(event) => {
                      const nextBlocks = [...editableBlocks];
                      nextBlocks[index] = { ...nextBlocks[index], html: event.currentTarget.innerHTML };
                      setEditableBlocks(nextBlocks);
                    }}
                  />
                </div>
              ))}
            </div>
          ) : (
            <div className={styles.postContent}>
              {parsedBlocks.map((block, index) =>
                React.createElement(block.tag || 'p', {
                  key: `${block.tag || 'p'}-${index}`,
                  className: styles.postParagraph,
                  onDoubleClick: () => handleParagraphDoubleClick(index),
                  dangerouslySetInnerHTML: { __html: decorateDialogueHtmlForRender(sanitizeHtml(block.html)) },
                })
              )}
            </div>
          )}
          <div className={styles.postActions}>
            {isInlineEditing && onInlineSave && (
              <button
                onClick={() => onInlineSave(post, rebuiltContent)}
                className={styles.actionButton}
                title="Enregistrer"
              >
                Enregistrer
              </button>
            )}
            {isInlineEditing && onInlineCancel && (
              <button
                onClick={() => onInlineCancel(post)}
                className={styles.actionButton}
                title="Annuler"
              >
                Annuler
              </button>
            )}
            {onQuote && (
              <button
                onClick={() => onQuote(post)}
                className={styles.actionButton}
                title="Citer"
              >
                Citer
              </button>
            )}
            {canEdit && onEdit && !isInlineEditing && (
              <button
                onClick={() => onEdit(post)}
                className={styles.actionButton}
                title="Éditer"
              >
                Éditer
              </button>
            )}
            {canDelete && onDelete && (
              <button
                onClick={() => onDelete(post)}
                className={styles.actionButton}
                title="Supprimer"
              >
                Supprimer
              </button>
            )}
          </div>
        </div>
      </div>
    );
  }

  // Style HRP (Style 1 - Classique)
  return (
    <div className={styles.postCardHRP}>
      <div className={styles.postHeader}>
        <div className={styles.postAuthor}>
          {post.avatar && (
            <img
              src={post.avatar}
              alt={post.author}
              className={styles.avatar}
            />
          )}
          <div className={styles.authorInfo}>
            <span className={styles.authorName}>{post.author}</span>
            <span className={styles.postDate}>{post.date}</span>
          </div>
        </div>
        <div className={styles.postActions}>
          {onQuote && (
            <button
              onClick={() => onQuote(post)}
              className={styles.actionButton}
              title="Citer"
            >
              Citer
            </button>
          )}
          {canEdit && onEdit && (
            <button
              onClick={() => onEdit(post)}
              className={styles.actionButton}
              title="Éditer"
            >
              Éditer
            </button>
          )}
          {canDelete && onDelete && (
            <button
              onClick={() => onDelete(post)}
              className={styles.actionButton}
              title="Supprimer"
            >
              Supprimer
            </button>
          )}
        </div>
      </div>
      <div
        className={styles.postContent}
        dangerouslySetInnerHTML={{ __html: sanitizedContent }}
      />
    </div>
  );
};

export default PostCard;
