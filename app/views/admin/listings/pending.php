<?php
$content = ob_start();
?>
<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b">
        <h2 class="text-2xl font-bold text-gray-900">Pending Approvals</h2>
        <p class="text-sm text-gray-500">Listings waiting for admin approval. Only approved listings are visible publicly.</p>
    </div>
    <div class="p-6">
        <?php if (empty($listings)): ?>
            <p class="text-gray-500">No pending listings.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($listings as $l): ?>
                    <div class="border rounded-lg p-4 flex flex-wrap justify-between items-start gap-4">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($l['title']) ?></h3>
                            <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($l['category_name'] ?? '') ?> <?= !empty($l['location_name']) ? '• ' . htmlspecialchars($l['location_name']) : '' ?></p>
                            <?php if (!empty($l['short_description'])): ?>
                                <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($l['short_description']) ?></p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-400 mt-2">By <?= htmlspecialchars($l['user_name'] ?? '') ?> (<?= htmlspecialchars($l['user_email'] ?? '') ?>) • Updated <?= date('M j, Y', strtotime($l['updated_at'])) ?></p>
                        </div>
                        <div class="flex gap-2">
                            <a href="/listings/edit/<?= (int)$l['id'] ?>" class="text-sm text-gray-600 hover:underline">Edit / Preview</a>
                            <button type="button" onclick="approve(<?= (int)$l['id'] ?>)" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">Approve</button>
                            <button type="button" onclick="reject(<?= (int)$l['id'] ?>)" class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700">Reject</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
async function approve(id) {
    const r = await fetch('/admin/listings/approve/' + id, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({}) });
    const d = await r.json();
    if (d.success) location.reload();
    else alert(d.message || 'Failed');
}
async function reject(id) {
    const r = await fetch('/admin/listings/reject/' + id, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({}) });
    const d = await r.json();
    if (d.success) location.reload();
    else alert(d.message || 'Failed');
}
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/admin.php';
?>
