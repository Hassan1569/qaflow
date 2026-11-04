import React from 'react';
import { formatDate } from '../../utils/format';

export function ActivityTimeline({ activities = [] }) {
  if (activities.length === 0) {
    return <p className="text-sm text-text-muted italic py-4">No activity recorded yet.</p>;
  }

  return (
    <div className="flow-root">
      <ul className="-mb-8">
        {activities.map((activity, activityIdx) => (
          <li key={activity.id}>
            <div className="relative pb-8">
              {activityIdx !== activities.length - 1 ? (
                <span className="absolute top-4 left-4 -ml-px h-full w-0.5 bg-border" aria-hidden="true" />
              ) : null}
              <div className="relative flex space-x-3">
                <div>
                  <span className="h-8 w-8 rounded-full bg-bg border border-border flex items-center justify-center ring-8 ring-surface">
                    <svg className="h-4 w-4 text-text-muted" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clipRule="evenodd" />
                    </svg>
                  </span>
                </div>
                <div className="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                  <div>
                    <p className="text-sm text-text">
                      {activity.description || activity.action}
                    </p>
                  </div>
                  <div className="text-right text-xs whitespace-nowrap text-text-muted">
                    <time dateTime={activity.created_at}>{formatDate(activity.created_at, true)}</time>
                  </div>
                </div>
              </div>
            </div>
          </li>
        ))}
      </ul>
    </div>
  );
}