import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { PageHeader } from '../components/common/PageHeader';
import { Breadcrumb } from '../components/common/Breadcrumb';
import { StatusBadge } from '../components/common/StatusBadge';
import { Button } from '../components/common/Button';
import { LoadingState } from '../components/common/LoadingState';
import { ErrorState } from '../components/common/ErrorState';
import { DataTable } from '../components/common/DataTable';
import { TestRunProgress } from '../components/testRuns/TestRunProgress';
import { testRunService } from '../services/testRunService';
import { usePermissions } from '../hooks/usePermissions';
import { useToast } from '../hooks/useToast';

export default function TestRunDetailPage() {
  const { id } = useParams();
  const [run, setRun] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [isActionLoading, setIsActionLoading] = useState(false);

  const { canExecuteTests, canManageTestCases } = usePermissions();
  const toast = useToast();

  const fetchRun = async () => {
    try {
      const data = await testRunService.get(id);
      setRun(data);
    } catch (err) {
      setError(err.message || 'Failed to load test run.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchRun();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id]);

  const handleStart = async () => {
    setIsActionLoading(true);
    try {
      await testRunService.start(id);
      toast.success('Run started.');
      fetchRun();
    } catch (err) {
      toast.error(err.message || 'Failed to start run.');
    } finally {
      setIsActionLoading(false);
    }
  };

  const handleComplete = async () => {
    if (!window.confirm('Mark this run as completed? No further executions will be allowed.')) return;
    setIsActionLoading(true);
    try {
      await testRunService.complete(id);
      toast.success('Run completed.');
      fetchRun();
    } catch (err) {
      toast.error(err.message || 'Failed to complete run.');
    } finally {
      setIsActionLoading(false);
    }
  };

  if (loading) return <LoadingState />;
  if (error) return <ErrorState message={error} />;
  if (!run) return null;

  const caseColumns = [
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
        <Link to={`/execution/${row.run_case_id}`} className="text-primary hover:underline font-medium">
          {row.title}
        </Link>
      )
    },
    {
      key: 'assignee_name',
      label: 'Assignee',
      className: 'w-40',
      render: (row) => <span className="text-sm text-text-muted">{row.assignee_name || 'Unassigned'}</span>
    },
    {
      key: 'result_status',
      label: 'Status',
      className: 'w-32',
      render: (row) => <StatusBadge status={row.result_status || 'not_run'} />
    },
    {
      key: 'action',
      label: '',
      className: 'w-24 text-right',
      render: (row) => (
        <Link to={`/execution/${row.run_case_id}`}>
          <Button variant="secondary" size="sm" className="w-full">Execute</Button>
        </Link>
      )
    }
  ];

  return (
    <div>
      <Breadcrumb items={[
        { label: 'Projects', to: '/projects' },
        { label: run.project_key, to: `/projects/${run.project_id}` },
        { label: 'Test Runs', to: `/test-runs?project_id=${run.project_id}` },
        { label: run.name }
      ]} />

      <PageHeader 
        title={run.name} 
        description={`Environment: ${run.environment_name || 'None'} • Assignee: ${run.assigned_name || 'Unassigned'}`}
        action={
          <div className="flex gap-2">
            {run.status === 'not_started' && canExecuteTests() && (
              <Button variant="primary" onClick={handleStart} isLoading={isActionLoading}>Start Run</Button>
            )}
            {run.status === 'in_progress' && canManageTestCases() && (
              <Button variant="primary" onClick={handleComplete} isLoading={isActionLoading}>Complete Run</Button>
            )}
            <StatusBadge status={run.status} className="!px-4 !py-2 !text-sm" />
          </div>
        }
      />

      <div className="mb-6">
        <TestRunProgress progress={run.progress} />
      </div>

      <DataTable 
        columns={caseColumns}
        data={run.cases || []}
        emptyTitle="No test cases in this run"
      />
    </div>
  );
}