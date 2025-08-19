<?php
/**
 * Chart Widget Component
 * Displays various chart types (line, bar, pie, area)
 */

$widget_id = $widget_id ?? 'chart_' . uniqid();
$data = $data ?? [];
$settings = $settings ?? [];

$chart_type = $data['chart_type'] ?? 'line';
$chart_data = $data['data'] ?? [];
$data_source = $data['data_source'] ?? 'pageviews';
$title = $settings['title'] ?? ucwords(str_replace('_', ' ', $data_source)) . ' Chart';
?>

<div class="chart-widget bg-crypto-dark border border-crypto-border rounded-crypto p-4 h-full" data-widget-id="<?= $widget_id ?>">
    <div class="flex items-center justify-between mb-3">
        <h4 class="text-sm font-medium text-gray-300"><?= htmlspecialchars($title) ?></h4>
        <div class="flex items-center space-x-2">
            <!-- Chart Type Selector -->
            <select onchange="changeChartType('<?= $widget_id ?>', this.value)" class="bg-crypto-gray border border-crypto-border rounded text-xs text-white px-2 py-1">
                <option value="line" <?= $chart_type === 'line' ? 'selected' : '' ?>>Line</option>
                <option value="bar" <?= $chart_type === 'bar' ? 'selected' : '' ?>>Bar</option>
                <option value="area" <?= $chart_type === 'area' ? 'selected' : '' ?>>Area</option>
                <?php if ($data_source !== 'timeline'): ?>
                    <option value="pie" <?= $chart_type === 'pie' ? 'selected' : '' ?>>Pie</option>
                <?php endif; ?>
            </select>
            
            <button onclick="refreshChartWidget('<?= $widget_id ?>')" class="text-gray-400 hover:text-white transition-colors">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    </div>
    
    <div class="flex-1 h-full">
        <?php if (empty($chart_data)): ?>
            <div class="flex items-center justify-center h-32 bg-crypto-gray rounded border border-dashed border-gray-500">
                <div class="text-center text-gray-400">
                    <svg class="w-8 h-8 mx-auto mb-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z"></path>
                    </svg>
                    <div class="text-sm">No data available</div>
                </div>
            </div>
        <?php else: ?>
            <div id="chart-<?= $widget_id ?>" class="h-full min-h-[200px]"></div>
        <?php endif; ?>
    </div>
    
    <!-- Chart Legend/Summary -->
    <?php if (!empty($chart_data)): ?>
        <div class="mt-3 pt-3 border-t border-crypto-border">
            <div class="flex justify-between text-xs text-gray-400">
                <span>
                    <?php
                    $total = 0;
                    if ($chart_type === 'pie') {
                        $total = array_sum(array_column($chart_data, 'value'));
                    } else {
                        $total = array_sum(array_column($chart_data, 'y'));
                    }
                    echo 'Total: ' . number_format($total);
                    ?>
                </span>
                <span>
                    <?= count($chart_data) ?> data points
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($chart_data)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    renderChart('<?= $widget_id ?>', <?= json_encode($chart_data) ?>, '<?= $chart_type ?>', '<?= $data_source ?>');
});

function renderChart(widgetId, data, chartType, dataSource) {
    const chartElement = document.getElementById(`chart-${widgetId}`);
    if (!chartElement) return;
    
    // ApexCharts configuration
    const chartConfig = {
        chart: {
            type: chartType === 'area' ? 'area' : chartType,
            height: '100%',
            background: 'transparent',
            toolbar: {
                show: false
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            }
        },
        theme: {
            mode: 'dark'
        },
        colors: ['#3B82F6', '#8B5CF6', '#10B981', '#F59E0B', '#EF4444'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: chartType === 'line' || chartType === 'area' ? 2 : 0
        },
        grid: {
            borderColor: '#2A2A2E',
            strokeDashArray: 5
        },
        tooltip: {
            theme: 'dark',
            style: {
                fontSize: '12px'
            }
        },
        legend: {
            show: chartType === 'pie',
            position: 'bottom',
            labels: {
                colors: '#9CA3AF'
            }
        }
    };
    
    if (chartType === 'pie') {
        chartConfig.series = data.map(item => item.value || item.y);
        chartConfig.labels = data.map(item => item.label || item.x);
        chartConfig.plotOptions = {
            pie: {
                donut: {
                    size: '60%'
                }
            }
        };
    } else {
        chartConfig.series = [{
            name: dataSource.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()),
            data: data.map(item => ({
                x: item.x || item.label,
                y: item.y || item.value
            }))
        }];
        
        chartConfig.xaxis = {
            labels: {
                style: {
                    colors: '#9CA3AF'
                }
            },
            axisBorder: {
                color: '#2A2A2E'
            }
        };
        
        chartConfig.yaxis = {
            labels: {
                style: {
                    colors: '#9CA3AF'
                },
                formatter: function(value) {
                    if (dataSource.includes('rate')) {
                        return value.toFixed(1) + '%';
                    }
                    return Math.round(value);
                }
            }
        };
        
        if (chartType === 'area') {
            chartConfig.fill = {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.9,
                    stops: [0, 90, 100]
                }
            };
        }
    }
    
    // Render chart
    const chart = new ApexCharts(chartElement, chartConfig);
    chart.render();
    
    // Store chart instance for updates
    chartElement._chart = chart;
}

function changeChartType(widgetId, newType) {
    const chartElement = document.getElementById(`chart-${widgetId}`);
    if (!chartElement || !chartElement._chart) return;
    
    // Update chart type
    chartElement._chart.updateOptions({
        chart: {
            type: newType === 'area' ? 'area' : newType
        },
        stroke: {
            width: newType === 'line' || newType === 'area' ? 2 : 0
        },
        fill: {
            type: newType === 'area' ? 'gradient' : 'solid'
        },
        legend: {
            show: newType === 'pie'
        }
    });
}

function refreshChartWidget(widgetId) {
    const widget = document.querySelector(`[data-widget-id="${widgetId}"]`);
    if (!widget) return;
    
    // Add loading overlay
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'absolute inset-0 bg-crypto-dark bg-opacity-75 flex items-center justify-center rounded-crypto';
    loadingOverlay.innerHTML = `
        <div class="text-center text-white">
            <svg class="animate-spin w-6 h-6 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <div class="text-sm">Updating...</div>
        </div>
    `;
    
    widget.style.position = 'relative';
    widget.appendChild(loadingOverlay);
    
    // Simulate refresh
    setTimeout(() => {
        loadingOverlay.remove();
        
        // Add success indicator
        const indicator = document.createElement('div');
        indicator.className = 'absolute top-2 right-2 w-2 h-2 bg-green-400 rounded-full';
        widget.appendChild(indicator);
        
        setTimeout(() => {
            indicator.remove();
        }, 2000);
    }, 1500);
}
</script>
<?php endif; ?>