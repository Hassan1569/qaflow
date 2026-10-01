# QAFlow — Database

The QAFlow schema is defined entirely in `schema.sql`. The `seed.sql` file loads a realistic demo dataset that exercises every major relationship in the product.

## Requirements

- MySQL 8.0 or newer.
- InnoDB engine.
- Charset `utf8mb4` with collation `utf8mb4_unicode_ci`.

## Files

- `schema.sql` — DDL for every table. Idempotent only when the target database is empty; it does not `DROP TABLE`.
- `seed.sql` — truncates the tables in dependency-safe order and inserts demo data. It is safe to re-run.

## Applying the Schema

Create the database and load the schema:

```
mysql -u root -p -e "CREATE DATABASE qaflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p qaflow < schema.sql
```

Load seed data:

```
mysql -u root -p qaflow < seed.sql
```

Reset the database and reload everything:

```
mysql -u root -p -e "DROP DATABASE IF EXISTS qaflow; CREATE DATABASE qaflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p qaflow < schema.sql
mysql -u root -p qaflow < seed.sql
```

## Table Overview

Identity and access:
- `users`
- `auth_tokens`
- `api_tokens`
- `login_attempts`

Project scope:
- `projects`
- `project_members`
- `environments`

Requirements:
- `requirements`
- `requirement_versions`
- `test_case_requirements`

Test repository:
- `test_suites`
- `test_cases`
- `test_steps`
- `test_case_versions`

Planning and execution:
- `test_plans`
- `test_runs`
- `test_run_cases`
- `test_results`
- `test_step_results`

Defects and collaboration:
- `defects`
- `attachments`
- `comments`

Audit:
- `activity_logs`

## Design Notes

- Every foreign key is indexed.
- Test cases are defined once. Test runs reference them through `test_run_cases` and store execution-specific state in `test_results`.
- Version history for requirements and test cases is stored as JSON snapshots in `*_versions` tables.
- `severity` and `priority` are distinct columns on `defects`.
- Attachments and comments use polymorphic references (`entity_type`, `entity_id`) with a composite index for lookup.
- Enum-like fields use `VARCHAR(32)` with app-level constants; MySQL `ENUM` is intentionally avoided to keep migrations simple.
- Timestamps (`created_at`, `updated_at`) exist on every mutable table.
- `activity_logs` records key mutations with actor, entity type, entity id, action, and metadata.

## Seed Data

The seed demonstrates the full lifecycle:

- Project: `Banking Portal` (BANK), `E-commerce Website` (SHOP), `Mobile Banking App` (MOB).
- Users: one per role, sharing the password `Password123!`.
- Requirements: `REQ-001` through `REQ-008` on the Banking Portal, plus two on the E-commerce project.
- Test Suites: hierarchical under `Authentication`, `Accounts`, `Transactions`.
- Test Cases: `TC-001` through several dozen covering login, logout, password reset, transfers, bill payment, history, beneficiaries, plus a handful of E-commerce cases.
- Environments: `Chrome / Windows`, `Firefox / Windows`, `Edge / Windows`, `Android`, `iOS`, plus staging variants.
- Test Plans: one for the `Banking Portal v2.5` release.
- Test Runs: one Regression run seeded with results across all four statuses.
- Defects: several with a mix of severity, priority, and status, all linked to a test case, a requirement, and an execution.
- Attachments and comments: none preloaded; they are created through the app.

The dashboard and reports are derived entirely from this dataset. No metric is hard-coded.

## Reapplying Seed Data

`seed.sql` begins with `SET FOREIGN_KEY_CHECKS = 0;` and truncates every table, so it can be re-run safely at any point. In production, do not run the seed file.