import { apiClient } from './apiClient';

export const commentService = {
  /**
   * Fetch a list of comments for a specific entity.
   * @param {string} entityType - e.g., 'defect', 'test_case'
   * @param {number} entityId
   */
  list: async (entityType, entityId) => {
    const response = await apiClient.get('/comments', {
      params: { entity_type: entityType, entity_id: entityId },
    });
    return response.data;
  },

  /**
   * Create a new comment.
   * @param {Object} data - { entity_type, entity_id, content }
   */
  create: async (data) => {
    const response = await apiClient.post('/comments', data);
    return response.data;
  },

  /**
   * Delete a comment.
   * (Not currently supported by the backend but added for completeness)
   * @param {number} id
   */
  delete: async (id) => {
    await apiClient.delete(`/comments/${id}`);
  },
};