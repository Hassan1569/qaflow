import React, { useState, useEffect } from 'react';
import { Button } from '../common/Button';
import { FormField } from '../common/FormField';
import { Input } from '../common/Input';
import { Textarea } from '../common/Textarea';
import { Select } from '../common/Select';
import { SEVERITIES, PRIORITIES, DEFECT_STATUSES } from '../../utils/constants';
import { userService } from '../../services/userService';

export function DefectForm({ initialData, projectId, onSubmit, onCancel, isLoading }) {
  const [formData, setFormData] = useState({
    project_id: projectId,
    title: '',
    description: '',
    expected_result: '',
    actual_result: '',
    severity: 'medium',
    priority: 'medium',
    status: 'open',
    assignee_id: '',
    environment_id: '',
    test_case_id: '',
    requirement_id: '',
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
        title: initialData.title || '',
        description: initialData.description || '',
        expected_result: initialData.expected_result || '',
        actual_result: initialData.actual_result || '',
        severity: initialData.severity || 'medium',
        priority: initialData.priority || 'medium',
        status: initialData.status || 'open',
        assignee_id: initialData.assignee_id || '',
        environment_id: initialData.environment_id || '',
        test_case_id: initialData.test_case_id || '',
        requirement_id: initialData.requirement_id || '',
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
    if (!formData.title) newErrors.title = 'Title is required.';

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }
    
    const payload = { ...formData };
    if (!payload.assignee_id) payload.assignee_id = null;
    if (!payload.environment_id) payload.environment_id = null;
    if (!payload.test_case_id) payload.test_case_id = null;
    if (!payload.requirement_id) payload.requirement_id = null;

    onSubmit(payload);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <FormField label="Title" htmlFor="title" error={errors.title} required>
        <Input id="title" name="title" value={formData.title} onChange={handleChange} placeholder="Brief summary of the issue" />
      </FormField>

      <FormField label="Description" htmlFor="description">
        <Textarea id="description" name="description" value={formData.description} onChange={handleChange} rows={3} placeholder="Steps to reproduce, context, logs..." />
      </FormField>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <FormField label="Expected Result" htmlFor="expected_result">
          <Textarea id="expected_result" name="expected_result" value={formData.expected_result} onChange={handleChange} rows={2} />
        </FormField>
        <FormField label="Actual Result" htmlFor="actual_result">
          <Textarea id="actual_result" name="actual_result" value={formData.actual_result} onChange={handleChange} rows={2} />
        </FormField>
      </div>

      <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
        <FormField label="Severity" htmlFor="severity">
          <Select id="severity" name="severity" value={formData.severity} onChange={handleChange}>
            {SEVERITIES.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
          </Select>
        </FormField>
        <FormField label="Priority" htmlFor="priority">
          <Select id="priority" name="priority" value={formData.priority} onChange={handleChange}>
            {PRIORITIES.map(p => <option key={p.value} value={p.value}>{p.label}</option>)}
          </Select>
        </FormField>
        {initialData && (
          <FormField label="Status" htmlFor="status">
            <Select id="status" name="status" value={formData.status} onChange={handleChange}>
              {DEFECT_STATUSES.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
            </Select>
          </FormField>
        )}
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <FormField label="Assignee" htmlFor="assignee_id">
          <Select id="assignee_id" name="assignee_id" value={formData.assignee_id} onChange={handleChange}>
            <option value="">Unassigned</option>
            {users.map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
          </Select>
        </FormField>
      </div>

      <div className="flex justify-end gap-3 pt-4 border-t border-border mt-6">
        <Button type="button" variant="secondary" onClick={onCancel} disabled={isLoading}>Cancel</Button>
        <Button type="submit" variant="primary" isLoading={isLoading}>
          {initialData ? 'Save Changes' : 'Create Defect'}
        </Button>
      </div>
    </form>
  );
}