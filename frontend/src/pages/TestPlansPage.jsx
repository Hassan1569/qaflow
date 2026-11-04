import React, { useState, useEffect, useCallback } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import { PageHeader } from '../components/common/PageHeader';
import { SearchBar } from '../components/common/SearchBar';
import { FilterBar } from '../components/common/FilterBar';
import { Button } from '../components/common/Button';
import { Select } from '../components/common/Select';
import { DataTable } from '../components/common/DataTable';
import { StatusBadge } from '../components/common/StatusBadge';
import { TestPlanForm } from '../components/testPlans/TestPlanForm';
import { Modal } from '../components/common/Modal';
import { testPlanService } from '../services/testPlanService';
import { projectService } from '../services/projectService';
import { usePermissions } from '../hooks/usePermissions';
import { useToast } from '../hooks/useToast';
import { PLAN_STATUSES } from '../utils/constants';
import { formatDate } from '../utils/format';

export default function TestPlansPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const projectIdParam = searchParams.get('project_id');

  const [plans, setPlans] = useState([]);
  const [meta, setMeta] = useState({ page: 1, per_page: 25, total: 0 });
  const [projects, setProjects] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  
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

  const fetchPlans = useCallback(async (page = meta.page, perPage = meta.per_page) => {
    if (!selectedProject) return;
    
    setIsLoading(true);
    try {
      const res = await testPlanService.list({
        project_id: selectedProject,
        page,
        per_page: perPage,
        search,
        status,
      });
      setPlans(res.data);
      setMeta(res.meta);
    } catch (err) {
      toast.error(err.message || 'Failed to load test plans.');
    } finally {
      setIsLoading(false);
    }
  }, [selectedProject, search, status, meta.page, meta.per_page, toast]);

  useEffect(() => {
    if (selectedProject) {
      setSearchParams({ project_id: selectedProject });
      fetchPlans(1);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selectedProject, search, status]);

  const handleCreate = async (data) => {
    setIsSubmitting(true);
    try {
      await testPlanService.create({ ...data, project_id: selectedProject });
      toast.success('Test plan created successfully.');
      setIsModalOpen(false);
      fetchPlans(1);
    } catch (err) {
      toast.error(err.message || 'Failed to create test plan.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const columns = [
    {
      key: 'name',
      label: 'Plan Name',
      render: (row) => (
        <Link to={`/test-plans/${row.id}`} className="text-primary hover:underline font-medium">
          {row.name}
        </Link>
      )
    },
    {
      key: 'release_version',
      label: 'Release',
      className: 'w-32',
      render: (row) => <span className="font-mono text-xs text-text-muted">{row.release_version || '—'}</span>
    },
    {
      key: 'status',
      label: 'Status',
      className: 'w-32',
      render: (row) => <StatusBadge status={row.status} />
    },
    {
      key: 'run_count',
      label: 'Runs',
      className: 'w-24 text-right',
      cellClassName: 'text-right',
      render: (row) => <span className="font-medium">{row.run_count || 0}</span>
    },
    {
      key: 'owner_name',
      label: 'Owner',
      className: 'w-40',
      render: (row) => <span className="text-sm text-text-muted">{row.owner_name}</span>
    },
    {
      key: 'updated_at',
      label: 'Last Updated',
      className: 'w-40',
      render: (row) => <span className="text-sm text-text-muted">{formatDate(row.updated_at)}</span>
    }
  ];

  return (
    <div>
      <PageHeader 
        title="Test Plans" 
        description="Organize testing cycles and release scopes."
        action={
          canManageTestCases() && selectedProject && (
            <Button onClick={() => setIsModalOpen(true)}>Create Test Plan</Button>
          )
        }
      />

      <FilterBar>
        <div className="flex-1">
          <SearchBar onSearch={setSearch} placeholder="Search test plans..." />
        </div>
        <div className="flex items-center gap-2">
          <span className="text-xs text-text-muted">Project:</span>
          <Select value={selectedProject} onChange={(e) => setSelectedProject(e.target.value)} className="!py-1.5 !text-sm">
            {projects.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
          </Select>
        </div>
        <Select value={status} onChange={(e) => setStatus(e.target.value)} className="!py-1.5 !text-sm w-40">
          <option value="">All Statuses</option>
          {PLAN_STATUSES.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
        </Select>
      </FilterBar>

      <DataTable 
        columns={columns}
        data={plans}
        isLoading={isLoading}
        emptyTitle="No test plans found"
        emptyDescription={search ? `No results for "${search}"` : "Create a test plan for your next release."}
        emptyAction={{ label: 'Create Test Plan', onClick: () => setIsModalOpen(true) }}
        pagination={{
          page: meta.page,
          perPage: meta.per_page,
          total: meta.total,
          onPageChange: (p) => fetchPlans(p),
          onPerPageChange: (pp) => fetchPlans(1, pp)
        }}
      />

      <Modal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} title="Create Test Plan" maxWidth="max-w-2xl">
        <TestPlanForm 
          projectId={selectedProject}
          onSubmit={handleCreate}
          onCancel={() => setIsModalOpen(false)}
          isLoading={isSubmitting}
        />
      </Modal>
    </div>
  );
}