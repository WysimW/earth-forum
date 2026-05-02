import api from './api';

const userService = {
  async getCurrentUser() {
    const response = await api.get('/api/auth/me');
    return response.data;
  },

  async updateProfile(userData) {
    const response = await api.put('/api/users/me', userData);
    return response.data;
  },
};

export default userService;

