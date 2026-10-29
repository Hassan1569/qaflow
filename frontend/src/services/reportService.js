import { apiClient } from './apiClient';

export const reportService = {
  /**
   * Fetch the Test Execution Report.
   * @param {number} projectId
   */
  getExecution: async (projectId) => {
    const response = await apiClient.get('/reports/execution', {
      params: { project_id: projectId },
    });
    return response.data;
  },

  /**
   * Fetch the Defect Report.
   * @param {number} projectId
   */
  getDefects: async (projectId) => {
    const response = await apiClient.get('/reports/defects', {
      params: { project_id: projectId },
    });
    return response.data;
  },

  /**
   * Fetch the Coverage Report.
   * @param {number} projectId
   */
  getCoverage: async (projectId) => {
    const response = await apiClient.get('/reports/coverage', {
      params: { project_id: projectId },
    });
    return response.data;
  },

  /**
   * Fetch the Project QA Summary.
   * @param {number} projectId
   */
  getProjectSummary: async (projectId) => {
    const response = await apiClient.get('/reports/project-summary', {
      params: { project_id: projectId },
    });
    return response.data;
  },
};