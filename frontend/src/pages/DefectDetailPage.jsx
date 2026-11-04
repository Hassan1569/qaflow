import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { PageHeader } from '../components/common/PageHeader';
import { Breadcrumb } from '../components/common/Breadcrumb';
import { StatusBadge } from '../components/common/StatusBadge';
import { PriorityBadge } from '../components/common/PriorityBadge';
import { LoadingState } from '../components/common/LoadingState';
import { ErrorState } from '../components/common/ErrorState';
import { Tabs } from '../components/common/Tabs';
import { Select } from '../components/common/Select';
import { CommentThread } from '../components/shared/CommentThread';
import { ActivityTimeline } from '../components/shared/ActivityTimeline';
import { AttachmentList } from '../components/shared/AttachmentList';
import { defectService } from '../services/defectService';
import { commentService } from '../services/commentService';
import { activityService } from '../services/activityService';
import { usePermissions } from '../hooks/usePermissions';
import { useToast } from '../hooks/useToast';
import { DEFECT_STATUSES } from '../utils/constants';
import { formatDate } from '../utils/format';

export default function DefectDetailPage() {
  const { id } = useParams();
  const [defect, setDefect] = useState(null);
  const [comments, setComments] = useState([]);
  const [activities, setActivities] = useState([]);
  const [attachments, setAttachments] = useState([]);
  
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [activeTab, setActiveTab] = useState('details');
  const [isUpdatingStatus, setIsUpdatingStatus] = useState(false);

  const { canUpdateDefects } = usePermissions();
  const toast = useToast();

  const fetchData = async () => {
    setLoading(true);
    try {
      const defectData = await defectService.get(id);
      setDefect(defectData);
      
      const [commentRes, activityRes] = await Promise.all([
        commentService.list('defect', id),
        activityService.list({ entity_type: 'defect', entity_id: id, per_page: 20 })
      ]);
      
      setComments(commentRes);
      setActivities(activityRes.data);
      // Note: Attachments would normally be fetched here too, but the backend doesn't have a specific endpoint for listing by entity yet, 
      // so we'll assume the Defect model doesn't return them inline. We'll mock the initial state for the UI.
      setAttachments([]); // In a real app, you'd fetch this via a dedicated endpoint.
      
    } catch (err) {
      setError(err.message || 'Failed to load defect details.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id]);

  const handleStatusChange = async (e) => {
    const newStatus = e.target.value;
    if (newStatus === defect.status) return;
    
    setIsUpdatingStatus(true);
    try {
      await defectService.changeStatus(id, newStatus);
      toast.success('Status updated.');
      setDefect(prev => ({ ...prev, status: newStatus }));
      // Refresh activity log
      const activityRes = await activityService.list({ entity_type: 'defect', entity_id: id, per_page: 20 });
      setActivities(activityRes.data);
    } catch (err) {
      toast.error(err.message || 'Failed to update status.');
    } finally {
      setIsUpdatingStatus(false);
    }
  };

  if (loading) return <LoadingState />;
  if (error) return <ErrorState message={error} onRetry={fetchData} />;
  if (!defect) return null;

  const tabs = [
    { id: 'details', label: 'Details' },
    { id: 'comments', label: `Comments (${comments.length})` },
    { id: 'attachments', label: 'Attachments' },
    { id: 'activity', label: 'Activity Log' },
  ];

  return (
    <div>
      <Breadcrumb items={[
        { label: 'Projects', to: '/projects' },
        { label: defect.project_key, to: `/projects/${defect.project_id}` },
        { label: 'Defects', to: `/defects?project_id=${defect.project_id}` },
        { label: defect.code }
      ]} />

      <PageHeader 
        title={defect.title} 
        description={`${defect.code} • Reported by ${defect.reporter_name}`}
        action={
          <div className="flex items-center gap-3 bg-surface border border-border p-1 rounded-md pr-3">
            <span className="text-xs font-medium text-text-muted pl-2 uppercase tracking-wide">Status:</span>
            {canUpdateDefects() ? (
              <Select 
                value={defect.status} 
                onChange={handleStatusChange} 
                disabled={isUpdatingStatus}
                className="!py-1 !text-sm !border-0 !ring-0 !bg-transparent font-medium"
              >
                {DEFECT_STATUSES.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
              </Select>
            ) : (
              <StatusBadge status={defect.status} />
            )}
          </div>
        }
      />

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-6">
          <Tabs tabs={tabs} activeTab={activeTab} onChange={setActiveTab} />

          {activeTab === 'details' && (
            <div className="bg-surface border border-border rounded-md p-6 space-y-6">
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4 pb-4 border-b border-border">
                <div>
                  <p className="text-xs text-text-muted uppercase mb-1">Severity</p>
                  <PriorityBadge priority={defect.severity} />
                </div>
                <div>
                  <p className="text-xs text-text-muted uppercase mb-1">Priority</p>
                  <PriorityBadge priority={defect.priority} />
                </div>
                <div>
                  <p className="text-xs text-text-muted uppercase mb-1">Assignee</p>
                  <p className="text-sm font-medium">{defect.assignee_name || 'Unassigned'}</p>
                </div>
                <div>
                  <p className="text-xs text-text-muted uppercase mb-1">Environment</p>
                  <p className="text-sm font-medium">{defect.environment_name || '—'}</p>
                </div>
              </div>

              {defect.description && (
                <div>
                  <h3 className="text-sm font-semibold text-text mb-2">Description</h3>
                  <p className="text-sm text-text whitespace-pre-wrap bg-bg p-3 rounded border border-border">{defect.description}</p>
                </div>
              )}

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <h3 className="text-sm font-semibold text-text mb-2">Expected Result</h3>
                  <p className="text-sm text-text whitespace-pre-wrap bg-bg p-3 rounded border border-border">{defect.expected_result || '—'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-semibold text-text mb-2">Actual Result</h3>
                  <p className="text-sm text-text whitespace-pre-wrap bg-bg p-3 rounded border border-border border-l-4 border-l-fail">{defect.actual_result || '—'}</p>
                </div>
              </div>
            </div>
          )}

          {activeTab === 'comments' && (
            <div className="bg-surface border border-border rounded-md p-6">
              <CommentThread 
                entityType="defect" 
                entityId={id} 
                comments={comments} 
                onRefresh={fetchData} 
              />
            </div>
          )}

          {activeTab === 'attachments' && (
            <div className="bg-surface border border-border rounded-md p-6">
              <AttachmentList 
                entityType="defect"
                entityId={id}
                attachments={attachments}
                onRefresh={fetchData}
              />
            </div>
          )}

          {activeTab === 'activity' && (
            <div className="bg-surface border border-border rounded-md p-6">
              <ActivityTimeline activities={activities} />
            </div>
          )}
        </div>

        <div className="lg:col-span-1">
          <div className="bg-surface border border-border rounded-md p-5 sticky top-6">
            <h3 className="text-sm font-semibold text-text mb-4 border-b border-border pb-2">Traceability Links</h3>
            <dl className="space-y-4 text-sm">
              <div>
                <dt className="text-text-muted text-xs mb-1">Related Test Case</dt>
                <dd>
                  {defect.test_case_id ? (
                    <Link to={`/test-cases/${defect.test_case_id}`} className="text-primary hover:underline font-medium">
                      {defect.test_case_code}
                    </Link>
                  ) : '—'}
                </dd>
              </div>
              <div>
                <dt className="text-text-muted text-xs mb-1">Related Requirement</dt>
                <dd>
                  {defect.requirement_id ? (
                    <Link to={`/requirements/${defect.requirement_id}`} className="text-primary hover:underline font-medium">
                      {defect.requirement_code}
                    </Link>
                  ) : '—'}
                </dd>
              </div>
            </dl>

            <h3 className="text-sm font-semibold text-text mb-2 mt-6 border-b border-border pb-2">Metadata</h3>
            <dl className="space-y-3 text-sm">
              <div>
                <dt className="text-text-muted text-xs mb-0.5">Created</dt>
                <dd className="font-medium text-text">{formatDate(defect.created_at, true)}</dd>
              </div>
              <div>
                <dt className="text-text-muted text-xs mb-0.5">Last Updated</dt>
                <dd className="font-medium text-text">{formatDate(defect.updated_at, true)}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
    </div>
  );
}