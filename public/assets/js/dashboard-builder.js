/**
 * Dashboard Builder JavaScript
 * Handles drag-and-drop dashboard creation, widget configuration, and real-time updates
 */

class DashboardBuilder {
    constructor() {
        this.grid = null;
        this.currentWidgetId = null;
        this.dashboardData = null;
        this.availableWidgets = {};
        this.dataSources = {};
        this.autosaveInterval = null;
        this.isDirty = false;
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.initializeGrid();
        this.setupAutosave();
        
        // Initialize tooltips and interactions
        this.initializeTooltips();
        this.setupKeyboardShortcuts();
    }
    
    setupEventListeners() {
        // Save dashboard
        const saveBtn = document.getElementById('saveDashboard');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.saveDashboard());
        }
        
        // Preview dashboard
        const previewBtn = document.getElementById('previewBtn');
        if (previewBtn) {
            previewBtn.addEventListener('click', () => this.previewDashboard());
        }
        
        // Dashboard name/description changes
        const nameInput = document.getElementById('dashboardName');
        const descInput = document.getElementById('dashboardDescription');
        const sharedCheckbox = document.getElementById('isShared');
        
        [nameInput, descInput, sharedCheckbox].forEach(element => {
            if (element) {
                element.addEventListener('change', () => this.markDirty());
            }
        });
        
        // Widget dragging
        this.setupWidgetDragging();
        
        // Modal event listeners
        this.setupModalListeners();
        
        // Window beforeunload for unsaved changes
        window.addEventListener('beforeunload', (e) => {
            if (this.isDirty) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });
    }
    
    initializeGrid() {
        this.grid = GridStack.init({
            column: 12,
            minRow: 1,
            margin: 10,
            resizable: {
                handles: 'e, se, s, sw, w'
            },
            removable: '.trash-zone',
            acceptWidgets: true,
            cellHeight: 80,
            animate: true,
            float: false
        });
        
        // Grid event handlers
        this.grid.on('change', (event, items) => {
            this.updateEmptyState();
            this.markDirty();
            this.updateGridOverlay();
        });
        
        this.grid.on('removed', (event, items) => {
            this.updateEmptyState();
            this.markDirty();
        });
        
        this.grid.on('dropped', (event, previousWidget, newWidget) => {
            this.handleWidgetDrop(event, newWidget);
        });
        
        this.grid.on('resizestop', (event, element) => {
            this.handleWidgetResize(element);
        });
    }
    
    setupWidgetDragging() {
        const widgetItems = document.querySelectorAll('.widget-item');
        
        widgetItems.forEach(item => {
            item.setAttribute('draggable', 'true');
            
            item.addEventListener('dragstart', (e) => {
                const widgetType = item.dataset.widgetType;
                const widgetName = item.dataset.widgetName;
                const widgetCategory = item.dataset.widgetCategory;
                
                e.dataTransfer.setData('text/plain', '');
                e.dataTransfer.setData('widget-type', widgetType);
                e.dataTransfer.setData('widget-name', widgetName);
                e.dataTransfer.setData('widget-category', widgetCategory);
                
                // Add visual feedback
                item.classList.add('dragging');
                this.showDropZones();
            });
            
            item.addEventListener('dragend', (e) => {
                item.classList.remove('dragging');
                this.hideDropZones();
            });
            
            // Add hover effects
            item.addEventListener('mouseenter', () => {
                item.style.transform = 'translateY(-2px)';
                item.style.boxShadow = '0 4px 12px rgba(59, 130, 246, 0.3)';
            });
            
            item.addEventListener('mouseleave', () => {
                item.style.transform = 'translateY(0)';
                item.style.boxShadow = '';
            });
        });
    }
    
    handleWidgetDrop(event, newWidget) {
        const widgetType = event.dataTransfer?.getData('widget-type');
        const widgetName = event.dataTransfer?.getData('widget-name');
        const widgetCategory = event.dataTransfer?.getData('widget-category');
        
        if (!widgetType) return;
        
        const widgetId = 'widget_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        const gridItem = newWidget.el;
        
        // Set widget data
        gridItem.setAttribute('gs-id', widgetId);
        gridItem.dataset.widgetType = widgetType;
        gridItem.dataset.widgetCategory = widgetCategory;
        
        // Create widget HTML
        gridItem.innerHTML = this.createWidgetHTML(widgetId, widgetType, widgetName);
        
        // Show configuration modal
        this.showWidgetConfig(widgetId, widgetType, widgetName);
        
        // Add animation
        this.animateWidgetIn(gridItem);
    }
    
    createWidgetHTML(widgetId, widgetType, widgetName) {
        const iconSVG = this.getWidgetIcon(widgetType);
        
        return `
            <div class="grid-stack-item-content bg-crypto-dark border border-crypto-border rounded-crypto p-4 h-full">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2">
                        <div class="w-6 h-6 text-crypto-blue">${iconSVG}</div>
                        <h4 class="text-sm font-medium text-white truncate">${widgetName}</h4>
                    </div>
                    <div class="flex items-center space-x-1">
                        <button onclick="dashboardBuilder.editWidget('${widgetId}')" 
                                class="text-gray-400 hover:text-crypto-blue transition-colors p-1 rounded"
                                title="Configure widget">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                            </svg>
                        </button>
                        <button onclick="dashboardBuilder.duplicateWidget('${widgetId}')" 
                                class="text-gray-400 hover:text-crypto-purple transition-colors p-1 rounded"
                                title="Duplicate widget">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M7 9a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9z"></path>
                                <path d="M5 3a2 2 0 00-2 2v6a2 2 0 002 2V5h8a2 2 0 00-2-2H5z"></path>
                            </svg>
                        </button>
                        <button onclick="dashboardBuilder.removeWidget('${widgetId}')" 
                                class="text-gray-400 hover:text-red-400 transition-colors p-1 rounded"
                                title="Remove widget">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd"></path>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 012 0v4a1 1 0 11-2 0V9zm4 0a1 1 0 112 0v4a1 1 0 11-2 0V9z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="widget-content-${widgetId} flex items-center justify-center h-20 bg-crypto-gray rounded border border-dashed border-gray-500 text-gray-400 text-sm">
                    <div class="text-center">
                        <svg class="w-8 h-8 mx-auto mb-1 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
                        </svg>
                        Configure to view data
                    </div>
                </div>
            </div>
        `;
    }
    
    getWidgetIcon(widgetType) {
        const icons = {
            metric: '<svg fill="currentColor" viewBox="0 0 20 20"><path d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z"></path></svg>',
            chart: '<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
            list: '<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>',
            map: '<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 019 7.5V8a2 2 0 004 0 2 2 0 011.523-1.943A5.977 5.977 0 0116 10c0 .34-.028.675-.083 1H15a2 2 0 00-2 2v2.197A5.973 5.973 0 0110 16v-2a2 2 0 00-2-2 2 2 0 01-2-2 2 2 0 00-1.668-1.973z" clip-rule="evenodd"></path></svg>',
            funnel: '<svg fill="currentColor" viewBox="0 0 20 20"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V4z"></path></svg>',
            realtime: '<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a3 3 0 01-3-3V6z" clip-rule="evenodd"></path></svg>'
        };
        
        return icons[widgetType] || icons.metric;
    }
    
    showWidgetConfig(widgetId, widgetType, widgetName) {
        this.currentWidgetId = widgetId;
        
        const configHTML = this.generateConfigForm(widgetType, widgetName);
        document.getElementById('widget-config-content').innerHTML = configHTML;
        
        this.showModal('widgetConfigModal');
        
        // Focus first input
        const firstInput = document.querySelector('#widgetConfigModal input, #widgetConfigModal select');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }
    
    generateConfigForm(widgetType, widgetName) {
        const widget = this.availableWidgets[widgetType];
        if (!widget) return '<div class="text-red-400">Widget configuration not found</div>';
        
        let html = '<div class="space-y-4">';
        
        // Widget title
        html += `
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Widget Title</label>
                <input type="text" id="widgetTitle" value="${widgetName}" 
                       class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white focus:border-crypto-blue focus:outline-none">
            </div>
        `;
        
        // Site selection (for most widgets)
        if (widgetType !== 'funnel') {
            html += this.generateSiteSelector();
        }
        
        // Widget-specific settings
        if (widget.settings) {
            html += this.generateWidgetSettings(widgetType, widget.settings);
        }
        
        // Advanced settings
        html += this.generateAdvancedSettings();
        
        html += '</div>';
        return html;
    }
    
    generateSiteSelector() {
        if (!this.dataSources.sites) return '';
        
        return `
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Site</label>
                <select id="widgetSite" class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white focus:border-crypto-blue focus:outline-none">
                    <option value="">Select a site</option>
                    ${this.dataSources.sites.map(site => 
                        `<option value="${site.id}">${site.name} (${site.domain})</option>`
                    ).join('')}
                </select>
            </div>
        `;
    }
    
    generateWidgetSettings(widgetType, settings) {
        let html = '';
        
        Object.entries(settings).forEach(([key, options]) => {
            const fieldId = `widget${key.replace(/_/g, '')}`;
            const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            
            if (Array.isArray(options)) {
                html += `
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">${label}</label>
                        <select id="${fieldId}" class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white focus:border-crypto-blue focus:outline-none">
                            ${options.map(option => {
                                const displayText = option.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                return `<option value="${option}">${displayText}</option>`;
                            }).join('')}
                        </select>
                    </div>
                `;
            } else if (key === 'funnel_id' && this.dataSources.funnels) {
                html += `
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Funnel</label>
                        <select id="widgetfunnelid" class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white focus:border-crypto-blue focus:outline-none">
                            <option value="">Select a funnel</option>
                            ${this.dataSources.funnels.map(funnel => 
                                `<option value="${funnel.id}">${funnel.name}</option>`
                            ).join('')}
                        </select>
                    </div>
                `;
            }
        });
        
        return html;
    }
    
    generateAdvancedSettings() {
        return `
            <div class="border-t border-crypto-border pt-4">
                <h5 class="text-sm font-medium text-gray-300 mb-3">Advanced Settings</h5>
                <div class="space-y-3">
                    <div class="flex items-center">
                        <input type="checkbox" id="widgetAutoRefresh" class="rounded border-crypto-border text-crypto-blue focus:border-crypto-blue focus:ring-crypto-blue">
                        <label for="widgetAutoRefresh" class="ml-2 text-sm text-gray-300">Auto-refresh data</label>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Refresh Interval (seconds)</label>
                        <select id="widgetRefreshInterval" class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white focus:border-crypto-blue focus:outline-none">
                            <option value="30">30 seconds</option>
                            <option value="60" selected>1 minute</option>
                            <option value="300">5 minutes</option>
                            <option value="600">10 minutes</option>
                        </select>
                    </div>
                </div>
            </div>
        `;
    }
    
    editWidget(widgetId) {
        const gridItem = document.querySelector(`[gs-id="${widgetId}"]`);
        if (!gridItem) return;
        
        const widgetType = gridItem.dataset.widgetType;
        const currentConfig = JSON.parse(gridItem.dataset.config || '{}');
        
        this.showWidgetConfig(widgetId, widgetType, currentConfig.title || 'Widget');
        
        // Populate form with current values
        setTimeout(() => {
            Object.entries(currentConfig).forEach(([key, value]) => {
                const input = document.getElementById(`widget${key}`) || document.getElementById(`widget${key.replace(/_/g, '')}`);
                if (input) {
                    if (input.type === 'checkbox') {
                        input.checked = value;
                    } else {
                        input.value = value;
                    }
                }
            });
        }, 100);
    }
    
    duplicateWidget(widgetId) {
        const gridItem = document.querySelector(`[gs-id="${widgetId}"]`);
        if (!gridItem) return;
        
        const node = gridItem.gridstackNode;
        const widgetType = gridItem.dataset.widgetType;
        const currentConfig = JSON.parse(gridItem.dataset.config || '{}');
        
        // Create new widget
        const newWidgetId = 'widget_' + Date.now() + '_copy';
        const newGridItem = {
            x: Math.min(node.x + 1, 11),
            y: node.y + 1,
            w: node.w,
            h: node.h,
            content: this.createWidgetHTML(newWidgetId, widgetType, currentConfig.title + ' (Copy)'),
            id: newWidgetId
        };
        
        const addedItem = this.grid.addWidget(newGridItem);
        addedItem.setAttribute('gs-id', newWidgetId);
        addedItem.dataset.widgetType = widgetType;
        addedItem.dataset.config = JSON.stringify({...currentConfig, title: currentConfig.title + ' (Copy)'});
        
        this.animateWidgetIn(addedItem);
        this.markDirty();
    }
    
    removeWidget(widgetId) {
        if (confirm('Are you sure you want to remove this widget?')) {
            const gridItem = document.querySelector(`[gs-id="${widgetId}"]`);
            if (gridItem) {
                this.animateWidgetOut(gridItem, () => {
                    this.grid.removeWidget(gridItem);
                });
            }
        }
    }
    
    saveWidgetConfig() {
        if (!this.currentWidgetId) return;
        
        const gridItem = document.querySelector(`[gs-id="${this.currentWidgetId}"]`);
        if (!gridItem) return;
        
        // Collect configuration values
        const config = this.collectConfigValues();
        
        // Validate configuration
        const validation = this.validateConfig(config);
        if (!validation.valid) {
            this.showError(validation.message);
            return;
        }
        
        // Store configuration
        gridItem.dataset.config = JSON.stringify(config);
        
        // Update widget title
        const titleElement = gridItem.querySelector('h4');
        if (titleElement) {
            titleElement.textContent = config.title || 'Widget';
        }
        
        // Update widget content with loading state
        this.updateWidgetContent(this.currentWidgetId, config);
        
        this.hideModal('widgetConfigModal');
        this.markDirty();
    }
    
    collectConfigValues() {
        const config = {
            title: document.getElementById('widgetTitle')?.value || '',
            site_id: document.getElementById('widgetSite')?.value || '',
            auto_refresh: document.getElementById('widgetAutoRefresh')?.checked || false,
            refresh_interval: parseInt(document.getElementById('widgetRefreshInterval')?.value) || 60
        };
        
        // Collect widget-specific settings
        document.querySelectorAll('#widget-config-content select, #widget-config-content input').forEach(input => {
            if (input.id && !['widgetTitle', 'widgetSite', 'widgetAutoRefresh', 'widgetRefreshInterval'].includes(input.id)) {
                const key = input.id.replace(/^widget/, '').toLowerCase().replace(/([A-Z])/g, '_$1').toLowerCase();
                config[key] = input.type === 'checkbox' ? input.checked : input.value;
            }
        });
        
        return config;
    }
    
    validateConfig(config) {
        if (!config.title.trim()) {
            return { valid: false, message: 'Widget title is required' };
        }
        
        if (!config.site_id && !config.funnel_id) {
            return { valid: false, message: 'Please select a data source (site or funnel)' };
        }
        
        return { valid: true };
    }
    
    updateWidgetContent(widgetId, config) {
        const contentElement = document.querySelector(`.widget-content-${widgetId}`);
        if (!contentElement) return;
        
        // Show loading state
        contentElement.innerHTML = `
            <div class="text-center text-white">
                <svg class="animate-spin w-6 h-6 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <div class="text-sm">Loading data...</div>
            </div>
        `;
        
        // Simulate data loading (in real implementation, fetch actual data)
        setTimeout(() => {
            const mockData = this.generateMockData(config);
            contentElement.innerHTML = `
                <div class="text-center text-white">
                    <div class="text-2xl font-bold mono-data">${mockData.value}</div>
                    <div class="text-xs text-gray-400">${mockData.label}</div>
                    ${mockData.trend ? `<div class="text-xs ${mockData.trend > 0 ? 'text-green-400' : 'text-red-400'}">${mockData.trend > 0 ? '+' : ''}${mockData.trend}%</div>` : ''}
                </div>
            `;
        }, 1000);
    }
    
    generateMockData(config) {
        const baseValue = Math.floor(Math.random() * 10000) + 100;
        const trend = Math.floor(Math.random() * 40) - 20; // -20 to +20
        
        return {
            value: baseValue.toLocaleString(),
            label: config.metric_type || 'Data',
            trend: trend
        };
    }
    
    saveDashboard() {
        const name = document.getElementById('dashboardName')?.value.trim();
        if (!name) {
            this.showError('Please enter a dashboard name');
            return;
        }
        
        const widgets = this.grid.getGridItems();
        if (widgets.length === 0) {
            this.showError('Please add at least one widget to your dashboard');
            return;
        }
        
        // Show saving state
        const saveBtn = document.getElementById('saveDashboard');
        const originalText = saveBtn.textContent;
        saveBtn.textContent = 'Saving...';
        saveBtn.disabled = true;
        
        // Collect dashboard data
        const dashboardPayload = {
            id: this.dashboardData?.id || null,
            name: name,
            description: document.getElementById('dashboardDescription')?.value.trim() || '',
            layout: this.collectLayoutData(),
            widgets: this.collectWidgetData(),
            settings: {
                columns: 12,
                auto_save: true
            },
            is_shared: document.getElementById('isShared')?.checked || false
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
                this.showSuccess('Dashboard saved successfully!');
                this.isDirty = false;
                
                // Redirect to dashboard view after a short delay
                setTimeout(() => {
                    window.location.href = `/dashboard/custom/view?id=${data.dashboard_id}`;
                }, 1500);
            } else {
                throw new Error(data.error || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showError('Error saving dashboard: ' + error.message);
        })
        .finally(() => {
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;
        });
    }
    
    collectLayoutData() {
        return this.grid.getGridItems().map(item => {
            const node = item.gridstackNode;
            return {
                id: item.getAttribute('gs-id'),
                x: node.x,
                y: node.y,
                w: node.w,
                h: node.h
            };
        });
    }
    
    collectWidgetData() {
        return this.grid.getGridItems().map(item => {
            const node = item.gridstackNode;
            const config = JSON.parse(item.dataset.config || '{}');
            
            return {
                id: item.getAttribute('gs-id'),
                type: item.dataset.widgetType,
                title: config.title || 'Widget',
                settings: config,
                x: node.x,
                y: node.y,
                w: node.w,
                h: node.h
            };
        });
    }
    
    previewDashboard() {
        const widgets = this.grid.getGridItems();
        if (widgets.length === 0) {
            this.showError('Add some widgets to preview the dashboard');
            return;
        }
        
        // Create preview modal or new window
        this.showPreviewModal();
    }
    
    showPreviewModal() {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg w-full max-w-6xl mx-4 h-5/6 flex flex-col">
                <div class="flex items-center justify-between p-4 border-b border-crypto-border">
                    <h3 class="text-lg font-medium text-white">Dashboard Preview</h3>
                    <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-white">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
                <div class="flex-1 p-4 overflow-auto">
                    <div class="text-center text-gray-400 py-20">
                        <svg class="w-16 h-16 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                        </svg>
                        <h4 class="text-lg font-medium text-white mb-2">Preview Coming Soon</h4>
                        <p>Full dashboard preview with live data will be available in the next update.</p>
                        <p class="mt-2 text-sm">Current widgets: ${this.grid.getGridItems().length}</p>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    }
    
    // Utility methods
    markDirty() {
        this.isDirty = true;
        
        // Update save button
        const saveBtn = document.getElementById('saveDashboard');
        if (saveBtn && !saveBtn.textContent.includes('*')) {
            saveBtn.innerHTML = saveBtn.innerHTML.replace('Save Dashboard', 'Save Dashboard *');
        }
    }
    
    setupAutosave() {
        this.autosaveInterval = setInterval(() => {
            if (this.isDirty && this.grid.getGridItems().length > 0) {
                this.autoSave();
            }
        }, 30000); // Autosave every 30 seconds
    }
    
    autoSave() {
        const name = document.getElementById('dashboardName')?.value.trim();
        if (!name) return;
        
        // Only autosave if we have an existing dashboard ID
        if (!this.dashboardData?.id) return;
        
        const payload = {
            id: this.dashboardData.id,
            name: name,
            description: document.getElementById('dashboardDescription')?.value.trim() || '',
            layout: this.collectLayoutData(),
            widgets: this.collectWidgetData(),
            settings: { columns: 12, auto_save: true },
            is_shared: document.getElementById('isShared')?.checked || false
        };
        
        fetch('/dashboard/custom/save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.isDirty = false;
                this.showNotification('Auto-saved', 'success');
            }
        })
        .catch(error => {
            console.error('Autosave failed:', error);
        });
    }
    
    updateEmptyState() {
        const hasWidgets = this.grid.getGridItems().length > 0;
        const emptyState = document.getElementById('empty-state');
        
        if (emptyState) {
            emptyState.classList.toggle('hidden', hasWidgets);
        }
    }
    
    showDropZones() {
        // Add visual drop zones
        const gridContainer = document.getElementById('dashboard-grid');
        if (gridContainer) {
            gridContainer.classList.add('drag-active');
        }
    }
    
    hideDropZones() {
        const gridContainer = document.getElementById('dashboard-grid');
        if (gridContainer) {
            gridContainer.classList.remove('drag-active');
        }
    }
    
    animateWidgetIn(element) {
        element.style.opacity = '0';
        element.style.transform = 'scale(0.8)';
        element.style.transition = 'all 0.3s ease';
        
        setTimeout(() => {
            element.style.opacity = '1';
            element.style.transform = 'scale(1)';
        }, 10);
    }
    
    animateWidgetOut(element, callback) {
        element.style.transition = 'all 0.3s ease';
        element.style.opacity = '0';
        element.style.transform = 'scale(0.8)';
        
        setTimeout(callback, 300);
    }
    
    setupModalListeners() {
        // Close modal when clicking outside
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('fixed') && e.target.classList.contains('inset-0')) {
                this.hideModal(e.target.id);
            }
        });
        
        // ESC key to close modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideModal('widgetConfigModal');
            }
        });
    }
    
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch (e.key) {
                    case 's':
                        e.preventDefault();
                        this.saveDashboard();
                        break;
                    case 'z':
                        e.preventDefault();
                        // Undo functionality could be added here
                        break;
                }
            }
        });
    }
    
    initializeTooltips() {
        // Add tooltips to buttons and interactive elements
        document.querySelectorAll('[title]').forEach(element => {
            element.addEventListener('mouseenter', this.showTooltip);
            element.addEventListener('mouseleave', this.hideTooltip);
        });
    }
    
    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }
    
    hideModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        this.currentWidgetId = null;
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
    
    showSuccess(message) {
        this.showNotification(message, 'success');
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-crypto border z-50 ${
            type === 'success' ? 'bg-green-900 border-green-600 text-green-100' :
            type === 'error' ? 'bg-red-900 border-red-600 text-red-100' :
            'bg-crypto-dark border-crypto-border text-white'
        }`;
        
        notification.innerHTML = `
            <div class="flex items-center space-x-2">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="text-current opacity-70 hover:opacity-100">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
}

// Global instance and helper functions
let dashboardBuilder;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    dashboardBuilder = new DashboardBuilder();
});

// Global functions for widget management (called from HTML)
function closeConfigModal() {
    dashboardBuilder?.hideModal('widgetConfigModal');
}

function saveWidgetConfig() {
    dashboardBuilder?.saveWidgetConfig();
}