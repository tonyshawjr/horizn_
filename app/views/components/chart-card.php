<?php
/**
 * Chart Card Component
 * 
 * @param string $title - Card title
 * @param string $chartId - Unique ID for the chart container
 * @param string $subtitle - Optional subtitle
 * @param array $actions - Optional action buttons array
 * @param string $height - Chart height (default: 300px)
 * @param bool $showHeader - Show header section (default: true)
 */

$title = $title ?? 'Chart';
$chartId = $chartId ?? 'chart-' . uniqid();
$subtitle = $subtitle ?? '';
$actions = $actions ?? [];
$height = $height ?? '300px';
$showHeader = $showHeader ?? true;
?>

<div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg crypto-glow hover:border-crypto-blue transition-all">
    <?php if ($showHeader): ?>
        <!-- Header -->
        <div class="p-6 border-b border-crypto-border">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-white mb-1"><?= htmlspecialchars($title) ?></h3>
                    <?php if ($subtitle): ?>
                        <p class="text-sm text-gray-400"><?= htmlspecialchars($subtitle) ?></p>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($actions)): ?>
                    <div class="flex items-center space-x-2">
                        <?php foreach ($actions as $action): ?>
                            <button 
                                type="button"
                                class="px-3 py-1 text-xs bg-crypto-gray hover:bg-crypto-blue text-gray-300 hover:text-white rounded-crypto transition-all"
                                <?php if (isset($action['onclick'])): ?>onclick="<?= htmlspecialchars($action['onclick']) ?>"<?php endif; ?>
                            >
                                <?= htmlspecialchars($action['label'] ?? 'Action') ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Chart Container -->
    <div class="p-6">
        <div 
            id="<?= $chartId ?>" 
            style="height: <?= $height ?>"
            class="w-full"
        ></div>
    </div>
</div>

<script>
    // ApexCharts Dark Theme Configuration
    window.cryptoChartConfig = {
        chart: {
            background: 'transparent',
            fontFamily: 'Inter, sans-serif',
            toolbar: {
                show: false
            },
            zoom: {
                enabled: false
            }
        },
        theme: {
            mode: 'dark'
        },
        grid: {
            borderColor: '#2A2A2E',
            strokeDashArray: 2,
            xaxis: {
                lines: {
                    show: true
                }
            },
            yaxis: {
                lines: {
                    show: true
                }
            }
        },
        colors: ['#3B82F6', '#8B5CF6', '#10B981', '#F59E0B', '#EF4444'],
        stroke: {
            width: 2,
            curve: 'smooth'
        },
        dataLabels: {
            enabled: false
        },
        tooltip: {
            theme: 'dark',
            style: {
                fontSize: '12px',
                fontFamily: 'JetBrains Mono, monospace',
            },
            x: {
                show: true
            },
            y: {
                formatter: function(value) {
                    if (typeof value === 'number') {
                        return value.toLocaleString();
                    }
                    return value;
                }
            }
        },
        legend: {
            labels: {
                colors: '#9CA3AF'
            }
        },
        xaxis: {
            labels: {
                style: {
                    colors: '#6B7280',
                    fontSize: '12px',
                    fontFamily: 'JetBrains Mono, monospace',
                }
            },
            axisBorder: {
                color: '#2A2A2E'
            },
            axisTicks: {
                color: '#2A2A2E'
            }
        },
        yaxis: {
            labels: {
                style: {
                    colors: '#6B7280',
                    fontSize: '12px',
                    fontFamily: 'JetBrains Mono, monospace',
                },
                formatter: function(value) {
                    if (typeof value === 'number') {
                        return value.toLocaleString();
                    }
                    return value;
                }
            }
        },
        fill: {
            opacity: 0.8,
            gradient: {
                shade: 'dark',
                type: 'vertical',
                shadeIntensity: 0.1,
                gradientToColors: undefined,
                inverseColors: false,
                opacityFrom: 0.8,
                opacityTo: 0.3,
                stops: [0, 100]
            }
        }
    };
    
    // Export chart instance for external manipulation
    window.charts = window.charts || {};
    window.charts['<?= $chartId ?>'] = null;
</script>