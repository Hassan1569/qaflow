import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api';

export const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor: attach the bearer token if it exists.
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('qaflow_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor: unwrap data and normalize errors.
apiClient.interceptors.response.use(
  (response) => {
    // The API always returns { data: ..., meta: ... } for success.
    // Return the whole payload so callers can access both.
    return response.data;
  },
  (error) => {
    const status = error.response?.status;
    const payload = error.response?.data;
    
    // Build a normalized error object
    const normalizedError = {
      status: status || 500,
      code: payload?.error?.code || 'UNKNOWN_ERROR',
      message: payload?.error?.message || error.message || 'An unexpected error occurred.',
      details: payload?.error?.details || null,
    };

    // Handle 401 Unauthorized globally
    if (status === 401) {
      localStorage.removeItem('qaflow_token');
      // We don't use window.location.href here to avoid a full page reload,
      // the AuthContext will handle the redirect via state updates if we
      // dispatch an event.
      window.dispatchEvent(new Event('qaflow:unauthorized'));
    }

    return Promise.reject(normalizedError);
  }
);