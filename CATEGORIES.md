# Category Management System

## Overview

Dynamic nested category system with unlimited hierarchy, admin CRUD, and user suggestions.

## Features

✅ **Unlimited Nesting** - Parent-child hierarchy with no depth limit  
✅ **Slug Auto-generation** - SEO-friendly URLs from category names  
✅ **Enable/Disable** - Toggle category visibility  
✅ **Admin CRUD Only** - Full management interface for admins  
✅ **Delete Protection** - Prevents deleting categories with children  
✅ **Circular Reference Prevention** - Database triggers + application validation  
✅ **Category Suggestions** - User-submitted categories requiring admin approval  

---

## Database Schema

### Categories Table
```sql
- id (INT UNSIGNED)
- parent_id (INT UNSIGNED, NULL for root)
- name (VARCHAR 255)
- slug (VARCHAR 255, UNIQUE)
- description (TEXT)
- sort_order (INT)
- is_active (TINYINT 1)
- deleted_at (TIMESTAMP, soft delete)
- created_at, updated_at
```

### Category Suggestions Table
```sql
- id (BIGINT UNSIGNED)
- user_id (BIGINT UNSIGNED)
- name (VARCHAR 255)
- description (TEXT)
- parent_id (INT UNSIGNED, NULL)
- status (ENUM: pending, approved, rejected)
- approved_by (BIGINT UNSIGNED, admin user)
- created_at, updated_at
```

---

## Setup

### 1. Run Migration

```bash
mysql -u root -p < database/migrations/category_suggestions.sql
```

### 2. Access Admin Panel

1. Login as admin user
2. Navigate to: `http://localhost/dmd/public/admin/categories`

---

## Usage

### Admin Routes

| Route | Method | Description |
|-------|--------|-------------|
| `/admin/categories` | GET | List all categories (tree view) |
| `/admin/categories/create` | GET | Show create form |
| `/admin/categories/store` | POST | Create new category |
| `/admin/categories/edit/{id}` | GET | Show edit form |
| `/admin/categories/update/{id}` | POST | Update category |
| `/admin/categories/delete/{id}` | POST | Delete category |
| `/admin/categories/toggle-active/{id}` | POST | Toggle active status |
| `/admin/categories/suggestions/approve/{id}` | POST | Approve suggestion |
| `/admin/categories/suggestions/reject/{id}` | POST | Reject suggestion |

### Public API

| Route | Method | Description |
|-------|--------|-------------|
| `/api/categories/tree` | GET | Get full category tree (JSON) |

---

## Code Examples

### Get Category Tree

```php
use App\Models\Category;

// Get full tree structure
$tree = Category::getTree();
// Returns nested array with 'children' key

// Get root categories only
$roots = Category::getRoots();

// Get children of a category
$children = Category::getChildren($parentId);

// Get category path (breadcrumb)
$path = Category::getPath($categoryId);
```

### Render Category Tree

```php
use App\Helpers\CategoryHelper;

// Render as HTML nested list
$html = CategoryHelper::renderTree($categories);

// Render as select options
$options = CategoryHelper::renderSelectOptions($categories, $selectedId, $excludeId);

// Render breadcrumb
$breadcrumb = CategoryHelper::renderBreadcrumb($path);
```

### Create Category

```php
use App\Models\Category;

$categoryId = Category::create([
    'name' => 'Restaurants',
    'slug' => Category::makeSlugUnique(Category::generateSlug('Restaurants')),
    'description' => 'Food and dining',
    'parent_id' => null, // Root category
    'sort_order' => 0,
    'is_active' => 1,
]);
```

### Check for Children

```php
if (Category::hasChildren($categoryId)) {
    // Cannot delete - has children
}
```

### Delete Category

```php
$result = Category::delete($categoryId);
if ($result['success']) {
    echo $result['message']; // "Category deleted successfully."
} else {
    echo $result['message']; // "Cannot delete category with children..."
}
```

---

## Security Features

### 1. Circular Reference Prevention

**Database Level:**
- Triggers prevent A → B → A cycles
- CHECK constraint prevents self-reference

**Application Level:**
- `CategoryController::update()` validates parent chain
- Prevents setting ancestor as parent

### 2. Delete Protection

- `Category::delete()` checks for children
- Returns error if children exist
- Prevents orphaned categories

### 3. Admin-Only Access

- All routes protected by `AdminMiddleware`
- Requires `admin` role
- CSRF protection on all POST requests

---

## Category Suggestions

Users can suggest new categories (requires implementation in frontend):

```php
use App\Models\CategorySuggestion;

$suggestionId = CategorySuggestion::create([
    'user_id' => Auth::id(),
    'name' => 'New Category',
    'description' => 'Description here',
    'parent_id' => null, // Optional
    'status' => 'pending',
]);
```

Admin approves/rejects via dashboard.

---

## API Response Examples

### GET /api/categories/tree

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "parent_id": null,
            "name": "Restaurants",
            "slug": "restaurants",
            "description": "Food and dining",
            "sort_order": 0,
            "is_active": 1,
            "children": [
                {
                    "id": 2,
                    "parent_id": 1,
                    "name": "Fast Food",
                    "slug": "fast-food",
                    "children": []
                }
            ]
        }
    ]
}
```

---

## Best Practices

1. **Always check for children** before deleting
2. **Validate parent chain** when updating parent_id
3. **Use soft delete** (deleted_at) to preserve data
4. **Generate unique slugs** automatically
5. **Sort by sort_order** for consistent display
6. **Check is_active** when displaying categories publicly

---

## Troubleshooting

### Cannot Delete Category

**Error:** "Cannot delete category with children"

**Solution:** Delete or move children first, then delete parent.

### Circular Reference Error

**Error:** "Cannot set parent: would create circular reference"

**Solution:** Database trigger prevents this. Check parent chain before updating.

### Slug Already Exists

**Solution:** `Category::makeSlugUnique()` automatically appends numbers (e.g., `restaurants-2`).

---

## Next Steps

- Add category image/icon support
- Implement category search
- Add category statistics (listing count)
- Create category import/export
- Add category templates
