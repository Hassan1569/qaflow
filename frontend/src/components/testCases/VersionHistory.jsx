import React, { useState, useEffect } from 'react';
import { testCaseService } from '../../services/testCaseService';
import { formatDate } from '../../utils/format';
import { LoadingState } from '../common/LoadingState';
import { ErrorState } from '../common/ErrorState';

export function VersionHistory({ testCaseId }) {
  const [versions, setVersions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [expandedVersion, setExpandedVersion] = useState(null);

  useEffect(() => {
    const fetchVersions = async () => {
      try {
        setLoading(true);
        const data = await testCaseService.getVersions(testCaseId);
        setVersions(data);
      } catch (err) {
        setError(err.message || 'Failed to load version history.');
      } finally {
        setLoading(false);
      }
    };
    fetchVersions();
  }, [testCaseId]);

  if (loading) return <LoadingState message="Loading history..." />;
  if (error) return <ErrorState message={error} />;
  if (versions.length === 0) {
    return <p className="text-sm text-text-muted italic py-4">No version history available.</p>;
  }

  return (
    <div className="space-y-4">
      {versions.map((v) => (
        <div key={v.id} className="border border-border rounded-md bg-surface overflow-hidden">
          <div 
            className="px-4 py-3 bg-bg flex justify-between items-center cursor-pointer hover:bg-border/30 transition-colors"
            onClick={() => setExpandedVersion(expandedVersion === v.id ? null : v.id)}
          >
            <div className="flex items-center gap-3">
              <span className="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary-light text-primary font-bold text-sm">
                v{v.version_no}
              </span>
              <div>
                <p className="text-sm font-medium text-text">Updated by {v.changed_by_name}</p>
                <p className="text-xs text-text-muted">{formatDate(v.created_at, true)}</p>
              </div>
            </div>
            <svg 
              className={`w-5 h-5 text-text-muted transition-transform ${expandedVersion === v.id ? 'rotate-180' : ''}`} 
              fill="none" viewBox="0 0 24 24" stroke="currentColor"
            >
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
            </svg>
          </div>
          
          {expandedVersion === v.id && (
            <div className="p-4 border-t border-border text-sm">
              {v.changed_fields && (
                <p className="mb-3">
                  <span className="font-medium text-text-muted">Changed fields: </span>
                  <span className="text-text">{v.changed_fields}</span>
                </p>
              )}
              
              <div className="bg-bg p-3 rounded border border-border overflow-x-auto text-xs font-mono text-text-muted whitespace-pre-wrap">
                {JSON.stringify(v.snapshot, null, 2)}
              </div>
            </div>
          )}
        </div>
      ))}
    </div>
  );
}