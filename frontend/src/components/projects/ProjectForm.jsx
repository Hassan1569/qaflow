import React, { useState, useEffect } from 'react';
import { Button } from '../common/Button';
import { FormField } from '../common/FormField';
import { Input } from '../common/Input';
import { Textarea } from '../common/Textarea';
import { Select } from '../common/Select';
import { PROJECT_STATUSES } from '../../utils/constants';
import { userService } from '../../services/userService';

export function ProjectForm({ initialData, onSubmit, onCancel, isLoading }) {
  const [formData, setFormData] = useState({
    key_code: '',
    name: '',
    description: '',
    status: 'active',
    owner_id: '',
  });
  
  const [users, setUsers] = useState([]);
  const [errors, setErrors] = useState({});

  // Load users for the owner dropdown
  useEffect(() => {
    const fetchUsers = async () => {
      try {
        const res = await userService.list({ options: true });
        setUsers(res.data);
      } catch (err) {
        console.error("Failed to load users", err);
      }
    };
    fetchUsers();
  }, []);

  // Populate form if editing
  useEffect(() => {
    if (initialData) {
      setFormData({
        key_code: initialData.key_code || '',
        name: initialData.name || '',
        description: initialData.description || '',
        status: initialData.status || 'active',
        owner_id: initialData.owner_id || '',
      });
    }
  }, [initialData]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    // Clear error for this field when user types
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: null }));
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    
    // Basic validation
    const newErrors = {};
    if (!formData.key_code) newErrors.key_code = 'Key is required.';
    if (!formData.name) newErrors.name = 'Name is required.';
    if (!formData.owner_id) newErrors.owner_id = 'Owner is required.';

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    onSubmit(formData);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <FormField label="Project Key" htmlFor="key_code" error={errors.key_code} required>
          <Input 
            id="key_code" 
            name="key_code" 
            value={formData.key_code} 
            onChange={handleChange} 
            placeholder="e.g. BANK"
            disabled={!!initialData} // Key cannot be changed
            error={errors.key_code}
          />
        </FormField>
        
        <FormField label="Project Name" htmlFor="name" error={errors.name} required>
          <Input 
            id="name" 
            name="name" 
            value={formData.name} 
            onChange={handleChange} 
            placeholder="e.g. Banking Portal"
            error={errors.name}
          />
        </FormField>
      </div>

      <FormField label="Description" htmlFor="description">
        <Textarea 
          id="description" 
          name="description" 
          value={formData.description} 
          onChange={handleChange} 
          placeholder="Brief description of the project..."
          rows={3}
        />
      </FormField>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <FormField label="Owner" htmlFor="owner_id" error={errors.owner_id} required>
          <Select 
            id="owner_id" 
            name="owner_id" 
            value={formData.owner_id} 
            onChange={handleChange}
            error={errors.owner_id}
          >
            <option value="">Select an owner...</option>
            {users.map(u => (
              <option key={u.id} value={u.id}>{u.name} ({u.email})</option>
            ))}
          </Select>
        </FormField>
        
        {initialData && (
          <FormField label="Status" htmlFor="status">
            <Select id="status" name="status" value={formData.status} onChange={handleChange}>
              {PROJECT_STATUSES.map(s => (
                <option key={s.value} value={s.value}>{s.label}</option>
              ))}
            </Select>
          </FormField>
        )}
      </div>

      <div className="flex justify-end gap-3 pt-4 border-t border-border mt-6">
        <Button type="button" variant="secondary" onClick={onCancel} disabled={isLoading}>
          Cancel
        </Button>
        <Button type="submit" variant="primary" isLoading={isLoading}>
          {initialData ? 'Save Changes' : 'Create Project'}
        </Button>
      </div>
    </form>
  );
}