/**
 * Delice Recipe Manager - ARIA Accessibility Enhancements
 * Adds ARIA labels, roles, and keyboard navigation dynamically
 * WCAG 2.1 Level AA Compliance
 */

(function($) {
    'use strict';
    
    /**
     * Initialize accessibility enhancements
     */
    function initAccessibility() {
        addARIALabels();
        addKeyboardNavigation();
        addSkipLinks();
        manageFocusStates();
        addLiveRegions();
        enhanceToggles();
        enhanceModals();
        enhanceForms();
    }
    
    /**
     * Add ARIA labels to elements
     */
    function addARIALabels() {
        // Buttons
        $('.delice-btn-primary').each(function() {
            if (!$(this).attr('aria-label')) {
                const text = $(this).text().trim() || 'Save';
                $(this).attr('aria-label', text);
            }
        });
        
        $('.delice-btn-secondary').each(function() {
            if (!$(this).attr('aria-label')) {
                const text = $(this).text().trim() || 'Action';
                $(this).attr('aria-label', text);
            }
        });
        
        // Icon-only buttons
        $('.delice-icon-btn, button[class*="dashicons"]').each(function() {
            if (!$(this).attr('aria-label')) {
                const title = $(this).attr('title') || 'Button';
                $(this).attr('aria-label', title);
            }
        });
        
        // Inputs
        $('.delice-input').each(function() {
            if (!$(this).attr('aria-label') && !$(this).attr('aria-labelledby')) {
                const placeholder = $(this).attr('placeholder');
                const name = $(this).attr('name');
                const label = placeholder || name || 'Input field';
                $(this).attr('aria-label', label);
            }
        });
        
        // Textareas
        $('.delice-textarea').each(function() {
            if (!$(this).attr('aria-label')) {
                const placeholder = $(this).attr('placeholder') || 'Text area';
                $(this).attr('aria-label', placeholder);
            }
        });
        
        // Tabs
        $('.delice-admin-tab').each(function() {
            if (!$(this).attr('role')) {
                $(this).attr('role', 'tab');
            }
            if (!$(this).attr('aria-label')) {
                $(this).attr('aria-label', $(this).text().trim());
            }
            $(this).attr('aria-selected', $(this).hasClass('active') ? 'true' : 'false');
        });
        
        // Navigation items
        $('.delice-nav-item').each(function() {
            if (!$(this).attr('aria-label')) {
                $(this).attr('aria-label', $(this).text().trim());
            }
        });
        
        // Tables
        $('.delice-table').each(function() {
            if (!$(this).attr('role')) {
                $(this).attr('role', 'table');
            }
        });
    }
    
    /**
     * Enhance toggles with ARIA
     */
    function enhanceToggles() {
        $('.delice-toggle').each(function() {
            const $toggle = $(this);
            
            // Add role and state
            if (!$toggle.attr('role')) {
                $toggle.attr('role', 'switch');
            }
            
            // Set checked state
            const isActive = $toggle.hasClass('active');
            $toggle.attr('aria-checked', isActive ? 'true' : 'false');
            
            // Add label if not present
            if (!$toggle.attr('aria-label')) {
                const setting = $toggle.data('setting') || 'Toggle';
                $toggle.attr('aria-label', 'Toggle ' + setting.replace(/_/g, ' '));
            }
            
            // Make focusable
            if (!$toggle.attr('tabindex')) {
                $toggle.attr('tabindex', '0');
            }
            
            // Update aria-checked on click
            $toggle.on('click', function() {
                setTimeout(function() {
                    const newState = $toggle.hasClass('active');
                    $toggle.attr('aria-checked', newState ? 'true' : 'false');
                }, 100);
            });
        });
    }
    
    /**
     * Add keyboard navigation
     */
    function addKeyboardNavigation() {
        // Toggles
        $('.delice-toggle').on('keypress', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this).click();
            }
        });
        
        // Template cards and clickable divs
        $('.delice-template-card, [onclick], .delice-card').each(function() {
            if (!$(this).attr('tabindex')) {
                $(this).attr('tabindex', '0');
            }
            
            if (!$(this).attr('role')) {
                $(this).attr('role', 'button');
            }
        }).on('keypress', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this).click();
            }
        });
        
        // Tabs
        $('.delice-admin-tab').on('keydown', function(e) {
            const $tabs = $('.delice-admin-tab');
            const $currentTab = $(this);
            const currentIndex = $tabs.index($currentTab);
            
            let $nextTab;
            
            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                e.preventDefault();
                $nextTab = $tabs.eq((currentIndex + 1) % $tabs.length);
            } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                e.preventDefault();
                $nextTab = $tabs.eq((currentIndex - 1 + $tabs.length) % $tabs.length);
            } else if (e.key === 'Home') {
                e.preventDefault();
                $nextTab = $tabs.first();
            } else if (e.key === 'End') {
                e.preventDefault();
                $nextTab = $tabs.last();
            }
            
            if ($nextTab) {
                $nextTab.focus().click();
            }
        });
    }
    
    /**
     * Add skip links
     */
    function addSkipLinks() {
        // Check if skip link already exists
        if ($('.skip-link').length === 0) {
            const $mainContent = $('.delice-admin-content, .delice-main-content, #main-content').first();
            
            if ($mainContent.length) {
                // Add ID if not present
                if (!$mainContent.attr('id')) {
                    $mainContent.attr('id', 'main-content');
                }
                
                // Add skip link
                const skipLink = $('<a>')
                    .attr('href', '#main-content')
                    .addClass('skip-link screen-reader-text')
                    .text('Skip to main content');
                
                $('body').prepend(skipLink);
            }
        }
    }
    
    /**
     * Manage focus states
     */
    function manageFocusStates() {
        // Trap focus in modals when open
        $(document).on('keydown', function(e) {
            const $modal = $('.delice-modal:visible, [role="dialog"]:visible');
            
            if ($modal.length && e.key === 'Tab') {
                const $focusable = $modal.find('a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])').filter(':visible');
                const $first = $focusable.first();
                const $last = $focusable.last();
                
                if (e.shiftKey && document.activeElement === $first[0]) {
                    e.preventDefault();
                    $last.focus();
                } else if (!e.shiftKey && document.activeElement === $last[0]) {
                    e.preventDefault();
                    $first.focus();
                }
            }
        });
        
        // Return focus after modal closes
        let $lastFocus;
        $(document).on('click', '[data-modal-trigger]', function() {
            $lastFocus = $(this);
        });
        
        $(document).on('modal-close', function() {
            if ($lastFocus) {
                $lastFocus.focus();
            }
        });
    }
    
    /**
     * Add ARIA live regions
     */
    function addLiveRegions() {
        // Add notification container if not exists
        if ($('.delice-notifications').length === 0) {
            const $notifications = $('<div>')
                .addClass('delice-notifications')
                .attr({
                    'aria-live': 'polite',
                    'aria-atomic': 'true',
                    'role': 'status'
                });
            
            $('body').append($notifications);
        }
    }
    
    /**
     * Enhance modals
     */
    function enhanceModals() {
        $('[role="dialog"], .delice-modal').each(function() {
            const $modal = $(this);
            
            // Add aria-modal
            if (!$modal.attr('aria-modal')) {
                $modal.attr('aria-modal', 'true');
            }
            
            // Add aria-labelledby if title exists
            const $title = $modal.find('h1, h2, h3').first();
            if ($title.length && !$title.attr('id')) {
                const id = 'modal-title-' + Math.random().toString(36).substr(2, 9);
                $title.attr('id', id);
                $modal.attr('aria-labelledby', id);
            }
            
            // Focus first element when modal opens
            if ($modal.is(':visible')) {
                const $firstFocusable = $modal.find('a, button, input, select, textarea').filter(':visible').first();
                $firstFocusable.focus();
            }
        });
    }
    
    /**
     * Enhance forms
     */
    function enhanceForms() {
        // Add required indicators
        $('input[required], textarea[required], select[required]').each(function() {
            if (!$(this).attr('aria-required')) {
                $(this).attr('aria-required', 'true');
            }
            
            // Add visual indicator if label exists
            const $label = $('label[for="' + $(this).attr('id') + '"]');
            if ($label.length && !$label.find('.required').length) {
                $label.append(' <span class="required" aria-label="required">*</span>');
            }
        });
        
        // Link errors to inputs
        $('.error-message').each(function() {
            const $error = $(this);
            if (!$error.attr('role')) {
                $error.attr('role', 'alert');
            }
            
            // Find associated input
            const $input = $error.prev('input, textarea, select');
            if ($input.length) {
                const errorId = 'error-' + Math.random().toString(36).substr(2, 9);
                $error.attr('id', errorId);
                $input.attr({
                    'aria-invalid': 'true',
                    'aria-describedby': errorId
                });
            }
        });
        
        // Fieldsets
        $('fieldset').each(function() {
            const $fieldset = $(this);
            const $legend = $fieldset.find('legend').first();
            
            if ($legend.length && !$legend.attr('id')) {
                const id = 'legend-' + Math.random().toString(36).substr(2, 9);
                $legend.attr('id', id);
                $fieldset.attr('aria-labelledby', id);
            }
        });
    }
    
    /**
     * Announce to screen readers
     */
    function announceToScreenReader(message) {
        const $liveRegion = $('.delice-notifications[aria-live]');
        if ($liveRegion.length) {
            $liveRegion.text(message);
            
            // Clear after 3 seconds
            setTimeout(function() {
                $liveRegion.text('');
            }, 3000);
        }
    }
    
    // Make announce function globally available
    window.deliceAnnounce = announceToScreenReader;
    
    // Initialize on document ready
    $(document).ready(function() {
        initAccessibility();
        
        // Re-run after AJAX updates
        $(document).ajaxComplete(function() {
            setTimeout(initAccessibility, 500);
        });
    });
    
})(jQuery);
