import React from 'react';
import { Button } from './Button';

export function EmptyState({ title, description, actionLabel, onAction, icon }) {
  return (
    <div className="text-center py-12 px-4 sm:px-6 lg:px-8 bg-surface rounded-md border border-border mt-4">
      <div className="mx-auto h-12 w-12 text-text-muted flex items-center justify-center rounded-full bg-bg mb-4">
        {icon || (
          <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
          </svg>
        )}
      </div>
      <h3 className="text-sm font-medium text-text">{title}</h3>
      {description && (
        <p className="mt-1 text-sm text-text-muted max-w-sm mx-auto">{description}</p>
      )}
      {actionLabel && onAction && (
        <div className="mt-6">
          <Button onClick={onAction} variant="primary" size="sm">
            {actionLabel}
          </Button>
        </div>
      )}
    </div>
  );
}