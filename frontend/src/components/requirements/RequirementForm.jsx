import React, { useState, useEffect } from 'react';
import { Button } from '../common/Button';
import { FormField } from '../common/FormField';
import { Input } from '../common/Input';
import { Textarea } from '../common/Textarea';
import { Select } from '../common/Select';
import { PRIORITIES, REQUIREMENT_STATUSES } from '../../utils/constants';

export function RequirementForm({ initialData, projectId, onSubmit, onCancel, isLoading }) {
  const [formData, setFormData] = useState({
    project_id: projectId,
    title: '',
    description: '',
    priority: 'medium',
    status: 'draft',
  });
  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (initialData) {
      setFormData({
        project_id: initialData.project_id,
        title: initialData.title || '',
        description: initialData.description || '',
        priority: initialData.priority || 'medium',
        status: initialData.status || 'draft',
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
    onSubmit(formData);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <FormField label="Title" htmlFor="title" error={errors.title} required>
        <Input 
          id="title" name="title" value={formData.title} 
          onChange={handleChange} placeholder="e.g. User Login" error={errors.title}
        />
      </FormField>

      <FormField label="Description" htmlFor="description">
        <Textarea 
          id="description" name="description" value={formData.description} 
          onChange={handleChange} placeholder="Detailed requirement description..."
          rows={4}
        />
      </FormField>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <FormField label="Priority" htmlFor="priority">
          <Select id="priority" name="priority" value={formData.priority} onChange={handleChange}>
            {PRIORITIES.map(p => (
              <option key={p.value} value={p.value}>{p.label}</option>
            ))}
          </Select>
        </FormField>
        
        <FormField label="Status" htmlFor="status">
          <Select id="status" name="status" value={formData.status} onChange={handleChange}>
            {REQUIREMENT_STATUSES.map(s => (
              <option key={s.value} value={s.value}>{s.label}</option>
            ))}
          </Select>
        </FormField>
      </div>

      <div className="flex justify-end gap-3 pt-4 border-t border-border mt-6">
        <Button type="button" variant="secondary" onClick={onCancel} disabled={isLoading}>
          Cancel
        </Button>
        <Button type="submit" variant="primary" isLoading={isLoading}>
          {initialData ? 'Save Changes' : 'Create Requirement'}
        </Button>
      </div>
    </form>
  );
}