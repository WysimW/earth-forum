import api from './api';

const seoAdminService = {
  async getSitePage(pageKey) {
    const response = await api.get(`/api/admin/seo/site-pages/${pageKey}`);
    return response.data;
  },

  async saveSitePage(pageKey, payload) {
    const response = await api.put(`/api/admin/seo/site-pages/${pageKey}`, payload);
    return response.data;
  },

  async getUniverseSeo(universeId) {
    const response = await api.get(`/api/admin/universes/${universeId}/seo`);
    return response.data;
  },

  async saveUniverseSeo(universeId, payload) {
    const response = await api.put(`/api/admin/universes/${universeId}/seo`, payload);
    return response.data;
  },
};

export default seoAdminService;
