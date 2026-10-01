# QAFlow — Backend

PHP 8.1+ REST API for QAFlow. Uses PDO with MySQL 8, a small custom router, and layered separation between controllers, services, and repositories.

## Requirements

- PHP 8.1 or newer.
- Extensions: `pdo`, `pdo_mysql`, `json`, `mbstring`, `fileinfo`.
- MySQL 8.0 or newer.
- Optional: Composer (only used for PSR-4 autoloading; the project also ships a fallback loader).

## Layout

```
backend/
├── public/            Web root. `index.php` is the single entry point.
├── config/            Configuration files (config, database, cors).
├── core/              Router, Container, AppException, Handler.
├── middleware/        Auth, Role, CORS, RateLimit.
├── controllers/       HTTP boundary. One file per resource.
├── services/          Business rules.
├── repositories/      PDO queries. No business logic.
├── models/            Lightweight entity wrappers.
├── validators/        Field-level validation.
├── helpers/           Response, Request, Logger, Sanitizer, Uuid.
├── routes/            Route table.
├── scripts/           One-off CLI scripts (user seeding, maintenance).
└── storage/           Uploads and logs. Not web-accessible.
```

## Setup

1. Copy `.env.example` to `.env` and fill in the values:

   ```
   cp .env.example .env
   ```

2. Load the schema and seed data. From the repository root:

   ```
   mysql -u root -p -e "CREATE DATABASE qaflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p qaflow < database/schema.sql
   mysql -u root -p qaflow < database/seed.sql
   ```

3. Seed user passwords. The SQL seed inserts users with an empty `password_hash`. Run the seeder once to set every account to `Password123!`:

   ```
   php scripts/seed-users.php
   ```

4. Start the dev server:

   ```
   php -S localhost:8000 -t public
   ```

The API is served under `http://localhost:8000/api`.

## Directory Notes

- `public/` is the only directory that should be exposed to the web. Everything else sits above it.
- `storage/uploads/` and `storage/logs/` must be writable by the PHP user.
- `storage/` must be outside the web root. Do not symlink it into `public/`.
- Logs are written to `storage/logs/app.log` with rotation handled by the operating system or logrotate.

## Authentication Flow

1. `POST /api/auth/login` with `email` and `password`.
2. Server verifies with `password_verify` and returns a bearer token.
3. Clients send `Authorization: Bearer <token>` on every subsequent request.
4. `POST /api/auth/logout` deletes the token row.
5. Tokens expire after `TOKEN_TTL_HOURS` (default 24).

## Error Format

All errors return a consistent JSON envelope:

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid input",
    "details": { "email": ["Required"] }
  }
}
```

Status codes used: 200, 201, 204, 400, 401, 403, 404, 409, 422, 429, 500.

## Production Notes

- Point the web server at `backend/public/`. Rewrite all non-file requests to `index.php`.
- Set `APP_DEBUG=false` in production. Stack traces are suppressed.
- Restrict CORS to the deployed frontend origin via `CORS_ORIGIN`.
- Ensure `.env` is not readable by the web server user beyond PHP.
- Serve the frontend build separately and route `/api` to this backend.

## Reset / Re-seed

To reset the database and reload everything:

```
mysql -u root -p -e "DROP DATABASE IF EXISTS qaflow; CREATE DATABASE qaflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p qaflow < ../database/schema.sql
mysql -u root -p qaflow < ../database/seed.sql
php scripts/seed-users.php
```

The seed truncates every table, so it can be run repeatedly during development.