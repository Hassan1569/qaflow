-- QAFlow — MySQL Schema
-- Requires MySQL 8.0+
-- Charset: utf8mb4 / utf8mb4_unicode_ci

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- USERS & AUTH
-- ---------------------------------------------------------------------------

CREATE TABLE users (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name            VARCHAR(120) NOT NULL,
  email           VARCHAR(160) NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,
  role            VARCHAR(32) NOT NULL DEFAULT 'viewer',
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE auth_tokens (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         BIGINT UNSIGNED NOT NULL,
  token           CHAR(64) NOT NULL,
  expires_at      DATETIME NOT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_auth_tokens_token (token),
  KEY idx_auth_tokens_user (user_id),
  KEY idx_auth_tokens_expires (expires_at),
  CONSTRAINT fk_auth_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE api_tokens (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id      BIGINT UNSIGNED NOT NULL,
  name            VARCHAR(120) NOT NULL,
  token           CHAR(64) NOT NULL,
  created_by      BIGINT UNSIGNED NOT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_api_tokens_token (token),
  KEY idx_api_tokens_project (project_id),
  CONSTRAINT fk_api_tokens_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_api_tokens_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ip              VARCHAR(64) NOT NULL,
  email           VARCHAR(160) NOT NULL,
  attempted_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_login_attempts_ip_time (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- PROJECTS & MEMBERS
-- ---------------------------------------------------------------------------

CREATE TABLE projects (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  key_code        VARCHAR(16) NOT NULL,
  name            VARCHAR(160) NOT NULL,
  description     TEXT NULL,
  status          VARCHAR(24) NOT NULL DEFAULT 'active',
  owner_id        BIGINT UNSIGNED NOT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_projects_key (key_code),
  KEY idx_projects_owner (owner_id),
  KEY idx_projects_status (status),
  CONSTRAINT fk_projects_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE project_members (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id      BIGINT UNSIGNED NOT NULL,
  user_id         BIGINT UNSIGNED NOT NULL,
  role            VARCHAR(32) NOT NULL DEFAULT 'qa_engineer',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_project_members (project_id, user_id),
  KEY idx_project_members_user (user_id),
  CONSTRAINT fk_project_members_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_project_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- ENVIRONMENTS
-- ---------------------------------------------------------------------------

CREATE TABLE environments (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id      BIGINT UNSIGNED NOT NULL,
  name            VARCHAR(80) NOT NULL,
  browser         VARCHAR(60) NULL,
  browser_version VARCHAR(40) NULL,
  os              VARCHAR(60) NULL,
  os_version      VARCHAR(40) NULL,
  device          VARCHAR(60) NULL,
  device_version  VARCHAR(40) NULL,
  app_version     VARCHAR(60) NULL,
  notes           TEXT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_environments_project (project_id),
  CONSTRAINT fk_environments_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- REQUIREMENTS
-- ---------------------------------------------------------------------------

CREATE TABLE requirements (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id      BIGINT UNSIGNED NOT NULL,
  code            VARCHAR(32) NOT NULL,
  title           VARCHAR(200) NOT NULL,
  description     TEXT NULL,
  priority        VARCHAR(16) NOT NULL DEFAULT 'medium',
  status          VARCHAR(24) NOT NULL DEFAULT 'draft',
  author_id       BIGINT UNSIGNED NOT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_requirements_project_code (project_id, code),
  KEY idx_requirements_project (project_id),
  KEY idx_requirements_status (status),
  KEY idx_requirements_priority (priority),
  CONSTRAINT fk_requirements_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_requirements_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE requirement_versions (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  requirement_id  BIGINT UNSIGNED NOT NULL,
  version_no      INT UNSIGNED NOT NULL,
  snapshot        JSON NOT NULL,
  changed_by      BIGINT UNSIGNED NOT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_requirement_versions (requirement_id, version_no),
  KEY idx_requirement_versions_req (requirement_id),
  CONSTRAINT fk_requirement_versions_req FOREIGN KEY (requirement_id) REFERENCES requirements(id) ON DELETE CASCADE,
  CONSTRAINT fk_requirement_versions_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- TEST SUITES
-- ---------------------------------------------------------------------------

CREATE TABLE test_suites (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id      BIGINT UNSIGNED NOT NULL,
  parent_id       BIGINT UNSIGNED NULL,
  name            VARCHAR(160) NOT NULL,
  description     TEXT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_test_suites_project (project_id),
  KEY idx_test_suites_parent (parent_id),
  CONSTRAINT fk_test_suites_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_test_suites_parent FOREIGN KEY (parent_id) REFERENCES test_suites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- TEST CASES
-- ---------------------------------------------------------------------------

CREATE TABLE test_cases (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id      BIGINT UNSIGNED NOT NULL,
  suite_id        BIGINT UNSIGNED NULL,
  code            VARCHAR(32) NOT NULL,
  title           VARCHAR(220) NOT NULL,
  description     TEXT NULL,
  preconditions   TEXT NULL,
  priority        VARCHAR(16) NOT NULL DEFAULT 'medium',
  severity        VARCHAR(16) NOT NULL DEFAULT 'medium',
  test_type       VARCHAR(24) NOT NULL DEFAULT 'functional',
  component       VARCHAR(80) NULL,
  tags            VARCHAR(255) NULL,
  automation_status VARCHAR(16) NOT NULL DEFAULT 'manual',
  external_id     VARCHAR(120) NULL,
  author_id       BIGINT UNSIGNED NOT NULL,
  current_version INT UNSIGNED NOT NULL DEFAULT 1,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_test_cases_project_code (project_id, code),
  KEY idx_test_cases_project (project_id),
  KEY idx_test_cases_suite (suite_id),
  KEY idx_test_cases_priority (priority),
  KEY idx_test_cases_type (test_type),
  KEY idx_test_cases_automation (automation_status),
  KEY idx_test_cases_external (external_id),
  CONSTRAINT fk_test_cases_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_test_cases_suite FOREIGN KEY (suite_id) REFERENCES test_suites(id) ON DELETE SET NULL,
  CONSTRAINT fk_test_cases_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE test_steps (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  test_case_id    BIGINT UNSIGNED NOT NULL,
  step_order      INT UNSIGNED NOT NULL,
  action          TEXT NOT NULL,
  expected_result TEXT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_test_steps_case_order (test_case_id, step_order),
  CONSTRAINT fk_test_steps_case FOREIGN KEY (test_case_id) REFERENCES test_cases(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE test_case_versions (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  test_case_id    BIGINT UNSIGNED NOT NULL,
  version_no      INT UNSIGNED NOT NULL,
  snapshot        JSON NOT NULL,
  changed_fields  VARCHAR(500) NULL,
  changed_by      BIGINT UNSIGNED NOT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_test_case_versions (test_case_id, version_no),
  KEY idx_test_case_versions_case (test_case_id),
  CONSTRAINT fk_test_case_versions_case FOREIGN KEY (test_case_id) REFERENCES test_cases(id) ON DELETE CASCADE,
  CONSTRAINT fk_test_case_versions_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE test_case_requirements (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  test_case_id    BIGINT UNSIGNED NOT NULL,
  requirement_id  BIGINT UNSIGNED NOT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tcr (test_case_id, requirement_id),
  KEY idx_tcr_requirement (requirement_id),
  CONSTRAINT fk_tcr_case FOREIGN KEY (test_case_id) REFERENCES test_cases(id) ON DELETE CASCADE,
  CONSTRAINT fk_tcr_requirement FOREIGN KEY (requirement_id) REFERENCES requirements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- TEST PLANS
-- ---------------------------------------------------------------------------

CREATE TABLE test_plans (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id      BIGINT UNSIGNED NOT NULL,
  name            VARCHAR(180) NOT NULL,
  release_version VARCHAR(60) NULL,
  description     TEXT NULL,
  scope           TEXT NULL,
  status          VARCHAR(24) NOT NULL DEFAULT 'draft',
  start_date      DATE NULL,
  end_date        DATE NULL,
  owner_id        BIGINT UNSIGNED NOT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_test_plans_project (project_id),
  KEY idx_test_plans_status (status),
  CONSTRAINT fk_test_plans_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_test_plans_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- TEST RUNS
-- ---------------------------------------------------------------------------

CREATE TABLE test_runs (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id      BIGINT UNSIGNED NOT NULL,
  plan_id         BIGINT UNSIGNED NULL,
  environment_id  BIGINT UNSIGNED NULL,
  name            VARCHAR(180) NOT NULL,
  description     TEXT NULL,
  status          VARCHAR(24) NOT NULL DEFAULT 'not_started',
  assigned_to     BIGINT UNSIGNED NULL,
  created_by      BIGINT UNSIGNED NOT NULL,
  started_at      DATETIME NULL,
  completed_at    DATETIME NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_test_runs_project (project_id),
  KEY idx_test_runs_plan (plan_id),
  KEY idx_test_runs_status (status),
  KEY idx_test_runs_assigned (assigned_to),
  CONSTRAINT fk_test_runs_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_test_runs_plan FOREIGN KEY (plan_id) REFERENCES test_plans(id) ON DELETE SET NULL,
  CONSTRAINT fk_test_runs_env FOREIGN KEY (environment_id) REFERENCES environments(id) ON DELETE SET NULL,
  CONSTRAINT fk_test_runs_assigned FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_test_runs_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE test_run_cases (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  test_run_id     BIGINT UNSIGNED NOT NULL,
  test_case_id    BIGINT UNSIGNED NOT NULL,
  environment_id  BIGINT UNSIGNED NULL,
  assignee_id     BIGINT UNSIGNED NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_test_run_cases (test_run_id, test_case_id),
  KEY idx_trc_case (test_case_id),
  KEY idx_trc_assignee (assignee_id),
  CONSTRAINT fk_trc_run FOREIGN KEY (test_run_id) REFERENCES test_runs(id) ON DELETE CASCADE,
  CONSTRAINT fk_trc_case FOREIGN KEY (test_case_id) REFERENCES test_cases(id) ON DELETE CASCADE,
  CONSTRAINT fk_trc_env FOREIGN KEY (environment_id) REFERENCES environments(id) ON DELETE SET NULL,
  CONSTRAINT fk_trc_assignee FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- TEST RESULTS & STEP RESULTS
-- ---------------------------------------------------------------------------

CREATE TABLE test_results (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  test_run_case_id BIGINT UNSIGNED NOT NULL,
  status          VARCHAR(16) NOT NULL DEFAULT 'not_run',
  actual_result   TEXT NULL,
  notes           TEXT NULL,
  executed_by     BIGINT UNSIGNED NULL,
  is_automated    TINYINT(1) NOT NULL DEFAULT 0,
  duration_ms     INT UNSIGNED NULL,
  executed_at     DATETIME NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_test_results_trc (test_run_case_id),
  KEY idx_test_results_status (status),
  CONSTRAINT fk_test_results_trc FOREIGN KEY (test_run_case_id) REFERENCES test_run_cases(id) ON DELETE CASCADE,
  CONSTRAINT fk_test_results_user FOREIGN KEY (executed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE test_step_results (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  test_result_id  BIGINT UNSIGNED NOT NULL,
  step_order      INT UNSIGNED NOT NULL,
  status          VARCHAR(16) NOT NULL DEFAULT 'not_run',
  actual_result   TEXT NULL,
  notes           TEXT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_test_step_results (test_result_id, step_order),
  CONSTRAINT fk_test_step_results_result FOREIGN KEY (test_result_id) REFERENCES test_results(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- DEFECTS
-- ---------------------------------------------------------------------------

CREATE TABLE defects (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id      BIGINT UNSIGNED NOT NULL,
  code            VARCHAR(32) NOT NULL,
  title           VARCHAR(220) NOT NULL,
  description     TEXT NULL,
  expected_result TEXT NULL,
  actual_result   TEXT NULL,
  severity        VARCHAR(16) NOT NULL DEFAULT 'medium',
  priority        VARCHAR(16) NOT NULL DEFAULT 'medium',
  status          VARCHAR(24) NOT NULL DEFAULT 'open',
  test_case_id    BIGINT UNSIGNED NULL,
  requirement_id  BIGINT UNSIGNED NULL,
  test_result_id  BIGINT UNSIGNED NULL,
  environment_id  BIGINT UNSIGNED NULL,
  reporter_id     BIGINT UNSIGNED NOT NULL,
  assignee_id     BIGINT UNSIGNED NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_defects_project_code (project_id, code),
  KEY idx_defects_project (project_id),
  KEY idx_defects_status (status),
  KEY idx_defects_severity (severity),
  KEY idx_defects_priority (priority),
  KEY idx_defects_assignee (assignee_id),
  KEY idx_defects_case (test_case_id),
  KEY idx_defects_req (requirement_id),
  CONSTRAINT fk_defects_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_defects_case FOREIGN KEY (test_case_id) REFERENCES test_cases(id) ON DELETE SET NULL,
  CONSTRAINT fk_defects_req FOREIGN KEY (requirement_id) REFERENCES requirements(id) ON DELETE SET NULL,
  CONSTRAINT fk_defects_result FOREIGN KEY (test_result_id) REFERENCES test_results(id) ON DELETE SET NULL,
  CONSTRAINT fk_defects_env FOREIGN KEY (environment_id) REFERENCES environments(id) ON DELETE SET NULL,
  CONSTRAINT fk_defects_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_defects_assignee FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- ATTACHMENTS & COMMENTS (polymorphic)
-- ---------------------------------------------------------------------------

CREATE TABLE attachments (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entity_type     VARCHAR(40) NOT NULL,
  entity_id       BIGINT UNSIGNED NOT NULL,
  original_name   VARCHAR(255) NOT NULL,
  stored_path     VARCHAR(400) NOT NULL,
  mime_type       VARCHAR(120) NOT NULL,
  size_bytes      BIGINT UNSIGNED NOT NULL,
  uploaded_by     BIGINT UNSIGNED NOT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_attachments_entity (entity_type, entity_id),
  CONSTRAINT fk_attachments_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE comments (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entity_type     VARCHAR(40) NOT NULL,
  entity_id       BIGINT UNSIGNED NOT NULL,
  content         TEXT NOT NULL,
  author_id       BIGINT UNSIGNED NOT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_comments_entity (entity_type, entity_id),
  CONSTRAINT fk_comments_user FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- ACTIVITY LOG
-- ---------------------------------------------------------------------------

CREATE TABLE activity_logs (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         BIGINT UNSIGNED NULL,
  project_id      BIGINT UNSIGNED NULL,
  action          VARCHAR(60) NOT NULL,
  entity_type     VARCHAR(40) NOT NULL,
  entity_id       BIGINT UNSIGNED NULL,
  metadata        JSON NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_activity_user (user_id),
  KEY idx_activity_project (project_id),
  KEY idx_activity_entity (entity_type, entity_id),
  KEY idx_activity_created (created_at),
  CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_activity_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;