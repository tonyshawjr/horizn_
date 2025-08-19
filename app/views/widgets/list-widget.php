<?php
/**
 * List Widget Component
 * Displays top pages, referrers, events, etc.
 */

$widget_id = $widget_id ?? 'list_' . uniqid();
$data = $data ?? [];
$settings = $settings ?? [];

$list_type = $data['list_type'] ?? 'top_pages';
$list_data = $data['data'] ?? [];
$limit = $data['limit'] ?? 10;
$title = $settings['title'] ?? ucwords(str_replace('_', ' ', $list_type));

// Define icons for different list types
function getListIcon($type) {
    switch ($type) {
        case 'top_pages':
            return '<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>';
        case 'top_referrers':
            return '<path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>';
        case 'recent_events':
            return '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>';
        case 'top_events':
            return '<path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>';
        default:
            return '<path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>';
    }
}

// Format values based on list type
function formatListValue($item, $type) {
    switch ($type) {
        case 'top_pages':
        case 'top_referrers':
            return number_format($item['pageviews'] ?? $item['visits'] ?? $item['count'] ?? 0);
        case 'recent_events':
        case 'top_events':
            return isset($item['timestamp']) ? date('H:i', strtotime($item['timestamp'])) : 
                   (isset($item['count']) ? number_format($item['count']) : '');
        default:
            return $item['value'] ?? $item['count'] ?? '';
    }
}

// Get display text for list items
function getDisplayText($item, $type) {
    switch ($type) {
        case 'top_pages':
            return [
                'primary' => $item['page_title'] ?: basename($item['page_url']),
                'secondary' => $item['page_url']
            ];
        case 'top_referrers':
            return [
                'primary' => $item['referrer_domain'] ?: 'Direct',
                'secondary' => $item['referrer_url'] ?: 'No referrer'
            ];
        case 'recent_events':
        case 'top_events':
            return [
                'primary' => $item['event_name'],
                'secondary' => $item['page_url'] ?? $item['category'] ?? ''
            ];
        default:
            return [
                'primary' => $item['name'] ?? $item['title'] ?? 'Unknown',
                'secondary' => $item['description'] ?? ''
            ];
    }
}
?>

<div class="list-widget bg-crypto-dark border border-crypto-border rounded-crypto p-4 h-full" data-widget-id="<?= $widget_id ?>">
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center space-x-2">
            <svg class="w-4 h-4 text-crypto-blue" fill="currentColor" viewBox="0 0 20 20">
                <?= getListIcon($list_type) ?>
            </svg>
            <h4 class="text-sm font-medium text-gray-300"><?= htmlspecialchars($title) ?></h4>
        </div>
        <div class="flex items-center space-x-2">
            <!-- Limit Selector -->
            <select onchange="changeListLimit('<?= $widget_id ?>', this.value)" class="bg-crypto-gray border border-crypto-border rounded text-xs text-white px-2 py-1">
                <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>Top 5</option>
                <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>Top 10</option>
                <option value="20" <?= $limit == 20 ? 'selected' : '' ?>>Top 20</option>
                <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>Top 50</option>
            </select>
            
            <button onclick="refreshListWidget('<?= $widget_id ?>')" class="text-gray-400 hover:text-white transition-colors">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    </div>
    
    <div class="flex-1 overflow-y-auto">
        <?php if (empty($list_data)): ?>
            <div class="flex items-center justify-center h-32 bg-crypto-gray rounded border border-dashed border-gray-500">
                <div class="text-center text-gray-400">
                    <svg class="w-8 h-8 mx-auto mb-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="text-sm">No data available</div>
                </div>
            </div>
        <?php else: ?>
            <div class="space-y-2" id="list-content-<?= $widget_id ?>">
                <?php foreach (array_slice($list_data, 0, $limit) as $index => $item): 
                    $display = getDisplayText($item, $list_type);
                    $value = formatListValue($item, $list_type);
                ?>
                    <div class="flex items-center justify-between p-2 rounded-crypto hover:bg-crypto-gray transition-colors group">
                        <div class="flex items-center space-x-3 flex-1 min-w-0">
                            <!-- Rank -->
                            <div class="flex-shrink-0 w-6 h-6 bg-crypto-blue bg-opacity-20 rounded-crypto flex items-center justify-center">
                                <span class="text-xs font-bold text-crypto-blue mono-data"><?= $index + 1 ?></span>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-white truncate">
                                    <?= htmlspecialchars($display['primary']) ?>
                                </div>
                                <?php if ($display['secondary']): ?>
                                    <div class="text-xs text-gray-400 truncate">
                                        <?= htmlspecialchars($display['secondary']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Value -->
                        <div class="flex-shrink-0 text-right">
                            <div class="text-sm font-bold text-white mono-data">
                                <?= htmlspecialchars($value) ?>
                            </div>
                            
                            <!-- Progress Bar (for top lists) -->
                            <?php if (in_array($list_type, ['top_pages', 'top_referrers', 'top_events']) && isset($item['percentage'])): ?>
                                <div class="w-16 h-1 bg-crypto-gray rounded-full mt-1">
                                    <div class="h-full bg-crypto-blue rounded-full transition-all duration-500" 
                                         style="width: <?= min(100, $item['percentage']) ?>%"></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Show More Button -->
            <?php if (count($list_data) > $limit): ?>
                <div class="mt-3 pt-3 border-t border-crypto-border">
                    <button onclick="showMoreItems('<?= $widget_id ?>')" class="w-full text-center text-xs text-crypto-blue hover:text-blue-400 transition-colors">
                        Show <?= min(10, count($list_data) - $limit) ?> more items
                    </button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Footer Stats -->
    <?php if (!empty($list_data)): ?>
        <div class="mt-3 pt-3 border-t border-crypto-border">
            <div class="flex justify-between text-xs text-gray-400">
                <span>
                    Showing <?= min($limit, count($list_data)) ?> of <?= count($list_data) ?>
                </span>
                <?php if (isset($data['date_range'])): ?>
                    <span>
                        <?= date('M j', strtotime($data['date_range']['start'])) ?> - 
                        <?= date('M j', strtotime($data['date_range']['end'])) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function changeListLimit(widgetId, newLimit) {
    // In a real implementation, this would fetch new data with the updated limit
    console.log(`Changing limit for widget ${widgetId} to ${newLimit}`);
    
    // For now, just show a loading state
    const content = document.getElementById(`list-content-${widgetId}`);
    if (content) {
        content.innerHTML = '<div class="text-center text-gray-400 py-4">Loading...</div>';
        
        // Simulate loading
        setTimeout(() => {
            location.reload(); // In real implementation, would update content dynamically
        }, 1000);
    }
}

function showMoreItems(widgetId) {
    // In a real implementation, this would load more items
    console.log(`Loading more items for widget ${widgetId}`);
    
    // Simulate loading more items
    const widget = document.querySelector(`[data-widget-id="${widgetId}"]`);
    const button = widget.querySelector('button[onclick*="showMoreItems"]');
    
    if (button) {
        button.innerHTML = 'Loading...';
        button.disabled = true;
        
        setTimeout(() => {
            button.remove(); // Hide button after loading
        }, 1000);
    }
}

function refreshListWidget(widgetId) {
    const widget = document.querySelector(`[data-widget-id="${widgetId}"]`);
    if (!widget) return;
    
    const content = document.getElementById(`list-content-${widgetId}`);
    if (!content) return;
    
    // Add loading state
    const originalContent = content.innerHTML;
    content.innerHTML = `
        <div class="flex items-center justify-center py-8">
            <svg class="animate-spin w-6 h-6 text-crypto-blue" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    `;
    
    // Simulate refresh
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
    }, 1500);
}

// Add hover effects for list items
document.addEventListener('DOMContentLoaded', function() {
    const widget = document.querySelector('[data-widget-id="<?= $widget_id ?>"]');
    if (!widget) return;
    
    const listItems = widget.querySelectorAll('.group');
    listItems.forEach((item, index) => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(4px)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
        
        // Add click functionality (could open details modal)
        item.addEventListener('click', function() {
            console.log(`Clicked item ${index + 1}`);
            // Could show detailed analytics for this item
        });
    });
});
</script>