<?php
declare(strict_types=1);

/**
 * QAFlow — Defect service.
 *
 * Business rules for defects. Validates input, enforces project membership,
 * handles status transitions, and writes activity log entries.
 */

namespace QAFlow\Services;

use QAFlow\Core\AppException;
use QAFlow\Repositories\DefectRepository;
use QAFlow\Repositories\ProjectRepository;
use QAFlow\Validators\DefectValidator;

final class DefectService
{
    private DefectRepository $defects;
    private ProjectRepository $projects;
    private ActivityService $activity;

    public function __construct(
        DefectRepository $defects,
        ProjectRepository $projects,
        ActivityService $activity
    ) {
        $this->defects  = $defects;
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

        return $this->defects->paginate($filters, $page, $perPage);
    }

    /**
     * @return array<string,mixed>
     */
    public function show(int $id, int $userId, string $role): array
    {
        $defect = $this->defects->findById($id);
        if ($defect === null) {
            throw AppException::notFound('Defect not found.');
        }

        $this->assertCanRead((int) $defect['project_id'], $userId, $role);

        return $defect;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function create(array $data, int $userId, string $role): array
    {
        DefectValidator::validateCreate($data);

        $projectId = (int) $data['project_id'];
        $this->assertCanCreate($projectId, $userId, $role);

        $code = $this->defects->nextCode($projectId, 'DEF');

        $id = $this->defects->create([
            'project_id'      => $projectId,
            'code'            => $code,
            'title'           => trim((string) $data['title']),
            'description'     => isset($data['description']) ? trim((string) $data['description']) : null,
            'expected_result' => isset($data['expected_result']) ? trim((string) $data['expected_result']) : null,
            'actual_result'   => isset($data['actual_result']) ? trim((string) $data['actual_result']) : null,
            'severity'        => $data['severity'] ?? 'medium',
            'priority'        => $data['priority'] ?? 'medium',
            'status'          => $data['status'] ?? 'open',
            'test_case_id'    => $data['test_case_id'] ?? null,
            'requirement_id'  => $data['requirement_id'] ?? null,
            'test_result_id'  => $data['test_result_id'] ?? null,
            'environment_id'  => $data['environment_id'] ?? null,
            'reporter_id'     => $userId,
            'assignee_id'     => $data['assignee_id'] ?? null,
        ]);

        $this->activity->record($userId, $projectId, 'defect.created', 'defect', $id, [
            'code'     => $code,
            'title'    => $data['title'],
            'severity' => $data['severity'] ?? 'medium',
        ]);

        $defect = $this->defects->findById($id);
        if ($defect === null) {
            throw AppException::serverError('Failed to load defect after create.');
        }

        return $defect;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function update(int $id, array $data, int $userId, string $role): array
    {
        $existing = $this->defects->findById($id);
        if ($existing === null) {
            throw AppException::notFound('Defect not found.');
        }

        $this->assertCanUpdate((int) $existing['project_id'], $userId, $role);

        DefectValidator::validateUpdate($data);

        if (isset($data['title'])) {
            $data['title'] = trim((string) $data['title']);
        }
        if (array_key_exists('description', $data)) {
            $data['description'] = $data['description'] === null ? null : trim((string) $data['description']);
        }
        if (array_key_exists('expected_result', $data)) {
            $data['expected_result'] = $data['expected_result'] === null ? null : trim((string) $data['expected_result']);
        }
        if (array_key_exists('actual_result', $data)) {
            $data['actual_result'] = $data['actual_result'] === null ? null : trim((string) $data['actual_result']);
        }

        $this->defects->update($id, $data);

        $this->activity->record($userId, (int) $existing['project_id'], 'defect.updated', 'defect', $id, [
            'code'    => $existing['code'],
            'changed' => array_keys($data),
        ]);

        $updated = $this->defects->findById($id);
        if ($updated === null) {
            throw AppException::serverError('Failed to load defect after update.');
        }

        return $updated;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function changeStatus(int $id, array $data, int $userId, string $role): void
    {
        $existing = $this->defects->findById($id);
        if ($existing === null) {
            throw AppException::notFound('Defect not found.');
        }

        $this->assertCanUpdate((int) $existing['project_id'], $userId, $role);

        DefectValidator::validateStatusChange($data);

        $newStatus = (string) $data['status'];
        $oldStatus = (string) $existing['status'];

        if ($newStatus === $oldStatus) {
            return;
        }

        $this->defects->updateStatus($id, $newStatus);

        $this->activity->record($userId, (int) $existing['project_id'], 'defect.status_changed', 'defect', $id, [
            'code'     => $existing['code'],
            'from'     => $oldStatus,
            'to'       => $newStatus,
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

    private function assertCanCreate(int $projectId, int $userId, string $role): void
    {
        if (in_array($role, ['admin', 'qa_lead', 'qa_engineer'], true)) {
            if ($role !== 'admin' && !$this->projects->isMember($projectId, $userId)) {
                throw AppException::forbidden('You do not have access to this project.');
            }
            return;
        }

        throw AppException::forbidden('You do not have permission to create defects.');
    }

    private function assertCanUpdate(int $projectId, int $userId, string $role): void
    {
        if (in_array($role, ['admin', 'qa_lead', 'qa_engineer', 'developer'], true)) {
            if ($role !== 'admin' && !$this->projects->isMember($projectId, $userId)) {
                throw AppException::forbidden('You do not have access to this project.');
            }
            return;
        }

        throw AppException::forbidden('You do not have permission to update defects.');
    }
}