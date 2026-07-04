import api from './api';

const rpActivityService = {
  async getUniverseActivities(universeSlug, options = {}) {
    const params = {};
    if (options.kind) params.kind = options.kind;
    if (options.status) params.status = options.status;
    if (options.factionId) params.faction = options.factionId;
    if (options.participating) params.participating = true;

    const response = await api.get(`/api/universes/${universeSlug}/rp-activities`, { params });
    return response.data;
  },

  async getActivity(id) {
    const response = await api.get(`/api/rp-activities/${id}`);
    return response.data;
  },

  async markActivitySeen(id) {
    const response = await api.post(`/api/rp-activities/${id}/mark-seen`);
    return response.data;
  },

  async markUniverseActivitiesSeen(universeSlug) {
    const response = await api.post(`/api/universes/${universeSlug}/rp-activities/mark-seen`);
    return response.data;
  },

  async createActivity(payload) {
    const response = await api.post('/api/rp-activities', payload);
    return response.data;
  },

  async updateActivity(activityId, payload) {
    const response = await api.put(`/api/rp-activities/${activityId}`, payload);
    return response.data;
  },

  async deleteActivity(activityId) {
    const response = await api.delete(`/api/rp-activities/${activityId}`);
    return response.data;
  },

  async getThreadContext(activityId) {
    const response = await api.get(`/api/rp-activities/${activityId}/thread-context`);
    return response.data;
  },

  async createThread(activityId, payload) {
    const response = await api.post(`/api/rp-activities/${activityId}/threads/create`, payload);
    return response.data;
  },

  async getEventCharacters(activityId) {
    const response = await api.get(`/api/rp-activities/${activityId}/event-characters`);
    return response.data;
  },

  async createEventCharacter(activityId, payload) {
    const response = await api.post(`/api/rp-activities/${activityId}/event-characters`, payload);
    return response.data;
  },

  async updateEventCharacter(activityId, characterId, payload) {
    const response = await api.put(`/api/rp-activities/${activityId}/event-characters/${characterId}`, payload);
    return response.data;
  },

  async deleteEventCharacter(activityId, characterId) {
    const response = await api.delete(`/api/rp-activities/${activityId}/event-characters/${characterId}`);
    return response.data;
  },

  async updateEventCharacterAccess(activityId, userIds) {
    const response = await api.put(`/api/rp-activities/${activityId}/event-character-access`, { userIds });
    return response.data;
  },

  async getSelectableCharacters(activityId) {
    const response = await api.get(`/api/rp-activities/${activityId}/selectable-characters`);
    return response.data;
  },

  async registerCharacter(activityId, characterId) {
    const response = await api.post(`/api/rp-activities/${activityId}/register`, { characterId });
    return response.data;
  },

  async unregisterCharacter(activityId, characterId) {
    const response = await api.delete(`/api/rp-activities/${activityId}/register/${characterId}`);
    return response.data;
  },

  async approveRegistration(activityId, registrationId) {
    const response = await api.post(`/api/rp-activities/${activityId}/registrations/${registrationId}/approve`);
    return response.data;
  },
};

export default rpActivityService;
