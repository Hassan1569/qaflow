<?php
declare(strict_types=1);

/**
 * QAFlow — Test plan service.
 *
 * Business rules for test plans. Validates input, enforces project
 * membership, and writes activity log entries after mutations.
 */

namespace QAFlow\Services;

use QAFlow\Core\AppException;
use QAFlow\Repositories\ProjectRepository;
use QAFlow\Repositories\TestPlanRepository;
use QAFlow\Validators\TestPlanValidator;

final class TestPlanService
{
    private TestPlanRepository $plans;
    private ProjectRepository $projects;
    private ActivityService $activity;

    public function __construct(
        TestPlanRepository $plans,
        ProjectRepository $projects,
        ActivityService $activity
    ) {
        $this->plans    = $plans;
        $this->projects = $projects;
        $this->activity = $activity;
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

        return $this->plans->paginate($filters, $page, $perPage);
    }

    /**
     * @return array<string,mixed>
     */
    public function show(int $id, int $userId, string $role): array
    {
        $plan = $this->plans->findById($id);
        if ($plan === null) {
            throw AppException::notFound('Test plan not found.');
        }

        $this->assertCanRead((int) $plan['project_id'], $userId, $role);

        $plan['runs'] = $this->plans->runsForPlan($id);

        return $plan;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function create(array $data, int $userId, string $role): array
    {
        TestPlanValidator::validateCreate($data);

        $projectId = (int) $data['project_id'];
        $this->assertCanWrite($projectId, $userId, $role);

        $id = $this->plans->create([
            'project_id'      => $projectId,
            'name'            => trim((string) $data['name']),
            'release_version' => isset($data['release_version']) ? trim((string) $data['release_version']) : null,
            'description'     => isset($data['description']) ? trim((string) $data['description']) : null,
            'scope'           => isset($data['scope']) ? trim((string) $data['scope']) : null,
            'status'          => $data['status'] ?? 'draft',
            'start_date'      => $data['start_date'] ?? null,
            'end_date'        => $data['end_date'] ?? null,
            'owner_id'        => (int) $data['owner_id'],
        ]);

        $this->activity->record($userId, $projectId, 'test_plan.created', 'test_plan', $id, [
            'name' => $data['name'],
        ]);

        $plan = $this->plans->findById($id);
        if ($plan === null) {
            throw AppException::serverError('Failed to load test plan after create.');
        }

        return $plan;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function update(int $id, array $data, int $userId, string $role): array
    {
        $existing = $this->plans->findById($id);
        if ($existing === null) {
            throw AppException::notFound('Test plan not found.');
        }

        $this->assertCanWrite((int) $existing['project_id'], $userId, $role);

        TestPlanValidator::validateUpdate($data);

        if (isset($data['name'])) {
            $data['name'] = trim((string) $data['name']);
        }
        if (array_key_exists('description', $data)) {
            $data['description'] = $data['description'] === null ? null : trim((string) $data['description']);
        }
        if (array_key_exists('scope', $data)) {
            $data['scope'] = $data['scope'] === null ? null : trim((string) $data['scope']);
        }

        $this->plans->update($id, $data);

        $this->activity->record($userId, (int) $existing['project_id'], 'test_plan.updated', 'test_plan', $id, [
            'name'    => $existing['name'],
            'changed' => array_keys($data),
        ]);

        $updated = $this->plans->findById($id);
        if ($updated === null) {
            throw AppException::serverError('Failed to load test plan after update.');
        }

        return $updated;
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
            throw AppException::forbidden('You do not have permission to modify test plans.');
        }
        $projectRole = $this->projects->memberRole($projectId, $userId);
        if (!in_array($projectRole, ['admin', 'qa_lead'], true)) {
            throw AppException::forbidden('You do not have permission to modify test plans.');
        }
    }
}