import { apiClient } from './apiClient';

export const userService = {
  /**
   * Fetch a paginated list of users or lightweight options for dropdowns.
   * @param {Object} params - Query parameters: page, per_page, search, role, is_active, options.
   */
  list: async (params = {}) => {
    const response = await apiClient.get('/users', { params });
    return response;
  },

  /**
   * Fetch a single user by ID.
   * @param {number} id
   */
  get: async (id) => {
    const response = await apiClient.get(`/users/${id}`);
    return response.data;
  },

  /**
   * Create a new user.
   * @param {Object} data - { name, email, password, role, is_active }
   */
  create: async (data) => {
    const response = await apiClient.post('/users', data);
    return response.data;
  },

  /**
   * Update an existing user.
   * @param {number} id
   * @param {Object} data
   */
  update: async (id, data) => {
    const response = await apiClient.put(`/users/${id}`, data);
    return response.data;
  },

  /**
   * Deactivate a user.
   * @param {number} id
   */
  deactivate: async (id) => {
    await apiClient.post(`/users/${id}/deactivate`);
  },
};