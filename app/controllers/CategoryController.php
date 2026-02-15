<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Sanitize;
use App\Models\Category;
use App\Models\CategorySuggestion;

/**
 * Category controller - admin CRUD for nested categories.
 */
class CategoryController extends Controller
{
    /**
     * List all categories (tree view).
     */
    public function index(): void
    {
        Auth::requireRole('admin');
        
        $categories = Category::getTree();
        $suggestions = CategorySuggestion::getAll('pending');
        
        $this->view('admin.categories.index', [
            'title' => 'Manage Categories',
            'categories' => $categories,
            'pendingSuggestions' => $suggestions,
        ]);
    }

    /**
     * Show create form.
     */
    public function create(): void
    {
        Auth::requireRole('admin');
        
        $categories = Category::getTree();
        
        $this->view('admin.categories.create', [
            'title' => 'Create Category',
            'categories' => $categories,
            'csrf_token' => Csrf::token(),
        ]);
    }

    /**
     * Store new category.
     */
    public function store(): void
    {
        Auth::requireRole('admin');
        
        // Get input (JSON or POST)
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        // Validate CSRF
        $token = $input[Csrf::fieldName()] ?? null;
        if (!Csrf::validate($token)) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 400);
            return;
        }
        
        // Sanitize input
        $name = Sanitize::string($input['name'] ?? '');
        $description = Sanitize::text($input['description'] ?? '');
        $parentId = !empty($input['parent_id']) ? Sanitize::int($input['parent_id']) : null;
        $sortOrder = !empty($input['sort_order']) ? Sanitize::int($input['sort_order']) : 0;
        $isActive = isset($input['is_active']) ? 1 : 0;
        
        // Validate
        $errors = [];
        if (empty($name) || strlen($name) < 2) {
            $errors[] = 'Name must be at least 2 characters.';
        }
        
        if (!empty($errors)) {
            $this->json(['success' => false, 'errors' => $errors], 400);
            return;
        }
        
        // Generate slug
        $slug = Category::generateSlug($name);
        $slug = Category::makeSlugUnique($slug);
        
        // Check parent exists (if provided)
        if ($parentId !== null) {
            $parent = Category::find($parentId);
            if (!$parent) {
                $this->json(['success' => false, 'message' => 'Parent category not found.'], 400);
                return;
            }
        }
        
        // Create category
        try {
            $categoryId = Category::create([
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'parent_id' => $parentId,
                'sort_order' => $sortOrder,
                'is_active' => $isActive,
            ]);
            
            Csrf::regenerate();
            $this->json(['success' => true, 'message' => 'Category created successfully.', 'id' => $categoryId]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Failed to create category: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show edit form.
     */
    public function edit(int $id): void
    {
        Auth::requireRole('admin');
        
        $category = Category::find($id);
        if (!$category) {
            http_response_code(404);
            die('Category not found.');
        }
        
        $categories = Category::getTree();
        
        $this->view('admin.categories.edit', [
            'title' => 'Edit Category',
            'category' => $category,
            'categories' => $categories,
            'csrf_token' => Csrf::token(),
        ]);
    }

    /**
     * Update category.
     */
    public function update(int $id): void
    {
        Auth::requireRole('admin');
        
        // Get input (JSON or POST)
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        // Validate CSRF
        $token = $input[Csrf::fieldName()] ?? null;
        if (!Csrf::validate($token)) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 400);
            return;
        }
        
        $category = Category::find($id);
        if (!$category) {
            $this->json(['success' => false, 'message' => 'Category not found.'], 404);
            return;
        }
        
        // Sanitize input
        $name = Sanitize::string($input['name'] ?? '');
        $description = Sanitize::text($input['description'] ?? '');
        $parentId = !empty($input['parent_id']) ? Sanitize::int($input['parent_id']) : null;
        $sortOrder = !empty($input['sort_order']) ? Sanitize::int($input['sort_order']) : 0;
        $isActive = isset($input['is_active']) ? 1 : 0;
        
        // Validate
        $errors = [];
        if (empty($name) || strlen($name) < 2) {
            $errors[] = 'Name must be at least 2 characters.';
        }
        
        // Prevent setting self as parent
        if ($parentId === $id) {
            $errors[] = 'Category cannot be its own parent.';
        }
        
        // Check if new parent would create circular reference
        if ($parentId !== null && $parentId !== (int)$category['parent_id']) {
            $path = Category::getPath($parentId);
            foreach ($path as $ancestor) {
                if ((int)$ancestor['id'] === $id) {
                    $errors[] = 'Cannot set parent: would create circular reference.';
                    break;
                }
            }
        }
        
        if (!empty($errors)) {
            $this->json(['success' => false, 'errors' => $errors], 400);
            return;
        }
        
        // Generate slug if name changed
        $slug = $category['slug'];
        if ($name !== $category['name']) {
            $slug = Category::generateSlug($name);
            $slug = Category::makeSlugUnique($slug, $id);
        }
        
        // Update category
        try {
            Category::update($id, [
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'parent_id' => $parentId,
                'sort_order' => $sortOrder,
                'is_active' => $isActive,
            ]);
            
            Csrf::regenerate();
            $this->json(['success' => true, 'message' => 'Category updated successfully.']);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Failed to update category: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete category.
     */
    public function delete(int $id): void
    {
        Auth::requireRole('admin');
        
        // Get input (JSON or POST)
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        // Validate CSRF
        $token = $input[Csrf::fieldName()] ?? $_GET['token'] ?? null;
        if (!Csrf::validate($token)) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 400);
            return;
        }
        
        $result = Category::delete($id);
        
        if ($result['success']) {
            Csrf::regenerate();
        }
        
        $this->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Toggle active status.
     */
    public function toggleActive(int $id): void
    {
        Auth::requireRole('admin');
        
        $category = Category::find($id);
        if (!$category) {
            $this->json(['success' => false, 'message' => 'Category not found.'], 404);
            return;
        }
        
        // Get enable from JSON body or toggle current status
        $input = json_decode(file_get_contents('php://input'), true);
        $newStatus = isset($input['enable']) ? ($input['enable'] ? 1 : 0) : ($category['is_active'] ? 0 : 1);
        
        Category::update($id, ['is_active' => $newStatus]);
        
        $this->json(['success' => true, 'is_active' => (bool)$newStatus]);
    }

    /**
     * Get category tree (API).
     */
    public function tree(): void
    {
        // Public API - no auth required, but can add if needed
        $tree = Category::getTree();
        $this->json(['success' => true, 'data' => $tree]);
    }

    /**
     * Approve category suggestion.
     */
    public function approveSuggestion(int $id): void
    {
        Auth::requireRole('admin');
        
        $suggestion = CategorySuggestion::find($id);
        if (!$suggestion) {
            $this->json(['success' => false, 'message' => 'Suggestion not found.'], 404);
            return;
        }
        
        if ($suggestion['status'] !== 'pending') {
            $this->json(['success' => false, 'message' => 'Suggestion already processed.'], 400);
            return;
        }
        
        // Create category from suggestion
        $slug = Category::generateSlug($suggestion['name']);
        $slug = Category::makeSlugUnique($slug);
        
        try {
            $categoryId = Category::create([
                'name' => $suggestion['name'],
                'slug' => $slug,
                'description' => $suggestion['description'],
                'parent_id' => $suggestion['parent_id'],
                'is_active' => 1,
            ]);
            
            // Update suggestion status
            CategorySuggestion::updateStatus($id, 'approved', Auth::id());
            
            $this->json(['success' => true, 'message' => 'Category suggestion approved.', 'category_id' => $categoryId]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Failed to approve suggestion: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reject category suggestion.
     */
    public function rejectSuggestion(int $id): void
    {
        Auth::requireRole('admin');
        
        $suggestion = CategorySuggestion::find($id);
        if (!$suggestion) {
            $this->json(['success' => false, 'message' => 'Suggestion not found.'], 404);
            return;
        }
        
        CategorySuggestion::updateStatus($id, 'rejected', Auth::id());
        
        $this->json(['success' => true, 'message' => 'Category suggestion rejected.']);
    }
}
