<?php
declare(strict_types=1);

/**
 * QAFlow — Container bindings.
 *
 * Called from public/index.php after the Container is constructed. Registers
 * every controller, service, and repository that the app resolves. Bindings
 * are singletons unless noted otherwise.
 */

use QAFlow\Config\Config;
use QAFlow\Core\Container;

use QAFlow\Repositories\UserRepository;
use QAFlow\Repositories\ProjectRepository;
use QAFlow\Repositories\RequirementRepository;
use QAFlow\Repositories\TestSuiteRepository;
use QAFlow\Repositories\TestCaseRepository;
use QAFlow\Repositories\TestPlanRepository;
use QAFlow\Repositories\TestRunRepository;
use QAFlow\Repositories\TestResultRepository;
use QAFlow\Repositories\DefectRepository;
use QAFlow\Repositories\EnvironmentRepository;
use QAFlow\Repositories\AttachmentRepository;
use QAFlow\Repositories\CommentRepository;
use QAFlow\Repositories\ActivityRepository;

use QAFlow\Services\AuthService;
use QAFlow\Services\ProjectService;
use QAFlow\Services\RequirementService;
use QAFlow\Services\TestCaseService;
use QAFlow\Services\TestPlanService;
use QAFlow\Services\TestRunService;
use QAFlow\Services\ExecutionService;
use QAFlow\Services\DefectService;
use QAFlow\Services\AttachmentService;
use QAFlow\Services\ActivityService;
use QAFlow\Services\DashboardService;
use QAFlow\Services\ReportService;
use QAFlow\Services\TraceabilityService;
use QAFlow\Services\AutomationService;
use QAFlow\Services\AiSuggestionService;

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

/** @var Container $container */

// ---------------------------------------------------------------------------
// Repositories — stateless, safe to share.
// ---------------------------------------------------------------------------
$container->singleton(UserRepository::class,        fn () => new UserRepository());
$container->singleton(ProjectRepository::class,     fn () => new ProjectRepository());
$container->singleton(RequirementRepository::class, fn () => new RequirementRepository());
$container->singleton(TestSuiteRepository::class,   fn () => new TestSuiteRepository());
$container->singleton(TestCaseRepository::class,    fn () => new TestCaseRepository());
$container->singleton(TestPlanRepository::class,    fn () => new TestPlanRepository());
$container->singleton(TestRunRepository::class,     fn () => new TestRunRepository());
$container->singleton(TestResultRepository::class,  fn () => new TestResultRepository());
$container->singleton(DefectRepository::class,      fn () => new DefectRepository());
$container->singleton(EnvironmentRepository::class, fn () => new EnvironmentRepository());
$container->singleton(AttachmentRepository::class,  fn () => new AttachmentRepository());
$container->singleton(CommentRepository::class,     fn () => new CommentRepository());
$container->singleton(ActivityRepository::class,    fn () => new ActivityRepository());

// ---------------------------------------------------------------------------
// Services.
// ---------------------------------------------------------------------------
$container->singleton(ActivityService::class, fn ($c) => new ActivityService(
    $c->get(ActivityRepository::class)
));

$container->singleton(AuthService::class, fn ($c) => new AuthService(
    $c->get(UserRepository::class),
    $c->get(ActivityService::class)
));

$container->singleton(ProjectService::class, fn ($c) => new ProjectService(
    $c->get(ProjectRepository::class),
    $c->get(UserRepository::class),
    $c->get(ActivityService::class)
));

$container->singleton(RequirementService::class, fn ($c) => new RequirementService(
    $c->get(RequirementRepository::class),
    $c->get(ProjectRepository::class),
    $c->get(ActivityService::class)
));

$container->singleton(TestCaseService::class, fn ($c) => new TestCaseService(
    $c->get(TestCaseRepository::class),
    $c->get(TestSuiteRepository::class),
    $c->get(ProjectRepository::class),
    $c->get(RequirementRepository::class),
    $c->get(ActivityService::class)
));

$container->singleton(TestPlanService::class, fn ($c) => new TestPlanService(
    $c->get(TestPlanRepository::class),
    $c->get(ProjectRepository::class),
    $c->get(ActivityService::class)
));

$container->singleton(TestRunService::class, fn ($c) => new TestRunService(
    $c->get(TestRunRepository::class),
    $c->get(TestCaseRepository::class),
    $c->get(EnvironmentRepository::class),
    $c->get(ProjectRepository::class),
    $c->get(ActivityService::class)
));

$container->singleton(ExecutionService::class, fn ($c) => new ExecutionService(
    $c->get(TestRunRepository::class),
    $c->get(TestResultRepository::class),
    $c->get(TestCaseRepository::class),
    $c->get(ActivityService::class)
));

$container->singleton(DefectService::class, fn ($c) => new DefectService(
    $c->get(DefectRepository::class),
    $c->get(ProjectRepository::class),
    $c->get(ActivityService::class)
));

$container->singleton(AttachmentService::class, fn ($c) => new AttachmentService(
    $c->get(AttachmentRepository::class),
    $c->get(ActivityService::class)
));

$container->singleton(DashboardService::class, fn ($c) => new DashboardService(
    $c->get(ProjectRepository::class),
    $c->get(RequirementRepository::class),
    $c->get(TestCaseRepository::class),
    $c->get(TestResultRepository::class),
    $c->get(DefectRepository::class)
));

$container->singleton(ReportService::class, fn ($c) => new ReportService(
    $c->get(ProjectRepository::class),
    $c->get(TestResultRepository::class),
    $c->get(DefectRepository::class),
    $c->get(RequirementRepository::class)
));

$container->singleton(TraceabilityService::class, fn ($c) => new TraceabilityService(
    $c->get(RequirementRepository::class),
    $c->get(TestCaseRepository::class),
    $c->get(TestResultRepository::class),
    $c->get(DefectRepository::class)
));

$container->singleton(AutomationService::class, fn ($c) => new AutomationService(
    $c->get(TestRunRepository::class),
    $c->get(TestResultRepository::class),
    $c->get(TestCaseRepository::class),
    $c->get(ActivityService::class)
));

$container->singleton(AiSuggestionService::class, fn () => new AiSuggestionService());

// ---------------------------------------------------------------------------
// Controllers.
// ---------------------------------------------------------------------------
$container->singleton(AuthController::class,         fn ($c) => new AuthController($c->get(AuthService::class)));
$container->singleton(UserController::class,         fn ($c) => new UserController($c->get(UserRepository::class)));
$container->singleton(ProjectController::class,      fn ($c) => new ProjectController($c->get(ProjectService::class)));
$container->singleton(RequirementController::class,  fn ($c) => new RequirementController($c->get(RequirementService::class)));
$container->singleton(TestSuiteController::class,    fn ($c) => new TestSuiteController($c->get(TestCaseService::class)));
$container->singleton(TestCaseController::class,     fn ($c) => new TestCaseController($c->get(TestCaseService::class)));
$container->singleton(TestPlanController::class,     fn ($c) => new TestPlanController($c->get(TestPlanService::class)));
$container->singleton(TestRunController::class,      fn ($c) => new TestRunController($c->get(TestRunService::class)));
$container->singleton(TestExecutionController::class,fn ($c) => new TestExecutionController($c->get(ExecutionService::class)));
$container->singleton(DefectController::class,       fn ($c) => new DefectController($c->get(DefectService::class)));
$container->singleton(EnvironmentController::class,  fn ($c) => new EnvironmentController($c->get(EnvironmentRepository::class)));
$container->singleton(AttachmentController::class,   fn ($c) => new AttachmentController($c->get(AttachmentService::class)));
$container->singleton(CommentController::class,      fn ($c) => new CommentController($c->get(CommentRepository::class)));
$container->singleton(DashboardController::class,    fn ($c) => new DashboardController($c->get(DashboardService::class)));
$container->singleton(ReportController::class,       fn ($c) => new ReportController($c->get(ReportService::class)));
$container->singleton(TraceabilityController::class, fn ($c) => new TraceabilityController($c->get(TraceabilityService::class)));
$container->singleton(ActivityController::class,     fn ($c) => new ActivityController($c->get(ActivityService::class)));
$container->singleton(AutomationController::class,   fn ($c) => new AutomationController($c->get(AutomationService::class)));

// ---------------------------------------------------------------------------
// Feature flags — resolved once.
// ---------------------------------------------------------------------------
$container->bind('config.ai_enabled', fn () => Config::bool('AI_ENABLED', false));