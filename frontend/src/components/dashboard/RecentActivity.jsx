import React from 'react';
import { Link } from 'react-router-dom';
import { formatDate } from '../../utils/format';

export function RecentActivity({ activities }) {
  if (!activities || activities.length === 0) {
    return (
      <div className="bg-surface border border-border rounded-md shadow-card p-6 text-center text-sm text-text-muted">
        No recent activity found.
      </div>
    );
  }

  return (
    <div className="bg-surface border border-border rounded-md shadow-card">
      <div className="px-5 py-4 border-b border-border flex justify-between items-center">
        <h3 className="text-base font-semibold text-text">Recent Activity</h3>
        <Link to="/activity" className="text-sm text-primary hover:text-primary-hover font-medium">
          View all
        </Link>
      </div>
      <div className="divide-y divide-border">
        {activities.map((activity) => (
          <div key={activity.id} className="px-5 py-3 hover:bg-bg transition-colors">
            <p className="text-sm text-text">
              <span className="font-medium">{activity.user_name || 'System'}</span> {activity.description}
            </p>
            <span className="text-xs text-text-muted">
              {formatDate(activity.created_at, true)}
            </span>
          </div>
        ))}
      </div>
    </div>
  );
}