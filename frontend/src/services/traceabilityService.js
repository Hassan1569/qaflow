import { apiClient } from './apiClient';

export const traceabilityService = {
  /**
   * Fetch the requirement traceability matrix for a project.
   * @param {number} projectId
   */
  getMatrix: async (projectId) => {
    const response = await apiClient.get('/traceability/matrix', {
      params: { project_id: projectId },
    });
    return response.data;
  },
};