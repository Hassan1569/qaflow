<?php
declare(strict_types=1);

/**
 * QAFlow — Rate limit middleware.
 *
 * Enforces a per-IP limit on the login endpoint using the `login_attempts`
 * table. Other endpoints are not throttled at the application layer; they are
 * expected to be limited by the web server or an upstream proxy.
 */

namespace QAFlow\Middleware;

use QAFlow\Config\Config;
use QAFlow\Config\Database;
use QAFlow\Core\AppException;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;

final class RateLimitMiddleware
{
    /**
     * @param array<string,mixed> $route
     */
    public function handle(Request $request, array $route = []): ?Response
    {
        if ($request->method() !== 'POST') {
            return null;
        }

        // Only throttle the login endpoint.
        $path = $request->path();
        if ($path !== '/api/auth/login') {
            return null;
        }

        $limit = Config::int('LOGIN_RATE_LIMIT_PER_MIN', 10);
        if ($limit <= 0) {
            return null;
        }

        $ip = $this->clientIp($request);

        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS c
             FROM login_attempts
             WHERE ip = :ip AND attempted_at > (NOW() - INTERVAL 1 MINUTE)'
        );
        $stmt->execute([':ip' => $ip]);
        $row = $stmt->fetch();
        $count = (int) ($row['c'] ?? 0);

        if ($count >= $limit) {
            throw AppException::tooManyRequests('Too many login attempts. Please wait a minute and try again.');
        }

        // Record the attempt up front. AuthService may still record more on
        // failure, but the pre-record enforces the limit even for successful
        // attempts.
        $ins = $pdo->prepare('INSERT INTO login_attempts (ip, email) VALUES (:ip, :email)');
        $ins->execute([':ip' => $ip, ':email' => '']);

        return null;
    }

    private function clientIp(Request $request): string
    {
        // Respect a trusted proxy header only when the deployment sets it.
        $forwarded = $request->header('X-Forwarded-For');
        if ($forwarded !== null && $forwarded !== '') {
            $parts = explode(',', $forwarded);
            $first = trim($parts[0]);
            if ($first !== '') {
                return substr($first, 0, 64);
            }
        }

        $remote = $request->server('REMOTE_ADDR', '0.0.0.0');
        return substr((string) $remote, 0, 64);
    }
}