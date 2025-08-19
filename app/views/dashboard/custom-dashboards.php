<?php $currentPage = 'custom-dashboards'; ?>

<!-- Header -->
<div class="p-6 border-b border-crypto-border">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white mono-data">Custom Dashboards</h1>
            <p class="text-gray-400 mt-1">Create and manage your personalized analytics dashboards</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="/dashboard/custom/builder" class="bg-crypto-blue hover:bg-blue-600 text-white px-4 py-2 rounded-crypto transition-colors crypto-glow">
                <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                </svg>
                Create Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Dashboard Grid -->
<div class="p-6">
    <!-- My Dashboards -->
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-white">My Dashboards</h2>
            <span class="text-sm text-gray-400"><?= count($dashboards) ?> dashboard<?= count($dashboards) !== 1 ? 's' : '' ?></span>
        </div>
        
        <?php if (empty($dashboards)): ?>
            <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-8 text-center">
                <div class="w-16 h-16 bg-crypto-gray rounded-crypto-lg flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V4z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-white mb-2">No custom dashboards yet</h3>
                <p class="text-gray-400 mb-4">Create your first custom dashboard to organize your analytics data exactly how you want it.</p>
                <a href="/dashboard/custom/builder" class="bg-crypto-blue hover:bg-blue-600 text-white px-6 py-2 rounded-crypto transition-colors crypto-glow inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                    </svg>
                    Create Your First Dashboard
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($dashboards as $dashboard): ?>
                    <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg hover:border-crypto-blue transition-colors crypto-glow group">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-medium text-white group-hover:text-crypto-blue transition-colors">
                                        <?= htmlspecialchars($dashboard['name']) ?>
                                    </h3>
                                    <?php if ($dashboard['description']): ?>
                                        <p class="text-gray-400 text-sm mt-1"><?= htmlspecialchars($dashboard['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center space-x-2 ml-4">
                                    <?php if ($dashboard['is_shared']): ?>
                                        <span class="bg-crypto-purple text-white text-xs px-2 py-1 rounded-crypto">Shared</span>
                                    <?php endif; ?>
                                    <div class="relative dashboard-menu">
                                        <button class="p-1 text-gray-400 hover:text-white transition-colors dashboard-menu-trigger">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                            </svg>
                                        </button>
                                        <div class="dashboard-menu-dropdown absolute right-0 top-6 bg-crypto-gray border border-crypto-border rounded-crypto shadow-lg py-1 w-32 z-10 hidden">
                                            <a href="/dashboard/custom/view?id=<?= $dashboard['id'] ?>" class="block px-3 py-2 text-sm text-gray-300 hover:bg-crypto-dark hover:text-white">View</a>
                                            <a href="/dashboard/custom/builder?id=<?= $dashboard['id'] ?>" class="block px-3 py-2 text-sm text-gray-300 hover:bg-crypto-dark hover:text-white">Edit</a>
                                            <button onclick="shareDashboard(<?= $dashboard['id'] ?>)" class="block w-full text-left px-3 py-2 text-sm text-gray-300 hover:bg-crypto-dark hover:text-white">Share</button>
                                            <button onclick="deleteDashboard(<?= $dashboard['id'] ?>)" class="block w-full text-left px-3 py-2 text-sm text-red-400 hover:bg-crypto-dark hover:text-red-300">Delete</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Dashboard Stats -->
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="text-center">
                                    <div class="text-lg font-bold text-white mono-data"><?= $dashboard['usage_stats']['total_views'] ?? 0 ?></div>
                                    <div class="text-xs text-gray-400">Total Views</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-white mono-data"><?= count(json_decode($dashboard['widgets'], true) ?? []) ?></div>
                                    <div class="text-xs text-gray-400">Widgets</div>
                                </div>
                            </div>
                            
                            <!-- Last Viewed -->
                            <div class="text-xs text-gray-400 mb-4">
                                <?php if ($dashboard['last_viewed']): ?>
                                    Last viewed <?= date('M j, Y', strtotime($dashboard['last_viewed'])) ?>
                                <?php else: ?>
                                    Never viewed
                                <?php endif; ?>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex space-x-2">
                                <a href="/dashboard/custom/view?id=<?= $dashboard['id'] ?>" class="flex-1 bg-crypto-blue hover:bg-blue-600 text-white text-center py-2 rounded-crypto transition-colors text-sm">
                                    View Dashboard
                                </a>
                                <a href="/dashboard/custom/builder?id=<?= $dashboard['id'] ?>" class="bg-crypto-gray hover:bg-gray-600 text-white px-3 py-2 rounded-crypto transition-colors">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Shared Dashboards -->
    <?php if (!empty($sharedDashboards)): ?>
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-white">Shared with Me</h2>
                <span class="text-sm text-gray-400"><?= count($sharedDashboards) ?> dashboard<?= count($sharedDashboards) !== 1 ? 's' : '' ?></span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($sharedDashboards as $dashboard): ?>
                    <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg hover:border-crypto-purple transition-colors crypto-glow-purple group">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-medium text-white group-hover:text-crypto-purple transition-colors">
                                        <?= htmlspecialchars($dashboard['name']) ?>
                                    </h3>
                                    <?php if ($dashboard['description']): ?>
                                        <p class="text-gray-400 text-sm mt-1"><?= htmlspecialchars($dashboard['description']) ?></p>
                                    <?php endif; ?>
                                    <p class="text-crypto-purple text-xs mt-2">
                                        by <?= htmlspecialchars($dashboard['owner_name']) ?>
                                    </p>
                                </div>
                                <span class="bg-crypto-purple text-white text-xs px-2 py-1 rounded-crypto">
                                    <?= ucfirst($dashboard['permissions']) ?>
                                </span>
                            </div>
                            
                            <!-- Dashboard Stats -->
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="text-center">
                                    <div class="text-lg font-bold text-white mono-data"><?= $dashboard['total_views'] ?? 0 ?></div>
                                    <div class="text-xs text-gray-400">Total Views</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-white mono-data"><?= count(json_decode($dashboard['widgets'], true) ?? []) ?></div>
                                    <div class="text-xs text-gray-400">Widgets</div>
                                </div>
                            </div>
                            
                            <!-- Shared Date -->
                            <div class="text-xs text-gray-400 mb-4">
                                Shared <?= date('M j, Y', strtotime($dashboard['shared_at'])) ?>
                            </div>
                            
                            <!-- Action Button -->
                            <a href="/dashboard/custom/view?id=<?= $dashboard['id'] ?>" class="block bg-crypto-purple hover:bg-purple-600 text-white text-center py-2 rounded-crypto transition-colors text-sm">
                                View Dashboard
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Share Dashboard Modal -->
<div id="shareDashboardModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-white">Share Dashboard</h3>
            <button onclick="closeShareModal()" class="text-gray-400 hover:text-white">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        
        <form onsubmit="confirmShareDashboard(event)">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-2">Share with</label>
                <select class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white">
                    <option value="organization">Everyone in organization</option>
                    <option value="specific" disabled>Specific users (coming soon)</option>
                </select>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-300 mb-2">Permissions</label>
                <select class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white">
                    <option value="view">View only</option>
                    <option value="edit" disabled>Edit (coming soon)</option>
                </select>
            </div>
            
            <div class="flex space-x-3">
                <button type="button" onclick="closeShareModal()" class="flex-1 bg-crypto-gray hover:bg-gray-600 text-white py-2 rounded-crypto transition-colors">
                    Cancel
                </button>
                <button type="submit" class="flex-1 bg-crypto-blue hover:bg-blue-600 text-white py-2 rounded-crypto transition-colors">
                    Share Dashboard
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteDashboardModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-white">Delete Dashboard</h3>
            <button onclick="closeDeleteModal()" class="text-gray-400 hover:text-white">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        
        <p class="text-gray-300 mb-6">Are you sure you want to delete this dashboard? This action cannot be undone.</p>
        
        <div class="flex space-x-3">
            <button onclick="closeDeleteModal()" class="flex-1 bg-crypto-gray hover:bg-gray-600 text-white py-2 rounded-crypto transition-colors">
                Cancel
            </button>
            <button onclick="confirmDeleteDashboard()" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 rounded-crypto transition-colors">
                Delete Dashboard
            </button>
        </div>
    </div>
</div>

<script>
let currentDashboardId = null;

// Dashboard menu functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle dashboard menu toggles
    document.querySelectorAll('.dashboard-menu-trigger').forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = this.parentNode;
            const dropdown = menu.querySelector('.dashboard-menu-dropdown');
            
            // Close all other menus
            document.querySelectorAll('.dashboard-menu-dropdown').forEach(d => {
                if (d !== dropdown) d.classList.add('hidden');
            });
            
            // Toggle current menu
            dropdown.classList.toggle('hidden');
        });
    });
    
    // Close menus when clicking outside
    document.addEventListener('click', function() {
        document.querySelectorAll('.dashboard-menu-dropdown').forEach(dropdown => {
            dropdown.classList.add('hidden');
        });
    });
});

function shareDashboard(dashboardId) {
    currentDashboardId = dashboardId;
    document.getElementById('shareDashboardModal').classList.remove('hidden');
    document.getElementById('shareDashboardModal').classList.add('flex');
}

function closeShareModal() {
    document.getElementById('shareDashboardModal').classList.add('hidden');
    document.getElementById('shareDashboardModal').classList.remove('flex');
    currentDashboardId = null;
}

function confirmShareDashboard(event) {
    event.preventDefault();
    
    if (!currentDashboardId) return;
    
    fetch('/dashboard/custom/share', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            dashboard_id: currentDashboardId,
            share_with: 'organization',
            permissions: 'view'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeShareModal();
            location.reload(); // Refresh to show updated share status
        } else {
            alert('Error sharing dashboard: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error sharing dashboard');
    });
}

function deleteDashboard(dashboardId) {
    currentDashboardId = dashboardId;
    document.getElementById('deleteDashboardModal').classList.remove('hidden');
    document.getElementById('deleteDashboardModal').classList.add('flex');
}

function closeDeleteModal() {
    document.getElementById('deleteDashboardModal').classList.add('hidden');
    document.getElementById('deleteDashboardModal').classList.remove('flex');
    currentDashboardId = null;
}

function confirmDeleteDashboard() {
    if (!currentDashboardId) return;
    
    fetch(`/dashboard/custom/delete?id=${currentDashboardId}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeDeleteModal();
            location.reload(); // Refresh to remove deleted dashboard
        } else {
            alert('Error deleting dashboard: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting dashboard');
    });
}
</script>