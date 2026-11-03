import React from 'react';
import { getStatusColor, formatStatusLabel } from '../../utils/statusMaps';

export function StatusBadge({ status, className = '' }) {
  if (!status) return null;

  const colorClasses = getStatusColor(status);
  const label = formatStatusLabel(status);

  return (
    <span
      className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ${colorClasses} ${className}`}
    >
      {label}
    </span>
  );
}