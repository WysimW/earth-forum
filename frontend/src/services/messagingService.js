import api from './api';

/** Émis après lecture des messages (thread) pour rafraîchir badges non lus. */
export const MESSAGING_UNREAD_REFRESH_EVENT = 'earth-forum:messaging-unread-refresh';

export function emitMessagingUnreadRefresh() {
  if (typeof window !== 'undefined') {
    window.dispatchEvent(new CustomEvent(MESSAGING_UNREAD_REFRESH_EVENT));
  }
}

/**
 * Messagerie privée (API JWT).
 * Pagination messages : derniers d’abord ; charger plus ancien avec { before: oldestMessageId }.
 */
const messagingService = {
  async getUnreadCount() {
    const response = await api.get('/api/messaging/unread/count');
    return response.data;
  },

  async getConversations() {
    const response = await api.get('/api/messaging/conversations');
    return response.data;
  },

  /**
   * Recherche de membres (pseudo) pour ouvrir une conversation.
   * @param {string} q — au moins 2 caractères significatifs
   * @param {{ limit?: number }} [opts]
   */
  async searchMembers(q, opts = {}) {
    const search = new URLSearchParams();
    search.set('q', q);
    if (opts.limit != null) search.set('limit', String(opts.limit));
    const response = await api.get(`/api/messaging/members/search?${search.toString()}`);
    return response.data;
  },

  async getConversation(id) {
    const response = await api.get(`/api/messaging/conversations/${id}`);
    return response.data;
  },

  /**
   * @param {{ pseudo?: string, targetUserId?: number }} conversationData
   */
  async createConversation(conversationData) {
    const response = await api.post('/api/messaging/conversations', conversationData);
    return response.data;
  },

  /**
   * @param {number|string} conversationId
   * @param {{ limit?: number, before?: number }} [params] before = id du message le plus ancien déjà affiché (scroll historique)
   */
  async getMessages(conversationId, params = {}) {
    const search = new URLSearchParams();
    if (params.limit != null) search.set('limit', String(params.limit));
    if (params.before != null) search.set('before', String(params.before));
    const q = search.toString();
    const url = `/api/messaging/conversations/${conversationId}/messages${q ? `?${q}` : ''}`;
    const response = await api.get(url);
    return response.data;
  },

  /**
   * @param {number|string} conversationId
   * @param {{ content: string, isRoleplay?: boolean, characterId?: number }} messageData
   */
  async sendMessage(conversationId, messageData) {
    const response = await api.post(
      `/api/messaging/conversations/${conversationId}/messages`,
      messageData,
    );
    return response.data;
  },

  /**
   * Motifs de signalement (alignés sur `App\Entity\Messaging\MessageReport`).
   */
  getReportReasons() {
    return [
      { value: 'inappropriate', label: 'Contenu inapproprié' },
      { value: 'harassment', label: 'Harcèlement' },
      { value: 'spam', label: 'Spam' },
      { value: 'offensive', label: 'Contenu offensant' },
      { value: 'other', label: 'Autre' },
    ];
  },

  /**
   * @param {number|string} conversationId
   * @param {number|string} messageId
   * @param {{ reason: string, details?: string }} payload
   */
  async reportMessage(conversationId, messageId, payload) {
    const response = await api.post(
      `/api/messaging/conversations/${conversationId}/messages/${messageId}/report`,
      payload,
    );
    return response.data;
  },

  /**
   * @param {number|string} conversationId
   * @param {number|string} messageId
   * @param {{ content: string }} payload
   */
  async updateMessage(conversationId, messageId, payload) {
    const response = await api.patch(
      `/api/messaging/conversations/${conversationId}/messages/${messageId}`,
      payload,
    );
    return response.data;
  },

  /**
   * @param {number|string} conversationId
   * @param {number|string} messageId
   */
  async deleteMessage(conversationId, messageId) {
    const response = await api.delete(`/api/messaging/conversations/${conversationId}/messages/${messageId}`);
    return response.data;
  },

  /**
   * Modération globale (ROLE_MODERATOR).
   * @param {{ limit?: number, offset?: number }} [params]
   */
  async getModerationPendingReports(params = {}) {
    const search = new URLSearchParams();
    if (params.limit != null) search.set('limit', String(params.limit));
    if (params.offset != null) search.set('offset', String(params.offset));
    const q = search.toString();
    const url = `/api/messaging/moderation/pending-reports${q ? `?${q}` : ''}`;
    const response = await api.get(url);
    return response.data;
  },

  async getModerationPendingCount() {
    const response = await api.get('/api/messaging/moderation/pending-reports/count');
    return response.data;
  },

  /**
   * Signalements en attente pour une conversation (modérateur de conv. ou global).
   * @param {number|string} conversationId
   */
  async getConversationModerationPendingReports(conversationId) {
    const response = await api.get(`/api/messaging/conversations/${conversationId}/moderation/pending-reports`);
    return response.data;
  },

  /**
   * @param {number|string} reportId
   * @param {{ action: 'approve'|'reject', moderationNotes?: string }} payload
   */
  async resolveMessagingReport(reportId, payload) {
    const response = await api.post(`/api/messaging/reports/${reportId}/resolve`, payload);
    return response.data;
  },
};

export default messagingService;
