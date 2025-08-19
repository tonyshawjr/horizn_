<?php
/**
 * Journey Timeline Component
 * 
 * Displays an interactive timeline visualization for a user's journey
 */
?>
<div class="journey-timeline-item p-6 hover:bg-crypto-gray/30 transition-all" 
     data-person-id="<?= $journey['person_id'] ?>">
    
    <!-- Journey Header -->
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-4">
            <!-- Identity Badge -->
            <a href="/journey/detail/<?= $journey['person_id'] ?>?site=<?= $journey['site_id'] ?>" 
               class="flex items-center hover:opacity-80 transition-opacity">
                <?php include APP_PATH . '/views/components/identity-badge.php'; ?>
            </a>
            
            <!-- Journey Stats -->
            <div class="flex items-center space-x-6 text-sm">
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-crypto-purple" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-white mono-data"><?= count($journey['events']) ?></span>
                    <span class="text-gray-400">events</span>
                </div>
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-crypto-blue" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-white mono-data"><?= count($journey['pageviews']) ?></span>
                    <span class="text-gray-400">pages</span>
                </div>
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-white mono-data"><?= gmdate("i:s", $journey['total_duration']) ?></span>
                    <span class="text-gray-400">duration</span>
                </div>
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-white"><?= htmlspecialchars($journey['country_code'] ?? 'Unknown') ?></span>
                    <span class="text-gray-400">location</span>
                </div>
            </div>
        </div>
        
        <!-- Journey Timeframe & Quick Actions -->
        <div class="flex items-center space-x-3">
            <button onclick="toggleJourneyDetails('journey-<?= $journey['person_id'] ?>')" 
                   class="px-3 py-1 bg-crypto-gray hover:bg-crypto-gray/80 rounded-crypto text-xs text-white transition-colors">
                Toggle Details
            </button>
            <a href="/journey/detail/<?= $journey['person_id'] ?>?site=<?= $journey['site_id'] ?>" 
               class="px-3 py-1 bg-crypto-blue hover:bg-crypto-blue/80 rounded-crypto text-xs text-white transition-colors">
                View Full Journey
            </a>
        </div>
        <div class="text-right text-sm mt-2">
            <div class="text-white font-mono">
                <?= date('M j, Y g:i A', strtotime($journey['first_visit'])) ?>
            </div>
            <div class="text-gray-400">
                <?php 
                $timeAgo = time() - strtotime($journey['last_activity']);
                if ($timeAgo < 3600) {
                    echo floor($timeAgo / 60) . ' minutes ago';
                } elseif ($timeAgo < 86400) {
                    echo floor($timeAgo / 3600) . ' hours ago';
                } else {
                    echo floor($timeAgo / 86400) . ' days ago';
                }
                ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Journey Path Preview -->
    <div class="flex items-center space-x-2 mb-4 overflow-x-auto">
        <?php 
        $previewPages = array_slice($journey['pageviews'], 0, 4);
        foreach ($previewPages as $index => $page): 
        ?>
            <div class="flex items-center">
                <div class="bg-crypto-gray/50 border border-crypto-border rounded px-2 py-1 text-xs text-white whitespace-nowrap">
                    <?= htmlspecialchars($page['page_title'] ?: basename($page['page_url'])) ?>
                </div>
                <?php if ($index < count($previewPages) - 1): ?>
                    <svg class="w-3 h-3 text-gray-500 mx-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <?php if (count($journey['pageviews']) > 4): ?>
            <span class="text-gray-400 text-xs">
                +<?= count($journey['pageviews']) - 4 ?> more
            </span>
        <?php endif; ?>
    </div>
    
    <!-- Expandable Journey Details -->
    <div id="journey-<?= $journey['person_id'] ?>" class="journey-details hidden" onclick="event.stopPropagation()">
        <div class="border-t border-crypto-border pt-6 mt-4">
            
            <!-- Journey Statistics -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-crypto-gray/30 rounded-crypto p-3">
                    <div class="text-xs text-gray-400">Sessions</div>
                    <div class="text-lg font-bold text-white mono-data"><?= count($journey['sessions']) ?></div>
                </div>
                <div class="bg-crypto-gray/30 rounded-crypto p-3">
                    <div class="text-xs text-gray-400">Total Pages</div>
                    <div class="text-lg font-bold text-white mono-data"><?= array_sum(array_column($journey['sessions'], 'page_count')) ?></div>
                </div>
                <div class="bg-crypto-gray/30 rounded-crypto p-3">
                    <div class="text-xs text-gray-400">Devices Used</div>
                    <div class="text-lg font-bold text-white mono-data"><?= count(array_unique(array_column($journey['sessions'], 'device_type'))) ?></div>
                </div>
                <div class="bg-crypto-gray/30 rounded-crypto p-3">
                    <div class="text-xs text-gray-400">Browsers Used</div>
                    <div class="text-lg font-bold text-white mono-data"><?= count(array_unique(array_column($journey['sessions'], 'browser'))) ?></div>
                </div>
            </div>
            
            <!-- Session Timeline -->
            <div class="space-y-4">
                <h4 class="text-white font-semibold flex items-center">
                    <svg class="w-4 h-4 mr-2 text-crypto-blue" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                    </svg>
                    Journey Timeline
                </h4>
                
                <div class="relative">
                    <!-- Timeline line -->
                    <div class="absolute left-6 top-8 bottom-0 w-px bg-crypto-border"></div>
                    
                    <?php foreach ($journey['timeline'] as $item): ?>
                        <div class="relative flex items-start space-x-4 pb-6">
                            <!-- Timeline dot -->
                            <div class="relative z-10 flex items-center justify-center w-12 h-12 
                                        <?php if ($item['type'] === 'session_start'): ?>
                                            bg-crypto-blue/20 border-2 border-crypto-blue
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
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h5 class="text-white font-medium">
                                            <?php if ($item['type'] === 'session_start'): ?>
                                                Session Started
                                            <?php elseif ($item['type'] === 'pageview'): ?>
                                                <?= htmlspecialchars($item['page_title'] ?: basename($item['page_url'])) ?>
                                            <?php elseif ($item['type'] === 'event'): ?>
                                                <?= htmlspecialchars($item['event_name']) ?>
                                            <?php endif; ?>
                                        </h5>
                                        <p class="text-sm text-gray-400 mt-1">
                                            <?php if ($item['type'] === 'session_start'): ?>
                                                New session from <?= htmlspecialchars($item['referrer'] ?: 'Direct') ?> on <?= $item['device_type'] ?>
                                            <?php elseif ($item['type'] === 'pageview'): ?>
                                                <?= htmlspecialchars($item['page_url']) ?>
                                                <?php if ($item['load_time']): ?>
                                                    • Loaded in <?= $item['load_time'] ?>ms
                                                <?php endif; ?>
                                            <?php elseif ($item['type'] === 'event'): ?>
                                                <?= htmlspecialchars($item['event_category']) ?>
                                                <?php if ($item['event_value']): ?>
                                                    • Value: <?= $item['event_value'] ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="text-xs text-gray-500 font-mono">
                                        <?= date('g:i:s A', strtotime($item['timestamp'])) ?>
                                    </div>
                                </div>
                                
                                <?php if ($item['type'] === 'session_start' && !empty($item['identities'])): ?>
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <?php foreach ($item['identities'] as $identity): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs 
                                                        <?php if ($identity['type'] === 'email'): ?>
                                                            bg-crypto-blue/20 text-crypto-blue
                                                        <?php elseif ($identity['type'] === 'user_id'): ?>
                                                            bg-green-500/20 text-green-500
                                                        <?php else: ?>
                                                            bg-crypto-gray/20 text-gray-400
                                                        <?php endif; ?>">
                                                <?= ucfirst($identity['type']) ?>: <?= htmlspecialchars($identity['value']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleJourneyDetails(journeyId) {
    const details = document.getElementById(journeyId);
    details.classList.toggle('hidden');
    
    // Update expand/collapse icon if needed
    const item = details.closest('.journey-timeline-item');
    item.classList.toggle('expanded');
}
</script>