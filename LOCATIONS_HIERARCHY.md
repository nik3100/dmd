# Hierarchical Location System

## Structure

```
Country (can be disabled initially via is_active)
  → State
  → District
  → Taluka
  → Village
  → Area
  → Locality
```

- **Single table** `locations` with `parent_id` and `type`.
- **Slug** per location for SEO.
- **Enable/disable** via `is_active` (e.g. disable a country until ready).
- **Indexed** for search: `type`, `is_active`, `deleted_at`, `name`.

---

## Setup

### New install (schema.sql already has full hierarchy)

No extra step if you use the updated `schema.sql` (with `is_active` and full type ENUM).

### Existing database (old locations table)

Run the migration:

```bash
mysql -u root -p digitalmarketing_display < database/migrations/locations_hierarchy.sql
```

If you had `type = 'city'`, update first:

```sql
UPDATE locations SET type = 'district' WHERE type = 'city';
```

---

## Files

| File | Purpose |
|------|---------|
| `app/models/Location.php` | Model: CRUD, tree, children, path, search |
| `app/helpers/LocationHelper.php` | Recursive retrieval, breadcrumb, tree render |
| `app/controllers/LocationController.php` | Admin CRUD, AJAX children, tree API |
| `app/views/admin/locations/*.php` | Admin list, create, edit (with hierarchical dropdowns) |
| `database/migrations/locations_hierarchy.sql` | Add `is_active`, extend type ENUM, indexes |

---

## Routes

| Route | Method | Description |
|-------|--------|-------------|
| `/admin/locations` | GET | List locations (tree) |
| `/admin/locations/create` | GET | Create form |
| `/admin/locations/store` | POST | Create location |
| `/admin/locations/edit/{id}` | GET | Edit form |
| `/admin/locations/update/{id}` | POST | Update location |
| `/admin/locations/delete/{id}` | POST | Soft delete (blocked if has children) |
| `/admin/locations/toggle-active/{id}` | POST | Toggle is_active (enable/disable) |
| `/admin/locations/children/{parentId}` | GET | **AJAX** children for dropdowns (`0` = roots) |
| `/api/locations/tree` | GET | Public API: full tree (active only) |

---

## Usage

### Recursive retrieval

```php
use App\Models\Location;
use App\Helpers\LocationHelper;

// Full tree (active only)
$tree = Location::getTree(true);

// Roots (countries), active only
$countries = Location::getRoots(true);

// Children of a location (for dropdowns)
$states = Location::getChildren($countryId, true);

// Path from root to location (breadcrumb)
$path = Location::getPath($locationId);
$breadcrumb = LocationHelper::renderBreadcrumb($path);

// Search by name
$results = Location::search('Mumbai', 'city', 20);
```

### AJAX hierarchical dropdowns

1. **Create form:** Choose type (e.g. State). Parent dropdowns appear (e.g. Country). Load roots with `GET /admin/locations/children/0`. When user selects a country, load `GET /admin/locations/children/{countryId}` into the next dropdown. Repeat for each level; last selection is `parent_id`.
2. **Edit form:** Prefill parent chain from `Location::getPath($id)` (excluding current). Load roots, then cascade load children for each level and set selected values.

### Enable/disable country

- Set `is_active = 0` for a country (e.g. via Edit or “Disable” on list).
- `Location::getRoots(true)` returns only active roots, so that country is excluded from dropdowns and tree API.

---

## API

### GET /api/locations/tree

Returns full location tree (active only, no deleted).

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "parent_id": null,
      "name": "India",
      "slug": "india",
      "type": "country",
      "is_active": 1,
      "children": [
        {
          "id": 2,
          "parent_id": 1,
          "name": "Maharashtra",
          "slug": "maharashtra",
          "type": "state",
          "children": []
        }
      ]
    }
  ]
}
```

### GET /admin/locations/children/{parentId}

Admin-only. Returns direct children for dropdowns. `parentId = 0` returns roots (countries).

```json
{
  "success": true,
  "data": [
    { "id": 1, "name": "India", "slug": "india", "type": "country" },
    { "id": 2, "name": "USA", "slug": "usa", "type": "country" }
  ]
}
```

---

## Security & validation

- All admin routes use `AdminMiddleware` (admin role).
- Cannot delete a location that has children.
- Circular parent is prevented (DB triggers + app validation when changing parent).
- Type hierarchy is enforced: child type must be the next level (e.g. State under Country only).
