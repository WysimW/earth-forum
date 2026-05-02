import api from './api';

export const characterApi = {
  // Récupérer la liste des personnages
  getCharacters: async () => {
    const response = await api.get('/api/characters');
    return response.data;
  },

  // Récupérer tous les personnages sélectionnables d'un univers
  getUniverseSelectableCharacters: async (universeSlug) => {
    const response = await api.get(`/api/characters/universe/${universeSlug}/selectable`);
    return response.data;
  },

  // Récupérer tous les personnages validés d'un univers (tous utilisateurs)
  getUniverseValidatedCharacters: async (universeSlug) => {
    const response = await api.get(`/api/characters/universe/${universeSlug}/validated`);
    return response.data;
  },

  // Récupérer un personnage par ID
  getCharacter: async (id) => {
    const response = await api.get(`/api/characters/${id}`);
    // L'API retourne directement l'objet character (pas dans une propriété character)
    return response.data;
  },

  // Créer un personnage
  createCharacter: async (data, isDraft = true) => {
    const payload = { ...data, isDraft };
    const response = await api.post('/api/characters', payload);
    return response.data;
  },

  // Mettre à jour un personnage
  updateCharacter: async (id, data) => {
    const response = await api.put(`/api/characters/${id}`, data);
    return response.data;
  },

  // Supprimer un personnage
  deleteCharacter: async (id) => {
    const response = await api.delete(`/api/characters/${id}`);
    return response.data;
  },

  // Soumettre un personnage pour validation
  submitCharacter: async (id) => {
    const response = await api.post(`/api/characters/${id}/submit`);
    return response.data;
  },

  // Valider un personnage (admin/modérateur)
  validateCharacter: async (id, moderationNote = '', preambule = '', conclusion = '') => {
    const response = await api.post(`/api/characters/${id}/validate`, { 
      moderationNote,
      preambule,
      conclusion
    });
    return response.data;
  },

  // Rejeter un personnage (admin/modérateur)
  rejectCharacter: async (id, rejectionReason, moderationNote = '', preambule = '', conclusion = '') => {
    const response = await api.post(`/api/characters/${id}/reject`, { 
      rejectionReason, 
      moderationNote,
      preambule,
      conclusion
    });
    return response.data;
  },

  // Marquer un personnage comme nécessitant des modifications (admin/modérateur)
  needsRevision: async (id, moderationNote = '', fieldsToModify = [], fieldCitations = {}, preambule = '', conclusion = '') => {
    const response = await api.post(`/api/characters/${id}/needs-revision`, {
      moderationNote,
      fieldsToModify,
      fieldCitations,
      preambule,
      conclusion
    });
    return response.data;
  },
};

export const npcApi = {
  // Récupérer la liste des PNJ
  getNpcs: async () => {
    const response = await api.get('/api/npcs');
    return response.data;
  },

  // Récupérer un PNJ par ID
  getNpc: async (id) => {
    const response = await api.get(`/api/npcs/${id}`);
    return response.data;
  },

  // Créer un PNJ
  createNpc: async (data, isDraft = true) => {
    const payload = { ...data, isDraft };
    const response = await api.post('/api/npcs', payload);
    return response.data;
  },

  // Mettre à jour un PNJ
  updateNpc: async (id, data) => {
    const response = await api.put(`/api/npcs/${id}`, data);
    return response.data;
  },

  // Supprimer un PNJ
  deleteNpc: async (id) => {
    const response = await api.delete(`/api/npcs/${id}`);
    return response.data;
  },
};

export default characterApi;

