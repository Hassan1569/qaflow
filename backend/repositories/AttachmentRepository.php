<?php
declare(strict_types=1);

/**
 * QAFlow — Attachment repository.
 *
 * SQL for attachment metadata. Files themselves live on disk under
 * storage/uploads/{yyyy}/{mm}/ with UUID filenames. Attachments are
 * polymorphic: (entity_type, entity_id) points back to the owning row.
 */

namespace QAFlow\Repositories;

use QAFlow\Config\Database;
use PDO;

final class AttachmentRepository
{
    /** Entity types this repository recognizes. Keeps callers honest. */
    public const ENTITY_TYPES = [
        'defect',
        'test_case',
        'test_result',
        'requirement',
        'test_run',
        'comment',
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
            'SELECT a.id, a.entity_type, a.entity_id, a.original_name, a.stored_path,
                    a.mime_type, a.size_bytes, a.uploaded_by, a.created_at,
                    u.name AS uploaded_by_name
             FROM attachments a
             INNER JOIN users u ON u.id = a.uploaded_by
             WHERE a.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO attachments
                (entity_type, entity_id, original_name, stored_path, mime_type, size_bytes, uploaded_by)
             VALUES
                (:type, :eid, :oname, :spath, :mime, :size, :uploader)'
        );

        $stmt->execute([
            ':type'     => $data['entity_type'],
            ':eid'      => (int) $data['entity_id'],
            ':oname'    => $data['original_name'],
            ':spath'    => $data['stored_path'],
            ':mime'     => $data['mime_type'],
            ':size'     => (int) $data['size_bytes'],
            ':uploader' => (int) $data['uploaded_by'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listForEntity(string $entityType, int $entityId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.id, a.entity_type, a.entity_id, a.original_name, a.mime_type,
                    a.size_bytes, a.uploaded_by, a.created_at,
                    u.name AS uploaded_by_name
             FROM attachments a
             INNER JOIN users u ON u.id = a.uploaded_by
             WHERE a.entity_type = :t AND a.entity_id = :e
             ORDER BY a.created_at DESC'
        );
        $stmt->execute([':t' => $entityType, ':e' => $entityId]);
        return $stmt->fetchAll();
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM attachments WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function deleteForEntity(string $entityType, int $entityId): void
    {
        $stmt = $this->db->prepare('DELETE FROM attachments WHERE entity_type = :t AND entity_id = :e');
        $stmt->execute([':t' => $entityType, ':e' => $entityId]);
    }

    /**
     * Total size of files already attached to an entity. Useful to enforce
     * per-entity quotas if that becomes a requirement.
     */
    public function totalBytesForEntity(string $entityType, int $entityId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(size_bytes), 0) AS s
             FROM attachments
             WHERE entity_type = :t AND entity_id = :e'
        );
        $stmt->execute([':t' => $entityType, ':e' => $entityId]);
        $row = $stmt->fetch();
        return (int) ($row['s'] ?? 0);
    }
}