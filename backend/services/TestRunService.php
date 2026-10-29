<?php
declare(strict_types=1);

/**
 * QAFlow — Test run service.
 *
 * Business rules for test runs. Validates input, enforces project
 * membership, attaches test cases, and manages status transitions.
 */

namespace QAFlow\Services;

use QAFlow\Core\AppException;
use QAFlow\Helpers\Sanitizer;
use QAFlow\Repositories\EnvironmentRepository;
use QAFlow\Repositories\ProjectRepository;
use QAFlow\Repositories\TestCaseRepository;
use QAFlow\Repositories\TestRunRepository;
use QAFlow\Validators\TestRunValidator;

final class TestRunService
{
    private TestRunRepository $runs;
    private TestCaseRepository $cases;
    private EnvironmentRepository $environments;
    private ProjectRepository $projects;
    private ActivityService $activity;

    public function __construct(
        TestRunRepository $runs,
        TestCaseRepository $cases,
        EnvironmentRepository $environments,
        ProjectRepository $projects,
        ActivityService $activity
    ) {
        $this->runs         = $runs;
        $this->cases        = $cases;
        $this->environments = $environments;
        $this->projects     = $projects;
        $this->activity     = $activity;
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{items:array<int,array<string,mixed>>,total:int}
     */
    public function list(array $filters, int $userId, string $role, int $page, int $perPage): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        if (!empty($filters['project_id'])) {
            $this->assertCanRead((int) $filters['project_id'], $userId, $role);
        }

        return $this->runs->paginate($filters, $page, $perPage);
    }

    /**
     * @return array<string,mixed>
     */
    public function show(int $id, int $userId, string $role): array
    {
        $run = $this->runs->findById($id);
        if ($run === null) {
            throw AppException::notFound('Test run not found.');
        }

        $this->assertCanRead((int) $run['project_id'], $userId, $role);

        $run['cases']    = $this->runs->cases($id);
        $run['progress'] = $this->runs->progress($id);

        return $run;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function create(array $data, int $userId, string $role): array
    {
        TestRunValidator::validateCreate($data);

        $projectId = (int) $data['project_id'];
        $this->assertCanWrite($projectId, $userId, $role);

        if (!empty($data['environment_id'])) {
            $env = $this->environments->findById((int) $data['environment_id']);
            if ($env === null || (int) $env['project_id'] !== $projectId) {
                throw AppException::validation('Validation failed.', [
                    'environment_id' => ['Environment not found in this project.'],
                ]);
            }
        }

        $caseIds = Sanitizer::intArray($data['test_case_ids']);
        if ($caseIds === []) {
            throw AppException::validation('Validation failed.', [
                'test_case_ids' => ['At least one test case is required.'],
            ]);
        }

        // Validate every case belongs to the project.
        foreach ($caseIds as $cid) {
            $case = $this->cases->findById($cid);
            if ($case === null || (int) $case['project_id'] !== $projectId) {
                throw AppException::validation('Validation failed.', [
                    'test_case_ids' => ["Test case $cid does not belong to this project."],
                ]);
            }
        }

        $id = $this->runs->create([
            'project_id'     => $projectId,
            'plan_id'        => $data['plan_id'] ?? null,
            'environment_id' => $data['environment_id'] ?? null,
            'name'           => trim((string) $data['name']),
            'description'    => isset($data['description']) ? trim((string) $data['description']) : null,
            'status'         => 'not_started',
            'assigned_to'    => $data['assigned_to'] ?? null,
            'created_by'     => $userId,
        ]);

        $envId    = $data['environment_id'] ?? null;
        $assignee = $data['assigned_to'] ?? null;

        foreach ($caseIds as $cid) {
            $this->runs->addCase($id, $cid, $envId ? (int) $envId : null, $assignee ? (int) $assignee : null);
        }

        $this->activity->record($userId, $projectId, 'test_run.created', 'test_run', $id, [
            'name'       => $data['name'],
            'case_count' => count($caseIds),
        ]);

        $run = $this->runs->findById($id);
        if ($run === null) {
            throw AppException::serverError('Failed to load test run after create.');
        }

        return $run;
    }

    public function start(int $id, int $userId, string $role): void
    {
        $run = $this->runs->findById($id);
        if ($run === null) {
            throw AppException::notFound('Test run not found.');
        }

        if ((int) $run['project_id'] !== 0) {
            $this->assertCanExecute((int) $run['project_id'], $userId, $role);
        }

        if ($run['status'] === 'completed') {
            throw AppException::validation('Validation failed.', [
                'status' => ['Cannot start a completed run.'],
            ]);
        }

        $this->runs->markStarted($id);

        $this->activity->record($userId, (int) $run['project_id'], 'test_run.started', 'test_run', $id, [
            'name' => $run['name'],
        ]);
    }

    public function complete(int $id, int $userId, string $role): void
    {
        $run = $this->runs->findById($id);
        if ($run === null) {
            throw AppException::notFound('Test run not found.');
        }

        $this->assertCanWrite((int) $run['project_id'], $userId, $role);

        $this->runs->markCompleted($id);

        $this->activity->record($userId, (int) $run['project_id'], 'test_run.completed', 'test_run', $id, [
            'name' => $run['name'],
        ]);
    }

    // -----------------------------------------------------------------------
    // Authorization helpers
    // -----------------------------------------------------------------------

    private function assertCanRead(int $projectId, int $userId, string $role): void
    {
        if ($role === 'admin') {
            return;
        }
        if (!$this->projects->isMember($projectId, $userId)) {
            throw AppException::forbidden('You do not have access to this project.');
        }
    }

    private function assertCanWrite(int $projectId, int $userId, string $role): void
    {
        if ($role === 'admin') {
            return;
        }
        if ($role !== 'qa_lead') {
            throw AppException::forbidden('You do not have permission to modify test runs.');
        }
        $projectRole = $this->projects->memberRole($projectId, $userId);
        if (!in_array($projectRole, ['admin', 'qa_lead'], true)) {
            throw AppException::forbidden('You do not have permission to modify test runs.');
        }
    }

    private function assertCanExecute(int $projectId, int $userId, string $role): void
    {
        if (in_array($role, ['admin', 'qa_lead', 'qa_engineer'], true)) {
            if ($role !== 'admin' && !$this->projects->isMember($projectId, $userId)) {
                throw AppException::forbidden('You do not have access to this project.');
            }
            return;
        }

        throw AppException::forbidden('You do not have permission to execute this test run.');
    }
}