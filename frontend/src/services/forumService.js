import api from './api';

const forumService = {
  async getCategories(universeSlug = null, universeId = null) {
    const params = {};
    if (universeSlug) {
      params.universe = universeSlug;
    }
    if (universeId) {
      params.universeId = universeId;
    }
    const response = await api.get('/api/categories', { params });
    return response.data.categories;
  },

  async getCategoriesList() {
    const response = await api.get('/api/categories/list');
    return response.data;
  },

  async getAllForums() {
    const response = await api.get('/api/forums');
    return response.data;
  },

  async getForumList() {
    const response = await api.get('/api/forumslist');
    return response.data;
  },

  async getForum(id) {
    const response = await api.get(`/api/forums/${id}`);
    return response.data;
  },

  async getForumBySlug(slug, options = {}) {
    const params = {};
    
    // Pagination
    if (options.page) {
      params.page = options.page;
    }
    if (options.limit) {
      params.limit = options.limit;
    }
    
    // Filtres
    if (options.filters) {
      if (options.filters.status && options.filters.status !== 'all') {
        params.status = options.filters.status;
      }
      if (options.filters.search) {
        params.search = options.filters.search;
      }
      if (options.filters.userId) {
        params.userId = options.filters.userId;
      }
      if (options.filters.author) {
        params.author = options.filters.author;
      }
      if (options.filters.character) {
        params.character = options.filters.character;
      }
    }
    
    const response = await api.get(`/api/forums/by-slug/${slug}`, { params });
    return response.data;
  },

  async getForumEditData(id) {
    const response = await api.get(`/api/forums/${id}/edit-data`);
    return response.data;
  },

  async createForum(forumData) {
    const response = await api.post('/api/forums', forumData);
    return response.data;
  },

  async updateForum(id, forumData) {
    const response = await api.put(`/api/forums/${id}`, forumData);
    return response.data;
  },

  async deleteForum(id) {
    const response = await api.delete(`/api/forums/${id}`);
    return response.data;
  },
};

export default forumService;

