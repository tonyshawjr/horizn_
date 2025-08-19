<?php
// Sample data - in real app this would come from database/API
$pageTitle = 'Dashboard - horizn_';
$currentPage = 'dashboard';
$liveVisitors = 247;

// Key metrics
$metrics = [
    [
        'title' => 'Live Visitors',
        'value' => '247',
        'change' => '+12.5%',
        'trend' => 'up',
        'subtitle' => 'Active users',
        'period' => 'vs last hour',
        'icon' => '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
    ],
    [
        'title' => 'Page Views',
        'value' => '18.2K',
        'change' => '+8.1%',
        'trend' => 'up',
        'subtitle' => 'Today',
        'period' => 'vs yesterday',
        'icon' => '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V4z" clip-rule="evenodd"></path></svg>'
    ],
    [
        'title' => 'Bounce Rate',
        'value' => '34.2%',
        'change' => '-2.4%',
        'trend' => 'up',
        'subtitle' => 'Sessions',
        'period' => 'vs last week',
        'icon' => '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"></path></svg>'
    ],
    [
        'title' => 'Avg. Session',
        'value' => '2m 34s',
        'change' => '+15.3%',
        'trend' => 'up',
        'subtitle' => 'Duration',
        'period' => 'vs last month',
        'icon' => '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>'
    ]
];

ob_start();
?>

<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white mb-2">Dashboard Overview</h1>
            <p class="text-gray-400">Real-time analytics for your websites</p>
        </div>
        <div class="flex items-center space-x-4">
            <!-- Date Range Selector -->
            <select class="bg-crypto-dark border border-crypto-border rounded-crypto px-3 py-2 text-sm text-white focus:border-crypto-blue transition-colors">
                <option>Last 24 hours</option>
                <option>Last 7 days</option>
                <option>Last 30 days</option>
                <option>Last 90 days</option>
            </select>
            
            <!-- Refresh Button -->
            <button class="bg-crypto-blue hover:bg-blue-600 text-white px-4 py-2 rounded-crypto text-sm crypto-glow transition-all">
                <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                </svg>
                Refresh
            </button>
        </div>
    </div>
    
    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($metrics as $metric): ?>
            <?php 
            $title = $metric['title'];
            $value = $metric['value'];
            $change = $metric['change'];
            $trend = $metric['trend'];
            $subtitle = $metric['subtitle'];
            $period = $metric['period'];
            $icon = $metric['icon'];
            include __DIR__ . '/../components/stat-card.php'; 
            ?>
        <?php endforeach; ?>
    </div>
    
    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Traffic Chart -->
        <?php 
        $title = 'Website Traffic';
        $subtitle = 'Visitors over time';
        $chartId = 'traffic-chart';
        $actions = [
            ['label' => 'Hourly', 'onclick' => 'updateTrafficChart("hourly")'],
            ['label' => 'Daily', 'onclick' => 'updateTrafficChart("daily")'],
            ['label' => 'Weekly', 'onclick' => 'updateTrafficChart("weekly")']
        ];
        include __DIR__ . '/../components/chart-card.php';
        ?>
        
        <!-- Top Pages Chart -->
        <?php 
        $title = 'Top Pages';
        $subtitle = 'Most visited pages today';
        $chartId = 'pages-chart';
        $height = '300px';
        include __DIR__ . '/../components/chart-card.php';
        ?>
    </div>
    
    <!-- Bottom Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Browser Stats -->
        <?php 
        $title = 'Browser Usage';
        $chartId = 'browser-chart';
        $height = '250px';
        include __DIR__ . '/../components/chart-card.php';
        ?>
        
        <!-- Geographic Data -->
        <?php 
        $title = 'Top Countries';
        $chartId = 'geo-chart';
        $height = '250px';
        include __DIR__ . '/../components/chart-card.php';
        ?>
        
        <!-- Live Feed -->
        <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg crypto-glow">
            <div class="p-6 border-b border-crypto-border">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-white">Live Activity</h3>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        <span class="text-xs text-gray-400">LIVE</span>
                    </div>
                </div>
            </div>
            <div class="p-6 space-y-4 max-h-64 overflow-y-auto">
                <div class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-crypto-blue rounded-full mt-2 flex-shrink-0"></div>
                    <div class="text-sm">
                        <p class="text-gray-300">New visitor from <span class="font-mono text-crypto-blue">New York, US</span></p>
                        <p class="text-xs text-gray-500 mt-1">2 seconds ago</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-crypto-purple rounded-full mt-2 flex-shrink-0"></div>
                    <div class="text-sm">
                        <p class="text-gray-300">Page view: <span class="font-mono text-crypto-purple">/products/analytics</span></p>
                        <p class="text-xs text-gray-500 mt-1">8 seconds ago</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-green-400 rounded-full mt-2 flex-shrink-0"></div>
                    <div class="text-sm">
                        <p class="text-gray-300">Conversion: <span class="font-mono text-green-400">Sign up completed</span></p>
                        <p class="text-xs text-gray-500 mt-1">23 seconds ago</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-crypto-blue rounded-full mt-2 flex-shrink-0"></div>
                    <div class="text-sm">
                        <p class="text-gray-300">Referrer: <span class="font-mono text-crypto-blue">twitter.com</span></p>
                        <p class="text-xs text-gray-500 mt-1">45 seconds ago</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$additionalScripts = '
<script>
// Traffic Chart
const trafficOptions = {
    ...window.cryptoChartConfig,
    series: [{
        name: "Visitors",
        data: [44, 55, 57, 56, 61, 58, 63, 60, 66, 70, 72, 68]
    }],
    chart: {
        ...window.cryptoChartConfig.chart,
        type: "area",
        height: 300
    },
    xaxis: {
        ...window.cryptoChartConfig.xaxis,
        categories: ["12AM", "2AM", "4AM", "6AM", "8AM", "10AM", "12PM", "2PM", "4PM", "6PM", "8PM", "10PM"]
    },
    fill: {
        type: "gradient",
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.7,
            opacityTo: 0.1,
            stops: [0, 100]
        }
    }
};

window.charts["traffic-chart"] = new ApexCharts(document.querySelector("#traffic-chart"), trafficOptions);
window.charts["traffic-chart"].render();

// Top Pages Chart
const pagesOptions = {
    ...window.cryptoChartConfig,
    series: [4421, 3251, 2341, 1876, 1432],
    chart: {
        ...window.cryptoChartConfig.chart,
        type: "donut",
        height: 300
    },
    labels: ["/", "/products", "/pricing", "/about", "/contact"],
    legend: {
        ...window.cryptoChartConfig.legend,
        position: "bottom"
    },
    plotOptions: {
        pie: {
            donut: {
                size: "70%"
            }
        }
    }
};

window.charts["pages-chart"] = new ApexCharts(document.querySelector("#pages-chart"), pagesOptions);
window.charts["pages-chart"].render();

// Browser Chart
const browserOptions = {
    ...window.cryptoChartConfig,
    series: [{
        data: [65, 23, 8, 4]
    }],
    chart: {
        ...window.cryptoChartConfig.chart,
        type: "bar",
        height: 250,
        horizontal: true
    },
    xaxis: {
        ...window.cryptoChartConfig.xaxis,
        categories: ["Chrome", "Safari", "Firefox", "Edge"]
    },
    plotOptions: {
        bar: {
            borderRadius: 2,
            horizontal: true
        }
    }
};

window.charts["browser-chart"] = new ApexCharts(document.querySelector("#browser-chart"), browserOptions);
window.charts["browser-chart"].render();

// Geographic Chart
const geoOptions = {
    ...window.cryptoChartConfig,
    series: [{
        data: [44, 35, 28, 22, 18]
    }],
    chart: {
        ...window.cryptoChartConfig.chart,
        type: "bar",
        height: 250
    },
    xaxis: {
        ...window.cryptoChartConfig.xaxis,
        categories: ["United States", "United Kingdom", "Canada", "Germany", "France"]
    },
    plotOptions: {
        bar: {
            borderRadius: 2,
            columnWidth: "50%"
        }
    }
};

window.charts["geo-chart"] = new ApexCharts(document.querySelector("#geo-chart"), geoOptions);
window.charts["geo-chart"].render();

// Update traffic chart function
function updateTrafficChart(period) {
    let data, categories;
    
    switch(period) {
        case "hourly":
            data = [44, 55, 57, 56, 61, 58, 63, 60, 66, 70, 72, 68];
            categories = ["12AM", "2AM", "4AM", "6AM", "8AM", "10AM", "12PM", "2PM", "4PM", "6PM", "8PM", "10PM"];
            break;
        case "daily":
            data = [320, 280, 450, 390, 520, 610, 580];
            categories = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
            break;
        case "weekly":
            data = [1200, 1800, 1400, 2100, 1900, 2400];
            categories = ["Week 1", "Week 2", "Week 3", "Week 4", "Week 5", "Week 6"];
            break;
    }
    
    window.charts["traffic-chart"].updateOptions({
        xaxis: { categories: categories }
    });
    window.charts["traffic-chart"].updateSeries([{
        name: "Visitors",
        data: data
    }]);
}

// Auto-refresh live visitors count every 30 seconds
setInterval(() => {
    // Simulate live visitor count updates
    const currentCount = parseInt(document.querySelector(".bg-crypto-blue.text-crypto-black").textContent);
    const newCount = currentCount + Math.floor(Math.random() * 10) - 5;
    if (newCount > 0) {
        document.querySelector(".bg-crypto-blue.text-crypto-black").textContent = newCount;
    }
}, 30000);

// Add new activity to live feed every 10 seconds
const activities = [
    "New visitor from London, UK",
    "Page view: /dashboard",
    "Conversion: Newsletter signup",
    "Referrer: google.com",
    "New session started",
    "Page view: /pricing"
];

setInterval(() => {
    const liveFeed = document.querySelector(".max-h-64.overflow-y-auto");
    const newActivity = activities[Math.floor(Math.random() * activities.length)];
    const colors = ["crypto-blue", "crypto-purple", "green-400"];
    const color = colors[Math.floor(Math.random() * colors.length)];
    
    const activityElement = document.createElement("div");
    activityElement.className = "flex items-start space-x-3 opacity-0 transition-opacity";
    activityElement.innerHTML = `
        <div class="w-2 h-2 bg-${color} rounded-full mt-2 flex-shrink-0"></div>
        <div class="text-sm">
            <p class="text-gray-300">${newActivity}</p>
            <p class="text-xs text-gray-500 mt-1">just now</p>
        </div>
    `;
    
    liveFeed.insertBefore(activityElement, liveFeed.firstChild);
    
    // Fade in
    setTimeout(() => {
        activityElement.classList.remove("opacity-0");
    }, 100);
    
    // Remove old activities (keep only 6)
    const activities = liveFeed.children;
    if (activities.length > 6) {
        liveFeed.removeChild(activities[activities.length - 1]);
    }
}, 10000);
</script>
';

include __DIR__ . '/../layout.php';
?>