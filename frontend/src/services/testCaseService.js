import { apiClient } from './apiClient';

export const testCaseService = {
  /**
   * Fetch a paginated list of test cases.
   * @param {Object} params - Query parameters: page, per_page, project_id, suite_id, search, priority, test_type, automation_status, requirement_id.
   */
  list: async (params = {}) => {
    const response = await apiClient.get('/test-cases', { params });
    return response;
  },

  /**
   * Fetch a single test case by ID.
   * @param {number} id
   */
  get: async (id) => {
    const response = await apiClient.get(`/test-cases/${id}`);
    return response.data;
  },

  /**
   * Create a new test case.
   * @param {Object} data - { project_id, suite_id, title, description, preconditions, priority, severity, test_type, tags, steps, requirement_ids }
   */
  create: async (data) => {
    const response = await apiClient.post('/test-cases', data);
    return response.data;
  },

  /**
   * Update an existing test case.
   * @param {number} id
   * @param {Object} data
   */
  update: async (id, data) => {
    const response = await apiClient.put(`/test-cases/${id}`, data);
    return response.data;
  },

  /**
   * Delete a test case.
   * @param {number} id
   */
  delete: async (id) => {
    await apiClient.delete(`/test-cases/${id}`);
  },

  /**
   * Fetch version history for a test case.
   * @param {number} id
   */
  getVersions: async (id) => {
    const response = await apiClient.get(`/test-cases/${id}/versions`);
    return response.data;
  },
};