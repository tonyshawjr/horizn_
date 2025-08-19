<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'horizn_' ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    
    <!-- Preline UI -->
    <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
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
        /* Custom dark theme styles */
        body {
            background: linear-gradient(135deg, #0A0A0B 0%, #111113 100%);
            font-family: 'Inter', sans-serif;
        }
        
        .crypto-glow:hover {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
            transition: all 0.3s ease;
        }
        
        .crypto-glow-purple:hover {
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.3);
            transition: all 0.3s ease;
        }
        
        .mono-data {
            font-family: 'JetBrains Mono', monospace;
            font-feature-settings: 'tnum' 1;
        }
        
        .sidebar-gradient {
            background: linear-gradient(180deg, #111113 0%, #0A0A0B 100%);
            border-right: 1px solid #2A2A2E;
        }
        
        /* Scrollbar customization */
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #0A0A0B;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #2A2A2E;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #3A3A3E;
        }
    </style>
</head>
<body class="bg-crypto-black text-white">
    <!-- Theme Toggle (Fixed Position) -->
    <div class="fixed top-4 right-4 z-50">
        <button id="theme-toggle" class="p-2 rounded-crypto bg-crypto-dark border border-crypto-border crypto-glow">
            <svg class="w-5 h-5 text-crypto-blue" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
            </svg>
        </button>
    </div>

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 sidebar-gradient flex flex-col">
            <!-- Logo -->
            <div class="p-6 border-b border-crypto-border">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-crypto-blue to-crypto-purple rounded-crypto"></div>
                    <h1 class="text-xl font-bold text-white mono-data">horizn_</h1>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-2">
                <a href="/dashboard" class="<?= $currentPage === 'dashboard' ? 'bg-crypto-gray border-crypto-blue text-crypto-blue' : 'text-gray-300 hover:bg-crypto-gray hover:text-white' ?> flex items-center px-4 py-3 rounded-crypto border border-transparent transition-all crypto-glow">
                    <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                    </svg>
                    <span>Dashboard</span>
                    <?php if ($currentPage === 'dashboard' && isset($liveVisitors)): ?>
                        <span class="ml-auto bg-crypto-blue text-crypto-black px-2 py-1 rounded-crypto text-xs mono-data font-bold">
                            <?= $liveVisitors ?>
                        </span>
                    <?php endif; ?>
                </a>
                
                <a href="/sites" class="<?= $currentPage === 'sites' ? 'bg-crypto-gray border-crypto-blue text-crypto-blue' : 'text-gray-300 hover:bg-crypto-gray hover:text-white' ?> flex items-center px-4 py-3 rounded-crypto border border-transparent transition-all crypto-glow">
                    <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2h12v8H4V6z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Sites</span>
                </a>
                
                <a href="/journeys" class="<?= $currentPage === 'journeys' ? 'bg-crypto-gray border-crypto-blue text-crypto-blue' : 'text-gray-300 hover:bg-crypto-gray hover:text-white' ?> flex items-center px-4 py-3 rounded-crypto border border-transparent transition-all crypto-glow">
                    <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Journeys</span>
                </a>
                
                <a href="/funnels" class="<?= $currentPage === 'funnels' ? 'bg-crypto-gray border-crypto-blue text-crypto-blue' : 'text-gray-300 hover:bg-crypto-gray hover:text-white' ?> flex items-center px-4 py-3 rounded-crypto border border-transparent transition-all crypto-glow">
                    <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V4z"></path>
                    </svg>
                    <span>Funnels</span>
                </a>
                
                <a href="/dashboard/custom" class="<?= in_array($currentPage, ['custom-dashboards', 'dashboard-builder', 'custom-dashboard-view']) ? 'bg-crypto-gray border-crypto-blue text-crypto-blue' : 'text-gray-300 hover:bg-crypto-gray hover:text-white' ?> flex items-center px-4 py-3 rounded-crypto border border-transparent transition-all crypto-glow">
                    <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z"></path>
                    </svg>
                    <span>Custom Dashboards</span>
                </a>
                
                <div class="pt-4 mt-4 border-t border-crypto-border">
                    <a href="/settings" class="<?= $currentPage === 'settings' ? 'bg-crypto-gray border-crypto-blue text-crypto-blue' : 'text-gray-300 hover:bg-crypto-gray hover:text-white' ?> flex items-center px-4 py-3 rounded-crypto border border-transparent transition-all crypto-glow">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Settings</span>
                    </a>
                </div>
            </nav>
            
            <!-- User Info -->
            <div class="p-4 border-t border-crypto-border">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-crypto-purple to-crypto-blue rounded-crypto-lg flex items-center justify-center">
                        <span class="text-sm font-bold mono-data">T</span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-white">Tony Shaw</p>
                        <p class="text-xs text-gray-400">Premium Plan</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto bg-crypto-black">
            <?= $content ?? '' ?>
        </main>
    </div>
    
    <!-- Preline UI JS -->
    <script src="https://preline.co/assets/js/main.min.js"></script>
    
    <!-- Theme Toggle Script -->
    <script>
        document.getElementById('theme-toggle').addEventListener('click', function() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            
            if (isDark) {
                html.classList.remove('dark');
                html.classList.add('light');
            } else {
                html.classList.remove('light');
                html.classList.add('dark');
            }
            
            // Store preference
            localStorage.setItem('theme', isDark ? 'light' : 'dark');
        });
        
        // Initialize theme from localStorage
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.classList.add(savedTheme);
    </script>
    
    <?= $additionalScripts ?? '' ?>
</body>
</html>