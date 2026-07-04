import api from './api';

const footerStatsService = {
  async getLiveStats() {
    const response = await api.get('/api/footer/live-stats');
    return response.data;
  },
};

export default footerStatsService;

