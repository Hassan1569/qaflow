import { apiClient } from './apiClient';

export const defectService = {
  /**
   * Fetch a paginated list of defects.
   * @param {Object} params - Query parameters: page, per_page, project_id, search, status, severity, priority, assignee_id, test_case_id, requirement_id.
   */
  list: async (params = {}) => {
    const response = await apiClient.get('/defects', { params });
    return response;
  },

  /**
   * Fetch a single defect by ID.
   * @param {number} id
   */
  get: async (id) => {
    const response = await apiClient.get(`/defects/${id}`);
    return response.data;
  },

  /**
   * Create a new defect.
   * @param {Object} data - { project_id, title, description, expected_result, actual_result, severity, priority, status, test_case_id, requirement_id, test_result_id, environment_id, assignee_id }
   */
  create: async (data) => {
    const response = await apiClient.post('/defects', data);
    return response.data;
  },

  /**
   * Update an existing defect.
   * @param {number} id
   * @param {Object} data
   */
  update: async (id, data) => {
    const response = await apiClient.put(`/defects/${id}`, data);
    return response.data;
  },

  /**
   * Change the status of a defect.
   * @param {number} id
   * @param {string} status
   */
  changeStatus: async (id, status) => {
    await apiClient.post(`/defects/${id}/status`, { status });
  },
};