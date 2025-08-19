<?php
// Check if setup is already completed
$users_exist = Database::selectOne("SELECT COUNT(*) as count FROM users");
if ($users_exist && $users_exist['count'] > 0) {
    header('Location: /auth/login');
    exit;
}

$error = $error ?? null;
$success = $success ?? null;
?>

<div class="min-h-screen bg-black flex items-center justify-center px-4">
    <!-- Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-purple-900/20 via-black to-blue-900/20"></div>
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl animate-pulse delay-1000"></div>
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PGNpcmNsZSBjeD0iNzAiIGN5PSI3MCIgcj0iMSIvPjwvZz48L2c+PC9zdmc+')] opacity-20"></div>
    </div>

    <div class="relative w-full max-w-lg">
        <!-- Logo & Welcome -->
        <div class="text-center mb-8">
            <h1 class="text-5xl font-black text-white mb-3 tracking-tight">
                horizn<span class="text-purple-400">_</span>
            </h1>
            <p class="text-gray-400 text-sm font-medium mb-6">Analytics Platform</p>
            <div class="text-center">
                <h2 class="text-2xl font-bold text-white mb-2">Welcome to horizn_</h2>
                <p class="text-gray-400 text-base">Set up your admin account to get started</p>
            </div>
        </div>

        <!-- Setup Form -->
        <div class="bg-gray-900/50 backdrop-blur-lg border border-gray-800 rounded-2xl p-8 shadow-2xl">
            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-900/50 border border-red-700 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <p class="text-red-300 text-sm"><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-6 p-4 bg-green-900/50 border border-green-700 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <p class="text-green-300 text-sm"><?= htmlspecialchars($success) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="/auth/setup" class="space-y-6">
                <!-- Personal Information -->
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4">Personal Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-300 mb-2">
                                First Name
                            </label>
                            <input
                                type="text"
                                id="first_name"
                                name="first_name"
                                value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                                required
                                class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                                placeholder="John"
                            >
                        </div>
                        
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-300 mb-2">
                                Last Name
                            </label>
                            <input
                                type="text"
                                id="last_name"
                                name="last_name"
                                value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                                required
                                class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                                placeholder="Doe"
                            >
                        </div>
                    </div>
                </div>

                <!-- Account Information -->
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4">Account Information</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-300 mb-2">
                                Email Address
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                required
                                class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                                placeholder="admin@example.com"
                            >
                            <p class="mt-2 text-xs text-gray-500">
                                This will be your login email. We'll send you a secure login link.
                            </p>
                        </div>
                        
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-300 mb-2">
                                Username
                            </label>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                required
                                pattern="[a-zA-Z0-9_-]+"
                                class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                                placeholder="admin"
                            >
                            <p class="mt-2 text-xs text-gray-500">
                                Letters, numbers, hyphens, and underscores only
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Security Information -->
                <div class="bg-gray-800/30 border border-gray-700/50 rounded-lg p-6">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 mt-1">
                            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-purple-400 mb-2">Passwordless Authentication</h4>
                            <p class="text-xs text-gray-400 leading-relaxed">
                                horizn_ uses secure magic link authentication. Instead of passwords, 
                                you'll receive a unique login link via email each time you sign in. 
                                This eliminates password-related security risks.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="w-full bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-semibold py-4 px-6 rounded-lg transition-all duration-200 transform hover:scale-[1.01] focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 focus:ring-offset-gray-900 shadow-lg"
                >
                    Create Admin Account & Send Login Link
                </button>

                <!-- Terms -->
                <p class="text-xs text-gray-500 text-center leading-relaxed">
                    By creating an account, you agree to our 
                    <a href="#" class="text-purple-400 hover:text-purple-300 transition-colors">Terms of Service</a> 
                    and 
                    <a href="#" class="text-purple-400 hover:text-purple-300 transition-colors">Privacy Policy</a>
                </p>
            </form>
        </div>

        <!-- Features Preview -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-900/30 backdrop-blur-sm border border-gray-800 rounded-lg p-4 text-center">
                <div class="w-8 h-8 bg-purple-500/20 rounded-lg flex items-center justify-center mx-auto mb-2">
                    <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h4 class="text-sm font-semibold text-white mb-1">Real-time Analytics</h4>
                <p class="text-xs text-gray-400">Live visitor tracking</p>
            </div>
            
            <div class="bg-gray-900/30 backdrop-blur-sm border border-gray-800 rounded-lg p-4 text-center">
                <div class="w-8 h-8 bg-purple-500/20 rounded-lg flex items-center justify-center mx-auto mb-2">
                    <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h4 class="text-sm font-semibold text-white mb-1">Privacy First</h4>
                <p class="text-xs text-gray-400">GDPR compliant</p>
            </div>
            
            <div class="bg-gray-900/30 backdrop-blur-sm border border-gray-800 rounded-lg p-4 text-center">
                <div class="w-8 h-8 bg-purple-500/20 rounded-lg flex items-center justify-center mx-auto mb-2">
                    <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h4 class="text-sm font-semibold text-white mb-1">Lightning Fast</h4>
                <p class="text-xs text-gray-400">Sub-100ms responses</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate username from email
    const emailInput = document.getElementById('email');
    const usernameInput = document.getElementById('username');
    
    emailInput.addEventListener('input', function() {
        if (!usernameInput.value) {
            const email = this.value;
            const username = email.split('@')[0].replace(/[^a-zA-Z0-9_-]/g, '');
            usernameInput.value = username;
        }
    });
    
    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const email = emailInput.value;
        const username = usernameInput.value;
        
        // Email validation
        if (!email.includes('@')) {
            e.preventDefault();
            alert('Please enter a valid email address.');
            return;
        }
        
        // Username validation
        if (!/^[a-zA-Z0-9_-]+$/.test(username)) {
            e.preventDefault();
            alert('Username can only contain letters, numbers, hyphens, and underscores.');
            return;
        }
        
        if (username.length < 3) {
            e.preventDefault();
            alert('Username must be at least 3 characters long.');
            return;
        }
    });
});
</script>