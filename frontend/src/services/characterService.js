import api from './api';

const characterService = {
  async getAvailableCharacters() {
    const response = await api.get('/ajax/characters/available');
    return response.data;
  },
};

export default characterService;

