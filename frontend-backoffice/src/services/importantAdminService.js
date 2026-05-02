import api from './api';

const importantAdminService = {
  async getRegulation() {
    const response = await api.get('/api/reglement');
    return response.data;
  },

  async saveRegulation(content) {
    const response = await api.put('/api/admin/site-settings/reglement', { content });
    return response.data;
  },

  async getGuide() {
    const response = await api.get('/api/mode-emploi');
    return response.data;
  },

  async saveGuide(content) {
    const response = await api.put('/api/admin/site-settings/mode-emploi', { content });
    return response.data;
  },

  async getVoteUrl(universeSlug = '') {
    const query = universeSlug ? `?universe=${encodeURIComponent(universeSlug)}` : '';
    const response = await api.get(`/api/sidebar/important${query}`);
    const voteItem = (response.data?.items || []).find((item) => item.key === 'vote');
    return voteItem?.url || '';
  },

  async saveVoteUrl(url) {
    const response = await api.put('/api/admin/site-settings/vote-url', { url });
    return response.data;
  },

  async getMemberStats(universeId, scope) {
    const response = await api.get(`/api/admin/member-of-month/stats?universe=${universeId}&scope=${scope}`);
    return response.data;
  },

  async selectMember(payload) {
    const response = await api.post('/api/admin/member-of-month/select', payload);
    return response.data;
  },

  async getMemberHistory(universeId) {
    const response = await api.get(`/api/admin/member-of-month/history?universe=${universeId}`);
    return response.data;
  },

  async getCharacterHistory(universeId) {
    const response = await api.get(`/api/admin/character-of-month/history?universe=${universeId}`);
    return response.data;
  },

  async createCharacter(payload) {
    const response = await api.post('/api/admin/character-of-month', payload);
    return response.data;
  },
};

export default importantAdminService;

