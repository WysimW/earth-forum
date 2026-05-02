/**
 * Normalize forum data to ensure consistent property names
 * Handles both 'name' and 'forumName' properties from different API endpoints
 * @param {Object} forum - Forum object from API
 * @returns {Object} - Normalized forum object with 'name' property
 */
export const normalizeForum = (forum) => {
  if (!forum) return null;
  
  return {
    ...forum,
    name: forum.name || forum.forumName || '',
  };
};

/**
 * Get forum name safely, handling both property name variations
 * @param {Object} forum - Forum object
 * @returns {string} - Forum name
 */
export const getForumName = (forum) => {
  if (!forum) return '';
  return forum.name || forum.forumName || '';
};

