import React, { useState, useEffect } from 'react';
import { Button } from '../common/Button';
import { FormField } from '../common/FormField';
import { Input } from '../common/Input';
import { Textarea } from '../common/Textarea';
import { Select } from '../common/Select';
import { PLAN_STATUSES } from '../../utils/constants';
import { userService } from '../../services/userService';

export function TestPlanForm({ initialData, projectId, onSubmit, onCancel, isLoading }) {
  const [formData, setFormData] = useState({
    project_id: projectId,
    name: '',
    release_version: '',
    description: '',
    scope: '',
    status: 'draft',
    start_date: '',
    end_date: '',
    owner_id: '',
  });
  const [users, setUsers] = useState([]);
  const [errors, setErrors] = useState({});

  useEffect(() => {
    const fetchUsers = async () => {
      try {
        const res = await userService.list({ options: true });
        setUsers(res.data);
      } catch (err) {
        console.error(err);
      }
    };
    fetchUsers();
  }, []);

  useEffect(() => {
    if (initialData) {
      setFormData({
        project_id: initialData.project_id,
        name: initialData.name || '',
        release_version: initialData.release_version || '',
        description: initialData.description || '',
        scope: initialData.scope || '',
        status: initialData.status || 'draft',
        start_date: initialData.start_date || '',
        end_date: initialData.end_date || '',
        owner_id: initialData.owner_id || '',
      });
    }
  }, [initialData]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    if (errors[name]) setErrors(prev => ({ ...prev, [name]: null }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const newErrors = {};
    if (!formData.name) newErrors.name = 'Name is required.';
    if (!formData.owner_id) newErrors.owner_id = 'Owner is required.';

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }
    
    // Clean up empty dates
    const payload = { ...formData };
    if (!payload.start_date) payload.start_date = null;
    if (!payload.end_date) payload.end_date = null;

    onSubmit(payload);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <FormField label="Plan Name" htmlFor="name" error={errors.name} required>
          <Input id="name" name="name" value={formData.name} onChange={handleChange} placeholder="e.g. v2.5 Regression" />
        </FormField>
        <FormField label="Release Version" htmlFor="release_version">
          <Input id="release_version" name="release_version" value={formData.release_version} onChange={handleChange} placeholder="e.g. 2.5.0" />
        </FormField>
      </div>

      <FormField label="Description" htmlFor="description">
        <Textarea id="description" name="description" value={formData.description} onChange={handleChange} rows={2} />
      </FormField>

      <FormField label="Scope" htmlFor="scope">
        <Textarea id="scope" name="scope" value={formData.scope} onChange={handleChange} rows={2} placeholder="What is included in this plan?" />
      </FormField>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <FormField label="Owner" htmlFor="owner_id" error={errors.owner_id} required>
          <Select id="owner_id" name="owner_id" value={formData.owner_id} onChange={handleChange} error={errors.owner_id}>
            <option value="">Select owner...</option>
            {users.map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
          </Select>
        </FormField>
        <FormField label="Status" htmlFor="status">
          <Select id="status" name="status" value={formData.status} onChange={handleChange}>
            {PLAN_STATUSES.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
          </Select>
        </FormField>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <FormField label="Start Date" htmlFor="start_date">
          <Input type="date" id="start_date" name="start_date" value={formData.start_date} onChange={handleChange} />
        </FormField>
        <FormField label="End Date" htmlFor="end_date">
          <Input type="date" id="end_date" name="end_date" value={formData.end_date} onChange={handleChange} />
        </FormField>
      </div>

      <div className="flex justify-end gap-3 pt-4 border-t border-border mt-6">
        <Button type="button" variant="secondary" onClick={onCancel} disabled={isLoading}>Cancel</Button>
        <Button type="submit" variant="primary" isLoading={isLoading}>
          {initialData ? 'Save Changes' : 'Create Plan'}
        </Button>
      </div>
    </form>
  );
}