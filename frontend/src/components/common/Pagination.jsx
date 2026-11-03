import React from 'react';
import { PAGE_SIZES } from '../../utils/constants';

export function Pagination({ page, perPage, total, onPageChange, onPerPageChange }) {
  const totalPages = Math.ceil(total / perPage) || 1;
  const startItem = (page - 1) * perPage + 1;
  const endItem = Math.min(page * perPage, total);

  if (total === 0) return null;

  return (
    <div className="bg-surface px-4 py-3 flex items-center justify-between border-t border-border sm:px-6 rounded-b-md">
      <div className="flex-1 flex justify-between sm:hidden">
        <button
          onClick={() => onPageChange(page - 1)}
          disabled={page <= 1}
          className="relative inline-flex items-center px-4 py-2 border border-border text-sm font-medium rounded-md text-text bg-surface hover:bg-bg disabled:opacity-50"
        >
          Previous
        </button>
        <button
          onClick={() => onPageChange(page + 1)}
          disabled={page >= totalPages}
          className="ml-3 relative inline-flex items-center px-4 py-2 border border-border text-sm font-medium rounded-md text-text bg-surface hover:bg-bg disabled:opacity-50"
        >
          Next
        </button>
      </div>
      <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div className="flex items-center gap-4">
          <p className="text-sm text-text">
            Showing <span className="font-medium">{startItem}</span> to <span className="font-medium">{endItem}</span> of <span className="font-medium">{total}</span> results
          </p>
          {onPerPageChange && (
            <select
              value={perPage}
              onChange={(e) => onPerPageChange(Number(e.target.value))}
              className="block w-full pl-3 pr-10 py-1 text-base border-border focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md bg-surface border"
            >
              {PAGE_SIZES.map(size => (
                <option key={size} value={size}>{size} / page</option>
              ))}
            </select>
          )}
        </div>
        <div>
          <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <button
              onClick={() => onPageChange(page - 1)}
              disabled={page <= 1}
              className="relative inline-flex items-center px-2 py-2 rounded-l-md border border-border bg-surface text-sm font-medium text-text-muted hover:bg-bg disabled:opacity-50"
            >
              <span className="sr-only">Previous</span>
              <svg className="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clipRule="evenodd" />
              </svg>
            </button>
            
            <span className="relative inline-flex items-center px-4 py-2 border border-border bg-surface text-sm font-medium text-text">
              Page {page} of {totalPages}
            </span>

            <button
              onClick={() => onPageChange(page + 1)}
              disabled={page >= totalPages}
              className="relative inline-flex items-center px-2 py-2 rounded-r-md border border-border bg-surface text-sm font-medium text-text-muted hover:bg-bg disabled:opacity-50"
            >
              <span className="sr-only">Next</span>
              <svg className="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clipRule="evenodd" />
              </svg>
            </button>
          </nav>
        </div>
      </div>
    </div>
  );
}