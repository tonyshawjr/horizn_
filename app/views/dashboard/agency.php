<?php
/**
 * Agency Dashboard - Multi-tenant overview of all client sites
 */

$pageTitle = 'Agency Dashboard - horizn_';
$currentPage = 'dashboard';
$additionalScripts = '<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script><script src="/assets/js/dashboard.js"></script>';
?>

<!-- Agency Dashboard Header -->
<div class="container">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-primary font-mono">Agency Dashboard</h1>
            <p class="text-tertiary text-sm">Overview of all your client sites</p>
        </div>
        <div class="flex items-center space-x-4">
            <!-- Date Range Selector -->
            <div class="flex items-center space-x-2">
                <select id="dateRange" class="form-input">
                    <option value="1d">Today</option>
                    <option value="7d">Last 7 days</option>
                    <option value="30d" selected>Last 30 days</option>
                    <option value="90d">Last 90 days</option>
                </select>
            </div>
            <!-- Data Advantage Badge -->
            <div class="bg-gradient-to-r from-accent-blue to-accent-purple rounded-md px-3 py-2">
                <span class="text-xs font-bold font-mono text-white">20%+ MORE DATA THAN GA</span>
            </div>
        </div>
    </div>

    <!-- Agency Overview Stats -->
    <div class="grid grid-cols-4 mb-8">
        <div class="analytics-card">
            <div class="flex items-center justify-between">
                <div>
                    <span class="metric-label">Total Sites</span>
                    <span class="metric-number" id="totalSites">
                        <?= count($user_sites ?? []) ?>
                    </span>
                </div>
                <div class="w-12 h-12 bg-accent-blue/20 rounded-md flex items-center justify-center">
                    <svg class="w-6 h-6 text-accent-blue" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2h12v8H4V6z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="analytics-card">
            <div class="flex items-center justify-between">
                <div>
                    <span class="metric-label">Live Visitors</span>
                    <span class="metric-number text-accent-blue" id="totalLiveVisitors">
                        <?= $agency_stats['total_live_visitors'] ?? 0 ?>
                    </span>
                </div>
                <div class="w-12 h-12 bg-accent-blue/20 rounded-md flex items-center justify-center">
                    <div class="w-3 h-3 bg-accent-blue rounded-full animate-pulse"></div>
                </div>
            </div>
        </div>

        <div class="analytics-card">
            <div class="flex items-center justify-between">
                <div>
                    <span class="metric-label">Total Pageviews</span>
                    <span class="metric-number" id="totalPageviews">
                        <?= number_format($agency_stats['total_pageviews'] ?? 0) ?>
                    </span>
                </div>
                <div class="w-12 h-12 bg-accent-purple/20 rounded-md flex items-center justify-center">
                    <svg class="w-6 h-6 text-accent-purple" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="analytics-card">
            <div class="flex items-center justify-between">
                <div>
                    <span class="metric-label">Active Events</span>
                    <span class="metric-number" id="totalEvents">
                        <?= number_format($agency_stats['total_events'] ?? 0) ?>
                    </span>
                </div>
                <div class="w-12 h-12 bg-accent-green/20 rounded-md flex items-center justify-center">
                    <svg class="w-6 h-6 text-accent-green" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Sites Grid -->
    <div class="grid grid-cols-3 gap-6">
        <?php if (!empty($user_sites)): ?>
            <?php foreach ($user_sites as $site): ?>
                <div class="card cursor-pointer" onclick="window.location.href='/dashboard/site?site=<?= $site['id'] ?>'">
                    <!-- Site Header -->
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-primary"><?= htmlspecialchars($site['name']) ?></h3>
                            <p class="text-sm text-tertiary truncate"><?= htmlspecialchars($site['domain']) ?></p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <!-- Live indicator -->
                            <?php if (($site['live_visitors'] ?? 0) > 0): ?>
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-accent-blue rounded-full animate-pulse mr-1"></div>
                                    <span class="text-xs font-mono text-accent-blue"><?= $site['live_visitors'] ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Site status -->
                            <div class="w-2 h-2 <?= $site['is_active'] ? 'bg-green-500' : 'bg-gray-500' ?> rounded-full"></div>
                        </div>
                    </div>

                    <!-- Traffic Sparkline -->
                    <div class="mb-4">
                        <div id="sparkline-<?= $site['id'] ?>" class="h-16"></div>
                    </div>

                    <!-- Site Stats -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-tertiary">Pageviews</p>
                            <p class="font-mono font-bold text-primary data-value">
                                <?= number_format($site['stats']['pageviews'] ?? 0) ?>
                            </p>
                            <?php if (isset($site['stats']['pageviews_change'])): ?>
                                <span class="metric-change <?= $site['stats']['pageviews_change'] >= 0 ? 'positive' : 'negative' ?>">
                                    <?= $site['stats']['pageviews_change'] >= 0 ? '+' : '' ?><?= round($site['stats']['pageviews_change'], 1) ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="text-xs text-tertiary">Visitors</p>
                            <p class="font-mono font-bold text-primary data-value">
                                <?= number_format($site['stats']['visitors'] ?? 0) ?>
                            </p>
                            <?php if (isset($site['stats']['visitors_change'])): ?>
                                <span class="metric-change <?= $site['stats']['visitors_change'] >= 0 ? 'positive' : 'negative' ?>">
                                    <?= $site['stats']['visitors_change'] >= 0 ? '+' : '' ?><?= round($site['stats']['visitors_change'], 1) ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-secondary">
                        <div class="text-xs text-muted">
                            Last active: <?= date('M j, g:ia', strtotime($site['last_activity'] ?? 'now')) ?>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button class="text-xs text-accent hover:text-primary" 
                                    onclick="event.stopPropagation(); window.open('/dashboard/site?site=<?= $site['id'] ?>', '_blank')">
                                View
                            </button>
                            <button class="text-xs text-muted hover:text-primary" 
                                    onclick="event.stopPropagation(); window.open('<?= $site['domain'] ?>', '_blank')">
                                Visit
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Empty State -->
            <div class="col-span-full card p-12 text-center">
                <div class="w-16 h-16 bg-accent-blue/20 rounded-md mx-auto mb-4 flex items-center justify-center">
                    <svg class="w-8 h-8 text-accent-blue" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-primary mb-2">No Sites Yet</h3>
                <p class="text-tertiary mb-6">Add your first site to start tracking analytics</p>
                <a href="/sites/add" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path>
                    </svg>
                    Add First Site
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Initialize sparklines for each site
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($user_sites)): ?>
        <?php foreach ($user_sites as $site): ?>
            // Sparkline for site <?= $site['id'] ?>
            
            const sparklineOptions<?= $site['id'] ?> = {
                series: [{
                    data: <?= json_encode($site['sparkline_data'] ?? [10, 15, 8, 22, 18, 25, 30, 28, 35, 42, 38, 45, 50, 48, 55]) ?>
                }],
                chart: {
                    type: 'line',
                    width: '100%',
                    height: 64,
                    sparkline: {
                        enabled: true
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 2,
                    colors: ['#3B82F6']
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.3,
                        opacityTo: 0.1,
                        stops: [0, 100],
                        colorStops: [{
                            offset: 0,
                            color: '#3B82F6',
                            opacity: 0.3
                        }, {
                            offset: 100,
                            color: '#3B82F6',
                            opacity: 0.1
                        }]
                    }
                },
                markers: {
                    size: 0
                },
                tooltip: {
                    enabled: false
                }
            };
            
            const sparkline<?= $site['id'] ?> = new ApexCharts(
                document.querySelector("#sparkline-<?= $site['id'] ?>"), 
                sparklineOptions<?= $site['id'] ?>
            );
            sparkline<?= $site['id'] ?>.render();
        <?php endforeach; ?>
    <?php endif; ?>
    
    // Auto-refresh live data every 30 seconds
    setInterval(function() {
        updateLiveStats();
    }, 30000);
});

function updateLiveStats() {
    fetch('/api/live/agency-stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalLiveVisitors').textContent = data.total_live_visitors;
                // Update individual site live visitor counts
                data.sites.forEach(site => {
                    const siteCard = document.querySelector(`[onclick*="site=${site.id}"]`);
                    if (siteCard) {
                        const liveIndicator = siteCard.querySelector('.animate-pulse');
                        if (liveIndicator && site.live_visitors > 0) {
                            liveIndicator.nextElementSibling.textContent = site.live_visitors;
                        }
                    }
                });
            }
        })
        .catch(error => console.log('Failed to update live stats:', error));
}

// Date range change handler
document.getElementById('dateRange').addEventListener('change', function() {
    const period = this.value;
    window.location.href = `/dashboard/agency?period=${period}`;
});
</script>