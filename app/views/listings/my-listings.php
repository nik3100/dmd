<?php
$content = ob_start();
$statusLabels = [
    'draft' => 'Draft',
    'pending_approval' => 'Pending Approval',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'expired' => 'Expired',
    'suspended' => 'Suspended',
];
$statusClass = [
    'draft' => 'bg-gray-100 text-gray-800',
    'pending_approval' => 'bg-yellow-100 text-yellow-800',
    'approved' => 'bg-green-100 text-green-800',
    'rejected' => 'bg-red-100 text-red-800',
    'expired' => 'bg-orange-100 text-orange-800',
    'suspended' => 'bg-red-100 text-red-800',
];
?>
<div class="mb-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold text-gray-900">My Listings</h1>
    <a href="/listings/create" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Add Listing</a>
</div>
<?php if (empty($listings)): ?>
    <div class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
        <p>You have no listings yet.</p>
        <a href="/listings/create" class="inline-block mt-2 text-indigo-600 hover:underline">Create your first listing</a>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($listings as $l): ?>
                    <tr>
                        <td class="px-6 py-4">
                            <a href="<?= $l['status'] === 'approved' ? '/listings/show/' . htmlspecialchars($l['slug']) : '#' ?>" class="font-medium text-indigo-600 hover:underline"><?= htmlspecialchars($l['title']) ?></a>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($l['category_name'] ?? '') ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded <?= $statusClass[$l['status']] ?? 'bg-gray-100' ?>"><?= $statusLabels[$l['status']] ?? $l['status'] ?></span>
                        </td>
                        <td class="px-6 py-4 text-sm space-x-2">
                            <a href="/listings/edit/<?= (int) $l['id'] ?>" class="text-indigo-600 hover:underline">Edit</a>
                            <button type="button" onclick="deleteListing(<?= (int) $l['id'] ?>)" class="text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<script>
async function deleteListing(id) {
    if (!confirm('Delete this listing?')) return;
    const form = new FormData();
    form.append('_token', '<?= \App\Helpers\Csrf::token() ?>');
    const r = await fetch('/listings/delete/' + id, { method: 'POST', body: form });
    const d = await r.json();
    if (d.success) location.reload();
    else alert(d.message || 'Failed');
}
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/app.php';
?>
