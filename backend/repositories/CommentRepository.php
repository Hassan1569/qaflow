<?php
declare(strict_types=1);


namespace QAFlow\Repositories;

use QAFlow\Config\Database;
use PDO;

final class CommentRepository
{
    /** Entity types this repository recognizes. */
    public const ENTITY_TYPES = [
        'defect',
        'test_case',
        'requirement',
        'test_run',
        'test_plan',
    ];

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
            'SELECT c.id, c.entity_type, c.entity_id, c.content,
                    c.author_id, c.created_at, c.updated_at,
                    u.name AS author_name, u.role AS author_role
             FROM comments c
             INNER JOIN users u ON u.id = c.author_id
             WHERE c.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listForEntity(string $entityType, int $entityId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.entity_type, c.entity_id, c.content,
                    c.author_id, c.created_at, c.updated_at,
                    u.name AS author_name, u.role AS author_role
             FROM comments c
             INNER JOIN users u ON u.id = c.author_id
             WHERE c.entity_type = :t AND c.entity_id = :e
             ORDER BY c.created_at ASC'
        );
        $stmt->execute([':t' => $entityType, ':e' => $entityId]);
        return $stmt->fetchAll();
    }

    /**
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO comments (entity_type, entity_id, content, author_id)
             VALUES (:type, :eid, :content, :author)'
        );

        $stmt->execute([
            ':type'    => $data['entity_type'],
            ':eid'     => (int) $data['entity_id'],
            ':content' => $data['content'],
            ':author'  => (int) $data['author_id'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update the content of a comment. Only the author or an admin should
     * reach this path; the service enforces that.
     */
    public function update(int $id, string $content): void
    {
        $stmt = $this->db->prepare('UPDATE comments SET content = :c WHERE id = :id');
        $stmt->execute([':c' => $content, ':id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM comments WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function countForEntity(string $entityType, int $entityId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS c FROM comments WHERE entity_type = :t AND entity_id = :e'
        );
        $stmt->execute([':t' => $entityType, ':e' => $entityId]);
        $row = $stmt->fetch();
        return (int) ($row['c'] ?? 0);
    }
}