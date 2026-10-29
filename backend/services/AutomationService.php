<?php
declare(strict_types=1);

/**
 * QAFlow — Automation ingestion service.
 *
 * Accepts external test results (e.g. from Playwright or JUnit XML converted
 * to JSON) and records them against an existing test run. Test cases are
 * matched by `external_id`. This is the foundation for future CI/CD
 * integration; it does not execute tests.
 */

namespace QAFlow\Services;

use QAFlow\Core\AppException;
use QAFlow\Repositories\TestCaseRepository;
use QAFlow\Repositories\TestResultRepository;
use QAFlow\Repositories\TestRunRepository;

final class AutomationService
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
     * Ingest a batch of automated results into a test run.
     *
     * Each item in `$payload['results']` must contain:
     *   - external_id (string): the QAFlow test case's external_id
     *   - status (string): pass | fail | blocked | not_run
     *   - duration_ms (int, optional)
     *   - steps (array, optional): [{ step_order, status, actual_result?, notes? }]
     *
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function ingest(array $payload, int $apiProjectId): array
    {
        if (!isset($payload['test_run_id']) || (int) $payload['test_run_id'] < 1) {
            throw AppException::validation('Validation failed.', [
                'test_run_id' => ['Required.'],
            ]);
        }

        if (!isset($payload['results']) || !is_array($payload['results']) || $payload['results'] === []) {
            throw AppException::validation('Validation failed.', [
                'results' => ['At least one result is required.'],
            ]);
        }

        $runId = (int) $payload['test_run_id'];
        $run   = $this->runs->findById($runId);

        if ($run === null) {
            throw AppException::notFound('Test run not found.');
        }

        if ((int) $run['project_id'] !== $apiProjectId) {
            throw AppException::forbidden('API token does not grant access to this project.');
        }

        if ($run['status'] === 'completed') {
            throw AppException::validation('Validation failed.', [
                'test_run_id' => ['Cannot ingest into a completed run.'],
            ]);
        }

        $recorded = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($payload['results'] as $index => $raw) {
            if (!is_array($raw)) {
                $errors[] = ["Result at index $index is not an object."];
                $skipped++;
                continue;
            }

            $externalId = trim((string) ($raw['external_id'] ?? ''));
            if ($externalId === '') {
                $errors[] = ["Result at index $index is missing external_id."];
                $skipped++;
                continue;
            }

            $status = (string) ($raw['status'] ?? '');
            if (!in_array($status, ['pass', 'fail', 'blocked', 'not_run'], true)) {
                $errors[] = ["Result at index $index has an invalid status."];
                $skipped++;
                continue;
            }

            $testCase = $this->cases->findByExternalId($run['project_id'], $externalId);
            if ($testCase === null) {
                $errors[] = ["Test case with external_id '$externalId' not found."];
                $skipped++;
                continue;
            }

            $runCase = $this->runs->findRunCaseByCaseId($runId, (int) $testCase['id']);
            if ($runCase === null) {
                // Attach automatically to the run so the result is not lost.
                $envId = $run['environment_id'] !== null ? (int) $run['environment_id'] : null;
                $this->runs->addCase($runId, (int) $testCase['id'], $envId, null);
                $runCase = $this->runs->findRunCaseByCaseId($runId, (int) $testCase['id']);
            }

            if ($runCase === null) {
                $errors[] = ["Unable to attach test case '$externalId' to the run."];
                $skipped++;
                continue;
            }

            $runCaseId = (int) $runCase['id'];

            $resultId = $this->results->upsertResult($runCaseId, [
                'status'        => $status,
                'executed_by'   => null,
                'executed_at'   => date('Y-m-d H:i:s'),
                'is_automated'  => 1,
                'duration_ms'   => isset($raw['duration_ms']) ? (int) $raw['duration_ms'] : null,
                'actual_result' => isset($raw['actual_result']) ? (string) $raw['actual_result'] : null,
                'notes'         => isset($raw['notes']) ? (string) $raw['notes'] : null,
            ]);

            if (isset($raw['steps']) && is_array($raw['steps'])) {
                $steps = [];
                foreach ($raw['steps'] as $step) {
                    if (!is_array($step) || !isset($step['step_order'], $step['status'])) {
                        continue;
                    }
                    $steps[] = [
                        'step_order'    => (int) $step['step_order'],
                        'status'        => (string) $step['status'],
                        'actual_result' => isset($step['actual_result']) ? (string) $step['actual_result'] : null,
                        'notes'         => isset($step['notes']) ? (string) $step['notes'] : null,
                    ];
                }
                $this->results->saveStepResults($resultId, $steps);
            }

            $this->activity->record(
                null,
                (int) $run['project_id'],
                'test_result.recorded',
                'test_result',
                $resultId,
                ['run_case_id' => $runCaseId, 'source' => 'automation', 'status' => $status]
            );

            $recorded++;
        }

        return [
            'test_run_id' => $runId,
            'recorded'    => $recorded,
            'skipped'     => $skipped,
            'errors'      => $errors,
        ];
    }
}