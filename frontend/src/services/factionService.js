import api from './api';

const factionService = {
  async getFactions(params = {}) {
    const response = await api.get('/api/factions', { params });
    return response.data;
  },

  async getFaction(identifier) {
    const response = await api.get(`/api/factions/${identifier}`);
    return response.data;
  },

  async getAvailableCharacters(factionId) {
    const response = await api.get(`/api/factions/${factionId}/available-characters`);
    return response.data;
  },

  async getMyApplicableCharacters(factionId) {
    const response = await api.get(`/api/factions/${factionId}/my-applicable-characters`);
    return response.data;
  },

  async createFaction(data) {
    const response = await api.post('/api/factions', data);
    return response.data;
  },

  async updateFaction(id, data) {
    const response = await api.put(`/api/factions/${id}`, data);
    return response.data;
  },

  async deleteFaction(id) {
    const response = await api.delete(`/api/factions/${id}`);
    return response.data;
  },

  async addCharacterMember(factionId, characterId) {
    const response = await api.post(`/api/factions/${factionId}/members/characters/${characterId}`);
    return response.data;
  },

  async removeCharacterMember(factionId, characterId) {
    const response = await api.delete(`/api/factions/${factionId}/members/characters/${characterId}`);
    return response.data;
  },

  async updateCharacterRole(factionId, characterId, roleRp) {
    const response = await api.put(`/api/factions/${factionId}/members/characters/${characterId}/role`, { roleRp });
    return response.data;
  },

  async applyToFaction(factionId, characterId) {
    const response = await api.post(`/api/factions/${factionId}/applications`, { characterId });
    return response.data;
  },

  async getApplications(factionId) {
    const response = await api.get(`/api/factions/${factionId}/applications`);
    return response.data;
  },

  async acceptApplication(factionId, applicationId) {
    const response = await api.post(`/api/factions/${factionId}/applications/${applicationId}/accept`);
    return response.data;
  },

  async rejectApplication(factionId, applicationId) {
    const response = await api.post(`/api/factions/${factionId}/applications/${applicationId}/reject`);
    return response.data;
  },

  async addNpcMember(factionId, npcId) {
    const response = await api.post(`/api/factions/${factionId}/members/npcs/${npcId}`);
    return response.data;
  },

  async removeNpcMember(factionId, npcId) {
    const response = await api.delete(`/api/factions/${factionId}/members/npcs/${npcId}`);
    return response.data;
  },

  async createFactionNpc(factionId, payload) {
    const response = await api.post(`/api/factions/${factionId}/npcs`, payload);
    return response.data;
  },
};

export default factionService;

