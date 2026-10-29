<?php
declare(strict_types=1);

/**
 * QAFlow — Traceability controller.
 *
 * HTTP boundary for the requirement traceability matrix. Business logic
 * lives in TraceabilityService.
 */

namespace QAFlow\Controllers;

use QAFlow\Core\AppException;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;
use QAFlow\Services\TraceabilityService;

final class TraceabilityController
{
    private TraceabilityService $traceability;

    public function __construct(TraceabilityService $traceability)
    {
        $this->traceability = $traceability;
    }

    /**
     * GET /api/traceability/matrix?project_id=1
     */
    public function matrix(Request $request): Response
    {
        $user = $this->requireUser($request);

        $projectId = (int) ($request->query('project_id') ?? 0);
        if ($projectId < 1) {
            throw AppException::validation('Validation failed.', [
                'project_id' => ['Required.'],
            ]);
        }

        $matrix = $this->traceability->matrix($projectId, $user['id'], $user['role']);

        return Response::json($matrix);
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