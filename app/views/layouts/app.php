<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Listings') ?> - Universal Business Directory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14">
                <div class="flex items-center">
                    <a href="/" class="text-xl font-bold text-gray-900">Universal Business Directory</a>
                    <a href="/listings" class="ml-6 text-gray-600 hover:text-gray-900">Listings</a>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if (\App\Helpers\Auth::check()): ?>
                        <a href="/my-listings" class="text-gray-600 hover:text-gray-900">My Listings</a>
                        <a href="/dashboard" class="text-gray-600 hover:text-gray-900">Dashboard</a>
                        <a href="/logout" class="text-gray-600 hover:text-gray-900">Logout</a>
                    <?php else: ?>
                        <a href="/login" class="text-gray-600 hover:text-gray-900">Login</a>
                        <a href="/register" class="text-gray-600 hover:text-gray-900">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <?= $content ?? '' ?>
    </main>
</body>
</html>
