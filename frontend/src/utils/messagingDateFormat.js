/**
 * Formate une date/heure messagerie (chaîne API type `Y-m-d H:i:s`) pour l'affichage en français.
 *
 * - Aujourd'hui : « Aujourd'hui à 11h46 »
 * - Hier (calendrier local, à partir de 00h00) : « Hier à 11h46 »
 * - Même année, autre jour : « 11/06 à 11h46 »
 * - Autre année : « 11/06/2025 à 11h46 »
 *
 * @param {string|null|undefined} apiDateTime
 * @returns {string}
 */
export function formatMessagingDateTime(apiDateTime) {
  if (apiDateTime == null || typeof apiDateTime !== 'string') {
    return '';
  }
  const d = parseApiDateTime(apiDateTime.trim());
  if (!d || Number.isNaN(d.getTime())) {
    return apiDateTime;
  }

  const now = new Date();
  const startToday = startOfLocalDay(now);
  const startMsg = startOfLocalDay(d);
  const diffMs = startToday.getTime() - startMsg.getTime();
  const diffDays = Math.round(diffMs / 86400000);

  const h = d.getHours();
  const min = String(d.getMinutes()).padStart(2, '0');
  const timePart = `${h}h${min}`;

  if (diffDays === 0) {
    return `Aujourd'hui à ${timePart}`;
  }
  if (diffDays === 1) {
    return `Hier à ${timePart}`;
  }

  const dd = String(d.getDate()).padStart(2, '0');
  const mo = String(d.getMonth() + 1).padStart(2, '0');
  const yyyy = d.getFullYear();

  if (yyyy === now.getFullYear()) {
    return `${dd}/${mo} à ${timePart}`;
  }
  return `${dd}/${mo}/${yyyy} à ${timePart}`;
}

/**
 * Valeur `datetime` pour <time> (heure locale, sans décalage UTC).
 * @param {string|null|undefined} apiDateTime
 * @returns {string}
 */
export function messagingApiDateToIsoLocal(apiDateTime) {
  if (apiDateTime == null || typeof apiDateTime !== 'string') {
    return '';
  }
  const d = parseApiDateTime(apiDateTime.trim());
  if (!d || Number.isNaN(d.getTime())) {
    return '';
  }
  const p = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}:00`;
}

/**
 * @param {Date} d
 */
function startOfLocalDay(d) {
  return new Date(d.getFullYear(), d.getMonth(), d.getDate());
}

/**
 * @param {string} s
 * @returns {Date|null}
 */
function parseApiDateTime(s) {
  const m = s.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?/);
  if (!m) {
    return null;
  }
  const [, y, mo, day, hh, mi] = m;
  return new Date(Number(y), Number(mo) - 1, Number(day), Number(hh), Number(mi), 0);
}
