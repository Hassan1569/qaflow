import React, { useState, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import { PageHeader } from '../components/common/PageHeader';
import { SearchBar } from '../components/common/SearchBar';
import { FilterBar } from '../components/common/FilterBar';
import { Button } from '../components/common/Button';
import { Select } from '../components/common/Select';
import { DataTable } from '../components/common/DataTable';
import { Modal } from '../components/common/Modal';
import { FormField } from '../components/common/FormField';
import { Input } from '../components/common/Input';
import { Textarea } from '../components/common/Textarea';
import { ConfirmationDialog } from '../components/common/ConfirmationDialog';
import { environmentService } from '../services/environmentService';
import { projectService } from '../services/projectService';
import { usePermissions } from '../hooks/usePermissions';
import { useToast } from '../hooks/useToast';

export default function EnvironmentsPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const projectIdParam = searchParams.get('project_id');

  const [environments, setEnvironments] = useState([]);
  const [meta, setMeta] = useState({ page: 1, per_page: 100, total: 0 });
  const [projects, setProjects] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  
  const [search, setSearch] = useState('');
  const [selectedProject, setSelectedProject] = useState(projectIdParam || '');

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingEnv, setEditingEnv] = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  // Form state
  const [formData, setFormData] = useState({
    name: '', browser: '', browser_version: '', os: '', os_version: '', device: '', device_version: '', app_version: '', notes: ''
  });

  const { canManageProjects } = usePermissions();
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

  const fetchEnvironments = async () => {
    if (!selectedProject) return;
    setIsLoading(true);
    try {
      const res = await environmentService.list({ project_id: selectedProject, search });
      setEnvironments(res.data);
      setMeta(res.meta);
    } catch (err) {
      toast.error(err.message || 'Failed to load environments.');
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    if (selectedProject) {
      setSearchParams({ project_id: selectedProject });
      fetchEnvironments();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selectedProject, search]);

  const handleOpenModal = (env = null) => {
    if (env) {
      setEditingEnv(env);
      setFormData({
        name: env.name || '', browser: env.browser || '', browser_version: env.browser_version || '',
        os: env.os || '', os_version: env.os_version || '', device: env.device || '',
        device_version: env.device_version || '', app_version: env.app_version || '', notes: env.notes || ''
      });
    } else {
      setEditingEnv(null);
      setFormData({ name: '', browser: '', browser_version: '', os: '', os_version: '', device: '', device_version: '', app_version: '', notes: '' });
    }
    setIsModalOpen(true);
  };

  const handleSave = async (e) => {
    e.preventDefault();
    if (!formData.name) {
      toast.error('Name is required.');
      return;
    }
    
    setIsSubmitting(true);
    try {
      if (editingEnv) {
        await environmentService.update(editingEnv.id, formData);
        toast.success('Environment updated.');
      } else {
        await environmentService.create({ ...formData, project_id: selectedProject });
        toast.success('Environment created.');
      }
      setIsModalOpen(false);
      fetchEnvironments();
    } catch (err) {
      toast.error(err.message || 'Failed to save environment.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    try {
      await environmentService.delete(deleteTarget.id);
      toast.success('Environment deleted.');
      setDeleteTarget(null);
      fetchEnvironments();
    } catch (err) {
      toast.error(err.message || 'Failed to delete environment.');
      setDeleteTarget(null);
    }
  };

  const columns = [
    {
      key: 'name',
      label: 'Name',
      render: (row) => <span className="font-medium text-text">{row.name}</span>
    },
    {
      key: 'display',
      label: 'Details',
      render: (row) => {
        const parts = [];
        if (row.browser) parts.push(`${row.browser} ${row.browser_version || ''}`.trim());
        if (row.os) parts.push(`${row.os} ${row.os_version || ''}`.trim());
        if (row.device) parts.push(`${row.device} ${row.device_version || ''}`.trim());
        return <span className="text-sm text-text-muted">{parts.join(' / ') || '—'}</span>;
      }
    },
    {
      key: 'app_version',
      label: 'App Version',
      className: 'w-32',
      render: (row) => <span className="text-sm font-mono text-text-muted">{row.app_version || '—'}</span>
    },
    {
      key: 'actions',
      label: '',
      className: 'w-32 text-right',
      render: (row) => canManageProjects() && (
        <div className="flex justify-end gap-2">
          <button onClick={() => handleOpenModal(row)} className="text-xs font-medium text-primary hover:underline">Edit</button>
          <button onClick={() => setDeleteTarget(row)} className="text-xs font-medium text-fail hover:underline">Delete</button>
        </div>
      )
    }
  ];

  return (
    <div>
      <PageHeader 
        title="Environments" 
        description="Manage test devices and browser configurations."
        action={
          canManageProjects() && selectedProject && (
            <Button onClick={() => handleOpenModal()}>Add Environment</Button>
          )
        }
      />

      <FilterBar>
        <div className="flex-1">
          <SearchBar onSearch={setSearch} placeholder="Search environments..." />
        </div>
        <div className="flex items-center gap-2">
          <span className="text-xs text-text-muted">Project:</span>
          <Select value={selectedProject} onChange={(e) => setSelectedProject(e.target.value)} className="!py-1.5 !text-sm">
            {projects.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
          </Select>
        </div>
      </FilterBar>

      <DataTable 
        columns={columns}
        data={environments}
        isLoading={isLoading}
        emptyTitle="No environments found"
        emptyDescription="Add a target environment to associate with test runs."
        emptyAction={{ label: 'Add Environment', onClick: () => handleOpenModal() }}
        pagination={{
          page: meta.page,
          perPage: meta.per_page,
          total: meta.total,
          onPageChange: () => {},
          onPerPageChange: () => {}
        }}
      />

      <Modal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} title={editingEnv ? 'Edit Environment' : 'Add Environment'}>
        <form onSubmit={handleSave} className="space-y-4">
          <FormField label="Name" htmlFor="name" required>
            <Input id="name" value={formData.name} onChange={e => setFormData({...formData, name: e.target.value})} placeholder="e.g. Chrome / Windows 11" />
          </FormField>
          
          <div className="grid grid-cols-2 gap-4">
            <FormField label="Browser" htmlFor="browser">
              <Input id="browser" value={formData.browser} onChange={e => setFormData({...formData, browser: e.target.value})} />
            </FormField>
            <FormField label="Browser Version" htmlFor="browser_version">
              <Input id="browser_version" value={formData.browser_version} onChange={e => setFormData({...formData, browser_version: e.target.value})} />
            </FormField>
          </div>
          
          <div className="grid grid-cols-2 gap-4">
            <FormField label="OS" htmlFor="os">
              <Input id="os" value={formData.os} onChange={e => setFormData({...formData, os: e.target.value})} />
            </FormField>
            <FormField label="OS Version" htmlFor="os_version">
              <Input id="os_version" value={formData.os_version} onChange={e => setFormData({...formData, os_version: e.target.value})} />
            </FormField>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <FormField label="Device" htmlFor="device">
              <Input id="device" value={formData.device} onChange={e => setFormData({...formData, device: e.target.value})} placeholder="e.g. iPhone 14" />
            </FormField>
            <FormField label="Device Version" htmlFor="device_version">
              <Input id="device_version" value={formData.device_version} onChange={e => setFormData({...formData, device_version: e.target.value})} />
            </FormField>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <FormField label="App Version" htmlFor="app_version">
              <Input id="app_version" value={formData.app_version} onChange={e => setFormData({...formData, app_version: e.target.value})} />
            </FormField>
          </div>

          <FormField label="Notes" htmlFor="notes">
            <Textarea id="notes" value={formData.notes} onChange={e => setFormData({...formData, notes: e.target.value})} rows={2} />
          </FormField>

          <div className="flex justify-end gap-3 pt-4 border-t border-border mt-6">
            <Button type="button" variant="secondary" onClick={() => setIsModalOpen(false)} disabled={isSubmitting}>Cancel</Button>
            <Button type="submit" variant="primary" isLoading={isSubmitting}>Save</Button>
          </div>
        </form>
      </Modal>

      <ConfirmationDialog
        isOpen={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
        title="Delete Environment"
        message={`Are you sure you want to delete "${deleteTarget?.name}"?`}
        isDanger
      />
    </div>
  );
}