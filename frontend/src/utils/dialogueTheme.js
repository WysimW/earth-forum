const DIALOGUE_SELECTOR = 'span[data-dialogue-theme-id]';

const QUOTE_PATTERNS = [
  /"([^"]+?)"/g,
  /«\s*([^»]+?)\s*»/g,
  /“([^”]+?)”/g,
  /„([^“]+?)“/g,
  /‹\s*([^›]+?)\s*›/g,
];

const INVALID_PARENT_TAGS = new Set(['CODE', 'PRE']);

const isValidHexColor = (value) => /^#[0-9a-fA-F]{6}$/.test((value || '').trim());

const sanitizeFontFamily = (fontFamily) => {
  if (!fontFamily || typeof fontFamily !== 'string') return null;
  const sanitized = fontFamily.replace(/[^a-zA-Z0-9,\- _'"]/g, '').trim();
  return sanitized || null;
};

const buildDialogueStyle = (theme) => {
  const declarations = [];
  if (isValidHexColor(theme?.color)) {
    declarations.push(`color: ${theme.color}`);
  }
  const fontFamily = sanitizeFontFamily(theme?.fontFamily);
  if (fontFamily) {
    declarations.push(`font-family: ${fontFamily}`);
  }
  if (theme?.isBold) {
    declarations.push('font-weight: 700');
  }
  if (theme?.isItalic) {
    declarations.push('font-style: italic');
  }
  return declarations.join('; ');
};

const buildDialogueSpan = (documentRef, theme) => {
  const span = documentRef.createElement('span');
  span.className = 'dialogueThemeSpan';
  span.setAttribute('data-dialogue-theme-id', String(theme.id));
  span.setAttribute('data-dialogue-color', theme.color || '');
  span.setAttribute('data-dialogue-bold', theme.isBold ? '1' : '0');
  span.setAttribute('data-dialogue-italic', theme.isItalic ? '1' : '0');
  if (theme.name) {
    span.setAttribute('data-dialogue-theme-name', theme.name);
  }
  if (theme.fontFamily) {
    span.setAttribute('data-dialogue-font-family', theme.fontFamily);
  }

  const style = buildDialogueStyle(theme);
  if (style) {
    span.setAttribute('style', style);
  }

  return span;
};

const getRangeInEditor = (editor) => {
  const selection = window.getSelection();
  if (!selection || selection.rangeCount === 0) return null;
  const range = selection.getRangeAt(0);
  if (!editor.contains(range.commonAncestorContainer)) return null;
  return range;
};

const isDialogueNode = (node) => node?.nodeType === Node.ELEMENT_NODE && node.matches?.(DIALOGUE_SELECTOR);

const closestDialogueNode = (node, editor) => {
  if (!node) return null;
  const elementNode = node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement;
  const closest = elementNode?.closest?.(DIALOGUE_SELECTOR) || null;
  return closest && editor.contains(closest) ? closest : null;
};

const unwrapElement = (element) => {
  const parent = element.parentNode;
  if (!parent) return;
  while (element.firstChild) {
    parent.insertBefore(element.firstChild, element);
  }
  parent.removeChild(element);
};

const applyThemeOnExistingWrapper = (wrapper, theme) => {
  wrapper.classList.add('dialogueThemeSpan');
  wrapper.setAttribute('data-dialogue-theme-id', String(theme.id));
  wrapper.setAttribute('data-dialogue-color', theme.color || '');
  wrapper.setAttribute('data-dialogue-bold', theme.isBold ? '1' : '0');
  wrapper.setAttribute('data-dialogue-italic', theme.isItalic ? '1' : '0');

  if (theme.name) {
    wrapper.setAttribute('data-dialogue-theme-name', theme.name);
  } else {
    wrapper.removeAttribute('data-dialogue-theme-name');
  }

  if (theme.fontFamily) {
    wrapper.setAttribute('data-dialogue-font-family', theme.fontFamily);
  } else {
    wrapper.removeAttribute('data-dialogue-font-family');
  }

  const style = buildDialogueStyle(theme);
  if (style) {
    wrapper.setAttribute('style', style);
  } else {
    wrapper.removeAttribute('style');
  }
};

export const applyDialogueThemeToSelection = (editor, theme) => {
  const range = getRangeInEditor(editor);
  if (!range || range.collapsed) return false;

  const startWrapper = closestDialogueNode(range.startContainer, editor);
  const endWrapper = closestDialogueNode(range.endContainer, editor);
  if (startWrapper && startWrapper === endWrapper) {
    const currentThemeId = String(startWrapper.getAttribute('data-dialogue-theme-id') || '');
    const nextThemeId = String(theme?.id || '');

    if (currentThemeId === nextThemeId) {
      unwrapElement(startWrapper);
    } else {
      applyThemeOnExistingWrapper(startWrapper, theme);
    }
    return true;
  }

  const fragment = range.extractContents();
  if (!fragment.textContent?.trim()) return false;

  const wrapper = buildDialogueSpan(editor.ownerDocument, theme);
  wrapper.appendChild(fragment);
  range.insertNode(wrapper);

  const selection = window.getSelection();
  if (selection) {
    selection.removeAllRanges();
    const nextRange = editor.ownerDocument.createRange();
    nextRange.selectNodeContents(wrapper);
    selection.addRange(nextRange);
  }

  return true;
};

export const removeDialogueThemeFromSelection = (editor) => {
  const range = getRangeInEditor(editor);
  if (!range) return false;

  const startWrapper = closestDialogueNode(range.startContainer, editor);
  const endWrapper = closestDialogueNode(range.endContainer, editor);
  if (startWrapper && startWrapper === endWrapper) {
    unwrapElement(startWrapper);
    return true;
  }

  const candidates = Array.from(editor.querySelectorAll(DIALOGUE_SELECTOR));
  let changed = false;

  candidates.forEach((candidate) => {
    try {
      if (range.intersectsNode(candidate)) {
        unwrapElement(candidate);
        changed = true;
      }
    } catch (error) {
      // Ignore invalid range intersections.
    }
  });

  return changed;
};

const findQuotedSegments = (value) => {
  const segments = [];
  QUOTE_PATTERNS.forEach((pattern) => {
    pattern.lastIndex = 0;
    let match = pattern.exec(value);
    while (match) {
      segments.push({ start: match.index, end: match.index + match[0].length });
      match = pattern.exec(value);
    }
  });

  return segments.sort((a, b) => a.start - b.start);
};

const mergeSegments = (segments) => {
  if (segments.length === 0) return segments;
  const merged = [segments[0]];
  for (let i = 1; i < segments.length; i += 1) {
    const current = segments[i];
    const last = merged[merged.length - 1];
    if (current.start <= last.end) {
      last.end = Math.max(last.end, current.end);
    } else {
      merged.push({ ...current });
    }
  }
  return merged;
};

const isInInvalidContext = (node, editor) => {
  let current = node.parentNode;
  while (current && current !== editor) {
    if (current.nodeType === Node.ELEMENT_NODE) {
      if (INVALID_PARENT_TAGS.has(current.tagName)) return true;
      if (isDialogueNode(current)) return true;
    }
    current = current.parentNode;
  }
  return false;
};

export const applyDialogueAutoDetect = (editor, theme) => {
  const textNodes = [];
  const walker = editor.ownerDocument.createTreeWalker(editor, NodeFilter.SHOW_TEXT);
  let node = walker.nextNode();
  while (node) {
    if (node.nodeValue?.trim() && !isInInvalidContext(node, editor)) {
      textNodes.push(node);
    }
    node = walker.nextNode();
  }

  let appliedCount = 0;

  textNodes.forEach((textNode) => {
    const value = textNode.nodeValue || '';
    const mergedSegments = mergeSegments(findQuotedSegments(value));
    if (mergedSegments.length === 0) return;

    const fragment = editor.ownerDocument.createDocumentFragment();
    let cursor = 0;

    mergedSegments.forEach((segment) => {
      if (segment.start > cursor) {
        fragment.appendChild(editor.ownerDocument.createTextNode(value.slice(cursor, segment.start)));
      }
      const span = buildDialogueSpan(editor.ownerDocument, theme);
      span.textContent = value.slice(segment.start, segment.end);
      fragment.appendChild(span);
      cursor = segment.end;
      appliedCount += 1;
    });

    if (cursor < value.length) {
      fragment.appendChild(editor.ownerDocument.createTextNode(value.slice(cursor)));
    }

    textNode.parentNode?.replaceChild(fragment, textNode);
  });

  return appliedCount;
};

export const countDialogueCandidates = (html) => {
  if (!html) return 0;
  const container = document.createElement('div');
  container.innerHTML = html;

  const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT);
  let node = walker.nextNode();
  let count = 0;

  while (node) {
    if (!isInInvalidContext(node, container)) {
      const segments = mergeSegments(findQuotedSegments(node.nodeValue || ''));
      count += segments.length;
    }
    node = walker.nextNode();
  }

  return count;
};

export const decorateDialogueHtmlForRender = (html) => {
  if (!html) return '';
  const container = document.createElement('div');
  container.innerHTML = html;

  const spans = container.querySelectorAll(DIALOGUE_SELECTOR);
  spans.forEach((span) => {
    const theme = {
      id: span.getAttribute('data-dialogue-theme-id') || '',
      name: span.getAttribute('data-dialogue-theme-name') || '',
      color: span.getAttribute('data-dialogue-color') || '',
      fontFamily: span.getAttribute('data-dialogue-font-family') || '',
      isBold: span.getAttribute('data-dialogue-bold') === '1',
      isItalic: span.getAttribute('data-dialogue-italic') === '1',
    };
    const style = buildDialogueStyle(theme);
    if (style) {
      span.setAttribute('style', style);
    }
    span.classList.add('dialogueThemeSpan');
  });

  return container.innerHTML;
};
