<?php
declare(strict_types=1);

/**
 * QAFlow — Test suite repository.
 *
 * SQL for test suites. Suites are hierarchical via parent_id and scoped to a
 * project. Deleting a suite cascades to children and sets the suite_id of any
 * orphaned test cases to NULL.
 */

namespace QAFlow\Repositories;

use QAFlow\Config\Database;
use PDO;

final class TestSuiteRepository
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
            'SELECT ts.id, ts.project_id, ts.parent_id, ts.name, ts.description,
                    ts.created_at, ts.updated_at,
                    p.key_code AS project_key,
                    parent.name AS parent_name
             FROM test_suites ts
             INNER JOIN projects p ON p.id = ts.project_id
             LEFT JOIN test_suites parent ON parent.id = ts.parent_id
             WHERE ts.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{items:array<int,array<string,mixed>>,total:int}
     */
    public function paginate(array $filters, int $page, int $perPage): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['project_id'])) {
            $where[] = 'ts.project_id = :pid';
            $params[':pid'] = (int) $filters['project_id'];
        }

        if (!empty($filters['search'])) {
            $where[] = 'ts.name LIKE :s';
            $params[':s'] = '%' . $filters['search'] . '%';
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) AS c FROM test_suites ts $whereSql");
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) ($countStmt->fetch()['c'] ?? 0);

        $offset = max(0, ($page - 1) * $perPage);

        $sql = "SELECT ts.id, ts.project_id, ts.parent_id, ts.name, ts.description,
                       ts.created_at, ts.updated_at,
                       parent.name AS parent_name,
                       (SELECT COUNT(*) FROM test_cases tc WHERE tc.suite_id = ts.id) AS test_case_count
                FROM test_suites ts
                LEFT JOIN test_suites parent ON parent.id = ts.parent_id
                $whereSql
                ORDER BY ts.name ASC
                LIMIT $perPage OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total];
    }

    /**
     * Return every suite for a project, ordered for tree rendering.
     *
     * @return array<int,array<string,mixed>>
     */
    public function tree(int $projectId): array
    {
        $sql = "SELECT ts.id, ts.project_id, ts.parent_id, ts.name, ts.description,
                       (SELECT COUNT(*) FROM test_cases tc WHERE tc.suite_id = ts.id) AS test_case_count
                FROM test_suites ts
                WHERE ts.project_id = :p
                ORDER BY COALESCE(ts.parent_id, 0) ASC, ts.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':p' => $projectId]);

        return $stmt->fetchAll();
    }

    /**
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO test_suites (project_id, parent_id, name, description)
             VALUES (:project, :parent, :name, :desc)'
        );

        $stmt->execute([
            ':project' => $data['project_id'],
            ':parent'  => $data['parent_id'] ?? null,
            ':name'    => $data['name'],
            ':desc'    => $data['description'] ?? null,
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

        foreach (['name', 'description', 'parent_id'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }

        if ($fields === []) {
            return;
        }

        $sql  = 'UPDATE test_suites SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM test_suites WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function countChildren(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS c FROM test_suites WHERE parent_id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return (int) ($row['c'] ?? 0);
    }

    public function countTestCases(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS c FROM test_cases WHERE suite_id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return (int) ($row['c'] ?? 0);
    }

    /**
     * Prevent cycles: would `$parentId` sit below `$id` in the tree?
     */
    public function wouldCreateCycle(int $id, ?int $parentId): bool
    {
        if ($parentId === null || $parentId === 0) {
            return false;
        }

        if ($parentId === $id) {
            return true;
        }

        $current = $parentId;
        $guard   = 0;

        while ($current > 0 && $guard < 64) {
            $stmt = $this->db->prepare('SELECT parent_id FROM test_suites WHERE id = :id');
            $stmt->execute([':id' => $current]);
            $row = $stmt->fetch();

            if ($row === false) {
                return false;
            }

            $next = (int) $row['parent_id'];
            if ($next === $id) {
                return true;
            }

            if ($next === $current || $next === 0) {
                return false;
            }

            $current = $next;
            $guard++;
        }

        return false;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function optionsForProject(int $projectId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, parent_id, name FROM test_suites WHERE project_id = :p ORDER BY name ASC'
        );
        $stmt->execute([':p' => $projectId]);
        return $stmt->fetchAll();
    }
}