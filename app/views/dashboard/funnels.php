<?php
/**
 * Conversion Funnel Analysis View
 */

$pageTitle = 'Conversion Funnels - horizn_';
$currentPage = 'funnels';
$additionalScripts = '<script src="/assets/js/dashboard.js"></script><script src="/assets/js/charts.js"></script>';
?>

<!-- Funnels Header -->
<div class="p-6 border-b border-crypto-border">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white mono-data">Conversion Funnels</h1>
            <p class="text-gray-400 text-sm">Analyze user conversion paths and drop-off points</p>
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
                <option value="7d">Last 7 days</option>
                <option value="30d" selected>Last 30 days</option>
                <option value="90d">Last 90 days</option>
            </select>
            
            <!-- New Funnel Button -->
            <button id="createFunnel" class="px-4 py-2 bg-crypto-blue text-white rounded-crypto hover:bg-crypto-blue/80 transition-all">
                <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path>
                </svg>
                New Funnel
            </button>
        </div>
    </div>
</div>

<div class="p-6">
    <!-- Funnel Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-6 crypto-glow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Active Funnels</p>
                    <p class="text-2xl font-bold mono-data text-white">
                        <?= count($funnels ?? []) ?>
                    </p>
                </div>
                <div class="w-12 h-12 bg-crypto-blue/20 rounded-crypto flex items-center justify-center">
                    <svg class="w-6 h-6 text-crypto-blue" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V4z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-6 crypto-glow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Avg. Conversion Rate</p>
                    <p class="text-2xl font-bold mono-data text-white">
                        <?= round($funnel_stats['avg_conversion_rate'] ?? 0, 1) ?>%
                    </p>
                    <?php if (isset($funnel_stats['conversion_change'])): ?>
                        <p class="text-sm flex items-center mt-1 <?= $funnel_stats['conversion_change'] >= 0 ? 'text-green-500' : 'text-red-500' ?>">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <?php if ($funnel_stats['conversion_change'] >= 0): ?>
                                    <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L10 4.414 6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                <?php else: ?>
                                    <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L10 15.586l3.293-3.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                <?php endif; ?>
                            </svg>
                            <?= abs(round($funnel_stats['conversion_change'], 1)) ?>%
                        </p>
                    <?php endif; ?>
                </div>
                <div class="w-12 h-12 bg-green-500/20 rounded-crypto flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-6 crypto-glow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Total Conversions</p>
                    <p class="text-2xl font-bold mono-data text-white">
                        <?= number_format($funnel_stats['total_conversions'] ?? 0) ?>
                    </p>
                </div>
                <div class="w-12 h-12 bg-crypto-purple/20 rounded-crypto flex items-center justify-center">
                    <svg class="w-6 h-6 text-crypto-purple" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-6 crypto-glow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Drop-off Rate</p>
                    <p class="text-2xl font-bold mono-data text-white">
                        <?= round($funnel_stats['avg_dropoff_rate'] ?? 0, 1) ?>%
                    </p>
                    <p class="text-sm text-gray-400">avg per step</p>
                </div>
                <div class="w-12 h-12 bg-red-500/20 rounded-crypto flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Funnel List -->
    <div class="space-y-6">
        <?php if (!empty($funnels)): ?>
            <?php foreach ($funnels as $funnel): ?>
                <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg">
                    <!-- Funnel Header -->
                    <div class="p-6 border-b border-crypto-border">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-white"><?= htmlspecialchars($funnel['name']) ?></h3>
                                <p class="text-sm text-gray-400"><?= htmlspecialchars($funnel['description'] ?? '') ?></p>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="text-right">
                                    <p class="text-2xl font-bold mono-data text-white">
                                        <?= round($funnel['conversion_rate'], 1) ?>%
                                    </p>
                                    <p class="text-sm text-gray-400">conversion rate</p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button class="p-2 text-gray-400 hover:text-white transition-colors" title="Edit funnel">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                        </svg>
                                    </button>
                                    <button class="p-2 text-gray-400 hover:text-red-500 transition-colors" title="Delete funnel">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd"></path>
                                            <path fill-rule="evenodd" d="M4 5a1 1 0 011-1h10a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM5 9a1 1 0 011-1h8a1 1 0 011 1v6a2 2 0 01-2 2H7a2 2 0 01-2-2V9z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Funnel Visualization -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Funnel Chart -->
                            <div>
                                <div id="funnel-chart-<?= $funnel['id'] ?>" class="h-80"></div>
                            </div>

                            <!-- Step Details -->
                            <div class="space-y-4">
                                <?php foreach ($funnel['steps'] as $index => $step): ?>
                                    <div class="border border-crypto-border rounded-crypto p-4 <?= $step['is_critical_dropoff'] ? 'border-red-500/50 bg-red-500/5' : '' ?>">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-8 h-8 bg-crypto-blue/20 rounded-crypto flex items-center justify-center text-sm mono-data text-crypto-blue">
                                                    <?= $index + 1 ?>
                                                </div>
                                                <div>
                                                    <h4 class="font-medium text-white"><?= htmlspecialchars($step['name']) ?></h4>
                                                    <p class="text-xs text-gray-400"><?= htmlspecialchars($step['event_name'] ?? $step['page_url']) ?></p>
                                                </div>
                                            </div>
                                            <?php if ($step['is_critical_dropoff']): ?>
                                                <div class="text-xs text-red-400 flex items-center">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    High drop-off
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="grid grid-cols-3 gap-4 text-sm">
                                            <div>
                                                <p class="text-gray-400">Users</p>
                                                <p class="mono-data font-bold text-white"><?= number_format($step['user_count']) ?></p>
                                            </div>
                                            <div>
                                                <p class="text-gray-400">Conversion</p>
                                                <p class="mono-data font-bold text-white"><?= round($step['conversion_rate'], 1) ?>%</p>
                                            </div>
                                            <div>
                                                <p class="text-gray-400">Drop-off</p>
                                                <p class="mono-data font-bold <?= $step['dropoff_rate'] > 50 ? 'text-red-500' : 'text-white' ?>">
                                                    <?= round($step['dropoff_rate'], 1) ?>%
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Funnel Insights -->
                        <?php if (!empty($funnel['insights'])): ?>
                            <div class="mt-6 bg-crypto-gray/50 border border-crypto-border rounded-crypto p-4">
                                <h4 class="font-bold text-white mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                    Insights & Recommendations
                                </h4>
                                <ul class="space-y-1 text-sm text-gray-300">
                                    <?php foreach ($funnel['insights'] as $insight): ?>
                                        <li class="flex items-start">
                                            <span class="text-crypto-blue mr-2">•</span>
                                            <?= htmlspecialchars($insight) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Empty State -->
            <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-12 text-center">
                <div class="w-16 h-16 bg-crypto-blue/20 rounded-crypto mx-auto mb-4 flex items-center justify-center">
                    <svg class="w-8 h-8 text-crypto-blue" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V4z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">No Funnels Created</h3>
                <p class="text-gray-400 mb-6">Create your first conversion funnel to start tracking user journeys</p>
                <button class="inline-flex items-center px-6 py-3 bg-crypto-blue text-white rounded-crypto hover:bg-crypto-blue/80 transition-all">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path>
                    </svg>
                    Create Your First Funnel
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Site selector change
document.getElementById('siteSelector').addEventListener('change', function() {
    const siteId = this.value;
    const period = document.getElementById('dateRange').value;
    window.location.href = `/dashboard/funnels?site=${siteId}&period=${period}`;
});

// Date range change
document.getElementById('dateRange').addEventListener('change', function() {
    const period = this.value;
    const siteId = document.getElementById('siteSelector').value;
    window.location.href = `/dashboard/funnels?site=${siteId}&period=${period}`;
});

// Initialize funnel charts
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($funnels)): ?>
        <?php foreach ($funnels as $funnel): ?>
            initializeFunnelChart(<?= $funnel['id'] ?>, <?= json_encode($funnel['chart_data']) ?>);
        <?php endforeach; ?>
    <?php endif; ?>
});

function initializeFunnelChart(funnelId, data) {
    const options = {
        series: [{
            name: 'Users',
            data: data.map(step => step.user_count)
        }],
        chart: {
            type: 'bar',
            height: 320,
            background: 'transparent',
            toolbar: {
                show: false
            }
        },
        plotOptions: {
            bar: {
                horizontal: true,
                distributed: true,
                barHeight: '70%',
                borderRadius: 2
            }
        },
        colors: ['#3B82F6', '#8B5CF6', '#10B981', '#F59E0B', '#EF4444'],
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return val.toLocaleString();
            },
            style: {
                colors: ['#FFFFFF'],
                fontSize: '12px',
                fontFamily: 'JetBrains Mono'
            }
        },
        xaxis: {
            categories: data.map(step => step.name),
            labels: {
                style: {
                    colors: '#9CA3AF',
                    fontSize: '12px'
                }
            }
        },
        yaxis: {
            labels: {
                style: {
                    colors: '#9CA3AF',
                    fontSize: '12px'
                }
            }
        },
        grid: {
            borderColor: '#2A2A2E',
            strokeDashArray: 0
        },
        tooltip: {
            theme: 'dark',
            y: {
                formatter: function(val, { seriesIndex, dataPointIndex }) {
                    const step = data[dataPointIndex];
                    return `
                        <div>
                            <div>Users: ${val.toLocaleString()}</div>
                            <div>Conversion: ${step.conversion_rate}%</div>
                            <div>Drop-off: ${step.dropoff_rate}%</div>
                        </div>
                    `;
                }
            }
        },
        legend: {
            show: false
        }
    };

    const chart = new ApexCharts(document.querySelector(`#funnel-chart-${funnelId}`), options);
    chart.render();
}

// Create funnel modal (placeholder)
document.getElementById('createFunnel').addEventListener('click', function() {
    alert('Funnel creation modal would open here');
});
</script>