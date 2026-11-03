import React from 'react';

export function ChartCard({ title, subtitle, children, action, className = '' }) {
  return (
    <div className={`bg-surface border border-border rounded-md shadow-card flex flex-col ${className}`}>
      <div className="px-5 py-4 border-b border-border flex items-center justify-between">
        <div>
          <h3 className="text-base font-semibold text-text">{title}</h3>
          {subtitle && <p className="text-xs text-text-muted mt-1">{subtitle}</p>}
        </div>
        {action && <div>{action}</div>}
      </div>
      <div className="p-5 flex-1 min-h-[300px]">
        {children}
      </div>
    </div>
  );
}