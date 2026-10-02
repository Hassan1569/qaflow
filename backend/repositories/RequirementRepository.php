<?php
declare(strict_types=1);

/**
 * QAFlow — Requirement repository.
 *
 * SQL for requirements, requirement versions, and requirement↔test-case
 * linkages used by traceability.
 */

namespace QAFlow\Repositories;

use QAFlow\Config\Database;
use PDO;

final class RequirementRepository
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
            'SELECT r.id, r.project_id, r.code, r.title, r.description, r.priority,
                    r.status, r.author_id, r.created_at, r.updated_at,
                    u.name AS author_name, p.name AS project_name, p.key_code AS project_key
             FROM requirements r
             INNER JOIN users u ON u.id = r.author_id
             INNER JOIN projects p ON p.id = r.project_id
             WHERE r.id = :id
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
            'SELECT id, code, title FROM requirements WHERE project_id = :p AND code = :c LIMIT 1'
        );
        $stmt->execute([':p' => $projectId, ':c' => $code]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Next code suffix for a project, e.g. `REQ-009` after `REQ-008`.
     */
    public function nextCode(int $projectId, string $prefix = 'REQ'): string
    {
        $stmt = $this->db->prepare(
            "SELECT code FROM requirements WHERE project_id = :p AND code LIKE :prefix
             ORDER BY CAST(SUBSTRING(code, LENGTH(:prefix) + 2) AS UNSIGNED) DESC LIMIT 1"
        );
        $stmt->execute([':p' => $projectId, ':prefix' => $prefix . '-%']);
        $last = $stmt->fetchColumn();

        $next = 1;
        if ($last !== false && is_string($last) && preg_match('/-(\d+)$/', $last, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return sprintf('%s-%03d', $prefix, $next);
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
            $where[] = 'r.project_id = :pid';
            $params[':pid'] = (int) $filters['project_id'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(r.code LIKE :s OR r.title LIKE :s OR r.description LIKE :s)';
            $params[':s'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status'])) {
            $where[] = 'r.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $where[] = 'r.priority = :priority';
            $params[':priority'] = $filters['priority'];
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) AS c FROM requirements r $whereSql");
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) ($countStmt->fetch()['c'] ?? 0);

        $offset = max(0, ($page - 1) * $perPage);

        $sql = "SELECT r.id, r.project_id, r.code, r.title, r.description, r.priority,
                       r.status, r.author_id, r.created_at, r.updated_at,
                       u.name AS author_name,
                       p.key_code AS project_key,
                       (SELECT COUNT(*) FROM test_case_requirements tcr WHERE tcr.requirement_id = r.id) AS test_case_count
                FROM requirements r
                INNER JOIN users u ON u.id = r.author_id
                INNER JOIN projects p ON p.id = r.project_id
                $whereSql
                ORDER BY r.updated_at DESC, r.id DESC
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
            'INSERT INTO requirements (project_id, code, title, description, priority, status, author_id)
             VALUES (:project, :code, :title, :desc, :priority, :status, :author)'
        );

        $stmt->execute([
            ':project'  => $data['project_id'],
            ':code'     => $data['code'],
            ':title'    => $data['title'],
            ':desc'     => $data['description'] ?? null,
            ':priority' => $data['priority'] ?? 'medium',
            ':status'   => $data['status'] ?? 'draft',
            ':author'   => $data['author_id'],
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

        foreach (['title', 'description', 'priority', 'status'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }

        if ($fields === []) {
            return;
        }

        $sql = 'UPDATE requirements SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM requirements WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    // -----------------------------------------------------------------------
    // Versions
    // -----------------------------------------------------------------------

    /**
     * @return array<int,array<string,mixed>>
     */
    public function versions(int $requirementId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, version_no, snapshot, changed_by, created_at
             FROM requirement_versions
             WHERE requirement_id = :r
             ORDER BY version_no DESC'
        );
        $stmt->execute([':r' => $requirementId]);
        return $stmt->fetchAll();
    }

    public function nextVersionNo(int $requirementId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(MAX(version_no), 0) AS v FROM requirement_versions WHERE requirement_id = :r'
        );
        $stmt->execute([':r' => $requirementId]);
        $row = $stmt->fetch();
        return ((int) ($row['v'] ?? 0)) + 1;
    }

    /**
     * @param array<string,mixed> $snapshot
     */
    public function storeVersion(int $requirementId, int $versionNo, array $snapshot, int $changedBy): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO requirement_versions (requirement_id, version_no, snapshot, changed_by)
             VALUES (:r, :v, :snap, :by)'
        );
        $stmt->execute([
            ':r'    => $requirementId,
            ':v'    => $versionNo,
            ':snap' => json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':by'   => $changedBy,
        ]);
    }

    // -----------------------------------------------------------------------
    // Linkages & traceability
    // -----------------------------------------------------------------------

    /**
     * @return array<int,array<string,mixed>>
     */
    public function testCasesFor(int $requirementId): array
    {
        $stmt = $this->db->prepare(
            'SELECT tc.id, tc.code, tc.title, tc.priority, tc.automation_status, tc.suite_id
             FROM test_case_requirements tcr
             INNER JOIN test_cases tc ON tc.id = tcr.test_case_id
             WHERE tcr.requirement_id = :r
             ORDER BY tc.code ASC'
        );
        $stmt->execute([':r' => $requirementId]);
        return $stmt->fetchAll();
    }

    /**
     * Full traceability matrix for a project.
     *
     * @return array<int,array<string,mixed>>
     */
    public function traceabilityMatrix(int $projectId): array
    {
        $sql = "SELECT
                    r.id AS requirement_id,
                    r.code AS requirement_code,
                    r.title AS requirement_title,
                    r.priority AS requirement_priority,
                    r.status AS requirement_status,
                    (SELECT COUNT(DISTINCT tcr.test_case_id)
                       FROM test_case_requirements tcr
                       INNER JOIN test_cases tc ON tc.id = tcr.test_case_id
                      WHERE tcr.requirement_id = r.id AND tc.project_id = r.project_id) AS test_case_count,
                    (SELECT COUNT(DISTINCT trc.test_case_id)
                       FROM test_case_requirements tcr
                       INNER JOIN test_run_cases trc ON trc.test_case_id = tcr.test_case_id
                       INNER JOIN test_runs tr ON tr.id = trc.test_run_id
                       INNER JOIN test_results res ON res.test_run_case_id = trc.id
                      WHERE tcr.requirement_id = r.id AND tr.project_id = r.project_id) AS executed_count,
                    (SELECT COUNT(DISTINCT trc.test_case_id)
                       FROM test_case_requirements tcr
                       INNER JOIN test_run_cases trc ON trc.test_case_id = tcr.test_case_id
                       INNER JOIN test_runs tr ON tr.id = trc.test_run_id
                       INNER JOIN test_results res ON res.test_run_case_id = trc.id
                      WHERE tcr.requirement_id = r.id AND tr.project_id = r.project_id AND res.status = 'pass') AS passed_count,
                    (SELECT COUNT(DISTINCT trc.test_case_id)
                       FROM test_case_requirements tcr
                       INNER JOIN test_run_cases trc ON trc.test_case_id = tcr.test_case_id
                       INNER JOIN test_runs tr ON tr.id = trc.test_run_id
                       INNER JOIN test_results res ON res.test_run_case_id = trc.id
                      WHERE tcr.requirement_id = r.id AND tr.project_id = r.project_id AND res.status = 'fail') AS failed_count,
                    (SELECT COUNT(DISTINCT trc.test_case_id)
                       FROM test_case_requirements tcr
                       INNER JOIN test_run_cases trc ON trc.test_case_id = tcr.test_case_id
                       INNER JOIN test_runs tr ON tr.id = trc.test_run_id
                       INNER JOIN test_results res ON res.test_run_case_id = trc.id
                      WHERE tcr.requirement_id = r.id AND tr.project_id = r.project_id AND res.status = 'blocked') AS blocked_count,
                    (SELECT COUNT(DISTINCT d.id)
                       FROM defects d
                      WHERE d.requirement_id = r.id AND d.project_id = r.project_id) AS defect_count
                FROM requirements r
                WHERE r.project_id = :p
                ORDER BY r.code ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':p' => $projectId]);
        return $stmt->fetchAll();
    }

    /**
     * Project-level coverage summary. Counts requirements with at least one
     * linked test case.
     *
     * @return array{total:int,covered:int,uncovered:int,coverage:float}
     */
    public function coverageSummary(int $projectId): array
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN EXISTS (
                        SELECT 1 FROM test_case_requirements tcr
                        INNER JOIN test_cases tc ON tc.id = tcr.test_case_id
                        WHERE tcr.requirement_id = r.id AND tc.project_id = r.project_id
                    ) THEN 1 ELSE 0 END) AS covered
                FROM requirements r
                WHERE r.project_id = :p";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':p' => $projectId]);
        $row = $stmt->fetch() ?: ['total' => 0, 'covered' => 0];

        $total   = (int) $row['total'];
        $covered = (int) $row['covered'];

        return [
            'total'     => $total,
            'covered'   => $covered,
            'uncovered' => max(0, $total - $covered),
            'coverage'  => $total > 0 ? round(($covered / $total) * 100, 2) : 0.0,
        ];
    }

    public function linkTestCase(int $requirementId, int $testCaseId): void
    {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO test_case_requirements (test_case_id, requirement_id) VALUES (:tc, :r)'
        );
        $stmt->execute([':tc' => $testCaseId, ':r' => $requirementId]);
    }

    public function unlinkTestCase(int $requirementId, int $testCaseId): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM test_case_requirements WHERE requirement_id = :r AND test_case_id = :tc'
        );
        $stmt->execute([':r' => $requirementId, ':tc' => $testCaseId]);
    }

    /**
     * @return array<int,int>
     */
    public function requirementIdsForTestCase(int $testCaseId): array
    {
        $stmt = $this->db->prepare(
            'SELECT requirement_id FROM test_case_requirements WHERE test_case_id = :tc'
        );
        $stmt->execute([':tc' => $testCaseId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'requirement_id'));
    }
}