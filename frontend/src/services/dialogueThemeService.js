import api from './api';

const dialogueThemeService = {
  async listThemes() {
    const response = await api.get('/api/user/dialogue-themes');
    return response.data;
  },

  async createTheme(payload) {
    const response = await api.post('/api/user/dialogue-themes', payload);
    return response.data;
  },

  async updateTheme(id, payload) {
    const response = await api.put(`/api/user/dialogue-themes/${id}`, payload);
    return response.data;
  },

  async deleteTheme(id) {
    const response = await api.delete(`/api/user/dialogue-themes/${id}`);
    return response.data;
  },
};

export default dialogueThemeService;
