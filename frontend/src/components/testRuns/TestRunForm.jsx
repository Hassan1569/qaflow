import React, { useState, useEffect } from 'react';
import { Button } from '../common/Button';
import { FormField } from '../common/FormField';
import { Input } from '../common/Input';
import { Textarea } from '../common/Textarea';
import { Select } from '../common/Select';

export function TestRunForm({ projectId, plans, environments, users, testCases, onSubmit, onCancel, isLoading }) {
  const [formData, setFormData] = useState({
    project_id: projectId,
    plan_id: '',
    environment_id: '',
    name: '',
    description: '',
    assigned_to: '',
    test_case_ids: [],
  });
  const [errors, setErrors] = useState({});

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    if (errors[name]) setErrors(prev => ({ ...prev, [name]: null }));
  };

  const handleTestCaseToggle = (id) => {
    setFormData(prev => {
      const isSelected = prev.test_case_ids.includes(id);
      return {
        ...prev,
        test_case_ids: isSelected 
          ? prev.test_case_ids.filter(cid => cid !== id)
          : [...prev.test_case_ids, id]
      };
    });
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const newErrors = {};
    if (!formData.name) newErrors.name = 'Name is required.';
    if (formData.test_case_ids.length === 0) newErrors.test_case_ids = 'Select at least one test case.';

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }
    
    const payload = { ...formData };
    if (!payload.plan_id) payload.plan_id = null;
    if (!payload.environment_id) payload.environment_id = null;
    if (!payload.assigned_to) payload.assigned_to = null;

    onSubmit(payload);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <FormField label="Run Name" htmlFor="name" error={errors.name} required>
          <Input id="name" name="name" value={formData.name} onChange={handleChange} placeholder="e.g. Regression - Chrome" />
        </FormField>
        <FormField label="Test Plan (Optional)" htmlFor="plan_id">
          <Select id="plan_id" name="plan_id" value={formData.plan_id} onChange={handleChange}>
            <option value="">Standalone Run</option>
            {plans.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
          </Select>
        </FormField>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <FormField label="Environment" htmlFor="environment_id">
          <Select id="environment_id" name="environment_id" value={formData.environment_id} onChange={handleChange}>
            <option value="">No specific environment</option>
            {environments.map(e => <option key={e.id} value={e.id}>{e.name}</option>)}
          </Select>
        </FormField>
        <FormField label="Assign To" htmlFor="assigned_to">
          <Select id="assigned_to" name="assigned_to" value={formData.assigned_to} onChange={handleChange}>
            <option value="">Unassigned</option>
            {users.map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
          </Select>
        </FormField>
      </div>

      <FormField label="Description" htmlFor="description">
        <Textarea id="description" name="description" value={formData.description} onChange={handleChange} rows={2} />
      </FormField>

      <FormField label="Select Test Cases" error={errors.test_case_ids} required>
        <div className="border border-border rounded-md bg-surface max-h-64 overflow-y-auto">
          {testCases.length === 0 ? (
            <p className="p-3 text-sm text-text-muted italic">No test cases available in this project.</p>
          ) : (
            <ul className="divide-y divide-border">
              {testCases.map(tc => (
                <li key={tc.id} className="p-2 flex items-center gap-3 hover:bg-bg transition-colors">
                  <input 
                    type="checkbox" 
                    id={`tc-${tc.id}`}
                    checked={formData.test_case_ids.includes(tc.id)}
                    onChange={() => handleTestCaseToggle(tc.id)}
                    className="h-4 w-4 rounded border-border text-primary focus:ring-primary cursor-pointer"
                  />
                  <label htmlFor={`tc-${tc.id}`} className="flex-1 text-sm cursor-pointer">
                    <span className="font-mono text-xs text-text-muted mr-2">{tc.code}</span>
                    {tc.title}
                  </label>
                </li>
              ))}
            </ul>
          )}
        </div>
      </FormField>

      <div className="flex justify-end gap-3 pt-4 border-t border-border mt-6">
        <Button type="button" variant="secondary" onClick={onCancel} disabled={isLoading}>Cancel</Button>
        <Button type="submit" variant="primary" isLoading={isLoading}>Create Run</Button>
      </div>
    </form>
  );
}