<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Sanitize;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Location;

/**
 * Listing controller - public view, user dashboard, admin approval.
 */
class ListingController extends Controller
{
    /**
     * Public: single listing by slug (only approved).
     */
    public function show(string $slug): void
    {
        $listing = Listing::findBySlugPublic($slug);
        if (!$listing) {
            http_response_code(404);
            $this->view('home.404', ['title' => 'Listing Not Found']);
            return;
        }
        Listing::incrementViewCount((int) $listing['id']);
        $this->view('listings.show', [
            'title' => $listing['title'],
            'listing' => $listing,
        ]);
    }

    /**
     * Public: list approved listings.
     */
    public function index(): void
    {
        $listings = Listing::getApproved('created_at', 'DESC', 24, 0);
        $this->view('listings.index', [
            'title' => 'Listings',
            'listings' => $listings,
        ]);
    }

    /**
     * User dashboard: my listings.
     */
    public function myListings(): void
    {
        Auth::requireAuth();
        $userId = Auth::id();
        if (!$userId) {
            $this->redirect('/login');
            return;
        }
        // Optionally expire listings whose subscription ended
        Listing::expireListingsForExpiredSubscriptions();
        $listings = Listing::getByUserId($userId);
        $this->view('listings.my-listings', [
            'title' => 'My Listings',
            'listings' => $listings,
        ]);
    }

    /**
     * User: create form.
     */
    public function create(): void
    {
        Auth::requireAuth();
        $categories = Category::getTree();
        $roots = Location::getRoots(true);
        $this->view('listings.form', [
            'title' => 'Add Listing',
            'listing' => null,
            'categories' => $categories,
            'locationRoots' => $roots,
            'csrf_token' => Csrf::token(),
        ]);
    }

    /**
     * User: store new listing (submit for approval).
     */
    public function store(): void
    {
        Auth::requireAuth();
        $userId = Auth::id();
        if (!$userId) {
            $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (!Csrf::validate($input[Csrf::fieldName()] ?? null)) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 400);
            return;
        }
        $title = Sanitize::string($input['title'] ?? '');
        $description = Sanitize::text($input['description'] ?? '');
        $shortDescription = Sanitize::string($input['short_description'] ?? '');
        $categoryId = Sanitize::int($input['category_id'] ?? 0);
        $locationId = !empty($input['location_id']) ? Sanitize::int($input['location_id']) : null;
        $address = Sanitize::text($input['address'] ?? '');
        $phone = Sanitize::string($input['phone'] ?? '');
        $whatsapp = Sanitize::string($input['whatsapp'] ?? '');
        $email = Sanitize::email($input['email'] ?? '');
        $website = Sanitize::url($input['website'] ?? '');
        $submitForApproval = !empty($input['submit_for_approval']);

        $errors = [];
        if (strlen($title) < 2) {
            $errors[] = 'Title is required (min 2 characters).';
        }
        if ($categoryId < 1) {
            $errors[] = 'Please select a category.';
        }
        if (!empty($errors)) {
            $this->json(['success' => false, 'errors' => $errors], 400);
            return;
        }

        $slug = Listing::generateSlug($title);
        $slug = Listing::makeSlugUnique($slug);
        $status = $submitForApproval ? Listing::STATUS_PENDING : Listing::STATUS_DRAFT;

        try {
            $id = Listing::create([
                'user_id' => $userId,
                'category_id' => $categoryId,
                'location_id' => $locationId,
                'title' => $title,
                'slug' => $slug,
                'description' => $description,
                'short_description' => $shortDescription,
                'address' => $address,
                'phone' => $phone,
                'whatsapp' => $whatsapp,
                'email' => $email,
                'website' => $website,
                'status' => $status,
            ]);
            Csrf::regenerate();
            $this->json(['success' => true, 'message' => $submitForApproval ? 'Listing submitted for approval.' : 'Draft saved.', 'id' => $id]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * User: edit form (only owner).
     */
    public function edit(int $id): void
    {
        Auth::requireAuth();
        $listing = Listing::findById($id);
        if (!$listing) {
            http_response_code(404);
            die('Listing not found.');
        }
        if ((int) $listing['user_id'] !== Auth::id() && !Auth::hasRole('admin')) {
            http_response_code(403);
            die('You can only edit your own listings.');
        }
        $categories = Category::getTree();
        $roots = Location::getRoots(true);
        $this->view('listings.form', [
            'title' => 'Edit Listing',
            'listing' => $listing,
            'categories' => $categories,
            'locationRoots' => $roots,
            'csrf_token' => Csrf::token(),
        ]);
    }

    /**
     * User: update (only owner).
     */
    public function update(int $id): void
    {
        Auth::requireAuth();
        $listing = Listing::findById($id);
        if (!$listing) {
            $this->json(['success' => false, 'message' => 'Listing not found.'], 404);
            return;
        }
        if ((int) $listing['user_id'] !== Auth::id() && !Auth::hasRole('admin')) {
            $this->json(['success' => false, 'message' => 'Forbidden.'], 403);
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (!Csrf::validate($input[Csrf::fieldName()] ?? null)) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 400);
            return;
        }
        $title = Sanitize::string($input['title'] ?? '');
        $description = Sanitize::text($input['description'] ?? '');
        $shortDescription = Sanitize::string($input['short_description'] ?? '');
        $categoryId = Sanitize::int($input['category_id'] ?? 0);
        $locationId = !empty($input['location_id']) ? Sanitize::int($input['location_id']) : null;
        $address = Sanitize::text($input['address'] ?? '');
        $phone = Sanitize::string($input['phone'] ?? '');
        $whatsapp = Sanitize::string($input['whatsapp'] ?? '');
        $email = Sanitize::email($input['email'] ?? '');
        $website = Sanitize::url($input['website'] ?? '');
        $submitForApproval = !empty($input['submit_for_approval']);

        $errors = [];
        if (strlen($title) < 2) {
            $errors[] = 'Title is required (min 2 characters).';
        }
        if ($categoryId < 1) {
            $errors[] = 'Please select a category.';
        }
        if (!empty($errors)) {
            $this->json(['success' => false, 'errors' => $errors], 400);
            return;
        }

        $slug = $listing['slug'];
        if ($title !== $listing['title']) {
            $slug = Listing::generateSlug($title);
            $slug = Listing::makeSlugUnique($slug, $id);
        }
        $status = $listing['status'];
        if ($submitForApproval && in_array($listing['status'], [Listing::STATUS_DRAFT, Listing::STATUS_REJECTED], true)) {
            $status = Listing::STATUS_PENDING;
        }

        try {
            Listing::update($id, [
                'category_id' => $categoryId,
                'location_id' => $locationId,
                'title' => $title,
                'slug' => $slug,
                'description' => $description,
                'short_description' => $shortDescription,
                'address' => $address,
                'phone' => $phone,
                'whatsapp' => $whatsapp,
                'email' => $email,
                'website' => $website,
                'status' => $status,
            ]);
            Csrf::regenerate();
            $this->json(['success' => true, 'message' => 'Listing updated.']);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * User: delete (only owner). Soft delete.
     */
    public function delete(int $id): void
    {
        Auth::requireAuth();
        $listing = Listing::findById($id);
        if (!$listing) {
            $this->json(['success' => false, 'message' => 'Listing not found.'], 404);
            return;
        }
        if ((int) $listing['user_id'] !== Auth::id() && !Auth::hasRole('admin')) {
            $this->json(['success' => false, 'message' => 'Forbidden.'], 403);
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (!Csrf::validate($input[Csrf::fieldName()] ?? null)) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 400);
            return;
        }
        Listing::softDelete($id);
        Csrf::regenerate();
        $this->json(['success' => true, 'message' => 'Listing deleted.']);
    }

    /**
     * Admin: pending approvals panel.
     */
    public function pending(): void
    {
        Auth::requireRole('admin');
        Listing::expireListingsForExpiredSubscriptions();
        $listings = Listing::getPendingApprovals();
        $this->view('admin.listings.pending', [
            'title' => 'Pending Approvals',
            'listings' => $listings,
        ]);
    }

    /**
     * Admin: approve listing.
     */
    public function approve(int $id): void
    {
        Auth::requireRole('admin');
        $listing = Listing::findById($id);
        if (!$listing) {
            $this->json(['success' => false, 'message' => 'Listing not found.'], 404);
            return;
        }
        if ($listing['status'] !== Listing::STATUS_PENDING) {
            $this->json(['success' => false, 'message' => 'Listing is not pending.'], 400);
            return;
        }
        Listing::updateStatus($id, Listing::STATUS_APPROVED);
        $this->json(['success' => true, 'message' => 'Listing approved.']);
    }

    /**
     * Admin: reject listing.
     */
    public function reject(int $id): void
    {
        Auth::requireRole('admin');
        $listing = Listing::findById($id);
        if (!$listing) {
            $this->json(['success' => false, 'message' => 'Listing not found.'], 404);
            return;
        }
        if ($listing['status'] !== Listing::STATUS_PENDING) {
            $this->json(['success' => false, 'message' => 'Listing is not pending.'], 400);
            return;
        }
        Listing::updateStatus($id, Listing::STATUS_REJECTED);
        $this->json(['success' => true, 'message' => 'Listing rejected.']);
    }
}
