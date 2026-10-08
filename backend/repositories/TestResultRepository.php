<?php
declare(strict_types=1);

/**
 * QAFlow — Test result repository.
 *
 * SQL for test_results (one row per test_run_case) and test_step_results
 * (one row per step of the referenced test case).
 *
 * Execution flow:
 *   - The service upserts a test_results row for the run_case.
 *   - Each step writes a test_step_results row keyed by step_order.
 *   - The overall result is either computed from steps or overridden by the
 *     caller via saveResult(status).
 */

namespace QAFlow\Repositories;

use QAFlow\Config\Database;
use PDO;

final class TestResultRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findByRunCase(int $runCaseId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, test_run_case_id, status, actual_result, notes,
                    executed_by, is_automated, duration_ms, executed_at,
                    created_at, updated_at
             FROM test_results
             WHERE test_run_case_id = :rc
             LIMIT 1'
        );
        $stmt->execute([':rc' => $runCaseId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Full detail for one execution: run + run_case + case + steps + results.
     *
     * @return array<string,mixed>|null
     */
    public function executionDetail(int $runCaseId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT trc.id AS run_case_id, trc.test_run_id, trc.test_case_id,
                    trc.environment_id, trc.assignee_id,
                    tr.id AS run_id, tr.name AS run_name, tr.status AS run_status,
                    tr.project_id, p.key_code AS project_key,
                    tc.code AS case_code, tc.title AS case_title,
                    tc.description AS case_description, tc.preconditions AS case_preconditions,
                    tc.priority AS case_priority, tc.severity AS case_severity,
                    tc.test_type AS case_type,
                    e.name AS environment_name,
                    u.name AS assignee_name,
                    res.id AS result_id, res.status AS result_status,
                    res.actual_result, res.notes,
                    res.executed_by, res.is_automated, res.duration_ms, res.executed_at,
                    eu.name AS executed_by_name
             FROM test_run_cases trc
             INNER JOIN test_runs tr ON tr.id = trc.test_run_id
             INNER JOIN projects p ON p.id = tr.project_id
             INNER JOIN test_cases tc ON tc.id = trc.test_case_id
             LEFT JOIN environments e ON e.id = trc.environment_id
             LEFT JOIN users u ON u.id = trc.assignee_id
             LEFT JOIN test_results res ON res.test_run_case_id = trc.id
             LEFT JOIN users eu ON eu.id = res.executed_by
             WHERE trc.id = :rc
             LIMIT 1'
        );
        $stmt->execute([':rc' => $runCaseId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Upsert the overall result. Status defaults to not_run when the caller
     * passes nothing useful.
     *
     * @param array<string,mixed> $data
     */
    public function upsertResult(int $runCaseId, array $data): int
    {
        $existing = $this->findByRunCase($runCaseId);

        if ($existing === null) {
            $stmt = $this->db->prepare(
                'INSERT INTO test_results
                    (test_run_case_id, status, actual_result, notes,
                     executed_by, is_automated, duration_ms, executed_at)
                 VALUES
                    (:rc, :status, :actual, :notes,
                     :by, :auto, :duration, :executed_at)'
            );

            $stmt->execute([
                ':rc'          => $runCaseId,
                ':status'      => $data['status']      ?? 'not_run',
                ':actual'      => $data['actual_result'] ?? null,
                ':notes'       => $data['notes']       ?? null,
                ':by'          => $data['executed_by'] ?? null,
                ':auto'        => !empty($data['is_automated']) ? 1 : 0,
                ':duration'    => $data['duration_ms'] ?? null,
                ':executed_at' => $data['executed_at'] ?? date('Y-m-d H:i:s'),
            ]);

            return (int) $this->db->lastInsertId();
        }

        $id = (int) $existing['id'];

        $fields = [];
        $params = [':id' => $id];

        $columns = ['status', 'actual_result', 'notes', 'executed_by', 'is_automated', 'duration_ms', 'executed_at'];

        foreach ($columns as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = :$col";
                $params[":$col"] = $col === 'is_automated' ? (!empty($data[$col]) ? 1 : 0) : $data[$col];
            }
        }

        if ($fields !== []) {
            $sql  = 'UPDATE test_results SET ' . implode(', ', $fields) . ' WHERE id = :id';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        }

        return $id;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function stepResults(int $testResultId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, step_order, status, actual_result, notes
             FROM test_step_results
             WHERE test_result_id = :r
             ORDER BY step_order ASC'
        );
        $stmt->execute([':r' => $testResultId]);
        return $stmt->fetchAll();
    }

    /**
     * Insert or update a single step result.
     */
    public function saveStepResult(
        int $testResultId,
        int $stepOrder,
        string $status,
        ?string $actualResult,
        ?string $notes
    ): void {
        $stmt = $this->db->prepare(
            'INSERT INTO test_step_results (test_result_id, step_order, status, actual_result, notes)
             VALUES (:r, :o, :s, :a, :n)
             ON DUPLICATE KEY UPDATE status = VALUES(status),
                                     actual_result = VALUES(actual_result),
                                     notes = VALUES(notes)'
        );

        $stmt->execute([
            ':r' => $testResultId,
            ':o' => $stepOrder,
            ':s' => $status,
            ':a' => $actualResult,
            ':n' => $notes,
        ]);
    }

    /**
     * Bulk save step results in one transaction. Used by the automation
     * ingestion endpoint and by the front-end save-all action.
     *
     * @param array<int,array{step_order:int,status:string,actual_result?:string,notes?:string}> $steps
     */
    public function saveStepResults(int $testResultId, array $steps): void
    {
        if ($steps === []) {
            return;
        }

        $this->db->beginTransaction();
        try {
            foreach ($steps as $step) {
                $this->saveStepResult(
                    $testResultId,
                    (int) $step['step_order'],
                    (string) $step['status'],
                    isset($step['actual_result']) ? (string) $step['actual_result'] : null,
                    isset($step['notes']) ? (string) $step['notes'] : null,
                );
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Compute the overall status from step results. Steps that are all pass
     * give pass; any fail gives fail; any blocked (and no fail) gives blocked;
     * no results gives not_run.
     */
    public function computedStatus(int $testResultId): string
    {
        $stmt = $this->db->prepare(
            'SELECT status, COUNT(*) AS c FROM test_step_results WHERE test_result_id = :r GROUP BY status'
        );
        $stmt->execute([':r' => $testResultId]);
        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[(string) $row['status']] = (int) $row['c'];
        }

        if ($counts === []) {
            return 'not_run';
        }

        if (($counts['fail'] ?? 0) > 0) {
            return 'fail';
        }
        if (($counts['blocked'] ?? 0) > 0) {
            return 'blocked';
        }
        if (($counts['pass'] ?? 0) > 0 && !isset($counts['not_run'])) {
            return 'pass';
        }

        return 'not_run';
    }

    // -----------------------------------------------------------------------
    // Project aggregates (used by dashboard and reports)
    // -----------------------------------------------------------------------

    /**
     * @return array<string,int>
     */
    public function countsByStatusForProject(int $projectId): array
    {
        $sql = "SELECT res.status, COUNT(*) AS c
                FROM test_results res
                INNER JOIN test_run_cases trc ON trc.id = res.test_run_case_id
                INNER JOIN test_runs tr ON tr.id = trc.test_run_id
                WHERE tr.project_id = :p
                GROUP BY res.status";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':p' => $projectId]);

        $out = ['pass' => 0, 'fail' => 0, 'blocked' => 0, 'not_run' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $out[(string) $row['status']] = (int) $row['c'];
        }
        return $out;
    }

    /**
     * @return array<string,int>
     */
    public function countsByStatusForRun(int $runId): array
    {
        $sql = "SELECT res.status, COUNT(*) AS c
                FROM test_results res
                INNER JOIN test_run_cases trc ON trc.id = res.test_run_case_id
                WHERE trc.test_run_id = :r
                GROUP BY res.status";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':r' => $runId]);

        $out = ['pass' => 0, 'fail' => 0, 'blocked' => 0, 'not_run' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $out[(string) $row['status']] = (int) $row['c'];
        }
        return $out;
    }

    /**
     * @return array<string,int>
     */
    public function countsByStatusForRunCase(int $runCaseId): array
    {
        $stmt = $this->db->prepare(
            'SELECT status, COUNT(*) AS c FROM test_step_results
             WHERE test_result_id = (SELECT id FROM test_results WHERE test_run_case_id = :rc LIMIT 1)
             GROUP BY status'
        );
        $stmt->execute([':rc' => $runCaseId]);

        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(string) $row['status']] = (int) $row['c'];
        }
        return $out;
    }

    /**
     * Latest 30 executions for a project, used by the activity feed.
     *
     * @return array<int,array<string,mixed>>
     */
    public function recentForProject(int $projectId, int $limit = 30): array
    {
        $limit = max(1, min(200, $limit));

        $sql = "SELECT res.id, res.status, res.executed_at,
                       trc.id AS run_case_id, trc.test_case_id,
                       tc.code AS case_code, tc.title AS case_title,
                       tr.id AS run_id, tr.name AS run_name,
                       u.name AS executed_by_name
                FROM test_results res
                INNER JOIN test_run_cases trc ON trc.id = res.test_run_case_id
                INNER JOIN test_cases tc ON tc.id = trc.test_case_id
                INNER JOIN test_runs tr ON tr.id = trc.test_run_id
                LEFT JOIN users u ON u.id = res.executed_by
                WHERE tr.project_id = :p
                ORDER BY res.executed_at DESC, res.id DESC
                LIMIT $limit";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':p' => $projectId]);
        return $stmt->fetchAll();
    }
}