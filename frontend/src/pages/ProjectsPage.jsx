import React, { useState, useEffect, useCallback } from 'react';
import { PageHeader } from '../components/common/PageHeader';
import { SearchBar } from '../components/common/SearchBar';
import { Button } from '../components/common/Button';
import { ProjectCard } from '../components/projects/ProjectCard';
import { ProjectForm } from '../components/projects/ProjectForm';
import { Modal } from '../components/common/Modal';
import { Pagination } from '../components/common/Pagination';
import { LoadingState } from '../components/common/LoadingState';
import { EmptyState } from '../components/common/EmptyState';
import { projectService } from '../services/projectService';
import { usePermissions } from '../hooks/usePermissions';
import { useToast } from '../hooks/useToast';

export default function ProjectsPage() {
  const [projects, setProjects] = useState([]);
  const [meta, setMeta] = useState({ page: 1, per_page: 25, total: 0 });
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');
  
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { canManageProjects } = usePermissions();
  const toast = useToast();

  const fetchProjects = useCallback(async (page = meta.page, perPage = meta.per_page) => {
    setIsLoading(true);
    try {
      const res = await projectService.list({ page, per_page: perPage, search });
      setProjects(res.data);
      setMeta(res.meta);
    } catch (err) {
      toast.error(err.message || 'Failed to load projects.');
    } finally {
      setIsLoading(false);
    }
  }, [search, meta.page, meta.per_page, toast]);

  useEffect(() => {
    fetchProjects(1);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [search]);

  const handleCreate = async (data) => {
    setIsSubmitting(true);
    try {
      await projectService.create(data);
      toast.success('Project created successfully.');
      setIsModalOpen(false);
      fetchProjects(1);
    } catch (err) {
      toast.error(err.message || 'Failed to create project.');
      if (err.details) {
        // Optionally map backend errors to form fields here
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div>
      <PageHeader 
        title="Projects" 
        description="Manage your QA projects and their configurations."
        action={
          canManageProjects() && (
            <Button onClick={() => setIsModalOpen(true)}>Create Project</Button>
          )
        }
      />

      <div className="mb-6 flex justify-between items-center">
        <SearchBar onSearch={setSearch} placeholder="Search projects by name or key..." />
      </div>

      {isLoading && projects.length === 0 ? (
        <LoadingState />
      ) : projects.length === 0 ? (
        <EmptyState 
          title="No projects found" 
          description={search ? `No results for "${search}"` : "Get started by creating your first QA project."}
          actionLabel={canManageProjects() ? "Create Project" : null}
          onAction={() => setIsModalOpen(true)}
        />
      ) : (
        <>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            {projects.map((project) => (
              <ProjectCard key={project.id} project={project} />
            ))}
          </div>
          
          <Pagination 
            page={meta.page}
            perPage={meta.per_page}
            total={meta.total}
            onPageChange={(p) => fetchProjects(p)}
            onPerPageChange={(pp) => fetchProjects(1, pp)}
          />
        </>
      )}

      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title="Create New Project"
      >
        <ProjectForm 
          onSubmit={handleCreate}
          onCancel={() => setIsModalOpen(false)}
          isLoading={isSubmitting}
        />
      </Modal>
    </div>
  );
}