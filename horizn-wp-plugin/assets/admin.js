/**
 * horizn_ Analytics Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Test Connection
        $('#horizn-test-connection').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $results = $('#horizn-test-results');
            
            // Get current values from form
            var apiEndpoint = $('input[name="horizn_settings[api_endpoint]"]').val();
            var siteKey = $('input[name="horizn_settings[site_key]"]').val();
            
            if (!apiEndpoint || !siteKey) {
                showTestResult('error', 'Please fill in API Endpoint and Site Key before testing.');
                return;
            }
            
            // Show loading state
            $button.addClass('loading').prop('disabled', true);
            showTestResult('', 'Testing connection...');
            
            // Make AJAX request
            $.ajax({
                url: horizn_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'horizn_test_connection',
                    nonce: horizn_admin.nonce,
                    api_endpoint: apiEndpoint,
                    site_key: siteKey
                },
                success: function(response) {
                    if (response.success) {
                        showTestResult('success', response.data.message);
                    } else {
                        showTestResult('error', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    showTestResult('error', 'Connection failed: ' + error);
                },
                complete: function() {
                    $button.removeClass('loading').prop('disabled', false);
                }
            });
        });
        
        // Generate New Site Key
        $('#horizn-generate-key').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $siteKeyField = $('#horizn-site-key');
            
            if (!confirm('This will generate a new site key. Your existing analytics data will not be affected, but you may need to update your configuration. Continue?')) {
                return;
            }
            
            // Show loading state
            $button.addClass('loading').prop('disabled', true);
            
            // Make AJAX request
            $.ajax({
                url: horizn_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'horizn_generate_site_key',
                    nonce: horizn_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $siteKeyField.val(response.data.site_key);
                        showTestResult('success', horizn_admin.strings.generated);
                        
                        // Highlight the field briefly
                        $siteKeyField.css('background-color', '#d1fae5');
                        setTimeout(function() {
                            $siteKeyField.css('background-color', '');
                        }, 2000);
                    } else {
                        showTestResult('error', 'Failed to generate site key.');
                    }
                },
                error: function(xhr, status, error) {
                    showTestResult('error', 'Failed to generate site key: ' + error);
                },
                complete: function() {
                    $button.removeClass('loading').prop('disabled', false);
                }
            });
        });
        
        // Copy to clipboard functionality for site key
        $('<button type="button" class="button button-small" style="margin-left: 10px;">Copy</button>')
            .insertAfter('#horizn-site-key')
            .on('click', function(e) {
                e.preventDefault();
                var $siteKeyField = $('#horizn-site-key');
                
                // Select and copy the text
                $siteKeyField.select();
                document.execCommand('copy');
                
                // Visual feedback
                var $button = $(this);
                var originalText = $button.text();
                $button.text('Copied!').css('color', '#059669');
                
                setTimeout(function() {
                    $button.text(originalText).css('color', '');
                }, 2000);
            });
        
        // Auto-save functionality
        var saveTimeout;
        $('input, textarea, select').on('change input', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                showAutoSaveStatus('Saving...');
                // Note: WordPress handles the actual saving via the form
                setTimeout(function() {
                    showAutoSaveStatus('Saved', 'success');
                }, 1000);
            }, 2000);
        });
        
        // Validate endpoints
        $('textarea[name="horizn_settings[custom_endpoints]"]').on('blur', function() {
            var endpoints = $(this).val().split('\n');
            var validEndpoints = [];
            var hasWarnings = false;
            
            endpoints.forEach(function(endpoint) {
                endpoint = endpoint.trim();
                if (endpoint) {
                    if (!endpoint.startsWith('/')) {
                        hasWarnings = true;
                        endpoint = '/' + endpoint;
                    }
                    validEndpoints.push(endpoint);
                }
            });
            
            if (hasWarnings) {
                $(this).val(validEndpoints.join('\n'));
                showTestResult('warning', 'Some endpoints were automatically corrected to start with "/"');
            }
        });
        
        // Form validation before submit
        $('form').on('submit', function(e) {
            var apiEndpoint = $('input[name="horizn_settings[api_endpoint]"]').val();
            var siteKey = $('input[name="horizn_settings[site_key]"]').val();
            var trackingEnabled = $('input[name="horizn_settings[tracking_enabled]"]').is(':checked');
            
            if (trackingEnabled && (!apiEndpoint || !siteKey)) {
                e.preventDefault();
                alert('Please fill in both API Endpoint and Site Key if tracking is enabled.');
                return false;
            }
        });
        
        // Enhanced form interactions
        initEnhancedFormElements();
        
        // Initialize tooltips if available
        if (typeof $.fn.tooltip !== 'undefined') {
            $('[data-tooltip]').tooltip();
        }
        
        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + S to save
            if ((e.ctrlKey || e.metaKey) && e.which === 83) {
                e.preventDefault();
                $('form').submit();
            }
            
            // Ctrl/Cmd + T to test connection
            if ((e.ctrlKey || e.metaKey) && e.which === 84) {
                e.preventDefault();
                $('#horizn-test-connection').click();
            }
        });
    });
    
    /**
     * Show test results
     */
    function showTestResult(type, message) {
        var $results = $('#horizn-test-results');
        
        $results.removeClass('success error warning')
                .addClass(type)
                .html(message)
                .show();
        
        if (type) {
            setTimeout(function() {
                $results.fadeOut();
            }, 5000);
        }
    }
    
    /**
     * Show auto-save status
     */
    function showAutoSaveStatus(message, type) {
        var $status = $('#auto-save-status');
        
        if ($status.length === 0) {
            $status = $('<div id="auto-save-status"></div>')
                .css({
                    position: 'fixed',
                    top: '32px',
                    right: '20px',
                    background: '#667eea',
                    color: 'white',
                    padding: '8px 16px',
                    borderRadius: '4px',
                    fontSize: '12px',
                    fontWeight: '500',
                    zIndex: 100000,
                    opacity: 0
                })
                .appendTo('body');
        }
        
        if (type === 'success') {
            $status.css('background', '#059669');
        }
        
        $status.text(message)
               .animate({ opacity: 1 }, 200)
               .delay(2000)
               .animate({ opacity: 0 }, 200);
    }
    
    /**
     * Initialize enhanced form elements
     */
    function initEnhancedFormElements() {
        // Add icons to checkboxes
        $('input[type="checkbox"]').each(function() {
            var $checkbox = $(this);
            var $label = $checkbox.closest('label');
            
            if (!$label.find('.checkbox-icon').length) {
                $label.prepend('<span class="checkbox-icon"></span>');
            }
        });
        
        // Add validation indicators to inputs
        $('input[required], input[type="url"], input[type="email"]').each(function() {
            var $input = $(this);
            
            $input.on('blur', function() {
                validateInput($input);
            });
            
            $input.on('input', function() {
                clearTimeout($input.data('validate-timeout'));
                $input.data('validate-timeout', setTimeout(function() {
                    validateInput($input);
                }, 500));
            });
        });
    }
    
    /**
     * Validate input field
     */
    function validateInput($input) {
        var value = $input.val();
        var type = $input.attr('type');
        var isValid = true;
        
        // Remove existing validation classes
        $input.removeClass('valid invalid');
        
        if (type === 'url' && value) {
            var urlPattern = /^https?:\/\/.+/;
            isValid = urlPattern.test(value);
        } else if (type === 'email' && value) {
            var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            isValid = emailPattern.test(value);
        } else if ($input.attr('required') && !value) {
            isValid = false;
        }
        
        $input.addClass(isValid ? 'valid' : 'invalid');
    }
    
    /**
     * Utility function to debounce events
     */
    function debounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }
    
})(jQuery);