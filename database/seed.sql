-- QAFlow — Seed Data
-- Demonstrates relationships, traceability, and dashboard calculations.
--
-- NOTE ON PASSWORDS:
--   The user rows below insert an EMPTY password_hash on purpose.
--   Run `php backend/scripts/seed-users.php` once after loading this file
--   to set every seeded account's password to "Password123!" using bcrypt.
--   The application cannot authenticate until that script has been run.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE activity_logs;
TRUNCATE TABLE comments;
TRUNCATE TABLE attachments;
TRUNCATE TABLE defects;
TRUNCATE TABLE test_step_results;
TRUNCATE TABLE test_results;
TRUNCATE TABLE test_run_cases;
TRUNCATE TABLE test_runs;
TRUNCATE TABLE test_plans;
TRUNCATE TABLE test_case_requirements;
TRUNCATE TABLE test_case_versions;
TRUNCATE TABLE test_steps;
TRUNCATE TABLE test_cases;
TRUNCATE TABLE test_suites;
TRUNCATE TABLE requirement_versions;
TRUNCATE TABLE requirements;
TRUNCATE TABLE environments;
TRUNCATE TABLE project_members;
TRUNCATE TABLE api_tokens;
TRUNCATE TABLE auth_tokens;
TRUNCATE TABLE login_attempts;
TRUNCATE TABLE projects;
TRUNCATE TABLE users;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- USERS  (password_hash is empty; run backend/scripts/seed-users.php next)
-- ---------------------------------------------------------------------------
INSERT INTO users (id, name, email, password_hash, role, is_active) VALUES
  (1, 'Admin User',    'admin@qaflow.local',  '', 'admin',       1),
  (2, 'Hassan Ali',    'hassan@qaflow.local', '', 'qa_lead',     1),
  (3, 'Ahmed Raza',    'ahmed@qaflow.local',  '', 'qa_engineer', 1),
  (4, 'Sara Khan',     'sara@qaflow.local',   '', 'developer',   1),
  (5, 'Bilal Mahmood', 'bilal@qaflow.local',  '', 'qa_engineer', 1),
  (6, 'Nadia Sheikh',  'nadia@qaflow.local',  '', 'viewer',      1);

-- ---------------------------------------------------------------------------
-- PROJECTS
-- ---------------------------------------------------------------------------
INSERT INTO projects (id, key_code, name, description, status, owner_id) VALUES
  (1, 'BANK', 'Banking Portal',     'Customer-facing online banking web application.', 'active', 2),
  (2, 'SHOP', 'E-commerce Website', 'Public storefront and checkout flow.',            'active', 2),
  (3, 'MOB',  'Mobile Banking App', 'iOS and Android mobile banking application.',     'active', 2);

-- ---------------------------------------------------------------------------
-- PROJECT MEMBERS
-- ---------------------------------------------------------------------------
INSERT INTO project_members (project_id, user_id, role) VALUES
  (1, 1, 'admin'),
  (1, 2, 'qa_lead'),
  (1, 3, 'qa_engineer'),
  (1, 4, 'developer'),
  (1, 5, 'qa_engineer'),
  (1, 6, 'viewer'),
  (2, 2, 'qa_lead'),
  (2, 3, 'qa_engineer'),
  (3, 2, 'qa_lead'),
  (3, 5, 'qa_engineer');

-- ---------------------------------------------------------------------------
-- ENVIRONMENTS
-- ---------------------------------------------------------------------------
INSERT INTO environments (id, project_id, name, browser, browser_version, os, os_version, device, device_version, app_version, notes) VALUES
  (1, 1, 'Chrome / Windows',  'Chrome',  '120', 'Windows', '11', NULL,       NULL, '2.5.0', 'Primary desktop target.'),
  (2, 1, 'Firefox / Windows', 'Firefox', '121', 'Windows', '11', NULL,       NULL, '2.5.0', 'Secondary browser coverage.'),
  (3, 1, 'Edge / Windows',    'Edge',    '120', 'Windows', '11', NULL,       NULL, '2.5.0', 'Enterprise default browser.'),
  (4, 1, 'Android',           NULL,      NULL,  'Android', '14', 'Pixel 7',  '14', '2.5.0', 'Primary mobile target.'),
  (5, 1, 'iOS',               NULL,      NULL,  'iOS',     '17', 'iPhone 14','17', '2.5.0', 'Secondary mobile target.'),
  (6, 2, 'Staging Web',       'Chrome',  '120', 'Windows', '11', NULL,       NULL, '1.8.0', 'Staging checkout environment.'),
  (7, 3, 'Android QA',        NULL,      NULL,  'Android', '14', 'Pixel 7',  '14', '3.1.0', 'Android QA build.');

-- ---------------------------------------------------------------------------
-- REQUIREMENTS — Banking Portal
-- ---------------------------------------------------------------------------
INSERT INTO requirements (id, project_id, code, title, description, priority, status, author_id) VALUES
  (1, 1, 'REQ-001', 'User Login',            'Registered users must be able to authenticate with email and password.', 'critical', 'approved',  2),
  (2, 1, 'REQ-002', 'Password Reset',        'Users must be able to reset their password via a valid email link.',     'high',     'approved',  2),
  (3, 1, 'REQ-003', 'Fund Transfer',         'Users must be able to transfer funds between their own accounts.',        'critical', 'approved',  2),
  (4, 1, 'REQ-004', 'Bill Payment',          'Users must be able to pay registered utility bills.',                    'high',     'in_review', 2),
  (5, 1, 'REQ-005', 'Transaction History',   'Users must be able to view their last 12 months of transactions.',       'medium',   'approved',  2),
  (6, 1, 'REQ-006', 'Account Statement PDF', 'Users must be able to download a monthly account statement as PDF.',    'low',      'draft',     2),
  (7, 1, 'REQ-007', 'Session Timeout',       'Idle sessions must expire after 15 minutes of inactivity.',              'medium',   'approved',  2),
  (8, 1, 'REQ-008', 'Beneficiary Management','Users must be able to add and remove payees for transfers.',             'high',     'approved',  2);

-- ---------------------------------------------------------------------------
-- REQUIREMENTS — E-commerce
-- ---------------------------------------------------------------------------
INSERT INTO requirements (id, project_id, code, title, description, priority, status, author_id) VALUES
  (9,  2, 'REQ-101', 'Product Search', 'Customers must be able to search the product catalog.', 'high',     'approved', 2),
  (10, 2, 'REQ-102', 'Cart Checkout',  'Customers must be able to complete checkout and pay.',  'critical', 'approved', 2);

-- ---------------------------------------------------------------------------
-- TEST SUITES — Banking Portal
-- ---------------------------------------------------------------------------
INSERT INTO test_suites (id, project_id, parent_id, name, description) VALUES
  (1,  1, NULL, 'Authentication',      'Login, logout, and password management.'),
  (2,  1, 1,    'Login',               'Login scenarios.'),
  (3,  1, 1,    'Logout',              'Logout scenarios.'),
  (4,  1, 1,    'Password Reset',      'Password reset flows.'),
  (5,  1, NULL, 'Accounts',            'Account and beneficiary management.'),
  (6,  1, 5,    'Beneficiaries',       'Payee add/remove flows.'),
  (7,  1, NULL, 'Transactions',        'Money movement and history.'),
  (8,  1, 7,    'Fund Transfer',       'Transfer between own accounts.'),
  (9,  1, 7,    'Bill Payment',        'Utility bill payment.'),
  (10, 1, 7,    'Transaction History', 'History listing and filters.');

-- ---------------------------------------------------------------------------
-- TEST SUITES — E-commerce
-- ---------------------------------------------------------------------------
INSERT INTO test_suites (id, project_id, parent_id, name, description) VALUES
  (11, 2, NULL, 'Catalog',  'Product browsing and search.'),
  (12, 2, NULL, 'Checkout', 'Cart and payment flow.');

-- ---------------------------------------------------------------------------
-- TEST CASES — Banking Portal
-- ---------------------------------------------------------------------------
INSERT INTO test_cases
  (id, project_id, suite_id, code, title, description, preconditions, priority, severity, test_type, component, tags, automation_status, author_id, current_version)
VALUES
  (1,  1, 2,  'TC-001', 'Login with valid credentials',         'Verify a registered user can log in.',                                    'A registered, active user exists.',                      'critical', 'critical', 'smoke',       'Auth',         'smoke,login',         'manual', 2, 1),
  (2,  1, 2,  'TC-002', 'Login with invalid password',          'Verify login is rejected for a wrong password.',                          'A registered, active user exists.',                      'high',     'high',     'functional',  'Auth',         'negative,login',      'manual', 2, 1),
  (3,  1, 2,  'TC-003', 'Login with empty fields',              'Verify validation appears when both fields are empty.',                   'None.',                                                  'medium',   'medium',   'functional',  'Auth',         'negative,validation', 'manual', 2, 1),
  (4,  1, 2,  'TC-004', 'Login with unknown email',             'Verify login is rejected for an unregistered email.',                     'None.',                                                  'medium',   'medium',   'functional',  'Auth',         'negative,login',      'manual', 2, 1),
  (5,  1, 2,  'TC-005', 'Account locked after 5 failed logins', 'Verify the account is locked after 5 consecutive failed logins.',         'A registered, active user exists.',                      'high',     'high',     'security',    'Auth',         'security,lockout',    'manual', 2, 1),
  (6,  1, 3,  'TC-006', 'Logout clears session',                'Verify logout invalidates the session and redirects to login.',           'User is logged in.',                                     'medium',   'medium',   'functional',  'Auth',         'logout',              'manual', 2, 1),
  (7,  1, 4,  'TC-007', 'Password reset with valid email',      'Verify a reset email is sent for a registered address.',                  'A registered user exists.',                              'high',     'high',     'functional',  'Auth',         'password,reset',      'manual', 2, 1),
  (8,  1, 4,  'TC-008', 'Password reset with unknown email',    'Verify no enumeration hint is returned for an unknown email.',            'None.',                                                  'medium',   'medium',   'security',    'Auth',         'password,security',   'manual', 2, 1),
  (9,  1, 8,  'TC-009', 'Transfer between own accounts',        'Verify a valid transfer moves funds between own accounts.',               'Two active accounts with sufficient balance.',           'critical', 'critical', 'functional',  'Transfers',    'money',               'manual', 2, 1),
  (10, 1, 8,  'TC-010', 'Transfer with insufficient balance',   'Verify transfer is rejected when the source balance is insufficient.',    'Two active accounts, source balance lower than amount.', 'high',     'high',     'negative',    'Transfers',    'money,negative',      'manual', 2, 1),
  (11, 1, 8,  'TC-011', 'Transfer to external account',         'Verify transfers to non-owned accounts are blocked.',                     'A registered payee exists.',                             'high',     'high',     'security',    'Transfers',    'security',            'manual', 2, 1),
  (12, 1, 9,  'TC-012', 'Pay a registered bill',                'Verify a registered utility bill can be paid.',                           'A registered biller exists.',                            'high',     'high',     'functional',  'Payments',     'bills',               'manual', 2, 1),
  (13, 1, 9,  'TC-013', 'Pay an unregistered biller',           'Verify payment is blocked for an unknown biller.',                        'None.',                                                  'medium',   'medium',   'negative',    'Payments',     'bills,negative',      'manual', 2, 1),
  (14, 1, 10, 'TC-014', 'View 12 months of history',            'Verify up to 12 months of transactions are listed.',                      'At least one year of transactions exists.',              'medium',   'medium',   'functional',  'History',      'history',             'manual', 2, 1),
  (15, 1, 10, 'TC-015', 'Filter history by date range',         'Verify history can be filtered by date range.',                           'Multiple months of transactions exist.',                 'low',      'low',      'functional',  'History',      'history,filter',      'manual', 2, 1),
  (16, 1, 6,  'TC-016', 'Add a new payee',                      'Verify a payee can be added.',                                            'User is logged in.',                                     'high',     'medium',   'functional',  'Beneficiaries','payee',               'manual', 2, 1),
  (17, 1, 6,  'TC-017', 'Remove an existing payee',             'Verify a payee can be removed.',                                          'At least one payee exists.',                             'medium',   'low',      'functional',  'Beneficiaries','payee',               'manual', 2, 1),
  (18, 1, NULL,'TC-018', 'Idle session expires after 15 minutes','Verify idle sessions expire and redirect to login.',                     'User is logged in.',                                     'medium',   'medium',   'security',    'Auth',         'security,timeout',    'manual', 2, 1),
  (19, 1, NULL,'TC-019', 'Download statement as PDF',           'Verify a monthly statement can be downloaded as PDF.',                    'At least one month of transactions exists.',             'low',      'low',      'functional',  'Statements',   'pdf',                 'manual', 2, 1),
  (20, 1, NULL,'TC-020', 'Login over HTTPS only',               'Verify HTTP requests are redirected to HTTPS.',                           'None.',                                                  'high',     'high',     'security',    'Auth',         'security,tls',        'manual', 2, 1);

-- ---------------------------------------------------------------------------
-- TEST CASES — E-commerce
-- ---------------------------------------------------------------------------
INSERT INTO test_cases
  (id, project_id, suite_id, code, title, description, preconditions, priority, severity, test_type, component, tags, automation_status, author_id, current_version)
VALUES
  (21, 2, 11, 'TC-101', 'Search returns matching products', 'Verify search returns products matching the query.', 'Catalog is populated.', 'high',     'high',     'functional', 'Catalog',  'search',         'manual', 2, 1),
  (22, 2, 11, 'TC-102', 'Search with empty query',          'Verify empty search shows the full catalog.',        'Catalog is populated.', 'low',      'low',      'functional', 'Catalog',  'search',         'manual', 2, 1),
  (23, 2, 12, 'TC-103', 'Add product to cart',              'Verify a product can be added to the cart.',         'A product exists.',     'high',     'high',     'functional', 'Cart',     'cart',           'manual', 2, 1),
  (24, 2, 12, 'TC-104', 'Checkout with valid card',         'Verify checkout completes with a valid card.',       'Cart contains items.',  'critical', 'critical', 'functional', 'Checkout', 'payment',        'manual', 2, 1),
  (25, 2, 12, 'TC-105', 'Checkout with declined card',      'Verify checkout surfaces a clear decline message.',  'Cart contains items.',  'high',     'high',     'negative',   'Checkout', 'payment,negative','manual', 2, 1);

-- ---------------------------------------------------------------------------
-- TEST STEPS
-- ---------------------------------------------------------------------------
INSERT INTO test_steps (test_case_id, step_order, action, expected_result) VALUES
  (1, 1, 'Open the login page.',                                  'Login page is displayed.'),
  (1, 2, 'Enter a valid email address.',                          'Email is accepted.'),
  (1, 3, 'Enter the correct password.',                           'Password is accepted.'),
  (1, 4, 'Click the Login button.',                               'Dashboard opens with the user''s name.'),
  (2, 1, 'Open the login page.',                                  'Login page is displayed.'),
  (2, 2, 'Enter a valid email address.',                          'Email is accepted.'),
  (2, 3, 'Enter an incorrect password.',                          'Password field accepts the input.'),
  (2, 4, 'Click the Login button.',                               'A generic error is shown; the user stays on the login page.'),
  (3, 1, 'Open the login page.',                                  'Login page is displayed.'),
  (3, 2, 'Leave both fields empty and click Login.',              'Both fields show a required validation message.'),
  (4, 1, 'Open the login page.',                                  'Login page is displayed.'),
  (4, 2, 'Enter an unregistered email and any password.',         'Input is accepted.'),
  (4, 3, 'Click Login.',                                          'A generic error is shown; no enumeration hint.'),
  (5, 1, 'Open the login page.',                                  'Login page is displayed.'),
  (5, 2, 'Submit an incorrect password five times.',              'Each attempt is rejected.'),
  (5, 3, 'Submit the correct password on the sixth try.',         'The account is locked and login is refused.'),
  (9, 1, 'Open the Fund Transfer page.',                          'Transfer form is displayed.'),
  (9, 2, 'Select a source account and a destination account.',    'Both accounts are selectable.'),
  (9, 3, 'Enter a valid amount within the balance.',              'Amount is accepted.'),
  (9, 4, 'Submit the transfer.',                                  'The transfer succeeds and both balances update.'),
  (10, 1, 'Open the Fund Transfer page.',                         'Transfer form is displayed.'),
  (10, 2, 'Select a source account with an insufficient balance.', 'Source account is selectable.'),
  (10, 3, 'Enter an amount larger than the balance.',             'Amount is accepted by the form.'),
  (10, 4, 'Submit the transfer.',                                 'A validation error is shown; no transfer occurs.'),
  (12, 1, 'Open the Bill Payment page.',                          'Bill Payment form is displayed.'),
  (12, 2, 'Select a registered biller.',                          'Biller details are shown.'),
  (12, 3, 'Enter a valid amount and submit.',                     'Payment is accepted and a confirmation is shown.'),
  (14, 1, 'Open Transaction History.',                            'History page is displayed.'),
  (14, 2, 'Select the last 12 months range.',                     'Up to 12 months of transactions are listed.'),
  (16, 1, 'Open Beneficiaries.',                                  'Beneficiaries page is displayed.'),
  (16, 2, 'Click Add Payee.',                                     'Payee form opens.'),
  (16, 3, 'Enter valid payee details and save.',                  'Payee is added and appears in the list.'),
  (18, 1, 'Log in and remain idle for 15 minutes.',               'Session is active.'),
  (18, 2, 'Attempt any action after the idle period.',            'User is redirected to the login page.'),
  (24, 1, 'Open the cart with at least one item.',                'Cart page is displayed.'),
  (24, 2, 'Proceed to checkout.',                                 'Checkout form is displayed.'),
  (24, 3, 'Enter valid card details and submit.',                 'Order confirmation is displayed.');

-- ---------------------------------------------------------------------------
-- LINK TEST CASES TO REQUIREMENTS
-- ---------------------------------------------------------------------------
INSERT INTO test_case_requirements (test_case_id, requirement_id) VALUES
  (1, 1), (2, 1), (3, 1), (4, 1), (5, 1), (20, 1),
  (7, 2), (8, 2),
  (9, 3), (10, 3), (11, 3),
  (12, 4), (13, 4),
  (14, 5), (15, 5),
  (19, 6),
  (18, 7),
  (16, 8), (17, 8),
  (21, 9), (22, 9),
  (23, 10), (24, 10), (25, 10);

-- ---------------------------------------------------------------------------
-- TEST PLANS
-- ---------------------------------------------------------------------------
INSERT INTO test_plans (id, project_id, name, release_version, description, scope, status, start_date, end_date, owner_id) VALUES
  (1, 1, 'Banking Portal v2.5 Regression', '2.5.0', 'Full regression for the v2.5 release.', 'Authentication, Accounts, Transactions', 'active', '2026-09-01', '2026-09-30', 2),
  (2, 2, 'E-commerce v1.8 Smoke',          '1.8.0', 'Smoke pass for the storefront release.', 'Catalog, Cart, Checkout',               'draft',  '2026-10-01', '2026-10-15', 2);

-- ---------------------------------------------------------------------------
-- TEST RUNS
-- ---------------------------------------------------------------------------
INSERT INTO test_runs (id, project_id, plan_id, environment_id, name, description, status, assigned_to, created_by, started_at, completed_at) VALUES
  (1, 1, 1, 1, 'Regression — Chrome / Windows', 'Regression on Chrome / Windows for v2.5.', 'in_progress', 3, 2, '2026-09-02 09:00:00', NULL),
  (2, 1, 1, 4, 'Regression — Android',          'Regression on Android for v2.5.',          'in_progress', 5, 2, '2026-09-03 09:00:00', NULL),
  (3, 2, 2, 6, 'Smoke — Staging Web',           'Smoke pass on staging for v1.8.',          'not_started', 3, 2, NULL, NULL);

-- ---------------------------------------------------------------------------
-- TEST RUN CASES
-- ---------------------------------------------------------------------------
INSERT INTO test_run_cases (test_run_id, test_case_id, environment_id, assignee_id) VALUES
  (1,  1, 1, 3),
  (1,  2, 1, 3),
  (1,  3, 1, 3),
  (1,  4, 1, 3),
  (1,  5, 1, 3),
  (1,  6, 1, 3),
  (1,  7, 1, 3),
  (1,  8, 1, 3),
  (1,  9, 1, 3),
  (1, 10, 1, 3),
  (1, 11, 1, 3),
  (1, 12, 1, 3),
  (1, 13, 1, 3),
  (1, 14, 1, 3),
  (1, 15, 1, 3),
  (1, 16, 1, 3),
  (1, 17, 1, 3),
  (1, 18, 1, 3),
  (1, 19, 1, 3),
  (1, 20, 1, 3);

INSERT INTO test_run_cases (test_run_id, test_case_id, environment_id, assignee_id) VALUES
  (2,  1, 4, 5),
  (2,  2, 4, 5),
  (2,  9, 4, 5),
  (2, 10, 4, 5),
  (2, 12, 4, 5),
  (2, 14, 4, 5),
  (2, 16, 4, 5),
  (2, 18, 4, 5);

-- ---------------------------------------------------------------------------
-- TEST RESULTS — Run 1 (Chrome / Windows)
-- ---------------------------------------------------------------------------
INSERT INTO test_results (test_run_case_id, status, actual_result, notes, executed_by, is_automated, duration_ms, executed_at) VALUES
  (1,  'pass',    'Dashboard opened successfully.',                            '',                      3, 0, 4200, '2026-09-02 09:05:00'),
  (2,  'pass',    'Generic error shown, no enumeration hint.',                 '',                      3, 0, 2100, '2026-09-02 09:07:00'),
  (3,  'pass',    'Required messages shown on both fields.',                   '',                      3, 0, 1500, '2026-09-02 09:08:00'),
  (4,  'pass',    'Generic error shown.',                                      '',                      3, 0, 1800, '2026-09-02 09:09:00'),
  (5,  'fail',    'Account was not locked after 5 failed attempts.',           'Reported as DEF-1001.', 3, 0, 3600, '2026-09-02 09:15:00'),
  (6,  'pass',    'Session cleared and redirected to login.',                  '',                      3, 0, 1200, '2026-09-02 09:17:00'),
  (7,  'pass',    'Reset email dispatched.',                                   '',                      3, 0, 2600, '2026-09-02 09:20:00'),
  (8,  'pass',    'No enumeration hint returned.',                             '',                      3, 0, 1900, '2026-09-02 09:22:00'),
  (9,  'pass',    'Funds moved and balances updated.',                         '',                      3, 0, 3400, '2026-09-02 09:30:00'),
  (10, 'pass',    'Validation error shown, no transfer.',                      '',                      3, 0, 2400, '2026-09-02 09:32:00'),
  (11, 'blocked', 'External transfer flow depends on a payee service not yet deployed.', 'Blocked pending service.', 3, 0, 0, '2026-09-02 09:35:00'),
  (12, 'pass',    'Bill paid and confirmation shown.',                         '',                      3, 0, 3100, '2026-09-02 09:40:00'),
  (13, 'pass',    'Payment blocked for unknown biller.',                       '',                      3, 0, 1600, '2026-09-02 09:42:00'),
  (14, 'pass',    '12 months listed.',                                         '',                      3, 0, 2800, '2026-09-02 09:45:00'),
  (15, 'fail',    'Date range filter returned no rows for a valid range.',     'Reported as DEF-1002.', 3, 0, 2200, '2026-09-02 09:48:00'),
  (16, 'pass',    'Payee added.',                                              '',                      3, 0, 1900, '2026-09-02 09:50:00'),
  (17, 'not_run', NULL,                                                        '',                      NULL, 0, NULL, NULL),
  (18, 'not_run', NULL,                                                        '',                      NULL, 0, NULL, NULL),
  (19, 'not_run', NULL,                                                        '',                      NULL, 0, NULL, NULL),
  (20, 'not_run', NULL,                                                        '',                      NULL, 0, NULL, NULL);

-- ---------------------------------------------------------------------------
-- TEST RESULTS — Run 2 (Android)
-- ---------------------------------------------------------------------------
INSERT INTO test_results (test_run_case_id, status, actual_result, notes, executed_by, is_automated, duration_ms, executed_at) VALUES
  (21, 'pass',    'Login succeeded on Android.',                              '',                      5, 0, 3200, '2026-09-03 09:05:00'),
  (22, 'pass',    'Invalid password rejected.',                               '',                      5, 0, 2400, '2026-09-03 09:07:00'),
  (23, 'fail',    'Transfer form froze on submit.',                           'Reported as DEF-1003.', 5, 0, 4100, '2026-09-03 09:20:00'),
  (24, 'blocked', 'Cannot verify insufficient-balance path on this device.',  'Blocked by DEV-1.',     5, 0, 0,    '2026-09-03 09:22:00'),
  (25, 'not_run', NULL,                                                       '',                      NULL, 0, NULL, NULL),
  (26, 'not_run', NULL,                                                       '',                      NULL, 0, NULL, NULL),
  (27, 'not_run', NULL,                                                       '',                      NULL, 0, NULL, NULL),
  (28, 'not_run', NULL,                                                       '',                      NULL, 0, NULL, NULL);

-- ---------------------------------------------------------------------------
-- STEP RESULTS
-- ---------------------------------------------------------------------------
INSERT INTO test_step_results (test_result_id, step_order, status, actual_result, notes) VALUES
  (1, 1, 'pass', 'Login page displayed.', ''),
  (1, 2, 'pass', 'Email accepted.',       ''),
  (1, 3, 'pass', 'Password accepted.',    ''),
  (1, 4, 'pass', 'Dashboard opened.',     '');

INSERT INTO test_step_results (test_result_id, step_order, status, actual_result, notes) VALUES
  (5, 1, 'pass', 'Login page displayed.',      ''),
  (5, 2, 'pass', 'Attempts 1..4 rejected.',    ''),
  (5, 3, 'fail', 'Account remained unlocked.', 'Lockout rule did not trigger.');

INSERT INTO test_step_results (test_result_id, step_order, status, actual_result, notes) VALUES
  (23, 1, 'pass', 'Transfer form displayed.', ''),
  (23, 2, 'pass', 'Accounts selected.',       ''),
  (23, 3, 'pass', 'Amount accepted.',         ''),
  (23, 4, 'fail', 'UI froze after submit.',   'No response for 20 seconds.');

-- ---------------------------------------------------------------------------
-- DEFECTS
-- ---------------------------------------------------------------------------
INSERT INTO defects
  (id, project_id, code, title, description, expected_result, actual_result, severity, priority, status, test_case_id, requirement_id, test_result_id, environment_id, reporter_id, assignee_id)
VALUES
  (1, 1, 'DEF-1001', 'Account not locked after 5 failed logins',
   'The lockout rule did not trigger after five consecutive failed login attempts.',
   'Account should be locked after five consecutive failed logins.',
   'Account remained unlocked and allowed further attempts.',
   'high', 'high', 'open', 5, 1, 5, 1, 3, 4),

  (2, 1, 'DEF-1002', 'Transaction history date filter returns no rows',
   'Filtering by a valid date range returns an empty list.',
   'Valid date ranges should return matching transactions.',
   'Empty list for every valid range tested.',
   'medium', 'medium', 'in_progress', 15, 5, 15, 1, 3, 4),

  (3, 1, 'DEF-1003', 'Transfer form freezes on Android submit',
   'The transfer form UI hangs after submitting on Android.',
   'Transfer should submit and show confirmation.',
   'UI freezes and no response is returned.',
   'critical', 'high', 'assigned', 9, 3, 23, 4, 5, 4);

-- ---------------------------------------------------------------------------
-- COMMENTS
-- ---------------------------------------------------------------------------
INSERT INTO comments (entity_type, entity_id, content, author_id) VALUES
  ('defect', 1, 'Reproduced on Chrome and Firefox. Preparing a fix branch.',             4),
  ('defect', 1, 'Fix pushed to staging. Please retest after the next build.',            4),
  ('defect', 2, 'Looks like the filter compares local time against UTC stored values.',  4),
  ('defect', 3, 'Android-only. Suspect a blocking call on the main thread.',             5);

-- ---------------------------------------------------------------------------
-- ACTIVITY LOG (illustrative rows; the app writes more at runtime)
-- ---------------------------------------------------------------------------
INSERT INTO activity_logs (user_id, project_id, action, entity_type, entity_id, metadata) VALUES
  (2, 1, 'project.created',     'project',     1, JSON_OBJECT('name', 'Banking Portal')),
  (2, 1, 'requirement.created', 'requirement', 1, JSON_OBJECT('code', 'REQ-001')),
  (2, 1, 'test_case.created',   'test_case',   1, JSON_OBJECT('code', 'TC-001')),
  (2, 1, 'test_plan.created',   'test_plan',   1, JSON_OBJECT('name', 'Banking Portal v2.5 Regression')),
  (2, 1, 'test_run.created',    'test_run',    1, JSON_OBJECT('name', 'Regression — Chrome / Windows')),
  (3, 1, 'test_case.executed',  'test_result', 1, JSON_OBJECT('status', 'pass')),
  (3, 1, 'defect.created',      'defect',      1, JSON_OBJECT('code', 'DEF-1001'));