<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - horizn_</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    
    <!-- Preline UI -->
    <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Tailwind Config -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'crypto-black': '#0A0A0B',
                        'crypto-dark': '#111113',
                        'crypto-gray': '#1A1A1D',
                        'crypto-blue': '#3B82F6',
                        'crypto-purple': '#8B5CF6',
                        'crypto-border': '#2A2A2E'
                    },
                    fontFamily: {
                        'mono': ['JetBrains Mono', 'ui-monospace', 'SFMono-Regular', 'monospace'],
                        'sans': ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif']
                    },
                    borderRadius: {
                        'crypto': '2px',
                        'crypto-lg': '4px'
                    }
                }
            }
        }
    </script>
    
    <style>
        body {
            background: radial-gradient(ellipse at center, #111113 0%, #0A0A0B 70%, #000000 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        
        .login-glow {
            box-shadow: 0 0 40px rgba(59, 130, 246, 0.1);
        }
        
        .crypto-glow:hover, .crypto-glow:focus {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
            transition: all 0.3s ease;
        }
        
        .input-focus:focus {
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
    </style>
</head>
<body class="bg-crypto-black text-white">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Logo and Brand -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center space-x-3 mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-crypto-blue to-crypto-purple rounded-crypto-lg"></div>
                    <h1 class="text-3xl font-bold text-white font-mono">horizn_</h1>
                </div>
                <p class="text-gray-400">Analytics for the modern web</p>
            </div>
            
            <!-- Login Form -->
            <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-8 login-glow">
                <form action="/auth/login" method="POST" class="space-y-6">
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-2">
                            Email Address
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required
                            class="w-full px-4 py-3 bg-crypto-gray border border-crypto-border rounded-crypto text-white placeholder-gray-500 input-focus focus:outline-none transition-all"
                            placeholder="you@company.com"
                        >
                    </div>
                    
                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-300 mb-2">
                            Password
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full px-4 py-3 bg-crypto-gray border border-crypto-border rounded-crypto text-white placeholder-gray-500 input-focus focus:outline-none transition-all font-mono"
                            placeholder="••••••••••••"
                        >
                    </div>
                    
                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input 
                                id="remember" 
                                name="remember" 
                                type="checkbox" 
                                class="w-4 h-4 rounded border-crypto-border bg-crypto-gray text-crypto-blue focus:ring-crypto-blue focus:ring-2 focus:ring-offset-0"
                            >
                            <label for="remember" class="ml-2 text-sm text-gray-300">
                                Remember me
                            </label>
                        </div>
                        <a href="/auth/forgot-password" class="text-sm text-crypto-blue hover:text-blue-300 transition-colors">
                            Forgot password?
                        </a>
                    </div>
                    
                    <!-- Submit Button -->
                    <button 
                        type="submit"
                        class="w-full bg-crypto-blue hover:bg-blue-600 text-white font-medium py-3 px-4 rounded-crypto crypto-glow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-crypto-blue focus:ring-offset-2 focus:ring-offset-crypto-dark"
                    >
                        Sign In
                    </button>
                    
                    <!-- Divider -->
                    <div class="relative my-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-crypto-border"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-crypto-dark text-gray-400">or</span>
                        </div>
                    </div>
                    
                    <!-- Social Login -->
                    <button 
                        type="button"
                        class="w-full bg-crypto-gray hover:bg-gray-700 border border-crypto-border text-gray-300 font-medium py-3 px-4 rounded-crypto transition-all duration-200 flex items-center justify-center space-x-3"
                    >
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span>Continue with Google</span>
                    </button>
                </form>
                
                <!-- Sign Up Link -->
                <div class="text-center mt-6 pt-6 border-t border-crypto-border">
                    <p class="text-gray-400">
                        Don't have an account? 
                        <a href="/auth/register" class="text-crypto-blue hover:text-blue-300 transition-colors font-medium">
                            Sign up
                        </a>
                    </p>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="text-center mt-8">
                <p class="text-xs text-gray-500">
                    Protected by enterprise-grade security
                </p>
            </div>
        </div>
    </div>
    
    <!-- Preline UI JS -->
    <script src="https://preline.co/assets/js/main.min.js"></script>
    
    <!-- Form Enhancement -->
    <script>
        // Auto-focus first input
        document.getElementById('email').focus();
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return;
            }
            
            // Add loading state to submit button
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.textContent = 'Signing in...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>