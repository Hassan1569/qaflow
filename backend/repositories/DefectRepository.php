<?php
declare(strict_types=1);

/**
 * QAFlow — Defect repository.
 *
 * SQL for defects. Severity and priority are separate concepts and separate
 * columns. A defect can reference a test case, a requirement, a test result,
 * and an environment; those links are optional because defects may be filed
 * manually without an execution.
 */

namespace QAFlow\Repositories;

use QAFlow\Config\Database;
use PDO;

final class DefectRepository
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
            'SELECT d.id, d.project_id, d.code, d.title, d.description,
                    d.expected_result, d.actual_result, d.severity, d.priority,
                    d.status, d.test_case_id, d.requirement_id, d.test_result_id,
                    d.environment_id, d.reporter_id, d.assignee_id,
                    d.created_at, d.updated_at,
                    p.key_code AS project_key, p.name AS project_name,
                    tc.code AS test_case_code, tc.title AS test_case_title,
                    r.code AS requirement_code, r.title AS requirement_title,
                    e.name AS environment_name,
                    ru.name AS reporter_name,
                    au.name AS assignee_name
             FROM defects d
             INNER JOIN projects p ON p.id = d.project_id
             INNER JOIN users ru ON ru.id = d.reporter_id
             LEFT JOIN test_cases tc ON tc.id = d.test_case_id
             LEFT JOIN requirements r ON r.id = d.requirement_id
             LEFT JOIN environments e ON e.id = d.environment_id
             LEFT JOIN users au ON au.id = d.assignee_id
             WHERE d.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findByCode(int $projectId, string $code): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, code, title FROM defects WHERE project_id = :p AND code = :c LIMIT 1'
        );
        $stmt->execute([':p' => $projectId, ':c' => $code]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function nextCode(int $projectId, string $prefix = 'DEF'): string
    {
        $stmt = $this->db->prepare(
            "SELECT code FROM defects WHERE project_id = :p AND code LIKE :prefix
             ORDER BY CAST(SUBSTRING(code, LENGTH(:prefix) + 2) AS UNSIGNED) DESC LIMIT 1"
        );
        $stmt->execute([':p' => $projectId, ':prefix' => $prefix . '-%']);
        $last = $stmt->fetchColumn();

        // Start at 1001 to match the seed data convention.
        $next = 1001;
        if ($last !== false && is_string($last) && preg_match('/-(\d+)$/', $last, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return sprintf('%s-%d', $prefix, $next);
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
            $where[] = 'd.project_id = :pid';
            $params[':pid'] = (int) $filters['project_id'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'd.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['severity'])) {
            $where[] = 'd.severity = :severity';
            $params[':severity'] = $filters['severity'];
        }

        if (!empty($filters['priority'])) {
            $where[] = 'd.priority = :priority';
            $params[':priority'] = $filters['priority'];
        }

        if (!empty($filters['assignee_id'])) {
            $where[] = 'd.assignee_id = :assignee';
            $params[':assignee'] = (int) $filters['assignee_id'];
        }

        if (!empty($filters['test_case_id'])) {
            $where[] = 'd.test_case_id = :tcid';
            $params[':tcid'] = (int) $filters['test_case_id'];
        }

        if (!empty($filters['requirement_id'])) {
            $where[] = 'd.requirement_id = :rid';
            $params[':rid'] = (int) $filters['requirement_id'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(d.code LIKE :s OR d.title LIKE :s OR d.description LIKE :s)';
            $params[':s'] = '%' . $filters['search'] . '%';
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) AS c FROM defects d $whereSql");
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) ($countStmt->fetch()['c'] ?? 0);

        $offset = max(0, ($page - 1) * $perPage);

        $sql = "SELECT d.id, d.project_id, d.code, d.title, d.severity, d.priority,
                       d.status, d.test_case_id, d.requirement_id, d.environment_id,
                       d.reporter_id, d.assignee_id, d.created_at, d.updated_at,
                       tc.code AS test_case_code,
                       r.code AS requirement_code,
                       e.name AS environment_name,
                       ru.name AS reporter_name,
                       au.name AS assignee_name
                FROM defects d
                LEFT JOIN test_cases tc ON tc.id = d.test_case_id
                LEFT JOIN requirements r ON r.id = d.requirement_id
                LEFT JOIN environments e ON e.id = d.environment_id
                INNER JOIN users ru ON ru.id = d.reporter_id
                LEFT JOIN users au ON au.id = d.assignee_id
                $whereSql
                ORDER BY d.updated_at DESC, d.id DESC
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
            'INSERT INTO defects
                (project_id, code, title, description, expected_result, actual_result,
                 severity, priority, status, test_case_id, requirement_id, test_result_id,
                 environment_id, reporter_id, assignee_id)
             VALUES
                (:project, :code, :title, :desc, :expected, :actual,
                 :severity, :priority, :status, :tcid, :rid, :resid,
                 :envid, :reporter, :assignee)'
        );

        $stmt->execute([
            ':project'  => $data['project_id'],
            ':code'     => $data['code'],
            ':title'    => $data['title'],
            ':desc'     => $data['description'] ?? null,
            ':expected' => $data['expected_result'] ?? null,
            ':actual'   => $data['actual_result'] ?? null,
            ':severity' => $data['severity'] ?? 'medium',
            ':priority' => $data['priority'] ?? 'medium',
            ':status'   => $data['status'] ?? 'open',
            ':tcid'     => $data['test_case_id'] ?? null,
            ':rid'      => $data['requirement_id'] ?? null,
            ':resid'    => $data['test_result_id'] ?? null,
            ':envid'    => $data['environment_id'] ?? null,
            ':reporter' => $data['reporter_id'],
            ':assignee' => $data['assignee_id'] ?? null,
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
            'title', 'description', 'expected_result', 'actual_result',
            'severity', 'priority', 'status',
            'test_case_id', 'requirement_id', 'test_result_id',
            'environment_id', 'assignee_id',
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

        $sql  = 'UPDATE defects SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE defects SET status = :s WHERE id = :id');
        $stmt->execute([':s' => $status, ':id' => $id]);
    }

    public function assign(int $id, ?int $userId): void
    {
        $stmt = $this->db->prepare('UPDATE defects SET assignee_id = :u WHERE id = :id');
        $stmt->execute([':u' => $userId, ':id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM defects WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /**
     * @param array<string,int> $filters
     * @return array<int,array<string,mixed>>
     */
    public function forTestCase(int $testCaseId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, code, title, severity, priority, status, created_at
             FROM defects
             WHERE test_case_id = :tc
             ORDER BY created_at DESC'
        );
        $stmt->execute([':tc' => $testCaseId]);
        return $stmt->fetchAll();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function forRequirement(int $requirementId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, code, title, severity, priority, status, created_at
             FROM defects
             WHERE requirement_id = :r
             ORDER BY created_at DESC'
        );
        $stmt->execute([':r' => $requirementId]);
        return $stmt->fetchAll();
    }

    // -----------------------------------------------------------------------
    // Aggregates for dashboard and reports
    // -----------------------------------------------------------------------

    /**
     * @return array<string,int>
     */
    public function countsBySeverityForProject(int $projectId): array
    {
        $stmt = $this->db->prepare(
            'SELECT severity, COUNT(*) AS c FROM defects WHERE project_id = :p GROUP BY severity'
        );
        $stmt->execute([':p' => $projectId]);

        $out = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $out[(string) $row['severity']] = (int) $row['c'];
        }
        return $out;
    }

    /**
     * @return array<string,int>
     */
    public function countsByStatusForProject(int $projectId): array
    {
        $stmt = $this->db->prepare(
            'SELECT status, COUNT(*) AS c FROM defects WHERE project_id = :p GROUP BY status'
        );
        $stmt->execute([':p' => $projectId]);

        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(string) $row['status']] = (int) $row['c'];
        }
        return $out;
    }

    public function countOpenForProject(int $projectId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS c FROM defects
             WHERE project_id = :p AND status NOT IN ('closed','verified','rejected','duplicate')"
        );
        $stmt->execute([':p' => $projectId]);
        $row = $stmt->fetch();
        return (int) ($row['c'] ?? 0);
    }

    public function countCriticalOpenForProject(int $projectId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS c FROM defects
             WHERE project_id = :p
               AND severity = 'critical'
               AND status NOT IN ('closed','verified','rejected','duplicate')"
        );
        $stmt->execute([':p' => $projectId]);
        $row = $stmt->fetch();
        return (int) ($row['c'] ?? 0);
    }
}