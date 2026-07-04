import api from './api';

const dashboardService = {
  async getDashboard({ universe, threadType, threadStatus, limit } = {}) {
    const params = new URLSearchParams();

    if (universe) params.set('universe', universe);
    if (threadType) params.set('threadType', threadType);
    if (threadStatus) params.set('threadStatus', threadStatus);
    if (limit != null) params.set('limit', String(limit));

    const query = params.toString();
    const url = query ? `/api/dashboard?${query}` : '/api/dashboard';
    const response = await api.get(url);

    return response.data;
  },
};

export default dashboardService;
