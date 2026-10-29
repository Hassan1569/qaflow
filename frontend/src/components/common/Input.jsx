import React, { forwardRef } from 'react';

export const Input = forwardRef(({ className = '', error, ...props }, ref) => {
  return (
    <input
      ref={ref}
      className={`block w-full rounded-md border ${error ? 'border-fail focus:ring-fail focus:border-fail' : 'border-border focus:ring-primary focus:border-primary'} bg-surface text-text sm:text-sm px-3 py-2 transition-colors focus:outline-none focus:ring-2 focus:ring-opacity-50 disabled:opacity-50 disabled:bg-bg ${className}`}
      {...props}
    />
  );
});

Input.displayName = 'Input';