import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { PageHeader } from '../components/common/PageHeader';
import { Breadcrumb } from '../components/common/Breadcrumb';
import { PriorityBadge } from '../components/common/PriorityBadge';
import { StatusBadge } from '../components/common/StatusBadge';
import { Button } from '../components/common/Button';
import { TestCaseForm } from '../components/testCases/TestCaseForm';
import { VersionHistory } from '../components/testCases/VersionHistory';
import { Modal } from '../components/common/Modal';
import { Tabs } from '../components/common/Tabs';
import { LoadingState } from '../components/common/LoadingState';
import { ErrorState } from '../components/common/ErrorState';
import { CommentThread } from '../components/shared/CommentThread';
import { testCaseService } from '../services/testCaseService';
import { commentService } from '../services/commentService';
import { usePermissions } from '../hooks/usePermissions';
import { useToast } from '../hooks/useToast';
import { formatDate } from '../utils/format';

export default function TestCaseDetailPage() {
  const { id } = useParams();
  const [testCase, setTestCase] = useState(null);
  const [comments, setComments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [activeTab, setActiveTab] = useState('details');
  
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { canManageTestCases } = usePermissions();
  const toast = useToast();

  const fetchData = async () => {
    setLoading(true);
    try {
      const tcData = await testCaseService.get(id);
      setTestCase(tcData);
      
      const commentRes = await commentService.list('test_case', id);
      setComments(commentRes);
    } catch (err) {
      setError(err.message || 'Failed to load test case.');
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
      await testCaseService.update(id, data);
      toast.success('Test case updated.');
      setIsEditModalOpen(false);
      fetchData();
    } catch (err) {
      toast.error(err.message || 'Failed to update test case.');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (loading) return <LoadingState />;
  if (error) return <ErrorState message={error} onRetry={fetchData} />;
  if (!testCase) return null;

  const tabs = [
    { id: 'details', label: 'Details' },
    { id: 'steps', label: `Steps (${testCase.steps?.length || 0})` },
    { id: 'comments', label: `Comments (${comments.length})` },
    { id: 'history', label: 'Version History' },
  ];

  return (
    <div>
      <Breadcrumb items={[
        { label: 'Projects', to: '/projects' },
        { label: testCase.project_key, to: `/projects/${testCase.project_id}` },
        { label: 'Test Cases', to: `/test-cases?project_id=${testCase.project_id}` },
        { label: testCase.code }
      ]} />

      <PageHeader 
        title={testCase.title} 
        description={`${testCase.code} • ${testCase.suite_name || 'No Suite'}`}
        action={
          canManageTestCases() && (
            <Button variant="secondary" onClick={() => setIsEditModalOpen(true)}>Edit</Button>
          )
        }
      />

      <Tabs tabs={tabs} activeTab={activeTab} onChange={setActiveTab} />

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-6">
          {activeTab === 'details' && (
            <div className="bg-surface border border-border rounded-md p-6">
              <div className="flex gap-4 mb-6 pb-4 border-b border-border">
                <div>
                  <p className="text-xs text-text-muted uppercase mb-1">Priority</p>
                  <PriorityBadge priority={testCase.priority} />
                </div>
                <div>
                  <p className="text-xs text-text-muted uppercase mb-1">Severity</p>
                  <PriorityBadge priority={testCase.severity} />
                </div>
                <div>
                  <p className="text-xs text-text-muted uppercase mb-1">Type</p>
                  <StatusBadge status={testCase.test_type} />
                </div>
                <div>
                  <p className="text-xs text-text-muted uppercase mb-1">Automation</p>
                  <span className="text-sm font-medium capitalize">{testCase.automation_status}</span>
                </div>
              </div>

              {testCase.description && (
                <div className="mb-4">
                  <h3 className="text-sm font-semibold text-text mb-2">Description</h3>
                  <p className="text-sm text-text whitespace-pre-wrap">{testCase.description}</p>
                </div>
              )}
              
              {testCase.preconditions && (
                <div className="mb-4">
                  <h3 className="text-sm font-semibold text-text mb-2">Preconditions</h3>
                  <p className="text-sm text-text bg-bg p-3 rounded border border-border whitespace-pre-wrap">{testCase.preconditions}</p>
                </div>
              )}

              <div className="grid grid-cols-2 gap-4 mt-6 pt-4 border-t border-border">
                <div>
                  <p className="text-xs text-text-muted mb-1">Component</p>
                  <p className="text-sm font-medium">{testCase.component || '—'}</p>
                </div>
                <div>
                  <p className="text-xs text-text-muted mb-1">Tags</p>
                  <div className="flex flex-wrap gap-1">
                    {testCase.tag_list?.length > 0 ? testCase.tag_list.map(t => (
                      <span key={t} className="text-xs bg-bg border border-border px-2 py-0.5 rounded-full">{t}</span>
                    )) : '—'}
                  </div>
                </div>
              </div>
            </div>
          )}

          {activeTab === 'steps' && (
            <div className="bg-surface border border-border rounded-md p-6">
              <h3 className="text-lg font-semibold text-text mb-4">Test Steps</h3>
              {testCase.steps?.length === 0 ? (
                <p className="text-sm text-text-muted italic">No steps defined for this test case.</p>
              ) : (
                <div className="space-y-4">
                  {testCase.steps.map((step) => (
                    <div key={step.id} className="flex gap-4 p-3 bg-bg rounded border border-border">
                      <div className="flex-shrink-0 w-6 h-6 rounded-full bg-surface border border-border flex items-center justify-center text-xs font-bold text-text-muted mt-0.5">
                        {step.step_order}
                      </div>
                      <div className="flex-1">
                        <p className="text-sm font-medium text-text">{step.action}</p>
                        {step.expected_result && (
                          <p className="text-xs text-text-muted mt-1"><span className="font-semibold text-text-muted">Expected:</span> {step.expected_result}</p>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          {activeTab === 'comments' && (
            <div className="bg-surface border border-border rounded-md p-6">
              <CommentThread 
                entityType="test_case" 
                entityId={id} 
                comments={comments} 
                onRefresh={fetchData} 
              />
            </div>
          )}

          {activeTab === 'history' && (
            <div className="bg-surface border border-border rounded-md p-6">
              <VersionHistory testCaseId={id} />
            </div>
          )}
        </div>

        <div className="lg:col-span-1">
          <div className="bg-surface border border-border rounded-md p-5 sticky top-6">
            <h3 className="text-sm font-semibold text-text mb-4 border-b border-border pb-2">Details</h3>
            <dl className="space-y-3 text-sm">
              <div>
                <dt className="text-text-muted text-xs mb-0.5">Author</dt>
                <dd className="font-medium text-text">{testCase.author_name}</dd>
              </div>
              <div>
                <dt className="text-text-muted text-xs mb-0.5">Version</dt>
                <dd className="font-medium text-text">v{testCase.current_version}</dd>
              </div>
              <div>
                <dt className="text-text-muted text-xs mb-0.5">Created</dt>
                <dd className="font-medium text-text">{formatDate(testCase.created_at, true)}</dd>
              </div>
              <div>
                <dt className="text-text-muted text-xs mb-0.5">Last Updated</dt>
                <dd className="font-medium text-text">{formatDate(testCase.updated_at, true)}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <Modal isOpen={isEditModalOpen} onClose={() => setIsEditModalOpen(false)} title="Edit Test Case" maxWidth="max-w-4xl">
        <TestCaseForm 
          initialData={testCase}
          projectId={testCase.project_id}
          suites={[]} // Ideally fetch suites here, but omitting for brevity in this view
          requirements={[]} // Ideally fetch requirements here
          onSubmit={handleUpdate}
          onCancel={() => setIsEditModalOpen(false)}
          isLoading={isSubmitting}
        />
      </Modal>
    </div>
  );
}