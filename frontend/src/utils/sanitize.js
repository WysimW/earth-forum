import DOMPurify from 'dompurify';

/**
 * Sanitize HTML content to prevent XSS attacks
 * @param {string} dirty - The HTML string to sanitize
 * @returns {string} - Sanitized HTML string
 */
export const sanitizeHtml = (dirty) => {
  if (!dirty) return '';

  const sanitized = DOMPurify.sanitize(dirty, {
    ALLOWED_TAGS: [
      'p', 'br', 'strong', 'em', 'u', 's', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
      'ul', 'ol', 'li', 'blockquote', 'code', 'pre', 'a', 'img',
      'div', 'span', 'table', 'thead', 'tbody', 'tr', 'td', 'th'
    ],
    ALLOWED_ATTR: [
      'href', 'src', 'alt', 'title', 'class', 'id', 'width', 'height',
      'target', 'rel', 'data-dialogue-theme-id', 'data-dialogue-theme-name',
      'data-dialogue-color', 'data-dialogue-font-family', 'data-dialogue-bold',
      'data-dialogue-italic', 'style'
    ],
    ALLOWED_URI_REGEXP: /^(?:(?:(?:f|ht)tps?|mailto|tel|callto|sms|cid|xmpp):|[^a-z]|[a-z+.\-]+(?:[^a-z+.\-:]|$))/i,
  });

  const container = document.createElement('div');
  container.innerHTML = sanitized;

  const styledElements = container.querySelectorAll('[style]');
  styledElements.forEach((element) => {
    if (!(element instanceof HTMLElement)) return;

    element.style.removeProperty('background');
    element.style.removeProperty('background-color');

    const style = element.getAttribute('style');
    if (!style || !style.trim()) {
      element.removeAttribute('style');
    }
  });

  return container.innerHTML;
};

