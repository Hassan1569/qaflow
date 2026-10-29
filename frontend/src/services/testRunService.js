import { apiClient } from './apiClient';

export const testRunService = {
  /**
   * Fetch a paginated list of test runs.
   * @param {Object} params - Query parameters: page, per_page, project_id, plan_id, status, assigned_to, search.
   */
  list: async (params = {}) => {
    const response = await apiClient.get('/test-runs', { params });
    return response;
  },

  /**
   * Fetch a single test run by ID, including cases and progress.
   * @param {number} id
   */
  get: async (id) => {
    const response = await apiClient.get(`/test-runs/${id}`);
    return response.data;
  },

  /**
   * Create a new test run.
   * @param {Object} data - { project_id, plan_id, environment_id, name, description, test_case_ids, assigned_to }
   */
  create: async (data) => {
    const response = await apiClient.post('/test-runs', data);
    return response.data;
  },

  /**
   * Start a test run.
   * @param {number} id
   */
  start: async (id) => {
    await apiClient.post(`/test-runs/${id}/start`);
  },

  /**
   * Complete a test run.
   * @param {number} id
   */
  complete: async (id) => {
    await apiClient.post(`/test-runs/${id}/complete`);
  },
};