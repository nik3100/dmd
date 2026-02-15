<?php
$content = ob_start();
$l = $listing;
?>
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b">
        <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($l['title']) ?></h1>
        <p class="text-sm text-gray-500 mt-1">
            <?php if (!empty($l['category_name'])): ?>
                <span><?= htmlspecialchars($l['category_name']) ?></span>
            <?php endif; ?>
            <?php if (!empty($l['location_name'])): ?>
                <span class="ml-2">• <?= htmlspecialchars($l['location_name']) ?></span>
            <?php endif; ?>
            <span class="ml-2">• <?= (int) $l['view_count'] ?> views</span>
        </p>
    </div>
    <div class="px-6 py-4 space-y-4">
        <?php if (!empty($l['short_description'])): ?>
            <p class="text-gray-700"><?= nl2br(htmlspecialchars($l['short_description'])) ?></p>
        <?php endif; ?>
        <?php if (!empty($l['description'])): ?>
            <div class="prose max-w-none">
                <?= nl2br(htmlspecialchars($l['description'])) ?>
            </div>
        <?php endif; ?>
        <dl class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <?php if (!empty($l['address'])): ?>
                <div><dt class="text-sm font-medium text-gray-500">Address</dt><dd><?= htmlspecialchars($l['address']) ?></dd></div>
            <?php endif; ?>
            <?php if (!empty($l['phone'])): ?>
                <div><dt class="text-sm font-medium text-gray-500">Phone</dt><dd><a href="tel:<?= htmlspecialchars($l['phone']) ?>" class="text-indigo-600"><?= htmlspecialchars($l['phone']) ?></a></dd></div>
            <?php endif; ?>
            <?php if (!empty($l['whatsapp'])): ?>
                <div><dt class="text-sm font-medium text-gray-500">WhatsApp</dt><dd><a href="https://wa.me/<?= preg_replace('/\D/', '', $l['whatsapp']) ?>" class="text-indigo-600" target="_blank" rel="noopener"><?= htmlspecialchars($l['whatsapp']) ?></a></dd></div>
            <?php endif; ?>
            <?php if (!empty($l['email'])): ?>
                <div><dt class="text-sm font-medium text-gray-500">Email</dt><dd><a href="mailto:<?= htmlspecialchars($l['email']) ?>" class="text-indigo-600"><?= htmlspecialchars($l['email']) ?></a></dd></div>
            <?php endif; ?>
            <?php if (!empty($l['website'])): ?>
                <div><dt class="text-sm font-medium text-gray-500">Website</dt><dd><a href="<?= htmlspecialchars($l['website']) ?>" class="text-indigo-600" target="_blank" rel="noopener"><?= htmlspecialchars($l['website']) ?></a></dd></div>
            <?php endif; ?>
        </dl>
    </div>
</div>
<p class="mt-4"><a href="/listings" class="text-indigo-600 hover:underline">← Back to listings</a></p>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/app.php';
?>
