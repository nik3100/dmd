<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * User model - handles user data and authentication queries.
 */
class User extends Model
{
    protected static function tableName(): string
    {
        return 'users';
    }

    /**
     * Find user by email (for login).
     *
     * @return array<string, mixed>|null
     */
    public static function findByEmail(string $email): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM ' . static::tableName() . ' WHERE email = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * Find user by slug.
     *
     * @return array<string, mixed>|null
     */
    public static function findBySlug(string $slug): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM ' . static::tableName() . ' WHERE slug = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * Check if email exists.
     */
    public static function emailExists(string $email): bool
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM ' . static::tableName() . ' WHERE email = ? AND deleted_at IS NULL');
        $stmt->execute([$email]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Check if slug exists.
     */
    public static function slugExists(string $slug): bool
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM ' . static::tableName() . ' WHERE slug = ? AND deleted_at IS NULL');
        $stmt->execute([$slug]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Create a new user.
     *
     * @param array<string, mixed> $data
     * @return int User ID
     */
    public static function create(array $data): int
    {
        $sql = 'INSERT INTO ' . static::tableName() . ' 
                (email, password_hash, name, slug, phone, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())';
        
        $stmt = self::db()->prepare($sql);
        $stmt->execute([
            $data['email'],
            $data['password_hash'],
            $data['name'],
            $data['slug'],
            $data['phone'] ?? null,
        ]);
        
        return (int) self::db()->lastInsertId();
    }

    /**
     * Get user roles.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getRoles(int $userId): array
    {
        $sql = 'SELECT r.* FROM roles r
                INNER JOIN user_roles ur ON r.id = ur.role_id
                WHERE ur.user_id = ?';
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result !== false ? $result : [];
    }

    /**
     * Assign role to user.
     */
    public static function assignRole(int $userId, int $roleId): bool
    {
        $stmt = self::db()->prepare('INSERT IGNORE INTO user_roles (user_id, role_id, created_at) VALUES (?, ?, NOW())');
        return $stmt->execute([$userId, $roleId]);
    }

    /**
     * Update user.
     *
     * @param array<string, mixed> $data
     */
    public static function update(int $userId, array $data): bool
    {
        $allowed = ['name', 'email', 'phone', 'avatar_url', 'is_active'];
        $updates = [];
        $params = [];
        
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $updates[] = 'updated_at = NOW()';
        $params[] = $userId;
        
        $sql = 'UPDATE ' . static::tableName() . ' SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $stmt = self::db()->prepare($sql);
        return $stmt->execute($params);
    }
}
