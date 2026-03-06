<?php
/**
 * Handles recipe shortcodes - Fixed constructor and registration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Delice_Recipe_Shortcode {
    
    /**
     * Constructor - Register shortcodes
     */
    public function __construct() {
        // Register shortcodes immediately
        add_shortcode('delice_recipe', array($this, 'process_shortcode'));
        add_shortcode('delice_recipe_card', array($this, 'process_shortcode'));
        add_shortcode('recipe_card', array($this, 'process_shortcode'));
    }
    
    /**
     * Process shortcode
     */
    public function process_shortcode($atts = array(), $content = '') {
        $atts = shortcode_atts(array(
            'id' => 0,
            'template' => 'default',
            'show_image' => true,
            'show_rating' => true,
        ), $atts, 'delice_recipe');
        
        $recipe_id = intval($atts['id']);
        if (!$recipe_id || get_post_type($recipe_id) !== 'delice_recipe') {
            return '<p>Recipe not found.</p>';
        }
        
        // Load template system
        $templates = new Delice_Recipe_Templates();
        return $templates->render_recipe($recipe_id, $atts);
    }
}
