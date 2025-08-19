<?php
/**
 * User Journey Tracking View
 */

$pageTitle = 'User Journeys - horizn_';
$currentPage = 'journeys';
$additionalScripts = '<script src="/assets/js/dashboard.js"></script><script src="/assets/js/live-updates.js"></script>';
?>

<!-- Journeys Header -->
<div class="p-6 border-b border-crypto-border">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white mono-data">User Journeys</h1>
            <p class="text-gray-400 text-sm">Track individual visitor paths through your site</p>
        </div>
        <div class="flex items-center space-x-4">
            <!-- Site Selector -->
            <select id="siteSelector" class="bg-crypto-dark border border-crypto-border rounded-crypto px-3 py-2 text-white text-sm">
                <?php if (!empty($user_sites)): ?>
                    <?php foreach ($user_sites as $site): ?>
                        <option value="<?= $site['id'] ?>" <?= $site['id'] == ($current_site_id ?? 0) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($site['name']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            
            <!-- Date Range Selector -->
            <select id="dateRange" class="bg-crypto-dark border border-crypto-border rounded-crypto px-3 py-2 text-white text-sm">
                <option value="1d">Today</option>
                <option value="7d" selected>Last 7 days</option>
                <option value="30d">Last 30 days</option>
            </select>
            
            <!-- Filter Toggle -->
            <button id="filterToggle" class="px-4 py-2 bg-crypto-dark border border-crypto-border rounded-crypto text-white hover:bg-crypto-gray transition-all">
                <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"></path>
                </svg>
                Filters
            </button>
        </div>
    </div>
</div>

<div class="p-6">
    <!-- Journey Filters (Hidden by default) -->
    <div id="journeyFilters" class="hidden bg-crypto-dark border border-crypto-border rounded-crypto-lg p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm text-gray-400 mb-2">Journey Length</label>
                <select class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white text-sm">
                    <option value="">All journeys</option>
                    <option value="1">Single page</option>
                    <option value="2-5">2-5 pages</option>
                    <option value="6+">6+ pages</option>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-2">Entry Page</label>
                <select class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white text-sm">
                    <option value="">All pages</option>
                    <option value="/">Homepage</option>
                    <option value="/products">Products</option>
                    <option value="/about">About</option>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-2">Device Type</label>
                <select class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white text-sm">
                    <option value="">All devices</option>
                    <option value="desktop">Desktop</option>
                    <option value="mobile">Mobile</option>
                    <option value="tablet">Tablet</option>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-2">Source</label>
                <select class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white text-sm">
                    <option value="">All sources</option>
                    <option value="direct">Direct</option>
                    <option value="organic">Organic Search</option>
                    <option value="social">Social Media</option>
                    <option value="referral">Referral</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Journey Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-6 crypto-glow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Active Journeys</p>
                    <p class="text-2xl font-bold mono-data text-crypto-blue" id="activeJourneys">
                        <?= $journey_stats['active_journeys'] ?? 0 ?>
                    </p>
                </div>
                <div class="w-12 h-12 bg-crypto-blue/20 rounded-crypto flex items-center justify-center">
                    <div class="w-3 h-3 bg-crypto-blue rounded-full animate-pulse"></div>
                </div>
            </div>
        </div>

        <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-6 crypto-glow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Avg. Journey Length</p>
                    <p class="text-2xl font-bold mono-data text-white">
                        <?= round($journey_stats['avg_pages_per_journey'] ?? 0, 1) ?>
                    </p>
                    <p class="text-sm text-gray-400">pages</p>
                </div>
                <div class="w-12 h-12 bg-crypto-purple/20 rounded-crypto flex items-center justify-center">
                    <svg class="w-6 h-6 text-crypto-purple" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-6 crypto-glow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Avg. Journey Time</p>
                    <p class="text-2xl font-bold mono-data text-white">
                        <?= gmdate("i:s", $journey_stats['avg_journey_duration'] ?? 0) ?>
                    </p>
                    <p class="text-sm text-gray-400">min:sec</p>
                </div>
                <div class="w-12 h-12 bg-green-500/20 rounded-crypto flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-6 crypto-glow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Conversion Rate</p>
                    <p class="text-2xl font-bold mono-data text-white">
                        <?= round($journey_stats['conversion_rate'] ?? 0, 1) ?>%
                    </p>
                    <p class="text-sm text-gray-400">journeys with events</p>
                </div>
                <div class="w-12 h-12 bg-yellow-500/20 rounded-crypto flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Journey Timeline -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg">
            <div class="p-6 border-b border-crypto-border">
                <h3 class="text-lg font-bold text-white">Popular Paths</h3>
                <p class="text-sm text-gray-400">Most common user journey sequences</p>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <?php if (!empty($popular_paths)): ?>
                        <?php foreach ($popular_paths as $path): ?>
                            <div class="border border-crypto-border rounded-crypto p-4 hover:bg-crypto-gray/50 transition-all">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="mono-data text-sm text-crypto-blue"><?= $path['count'] ?> users</span>
                                    <span class="text-xs text-gray-400"><?= round($path['percentage'], 1) ?>%</span>
                                </div>
                                <div class="flex items-center space-x-2 overflow-x-auto">
                                    <?php foreach ($path['pages'] as $index => $page): ?>
                                        <div class="flex items-center">
                                            <div class="bg-crypto-gray rounded-crypto px-3 py-1 text-xs text-white whitespace-nowrap">
                                                <?= htmlspecialchars($page['title'] ?: $page['url']) ?>
                                            </div>
                                            <?php if ($index < count($path['pages']) - 1): ?>
                                                <svg class="w-4 h-4 text-gray-400 mx-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <p class="text-gray-400">No journey data available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Live Journey Activity -->
        <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg">
            <div class="p-6 border-b border-crypto-border">
                <h3 class="text-lg font-bold text-white flex items-center">
                    Live Journeys
                    <div class="w-2 h-2 bg-crypto-blue rounded-full animate-pulse ml-2"></div>
                </h3>
                <p class="text-sm text-gray-400">Real-time visitor activity</p>
            </div>
            <div class="p-6">
                <div id="liveJourneys" class="space-y-3 max-h-96 overflow-y-auto">
                    <?php if (!empty($live_journeys)): ?>
                        <?php foreach ($live_journeys as $journey): ?>
                            <div class="border border-crypto-border rounded-crypto p-3 hover:bg-crypto-gray/50 transition-all">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-2 h-2 bg-crypto-blue rounded-full"></div>
                                        <span class="text-sm text-white"><?= htmlspecialchars($journey['location'] ?? 'Unknown') ?></span>
                                        <span class="text-xs text-gray-400"><?= $journey['device_type'] ?></span>
                                    </div>
                                    <span class="text-xs text-gray-400">
                                        <?= $journey['pages_visited'] ?> pages
                                    </span>
                                </div>
                                <div class="text-xs text-gray-400">
                                    Currently on: <?= htmlspecialchars($journey['current_page']) ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    Started <?= date('g:i A', strtotime($journey['started_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <p class="text-gray-400">No active journeys</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Individual Journey Details -->
    <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg">
        <div class="p-6 border-b border-crypto-border">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-white">Recent Journeys</h3>
                    <p class="text-sm text-gray-400">Detailed visitor paths from the last 24 hours</p>
                </div>
                <a href="/dashboard/journeys/all?site=<?= $current_site_id ?? 0 ?>" class="text-sm text-crypto-blue hover:text-crypto-blue/80">
                    View All Journeys
                </a>
            </div>
        </div>
        <div class="divide-y divide-crypto-border">
            <?php if (!empty($recent_journeys)): ?>
                <?php foreach ($recent_journeys as $journey): ?>
                    <div class="border-b border-crypto-border last:border-b-0">
                        <?php include APP_PATH . '/views/components/journey-timeline.php'; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-6 text-center">
                    <p class="text-gray-400">No journey data available</p>
                    <p class="text-sm text-gray-500 mt-2">Journeys will appear here as visitors navigate your site</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Toggle filters
document.getElementById('filterToggle').addEventListener('click', function() {
    const filters = document.getElementById('journeyFilters');
    filters.classList.toggle('hidden');
});

// Site selector change
document.getElementById('siteSelector').addEventListener('change', function() {
    const siteId = this.value;
    const period = document.getElementById('dateRange').value;
    window.location.href = `/dashboard/journeys?site=${siteId}&period=${period}`;
});

// Date range change
document.getElementById('dateRange').addEventListener('change', function() {
    const period = this.value;
    const siteId = document.getElementById('siteSelector').value;
    window.location.href = `/dashboard/journeys?site=${siteId}&period=${period}`;
});

// Auto-refresh live journeys every 15 seconds
setInterval(function() {
    updateLiveJourneys();
}, 15000);

function updateLiveJourneys() {
    const siteId = document.getElementById('siteSelector').value;
    
    fetch(`/api/live/journeys?site=${siteId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.live_journeys) {
                updateLiveJourneysList(data.live_journeys);
                document.getElementById('activeJourneys').textContent = data.stats.active_journeys;
            }
        })
        .catch(error => console.log('Failed to update live journeys:', error));
}

function updateLiveJourneysList(journeys) {
    const container = document.getElementById('liveJourneys');
    
    if (journeys.length === 0) {
        container.innerHTML = '<div class="text-center py-8"><p class="text-gray-400">No active journeys</p></div>';
        return;
    }
    
    const html = journeys.map(journey => `
        <div class="border border-crypto-border rounded-crypto p-3 hover:bg-crypto-gray/50 transition-all">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-crypto-blue rounded-full"></div>
                    <span class="text-sm text-white">${journey.location || 'Unknown'}</span>
                    <span class="text-xs text-gray-400">${journey.device_type}</span>
                </div>
                <span class="text-xs text-gray-400">
                    ${journey.pages_visited} pages
                </span>
            </div>
            <div class="text-xs text-gray-400">
                Currently on: ${journey.current_page}
            </div>
            <div class="text-xs text-gray-500">
                Started ${new Date(journey.started_at).toLocaleTimeString()}
            </div>
        </div>
    `).join('');
    
    container.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function() {
    // Start live updates if we have a site selected
    const siteId = document.getElementById('siteSelector').value;
    if (siteId) {
        updateLiveJourneys();
    }
});
</script>