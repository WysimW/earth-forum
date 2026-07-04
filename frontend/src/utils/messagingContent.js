import { sanitizeHtml } from './sanitize';

/**
 * HTML messagerie : même assainissement que le forum (balises / attributs limités).
 * @param {string|null|undefined} html
 * @returns {string}
 */
export function sanitizeMessagingHtml(html) {
  return sanitizeHtml(html || '').trim();
}

/**
 * Vrai si le message contient du texte visible ou au moins une image.
 * @param {string|null|undefined} html
 * @returns {boolean}
 */
export function isSubstantialMessagingHtml(html) {
  const clean = sanitizeMessagingHtml(html);
  if (!clean) return false;
  const tmp = document.createElement('div');
  tmp.innerHTML = clean;
  if (tmp.querySelector('img')) return true;
  const text = (tmp.textContent || '').replace(/\u00a0/g, ' ').trim();
  return text.length > 0;
}

/**
 * Compare deux fragments HTML messagerie après assainissement et sérialisation DOM
 * (évite les setContent intempestifs qui cassent l’insertion d’images).
 * @param {string|null|undefined} a
 * @param {string|null|undefined} b
 * @returns {boolean}
 */
export function messagingHtmlDomEquivalent(a, b) {
  const da = document.createElement('div');
  da.innerHTML = sanitizeMessagingHtml(a || '');
  const db = document.createElement('div');
  db.innerHTML = sanitizeMessagingHtml(b || '');
  return da.innerHTML === db.innerHTML;
}
