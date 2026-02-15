<?php
use App\Helpers\CategoryHelper;
$content = ob_start();
$isEdit = $listing !== null;
?>
<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b">
        <h2 class="text-2xl font-bold text-gray-900"><?= $isEdit ? 'Edit Listing' : 'Add Listing' ?></h2>
    </div>
    <form id="listingForm" class="p-6 space-y-4">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700">Business / Listing name *</label>
            <input type="text" id="title" name="title" required value="<?= $listing ? htmlspecialchars($listing['title']) : '' ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. My Restaurant">
        </div>
        <div>
            <label for="category_id" class="block text-sm font-medium text-gray-700">Category *</label>
            <select id="category_id" name="category_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 sm:text-sm">
                <option value="">Select category</option>
                <?= CategoryHelper::renderSelectOptions($categories, $listing ? (int)$listing['category_id'] : null) ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Location</label>
            <div class="mt-1 flex gap-2">
                <select id="location_parent" class="flex-1 rounded-md border-gray-300 shadow-sm sm:text-sm">
                    <option value="">Select...</option>
                </select>
                <select id="location_id" name="location_id" class="flex-1 rounded-md border-gray-300 shadow-sm sm:text-sm">
                    <option value="">Select...</option>
                </select>
            </div>
        </div>
        <div>
            <label for="short_description" class="block text-sm font-medium text-gray-700">Short description</label>
            <textarea id="short_description" name="short_description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" placeholder="One line summary"><?= $listing ? htmlspecialchars($listing['short_description'] ?? '') : '' ?></textarea>
        </div>
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
            <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"><?= $listing ? htmlspecialchars($listing['description'] ?? '') : '' ?></textarea>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                <input type="text" id="address" name="address" value="<?= $listing ? htmlspecialchars($listing['address'] ?? '') : '' ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                <input type="text" id="phone" name="phone" value="<?= $listing ? htmlspecialchars($listing['phone'] ?? '') : '' ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <label for="whatsapp" class="block text-sm font-medium text-gray-700">WhatsApp</label>
                <input type="text" id="whatsapp" name="whatsapp" value="<?= $listing ? htmlspecialchars($listing['whatsapp'] ?? '') : '' ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" placeholder="With country code">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="email" name="email" value="<?= $listing ? htmlspecialchars($listing['email'] ?? '') : '' ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
            </div>
        </div>
        <div>
            <label for="website" class="block text-sm font-medium text-gray-700">Website</label>
            <input type="url" id="website" name="website" value="<?= $listing ? htmlspecialchars($listing['website'] ?? '') : '' ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" placeholder="https://">
        </div>
        <?php if (!$isEdit || in_array($listing['status'] ?? '', ['draft', 'rejected'])): ?>
        <div class="flex items-center">
            <input type="checkbox" id="submit_for_approval" name="submit_for_approval" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
            <label for="submit_for_approval" class="ml-2 text-sm text-gray-700">Submit for approval (go live after admin approves)</label>
        </div>
        <?php endif; ?>
        <div class="flex justify-end gap-2 pt-4">
            <a href="<?= $isEdit ? '/my-listings' : '/listings/create' ?>" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300">Cancel</a>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700"><?= $isEdit ? 'Update' : 'Save' ?></button>
        </div>
    </form>
</div>
<script>
(function() {
    const locationParent = document.getElementById('location_parent');
    const locationId = document.getElementById('location_id');
    const presetLocationId = <?= $listing && !empty($listing['location_id']) ? (int)$listing['location_id'] : 'null' ?>;

    function loadLocations(parentId, intoSelect, selectedId) {
        fetch('/api/locations/children/' + (parentId || 0))
            .then(r => r.json())
            .then(d => {
                if (!d.success) return;
                intoSelect.innerHTML = '<option value="">Select...</option>';
                (d.data || []).forEach(function(r) {
                    const opt = document.createElement('option');
                    opt.value = r.id;
                    opt.textContent = r.name;
                    if (selectedId && r.id == selectedId) opt.selected = true;
                    intoSelect.appendChild(opt);
                });
            });
    }

    loadLocations(0, locationParent, null);
    locationParent.addEventListener('change', function() {
        const pid = this.value;
        locationId.innerHTML = '<option value="">Select...</option>';
        if (pid) loadLocations(pid, locationId, null);
    });
    locationId.addEventListener('change', function() {});

    document.getElementById('listingForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const locVal = locationId.value || locationParent.value || '';
        const payload = {
            _token: document.querySelector('input[name="_token"]').value,
            title: document.getElementById('title').value,
            category_id: document.getElementById('category_id').value,
            location_id: locVal || null,
            short_description: document.getElementById('short_description').value,
            description: document.getElementById('description').value,
            address: document.getElementById('address').value,
            phone: document.getElementById('phone').value,
            whatsapp: document.getElementById('whatsapp').value,
            email: document.getElementById('email').value,
            website: document.getElementById('website').value,
            submit_for_approval: document.getElementById('submit_for_approval') && document.getElementById('submit_for_approval').checked
        };
        const url = <?= $isEdit ? json_encode('/listings/update/' . $listing['id']) : '"/listings/store"' ?>;
        const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const d = await r.json();
        if (d.success) {
            alert(d.message);
            window.location.href = '/my-listings';
        } else {
            alert(d.errors ? d.errors.join('\\n') : d.message || 'Failed');
        }
    });
})();
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/app.php';
?>
