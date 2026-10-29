<?php
declare(strict_types=1);

/**
 * QAFlow — Dashboard controller.
 *
 * HTTP boundary for dashboard metrics and charts. Business logic lives in
 * DashboardService. The project_id is optional; when absent, the response
 * aggregates across all projects the caller can access.
 */

namespace QAFlow\Controllers;

use QAFlow\Core\AppException;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;
use QAFlow\Services\DashboardService;

final class DashboardController
{
    private DashboardService $dashboard;

    public function __construct(DashboardService $dashboard)
    {
        $this->dashboard = $dashboard;
    }

    /**
     * GET /api/dashboard/summary
     *
     * Query params: project_id (optional)
     */
    public function summary(Request $request): Response
    {
        $user = $this->requireUser($request);

        $projectId = $request->query('project_id');
        $pid       = ($projectId !== null && $projectId !== '') ? (int) $projectId : null;

        $summary = $this->dashboard->summary($user['id'], $user['role'], $pid);

        return Response::json($summary);
    }

    /**
     * GET /api/dashboard/charts
     *
     * Requires project_id, since the charts are project-scoped.
     */
    public function charts(Request $request): Response
    {
        $user = $this->requireUser($request);

        $projectId = (int) ($request->query('project_id') ?? 0);
        if ($projectId < 1) {
            throw AppException::validation('Validation failed.', [
                'project_id' => ['Required for charts.'],
            ]);
        }

        $charts = $this->dashboard->charts($user['id'], $user['role'], $projectId);

        return Response::json($charts);
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