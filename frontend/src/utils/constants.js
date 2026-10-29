/**
 * QAFlow — Static Constants
 * 
 * Defines the enums and display labels used across the application.
 * These map directly to the backend validations defined in the PRD.
 */

export const ROLES = [
  { value: 'admin', label: 'Administrator' },
  { value: 'qa_lead', label: 'QA Lead' },
  { value: 'qa_engineer', label: 'QA Engineer' },
  { value: 'developer', label: 'Developer' },
  { value: 'viewer', label: 'Viewer' },
];

export const PROJECT_STATUSES = [
  { value: 'active', label: 'Active' },
  { value: 'archived', label: 'Archived' },
  { value: 'on_hold', label: 'On Hold' },
];

export const PRIORITIES = [
  { value: 'critical', label: 'Critical' },
  { value: 'high', label: 'High' },
  { value: 'medium', label: 'Medium' },
  { value: 'low', label: 'Low' },
];

export const SEVERITIES = [
  { value: 'critical', label: 'Critical' },
  { value: 'high', label: 'High' },
  { value: 'medium', label: 'Medium' },
  { value: 'low', label: 'Low' },
];

export const REQUIREMENT_STATUSES = [
  { value: 'draft', label: 'Draft' },
  { value: 'in_review', label: 'In Review' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'implemented', label: 'Implemented' },
  { value: 'deprecated', label: 'Deprecated' },
];

export const TEST_TYPES = [
  { value: 'functional', label: 'Functional' },
  { value: 'regression', label: 'Regression' },
  { value: 'smoke', label: 'Smoke' },
  { value: 'sanity', label: 'Sanity' },
  { value: 'integration', label: 'Integration' },
  { value: 'ui', label: 'UI' },
  { value: 'api', label: 'API' },
  { value: 'security', label: 'Security' },
  { value: 'performance', label: 'Performance' },
  { value: 'compatibility', label: 'Compatibility' },
];

export const AUTOMATION_STATUSES = [
  { value: 'manual', label: 'Manual' },
  { value: 'automated', label: 'Automated' },
  { value: 'planned', label: 'Planned' },
];

export const PLAN_STATUSES = [
  { value: 'draft', label: 'Draft' },
  { value: 'active', label: 'Active' },
  { value: 'completed', label: 'Completed' },
  { value: 'archived', label: 'Archived' },
];

export const RUN_STATUSES = [
  { value: 'not_started', label: 'Not Started' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'completed', label: 'Completed' },
  { value: 'aborted', label: 'Aborted' },
];

export const EXECUTION_STATUSES = [
  { value: 'pass', label: 'Pass' },
  { value: 'fail', label: 'Fail' },
  { value: 'blocked', label: 'Blocked' },
  { value: 'not_run', label: 'Not Run' },
];

export const DEFECT_STATUSES = [
  { value: 'open', label: 'Open' },
  { value: 'assigned', label: 'Assigned' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'fixed', label: 'Fixed' },
  { value: 'retest', label: 'Retest' },
  { value: 'verified', label: 'Verified' },
  { value: 'closed', label: 'Closed' },
  { value: 'reopened', label: 'Reopened' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'duplicate', label: 'Duplicate' },
];

export const DEFECT_OPEN_STATUSES = [
  'open', 'assigned', 'in_progress', 'fixed', 'retest', 'reopened'
];

export const PAGE_SIZES = [10, 25, 50, 100];