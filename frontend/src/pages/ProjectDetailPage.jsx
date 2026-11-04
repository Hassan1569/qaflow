import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { PageHeader } from '../components/common/PageHeader';
import { Breadcrumb } from '../components/common/Breadcrumb';
import { StatusBadge } from '../components/common/StatusBadge';
import { Button } from '../components/common/Button';
import { ProjectForm } from '../components/projects/ProjectForm';
import { ProjectMembers } from '../components/projects/ProjectMembers';
import { Modal } from '../components/common/Modal';
import { LoadingState } from '../components/common/LoadingState';
import { ErrorState } from '../components/common/ErrorState';
import { StatCard } from '../components/common/StatCard';
import { projectService } from '../services/projectService';
import { usePermissions } from '../hooks/usePermissions';
import { useToast } from '../hooks/useToast';
import { formatDate } from '../utils/format';

export default function ProjectDetailPage() {
  const { id } = useParams();
  const [project, setProject] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { canManageProjects } = usePermissions();
  const toast = useToast();

  const fetchProject = async () => {
    setLoading(true);
    try {
      const data = await projectService.get(id);
      setProject(data);
    } catch (err) {
      setError(err.message || 'Failed to load project.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchProject();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id]);

  const handleUpdate = async (data) => {
    setIsSubmitting(true);
    try {
      await projectService.update(id, data);
      toast.success('Project updated.');
      setIsEditModalOpen(false);
      fetchProject();
    } catch (err) {
      toast.error(err.message || 'Failed to update project.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleArchive = async () => {
    if (!window.confirm('Archive this project? It will remain read-only.')) return;
    try {
      await projectService.archive(id);
      toast.success('Project archived.');
      fetchProject();
    } catch (err) {
      toast.error(err.message || 'Failed to archive project.');
    }
  };

  if (loading) return <LoadingState />;
  if (error) return <ErrorState message={error} onRetry={fetchProject} />;
  if (!project) return null;

  return (
    <div>
      <Breadcrumb items={[{ label: 'Projects', to: '/projects' }, { label: project.name }]} />
      
      <PageHeader 
        title={project.name} 
        description={`Project Key: ${project.key_code}`}
        action={
          canManageProjects() && (
            <div className="flex gap-2">
              <Button variant="secondary" onClick={() => setIsEditModalOpen(true)}>Edit</Button>
              {project.status !== 'archived' && (
                <Button variant="danger" onClick={handleArchive}>Archive</Button>
              )}
            </div>
          )
        }
      />

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div className="lg:col-span-2 space-y-6">
          <div className="bg-surface border border-border rounded-md p-6">
            <h3 className="text-lg font-semibold text-text mb-4">Project Details</h3>
            <div className="grid grid-cols-2 gap-4 mb-4">
              <div>
                <p className="text-sm text-text-muted">Status</p>
                <div className="mt-1"><StatusBadge status={project.status} /></div>
              </div>
              <div>
                <p className="text-sm text-text-muted">Owner</p>
                <p className="text-sm font-medium text-text mt-1">{project.owner_name}</p>
              </div>
              <div>
                <p className="text-sm text-text-muted">Created</p>
                <p className="text-sm font-medium text-text mt-1">{formatDate(project.created_at)}</p>
              </div>
              <div>
                <p className="text-sm text-text-muted">Last Updated</p>
                <p className="text-sm font-medium text-text mt-1">{formatDate(project.updated_at)}</p>
              </div>
            </div>
            <div>
              <p className="text-sm text-text-muted mb-1">Description</p>
              <p className="text-sm text-text bg-bg p-3 rounded border border-border">
                {project.description || 'No description provided.'}
              </p>
            </div>
          </div>

          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <StatCard title="Requirements" value={project.statistics.requirements} />
            <StatCard title="Test Cases" value={project.statistics.test_cases} />
            <StatCard title="Test Runs" value={project.statistics.test_runs} />
            <StatCard title="Open Defects" value={project.statistics.open_defects} />
          </div>
        </div>

        <div className="lg:col-span-1">
          <ProjectMembers 
            projectId={project.id} 
            initialMembers={project.members} 
            onRefresh={fetchProject}
          />
        </div>
      </div>

      <Modal isOpen={isEditModalOpen} onClose={() => setIsEditModalOpen(false)} title="Edit Project">
        <ProjectForm 
          initialData={project}
          onSubmit={handleUpdate}
          onCancel={() => setIsEditModalOpen(false)}
          isLoading={isSubmitting}
        />
      </Modal>
    </div>
  );
}