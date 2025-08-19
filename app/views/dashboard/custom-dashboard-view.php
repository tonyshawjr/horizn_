<?php $currentPage = 'custom-dashboard-view'; ?>

<!-- Header -->
<div class="p-6 border-b border-crypto-border">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-3">
                <a href="/dashboard/custom" class="text-gray-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L4.414 9H17a1 1 0 110 2H4.414l5.293 5.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-white mono-data"><?= htmlspecialchars($dashboard['name']) ?></h1>
                <?php if ($dashboard['is_shared']): ?>
                    <span class="bg-crypto-purple text-white text-xs px-2 py-1 rounded-crypto">Shared</span>
                <?php endif; ?>
            </div>
            <?php if ($dashboard['description']): ?>
                <p class="text-gray-400 mt-1 ml-8"><?= htmlspecialchars($dashboard['description']) ?></p>
            <?php endif; ?>
        </div>
        <div class="flex items-center space-x-3">
            <!-- Auto-refresh toggle -->
            <label class="flex items-center space-x-2 text-sm text-gray-300">
                <input type="checkbox" id="autoRefreshToggle" class="rounded border-crypto-border text-crypto-blue focus:border-crypto-blue focus:ring-crypto-blue">
                <span>Auto-refresh</span>
            </label>
            
            <!-- Time range selector -->
            <select id="timeRangeSelector" class="bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white text-sm">
                <option value="1d">Today</option>
                <option value="7d">Last 7 days</option>
                <option value="30d" selected>Last 30 days</option>
                <option value="90d">Last 90 days</option>
            </select>
            
            <!-- Action buttons -->
            <button onclick="refreshAllWidgets()" class="bg-crypto-gray hover:bg-gray-600 text-white px-4 py-2 rounded-crypto transition-colors">
                <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                </svg>
                Refresh
            </button>
            
            <a href="/dashboard/custom/builder?id=<?= $dashboard['id'] ?>" class="bg-crypto-blue hover:bg-blue-600 text-white px-4 py-2 rounded-crypto transition-colors crypto-glow">
                <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                </svg>
                Edit Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Dashboard Grid -->
<div class="p-6">
    <?php if (empty($dashboard['widgets'])): ?>
        <div class="text-center py-20">
            <div class="w-16 h-16 bg-crypto-gray rounded-crypto-lg flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V4z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-white mb-2">This dashboard is empty</h3>
            <p class="text-gray-400 mb-4">Add some widgets to start visualizing your data.</p>
            <a href="/dashboard/custom/builder?id=<?= $dashboard['id'] ?>" class="bg-crypto-blue hover:bg-blue-600 text-white px-6 py-2 rounded-crypto transition-colors crypto-glow inline-flex items-center">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                </svg>
                Add Widgets
            </a>
        </div>
    <?php else: ?>
        <div id="dashboard-view-grid" class="grid-stack">
            <?php foreach ($dashboard['widgets'] as $widget): ?>
                <div class="grid-stack-item" 
                     gs-id="<?= $widget['id'] ?>"
                     gs-x="<?= $widget['x'] ?>" 
                     gs-y="<?= $widget['y'] ?>" 
                     gs-w="<?= $widget['w'] ?>" 
                     gs-h="<?= $widget['h'] ?>"
                     data-widget-type="<?= $widget['type'] ?>"
                     data-widget-config='<?= json_encode($widget['settings']) ?>'>
                    <div class="grid-stack-item-content bg-crypto-dark border border-crypto-border rounded-crypto p-4 h-full">
                        <!-- Widget content will be loaded here -->
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-medium text-white"><?= htmlspecialchars($widget['title']) ?></h4>
                            <div class="flex items-center space-x-2">
                                <button onclick="refreshWidget('<?= $widget['id'] ?>')" class="text-gray-400 hover:text-white transition-colors opacity-0 group-hover:opacity-100">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <div id="widget-content-<?= $widget['id'] ?>" class="flex-1">
                            <!-- Widget content will be dynamically loaded -->
                            <div class="flex items-center justify-center h-32 text-gray-400">
                                <svg class="animate-spin w-6 h-6 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Loading...
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Dashboard Info Panel (Collapsible) -->
<div id="dashboardInfo" class="fixed bottom-4 right-4 bg-crypto-dark border border-crypto-border rounded-crypto-lg p-4 w-80 transform translate-y-full transition-transform duration-300">
    <div class="flex items-center justify-between mb-3">
        <h5 class="text-sm font-medium text-white">Dashboard Info</h5>
        <button onclick="toggleInfoPanel()" class="text-gray-400 hover:text-white">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
            </svg>
        </button>
    </div>
    
    <div class="space-y-2 text-xs text-gray-400">
        <div class="flex justify-between">
            <span>Created:</span>
            <span><?= date('M j, Y', strtotime($dashboard['created_at'])) ?></span>
        </div>
        <div class="flex justify-between">
            <span>Last updated:</span>
            <span><?= date('M j, Y g:i A', strtotime($dashboard['updated_at'])) ?></span>
        </div>
        <div class="flex justify-between">
            <span>Widgets:</span>
            <span><?= count($dashboard['widgets']) ?></span>
        </div>
        <?php if ($dashboard['is_shared']): ?>
            <div class="flex justify-between">
                <span>Visibility:</span>
                <span class="text-crypto-purple">Shared</span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Info Panel Toggle Button -->
<button onclick="toggleInfoPanel()" class="fixed bottom-4 right-4 bg-crypto-blue hover:bg-blue-600 text-white p-3 rounded-full transition-colors crypto-glow" id="infoPanelToggle">
    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
    </svg>
</button>

<!-- Include GridStack CSS and JS for read-only view -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@8.4.0/dist/gridstack.min.css" />
<script src="https://cdn.jsdelivr.net/npm/gridstack@8.4.0/dist/gridstack-all.js"></script>

<script>
let grid;
let autoRefreshInterval;
let dashboardData = <?= json_encode($dashboard) ?>;
let widgetData = <?= json_encode($widget_data) ?>;

document.addEventListener('DOMContentLoaded', function() {
    initializeViewGrid();
    loadWidgetData();
    setupEventListeners();
});

function initializeViewGrid() {
    grid = GridStack.init({
        column: 12,
        staticGrid: true, // Read-only mode
        margin: 10,
        cellHeight: 80,
        animate: false
    });
}

function loadWidgetData() {
    dashboardData.widgets.forEach(widget => {
        const widgetElement = document.getElementById(`widget-content-${widget.id}`);
        if (widgetElement && widgetData[widget.id]) {
            renderWidgetContent(widget.id, widget.type, widgetData[widget.id], widget.settings);
        } else {
            // Load widget data via AJAX
            loadSingleWidgetData(widget.id, widget.type, widget.settings);
        }
    });
}

function loadSingleWidgetData(widgetId, widgetType, settings) {
    const params = new URLSearchParams({
        widget_id: widgetId,
        widget_type: widgetType,
        site_id: settings.site_id || '',
        ...settings
    });
    
    fetch(`/dashboard/custom/widget-data?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderWidgetContent(widgetId, widgetType, data.data, settings);
            } else {
                showWidgetError(widgetId, data.error || 'Failed to load widget data');
            }
        })
        .catch(error => {
            console.error('Error loading widget data:', error);
            showWidgetError(widgetId, 'Network error');
        });
}

function renderWidgetContent(widgetId, widgetType, data, settings) {
    const widgetElement = document.getElementById(`widget-content-${widgetId}`);
    if (!widgetElement) return;
    
    // Render based on widget type
    switch (widgetType) {
        case 'metric':
            renderMetricWidget(widgetElement, data, settings);
            break;
        case 'chart':
            renderChartWidget(widgetElement, data, settings);
            break;
        case 'list':
            renderListWidget(widgetElement, data, settings);
            break;
        case 'map':
            renderMapWidget(widgetElement, data, settings);
            break;
        default:
            renderGenericWidget(widgetElement, data, settings);
    }
}

function renderMetricWidget(element, data, settings) {
    const trend = data.change_percent >= 0 ? 'up' : 'down';
    const trendColor = trend === 'up' ? 'text-green-400' : 'text-red-400';
    const trendIcon = trend === 'up' ? '↑' : '↓';
    
    element.innerHTML = `
        <div class="text-center">
            <div class="text-3xl font-bold text-white mono-data mb-2">
                ${data.current_value?.toLocaleString() || '0'}
            </div>
            ${data.change_percent !== undefined ? `
                <div class="flex items-center justify-center space-x-1 text-sm ${trendColor}">
                    <span>${trendIcon}</span>
                    <span class="mono-data">${Math.abs(data.change_percent)}%</span>
                </div>
            ` : ''}
        </div>
    `;
}

function renderChartWidget(element, data, settings) {
    element.innerHTML = `
        <div id="chart-${element.closest('[gs-id]').getAttribute('gs-id')}" class="h-full min-h-[200px]"></div>
    `;
    
    // Initialize chart (simplified version)
    setTimeout(() => {
        const chartElement = element.querySelector('[id^="chart-"]');
        if (chartElement && data.length > 0) {
            // Simple chart implementation - in production, use ApexCharts
            chartElement.innerHTML = `
                <div class="flex items-end space-x-1 h-full p-4">
                    ${data.slice(0, 10).map((item, i) => `
                        <div class="flex-1 bg-crypto-blue rounded-t" 
                             style="height: ${Math.max(10, (item.value / Math.max(...data.map(d => d.value))) * 100)}%"
                             title="${item.label}: ${item.value}">
                        </div>
                    `).join('')}
                </div>
            `;
        }
    }, 100);
}

function renderListWidget(element, data, settings) {
    if (!data || data.length === 0) {
        element.innerHTML = '<div class="text-center text-gray-400">No data available</div>';
        return;
    }
    
    element.innerHTML = `
        <div class="space-y-2">
            ${data.slice(0, settings.limit || 5).map((item, i) => `
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center space-x-2 flex-1 min-w-0">
                        <span class="w-5 h-5 bg-crypto-blue bg-opacity-20 rounded text-xs text-crypto-blue flex items-center justify-center mono-data">${i + 1}</span>
                        <span class="truncate text-white">${item.name || item.page_title || item.event_name || 'Unknown'}</span>
                    </div>
                    <span class="text-white mono-data">${item.value || item.pageviews || item.count || 0}</span>
                </div>
            `).join('')}
        </div>
    `;
}

function renderMapWidget(element, data, settings) {
    element.innerHTML = `
        <div class="h-full">
            <div class="text-center text-gray-400 py-8">
                <svg class="w-8 h-8 mx-auto mb-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 019 7.5V8a2 2 0 004 0 2 2 0 011.523-1.943A5.977 5.977 0 0116 10c0 .34-.028.675-.083 1H15a2 2 0 00-2 2v2.197A5.973 5.973 0 0110 16v-2a2 2 0 00-2-2 2 2 0 01-2-2 2 2 0 00-1.668-1.973z" clip-rule="evenodd"></path>
                </svg>
                <div class="text-sm">Geographic data visualization</div>
                <div class="text-xs mt-1">${data?.length || 0} locations</div>
            </div>
        </div>
    `;
}

function renderGenericWidget(element, data, settings) {
    element.innerHTML = `
        <div class="text-center text-gray-400">
            <div class="text-lg font-bold text-white mono-data">${data?.value || 'N/A'}</div>
            <div class="text-sm">${data?.label || 'Data'}</div>
        </div>
    `;
}

function showWidgetError(widgetId, error) {
    const widgetElement = document.getElementById(`widget-content-${widgetId}`);
    if (widgetElement) {
        widgetElement.innerHTML = `
            <div class="text-center text-red-400">
                <svg class="w-8 h-8 mx-auto mb-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div class="text-sm">Error loading widget</div>
                <div class="text-xs">${error}</div>
            </div>
        `;
    }
}

function setupEventListeners() {
    // Auto-refresh toggle
    const autoRefreshToggle = document.getElementById('autoRefreshToggle');
    autoRefreshToggle.addEventListener('change', function() {
        if (this.checked) {
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    });
    
    // Time range selector
    const timeRangeSelector = document.getElementById('timeRangeSelector');
    timeRangeSelector.addEventListener('change', function() {
        refreshAllWidgets();
    });
    
    // Add hover effects to widgets
    document.querySelectorAll('.grid-stack-item').forEach(item => {
        item.classList.add('group');
    });
}

function refreshWidget(widgetId) {
    const widget = dashboardData.widgets.find(w => w.id === widgetId);
    if (widget) {
        const widgetElement = document.getElementById(`widget-content-${widgetId}`);
        if (widgetElement) {
            widgetElement.innerHTML = `
                <div class="flex items-center justify-center h-32 text-gray-400">
                    <svg class="animate-spin w-6 h-6 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Refreshing...
                </div>
            `;
        }
        
        loadSingleWidgetData(widgetId, widget.type, widget.settings);
    }
}

function refreshAllWidgets() {
    dashboardData.widgets.forEach(widget => {
        refreshWidget(widget.id);
    });
}

function startAutoRefresh() {
    stopAutoRefresh(); // Clear any existing interval
    autoRefreshInterval = setInterval(() => {
        refreshAllWidgets();
    }, 60000); // Refresh every minute
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

function toggleInfoPanel() {
    const panel = document.getElementById('dashboardInfo');
    const toggle = document.getElementById('infoPanelToggle');
    
    if (panel.classList.contains('translate-y-full')) {
        panel.classList.remove('translate-y-full');
        panel.classList.add('translate-y-0');
        toggle.style.display = 'none';
    } else {
        panel.classList.add('translate-y-full');
        panel.classList.remove('translate-y-0');
        toggle.style.display = 'block';
    }
}
</script>

<style>
.grid-stack {
    background: none;
}

.grid-stack-item-content {
    overflow: hidden;
}

.grid-stack-item:hover .grid-stack-item-content {
    border-color: #3B82F6;
}

#dashboardInfo {
    z-index: 40;
}

#infoPanelToggle {
    z-index: 50;
}
</style>