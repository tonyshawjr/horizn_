/**
 * horizn_ Analytics Dashboard JavaScript
 * Handles real-time updates, charts, and interactive features
 */

// Global chart instances
let trafficChart = null;
let deviceChart = null;
let geoChart = null;

// Auto-refresh intervals
let liveUpdateInterval = null;
let chartUpdateInterval = null;

/**
 * Initialize dashboard when DOM is loaded
 */
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
});

/**
 * Main dashboard initialization
 */
function initializeDashboard() {
    // Initialize charts if elements exist
    if (document.getElementById('trafficChart')) {
        initializeTrafficChart();
    }
    
    if (document.getElementById('deviceChart')) {
        initializeDeviceChart();
    }
    
    // Start live updates
    startLiveUpdates();
    
    // Handle date range changes
    setupDateRangeHandler();
    
    // Initialize any existing sparklines
    initializeSparklines();
}

/**
 * Initialize traffic overview chart
 */
function initializeTrafficChart() {
    const options = {
        series: [{
            name: 'Pageviews',
            data: generateSampleTrafficData()
        }, {
            name: 'Sessions',
            data: generateSampleSessionData()
        }],
        chart: {
            type: 'area',
            height: 300,
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
        colors: ['#3B82F6', '#8B5CF6'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 2
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.3,
                opacityTo: 0.1,
                stops: [0, 90, 100]
            }
        },
        xaxis: {
            type: 'datetime',
            labels: {
                style: {
                    colors: '#a0a0a0',
                    fontFamily: 'JetBrains Mono'
                },
                format: 'MMM dd'
            },
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            }
        },
        yaxis: {
            labels: {
                style: {
                    colors: '#a0a0a0',
                    fontFamily: 'JetBrains Mono'
                },
                formatter: function(val) {
                    return Math.round(val).toLocaleString();
                }
            }
        },
        grid: {
            borderColor: '#3a3a3a',
            strokeDashArray: 3,
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
        legend: {
            show: true,
            position: 'top',
            horizontalAlign: 'right',
            labels: {
                colors: '#e0e0e0'
            }
        },
        tooltip: {
            theme: 'dark',
            style: {
                fontSize: '12px',
                fontFamily: 'JetBrains Mono'
            },
            x: {
                format: 'MMM dd, yyyy'
            }
        }
    };

    trafficChart = new ApexCharts(document.querySelector("#trafficChart"), options);
    trafficChart.render();
}

/**
 * Initialize device breakdown chart
 */
function initializeDeviceChart() {
    const options = {
        series: [65, 25, 10],
        labels: ['Desktop', 'Mobile', 'Tablet'],
        chart: {
            type: 'donut',
            height: 300,
            background: 'transparent'
        },
        colors: ['#3B82F6', '#8B5CF6', '#10B981'],
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return Math.round(val) + '%';
            },
            style: {
                fontSize: '14px',
                fontFamily: 'JetBrains Mono',
                fontWeight: '600',
                colors: ['#ffffff']
            }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            fontSize: '18px',
                            fontFamily: 'Inter',
                            fontWeight: 600,
                            color: '#ffffff'
                        },
                        value: {
                            show: true,
                            fontSize: '24px',
                            fontFamily: 'JetBrains Mono',
                            fontWeight: 600,
                            color: '#3B82F6',
                            formatter: function(val) {
                                return Math.round(val) + '%';
                            }
                        },
                        total: {
                            show: true,
                            label: 'Total',
                            fontSize: '14px',
                            fontFamily: 'Inter',
                            color: '#a0a0a0',
                            formatter: function() {
                                return '100%';
                            }
                        }
                    }
                }
            }
        },
        legend: {
            show: true,
            position: 'bottom',
            labels: {
                colors: '#e0e0e0',
                useSeriesColors: false
            },
            markers: {
                width: 8,
                height: 8
            }
        },
        tooltip: {
            theme: 'dark',
            style: {
                fontSize: '12px',
                fontFamily: 'JetBrains Mono'
            }
        }
    };

    deviceChart = new ApexCharts(document.querySelector("#deviceChart"), options);
    deviceChart.render();
}

/**
 * Initialize sparkline charts for agency dashboard
 */
function initializeSparklines() {
    const sparklineElements = document.querySelectorAll('[id^="sparkline-"]');
    
    sparklineElements.forEach(element => {
        if (!element.hasAttribute('data-initialized')) {
            const siteId = element.id.replace('sparkline-', '');
            createSparkline(element, generateSampleSparklineData());
            element.setAttribute('data-initialized', 'true');
        }
    });
}

/**
 * Create a sparkline chart
 */
function createSparkline(element, data) {
    const options = {
        series: [{
            data: data
        }],
        chart: {
            type: 'line',
            width: '100%',
            height: 64,
            sparkline: {
                enabled: true
            },
            animations: {
                enabled: false
            }
        },
        stroke: {
            curve: 'smooth',
            width: 2,
            colors: ['#3B82F6']
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.3,
                opacityTo: 0.1,
                stops: [0, 100],
                colorStops: [{
                    offset: 0,
                    color: '#3B82F6',
                    opacity: 0.3
                }, {
                    offset: 100,
                    color: '#3B82F6',
                    opacity: 0.1
                }]
            }
        },
        markers: {
            size: 0
        },
        tooltip: {
            enabled: false
        }
    };
    
    const sparkline = new ApexCharts(element, options);
    sparkline.render();
}

/**
 * Start live data updates
 */
function startLiveUpdates() {
    // Update live stats every 30 seconds
    liveUpdateInterval = setInterval(() => {
        updateLiveStats();
    }, 30000);
    
    // Update charts every 5 minutes
    chartUpdateInterval = setInterval(() => {
        updateChartData();
    }, 300000);
}

/**
 * Update live visitor counts and other real-time stats
 */
function updateLiveStats() {
    // Determine if we're on agency or site dashboard
    const isAgencyDashboard = window.location.pathname.includes('/dashboard/agency');
    const endpoint = isAgencyDashboard ? '/api/live/agency-stats' : `/api/live/site-stats?site=${getSiteId()}`;
    
    fetch(endpoint)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateLiveElements(data, isAgencyDashboard);
            }
        })
        .catch(error => {
            console.log('Live update failed:', error);
        });
}

/**
 * Update DOM elements with live data
 */
function updateLiveElements(data, isAgencyDashboard) {
    if (isAgencyDashboard) {
        // Update agency dashboard elements
        updateElement('totalLiveVisitors', data.total_live_visitors);
        updateElement('totalPageviews', formatNumber(data.total_pageviews_today));
        
        // Update individual site live visitor counts
        if (data.sites) {
            data.sites.forEach(site => {
                const siteCard = document.querySelector(`[onclick*="site=${site.id}"]`);
                if (siteCard) {
                    const liveIndicator = siteCard.querySelector('.animate-pulse + span');
                    if (liveIndicator) {
                        liveIndicator.textContent = site.live_visitors;
                        
                        // Show/hide live indicator based on visitor count
                        const indicatorContainer = liveIndicator.parentElement;
                        if (site.live_visitors > 0) {
                            indicatorContainer.style.display = 'flex';
                        } else {
                            indicatorContainer.style.display = 'none';
                        }
                    }
                }
            });
        }
    } else {
        // Update site dashboard elements
        updateElement('liveVisitors', data.live_visitors);
    }
}

/**
 * Update chart data
 */
function updateChartData() {
    // Fetch new chart data
    const siteId = getSiteId();
    const period = getSelectedPeriod();
    
    fetch(`/api/stats/chart-data?site=${siteId}&period=${period}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update traffic chart
                if (trafficChart && data.traffic) {
                    trafficChart.updateSeries([
                        {
                            name: 'Pageviews',
                            data: data.traffic.pageviews
                        },
                        {
                            name: 'Sessions',
                            data: data.traffic.sessions
                        }
                    ]);
                }
                
                // Update device chart
                if (deviceChart && data.devices) {
                    deviceChart.updateSeries(data.devices.values);
                }
            }
        })
        .catch(error => {
            console.log('Chart update failed:', error);
        });
}

/**
 * Setup date range change handler
 */
function setupDateRangeHandler() {
    const dateRangeSelect = document.getElementById('dateRange');
    if (dateRangeSelect) {
        dateRangeSelect.addEventListener('change', function() {
            const period = this.value;
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('period', period);
            window.location.href = currentUrl.toString();
        });
    }
}

/**
 * Change chart time period
 */
function changeChartPeriod(period) {
    // Update UI to show active period
    document.querySelectorAll('[onclick*="changeChartPeriod"]').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn');
    });
    
    event.target.classList.remove('btn');
    event.target.classList.add('btn-primary');
    
    // Fetch new data for the period
    const siteId = getSiteId();
    
    fetch(`/api/stats/chart-data?site=${siteId}&chart_period=${period}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && trafficChart) {
                trafficChart.updateSeries([
                    {
                        name: 'Pageviews',
                        data: data.traffic.pageviews
                    },
                    {
                        name: 'Sessions',
                        data: data.traffic.sessions
                    }
                ]);
                
                // Update x-axis format based on period
                let xaxisFormat = 'MMM dd';
                if (period === 'hourly') {
                    xaxisFormat = 'HH:mm';
                } else if (period === 'weekly') {
                    xaxisFormat = 'MMM dd';
                }
                
                trafficChart.updateOptions({
                    xaxis: {
                        labels: {
                            format: xaxisFormat
                        }
                    }
                });
            }
        })
        .catch(error => {
            console.log('Chart period update failed:', error);
        });
}

/**
 * Utility functions
 */
function updateElement(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
    }
}

function formatNumber(num) {
    return new Intl.NumberFormat().format(num);
}

function getSiteId() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('site') || '';
}

function getSelectedPeriod() {
    const dateRangeSelect = document.getElementById('dateRange');
    return dateRangeSelect ? dateRangeSelect.value : '30d';
}

/**
 * Sample data generators for development/fallback
 */
function generateSampleTrafficData() {
    const data = [];
    const now = new Date();
    
    for (let i = 29; i >= 0; i--) {
        const date = new Date(now);
        date.setDate(date.getDate() - i);
        
        const baseValue = 1000;
        const variance = Math.random() * 500 - 250;
        const weekendMultiplier = date.getDay() === 0 || date.getDay() === 6 ? 0.7 : 1;
        
        data.push({
            x: date.getTime(),
            y: Math.round(Math.max(0, (baseValue + variance) * weekendMultiplier))
        });
    }
    
    return data;
}

function generateSampleSessionData() {
    const data = [];
    const now = new Date();
    
    for (let i = 29; i >= 0; i--) {
        const date = new Date(now);
        date.setDate(date.getDate() - i);
        
        const baseValue = 600;
        const variance = Math.random() * 300 - 150;
        const weekendMultiplier = date.getDay() === 0 || date.getDay() === 6 ? 0.6 : 1;
        
        data.push({
            x: date.getTime(),
            y: Math.round(Math.max(0, (baseValue + variance) * weekendMultiplier))
        });
    }
    
    return data;
}

function generateSampleSparklineData() {
    const data = [];
    const baseValue = 100;
    
    for (let i = 0; i < 24; i++) {
        const variance = Math.random() * 40 - 20;
        const timeMultiplier = Math.sin((i / 24) * Math.PI * 2) * 0.3 + 1;
        data.push(Math.round(Math.max(10, (baseValue + variance) * timeMultiplier)));
    }
    
    return data;
}

/**
 * Cleanup function
 */
function cleanup() {
    if (liveUpdateInterval) {
        clearInterval(liveUpdateInterval);
    }
    
    if (chartUpdateInterval) {
        clearInterval(chartUpdateInterval);
    }
    
    if (trafficChart) {
        trafficChart.destroy();
    }
    
    if (deviceChart) {
        deviceChart.destroy();
    }
}

// Cleanup on page unload
window.addEventListener('beforeunload', cleanup);