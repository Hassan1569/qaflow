<?php
declare(strict_types=1);

/**
 * QAFlow — Test execution controller.
 *
 * HTTP boundary for the execution screen. Business logic lives in
 * ExecutionService.
 */

namespace QAFlow\Controllers;

use QAFlow\Core\AppException;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;
use QAFlow\Services\ExecutionService;

final class TestExecutionController
{
    private ExecutionService $executions;

    public function __construct(ExecutionService $executions)
    {
        $this->executions = $executions;
    }

    /**
     * GET /api/executions/{id}
     *
     * Returns the full execution detail: run, run_case, test case, merged
     * steps, and the current overall result.
     */
    public function show(Request $request, array $params): Response
    {
        $this->requireUser($request);
        $id = (int) ($params['id'] ?? 0);

        return Response::json($this->executions->show($id));
    }

    /**
     * GET /api/executions/{id}/available
     *
     * Returns the sibling executions in the same run, used by the sidebar.
     */
    public function available(Request $request, array $params): Response
    {
        $this->requireUser($request);
        $runCaseId = (int) ($params['id'] ?? 0);

        $detail = $this->executions->show($runCaseId);

        return Response::json($this->executions->available((int) $detail['run']['id']));
    }

    /**
     * POST /api/executions/{id}/steps/{stepId}/result
     *
     * Body: { status, actual_result?, notes? }
     */
    public function saveStepResult(Request $request, array $params): Response
    {
        $user      = $this->requireUser($request);
        $runCaseId = (int) ($params['id'] ?? 0);
        $stepOrder = (int) ($params['stepId'] ?? 0);

        $result = $this->executions->saveStepResult(
            $runCaseId,
            $stepOrder,
            $request->json(),
            $user['id']
        );

        return Response::json($result);
    }

    /**
     * POST /api/executions/{id}/result
     *
     * Body: { status, actual_result?, notes? }
     *
     * Sets the overall result explicitly, overriding step-derived status.
     */
    public function saveResult(Request $request, array $params): Response
    {
        $user      = $this->requireUser($request);
        $runCaseId = (int) ($params['id'] ?? 0);

        $result = $this->executions->saveResult(
            $runCaseId,
            $request->json(),
            $user['id']
        );

        return Response::json($result);
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