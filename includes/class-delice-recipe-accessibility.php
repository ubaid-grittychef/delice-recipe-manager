<?php
/**
 * Accessibility Helper Functions
 * Provides ARIA labels, roles, and keyboard navigation support
 * 
 * @package Delice_Recipe_Manager
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate ARIA label attribute
 * 
 * @param string $label The label text
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function delice_aria_label($label, $echo = true) {
    $output = sprintf('aria-label="%s"', esc_attr($label));
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Generate ARIA labelledby attribute
 * 
 * @param string $id The ID to reference
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function delice_aria_labelledby($id, $echo = true) {
    $output = sprintf('aria-labelledby="%s"', esc_attr($id));
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Generate ARIA describedby attribute
 * 
 * @param string $id The ID to reference
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function delice_aria_describedby($id, $echo = true) {
    $output = sprintf('aria-describedby="%s"', esc_attr($id));
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Generate role attribute
 * 
 * @param string $role The ARIA role
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function delice_aria_role($role, $echo = true) {
    $output = sprintf('role="%s"', esc_attr($role));
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Generate ARIA live region attribute
 * 
 * @param string $politeness 'polite', 'assertive', or 'off'
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function delice_aria_live($politeness = 'polite', $echo = true) {
    $output = sprintf('aria-live="%s" aria-atomic="true"', esc_attr($politeness));
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Generate ARIA expanded attribute for toggles
 * 
 * @param bool $expanded Whether expanded or not
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function delice_aria_expanded($expanded, $echo = true) {
    $output = sprintf('aria-expanded="%s"', $expanded ? 'true' : 'false');
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Generate ARIA checked attribute for switches
 * 
 * @param bool $checked Whether checked or not
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function delice_aria_checked($checked, $echo = true) {
    $output = sprintf('aria-checked="%s"', $checked ? 'true' : 'false');
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Generate ARIA required attribute
 * 
 * @param bool $required Whether required or not
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function delice_aria_required($required = true, $echo = true) {
    if (!$required) {
        return '';
    }
    $output = 'aria-required="true"';
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Generate ARIA invalid attribute for form validation
 * 
 * @param bool $invalid Whether invalid or not
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function delice_aria_invalid($invalid, $echo = true) {
    $output = sprintf('aria-invalid="%s"', $invalid ? 'true' : 'false');
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Generate ARIA hidden attribute
 * 
 * @param bool $hidden Whether hidden or not
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function delice_aria_hidden($hidden = true, $echo = true) {
    if (!$hidden) {
        return '';
    }
    $output = 'aria-hidden="true"';
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Generate complete ARIA attributes for a button
 * 
 * @param string $label The button label
 * @param array $args Additional arguments
 * @return string
 */
function delice_button_aria($label, $args = array()) {
    $defaults = array(
        'describedby' => '',
        'controls' => '',
        'expanded' => null,
        'pressed' => null,
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $aria = 'aria-label="' . esc_attr($label) . '"';
    
    if (!empty($args['describedby'])) {
        $aria .= ' aria-describedby="' . esc_attr($args['describedby']) . '"';
    }
    
    if (!empty($args['controls'])) {
        $aria .= ' aria-controls="' . esc_attr($args['controls']) . '"';
    }
    
    if ($args['expanded'] !== null) {
        $aria .= ' aria-expanded="' . ($args['expanded'] ? 'true' : 'false') . '"';
    }
    
    if ($args['pressed'] !== null) {
        $aria .= ' aria-pressed="' . ($args['pressed'] ? 'true' : 'false') . '"';
    }
    
    return $aria;
}

/**
 * Generate complete ARIA attributes for an input field
 * 
 * @param string $label The field label
 * @param array $args Additional arguments
 * @return string
 */
function delice_input_aria($label, $args = array()) {
    $defaults = array(
        'required' => false,
        'invalid' => false,
        'describedby' => '',
        'labelledby' => '',
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $aria = '';
    
    if (!empty($label)) {
        $aria .= 'aria-label="' . esc_attr($label) . '"';
    }
    
    if (!empty($args['labelledby'])) {
        $aria .= ' aria-labelledby="' . esc_attr($args['labelledby']) . '"';
    }
    
    if (!empty($args['describedby'])) {
        $aria .= ' aria-describedby="' . esc_attr($args['describedby']) . '"';
    }
    
    if ($args['required']) {
        $aria .= ' aria-required="true"';
    }
    
    if ($args['invalid']) {
        $aria .= ' aria-invalid="true"';
    }
    
    return trim($aria);
}

/**
 * Generate complete ARIA attributes for a toggle/switch
 * 
 * @param string $label The toggle label
 * @param bool $checked Whether checked
 * @return string
 */
function delice_toggle_aria($label, $checked = false) {
    return sprintf(
        'role="switch" aria-label="%s" aria-checked="%s" tabindex="0"',
        esc_attr($label),
        $checked ? 'true' : 'false'
    );
}

/**
 * Generate skip link
 * 
 * @param string $target_id Target element ID
 * @param string $label Link text
 * @return string
 */
function delice_skip_link($target_id, $label) {
    return sprintf(
        '<a href="#%s" class="delice-skip-link screen-reader-text">%s</a>',
        esc_attr($target_id),
        esc_html($label)
    );
}

/**
 * Generate screenreader-only text
 * 
 * @param string $text The text
 * @return string
 */
function delice_sr_only($text) {
    return sprintf('<span class="screen-reader-text">%s</span>', esc_html($text));
}
