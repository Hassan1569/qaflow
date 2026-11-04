import React, { useState, useEffect } from 'react';
import { PageHeader } from '../components/common/PageHeader';
import { DataTable } from '../components/common/DataTable';
import { Button } from '../components/common/Button';
import { Modal } from '../components/common/Modal';
import { FormField } from '../components/common/FormField';
import { Input } from '../components/common/Input';
import { Select } from '../components/common/Select';
import { StatusBadge } from '../components/common/StatusBadge';
import { userService } from '../services/userService';
import { usePermissions } from '../hooks/usePermissions';
import { useToast } from '../hooks/useToast';
import { ROLES } from '../utils/constants';
import { formatDate } from '../utils/format';

export default function TeamPage() {
  const [users, setUsers] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  const [formData, setFormData] = useState({
    name: '', email: '', password: '', role: 'qa_engineer'
  });

  const { canManageUsers } = usePermissions();
  const toast = useToast();

  const fetchUsers = async () => {
    setIsLoading(true);
    try {
      const res = await userService.list({ per_page: 100 });
      setUsers(res.data);
    } catch (err) {
      toast.error(err.message || 'Failed to load team members.');
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchUsers();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const handleCreate = async (e) => {
    e.preventDefault();
    if (!formData.name || !formData.email || !formData.password) {
      toast.error('Name, email, and password are required.');
      return;
    }
    
    setIsSubmitting(true);
    try {
      await userService.create(formData);
      toast.success('User created successfully.');
      setIsModalOpen(false);
      setFormData({ name: '', email: '', password: '', role: 'qa_engineer' });
      fetchUsers();
    } catch (err) {
      toast.error(err.message || 'Failed to create user.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDeactivate = async (id) => {
    if (!window.confirm('Deactivate this user?')) return;
    try {
      await userService.deactivate(id);
      toast.success('User deactivated.');
      fetchUsers();
    } catch (err) {
      toast.error(err.message || 'Failed to deactivate user.');
    }
  };

  const columns = [
    {
      key: 'name',
      label: 'Name',
      render: (row) => (
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 rounded-full bg-primary-light text-primary flex items-center justify-center font-bold text-xs uppercase">
            {row.name.charAt(0)}
          </div>
          <span className="font-medium text-text">{row.name}</span>
        </div>
      )
    },
    {
      key: 'email',
      label: 'Email',
      render: (row) => <span className="text-sm text-text-muted">{row.email}</span>
    },
    {
      key: 'role',
      label: 'System Role',
      className: 'w-40',
      render: (row) => {
        const role = ROLES.find(r => r.value === row.role);
        return <span className="text-sm font-medium capitalize">{role ? role.label : row.role}</span>;
      }
    },
    {
      key: 'is_active',
      label: 'Status',
      className: 'w-32',
      render: (row) => (
        <StatusBadge status={row.is_active ? 'active' : 'archived'} />
      )
    },
    {
      key: 'created_at',
      label: 'Joined',
      className: 'w-32',
      render: (row) => <span className="text-sm text-text-muted">{formatDate(row.created_at)}</span>
    },
    {
      key: 'actions',
      label: '',
      className: 'w-24 text-right',
      render: (row) => canManageUsers() && row.is_active ? (
        <button onClick={() => handleDeactivate(row.id)} className="text-xs font-medium text-fail hover:underline">Deactivate</button>
      ) : null
    }
  ];

  return (
    <div>
      <PageHeader 
        title="Team" 
        description="Manage system users and global roles."
        action={
          canManageUsers() && (
            <Button onClick={() => setIsModalOpen(true)}>Add User</Button>
          )
        }
      />

      <DataTable 
        columns={columns}
        data={users}
        isLoading={isLoading}
        emptyTitle="No users found"
      />

      <Modal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} title="Add New User">
        <form onSubmit={handleCreate} className="space-y-4">
          <FormField label="Full Name" htmlFor="name" required>
            <Input id="name" value={formData.name} onChange={e => setFormData({...formData, name: e.target.value})} />
          </FormField>
          <FormField label="Email Address" htmlFor="email" required>
            <Input type="email" id="email" value={formData.email} onChange={e => setFormData({...formData, email: e.target.value})} />
          </FormField>
          <FormField label="Temporary Password" htmlFor="password" required>
            <Input type="password" id="password" value={formData.password} onChange={e => setFormData({...formData, password: e.target.value})} />
          </FormField>
          <FormField label="System Role" htmlFor="role" required>
            <Select id="role" value={formData.role} onChange={e => setFormData({...formData, role: e.target.value})}>
              {ROLES.map(r => <option key={r.value} value={r.value}>{r.label}</option>)}
            </Select>
          </FormField>

          <div className="flex justify-end gap-3 pt-4 border-t border-border mt-6">
            <Button type="button" variant="secondary" onClick={() => setIsModalOpen(false)} disabled={isSubmitting}>Cancel</Button>
            <Button type="submit" variant="primary" isLoading={isSubmitting}>Create User</Button>
          </div>
        </form>
      </Modal>
    </div>
  );
}