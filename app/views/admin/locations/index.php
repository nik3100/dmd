<?php
use App\Helpers\LocationHelper;
$content = ob_start();
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-900">Locations</h2>
            <a href="/admin/locations/create" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Add Location</a>
        </div>
        <p class="text-sm text-gray-500 mt-1">Country → State → District → Taluka → Village → Area → Locality</p>
    </div>
    <div class="p-6">
        <?php if (empty($locations)): ?>
            <p class="text-gray-500">No locations yet. <a href="/admin/locations/create" class="text-indigo-600 hover:underline">Add one</a></p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug / Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        function renderLocationRow($loc, $level = 0) {
                            $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
                            $typeLabel = ucfirst($loc['type']);
                            $activeClass = !empty($loc['is_active']) ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
                            $activeText = !empty($loc['is_active']) ? 'Active' : 'Inactive';
                            echo '<tr>';
                            echo '<td class="px-6 py-4">' . $indent . '<strong>' . htmlspecialchars($loc['name']) . '</strong></td>';
                            echo '<td class="px-6 py-4">' . $typeLabel . '</td>';
                            echo '<td class="px-6 py-4 text-sm">' . htmlspecialchars($loc['slug']) . ($loc['code'] ? ' / ' . htmlspecialchars($loc['code']) : '') . '</td>';
                            echo '<td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded ' . $activeClass . '">' . $activeText . '</span></td>';
                            echo '<td class="px-6 py-4 text-sm space-x-2">';
                            echo '<a href="/admin/locations/edit/' . $loc['id'] . '" class="text-indigo-600 hover:underline">Edit</a>';
                            echo '<button type="button" onclick="toggleActive(' . $loc['id'] . ')" class="text-blue-600 hover:underline">' . (!empty($loc['is_active']) ? 'Disable' : 'Enable') . '</button>';
                            echo '<button type="button" onclick="deleteLocation(' . $loc['id'] . ')" class="text-red-600 hover:underline">Delete</button>';
                            echo '</td></tr>';
                            if (!empty($loc['children'])) {
                                foreach ($loc['children'] as $child) {
                                    renderLocationRow($child, $level + 1);
                                }
                            }
                        }
                        foreach ($locations as $loc) {
                            renderLocationRow($loc);
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
async function deleteLocation(id) {
    if (!confirm('Delete this location? Cannot delete if it has children.')) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/locations/delete/' + id;
    const token = document.createElement('input');
    token.type = 'hidden';
    token.name = '_token';
    token.value = '<?= \App\Helpers\Csrf::token() ?>';
    form.appendChild(token);
    document.body.appendChild(form);
    form.submit();
}
async function toggleActive(id) {
    const r = await fetch('/admin/locations/toggle-active/' + id, { method: 'POST' });
    const d = await r.json();
    if (d.success) location.reload();
    else alert('Failed');
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/admin.php';
?>
