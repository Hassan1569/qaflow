<?php
declare(strict_types=1);

/**
 * QAFlow — API entry point.
 *
 * All requests under /api are routed here. The file is intentionally small:
 * it bootstraps the environment, installs a global error/exception handler,
 * builds the router, registers the route table, and dispatches.
 */

// ---------------------------------------------------------------------------
// Error reporting (development vs production is decided after .env is loaded).
// ---------------------------------------------------------------------------
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// ---------------------------------------------------------------------------
// Bootstrap: autoloader, environment loader, helpers.
// ---------------------------------------------------------------------------
require __DIR__ . '/../bootstrap.php';

use QAFlow\Core\AppException;
use QAFlow\Core\Container;
use QAFlow\Core\Handler;
use QAFlow\Core\Router;
use QAFlow\Middleware\CorsMiddleware;
use QAFlow\Middleware\RateLimitMiddleware;

// ---------------------------------------------------------------------------
// Install global handlers early so any failure is returned as JSON.
// ---------------------------------------------------------------------------
Handler::install();

// ---------------------------------------------------------------------------
// Build the container and register core services.
// ---------------------------------------------------------------------------
$container = new Container();
require __DIR__ . '/../config/container.php';
$container->boot();

// ---------------------------------------------------------------------------
// Build the router and register routes.
// ---------------------------------------------------------------------------
$router = new Router($container);

// Global middleware — runs before any route.
$router->use(new CorsMiddleware());
$router->use(new RateLimitMiddleware());

// Route table.
(require __DIR__ . '/../routes/api.php')($router);

// ---------------------------------------------------------------------------
// Dispatch. The Router will emit the response itself.
// ---------------------------------------------------------------------------
try {
    $router->dispatch(
        $_SERVER['REQUEST_METHOD'] ?? 'GET',
        $_SERVER['REQUEST_URI']    ?? '/'
    );
} catch (AppException $e) {
    Handler::renderAppException($e);
} catch (\Throwable $e) {
    Handler::renderUnexpected($e);
}