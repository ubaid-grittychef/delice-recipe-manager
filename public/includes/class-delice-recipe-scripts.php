
<?php
/**
 * Handle public script enqueuing
 */
class Delice_Recipe_Scripts {
    
    public function __construct() {
        // Constructor logic if needed
    }
    
    /**
     * Enqueue public scripts and styles
     */
    public function enqueue() {
        // Only enqueue on recipe pages or pages with recipe shortcodes
        if (!$this->should_enqueue_scripts()) {
            return;
        }
        
        // Aggressive cache buster
        $cache_buster = md5(time() . rand() . 'v27-no-image');
        
        // Load FINAL V2 CSS
        wp_enqueue_style(
            'delice-recipe-final-v2',
            DELICE_RECIPE_PLUGIN_URL . 'public/css/delice-recipe-final-v2.css',
            array(),
            $cache_buster
        );
        
        // Load ATTRIBUTION CARD CSS
        wp_enqueue_style(
            'delice-attribution-card',
            DELICE_RECIPE_PLUGIN_URL . 'public/css/delice-attribution-card.css',
            array('delice-recipe-final-v2'),
            $cache_buster
        );
        
        // Load FORCE OVERRIDE CSS
        wp_enqueue_style(
            'delice-force-override',
            DELICE_RECIPE_PLUGIN_URL . 'public/css/delice-force-override.css',
            array('delice-attribution-card'),
            $cache_buster,
            'all'
        );
        
        // Load PRINT CSS - NO IMAGE VERSION
        wp_enqueue_style(
            'delice-print-no-image',
            DELICE_RECIPE_PLUGIN_URL . 'public/css/delice-print-no-image.css',
            array('delice-force-override'),
            $cache_buster,
            'print'
        );
        
        // Enqueue jQuery
        wp_enqueue_script('jquery');
        
        // Enqueue interactive JS
        wp_enqueue_script(
            'delice-recipe-interactive',
            DELICE_RECIPE_PLUGIN_URL . 'public/js/delice-recipe-interactive.js',
            array('jquery'),
            $cache_buster,
            true
        );
        
        // Enqueue print handler - vanilla JS, no dependencies
        wp_enqueue_script(
            'delice-print-handler',
            DELICE_RECIPE_PLUGIN_URL . 'public/js/delice-print-handler.js',
            array(),
            $cache_buster,
            true
        );
        
        // Enqueue Font Awesome for icons
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            array(),
            '6.0.0'
        );
    }
    
    /**
     * Check if we should enqueue scripts
     */
    private function should_enqueue_scripts() {
        global $post;
        
        // Always enqueue on recipe post type
        if (is_singular('delice_recipe')) {
            return true;
        }
        
        // Check for migrated recipes (posts with recipe metadata)
        if (isset($post->ID)) {
            // Check if this is a migrated recipe
            $is_migrated = get_post_meta($post->ID, '_delice_recipe_migrated', true);
            if ($is_migrated === '1') {
                return true;
            }
            
            // Check if post has recipe data (ingredients or instructions)
            $has_ingredients = get_post_meta($post->ID, '_delice_recipe_ingredients', true);
            $has_instructions = get_post_meta($post->ID, '_delice_recipe_instructions', true);
            if (!empty($has_ingredients) || !empty($has_instructions)) {
                return true;
            }
        }
        
        // Check for recipe shortcode in post content
        if (isset($post->post_content) && has_shortcode($post->post_content, 'delice_recipe')) {
            return true;
        }
        
        // Check if this is a page that might have recipes
        if (is_home() || is_front_page() || is_archive()) {
            return true;
        }
        
        return false;
    }
}
