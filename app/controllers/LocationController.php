<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Sanitize;
use App\Models\Location;

/**
 * Location controller - admin CRUD for hierarchical locations.
 * Structure: Country → State → District → Taluka → Village → Area → Locality
 */
class LocationController extends Controller
{
    /**
     * List all locations (tree view).
     */
    public function index(): void
    {
        Auth::requireRole('admin');
        $locations = Location::getTree(false); // include inactive for admin
        $this->view('admin.locations.index', [
            'title' => 'Manage Locations',
            'locations' => $locations,
        ]);
    }

    /**
     * Show create form.
     */
    public function create(): void
    {
        Auth::requireRole('admin');
        $roots = Location::getRoots(false);
        $this->view('admin.locations.create', [
            'title' => 'Create Location',
            'roots' => $roots,
            'types' => Location::TYPE_HIERARCHY,
            'csrf_token' => Csrf::token(),
        ]);
    }

    /**
     * Store new location.
     */
    public function store(): void
    {
        Auth::requireRole('admin');
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $token = $input[Csrf::fieldName()] ?? null;
        if (!Csrf::validate($token)) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 400);
            return;
        }

        $name = Sanitize::string($input['name'] ?? '');
        $type = Sanitize::string($input['type'] ?? '');
        $parentId = !empty($input['parent_id']) ? Sanitize::int($input['parent_id']) : null;
        $code = Sanitize::string($input['code'] ?? '');
        $latitude = isset($input['latitude']) ? (float) $input['latitude'] : null;
        $longitude = isset($input['longitude']) ? (float) $input['longitude'] : null;
        $isActive = isset($input['is_active']) ? 1 : 0;

        $errors = [];
        if (empty($name) || strlen($name) < 2) {
            $errors[] = 'Name must be at least 2 characters.';
        }
        if (!in_array($type, Location::TYPE_HIERARCHY, true)) {
            $errors[] = 'Invalid location type.';
        }
        if ($parentId !== null) {
            $parent = Location::findById($parentId);
            if (!$parent) {
                $errors[] = 'Parent location not found.';
            } else {
                $expectedChildType = Location::getNextType($parent['type']);
                if ($expectedChildType !== null && $type !== $expectedChildType) {
                    $errors[] = 'Child type must be: ' . $expectedChildType;
                }
            }
        } else {
            if ($type !== Location::TYPE_COUNTRY) {
                $errors[] = 'Root location must be type Country.';
            }
        }
        if (!empty($errors)) {
            $this->json(['success' => false, 'errors' => $errors], 400);
            return;
        }

        $slug = Location::generateSlug($name);
        $slug = Location::makeSlugUnique($slug);

        try {
            $id = Location::create([
                'name' => $name,
                'slug' => $slug,
                'type' => $type,
                'parent_id' => $parentId,
                'code' => $code ?: null,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'is_active' => $isActive,
            ]);
            Csrf::regenerate();
            $this->json(['success' => true, 'message' => 'Location created.', 'id' => $id]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show edit form.
     */
    public function edit(int $id): void
    {
        Auth::requireRole('admin');
        $location = Location::findById($id);
        if (!$location) {
            http_response_code(404);
            die('Location not found.');
        }
        $roots = Location::getRoots(false);
        $path = Location::getPath($id);
        $this->view('admin.locations.edit', [
            'title' => 'Edit Location',
            'location' => $location,
            'roots' => $roots,
            'path' => $path,
            'types' => Location::TYPE_HIERARCHY,
            'csrf_token' => Csrf::token(),
        ]);
    }

    /**
     * Update location.
     */
    public function update(int $id): void
    {
        Auth::requireRole('admin');
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $token = $input[Csrf::fieldName()] ?? null;
        if (!Csrf::validate($token)) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 400);
            return;
        }
        $location = Location::findById($id);
        if (!$location) {
            $this->json(['success' => false, 'message' => 'Location not found.'], 404);
            return;
        }

        $name = Sanitize::string($input['name'] ?? '');
        $type = Sanitize::string($input['type'] ?? '');
        $parentId = array_key_exists('parent_id', $input) && $input['parent_id'] !== '' ? Sanitize::int($input['parent_id']) : null;
        $code = Sanitize::string($input['code'] ?? '');
        $latitude = isset($input['latitude']) ? (float) $input['latitude'] : null;
        $longitude = isset($input['longitude']) ? (float) $input['longitude'] : null;
        $isActive = isset($input['is_active']) ? 1 : 0;

        $errors = [];
        if (empty($name) || strlen($name) < 2) {
            $errors[] = 'Name must be at least 2 characters.';
        }
        if (!in_array($type, Location::TYPE_HIERARCHY, true)) {
            $errors[] = 'Invalid location type.';
        }
        if ($parentId === $id) {
            $errors[] = 'Location cannot be its own parent.';
        }
        if ($parentId !== null) {
            $parent = Location::findById($parentId);
            if (!$parent) {
                $errors[] = 'Parent location not found.';
            } else {
                $expectedChildType = Location::getNextType($parent['type']);
                if ($expectedChildType !== null && $type !== $expectedChildType) {
                    $errors[] = 'Child type must be: ' . $expectedChildType;
                }
            }
            // Circular: parent must not be a descendant of id
            $path = Location::getPath($parentId);
            foreach ($path as $ancestor) {
                if ((int) $ancestor['id'] === $id) {
                    $errors[] = 'Cannot set parent: would create circular reference.';
                    break;
                }
            }
        } else {
            if ($type !== Location::TYPE_COUNTRY) {
                $errors[] = 'Root location must be type Country.';
            }
        }
        if (!empty($errors)) {
            $this->json(['success' => false, 'errors' => $errors], 400);
            return;
        }

        $slug = $location['slug'];
        if ($name !== $location['name']) {
            $slug = Location::generateSlug($name);
            $slug = Location::makeSlugUnique($slug, $id);
        }

        try {
            Location::update($id, [
                'name' => $name,
                'slug' => $slug,
                'type' => $type,
                'parent_id' => $parentId,
                'code' => $code ?: null,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'is_active' => $isActive,
            ]);
            Csrf::regenerate();
            $this->json(['success' => true, 'message' => 'Location updated.']);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete location.
     */
    public function delete(int $id): void
    {
        Auth::requireRole('admin');
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $token = $input[Csrf::fieldName()] ?? $_GET['token'] ?? null;
        if (!Csrf::validate($token)) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 400);
            return;
        }
        $result = Location::delete($id);
        if ($result['success']) {
            Csrf::regenerate();
        }
        $this->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Toggle active status (enable/disable - e.g. country).
     */
    public function toggleActive(int $id): void
    {
        Auth::requireRole('admin');
        $location = Location::findById($id);
        if (!$location) {
            $this->json(['success' => false, 'message' => 'Location not found.'], 404);
            return;
        }
        $newStatus = $location['is_active'] ? 0 : 1;
        Location::update($id, ['is_active' => $newStatus]);
        $this->json(['success' => true, 'is_active' => (bool) $newStatus]);
    }

    /**
     * AJAX: Get children of a location (for hierarchical dropdown loading).
     * parentId 0 returns roots (countries).
     */
    public function children(int $parentId): void
    {
        Auth::requireRole('admin');
        if ($parentId === 0) {
            $roots = Location::getRoots(false);
            $list = array_map(static fn($r) => [
                'id' => (int) $r['id'],
                'name' => $r['name'],
                'slug' => $r['slug'],
                'type' => $r['type'],
            ], $roots);
        } else {
            $list = Location::getChildrenForSelect($parentId);
        }
        $this->json(['success' => true, 'data' => $list]);
    }

    /**
     * Public API: Get full location tree.
     */
    public function tree(): void
    {
        $tree = Location::getTree(true);
        $this->json(['success' => true, 'data' => $tree]);
    }

    /**
     * Public API: Get children for dropdowns (e.g. listing form). parentId 0 = roots.
     */
    public function childrenPublic(int $parentId): void
    {
        if ($parentId === 0) {
            $roots = Location::getRoots(true);
            $list = array_map(static fn($r) => [
                'id' => (int) $r['id'],
                'name' => $r['name'],
                'slug' => $r['slug'],
                'type' => $r['type'],
            ], $roots);
        } else {
            $list = Location::getChildrenForSelect($parentId);
        }
        $this->json(['success' => true, 'data' => $list]);
    }
}
