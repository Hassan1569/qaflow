<?php
declare(strict_types=1);

/**
 * QAFlow — Test run controller.
 *
 * HTTP boundary for test runs. Business logic lives in TestRunService.
 */

namespace QAFlow\Controllers;

use QAFlow\Core\AppException;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;
use QAFlow\Services\TestRunService;

final class TestRunController
{
    private TestRunService $runs;

    public function __construct(TestRunService $runs)
    {
        $this->runs = $runs;
    }

    /**
     * GET /api/test-runs
     *
     * Query params: project_id, plan_id, status, assigned_to, search, page, per_page
     */
    public function index(Request $request): Response
    {
        $user = $this->requireUser($request);

        $page    = max(1, (int) ($request->query('page') ?? 1));
        $perPage = max(1, min(100, (int) ($request->query('per_page') ?? 25)));

        $filters = [
            'project_id'  => $request->query('project_id'),
            'plan_id'     => $request->query('plan_id'),
            'status'      => trim((string) ($request->query('status') ?? '')),
            'assigned_to' => $request->query('assigned_to'),
            'search'      => trim((string) ($request->query('search') ?? '')),
        ];

        $result = $this->runs->list(
            $filters,
            $user['id'],
            $user['role'],
            $page,
            $perPage
        );

        return Response::paginated($result['items'], $page, $perPage, $result['total']);
    }

    /**
     * GET /api/test-runs/{id}
     */
    public function show(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $run = $this->runs->show($id, $user['id'], $user['role']);

        return Response::json($run);
    }

    /**
     * POST /api/test-runs
     */
    public function store(Request $request): Response
    {
        $user = $this->requireUser($request);

        $run = $this->runs->create(
            $request->json(),
            $user['id'],
            $user['role']
        );

        return Response::created($run);
    }

    /**
     * POST /api/test-runs/{id}/start
     */
    public function start(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $this->runs->start($id, $user['id'], $user['role']);

        return Response::noContent();
    }

    /**
     * POST /api/test-runs/{id}/complete
     */
    public function complete(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $this->runs->complete($id, $user['id'], $user['role']);

        return Response::noContent();
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