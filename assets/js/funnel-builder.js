/**
 * Funnel Builder JavaScript
 * Drag-drop funnel builder with step configuration and live preview
 */

class FunnelBuilder {
    constructor() {
        this.steps = [];
        this.currentStepId = null;
        this.stepCounter = 0;
        this.previewChart = null;
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadExistingFunnel();
        this.updateUI();
    }
    
    setupEventListeners() {
        // Step template buttons
        document.querySelectorAll('.step-template').forEach(btn => {
            btn.addEventListener('click', () => {
                const stepType = btn.dataset.type;
                this.addStep(stepType);
            });
        });
        
        // Popular pages and events
        document.querySelectorAll('.popular-page').forEach(item => {
            item.addEventListener('click', () => {
                this.addPageViewStep(item.dataset.path);
            });
        });
        
        document.querySelectorAll('.popular-event').forEach(item => {
            item.addEventListener('click', () => {
                this.addEventStep(item.dataset.name, item.dataset.category);
            });
        });
        
        // Save funnel button
        document.getElementById('saveFunnel').addEventListener('click', () => {
            this.saveFunnel();
        });
        
        // Preview button
        document.getElementById('previewFunnel').addEventListener('click', () => {
            this.togglePreview();
        });
        
        // Save step modal
        document.getElementById('saveStepBtn').addEventListener('click', () => {
            this.saveCurrentStep();
        });
    }
    
    loadExistingFunnel() {
        if (window.funnelBuilderData.funnelSteps && window.funnelBuilderData.funnelSteps.length > 0) {
            this.steps = window.funnelBuilderData.funnelSteps.map(step => ({
                id: `step-${++this.stepCounter}`,
                name: step.name,
                type: step.step_type,
                conditions: step.conditions,
                isRequired: step.is_required,
                order: step.step_order
            }));
            
            this.renderSteps();
        }
    }
    
    addStep(type) {
        const step = {
            id: `step-${++this.stepCounter}`,
            name: `Step ${this.steps.length + 1}`,
            type: type,
            conditions: {},
            isRequired: true,
            order: this.steps.length + 1
        };
        
        this.steps.push(step);
        this.renderSteps();
        this.openStepModal(step.id);
    }
    
    addPageViewStep(pagePath) {
        const step = {
            id: `step-${++this.stepCounter}`,
            name: `Visit ${pagePath}`,
            type: 'pageview',
            conditions: { page_path: pagePath },
            isRequired: true,
            order: this.steps.length + 1
        };
        
        this.steps.push(step);
        this.renderSteps();
    }
    
    addEventStep(eventName, eventCategory) {
        const step = {
            id: `step-${++this.stepCounter}`,
            name: eventName,
            type: 'event',
            conditions: { 
                event_name: eventName,
                event_category: eventCategory || null
            },
            isRequired: true,
            order: this.steps.length + 1
        };
        
        this.steps.push(step);
        this.renderSteps();
    }
    
    renderSteps() {
        const container = document.getElementById('funnelSteps');
        const emptyState = document.getElementById('emptyState');
        
        if (this.steps.length === 0) {
            emptyState.style.display = 'block';
            container.innerHTML = '';
        } else {
            emptyState.style.display = 'none';
            
            container.innerHTML = this.steps.map((step, index) => {
                return this.renderStepCard(step, index);
            }).join('');
            
            // Add event listeners to step cards
            this.setupStepCardListeners();
        }
        
        this.updateStepCount();
    }
    
    renderStepCard(step, index) {
        const stepNumber = index + 1;
        const isLast = index === this.steps.length - 1;
        
        const typeConfig = {
            pageview: { 
                color: 'blue', 
                icon: 'M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0h8v12H6V4z',
                label: 'Page View'
            },
            event: { 
                color: 'green', 
                icon: 'M6.672 1.911a1 1 0 10-1.932.518l.259.966a1 1 0 001.932-.518l-.26-.966zM2.429 4.74a1 1 0 10-.517 1.932l.966.259a1 1 0 00.517-1.932l-.966-.26zm8.814-.569a1 1 0 00-1.415-1.414l-.707.707a1 1 0 101.415 1.414l.707-.707zm-7.071 7.072l.707-.707A1 1 0 003.465 9.12l-.708.707a1 1 0 001.415 1.414zm3.2-5.171a1 1 0 00-1.3 1.3l4 10a1 1 0 001.823.075l1.38-2.759 3.018 3.02a1 1 0 001.414-1.415l-3.019-3.02 2.76-1.379a1 1 0 00-.076-1.822l-10-4z',
                label: 'Event'
            },
            custom: { 
                color: 'purple', 
                icon: 'M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z',
                label: 'Custom'
            }
        };
        
        const config = typeConfig[step.type] || typeConfig.custom;
        
        return `
            <div class="step-card relative" data-step-id="${step.id}">
                <div class="bg-crypto-gray border border-crypto-border rounded-crypto-lg p-4 cursor-pointer hover:border-${config.color}-500/50 transition-all group">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <!-- Step Number -->
                            <div class="w-10 h-10 bg-${config.color}-500/20 rounded-crypto flex items-center justify-center text-${config.color}-500 font-bold mono-data">
                                ${stepNumber}
                            </div>
                            
                            <!-- Step Info -->
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-1">
                                    <h4 class="font-medium text-white">${step.name}</h4>
                                    <span class="text-xs px-2 py-1 bg-${config.color}-500/20 text-${config.color}-400 rounded-crypto">
                                        ${config.label}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-400">
                                    ${this.getStepDescription(step)}
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button class="edit-step p-1.5 text-gray-400 hover:text-white rounded transition-colors" 
                                    data-step-id="${step.id}" title="Edit step">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                </svg>
                            </button>
                            
                            ${index > 0 ? `
                                <button class="move-up p-1.5 text-gray-400 hover:text-white rounded transition-colors"
                                        data-step-id="${step.id}" title="Move up">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L10 15.586l3.293-3.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            ` : ''}
                            
                            ${!isLast ? `
                                <button class="move-down p-1.5 text-gray-400 hover:text-white rounded transition-colors"
                                        data-step-id="${step.id}" title="Move down">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L10 4.414 6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            ` : ''}
                            
                            <button class="delete-step p-1.5 text-gray-400 hover:text-red-500 rounded transition-colors"
                                    data-step-id="${step.id}" title="Delete step">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd"></path>
                                    <path fill-rule="evenodd" d="M4 5a1 1 0 011-1h10a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM5 9a1 1 0 011-1h8a1 1 0 011 1v6a2 2 0 01-2 2H7a2 2 0 01-2-2V9z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                ${!isLast ? `
                    <!-- Flow Arrow -->
                    <div class="flex justify-center my-4">
                        <div class="w-8 h-8 bg-crypto-dark border border-crypto-border rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L10 4.414 6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }
    
    getStepDescription(step) {
        switch (step.type) {
            case 'pageview':
                return step.conditions.page_path || 'Any page';
            case 'event':
                const eventName = step.conditions.event_name || 'Any event';
                const category = step.conditions.event_category;
                return category ? `${eventName} (${category})` : eventName;
            case 'custom':
                return 'Custom conditions';
            default:
                return 'Not configured';
        }
    }
    
    setupStepCardListeners() {
        // Edit buttons
        document.querySelectorAll('.edit-step').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.openStepModal(btn.dataset.stepId);
            });
        });
        
        // Delete buttons
        document.querySelectorAll('.delete-step').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.deleteStep(btn.dataset.stepId);
            });
        });
        
        // Move buttons
        document.querySelectorAll('.move-up').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.moveStep(btn.dataset.stepId, 'up');
            });
        });
        
        document.querySelectorAll('.move-down').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.moveStep(btn.dataset.stepId, 'down');
            });
        });
        
        // Click on card to edit
        document.querySelectorAll('.step-card').forEach(card => {
            card.addEventListener('click', () => {
                this.openStepModal(card.dataset.stepId);
            });
        });
    }
    
    openStepModal(stepId) {
        this.currentStepId = stepId;
        const step = this.steps.find(s => s.id === stepId);
        if (!step) return;
        
        const modal = document.getElementById('stepModal');
        const title = document.getElementById('modalTitle');
        const content = document.getElementById('modalContent');
        
        title.textContent = `Configure ${step.name}`;
        content.innerHTML = this.getStepModalContent(step);
        
        modal.classList.remove('hidden');
        
        // Setup modal-specific listeners
        this.setupModalListeners(step);
    }
    
    getStepModalContent(step) {
        switch (step.type) {
            case 'pageview':
                return this.getPageViewModalContent(step);
            case 'event':
                return this.getEventModalContent(step);
            case 'custom':
                return this.getCustomModalContent(step);
            default:
                return '<p class="text-gray-400">Unknown step type</p>';
        }
    }
    
    getPageViewModalContent(step) {
        return `
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Step Name</label>
                    <input type="text" id="stepName" 
                           class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white"
                           value="${step.name}" placeholder="Enter step name">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Page Path</label>
                    <input type="text" id="pagePath" 
                           class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white"
                           value="${step.conditions.page_path || ''}" placeholder="e.g., /signup, /checkout/*">
                    <p class="text-xs text-gray-400 mt-1">Use * for wildcards. Example: /product/* matches all product pages</p>
                </div>
                
                <div class="flex items-center space-x-3">
                    <input type="checkbox" id="isRequired" ${step.isRequired ? 'checked' : ''}
                           class="w-4 h-4 text-crypto-blue bg-crypto-gray border-crypto-border rounded focus:ring-crypto-blue">
                    <label for="isRequired" class="text-sm text-gray-300">Required step (users must complete this to continue)</label>
                </div>
            </div>
        `;
    }
    
    getEventModalContent(step) {
        return `
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Step Name</label>
                    <input type="text" id="stepName" 
                           class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white"
                           value="${step.name}" placeholder="Enter step name">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Event Name</label>
                    <input type="text" id="eventName" 
                           class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white"
                           value="${step.conditions.event_name || ''}" placeholder="e.g., button_click, form_submit">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Event Category (optional)</label>
                    <input type="text" id="eventCategory" 
                           class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white"
                           value="${step.conditions.event_category || ''}" placeholder="e.g., engagement, conversion">
                </div>
                
                <div class="flex items-center space-x-3">
                    <input type="checkbox" id="isRequired" ${step.isRequired ? 'checked' : ''}
                           class="w-4 h-4 text-crypto-blue bg-crypto-gray border-crypto-border rounded focus:ring-crypto-blue">
                    <label for="isRequired" class="text-sm text-gray-300">Required step (users must complete this to continue)</label>
                </div>
            </div>
        `;
    }
    
    getCustomModalContent(step) {
        return `
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Step Name</label>
                    <input type="text" id="stepName" 
                           class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white"
                           value="${step.name}" placeholder="Enter step name">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Custom Conditions (JSON)</label>
                    <textarea id="customConditions" rows="6"
                              class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white font-mono text-sm"
                              placeholder='{"page_path": "/checkout", "event_name": "purchase"}'>${JSON.stringify(step.conditions, null, 2)}</textarea>
                    <p class="text-xs text-gray-400 mt-1">Define custom matching conditions in JSON format</p>
                </div>
                
                <div class="flex items-center space-x-3">
                    <input type="checkbox" id="isRequired" ${step.isRequired ? 'checked' : ''}
                           class="w-4 h-4 text-crypto-blue bg-crypto-gray border-crypto-border rounded focus:ring-crypto-blue">
                    <label for="isRequired" class="text-sm text-gray-300">Required step (users must complete this to continue)</label>
                </div>
            </div>
        `;
    }
    
    setupModalListeners(step) {
        // Auto-update step name as user types
        const stepNameInput = document.getElementById('stepName');
        if (stepNameInput) {
            stepNameInput.addEventListener('input', () => {
                step.name = stepNameInput.value;
            });
        }
    }
    
    saveCurrentStep() {
        const step = this.steps.find(s => s.id === this.currentStepId);
        if (!step) return;
        
        // Get values from modal
        const stepName = document.getElementById('stepName')?.value || step.name;
        const isRequired = document.getElementById('isRequired')?.checked || false;
        
        step.name = stepName;
        step.isRequired = isRequired;
        
        // Step-specific conditions
        switch (step.type) {
            case 'pageview':
                const pagePath = document.getElementById('pagePath')?.value;
                step.conditions = { page_path: pagePath };
                break;
                
            case 'event':
                const eventName = document.getElementById('eventName')?.value;
                const eventCategory = document.getElementById('eventCategory')?.value;
                step.conditions = { 
                    event_name: eventName,
                    event_category: eventCategory || null
                };
                break;
                
            case 'custom':
                try {
                    const customConditions = document.getElementById('customConditions')?.value;
                    step.conditions = JSON.parse(customConditions || '{}');
                } catch (e) {
                    alert('Invalid JSON in custom conditions');
                    return;
                }
                break;
        }
        
        this.closeStepModal();
        this.renderSteps();
    }
    
    closeStepModal() {
        document.getElementById('stepModal').classList.add('hidden');
        this.currentStepId = null;
    }
    
    deleteStep(stepId) {
        if (confirm('Are you sure you want to delete this step?')) {
            this.steps = this.steps.filter(s => s.id !== stepId);
            this.reorderSteps();
            this.renderSteps();
        }
    }
    
    moveStep(stepId, direction) {
        const index = this.steps.findIndex(s => s.id === stepId);
        if (index === -1) return;
        
        const newIndex = direction === 'up' ? index - 1 : index + 1;
        
        if (newIndex >= 0 && newIndex < this.steps.length) {
            // Swap steps
            [this.steps[index], this.steps[newIndex]] = [this.steps[newIndex], this.steps[index]];
            this.reorderSteps();
            this.renderSteps();
        }
    }
    
    reorderSteps() {
        this.steps.forEach((step, index) => {
            step.order = index + 1;
        });
    }
    
    updateStepCount() {
        document.getElementById('stepCount').textContent = this.steps.length;
    }
    
    updateUI() {
        this.updateStepCount();
    }
    
    togglePreview() {
        const previewSection = document.getElementById('livePreview');
        const isVisible = previewSection.style.display !== 'none';
        
        if (isVisible) {
            previewSection.style.display = 'none';
        } else {
            previewSection.style.display = 'block';
            this.generatePreview();
        }
    }
    
    generatePreview() {
        if (this.steps.length === 0) return;
        
        // Simulate funnel data for preview
        const previewData = this.steps.map((step, index) => {
            const dropoffRate = Math.random() * 30 + 10; // 10-40% dropoff
            const conversionRate = index === 0 ? 100 : (100 - dropoffRate);
            const userCount = Math.floor(1000 * (conversionRate / 100));
            
            return {
                name: step.name,
                user_count: userCount,
                conversion_rate: conversionRate,
                dropoff_rate: dropoffRate
            };
        });
        
        this.renderPreviewChart(previewData);
    }
    
    renderPreviewChart(data) {
        const options = {
            series: [{
                name: 'Users',
                data: data.map(step => step.user_count)
            }],
            chart: {
                type: 'bar',
                height: 250,
                background: 'transparent',
                toolbar: { show: false }
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
                theme: 'dark'
            },
            legend: { show: false }
        };
        
        if (this.previewChart) {
            this.previewChart.destroy();
        }
        
        this.previewChart = new ApexCharts(document.querySelector('#previewChart'), options);
        this.previewChart.render();
    }
    
    async saveFunnel() {
        const name = document.getElementById('funnelName').value.trim();
        const description = document.getElementById('funnelDescription').value.trim();
        
        if (!name) {
            alert('Please enter a funnel name');
            return;
        }
        
        if (this.steps.length < 2) {
            alert('Funnel must have at least 2 steps');
            return;
        }
        
        const funnelData = {
            site_id: window.funnelBuilderData.siteId,
            name: name,
            description: description,
            steps: this.steps.map(step => ({
                name: step.name,
                type: step.type,
                conditions: step.conditions,
                is_required: step.isRequired
            }))
        };
        
        // Add funnel_id if editing existing funnel
        if (window.funnelBuilderData.funnel) {
            funnelData.funnel_id = window.funnelBuilderData.funnel.id;
        }
        
        try {
            const response = await fetch('/api/funnels/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(funnelData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Funnel saved successfully!');
                window.location.href = `/dashboard/funnels?site=${window.funnelBuilderData.siteId}`;
            } else {
                alert(`Error: ${result.error}`);
            }
        } catch (error) {
            console.error('Save error:', error);
            alert('Failed to save funnel');
        }
    }
}

// Global functions for modal
function closeStepModal() {
    if (window.funnelBuilder) {
        window.funnelBuilder.closeStepModal();
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.funnelBuilder = new FunnelBuilder();
});