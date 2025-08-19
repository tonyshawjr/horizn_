<?php
/**
 * Individual Journey Detail View
 */

$pageTitle = 'Journey Details - horizn_';
$currentPage = 'journeys';
$additionalScripts = '<script src="/assets/js/journey-detail.js"></script><script src="/assets/js/live-updates.js"></script>';
?>

<!-- Journey Detail Header -->
<div class="p-6 border-b border-crypto-border">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <!-- Back Button -->
            <a href="/dashboard/journeys?site=<?= $current_site_id ?>" 
               class="flex items-center text-crypto-blue hover:text-crypto-blue/80 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                </svg>
                Back to Journeys
            </a>
            
            <!-- Journey Identity -->
            <div class="flex items-center space-x-3">
                <?php include APP_PATH . '/views/components/identity-badge.php'; ?>
                <div>
                    <h1 class="text-2xl font-bold text-white mono-data">Journey Details</h1>
                    <p class="text-gray-400 text-sm">
                        <?= date('M j, Y g:i A', strtotime($journey['first_visit'])) ?> - 
                        <?= date('g:i A', strtotime($journey['last_activity'])) ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="flex items-center space-x-3">
            <!-- Export Journey -->
            <button onclick="exportJourney('<?= $journey['person_id'] ?>')" 
                    class="px-4 py-2 bg-crypto-dark border border-crypto-border rounded-crypto text-white hover:bg-crypto-gray transition-all">
                <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
                Export
            </button>
            
            <!-- Live Updates Toggle -->
            <label class="flex items-center space-x-2 text-sm">
                <input type="checkbox" id="liveUpdates" class="rounded border-crypto-border" checked>
                <span class="text-white">Live Updates</span>
            </label>
        </div>
    </div>
</div>

<div class="p-6">
    <!-- Journey Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-6 crypto-glow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Total Sessions</p>
                    <p class="text-2xl font-bold mono-data text-white"><?= count($journey['sessions']) ?></p>
                </div>
                <div class="w-12 h-12 bg-crypto-blue/20 rounded-crypto flex items-center justify-center">
                    <svg class="w-6 h-6 text-crypto-blue" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-6 crypto-glow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Total Pages</p>
                    <p class="text-2xl font-bold mono-data text-white"><?= count($journey['pageviews']) ?></p>
                </div>
                <div class="w-12 h-12 bg-crypto-purple/20 rounded-crypto flex items-center justify-center">
                    <svg class="w-6 h-6 text-crypto-purple" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-6 crypto-glow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Events Triggered</p>
                    <p class="text-2xl font-bold mono-data text-white"><?= count($journey['events']) ?></p>
                </div>
                <div class="w-12 h-12 bg-green-500/20 rounded-crypto flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-6 crypto-glow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Total Duration</p>
                    <p class="text-2xl font-bold mono-data text-white"><?= gmdate("i:s", $journey['total_duration']) ?></p>
                    <p class="text-sm text-gray-400">min:sec</p>
                </div>
                <div class="w-12 h-12 bg-yellow-500/20 rounded-crypto flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-6 crypto-glow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Devices Used</p>
                    <p class="text-2xl font-bold mono-data text-white"><?= count($journey['device_summary']) ?></p>
                    <p class="text-sm text-gray-400"><?= implode(', ', array_keys($journey['device_summary'])) ?></p>
                </div>
                <div class="w-12 h-12 bg-crypto-gray/20 rounded-crypto flex items-center justify-center">
                    <svg class="w-6 h-6 text-crypto-gray" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Journey Timeline -->
        <div class="lg:col-span-2 bg-crypto-dark border border-crypto-border rounded-crypto-lg">
            <div class="p-6 border-b border-crypto-border">
                <h3 class="text-lg font-bold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-crypto-blue" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                    </svg>
                    Complete Journey Timeline
                </h3>
                <p class="text-sm text-gray-400">Chronological view of all user activities</p>
            </div>
            
            <div class="p-6 max-h-[800px] overflow-y-auto">
                <div class="relative">
                    <!-- Timeline line -->
                    <div class="absolute left-6 top-8 bottom-8 w-px bg-crypto-border"></div>
                    
                    <?php foreach ($journey['timeline'] as $index => $item): ?>
                        <div class="relative flex items-start space-x-4 pb-8 <?= $index === count($journey['timeline']) - 1 ? '' : 'mb-4' ?>">
                            <!-- Timeline dot -->
                            <div class="relative z-10 flex items-center justify-center w-12 h-12 
                                        <?php if ($item['type'] === 'session_start'): ?>
                                            bg-crypto-blue/20 border-2 border-crypto-blue
                                        <?php elseif ($item['type'] === 'session_end'): ?>
                                            bg-red-500/20 border-2 border-red-500
                                        <?php elseif ($item['type'] === 'pageview'): ?>
                                            bg-crypto-purple/20 border-2 border-crypto-purple
                                        <?php elseif ($item['type'] === 'event'): ?>
                                            bg-green-500/20 border-2 border-green-500
                                        <?php else: ?>
                                            bg-crypto-gray/20 border-2 border-crypto-gray
                                        <?php endif; ?>
                                        rounded-full">
                                
                                <?php if ($item['type'] === 'session_start'): ?>
                                    <svg class="w-5 h-5 text-crypto-blue" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path>
                                    </svg>
                                <?php elseif ($item['type'] === 'session_end'): ?>
                                    <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-2.414-2.414a1 1 0 00-1.414 1.414L11.586 10l-1.707 1.707a1 1 0 001.414 1.414L13.707 11.5a1 1 0 000-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                <?php elseif ($item['type'] === 'pageview'): ?>
                                    <svg class="w-5 h-5 text-crypto-purple" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                    </svg>
                                <?php elseif ($item['type'] === 'event'): ?>
                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Timeline content -->
                            <div class="flex-1 min-w-0 bg-crypto-gray/20 rounded-crypto p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h5 class="text-white font-medium mb-1">
                                            <?php if ($item['type'] === 'session_start'): ?>
                                                🟢 Session Started
                                            <?php elseif ($item['type'] === 'session_end'): ?>
                                                🔴 Session Ended
                                            <?php elseif ($item['type'] === 'pageview'): ?>
                                                📄 <?= htmlspecialchars($item['page_title'] ?: basename($item['page_url'])) ?>
                                            <?php elseif ($item['type'] === 'event'): ?>
                                                ⚡ <?= htmlspecialchars($item['event_name']) ?>
                                            <?php endif; ?>
                                        </h5>
                                        
                                        <div class="text-sm text-gray-400 space-y-1">
                                            <?php if ($item['type'] === 'session_start'): ?>
                                                <div>🌐 From: <?= htmlspecialchars($item['referrer'] ?: 'Direct') ?></div>
                                                <div>💻 Device: <?= $item['device_type'] ?> • <?= $item['browser'] ?></div>
                                                <div>📍 Location: <?= $item['country_code'] ?></div>
                                            <?php elseif ($item['type'] === 'pageview'): ?>
                                                <div>🔗 <?= htmlspecialchars($item['page_url']) ?></div>
                                                <?php if ($item['load_time']): ?>
                                                    <div>⚡ Loaded in <?= $item['load_time'] ?>ms</div>
                                                <?php endif; ?>
                                                <?php if ($item['referrer']): ?>
                                                    <div>📥 From: <?= htmlspecialchars($item['referrer']) ?></div>
                                                <?php endif; ?>
                                            <?php elseif ($item['type'] === 'event'): ?>
                                                <div>📂 Category: <?= htmlspecialchars($item['event_category']) ?></div>
                                                <?php if ($item['event_action']): ?>
                                                    <div>🎯 Action: <?= htmlspecialchars($item['event_action']) ?></div>
                                                <?php endif; ?>
                                                <?php if ($item['event_label']): ?>
                                                    <div>🏷️ Label: <?= htmlspecialchars($item['event_label']) ?></div>
                                                <?php endif; ?>
                                                <?php if ($item['event_value']): ?>
                                                    <div>💰 Value: <?= $item['event_value'] ?></div>
                                                <?php endif; ?>
                                                <div>📄 Page: <?= htmlspecialchars($item['page_url']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($item['event_data'])): ?>
                                            <div class="mt-2 p-2 bg-crypto-dark rounded border">
                                                <div class="text-xs text-gray-400 mb-1">Event Data:</div>
                                                <pre class="text-xs text-green-400 font-mono overflow-x-auto"><?= json_encode($item['event_data'], JSON_PRETTY_PRINT) ?></pre>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="text-xs text-gray-500 font-mono ml-4 text-right">
                                        <div><?= date('M j', strtotime($item['timestamp'])) ?></div>
                                        <div><?= date('g:i:s A', strtotime($item['timestamp'])) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Journey Sidebar -->
        <div class="space-y-6">
            <!-- Session Summary -->
            <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg">
                <div class="p-6 border-b border-crypto-border">
                    <h3 class="text-lg font-bold text-white">Sessions</h3>
                    <p class="text-sm text-gray-400">All sessions for this visitor</p>
                </div>
                <div class="divide-y divide-crypto-border max-h-80 overflow-y-auto">
                    <?php foreach ($journey['sessions'] as $session): ?>
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-mono text-crypto-blue"><?= substr($session['id'], 0, 8) ?>...</span>
                                <span class="text-xs text-gray-400"><?= $session['page_count'] ?> pages</span>
                            </div>
                            <div class="text-xs text-gray-400 space-y-1">
                                <div>⏰ <?= date('M j, g:i A', strtotime($session['first_visit'])) ?></div>
                                <div>💻 <?= $session['device_type'] ?> • <?= $session['browser'] ?></div>
                                <div>🌐 <?= htmlspecialchars($session['referrer_domain'] ?: 'Direct') ?></div>
                                <div>📍 <?= $session['country_code'] ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Top Pages -->
            <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg">
                <div class="p-6 border-b border-crypto-border">
                    <h3 class="text-lg font-bold text-white">Top Pages</h3>
                    <p class="text-sm text-gray-400">Most visited pages in this journey</p>
                </div>
                <div class="divide-y divide-crypto-border">
                    <?php 
                    $page_counts = [];
                    foreach ($journey['pageviews'] as $pv) {
                        $page_counts[$pv['page_path']] = ($page_counts[$pv['page_path']] ?? 0) + 1;
                    }
                    arsort($page_counts);
                    $top_pages = array_slice($page_counts, 0, 5, true);
                    ?>
                    <?php foreach ($top_pages as $page => $count): ?>
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-white font-medium"><?= basename($page) ?></span>
                                <span class="text-xs text-crypto-blue font-mono"><?= $count ?>x</span>
                            </div>
                            <div class="text-xs text-gray-400"><?= htmlspecialchars($page) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Journey Stats -->
            <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg">
                <div class="p-6 border-b border-crypto-border">
                    <h3 class="text-lg font-bold text-white">Journey Statistics</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-400">First Seen</span>
                        <span class="text-sm text-white font-mono"><?= date('M j, Y', strtotime($journey['first_visit'])) ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-400">Last Seen</span>
                        <span class="text-sm text-white font-mono"><?= date('M j, Y', strtotime($journey['last_activity'])) ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-400">Total Time</span>
                        <span class="text-sm text-white font-mono"><?= gmdate("H:i:s", $journey['total_duration']) ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-400">Avg. Session</span>
                        <span class="text-sm text-white font-mono"><?= gmdate("i:s", $journey['avg_session_duration']) ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-400">Bounce Rate</span>
                        <span class="text-sm text-white font-mono"><?= round($journey['bounce_rate'], 1) ?>%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Live updates for journey detail
let liveUpdatesEnabled = true;
let updateInterval;

document.getElementById('liveUpdates').addEventListener('change', function() {
    liveUpdatesEnabled = this.checked;
    if (liveUpdatesEnabled) {
        startLiveUpdates();
    } else {
        stopLiveUpdates();
    }
});

function startLiveUpdates() {
    if (updateInterval) clearInterval(updateInterval);
    
    updateInterval = setInterval(function() {
        if (liveUpdatesEnabled) {
            updateJourneyData();
        }
    }, 30000); // Update every 30 seconds
}

function stopLiveUpdates() {
    if (updateInterval) {
        clearInterval(updateInterval);
        updateInterval = null;
    }
}

function updateJourneyData() {
    const personId = '<?= $journey['person_id'] ?>';
    const siteId = '<?= $current_site_id ?>';
    
    fetch(`/api/journeys/detail/${personId}?site=${siteId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.journey) {
                // Update timeline if new activities detected
                const currentTimelineCount = document.querySelectorAll('.relative.flex.items-start').length;
                if (data.journey.timeline.length > currentTimelineCount) {
                    // Reload page to show new activities
                    window.location.reload();
                }
            }
        })
        .catch(error => console.log('Failed to update journey data:', error));
}

function exportJourney(personId) {
    const siteId = '<?= $current_site_id ?>';
    window.open(`/api/journeys/export/${personId}?site=${siteId}&format=json`, '_blank');
}

// Start live updates on page load
document.addEventListener('DOMContentLoaded', function() {
    startLiveUpdates();
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    stopLiveUpdates();
});
</script>