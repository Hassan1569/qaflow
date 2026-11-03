import React, { useState, useEffect } from 'react';
import { Button } from '../common/Button';
import { FormField } from '../common/FormField';
import { Input } from '../common/Input';
import { Textarea } from '../common/Textarea';
import { Select } from '../common/Select';
import { TestStepEditor } from './TestStepEditor';
import { PRIORITIES, SEVERITIES, TEST_TYPES, AUTOMATION_STATUSES } from '../../utils/constants';

export function TestCaseForm({ initialData, projectId, suites, requirements, onSubmit, onCancel, isLoading }) {
  const [formData, setFormData] = useState({
    project_id: projectId,
    suite_id: '',
    title: '',
    description: '',
    preconditions: '',
    priority: 'medium',
    severity: 'medium',
    test_type: 'functional',
    component: '',
    tags: '',
    automation_status: 'manual',
    steps: [],
    requirement_ids: []
  });
  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (initialData) {
      setFormData({
        project_id: initialData.project_id,
        suite_id: initialData.suite_id || '',
        title: initialData.title || '',
        description: initialData.description || '',
        preconditions: initialData.preconditions || '',
        priority: initialData.priority || 'medium',
        severity: initialData.severity || 'medium',
        test_type: initialData.test_type || 'functional',
        component: initialData.component || '',
        tags: initialData.tags || '',
        automation_status: initialData.automation_status || 'manual',
        steps: initialData.steps || [],
        requirement_ids: initialData.requirement_ids || []
      });
    }
  }, [initialData]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    if (errors[name]) setErrors(prev => ({ ...prev, [name]: null }));
  };

  const handleStepsChange = (steps) => {
    setFormData(prev => ({ ...prev, steps }));
  };

  const handleRequirementsChange = (e) => {
    const options = e.target.options;
    const selected = [];
    for (let i = 0; i < options.length; i++) {
      if (options[i].selected) selected.push(Number(options[i].value));
    }
    setFormData(prev => ({ ...prev, requirement_ids: selected }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const newErrors = {};
    if (!formData.title) newErrors.title = 'Title is required.';
    if (formData.steps.length === 0) {
      // Not strictly required by backend, but good UX
      // newErrors.steps = 'At least one step is recommended.';
    }

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }
    
    // Clean up empty suite_id
    const payload = { ...formData };
    if (!payload.suite_id) payload.suite_id = null;
    
    onSubmit(payload);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <FormField label="Title" htmlFor="title" error={errors.title} required>
          <Input 
            id="title" name="title" value={formData.title} 
            onChange={handleChange} placeholder="e.g. Login with valid credentials" error={errors.title}
          />
        </FormField>
        
        <FormField label="Suite" htmlFor="suite_id">
          <Select id="suite_id" name="suite_id" value={formData.suite_id} onChange={handleChange}>
            <option value="">No Suite (Uncategorized)</option>
            {suites.map(s => (
              <option key={s.id} value={s.id}>{s.name}</option>
            ))}
          </Select>
        </FormField>
      </div>

      <FormField label="Description" htmlFor="description">
        <Textarea 
          id="description" name="description" value={formData.description} 
          onChange={handleChange} rows={2}
        />
      </FormField>

      <FormField label="Preconditions" htmlFor="preconditions">
        <Textarea 
          id="preconditions" name="preconditions" value={formData.preconditions} 
          onChange={handleChange} rows={2} placeholder="Any setup required before execution..."
        />
      </FormField>

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <FormField label="Priority" htmlFor="priority">
          <Select id="priority" name="priority" value={formData.priority} onChange={handleChange}>
            {PRIORITIES.map(p => <option key={p.value} value={p.value}>{p.label}</option>)}
          </Select>
        </FormField>
        <FormField label="Severity" htmlFor="severity">
          <Select id="severity" name="severity" value={formData.severity} onChange={handleChange}>
            {SEVERITIES.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
          </Select>
        </FormField>
        <FormField label="Type" htmlFor="test_type">
          <Select id="test_type" name="test_type" value={formData.test_type} onChange={handleChange}>
            {TEST_TYPES.map(t => <option key={t.value} value={t.value}>{t.label}</option>)}
          </Select>
        </FormField>
        <FormField label="Automation" htmlFor="automation_status">
          <Select id="automation_status" name="automation_status" value={formData.automation_status} onChange={handleChange}>
            {AUTOMATION_STATUSES.map(a => <option key={a.value} value={a.value}>{a.label}</option>)}
          </Select>
        </FormField>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <FormField label="Component" htmlFor="component">
          <Input id="component" name="component" value={formData.component} onChange={handleChange} placeholder="e.g. Auth" />
        </FormField>
        <FormField label="Tags" htmlFor="tags" helpText="Comma separated">
          <Input id="tags" name="tags" value={formData.tags} onChange={handleChange} placeholder="e.g. smoke, login" />
        </FormField>
      </div>

      <FormField label="Linked Requirements" htmlFor="requirement_ids" helpText="Hold Ctrl/Cmd to select multiple">
        <select
          id="requirement_ids"
          multiple
          className="block w-full rounded-md border border-border bg-surface text-text sm:text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50 min-h-[100px]"
          value={formData.requirement_ids.map(String)}
          onChange={handleRequirementsChange}
        >
          {requirements.map(r => (
            <option key={r.id} value={r.id}>{r.code} - {r.title}</option>
          ))}
        </select>
      </FormField>

      <div className="border border-border rounded-md p-4 bg-bg">
        <h4 className="text-sm font-medium text-text mb-3">Test Steps</h4>
        <TestStepEditor steps={formData.steps} onChange={handleStepsChange} />
      </div>

      <div className="flex justify-end gap-3 pt-4 border-t border-border">
        <Button type="button" variant="secondary" onClick={onCancel} disabled={isLoading}>Cancel</Button>
        <Button type="submit" variant="primary" isLoading={isLoading}>
          {initialData ? 'Save Changes' : 'Create Test Case'}
        </Button>
      </div>
    </form>
  );
}