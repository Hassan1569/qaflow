import React from 'react';
import { Link } from 'react-router-dom';
import { StatusBadge } from '../common/StatusBadge';

export function ExecutionSidebar({ available, currentId, basePath }) {
  return (
    <div className="bg-surface border border-border rounded-md flex flex-col h-full overflow-hidden">
      <div className="px-4 py-3 border-b border-border bg-bg flex justify-between items-center">
        <h3 className="text-sm font-semibold text-text">Test Cases in Run</h3>
        <span className="text-xs text-text-muted">{available.length} total</span>
      </div>
      
      <div className="overflow-y-auto flex-1">
        <ul className="divide-y divide-border">
          {available.map((item) => {
            const isActive = item.run_case_id === currentId;
            return (
              <li key={item.run_case_id}>
                <Link 
                  to={`${basePath}/${item.run_case_id}`}
                  className={`block px-4 py-3 hover:bg-bg transition-colors ${isActive ? 'bg-primary-light border-l-2 border-primary' : ''}`}
                >
                  <div className="flex justify-between items-start mb-1">
                    <span className="font-mono text-xs font-medium text-text-muted">{item.code}</span>
                    <StatusBadge status={item.result_status || 'not_run'} />
                  </div>
                  <p className={`text-sm font-medium truncate ${isActive ? 'text-primary' : 'text-text'}`}>
                    {item.title}
                  </p>
                </Link>
              </li>
            );
          })}
        </ul>
      </div>
    </div>
  );
}