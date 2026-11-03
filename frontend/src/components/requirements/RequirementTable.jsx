import React from 'react';
import { Link } from 'react-router-dom';
import { DataTable } from '../common/DataTable';
import { StatusBadge } from '../common/StatusBadge';
import { PriorityBadge } from '../common/PriorityBadge';
import { truncate } from '../../utils/format';

export function RequirementTable({ 
  data, isLoading, page, perPage, total, 
  onPageChange, onPerPageChange, onSearch, 
  onCreate, emptyDescription 
}) {
  const columns = [
    {
      key: 'code',
      label: 'Code',
      className: 'w-24',
      render: (row) => <span className="font-mono text-xs font-medium text-text-muted">{row.code}</span>
    },
    {
      key: 'title',
      label: 'Title',
      render: (row) => (
        <Link to={`/requirements/${row.id}`} className="text-primary hover:underline font-medium">
          {row.title}
        </Link>
      )
    },
    {
      key: 'priority',
      label: 'Priority',
      className: 'w-28',
      render: (row) => <PriorityBadge priority={row.priority} />
    },
    {
      key: 'status',
      label: 'Status',
      className: 'w-32',
      render: (row) => <StatusBadge status={row.status} />
    },
    {
      key: 'test_case_count',
      label: 'Test Cases',
      className: 'w-24 text-right',
      cellClassName: 'text-right',
      render: (row) => (
        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-bg border border-border text-text-muted">
          {row.test_case_count || 0}
        </span>
      )
    }
  ];

  return (
    <DataTable
      columns={columns}
      data={data}
      isLoading={isLoading}
      emptyTitle="No requirements found"
      emptyDescription={emptyDescription || "Create your first requirement to start tracking coverage."}
      emptyAction={{ label: 'Create Requirement', onClick: onCreate }}
      pagination={{
        page, perPage, total,
        onPageChange, onPerPageChange
      }}
    />
  );
}