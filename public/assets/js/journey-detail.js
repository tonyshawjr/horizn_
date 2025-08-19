/**
 * Journey Detail JavaScript
 * 
 * Handles interactive features for the journey detail view
 */

class JourneyDetail {
    constructor() {
        this.currentPersonId = null;
        this.currentSiteId = null;
        this.liveUpdatesEnabled = true;
        this.updateInterval = null;
        
        this.init();
    }
    
    init() {
        // Get IDs from URL or data attributes
        this.extractIds();
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Start live updates if enabled
        if (this.liveUpdatesEnabled) {
            this.startLiveUpdates();
        }
    }
    
    extractIds() {
        // Extract from URL path (assuming /dashboard/journeys/detail/{person_id})
        const pathParts = window.location.pathname.split('/');
        if (pathParts.includes('detail') && pathParts.length > pathParts.indexOf('detail') + 1) {
            this.currentPersonId = pathParts[pathParts.indexOf('detail') + 1];
        }
        
        // Get site ID from URL params or site selector
        const urlParams = new URLSearchParams(window.location.search);
        this.currentSiteId = urlParams.get('site') || 
                           document.getElementById('siteSelector')?.value;
    }
    
    setupEventListeners() {
        // Live updates toggle
        const liveUpdatesCheckbox = document.getElementById('liveUpdates');
        if (liveUpdatesCheckbox) {
            liveUpdatesCheckbox.addEventListener('change', (e) => {
                this.liveUpdatesEnabled = e.target.checked;
                if (this.liveUpdatesEnabled) {
                    this.startLiveUpdates();
                } else {
                    this.stopLiveUpdates();
                }
            });
        }
        
        // Timeline item clicks (for expanding/collapsing details)
        document.addEventListener('click', (e) => {
            if (e.target.closest('.timeline-item-expandable')) {
                this.toggleTimelineItem(e.target.closest('.timeline-item-expandable'));
            }
        });
        
        // Export functionality
        const exportButton = document.querySelector('[onclick*="exportJourney"]');
        if (exportButton) {
            exportButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.exportJourney();
            });
        }
    }
    
    startLiveUpdates() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
        
        // Update every 30 seconds
        this.updateInterval = setInterval(() => {
            if (this.liveUpdatesEnabled && this.currentPersonId && this.currentSiteId) {
                this.updateJourneyData();
            }
        }, 30000);
        
        // Show live indicator
        this.showLiveIndicator();
    }
    
    stopLiveUpdates() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }
        
        // Hide live indicator
        this.hideLiveIndicator();
    }
    
    async updateJourneyData() {
        if (!this.currentPersonId || !this.currentSiteId) return;
        
        try {
            const response = await fetch(`/api/journeys/detail/${this.currentPersonId}?site=${this.currentSiteId}`);
            const data = await response.json();
            
            if (data.success && data.journey) {
                // Check if there are new timeline items
                const currentTimelineItems = document.querySelectorAll('.timeline-item').length;
                if (data.journey.timeline.length > currentTimelineItems) {
                    // New activity detected - show notification
                    this.showNewActivityNotification(data.journey.timeline.length - currentTimelineItems);
                }
                
                // Update statistics
                this.updateStatistics(data.journey);
            }
        } catch (error) {
            console.error('Failed to update journey data:', error);
            this.showErrorNotification('Failed to update journey data');
        }
    }
    
    updateStatistics(journey) {
        // Update the stats cards
        const statElements = {
            'totalSessions': journey.sessions?.length || 0,
            'totalPages': journey.pageviews?.length || 0,
            'totalEvents': journey.events?.length || 0,
            'totalDuration': this.formatDuration(journey.total_duration || 0)
        };
        
        Object.entries(statElements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        });
    }
    
    toggleTimelineItem(item) {
        const details = item.querySelector('.timeline-item-details');
        if (details) {
            details.classList.toggle('hidden');
            
            // Update expand/collapse icon if present
            const icon = item.querySelector('.expand-icon');
            if (icon) {
                icon.classList.toggle('rotate-180');
            }
        }
    }
    
    async exportJourney() {
        if (!this.currentPersonId || !this.currentSiteId) return;
        
        const format = 'json'; // Could be made configurable
        const url = `/api/journeys/export/${this.currentPersonId}?site=${this.currentSiteId}&format=${format}`;
        
        try {
            // Open in new window for download
            window.open(url, '_blank');
            
            // Show success notification
            this.showSuccessNotification('Journey export initiated');
        } catch (error) {
            console.error('Failed to export journey:', error);
            this.showErrorNotification('Failed to export journey data');
        }
    }
    
    showLiveIndicator() {
        const indicator = document.querySelector('.live-indicator');
        if (indicator) {
            indicator.classList.remove('hidden');
            indicator.classList.add('animate-pulse');
        }
    }
    
    hideLiveIndicator() {
        const indicator = document.querySelector('.live-indicator');
        if (indicator) {
            indicator.classList.add('hidden');
            indicator.classList.remove('animate-pulse');
        }
    }
    
    showNewActivityNotification(count) {
        this.showNotification(`${count} new activity detected. Refresh to see updates.`, 'info', () => {
            window.location.reload();
        });\n    }\n    \n    showSuccessNotification(message) {\n        this.showNotification(message, 'success');\n    }\n    \n    showErrorNotification(message) {\n        this.showNotification(message, 'error');\n    }\n    \n    showNotification(message, type = 'info', action = null) {\n        // Create notification element\n        const notification = document.createElement('div');\n        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-crypto-lg shadow-lg max-w-sm transition-all transform translate-x-full`;\n        \n        // Style based on type\n        switch (type) {\n            case 'success':\n                notification.classList.add('bg-green-600', 'text-white');\n                break;\n            case 'error':\n                notification.classList.add('bg-red-600', 'text-white');\n                break;\n            case 'info':\n            default:\n                notification.classList.add('bg-crypto-blue', 'text-white');\n                break;\n        }\n        \n        // Add content\n        const content = document.createElement('div');\n        content.className = 'flex items-center justify-between';\n        \n        const messageEl = document.createElement('p');\n        messageEl.className = 'text-sm font-medium';\n        messageEl.textContent = message;\n        \n        const closeBtn = document.createElement('button');\n        closeBtn.className = 'ml-4 text-white hover:text-gray-200';\n        closeBtn.innerHTML = '×';\n        closeBtn.onclick = () => this.hideNotification(notification);\n        \n        content.appendChild(messageEl);\n        content.appendChild(closeBtn);\n        notification.appendChild(content);\n        \n        // Add action button if provided\n        if (action) {\n            const actionBtn = document.createElement('button');\n            actionBtn.className = 'mt-2 w-full bg-white bg-opacity-20 hover:bg-opacity-30 rounded px-3 py-1 text-sm transition-colors';\n            actionBtn.textContent = 'Refresh';\n            actionBtn.onclick = action;\n            notification.appendChild(actionBtn);\n        }\n        \n        // Add to page\n        document.body.appendChild(notification);\n        \n        // Animate in\n        setTimeout(() => {\n            notification.classList.remove('translate-x-full');\n        }, 100);\n        \n        // Auto-hide after 5 seconds\n        setTimeout(() => {\n            this.hideNotification(notification);\n        }, 5000);\n    }\n    \n    hideNotification(notification) {\n        notification.classList.add('translate-x-full');\n        setTimeout(() => {\n            if (notification.parentNode) {\n                notification.parentNode.removeChild(notification);\n            }\n        }, 300);\n    }\n    \n    formatDuration(seconds) {\n        if (seconds < 60) {\n            return `${seconds}s`;\n        } else if (seconds < 3600) {\n            const minutes = Math.floor(seconds / 60);\n            const remainingSeconds = seconds % 60;\n            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;\n        } else {\n            const hours = Math.floor(seconds / 3600);\n            const minutes = Math.floor((seconds % 3600) / 60);\n            const remainingSeconds = seconds % 60;\n            return `${hours}:${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;\n        }\n    }\n}\n\n// Initialize when DOM is loaded\ndocument.addEventListener('DOMContentLoaded', () => {\n    new JourneyDetail();\n});

// Global functions for backward compatibility\nfunction exportJourney(personId) {\n    if (window.journeyDetail) {\n        window.journeyDetail.exportJourney();\n    }\n}\n\nfunction toggleJourneyDetails(journeyId) {\n    const details = document.getElementById(journeyId);\n    if (details) {\n        details.classList.toggle('hidden');\n        \n        // Update expand/collapse state on parent\n        const item = details.closest('.journey-timeline-item');\n        if (item) {\n            item.classList.toggle('expanded');\n        }\n    }\n}