<?php
declare(strict_types=1);

/**
 * QAFlow — Test plan repository.
 *
 * SQL for test plans. A plan is scoped to a project and can spawn multiple
 * test runs. Deletion is soft-referenced: runs keep their plan_id but the
 * FK is `ON DELETE SET NULL`, so runs survive plan removal.
 */

namespace QAFlow\Repositories;

use QAFlow\Config\Database;
use PDO;

final class TestPlanRepository
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
            'SELECT tp.id, tp.project_id, tp.name, tp.release_version, tp.description,
                    tp.scope, tp.status, tp.start_date, tp.end_date, tp.owner_id,
                    tp.created_at, tp.updated_at,
                    u.name AS owner_name,
                    p.key_code AS project_key, p.name AS project_name,
                    (SELECT COUNT(*) FROM test_runs tr WHERE tr.plan_id = tp.id) AS run_count
             FROM test_plans tp
             INNER JOIN users u ON u.id = tp.owner_id
             INNER JOIN projects p ON p.id = tp.project_id
             WHERE tp.id = :id
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
            $where[] = 'tp.project_id = :pid';
            $params[':pid'] = (int) $filters['project_id'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'tp.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(tp.name LIKE :s OR tp.release_version LIKE :s OR tp.description LIKE :s)';
            $params[':s'] = '%' . $filters['search'] . '%';
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) AS c FROM test_plans tp $whereSql");
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) ($countStmt->fetch()['c'] ?? 0);

        $offset = max(0, ($page - 1) * $perPage);

        $sql = "SELECT tp.id, tp.project_id, tp.name, tp.release_version, tp.description,
                       tp.scope, tp.status, tp.start_date, tp.end_date, tp.owner_id,
                       tp.created_at, tp.updated_at,
                       u.name AS owner_name,
                       p.key_code AS project_key,
                       (SELECT COUNT(*) FROM test_runs tr WHERE tr.plan_id = tp.id) AS run_count
                FROM test_plans tp
                INNER JOIN users u ON u.id = tp.owner_id
                INNER JOIN projects p ON p.id = tp.project_id
                $whereSql
                ORDER BY tp.updated_at DESC, tp.id DESC
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
            'INSERT INTO test_plans
                (project_id, name, release_version, description, scope, status,
                 start_date, end_date, owner_id)
             VALUES
                (:project, :name, :release, :desc, :scope, :status,
                 :start, :end, :owner)'
        );

        $stmt->execute([
            ':project' => $data['project_id'],
            ':name'    => $data['name'],
            ':release' => $data['release_version'] ?? null,
            ':desc'    => $data['description'] ?? null,
            ':scope'   => $data['scope'] ?? null,
            ':status'  => $data['status'] ?? 'draft',
            ':start'   => $data['start_date'] ?? null,
            ':end'     => $data['end_date'] ?? null,
            ':owner'   => $data['owner_id'],
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

        $columns = [
            'name', 'release_version', 'description', 'scope', 'status',
            'start_date', 'end_date', 'owner_id',
        ];

        foreach ($columns as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }

        if ($fields === []) {
            return;
        }

        $sql  = 'UPDATE test_plans SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM test_plans WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function runsForPlan(int $planId): array
    {
        $stmt = $this->db->prepare(
            'SELECT tr.id, tr.name, tr.status, tr.started_at, tr.completed_at,
                    tr.environment_id, e.name AS environment_name,
                    tr.assigned_to, u.name AS assigned_name,
                    (SELECT COUNT(*) FROM test_run_cases trc WHERE trc.test_run_id = tr.id) AS total_cases,
                    (SELECT COUNT(*) FROM test_results res
                       INNER JOIN test_run_cases trc2 ON trc2.id = res.test_run_case_id
                      WHERE trc2.test_run_id = tr.id AND res.status = \'pass\') AS passed_cases
             FROM test_runs tr
             LEFT JOIN environments e ON e.id = tr.environment_id
             LEFT JOIN users u ON u.id = tr.assigned_to
             WHERE tr.plan_id = :p
             ORDER BY tr.created_at DESC'
        );
        $stmt->execute([':p' => $planId]);
        return $stmt->fetchAll();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function optionsForProject(int $projectId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, release_version, status FROM test_plans WHERE project_id = :p ORDER BY name ASC'
        );
        $stmt->execute([':p' => $projectId]);
        return $stmt->fetchAll();
    }
}