# QAFlow — Engineering Roadmap

Each task lists a concrete deliverable and acceptance criteria. Tasks are ordered so the project remains runnable at the end of every phase.

## Phase 0 — Foundation

- [ ] Initialize repo structure per `architecture.md`.
- [ ] Configure backend `.env.example` and `.env` loader.
- [ ] Implement `core/Router`, `core/Container`, `core/AppException`, `core/Handler`.
- [ ] Implement `helpers/Response`, `helpers/Request`, `helpers/Logger`, `helpers/Sanitizer`, `helpers/Uuid`.
- [ ] Configure frontend Vite + React + Tailwind + Emotion + Router.
- [ ] Configure axios `apiClient` with base URL and interceptors.
- **Acceptance**: `GET /api/health` returns `{ "data": { "status": "ok" } }`; frontend renders a blank shell.

## Phase 1 — Authentication

- [ ] `users`, `auth_tokens`, `login_attempts` tables.
- [ ] `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/me`.
- [ ] `AuthMiddleware` with bearer token validation.
- [ ] `AuthService` with `password_hash` / `password_verify`.
- [ ] Login rate limiting (10/min/IP).
- [ ] Frontend `LoginPage`, `AuthContext`, `ProtectedRoute`.
- **Acceptance**: Invalid credentials return 401 with a generic message; a valid login issues a token and `/auth/me` returns the profile.

## Phase 2 — Projects

- [ ] `projects`, `project_members` tables.
- [ ] Project CRUD endpoints with role enforcement.
- [ ] Member management endpoints.
- [ ] Frontend `ProjectsPage`, `ProjectDetailPage`, `ProjectForm`, `ProjectMembers`.
- **Acceptance**: A QA Lead can create a project, add a QA Engineer, and archive the project.

## Phase 3 — Requirements

- [ ] `requirements`, `requirement_versions` tables.
- [ ] CRUD endpoints with filters (`status`, `priority`, `search`).
- [ ] Frontend `RequirementsPage`, `RequirementForm`, `RequirementDetailPage`.
- **Acceptance**: Requirements list is paginated and filterable server-side; updates write a snapshot row.

## Phase 4 — Test Cases

- [ ] `test_cases`, `test_steps`, `test_case_versions`, `test_case_requirements` tables.
- [ ] CRUD with steps array in the request body.
- [ ] Version snapshot on update; `GET /test-cases/{id}/versions` returns snapshots.
- [ ] Frontend `TestCasesPage`, `TestCaseForm`, `TestStepEditor`, `VersionHistory`.
- **Acceptance**: Creating a case with 4 steps persists them in order; updating writes a snapshot; listing is paginated.

## Phase 5 — Test Suites

- [ ] `test_suites` table with `parent_id`.
- [ ] CRUD endpoints, move case between suites.
- [ ] Frontend `TestSuitesPage`, `SuiteTree`, `SuiteForm`.
- **Acceptance**: Nested suites render; case counts reflect actual test cases.

## Phase 6 — Test Plans

- [ ] `test_plans` table.
- [ ] CRUD endpoints with `status`.
- [ ] Frontend `TestPlansPage`, `TestPlanForm`, `TestPlanDetailPage`.
- **Acceptance**: Plan detail lists its runs; owner assignment is validated.

## Phase 7 — Test Runs

- [ ] `test_runs`, `test_run_cases` tables.
- [ ] Create run from a set of case IDs, environment ID, and assignee.
- [ ] Start/complete transitions.
- [ ] Frontend `TestRunsPage`, `TestRunForm`, `TestRunDetailPage`, `TestRunProgress`.
- **Acceptance**: Run creation copies case refs; starting sets `started_at`; completing sets `completed_at` and blocks further execution.

## Phase 8 — Execution

- [ ] `test_results`, `test_step_results` tables.
- [ ] `POST /executions/{id}/steps/{stepId}/result` writes step results.
- [ ] `POST /executions/{id}/result` writes the overall result.
- [ ] Frontend `ExecutionPage`, `ExecutionPanel`, `StepResultRow`, `ExecutionSidebar`.
- **Acceptance**: Step-by-step execution persists; overall status is computed or overridden; the run progress bar updates.

## Phase 9 — Defects

- [ ] `defects` table with severity/priority as separate columns.
- [ ] CRUD endpoints, status transitions, comments linked to defects.
- [ ] Frontend `DefectsPage`, `DefectForm`, `DefectDetailPage`, `DefectPanel`.
- **Acceptance**: Creating a defect from a failed execution pre-fills test case, requirement, environment, expected and actual.

## Phase 10 — Traceability

- [ ] `TraceabilityService` and `GET /api/traceability/matrix`.
- [ ] Frontend `TraceabilityPage` with expandable rows.
- **Acceptance**: Per-requirement counts match a hand-computed example on the seed data.

## Phase 11 — Reports

- [ ] `ReportService` with execution, defects, coverage, project-summary queries.
- [ ] Frontend `ReportsPage`, `ReportFilters`, `ReportTable`, CSV export.
- **Acceptance**: CSV export matches the on-screen table; totals reconcile with dashboard.

## Phase 12 — Attachments

- [ ] `attachments` table and storage layout.
- [ ] `POST /api/attachments`, `GET /api/attachments/{id}`, `DELETE`.
- [ ] MIME validation, size cap, safe filename.
- [ ] Frontend `AttachmentList` on defect, test case, and execution pages.
- **Acceptance**: PNG/PDF accepted; `.exe` rejected; download requires auth.

## Phase 13 — Comments

- [ ] `comments` table with polymorphic reference.
- [ ] `POST /api/comments`, list by entity.
- [ ] Frontend `CommentThread` on defects, test cases, requirements, runs.
- **Acceptance**: Comment appears immediately; author and timestamp are correct.

## Phase 14 — Activity Log

- [ ] `activity_logs` table.
- [ ] `ActivityService` called from mutating services.
- [ ] `GET /api/activity` with filters.
- [ ] Frontend `ActivityTimeline` on entity detail pages.
- **Acceptance**: Creating a test case writes an activity row with actor and entity.

## Phase 15 — Roles & Permissions

- [ ] `RoleMiddleware` and per-route role declarations.
- [ ] `usePermissions` hook on the frontend.
- [ ] Deny UI elements the current user cannot use.
- **Acceptance**: A Viewer sees no create/edit controls; a Developer cannot create a test case via API.

## Phase 16 — Automation API

- [ ] `api_tokens` table and token issuance.
- [ ] `POST /api/automation/ingest` writes results with `is_automated = 1`.
- [ ] Documentation in `architecture.md`.
- **Acceptance**: A sample payload from the docs creates results visible in the run.

## Phase 17 — AI Extension

- [ ] `AiSuggestionService` interface with a deterministic default.
- [ ] Config flag `AI_ENABLED`.
- **Acceptance**: Service returns a template-based list; no external call required.

## Phase 18 — Testing

- [ ] Backend smoke tests for authentication, project CRUD, execution flow.
- [ ] Frontend manual QA pass on all routes with seed data.
- **Acceptance**: Full lifecycle can be executed end-to-end without errors.

## Phase 19 — Security Review

- [ ] Verify all SQL is parameterized.
- [ ] Verify uploads are validated and stored outside the web root.
- [ ] Verify rate limits on login and ingestion.
- [ ] Verify `.env` is gitignored.
- **Acceptance**: A security checklist is committed; no plaintext secrets.

## Phase 20 — Production Hardening

- [ ] Configure error logging and rotation.
- [ ] Configure HTTPS termination and CORS for the production origin.
- [ ] Document deployment in `architecture.md`.
- **Acceptance**: Production build is reachable via HTTPS with a valid CORS origin.