import React, { forwardRef } from 'react';

export const Textarea = forwardRef(({ className = '', error, rows = 3, ...props }, ref) => {
  return (
    <textarea
      ref={ref}
      rows={rows}
      className={`block w-full rounded-md border ${error ? 'border-fail focus:ring-fail focus:border-fail' : 'border-border focus:ring-primary focus:border-primary'} bg-surface text-text sm:text-sm px-3 py-2 transition-colors focus:outline-none focus:ring-2 focus:ring-opacity-50 disabled:opacity-50 disabled:bg-bg ${className}`}
      {...props}
    />
  );
});

Textarea.displayName = 'Textarea';