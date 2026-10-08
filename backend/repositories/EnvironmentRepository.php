<?php
declare(strict_types=1);

/**
 * QAFlow — Environment repository.
 *
 * SQL for environments. Each environment belongs to a project and may be
 * attached to test runs, run cases, and defects. Environments are reusable
 * descriptors (browser, OS, device, app version), not execution state.
 */

namespace QAFlow\Repositories;

use QAFlow\Config\Database;
use PDO;

final class EnvironmentRepository
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
            'SELECT e.id, e.project_id, e.name, e.browser, e.browser_version,
                    e.os, e.os_version, e.device, e.device_version,
                    e.app_version, e.notes, e.created_at, e.updated_at,
                    p.key_code AS project_key
             FROM environments e
             INNER JOIN projects p ON p.id = e.project_id
             WHERE e.id = :id
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
            $where[] = 'e.project_id = :pid';
            $params[':pid'] = (int) $filters['project_id'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(e.name LIKE :s OR e.browser LIKE :s OR e.os LIKE :s OR e.device LIKE :s)';
            $params[':s'] = '%' . $filters['search'] . '%';
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) AS c FROM environments e $whereSql");
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) ($countStmt->fetch()['c'] ?? 0);

        $offset = max(0, ($page - 1) * $perPage);

        $sql = "SELECT e.id, e.project_id, e.name, e.browser, e.browser_version,
                       e.os, e.os_version, e.device, e.device_version,
                       e.app_version, e.notes, e.created_at, e.updated_at,
                       p.key_code AS project_key
                FROM environments e
                INNER JOIN projects p ON p.id = e.project_id
                $whereSql
                ORDER BY e.name ASC
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
            'INSERT INTO environments
                (project_id, name, browser, browser_version, os, os_version,
                 device, device_version, app_version, notes)
             VALUES
                (:project, :name, :browser, :bv, :os, :osv,
                 :device, :dv, :av, :notes)'
        );

        $stmt->execute([
            ':project' => $data['project_id'],
            ':name'    => $data['name'],
            ':browser' => $data['browser'] ?? null,
            ':bv'      => $data['browser_version'] ?? null,
            ':os'      => $data['os'] ?? null,
            ':osv'     => $data['os_version'] ?? null,
            ':device'  => $data['device'] ?? null,
            ':dv'      => $data['device_version'] ?? null,
            ':av'      => $data['app_version'] ?? null,
            ':notes'   => $data['notes'] ?? null,
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
            'name', 'browser', 'browser_version', 'os', 'os_version',
            'device', 'device_version', 'app_version', 'notes',
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

        $sql  = 'UPDATE environments SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM environments WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function forProject(int $projectId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, browser, browser_version, os, os_version,
                    device, device_version, app_version, notes
             FROM environments
             WHERE project_id = :p
             ORDER BY name ASC'
        );
        $stmt->execute([':p' => $projectId]);
        return $stmt->fetchAll();
    }

    /**
     * Count results per environment for a project. Used by dashboard
     * "environment-specific failures" chart.
     *
     * @return array<int,array<string,mixed>>
     */
    public function resultBreakdownForProject(int $projectId): array
    {
        $sql = "SELECT e.id AS environment_id, e.name AS environment_name,
                       COUNT(res.id) AS total,
                       SUM(CASE WHEN res.status = 'pass'    THEN 1 ELSE 0 END) AS passed,
                       SUM(CASE WHEN res.status = 'fail'    THEN 1 ELSE 0 END) AS failed,
                       SUM(CASE WHEN res.status = 'blocked' THEN 1 ELSE 0 END) AS blocked
                FROM environments e
                LEFT JOIN test_run_cases trc ON trc.environment_id = e.id
                LEFT JOIN test_runs tr ON tr.id = trc.test_run_id AND tr.project_id = e.project_id
                LEFT JOIN test_results res ON res.test_run_case_id = trc.id
                WHERE e.project_id = :p
                GROUP BY e.id, e.name
                ORDER BY e.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':p' => $projectId]);
        return $stmt->fetchAll();
    }
}