import React from 'react';
import { EmptyState } from './EmptyState';
import { LoadingState } from './LoadingState';
import { Pagination } from './Pagination';

export function DataTable({ 
  columns, 
  data, 
  isLoading, 
  emptyTitle = 'No records found',
  emptyDescription,
  emptyAction,
  pagination,
  onRowClick,
  keyField = 'id'
}) {
  if (isLoading) {
    return <LoadingState message="Loading data..." />;
  }

  if (!data || data.length === 0) {
    return (
      <EmptyState 
        title={emptyTitle} 
        description={emptyDescription} 
        actionLabel={emptyAction?.label}
        onAction={emptyAction?.onClick}
      />
    );
  }

  return (
    <div className="bg-surface border border-border rounded-md overflow-hidden flex flex-col">
      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-border">
          <thead className="bg-bg">
            <tr>
              {columns.map((col, index) => (
                <th
                  key={col.key || index}
                  scope="col"
                  className={`px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider ${col.className || ''}`}
                >
                  {col.label}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="bg-surface divide-y divide-border">
            {data.map((row, rowIndex) => (
              <tr 
                key={row[keyField] || rowIndex}
                onClick={onRowClick ? () => onRowClick(row) : undefined}
                className={`transition-colors ${onRowClick ? 'cursor-pointer hover:bg-bg' : ''}`}
              >
                {columns.map((col, colIndex) => (
                  <td 
                    key={col.key || colIndex}
                    className={`px-4 py-3 whitespace-nowrap text-sm ${col.cellClassName || 'text-text'}`}
                  >
                    {col.render ? col.render(row) : row[col.key]}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      
      {pagination && (
        <Pagination 
          page={pagination.page}
          perPage={pagination.perPage}
          total={pagination.total}
          onPageChange={pagination.onPageChange}
          onPerPageChange={pagination.onPerPageChange}
        />
      )}
    </div>
  );
}