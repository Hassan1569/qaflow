<?php
declare(strict_types=1);

/**
 * QAFlow — Role-based access middleware.
 *
 * Consumes the role list declared on the route. Admin always passes. For
 * project-scoped routes, the middleware also considers the user's role in
 * `project_members` when a project id is present in the request (query
 * string, JSON body, or route params).
 */

namespace QAFlow\Middleware;

use QAFlow\Config\Database;
use QAFlow\Core\AppException;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;

final class RoleMiddleware
{
    /**
     * @param array<string,mixed> $route
     */
    public function handle(Request $request, array $route = []): ?Response
    {
        $required = $route['roles'] ?? [];
        if (!is_array($required) || $required === []) {
            return null; // No role requirement.
        }

        $user = $request->user();
        if ($user === null) {
            throw AppException::unauthorized('Authentication required.');
        }

        $role = (string) ($user['role'] ?? 'viewer');

        if ($role === 'admin' || in_array($role, $required, true)) {
            return null;
        }

        // Consider project-scoped role if a project id is present.
        $projectId = $this->extractProjectId($request);
        if ($projectId > 0) {
            $projectRole = $this->projectRole($projectId, (int) $user['id']);
            if ($projectRole === 'admin' || in_array($projectRole, $required, true)) {
                return null;
            }
        }

        throw AppException::forbidden('You do not have permission to perform this action.');
    }

    private function extractProjectId(Request $request): int
    {
        // Query string.
        $q = $request->query('project_id');
        if ($q !== null && ctype_digit((string) $q)) {
            return (int) $q;
        }

        // JSON body.
        $body = $request->json();
        if (is_array($body) && isset($body['project_id']) && ctype_digit((string) $body['project_id'])) {
            return (int) $body['project_id'];
        }

        // Route params are also exposed on the request as attributes.
        $attr = $request->attribute('project_id');
        if ($attr !== null && ctype_digit((string) $attr)) {
            return (int) $attr;
        }

        return 0;
    }

    private function projectRole(int $projectId, int $userId): string
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT role FROM project_members WHERE project_id = :p AND user_id = :u LIMIT 1');
        $stmt->execute([':p' => $projectId, ':u' => $userId]);
        $row = $stmt->fetch();

        return $row === false ? '' : (string) $row['role'];
    }
}