<?php
declare(strict_types=1);

/**
 * QAFlow — User repository.
 *
 * All SQL for users, auth tokens, and project memberships lives here. Methods
 * return arrays; services shape the response. No business rules here.
 */

namespace QAFlow\Repositories;

use QAFlow\Config\Database;
use PDO;

final class UserRepository
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
            'SELECT id, name, email, password_hash, role, is_active, created_at, updated_at
             FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, password_hash, role, is_active, created_at, updated_at
             FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
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

        if (!empty($filters['search'])) {
            $where[] = '(name LIKE :s OR email LIKE :s)';
            $params[':s'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['role'])) {
            $where[] = 'role = :role';
            $params[':role'] = $filters['role'];
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $where[] = 'is_active = :active';
            $params[':active'] = (int) $filters['is_active'];
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) AS c FROM users $whereSql");
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) ($countStmt->fetch()['c'] ?? 0);

        $offset = max(0, ($page - 1) * $perPage);

        $sql = "SELECT id, name, email, role, is_active, created_at, updated_at
                FROM users
                $whereSql
                ORDER BY created_at DESC, id DESC
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
            'INSERT INTO users (name, email, password_hash, role, is_active)
             VALUES (:name, :email, :hash, :role, :active)'
        );

        $stmt->execute([
            ':name'   => $data['name'],
            ':email'  => $data['email'],
            ':hash'   => $data['password_hash'],
            ':role'   => $data['role'],
            ':active' => isset($data['is_active']) ? (int) $data['is_active'] : 1,
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

        foreach (['name', 'email', 'role', 'is_active'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }

        if ($fields === []) {
            return;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function deactivate(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE users SET is_active = 0 WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $stmt = $this->db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute([':hash' => $hash, ':id' => $id]);
    }

    // -----------------------------------------------------------------------
    // Tokens
    // -----------------------------------------------------------------------

    public function storeToken(int $userId, string $token, string $expiresAt): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (:u, :t, :e)'
        );
        $stmt->execute([':u' => $userId, ':t' => $token, ':e' => $expiresAt]);
    }

    public function deleteToken(string $token): void
    {
        $stmt = $this->db->prepare('DELETE FROM auth_tokens WHERE token = :t');
        $stmt->execute([':t' => $token]);
    }

    public function deleteExpiredTokens(): int
    {
        $stmt = $this->db->prepare('DELETE FROM auth_tokens WHERE expires_at < NOW()');
        $stmt->execute();
        return $stmt->rowCount();
    }

    // -----------------------------------------------------------------------
    // Project memberships
    // -----------------------------------------------------------------------

    /**
     * @return array<int,array<string,mixed>>
     */
    public function projectMemberships(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT pm.project_id, pm.role, p.key_code, p.name AS project_name
             FROM project_members pm
             INNER JOIN projects p ON p.id = pm.project_id
             WHERE pm.user_id = :u
             ORDER BY p.name ASC'
        );
        $stmt->execute([':u' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function activeOptions(): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, role FROM users WHERE is_active = 1 ORDER BY name ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}