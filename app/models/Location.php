<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Location model - hierarchical locations (Country → State → District → Taluka → Village → Area → Locality).
 */
class Location extends Model
{
    public const TYPE_COUNTRY = 'country';
    public const TYPE_STATE = 'state';
    public const TYPE_DISTRICT = 'district';
    public const TYPE_TALUKA = 'taluka';
    public const TYPE_VILLAGE = 'village';
    public const TYPE_AREA = 'area';
    public const TYPE_LOCALITY = 'locality';

    /** Order of types from top to bottom in hierarchy */
    public const TYPE_HIERARCHY = [
        self::TYPE_COUNTRY,
        self::TYPE_STATE,
        self::TYPE_DISTRICT,
        self::TYPE_TALUKA,
        self::TYPE_VILLAGE,
        self::TYPE_AREA,
        self::TYPE_LOCALITY,
    ];

    protected static function tableName(): string
    {
        return 'locations';
    }

    /**
     * Get next type in hierarchy (child type).
     */
    public static function getNextType(string $type): ?string
    {
        $pos = array_search($type, self::TYPE_HIERARCHY, true);
        if ($pos === false || $pos >= count(self::TYPE_HIERARCHY) - 1) {
            return null;
        }
        return self::TYPE_HIERARCHY[$pos + 1];
    }

    /**
     * Get previous type in hierarchy (parent type).
     */
    public static function getParentType(string $type): ?string
    {
        $pos = array_search($type, self::TYPE_HIERARCHY, true);
        if ($pos === false || $pos <= 0) {
            return null;
        }
        return self::TYPE_HIERARCHY[$pos - 1];
    }

    /**
     * Get root locations (countries). Optionally only active.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getRoots(bool $activeOnly = true): array
    {
        $where = 'parent_id IS NULL AND deleted_at IS NULL AND type = ?';
        $params = [self::TYPE_COUNTRY];
        if ($activeOnly) {
            $where .= ' AND is_active = 1';
        }
        $where .= ' ORDER BY name ASC';
        return self::fetchAll($where, $params);
    }

    /**
     * Get children of a location (for AJAX hierarchical dropdown).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getChildren(int $parentId, bool $activeOnly = true): array
    {
        $where = 'parent_id = ? AND deleted_at IS NULL';
        $params = [$parentId];
        if ($activeOnly) {
            $where .= ' AND is_active = 1';
        }
        $where .= ' ORDER BY name ASC';
        return self::fetchAll($where, $params);
    }

    /**
     * Get children by parent ID - for API (returns id, name, type, slug).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getChildrenForSelect(int $parentId): array
    {
        $list = self::getChildren($parentId, true);
        return array_map(static function ($row) {
            return [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'type' => $row['type'],
            ];
        }, $list);
    }

    /**
     * Check if location has children.
     */
    public static function hasChildren(int $locationId): bool
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM ' . static::tableName() . ' WHERE parent_id = ? AND deleted_at IS NULL');
        $stmt->execute([$locationId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Find by ID (excluding soft-deleted).
     *
     * @return array<string, mixed>|null
     */
    public static function findById(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM ' . static::tableName() . ' WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
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
     * Get full location tree (recursive). Optionally only active.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getTree(bool $activeOnly = true): array
    {
        $where = 'deleted_at IS NULL';
        $params = [];
        if ($activeOnly) {
            $where .= ' AND is_active = 1';
        }
        $where .= ' ORDER BY type ASC, name ASC';
        $all = self::fetchAll($where, $params);
        return self::buildTree($all, null);
    }

    /**
     * Build tree structure from flat array.
     *
     * @param array<int, array<string, mixed>> $locations
     * @param int|null $parentId
     * @return array<int, array<string, mixed>>
     */
    private static function buildTree(array $locations, ?int $parentId): array
    {
        $tree = [];
        foreach ($locations as $loc) {
            $locParentId = $loc['parent_id'] !== null ? (int) $loc['parent_id'] : null;
            if ($locParentId === $parentId) {
                $loc['children'] = self::buildTree($locations, (int) $loc['id']);
                $tree[] = $loc;
            }
        }
        return $tree;
    }

    /**
     * Get location path (breadcrumb from root to this location).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getPath(int $locationId): array
    {
        $path = [];
        $current = self::findById($locationId);
        while ($current !== null) {
            array_unshift($path, $current);
            if ($current['parent_id'] !== null) {
                $current = self::findById((int) $current['parent_id']);
            } else {
                break;
            }
        }
        return $path;
    }

    /**
     * Search locations by name (indexed).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function search(string $query, ?string $type = null, int $limit = 50): array
    {
        $where = 'deleted_at IS NULL AND is_active = 1 AND name LIKE ?';
        $params = ['%' . $query . '%'];
        if ($type !== null) {
            $where .= ' AND type = ?';
            $params[] = $type;
        }
        $where .= ' ORDER BY name ASC LIMIT ' . (int) $limit;
        return self::fetchAll($where, $params);
    }

    /**
     * Create location.
     *
     * @param array<string, mixed> $data
     * @return int Location ID
     */
    public static function create(array $data): int
    {
        $sql = 'INSERT INTO ' . static::tableName() . ' 
                (parent_id, name, slug, type, code, latitude, longitude, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())';
        $stmt = self::db()->prepare($sql);
        $stmt->execute([
            $data['parent_id'] ?? null,
            $data['name'],
            $data['slug'],
            $data['type'],
            $data['code'] ?? null,
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $data['is_active'] ?? 1,
        ]);
        return (int) self::db()->lastInsertId();
    }

    /**
     * Update location.
     *
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): bool
    {
        $allowed = ['parent_id', 'name', 'slug', 'type', 'code', 'latitude', 'longitude', 'is_active'];
        $updates = [];
        $params = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
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
     * Soft delete. Prevents delete if has children.
     */
    public static function delete(int $id): array
    {
        if (self::hasChildren($id)) {
            return ['success' => false, 'message' => 'Cannot delete location with children. Delete or move children first.'];
        }
        $stmt = self::db()->prepare('UPDATE ' . static::tableName() . ' SET deleted_at = NOW() WHERE id = ?');
        $ok = $stmt->execute([$id]);
        return ['success' => $ok, 'message' => $ok ? 'Location deleted.' : 'Failed to delete.'];
    }

    /**
     * Slug from name.
     */
    public static function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

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

    public static function makeSlugUnique(string $slug, ?int $excludeId = null): string
    {
        $base = $slug;
        $n = 1;
        while (self::slugExists($slug, $excludeId)) {
            $slug = $base . '-' . $n;
            $n++;
        }
        return $slug;
    }
}
