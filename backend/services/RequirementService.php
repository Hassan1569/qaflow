<?php
declare(strict_types=1);

/**
 * QAFlow — Requirement service.
 *
 * Business rules for requirements. Validates input, enforces project
 * membership, generates codes, and writes version snapshots on update.
 */

namespace QAFlow\Services;

use QAFlow\Core\AppException;
use QAFlow\Repositories\ProjectRepository;
use QAFlow\Repositories\RequirementRepository;
use QAFlow\Validators\RequirementValidator;

final class RequirementService
{
    private RequirementRepository $requirements;
    private ProjectRepository $projects;
    private ActivityService $activity;

    public function __construct(
        RequirementRepository $requirements,
        ProjectRepository $projects,
        ActivityService $activity
    ) {
        $this->requirements = $requirements;
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

        return $this->requirements->paginate($filters, $page, $perPage);
    }

    /**
     * @return array<string,mixed>
     */
    public function show(int $id, int $userId, string $role): array
    {
        $requirement = $this->requirements->findById($id);
        if ($requirement === null) {
            throw AppException::notFound('Requirement not found.');
        }

        $this->assertCanRead((int) $requirement['project_id'], $userId, $role);

        $requirement['test_cases'] = $this->requirements->testCasesFor($id);

        return $requirement;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function create(array $data, int $userId, string $role): array
    {
        RequirementValidator::validateCreate($data);

        $projectId = (int) $data['project_id'];
        $this->assertCanWrite($projectId, $userId, $role);

        $code = $this->requirements->nextCode($projectId, 'REQ');

        $id = $this->requirements->create([
            'project_id'  => $projectId,
            'code'        => $code,
            'title'       => trim((string) $data['title']),
            'description' => isset($data['description']) ? trim((string) $data['description']) : null,
            'priority'    => $data['priority'] ?? 'medium',
            'status'      => $data['status'] ?? 'draft',
            'author_id'   => $userId,
        ]);

        $this->activity->record($userId, $projectId, 'requirement.created', 'requirement', $id, [
            'code'  => $code,
            'title' => $data['title'],
        ]);

        $requirement = $this->requirements->findById($id);
        if ($requirement === null) {
            throw AppException::serverError('Failed to load requirement after create.');
        }

        return $requirement;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function update(int $id, array $data, int $userId, string $role): array
    {
        $existing = $this->requirements->findById($id);
        if ($existing === null) {
            throw AppException::notFound('Requirement not found.');
        }

        $this->assertCanWrite((int) $existing['project_id'], $userId, $role);

        RequirementValidator::validateUpdate($data);

        // Snapshot current state before applying.
        $previousVersion = $this->requirements->nextVersionNo($id);
        $snapshot = [
            'title'       => $existing['title'],
            'description' => $existing['description'],
            'priority'    => $existing['priority'],
            'status'      => $existing['status'],
            'captured_at' => date('c'),
            'captured_by' => $userId,
        ];
        $this->requirements->storeVersion($id, $previousVersion, $snapshot, $userId);

        // Normalize fields.
        if (isset($data['title'])) {
            $data['title'] = trim((string) $data['title']);
        }
        if (array_key_exists('description', $data)) {
            $data['description'] = $data['description'] === null
                ? null
                : trim((string) $data['description']);
        }

        $this->requirements->update($id, $data);

        $this->activity->record($userId, (int) $existing['project_id'], 'requirement.updated', 'requirement', $id, [
            'code'    => $existing['code'],
            'changed' => array_keys($data),
        ]);

        $updated = $this->requirements->findById($id);
        if ($updated === null) {
            throw AppException::serverError('Failed to load requirement after update.');
        }

        return $updated;
    }

    public function delete(int $id, int $userId, string $role): void
    {
        $existing = $this->requirements->findById($id);
        if ($existing === null) {
            throw AppException::notFound('Requirement not found.');
        }

        $this->assertCanWrite((int) $existing['project_id'], $userId, $role);

        $this->requirements->delete($id);

        $this->activity->record($userId, (int) $existing['project_id'], 'requirement.deleted', 'requirement', $id, [
            'code' => $existing['code'],
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function testCases(int $requirementId, int $userId, string $role): array
    {
        $requirement = $this->requirements->findById($requirementId);
        if ($requirement === null) {
            throw AppException::notFound('Requirement not found.');
        }

        $this->assertCanRead((int) $requirement['project_id'], $userId, $role);

        return $this->requirements->testCasesFor($requirementId);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function versions(int $requirementId, int $userId, string $role): array
    {
        $requirement = $this->requirements->findById($requirementId);
        if ($requirement === null) {
            throw AppException::notFound('Requirement not found.');
        }

        $this->assertCanRead((int) $requirement['project_id'], $userId, $role);

        return $this->requirements->versions($requirementId);
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
            throw AppException::forbidden('You do not have permission to modify requirements.');
        }
        $projectRole = $this->projects->memberRole($projectId, $userId);
        if (!in_array($projectRole, ['admin', 'qa_lead'], true)) {
            throw AppException::forbidden('You do not have permission to modify requirements.');
        }
    }
}