import React from 'react';

export function FilterBar({ children, className = '' }) {
  return (
    <div className={`flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center bg-surface p-3 rounded-md border border-border ${className}`}>
      {children}
    </div>
  );
}

export function FilterItem({ label, children }) {
  return (
    <div className="flex items-center gap-2">
      {label && <span className="text-xs text-text-muted whitespace-nowrap">{label}:</span>}
      {children}
    </div>
  );
}