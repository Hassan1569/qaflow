import React from 'react';

export function Tabs({ tabs, activeTab, onChange }) {
  if (!tabs || tabs.length === 0) return null;

  return (
    <div className="border-b border-border mb-6">
      <nav className="-mb-px flex space-x-8" aria-label="Tabs">
        {tabs.map((tab) => {
          const isActive = activeTab === tab.id;
          return (
            <button
              key={tab.id}
              onClick={() => onChange(tab.id)}
              className={`
                whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors focus:outline-none
                ${isActive 
                  ? 'border-primary text-primary' 
                  : 'border-transparent text-text-muted hover:text-text hover:border-border'}
              `}
              aria-current={isActive ? 'page' : undefined}
            >
              {tab.label}
              {tab.count !== undefined && (
                <span className={`ml-2 py-0.5 px-2.5 rounded-full text-xs ${isActive ? 'bg-primary-light text-primary' : 'bg-bg text-text-muted'}`}>
                  {tab.count}
                </span>
              )}
            </button>
          );
        })}
      </nav>
    </div>
  );
}