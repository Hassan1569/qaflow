<?php
declare(strict_types=1);

/**
 * QAFlow — Authentication middleware.
 *
 * Validates the Authorization header, loads the user by bearer token, and
 * attaches both to the request. Used on every protected route.
 *
 * Not registered globally: only routes that opt in require authentication.
 * The Router passes the route definition so the middleware can decide.
 */

namespace QAFlow\Middleware;

use QAFlow\Config\Database;
use QAFlow\Core\AppException;
use QAFlow\Core\Container;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;

final class AuthMiddleware
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param array<string,mixed> $route
     */
    public function handle(Request $request, array $route = []): ?Response
    {
        $token = $this->extractToken($request);

        if ($token === '') {
            throw AppException::unauthorized('Authentication required.');
        }

        $row = $this->loadTokenRow($token);

        if ($row === null) {
            throw AppException::unauthorized('Invalid or expired token.');
        }

        if (strtotime((string) $row['expires_at']) < time()) {
            $this->deleteToken($token);
            throw AppException::unauthorized('Invalid or expired token.');
        }

        if ((int) $row['is_active'] !== 1) {
            throw AppException::forbidden('Account is inactive.');
        }

        $user = [
            'id'    => (int) $row['id'],
            'name'  => (string) $row['name'],
            'email' => (string) $row['email'],
            'role'  => (string) $row['role'],
        ];

        $request->setUser($user);
        $request->setAttribute('auth_token', $token);

        return null;
    }

    private function extractToken(Request $request): string
    {
        $header = $request->header('Authorization');

        if ($header === null || $header === '') {
            return '';
        }

        if (stripos($header, 'Bearer ') !== 0) {
            return '';
        }

        return trim(substr($header, 7));
    }

    /**
     * @return array<string,mixed>|null
     */
    private function loadTokenRow(string $token): ?array
    {
        $pdo = Database::connection();

        $sql = 'SELECT u.id, u.name, u.email, u.role, u.is_active, t.expires_at
                FROM auth_tokens t
                INNER JOIN users u ON u.id = t.user_id
                WHERE t.token = :token
                LIMIT 1';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    private function deleteToken(string $token): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('DELETE FROM auth_tokens WHERE token = :token');
        $stmt->execute([':token' => $token]);
    }
}