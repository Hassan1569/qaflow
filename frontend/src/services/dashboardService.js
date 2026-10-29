import { apiClient } from './apiClient';

export const dashboardService = {
  /**
   * Fetch the aggregated summary metrics for the dashboard.
   * @param {number|null} projectId - Optional. If provided, scopes metrics to the project.
   */
  getSummary: async (projectId = null) => {
    const params = {};
    if (projectId) {
      params.project_id = projectId;
    }
    const response = await apiClient.get('/dashboard/summary', { params });
    return response.data;
  },

  /**
   * Fetch chart data for a specific project.
   * @param {number} projectId
   */
  getCharts: async (projectId) => {
    const response = await apiClient.get('/dashboard/charts', {
      params: { project_id: projectId },
    });
    return response.data;
  },
};