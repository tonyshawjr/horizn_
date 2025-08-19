<?php
/**
 * Map Widget Component
 * Displays visitor locations on a geographic map
 */

$widget_id = $widget_id ?? 'map_' . uniqid();
$data = $data ?? [];
$settings = $settings ?? [];

$map_type = $data['map_type'] ?? 'world';
$metric = $data['metric'] ?? 'visitors';
$map_data = $data['data'] ?? [];
$title = $settings['title'] ?? 'Visitor Locations';

// Process data for map visualization
$countries = [];
$total_value = 0;

foreach ($map_data as $item) {
    $country_code = $item['country_code'] ?? 'unknown';
    $value = $item['visitors'] ?? $item['pageviews'] ?? $item['sessions'] ?? 0;
    
    if ($country_code && $country_code !== 'unknown') {
        $countries[$country_code] = [
            'name' => $item['country_name'] ?? $country_code,
            'value' => $value,
            'code' => $country_code
        ];
        $total_value += $value;
    }
}

// Calculate percentages
foreach ($countries as &$country) {
    $country['percentage'] = $total_value > 0 ? ($country['value'] / $total_value) * 100 : 0;
}
unset($country);

// Sort by value
uasort($countries, function($a, $b) {
    return $b['value'] - $a['value'];
});
?>

<div class="map-widget bg-crypto-dark border border-crypto-border rounded-crypto p-4 h-full" data-widget-id="<?= $widget_id ?>">
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center space-x-2">
            <svg class="w-4 h-4 text-crypto-blue" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 019 7.5V8a2 2 0 004 0 2 2 0 011.523-1.943A5.977 5.977 0 0116 10c0 .34-.028.675-.083 1H15a2 2 0 00-2 2v2.197A5.973 5.973 0 0110 16v-2a2 2 0 00-2-2 2 2 0 01-2-2 2 2 0 00-1.668-1.973z" clip-rule="evenodd"></path>
            </svg>
            <h4 class="text-sm font-medium text-gray-300"><?= htmlspecialchars($title) ?></h4>
        </div>
        <div class="flex items-center space-x-2">
            <!-- Metric Selector -->
            <select onchange="changeMapMetric('<?= $widget_id ?>', this.value)" class="bg-crypto-gray border border-crypto-border rounded text-xs text-white px-2 py-1">
                <option value="visitors" <?= $metric === 'visitors' ? 'selected' : '' ?>>Visitors</option>
                <option value="pageviews" <?= $metric === 'pageviews' ? 'selected' : '' ?>>Pageviews</option>
                <option value="sessions" <?= $metric === 'sessions' ? 'selected' : '' ?>>Sessions</option>
            </select>
            
            <button onclick="refreshMapWidget('<?= $widget_id ?>')" class="text-gray-400 hover:text-white transition-colors">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    </div>
    
    <div class="flex flex-col h-full">
        <?php if (empty($countries)): ?>
            <div class="flex-1 flex items-center justify-center bg-crypto-gray rounded border border-dashed border-gray-500">
                <div class="text-center text-gray-400">
                    <svg class="w-8 h-8 mx-auto mb-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 019 7.5V8a2 2 0 004 0 2 2 0 011.523-1.943A5.977 5.977 0 0116 10c0 .34-.028.675-.083 1H15a2 2 0 00-2 2v2.197A5.973 5.973 0 0110 16v-2a2 2 0 00-2-2 2 2 0 01-2-2 2 2 0 00-1.668-1.973z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="text-sm">No geographic data</div>
                </div>
            </div>
        <?php else: ?>
            <!-- World Map Visualization -->
            <div class="flex-1 mb-4">
                <div id="world-map-<?= $widget_id ?>" class="w-full h-full min-h-[200px] bg-crypto-gray rounded"></div>
            </div>
            
            <!-- Top Countries List -->
            <div class="border-t border-crypto-border pt-3">
                <div class="text-xs text-gray-400 mb-2">Top Countries</div>
                <div class="space-y-2 max-h-32 overflow-y-auto">
                    <?php foreach (array_slice($countries, 0, 5, true) as $code => $country): ?>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2 flex-1 min-w-0">
                                <!-- Flag placeholder -->
                                <div class="w-4 h-3 bg-crypto-blue rounded-sm flex-shrink-0"></div>
                                <span class="text-sm text-white truncate"><?= htmlspecialchars($country['name']) ?></span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-white mono-data"><?= number_format($country['value']) ?></span>
                                <div class="w-8 h-1 bg-crypto-gray rounded-full">
                                    <div class="h-full bg-crypto-blue rounded-full" style="width: <?= min(100, $country['percentage']) ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($countries) > 5): ?>
                    <button onclick="showAllCountries('<?= $widget_id ?>')" class="w-full text-center text-xs text-crypto-blue hover:text-blue-400 transition-colors mt-2">
                        View all <?= count($countries) ?> countries
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer Stats -->
    <?php if (!empty($countries)): ?>
        <div class="mt-3 pt-3 border-t border-crypto-border">
            <div class="flex justify-between text-xs text-gray-400">
                <span><?= count($countries) ?> countries</span>
                <span>Total: <?= number_format($total_value) ?> <?= $metric ?></span>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Country Details Modal -->
<div id="countryModal-<?= $widget_id ?>" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-6 w-full max-w-lg mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-white">Country Details</h3>
            <button onclick="closeCountryModal('<?= $widget_id ?>')" class="text-gray-400 hover:text-white">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        
        <div id="countryModalContent-<?= $widget_id ?>">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<?php if (!empty($countries)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeWorldMap('<?= $widget_id ?>', <?= json_encode($countries) ?>, '<?= $metric ?>');
});

function initializeWorldMap(widgetId, countriesData, metric) {
    const mapContainer = document.getElementById(`world-map-${widgetId}`);
    if (!mapContainer) return;
    
    // Create a simple world map visualization using SVG
    // In a real implementation, you might use libraries like D3.js, Leaflet, or AmCharts
    
    // For this demo, we'll create a simple visual representation
    const maxValue = Math.max(...Object.values(countriesData).map(c => c.value));
    
    let mapHTML = `
        <div class="world-map-container h-full flex items-center justify-center relative">
            <div class="world-map-grid grid grid-cols-8 gap-1 p-4">
    `;
    
    // Create a simplified world map grid
    Object.values(countriesData).slice(0, 32).forEach((country, index) => {
        const intensity = (country.value / maxValue) * 100;
        const color = `rgba(59, 130, 246, ${Math.max(0.2, intensity / 100)})`;
        
        mapHTML += `
            <div class="map-cell w-6 h-4 rounded-sm cursor-pointer transition-all hover:scale-110" 
                 style="background-color: ${color}"
                 title="${country.name}: ${country.value} ${metric}"
                 onclick="showCountryDetails('${widgetId}', '${country.code}', '${country.name}', ${country.value})">
            </div>
        `;
    });
    
    mapHTML += `
            </div>
            <div class="absolute bottom-2 right-2 text-xs text-gray-400">
                <div class="flex items-center space-x-2">
                    <span>Less</span>
                    <div class="flex space-x-1">
                        <div class="w-3 h-3 bg-blue-200 rounded-sm"></div>
                        <div class="w-3 h-3 bg-blue-400 rounded-sm"></div>
                        <div class="w-3 h-3 bg-blue-600 rounded-sm"></div>
                        <div class="w-3 h-3 bg-blue-800 rounded-sm"></div>
                    </div>
                    <span>More</span>
                </div>
            </div>
        </div>
    `;
    
    mapContainer.innerHTML = mapHTML;
}

function showCountryDetails(widgetId, countryCode, countryName, value) {
    const modal = document.getElementById(`countryModal-${widgetId}`);
    const content = document.getElementById(`countryModalContent-${widgetId}`);
    
    if (!modal || !content) return;
    
    content.innerHTML = `
        <div class="space-y-4">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-6 bg-crypto-blue rounded-sm"></div>
                <div>
                    <div class="text-lg font-medium text-white">${countryName}</div>
                    <div class="text-sm text-gray-400">${countryCode}</div>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-crypto-gray p-3 rounded-crypto">
                    <div class="text-2xl font-bold text-white mono-data">${value.toLocaleString()}</div>
                    <div class="text-sm text-gray-400">Visitors</div>
                </div>
                <div class="bg-crypto-gray p-3 rounded-crypto">
                    <div class="text-2xl font-bold text-white mono-data">${Math.round(Math.random() * 1000)}</div>
                    <div class="text-sm text-gray-400">Pageviews</div>
                </div>
            </div>
            
            <div class="text-sm text-gray-400">
                Click on map areas to explore geographic analytics.
            </div>
        </div>
    `;
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeCountryModal(widgetId) {
    const modal = document.getElementById(`countryModal-${widgetId}`);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

function showAllCountries(widgetId) {
    // In a real implementation, this would show a full list or larger map
    alert('Feature coming soon: View all countries in expanded view');
}

function changeMapMetric(widgetId, newMetric) {
    console.log(`Changing map metric for widget ${widgetId} to ${newMetric}`);
    
    // Add loading state
    const mapContainer = document.getElementById(`world-map-${widgetId}`);
    if (mapContainer) {
        mapContainer.innerHTML = `
            <div class="flex items-center justify-center h-full">
                <svg class="animate-spin w-8 h-8 text-crypto-blue" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        `;
        
        // Simulate loading new data
        setTimeout(() => {
            location.reload(); // In real implementation, would update map with new metric
        }, 1000);
    }
}

function refreshMapWidget(widgetId) {
    const widget = document.querySelector(`[data-widget-id="${widgetId}"]`);
    if (!widget) return;
    
    // Add loading overlay
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'absolute inset-0 bg-crypto-dark bg-opacity-75 flex items-center justify-center rounded-crypto z-10';
    loadingOverlay.innerHTML = `
        <div class="text-center text-white">
            <svg class="animate-spin w-6 h-6 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <div class="text-sm">Updating map...</div>
        </div>
    `;
    
    widget.style.position = 'relative';
    widget.appendChild(loadingOverlay);
    
    // Simulate refresh
    setTimeout(() => {
        loadingOverlay.remove();
        
        // Add success indicator
        const indicator = document.createElement('div');
        indicator.className = 'absolute top-2 right-2 w-2 h-2 bg-green-400 rounded-full z-10';
        widget.appendChild(indicator);
        
        setTimeout(() => {
            indicator.remove();
        }, 2000);
    }, 2000);
}
</script>
<?php endif; ?>