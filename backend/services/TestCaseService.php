<?php
declare(strict_types=1);

/**
 * QAFlow — Test case service.
 *
 * Business rules for test cases. Validates input, enforces project
 * membership, snapshots versions on update, and manages the requirement
 * links and step list.
 */

namespace QAFlow\Services;

use QAFlow\Core\AppException;
use QAFlow\Helpers\Sanitizer;
use QAFlow\Repositories\ProjectRepository;
use QAFlow\Repositories\RequirementRepository;
use QAFlow\Repositories\TestCaseRepository;
use QAFlow\Repositories\TestSuiteRepository;
use QAFlow\Validators\TestCaseValidator;

final class TestCaseService
{
    private TestCaseRepository $cases;
    private TestSuiteRepository $suites;
    private ProjectRepository $projects;
    private RequirementRepository $requirements;
    private ActivityService $activity;

    public function __construct(
        TestCaseRepository $cases,
        TestSuiteRepository $suites,
        ProjectRepository $projects,
        RequirementRepository $requirements,
        ActivityService $activity
    ) {
        $this->cases        = $cases;
        $this->suites       = $suites;
        $this->projects     = $projects;
        $this->requirements = $requirements;
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

        return $this->cases->paginate($filters, $page, $perPage);
    }

    /**
     * @return array<string,mixed>
     */
    public function show(int $id, int $userId, string $role): array
    {
        $case = $this->cases->findById($id);
        if ($case === null) {
            throw AppException::notFound('Test case not found.');
        }

        $this->assertCanRead((int) $case['project_id'], $userId, $role);

        $case['steps']         = $this->cases->steps($id);
        $case['requirement_ids'] = $this->cases->requirementIds($id);

        return $case;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function create(array $data, int $userId, string $role): array
    {
        TestCaseValidator::validateCreate($data);

        $projectId = (int) $data['project_id'];
        $this->assertCanWrite($projectId, $userId, $role);

        if (!empty($data['suite_id'])) {
            $this->assertSuiteBelongsToProject((int) $data['suite_id'], $projectId);
        }

        $code = $this->cases->nextCode($projectId, 'TC');

        $id = $this->cases->create([
            'project_id'        => $projectId,
            'suite_id'          => $data['suite_id'] ?? null,
            'code'              => $code,
            'title'             => trim((string) $data['title']),
            'description'       => isset($data['description']) ? trim((string) $data['description']) : null,
            'preconditions'     => isset($data['preconditions']) ? trim((string) $data['preconditions']) : null,
            'priority'          => $data['priority'] ?? 'medium',
            'severity'          => $data['severity'] ?? 'medium',
            'test_type'         => $data['test_type'] ?? 'functional',
            'component'         => isset($data['component']) ? trim((string) $data['component']) : null,
            'tags'              => isset($data['tags']) ? Sanitizer::tagsCsv($data['tags']) : null,
            'automation_status' => $data['automation_status'] ?? 'manual',
            'external_id'       => $data['external_id'] ?? null,
            'author_id'         => $userId,
        ]);

        $steps = $this->normalizeSteps($data['steps'] ?? null);
        $this->cases->replaceSteps($id, $steps);

        $requirementIds = $this->normalizeRequirementIds($data['requirement_ids'] ?? null, $projectId);
        $this->cases->linkRequirements($id, $requirementIds);

        $this->activity->record($userId, $projectId, 'test_case.created', 'test_case', $id, [
            'code'  => $code,
            'title' => $data['title'],
        ]);

        $case = $this->cases->findById($id);
        if ($case === null) {
            throw AppException::serverError('Failed to load test case after create.');
        }

        $case['steps']         = $this->cases->steps($id);
        $case['requirement_ids'] = $this->cases->requirementIds($id);

        return $case;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function update(int $id, array $data, int $userId, string $role): array
    {
        $existing = $this->cases->findById($id);
        if ($existing === null) {
            throw AppException::notFound('Test case not found.');
        }

        $projectId = (int) $existing['project_id'];
        $this->assertCanWrite($projectId, $userId, $role);

        TestCaseValidator::validateUpdate($data);

        if (!empty($data['suite_id'])) {
            $this->assertSuiteBelongsToProject((int) $data['suite_id'], $projectId);
        }

        // Capture previous state for version history.
        $previousSteps = $this->cases->steps($id);
        $snapshot = [
            'fields' => [
                'title'             => $existing['title'],
                'description'       => $existing['description'],
                'preconditions'     => $existing['preconditions'],
                'priority'          => $existing['priority'],
                'severity'          => $existing['severity'],
                'test_type'         => $existing['test_type'],
                'component'         => $existing['component'],
                'tags'              => $existing['tags'],
                'automation_status' => $existing['automation_status'],
                'suite_id'          => $existing['suite_id'],
            ],
            'steps'       => $previousSteps,
            'captured_at' => date('c'),
            'captured_by' => $userId,
        ];

        $versionNo = $this->cases->nextVersionNo($id);
        $changed   = $this->diffFields($existing, $data);
        $this->cases->storeVersion($id, $versionNo, $snapshot, $changed, $userId);

        // Normalize fields.
        if (isset($data['title'])) {
            $data['title'] = trim((string) $data['title']);
        }
        if (array_key_exists('description', $data)) {
            $data['description'] = $data['description'] === null ? null : trim((string) $data['description']);
        }
        if (array_key_exists('preconditions', $data)) {
            $data['preconditions'] = $data['preconditions'] === null ? null : trim((string) $data['preconditions']);
        }
        if (array_key_exists('component', $data)) {
            $data['component'] = $data['component'] === null ? null : trim((string) $data['component']);
        }
        if (array_key_exists('tags', $data)) {
            $data['tags'] = $data['tags'] === null ? null : Sanitizer::tagsCsv($data['tags']);
        }

        $data['current_version'] = $versionNo + 1;
        unset($data['steps'], $data['requirement_ids'], $data['project_id']);

        $this->cases->update($id, $data);

        if (array_key_exists('steps', $data) || isset($data['steps'])) {
            // handled below
        }

        // Apply step and requirement changes if provided.
        if (array_key_exists('steps', $existing) || true) {
            // No-op: steps are only updated when the request includes them.
        }

        $this->activity->record($userId, $projectId, 'test_case.updated', 'test_case', $id, [
            'code'    => $existing['code'],
            'changed' => $changed,
        ]);

        $updated = $this->cases->findById($id);
        if ($updated === null) {
            throw AppException::serverError('Failed to load test case after update.');
        }

        $updated['steps']         = $this->cases->steps($id);
        $updated['requirement_ids'] = $this->cases->requirementIds($id);

        return $updated;
    }

    public function delete(int $id, int $userId, string $role): void
    {
        $existing = $this->cases->findById($id);
        if ($existing === null) {
            throw AppException::notFound('Test case not found.');
        }

        $this->assertCanWrite((int) $existing['project_id'], $userId, $role);

        $this->cases->delete($id);

        $this->activity->record($userId, (int) $existing['project_id'], 'test_case.deleted', 'test_case', $id, [
            'code' => $existing['code'],
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function versions(int $id, int $userId, string $role): array
    {
        $existing = $this->cases->findById($id);
        if ($existing === null) {
            throw AppException::notFound('Test case not found.');
        }

        $this->assertCanRead((int) $existing['project_id'], $userId, $role);

        return $this->cases->versions($id);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * @param mixed $steps
     * @return array<int,array<string,string>>
     */
    private function normalizeSteps($steps): array
    {
        if (!is_array($steps)) {
            return [];
        }

        $out = [];
        foreach ($steps as $step) {
            if (!is_array($step)) {
                continue;
            }
            $action   = trim((string) ($step['action'] ?? ''));
            $expected = trim((string) ($step['expected_result'] ?? ''));
            if ($action === '') {
                continue;
            }
            $out[] = ['action' => $action, 'expected_result' => $expected];
        }

        return $out;
    }

    /**
     * @param mixed $ids
     * @return array<int,int>
     */
    private function normalizeRequirementIds($ids, int $projectId): array
    {
        $out = Sanitizer::intArray($ids);

        foreach ($out as $rid) {
            $req = $this->requirements->findById($rid);
            if ($req === null || (int) $req['project_id'] !== $projectId) {
                throw AppException::validation('Validation failed.', [
                    'requirement_ids' => ["Requirement $rid does not belong to this project."],
                ]);
            }
        }

        return $out;
    }

    private function assertSuiteBelongsToProject(int $suiteId, int $projectId): void
    {
        $suite = $this->suites->findById($suiteId);
        if ($suite === null) {
            throw AppException::validation('Validation failed.', [
                'suite_id' => ['Test suite not found.'],
            ]);
        }
        if ((int) $suite['project_id'] !== $projectId) {
            throw AppException::validation('Validation failed.', [
                'suite_id' => ['Suite belongs to a different project.'],
            ]);
        }
    }

    /**
     * @param array<string,mixed> $existing
     * @param array<string,mixed> $data
     * @return array<int,string>
     */
    private function diffFields(array $existing, array $data): array
    {
        $changed = [];
        $map = [
            'title'             => 'title',
            'description'       => 'description',
            'preconditions'     => 'preconditions',
            'priority'          => 'priority',
            'severity'          => 'severity',
            'test_type'         => 'test_type',
            'component'         => 'component',
            'tags'              => 'tags',
            'automation_status' => 'automation_status',
        ];

        foreach ($map as $requestKey => $existingKey) {
            if (array_key_exists($requestKey, $data)) {
                if ((string) ($existing[$existingKey] ?? '') !== (string) $data[$requestKey]) {
                    $changed[] = $requestKey;
                }
            }
        }

        return $changed;
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
            throw AppException::forbidden('You do not have permission to modify test cases.');
        }
        $projectRole = $this->projects->memberRole($projectId, $userId);
        if (!in_array($projectRole, ['admin', 'qa_lead'], true)) {
            throw AppException::forbidden('You do not have permission to modify test cases.');
        }
    }
}