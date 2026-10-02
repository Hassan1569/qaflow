<?php
declare(strict_types=1);

/**
 * QAFlow — Authentication controller.
 *
 * Handles login, logout, current-user lookup, and the health endpoint.
 * Business logic lives in AuthService.
 */

namespace QAFlow\Controllers;

use QAFlow\Core\AppException;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;
use QAFlow\Services\AuthService;

final class AuthController
{
    private AuthService $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    /**
     * GET /api/health
     *
     * Cheap unauthenticated probe used by load balancers and uptime checks.
     */
    public function health(Request $request): Response
    {
        return Response::json([
            'status'  => 'ok',
            'service' => 'qaflow',
            'time'    => date('c'),
        ]);
    }

    /**
     * POST /api/auth/login
     *
     * Body: { email, password }
     */
    public function login(Request $request): Response
    {
        $email    = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');

        $result = $this->auth->login($email, $password, $this->clientIp($request));

        return Response::json([
            'token'      => $result['token'],
            'expires_at' => $result['expires_at'],
            'user'       => $result['user'],
        ]);
    }

    /**
     * POST /api/auth/logout
     *
     * Auth required. Deletes the current token.
     */
    public function logout(Request $request): Response
    {
        $token = (string) $request->attribute('auth_token', '');

        if ($token === '') {
            throw AppException::unauthorized('Authentication required.');
        }

        $this->auth->logout($token);

        return Response::noContent();
    }

    /**
     * GET /api/auth/me
     *
     * Auth required. Returns the authenticated user with project memberships.
     */
    public function me(Request $request): Response
    {
        $userId = $request->userId();
        if ($userId === null || $userId <= 0) {
            throw AppException::unauthorized('Authentication required.');
        }

        $profile = $this->auth->profile($userId);

        return Response::json($profile);
    }

    private function clientIp(Request $request): string
    {
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