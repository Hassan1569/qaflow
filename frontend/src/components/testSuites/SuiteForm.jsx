import React, { useState, useEffect } from 'react';
import { Button } from '../common/Button';
import { FormField } from '../common/FormField';
import { Input } from '../common/Input';
import { Select } from '../common/Select';
import { Textarea } from '../common/Textarea';

export function SuiteForm({ initialData, suites, projectId, onSubmit, onCancel, isLoading }) {
  const [formData, setFormData] = useState({
    project_id: projectId,
    parent_id: '',
    name: '',
    description: '',
  });
  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (initialData) {
      setFormData({
        project_id: initialData.project_id,
        parent_id: initialData.parent_id || '',
        name: initialData.name || '',
        description: initialData.description || '',
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

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }
    
    const payload = { ...formData };
    if (!payload.parent_id) payload.parent_id = null;

    onSubmit(payload);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <FormField label="Suite Name" htmlFor="name" error={errors.name} required>
        <Input 
          id="name" name="name" value={formData.name} 
          onChange={handleChange} placeholder="e.g. Authentication"
          error={errors.name}
        />
      </FormField>

      <FormField label="Parent Suite" htmlFor="parent_id">
        <Select id="parent_id" name="parent_id" value={formData.parent_id} onChange={handleChange}>
          <option value="">None (Top Level)</option>
          {suites.filter(s => s.id !== initialData?.id).map(s => (
            <option key={s.id} value={s.id}>{s.name}</option>
          ))}
        </Select>
      </FormField>

      <FormField label="Description" htmlFor="description">
        <Textarea 
          id="description" name="description" value={formData.description} 
          onChange={handleChange} rows={3}
        />
      </FormField>

      <div className="flex justify-end gap-3 pt-4 border-t border-border mt-6">
        <Button type="button" variant="secondary" onClick={onCancel} disabled={isLoading}>Cancel</Button>
        <Button type="submit" variant="primary" isLoading={isLoading}>
          {initialData ? 'Save Changes' : 'Create Suite'}
        </Button>
      </div>
    </form>
  );
}