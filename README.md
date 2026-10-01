# qaflow

# QAFlow

QAFlow is an SQA / Test Management platform. It models the QA lifecycle from requirements through defects, with traceability and reporting derived from real execution data.

## Stack

- Frontend: React 18, Vite, Tailwind CSS, Emotion, Axios, React Router, Recharts, GSAP.
- Backend: PHP 8.1+, PDO, MySQL 8, custom router and DI container.
- Database: MySQL 8 (InnoDB, utf8mb4).

## Repository Layout

```
qaflow/
├── prd.md
├── architecture.md
├── rules.md
├── design.md
├── tasks.md
├── memory.md
├── README.md
├── .gitignore
├── database/
│   ├── schema.sql
│   ├── seed.sql
│   └── README.md
├── backend/
│   ├── public/
│   ├── config/
│   ├── core/
│   ├── middleware/
│   ├── controllers/
│   ├── services/
│   ├── repositories/
│   ├── models/
│   ├── validators/
│   ├── helpers/
│   ├── routes/
│   └── storage/
└── frontend/
    ├── public/
    └── src/
```

## Prerequisites

- PHP 8.1 or newer with `pdo_mysql`, `fileinfo`, `json`, `mbstring` extensions.
- MySQL 8.0 or newer.
- Node.js 18 or newer, npm 9 or newer.
- Apache or nginx for production; the built-in PHP server works for local development.

## Setup

### 1. Database

```
mysql -u root -p < database/schema.sql
mysql -u root -p qaflow < database/seed.sql
```

The seed creates a `qaflow` database, six users, three projects, requirements, suites, cases, a plan, a run, and results. All seed users share the password `Password123!`.

### 2. Backend

```
cd backend
cp .env.example .env
# edit .env with your DB credentials and CORS origin
php -S localhost:8000 -t public
```

The API is served under `http://localhost:8000/api`.

### 3. Frontend

```
cd frontend
cp .env.example .env
npm install
npm run dev
```

The dev server runs at `http://localhost:5173` and proxies `/api` to the backend.

## Default Accounts

| Email                 | Role        | Password       |
|-----------------------|-------------|----------------|
| admin@qaflow.local    | admin       | Password123!   |
| hassan@qaflow.local   | qa_lead     | Password123!   |
| ahmed@qaflow.local    | qa_engineer | Password123!   |
| sara@qaflow.local     | developer   | Password123!   |
| bilal@qaflow.local    | qa_engineer | Password123!   |
| nadia@qaflow.local    | viewer      | Password123!   |

Change these credentials before any non-local deployment.

## Documentation

The root markdown files are part of the product:

- `prd.md` — product requirements and scope.
- `architecture.md` — system blueprint, API map, data model, security model.
- `rules.md` — engineering rules.
- `design.md` — UI and design system.
- `tasks.md` — roadmap by phase.
- `memory.md` — current project state for future contributors.

## Production Build

```
cd frontend
npm run build
```

Serve `frontend/dist` statically and route `/api` to the PHP backend (`backend/public/index.php`).

## Security Notes

- Passwords are hashed with bcrypt.
- All SQL uses prepared statements.
- Uploads are stored outside the web root and validated by MIME and size.
- CORS is restricted to the configured origin.
- Login is rate-limited.
- `.env` is gitignored; never commit credentials.