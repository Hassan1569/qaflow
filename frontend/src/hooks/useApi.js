import { useState, useCallback, useRef, useEffect } from 'react';

/**
 * A generic hook for handling API calls with loading and error states.
 * Prevents state updates on unmounted components.
 */
export function useApi(apiFunc, immediate = false) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(immediate);
  const [error, setError] = useState(null);
  
  // Use a ref to track if the component is mounted
  const mounted = useRef(true);

  useEffect(() => {
    mounted.current = true;
    return () => {
      mounted.current = false;
    };
  }, []);

  const execute = useCallback(async (...args) => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await apiFunc(...args);
      if (mounted.current) {
        setData(response);
      }
      return response;
    } catch (err) {
      if (mounted.current) {
        setError(err);
      }
      throw err;
    } finally {
      if (mounted.current) {
        setLoading(false);
      }
    }
  }, [apiFunc]);

  useEffect(() => {
    if (immediate) {
      execute();
    }
  }, [execute, immediate]);

  return { data, loading, error, execute, setData };
}