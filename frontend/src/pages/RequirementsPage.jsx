import React, { useState, useEffect, useCallback } from 'react';
import { useSearchParams } from 'react-router-dom';
import { PageHeader } from '../components/common/PageHeader';
import { SearchBar } from '../components/common/SearchBar';
import { FilterBar } from '../components/common/FilterBar';
import { Button } from '../components/common/Button';
import { Select } from '../components/common/Select';
import { RequirementTable } from '../components/requirements/RequirementTable';
import { RequirementForm } from '../components/requirements/RequirementForm';
import { Modal } from '../components/common/Modal';
import { requirementService } from '../services/requirementService';
import { projectService } from '../services/projectService';
import { usePermissions } from '../hooks/usePermissions';
import { useToast } from '../hooks/useToast';
import { PRIORITIES, REQUIREMENT_STATUSES } from '../utils/constants';

export default function RequirementsPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const projectIdParam = searchParams.get('project_id');

  const [requirements, setRequirements] = useState([]);
  const [meta, setMeta] = useState({ page: 1, per_page: 25, total: 0 });
  const [projects, setProjects] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  
  // Filters
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [priority, setPriority] = useState('');
  const [selectedProject, setSelectedProject] = useState(projectIdParam || '');

  // Modal
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { canManageRequirements } = usePermissions();
  const toast = useToast();

  // Load projects for the dropdown
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

  const fetchRequirements = useCallback(async (page = meta.page, perPage = meta.per_page) => {
    if (!selectedProject) return; // Need a project context
    
    setIsLoading(true);
    try {
      const res = await requirementService.list({
        project_id: selectedProject,
        page,
        per_page: perPage,
        search,
        status,
        priority,
      });
      setRequirements(res.data);
      setMeta(res.meta);
    } catch (err) {
      toast.error(err.message || 'Failed to load requirements.');
    } finally {
      setIsLoading(false);
    }
  }, [selectedProject, search, status, priority, meta.page, meta.per_page, toast]);

  useEffect(() => {
    if (selectedProject) {
      // Update URL
      setSearchParams({ project_id: selectedProject });
      fetchRequirements(1);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selectedProject, search, status, priority]);

  const handleCreate = async (data) => {
    setIsSubmitting(true);
    try {
      await requirementService.create({ ...data, project_id: selectedProject });
      toast.success('Requirement created successfully.');
      setIsModalOpen(false);
      fetchRequirements(1);
    } catch (err) {
      toast.error(err.message || 'Failed to create requirement.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div>
      <PageHeader 
        title="Requirements" 
        description="Manage project requirements and traceability."
        action={
          canManageRequirements() && selectedProject && (
            <Button onClick={() => setIsModalOpen(true)}>Create Requirement</Button>
          )
        }
      />

      <FilterBar>
        <div className="flex-1">
          <SearchBar onSearch={setSearch} placeholder="Search requirements..." />
        </div>
        <div className="flex items-center gap-2">
          <span className="text-xs text-text-muted">Project:</span>
          <Select value={selectedProject} onChange={(e) => setSelectedProject(e.target.value)} className="!py-1.5 !text-sm">
            {projects.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
          </Select>
        </div>
        <Select value={status} onChange={(e) => setStatus(e.target.value)} className="!py-1.5 !text-sm w-40">
          <option value="">All Statuses</option>
          {REQUIREMENT_STATUSES.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
        </Select>
        <Select value={priority} onChange={(e) => setPriority(e.target.value)} className="!py-1.5 !text-sm w-40">
          <option value="">All Priorities</option>
          {PRIORITIES.map(p => <option key={p.value} value={p.value}>{p.label}</option>)}
        </Select>
      </FilterBar>

      <RequirementTable 
        data={requirements}
        isLoading={isLoading}
        page={meta.page}
        perPage={meta.per_page}
        total={meta.total}
        onPageChange={(p) => fetchRequirements(p)}
        onPerPageChange={(pp) => fetchRequirements(1, pp)}
        onCreate={() => setIsModalOpen(true)}
        emptyDescription={search ? `No results for "${search}"` : `This project has no requirements yet.`}
      />

      <Modal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} title="Create Requirement">
        <RequirementForm 
          projectId={selectedProject}
          onSubmit={handleCreate}
          onCancel={() => setIsModalOpen(false)}
          isLoading={isSubmitting}
        />
      </Modal>
    </div>
  );
}