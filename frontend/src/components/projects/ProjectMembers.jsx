import React, { useState, useEffect } from 'react';
import { Button } from '../common/Button';
import { Select } from '../common/Select';
import { ConfirmationDialog } from '../common/ConfirmationDialog';
import { userService } from '../../services/userService';
import { projectService } from '../../services/projectService';
import { useToast } from '../../hooks/useToast';
import { ROLES } from '../../utils/constants';

export function ProjectMembers({ projectId, initialMembers, onRefresh }) {
  const [members, setMembers] = useState(initialMembers || []);
  const [users, setUsers] = useState([]);
  const [newMember, setNewMember] = useState({ user_id: '', role: 'qa_engineer' });
  const [isAdding, setIsAdding] = useState(false);
  const [removeTarget, setRemoveTarget] = useState(null);
  const toast = useToast();

  useEffect(() => {
    const loadUsers = async () => {
      try {
        const res = await userService.list({ options: true });
        setUsers(res.data);
      } catch (err) {
        console.error(err);
      }
    };
    loadUsers();
  }, []);

  const handleAdd = async () => {
    if (!newMember.user_id) {
      toast.error('Please select a user.');
      return;
    }
    setIsAdding(true);
    try {
      await projectService.addMember(projectId, newMember);
      toast.success('Member added.');
      setNewMember({ user_id: '', role: 'qa_engineer' });
      if (onRefresh) onRefresh();
    } catch (err) {
      toast.error(err.message || 'Failed to add member.');
    } finally {
      setIsAdding(false);
    }
  };

  const handleRemove = async () => {
    if (!removeTarget) return;
    try {
      await projectService.removeMember(projectId, removeTarget.user_id);
      toast.success('Member removed.');
      setMembers(prev => prev.filter(m => m.user_id !== removeTarget.user_id));
      if (onRefresh) onRefresh();
    } catch (err) {
      toast.error(err.message || 'Failed to remove member.');
    } finally {
      setRemoveTarget(null);
    }
  };

  return (
    <div className="bg-surface border border-border rounded-md overflow-hidden">
      <div className="px-5 py-4 border-b border-border bg-bg">
        <h3 className="text-base font-semibold text-text">Project Members</h3>
      </div>

      <ul className="divide-y divide-border">
        {members.map((m) => (
          <li key={m.user_id} className="px-5 py-3 flex items-center justify-between hover:bg-bg transition-colors">
            <div className="flex items-center gap-3">
              <div className="w-8 h-8 rounded-full bg-primary-light text-primary flex items-center justify-center font-bold text-xs uppercase">
                {m.name.charAt(0)}
              </div>
              <div>
                <p className="text-sm font-medium text-text">{m.name}</p>
                <p className="text-xs text-text-muted">{m.email}</p>
              </div>
            </div>
            <div className="flex items-center gap-4">
              <span className="text-xs font-medium text-text-muted capitalize px-2 py-1 bg-bg border border-border rounded">
                {m.role.replace('_', ' ')}
              </span>
              <button 
                onClick={() => setRemoveTarget(m)}
                className="text-text-muted hover:text-fail transition-colors text-sm"
              >
                Remove
              </button>
            </div>
          </li>
        ))}
      </ul>

      <div className="p-5 border-t border-border bg-bg flex items-end gap-3">
        <div className="flex-1">
          <label className="block text-xs font-medium text-text-muted mb-1">Add Member</label>
          <Select 
            value={newMember.user_id} 
            onChange={(e) => setNewMember({...newMember, user_id: e.target.value})}
          >
            <option value="">Select user...</option>
            {users.map(u => (
              <option key={u.id} value={u.id}>{u.name}</option>
            ))}
          </Select>
        </div>
        <div className="w-40">
          <label className="block text-xs font-medium text-text-muted mb-1">Role</label>
          <Select 
            value={newMember.role} 
            onChange={(e) => setNewMember({...newMember, role: e.target.value})}
          >
            {ROLES.map(r => (
              <option key={r.value} value={r.value}>{r.label}</option>
            ))}
          </Select>
        </div>
        <Button onClick={handleAdd} isLoading={isAdding} size="md">Add</Button>
      </div>

      <ConfirmationDialog
        isOpen={!!removeTarget}
        onClose={() => setRemoveTarget(null)}
        onConfirm={handleRemove}
        title="Remove Member"
        message={`Are you sure you want to remove ${removeTarget?.name} from this project?`}
        isDanger
      />
    </div>
  );
}