<?php
declare(strict_types=1);

/**
 * QAFlow — HTTP router.
 *
 * Registers routes with method, URI pattern (supporting `{param}` placeholders),
 * a handler (controller class name + method), optional middleware, and an
 * optional role list. Dispatches an incoming request to the first matching
 * route.
 */

namespace QAFlow\Core;

use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;

final class Router
{
    private Container $container;

    /**
     * @var array<int,array{
     *   method:string,
     *   pattern:string,
     *   regex:string,
     *   params:array<int,string>,
     *   handler:array{0:string,1:string},
     *   middleware:array<int,object>,
     *   roles:array<int,string>
     * }>
     */
    private array $routes = [];

    /** @var array<int,object> */
    private array $globalMiddleware = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register a global middleware. Global middleware runs before any route.
     */
    public function use(object $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    /**
     * Register a route.
     *
     * @param string                $method     HTTP method (GET, POST, PUT, PATCH, DELETE).
     * @param string                $pattern    URI pattern, e.g. `/projects/{id}`.
     * @param array{0:string,1:string} $handler Controller class + method.
     * @param array<int,object>     $middleware Route-specific middleware.
     * @param array<int,string>     $roles      Required roles (empty = any authenticated user).
     */
    public function add(string $method, string $pattern, array $handler, array $middleware = [], array $roles = []): void
    {
        $method = strtoupper($method);

        [$regex, $params] = $this->compilePattern($pattern);

        $this->routes[] = [
            'method'     => $method,
            'pattern'    => $pattern,
            'regex'      => $regex,
            'params'     => $params,
            'handler'    => $handler,
            'middleware' => $middleware,
            'roles'      => $roles,
        ];
    }

    public function get(string $pattern, array $handler, array $middleware = [], array $roles = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware, $roles);
    }

    public function post(string $pattern, array $handler, array $middleware = [], array $roles = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware, $roles);
    }

    public function put(string $pattern, array $handler, array $middleware = [], array $roles = []): void
    {
        $this->add('PUT', $pattern, $handler, $middleware, $roles);
    }

    public function patch(string $pattern, array $handler, array $middleware = [], array $roles = []): void
    {
        $this->add('PATCH', $pattern, $handler, $middleware, $roles);
    }

    public function delete(string $pattern, array $handler, array $middleware = [], array $roles = []): void
    {
        $this->add('DELETE', $pattern, $handler, $middleware, $roles);
    }

    /**
     * Dispatch the request.
     */
    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $path   = $this->normalizePath($uri);

        $request = new Request($method, $path);
        $this->container->instance(Request::class, $request);

        // Global middleware.
        foreach ($this->globalMiddleware as $mw) {
            if (method_exists($mw, 'handle')) {
                $response = $mw->handle($request);
                if ($response instanceof Response) {
                    $response->send();
                    return;
                }
            }
        }

        // OPTIONS preflight — CorsMiddleware already set headers.
        if ($method === 'OPTIONS') {
            (new Response(204))->send();
            return;
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            // Extract named params.
            $params = [];
            foreach ($route['params'] as $name) {
                if (isset($matches[$name])) {
                    $params[$name] = $matches[$name];
                }
            }

            // Route-specific middleware.
            foreach ($route['middleware'] as $mw) {
                if (method_exists($mw, 'handle')) {
                    $response = $mw->handle($request, $route);
                    if ($response instanceof Response) {
                        $response->send();
                        return;
                    }
                }
            }

            // Resolve controller and method.
            [$controllerClass, $controllerMethod] = $route['handler'];

            if (!class_exists($controllerClass)) {
                throw AppException::serverError(sprintf('Controller %s not found.', $controllerClass));
            }

            $controller = $this->container->get($controllerClass);

            if (!method_exists($controller, $controllerMethod)) {
                throw AppException::serverError(sprintf('Method %s::%s not found.', $controllerClass, $controllerMethod));
            }

            // Call the controller.
            $response = $controller->{$controllerMethod}($request, $params);

            if ($response instanceof Response) {
                $response->send();
                return;
            }

            // A controller may also return a plain array/scalar; wrap it.
            (new Response(200, $response))->send();
            return;
        }

        throw AppException::notFound('Route not found.');
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Convert a URI pattern like `/projects/{id}` into a PCRE regex and the
     * list of parameter names.
     *
     * @return array{0:string,1:array<int,string>}
     */
    private function compilePattern(string $pattern): array
    {
        $params = [];

        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            static function (array $m) use (&$params): string {
                $params[] = $m[1];
                // Accept numbers, uuids, and short slugs.
                return '(?P<' . $m[1] . '>[A-Za-z0-9_\-]+)';
            },
            $pattern
        );

        $regex = '#^' . $regex . '$#';

        return [$regex, $params];
    }

    /**
     * Strip the query string and any trailing slash (except the root) so that
     * `/projects` and `/projects/` are treated identically.
     */
    private function normalizePath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH);
        if ($path === false || $path === null) {
            $path = '/';
        }

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        return $path;
    }
}