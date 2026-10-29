<?php
declare(strict_types=1);

/**
 * QAFlow — Defect controller.
 *
 * HTTP boundary for defects. Business logic lives in DefectService.
 */

namespace QAFlow\Controllers;

use QAFlow\Core\AppException;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;
use QAFlow\Services\DefectService;

final class DefectController
{
    private DefectService $defects;

    public function __construct(DefectService $defects)
    {
        $this->defects = $defects;
    }

    /**
     * GET /api/defects
     *
     * Query params: project_id, search, status, severity, priority,
     * assignee_id, test_case_id, requirement_id, page, per_page
     */
    public function index(Request $request): Response
    {
        $user = $this->requireUser($request);

        $page    = max(1, (int) ($request->query('page') ?? 1));
        $perPage = max(1, min(100, (int) ($request->query('per_page') ?? 25)));

        $filters = [
            'project_id'     => $request->query('project_id'),
            'search'         => trim((string) ($request->query('search') ?? '')),
            'status'         => trim((string) ($request->query('status') ?? '')),
            'severity'       => trim((string) ($request->query('severity') ?? '')),
            'priority'       => trim((string) ($request->query('priority') ?? '')),
            'assignee_id'    => $request->query('assignee_id'),
            'test_case_id'   => $request->query('test_case_id'),
            'requirement_id' => $request->query('requirement_id'),
        ];

        $result = $this->defects->list(
            $filters,
            $user['id'],
            $user['role'],
            $page,
            $perPage
        );

        return Response::paginated($result['items'], $page, $perPage, $result['total']);
    }

    /**
     * GET /api/defects/{id}
     */
    public function show(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $defect = $this->defects->show($id, $user['id'], $user['role']);

        return Response::json($defect);
    }

    /**
     * POST /api/defects
     */
    public function store(Request $request): Response
    {
        $user = $this->requireUser($request);

        $defect = $this->defects->create(
            $request->json(),
            $user['id'],
            $user['role']
        );

        return Response::created($defect);
    }

    /**
     * PUT /api/defects/{id}
     */
    public function update(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $defect = $this->defects->update(
            $id,
            $request->json(),
            $user['id'],
            $user['role']
        );

        return Response::json($defect);
    }

    /**
     * POST /api/defects/{id}/status
     *
     * Body: { status }
     */
    public function status(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $this->defects->changeStatus(
            $id,
            $request->json(),
            $user['id'],
            $user['role']
        );

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