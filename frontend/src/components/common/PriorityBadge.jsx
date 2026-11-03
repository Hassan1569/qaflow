import React from 'react';
import { getPriorityColor, formatStatusLabel } from '../../utils/statusMaps';

export function PriorityBadge({ priority, className = '' }) {
  if (!priority) return null;

  const colorClasses = getPriorityColor(priority);
  const label = formatStatusLabel(priority);

  return (
    <span
      className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ${colorClasses} ${className}`}
    >
      {label}
    </span>
  );
}