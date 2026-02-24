<?php
/**
 * Recipe Trust Class
 * 
 * Handles safety information, allergen warnings, and trustworthiness features
 * 
 * @package Delice_Recipe_Manager
 * @since 1.1.0
 */

if (!defined('ABSPATH')) exit;

class Delice_Recipe_Trust {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_trust_meta_boxes'));
        add_action('save_post', array($this, 'save_trust_meta'), 10, 2);
        add_filter('the_content', array($this, 'append_trust_content'), 18);
    }

    public function add_trust_meta_boxes() {
        add_meta_box(
            'delice_recipe_safety',
            __('🛡️ Safety & Trust', 'delice-recipe-manager'),
            array($this, 'render_safety_meta_box'),
            array('delice_recipe', 'post'),
            'normal',
            'default'
        );
    }

    public function render_safety_meta_box($post) {
        wp_nonce_field('delice_safety_meta_box', 'delice_safety_meta_box_nonce');
        
        $allergens = get_post_meta($post->ID, '_delice_allergens', true) ?: array();
        $dietary = get_post_meta($post->ID, '_delice_dietary_tags', true) ?: array();
        $safety_notes = get_post_meta($post->ID, '_delice_safety_notes', true);
        
        $common_allergens = array('Dairy', 'Eggs', 'Fish', 'Shellfish', 'Tree Nuts', 'Peanuts', 'Wheat', 'Soy');
        $dietary_options = array('Vegetarian', 'Vegan', 'Gluten-Free', 'Dairy-Free', 'Keto', 'Paleo', 'Halal', 'Kosher');
        
        echo '<div class="delice-safety-options">';
        echo '<h4>' . __('Allergen Information', 'delice-recipe-manager') . '</h4>';
        foreach ($common_allergens as $allergen) {
            $checked = in_array($allergen, $allergens) ? 'checked' : '';
            echo '<label style="display:inline-block;margin-right:15px;"><input type="checkbox" name="delice_allergens[]" value="' . esc_attr($allergen) . '" ' . $checked . '> ' . $allergen . '</label>';
        }
        
        echo '<h4 style="margin-top: 15px;">' . __('Dietary Tags', 'delice-recipe-manager') . '</h4>';
        foreach ($dietary_options as $option) {
            $checked = in_array($option, $dietary) ? 'checked' : '';
            echo '<label style="display:inline-block;margin-right:15px;"><input type="checkbox" name="delice_dietary_tags[]" value="' . esc_attr($option) . '" ' . $checked . '> ' . $option . '</label>';
        }
        
        echo '<h4 style="margin-top: 15px;">' . __('Food Safety Notes', 'delice-recipe-manager') . '</h4>';
        echo '<textarea name="delice_safety_notes" rows="4" style="width: 100%;">' . esc_textarea($safety_notes) . '</textarea>';
        echo '<p class="description">' . __('Add food safety tips like internal temperature, storage instructions, etc.', 'delice-recipe-manager') . '</p>';
        echo '</div>';
    }

    public function save_trust_meta($post_id, $post) {
        if (!isset($_POST['delice_safety_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['delice_safety_meta_box_nonce'], 'delice_safety_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        $allergens = isset($_POST['delice_allergens']) ? array_map('sanitize_text_field', $_POST['delice_allergens']) : array();
        $dietary = isset($_POST['delice_dietary_tags']) ? array_map('sanitize_text_field', $_POST['delice_dietary_tags']) : array();
        $safety_notes = isset($_POST['delice_safety_notes']) ? sanitize_textarea_field($_POST['delice_safety_notes']) : '';
        
        update_post_meta($post_id, '_delice_allergens', $allergens);
        update_post_meta($post_id, '_delice_dietary_tags', $dietary);
        update_post_meta($post_id, '_delice_safety_notes', $safety_notes);
    }

    public function append_trust_content($content) {
        if (!is_singular(array('delice_recipe', 'post'))) return $content;
        
        global $post;
        
        // Safety check
        if (!$post || !isset($post->ID)) {
            return $content;
        }
        
        // Check individual toggle for safety info
        if (!get_option('delice_eeat_show_safety_info', 1)) {
            return $content;
        }
        
        $allergens = get_post_meta($post->ID, '_delice_allergens', true);
        $dietary = get_post_meta($post->ID, '_delice_dietary_tags', true);
        $safety_notes = get_post_meta($post->ID, '_delice_safety_notes', true);
        
        if (empty($allergens) && empty($dietary) && empty($safety_notes)) {
            return $content;
        }
        
        $html = '<div class="delice-safety-info-section">';
        $html .= '<h3>' . __('⚠️ Safety & Dietary Information', 'delice-recipe-manager') . '</h3>';
        
        if (!empty($allergens)) {
            $html .= '<div class="allergen-warnings"><h4>' . __('Contains:', 'delice-recipe-manager') . '</h4>';
            $html .= '<ul class="allergen-list">';
            foreach ($allergens as $allergen) {
                $html .= '<li>⚠️ ' . esc_html($allergen) . '</li>';
            }
            $html .= '</ul></div>';
        }
        
        if (!empty($dietary)) {
            $html .= '<div class="dietary-tags"><h4>' . __('Dietary Info:', 'delice-recipe-manager') . '</h4>';
            $html .= '<ul class="dietary-list">';
            foreach ($dietary as $tag) {
                $html .= '<li>✓ ' . esc_html($tag) . '</li>';
            }
            $html .= '</ul></div>';
        }
        
        if ($safety_notes) {
            $html .= '<div class="safety-notes"><h4>' . __('Food Safety Tips:', 'delice-recipe-manager') . '</h4>';
            $html .= '<p>' . wp_kses_post(wpautop($safety_notes)) . '</p></div>';
        }
        
        $html .= '</div>';
        
        return $content . $html;
    }
}
