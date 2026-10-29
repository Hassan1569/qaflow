import React, { forwardRef } from 'react';

export const Select = forwardRef(({ className = '', error, children, ...props }, ref) => {
  return (
    <select
      ref={ref}
      className={`block w-full rounded-md border ${error ? 'border-fail focus:ring-fail focus:border-fail' : 'border-border focus:ring-primary focus:border-primary'} bg-surface text-text sm:text-sm pl-3 pr-10 py-2 transition-colors focus:outline-none focus:ring-2 focus:ring-opacity-50 disabled:opacity-50 disabled:bg-bg ${className}`}
      {...props}
    >
      {children}
    </select>
  );
});

Select.displayName = 'Select';