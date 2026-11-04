import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { PageHeader } from '../components/common/PageHeader';
import { Breadcrumb } from '../components/common/Breadcrumb';
import { StatusBadge } from '../components/common/StatusBadge';
import { PriorityBadge } from '../components/common/PriorityBadge';
import { Button } from '../components/common/Button';
import { RequirementForm } from '../components/requirements/RequirementForm';
import { Modal } from '../components/common/Modal';
import { LoadingState } from '../components/common/LoadingState';
import { ErrorState } from '../components/common/ErrorState';
import { CommentThread } from '../components/shared/CommentThread';
import { requirementService } from '../services/requirementService';
import { commentService } from '../services/commentService';
import { usePermissions } from '../hooks/usePermissions';
import { useToast } from '../hooks/useToast';
import { formatDate } from '../utils/format';

export default function RequirementDetailPage() {
  const { id } = useParams();
  const [requirement, setRequirement] = useState(null);
  const [comments, setComments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { canManageRequirements } = usePermissions();
  const toast = useToast();

  const fetchData = async () => {
    setLoading(true);
    try {
      const reqData = await requirementService.get(id);
      setRequirement(reqData);
      
      const commentRes = await commentService.list('requirement', id);
      setComments(commentRes);
    } catch (err) {
      setError(err.message || 'Failed to load requirement.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id]);

  const handleUpdate = async (data) => {
    setIsSubmitting(true);
    try {
      await requirementService.update(id, data);
      toast.success('Requirement updated.');
      setIsEditModalOpen(false);
      fetchData();
    } catch (err) {
      toast.error(err.message || 'Failed to update requirement.');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (loading) return <LoadingState />;
  if (error) return <ErrorState message={error} onRetry={fetchData} />;
  if (!requirement) return null;

  return (
    <div>
      <Breadcrumb items={[
        { label: 'Projects', to: '/projects' },
        { label: requirement.project_key, to: `/projects/${requirement.project_id}` },
        { label: 'Requirements', to: `/requirements?project_id=${requirement.project_id}` },
        { label: requirement.code }
      ]} />

      <PageHeader 
        title={requirement.title} 
        description={`${requirement.code} in ${requirement.project_name}`}
        action={
          canManageRequirements() && (
            <Button variant="secondary" onClick={() => setIsEditModalOpen(true)}>Edit</Button>
          )
        }
      />

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-6">
          <div className="bg-surface border border-border rounded-md p-6">
            <div className="flex gap-4 mb-4">
              <div>
                <p className="text-xs text-text-muted uppercase mb-1">Status</p>
                <StatusBadge status={requirement.status} />
              </div>
              <div>
                <p className="text-xs text-text-muted uppercase mb-1">Priority</p>
                <PriorityBadge priority={requirement.priority} />
              </div>
            </div>
            
            <h3 className="text-sm font-semibold text-text mb-2">Description</h3>
            <p className="text-sm text-text whitespace-pre-wrap bg-bg p-4 rounded border border-border">
              {requirement.description || 'No description provided.'}
            </p>
          </div>

          <div className="bg-surface border border-border rounded-md p-6">
            <h3 className="text-lg font-semibold text-text mb-4">Linked Test Cases ({requirement.test_cases?.length || 0})</h3>
            {requirement.test_cases?.length === 0 ? (
              <p className="text-sm text-text-muted italic">No test cases linked to this requirement yet.</p>
            ) : (
              <ul className="divide-y divide-border border border-border rounded-md">
                {requirement.test_cases.map(tc => (
                  <li key={tc.id} className="p-3 hover:bg-bg transition-colors flex justify-between items-center">
                    <div>
                      <Link to={`/test-cases/${tc.id}`} className="text-sm font-medium text-primary hover:underline">
                        {tc.code} - {tc.title}
                      </Link>
                      <p className="text-xs text-text-muted mt-0.5 capitalize">{tc.automation_status}</p>
                    </div>
                    <PriorityBadge priority={tc.priority} />
                  </li>
                ))}
              </ul>
            )}
          </div>

          <div className="bg-surface border border-border rounded-md p-6">
            <h3 className="text-lg font-semibold text-text mb-4">Comments</h3>
            <CommentThread 
              entityType="requirement" 
              entityId={id} 
              comments={comments} 
              onRefresh={fetchData} 
            />
          </div>
        </div>

        <div className="lg:col-span-1">
          <div className="bg-surface border border-border rounded-md p-5 sticky top-6">
            <h3 className="text-sm font-semibold text-text mb-4 border-b border-border pb-2">Details</h3>
            <dl className="space-y-3 text-sm">
              <div>
                <dt className="text-text-muted text-xs mb-0.5">Author</dt>
                <dd className="font-medium text-text">{requirement.author_name}</dd>
              </div>
              <div>
                <dt className="text-text-muted text-xs mb-0.5">Created</dt>
                <dd className="font-medium text-text">{formatDate(requirement.created_at, true)}</dd>
              </div>
              <div>
                <dt className="text-text-muted text-xs mb-0.5">Last Updated</dt>
                <dd className="font-medium text-text">{formatDate(requirement.updated_at, true)}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <Modal isOpen={isEditModalOpen} onClose={() => setIsEditModalOpen(false)} title="Edit Requirement">
        <RequirementForm 
          initialData={requirement}
          projectId={requirement.project_id}
          onSubmit={handleUpdate}
          onCancel={() => setIsEditModalOpen(false)}
          isLoading={isSubmitting}
        />
      </Modal>
    </div>
  );
}