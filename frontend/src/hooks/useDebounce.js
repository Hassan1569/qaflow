import { useState, useEffect } from 'react';

/**
 * Delays updating the returned value until after `delay` milliseconds have
 * elapsed since the last time the input value changed.
 * Useful for search inputs to prevent spamming the API.
 */
export function useDebounce(value, delay = 300) {
  const [debouncedValue, setDebouncedValue] = useState(value);

  useEffect(() => {
    const handler = setTimeout(() => {
      setDebouncedValue(value);
    }, delay);

    return () => {
      clearTimeout(handler);
    };
  }, [value, delay]);

  return debouncedValue;
}