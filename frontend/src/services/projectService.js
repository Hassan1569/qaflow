import { apiClient } from './apiClient';

export const projectService = {
  /**
   * Fetch a paginated list of projects.
   * @param {Object} params - Query parameters: page, per_page, search, status.
   */
  list: async (params = {}) => {
    const response = await apiClient.get('/projects', { params });
    return response;
  },

  /**
   * Fetch a single project by ID.
   * @param {number} id
   */
  get: async (id) => {
    const response = await apiClient.get(`/projects/${id}`);
    return response.data;
  },

  /**
   * Create a new project.
   * @param {Object} data - { key_code, name, description, owner_id }
   */
  create: async (data) => {
    const response = await apiClient.post('/projects', data);
    return response.data;
  },

  /**
   * Update an existing project.
   * @param {number} id
   * @param {Object} data
   */
  update: async (id, data) => {
    const response = await apiClient.put(`/projects/${id}`, data);
    return response.data;
  },

  /**
   * Archive a project.
   * @param {number} id
   */
  archive: async (id) => {
    await apiClient.post(`/projects/${id}/archive`);
  },

  /**
   * Fetch project members.
   * @param {number} id
   */
  getMembers: async (id) => {
    const response = await apiClient.get(`/projects/${id}/members`);
    return response.data;
  },

  /**
   * Add a member to a project.
   * @param {number} id
   * @param {Object} data - { user_id, role }
   */
  addMember: async (id, data) => {
    await apiClient.post(`/projects/${id}/members`, data);
  },

  /**
   * Remove a member from a project.
   * @param {number} id
   * @param {number} userId
   */
  removeMember: async (id, userId) => {
    await apiClient.delete(`/projects/${id}/members/${userId}`);
  },
};