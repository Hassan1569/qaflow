import { apiClient } from './apiClient';

export const environmentService = {
  /**
   * Fetch a paginated list of environments or lightweight options.
   * @param {Object} params - Query parameters: page, per_page, project_id, search, options.
   */
  list: async (params = {}) => {
    const response = await apiClient.get('/environments', { params });
    return response;
  },

  /**
   * Fetch a single environment by ID.
   * @param {number} id
   */
  get: async (id) => {
    const response = await apiClient.get(`/environments/${id}`);
    return response.data;
  },

  /**
   * Create a new environment.
   * @param {Object} data - { project_id, name, browser, browser_version, os, os_version, device, device_version, app_version, notes }
   */
  create: async (data) => {
    const response = await apiClient.post('/environments', data);
    return response.data;
  },

  /**
   * Update an existing environment.
   * @param {number} id
   * @param {Object} data
   */
  update: async (id, data) => {
    const response = await apiClient.put(`/environments/${id}`, data);
    return response.data;
  },

  /**
   * Delete an environment.
   * @param {number} id
   */
  delete: async (id) => {
    await apiClient.delete(`/environments/${id}`);
  },
};