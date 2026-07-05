import api from './api';

const seoService = {
  async getPortal() {
    const response = await api.get('/api/seo/portal');
    return response.data?.seo;
  },

  async getAbout() {
    const response = await api.get('/api/seo/about');
    return response.data;
  },

  async getUniverse(slug) {
    const response = await api.get(`/api/seo/universes/${slug}`);
    return response.data;
  },

  async getForum(slug) {
    const response = await api.get(`/api/seo/forums/${slug}`);
    return response.data?.seo;
  },

  async getThread(slug) {
    const response = await api.get(`/api/seo/threads/${slug}`);
    return response.data?.seo;
  },
};

export default seoService;
