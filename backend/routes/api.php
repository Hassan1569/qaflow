<?php
declare(strict_types=1);

/**
 * QAFlow — Route table.
 *
 * Returns a closure that receives the Router and registers every endpoint.
 * Route order matters: more specific patterns must come before wildcards.
 *
 * Middleware:
 *   - AuthMiddleware requires a valid bearer token.
 *   - RoleMiddleware enforces the roles declared on the route.
 *
 * A route with no AuthMiddleware is public (login only, plus health).
 */

use QAFlow\Core\Router;
use QAFlow\Middleware\AuthMiddleware;
use QAFlow\Middleware\RoleMiddleware;

use QAFlow\Controllers\AuthController;
use QAFlow\Controllers\UserController;
use QAFlow\Controllers\ProjectController;
use QAFlow\Controllers\RequirementController;
use QAFlow\Controllers\TestSuiteController;
use QAFlow\Controllers\TestCaseController;
use QAFlow\Controllers\TestPlanController;
use QAFlow\Controllers\TestRunController;
use QAFlow\Controllers\TestExecutionController;
use QAFlow\Controllers\DefectController;
use QAFlow\Controllers\EnvironmentController;
use QAFlow\Controllers\AttachmentController;
use QAFlow\Controllers\CommentController;
use QAFlow\Controllers\DashboardController;
use QAFlow\Controllers\ReportController;
use QAFlow\Controllers\TraceabilityController;
use QAFlow\Controllers\ActivityController;
use QAFlow\Controllers\AutomationController;

return static function (Router $router): void {
    // Build middleware instances once. They are stateless.
    $container = null;
    // The Router exposes the container via a getter-less design; instead we
    // build middleware lazily through the container registered in index.php.
    // To keep this file declarative, we resolve them from the global accessor.
    $container = \QAFlow\Core\ContainerAccess::get();

    $auth = $container->get(AuthMiddleware::class);
    $role = $container->get(RoleMiddleware::class);

    /** @var array<int,object> $protected */
    $protected = [$auth, $role];

    /** @var array<int,object> $authOnly */
    $authOnly = [$auth];

    // -----------------------------------------------------------------------
    // Health
    // -----------------------------------------------------------------------
    $router->get('/api/health', [AuthController::class, 'health']);

    // -----------------------------------------------------------------------
    // Auth
    // -----------------------------------------------------------------------
    $router->post('/api/auth/login',  [AuthController::class, 'login']);
    $router->post('/api/auth/logout', [AuthController::class, 'logout'], $authOnly);
    $router->get ('/api/auth/me',     [AuthController::class, 'me'],     $authOnly);

    // -----------------------------------------------------------------------
    // Users (admin only for mutations)
    // -----------------------------------------------------------------------
    $router->get ('/api/users',              [UserController::class, 'index'],  $protected, ['admin', 'qa_lead']);
    $router->get ('/api/users/{id}',         [UserController::class, 'show'],   $protected, ['admin', 'qa_lead']);
    $router->post('/api/users',              [UserController::class, 'store'],  $protected, ['admin']);
    $router->put ('/api/users/{id}',         [UserController::class, 'update'], $protected, ['admin']);
    $router->post('/api/users/{id}/deactivate', [UserController::class, 'deactivate'], $protected, ['admin']);

    // -----------------------------------------------------------------------
    // Projects
    // -----------------------------------------------------------------------
    $router->get ('/api/projects',                    [ProjectController::class, 'index'],   $authOnly);
    $router->post('/api/projects',                    [ProjectController::class, 'store'],   $protected, ['admin', 'qa_lead']);
    $router->get ('/api/projects/{id}',               [ProjectController::class, 'show'],    $authOnly);
    $router->put ('/api/projects/{id}',               [ProjectController::class, 'update'],  $protected, ['admin', 'qa_lead']);
    $router->post('/api/projects/{id}/archive',       [ProjectController::class, 'archive'], $protected, ['admin', 'qa_lead']);
    $router->get ('/api/projects/{id}/members',       [ProjectController::class, 'members'], $authOnly);
    $router->post('/api/projects/{id}/members',       [ProjectController::class, 'addMember'], $protected, ['admin', 'qa_lead']);
    $router->delete('/api/projects/{id}/members/{userId}', [ProjectController::class, 'removeMember'], $protected, ['admin', 'qa_lead']);

    // -----------------------------------------------------------------------
    // Requirements
    // -----------------------------------------------------------------------
    $router->get ('/api/requirements',              [RequirementController::class, 'index'],   $authOnly);
    $router->post('/api/requirements',              [RequirementController::class, 'store'],   $protected, ['admin', 'qa_lead']);
    $router->get ('/api/requirements/{id}',         [RequirementController::class, 'show'],    $authOnly);
    $router->put ('/api/requirements/{id}',         [RequirementController::class, 'update'],  $protected, ['admin', 'qa_lead']);
    $router->delete('/api/requirements/{id}',       [RequirementController::class, 'destroy'], $protected, ['admin', 'qa_lead']);
    $router->get ('/api/requirements/{id}/test-cases', [RequirementController::class, 'testCases'], $authOnly);

    // -----------------------------------------------------------------------
    // Test Suites
    // -----------------------------------------------------------------------
    $router->get ('/api/test-suites',          [TestSuiteController::class, 'index'],   $authOnly);
    $router->post('/api/test-suites',          [TestSuiteController::class, 'store'],   $protected, ['admin', 'qa_lead']);
    $router->put ('/api/test-suites/{id}',     [TestSuiteController::class, 'update'],  $protected, ['admin', 'qa_lead']);
    $router->delete('/api/test-suites/{id}',   [TestSuiteController::class, 'destroy'], $protected, ['admin', 'qa_lead']);

    // -----------------------------------------------------------------------
    // Test Cases
    // -----------------------------------------------------------------------
    $router->get ('/api/test-cases',                [TestCaseController::class, 'index'],   $authOnly);
    $router->post('/api/test-cases',                [TestCaseController::class, 'store'],   $protected, ['admin', 'qa_lead']);
    $router->get ('/api/test-cases/{id}',           [TestCaseController::class, 'show'],    $authOnly);
    $router->put ('/api/test-cases/{id}',           [TestCaseController::class, 'update'],  $protected, ['admin', 'qa_lead']);
    $router->delete('/api/test-cases/{id}',         [TestCaseController::class, 'destroy'], $protected, ['admin', 'qa_lead']);
    $router->get ('/api/test-cases/{id}/versions',  [TestCaseController::class, 'versions'], $authOnly);

    // -----------------------------------------------------------------------
    // Test Plans
    // -----------------------------------------------------------------------
    $router->get ('/api/test-plans',        [TestPlanController::class, 'index'],  $authOnly);
    $router->post('/api/test-plans',        [TestPlanController::class, 'store'],  $protected, ['admin', 'qa_lead']);
    $router->get ('/api/test-plans/{id}',   [TestPlanController::class, 'show'],   $authOnly);
    $router->put ('/api/test-plans/{id}',   [TestPlanController::class, 'update'], $protected, ['admin', 'qa_lead']);

    // -----------------------------------------------------------------------
    // Test Runs
    // -----------------------------------------------------------------------
    $router->get ('/api/test-runs',                  [TestRunController::class, 'index'],   $authOnly);
    $router->post('/api/test-runs',                  [TestRunController::class, 'store'],   $protected, ['admin', 'qa_lead']);
    $router->get ('/api/test-runs/{id}',             [TestRunController::class, 'show'],    $authOnly);
    $router->post('/api/test-runs/{id}/start',       [TestRunController::class, 'start'],   $protected, ['admin', 'qa_lead', 'qa_engineer']);
    $router->post('/api/test-runs/{id}/complete',    [TestRunController::class, 'complete'], $protected, ['admin', 'qa_lead']);

    // -----------------------------------------------------------------------
    // Executions
    // -----------------------------------------------------------------------
    $router->get ('/api/executions/{id}',                          [TestExecutionController::class, 'show'],           $authOnly);
    $router->get ('/api/executions/{id}/available',                [TestExecutionController::class, 'available'],      $authOnly);
    $router->post('/api/executions/{id}/steps/{stepId}/result',    [TestExecutionController::class, 'saveStepResult'], $protected, ['admin', 'qa_lead', 'qa_engineer']);
    $router->post('/api/executions/{id}/result',                   [TestExecutionController::class, 'saveResult'],     $protected, ['admin', 'qa_lead', 'qa_engineer']);

    // -----------------------------------------------------------------------
    // Defects
    // -----------------------------------------------------------------------
    $router->get ('/api/defects',            [DefectController::class, 'index'],   $authOnly);
    $router->post('/api/defects',            [DefectController::class, 'store'],   $protected, ['admin', 'qa_lead', 'qa_engineer']);
    $router->get ('/api/defects/{id}',       [DefectController::class, 'show'],    $authOnly);
    $router->put ('/api/defects/{id}',       [DefectController::class, 'update'],  $protected, ['admin', 'qa_lead', 'qa_engineer', 'developer']);
    $router->post('/api/defects/{id}/status',[DefectController::class, 'status'],  $protected, ['admin', 'qa_lead', 'qa_engineer', 'developer']);

    // -----------------------------------------------------------------------
    // Environments
    // -----------------------------------------------------------------------
    $router->get ('/api/environments',        [EnvironmentController::class, 'index'],   $authOnly);
    $router->post('/api/environments',        [EnvironmentController::class, 'store'],   $protected, ['admin', 'qa_lead']);
    $router->put ('/api/environments/{id}',   [EnvironmentController::class, 'update'],  $protected, ['admin', 'qa_lead']);
    $router->delete('/api/environments/{id}', [EnvironmentController::class, 'destroy'], $protected, ['admin', 'qa_lead']);

    // -----------------------------------------------------------------------
    // Attachments
    // -----------------------------------------------------------------------
    $router->post('/api/attachments',        [AttachmentController::class, 'store'],   $authOnly);
    $router->get ('/api/attachments/{id}',   [AttachmentController::class, 'show'],    $authOnly);
    $router->delete('/api/attachments/{id}', [AttachmentController::class, 'destroy'], $protected, ['admin', 'qa_lead', 'qa_engineer']);

    // -----------------------------------------------------------------------
    // Comments
    // -----------------------------------------------------------------------
    $router->get ('/api/comments', [CommentController::class, 'index'], $authOnly);
    $router->post('/api/comments', [CommentController::class, 'store'], $authOnly);

    // -----------------------------------------------------------------------
    // Dashboard
    // -----------------------------------------------------------------------
    $router->get('/api/dashboard/summary', [DashboardController::class, 'summary'], $authOnly);
    $router->get('/api/dashboard/charts',  [DashboardController::class, 'charts'],  $authOnly);

    // -----------------------------------------------------------------------
    // Reports
    // -----------------------------------------------------------------------
    $router->get('/api/reports/execution',       [ReportController::class, 'execution'],      $authOnly);
    $router->get('/api/reports/defects',         [ReportController::class, 'defects'],        $authOnly);
    $router->get('/api/reports/coverage',        [ReportController::class, 'coverage'],       $authOnly);
    $router->get('/api/reports/project-summary', [ReportController::class, 'projectSummary'], $authOnly);

    // -----------------------------------------------------------------------
    // Traceability
    // -----------------------------------------------------------------------
    $router->get('/api/traceability/matrix', [TraceabilityController::class, 'matrix'], $authOnly);

    // -----------------------------------------------------------------------
    // Activity
    // -----------------------------------------------------------------------
    $router->get('/api/activity', [ActivityController::class, 'index'], $authOnly);

    // -----------------------------------------------------------------------
    // Automation ingestion
    // -----------------------------------------------------------------------
    $router->post('/api/automation/ingest', [AutomationController::class, 'ingest']);
};