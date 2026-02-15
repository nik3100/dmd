<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Listing model - business directory listings with approval workflow.
 */
class Listing extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_SUSPENDED = 'suspended';

    protected static function tableName(): string
    {
        return 'listings';
    }

    /**
     * Get only approved listings (public).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getApproved(string $orderBy = 'created_at', string $dir = 'DESC', int $limit = 50, int $offset = 0): array
    {
        $allowed = ['created_at', 'view_count', 'title'];
        $orderBy = in_array($orderBy, $allowed, true) ? $orderBy : 'created_at';
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';
        $sql = 'SELECT l.*, c.name as category_name, loc.name as location_name 
                FROM ' . static::tableName() . ' l 
                LEFT JOIN categories c ON l.category_id = c.id AND c.deleted_at IS NULL
                LEFT JOIN locations loc ON l.location_id = loc.id AND loc.deleted_at IS NULL
                WHERE l.status = ? AND l.deleted_at IS NULL 
                ORDER BY l.' . $orderBy . ' ' . $dir . ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        $stmt = self::db()->prepare($sql);
        $stmt->execute([self::STATUS_APPROVED]);
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $r !== false ? $r : [];
    }

    /**
     * Find by slug (public: only approved).
     *
     * @return array<string, mixed>|null
     */
    public static function findBySlugPublic(string $slug): ?array
    {
        $sql = 'SELECT l.*, c.name as category_name, loc.name as location_name 
                FROM ' . static::tableName() . ' l 
                LEFT JOIN categories c ON l.category_id = c.id AND c.deleted_at IS NULL
                LEFT JOIN locations loc ON l.location_id = loc.id AND loc.deleted_at IS NULL
                WHERE l.slug = ? AND l.status = ? AND l.deleted_at IS NULL LIMIT 1';
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$slug, self::STATUS_APPROVED]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * Find by id (any status, for owner/admin).
     *
     * @return array<string, mixed>|null
     */
    public static function findById(int $id): ?array
    {
        $sql = 'SELECT l.*, c.name as category_name, loc.name as location_name 
                FROM ' . static::tableName() . ' l 
                LEFT JOIN categories c ON l.category_id = c.id AND c.deleted_at IS NULL
                LEFT JOIN locations loc ON l.location_id = loc.id AND loc.deleted_at IS NULL
                WHERE l.id = ? AND l.deleted_at IS NULL LIMIT 1';
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * Get listings by user (for dashboard).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getByUserId(int $userId): array
    {
        $sql = 'SELECT l.*, c.name as category_name, loc.name as location_name 
                FROM ' . static::tableName() . ' l 
                LEFT JOIN categories c ON l.category_id = c.id AND c.deleted_at IS NULL
                LEFT JOIN locations loc ON l.location_id = loc.id AND loc.deleted_at IS NULL
                WHERE l.user_id = ? AND l.deleted_at IS NULL ORDER BY l.updated_at DESC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$userId]);
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $r !== false ? $r : [];
    }

    /**
     * Get pending approvals (admin).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getPendingApprovals(): array
    {
        $sql = 'SELECT l.*, u.name as user_name, u.email as user_email, c.name as category_name, loc.name as location_name 
                FROM ' . static::tableName() . ' l 
                JOIN users u ON l.user_id = u.id AND u.deleted_at IS NULL
                LEFT JOIN categories c ON l.category_id = c.id AND c.deleted_at IS NULL
                LEFT JOIN locations loc ON l.location_id = loc.id AND loc.deleted_at IS NULL
                WHERE l.status = ? AND l.deleted_at IS NULL ORDER BY l.updated_at ASC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute([self::STATUS_PENDING]);
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $r !== false ? $r : [];
    }

    /**
     * Check if user has active subscription (for expiry logic).
     */
    public static function userHasActiveSubscription(int $userId): bool
    {
        $sql = "SELECT 1 FROM subscriptions WHERE user_id = ? AND status = 'active' AND deleted_at IS NULL AND (ends_at IS NULL OR ends_at > NOW()) LIMIT 1";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$userId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Mark listings as expired when user subscription expired. Call on load or cron.
     *
     * @return int Number of listings updated
     */
    public static function expireListingsForExpiredSubscriptions(): int
    {
        $sql = "UPDATE listings l 
                INNER JOIN subscriptions s ON s.user_id = l.user_id AND s.status = 'expired' AND s.deleted_at IS NULL
                SET l.status = 'expired', l.updated_at = NOW() 
                WHERE l.status = 'approved' AND l.deleted_at IS NULL";
        $stmt = self::db()->prepare($sql);
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Ensure listing status is expired if user has no active subscription (single listing).
     */
    public static function ensureExpiredBySubscription(int $listingId): void
    {
        $listing = self::findById($listingId);
        if (!$listing || $listing['status'] !== self::STATUS_APPROVED) {
            return;
        }
        if (!self::userHasActiveSubscription((int) $listing['user_id'])) {
            self::updateStatus($listingId, self::STATUS_EXPIRED);
        }
    }

    public static function updateStatus(int $id, string $status): bool
    {
        $allowed = [self::STATUS_DRAFT, self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_EXPIRED, self::STATUS_SUSPENDED];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        $stmt = self::db()->prepare('UPDATE ' . static::tableName() . ' SET status = ?, updated_at = NOW() WHERE id = ?');
        return $stmt->execute([$status, $id]);
    }

    /**
     * Increment view count.
     */
    public static function incrementViewCount(int $id): void
    {
        $stmt = self::db()->prepare('UPDATE ' . static::tableName() . ' SET view_count = view_count + 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Slug exists (excluding id).
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

    public static function generateSlug(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
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

    /**
     * Create listing.
     *
     * @param array<string, mixed> $data
     * @return int Listing ID
     */
    public static function create(array $data): int
    {
        $sql = 'INSERT INTO ' . static::tableName() . ' 
                (user_id, plan_id, category_id, location_id, title, slug, description, short_description, address, phone, whatsapp, email, website, status, featured, view_count, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, NOW(), NOW())';
        $stmt = self::db()->prepare($sql);
        $stmt->execute([
            $data['user_id'],
            $data['plan_id'] ?? null,
            $data['category_id'],
            $data['location_id'] ?? null,
            $data['title'],
            $data['slug'],
            $data['description'] ?? null,
            $data['short_description'] ?? null,
            $data['address'] ?? null,
            $data['phone'] ?? null,
            $data['whatsapp'] ?? null,
            $data['email'] ?? null,
            $data['website'] ?? null,
            $data['status'] ?? self::STATUS_DRAFT,
        ]);
        return (int) self::db()->lastInsertId();
    }

    /**
     * Update listing.
     *
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): bool
    {
        $allowed = ['plan_id', 'category_id', 'location_id', 'title', 'slug', 'description', 'short_description', 'address', 'phone', 'whatsapp', 'email', 'website', 'status', 'featured'];
        $updates = [];
        $params = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $updates[] = "{$f} = ?";
                $params[] = $data[$f];
            }
        }
        if (empty($updates)) {
            return false;
        }
        $updates[] = 'updated_at = NOW()';
        $params[] = $id;
        $stmt = self::db()->prepare('UPDATE ' . static::tableName() . ' SET ' . implode(', ', $updates) . ' WHERE id = ?');
        return $stmt->execute($params);
    }

    /**
     * Soft delete.
     */
    public static function softDelete(int $id): bool
    {
        $stmt = self::db()->prepare('UPDATE ' . static::tableName() . ' SET deleted_at = NOW() WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
