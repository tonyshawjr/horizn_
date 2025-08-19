<?php
/**
 * Identity Badge Component
 * 
 * Shows merged identities for a person (cookie, email, user_id)
 */

// Default identities if not passed
$identities = $journey['identities'] ?? [];
$person_id = $journey['person_id'] ?? 'unknown';
?>

<div class="identity-badge flex items-center space-x-2">
    <!-- Primary Identity Icon -->
    <div class="relative">
        <?php if (!empty($identities['user_id'])): ?>
            <!-- Authenticated user -->
            <div class="w-8 h-8 bg-green-500/20 border-2 border-green-500 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                </svg>
            </div>
        <?php elseif (!empty($identities['email'])): ?>
            <!-- Email identified -->
            <div class="w-8 h-8 bg-crypto-blue/20 border-2 border-crypto-blue rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-crypto-blue" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                </svg>
            </div>
        <?php else: ?>
            <!-- Anonymous visitor -->
            <div class="w-8 h-8 bg-crypto-gray/20 border-2 border-crypto-gray rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-crypto-gray" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"></path>
                </svg>
            </div>
        <?php endif; ?>
        
        <!-- Identity count indicator -->
        <?php if (count($identities) > 1): ?>
            <div class="absolute -top-1 -right-1 w-4 h-4 bg-crypto-purple rounded-full flex items-center justify-center">
                <span class="text-xs text-white font-bold"><?= count($identities) ?></span>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Identity Details -->
    <div class="flex flex-col">
        <div class="text-sm text-white font-medium">
            <?php if (!empty($identities['user_id'])): ?>
                User #<?= htmlspecialchars($identities['user_id']) ?>
            <?php elseif (!empty($identities['email'])): ?>
                <?= htmlspecialchars($identities['email']) ?>
            <?php else: ?>
                Anonymous Visitor
            <?php endif; ?>
        </div>
        
        <!-- Additional identities -->
        <?php if (count($identities) > 1): ?>
            <div class="text-xs text-gray-400">
                <?php
                $additional = [];
                foreach ($identities as $type => $value) {
                    if ($type === 'user_id' && !empty($identities['user_id'])) continue;
                    if ($type === 'email' && !empty($identities['user_id'])) {
                        $additional[] = "email";
                    } elseif ($type === 'cookie') {
                        $additional[] = "cookie";
                    } elseif ($type === 'session') {
                        $additional[] = "session";
                    }
                }
                if (!empty($additional)) {
                    echo "+" . implode(", ", array_unique($additional));
                }
                ?>
            </div>
        <?php else: ?>
            <div class="text-xs text-gray-400 font-mono">
                ID: <?= substr($person_id, 0, 8) ?>...
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Identity Merge Indicator -->
    <?php if (count($identities) > 1): ?>
        <div class="ml-2 relative group">
            <svg class="w-4 h-4 text-crypto-purple cursor-help" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
            </svg>
            
            <!-- Tooltip on hover -->
            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 opacity-0 group-hover:opacity-100 transition-opacity
                        bg-crypto-dark border border-crypto-border rounded-crypto shadow-lg p-3 min-w-max z-10">
                <div class="text-xs text-white font-semibold mb-2">Merged Identities:</div>
                <div class="space-y-1">
                    <?php foreach ($identities as $type => $value): ?>
                        <div class="flex items-center space-x-2 text-xs">
                            <div class="w-2 h-2 rounded-full 
                                        <?php if ($type === 'user_id'): ?>
                                            bg-green-500
                                        <?php elseif ($type === 'email'): ?>
                                            bg-crypto-blue
                                        <?php elseif ($type === 'cookie'): ?>
                                            bg-crypto-purple
                                        <?php else: ?>
                                            bg-crypto-gray
                                        <?php endif; ?>"></div>
                            <span class="text-gray-300"><?= ucfirst($type) ?>:</span>
                            <span class="text-white font-mono"><?= htmlspecialchars(substr($value, 0, 16)) ?>...</span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-xs text-gray-400 mt-2 pt-2 border-t border-crypto-border">
                    These identities were automatically merged based on session data
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>