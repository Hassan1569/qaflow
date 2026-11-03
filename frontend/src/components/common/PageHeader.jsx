import React from 'react';

export function PageHeader({ title, description, action }) {
  return (
    <div className="md:flex md:items-center md:justify-between mb-6 pb-4 border-b border-border">
      <div className="flex-1 min-w-0">
        <h2 className="text-2xl font-bold leading-7 text-text sm:text-3xl sm:truncate">
          {title}
        </h2>
        {description && (
          <p className="mt-1 text-sm text-text-muted">
            {description}
          </p>
        )}
      </div>
      {action && (
        <div className="mt-4 flex md:mt-0 md:ml-4">
          {action}
        </div>
      )}
    </div>
  );
}