<?php
declare(strict_types=1);

/**
 * QAFlow — Project controller.
 *
 * HTTP boundary for projects and project members. Business logic lives in
 * ProjectService. Route-level role checks are handled by RoleMiddleware; the
 * controller surfaces the authenticated user to the service for project-level
 * ownership checks.
 */

namespace QAFlow\Controllers;

use QAFlow\Core\AppException;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;
use QAFlow\Services\ProjectService;

final class ProjectController
{
    private ProjectService $projects;

    public function __construct(ProjectService $projects)
    {
        $this->projects = $projects;
    }

    /**
     * GET /api/projects
     *
     * Query params: search, status, page, per_page
     */
    public function index(Request $request): Response
    {
        $user = $this->requireUser($request);

        $page    = max(1, (int) ($request->query('page') ?? 1));
        $perPage = max(1, min(100, (int) ($request->query('per_page') ?? 25)));

        $filters = [
            'search' => trim((string) ($request->query('search') ?? '')),
            'status' => trim((string) ($request->query('status') ?? '')),
        ];

        $result = $this->projects->list(
            $user['id'],
            $user['role'],
            $filters,
            $page,
            $perPage
        );

        return Response::paginated($result['items'], $page, $perPage, $result['total']);
    }

    /**
     * GET /api/projects/{id}
     */
    public function show(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $project = $this->projects->show($id, $user['id'], $user['role']);

        return Response::json($project);
    }

    /**
     * POST /api/projects
     */
    public function store(Request $request): Response
    {
        $user = $this->requireUser($request);

        $project = $this->projects->create(
            $request->json(),
            $user['id'],
            $user['role']
        );

        return Response::created($project);
    }

    /**
     * PUT /api/projects/{id}
     */
    public function update(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $project = $this->projects->update(
            $id,
            $request->json(),
            $user['id'],
            $user['role']
        );

        return Response::json($project);
    }

    /**
     * POST /api/projects/{id}/archive
     */
    public function archive(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $this->projects->archive($id, $user['id'], $user['role']);

        return Response::noContent();
    }

    /**
     * GET /api/projects/{id}/members
     */
    public function members(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        return Response::json($this->projects->members($id, $user['id'], $user['role']));
    }

    /**
     * POST /api/projects/{id}/members
     */
    public function addMember(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $this->projects->addMember($id, $request->json(), $user['id'], $user['role']);

        return Response::noContent();
    }

    /**
     * DELETE /api/projects/{id}/members/{userId}
     */
    public function removeMember(Request $request, array $params): Response
    {
        $user     = $this->requireUser($request);
        $id       = (int) ($params['id'] ?? 0);
        $targetId = (int) ($params['userId'] ?? 0);

        $this->projects->removeMember($id, $targetId, $user['id'], $user['role']);

        return Response::noContent();
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

        return [
            'id'   => (int) $user['id'],
            'role' => (string) $user['role'],
        ];
    }
}