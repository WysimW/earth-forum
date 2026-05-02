import api from './api';

const threadService = {
  async getThread(slug, options = {}) {
    const response = await api.get(`/api/threads/${slug}`, {
      params: {
        page: options.page,
        limit: options.limit,
      },
    });
    return response.data;
  },

  async createThread(threadData) {
    const response = await api.post('/api/threads', threadData);
    return response.data;
  },

  async updateThread(id, threadData) {
    const response = await api.put(`/api/threads/${id}`, threadData);
    return response.data;
  },

  async deleteThread(id) {
    const response = await api.delete(`/api/threads/${id}`);
    return response.data;
  },
};

export default threadService;

