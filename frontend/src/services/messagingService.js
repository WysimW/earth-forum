import api from './api';

const messagingService = {
  async getConversations() {
    const response = await api.get('/api/messaging/conversations');
    return response.data;
  },

  async getConversation(id) {
    const response = await api.get(`/api/messaging/conversations/${id}`);
    return response.data;
  },

  async createConversation(conversationData) {
    const response = await api.post('/api/messaging/conversations', conversationData);
    return response.data;
  },

  async getMessages(conversationId) {
    const response = await api.get(`/api/messaging/conversations/${conversationId}/messages`);
    return response.data;
  },

  async sendMessage(conversationId, messageData) {
    const response = await api.post(`/api/messaging/conversations/${conversationId}/messages`, messageData);
    return response.data;
  },
};

export default messagingService;

