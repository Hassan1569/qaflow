<?php
declare(strict_types=1);

/**
 * QAFlow — Test suite controller.
 *
 * HTTP boundary for test suites. Business logic lives in TestCaseService
 * (which already owns suite-related invariants) but the controller exposes
 * the suite-specific endpoints. Direct SQL is not allowed here.
 */

namespace QAFlow\Controllers;

use QAFlow\Core\AppException;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;
use QAFlow\Repositories\ProjectRepository;
use QAFlow\Repositories\TestSuiteRepository;

final class TestSuiteController
{
    private TestSuiteRepository $suites;
    private ProjectRepository $projects;

    public function __construct(TestSuiteRepository $suites, ProjectRepository $projects)
    {
        $this->suites   = $suites;
        $this->projects = $projects;
    }

    /**
     * GET /api/test-suites
     *
     * Query params: project_id, search, tree (bool), page, per_page
     */
    public function index(Request $request): Response
    {
        $user = $this->requireUser($request);

        $projectId = (int) ($request->query('project_id') ?? 0);
        if ($projectId > 0) {
            $this->assertCanRead($projectId, $user['id'], $user['role']);
        }

        // Tree mode: return a flat list ordered for rendering as a tree.
        if ($request->query('tree') === '1' || $request->query('tree') === 'true') {
            if ($projectId < 1) {
                throw AppException::validation('Validation failed.', [
                    'project_id' => ['Required for tree mode.'],
                ]);
            }
            return Response::json($this->suites->tree($projectId));
        }

        $page    = max(1, (int) ($request->query('page') ?? 1));
        $perPage = max(1, min(100, (int) ($request->query('per_page') ?? 25)));

        $filters = [
            'project_id' => $projectId > 0 ? $projectId : null,
            'search'     => trim((string) ($request->query('search') ?? '')),
        ];

        $result = $this->suites->paginate($filters, $page, $perPage);

        return Response::paginated($result['items'], $page, $perPage, $result['total']);
    }

    /**
     * POST /api/test-suites
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
        if ($name === '') {
            throw AppException::validation('Validation failed.', [
                'name' => ['Required.'],
            ]);
        }
        if (mb_strlen($name) > 160) {
            throw AppException::validation('Validation failed.', [
                'name' => ['Name is too long.'],
            ]);
        }

        $parentId = null;
        if (!empty($data['parent_id'])) {
            $parent = $this->suites->findById((int) $data['parent_id']);
            if ($parent === null || (int) $parent['project_id'] !== $projectId) {
                throw AppException::validation('Validation failed.', [
                    'parent_id' => ['Parent suite not found in this project.'],
                ]);
            }
            $parentId = (int) $data['parent_id'];
        }

        $id = $this->suites->create([
            'project_id'  => $projectId,
            'parent_id'   => $parentId,
            'name'        => $name,
            'description' => isset($data['description']) ? trim((string) $data['description']) : null,
        ]);

        $suite = $this->suites->findById($id);
        if ($suite === null) {
            throw AppException::serverError('Failed to load test suite after create.');
        }

        return Response::created($suite);
    }

    /**
     * PUT /api/test-suites/{id}
     */
    public function update(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $existing = $this->suites->findById($id);
        if ($existing === null) {
            throw AppException::notFound('Test suite not found.');
        }

        $this->assertCanWrite((int) $existing['project_id'], $user['id'], $user['role']);

        $data = $request->json();

        if (isset($data['name'])) {
            $name = trim((string) $data['name']);
            if ($name === '' || mb_strlen($name) > 160) {
                throw AppException::validation('Validation failed.', [
                    'name' => ['Invalid name.'],
                ]);
            }
            $data['name'] = $name;
        }

        if (array_key_exists('description', $data)) {
            $data['description'] = $data['description'] === null ? null : trim((string) $data['description']);
        }

        if (array_key_exists('parent_id', $data)) {
            $parentId = $data['parent_id'] !== null && $data['parent_id'] !== '' ? (int) $data['parent_id'] : null;

            if ($parentId !== null) {
                if ($this->suites->wouldCreateCycle($id, $parentId)) {
                    throw AppException::validation('Validation failed.', [
                        'parent_id' => ['This move would create a cycle in the suite tree.'],
                    ]);
                }
                $parent = $this->suites->findById($parentId);
                if ($parent === null || (int) $parent['project_id'] !== (int) $existing['project_id']) {
                    throw AppException::validation('Validation failed.', [
                        'parent_id' => ['Parent suite not found in this project.'],
                    ]);
                }
            }

            $data['parent_id'] = $parentId;
        }

        $this->suites->update($id, $data);

        $fresh = $this->suites->findById($id);
        if ($fresh === null) {
            throw AppException::serverError('Failed to reload suite.');
        }

        return Response::json($fresh);
    }

    /**
     * DELETE /api/test-suites/{id}
     *
     * Cascades to child suites. Any test case that referenced this suite has
     * its suite_id set to NULL (handled by FK ON DELETE SET NULL).
     */
    public function destroy(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $existing = $this->suites->findById($id);
        if ($existing === null) {
            throw AppException::notFound('Test suite not found.');
        }

        $this->assertCanWrite((int) $existing['project_id'], $user['id'], $user['role']);

        $this->suites->delete($id);

        return Response::noContent();
    }

    // -----------------------------------------------------------------------
    // Authorization helpers
    // -----------------------------------------------------------------------

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
            throw AppException::forbidden('You do not have permission to modify test suites.');
        }
        $projectRole = $this->projects->memberRole($projectId, $userId);
        if (!in_array($projectRole, ['admin', 'qa_lead'], true)) {
            throw AppException::forbidden('You do not have permission to modify test suites.');
        }
    }
}