<?php
$content = ob_start();
$types = $types ?? \App\Models\Location::TYPE_HIERARCHY;
?>
<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-2xl font-bold text-gray-900">Edit Location</h2>
        <?php if (!empty($path)): ?>
            <p class="text-sm text-gray-500 mt-1"><?= \App\Helpers\LocationHelper::renderBreadcrumb($path) ?></p>
        <?php endif; ?>
    </div>
    <div class="p-6">
        <form id="locationForm" class="space-y-6">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="parent_id" id="parent_id" value="<?= (int)($location['parent_id'] ?? 0) ?>">

            <div>
                <label class="block text-sm font-medium text-gray-700">Type *</label>
                <select id="location_type" name="type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <?php foreach ($types as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= ($location['type'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="parentDropdowns">
                <label class="block text-sm font-medium text-gray-700 mb-2">Parent</label>
                <div id="levelSelects" class="space-y-2"></div>
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Name *</label>
                <input type="text" id="name" name="name" required value="<?= htmlspecialchars($location['name']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700">Code</label>
                <input type="text" id="code" name="code" value="<?= htmlspecialchars($location['code'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude</label>
                    <input type="number" step="any" id="latitude" name="latitude" value="<?= htmlspecialchars($location['latitude'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                </div>
                <div>
                    <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude</label>
                    <input type="number" step="any" id="longitude" name="longitude" value="<?= htmlspecialchars($location['longitude'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                </div>
            </div>
            <div class="flex items-center">
                <input type="checkbox" id="is_active" name="is_active" <?= !empty($location['is_active']) ? 'checked' : '' ?> class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                <label for="is_active" class="ml-2 block text-sm text-gray-700">Active</label>
            </div>
            <div class="flex justify-end space-x-3">
                <a href="/admin/locations" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">Cancel</a>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Update Location</button>
            </div>
        </form>
    </div>
</div>

<script>
const types = <?= json_encode($types) ?>;
const typeIndex = { country: 0, state: 1, district: 2, taluka: 3, village: 4, area: 5, locality: 6 };
<?php $parentChain = $path ? array_slice($path, 0, -1) : []; ?>
const currentPath = <?= json_encode(array_map(fn($p) => ['id' => (int)$p['id'], 'type' => $p['type'], 'name' => $p['name']], $parentChain)) ?>;

function getLevelLabel(idx) {
    return types[idx] ? types[idx].charAt(0).toUpperCase() + types[idx].slice(1) : '';
}

function renderParentDropdowns() {
    const type = document.getElementById('location_type').value;
    const idx = typeIndex[type] ?? 0;
    const container = document.getElementById('levelSelects');
    container.innerHTML = '';
    if (idx === 0) {
        document.getElementById('parent_id').value = '';
        return;
    }
    for (let i = 0; i < idx; i++) {
        const wrap = document.createElement('div');
        wrap.className = 'flex items-center gap-2';
        wrap.innerHTML = '<label class="w-24 text-sm text-gray-600">' + getLevelLabel(i) + '</label>' +
            '<select class="parent-level flex-1 rounded-md border-gray-300 shadow-sm sm:text-sm" data-level="' + types[i] + '" data-idx="' + i + '">' +
            '<option value="">Select ' + getLevelLabel(i) + '</option></select>';
        container.appendChild(wrap);
    }
    loadRoots();
}

function loadRoots() {
    fetch('/admin/locations/children/0')
        .then(r => r.json())
        .then(d => {
            if (!d.success) return;
            const sel = document.querySelector('.parent-level[data-idx="0"]');
            if (!sel) return;
            sel.innerHTML = '<option value="">Select ' + getLevelLabel(0) + '</option>';
            (d.data || []).forEach(r => {
                sel.innerHTML += '<option value="' + r.id + '">' + r.name + '</option>';
            });
            if (currentPath.length > 0) {
                sel.value = currentPath[0].id;
                cascadeLoadForEdit(0);
            }
        }).catch(() => {});
}

function loadChildren(parentId, intoSelect) {
    if (!parentId || parentId === '0') return;
    fetch('/admin/locations/children/' + parentId)
        .then(r => r.json())
        .then(d => {
            if (!d.success) return;
            intoSelect.innerHTML = '<option value="">Select...</option>';
            (d.data || []).forEach(r => {
                intoSelect.innerHTML += '<option value="' + r.id + '">' + r.name + '</option>';
            });
        });
}

function cascadeLoadForEdit(fromIdx) {
    const all = document.querySelectorAll('.parent-level');
    for (let i = fromIdx; i < currentPath.length && i + 1 < all.length; i++) {
        const pid = currentPath[i].id;
        const nextSel = all[i + 1];
        loadChildren(pid, nextSel);
    }
    const lastIdx = currentPath.length - 1;
    if (lastIdx >= 0) {
        setTimeout(function setSelected() {
            const all = document.querySelectorAll('.parent-level');
            currentPath.forEach((p, i) => {
                if (all[i]) all[i].value = p.id;
            });
            document.getElementById('parent_id').value = currentPath.length ? currentPath[currentPath.length - 1].id : '';
        }, 300);
    }
}

document.getElementById('levelSelects').addEventListener('change', function(e) {
    const sel = e.target;
    if (!sel.classList.contains('parent-level')) return;
    const idx = parseInt(sel.dataset.idx, 10);
    const val = sel.value;
    const all = document.querySelectorAll('.parent-level');
    for (let i = idx + 1; i < all.length; i++) {
        all[i].innerHTML = '<option value="">Select...</option>';
    }
    document.getElementById('parent_id').value = val || '';
    if (val && idx < all.length - 1) {
        loadChildren(parseInt(val, 10), all[idx + 1]);
    }
});

document.getElementById('locationForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const payload = {
        _token: document.querySelector('input[name="_token"]').value,
        name: document.getElementById('name').value,
        type: document.getElementById('location_type').value,
        parent_id: document.getElementById('parent_id').value || null,
        code: document.getElementById('code').value,
        latitude: document.getElementById('latitude').value || null,
        longitude: document.getElementById('longitude').value || null,
        is_active: document.getElementById('is_active').checked ? 1 : 0
    };
    const r = await fetch('/admin/locations/update/<?= (int)$location['id'] ?>', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
    const d = await r.json();
    if (d.success) {
        alert('Location updated.');
        window.location.href = '/admin/locations';
    } else {
        alert(d.errors ? d.errors.join('\n') : d.message || 'Failed');
    }
});

document.getElementById('location_type').addEventListener('change', renderParentDropdowns);
renderParentDropdowns();
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/admin.php';
?>
