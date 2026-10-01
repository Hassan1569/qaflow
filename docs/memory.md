# QAFlow — Project Memory

This file tracks the current state of the project so a future contributor or assistant can resume work without losing context. Update it whenever architecture, scope, or implementation state changes.

## 1. Project Identity

- Name: QAFlow
- Purpose: SQA / Test Management platform for organizing requirements, test cases, plans, runs, executions, defects, traceability, and reporting.
- Repository root: `qaflow/`
- Primary audience: internal QA teams.

## 2. Technology Stack

Backend:
- PHP 8.1+ (no framework), PDO, MySQL 8.
- Custom Router, Container, and exception handler.
- Opaque bearer tokens stored in `auth_tokens`.

Frontend:
- React 18, Vite, JavaScript (no TypeScript).
- Tailwind CSS for layout and tokens.
- Emotion for a small set of dynamic components.
- Axios for HTTP, React Router v6, Recharts for charts, GSAP for subtle entrance animations.

Database:
- MySQL 8, InnoDB, `utf8mb4_unicode_ci`.

## 3. Current Architecture Summary

- Layered backend: Controller → Service → Repository → PDO.
- Stateless REST API under `/api`.
- SPA frontend served from `frontend/dist`.
- Uploads stored on the local filesystem under `backend/storage/uploads/{yyyy}/{mm}`.
- Activity logging is centralized in `ActivityService`, called by mutating services.

## 4. Database Decisions

- Test cases are defined once; test runs reference them via `test_run_cases`.
- `test_results` is 1:1 with `test_run_cases`.
- `test_step_results` is 1:N per `test_result`, one row per step.
- Requirement ↔ Test Case is many-to-many via `test_case_requirements`.
- `severity` and `priority` on `defects` are distinct columns.
- Attachments and comments use polymorphic (`entity_type`, `entity_id`) references with indexes.
- Enum-like fields use `VARCHAR(32)` and app-level constants; no MySQL `ENUM`.
- Snapshots for version history are stored as JSON in `*_versions.snapshot`.

## 5. Important Relationships

- Requirement → Test Cases → Test Runs → Results → Defects.
- Project → Members → Roles (per project).
- Test Plan → Test Runs.
- Environment → Test Results and Defects.
- Users → Activity Logs, Comments, Attachments.

## 6. UI Decisions

- Flat design; no gradients anywhere.
- Semantic color tokens; status colors are consistent across the app.
- Inter is the primary font, with a system fallback chain.
- Sidebar + navbar shell; content is full-width for enterprise density.
- Every list uses `DataTable`; every status uses `StatusBadge`; every priority uses `PriorityBadge`.
- Dark mode is class-based and shares tokens.

## 7. Security Decisions

- `password_hash` / `password_verify` with bcrypt.
- All SQL parameterized via PDO with `ATTR_EMULATE_PREPARES = false`.
- Uploads validated by MIME (`finfo_file`) and size (10 MB cap). Filenames regenerated.
- CORS origin from `.env`.
- Login rate limited (10/min/IP) via `login_attempts`.
- No secrets in the repo; `.env` only.

## 8. Completed Modules

- Documentation set (`prd.md`, `architecture.md`, `rules.md`, `design.md`, `tasks.md`, `memory.md`).
- Database schema (`database/schema.sql`).
- Seed data (`database/seed.sql`).

## 9. Pending Modules

- All implementation files listed in Phase 1 scaffold are to be filled in.
- Priority order follows `tasks.md` Phase 0 through Phase 20.

## 10. Known Limitations

- No CI pipeline yet.
- No automated backend test suite yet.
- PDF export is handled via browser print; no server-side PDF generator.
- Uploads are stored on local disk; object storage is a future option.
- AI suggestion service is a template stub; no external provider wired.

## 11. Future Decisions

- Whether to introduce Composer for PSR-4 autoloading or keep the custom loader.
- Whether to add soft delete on projects and test cases.
- Whether to introduce a notifications module (email or in-app).
- Whether to add JUnit XML parsing to the automation ingestion endpoint.
- Whether to move uploads behind signed URLs.

## 12. Important Assumptions

- The user base is trusted internal staff; social features are unnecessary.
- Test case definitions change less frequently than runs; version snapshots are enough without a diff UI in MVP.
- Roles are coarse; no per-field permissions.
- Environments are project-scoped.

## 13. Change History

- Initial documentation and schema created.
- Design system codified with strict no-gradient rule.
- Roadmap split into 21 phases with acceptance criteria.