import React, { useState, useEffect, useCallback } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import { PageHeader } from '../components/common/PageHeader';
import { SearchBar } from '../components/common/SearchBar';
import { FilterBar } from '../components/common/FilterBar';
import { Button } from '../components/common/Button';
import { Select } from '../components/common/Select';
import { DataTable } from '../components/common/DataTable';
import { StatusBadge } from '../components/common/StatusBadge';
import { TestRunForm } from '../components/testRuns/TestRunForm';
import { Modal } from '../components/common/Modal';
import { testRunService } from '../services/testRunService';
import { projectService } from '../services/projectService';
import { environmentService } from '../services/environmentService';
import { testPlanService } from '../services/testPlanService';
import { testCaseService } from '../services/testCaseService';
import { userService } from '../services/userService';
import { usePermissions } from '../hooks/usePermissions';
import { useToast } from '../hooks/useToast';
import { RUN_STATUSES } from '../utils/constants';
import { formatDate } from '../utils/format';

export default function TestRunsPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const projectIdParam = searchParams.get('project_id');
  const planIdParam = searchParams.get('plan_id');

  const [runs, setRuns] = useState([]);
  const [meta, setMeta] = useState({ page: 1, per_page: 25, total: 0 });
  const [projects, setProjects] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  
  // Form data
  const [environments, setEnvironments] = useState([]);
  const [plans, setPlans] = useState([]);
  const [testCases, setTestCases] = useState([]);
  const [users, setUsers] = useState([]);

  // Filters
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [selectedProject, setSelectedProject] = useState(projectIdParam || '');

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { canManageTestCases } = usePermissions();
  const toast = useToast();

  useEffect(() => {
    const loadProjects = async () => {
      try {
        const res = await projectService.list({ per_page: 100 });
        setProjects(res.data);
        if (!projectIdParam && res.data.length > 0) {
          setSelectedProject(res.data[0].id.toString());
        }
      } catch (err) {
        console.error(err);
      }
    };
    loadProjects();
  }, [projectIdParam]);

  // Load form options when project changes
  useEffect(() => {
    if (!selectedProject) return;
    
    const loadOptions = async () => {
      try {
        const [envRes, planRes, tcRes, userRes] = await Promise.all([
          environmentService.list({ project_id: selectedProject, per_page: 100 }),
          testPlanService.list({ project_id: selectedProject, per_page: 100 }),
          testCaseService.list({ project_id: selectedProject, per_page: 100 }),
          userService.list({ options: true })
        ]);
        setEnvironments(envRes.data);
        setPlans(planRes.data);
        setTestCases(tcRes.data);
        setUsers(userRes.data);
      } catch (err) {
        console.error("Failed to load form options", err);
      }
    };
    loadOptions();
  }, [selectedProject]);

  const fetchRuns = useCallback(async (page = meta.page, perPage = meta.per_page) => {
    if (!selectedProject) return;
    
    setIsLoading(true);
    try {
      const res = await testRunService.list({
        project_id: selectedProject,
        page,
        per_page: perPage,
        search,
        status,
        plan_id: planIdParam
      });
      setRuns(res.data);
      setMeta(res.meta);
    } catch (err) {
      toast.error(err.message || 'Failed to load test runs.');
    } finally {
      setIsLoading(false);
    }
  }, [selectedProject, search, status, planIdParam, meta.page, meta.per_page, toast]);

  useEffect(() => {
    if (selectedProject) {
      const params = { project_id: selectedProject };
      if (planIdParam) params.plan_id = planIdParam;
      setSearchParams(params);
      fetchRuns(1);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selectedProject, search, status]);

  const handleCreate = async (data) => {
    setIsSubmitting(true);
    try {
      await testRunService.create({ ...data, project_id: selectedProject });
      toast.success('Test run created successfully.');
      setIsModalOpen(false);
      fetchRuns(1);
    } catch (err) {
      toast.error(err.message || 'Failed to create test run.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const columns = [
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
      key: 'progress',
      label: 'Progress',
      className: 'w-48',
      render: (row) => {
        const total = row.total_cases || 0;
        if (total === 0) return <span className="text-xs text-text-muted">No cases</span>;
        
        const passed = row.passed_cases || 0;
        const failed = row.failed_cases || 0;
        const blocked = row.blocked_cases || 0;
        const executed = passed + failed + blocked;
        const pct = Math.round((executed / total) * 100);
        
        return (
          <div className="flex items-center gap-2">
            <div className="flex-1 bg-bg border border-border rounded-full h-2 overflow-hidden flex">
              <div className="bg-pass h-full" style={{ width: `${(passed/total)*100}%` }} />
              <div className="bg-fail h-full" style={{ width: `${(failed/total)*100}%` }} />
              <div className="bg-blocked h-full" style={{ width: `${(blocked/total)*100}%` }} />
            </div>
            <span className="text-xs font-medium w-8 text-right">{pct}%</span>
          </div>
        );
      }
    },
    {
      key: 'environment_name',
      label: 'Environment',
      className: 'w-40',
      render: (row) => <span className="text-sm text-text-muted">{row.environment_name || '—'}</span>
    },
    {
      key: 'assigned_name',
      label: 'Assignee',
      className: 'w-40',
      render: (row) => <span className="text-sm text-text-muted">{row.assigned_name || 'Unassigned'}</span>
    },
    {
      key: 'updated_at',
      label: 'Last Updated',
      className: 'w-32',
      render: (row) => <span className="text-sm text-text-muted">{formatDate(row.updated_at)}</span>
    }
  ];

  return (
    <div>
      <PageHeader 
        title="Test Runs" 
        description="Execute tests and track results."
        action={
          canManageTestCases() && selectedProject && (
            <Button onClick={() => setIsModalOpen(true)}>Create Test Run</Button>
          )
        }
      />

      <FilterBar>
        <div className="flex-1">
          <SearchBar onSearch={setSearch} placeholder="Search test runs..." />
        </div>
        <div className="flex items-center gap-2">
          <span className="text-xs text-text-muted">Project:</span>
          <Select value={selectedProject} onChange={(e) => { setSelectedProject(e.target.value); }} className="!py-1.5 !text-sm">
            {projects.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
          </Select>
        </div>
        <Select value={status} onChange={(e) => setStatus(e.target.value)} className="!py-1.5 !text-sm w-40">
          <option value="">All Statuses</option>
          {RUN_STATUSES.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
        </Select>
      </FilterBar>

      <DataTable 
        columns={columns}
        data={runs}
        isLoading={isLoading}
        emptyTitle="No test runs found"
        emptyDescription={search ? `No results for "${search}"` : "Create a test run to start executing test cases."}
        emptyAction={{ label: 'Create Test Run', onClick: () => setIsModalOpen(true) }}
        pagination={{
          page: meta.page,
          perPage: meta.per_page,
          total: meta.total,
          onPageChange: (p) => fetchRuns(p),
          onPerPageChange: (pp) => fetchRuns(1, pp)
        }}
      />

      <Modal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} title="Create Test Run" maxWidth="max-w-2xl">
        <TestRunForm 
          projectId={selectedProject}
          plans={plans}
          environments={environments}
          users={users}
          testCases={testCases}
          onSubmit={handleCreate}
          onCancel={() => setIsModalOpen(false)}
          isLoading={isSubmitting}
        />
      </Modal>
    </div>
  );
}