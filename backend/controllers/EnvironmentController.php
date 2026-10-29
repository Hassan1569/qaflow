<?php
declare(strict_types=1);

/**
 * QAFlow — Environment controller.
 *
 * HTTP boundary for environments. Environments are project-scoped and
 * reusable across test runs. Business logic (project membership, ownership)
 * is enforced here against the project.
 */

namespace QAFlow\Controllers;

use QAFlow\Core\AppException;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;
use QAFlow\Repositories\EnvironmentRepository;
use QAFlow\Repositories\ProjectRepository;

final class EnvironmentController
{
    private EnvironmentRepository $environments;
    private ProjectRepository $projects;

    public function __construct(EnvironmentRepository $environments, ProjectRepository $projects)
    {
        $this->environments = $environments;
        $this->projects     = $projects;
    }

    /**
     * GET /api/environments
     *
     * Query params: project_id, search, page, per_page, options (bool)
     */
    public function index(Request $request): Response
    {
        $user      = $this->requireUser($request);
        $projectId = (int) ($request->query('project_id') ?? 0);

        if ($projectId > 0) {
            $this->assertCanRead($projectId, $user['id'], $user['role']);
        }

        // Lightweight options mode used by dropdowns.
        if ($request->query('options') === '1' || $request->query('options') === 'true') {
            if ($projectId < 1) {
                throw AppException::validation('Validation failed.', [
                    'project_id' => ['Required for options mode.'],
                ]);
            }
            return Response::json($this->environments->forProject($projectId));
        }

        $page    = max(1, (int) ($request->query('page') ?? 1));
        $perPage = max(1, min(100, (int) ($request->query('per_page') ?? 25)));

        $filters = [
            'project_id' => $projectId > 0 ? $projectId : null,
            'search'     => trim((string) ($request->query('search') ?? '')),
        ];

        $result = $this->environments->paginate($filters, $page, $perPage);

        return Response::paginated($result['items'], $page, $perPage, $result['total']);
    }

    /**
     * POST /api/environments
     */
    public function store(Request $request): Response
    {
        $user = $this->requireUser($request);
        $data = $request->json();

        $projectId = (int) ($data['project_id'] ?? 0);
        if ($projectId < 1) {
            throw AppException::validation('Validation failed.', [
                'project_id' => ['Required.'],
            ]);
        }

        $this->assertCanWrite($projectId, $user['id'], $user['role']);

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 80) {
            throw AppException::validation('Validation failed.', [
                'name' => ['A name of 1-80 characters is required.'],
            ]);
        }

        $id = $this->environments->create([
            'project_id'      => $projectId,
            'name'            => $name,
            'browser'         => $this->nullIfBlank($data['browser'] ?? null),
            'browser_version' => $this->nullIfBlank($data['browser_version'] ?? null),
            'os'              => $this->nullIfBlank($data['os'] ?? null),
            'os_version'      => $this->nullIfBlank($data['os_version'] ?? null),
            'device'          => $this->nullIfBlank($data['device'] ?? null),
            'device_version'  => $this->nullIfBlank($data['device_version'] ?? null),
            'app_version'     => $this->nullIfBlank($data['app_version'] ?? null),
            'notes'           => $this->nullIfBlank($data['notes'] ?? null),
        ]);

        $environment = $this->environments->findById($id);
        if ($environment === null) {
            throw AppException::serverError('Failed to load environment after create.');
        }

        return Response::created($environment);
    }

    /**
     * PUT /api/environments/{id}
     */
    public function update(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $existing = $this->environments->findById($id);
        if ($existing === null) {
            throw AppException::notFound('Environment not found.');
        }

        $this->assertCanWrite((int) $existing['project_id'], $user['id'], $user['role']);

        $data = $request->json();

        if (isset($data['name'])) {
            $name = trim((string) $data['name']);
            if ($name === '' || mb_strlen($name) > 80) {
                throw AppException::validation('Validation failed.', [
                    'name' => ['A name of 1-80 characters is required.'],
                ]);
            }
            $data['name'] = $name;
        }

        foreach (['browser', 'browser_version', 'os', 'os_version', 'device', 'device_version', 'app_version', 'notes'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $this->nullIfBlank($data[$field]);
            }
        }

        // Project cannot be reassigned via update.
        unset($data['project_id']);

        $this->environments->update($id, $data);

        $fresh = $this->environments->findById($id);
        if ($fresh === null) {
            throw AppException::serverError('Failed to reload environment.');
        }

        return Response::json($fresh);
    }

    /**
     * DELETE /api/environments/{id}
     */
    public function destroy(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $existing = $this->environments->findById($id);
        if ($existing === null) {
            throw AppException::notFound('Environment not found.');
        }

        $this->assertCanWrite((int) $existing['project_id'], $user['id'], $user['role']);

        $this->environments->delete($id);

        return Response::noContent();
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * @param mixed $value
     */
    private function nullIfBlank($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);
        return $s === '' ? null : $s;
    }

    /**
     * @return array{id:int,role:string}
     */
    private function requireUser(Request $request): array
    {
        $user = $request->user();
        if ($user === null) {
            throw AppException::unauthorized('Authentication required.');
        }

        return ['id' => (int) $user['id'], 'role' => (string) $user['role']];
    }

    private function assertCanRead(int $projectId, int $userId, string $role): void
    {
        if ($role === 'admin') {
            return;
        }
        if (!$this->projects->isMember($projectId, $userId)) {
            throw AppException::forbidden('You do not have access to this project.');
        }
    }

    private function assertCanWrite(int $projectId, int $userId, string $role): void
    {
        if ($role === 'admin') {
            return;
        }
        if ($role !== 'qa_lead') {
            throw AppException::forbidden('You do not have permission to modify environments.');
        }
        $projectRole = $this->projects->memberRole($projectId, $userId);
        if (!in_array($projectRole, ['admin', 'qa_lead'], true)) {
            throw AppException::forbidden('You do not have permission to modify environments.');
        }
    }
}