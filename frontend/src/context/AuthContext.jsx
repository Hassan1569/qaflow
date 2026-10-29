import React, { createContext, useState, useEffect, useCallback, useMemo } from 'react';
import { authService } from '../services/authService';

export const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  // Load the current user on mount if a token exists
  useEffect(() => {
    let mounted = true;
    const token = localStorage.getItem('qaflow_token');

    if (!token) {
      setIsLoading(false);
      return;
    }

    const fetchMe = async () => {
      try {
        const data = await authService.me();
        if (mounted) {
          setUser(data);
        }
      } catch (err) {
        if (mounted) {
          localStorage.removeItem('qaflow_token');
          setUser(null);
        }
      } finally {
        if (mounted) {
          setIsLoading(false);
        }
      }
    };

    fetchMe();

    return () => {
      mounted = false;
    };
  }, []);

  const login = useCallback(async (email, password) => {
    const data = await authService.login(email, password);
    localStorage.setItem('qaflow_token', data.token);
    setUser(data.user);
    return data.user;
  }, []);

  const logout = useCallback(async () => {
    try {
      await authService.logout();
    } catch (err) {
      // Ignore errors during logout (e.g., token already expired)
    } finally {
      localStorage.removeItem('qaflow_token');
      setUser(null);
    }
  }, []);

  const value = useMemo(() => ({
    user,
    isLoading,
    login,
    logout,
    isAuthenticated: !!user,
  }), [user, isLoading, login, logout]);

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
}