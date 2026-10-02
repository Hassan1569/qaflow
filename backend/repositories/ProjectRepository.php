<?php
declare(strict_types=1);

/**
 * QAFlow — Project repository.
 *
 * SQL for projects, project members, and project-scoped read helpers.
 */

namespace QAFlow\Repositories;

use QAFlow\Config\Database;
use PDO;

final class ProjectRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id, p.key_code, p.name, p.description, p.status, p.owner_id,
                    p.created_at, p.updated_at,
                    u.name AS owner_name, u.email AS owner_email
             FROM projects p
             INNER JOIN users u ON u.id = p.owner_id
             WHERE p.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findByKey(string $key): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, key_code, name, status FROM projects WHERE key_code = :k LIMIT 1'
        );
        $stmt->execute([':k' => $key]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Projects the given user is a member of (or all for admins).
     *
     * @param array<string,mixed> $filters
     * @return array{items:array<int,array<string,mixed>>,total:int}
     */
    public function paginateForUser(int $userId, string $role, array $filters, int $page, int $perPage): array
    {
        $where  = [];
        $params = [];

        if ($role !== 'admin') {
            $where[] = 'EXISTS (SELECT 1 FROM project_members pm WHERE pm.project_id = p.id AND pm.user_id = :uid)';
            $params[':uid'] = $userId;
        }

        if (!empty($filters['search'])) {
            $where[] = '(p.name LIKE :s OR p.key_code LIKE :s OR p.description LIKE :s)';
            $params[':s'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status'])) {
            $where[] = 'p.status = :status';
            $params[':status'] = $filters['status'];
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        $countSql  = "SELECT COUNT(*) AS c FROM projects p $whereSql";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) ($countStmt->fetch()['c'] ?? 0);

        $offset = max(0, ($page - 1) * $perPage);

        $sql = "SELECT p.id, p.key_code, p.name, p.description, p.status,
                       p.created_at, p.updated_at,
                       u.name AS owner_name,
                       (SELECT COUNT(*) FROM requirements r WHERE r.project_id = p.id) AS requirements_count,
                       (SELECT COUNT(*) FROM test_cases tc WHERE tc.project_id = p.id) AS test_cases_count,
                       (SELECT COUNT(*) FROM defects d WHERE d.project_id = p.id AND d.status NOT IN ('closed','verified','rejected','duplicate')) AS open_defects_count
                FROM projects p
                INNER JOIN users u ON u.id = p.owner_id
                $whereSql
                ORDER BY p.updated_at DESC, p.id DESC
                LIMIT $perPage OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total];
    }

    /**
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO projects (key_code, name, description, status, owner_id)
             VALUES (:key, :name, :desc, :status, :owner)'
        );

        $stmt->execute([
            ':key'    => $data['key_code'],
            ':name'   => $data['name'],
            ':desc'   => $data['description'] ?? null,
            ':status' => $data['status'] ?? 'active',
            ':owner'  => $data['owner_id'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @param array<string,mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $fields = [];
        $params = [':id' => $id];

        foreach (['name', 'description', 'status', 'owner_id'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }

        if ($fields === []) {
            return;
        }

        $sql  = 'UPDATE projects SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function archive(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE projects SET status = 'archived' WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    // -----------------------------------------------------------------------
    // Members
    // -----------------------------------------------------------------------

    /**
     * @return array<int,array<string,mixed>>
     */
    public function members(int $projectId): array
    {
        $stmt = $this->db->prepare(
            'SELECT pm.user_id, pm.role, u.name, u.email, u.is_active
             FROM project_members pm
             INNER JOIN users u ON u.id = pm.user_id
             WHERE pm.project_id = :p
             ORDER BY u.name ASC'
        );
        $stmt->execute([':p' => $projectId]);
        return $stmt->fetchAll();
    }

    public function addMember(int $projectId, int $userId, string $role): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO project_members (project_id, user_id, role)
             VALUES (:p, :u, :r)
             ON DUPLICATE KEY UPDATE role = VALUES(role)'
        );
        $stmt->execute([':p' => $projectId, ':u' => $userId, ':r' => $role]);
    }

    public function removeMember(int $projectId, int $userId): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM project_members WHERE project_id = :p AND user_id = :u'
        );
        $stmt->execute([':p' => $projectId, ':u' => $userId]);
    }

    public function isMember(int $projectId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM project_members WHERE project_id = :p AND user_id = :u LIMIT 1'
        );
        $stmt->execute([':p' => $projectId, ':u' => $userId]);
        return $stmt->fetchColumn() !== false;
    }

    public function memberRole(int $projectId, int $userId): string
    {
        $stmt = $this->db->prepare(
            'SELECT role FROM project_members WHERE project_id = :p AND user_id = :u LIMIT 1'
        );
        $stmt->execute([':p' => $projectId, ':u' => $userId]);
        $role = $stmt->fetchColumn();
        return $role === false ? '' : (string) $role;
    }

    // -----------------------------------------------------------------------
    // Aggregates
    // -----------------------------------------------------------------------

    /**
     * @return array<string,int>
     */
    public function statistics(int $projectId): array
    {
        $sql = 'SELECT
                    (SELECT COUNT(*) FROM requirements WHERE project_id = :p) AS requirements,
                    (SELECT COUNT(*) FROM test_cases WHERE project_id = :p) AS test_cases,
                    (SELECT COUNT(*) FROM test_suites WHERE project_id = :p) AS test_suites,
                    (SELECT COUNT(*) FROM test_plans WHERE project_id = :p) AS test_plans,
                    (SELECT COUNT(*) FROM test_runs WHERE project_id = :p) AS test_runs,
                    (SELECT COUNT(*) FROM defects WHERE project_id = :p) AS defects,
                    (SELECT COUNT(*) FROM defects WHERE project_id = :p AND status NOT IN (\'closed\',\'verified\',\'rejected\',\'duplicate\')) AS open_defects';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':p' => $projectId]);
        $row = $stmt->fetch();

        if ($row === false) {
            return [];
        }

        $out = [];
        foreach ($row as $k => $v) {
            $out[$k] = (int) $v;
        }
        return $out;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function activeOptions(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, key_code, name FROM projects WHERE status = 'active' ORDER BY name ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}