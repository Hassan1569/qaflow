# QAFlow — Engineering Rules

Rules are enforceable. If a rule cannot be verified in code review, it is not a rule.

## 1. Naming Conventions

- Files: `PascalCase.php` for classes; `camelCase.js` for services/utils; `PascalCase.jsx` for components and pages.
- PHP classes: `PascalCase`. Methods: `camelCase`. Constants: `UPPER_SNAKE`.
- Database tables: `snake_case`, plural. Columns: `snake_case`, singular.
- Frontend variables and functions: `camelCase`. Components: `PascalCase`.
- Booleans prefixed with `is`, `has`, `can`, or `should`.
- IDs: `{entity}_id` (e.g., `project_id`). Foreign keys always named after the referenced table.

## 2. Folder Organization

- Backend: `config`, `core`, `middleware`, `controllers`, `services`, `repositories`, `models`, `validators`, `helpers`, `routes`, `public`, `storage`.
- Frontend: `components`, `pages`, `services`, `context`, `hooks`, `utils`, `routes`, `styles`, `assets`.
- No new top-level folders without updating `architecture.md`.

## 3. React Component Design

- One component per file, default export.
- Props destructured in the signature.
- Components under 200 lines. Extract subcomponents if larger.
- Presentational components receive data via props; container logic lives in pages or hooks.
- No side effects in render. Effects only in `useEffect` with explicit dependencies.
- All interactive elements keyboard accessible.

## 4. Hooks

- Custom hooks live in `src/hooks/` and start with `use`.
- `useApi` handles loading/error/data for a single call.
- No hook may fetch data on every render. Debounce search inputs with `useDebounce`.

## 5. State Management

- Server state is fetched on mount and after mutations; do not duplicate it in a global store.
- Context is only for cross-cutting concerns: auth, theme, toasts.
- No prop drilling beyond two levels; introduce composition or context instead.

## 6. API Services (Frontend)

- Every backend endpoint has exactly one function in `src/services/*`.
- Services return `response.data.data` or throw a normalized error.
- No component calls axios directly; always through a service.
- Query params are built from a single object, never string-concatenated inline.

## 7. PHP Conventions

- Strict types declared in every file: `declare(strict_types=1);`.
- PSR-12 formatting.
- No global state. Use the `Container` for dependency resolution.
- Controllers return via `Response::json()`; never `echo` or `print`.

## 8. SQL Conventions

- Every query uses prepared statements with bound parameters.
- No `SELECT *` in application code; enumerate columns.
- Joins use explicit `INNER JOIN` / `LEFT JOIN`, not comma joins.
- Aggregates use `COUNT(DISTINCT ...)` where appropriate to avoid fan-out inflation.

## 9. Database Naming

- Join tables named `{table_a}_{table_b}` in alphabetical order (e.g., `test_case_requirements`).
- Timestamps: `created_at`, `updated_at` on every mutable table. `deleted_at` only where soft delete is intentional.
- Enum-like fields use `VARCHAR(32)` with an app-level enum, not MySQL `ENUM`, to keep migrations simple.

## 10. Validation

- Backend validation is authoritative. Frontend validation is UX only.
- Validators return `{ field: [messages] }` maps.
- No business logic in validators; only shape and range checks.

## 11. Security

- Passwords: `password_hash($p, PASSWORD_BCRYPT)` and `password_verify`.
- No secret in code, tests, or docs. `.env` only.
- Uploads: MIME check via `finfo_file`, size cap 10 MB, filename regenerated.
- All SQL parameterized.
- CORS origin from `.env`, not wildcard.
- Rate limit login and automation ingestion.
- Never return stack traces or SQL errors to clients.

## 12. Authentication

- Every protected route passes through `AuthMiddleware`.
- Tokens expire per `TOKEN_TTL_HOURS`.
- Logout deletes the token row.
- No "remember me" in MVP.

## 13. Authorization

- Every mutating route declares required roles in the route table.
- Services additionally verify project membership for project-scoped actions.
- Frontend hiding is not security.

## 14. Error Handling

- Backend uses `AppException` subclasses with explicit HTTP codes.
- Frontend surfaces errors through toasts and `ErrorState`; 401 triggers logout.
- No `catch (Exception $e) {}` swallowing errors.

## 15. UI Consistency

- All statuses render via `StatusBadge`; all priorities via `PriorityBadge`.
- All page headers use `PageHeader`.
- All lists use `DataTable` with pagination.
- All destructive actions use `ConfirmationDialog`.
- No inline hex colors. Use Tailwind tokens from `tailwind.config.js` and `src/styles/tokens.js`.

## 16. Accessibility

- Semantic HTML: `button`, `nav`, `main`, `header`, `table`.
- Every form field has a `<label>` or `aria-label`.
- Focus ring visible on all interactive elements.
- Modals trap focus and close on Escape.
- Status is not conveyed by color alone; badges include text.

## 17. Responsive Behavior

- Desktop-first. Sidebar collapses below `lg`.
- Tables remain usable on `md` via horizontal scroll with sticky first column.
- Forms use a single column below `md`.

## 18. Git Practices

- `main` is deployable.
- Feature branches: `feat/`, `fix/`, `chore/`.
- Commit messages: imperative mood, under 72 chars subject.
- No secrets in commits. `.env` is gitignored.

## 19. Documentation

- Any architectural change updates `architecture.md`.
- Any new rule updates this file.
- Any new module updates `tasks.md` and `memory.md`.
- The six root docs are part of the product.

## 20. Dependency Management

- Backend: no Composer dependency in MVP except a PSR-4 autoloader we ship ourselves; if Composer is added later, dependencies must be justified in `architecture.md`.
- Frontend: any new dependency over 50 KB gzipped must be justified in the PR description.
- No dependency is added for something implementable in under 50 lines.

## 21. Code Quality

- No `TODO` in committed code. Open an issue instead.
- No commented-out code blocks.
- No dead code. Delete it; Git remembers.
- Functions under 60 lines where practical.

## 22. Reusability

- Three similar lines is fine. Three similar components is a bug.
- Shared logic goes in `hooks/` (frontend) or `services/` (backend).
- Duplicate SQL is a bug; extract to a repository method.

## 23. Performance

- List endpoints paginate. Default 25, max 100.
- Index every foreign key and every column used in `WHERE` on list endpoints.
- Avoid N+1: repositories expose joined reads for lists.
- Frontend: `React.lazy` for route-level code splitting.
- Search inputs debounced 300 ms.