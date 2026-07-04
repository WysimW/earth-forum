import React, { useEffect, useMemo, useRef, useState } from 'react';
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
import styles from './RichTextComposer.module.css';
import {
  applyDialogueAutoDetect,
  applyDialogueThemeToSelection,
  countDialogueCandidates,
  removeDialogueThemeFromSelection,
} from '../../utils/dialogueTheme';

const INLINE_COMMANDS = [
  { id: 'bold', icon: FaBold, title: 'Gras' },
  { id: 'italic', icon: FaItalic, title: 'Italique' },
  { id: 'underline', icon: FaUnderline, title: 'Souligné' },
  { id: 'strikeThrough', icon: FaStrikethrough, title: 'Barré' },
];

const BLOCK_COMMANDS = [
  { id: 'insertUnorderedList', icon: FaListUl, title: 'Liste à puces' },
  { id: 'insertOrderedList', icon: FaListOl, title: 'Liste numérotée' },
  { id: 'outdent', icon: FaOutdent, title: 'Diminuer le retrait' },
  { id: 'indent', icon: FaIndent, title: 'Augmenter le retrait' },
  { id: 'justifyLeft', icon: FaAlignLeft, title: 'Aligner à gauche' },
  { id: 'justifyCenter', icon: FaAlignCenter, title: 'Centrer' },
  { id: 'justifyRight', icon: FaAlignRight, title: 'Aligner à droite' },
];

const UTILITY_COMMANDS = [
  { id: 'undo', icon: FaArrowRotateLeft, title: 'Annuler' },
  { id: 'redo', icon: FaArrowRotateRight, title: 'Rétablir' },
  { id: 'removeFormat', icon: FaEraser, title: 'Nettoyer le format' },
];

const FONT_SIZE_OPTIONS = ['12', '14', '16', '18', '20', '24', '28', '32'];
const BLOCK_TAG_SELECTOR = 'h1, h2, h3, h4, h5, h6, p, li, blockquote, pre, div';
const VOID_HTML_TAGS = new Set(['br', 'hr', 'img', 'input', 'meta', 'link']);
const TAG_PATTERN = /(&lt;\/?)([a-zA-Z][\w:-]*)([^&]*?)(\/?&gt;)/g;
const ATTRIBUTE_PATTERN = /(\s+[a-zA-Z_:][\w:.-]*)(=)("[^"]*"|'[^']*')?/g;

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
      if (node.nodeType === Node.ELEMENT_NODE && node.nodeName.toLowerCase() === 'li') {
        return;
      }

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

const normalizeTypographyMarkup = (html) => {
  if (!html) return '';

  const container = document.createElement('div');
  container.innerHTML = html;

  const fontSizedSpans = [...container.querySelectorAll('span[style*="font-size"]')];
  fontSizedSpans.forEach((span) => {
    const styleValue = span.getAttribute('style') || '';
    const fontSizeMatch = styleValue.match(/font-size\s*:\s*([^;]+)/i);
    const fontSizeValue = fontSizeMatch?.[1]?.trim();
    if (!fontSizeValue) return;

    const hasOwnText = Array.from(span.childNodes).some(
      (node) => node.nodeType === Node.TEXT_NODE && node.textContent?.trim()
    );
    const directBlockChildren = [...span.children].filter((child) => child.matches?.(BLOCK_TAG_SELECTOR));

    if (!hasOwnText && directBlockChildren.length > 0) {
      directBlockChildren.forEach((block) => {
        if (!block.style.fontSize) {
          block.style.fontSize = fontSizeValue;
        }
      });
      while (span.firstChild) {
        span.parentNode?.insertBefore(span.firstChild, span);
      }
      span.remove();
    }
  });

  const nestedFontSpans = [...container.querySelectorAll('span[style*="font-size"] span[style*="font-size"]')];
  nestedFontSpans.forEach((inner) => {
    const outer = inner.parentElement;
    if (!outer || outer.tagName.toLowerCase() !== 'span') return;
    const outerHasOwnText = Array.from(outer.childNodes).some(
      (node) => node !== inner && node.nodeType === Node.TEXT_NODE && node.textContent?.trim()
    );
    const outerHasOtherChildren = [...outer.children].some((child) => child !== inner);
    if (!outerHasOwnText && !outerHasOtherChildren) {
      outer.replaceWith(inner);
    }
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
      if (
        name === 'data-path-to-node'
        || name.startsWith('data-cursor-')
        || name === 'data-rich-composer-selected-image'
      ) {
        element.removeAttribute(attribute.name);
      }
    });
  });

  return container.innerHTML;
};

const sanitizeComposerHtml = (html) => stripTransientDataAttributes(
  normalizeTypographyMarkup(normalizeListMarkup(stripBackgroundStyles(html)))
);

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
    if (isClosingTag) {
      indentLevel = Math.max(0, indentLevel - 1);
    }

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
      const valuePart = attrValue
        ? `${equalsSign}<span class="${classNames.attrValue}">${attrValue}</span>`
        : '';
      return `<span class="${classNames.attrName}">${attrName}</span>${valuePart}`;
    });
    return `${start}<span class="${classNames.tagName}">${tagName}</span>${highlightedAttrs}${end}`;
  });

  return withTags.replace(/\n/g, '<br />');
};

const RichTextComposer = ({
  value,
  onChange,
  onQuote,
  dialogueThemes = [],
  activeDialogueThemeId = '',
  onActiveDialogueThemeIdChange = null,
}) => {
  const editorRef = useRef(null);
  const sourcePreviewRef = useRef(null);
  const savedSelectionRef = useRef(null);
  const imageFileInputRef = useRef(null);
  const selectedImageRef = useRef(null);
  const [textColor, setTextColor] = useState('#ffffff');
  const [fontSizePx, setFontSizePx] = useState('16');
  const [imageUploading, setImageUploading] = useState(false);
  const [imageError, setImageError] = useState('');
  const [imageAlign, setImageAlign] = useState('center');
  const [imageDisplayMode, setImageDisplayMode] = useState('block');
  const [imageMaxWidthPercent, setImageMaxWidthPercent] = useState(50);
  const [isImageSelected, setIsImageSelected] = useState(false);
  const [isSourceMode, setIsSourceMode] = useState(false);
  const [sourceHtml, setSourceHtml] = useState(value || '');

  useEffect(() => {
    if (!editorRef.current) return;
    if (editorRef.current.innerHTML !== value) {
      editorRef.current.innerHTML = value || '';
    }
  }, [value]);

  useEffect(() => {
    if (!isSourceMode) {
      setSourceHtml(value || '');
    }
  }, [value, isSourceMode]);

  useEffect(() => {
    const handleSelectionChange = () => {
      const selection = window.getSelection();
      if (!selection || selection.rangeCount === 0 || !editorRef.current) return;
      const range = selection.getRangeAt(0);
      if (editorRef.current.contains(range.commonAncestorContainer)) {
        savedSelectionRef.current = range.cloneRange();
      }
    };

    document.addEventListener('selectionchange', handleSelectionChange);
    return () => document.removeEventListener('selectionchange', handleSelectionChange);
  }, []);

  const plainLength = useMemo(() => {
    const temp = document.createElement('div');
    temp.innerHTML = value || '';
    return temp.textContent?.length ?? 0;
  }, [value]);

  const liveDialogueCandidates = useMemo(() => countDialogueCandidates(value || ''), [value]);

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

  const focusEditor = () => {
    editorRef.current?.focus();
  };

  const saveEditorSelection = () => {
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0 || !editorRef.current) return;
    const range = selection.getRangeAt(0);
    if (editorRef.current.contains(range.commonAncestorContainer)) {
      savedSelectionRef.current = range.cloneRange();
    }
  };

  const restoreEditorSelection = () => {
    if (!savedSelectionRef.current || !editorRef.current) return;
    const selection = window.getSelection();
    if (!selection) return;
    selection.removeAllRanges();
    selection.addRange(savedSelectionRef.current);
  };

  const execute = (command, commandValue) => {
    focusEditor();
    restoreEditorSelection();
    document.execCommand(command, false, commandValue);
    onChange(sanitizeComposerHtml(editorRef.current?.innerHTML || ''));
  };

  const applyBlockFormat = (event) => {
    const format = event.target.value;
    if (!format) return;
    execute('formatBlock', format);
  };

  const insertLink = () => {
    focusEditor();
    restoreEditorSelection();
    const url = window.prompt('URL du lien');
    if (!url) return;
    execute('createLink', url);
  };

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

  const clearSelectedImage = () => {
    if (selectedImageRef.current) {
      selectedImageRef.current.removeAttribute('data-rich-composer-selected-image');
    }
    selectedImageRef.current = null;
    setIsImageSelected(false);
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

  const selectImage = (imgElement) => {
    if (!imgElement) return;
    if (selectedImageRef.current && selectedImageRef.current !== imgElement) {
      selectedImageRef.current.removeAttribute('data-rich-composer-selected-image');
    }
    selectedImageRef.current = imgElement;
    selectedImageRef.current.setAttribute('data-rich-composer-selected-image', 'true');
    setIsImageSelected(true);
    parseSelectedImageSettings(imgElement);
  };

  const updateSelectedImageStyle = () => {
    const currentImage = selectedImageRef.current;
    if (!currentImage) return;
    currentImage.setAttribute('style', getImageStyle());
    onChange(sanitizeComposerHtml(editorRef.current?.innerHTML || ''));
  };

  const insertImageWithSettings = (url) => {
    if (!url) return;
    focusEditor();
    restoreEditorSelection();
    const safeUrl = String(url).replace(/"/g, '&quot;');
    const style = getImageStyle();
    const html =
      imageDisplayMode === 'inline'
        ? `<img src="${safeUrl}" alt="" style="${style}" data-rich-composer-image="true" />`
        : `<p><img src="${safeUrl}" alt="" style="${style}" data-rich-composer-image="true" /></p><p><br></p>`;
    document.execCommand('insertHTML', false, html);
    onChange(sanitizeComposerHtml(editorRef.current?.innerHTML || ''));
  };

  const insertImage = () => {
    focusEditor();
    restoreEditorSelection();
    const url = window.prompt('URL de l image');
    if (!url) return;
    setImageError('');
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

  const triggerImageUpload = () => {
    setImageError('');
    imageFileInputRef.current?.click();
  };

  const handleEditorClick = (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    if (target.tagName.toLowerCase() === 'img') {
      selectImage(target);
      return;
    }
    clearSelectedImage();
  };

  const insertHr = () => {
    execute('insertHorizontalRule');
  };

  const applyDialogueTheme = () => {
    if (!editorRef.current || !selectedDialogueTheme) return;
    focusEditor();
    restoreEditorSelection();
    const changed = applyDialogueThemeToSelection(editorRef.current, selectedDialogueTheme);
    if (changed) {
      onChange(editorRef.current.innerHTML || '');
    }
  };

  const clearDialogueTheme = () => {
    if (!editorRef.current) return;
    focusEditor();
    restoreEditorSelection();
    const changed = removeDialogueThemeFromSelection(editorRef.current);
    if (changed) {
      onChange(editorRef.current.innerHTML || '');
    }
  };

  const runDialogueAutoDetect = () => {
    if (!editorRef.current || !selectedDialogueTheme) return;
    focusEditor();
    restoreEditorSelection();
    const count = applyDialogueAutoDetect(editorRef.current, selectedDialogueTheme);
    if (count > 0) {
      onChange(editorRef.current.innerHTML || '');
    }
  };

  const preventFocusLoss = (event) => {
    event.preventDefault();
  };

  const applyTextColor = (nextColor) => {
    if (!nextColor) return;
    setTextColor(nextColor);
    focusEditor();
    restoreEditorSelection();
    document.execCommand('styleWithCSS', false, true);
    document.execCommand('foreColor', false, nextColor);
    onChange(sanitizeComposerHtml(editorRef.current?.innerHTML || ''));
  };

  const applyFontSize = (nextSizePx) => {
    if (!nextSizePx) return;
    setFontSizePx(nextSizePx);
    if (!editorRef.current) return;

    focusEditor();

    const selection = window.getSelection();
    let activeRange = null;

    if (selection && selection.rangeCount > 0) {
      const currentRange = selection.getRangeAt(0);
      if (editorRef.current.contains(currentRange.commonAncestorContainer)) {
        activeRange = currentRange.cloneRange();
      }
    }

    if (!activeRange && savedSelectionRef.current) {
      const savedRange = savedSelectionRef.current.cloneRange();
      if (editorRef.current.contains(savedRange.commonAncestorContainer)) {
        activeRange = savedRange;
      }
    }

    if (!activeRange || !selection) return;
    if (activeRange.collapsed) return;

    const selectedFragment = activeRange.cloneContents();
    const hasBlockElements = Boolean(selectedFragment.querySelector(BLOCK_TAG_SELECTOR));

    selection.removeAllRanges();
    selection.addRange(activeRange);

    if (hasBlockElements) {
      const blockElements = [...editorRef.current.querySelectorAll(BLOCK_TAG_SELECTOR)];
      blockElements.forEach((element) => {
        try {
          if (activeRange.intersectsNode(element)) {
            element.style.fontSize = `${nextSizePx}px`;
          }
        } catch {
          // Ignore invalid range intersections.
        }
      });
      saveEditorSelection();
    } else {
      const fontSpan = document.createElement('span');
      fontSpan.style.fontSize = `${nextSizePx}px`;
      fontSpan.appendChild(activeRange.extractContents());
      activeRange.insertNode(fontSpan);

      const nextRange = document.createRange();
      nextRange.selectNodeContents(fontSpan);
      selection.removeAllRanges();
      selection.addRange(nextRange);
      savedSelectionRef.current = nextRange.cloneRange();
    }

    onChange(sanitizeComposerHtml(editorRef.current.innerHTML || ''));
  };

  const handleInput = (event) => {
    const cleanedHtml = sanitizeComposerHtml(event.currentTarget.innerHTML);
    if (cleanedHtml !== event.currentTarget.innerHTML) {
      event.currentTarget.innerHTML = cleanedHtml;
    }
    onChange(cleanedHtml);
  };

  const handlePaste = (event) => {
    event.preventDefault();
    const clipboard = event.clipboardData || window.clipboardData;
    const html = clipboard?.getData('text/html');
    const text = clipboard?.getData('text/plain');

    focusEditor();
    if (html) {
      document.execCommand('insertHTML', false, sanitizeComposerHtml(html));
    } else if (text) {
      document.execCommand('insertText', false, text);
    }

    onChange(sanitizeComposerHtml(editorRef.current?.innerHTML || ''));
  };

  useEffect(() => {
    if (!isImageSelected) return;
    updateSelectedImageStyle();
  }, [imageAlign, imageDisplayMode, imageMaxWidthPercent, isImageSelected]);

  const toggleEditorMode = () => {
    if (isSourceMode) {
      const cleanedHtml = sanitizeComposerHtml(sourceHtml);
      onChange(cleanedHtml);
      if (editorRef.current) {
        editorRef.current.innerHTML = cleanedHtml;
      }
      setIsSourceMode(false);
      return;
    }

    const currentHtml = editorRef.current?.innerHTML || value || '';
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

  return (
    <div className={styles.wrapper}>
      <div className={styles.toolbar}>
        <div className={styles.toolbarRow}>
          <button
            type="button"
            className={`${styles.toolbarButton} ${isSourceMode ? styles.toolbarButtonActive : ''}`}
            onClick={toggleEditorMode}
            title={isSourceMode ? 'Revenir à l éditeur riche' : 'Passer en mode source HTML'}
            aria-label={isSourceMode ? 'Revenir à l éditeur riche' : 'Passer en mode source HTML'}
          >
            {isSourceMode ? 'Mode riche' : 'HTML'}
          </button>
        </div>

        {!isSourceMode && (
          <>
        <div className={styles.toolbarRow}>
          <select className={styles.toolbarSelect} onMouseDown={saveEditorSelection} onChange={applyBlockFormat} defaultValue="">
            <option value="">Style</option>
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
            onMouseDown={saveEditorSelection}
            onChange={(event) => applyFontSize(event.target.value)}
            title="Taille de police"
          >
            {FONT_SIZE_OPTIONS.map((size) => (
              <option key={size} value={size}>
                {size}px
              </option>
            ))}
          </select>

          {INLINE_COMMANDS.map((command) => (
            <button
              key={command.id}
              type="button"
              className={styles.toolbarButton}
              onMouseDown={preventFocusLoss}
              onClick={() => execute(command.id, command.value)}
              title={command.title}
              aria-label={command.title}
            >
              <command.icon className={styles.toolbarIcon} aria-hidden />
            </button>
          ))}

          {BLOCK_COMMANDS.map((command) => (
            <button
              key={command.id}
              type="button"
              className={styles.toolbarButton}
              onMouseDown={preventFocusLoss}
              onClick={() => execute(command.id)}
              title={command.title}
              aria-label={command.title}
            >
              <command.icon className={styles.toolbarIcon} aria-hidden />
            </button>
          ))}

          <button type="button" className={styles.toolbarButton} onMouseDown={preventFocusLoss} onClick={insertLink} title="Insérer un lien" aria-label="Insérer un lien">
            <FaLink className={styles.toolbarIcon} aria-hidden />
          </button>
          <button type="button" className={styles.toolbarButton} onMouseDown={preventFocusLoss} onClick={insertHr} title="Insérer un séparateur" aria-label="Insérer un séparateur">
            <FaMinus className={styles.toolbarIcon} aria-hidden />
          </button>

          <label className={styles.colorPickerLabel} title="Couleur du texte">
            A
            <input
              type="color"
              className={styles.colorPickerInput}
              value={textColor}
              onChange={(event) => applyTextColor(event.target.value)}
            />
          </label>

          {UTILITY_COMMANDS.map((command) => (
            <button
              key={command.id}
              type="button"
              className={styles.toolbarButton}
              onMouseDown={preventFocusLoss}
              onClick={() => execute(command.id)}
              title={command.title}
              aria-label={command.title}
            >
              <command.icon className={styles.toolbarIcon} aria-hidden />
            </button>
          ))}
        </div>

        <div className={styles.toolbarRow}>
          <button type="button" className={styles.toolbarButton} onMouseDown={preventFocusLoss} onClick={insertImage} title="Insérer une image" aria-label="Insérer une image">
            <FaImage className={styles.toolbarIcon} aria-hidden />
          </button>
          <button
            type="button"
            className={styles.toolbarButton}
            onMouseDown={preventFocusLoss}
            onClick={triggerImageUpload}
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
              onMouseDown={preventFocusLoss}
              onClick={clearSelectedImage}
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
          <div className={styles.toolbarRow}>
            {onQuote && (
              <button type="button" className={styles.toolbarButton} onMouseDown={preventFocusLoss} onClick={onQuote} title="Insérer une citation RP" aria-label="Insérer une citation RP">
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
                  onMouseDown={preventFocusLoss}
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
                  onMouseDown={preventFocusLoss}
                  onClick={clearDialogueTheme}
                  title="Retirer le style dialogue de la sélection"
                  aria-label="Retirer le style dialogue de la sélection"
                >
                  <FaBan className={styles.toolbarIcon} aria-hidden />
                </button>
                <button
                  type="button"
                  className={styles.toolbarButton}
                  onMouseDown={preventFocusLoss}
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
        <div
          ref={editorRef}
          className={styles.editor}
          contentEditable
          suppressContentEditableWarning
          onInput={handleInput}
          onPaste={handlePaste}
          onClick={handleEditorClick}
          onMouseUp={saveEditorSelection}
          onKeyUp={saveEditorSelection}
          onFocus={saveEditorSelection}
        />
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
      <div className={styles.meta}>Longueur: {plainLength} caracteres</div>
    </div>
  );
};

export default RichTextComposer;
