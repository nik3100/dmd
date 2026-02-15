<?php
use App\Helpers\CategoryHelper;
$content = ob_start();
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-900">Categories</h2>
            <a href="/admin/categories/create" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                Create Category
            </a>
        </div>
    </div>
    
    <div class="p-6">
        <?php if (!empty($pendingSuggestions)): ?>
            <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-yellow-800 mb-2">Pending Category Suggestions (<?= count($pendingSuggestions) ?>)</h3>
                <div class="space-y-2">
                    <?php foreach ($pendingSuggestions as $suggestion): ?>
                        <div class="flex justify-between items-center bg-white p-3 rounded">
                            <div>
                                <strong><?= htmlspecialchars($suggestion['name']) ?></strong>
                                <?php if ($suggestion['description']): ?>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($suggestion['description']) ?></p>
                                <?php endif; ?>
                                <p class="text-xs text-gray-500">Suggested by: <?= htmlspecialchars($suggestion['user_name'] ?? 'Unknown') ?></p>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="approveSuggestion(<?= $suggestion['id'] ?>)" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                                    Approve
                                </button>
                                <button onclick="rejectSuggestion(<?= $suggestion['id'] ?>)" class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700">
                                    Reject
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="category-list">
            <?php if (empty($categories)): ?>
                <p class="text-gray-500">No categories yet. <a href="/admin/categories/create" class="text-indigo-600 hover:underline">Create one</a></p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sort Order</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            function renderCategoryRow($category, $level = 0) {
                                $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
                                $statusClass = $category['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
                                $statusText = $category['is_active'] ? 'Active' : 'Inactive';
                                
                                echo '<tr>';
                                echo '<td class="px-6 py-4 whitespace-nowrap">';
                                echo $indent . '<strong>' . htmlspecialchars($category['name']) . '</strong>';
                                if ($category['description']) {
                                    echo '<br><span class="text-sm text-gray-500">' . htmlspecialchars($category['description']) . '</span>';
                                }
                                echo '</td>';
                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($category['slug']) . '</td>';
                                echo '<td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 text-xs rounded-full ' . $statusClass . '">' . $statusText . '</span></td>';
                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars((string)$category['sort_order']) . '</td>';
                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">';
                                echo '<a href="/admin/categories/edit/' . $category['id'] . '" class="text-indigo-600 hover:text-indigo-900">Edit</a>';
                                echo '<button onclick="toggleActive(' . $category['id'] . ', ' . ($category['is_active'] ? 'false' : 'true') . ')" class="text-blue-600 hover:text-blue-900">' . ($category['is_active'] ? 'Disable' : 'Enable') . '</button>';
                                echo '<button onclick="deleteCategory(' . $category['id'] . ')" class="text-red-600 hover:text-red-900">Delete</button>';
                                echo '</td>';
                                echo '</tr>';
                                
                                if (!empty($category['children'])) {
                                    foreach ($category['children'] as $child) {
                                        renderCategoryRow($child, $level + 1);
                                    }
                                }
                            }
                            
                            foreach ($categories as $category) {
                                renderCategoryRow($category);
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
async function deleteCategory(id) {
    if (!confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/categories/delete/' + id;
    
    const token = document.createElement('input');
    token.type = 'hidden';
    token.name = '_token';
    token.value = '<?= \App\Helpers\Csrf::token() ?>';
    form.appendChild(token);
    
    document.body.appendChild(form);
    form.submit();
}

async function toggleActive(id, enable) {
    const response = await fetch('/admin/categories/toggle-active/' + id, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ enable: enable })
    });
    
    const data = await response.json();
    if (data.success) {
        location.reload();
    } else {
        alert('Failed to update category status.');
    }
}

async function approveSuggestion(id) {
    if (!confirm('Approve this category suggestion?')) {
        return;
    }
    
    const response = await fetch('/admin/categories/suggestions/approve/' + id, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    });
    
    const data = await response.json();
    if (data.success) {
        location.reload();
    } else {
        alert('Failed to approve suggestion: ' + data.message);
    }
}

async function rejectSuggestion(id) {
    if (!confirm('Reject this category suggestion?')) {
        return;
    }
    
    const response = await fetch('/admin/categories/suggestions/reject/' + id, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    });
    
    const data = await response.json();
    if (data.success) {
        location.reload();
    } else {
        alert('Failed to reject suggestion: ' + data.message);
    }
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/admin.php';
?>
