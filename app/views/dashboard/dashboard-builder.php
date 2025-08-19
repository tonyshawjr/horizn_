<?php $currentPage = 'dashboard-builder'; ?>

<!-- Header -->
<div class="p-6 border-b border-crypto-border">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white mono-data">
                <?= $dashboard ? 'Edit Dashboard' : 'Create Dashboard' ?>
            </h1>
            <p class="text-gray-400 mt-1">
                <?= $dashboard ? 'Modify your custom dashboard layout and widgets' : 'Build a custom dashboard with drag-and-drop widgets' ?>
            </p>
        </div>
        <div class="flex items-center space-x-3">
            <button id="previewBtn" class="bg-crypto-gray hover:bg-gray-600 text-white px-4 py-2 rounded-crypto transition-colors">
                <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                </svg>
                Preview
            </button>
            <button id="saveDashboard" class="bg-crypto-blue hover:bg-blue-600 text-white px-4 py-2 rounded-crypto transition-colors crypto-glow">
                <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2zM3 7a1 1 0 011-1h12a1 1 0 011 1v1H3V7zM13 3a1 1 0 00-1-1H8a1 1 0 00-1 1v4h6V3z" clip-rule="evenodd"></path>
                </svg>
                Save Dashboard
            </button>
        </div>
    </div>
</div>

<div class="flex h-full">
    <!-- Widget Sidebar -->
    <div class="w-80 bg-crypto-dark border-r border-crypto-border overflow-y-auto">
        <!-- Dashboard Settings -->
        <div class="p-4 border-b border-crypto-border">
            <h3 class="text-lg font-semibold text-white mb-4">Dashboard Settings</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Dashboard Name</label>
                    <input 
                        type="text" 
                        id="dashboardName" 
                        value="<?= htmlspecialchars($dashboard['name'] ?? '') ?>"
                        placeholder="My Custom Dashboard"
                        class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white placeholder-gray-400 focus:border-crypto-blue focus:outline-none"
                    >
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                    <textarea 
                        id="dashboardDescription" 
                        placeholder="Dashboard description..."
                        rows="3"
                        class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white placeholder-gray-400 focus:border-crypto-blue focus:outline-none"
                    ><?= htmlspecialchars($dashboard['description'] ?? '') ?></textarea>
                </div>
                
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        id="isShared" 
                        <?= ($dashboard['is_shared'] ?? false) ? 'checked' : '' ?>
                        class="rounded border-crypto-border text-crypto-blue focus:border-crypto-blue focus:ring-crypto-blue"
                    >
                    <label for="isShared" class="ml-2 text-sm text-gray-300">Share with organization</label>
                </div>
            </div>
        </div>
        
        <!-- Available Widgets -->
        <div class="p-4">
            <h3 class="text-lg font-semibold text-white mb-4">Available Widgets</h3>
            
            <div class="space-y-3">
                <?php foreach ($available_widgets as $type => $widget): ?>
                    <div class="widget-item bg-crypto-gray border border-crypto-border rounded-crypto p-3 cursor-pointer hover:border-crypto-blue transition-colors crypto-glow" 
                         data-widget-type="<?= $type ?>"
                         data-widget-name="<?= htmlspecialchars($widget['name']) ?>"
                         data-widget-category="<?= htmlspecialchars($widget['category']) ?>">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-crypto-blue rounded-crypto flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <?php if ($widget['icon'] === 'chart-bar'): ?>
                                        <path d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z"></path>
                                    <?php elseif ($widget['icon'] === 'chart-line'): ?>
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    <?php elseif ($widget['icon'] === 'list-bullet'): ?>
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                    <?php elseif ($widget['icon'] === 'globe-americas'): ?>
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 019 7.5V8a2 2 0 004 0 2 2 0 011.523-1.943A5.977 5.977 0 0116 10c0 .34-.028.675-.083 1H15a2 2 0 00-2 2v2.197A5.973 5.973 0 0110 16v-2a2 2 0 00-2-2 2 2 0 01-2-2 2 2 0 00-1.668-1.973z" clip-rule="evenodd"></path>
                                    <?php elseif ($widget['icon'] === 'funnel'): ?>
                                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V4z"></path>
                                    <?php elseif ($widget['icon'] === 'signal'): ?>
                                        <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a3 3 0 01-3-3V6z" clip-rule="evenodd"></path>
                                    <?php endif; ?>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-white"><?= htmlspecialchars($widget['name']) ?></div>
                                <div class="text-xs text-gray-400"><?= htmlspecialchars($widget['description']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Dashboard Canvas -->
    <div class="flex-1 bg-crypto-black p-6 overflow-auto">
        <div id="dashboard-grid" class="grid-stack">
            <!-- Widgets will be added here dynamically -->
        </div>
        
        <!-- Empty State -->
        <div id="empty-state" class="text-center py-20 <?= $dashboard && !empty(json_decode($dashboard['widgets'], true)) ? 'hidden' : '' ?>">
            <div class="w-16 h-16 bg-crypto-gray rounded-crypto-lg flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V4z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-white mb-2">Start building your dashboard</h3>
            <p class="text-gray-400 mb-4">Drag widgets from the sidebar to create your custom dashboard layout.</p>
        </div>
    </div>
</div>

<!-- Widget Configuration Modal -->
<div id="widgetConfigModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-white">Configure Widget</h3>
            <button onclick="closeConfigModal()" class="text-gray-400 hover:text-white">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        
        <div id="widget-config-content">
            <!-- Widget configuration form will be loaded here -->
        </div>
        
        <div class="flex space-x-3 mt-6">
            <button onclick="closeConfigModal()" class="flex-1 bg-crypto-gray hover:bg-gray-600 text-white py-2 rounded-crypto transition-colors">
                Cancel
            </button>
            <button onclick="saveWidgetConfig()" class="flex-1 bg-crypto-blue hover:bg-blue-600 text-white py-2 rounded-crypto transition-colors">
                Save Widget
            </button>
        </div>
    </div>
</div>

<!-- Include GridStack CSS and JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@8.4.0/dist/gridstack.min.css" />
<script src="https://cdn.jsdelivr.net/npm/gridstack@8.4.0/dist/gridstack-all.js"></script>

<script>
let grid;
let currentWidgetId = null;
let dashboardData = <?= json_encode($dashboard) ?>;
let availableWidgets = <?= json_encode($available_widgets) ?>;
let dataSources = <?= json_encode($data_sources) ?>;

// Initialize dashboard builder
document.addEventListener('DOMContentLoaded', function() {
    initializeGrid();
    setupEventListeners();
    
    // Load existing dashboard if editing
    if (dashboardData && dashboardData.widgets) {
        loadExistingWidgets(dashboardData.widgets);
    }
});

function initializeGrid() {
    grid = GridStack.init({
        column: 12,
        minRow: 1,
        margin: '10px',
        resizable: {
            handles: 'e, se, s, sw, w'
        },
        removable: '.trash-zone',
        acceptWidgets: true,
        cellHeight: 80
    });
    
    // Handle widget changes
    grid.on('change', function(event, items) {
        updateEmptyState();
    });
    
    // Handle widget removal
    grid.on('removed', function(event, items) {
        updateEmptyState();
    });
}

function setupEventListeners() {
    // Make widget items draggable
    document.querySelectorAll('.widget-item').forEach(item => {
        item.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', '');
            e.dataTransfer.setData('widget-type', this.dataset.widgetType);
            e.dataTransfer.setData('widget-name', this.dataset.widgetName);
        });
        item.setAttribute('draggable', 'true');
    });
    
    // Handle widget drops
    grid.on('dropped', function(event, previousWidget, newWidget) {
        const widgetType = event.dataTransfer?.getData('widget-type');
        const widgetName = event.dataTransfer?.getData('widget-name');
        
        if (widgetType) {
            const widgetId = 'widget_' + Date.now();
            const gridItem = newWidget.el;
            
            // Update grid item content
            gridItem.innerHTML = createWidgetHTML(widgetId, widgetType, widgetName);
            gridItem.setAttribute('gs-id', widgetId);
            
            // Show configuration modal
            showWidgetConfig(widgetId, widgetType);
        }
    });
    
    // Save dashboard button
    document.getElementById('saveDashboard').addEventListener('click', saveDashboard);
    
    // Preview button
    document.getElementById('previewBtn').addEventListener('click', previewDashboard);
}

function createWidgetHTML(widgetId, widgetType, widgetName) {
    return `
        <div class="grid-stack-item-content bg-crypto-dark border border-crypto-border rounded-crypto p-4 h-full">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-medium text-white">${widgetName}</h4>
                <div class="flex items-center space-x-2">
                    <button onclick="editWidget('${widgetId}')" class="text-gray-400 hover:text-white">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                        </svg>
                    </button>
                    <button onclick="removeWidget('${widgetId}')" class="text-gray-400 hover:text-red-400">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd"></path>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 012 0v4a1 1 0 11-2 0V9zm4 0a1 1 0 112 0v4a1 1 0 11-2 0V9z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="widget-content-${widgetId} flex items-center justify-center h-20 bg-crypto-gray rounded border border-dashed border-gray-500 text-gray-400 text-sm">
                Configure widget to see data
            </div>
        </div>
    `;
}

function showWidgetConfig(widgetId, widgetType) {
    currentWidgetId = widgetId;
    const widget = availableWidgets[widgetType];
    
    if (!widget) return;
    
    const configHTML = generateConfigForm(widgetType, widget);
    document.getElementById('widget-config-content').innerHTML = configHTML;
    
    document.getElementById('widgetConfigModal').classList.remove('hidden');
    document.getElementById('widgetConfigModal').classList.add('flex');
}

function generateConfigForm(widgetType, widget) {
    let html = `<div class="space-y-4">`;
    
    // Widget title
    html += `
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Widget Title</label>
            <input type="text" id="widgetTitle" value="${widget.name}" class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white">
        </div>
    `;
    
    // Site selection (for most widgets)
    if (widgetType !== 'funnel') {
        html += `
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Site</label>
                <select id="widgetSite" class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white">
                    <option value="">Select a site</option>
                    ${dataSources.sites.map(site => `<option value="${site.id}">${site.name}</option>`).join('')}
                </select>
            </div>
        `;
    }
    
    // Widget-specific settings
    Object.entries(widget.settings).forEach(([key, options]) => {
        if (Array.isArray(options)) {
            html += `
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">${key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</label>
                    <select id="widget${key.replace('_', '')}" class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white">
                        ${options.map(option => `<option value="${option}">${option.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</option>`).join('')}
                    </select>
                </div>
            `;
        } else if (key === 'funnel_id') {
            html += `
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Funnel</label>
                    <select id="widgetfunnelid" class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white">
                        <option value="">Select a funnel</option>
                        ${dataSources.funnels.map(funnel => `<option value="${funnel.id}">${funnel.name}</option>`).join('')}
                    </select>
                </div>
            `;
        }
    });
    
    html += `</div>`;
    return html;
}

function editWidget(widgetId) {
    const gridItem = document.querySelector(`[gs-id="${widgetId}"]`);
    if (!gridItem) return;
    
    const widgetType = gridItem.dataset.widgetType;
    showWidgetConfig(widgetId, widgetType);
}

function removeWidget(widgetId) {
    const gridItem = document.querySelector(`[gs-id="${widgetId}"]`);
    if (gridItem) {
        grid.removeWidget(gridItem);
    }
}

function closeConfigModal() {
    document.getElementById('widgetConfigModal').classList.add('hidden');
    document.getElementById('widgetConfigModal').classList.remove('flex');
    currentWidgetId = null;
}

function saveWidgetConfig() {
    if (!currentWidgetId) return;
    
    const gridItem = document.querySelector(`[gs-id="${currentWidgetId}"]`);
    if (!gridItem) return;
    
    // Get configuration values
    const config = {
        title: document.getElementById('widgetTitle')?.value || '',
        site_id: document.getElementById('widgetSite')?.value || '',
    };
    
    // Get widget-specific settings
    document.querySelectorAll('#widget-config-content select, #widget-config-content input').forEach(input => {
        if (input.id && input.id !== 'widgetTitle' && input.id !== 'widgetSite') {
            const key = input.id.replace('widget', '').toLowerCase();
            config[key] = input.value;
        }
    });
    
    // Store configuration
    gridItem.dataset.config = JSON.stringify(config);
    
    // Update widget title
    const titleElement = gridItem.querySelector('h4');
    if (titleElement) {
        titleElement.textContent = config.title;
    }
    
    // Load widget data (placeholder)
    const contentElement = gridItem.querySelector(`[class*="widget-content-"]`);
    if (contentElement) {
        contentElement.innerHTML = '<div class="text-center text-white">Loading...</div>';
        
        // In a real implementation, you would fetch data here
        setTimeout(() => {
            contentElement.innerHTML = '<div class="text-center text-white mono-data text-lg">42</div>';
        }, 1000);
    }
    
    closeConfigModal();
}

function updateEmptyState() {
    const hasWidgets = grid.getGridItems().length > 0;
    const emptyState = document.getElementById('empty-state');
    
    if (hasWidgets) {
        emptyState.classList.add('hidden');
    } else {
        emptyState.classList.remove('hidden');
    }
}

function loadExistingWidgets(widgets) {
    widgets.forEach(widget => {
        const gridItem = {
            x: widget.x || 0,
            y: widget.y || 0,
            w: widget.w || 4,
            h: widget.h || 3,
            content: createWidgetHTML(widget.id, widget.type, widget.title || 'Widget'),
            id: widget.id
        };
        
        const addedItem = grid.addWidget(gridItem);
        addedItem.setAttribute('gs-id', widget.id);
        addedItem.dataset.widgetType = widget.type;
        addedItem.dataset.config = JSON.stringify(widget.settings || {});
    });
    
    updateEmptyState();
}

function saveDashboard() {
    const name = document.getElementById('dashboardName').value.trim();
    if (!name) {
        alert('Please enter a dashboard name');
        return;
    }
    
    // Get grid layout
    const layout = [];
    const widgets = [];
    
    grid.getGridItems().forEach(item => {
        const node = item.gridstackNode;
        const widgetId = item.getAttribute('gs-id');
        const config = JSON.parse(item.dataset.config || '{}');
        const widgetType = item.dataset.widgetType;
        
        layout.push({
            id: widgetId,
            x: node.x,
            y: node.y,
            w: node.w,
            h: node.h
        });
        
        widgets.push({
            id: widgetId,
            type: widgetType,
            title: config.title || 'Widget',
            settings: config,
            x: node.x,
            y: node.y,
            w: node.w,
            h: node.h
        });
    });
    
    const dashboardPayload = {
        id: dashboardData?.id || null,
        name: name,
        description: document.getElementById('dashboardDescription').value.trim(),
        layout: layout,
        widgets: widgets,
        settings: {
            columns: 12
        },
        is_shared: document.getElementById('isShared').checked
    };
    
    // Save dashboard
    fetch('/dashboard/custom/save', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(dashboardPayload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Dashboard saved successfully!');
            // Redirect to dashboard view
            window.location.href = `/dashboard/custom/view?id=${data.dashboard_id}`;
        } else {
            alert('Error saving dashboard: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving dashboard');
    });
}

function previewDashboard() {
    // Simple preview - could be enhanced with a modal or separate view
    const widgets = grid.getGridItems();
    if (widgets.length === 0) {
        alert('Add some widgets to preview the dashboard');
        return;
    }
    
    alert('Preview functionality coming soon! Your dashboard has ' + widgets.length + ' widget(s).');
}
</script>

<style>
.grid-stack {
    background: none;
}

.grid-stack-item {
    border-radius: 4px;
}

.grid-stack-item-content {
    cursor: move;
}

.grid-stack-item-content:hover {
    border-color: #3B82F6 !important;
}

.grid-stack-placeholder {
    background: rgba(59, 130, 246, 0.2) !important;
    border: 2px dashed #3B82F6 !important;
    border-radius: 4px;
}

.widget-item:hover {
    transform: translateY(-2px);
    transition: transform 0.2s ease;
}
</style>