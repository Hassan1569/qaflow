<?php
declare(strict_types=1);

/**
 * QAFlow — Authentication service.
 *
 * Owns the login flow: credential verification, token issuance, logout, and
 * profile lookup. Token generation is delegated to Uuid::token(); storage is
 * in `auth_tokens` via UserRepository.
 */

namespace QAFlow\Services;

use QAFlow\Config\Config;
use QAFlow\Config\Database;
use QAFlow\Core\AppException;
use QAFlow\Helpers\Uuid;
use QAFlow\Repositories\UserRepository;

final class AuthService
{
    private UserRepository $users;
    private ActivityService $activity;

    public function __construct(UserRepository $users, ActivityService $activity)
    {
        $this->users    = $users;
        $this->activity = $activity;
    }

    /**
     * Authenticate a user and issue a token.
     *
     * @return array{token:string,expires_at:string,user:array<string,mixed>}
     */
    public function login(string $email, string $password, string $ip): array
    {
        $email    = strtolower(trim($email));
        $password = (string) $password;

        if ($email === '' || $password === '') {
            throw AppException::validation('Email and password are required.', [
                'email'    => $email === '' ? ['Required'] : [],
                'password' => $password === '' ? ['Required'] : [],
            ]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw AppException::validation('Email format is invalid.', [
                'email' => ['Invalid email address'],
            ]);
        }

        $user = $this->users->findByEmail($email);

        // Generic message for any credential failure. Never reveal whether the
        // email exists.
        $generic = 'Invalid email or password.';

        if ($user === null) {
            $this->recordAttempt($ip, $email);
            throw AppException::unauthorized($generic);
        }

        if ((int) $user['is_active'] !== 1) {
            $this->recordAttempt($ip, $email);
            throw AppException::unauthorized($generic);
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            $this->recordAttempt($ip, $email);
            throw AppException::unauthorized($generic);
        }

        // Rehash if the algorithm or cost has changed.
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_BCRYPT)) {
            $newHash = password_hash($password, PASSWORD_BCRYPT);
            if ($newHash !== false) {
                $this->users->updatePasswordHash((int) $user['id'], $newHash);
            }
        }

        // Issue a token.
        $token     = Uuid::token(32);
        $ttlHours  = max(1, Config::int('TOKEN_TTL_HOURS', 24));
        $expiresAt = date('Y-m-d H:i:s', time() + ($ttlHours * 3600));

        $this->users->storeToken((int) $user['id'], $token, $expiresAt);

        $this->activity->record(
            (int) $user['id'],
            null,
            'auth.login',
            'user',
            (int) $user['id'],
            ['ip' => $ip]
        );

        return [
            'token'      => $token,
            'expires_at' => $expiresAt,
            'user'       => [
                'id'    => (int) $user['id'],
                'name'  => (string) $user['name'],
                'email' => (string) $user['email'],
                'role'  => (string) $user['role'],
            ],
        ];
    }

    /**
     * Invalidate the current token.
     */
    public function logout(string $token): void
    {
        if (!Uuid::isToken($token)) {
            // Nothing to do; token format is wrong.
            return;
        }

        $this->users->deleteToken($token);
    }

    /**
     * Return the authenticated user's full profile, including project
     * memberships and role.
     *
     * @return array<string,mixed>
     */
    public function profile(int $userId): array
    {
        $user = $this->users->findById($userId);
        if ($user === null) {
            throw AppException::unauthorized('Authentication required.');
        }

        $memberships = $this->users->projectMemberships($userId);

        return [
            'id'          => (int) $user['id'],
            'name'        => (string) $user['name'],
            'email'       => (string) $user['email'],
            'role'        => (string) $user['role'],
            'is_active'   => (int) $user['is_active'] === 1,
            'created_at'  => (string) $user['created_at'],
            'memberships' => $memberships,
        ];
    }

    /**
     * Record a failed attempt. The RateLimitMiddleware has already stored one
     * row on each request, but the email field of that row is empty. This
     * updates it so audits can see which emails were targeted.
     */
    private function recordAttempt(string $ip, string $email): void
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE login_attempts
             SET email = :email
             WHERE ip = :ip
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute([':email' => substr($email, 0, 160), ':ip' => $ip]);
    }
}