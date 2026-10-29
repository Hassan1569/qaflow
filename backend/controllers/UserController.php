<?php
declare(strict_types=1);

/**
 * QAFlow — User controller.
 *
 * Admin endpoints for managing users. The list endpoint is available to
 * admins and QA leads (needed for assignment dropdowns). All other mutations
 * are admin-only and enforced by the route table.
 */

namespace QAFlow\Controllers;

use QAFlow\Core\AppException;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;
use QAFlow\Repositories\UserRepository;

final class UserController
{
    private UserRepository $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * GET /api/users
     *
     * Query params:
     *   search, role, is_active, page, per_page, options (bool)
     */
    public function index(Request $request): Response
    {
        // A lightweight "options" mode used by dropdowns.
        if ($request->query('options') === '1' || $request->query('options') === 'true') {
            return Response::json($this->users->activeOptions());
        }

        $page    = max(1, (int) ($request->query('page') ?? 1));
        $perPage = max(1, min(100, (int) ($request->query('per_page') ?? 25)));

        $filters = [
            'search'    => trim((string) ($request->query('search') ?? '')),
            'role'      => trim((string) ($request->query('role') ?? '')),
            'is_active' => $request->query('is_active'),
        ];

        $result = $this->users->paginate($filters, $page, $perPage);

        return Response::paginated($result['items'], $page, $perPage, $result['total']);
    }

    /**
     * GET /api/users/{id}
     */
    public function show(Request $request, array $params): Response
    {
        $id   = (int) ($params['id'] ?? 0);
        $user = $this->users->findById($id);

        if ($user === null) {
            throw AppException::notFound('User not found.');
        }

        unset($user['password_hash']);

        return Response::json($user);
    }

    /**
     * POST /api/users
     *
     * Body: name, email, password, role, is_active
     */
    public function store(Request $request): Response
    {
        $data = $request->json();

        $errors = [];
        $name     = trim((string) ($data['name'] ?? ''));
        $email    = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        $role     = (string) ($data['role'] ?? 'viewer');

        if ($name === '') {
            $errors['name'][] = 'Required.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'A valid email is required.';
        }
        if (strlen($password) < 8) {
            $errors['password'][] = 'Password must be at least 8 characters.';
        }
        if (!in_array($role, ['admin', 'qa_lead', 'qa_engineer', 'developer', 'viewer'], true)) {
            $errors['role'][] = 'Invalid role.';
        }

        if ($errors !== []) {
            throw AppException::validation('Validation failed.', $errors);
        }

        if ($this->users->findByEmail($email) !== null) {
            throw AppException::validation('Validation failed.', [
                'email' => ['This email is already registered.'],
            ]);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        if ($hash === false) {
            throw AppException::serverError('Failed to hash password.');
        }

        $id = $this->users->create([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => $hash,
            'role'          => $role,
            'is_active'     => isset($data['is_active']) ? (int) $data['is_active'] : 1,
        ]);

        $user = $this->users->findById($id);
        if ($user === null) {
            throw AppException::serverError('Failed to load user after create.');
        }
        unset($user['password_hash']);

        return Response::created($user);
    }

    /**
     * PUT /api/users/{id}
     *
     * Body may include: name, email, role, is_active, password (optional).
     */
    public function update(Request $request, array $params): Response
    {
        $id   = (int) ($params['id'] ?? 0);
        $user = $this->users->findById($id);
        if ($user === null) {
            throw AppException::notFound('User not found.');
        }

        $data   = $request->json();
        $errors = [];

        if (isset($data['name'])) {
            $name = trim((string) $data['name']);
            if ($name === '') {
                $errors['name'][] = 'Required.';
            } else {
                $data['name'] = $name;
            }
        }

        if (isset($data['email'])) {
            $email = strtolower(trim((string) $data['email']));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'][] = 'A valid email is required.';
            } else {
                $other = $this->users->findByEmail($email);
                if ($other !== null && (int) $other['id'] !== $id) {
                    $errors['email'][] = 'This email is already in use.';
                }
                $data['email'] = $email;
            }
        }

        if (isset($data['role']) && !in_array($data['role'], ['admin', 'qa_lead', 'qa_engineer', 'developer', 'viewer'], true)) {
            $errors['role'][] = 'Invalid role.';
        }

        if (isset($data['is_active'])) {
            $data['is_active'] = (int) $data['is_active'] === 1 ? 1 : 0;
        }

        if ($errors !== []) {
            throw AppException::validation('Validation failed.', $errors);
        }

        if (!empty($data['password'])) {
            $pw = (string) $data['password'];
            if (strlen($pw) < 8) {
                throw AppException::validation('Validation failed.', [
                    'password' => ['Password must be at least 8 characters.'],
                ]);
            }
            $hash = password_hash($pw, PASSWORD_BCRYPT);
            if ($hash !== false) {
                $this->users->updatePasswordHash($id, $hash);
            }
        }

        $update = [];
        foreach (['name', 'email', 'role', 'is_active'] as $key) {
            if (array_key_exists($key, $data)) {
                $update[$key] = $data[$key];
            }
        }

        if ($update !== []) {
            $this->users->update($id, $update);
        }

        $fresh = $this->users->findById($id);
        if ($fresh === null) {
            throw AppException::serverError('Failed to reload user.');
        }
        unset($fresh['password_hash']);

        return Response::json($fresh);
    }

    /**
     * POST /api/users/{id}/deactivate
     */
    public function deactivate(Request $request, array $params): Response
    {
        $id   = (int) ($params['id'] ?? 0);
        $user = $this->users->findById($id);
        if ($user === null) {
            throw AppException::notFound('User not found.');
        }

        $me = $request->userId();
        if ($me !== null && $me === $id) {
            throw AppException::validation('Validation failed.', [
                'id' => ['You cannot deactivate your own account.'],
            ]);
        }

        $this->users->deactivate($id);

        return Response::noContent();
    }
}