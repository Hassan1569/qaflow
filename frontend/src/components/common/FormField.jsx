import React from 'react';

export function FormField({ label, htmlFor, error, helpText, required, children }) {
  return (
    <div className="mb-4">
      {label && (
        <label htmlFor={htmlFor} className="block text-sm font-medium text-text mb-1">
          {label}
          {required && <span className="text-fail ml-1">*</span>}
        </label>
      )}
      
      {children}
      
      {error && (
        <p className="mt-1 text-xs text-fail" role="alert">
          {error}
        </p>
      )}
      
      {helpText && !error && (
        <p className="mt-1 text-xs text-text-muted">
          {helpText}
        </p>
      )}
    </div>
  );
}