import { apiClient } from './apiClient';

export const testPlanService = {
  /**
   * Fetch a paginated list of test plans.
   * @param {Object} params - Query parameters: page, per_page, project_id, status, search.
   */
  list: async (params = {}) => {
    const response = await apiClient.get('/test-plans', { params });
    return response;
  },

  /**
   * Fetch a single test plan by ID, including its runs.
   * @param {number} id
   */
  get: async (id) => {
    const response = await apiClient.get(`/test-plans/${id}`);
    return response.data;
  },

  /**
   * Create a new test plan.
   * @param {Object} data - { project_id, name, release_version, description, scope, status, start_date, end_date, owner_id }
   */
  create: async (data) => {
    const response = await apiClient.post('/test-plans', data);
    return response.data;
  },

  /**
   * Update an existing test plan.
   * @param {number} id
   * @param {Object} data
   */
  update: async (id, data) => {
    const response = await apiClient.put(`/test-plans/${id}`, data);
    return response.data;
  },
};