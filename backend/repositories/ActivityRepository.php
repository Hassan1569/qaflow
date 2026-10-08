<?php
declare(strict_types=1);



namespace QAFlow\Repositories;

use QAFlow\Config\Database;
use PDO;

final class ActivityRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO activity_logs (user_id, project_id, action, entity_type, entity_id, metadata)
             VALUES (:user, :project, :action, :etype, :eid, :meta)'
        );

        $stmt->execute([
            ':user'    => $data['user_id']    ?? null,
            ':project' => $data['project_id'] ?? null,
            ':action'  => $data['action'],
            ':etype'   => $data['entity_type'],
            ':eid'     => $data['entity_id']  ?? null,
            ':meta'    => isset($data['metadata'])
                ? json_encode($data['metadata'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                : null,
        ]);

        return (int) $this->db->lastInsertId();
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
            $where[] = 'a.project_id = :pid';
            $params[':pid'] = (int) $filters['project_id'];
        }

        if (!empty($filters['user_id'])) {
            $where[] = 'a.user_id = :uid';
            $params[':uid'] = (int) $filters['user_id'];
        }

        if (!empty($filters['entity_type'])) {
            $where[] = 'a.entity_type = :etype';
            $params[':etype'] = (string) $filters['entity_type'];
        }

        if (!empty($filters['entity_id'])) {
            $where[] = 'a.entity_id = :eid';
            $params[':eid'] = (int) $filters['entity_id'];
        }

        if (!empty($filters['action'])) {
            $where[] = 'a.action = :action';
            $params[':action'] = (string) $filters['action'];
        }

        if (!empty($filters['since'])) {
            $where[] = 'a.created_at >= :since';
            $params[':since'] = (string) $filters['since'];
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) AS c FROM activity_logs a $whereSql");
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) ($countStmt->fetch()['c'] ?? 0);

        $offset = max(0, ($page - 1) * $perPage);

        $sql = "SELECT a.id, a.user_id, a.project_id, a.action, a.entity_type, a.entity_id,
                       a.metadata, a.created_at,
                       u.name AS user_name, u.email AS user_email,
                       p.key_code AS project_key
                FROM activity_logs a
                LEFT JOIN users u ON u.id = a.user_id
                LEFT JOIN projects p ON p.id = a.project_id
                $whereSql
                ORDER BY a.created_at DESC, a.id DESC
                LIMIT $perPage OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total];
    }

    /**
     * Feed for a single entity (defect, test case, etc.).
     *
     * @return array<int,array<string,mixed>>
     */
    public function forEntity(string $entityType, int $entityId, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));

        $sql = "SELECT a.id, a.user_id, a.action, a.entity_type, a.entity_id,
                       a.metadata, a.created_at,
                       u.name AS user_name
                FROM activity_logs a
                LEFT JOIN users u ON u.id = a.user_id
                WHERE a.entity_type = :t AND a.entity_id = :e
                ORDER BY a.created_at DESC, a.id DESC
                LIMIT $limit";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':t' => $entityType, ':e' => $entityId]);
        return $stmt->fetchAll();
    }

    /**
     * Recent activity for a project, used by the dashboard feed.
     *
     * @return array<int,array<string,mixed>>
     */
    public function recentForProject(int $projectId, int $limit = 20): array
    {
        $limit = max(1, min(200, $limit));

        $sql = "SELECT a.id, a.user_id, a.action, a.entity_type, a.entity_id,
                       a.metadata, a.created_at,
                       u.name AS user_name
                FROM activity_logs a
                LEFT JOIN users u ON u.id = a.user_id
                WHERE a.project_id = :p
                ORDER BY a.created_at DESC, a.id DESC
                LIMIT $limit";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':p' => $projectId]);
        return $stmt->fetchAll();
    }

    /**
     * Counts per action for a project, useful for lightweight analytics.
     *
     * @return array<string,int>
     */
    public function actionCountsForProject(int $projectId): array
    {
        $stmt = $this->db->prepare(
            'SELECT action, COUNT(*) AS c
             FROM activity_logs
             WHERE project_id = :p
             GROUP BY action'
        );
        $stmt->execute([':p' => $projectId]);

        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(string) $row['action']] = (int) $row['c'];
        }
        return $out;
    }
}