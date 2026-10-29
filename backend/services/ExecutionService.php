<?php
declare(strict_types=1);

/**
 * QAFlow — Execution service.
 *
 * Business rules for running a test case. Saves per-step results and the
 * overall result. Computes the overall status from step results unless the
 * caller overrides it explicitly.
 */

namespace QAFlow\Services;

use QAFlow\Core\AppException;
use QAFlow\Repositories\TestCaseRepository;
use QAFlow\Repositories\TestResultRepository;
use QAFlow\Repositories\TestRunRepository;
use QAFlow\Validators\TestRunValidator;

final class ExecutionService
{
    private TestRunRepository $runs;
    private TestResultRepository $results;
    private TestCaseRepository $cases;
    private ActivityService $activity;

    public function __construct(
        TestRunRepository $runs,
        TestResultRepository $results,
        TestCaseRepository $cases,
        ActivityService $activity
    ) {
        $this->runs     = $runs;
        $this->results  = $results;
        $this->cases    = $cases;
        $this->activity = $activity;
    }

    /**
     * Full detail for the execution page.
     *
     * @return array<string,mixed>
     */
    public function show(int $runCaseId): array
    {
        $detail = $this->results->executionDetail($runCaseId);
        if ($detail === null) {
            throw AppException::notFound('Execution not found.');
        }

        $caseId = (int) $detail['test_case_id'];
        $steps  = $this->cases->steps($caseId);

        $resultId = $detail['result_id'] !== null ? (int) $detail['result_id'] : null;
        $stepResults = $resultId !== null ? $this->results->stepResults($resultId) : [];

        // Index step results by step_order for easy merging.
        $byOrder = [];
        foreach ($stepResults as $sr) {
            $byOrder[(int) $sr['step_order']] = $sr;
        }

        $mergedSteps = [];
        foreach ($steps as $step) {
            $order = (int) $step['step_order'];
            $sr    = $byOrder[$order] ?? null;

            $mergedSteps[] = [
                'step_order'      => $order,
                'action'          => (string) $step['action'],
                'expected_result' => (string) ($step['expected_result'] ?? ''),
                'status'          => $sr !== null ? (string) $sr['status'] : 'not_run',
                'actual_result'   => $sr !== null ? (string) ($sr['actual_result'] ?? '') : '',
                'notes'           => $sr !== null ? (string) ($sr['notes'] ?? '') : '',
            ];
        }

        return [
            'run_case' => [
                'id'               => (int) $detail['run_case_id'],
                'test_run_id'      => (int) $detail['test_run_id'],
                'test_case_id'     => $caseId,
                'environment_id'   => $detail['environment_id'] !== null ? (int) $detail['environment_id'] : null,
                'environment_name' => (string) ($detail['environment_name'] ?? ''),
                'assignee_id'      => $detail['assignee_id'] !== null ? (int) $detail['assignee_id'] : null,
                'assignee_name'    => (string) ($detail['assignee_name'] ?? ''),
            ],
            'run' => [
                'id'     => (int) $detail['run_id'],
                'name'   => (string) $detail['run_name'],
                'status' => (string) $detail['run_status'],
            ],
            'test_case' => [
                'id'            => $caseId,
                'code'          => (string) $detail['case_code'],
                'title'         => (string) $detail['case_title'],
                'description'   => (string) ($detail['case_description'] ?? ''),
                'preconditions' => (string) ($detail['case_preconditions'] ?? ''),
                'priority'      => (string) $detail['case_priority'],
                'severity'      => (string) $detail['case_severity'],
                'test_type'     => (string) $detail['case_type'],
            ],
            'result' => [
                'id'            => $resultId,
                'status'        => (string) ($detail['result_status'] ?? 'not_run'),
                'actual_result' => (string) ($detail['actual_result'] ?? ''),
                'notes'         => (string) ($detail['notes'] ?? ''),
                'executed_by'   => $detail['executed_by'] !== null ? (int) $detail['executed_by'] : null,
                'executed_by_name' => (string) ($detail['executed_by_name'] ?? ''),
                'executed_at'   => (string) ($detail['executed_at'] ?? ''),
                'duration_ms'   => $detail['duration_ms'] !== null ? (int) $detail['duration_ms'] : null,
            ],
            'steps' => $mergedSteps,
        ];
    }

    /**
     * Return the list of executions available in the run for the sidebar.
     *
     * @return array<int,array<string,mixed>>
     */
    public function available(int $runId): array
    {
        return $this->runs->cases($runId);
    }

    /**
     * Save a single step result. Returns the newly-computed overall status
     * (may be `not_run` if steps are incomplete).
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function saveStepResult(int $runCaseId, int $stepOrder, array $data, int $userId): array
    {
        TestRunValidator::validateStepResult($data);

        $detail = $this->results->executionDetail($runCaseId);
        if ($detail === null) {
            throw AppException::notFound('Execution not found.');
        }

        $resultId = $this->ensureResult($runCaseId, $userId, $detail);

        $this->results->saveStepResult(
            $resultId,
            $stepOrder,
            (string) $data['status'],
            isset($data['actual_result']) ? (string) $data['actual_result'] : null,
            isset($data['notes']) ? (string) $data['notes'] : null,
        );

        // Recompute overall status from step results (does not overwrite an
        // explicit override; the caller can still call saveResult()).
        $computed = $this->results->computedStatus($resultId);

        $this->results->upsertResult($runCaseId, [
            'status'      => $computed,
            'executed_by' => $userId,
            'executed_at' => date('Y-m-d H:i:s'),
        ]);

        $this->activity->record(
            $userId,
            (int) $detail['project_id'],
            'test_result.recorded',
            'test_result',
            $resultId,
            [
                'run_case_id' => $runCaseId,
                'step_order'  => $stepOrder,
                'status'      => (string) $data['status'],
            ]
        );

        return [
            'run_case_id' => $runCaseId,
            'result_id'   => $resultId,
            'step_order'  => $stepOrder,
            'status'      => (string) $data['status'],
            'overall'     => $computed,
        ];
    }

    /**
     * Save the overall result explicitly. This overrides whatever the step
     * computation would produce.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function saveResult(int $runCaseId, array $data, int $userId): array
    {
        TestRunValidator::validateResult($data);

        $detail = $this->results->executionDetail($runCaseId);
        if ($detail === null) {
            throw AppException::notFound('Execution not found.');
        }

        $resultId = $this->ensureResult($runCaseId, $userId, $detail);

        $this->results->upsertResult($runCaseId, [
            'status'        => (string) $data['status'],
            'actual_result' => isset($data['actual_result']) ? (string) $data['actual_result'] : null,
            'notes'         => isset($data['notes']) ? (string) $data['notes'] : null,
            'executed_by'   => $userId,
            'executed_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->activity->record(
            $userId,
            (int) $detail['project_id'],
            'test_result.updated',
            'test_result',
            $resultId,
            [
                'run_case_id' => $runCaseId,
                'status'      => (string) $data['status'],
            ]
        );

        return [
            'run_case_id' => $runCaseId,
            'result_id'   => $resultId,
            'status'      => (string) $data['status'],
        ];
    }

    /**
     * Ensure a test_results row exists for the run_case. Creates an empty one
     * (status = not_run) if the case has not been executed yet.
     *
     * @param array<string,mixed> $detail
     */
    private function ensureResult(int $runCaseId, int $userId, array $detail): int
    {
        $existing = $this->results->findByRunCase($runCaseId);
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        return $this->results->upsertResult($runCaseId, [
            'status'      => 'not_run',
            'executed_by' => $userId,
            'executed_at' => date('Y-m-d H:i:s'),
        ]);
    }
}