import api from './api';

const universeService = {
  async getUniverses() {
    const response = await api.get('/api/universes');
    return response.data.universes;
  },

  async getUnivers(id) {
    const response = await api.get(`/api/universes/${id}`);
    return response.data;
  },

  async getUniversBySlug(slug) {
    const universes = await this.getUniverses();
    return universes.find(u => u.slug === slug);
  },

  async getUniversForums(slug) {
    const response = await api.get(`/api/universes/${slug}/forums`);
    return response.data;
  },
};

export default universeService;

