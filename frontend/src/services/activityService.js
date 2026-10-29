import { apiClient } from './apiClient';

export const activityService = {
  /**
   * Fetch a paginated list of activity logs.
   * @param {Object} params - Query parameters: page, per_page, project_id, user_id, entity_type, entity_id, action, since.
   */
  list: async (params = {}) => {
    const response = await apiClient.get('/activity', { params });
    return response;
  },
};