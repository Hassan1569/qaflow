<?php
declare(strict_types=1);

/**
 * QAFlow — Test run repository.
 *
 * SQL for test runs and their test_run_cases. A run references test cases via
 * test_run_cases; it never duplicates the case definition. Execution state
 * lives in test_results, one row per test_run_case.
 */

namespace QAFlow\Repositories;

use QAFlow\Config\Database;
use PDO;

final class TestRunRepository
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
            'SELECT tr.id, tr.project_id, tr.plan_id, tr.environment_id, tr.name,
                    tr.description, tr.status, tr.assigned_to, tr.created_by,
                    tr.started_at, tr.completed_at, tr.created_at, tr.updated_at,
                    p.key_code AS project_key, p.name AS project_name,
                    tp.name AS plan_name,
                    e.name AS environment_name,
                    u.name AS assigned_name,
                    cu.name AS creator_name
             FROM test_runs tr
             INNER JOIN projects p ON p.id = tr.project_id
             LEFT JOIN test_plans tp ON tp.id = tr.plan_id
             LEFT JOIN environments e ON e.id = tr.environment_id
             LEFT JOIN users u ON u.id = tr.assigned_to
             INNER JOIN users cu ON cu.id = tr.created_by
             WHERE tr.id = :id
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
            $where[] = 'tr.project_id = :pid';
            $params[':pid'] = (int) $filters['project_id'];
        }

        if (!empty($filters['plan_id'])) {
            $where[] = 'tr.plan_id = :plid';
            $params[':plid'] = (int) $filters['plan_id'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'tr.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['assigned_to'])) {
            $where[] = 'tr.assigned_to = :assignee';
            $params[':assignee'] = (int) $filters['assigned_to'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(tr.name LIKE :s OR tr.description LIKE :s)';
            $params[':s'] = '%' . $filters['search'] . '%';
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) AS c FROM test_runs tr $whereSql");
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) ($countStmt->fetch()['c'] ?? 0);

        $offset = max(0, ($page - 1) * $perPage);

        $sql = "SELECT tr.id, tr.project_id, tr.plan_id, tr.environment_id, tr.name,
                       tr.description, tr.status, tr.assigned_to, tr.created_by,
                       tr.started_at, tr.completed_at, tr.created_at, tr.updated_at,
                       p.key_code AS project_key,
                       e.name AS environment_name,
                       u.name AS assigned_name,
                       (SELECT COUNT(*) FROM test_run_cases trc WHERE trc.test_run_id = tr.id) AS total_cases,
                       (SELECT COUNT(*) FROM test_results res
                          INNER JOIN test_run_cases trc2 ON trc2.id = res.test_run_case_id
                         WHERE trc2.test_run_id = tr.id AND res.status = 'pass') AS passed_cases,
                       (SELECT COUNT(*) FROM test_results res
                          INNER JOIN test_run_cases trc3 ON trc3.id = res.test_run_case_id
                         WHERE trc3.test_run_id = tr.id AND res.status = 'fail') AS failed_cases,
                       (SELECT COUNT(*) FROM test_results res
                          INNER JOIN test_run_cases trc4 ON trc4.id = res.test_run_case_id
                         WHERE trc4.test_run_id = tr.id AND res.status = 'blocked') AS blocked_cases
                FROM test_runs tr
                INNER JOIN projects p ON p.id = tr.project_id
                LEFT JOIN environments e ON e.id = tr.environment_id
                LEFT JOIN users u ON u.id = tr.assigned_to
                $whereSql
                ORDER BY tr.updated_at DESC, tr.id DESC
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
            'INSERT INTO test_runs
                (project_id, plan_id, environment_id, name, description, status,
                 assigned_to, created_by)
             VALUES
                (:project, :plan, :env, :name, :desc, :status,
                 :assigned, :creator)'
        );

        $stmt->execute([
            ':project'  => $data['project_id'],
            ':plan'     => $data['plan_id'] ?? null,
            ':env'      => $data['environment_id'] ?? null,
            ':name'     => $data['name'],
            ':desc'     => $data['description'] ?? null,
            ':status'   => $data['status'] ?? 'not_started',
            ':assigned' => $data['assigned_to'] ?? null,
            ':creator'  => $data['created_by'],
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
            'plan_id', 'environment_id', 'name', 'description',
            'status', 'assigned_to', 'started_at', 'completed_at',
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

        $sql  = 'UPDATE test_runs SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM test_runs WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function markStarted(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE test_runs SET status = 'in_progress', started_at = COALESCE(started_at, NOW()) WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
    }

    public function markCompleted(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE test_runs SET status = 'completed', completed_at = NOW() WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
    }

    // -----------------------------------------------------------------------
    // Run cases
    // -----------------------------------------------------------------------

    /**
     * @return array<int,array<string,mixed>>
     */
    public function cases(int $runId): array
    {
        $sql = "SELECT trc.id AS run_case_id,
                       trc.test_case_id, trc.environment_id, trc.assignee_id,
                       tc.code, tc.title, tc.priority, tc.severity,
                       tc.test_type, tc.automation_status,
                       ts.name AS suite_name,
                       e.name AS environment_name,
                       u.name AS assignee_name,
                       res.id AS result_id, res.status AS result_status,
                       res.executed_at, res.executed_by,
                       eu.name AS executed_by_name
                FROM test_run_cases trc
                INNER JOIN test_cases tc ON tc.id = trc.test_case_id
                LEFT JOIN test_suites ts ON ts.id = tc.suite_id
                LEFT JOIN environments e ON e.id = trc.environment_id
                LEFT JOIN users u ON u.id = trc.assignee_id
                LEFT JOIN test_results res ON res.test_run_case_id = trc.id
                LEFT JOIN users eu ON eu.id = res.executed_by
                WHERE trc.test_run_id = :r
                ORDER BY tc.code ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':r' => $runId]);
        return $stmt->fetchAll();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findRunCase(int $runCaseId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, test_run_id, test_case_id, environment_id, assignee_id
             FROM test_run_cases WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $runCaseId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function addCase(int $runId, int $testCaseId, ?int $environmentId, ?int $assigneeId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO test_run_cases (test_run_id, test_case_id, environment_id, assignee_id)
             VALUES (:r, :tc, :env, :assignee)
             ON DUPLICATE KEY UPDATE environment_id = VALUES(environment_id), assignee_id = VALUES(assignee_id)'
        );

        $stmt->execute([
            ':r'        => $runId,
            ':tc'       => $testCaseId,
            ':env'      => $environmentId,
            ':assignee' => $assigneeId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function removeCase(int $runId, int $testCaseId): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM test_run_cases WHERE test_run_id = :r AND test_case_id = :tc'
        );
        $stmt->execute([':r' => $runId, ':tc' => $testCaseId]);
    }

    /**
     * @return array<string,mixed>
     */
    public function progress(int $runId): array
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN res.status = 'pass'    THEN 1 ELSE 0 END) AS passed,
                    SUM(CASE WHEN res.status = 'fail'    THEN 1 ELSE 0 END) AS failed,
                    SUM(CASE WHEN res.status = 'blocked' THEN 1 ELSE 0 END) AS blocked,
                    SUM(CASE WHEN res.status = 'not_run' OR res.status IS NULL THEN 1 ELSE 0 END) AS not_run
                FROM test_run_cases trc
                LEFT JOIN test_results res ON res.test_run_case_id = trc.id
                WHERE trc.test_run_id = :r";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':r' => $runId]);
        $row = $stmt->fetch() ?: [];

        return [
            'total'   => (int) ($row['total']   ?? 0),
            'passed'  => (int) ($row['passed']  ?? 0),
            'failed'  => (int) ($row['failed']  ?? 0),
            'blocked' => (int) ($row['blocked'] ?? 0),
            'not_run' => (int) ($row['not_run'] ?? 0),
        ];
    }
}