# QAFlow — Product Requirements Document

## 1. Product Overview

QAFlow is a web-based Software Quality Assurance and Test Management platform. It models the full QA lifecycle from requirements through defects, giving QA teams a single source of truth for test planning, execution, traceability, and reporting.

The platform targets internal enterprise use where accuracy, auditability, and workflow discipline matter more than decoration.

## 2. Problem Statement

QA teams commonly operate across spreadsheets, shared documents, chat threads, and ticket systems. This produces recurring problems:

- Test cases drift away from the requirements they were written for.
- Execution evidence is scattered and hard to audit.
- Coverage numbers are unreliable because they are maintained by hand.
- Defects lose their link to the test execution that produced them.
- Reports are stitched together manually from multiple sources.
- Role boundaries are informal; anyone can edit anything.

QAFlow addresses these problems by enforcing relationships between entities in a relational database and deriving every metric from that data rather than manual entry.

## 3. Goals

- Provide a single relational model for requirements, test cases, plans, runs, executions, defects, and evidence.
- Enforce traceability from requirement to defect without manual bookkeeping.
- Produce reliable dashboards and reports derived from real execution data.
- Enforce role-based access control on the backend.
- Maintain test case version history so edits do not destroy prior state.
- Provide a foundation for future automation ingestion and AI-assisted authoring without making them hard dependencies.

## 4. Non-Goals

- QAFlow is not a general-purpose issue tracker. It manages QA defects only.
- QAFlow does not execute Selenium, Playwright, or other automation runners. It accepts results from external runners via an ingestion endpoint.
- QAFlow is not a CI/CD orchestrator.
- AI test authoring is not part of the MVP. Only the service boundary is defined.

## 5. Target Users

- QA Leads who plan testing and assign work.
- QA Engineers who author and execute test cases.
- Developers who triage and fix defects.
- Project managers and stakeholders who read reports.
- Administrators who manage users, projects, and roles.

## 6. Personas

**Hassan — QA Lead.** Owns test plans for a Banking Portal release. Needs to know which requirements are uncovered, which runs are lagging, and where defects concentrate.

**Ahmed — QA Engineer.** Executes assigned test cases daily. Needs a fast execution screen, ability to attach screenshots, and a one-click path to open a defect from a failed test.

**Sara — Developer.** Receives assigned defects. Needs to see the failed test, the environment, the evidence, and the ability to move the defect to Verified.

**Admin.** Onboards users and projects, adjusts memberships, and expects every action to be auditable.

## 7. User Stories

- As a QA Lead, I can create a project, add members, and define requirements so test work is scoped.
- As a QA Lead, I can create a test plan for a release and generate a test run from selected test cases and environments.
- As a QA Engineer, I can open my assigned test run and execute each case step by step.
- As a QA Engineer, when a test fails I can create a defect pre-filled with the test, requirement, environment, and expected vs actual results.
- As a Developer, I can update a defect status, comment, and view the linked execution.
- As a QA Lead, I can view the requirement traceability matrix and see coverage, execution, pass/fail/blocked, and defect counts per requirement.
- As an Admin, I can inspect the activity log for any entity.

## 8. Functional Requirements

### 8.1 Authentication
- Email and password login.
- Passwords hashed via `password_hash` (bcrypt).
- Opaque bearer tokens persisted server-side with expiry.
- Logout deletes the token row.

### 8.2 Role-Based Access Control
Roles: Admin, QA Lead, QA Engineer, Developer, Viewer. Enforced in the backend middleware per route and per project membership.

### 8.3 Projects
CRUD, archive, member management, per-project statistics.

### 8.4 Requirements
CRUD, status and priority, link to test cases, version snapshots.

### 8.5 Test Suites
Hierarchical suites per project. Test cases belong to a suite.

### 8.6 Test Cases
CRUD with ordered steps, preconditions, priority, severity, test type, component, tags, automation status, and linked requirements. Snapshot-based version history on update.

### 8.7 Test Steps
Ordered rows with action and expected result. Reorderable.

### 8.8 Test Plans
Per-project planning entity with scope, dates, owner, and status. Contains test runs.

### 8.9 Test Runs
Generated from a plan. Contains references to selected test cases, assigned testers, and environments. Tracks progress and completion.

### 8.10 Test Execution
Per-case execution with per-step results and an overall result: PASS / FAIL / BLOCKED / NOT RUN. Notes and evidence supported.

### 8.11 Defects
CRUD, status lifecycle, severity and priority as separate concepts, comments, attachments, and links to test case, requirement, execution, and environment.

### 8.12 Environments
Reusable environment definitions (browser, OS, device, app version). Attached to execution results.

### 8.13 Attachments
Upload, entity association, secure storage, MIME and size validation.

### 8.14 Comments
Available on defects, test cases, requirements, and test runs.

### 8.15 Activity Log
Records key mutations with actor, entity type, entity id, action, and metadata.

### 8.16 Traceability Matrix
Per project: requirements with counts of linked test cases, executions, pass/fail/blocked, defects, and coverage percentage.

### 8.17 Reports
Test Execution Report, Defect Report, Coverage Report, Project QA Summary. CSV export; printable HTML for PDF via browser.

### 8.18 Automation Ingestion API
Endpoint accepting external test results (JSON) to create or update test results in a designated test run.

## 9. Non-Functional Requirements

- API responses under 300 ms for typical list endpoints on a seeded dataset of 10k test cases.
- All list endpoints paginated server-side.
- No plaintext secrets in source. Configuration via `.env`.
- Frontend presents loading, empty, and error states for every data-bound view.
- Flat UI: no gradients, no decorative animations.
- Light and dark themes share a semantic token system.

## 10. Core Workflows
Login
→ Create Project
→ Create Requirements
→ Create Test Suite(s)
→ Create Test Cases (link to requirements)
→ Create Test Plan
→ Create Test Run (select cases + environments + testers)
→ Execute Tests (PASS / FAIL / BLOCKED / NOT RUN)
→ Create Defects from failures
→ Retest on fix → Verify / Close
→ Review Traceability
→ Generate Reports

## 11. Modules

- Authentication
- Users & Roles
- Projects & Members
- Requirements
- Test Suites
- Test Cases (with Steps and Versions)
- Test Plans
- Test Runs
- Test Execution
- Defects
- Environments
- Attachments
- Comments
- Activity Log
- Traceability
- Reports
- Dashboard
- Automation Ingestion (foundation)
- AI Suggestion (foundation)

## 12. Roles & Permissions

| Capability                       | Admin | QA Lead | QA Engineer | Developer | Viewer |
|----------------------------------|:-----:|:-------:|:-----------:|:---------:|:------:|
| Manage users                     |   ✔   |         |             |           |        |
| Create/edit projects             |   ✔   |    ✔    |             |           |        |
| Manage project members           |   ✔   |    ✔    |             |           |        |
| CRUD requirements                |   ✔   |    ✔    |             |           |        |
| CRUD test suites / cases / plans |   ✔   |    ✔    |             |           |        |
| Create test runs                 |   ✔   |    ✔    |             |           |        |
| Execute tests                    |   ✔   |    ✔    |      ✔      |           |        |
| Create defects                   |   ✔   |    ✔    |      ✔      |           |        |
| Update defect status             |   ✔   |    ✔    |      ✔      |     ✔     |        |
| Comment on defects               |   ✔   |    ✔    |      ✔      |     ✔     |        |
| View dashboards / reports        |   ✔   |    ✔    |      ✔      |     ✔     |   ✔    |
| View audit log                   |   ✔   |    ✔    |             |           |        |

Permissions are enforced server-side.

## 13. Acceptance Criteria (selected)

- Login returns a token and user profile. Wrong credentials return 401 with a generic message.
- Creating a test case persists its steps in order and returns the full entity.
- Updating a test case writes a version snapshot of the previous state.
- Creating a test run copies selected case references and environments into `test_run_cases`; it does not duplicate case definitions.
- Executing a case writes `test_results` and per-step `test_step_results`.
- Creating a defect from a failed execution links to the execution, test case, requirement, and environment.
- Traceability endpoint returns per-requirement counts consistent with the underlying execution data.
- Dashboard metrics equal the corresponding aggregates in the database.
- Every list endpoint is paginated and filterable server-side.

## 14. MVP Scope

Authentication, Projects, Requirements, Test Suites, Test Cases, Test Plans, Test Runs, Test Execution, Defects, Environments, Comments, Attachments, Activity Log, Traceability, Reports, Dashboard.

## 15. Future Scope

- Automation ingestion UI and JUnit parser.
- AI test case suggestion service.
- Notifications (email and in-app).
- Rich diff viewer for version history.
- Advanced analytics (heatmaps, flakiness detection).
- Mobile-optimized execution mode.

## 16. Constraints

- PHP 8.1+ with PDO MySQL.
- MySQL 8.0+.
- Node 18+ for the frontend build.
- No external SaaS dependencies in the MVP.
- Uploads stored on the local filesystem with a documented path. Object storage is a future option.

## 17. Success Metrics

- Requirement coverage visibility: 100% of projects report coverage without manual bookkeeping.
- Execution time per test case under 20 seconds including evidence upload.
- Zero plaintext passwords or secrets in the repository.
- All dashboards and reports reproducible from raw SQL against the same database.