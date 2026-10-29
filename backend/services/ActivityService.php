<?php
declare(strict_types=1);

/**
 * QAFlow — Activity service.
 *
 * Central place for writing and reading activity log entries. Every mutating
 * service calls `record()` after a successful change. The repository handles
 * persistence; this service shapes the API and applies light defaults.
 */

namespace QAFlow\Services;

use QAFlow\Repositories\ActivityRepository;

final class ActivityService
{
    private ActivityRepository $repo;

    public function __construct(ActivityRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Record an activity entry.
     *
     * @param array<string,mixed>|null $metadata
     */
    public function record(
        ?int $userId,
        ?int $projectId,
        string $action,
        string $entityType,
        ?int $entityId,
        ?array $metadata = null
    ): void {
        $this->repo->create([
            'user_id'     => $userId,
            'project_id'  => $projectId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'metadata'    => $metadata,
        ]);
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{items:array<int,array<string,mixed>>,total:int}
     */
    public function paginate(array $filters, int $page, int $perPage): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        return $this->repo->paginate($filters, $page, $perPage);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function forEntity(string $entityType, int $entityId, int $limit = 50): array
    {
        return $this->repo->forEntity($entityType, $entityId, $limit);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function recentForProject(int $projectId, int $limit = 20): array
    {
        return $this->repo->recentForProject($projectId, $limit);
    }

    /**
     * @return array<string,int>
     */
    public function actionCountsForProject(int $projectId): array
    {
        return $this->repo->actionCountsForProject($projectId);
    }
}