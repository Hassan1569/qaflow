import React, { useState, useEffect } from 'react';
import { PageHeader } from '../components/common/PageHeader';
import { Select } from '../components/common/Select';
import { DataTable } from '../components/common/DataTable';
import { LoadingState } from '../components/common/LoadingState';
import { ErrorState } from '../components/common/ErrorState';
import { StatusBadge } from '../components/common/StatusBadge';
import { PriorityBadge } from '../components/common/PriorityBadge';
import { traceabilityService } from '../services/traceabilityService';
import { projectService } from '../services/projectService';
import { formatPercent } from '../utils/format';

export default function TraceabilityPage() {
  const [projects, setProjects] = useState([]);
  const [selectedProject, setSelectedProject] = useState('');
  const [matrix, setMatrix] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    const loadProjects = async () => {
      try {
        const res = await projectService.list({ per_page: 100 });
        setProjects(res.data);
        if (res.data.length > 0) {
          setSelectedProject(res.data[0].id.toString());
        }
      } catch (err) {
        console.error(err);
      }
    };
    loadProjects();
  }, []);

  useEffect(() => {
    if (!selectedProject) return;
    
    const fetchMatrix = async () => {
      setLoading(true);
      setError(null);
      try {
        const data = await traceabilityService.getMatrix(selectedProject);
        setMatrix(data);
      } catch (err) {
        setError(err.message || 'Failed to load traceability matrix.');
      } finally {
        setLoading(false);
      }
    };
    
    fetchMatrix();
  }, [selectedProject]);

  const columns = [
    {
      key: 'requirement_code',
      label: 'Requirement',
      render: (row) => (
        <div>
          <span className="font-mono text-xs font-medium text-text-muted block">{row.requirement_code}</span>
          <span className="font-medium text-text">{row.requirement_title}</span>
        </div>
      )
    },
    {
      key: 'requirement_priority',
      label: 'Priority',
      className: 'w-24',
      render: (row) => <PriorityBadge priority={row.requirement_priority} />
    },
    {
      key: 'test_case_count',
      label: 'Test Cases',
      className: 'w-24 text-center',
      cellClassName: 'text-center',
      render: (row) => <span className="font-semibold">{row.test_case_count}</span>
    },
    {
      key: 'executed',
      label: 'Executed',
      className: 'w-24 text-center',
      cellClassName: 'text-center',
      render: (row) => <span className="font-semibold">{row.executed}</span>
    },
    {
      key: 'passed',
      label: 'Passed',
      className: 'w-24 text-center',
      cellClassName: 'text-center',
      render: (row) => <span className="font-semibold text-pass">{row.passed}</span>
    },
    {
      key: 'failed',
      label: 'Failed',
      className: 'w-24 text-center',
      cellClassName: 'text-center',
      render: (row) => <span className="font-semibold text-fail">{row.failed}</span>
    },
    {
      key: 'defects',
      label: 'Defects',
      className: 'w-24 text-center',
      cellClassName: 'text-center',
      render: (row) => <span className="font-semibold text-high">{row.defects}</span>
    },
    {
      key: 'coverage',
      label: 'Coverage',
      className: 'w-32 text-center',
      cellClassName: 'text-center',
      render: (row) => (
        <div className="flex items-center justify-center gap-2">
          <div className="w-16 bg-bg border border-border rounded-full h-2 overflow-hidden">
            <div className={`h-full ${row.coverage === 100 ? 'bg-pass' : 'bg-fail'}`} style={{ width: `${row.coverage}%` }} />
          </div>
          <span className="text-xs font-medium w-8">{row.coverage}%</span>
        </div>
      )
    }
  ];

  return (
    <div>
      <PageHeader 
        title="Traceability Matrix" 
        description="Verify that requirements are covered by test cases and executed."
      />

      <div className="mb-6 flex items-center gap-2 bg-surface p-3 rounded-md border border-border w-fit">
        <span className="text-sm font-medium text-text-muted">Project:</span>
        <Select value={selectedProject} onChange={(e) => setSelectedProject(e.target.value)} className="!py-1.5 !text-sm min-w-[200px]">
          {projects.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
        </Select>
      </div>

      {loading ? (
        <LoadingState />
      ) : error ? (
        <ErrorState message={error} />
      ) : matrix ? (
        <div className="space-y-6">
          {/* Summary Bar */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="bg-surface p-4 rounded-md border border-border flex flex-col items-center justify-center">
              <span className="text-3xl font-bold text-text">{matrix.totals.requirements}</span>
              <span className="text-xs text-text-muted uppercase mt-1">Total Requirements</span>
            </div>
            <div className="bg-surface p-4 rounded-md border border-border flex flex-col items-center justify-center">
              <span className="text-3xl font-bold text-pass">{matrix.totals.with_test_cases}</span>
              <span className="text-xs text-text-muted uppercase mt-1">With Test Cases</span>
            </div>
            <div className="bg-surface p-4 rounded-md border border-border flex flex-col items-center justify-center">
              <span className="text-3xl font-bold text-fail">{matrix.totals.defects}</span>
              <span className="text-xs text-text-muted uppercase mt-1">Total Linked Defects</span>
            </div>
            <div className="bg-surface p-4 rounded-md border border-border flex flex-col items-center justify-center">
              <span className="text-3xl font-bold text-primary">{formatPercent(matrix.totals.coverage, 1)}</span>
              <span className="text-xs text-text-muted uppercase mt-1">Project Coverage</span>
            </div>
          </div>

          <DataTable 
            columns={columns}
            data={matrix.rows}
            emptyTitle="No requirements found"
            emptyDescription="Add requirements and link them to test cases to see the matrix."
          />
        </div>
      ) : null}
    </div>
  );
}