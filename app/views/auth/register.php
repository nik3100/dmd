<?php
$content = ob_start();
?>

<form class="mt-8 space-y-6" action="/register" method="POST">
    <input type="hidden" name="<?= \App\Helpers\Csrf::fieldName() ?>" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
    
    <div class="rounded-md shadow-sm space-y-4">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
            <input 
                id="name" 
                name="name" 
                type="text" 
                autocomplete="name" 
                required 
                class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                placeholder="John Doe"
                value="<?= htmlspecialchars($name ?? '') ?>"
            >
        </div>
        
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
            <input 
                id="email" 
                name="email" 
                type="email" 
                autocomplete="email" 
                required 
                class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                placeholder="john@example.com"
                value="<?= htmlspecialchars($email ?? '') ?>"
            >
        </div>
        
        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700">Phone (Optional)</label>
            <input 
                id="phone" 
                name="phone" 
                type="tel" 
                autocomplete="tel" 
                class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                placeholder="+1 (555) 123-4567"
                value="<?= htmlspecialchars($phone ?? '') ?>"
            >
        </div>
        
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input 
                id="password" 
                name="password" 
                type="password" 
                autocomplete="new-password" 
                required 
                minlength="8"
                class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                placeholder="At least 8 characters"
            >
            <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters long</p>
        </div>
        
        <div>
            <label for="password_confirm" class="block text-sm font-medium text-gray-700">Confirm Password</label>
            <input 
                id="password_confirm" 
                name="password_confirm" 
                type="password" 
                autocomplete="new-password" 
                required 
                minlength="8"
                class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                placeholder="Confirm your password"
            >
        </div>
    </div>

    <div>
        <button 
            type="submit" 
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
            Create Account
        </button>
    </div>
    
    <div class="text-center">
        <p class="text-sm text-gray-600">
            Already have an account? 
            <a href="/login" class="font-medium text-indigo-600 hover:text-indigo-500">
                Sign in here
            </a>
        </p>
    </div>
</form>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/auth.php';
?>
