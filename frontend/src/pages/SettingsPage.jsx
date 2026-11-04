import React from 'react';
import { PageHeader } from '../components/common/PageHeader';
import { useAuth } from '../hooks/useAuth';
import { useTheme } from '../context/ThemeContext';
import { Button } from '../components/common/Button';
import { Select } from '../components/common/Select';

export default function SettingsPage() {
  const { user } = useAuth();
  const { theme, setTheme } = useTheme();

  return (
    <div>
      <PageHeader title="Settings" description="Manage your personal preferences." />

      <div className="max-w-2xl space-y-6">
        <div className="bg-surface border border-border rounded-md p-6">
          <h3 className="text-lg font-semibold text-text mb-4 border-b border-border pb-2">Profile</h3>
          <div className="space-y-4">
            <div>
              <label className="text-sm font-medium text-text-muted block mb-1">Name</label>
              <p className="text-sm font-medium">{user?.name}</p>
            </div>
            <div>
              <label className="text-sm font-medium text-text-muted block mb-1">Email</label>
              <p className="text-sm font-medium">{user?.email}</p>
            </div>
            <div>
              <label className="text-sm font-medium text-text-muted block mb-1">System Role</label>
              <p className="text-sm font-medium capitalize">{user?.role.replace('_', ' ')}</p>
            </div>
            <div className="pt-2">
              <Button variant="secondary" disabled>Edit Profile (Coming Soon)</Button>
            </div>
          </div>
        </div>

        <div className="bg-surface border border-border rounded-md p-6">
          <h3 className="text-lg font-semibold text-text mb-4 border-b border-border pb-2">Appearance</h3>
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium">Theme</p>
              <p className="text-xs text-text-muted">Select light or dark mode.</p>
            </div>
            <Select 
              value={theme} 
              onChange={(e) => setTheme(e.target.value)}
              className="w-40"
            >
              <option value="light">Light</option>
              <option value="dark">Dark</option>
            </Select>
          </div>
        </div>
      </div>
    </div>
  );
}