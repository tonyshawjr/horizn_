<?php
/**
 * Stat Card Component
 * 
 * @param string $title - Card title
 * @param string $value - Main metric value
 * @param string $change - Percentage change (e.g., "+12.5%")
 * @param string $trend - 'up', 'down', or 'neutral'
 * @param string $icon - SVG icon HTML
 * @param string $subtitle - Optional subtitle/description
 * @param string $period - Optional time period (e.g., "vs last month")
 */

$title = $title ?? 'Metric';
$value = $value ?? '0';
$change = $change ?? '0%';
$trend = $trend ?? 'neutral';
$icon = $icon ?? '';
$subtitle = $subtitle ?? '';
$period = $period ?? '';

// Trend colors
$trendColors = [
    'up' => 'text-green-400',
    'down' => 'text-red-400',
    'neutral' => 'text-gray-400'
];

$trendColor = $trendColors[$trend] ?? 'text-gray-400';

// Trend icons
$trendIcons = [
    'up' => '<svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L10 4.414 4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>',
    'down' => '<svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L10 15.586l5.293-5.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>',
    'neutral' => '<svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>'
];

$trendIcon = $trendIcons[$trend] ?? $trendIcons['neutral'];
?>

<div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-6 crypto-glow hover:border-crypto-blue transition-all">
    <!-- Header -->
    <div class="flex items-start justify-between mb-4">
        <div class="flex items-center space-x-3">
            <?php if ($icon): ?>
                <div class="w-10 h-10 bg-crypto-gray rounded-crypto flex items-center justify-center text-crypto-blue">
                    <?= $icon ?>
                </div>
            <?php endif; ?>
            <div>
                <h3 class="text-sm font-medium text-gray-300"><?= htmlspecialchars($title) ?></h3>
                <?php if ($subtitle): ?>
                    <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($subtitle) ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Trend Indicator -->
        <div class="flex items-center space-x-1 <?= $trendColor ?>">
            <?= $trendIcon ?>
            <span class="text-sm font-medium mono-data"><?= htmlspecialchars($change) ?></span>
        </div>
    </div>
    
    <!-- Main Value -->
    <div class="mb-2">
        <span class="text-3xl font-bold mono-data text-white"><?= htmlspecialchars($value) ?></span>
    </div>
    
    <!-- Period -->
    <?php if ($period): ?>
        <p class="text-xs text-gray-500"><?= htmlspecialchars($period) ?></p>
    <?php endif; ?>
</div>