<?php
$content = ob_start();
?>
<h1 class="text-2xl font-bold text-gray-900 mb-4">Listings</h1>
<?php if (empty($listings)): ?>
    <p class="text-gray-500">No listings yet.</p>
<?php else: ?>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($listings as $l): ?>
            <a href="/listings/show/<?= htmlspecialchars($l['slug']) ?>" class="block bg-white rounded-lg shadow hover:shadow-md transition p-4 border border-gray-200">
                <h2 class="font-semibold text-gray-900"><?= htmlspecialchars($l['title']) ?></h2>
                <?php if (!empty($l['short_description'])): ?>
                    <p class="text-sm text-gray-600 mt-1 line-clamp-2"><?= htmlspecialchars($l['short_description']) ?></p>
                <?php endif; ?>
                <p class="text-xs text-gray-500 mt-2">
                    <?= htmlspecialchars($l['category_name'] ?? '') ?>
                    <?php if (!empty($l['location_name'])): ?> • <?= htmlspecialchars($l['location_name']) ?><?php endif; ?>
                </p>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/app.php';
?>
