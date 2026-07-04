import api from './api';

const importantService = {
  async getSidebar(universeSlug) {
    const params = new URLSearchParams();
    if (universeSlug) {
      params.set('universe', universeSlug);
    }

    const response = await api.get(`/api/sidebar/important${params.toString() ? `?${params.toString()}` : ''}`);
    return response.data;
  },

  async getRegulation() {
    const response = await api.get('/api/reglement');
    return response.data;
  },

  async getGuide() {
    const response = await api.get('/api/mode-emploi');
    return response.data;
  },

  async acceptRegulation() {
    const response = await api.post('/api/reglement/accept');
    return response.data;
  },

  async getMemberOfMonth(universeSlug) {
    const response = await api.get(`/api/member-of-month/${universeSlug}`);
    return response.data;
  },

  async markMemberOfMonthSeen(universeSlug) {
    const response = await api.post(`/api/member-of-month/${universeSlug}/mark-seen`);
    return response.data;
  },

  async getMemberOfMonthHistory(universeSlug) {
    const response = await api.get(`/api/member-of-month/${universeSlug}/history`);
    return response.data;
  },

  async getMemberMessages(entryId) {
    const response = await api.get(`/api/member-of-month/${entryId}/messages`);
    return response.data;
  },

  async createMemberMessage(entryId, content) {
    const response = await api.post(`/api/member-of-month/${entryId}/messages`, { content });
    return response.data;
  },

  async getCharacterOfMonth(universeSlug) {
    const response = await api.get(`/api/character-of-month/${universeSlug}`);
    return response.data;
  },

  async markCharacterOfMonthSeen(universeSlug) {
    const response = await api.post(`/api/character-of-month/${universeSlug}/mark-seen`);
    return response.data;
  },

  async getCharacterOfMonthHistory(universeSlug) {
    const response = await api.get(`/api/character-of-month/${universeSlug}/history`);
    return response.data;
  },
};

export default importantService;

