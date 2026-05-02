import axios from 'axios';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8050';

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Intercepteur pour ajouter le token JWT aux requêtes
api.interceptors.request.use(
  (config) => {
    // Ne pas ajouter le token pour les routes d'authentification publiques
    const publicRoutes = ['/api/auth/login', '/api/auth/register', '/api/auth/refresh'];
    const isPublicRoute = publicRoutes.some(route => config.url?.includes(route));
    
    if (!isPublicRoute) {
      const token = localStorage.getItem('token');
      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }
    }

    const contextUniverseId = localStorage.getItem('admin_universe_context');
    const isAdminRoute = config.url?.includes('/api/admin/');
    const isGetMethod = (config.method || 'get').toLowerCase() === 'get';
    const hasContextParam = config.url?.includes('context_universe_id=');
    if (contextUniverseId && isAdminRoute && isGetMethod && !hasContextParam) {
      const hasQuery = config.url.includes('?');
      const separator = hasQuery ? '&' : '?';
      config.url = `${config.url}${separator}context_universe_id=${encodeURIComponent(contextUniverseId)}`;
    }

    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Intercepteur pour gérer les erreurs de réponse
api.interceptors.response.use(
  (response) => {
    return response;
  },
  async (error) => {
    const originalRequest = error.config;

    // Si l'erreur est 401 et qu'on n'a pas déjà tenté de refresh
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;

      try {
        const refreshToken = localStorage.getItem('refreshToken');
        if (refreshToken) {
          const response = await axios.post(`${API_URL}/api/auth/refresh`, {
            refreshToken,
          });

          const { token } = response.data;
          localStorage.setItem('token', token);

          // Réessayer la requête originale avec le nouveau token
          originalRequest.headers.Authorization = `Bearer ${token}`;
          return api(originalRequest);
        }
      } catch (refreshError) {
        // Le refresh a échoué, déconnecter l'utilisateur
        localStorage.removeItem('token');
        localStorage.removeItem('refreshToken');
        localStorage.removeItem('user');
        window.location.href = '/login';
        return Promise.reject(refreshError);
      }
    }

    return Promise.reject(error);
  }
);

export default api;






