<?php
declare(strict_types=1);

/**
 * QAFlow — CORS middleware.
 *
 * Emits CORS headers for allowed origins and terminates OPTIONS preflight
 * requests with a 204. Origins are matched exactly against CORS_ORIGIN.
 */

namespace QAFlow\Middleware;

use QAFlow\Config\Cors;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;

final class CorsMiddleware
{
    /**
     * @param array<string,mixed> $route
     */
    public function handle(Request $request, array $route = []): ?Response
    {
        $origin = $request->header('Origin') ?? '';

        if (Cors::isAllowed($origin)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: ' . implode(', ', Cors::allowedMethods()));
            header('Access-Control-Allow-Headers: ' . implode(', ', Cors::allowedHeaders()));
            header('Access-Control-Expose-Headers: ' . implode(', ', Cors::exposedHeaders()));
            header('Access-Control-Max-Age: ' . Cors::maxAge());
        }

        if ($request->method() === 'OPTIONS') {
            // A route may not exist for OPTIONS; respond immediately.
            return new Response(204, null);
        }

        return null;
    }
}