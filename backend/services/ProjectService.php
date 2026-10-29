<?php
declare(strict_types=1);

/**
 * QAFlow — Project service.
 *
 * Business rules for projects and memberships. Validates input, enforces
 * ownership/membership constraints, and writes activity log entries after
 * successful mutations.
 */

namespace QAFlow\Services;

use QAFlow\Core\AppException;
use QAFlow\Repositories\ProjectRepository;
use QAFlow\Repositories\UserRepository;
use QAFlow\Validators\ProjectValidator;

final class ProjectService
{
    private ProjectRepository $projects;
    private UserRepository $users;
    private ActivityService $activity;

    public function __construct(
        ProjectRepository $projects,
        UserRepository $users,
        ActivityService $activity
    ) {
        $this->projects = $projects;
        $this->users    = $users;
        $this->activity = $activity;
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{items:array<int,array<string,mixed>>,total:int}
     */
    public function list(int $userId, string $role, array $filters, int $page, int $perPage): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        return $this->projects->paginateForUser($userId, $role, $filters, $page, $perPage);
    }

    /**
     * @return array<string,mixed>
     */
    public function show(int $id, int $userId, string $role): array
    {
        $project = $this->projects->findById($id);
        if ($project === null) {
            throw AppException::notFound('Project not found.');
        }

        if ($role !== 'admin' && !$this->projects->isMember($id, $userId)) {
            throw AppException::forbidden('You do not have access to this project.');
        }

        $project['members']    = $this->projects->members($id);
        $project['statistics'] = $this->projects->statistics($id);

        return $project;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function create(array $data, int $creatorId, string $role): array
    {
        ProjectValidator::validateCreate($data);

        $key = strtoupper(trim((string) $data['key_code']));
        if ($this->projects->findByKey($key) !== null) {
            throw AppException::validation('Validation failed.', [
                'key_code' => ['Project key is already in use.'],
            ]);
        }

        // Only admins can assign an owner other than themselves.
        $ownerId = (int) $data['owner_id'];
        if ($role !== 'admin' && $ownerId !== $creatorId) {
            throw AppException::forbidden('Only admins can assign a different owner.');
        }

        if ($this->users->findById($ownerId) === null) {
            throw AppException::validation('Validation failed.', [
                'owner_id' => ['Owner not found.'],
            ]);
        }

        $id = $this->projects->create([
            'key_code'    => $key,
            'name'        => trim((string) $data['name']),
            'description' => isset($data['description']) ? trim((string) $data['description']) : null,
            'status'      => $data['status'] ?? 'active',
            'owner_id'    => $ownerId,
        ]);

        // Ensure the owner is a member with an admin-level project role.
        $this->projects->addMember($id, $ownerId, 'admin');

        $this->activity->record($creatorId, $id, 'project.created', 'project', $id, [
            'name' => $data['name'],
            'key'  => $key,
        ]);

        $project = $this->projects->findById($id);
        if ($project === null) {
            throw AppException::serverError('Failed to load project after create.');
        }

        return $project;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function update(int $id, array $data, int $userId, string $role): array
    {
        $project = $this->projects->findById($id);
        if ($project === null) {
            throw AppException::notFound('Project not found.');
        }

        $this->assertCanManage($id, $userId, $role);

        ProjectValidator::validateUpdate($data);

        if (isset($data['owner_id'])) {
            $ownerId = (int) $data['owner_id'];
            if ($role !== 'admin' && $ownerId !== $userId) {
                throw AppException::forbidden('Only admins can change the owner.');
            }
            if ($this->users->findById($ownerId) === null) {
                throw AppException::validation('Validation failed.', [
                    'owner_id' => ['Owner not found.'],
                ]);
            }
            // Owner is always a member.
            $this->projects->addMember($id, $ownerId, 'admin');
        }

        if (isset($data['description'])) {
            $data['description'] = trim((string) $data['description']) ?: null;
        }
        if (isset($data['name'])) {
            $data['name'] = trim((string) $data['name']);
        }

        $this->projects->update($id, $data);

        $this->activity->record($userId, $id, 'project.updated', 'project', $id, [
            'changed' => array_keys($data),
        ]);

        $updated = $this->projects->findById($id);
        if ($updated === null) {
            throw AppException::serverError('Failed to load project after update.');
        }

        return $updated;
    }

    public function archive(int $id, int $userId, string $role): void
    {
        $project = $this->projects->findById($id);
        if ($project === null) {
            throw AppException::notFound('Project not found.');
        }

        $this->assertCanManage($id, $userId, $role);

        $this->projects->archive($id);

        $this->activity->record($userId, $id, 'project.archived', 'project', $id, [
            'name' => $project['name'],
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function members(int $projectId, int $userId, string $role): array
    {
        $project = $this->projects->findById($projectId);
        if ($project === null) {
            throw AppException::notFound('Project not found.');
        }

        if ($role !== 'admin' && !$this->projects->isMember($projectId, $userId)) {
            throw AppException::forbidden('You do not have access to this project.');
        }

        return $this->projects->members($projectId);
    }

    /**
     * @param array<string,mixed> $data
     */
    public function addMember(int $projectId, array $data, int $userId, string $role): void
    {
        $project = $this->projects->findById($projectId);
        if ($project === null) {
            throw AppException::notFound('Project not found.');
        }

        $this->assertCanManage($projectId, $userId, $role);

        ProjectValidator::validateMember($data);

        $targetId = (int) $data['user_id'];
        if ($this->users->findById($targetId) === null) {
            throw AppException::validation('Validation failed.', [
                'user_id' => ['User not found.'],
            ]);
        }

        $this->projects->addMember($projectId, $targetId, (string) $data['role']);

        $this->activity->record($userId, $projectId, 'project.member.added', 'project', $projectId, [
            'user_id' => $targetId,
            'role'    => $data['role'],
        ]);
    }

    public function removeMember(int $projectId, int $targetUserId, int $userId, string $role): void
    {
        $project = $this->projects->findById($projectId);
        if ($project === null) {
            throw AppException::notFound('Project not found.');
        }

        $this->assertCanManage($projectId, $userId, $role);

        // Prevent the owner from being removed.
        if ((int) $project['owner_id'] === (int) $targetUserId) {
            throw AppException::validation('Validation failed.', [
                'user_id' => ['The project owner cannot be removed.'],
            ]);
        }

        $this->projects->removeMember($projectId, $targetUserId);

        $this->activity->record($userId, $projectId, 'project.member.removed', 'project', $projectId, [
            'user_id' => $targetUserId,
        ]);
    }

    /**
     * Assert the current user has permission to manage this project.
     */
    private function assertCanManage(int $projectId, int $userId, string $role): void
    {
        if ($role === 'admin') {
            return;
        }
        if ($role !== 'qa_lead') {
            throw AppException::forbidden('You do not have permission to manage this project.');
        }

        $projectRole = $this->projects->memberRole($projectId, $userId);
        if (!in_array($projectRole, ['admin', 'qa_lead'], true)) {
            throw AppException::forbidden('You do not have permission to manage this project.');
        }
    }
}