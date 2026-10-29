<?php
declare(strict_types=1);

/**
 * QAFlow — Test case controller.
 *
 * HTTP boundary for test cases. Business logic lives in TestCaseService.
 */

namespace QAFlow\Controllers;

use QAFlow\Core\AppException;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;
use QAFlow\Services\TestCaseService;

final class TestCaseController
{
    private TestCaseService $cases;

    public function __construct(TestCaseService $cases)
    {
        $this->cases = $cases;
    }

    /**
     * GET /api/test-cases
     *
     * Query params: project_id, suite_id, search, priority, test_type,
     * automation_status, requirement_id, page, per_page
     */
    public function index(Request $request): Response
    {
        $user = $this->requireUser($request);

        $page    = max(1, (int) ($request->query('page') ?? 1));
        $perPage = max(1, min(100, (int) ($request->query('per_page') ?? 25)));

        $filters = [
            'project_id'        => $request->query('project_id'),
            'suite_id'          => $request->query('suite_id'),
            'search'            => trim((string) ($request->query('search') ?? '')),
            'priority'          => trim((string) ($request->query('priority') ?? '')),
            'test_type'         => trim((string) ($request->query('test_type') ?? '')),
            'automation_status' => trim((string) ($request->query('automation_status') ?? '')),
            'requirement_id'    => $request->query('requirement_id'),
        ];

        $result = $this->cases->list(
            $filters,
            $user['id'],
            $user['role'],
            $page,
            $perPage
        );

        return Response::paginated($result['items'], $page, $perPage, $result['total']);
    }

    /**
     * GET /api/test-cases/{id}
     */
    public function show(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $case = $this->cases->show($id, $user['id'], $user['role']);

        return Response::json($case);
    }

    /**
     * POST /api/test-cases
     */
    public function store(Request $request): Response
    {
        $user = $this->requireUser($request);

        $case = $this->cases->create(
            $request->json(),
            $user['id'],
            $user['role']
        );

        return Response::created($case);
    }

    /**
     * PUT /api/test-cases/{id}
     */
    public function update(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $case = $this->cases->update(
            $id,
            $request->json(),
            $user['id'],
            $user['role']
        );

        return Response::json($case);
    }

    /**
     * DELETE /api/test-cases/{id}
     */
    public function destroy(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $this->cases->delete($id, $user['id'], $user['role']);

        return Response::noContent();
    }

    /**
     * GET /api/test-cases/{id}/versions
     */
    public function versions(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $versions = $this->cases->versions($id, $user['id'], $user['role']);

        return Response::json($versions);
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
}