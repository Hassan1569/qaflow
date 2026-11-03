import React from 'react';
import { Link } from 'react-router-dom';

export function Breadcrumb({ items }) {
  if (!items || items.length === 0) return null;

  return (
    <nav className="flex mb-4" aria-label="Breadcrumb">
      <ol className="flex items-center space-x-2 text-sm text-text-muted">
        <li>
          <Link to="/dashboard" className="hover:text-text transition-colors">
            Dashboard
          </Link>
        </li>
        
        {items.map((item, index) => {
          const isLast = index === items.length - 1;
          return (
            <li key={index} className="flex items-center">
              <svg className="flex-shrink-0 h-4 w-4 text-border mx-1" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clipRule="evenodd" />
              </svg>
              {isLast || !item.to ? (
                <span className="text-text font-medium" aria-current="page">
                  {item.label}
                </span>
              ) : (
                <Link to={item.to} className="hover:text-text transition-colors">
                  {item.label}
                </Link>
              )}
            </li>
          );
        })}
      </ol>
    </nav>
  );
}