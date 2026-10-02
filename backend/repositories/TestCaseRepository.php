<?php
declare(strict_types=1);

/**
 * QAFlow — Test case repository.
 *
 * SQL for test cases, their steps, their version history, and their
 * requirement links. Steps are replaced wholesale on update; the service
 * snapshots the previous state first.
 */

namespace QAFlow\Repositories;

use QAFlow\Config\Database;
use PDO;

final class TestCaseRepository
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
            'SELECT tc.id, tc.project_id, tc.suite_id, tc.code, tc.title, tc.description,
                    tc.preconditions, tc.priority, tc.severity, tc.test_type, tc.component,
                    tc.tags, tc.automation_status, tc.external_id, tc.author_id,
                    tc.current_version, tc.created_at, tc.updated_at,
                    u.name AS author_name,
                    ts.name AS suite_name,
                    p.key_code AS project_key
             FROM test_cases tc
             INNER JOIN users u ON u.id = tc.author_id
             INNER JOIN projects p ON p.id = tc.project_id
             LEFT JOIN test_suites ts ON ts.id = tc.suite_id
             WHERE tc.id = :id
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
            'SELECT id, code, title FROM test_cases WHERE project_id = :p AND code = :c LIMIT 1'
        );
        $stmt->execute([':p' => $projectId, ':c' => $code]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function nextCode(int $projectId, string $prefix = 'TC'): string
    {
        $stmt = $this->db->prepare(
            "SELECT code FROM test_cases WHERE project_id = :p AND code LIKE :prefix
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
            $where[] = 'tc.project_id = :pid';
            $params[':pid'] = (int) $filters['project_id'];
        }

        if (!empty($filters['suite_id'])) {
            $where[] = 'tc.suite_id = :sid';
            $params[':sid'] = (int) $filters['suite_id'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(tc.code LIKE :s OR tc.title LIKE :s OR tc.description LIKE :s OR tc.tags LIKE :s)';
            $params[':s'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['priority'])) {
            $where[] = 'tc.priority = :priority';
            $params[':priority'] = $filters['priority'];
        }

        if (!empty($filters['test_type'])) {
            $where[] = 'tc.test_type = :type';
            $params[':type'] = $filters['test_type'];
        }

        if (!empty($filters['automation_status'])) {
            $where[] = 'tc.automation_status = :auto';
            $params[':auto'] = $filters['automation_status'];
        }

        if (!empty($filters['requirement_id'])) {
            $where[] = 'EXISTS (SELECT 1 FROM test_case_requirements tcr WHERE tcr.test_case_id = tc.id AND tcr.requirement_id = :rid)';
            $params[':rid'] = (int) $filters['requirement_id'];
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) AS c FROM test_cases tc $whereSql");
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) ($countStmt->fetch()['c'] ?? 0);

        $offset = max(0, ($page - 1) * $perPage);

        $sql = "SELECT tc.id, tc.project_id, tc.suite_id, tc.code, tc.title, tc.priority,
                       tc.severity, tc.test_type, tc.component, tc.tags,
                       tc.automation_status, tc.author_id, tc.current_version,
                       tc.created_at, tc.updated_at,
                       u.name AS author_name,
                       ts.name AS suite_name,
                       (SELECT COUNT(*) FROM test_case_requirements tcr WHERE tcr.test_case_id = tc.id) AS requirement_count
                FROM test_cases tc
                INNER JOIN users u ON u.id = tc.author_id
                LEFT JOIN test_suites ts ON ts.id = tc.suite_id
                $whereSql
                ORDER BY tc.updated_at DESC, tc.id DESC
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
            'INSERT INTO test_cases
                (project_id, suite_id, code, title, description, preconditions,
                 priority, severity, test_type, component, tags, automation_status,
                 external_id, author_id, current_version)
             VALUES
                (:project, :suite, :code, :title, :desc, :pre,
                 :priority, :severity, :type, :component, :tags, :auto,
                 :external, :author, 1)'
        );

        $stmt->execute([
            ':project'   => $data['project_id'],
            ':suite'     => $data['suite_id'] ?? null,
            ':code'      => $data['code'],
            ':title'     => $data['title'],
            ':desc'      => $data['description'] ?? null,
            ':pre'       => $data['preconditions'] ?? null,
            ':priority'  => $data['priority'] ?? 'medium',
            ':severity'  => $data['severity'] ?? 'medium',
            ':type'      => $data['test_type'] ?? 'functional',
            ':component' => $data['component'] ?? null,
            ':tags'      => $data['tags'] ?? null,
            ':auto'      => $data['automation_status'] ?? 'manual',
            ':external'  => $data['external_id'] ?? null,
            ':author'    => $data['author_id'],
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
            'suite_id', 'title', 'description', 'preconditions',
            'priority', 'severity', 'test_type', 'component', 'tags',
            'automation_status', 'external_id', 'current_version',
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

        $sql  = 'UPDATE test_cases SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM test_cases WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    // -----------------------------------------------------------------------
    // Steps
    // -----------------------------------------------------------------------

    /**
     * @return array<int,array<string,mixed>>
     */
    public function steps(int $testCaseId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, step_order, action, expected_result
             FROM test_steps
             WHERE test_case_id = :tc
             ORDER BY step_order ASC'
        );
        $stmt->execute([':tc' => $testCaseId]);
        return $stmt->fetchAll();
    }

    /**
     * Replace all steps for a test case. `$steps` is an ordered list of
     * ['action' => string, 'expected_result' => string].
     *
     * @param array<int,array<string,string>> $steps
     */
    public function replaceSteps(int $testCaseId, array $steps): void
    {
        $this->db->beginTransaction();
        try {
            $del = $this->db->prepare('DELETE FROM test_steps WHERE test_case_id = :tc');
            $del->execute([':tc' => $testCaseId]);

            if ($steps !== []) {
                $ins = $this->db->prepare(
                    'INSERT INTO test_steps (test_case_id, step_order, action, expected_result)
                     VALUES (:tc, :order, :action, :expected)'
                );

                $order = 1;
                foreach ($steps as $step) {
                    $ins->execute([
                        ':tc'       => $testCaseId,
                        ':order'    => $order,
                        ':action'   => $step['action'],
                        ':expected' => $step['expected_result'] ?? '',
                    ]);
                    $order++;
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // -----------------------------------------------------------------------
    // Versions
    // -----------------------------------------------------------------------

    /**
     * @return array<int,array<string,mixed>>
     */
    public function versions(int $testCaseId): array
    {
        $stmt = $this->db->prepare(
            'SELECT v.id, v.version_no, v.snapshot, v.changed_fields, v.changed_by, v.created_at,
                    u.name AS changed_by_name
             FROM test_case_versions v
             INNER JOIN users u ON u.id = v.changed_by
             WHERE v.test_case_id = :tc
             ORDER BY v.version_no DESC'
        );
        $stmt->execute([':tc' => $testCaseId]);
        return $stmt->fetchAll();
    }

    public function nextVersionNo(int $testCaseId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(MAX(version_no), 0) AS v FROM test_case_versions WHERE test_case_id = :tc'
        );
        $stmt->execute([':tc' => $testCaseId]);
        $row = $stmt->fetch();
        return ((int) ($row['v'] ?? 0)) + 1;
    }

    /**
     * @param array<string,mixed> $snapshot
     * @param array<int,string>   $changedFields
     */
    public function storeVersion(int $testCaseId, int $versionNo, array $snapshot, array $changedFields, int $changedBy): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO test_case_versions (test_case_id, version_no, snapshot, changed_fields, changed_by)
             VALUES (:tc, :v, :snap, :fields, :by)'
        );

        $stmt->execute([
            ':tc'     => $testCaseId,
            ':v'      => $versionNo,
            ':snap'   => json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':fields' => $changedFields === [] ? null : implode(',', $changedFields),
            ':by'     => $changedBy,
        ]);
    }

    // -----------------------------------------------------------------------
    // Requirement links
    // -----------------------------------------------------------------------

    public function linkRequirements(int $testCaseId, array $requirementIds): void
    {
        $del = $this->db->prepare('DELETE FROM test_case_requirements WHERE test_case_id = :tc');
        $del->execute([':tc' => $testCaseId]);

        if ($requirementIds === []) {
            return;
        }

        $ins = $this->db->prepare(
            'INSERT INTO test_case_requirements (test_case_id, requirement_id) VALUES (:tc, :r)'
        );

        foreach ($requirementIds as $rid) {
            $ins->execute([':tc' => $testCaseId, ':r' => (int) $rid]);
        }
    }

    /**
     * @return array<int,int>
     */
    public function requirementIds(int $testCaseId): array
    {
        $stmt = $this->db->prepare('SELECT requirement_id FROM test_case_requirements WHERE test_case_id = :tc');
        $stmt->execute([':tc' => $testCaseId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'requirement_id'));
    }
}