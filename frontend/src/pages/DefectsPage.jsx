import React, { useState, useEffect, useCallback } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import { PageHeader } from '../components/common/PageHeader';
import { SearchBar } from '../components/common/SearchBar';
import { FilterBar } from '../components/common/FilterBar';
import { Button } from '../components/common/Button';
import { Select } from '../components/common/Select';
import { DataTable } from '../components/common/DataTable';
import { StatusBadge } from '../components/common/StatusBadge';
import { PriorityBadge } from '../components/common/PriorityBadge';
import { DefectForm } from '../components/defects/DefectForm';
import { Modal } from '../components/common/Modal';
import { defectService } from '../services/defectService';
import { projectService } from '../services/projectService';
import { usePermissions } from '../hooks/usePermissions';
import { useToast } from '../hooks/useToast';
import { DEFECT_STATUSES, SEVERITIES, PRIORITIES } from '../utils/constants';
import { formatDate } from '../utils/format';

export default function DefectsPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const projectIdParam = searchParams.get('project_id');

  const [defects, setDefects] = useState([]);
  const [meta, setMeta] = useState({ page: 1, per_page: 25, total: 0 });
  const [projects, setProjects] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  
  // Filters
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [severity, setSeverity] = useState('');
  const [selectedProject, setSelectedProject] = useState(projectIdParam || '');

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { canCreateDefects } = usePermissions();
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

  const fetchDefects = useCallback(async (page = meta.page, perPage = meta.per_page) => {
    if (!selectedProject) return;
    
    setIsLoading(true);
    try {
      const res = await defectService.list({
        project_id: selectedProject,
        page,
        per_page: perPage,
        search,
        status,
        severity,
      });
      setDefects(res.data);
      setMeta(res.meta);
    } catch (err) {
      toast.error(err.message || 'Failed to load defects.');
    } finally {
      setIsLoading(false);
    }
  }, [selectedProject, search, status, severity, meta.page, meta.per_page, toast]);

  useEffect(() => {
    if (selectedProject) {
      setSearchParams({ project_id: selectedProject });
      fetchDefects(1);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selectedProject, search, status, severity]);

  const handleCreate = async (data) => {
    setIsSubmitting(true);
    try {
      await defectService.create({ ...data, project_id: selectedProject });
      toast.success('Defect created successfully.');
      setIsModalOpen(false);
      fetchDefects(1);
    } catch (err) {
      toast.error(err.message || 'Failed to create defect.');
    } finally {
      setIsSubmitting(false);
    }
  };

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
        <Link to={`/defects/${row.id}`} className="text-primary hover:underline font-medium">
          {row.title}
        </Link>
      )
    },
    {
      key: 'severity',
      label: 'Severity',
      className: 'w-24',
      render: (row) => <PriorityBadge priority={row.severity} />
    },
    {
      key: 'priority',
      label: 'Priority',
      className: 'w-24',
      render: (row) => <PriorityBadge priority={row.priority} />
    },
    {
      key: 'status',
      label: 'Status',
      className: 'w-32',
      render: (row) => <StatusBadge status={row.status} />
    },
    {
      key: 'assignee_name',
      label: 'Assignee',
      className: 'w-40',
      render: (row) => <span className="text-sm text-text-muted">{row.assignee_name || 'Unassigned'}</span>
    },
    {
      key: 'created_at',
      label: 'Reported',
      className: 'w-32',
      render: (row) => <span className="text-sm text-text-muted">{formatDate(row.created_at)}</span>
    }
  ];

  return (
    <div>
      <PageHeader 
        title="Defects" 
        description="Track bugs and issues reported during testing."
        action={
          canCreateDefects() && selectedProject && (
            <Button onClick={() => setIsModalOpen(true)}>Report Defect</Button>
          )
        }
      />

      <FilterBar>
        <div className="flex-1">
          <SearchBar onSearch={setSearch} placeholder="Search defects..." />
        </div>
        <div className="flex items-center gap-2">
          <span className="text-xs text-text-muted">Project:</span>
          <Select value={selectedProject} onChange={(e) => setSelectedProject(e.target.value)} className="!py-1.5 !text-sm">
            {projects.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
          </Select>
        </div>
        <Select value={status} onChange={(e) => setStatus(e.target.value)} className="!py-1.5 !text-sm w-40">
          <option value="">All Statuses</option>
          {DEFECT_STATUSES.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
        </Select>
        <Select value={severity} onChange={(e) => setSeverity(e.target.value)} className="!py-1.5 !text-sm w-32">
          <option value="">Severity</option>
          {SEVERITIES.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
        </Select>
      </FilterBar>

      <DataTable 
        columns={columns}
        data={defects}
        isLoading={isLoading}
        emptyTitle="No defects found"
        emptyDescription={search ? `No results for "${search}"` : "No defects have been reported yet."}
        emptyAction={{ label: 'Report Defect', onClick: () => setIsModalOpen(true) }}
        pagination={{
          page: meta.page,
          perPage: meta.per_page,
          total: meta.total,
          onPageChange: (p) => fetchDefects(p),
          onPerPageChange: (pp) => fetchDefects(1, pp)
        }}
      />

      <Modal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} title="Report Defect" maxWidth="max-w-3xl">
        <DefectForm 
          projectId={selectedProject}
          onSubmit={handleCreate}
          onCancel={() => setIsModalOpen(false)}
          isLoading={isSubmitting}
        />
      </Modal>
    </div>
  );
}