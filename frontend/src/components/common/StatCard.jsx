import React from 'react';

export function StatCard({ title, value, icon, trend, description }) {
  return (
    <div className="bg-surface overflow-hidden shadow-card rounded-md border border-border p-5">
      <div className="flex items-center">
        {icon && (
          <div className="flex-shrink-0 bg-primary-light text-primary rounded-md p-3 mr-4">
            {icon}
          </div>
        )}
        <div className="flex-1">
          <p className="text-sm font-medium text-text-muted truncate">{title}</p>
          <div className="flex items-baseline">
            <p className="text-2xl font-semibold text-text">{value}</p>
            {trend && (
              <span className={`ml-2 text-sm font-medium ${trend > 0 ? 'text-pass' : trend < 0 ? 'text-fail' : 'text-text-muted'}`}>
                {trend > 0 ? '+' : ''}{trend}%
              </span>
            )}
          </div>
          {description && (
            <p className="text-xs text-text-muted mt-1">{description}</p>
          )}
        </div>
      </div>
    </div>
  );
}