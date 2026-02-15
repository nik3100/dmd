<?php
use App\Helpers\CategoryHelper;
$content = ob_start();
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-2xl font-bold text-gray-900">Create Category</h2>
    </div>
    
    <div class="p-6">
        <form id="categoryForm" class="space-y-6">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Category Name *</label>
                <input type="text" id="name" name="name" required 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                       placeholder="e.g., Restaurants">
            </div>
            
            <div>
                <label for="parent_id" class="block text-sm font-medium text-gray-700">Parent Category</label>
                <select id="parent_id" name="parent_id" 
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">None (Root Category)</option>
                    <?= CategoryHelper::renderSelectOptions($categories) ?>
                </select>
            </div>
            
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea id="description" name="description" rows="3"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                          placeholder="Optional description"></textarea>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" value="0"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" id="is_active" name="is_active" checked
                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">Active</label>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3">
                <a href="/admin/categories" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                    Cancel
                </a>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                    Create Category
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('categoryForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    const response = await fetch('/admin/categories/store', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    });
    
    const result = await response.json();
    
    if (result.success) {
        alert('Category created successfully!');
        window.location.href = '/admin/categories';
    } else {
        let errorMsg = result.message || 'Failed to create category.';
        if (result.errors) {
            errorMsg = result.errors.join('\\n');
        }
        alert(errorMsg);
    }
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/admin.php';
?>
