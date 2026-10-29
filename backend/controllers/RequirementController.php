<?php
declare(strict_types=1);

/**
 * QAFlow — Requirement controller.
 *
 * HTTP boundary for requirements. Business logic lives in RequirementService.
 */

namespace QAFlow\Controllers;

use QAFlow\Core\AppException;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;
use QAFlow\Services\RequirementService;

final class RequirementController
{
    private RequirementService $requirements;

    public function __construct(RequirementService $requirements)
    {
        $this->requirements = $requirements;
    }

    /**
     * GET /api/requirements
     *
     * Query params: project_id, search, status, priority, page, per_page
     */
    public function index(Request $request): Response
    {
        $user = $this->requireUser($request);

        $page    = max(1, (int) ($request->query('page') ?? 1));
        $perPage = max(1, min(100, (int) ($request->query('per_page') ?? 25)));

        $filters = [
            'project_id' => $request->query('project_id'),
            'search'     => trim((string) ($request->query('search') ?? '')),
            'status'     => trim((string) ($request->query('status') ?? '')),
            'priority'   => trim((string) ($request->query('priority') ?? '')),
        ];

        $result = $this->requirements->list(
            $filters,
            $user['id'],
            $user['role'],
            $page,
            $perPage
        );

        return Response::paginated($result['items'], $page, $perPage, $result['total']);
    }

    /**
     * GET /api/requirements/{id}
     */
    public function show(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $requirement = $this->requirements->show($id, $user['id'], $user['role']);

        return Response::json($requirement);
    }

    /**
     * POST /api/requirements
     */
    public function store(Request $request): Response
    {
        $user = $this->requireUser($request);

        $requirement = $this->requirements->create(
            $request->json(),
            $user['id'],
            $user['role']
        );

        return Response::created($requirement);
    }

    /**
     * PUT /api/requirements/{id}
     */
    public function update(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $requirement = $this->requirements->update(
            $id,
            $request->json(),
            $user['id'],
            $user['role']
        );

        return Response::json($requirement);
    }

    /**
     * DELETE /api/requirements/{id}
     */
    public function destroy(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $this->requirements->delete($id, $user['id'], $user['role']);

        return Response::noContent();
    }

    /**
     * GET /api/requirements/{id}/test-cases
     */
    public function testCases(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $cases = $this->requirements->testCases($id, $user['id'], $user['role']);

        return Response::json($cases);
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

        return [
            'id'   => (int) $user['id'],
            'role' => (string) $user['role'],
        ];
    }
}