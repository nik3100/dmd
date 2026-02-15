<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Category suggestion model - user-submitted categories awaiting approval.
 */
class CategorySuggestion extends Model
{
    protected static function tableName(): string
    {
        return 'category_suggestions';
    }

    /**
     * Get all suggestions (pending, approved, rejected).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(string $status = 'pending'): array
    {
        $sql = 'SELECT cs.*, u.name as user_name, u.email as user_email 
                FROM ' . static::tableName() . ' cs
                LEFT JOIN users u ON cs.user_id = u.id
                WHERE cs.status = ?
                ORDER BY cs.created_at DESC';
        
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$status]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result !== false ? $result : [];
    }

    /**
     * Create suggestion.
     *
     * @param array<string, mixed> $data
     * @return int Suggestion ID
     */
    public static function create(array $data): int
    {
        $sql = 'INSERT INTO ' . static::tableName() . ' 
                (user_id, name, description, parent_id, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())';
        
        $stmt = self::db()->prepare($sql);
        $stmt->execute([
            $data['user_id'],
            $data['name'],
            $data['description'] ?? null,
            $data['parent_id'] ?? null,
            $data['status'] ?? 'pending',
        ]);
        
        return (int) self::db()->lastInsertId();
    }

    /**
     * Update status (approve/reject).
     */
    public static function updateStatus(int $id, string $status, ?int $approvedBy = null): bool
    {
        $sql = 'UPDATE ' . static::tableName() . ' 
                SET status = ?, approved_by = ?, updated_at = NOW() 
                WHERE id = ?';
        
        $stmt = self::db()->prepare($sql);
        return $stmt->execute([$status, $approvedBy, $id]);
    }

    /**
     * Find by ID.
     *
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $sql = 'SELECT cs.*, u.name as user_name, u.email as user_email 
                FROM ' . static::tableName() . ' cs
                LEFT JOIN users u ON cs.user_id = u.id
                WHERE cs.id = ? LIMIT 1';
        
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }
}
