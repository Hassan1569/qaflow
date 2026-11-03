import React from 'react';
import { NavLink } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';
import { Icon } from '../common/Icon';

const NAV_ITEMS = [
  { label: 'Dashboard', path: '/dashboard', icon: 'dashboard' },
  { label: 'Projects', path: '/projects', icon: 'folder' },
  { label: 'Requirements', path: '/requirements', icon: 'document' },
  { label: 'Test Cases', path: '/test-cases', icon: 'check' },
  { label: 'Test Suites', path: '/test-suites', icon: 'folder' },
  { label: 'Test Plans', path: '/test-plans', icon: 'document' },
  { label: 'Test Runs', path: '/test-runs', icon: 'play' },
  { label: 'Defects', path: '/defects', icon: 'bug' },
  { label: 'Traceability', path: '/traceability', icon: 'chart' },
  { label: 'Reports', path: '/reports', icon: 'chart' },
  { label: 'Environments', path: '/environments', icon: 'settings' },
  { label: 'Team', path: '/team', icon: 'users' },
];

export function Sidebar({ onClose }) {
  const { user } = useAuth();

  return (
    <div className="flex flex-col h-full bg-surface border-r border-border w-64">
      {/* Logo area */}
      <div className="flex items-center h-16 flex-shrink-0 px-4 border-b border-border justify-between">
        <div className="flex items-center gap-2">
          <div className="w-8 h-8 rounded bg-primary flex items-center justify-center text-white font-bold text-lg">
            Q
          </div>
          <span className="text-xl font-bold text-text tracking-tight">QAFlow</span>
        </div>
        {onClose && (
          <button onClick={onClose} className="lg:hidden text-text-muted hover:text-text">
            <Icon name="close" />
          </button>
        )}
      </div>

      {/* Navigation */}
      <div className="flex-1 flex flex-col overflow-y-auto pt-4 pb-4">
        <nav className="flex-1 px-2 space-y-1">
          {NAV_ITEMS.map((item) => (
            <NavLink
              key={item.path}
              to={item.path}
              onClick={onClose}
              className={({ isActive }) => `
                group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors
                ${isActive 
                  ? 'bg-primary-light text-primary' 
                  : 'text-text-muted hover:bg-bg hover:text-text'}
              `}
            >
              <Icon name={item.icon} className="mr-3 flex-shrink-0 h-5 w-5" />
              {item.label}
            </NavLink>
          ))}
        </nav>
      </div>

      {/* User profile snippet */}
      {user && (
        <div className="flex-shrink-0 flex border-t border-border p-4">
          <div className="flex items-center w-full">
            <div className="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-white font-bold text-xs uppercase">
              {user.name.charAt(0)}
            </div>
            <div className="ml-3 flex-1 min-w-0">
              <p className="text-sm font-medium text-text truncate">{user.name}</p>
              <p className="text-xs text-text-muted truncate capitalize">{user.role.replace('_', ' ')}</p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}