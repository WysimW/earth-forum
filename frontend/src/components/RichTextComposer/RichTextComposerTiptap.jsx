import React, { useEffect, useMemo, useRef, useState } from 'react';
import { EditorContent, useEditor } from '@tiptap/react';
import { Extension } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import { TextStyle } from '@tiptap/extension-text-style';
import Color from '@tiptap/extension-color';
import Underline from '@tiptap/extension-underline';
import TextAlign from '@tiptap/extension-text-align';
import {
  FaAlignCenter,
  FaAlignLeft,
  FaAlignRight,
  FaArrowRotateLeft,
  FaArrowRotateRight,
  FaBan,
  FaBold,
  FaCloudArrowUp,
  FaCommentDots,
  FaEraser,
  FaImage,
  FaIndent,
  FaItalic,
  FaLink,
  FaListOl,
  FaListUl,
  FaMinus,
  FaOutdent,
  FaQuoteLeft,
  FaStrikethrough,
  FaUnderline,
  FaWandMagicSparkles,
  FaXmark,
} from 'react-icons/fa6';
import api from '../../services/api';
import {
  applyDialogueAutoDetect,
  applyDialogueThemeToSelection,
  countDialogueCandidates,
  removeDialogueThemeFromSelection,
} from '../../utils/dialogueTheme';
import styles from './RichTextComposer.module.css';

const FONT_SIZE_OPTIONS = ['12', '14', '16', '18', '20', '24', '28', '32'];
const VOID_HTML_TAGS = new Set(['br', 'hr', 'img', 'input', 'meta', 'link']);
const TAG_PATTERN = /(&lt;\/?)([a-zA-Z][\w:-]*)([^&]*?)(\/?&gt;)/g;
const ATTRIBUTE_PATTERN = /(\s+[a-zA-Z_:][\w:.-]*)(=)("[^"]*"|'[^']*')?/g;

const INLINE_COMMANDS = [
  { id: 'bold', icon: FaBold, title: 'Gras' },
  { id: 'italic', icon: FaItalic, title: 'Italique' },
  { id: 'underline', icon: FaUnderline, title: 'Souligné' },
  { id: 'strike', icon: FaStrikethrough, title: 'Barré' },
];

const stripBackgroundStyles = (html) => {
  if (!html) return '';
  const container = document.createElement('div');
  container.innerHTML = html;
  const elements = container.querySelectorAll('*');
  elements.forEach((element) => {
    if (element instanceof HTMLElement) {
      element.style.removeProperty('background');
      element.style.removeProperty('background-color');
      if (!element.getAttribute('style')?.trim()) {
        element.removeAttribute('style');
      }
    }
    element.removeAttribute('bgcolor');
  });
  return container.innerHTML;
};

const normalizeListMarkup = (html) => {
  if (!html) return '';
  const container = document.createElement('div');
  container.innerHTML = html;
  const lists = container.querySelectorAll('ul, ol');
  lists.forEach((list) => {
    const nodes = [...list.childNodes];
    nodes.forEach((node) => {
      if (node.nodeType === Node.ELEMENT_NODE && node.nodeName.toLowerCase() === 'li') return;
      if (node.nodeType === Node.TEXT_NODE && !node.textContent?.trim()) {
        list.removeChild(node);
        return;
      }
      const li = document.createElement('li');
      list.replaceChild(li, node);
      li.appendChild(node);
    });
    const items = [...list.querySelectorAll('li')];
    items.forEach((item) => {
      const text = (item.textContent || '').replace(/\u00a0/g, ' ').trim();
      const hasRichContent = Boolean(item.querySelector('img, video, iframe, table, blockquote, pre, code, hr'));
      if (!text && !hasRichContent) {
        item.remove();
      }
    });
  });
  return container.innerHTML;
};

const stripTransientDataAttributes = (html) => {
  if (!html) return '';
  const container = document.createElement('div');
  container.innerHTML = html;
  const elements = container.querySelectorAll('*');
  elements.forEach((element) => {
    [...element.attributes].forEach((attribute) => {
      const name = attribute.name.toLowerCase();
      if (name === 'data-path-to-node' || name.startsWith('data-cursor-') || name === 'data-rich-composer-selected-image') {
        element.removeAttribute(attribute.name);
      }
    });
  });
  return container.innerHTML;
};

const sanitizeComposerHtml = (html) => stripTransientDataAttributes(normalizeListMarkup(stripBackgroundStyles(html)));

const formatHtmlForSource = (html) => {
  const cleaned = sanitizeComposerHtml(html || '').trim();
  if (!cleaned) return '';
  const expanded = cleaned.replace(/>\s*</g, '>\n<');
  const lines = expanded.split('\n');
  let indentLevel = 0;

  const formatted = lines.map((line) => {
    const trimmed = line.trim();
    if (!trimmed) return '';
    const isClosingTag = /^<\/[\w:-]+/.test(trimmed);
    if (isClosingTag) indentLevel = Math.max(0, indentLevel - 1);
    const currentLine = `${'  '.repeat(indentLevel)}${trimmed}`;
    const openTagMatch = trimmed.match(/^<([\w:-]+)/);
    const tagName = openTagMatch?.[1]?.toLowerCase();
    const isSelfClosing = /\/>$/.test(trimmed);
    const closesOnSameLine = /^<([\w:-]+)(?:\s[^>]*)?>.*<\/\1>$/.test(trimmed);
    if (!isClosingTag && tagName && !VOID_HTML_TAGS.has(tagName) && !isSelfClosing && !closesOnSameLine) {
      indentLevel += 1;
    }
    return currentLine;
  }).filter(Boolean);

  return formatted.join('\n');
};

const escapeHtml = (value) => (value || '')
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;');

const highlightHtmlForPreview = (html, classNames) => {
  const escaped = escapeHtml(html);
  const withTags = escaped.replace(TAG_PATTERN, (fullMatch, start, tagName, attrsChunk, end) => {
    const highlightedAttrs = attrsChunk.replace(ATTRIBUTE_PATTERN, (attrMatch, attrName, equalsSign, attrValue = '') => {
      const valuePart = attrValue ? `${equalsSign}<span class="${classNames.attrValue}">${attrValue}</span>` : '';
      return `<span class="${classNames.attrName}">${attrName}</span>${valuePart}`;
    });
    return `${start}<span class="${classNames.tagName}">${tagName}</span>${highlightedAttrs}${end}`;
  });
  return withTags.replace(/\n/g, '<br />');
};

const FontSize = Extension.create({
  name: 'fontSize',
  addGlobalAttributes() {
    return [
      {
        types: ['textStyle'],
        attributes: {
          fontSize: {
            default: null,
            parseHTML: (element) => element.style.fontSize || null,
            renderHTML: (attributes) => (attributes.fontSize ? { style: `font-size: ${attributes.fontSize}` } : {}),
          },
        },
      },
    ];
  },
  addCommands() {
    return {
      setFontSize: (size) => ({ chain }) => chain().setMark('textStyle', { fontSize: `${size}px` }).run(),
    };
  },
});

const CustomImage = Image.extend({
  addAttributes() {
    return {
      ...this.parent?.(),
      style: {
        default: null,
        parseHTML: (element) => element.getAttribute('style'),
        renderHTML: (attributes) => (attributes.style ? { style: attributes.style } : {}),
      },
    };
  },
});

const RichTextComposerTiptap = ({
  value,
  onChange,
  onQuote,
  dialogueThemes = [],
  activeDialogueThemeId = '',
  onActiveDialogueThemeIdChange = null,
}) => {
  const sourcePreviewRef = useRef(null);
  const imageFileInputRef = useRef(null);
  const [textColor, setTextColor] = useState('#ffffff');
  const [fontSizePx, setFontSizePx] = useState('16');
  const [imageUploading, setImageUploading] = useState(false);
  const [imageError, setImageError] = useState('');
  const [imageAlign, setImageAlign] = useState('center');
  const [imageDisplayMode, setImageDisplayMode] = useState('block');
  const [imageMaxWidthPercent, setImageMaxWidthPercent] = useState(50);
  const [isImageSelected, setIsImageSelected] = useState(false);
  const [selectedImagePos, setSelectedImagePos] = useState(null);
  const [isSourceMode, setIsSourceMode] = useState(false);
  const [sourceHtml, setSourceHtml] = useState(value || '');
  const [editorUiVersion, setEditorUiVersion] = useState(0);

  const selectedDialogueTheme = useMemo(() => {
    if (!activeDialogueThemeId) return null;
    return dialogueThemes.find((theme) => String(theme.id) === String(activeDialogueThemeId)) || null;
  }, [dialogueThemes, activeDialogueThemeId]);

  const highlightedSourcePreview = useMemo(
    () => highlightHtmlForPreview(sourceHtml, {
      tagName: styles.sourceTokenTag,
      attrName: styles.sourceTokenAttrName,
      attrValue: styles.sourceTokenAttrValue,
    }),
    [sourceHtml]
  );

  const editor = useEditor({
    extensions: [
      StarterKit,
      Underline,
      Link.configure({ openOnClick: false }),
      CustomImage,
      TextStyle,
      Color,
      FontSize,
      TextAlign.configure({ types: ['heading', 'paragraph'] }),
    ],
    content: value || '',
    onUpdate: ({ editor: editorInstance }) => {
      if (isSourceMode) return;
      const html = sanitizeComposerHtml(editorInstance.getHTML());
      onChange(html);
    },
  });

  useEffect(() => {
    if (!editor) return;
    if (isSourceMode) return;
    const current = sanitizeComposerHtml(editor.getHTML());
    const incoming = sanitizeComposerHtml(value || '');
    if (current !== incoming) {
      editor.commands.setContent(incoming, false);
    }
  }, [editor, value, isSourceMode]);

  useEffect(() => {
    if (!editor) return undefined;
    const refreshToolbarState = () => setEditorUiVersion((prev) => prev + 1);
    editor.on('selectionUpdate', refreshToolbarState);
    editor.on('transaction', refreshToolbarState);
    return () => {
      editor.off('selectionUpdate', refreshToolbarState);
      editor.off('transaction', refreshToolbarState);
    };
  }, [editor]);

  useEffect(() => {
    if (!isSourceMode) {
      setSourceHtml(value || '');
    }
  }, [value, isSourceMode]);

  const clampImageMaxWidth = (nextValue) => {
    const parsed = Number.parseInt(nextValue, 10);
    if (!Number.isFinite(parsed)) return 50;
    return Math.min(75, Math.max(20, parsed));
  };

  const getImageStyle = () => {
    const safeWidth = clampImageMaxWidth(imageMaxWidthPercent);
    const display = imageDisplayMode === 'inline' ? 'inline-block' : 'block';
    if (display === 'inline-block') {
      return `max-width:${safeWidth}%;width:100%;height:auto;display:inline-block;vertical-align:middle;`;
    }
    if (imageAlign === 'left') {
      return `max-width:${safeWidth}%;width:100%;height:auto;display:block;margin:0 auto 0 0;`;
    }
    if (imageAlign === 'right') {
      return `max-width:${safeWidth}%;width:100%;height:auto;display:block;margin:0 0 0 auto;`;
    }
    return `max-width:${safeWidth}%;width:100%;height:auto;display:block;margin:0 auto;`;
  };

  const parseSelectedImageSettings = (imgElement) => {
    if (!imgElement) return;
    const style = imgElement.style;
    const maxWidth = Number.parseInt(style.maxWidth || '', 10);
    if (Number.isFinite(maxWidth)) {
      setImageMaxWidthPercent(clampImageMaxWidth(maxWidth));
    }
    if (style.display === 'inline-block') {
      setImageDisplayMode('inline');
      return;
    }
    setImageDisplayMode('block');
    if (style.marginLeft === 'auto' && style.marginRight === '0px') {
      setImageAlign('right');
      return;
    }
    if (style.marginLeft === '0px' && style.marginRight === 'auto') {
      setImageAlign('left');
      return;
    }
    setImageAlign('center');
  };

  const updateSelectedImageStyle = () => {
    if (!editor || selectedImagePos === null) return;
    editor.chain().focus().setNodeSelection(selectedImagePos).updateAttributes('image', { style: getImageStyle() }).run();
  };

  useEffect(() => {
    if (!isImageSelected) return;
    updateSelectedImageStyle();
  }, [imageAlign, imageDisplayMode, imageMaxWidthPercent, isImageSelected]); // eslint-disable-line react-hooks/exhaustive-deps

  const executeInline = (commandId) => {
    if (!editor) return;
    const chain = editor.chain().focus();
    if (commandId === 'bold') chain.toggleBold().run();
    if (commandId === 'italic') chain.toggleItalic().run();
    if (commandId === 'underline') chain.toggleUnderline().run();
    if (commandId === 'strike') chain.toggleStrike().run();
  };

  const applyBlockFormat = (event) => {
    if (!editor) return;
    const format = event.target.value;
    if (!format) return;
    const chain = editor.chain().focus();
    if (format === 'p') chain.setParagraph().run();
    if (format === 'h2') chain.toggleHeading({ level: 2 }).run();
    if (format === 'h3') chain.toggleHeading({ level: 3 }).run();
    if (format === 'h4') chain.toggleHeading({ level: 4 }).run();
    if (format === 'blockquote') chain.toggleBlockquote().run();
    if (format === 'pre') chain.toggleCodeBlock().run();
  };

  const applyTextColor = (nextColor) => {
    if (!editor || !nextColor) return;
    setTextColor(nextColor);
    editor.chain().focus().setColor(nextColor).run();
  };

  const applyFontSize = (nextSize) => {
    if (!editor || !nextSize) return;
    setFontSizePx(nextSize);
    editor.chain().focus().setFontSize(nextSize).run();
  };

  const insertLink = () => {
    if (!editor) return;
    const url = window.prompt('URL du lien');
    if (!url) return;
    editor.chain().focus().setLink({ href: url, target: '_blank', rel: 'noopener noreferrer' }).run();
  };

  const insertHr = () => {
    if (!editor) return;
    editor.chain().focus().setHorizontalRule().run();
  };

  const insertSpacer = () => {
    if (!editor) return;
    editor.chain().focus().insertContent('<p><span class="rich-spacer"><br></span></p>').run();
  };

  const insertImageWithSettings = (url) => {
    if (!editor || !url) return;
    const safeUrl = String(url).replace(/"/g, '&quot;');
    editor.chain().focus().setImage({ src: safeUrl, alt: '', style: getImageStyle() }).run();
    setImageError('');
  };

  const insertImage = () => {
    const url = window.prompt('URL de l image');
    if (!url) return;
    insertImageWithSettings(url);
  };

  const uploadAndInsertImage = async (file) => {
    if (!file || !file.type.startsWith('image/')) {
      setImageError('Choisis une image valide.');
      return;
    }
    setImageUploading(true);
    setImageError('');
    const formData = new FormData();
    formData.append('file', file);
    try {
      const response = await api.post('/api/media/upload', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      const uploadedUrl = response?.data?.url || '';
      if (!uploadedUrl) {
        throw new Error('URL image manquante');
      }
      insertImageWithSettings(uploadedUrl);
    } catch (error) {
      setImageError(error?.response?.data?.error || 'Upload image impossible.');
    } finally {
      setImageUploading(false);
      if (imageFileInputRef.current) {
        imageFileInputRef.current.value = '';
      }
    }
  };

  const handleEditorClick = (event) => {
    if (!editor) return;
    const target = event.target;
    if (target instanceof HTMLImageElement) {
      const pos = editor.view.posAtDOM(target, 0);
      setSelectedImagePos(pos);
      setIsImageSelected(true);
      parseSelectedImageSettings(target);
      return;
    }
    setIsImageSelected(false);
    setSelectedImagePos(null);
  };

  const applyDialogueTheme = () => {
    if (!editor || !selectedDialogueTheme) return;
    editor.commands.focus();
    const changed = applyDialogueThemeToSelection(editor.view.dom, selectedDialogueTheme);
    if (changed) {
      const cleaned = sanitizeComposerHtml(editor.view.dom.innerHTML || '');
      editor.commands.setContent(cleaned, false);
      onChange(cleaned);
    }
  };

  const clearDialogueTheme = () => {
    if (!editor) return;
    editor.commands.focus();
    const changed = removeDialogueThemeFromSelection(editor.view.dom);
    if (changed) {
      const cleaned = sanitizeComposerHtml(editor.view.dom.innerHTML || '');
      editor.commands.setContent(cleaned, false);
      onChange(cleaned);
    }
  };

  const runDialogueAutoDetect = () => {
    if (!editor || !selectedDialogueTheme) return;
    editor.commands.focus();
    const count = applyDialogueAutoDetect(editor.view.dom, selectedDialogueTheme);
    if (count > 0) {
      const cleaned = sanitizeComposerHtml(editor.view.dom.innerHTML || '');
      editor.commands.setContent(cleaned, false);
      onChange(cleaned);
    }
  };

  const plainLength = useMemo(() => {
    const temp = document.createElement('div');
    temp.innerHTML = value || '';
    return temp.textContent?.length ?? 0;
  }, [value]);

  const liveDialogueCandidates = useMemo(() => countDialogueCandidates(value || ''), [value]);

  const toggleEditorMode = () => {
    if (!editor) return;
    if (isSourceMode) {
      const cleanedHtml = sanitizeComposerHtml(sourceHtml);
      editor.commands.setContent(cleanedHtml, false);
      onChange(cleanedHtml);
      setIsSourceMode(false);
      return;
    }
    const currentHtml = editor.getHTML() || value || '';
    setSourceHtml(formatHtmlForSource(currentHtml));
    setIsSourceMode(true);
  };

  const handleSourceChange = (event) => {
    const nextHtml = event.target.value;
    setSourceHtml(nextHtml);
    onChange(nextHtml);
  };

  const handleSourceScroll = (event) => {
    if (!sourcePreviewRef.current) return;
    sourcePreviewRef.current.scrollTop = event.currentTarget.scrollTop;
    sourcePreviewRef.current.scrollLeft = event.currentTarget.scrollLeft;
  };

  const getToolbarButtonClass = (isActive = false) => `${styles.toolbarButton} ${isActive ? styles.toolbarButtonActive : ''}`;

  const currentBlockFormat = useMemo(() => {
    if (!editor) return '';
    if (editor.isActive('heading', { level: 2 })) return 'h2';
    if (editor.isActive('heading', { level: 3 })) return 'h3';
    if (editor.isActive('heading', { level: 4 })) return 'h4';
    if (editor.isActive('blockquote')) return 'blockquote';
    if (editor.isActive('codeBlock')) return 'pre';
    return 'p';
  }, [editor, editorUiVersion, value]);

  return (
    <div className={styles.wrapper}>
      <div className={styles.toolbar}>
        {!isSourceMode && (
          <>
            <div className={styles.toolbarRow}>
              <div className={styles.toolbarSection}>
                <span className={styles.toolbarLabel}>Texte</span>
                <select className={styles.toolbarSelect} onChange={applyBlockFormat} value={currentBlockFormat}>
                  <option value="p">Paragraphe</option>
                  <option value="h2">Titre 2</option>
                  <option value="h3">Titre 3</option>
                  <option value="h4">Titre 4</option>
                  <option value="blockquote">Citation</option>
                  <option value="pre">Code bloc</option>
                </select>
                <select
                  className={styles.toolbarSelect}
                  value={fontSizePx}
                  onChange={(event) => applyFontSize(event.target.value)}
                  title="Taille de police"
                >
                  {FONT_SIZE_OPTIONS.map((size) => (
                    <option key={size} value={size}>
                      {size}px
                    </option>
                  ))}
                </select>
                {INLINE_COMMANDS.map((command) => {
                  const isActive = editor?.isActive(command.id === 'strike' ? 'strike' : command.id);
                  return (
                    <button
                      key={command.id}
                      type="button"
                      className={getToolbarButtonClass(isActive)}
                      onClick={() => executeInline(command.id)}
                      title={command.title}
                      aria-label={command.title}
                      aria-pressed={Boolean(isActive)}
                    >
                      <command.icon className={styles.toolbarIcon} aria-hidden />
                    </button>
                  );
                })}
              </div>

              <div className={styles.toolbarSection}>
                <span className={styles.toolbarLabel}>Mise en page</span>
                <button
                  type="button"
                  className={getToolbarButtonClass(editor?.isActive('bulletList'))}
                  onClick={() => editor?.chain().focus().toggleBulletList().run()}
                  title="Liste à puces"
                  aria-label="Liste à puces"
                  aria-pressed={editor?.isActive('bulletList')}
                >
                  <FaListUl className={styles.toolbarIcon} aria-hidden />
                </button>
                <button
                  type="button"
                  className={getToolbarButtonClass(editor?.isActive('orderedList'))}
                  onClick={() => editor?.chain().focus().toggleOrderedList().run()}
                  title="Liste numérotée"
                  aria-label="Liste numérotée"
                  aria-pressed={editor?.isActive('orderedList')}
                >
                  <FaListOl className={styles.toolbarIcon} aria-hidden />
                </button>
                <button type="button" className={styles.toolbarButton} onClick={() => editor?.chain().focus().liftListItem('listItem').run()} title="Diminuer le retrait" aria-label="Diminuer le retrait">
                  <FaOutdent className={styles.toolbarIcon} aria-hidden />
                </button>
                <button type="button" className={styles.toolbarButton} onClick={() => editor?.chain().focus().sinkListItem('listItem').run()} title="Augmenter le retrait" aria-label="Augmenter le retrait">
                  <FaIndent className={styles.toolbarIcon} aria-hidden />
                </button>
                <button
                  type="button"
                  className={getToolbarButtonClass(editor?.isActive({ textAlign: 'left' }))}
                  onClick={() => editor?.chain().focus().setTextAlign('left').run()}
                  title="Aligner à gauche"
                  aria-label="Aligner à gauche"
                  aria-pressed={editor?.isActive({ textAlign: 'left' })}
                >
                  <FaAlignLeft className={styles.toolbarIcon} aria-hidden />
                </button>
                <button
                  type="button"
                  className={getToolbarButtonClass(editor?.isActive({ textAlign: 'center' }))}
                  onClick={() => editor?.chain().focus().setTextAlign('center').run()}
                  title="Centrer"
                  aria-label="Centrer"
                  aria-pressed={editor?.isActive({ textAlign: 'center' })}
                >
                  <FaAlignCenter className={styles.toolbarIcon} aria-hidden />
                </button>
                <button
                  type="button"
                  className={getToolbarButtonClass(editor?.isActive({ textAlign: 'right' }))}
                  onClick={() => editor?.chain().focus().setTextAlign('right').run()}
                  title="Aligner à droite"
                  aria-label="Aligner à droite"
                  aria-pressed={editor?.isActive({ textAlign: 'right' })}
                >
                  <FaAlignRight className={styles.toolbarIcon} aria-hidden />
                </button>
              </div>

            </div>

            <div className={styles.toolbarRow}>
              <div className={styles.toolbarSection}>
                <span className={styles.toolbarLabel}>Actions</span>
                <button type="button" className={styles.toolbarButton} onClick={insertLink} title="Insérer un lien" aria-label="Insérer un lien">
                  <FaLink className={styles.toolbarIcon} aria-hidden />
                </button>
                <button type="button" className={styles.toolbarButton} onClick={insertHr} title="Insérer un séparateur" aria-label="Insérer un séparateur">
                  <FaMinus className={styles.toolbarIcon} aria-hidden />
                </button>
                <button type="button" className={styles.toolbarButton} onClick={insertSpacer} title="Insérer un espace" aria-label="Insérer un espace">
                  Espace
                </button>
                <label className={styles.colorPickerLabel} title="Couleur du texte">
                  A
                  <input type="color" className={styles.colorPickerInput} value={textColor} onChange={(event) => applyTextColor(event.target.value)} />
                </label>
                <button type="button" className={styles.toolbarButton} onClick={() => editor?.chain().focus().undo().run()} title="Annuler" aria-label="Annuler">
                  <FaArrowRotateLeft className={styles.toolbarIcon} aria-hidden />
                </button>
                <button type="button" className={styles.toolbarButton} onClick={() => editor?.chain().focus().redo().run()} title="Rétablir" aria-label="Rétablir">
                  <FaArrowRotateRight className={styles.toolbarIcon} aria-hidden />
                </button>
                <button type="button" className={styles.toolbarButton} onClick={() => editor?.chain().focus().unsetAllMarks().clearNodes().run()} title="Nettoyer le format" aria-label="Nettoyer le format">
                  <FaEraser className={styles.toolbarIcon} aria-hidden />
                </button>
              </div>

              <div className={styles.toolbarSection}>
                <span className={styles.toolbarLabel}>Images</span>
                <button type="button" className={styles.toolbarButton} onClick={insertImage} title="Insérer une image" aria-label="Insérer une image">
                  <FaImage className={styles.toolbarIcon} aria-hidden />
                </button>
                <button
                  type="button"
                  className={styles.toolbarButton}
                  onClick={() => imageFileInputRef.current?.click()}
                  title="Uploader puis insérer une image"
                  aria-label="Uploader puis insérer une image"
                  disabled={imageUploading}
                >
                  <FaCloudArrowUp className={styles.toolbarIcon} aria-hidden />
                </button>
                <div className={styles.imageControls}>
                  <label className={styles.imageControlLabel} htmlFor="rich-text-image-mode">
                    Img
                  </label>
                  <select
                    id="rich-text-image-mode"
                    className={styles.toolbarSelect}
                    value={imageDisplayMode}
                    onChange={(event) => setImageDisplayMode(event.target.value)}
                    title="Mode d'insertion de l'image"
                  >
                    <option value="block">Bloc</option>
                    <option value="inline">Inline</option>
                  </select>
                  <select
                    className={styles.toolbarSelect}
                    value={imageAlign}
                    onChange={(event) => setImageAlign(event.target.value)}
                    title="Alignement des nouvelles images"
                    disabled={imageDisplayMode === 'inline'}
                  >
                    <option value="left">Gauche</option>
                    <option value="center">Centre</option>
                    <option value="right">Droite</option>
                  </select>
                  <label className={styles.imageSizeLabel}>
                    Max
                    <input
                      type="number"
                      className={styles.imageSizeInput}
                      min={20}
                      max={75}
                      step={5}
                      value={imageMaxWidthPercent}
                      onChange={(event) => setImageMaxWidthPercent(clampImageMaxWidth(event.target.value))}
                      title="Largeur maximale de l'image en %"
                    />
                    %
                  </label>
                </div>
                {isImageSelected && (
                  <button
                    type="button"
                    className={styles.toolbarButton}
                    onClick={() => {
                      setIsImageSelected(false);
                      setSelectedImagePos(null);
                    }}
                    title="Désélectionner l'image active"
                    aria-label="Désélectionner l'image active"
                  >
                    <span className={styles.buttonLabel}>
                      <FaXmark className={styles.toolbarIcon} aria-hidden />
                      image
                    </span>
                  </button>
                )}
              </div>
              {(dialogueThemes.length > 0 || onQuote) && (
                <div className={styles.toolbarSection}>
                  <span className={styles.toolbarLabel}>Dialogue</span>
                  {onQuote && (
                    <button type="button" className={styles.toolbarButton} onClick={onQuote} title="Insérer une citation RP" aria-label="Insérer une citation RP">
                      <FaQuoteLeft className={styles.toolbarIcon} aria-hidden />
                    </button>
                  )}
                  {dialogueThemes.length > 0 && (
                    <>
                      <select
                        className={styles.toolbarSelect}
                        value={activeDialogueThemeId || ''}
                        onChange={(event) => onActiveDialogueThemeIdChange?.(event.target.value)}
                        title="Thème de dialogue actif"
                      >
                        <option value="">Theme dialogue</option>
                        {dialogueThemes.map((theme) => (
                          <option key={theme.id} value={theme.id}>
                            {theme.name}{theme.isDefault ? ' (defaut)' : ''}
                          </option>
                        ))}
                      </select>
                      <button
                        type="button"
                        className={styles.toolbarButton}
                        onClick={applyDialogueTheme}
                        title="Appliquer ou retirer le thème sur la sélection"
                        aria-label="Appliquer ou retirer le thème sur la sélection"
                        disabled={!selectedDialogueTheme}
                      >
                        <FaCommentDots className={styles.toolbarIcon} aria-hidden />
                      </button>
                      <button
                        type="button"
                        className={styles.toolbarButton}
                        onClick={clearDialogueTheme}
                        title="Retirer le style dialogue de la sélection"
                        aria-label="Retirer le style dialogue de la sélection"
                      >
                        <FaBan className={styles.toolbarIcon} aria-hidden />
                      </button>
                      <button
                        type="button"
                        className={styles.toolbarButton}
                        onClick={runDialogueAutoDetect}
                        title="Auto-détecter les dialogues entre guillemets"
                        aria-label="Auto-détecter les dialogues entre guillemets"
                        disabled={!selectedDialogueTheme}
                      >
                        <FaWandMagicSparkles className={styles.toolbarIcon} aria-hidden />
                      </button>
                    </>
                  )}
                </div>
              )}
            </div>
          </>
        )}
      </div>

      {isSourceMode ? (
        <>
          <div className={styles.sourceEditorStack}>
            <div
              ref={sourcePreviewRef}
              className={styles.sourcePreview}
              aria-hidden="true"
              dangerouslySetInnerHTML={{ __html: highlightedSourcePreview }}
            />
            <textarea
              className={styles.sourceEditor}
              value={sourceHtml}
              onChange={handleSourceChange}
              onScroll={handleSourceScroll}
              spellCheck={false}
              aria-label="Source HTML"
            />
          </div>
          <div className={styles.sourceHint}>Vue source HTML formatée pour édition fine.</div>
        </>
      ) : (
        <div className={styles.editor} onClick={handleEditorClick}>
          <EditorContent editor={editor} />
        </div>
      )}

      <input
        ref={imageFileInputRef}
        type="file"
        accept="image/*"
        className={styles.hiddenFileInput}
        onChange={(event) => uploadAndInsertImage(event.target.files?.[0])}
      />
      {imageError && <div className={styles.error}>{imageError}</div>}
      {dialogueThemes.length > 0 && (
        <div className={styles.dialogueMeta}>
          Suggestions dialogue detectees: {liveDialogueCandidates}
        </div>
      )}
      <div className={styles.metaRow}>
        <div className={styles.meta}>Longueur: {plainLength} caracteres</div>
        <button
          type="button"
          className={`${styles.metaSwitchButton} ${isSourceMode ? styles.metaSwitchButtonActive : ''}`}
          onClick={toggleEditorMode}
          title={isSourceMode ? 'Revenir à l éditeur riche' : 'Passer en mode source HTML'}
          aria-label={isSourceMode ? 'Revenir à l éditeur riche' : 'Passer en mode source HTML'}
          aria-pressed={isSourceMode}
        >
          {isSourceMode ? 'Mode riche' : 'HTML'}
        </button>
      </div>
    </div>
  );
};

export default RichTextComposerTiptap;
