<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Dashboard') ?> - Universal Business Directory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-gray-900">Universal Business Directory</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700"><?= htmlspecialchars($user['name'] ?? 'User') ?></span>
                    <a href="/logout" class="text-indigo-600 hover:text-indigo-500">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Welcome to your Dashboard</h2>
                
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700">User Information</h3>
                        <dl class="mt-2 grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Name</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($user['name'] ?? 'N/A') ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($user['email'] ?? 'N/A') ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">User ID</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars((string)($user['id'] ?? 'N/A')) ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Slug</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($user['slug'] ?? 'N/A') ?></dd>
                            </div>
                        </dl>
                    </div>

                    <div class="border-t border-gray-200 pt-4">
                        <h3 class="text-lg font-semibold text-gray-700">Roles</h3>
                        <div class="mt-2">
                            <?php if (!empty($roles)): ?>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($roles as $role): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                            <?= htmlspecialchars($role) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-sm text-gray-500">No roles assigned</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-4">
                        <h3 class="text-lg font-semibold text-gray-700">Quick Actions</h3>
                        <div class="mt-2 space-x-4">
                            <a href="/" class="text-indigo-600 hover:text-indigo-500">Home</a>
                            <a href="/dashboard" class="text-indigo-600 hover:text-indigo-500">Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
