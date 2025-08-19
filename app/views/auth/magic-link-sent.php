<?php
// Ensure user is not already authenticated
if (Auth::isAuthenticated()) {
    header('Location: /dashboard');
    exit;
}

$email = $_SESSION['magic_link_email'] ?? 'your email';
unset($_SESSION['magic_link_email']); // Clear from session after use
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
            <!-- Success Icon -->
            <div class="flex items-center justify-center mb-6">
                <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-emerald-600 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>

            <!-- Title -->
            <h2 class="text-2xl font-bold text-center text-white mb-4">
                Check Your Email
            </h2>

            <!-- Message -->
            <div class="text-center space-y-4">
                <p class="text-gray-300 text-base leading-relaxed">
                    We've sent a secure login link to<br>
                    <span class="text-purple-400 font-semibold"><?= htmlspecialchars($email) ?></span>
                </p>

                <div class="bg-gray-800/50 border border-gray-700 rounded-lg p-4">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 mt-1">
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="text-sm text-gray-400">
                            <p class="font-medium text-yellow-400 mb-1">Security Notice</p>
                            <ul class="space-y-1 text-xs">
                                <li>• Link expires in 15 minutes</li>
                                <li>• Can only be used once</li>
                                <li>• Check your spam folder</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 space-y-4">
                <!-- Primary CTA -->
                <button 
                    id="openEmailClient" 
                    class="w-full bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 focus:ring-offset-gray-900"
                >
                    Open Email App
                </button>

                <!-- Secondary Actions -->
                <div class="flex space-x-3">
                    <a 
                        href="/auth/login" 
                        class="flex-1 text-center bg-gray-800 hover:bg-gray-700 text-gray-300 font-medium py-2.5 px-4 rounded-lg transition-colors duration-200"
                    >
                        Back to Login
                    </a>
                    <button 
                        id="resendLink"
                        class="flex-1 text-center bg-gray-800 hover:bg-gray-700 text-gray-300 font-medium py-2.5 px-4 rounded-lg transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Resend Link
                    </button>
                </div>
            </div>

            <!-- Help Text -->
            <div class="mt-6 pt-6 border-t border-gray-800">
                <p class="text-xs text-gray-500 text-center">
                    Having trouble? The link might take a few minutes to arrive.<br>
                    If you don't receive it, check your spam folder or try again.
                </p>
            </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const openEmailBtn = document.getElementById('openEmailClient');
    const resendBtn = document.getElementById('resendLink');
    
    // Open default email client
    openEmailBtn.addEventListener('click', function() {
        // Try to open default email client
        window.location.href = 'mailto:';
        
        // Also try common webmail services
        const email = '<?= htmlspecialchars($email) ?>';
        const domain = email.split('@')[1];
        
        let webmailUrl = 'mailto:';
        if (domain.includes('gmail.com')) {
            webmailUrl = 'https://mail.google.com';
        } else if (domain.includes('outlook.com') || domain.includes('hotmail.com') || domain.includes('live.com')) {
            webmailUrl = 'https://outlook.live.com';
        } else if (domain.includes('yahoo.com')) {
            webmailUrl = 'https://mail.yahoo.com';
        } else if (domain.includes('icloud.com')) {
            webmailUrl = 'https://www.icloud.com/mail';
        }
        
        // Open webmail in new tab after a short delay
        setTimeout(() => {
            if (webmailUrl !== 'mailto:') {
                window.open(webmailUrl, '_blank');
            }
        }, 500);
    });
    
    // Resend link functionality
    let resendCooldown = 60; // 60 seconds
    let resendTimer;
    
    function startResendCooldown() {
        resendBtn.disabled = true;
        resendBtn.textContent = `Resend (${resendCooldown}s)`;
        
        resendTimer = setInterval(() => {
            resendCooldown--;
            resendBtn.textContent = `Resend (${resendCooldown}s)`;
            
            if (resendCooldown <= 0) {
                clearInterval(resendTimer);
                resendBtn.disabled = false;
                resendBtn.textContent = 'Resend Link';
                resendCooldown = 60;
            }
        }, 1000);
    }
    
    resendBtn.addEventListener('click', function() {
        if (this.disabled) return;
        
        fetch('/auth/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'email=<?= urlencode($email) ?>&login_type=magic'
        })
        .then(response => response.text())
        .then(() => {
            startResendCooldown();
            
            // Show success message
            const successMsg = document.createElement('div');
            successMsg.className = 'fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
            successMsg.textContent = 'New link sent!';
            document.body.appendChild(successMsg);
            
            setTimeout(() => {
                successMsg.remove();
            }, 3000);
        })
        .catch(error => {
            console.error('Error resending link:', error);
        });
    });
    
    // Auto-refresh page after 15 minutes (when link expires)
    setTimeout(() => {
        window.location.href = '/auth/login?expired=1';
    }, 15 * 60 * 1000);
});
</script>