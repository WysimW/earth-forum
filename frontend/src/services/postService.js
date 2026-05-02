import api from './api';

const postService = {
  async createPost(postData) {
    const response = await api.post('/api/posts', postData);
    return response.data;
  },

  async updatePost(id, postData) {
    const response = await api.put(`/api/posts/${id}`, postData);
    return response.data;
  },

  async deletePost(id) {
    const response = await api.delete(`/api/posts/${id}`);
    return response.data;
  },

  async quotePost(id) {
    const response = await api.get(`/api/posts/${id}/quote`);
    return response.data;
  },
};

export default postService;

