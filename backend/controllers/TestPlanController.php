<?php
declare(strict_types=1);

/**
 * QAFlow — Test plan controller.
 *
 * HTTP boundary for test plans. Business logic lives in TestPlanService.
 */

namespace QAFlow\Controllers;

use QAFlow\Core\AppException;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;
use QAFlow\Services\TestPlanService;

final class TestPlanController
{
    private TestPlanService $plans;

    public function __construct(TestPlanService $plans)
    {
        $this->plans = $plans;
    }

    /**
     * GET /api/test-plans
     *
     * Query params: project_id, status, search, page, per_page
     */
    public function index(Request $request): Response
    {
        $user = $this->requireUser($request);

        $page    = max(1, (int) ($request->query('page') ?? 1));
        $perPage = max(1, min(100, (int) ($request->query('per_page') ?? 25)));

        $filters = [
            'project_id' => $request->query('project_id'),
            'status'     => trim((string) ($request->query('status') ?? '')),
            'search'     => trim((string) ($request->query('search') ?? '')),
        ];

        $result = $this->plans->list(
            $filters,
            $user['id'],
            $user['role'],
            $page,
            $perPage
        );

        return Response::paginated($result['items'], $page, $perPage, $result['total']);
    }

    /**
     * GET /api/test-plans/{id}
     */
    public function show(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $plan = $this->plans->show($id, $user['id'], $user['role']);

        return Response::json($plan);
    }

    /**
     * POST /api/test-plans
     */
    public function store(Request $request): Response
    {
        $user = $this->requireUser($request);

        $plan = $this->plans->create(
            $request->json(),
            $user['id'],
            $user['role']
        );

        return Response::created($plan);
    }

    /**
     * PUT /api/test-plans/{id}
     */
    public function update(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $plan = $this->plans->update(
            $id,
            $request->json(),
            $user['id'],
            $user['role']
        );

        return Response::json($plan);
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