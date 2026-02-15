<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Category model - handles nested category hierarchy.
 */
class Category extends Model
{
    protected static function tableName(): string
    {
        return 'categories';
    }

    /**
     * Get all root categories (no parent).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getRoots(): array
    {
        return self::fetchAll('parent_id IS NULL AND deleted_at IS NULL', []);
    }

    /**
     * Get children of a category.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getChildren(int $parentId): array
    {
        return self::fetchAll('parent_id = ? AND deleted_at IS NULL', [$parentId]);
    }

    /**
     * Check if category has children.
     */
    public static function hasChildren(int $categoryId): bool
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM ' . static::tableName() . ' WHERE parent_id = ? AND deleted_at IS NULL');
        $stmt->execute([$categoryId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Get category count (excluding deleted).
     */
    public static function count(): int
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM ' . static::tableName() . ' WHERE deleted_at IS NULL');
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Find by slug.
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
     * Check if slug exists (excluding current ID).
     */
    public static function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM ' . static::tableName() . ' WHERE slug = ? AND deleted_at IS NULL';
        $params = [$slug];
        
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Get full category tree (recursive).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getTree(): array
    {
        $all = self::fetchAll('deleted_at IS NULL ORDER BY sort_order ASC, name ASC', []);
        return self::buildTree($all);
    }

    /**
     * Build tree structure from flat array.
     *
     * @param array<int, array<string, mixed>> $categories
     * @param int|null $parentId
     * @return array<int, array<string, mixed>>
     */
    private static function buildTree(array $categories, ?int $parentId = null): array
    {
        $tree = [];
        foreach ($categories as $category) {
            $catParentId = $category['parent_id'] !== null ? (int) $category['parent_id'] : null;
            if ($catParentId === $parentId) {
                $category['children'] = self::buildTree($categories, (int) $category['id']);
                $tree[] = $category;
            }
        }
        return $tree;
    }

    /**
     * Get category path (breadcrumb).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getPath(int $categoryId): array
    {
        $path = [];
        $current = self::find($categoryId);
        
        while ($current && $current['deleted_at'] === null) {
            array_unshift($path, $current);
            if ($current['parent_id'] !== null) {
                $current = self::find((int) $current['parent_id']);
            } else {
                break;
            }
        }
        
        return $path;
    }

    /**
     * Create category.
     *
     * @param array<string, mixed> $data
     * @return int Category ID
     */
    public static function create(array $data): int
    {
        $sql = 'INSERT INTO ' . static::tableName() . ' 
                (parent_id, name, slug, description, sort_order, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())';
        
        $stmt = self::db()->prepare($sql);
        $stmt->execute([
            $data['parent_id'] ?? null,
            $data['name'],
            $data['slug'],
            $data['description'] ?? null,
            $data['sort_order'] ?? 0,
            $data['is_active'] ?? 1,
        ]);
        
        return (int) self::db()->lastInsertId();
    }

    /**
     * Update category.
     *
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): bool
    {
        $allowed = ['parent_id', 'name', 'slug', 'description', 'sort_order', 'is_active'];
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
        $params[] = $id;
        
        $sql = 'UPDATE ' . static::tableName() . ' SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $stmt = self::db()->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Soft delete category (only if no children).
     */
    public static function delete(int $id): array
    {
        // Check if has children
        if (self::hasChildren($id)) {
            return ['success' => false, 'message' => 'Cannot delete category with children. Please delete or move children first.'];
        }
        
        $stmt = self::db()->prepare('UPDATE ' . static::tableName() . ' SET deleted_at = NOW() WHERE id = ?');
        $success = $stmt->execute([$id]);
        
        return [
            'success' => $success,
            'message' => $success ? 'Category deleted successfully.' : 'Failed to delete category.'
        ];
    }

    /**
     * Generate slug from name.
     */
    public static function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * Make slug unique.
     */
    public static function makeSlugUnique(string $slug, ?int $excludeId = null): string
    {
        $baseSlug = $slug;
        $counter = 1;
        
        while (self::slugExists($slug, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}
