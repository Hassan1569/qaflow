import { apiClient } from './apiClient';

export const authService = {
  /**
   * Authenticate a user and receive a token.
   */
  login: async (email, password) => {
    const response = await apiClient.post('/auth/login', { email, password });
    return response.data;
  },

  /**
   * Invalidate the current token.
   */
  logout: async () => {
    await apiClient.post('/auth/logout');
  },

  /**
   * Fetch the currently authenticated user's profile.
   */
  me: async () => {
    const response = await apiClient.get('/auth/me');
    return response.data;
  },
};