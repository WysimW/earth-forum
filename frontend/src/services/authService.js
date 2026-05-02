import api from './api';

const authService = {
  async login(email, password) {
    const response = await api.post('/api/auth/login', {
      email,
      password,
    });
    
    const { token, refreshToken, user } = response.data;
    
    localStorage.setItem('token', token);
    localStorage.setItem('refreshToken', refreshToken);
    localStorage.setItem('user', JSON.stringify(user));
    
    return { token, refreshToken, user };
  },

  async register(userData) {
    const response = await api.post('/api/auth/register', userData);
    
    const { token, refreshToken, user } = response.data;
    
    localStorage.setItem('token', token);
    localStorage.setItem('refreshToken', refreshToken);
    localStorage.setItem('user', JSON.stringify(user));
    
    return { token, refreshToken, user };
  },

  async logout() {
    try {
      await api.post('/api/auth/logout');
    } catch (error) {
      // Même si la requête échoue, on nettoie le localStorage
      console.error('Logout error:', error);
    } finally {
      localStorage.removeItem('token');
      localStorage.removeItem('refreshToken');
      localStorage.removeItem('user');
    }
  },

  async refreshToken() {
    const refreshToken = localStorage.getItem('refreshToken');
    if (!refreshToken) {
      throw new Error('No refresh token available');
    }

    const response = await api.post('/api/auth/refresh', {
      refreshToken,
    });

    const { token } = response.data;
    localStorage.setItem('token', token);

    return token;
  },

  async getCurrentUser() {
    const response = await api.get('/api/auth/me');
    const user = response.data;
    localStorage.setItem('user', JSON.stringify(user));
    return user;
  },

  getStoredUser() {
    const userStr = localStorage.getItem('user');
    return userStr ? JSON.parse(userStr) : null;
  },

  getToken() {
    return localStorage.getItem('token');
  },

  isAuthenticated() {
    return !!localStorage.getItem('token');
  },
};

export default authService;

