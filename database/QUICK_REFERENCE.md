# Database Quick Reference

## Import Schema

```bash
# Via command line
mysql -u root -p < database/schema.sql

# Or via phpMyAdmin: Import → Select schema.sql
```

## Update .env

```env
DB_NAME=digitalmarketing_display
DB_USER=root
DB_PASS=
DB_HOST=localhost
DB_PORT=3306
```

## Key Tables Overview

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `users` | User accounts | email, slug, is_active |
| `roles` | User roles | name, slug |
| `user_roles` | User-role assignments | user_id, role_id |
| `categories` | Nested categories | parent_id, slug, is_active |
| `locations` | Hierarchical locations | parent_id, type, slug |
| `listings` | Business listings | user_id, category_id, status, slug |
| `products` | Products/services | listing_id, slug, price |
| `media` | Files (polymorphic) | mediable_type, mediable_id |
| `plans` | Subscription plans | slug, price, billing_interval |
| `subscriptions` | User subscriptions | user_id, plan_id, status |
| `payments` | Payment transactions | subscription_id, user_id, status |
| `reviews` | Listing reviews | listing_id, user_id, rating |
| `promotions` | Marketing promotions | listing_id, type, starts_at, ends_at |
| `analytics_events` | Event tracking | entity_type, entity_id, event_type |
| `notifications` | User notifications | user_id, read_at |
| `approval_logs` | Moderation logs | approvable_type, approvable_id |

## Common Queries

### Get active listings
```sql
SELECT * FROM listings 
WHERE status = 'approved' 
  AND deleted_at IS NULL 
ORDER BY created_at DESC;
```

### Get user's listings
```sql
SELECT * FROM listings 
WHERE user_id = ? 
  AND deleted_at IS NULL;
```

### Get category tree
```sql
-- Requires recursive CTE (MySQL 8.0+)
WITH RECURSIVE category_tree AS (
    SELECT id, parent_id, name, slug, 0 as level
    FROM categories WHERE parent_id IS NULL
    UNION ALL
    SELECT c.id, c.parent_id, c.name, c.slug, ct.level + 1
    FROM categories c
    INNER JOIN category_tree ct ON c.parent_id = ct.id
)
SELECT * FROM category_tree ORDER BY level, id;
```

### Get listing with relations
```sql
SELECT 
    l.*,
    u.name as owner_name,
    c.name as category_name,
    loc.name as location_name
FROM listings l
LEFT JOIN users u ON l.user_id = u.id
LEFT JOIN categories c ON l.category_id = c.id
LEFT JOIN locations loc ON l.location_id = loc.id
WHERE l.id = ? AND l.deleted_at IS NULL;
```

### Get active subscription
```sql
SELECT s.*, p.name as plan_name, p.price
FROM subscriptions s
JOIN plans p ON s.plan_id = p.id
WHERE s.user_id = ?
  AND s.status = 'active'
  AND (s.ends_at IS NULL OR s.ends_at > NOW())
  AND s.deleted_at IS NULL;
```

## Indexes Summary

- **Unique:** All `slug` columns, `users.email`, `(user_id, listing_id)` in reviews
- **Foreign Keys:** All FK columns auto-indexed
- **Search:** `status`, `is_active`, `featured`, `deleted_at` on major tables
- **Composite:** `(status, featured, deleted_at)` on listings, `(user_id, read_at)` on notifications

## Soft Delete Pattern

```sql
-- Active records
WHERE deleted_at IS NULL

-- Deleted records
WHERE deleted_at IS NOT NULL

-- Include deleted
-- (no WHERE clause on deleted_at)
```

## Circular Prevention

Triggers prevent:
- Direct self-reference: `parent_id = id` ❌
- Deep cycles: Category A → Category B → Category A (cycle)
- Deep cycles: Location A → Location B → Location A (cycle)

Triggers traverse parent chain up to 100 levels to detect any circular reference.
