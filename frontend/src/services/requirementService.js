import { apiClient } from './apiClient';

export const requirementService = {
  /**
   * Fetch a paginated list of requirements.
   * @param {Object} params - Query parameters: page, per_page, project_id, search, status, priority.
   */
  list: async (params = {}) => {
    const response = await apiClient.get('/requirements', { params });
    return response;
  },

  /**
   * Fetch a single requirement by ID.
   * @param {number} id
   */
  get: async (id) => {
    const response = await apiClient.get(`/requirements/${id}`);
    return response.data;
  },

  /**
   * Create a new requirement.
   * @param {Object} data - { project_id, title, description, priority, status }
   */
  create: async (data) => {
    const response = await apiClient.post('/requirements', data);
    return response.data;
  },

  /**
   * Update an existing requirement.
   * @param {number} id
   * @param {Object} data
   */
  update: async (id, data) => {
    const response = await apiClient.put(`/requirements/${id}`, data);
    return response.data;
  },

  /**
   * Delete a requirement.
   * @param {number} id
   */
  delete: async (id) => {
    await apiClient.delete(`/requirements/${id}`);
  },

  /**
   * Fetch test cases linked to a requirement.
   * @param {number} id
   */
  getTestCases: async (id) => {
    const response = await apiClient.get(`/requirements/${id}/test-cases`);
    return response.data;
  },
};