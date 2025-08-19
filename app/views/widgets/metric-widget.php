<?php
/**
 * Metric Widget Component
 * Displays a single metric with trend indicator
 */

$widget_id = $widget_id ?? 'metric_' . uniqid();
$data = $data ?? [];
$settings = $settings ?? [];

$current_value = $data['current_value'] ?? 0;
$previous_value = $data['previous_value'] ?? 0;
$change_percent = $data['change_percent'] ?? 0;
$trend = $data['trend'] ?? 'neutral';
$metric_type = $data['metric_type'] ?? 'pageviews';

// Format values based on metric type
function formatMetricValue($value, $type) {
    switch ($type) {
        case 'bounce_rate':
            return number_format($value, 1) . '%';
        case 'avg_session_duration':
            $minutes = floor($value / 60);
            $seconds = $value % 60;
            return sprintf('%d:%02d', $minutes, $seconds);
        case 'conversion_rate':
            return number_format($value, 2) . '%';
        default:
            return number_format($value);
    }
}

$formatted_current = formatMetricValue($current_value, $metric_type);
$formatted_previous = formatMetricValue($previous_value, $metric_type);
?>

<div class="metric-widget bg-crypto-dark border border-crypto-border rounded-crypto p-4 h-full" data-widget-id="<?= $widget_id ?>">
    <div class="flex items-center justify-between mb-3">
        <h4 class="text-sm font-medium text-gray-300"><?= htmlspecialchars($settings['title'] ?? ucwords(str_replace('_', ' ', $metric_type))) ?></h4>
        <div class="flex items-center space-x-2">
            <?php if (isset($settings['refresh_enabled']) && $settings['refresh_enabled']): ?>
                <button onclick="refreshWidget('<?= $widget_id ?>')" class="text-gray-400 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="flex flex-col h-full">
        <!-- Main Metric -->
        <div class="flex-1 flex items-center">
            <div class="w-full">
                <div class="text-3xl font-bold text-white mono-data mb-1">
                    <?= $formatted_current ?>
                </div>
                
                <!-- Trend Indicator -->
                <?php if ($previous_value > 0): ?>
                    <div class="flex items-center space-x-2">
                        <div class="flex items-center space-x-1 text-sm">
                            <?php if ($trend === 'up'): ?>
                                <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L10 4.414 4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-green-400 mono-data">+<?= abs($change_percent) ?>%</span>
                            <?php elseif ($trend === 'down'): ?>
                                <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L10 15.586l5.293-5.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-red-400 mono-data">-<?= abs($change_percent) ?>%</span>
                            <?php else: ?>
                                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-gray-400 mono-data">0%</span>
                            <?php endif; ?>
                        </div>
                        <span class="text-xs text-gray-500">vs previous period</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Mini Sparkline (optional) -->
        <?php if (isset($data['sparkline_data']) && !empty($data['sparkline_data'])): ?>
            <div class="mt-3 h-8">
                <canvas id="sparkline-<?= $widget_id ?>" class="w-full h-full"></canvas>
            </div>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const canvas = document.getElementById('sparkline-<?= $widget_id ?>');
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    const data = <?= json_encode($data['sparkline_data']) ?>;
                    
                    // Simple sparkline drawing
                    drawSparkline(ctx, data, '<?= $trend ?>');
                }
            });
            
            function drawSparkline(ctx, data, trend) {
                const canvas = ctx.canvas;
                const width = canvas.width;
                const height = canvas.height;
                
                if (data.length < 2) return;
                
                const max = Math.max(...data);
                const min = Math.min(...data);
                const range = max - min || 1;
                
                ctx.clearRect(0, 0, width, height);
                ctx.strokeStyle = trend === 'up' ? '#10B981' : trend === 'down' ? '#EF4444' : '#6B7280';
                ctx.lineWidth = 2;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                
                ctx.beginPath();
                data.forEach((value, index) => {
                    const x = (index / (data.length - 1)) * width;
                    const y = height - ((value - min) / range) * height;
                    
                    if (index === 0) {
                        ctx.moveTo(x, y);
                    } else {
                        ctx.lineTo(x, y);
                    }
                });
                ctx.stroke();
            }
            </script>
        <?php endif; ?>
        
        <!-- Additional Context -->
        <?php if (isset($data['additional_context'])): ?>
            <div class="mt-2 text-xs text-gray-400">
                <?= htmlspecialchars($data['additional_context']) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function refreshWidget(widgetId) {
    const widget = document.querySelector(`[data-widget-id="${widgetId}"]`);
    if (!widget) return;
    
    // Add loading state
    const content = widget.querySelector('.text-3xl');
    const originalContent = content.innerHTML;
    content.innerHTML = '<div class="animate-pulse">...</div>';
    
    // Simulate refresh (in real implementation, this would fetch new data)
    setTimeout(() => {
        content.innerHTML = originalContent;
        
        // Add success indicator
        const indicator = document.createElement('div');
        indicator.className = 'absolute top-2 right-2 w-2 h-2 bg-green-400 rounded-full';
        widget.style.position = 'relative';
        widget.appendChild(indicator);
        
        setTimeout(() => {
            indicator.remove();
        }, 2000);
    }, 1000);
}
</script>