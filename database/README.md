# Database Schema Documentation

**Database:** `digitalmarketing_display`  
**Engine:** InnoDB  
**Charset:** utf8mb4_unicode_ci

---

## Table Relationships

### Core User & Access Control

**users** → **user_roles** ← **roles**
- Many-to-many: Users can have multiple roles (admin, business_owner, user)
- Cascade delete: Removing a user removes their role assignments

### Hierarchical Structures

**categories** (self-referencing)
- Parent-child hierarchy for nested categories
- `parent_id` → `categories.id` (ON DELETE SET NULL)
- Triggers prevent circular references (A→B→C→A)
- Example: "Restaurants" → "Fast Food" → "Burgers"

**locations** (self-referencing)
- Hierarchical: Country → State → City → Area
- `parent_id` → `locations.id` (ON DELETE SET NULL)
- Triggers prevent circular references
- Supports geolocation (latitude/longitude)

### Business Directory Core

**listings** (central entity)
- **Belongs to:** `users` (business owner), `categories`, `locations`
- **Has many:** `products`, `reviews`, `media` (polymorphic), `promotions`
- Status workflow: draft → pending_approval → approved/rejected
- Soft delete via `deleted_at`

**products**
- **Belongs to:** `listings` (ON DELETE CASCADE)
- Unique slug per listing: `(listing_id, slug)`
- Supports pricing, SKU, active/inactive

**media** (polymorphic)
- Attaches to any entity via `mediable_type` + `mediable_id`
- Types: image, video, document
- Used by: listings, products, users (avatar)

### Subscription & Payments

**plans** → **subscriptions** ← **users**
- Users subscribe to plans (monthly/yearly)
- Plans define listing limits and features
- Subscription status: active, cancelled, expired, past_due

**payments**
- **Belongs to:** `subscriptions` (optional - one-off payments), `users`
- Tracks payment gateway transactions
- Status: pending, completed, failed, refunded

### Reviews & Moderation

**reviews**
- **Belongs to:** `listings`, `users`
- One review per user per listing: `UNIQUE(user_id, listing_id)`
- Rating: 1-5 stars
- Status: pending, approved, rejected

**approval_logs** (polymorphic)
- Tracks approval/rejection actions
- Links to: listings, reviews, or any approvable entity
- Records who approved/rejected and when

### Promotions & Marketing

**promotions**
- **Belongs to:** `listings` (NULL = site-wide promotion)
- Types: featured, spotlight, banner, discount
- Date range: `starts_at` → `ends_at`

### Analytics & Notifications

**analytics_events**
- Immutable event log (no `updated_at`)
- Tracks: views, clicks, contact, share, etc.
- Links to any entity via `entity_type` + `entity_id`
- Stores session, IP, user agent, referer

**notifications**
- **Belongs to:** `users` (ON DELETE CASCADE)
- Read/unread tracking via `read_at`
- JSON `data` for flexible payloads

---

## Index Strategy

### Primary Keys
- All tables use `BIGINT UNSIGNED` or `INT UNSIGNED` auto-increment IDs
- Provides fast lookups and foreign key joins

### Unique Indexes (Enforce Uniqueness)
- **users:** `email`, `slug`
- **roles:** `name`, `slug`
- **categories:** `slug` (globally unique)
- **locations:** `slug` (globally unique)
- **listings:** `slug` (globally unique)
- **plans:** `slug`
- **products:** `(listing_id, slug)` composite unique
- **reviews:** `(user_id, listing_id)` composite unique

### Foreign Key Indexes (Join Performance)
- All foreign keys are automatically indexed by InnoDB
- Examples: `listings.user_id`, `listings.category_id`, `listings.location_id`

### Search & Filter Indexes

**Frequently queried columns:**
- **users:** `deleted_at`, `is_active`
- **categories:** `parent_id`, `is_active`, `sort_order`, `deleted_at`
- **locations:** `parent_id`, `type`, `code`, `deleted_at`
- **listings:** 
  - `status` (filter by draft/approved)
  - `featured` (show featured listings)
  - `view_count` (sort by popularity)
  - `created_at` (sort by newest)
  - Composite: `(status, featured, deleted_at)` for common search queries
- **products:** `is_active`, `sku`, `deleted_at`
- **subscriptions:** `status`, `ends_at`, composite `(user_id, status, ends_at)` for active subscription lookup
- **payments:** `status`, `gateway`, `created_at`
- **reviews:** `rating`, `status`, `created_at`
- **promotions:** `type`, `(starts_at, ends_at)`, `is_active`
- **analytics_events:** `(entity_type, entity_id)`, `event_type`, `created_at`, `session_id`
- **notifications:** `read_at`, composite `(user_id, read_at)` for unread count

### Soft Delete Indexes
- All tables with `deleted_at` have an index on it
- Enables efficient filtering: `WHERE deleted_at IS NULL`

### Composite Indexes (Multi-column Queries)

1. **listings_search:** `(status, featured, deleted_at)`
   - Common query: "Show approved, featured, non-deleted listings"

2. **subscriptions_active:** `(user_id, status, ends_at)`
   - Query: "Get active subscription for user"

3. **notifications_unread:** `(user_id, read_at)`
   - Query: "Count unread notifications per user"

4. **analytics_entity:** `(entity_type, entity_id)`
   - Query: "Get all events for a listing/product"

5. **products_listing_slug:** `(listing_id, slug)`
   - Unique constraint + fast lookup within a listing

6. **reviews_user_listing:** `(user_id, listing_id)`
   - Unique constraint + fast "has user reviewed this listing?" check

### Geolocation Index
- **locations:** `(latitude, longitude)` composite
- Enables spatial queries (distance-based search)

---

## Circular Reference Prevention

### Categories & Locations
Both tables use **BEFORE INSERT/UPDATE triggers** to prevent circular parent references:

1. **Self-reference check:** Prevents direct self-reference (`parent_id = id`)
2. **Deep cycle check:** Traverses parent chain up to 100 levels
   - If any ancestor equals the current record's ID → ERROR
   - Prevents cycles like: A → B → C → A

**Note:** MySQL doesn't allow CHECK constraints on foreign key columns, so validation is handled entirely by triggers.

**Example prevented:**
```sql
-- This would fail:
UPDATE categories SET parent_id = 3 WHERE id = 1;
-- If category 3's parent chain includes category 1
```

---

## Soft Delete Pattern

All major tables use `deleted_at` (TIMESTAMP NULL):
- `NULL` = active record
- `NOT NULL` = soft-deleted record

**Benefits:**
- Preserves data for audit/recovery
- Maintains referential integrity
- Enables "undelete" functionality

**Query pattern:**
```sql
SELECT * FROM listings WHERE deleted_at IS NULL;
```

---

## Foreign Key Cascade Rules

| Table | Foreign Key | On Delete | Reason |
|-------|-------------|-----------|--------|
| `user_roles` | `user_id`, `role_id` | CASCADE | Remove assignments when user/role deleted |
| `products` | `listing_id` | CASCADE | Products belong to listing |
| `media` | `user_id` (analytics) | SET NULL | Preserve analytics if user deleted |
| `subscriptions` | `user_id`, `plan_id` | RESTRICT | Prevent deletion if subscriptions exist |
| `payments` | `subscription_id` | SET NULL | Keep payment record if subscription deleted |
| `payments` | `user_id` | RESTRICT | Preserve payment history |
| `reviews` | `listing_id` | CASCADE | Remove reviews if listing deleted |
| `reviews` | `user_id` | RESTRICT | Preserve review author |
| `promotions` | `listing_id` | CASCADE | Remove promotion if listing deleted |
| `notifications` | `user_id` | CASCADE | Remove notifications if user deleted |

---

## Performance Considerations

1. **Large tables:** `analytics_events` will grow quickly
   - Consider partitioning by `created_at` (monthly/yearly)
   - Archive old events periodically

2. **Hierarchical queries:** Categories/Locations
   - For deep hierarchies, consider materialized path pattern (`path` column)
   - Current design uses recursive queries (CTE in MySQL 8.0+)

3. **Full-text search:** Listings, products, categories
   - Add FULLTEXT indexes on `title`, `description` if needed:
   ```sql
   ALTER TABLE listings ADD FULLTEXT(title, description);
   ```

4. **Slug generation:** Ensure uniqueness
   - Application layer should handle slug generation with collision detection
   - Consider `slug` + `id` fallback for uniqueness

---

## Usage Example

```sql
-- Import schema
mysql -u root -p < database/schema.sql

-- Or via phpMyAdmin: Import the schema.sql file
```

---

## Next Steps

1. Create seed data (default roles, categories, locations)
2. Add FULLTEXT indexes for search functionality
3. Create views for common queries (active listings, user subscriptions)
4. Set up database backups
5. Monitor slow query log for optimization opportunities
