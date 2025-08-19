<?php
/**
 * Funnel Builder View
 * Create/edit funnel interface with drag-drop functionality
 */

$pageTitle = ($funnel ? 'Edit' : 'Create') . ' Funnel - horizn_';
$currentPage = 'funnels';
$additionalScripts = '<script src="/assets/js/funnel-builder.js"></script>';
?>

<!-- Funnel Builder Header -->
<div class="p-6 border-b border-crypto-border">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <a href="/dashboard/funnels?site=<?= $site_id ?>" class="p-2 text-gray-400 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-white mono-data">
                    <?= $funnel ? 'Edit' : 'Create' ?> Funnel
                </h1>
                <p class="text-gray-400 text-sm">
                    <?= $funnel ? 'Modify your conversion funnel steps and settings' : 'Build a new conversion funnel to track user journeys' ?>
                </p>
            </div>
        </div>
        
        <div class="flex items-center space-x-3">
            <button id="previewFunnel" class="px-4 py-2 bg-crypto-gray border border-crypto-border text-white rounded-crypto hover:bg-crypto-gray/80 transition-all">
                <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                </svg>
                Preview
            </button>
            
            <button id="saveFunnel" class="px-6 py-2 bg-crypto-blue text-white rounded-crypto hover:bg-crypto-blue/80 transition-all">
                <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6a1 1 0 10-2 0v5.586l-1.293-1.293z"></path>
                    <path d="M5 3a2 2 0 00-2 2v1a1 1 0 002 0V5h8v14H5v-1a1 1 0 10-2 0v1a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2H5z"></path>
                </svg>
                Save Funnel
            </button>
        </div>
    </div>
</div>

<div class="p-6">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Funnel Configuration -->
        <div class="lg:col-span-1">
            <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg p-6 crypto-glow">
                <h3 class="text-lg font-bold text-white mb-4">Funnel Configuration</h3>
                
                <!-- Funnel Name -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Funnel Name</label>
                    <input type="text" id="funnelName" 
                           class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white placeholder-gray-400 focus:border-crypto-blue focus:ring-1 focus:ring-crypto-blue"
                           placeholder="e.g., Registration Flow"
                           value="<?= htmlspecialchars($funnel['name'] ?? '') ?>">
                </div>
                
                <!-- Funnel Description -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                    <textarea id="funnelDescription" rows="3"
                              class="w-full bg-crypto-gray border border-crypto-border rounded-crypto px-3 py-2 text-white placeholder-gray-400 focus:border-crypto-blue focus:ring-1 focus:ring-crypto-blue"
                              placeholder="Brief description of this funnel..."><?= htmlspecialchars($funnel['description'] ?? '') ?></textarea>
                </div>
                
                <!-- Step Templates -->
                <h4 class="font-semibold text-white mb-3">Add Steps</h4>
                
                <div class="space-y-2 mb-6">
                    <button class="w-full p-3 bg-crypto-gray/50 border border-crypto-border rounded-crypto text-sm text-white hover:bg-crypto-gray transition-all step-template"
                            data-type="pageview">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-blue-500/20 rounded-crypto flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0h8v12H6V4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <div class="font-medium">Page View</div>
                                <div class="text-xs text-gray-400">User visits a specific page</div>
                            </div>
                        </div>
                    </button>
                    
                    <button class="w-full p-3 bg-crypto-gray/50 border border-crypto-border rounded-crypto text-sm text-white hover:bg-crypto-gray transition-all step-template"
                            data-type="event">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-green-500/20 rounded-crypto flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M6.672 1.911a1 1 0 10-1.932.518l.259.966a1 1 0 001.932-.518l-.26-.966zM2.429 4.74a1 1 0 10-.517 1.932l.966.259a1 1 0 00.517-1.932l-.966-.26zm8.814-.569a1 1 0 00-1.415-1.414l-.707.707a1 1 0 101.415 1.414l.707-.707zm-7.071 7.072l.707-.707A1 1 0 003.465 9.12l-.708.707a1 1 0 001.415 1.414zm3.2-5.171a1 1 0 00-1.3 1.3l4 10a1 1 0 001.823.075l1.38-2.759 3.018 3.02a1 1 0 001.414-1.415l-3.019-3.02 2.76-1.379a1 1 0 00-.076-1.822l-10-4z"></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <div class="font-medium">Event</div>
                                <div class="text-xs text-gray-400">User triggers custom event</div>
                            </div>
                        </div>
                    </button>
                    
                    <button class="w-full p-3 bg-crypto-gray/50 border border-crypto-border rounded-crypto text-sm text-white hover:bg-crypto-gray transition-all step-template"
                            data-type="custom">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-purple-500/20 rounded-crypto flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <div class="font-medium">Custom</div>
                                <div class="text-xs text-gray-400">Custom conditions</div>
                            </div>
                        </div>
                    </button>
                </div>
                
                <!-- Quick References -->
                <div class="border-t border-crypto-border pt-4">
                    <h4 class="font-semibold text-white mb-3 text-sm">Popular Pages</h4>
                    <div class="space-y-1 max-h-32 overflow-y-auto">
                        <?php foreach ($popular_pages as $page): ?>
                            <div class="text-xs text-gray-400 p-2 bg-crypto-gray/30 rounded cursor-pointer hover:bg-crypto-gray/50 popular-page"
                                 data-path="<?= htmlspecialchars($page['page_path']) ?>"
                                 title="<?= htmlspecialchars($page['page_title']) ?>">
                                <?= htmlspecialchars(strlen($page['page_path']) > 25 ? substr($page['page_path'], 0, 25) . '...' : $page['page_path']) ?>
                                <span class="text-crypto-blue">(<?= number_format($page['pageview_count']) ?>)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <h4 class="font-semibold text-white mb-3 text-sm mt-4">Popular Events</h4>
                    <div class="space-y-1 max-h-32 overflow-y-auto">
                        <?php foreach ($available_events as $event): ?>
                            <div class="text-xs text-gray-400 p-2 bg-crypto-gray/30 rounded cursor-pointer hover:bg-crypto-gray/50 popular-event"
                                 data-name="<?= htmlspecialchars($event['event_name']) ?>"
                                 data-category="<?= htmlspecialchars($event['event_category']) ?>">
                                <?= htmlspecialchars($event['event_name']) ?>
                                <?php if ($event['event_category']): ?>
                                    <span class="text-gray-500">(<?= htmlspecialchars($event['event_category']) ?>)</span>
                                <?php endif; ?>
                                <span class="text-crypto-blue">(<?= number_format($event['event_count']) ?>)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Funnel Builder -->
        <div class="lg:col-span-3">
            <div class="bg-crypto-dark border border-crypto-border rounded-crypto-lg crypto-glow">
                <!-- Funnel Builder Header -->
                <div class="p-6 border-b border-crypto-border">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white">Funnel Steps</h3>
                        <div class="flex items-center space-x-2 text-sm text-gray-400">
                            <span id="stepCount">0</span> steps
                        </div>
                    </div>
                </div>
                
                <!-- Funnel Canvas -->
                <div class="p-6">
                    <div id="funnelCanvas" class="min-h-96 relative">
                        <!-- Empty State -->
                        <div id="emptyState" class="text-center py-16">
                            <div class="w-16 h-16 bg-crypto-blue/20 rounded-crypto mx-auto mb-4 flex items-center justify-center">
                                <svg class="w-8 h-8 text-crypto-blue" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V4z"></path>
                                </svg>
                            </div>
                            <h4 class="text-lg font-medium text-white mb-2">Build Your Funnel</h4>
                            <p class="text-gray-400 mb-4">Add steps from the sidebar to create your conversion funnel</p>
                            <p class="text-sm text-gray-500">Tip: Start with a page view or event that begins the user journey</p>
                        </div>
                        
                        <!-- Funnel Steps Container -->
                        <div id="funnelSteps" class="space-y-4"></div>
                    </div>
                </div>
                
                <!-- Live Preview -->
                <div class="border-t border-crypto-border p-6" id="livePreview" style="display: none;">
                    <h4 class="font-bold text-white mb-4">Live Preview</h4>
                    <div id="previewChart" class="h-64"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Step Configuration Modal -->
<div id="stepModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeStepModal()"></div>
    <div class="absolute inset-4 md:inset-8 lg:inset-16 bg-crypto-dark border border-crypto-border rounded-crypto-lg overflow-hidden flex flex-col">
        <!-- Modal Header -->
        <div class="p-6 border-b border-crypto-border flex items-center justify-between">
            <h3 class="text-lg font-bold text-white" id="modalTitle">Configure Step</h3>
            <button onclick="closeStepModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        
        <!-- Modal Content -->
        <div class="flex-1 overflow-y-auto p-6">
            <div id="modalContent">
                <!-- Dynamic content based on step type -->
            </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="p-6 border-t border-crypto-border flex items-center justify-end space-x-3">
            <button onclick="closeStepModal()" class="px-4 py-2 bg-crypto-gray text-white rounded-crypto hover:bg-crypto-gray/80">
                Cancel
            </button>
            <button id="saveStepBtn" class="px-6 py-2 bg-crypto-blue text-white rounded-crypto hover:bg-crypto-blue/80">
                Save Step
            </button>
        </div>
    </div>
</div>

<script>
// Initialize data
window.funnelBuilderData = {
    funnel: <?= json_encode($funnel) ?>,
    funnelSteps: <?= json_encode($funnel_steps) ?>,
    availableEvents: <?= json_encode($available_events) ?>,
    popularPages: <?= json_encode($popular_pages) ?>,
    siteId: <?= $site_id ?>
};
</script>