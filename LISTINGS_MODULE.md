# Listings Module (Approval Workflow)

## Listing fields

- **name** (stored as `title` in DB)
- **slug** (auto-generated, unique)
- **description**, **short_description**
- **category_id**, **location_id**
- **address**, **phone**, **whatsapp**, **email**, **website**
- **status**: draft, pending_approval, approved, rejected, expired, suspended
- **plan_id** (optional, for subscription)
- **user_id** (owner)

## Rules

- **Only approved listings** are visible on the public listing page and single listing view.
- **Only the business owner** (or admin) can edit/delete a listing.
- **Admin must approve** before a listing goes live; user submits for approval from draft/rejected.
- **Expired subscription** can auto-change status: run `Listing::expireListingsForExpiredSubscriptions()` (e.g. on my-listings load or via cron) to set approved listings to expired when the user’s subscription is expired.

## Generated files

| File | Purpose |
|------|--------|
| `app/models/Listing.php` | Model: CRUD, approved/pending lists, slug, expiry helpers |
| `app/controllers/ListingController.php` | Public show/index, user create/edit/delete, admin approve/reject |
| `app/views/listings/show.php` | Public single listing page |
| `app/views/listings/index.php` | Public listing list (approved only) |
| `app/views/listings/my-listings.php` | User dashboard: my listings |
| `app/views/listings/form.php` | Create/Edit form (category, location, contact fields) |
| `app/views/admin/listings/pending.php` | Admin approval panel |
| `database/migrations/listings_module.sql` | Add whatsapp, plan_id, status expired (for existing DBs) |
| `database/schema.sql` | Updated with whatsapp, plan_id, expired status |

## Routes

| Route | Method | Who | Description |
|-------|--------|-----|-------------|
| `/listings` | GET | Public | List approved listings |
| `/listings/show/{slug}` | GET | Public | Single listing (approved only) |
| `/my-listings` | GET | Auth | User’s listings (dashboard) |
| `/listings/create` | GET | Auth | Create form |
| `/listings/store` | POST | Auth | Create listing (draft or submit for approval) |
| `/listings/edit/{id}` | GET | Owner/Admin | Edit form |
| `/listings/update/{id}` | POST | Owner/Admin | Update listing |
| `/listings/delete/{id}` | POST | Owner/Admin | Soft delete |
| `/admin/listings/pending` | GET | Admin | Pending approvals panel |
| `/admin/listings/approve/{id}` | POST | Admin | Approve listing |
| `/admin/listings/reject/{id}` | POST | Admin | Reject listing |

## Usage

### Public (approved only)

```php
$listings = Listing::getApproved('created_at', 'DESC', 24, 0);
$listing = Listing::findBySlugPublic($slug);
```

### User (owner)

```php
$listings = Listing::getByUserId(Auth::id());
$listing = Listing::findById($id);
// Edit/delete only if (int)$listing['user_id'] === Auth::id() || Auth::hasRole('admin')
```

### Admin

```php
$pending = Listing::getPendingApprovals();
Listing::updateStatus($id, Listing::STATUS_APPROVED);
Listing::updateStatus($id, Listing::STATUS_REJECTED);
```

### Expired subscription

```php
// Call on dashboard or cron
Listing::expireListingsForExpiredSubscriptions();
// Or for one listing
Listing::ensureExpiredBySubscription($listingId);
```

## Status flow

- **draft** → User saves without submitting.
- **pending_approval** → User submits for approval; admin sees in Pending panel.
- **approved** → Admin approves; listing is public.
- **rejected** → Admin rejects; user can edit and submit again.
- **expired** → Set when subscription is expired (optional logic).
- **suspended** → Admin can set manually if needed.

## Dashboard links

- **User:** Dashboard and app layout link to “My Listings” and “Listings”.
- **Admin:** Admin nav includes “Pending Listings” to open the approval panel.
