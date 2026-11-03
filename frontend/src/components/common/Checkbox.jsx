import React, { forwardRef } from 'react';

export const Checkbox = forwardRef(({ label, id, className = '', error, ...props }, ref) => {
  const checkboxId = id || `checkbox-${Math.random().toString(36).substr(2, 9)}`;

  return (
    <div className={`flex items-start ${className}`}>
      <div className="flex items-center h-5">
        <input
          id={checkboxId}
          ref={ref}
          type="checkbox"
          className={`h-4 w-4 rounded border-border text-primary focus:ring-primary focus:ring-opacity-50 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed ${error ? 'border-fail' : ''}`}
          {...props}
        />
      </div>
      {label && (
        <div className="ml-3 text-sm">
          <label htmlFor={checkboxId} className="font-medium text-text cursor-pointer">
            {label}
          </label>
          {error && <p className="mt-1 text-xs text-fail">{error}</p>}
        </div>
      )}
    </div>
  );
});

Checkbox.displayName = 'Checkbox';