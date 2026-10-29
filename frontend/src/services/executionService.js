import { apiClient } from './apiClient';

export const executionService = {
  /**
   * Fetch full detail for a specific execution (run_case).
   * @param {number} runCaseId
   */
  get: async (runCaseId) => {
    const response = await apiClient.get(`/executions/${runCaseId}`);
    return response.data;
  },

  /**
   * Fetch sibling executions in the same run (for the sidebar).
   * @param {number} runCaseId
   */
  getAvailable: async (runCaseId) => {
    const response = await apiClient.get(`/executions/${runCaseId}/available`);
    return response.data;
  },

  /**
   * Save the result of a single step.
   * @param {number} runCaseId
   * @param {number} stepOrder
   * @param {Object} data - { status, actual_result, notes }
   */
  saveStepResult: async (runCaseId, stepOrder, data) => {
    const response = await apiClient.post(`/executions/${runCaseId}/steps/${stepOrder}/result`, data);
    return response.data;
  },

  /**
   * Save the overall result for the execution.
   * @param {number} runCaseId
   * @param {Object} data - { status, actual_result, notes }
   */
  saveResult: async (runCaseId, data) => {
    const response = await apiClient.post(`/executions/${runCaseId}/result`, data);
    return response.data;
  },
};