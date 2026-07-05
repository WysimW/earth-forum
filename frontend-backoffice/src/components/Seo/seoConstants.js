export const SEO_TITLE_GOOD = 60;
export const SEO_TITLE_MAX = 70;
export const SEO_DESC_GOOD = 160;
export const SEO_DESC_MAX = 160;

export const EMPTY_SEO = {
  metaTitle: '',
  metaDescription: '',
  ogImage: '',
  robotsIndex: true,
};

export const getCounterStatus = (length, goodLimit, maxLimit) => {
  if (length > maxLimit) return 'error';
  if (length > goodLimit) return 'warning';
  return 'success';
};

export const buildPreviewTitle = (seo, defaults = {}) => {
  const title = (seo?.metaTitle || defaults.metaTitle || 'Earth Forum').trim();
  return title.length > SEO_TITLE_MAX ? `${title.slice(0, SEO_TITLE_MAX - 1)}…` : title;
};

export const buildPreviewDescription = (seo, defaults = {}) => {
  const description = (seo?.metaDescription || defaults.metaDescription || '').trim();
  if (!description) return 'Ajoutez une meta description pour améliorer le référencement.';
  return description.length > SEO_DESC_MAX ? `${description.slice(0, SEO_DESC_MAX - 1)}…` : description;
};
