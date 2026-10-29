/**
 * QAFlow — Status & Priority Maps
 *
 * Maps backend enum values to Tailwind utility classes and display labels.
 * Used by StatusBadge and PriorityBadge.
 */

export const STATUS_COLORS = {
  // Execution Statuses
  pass: 'bg-pass/10 text-pass border-pass/20',
  fail: 'bg-fail/10 text-fail border-fail/20',
  blocked: 'bg-blocked/10 text-blocked border-blocked/20',
  not_run: 'bg-not-run/10 text-not-run border-not-run/20',

  // Defect Statuses
  open: 'bg-fail/10 text-fail border-fail/20',
  assigned: 'bg-info/10 text-info border-info/20',
  in_progress: 'bg-primary/10 text-primary border-primary/20',
  fixed: 'bg-pass/10 text-pass border-pass/20',
  retest: 'bg-blocked/10 text-blocked border-blocked/20',
  verified: 'bg-pass/10 text-pass border-pass/20',
  closed: 'bg-surface text-text-muted border-border',
  reopened: 'bg-fail/10 text-fail border-fail/20',
  rejected: 'bg-surface text-text-muted border-border',
  duplicate: 'bg-surface text-text-muted border-border',

  // Requirement & Plan Statuses
  draft: 'bg-surface text-text-muted border-border',
  in_review: 'bg-blocked/10 text-blocked border-blocked/20',
  approved: 'bg-pass/10 text-pass border-pass/20',
  implemented: 'bg-primary/10 text-primary border-primary/20',
  deprecated: 'bg-surface text-text-muted border-border',
  active: 'bg-pass/10 text-pass border-pass/20',
  archived: 'bg-surface text-text-muted border-border',
  on_hold: 'bg-blocked/10 text-blocked border-blocked/20',
  completed: 'bg-pass/10 text-pass border-pass/20',

  // Run Statuses
  not_started: 'bg-surface text-text-muted border-border',
  aborted: 'bg-fail/10 text-fail border-fail/20',
};

export const PRIORITY_COLORS = {
  critical: 'bg-critical/10 text-critical border-critical/20',
  high: 'bg-high/10 text-high border-high/20',
  medium: 'bg-medium/10 text-medium border-medium/20',
  low: 'bg-low/10 text-low border-low/20',
};

export const getStatusColor = (status) => {
  return STATUS_COLORS[status] || 'bg-surface text-text border-border';
};

export const getPriorityColor = (priority) => {
  return PRIORITY_COLORS[priority] || 'bg-surface text-text border-border';
};

export const formatStatusLabel = (status) => {
  if (!status) return '';
  return status
    .split('_')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
};