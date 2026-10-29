import { apiClient } from './apiClient';

export const testSuiteService = {
  /**
   * Fetch a paginated list of test suites.
   * @param {Object} params - Query parameters: page, per_page, project_id, search.
   */
  list: async (params = {}) => {
    const response = await apiClient.get('/test-suites', { params });
    return response;
  },

  /**
   * Fetch the suite tree for a project.
   * @param {number} projectId
   */
  getTree: async (projectId) => {
    const response = await apiClient.get('/test-suites', { 
      params: { project_id: projectId, tree: 1 } 
    });
    return response.data;
  },

  /**
   * Create a new test suite.
   * @param {Object} data - { project_id, parent_id?, name, description? }
   */
  create: async (data) => {
    const response = await apiClient.post('/test-suites', data);
    return response.data;
  },

  /**
   * Update an existing test suite.
   * @param {number} id
   * @param {Object} data
   */
  update: async (id, data) => {
    const response = await apiClient.put(`/test-suites/${id}`, data);
    return response.data;
  },

  /**
   * Delete a test suite.
   * @param {number} id
   */
  delete: async (id) => {
    await apiClient.delete(`/test-suites/${id}`);
  },
};