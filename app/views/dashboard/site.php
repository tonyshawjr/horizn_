<?php
/**
 * Individual Site Analytics Dashboard
 */

$pageTitle = 'Site Analytics - horizn_';
$currentPage = 'dashboard';
$additionalScripts = '<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script><script src="/assets/js/dashboard.js"></script>';
?>

<!-- Site Dashboard Header -->
<div class="container">
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center space-x-4">
            <!-- Back Button -->
            <a href="/dashboard/agency" class="p-2 text-tertiary hover:text-primary transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.707 14.707a1 1 0 01-1.414 0L2.586 11H17a1 1 0 110 2H2.586l3.707 3.707a1 1 0 01-1.414 1.414l-5.5-5.5a1 1 0 010-1.414l5.5-5.5a1 1 0 011.414 1.414L2.586 9H17a1 1 0 110 2H7.707z" clip-rule="evenodd"></path>
                </svg>
            </a>
            
            <div>
                <h1 class="text-2xl font-bold text-primary font-mono"><?= htmlspecialchars($site['name'] ?? 'Site Analytics') ?></h1>
                <p class="text-tertiary text-sm"><?= htmlspecialchars($site['domain'] ?? '') ?></p>
            </div>
        </div>
        
        <div class="flex items-center space-x-4">
            <!-- Date Range Selector -->
            <div class="flex items-center space-x-2">
                <select id="dateRange" class="form-input">
                    <option value="1d" <?= ($date_range['period'] ?? '30d') === '1d' ? 'selected' : '' ?>>Today</option>
                    <option value="7d" <?= ($date_range['period'] ?? '30d') === '7d' ? 'selected' : '' ?>>Last 7 days</option>
                    <option value="30d" <?= ($date_range['period'] ?? '30d') === '30d' ? 'selected' : '' ?>>Last 30 days</option>
                    <option value="90d" <?= ($date_range['period'] ?? '30d') === '90d' ? 'selected' : '' ?>>Last 90 days</option>
                </select>
            </div>
            
            <!-- Live Visitors -->
            <div class="flex items-center space-x-2 bg-card border border-primary rounded-md px-3 py-2">
                <div class="w-2 h-2 bg-accent-blue rounded-full animate-pulse"></div>
                <span class="font-mono font-bold text-accent-blue"><?= $analytics['realtime_stats']['active_visitors'] ?? 0 ?></span>
                <span class="text-xs text-tertiary">live</span>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-4 mb-8">
        <div class="analytics-card">
            <div class="flex items-center justify-between">
                <div>
                    <span class="metric-label">Pageviews</span>
                    <span class="metric-number">
                        <?= number_format($analytics['overview']['pageviews'] ?? 0) ?>
                    </span>
                    <?php if (isset($analytics['overview']['pageviews_change'])): ?>
                        <span class="metric-change <?= $analytics['overview']['pageviews_change'] >= 0 ? 'positive' : 'negative' ?>">
                            <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <?php if ($analytics['overview']['pageviews_change'] >= 0): ?>
                                    <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L10 4.414 6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                <?php else: ?>
                                    <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L10 15.586l3.293-3.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                <?php endif; ?>
                            </svg>
                            <?= abs(round($analytics['overview']['pageviews_change'], 1)) ?>%
                        </span>
                    <?php endif; ?>
                </div>
                <div class="w-12 h-12 bg-accent-blue/20 rounded-md flex items-center justify-center">
                    <svg class="w-6 h-6 text-accent-blue" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="analytics-card">
            <div class="flex items-center justify-between">
                <div>
                    <span class="metric-label">Unique Visitors</span>
                    <span class="metric-number">
                        <?= number_format($analytics['overview']['visitors'] ?? 0) ?>
                    </span>
                    <?php if (isset($analytics['overview']['visitors_change'])): ?>
                        <span class="metric-change <?= $analytics['overview']['visitors_change'] >= 0 ? 'positive' : 'negative' ?>">
                            <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <?php if ($analytics['overview']['visitors_change'] >= 0): ?>
                                    <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L10 4.414 6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                <?php else: ?>
                                    <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L10 15.586l3.293-3.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                <?php endif; ?>
                            </svg>
                            <?= abs(round($analytics['overview']['visitors_change'], 1)) ?>%
                        </span>
                    <?php endif; ?>
                </div>
                <div class="w-12 h-12 bg-accent-purple/20 rounded-md flex items-center justify-center">
                    <svg class="w-6 h-6 text-accent-purple" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="analytics-card">
            <div class="flex items-center justify-between">
                <div>
                    <span class="metric-label">Avg. Session</span>
                    <span class="metric-number">
                        <?= gmdate("i:s", $analytics['overview']['avg_session_duration'] ?? 0) ?>
                    </span>
                    <span class="text-sm text-muted">min:sec</span>
                </div>
                <div class="w-12 h-12 bg-accent-green/20 rounded-md flex items-center justify-center">
                    <svg class="w-6 h-6 text-accent-green" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="analytics-card">
            <div class="flex items-center justify-between">
                <div>
                    <span class="metric-label">Bounce Rate</span>
                    <span class="metric-number">
                        <?= round($analytics['overview']['bounce_rate'] ?? 0, 1) ?>%
                    </span>
                    <span class="text-sm text-muted">single page visits</span>
                </div>
                <div class="w-12 h-12 bg-accent-orange/20 rounded-md flex items-center justify-center">
                    <svg class="w-6 h-6 text-accent-orange" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Traffic Chart -->
    <div class="grid grid-cols-3 gap-6 mb-8">
        <div class="col-span-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Traffic Overview</h3>
                    <div class="flex space-x-2">
                        <button class="btn btn-sm" onclick="changeChartPeriod('hourly')">Hourly</button>
                        <button class="btn btn-sm" onclick="changeChartPeriod('daily')">Daily</button>
                        <button class="btn btn-sm" onclick="changeChartPeriod('weekly')">Weekly</button>
                    </div>
                </div>
                <div id="trafficChart" class="h-64"></div>
            </div>
        </div>
        <div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Device Breakdown</h3>
                </div>
                <div id="deviceChart" class="h-64"></div>
            </div>
        </div>
    </div>

    <!-- Secondary Charts -->
    <div class="grid grid-cols-2 gap-6 mb-8">
        <!-- Top Countries -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Top Countries</h3>
            </div>
            <div class="space-y-4">
                <?php foreach (($analytics['geo']['countries'] ?? []) as $country): ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <span class="text-lg"><?= $country['flag'] ?? '🌍' ?></span>
                            <span class="text-sm text-secondary"><?= htmlspecialchars($country['name']) ?></span>
                        </div>
                        <div class="text-right">
                            <span class="font-mono font-bold"><?= number_format($country['visitors']) ?></span>
                            <div class="w-20 h-2 bg-tertiary rounded-full overflow-hidden">
                                <div class="h-full bg-accent-blue rounded-full" style="width: <?= $country['percentage'] ?>%"></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Top Referrers -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Top Referrers</h3>
            </div>
            <div class="space-y-4">
                <?php foreach (($analytics['referrers']['top'] ?? []) as $referrer): ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-6 h-6 bg-tertiary rounded-full flex items-center justify-center">
                                <span class="text-xs">🔗</span>
                            </div>
                            <span class="text-sm text-secondary truncate"><?= htmlspecialchars($referrer['domain']) ?></span>
                        </div>
                        <div class="text-right">
                            <span class="font-mono font-bold"><?= number_format($referrer['sessions']) ?></span>
                            <span class="metric-change <?= $referrer['change'] >= 0 ? 'positive' : 'negative' ?>">
                                <?= $referrer['change'] >= 0 ? '+' : '' ?><?= round($referrer['change'], 1) ?>%
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Top Pages -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Top Pages</h3>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Pageviews</th>
                        <th>Unique Visitors</th>
                        <th>Avg. Time</th>
                        <th>Bounce Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($analytics['pages']['top'] ?? []) as $page): ?>
                        <tr>
                            <td>
                                <div>
                                    <div class="font-medium text-primary"><?= htmlspecialchars($page['title'] ?? $page['path']) ?></div>
                                    <div class="text-sm text-muted"><?= htmlspecialchars($page['path']) ?></div>
                                </div>
                            </td>
                            <td class="font-mono"><?= number_format($page['pageviews']) ?></td>
                            <td class="font-mono"><?= number_format($page['unique_visitors']) ?></td>
                            <td class="font-mono"><?= gmdate('i:s', $page['avg_time_on_page']) ?></td>
                            <td class="font-mono"><?= round($page['bounce_rate'], 1) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Date range change handler
document.getElementById('dateRange').addEventListener('change', function() {
    const period = this.value;
    const siteId = <?= $site_id ?? 0 ?>;
    window.location.href = `/dashboard/site?site=${siteId}&period=${period}`;
});

// Initialize real-time updates
document.addEventListener('DOMContentLoaded', function() {
    // Start live visitor updates
    startLiveVisitorUpdates(<?= $site_id ?? 0 ?>);
    
    // Initialize all charts
    initializeTrafficChart();
    initializeDeviceBreakdown();
    initializeGeoMap();
});
</script>