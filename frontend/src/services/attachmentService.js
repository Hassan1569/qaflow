import { apiClient } from './apiClient';

export const attachmentService = {
  /**
   * Upload an attachment for an entity (defect, test case, execution, etc.).
   * @param {File} file
   * @param {string} entityType
   * @param {number} entityId
   */
  upload: async (file, entityType, entityId) => {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('entity_type', entityType);
    formData.append('entity_id', entityId);

    const response = await apiClient.post('/attachments', formData, {
      headers: {
        // Let the browser set the correct multipart boundary
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  /**
   * Download or view an attachment. Since this is a file download, we
   * return the URL and let the browser handle it (the API includes the
   * bearer token via a cookie or the UI opens it in a new tab).
   * 
   * Note: If the API requires the Authorization header for file downloads,
   * this service should be updated to fetch as a blob and trigger a download.
   * 
   * @param {number} id
   */
  getDownloadUrl: (id) => {
    const baseUrl = import.meta.env.VITE_API_BASE_URL || '/api';
    return `${baseUrl}/attachments/${id}`;
  },

  /**
   * Delete an attachment.
   * @param {number} id
   */
  delete: async (id) => {
    await apiClient.delete(`/attachments/${id}`);
  },
};