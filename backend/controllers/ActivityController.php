<?php
declare(strict_types=1);

/**
 * QAFlow — Activity controller.
 *
 * HTTP boundary for the activity log. Entries are written automatically by
 * services when entities change; this endpoint only reads them.
 */

namespace QAFlow\Controllers;

use QAFlow\Core\AppException;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;
use QAFlow\Services\ActivityService;

final class ActivityController
{
    private ActivityService $activity;

    public function __construct(ActivityService $activity)
    {
        $this->activity = $activity;
    }

    /**
     * GET /api/activity
     *
     * Query params: project_id, user_id, entity_type, entity_id, action, since,
     * page, per_page
     *
     * Any authenticated user may read the log. The frontend hides the link
     * for viewers who should not typically see it, but the data itself is
     * not sensitive.
     */
    public function index(Request $request): Response
    {
        $user = $this->requireUser($request);

        $page    = max(1, (int) ($request->query('page') ?? 1));
        $perPage = max(1, min(100, (int) ($request->query('per_page') ?? 25)));

        $filters = [
            'project_id'  => $request->query('project_id'),
            'user_id'     => $request->query('user_id'),
            'entity_type' => trim((string) ($request->query('entity_type') ?? '')),
            'entity_id'   => $request->query('entity_id'),
            'action'      => trim((string) ($request->query('action') ?? '')),
            'since'       => trim((string) ($request->query('since') ?? '')),
        ];

        $result = $this->activity->paginate($filters, $page, $perPage);

        return Response::paginated($result['items'], $page, $perPage, $result['total']);
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