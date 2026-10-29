<?php
declare(strict_types=1);

/**
 * QAFlow — Report controller.
 *
 * HTTP boundary for the four required reports. Business logic lives in
 * ReportService. Reports are project-scoped; CSV export is generated on the
 * client from the same JSON to avoid duplicating business logic on the server.
 */

namespace QAFlow\Controllers;

use QAFlow\Core\AppException;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;
use QAFlow\Services\ReportService;

final class ReportController
{
    private ReportService $reports;

    public function __construct(ReportService $reports)
    {
        $this->reports = $reports;
    }

    /**
     * GET /api/reports/execution?project_id=1
     */
    public function execution(Request $request): Response
    {
        $user      = $this->requireUser($request);
        $projectId = $this->requireProjectId($request);

        $report = $this->reports->execution($projectId, $user['id'], $user['role']);

        return Response::json($report);
    }

    /**
     * GET /api/reports/defects?project_id=1
     */
    public function defects(Request $request): Response
    {
        $user      = $this->requireUser($request);
        $projectId = $this->requireProjectId($request);

        $report = $this->reports->defects($projectId, $user['id'], $user['role']);

        return Response::json($report);
    }

    /**
     * GET /api/reports/coverage?project_id=1
     */
    public function coverage(Request $request): Response
    {
        $user      = $this->requireUser($request);
        $projectId = $this->requireProjectId($request);

        $report = $this->reports->coverage($projectId, $user['id'], $user['role']);

        return Response::json($report);
    }

    /**
     * GET /api/reports/project-summary?project_id=1
     */
    public function projectSummary(Request $request): Response
    {
        $user      = $this->requireUser($request);
        $projectId = $this->requireProjectId($request);

        $summary = $this->reports->projectSummary($projectId, $user['id'], $user['role']);

        return Response::json($summary);
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

    private function requireProjectId(Request $request): int
    {
        $id = (int) ($request->query('project_id') ?? 0);
        if ($id < 1) {
            throw AppException::validation('Validation failed.', [
                'project_id' => ['Required.'],
            ]);
        }
        return $id;
    }
}