<?php
// This view is used for magic link processing feedback
$status = $_GET['status'] ?? 'processing';
$message = $_GET['message'] ?? '';
?>

<div class="min-h-screen bg-black flex items-center justify-center px-4">
    <!-- Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-purple-900/20 via-black to-blue-900/20"></div>
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl animate-pulse delay-1000"></div>
    </div>

    <div class="relative w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-black text-white mb-2 tracking-tight">
                horizn<span class="text-purple-400">_</span>
            </h1>
            <p class="text-gray-400 text-sm font-medium">Analytics Platform</p>
        </div>

        <!-- Main Card -->
        <div class="bg-gray-900/50 backdrop-blur-lg border border-gray-800 rounded-2xl p-8 shadow-2xl">
            
            <?php if ($status === 'processing'): ?>
                <!-- Processing State -->
                <div class="flex items-center justify-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                
                <h2 class="text-2xl font-bold text-center text-white mb-4">
                    Verifying Login Link
                </h2>
                
                <p class="text-gray-300 text-center mb-8">
                    Please wait while we verify your login link and sign you in...
                </p>
                
                <div class="space-y-3">
                    <div class="w-full bg-gray-800 rounded-full h-2">
                        <div class="bg-gradient-to-r from-purple-600 to-purple-400 h-2 rounded-full animate-pulse" style="width: 66%"></div>
                    </div>
                    <p class="text-xs text-gray-500 text-center">This should only take a moment...</p>
                </div>
                
                <script>
                    // Auto-redirect to dashboard after a brief delay
                    setTimeout(() => {
                        window.location.href = '/dashboard';
                    }, 2000);
                </script>
                
            <?php elseif ($status === 'success'): ?>
                <!-- Success State -->
                <div class="flex items-center justify-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-emerald-600 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
                
                <h2 class="text-2xl font-bold text-center text-white mb-4">
                    Login Successful!
                </h2>
                
                <p class="text-gray-300 text-center mb-8">
                    <?= htmlspecialchars($message ?: 'Welcome back! Redirecting to your dashboard...') ?>
                </p>
                
                <div class="text-center">
                    <button onclick="window.location.href='/dashboard'" class="bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200">
                        Continue to Dashboard
                    </button>
                </div>
                
                <script>
                    setTimeout(() => {
                        window.location.href = '/dashboard';
                    }, 3000);
                </script>
                
            <?php elseif ($status === 'error'): ?>
                <!-- Error State -->
                <div class="flex items-center justify-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-red-400 to-red-600 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                </div>
                
                <h2 class="text-2xl font-bold text-center text-white mb-4">
                    Login Link Invalid
                </h2>
                
                <p class="text-gray-300 text-center mb-6">
                    <?= htmlspecialchars($message ?: 'The login link is invalid, expired, or has already been used.') ?>
                </p>
                
                <div class="bg-gray-800/50 border border-gray-700 rounded-lg p-4 mb-8">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 mt-1">
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="text-sm text-gray-400">
                            <p class="font-medium text-yellow-400 mb-1">Common Issues:</p>
                            <ul class="space-y-1 text-xs">
                                <li>• Link expired (links expire after 15 minutes)</li>
                                <li>• Link already used (single-use only)</li>
                                <li>• Link copied incorrectly</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <a href="/auth/login" class="w-full bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 text-center block">
                        Request New Login Link
                    </a>
                    <a href="/" class="w-full text-center bg-gray-800 hover:bg-gray-700 text-gray-300 font-medium py-2.5 px-4 rounded-lg transition-colors duration-200 block">
                        Return to Homepage
                    </a>
                </div>
                
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-gray-600 text-xs">
                Powered by <span class="text-purple-400 font-semibold">horizn_</span> • 
                <a href="#" class="hover:text-purple-400 transition-colors">Privacy Policy</a>
            </p>
        </div>
    </div>
</div>