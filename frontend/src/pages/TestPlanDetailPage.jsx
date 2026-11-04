import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { PageHeader } from '../components/common/PageHeader';
import { Breadcrumb } from '../components/common/Breadcrumb';
import { StatusBadge } from '../components/common/StatusBadge';
import { Button } from '../components/common/Button';
import { LoadingState } from '../components/common/LoadingState';
import { ErrorState } from '../components/common/ErrorState';
import { DataTable } from '../components/common/DataTable';
import { testPlanService } from '../services/testPlanService';
import { formatDate } from '../utils/format';

export default function TestPlanDetailPage() {
  const { id } = useParams();
  const [plan, setPlan] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchPlan = async () => {
      try {
        const data = await testPlanService.get(id);
        setPlan(data);
      } catch (err) {
        setError(err.message || 'Failed to load test plan.');
      } finally {
        setLoading(false);
      }
    };
    fetchPlan();
  }, [id]);

  if (loading) return <LoadingState />;
  if (error) return <ErrorState message={error} />;
  if (!plan) return null;

  const runColumns = [
    {
      key: 'name',
      label: 'Run Name',
      render: (row) => (
        <Link to={`/test-runs/${row.id}`} className="text-primary hover:underline font-medium">
          {row.name}
        </Link>
      )
    },
    {
      key: 'status',
      label: 'Status',
      className: 'w-32',
      render: (row) => <StatusBadge status={row.status} />
    },
    {
      key: 'environment_name',
      label: 'Environment',
      className: 'w-40',
      render: (row) => <span className="text-sm text-text-muted">{row.environment_name || '—'}</span>
    },
    {
      key: 'progress',
      label: 'Progress',
      className: 'w-48',
      render: (row) => {
        const total = row.total_cases || 0;
        const passed = row.passed_cases || 0;
        const pct = total > 0 ? Math.round((passed / total) * 100) : 0;
        return (
          <div className="flex items-center gap-2">
            <div className="flex-1 bg-bg border border-border rounded-full h-2 overflow-hidden">
              <div className="bg-pass h-full" style={{ width: `${pct}%` }} />
            </div>
            <span className="text-xs font-medium w-8 text-right">{pct}%</span>
          </div>
        );
      }
    },
    {
      key: 'assigned_name',
      label: 'Assignee',
      className: 'w-40',
      render: (row) => <span className="text-sm text-text-muted">{row.assigned_name || 'Unassigned'}</span>
    }
  ];

  return (
    <div>
      <Breadcrumb items={[
        { label: 'Projects', to: '/projects' },
        { label: plan.project_key, to: `/projects/${plan.project_id}` },
        { label: 'Test Plans', to: `/test-plans?project_id=${plan.project_id}` },
        { label: plan.name }
      ]} />

      <PageHeader 
        title={plan.name} 
        description={`Release: ${plan.release_version || 'N/A'} • Owner: ${plan.owner_name}`}
        action={
          <Link to={`/test-runs?project_id=${plan.project_id}&plan_id=${plan.id}`}>
            <Button variant="primary">View All Runs</Button>
          </Link>
        }
      />

      <div className="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
        <div className="bg-surface border border-border rounded-md p-5 lg:col-span-3">
          <h3 className="text-lg font-semibold text-text mb-3">Plan Details</h3>
          <div className="flex gap-6 mb-4">
            <div>
              <p className="text-xs text-text-muted mb-1">Status</p>
              <StatusBadge status={plan.status} />
            </div>
            <div>
              <p className="text-xs text-text-muted mb-1">Start Date</p>
              <p className="text-sm font-medium">{plan.start_date ? formatDate(plan.start_date) : 'Not set'}</p>
            </div>
            <div>
              <p className="text-xs text-text-muted mb-1">End Date</p>
              <p className="text-sm font-medium">{plan.end_date ? formatDate(plan.end_date) : 'Not set'}</p>
            </div>
          </div>
          {plan.description && (
            <div className="mb-4">
              <p className="text-xs text-text-muted mb-1">Description</p>
              <p className="text-sm whitespace-pre-wrap">{plan.description}</p>
            </div>
          )}
          {plan.scope && (
            <div>
              <p className="text-xs text-text-muted mb-1">Scope</p>
              <p className="text-sm bg-bg p-3 rounded border border-border whitespace-pre-wrap">{plan.scope}</p>
            </div>
          )}
        </div>
      </div>

      <h3 className="text-lg font-semibold text-text mb-3">Test Runs in this Plan</h3>
      <DataTable 
        columns={runColumns}
        data={plan.runs || []}
        emptyTitle="No test runs yet"
        emptyDescription="Create a test run to begin execution for this plan."
      />
    </div>
  );
}