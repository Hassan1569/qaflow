import React, { useState, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import { PageHeader } from '../components/common/PageHeader';
import { Button } from '../components/common/Button';
import { Select } from '../components/common/Select';
import { SuiteTree } from '../components/testSuites/SuiteTree';
import { SuiteForm } from '../components/testSuites/SuiteForm';
import { Modal } from '../components/common/Modal';
import { ConfirmationDialog } from '../components/common/ConfirmationDialog';
import { LoadingState } from '../components/common/LoadingState';
import { ErrorState } from '../components/common/ErrorState';
import { testSuiteService } from '../services/testSuiteService';
import { projectService } from '../services/projectService';
import { usePermissions } from '../hooks/usePermissions';
import { useToast } from '../hooks/useToast';

export default function TestSuitesPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const projectIdParam = searchParams.get('project_id');

  const [suites, setSuites] = useState([]);
  const [projects, setProjects] = useState([]);
  const [selectedProject, setSelectedProject] = useState(projectIdParam || '');
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  // Modal state
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingSuite, setEditingSuite] = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { canManageTestCases } = usePermissions(); // Reuse permission
  const toast = useToast();

  // Load projects
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

  const fetchSuites = async () => {
    if (!selectedProject) return;
    setIsLoading(true);
    setError(null);
    try {
      const data = await testSuiteService.getTree(selectedProject);
      setSuites(data);
    } catch (err) {
      setError(err.message || 'Failed to load suites.');
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    if (selectedProject) {
      setSearchParams({ project_id: selectedProject });
      fetchSuites();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selectedProject]);

  const handleSave = async (data) => {
    setIsSubmitting(true);
    try {
      if (editingSuite) {
        await testSuiteService.update(editingSuite.id, data);
        toast.success('Suite updated.');
      } else {
        await testSuiteService.create({ ...data, project_id: selectedProject });
        toast.success('Suite created.');
      }
      setIsModalOpen(false);
      fetchSuites();
    } catch (err) {
      toast.error(err.message || 'Failed to save suite.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    try {
      await testSuiteService.delete(deleteTarget.id);
      toast.success('Suite deleted.');
      setDeleteTarget(null);
      fetchSuites();
    } catch (err) {
      toast.error(err.message || 'Failed to delete suite.');
      setDeleteTarget(null);
    }
  };

  const openCreateModal = () => {
    setEditingSuite(null);
    setIsModalOpen(true);
  };

  const openEditModal = (suite) => {
    setEditingSuite(suite);
    setIsModalOpen(true);
  };

  return (
    <div>
      <PageHeader 
        title="Test Suites" 
        description="Organize test cases into hierarchical suites."
        action={
          canManageTestCases() && selectedProject && (
            <Button onClick={openCreateModal}>Create Suite</Button>
          )
        }
      />

      <div className="mb-6 flex items-center gap-2 bg-surface p-3 rounded-md border border-border w-fit">
        <span className="text-sm font-medium text-text-muted">Project:</span>
        <Select value={selectedProject} onChange={(e) => setSelectedProject(e.target.value)} className="!py-1.5 !text-sm min-w-[200px]">
          {projects.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
        </Select>
      </div>

      {isLoading ? (
        <LoadingState />
      ) : error ? (
        <ErrorState message={error} onRetry={fetchSuites} />
      ) : (
        <SuiteTree 
          suites={suites} 
          onEdit={openEditModal} 
          onDelete={setDeleteTarget} 
        />
      )}

      <Modal 
        isOpen={isModalOpen} 
        onClose={() => setIsModalOpen(false)} 
        title={editingSuite ? 'Edit Suite' : 'Create Suite'}
      >
        <SuiteForm 
          initialData={editingSuite}
          suites={suites}
          projectId={selectedProject}
          onSubmit={handleSave}
          onCancel={() => setIsModalOpen(false)}
          isLoading={isSubmitting}
        />
      </Modal>

      <ConfirmationDialog
        isOpen={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
        title="Delete Suite"
        message={`Are you sure you want to delete "${deleteTarget?.name}"? Any child suites will also be deleted, and test cases will be uncategorized.`}
        isDanger
      />
    </div>
  );
}