
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
        // Only enqueue on recipe pages or pages with recipe shortcodes.
        if ( ! $this->should_enqueue_scripts() ) {
            return;
        }

        // Use the plugin version as the cache buster so browsers re-fetch on
        // plugin updates but cache properly between requests.
        $ver = defined( 'DELICE_RECIPE_VERSION' ) ? DELICE_RECIPE_VERSION : '1.0.0';

        wp_enqueue_style(
            'delice-recipe-final-v2',
            DELICE_RECIPE_PLUGIN_URL . 'public/css/delice-recipe-final-v2.css',
            array(),
            $ver
        );

        wp_enqueue_style(
            'delice-attribution-card',
            DELICE_RECIPE_PLUGIN_URL . 'public/css/delice-attribution-card.css',
            array( 'delice-recipe-final-v2' ),
            $ver
        );

        wp_enqueue_style(
            'delice-force-override',
            DELICE_RECIPE_PLUGIN_URL . 'public/css/delice-force-override.css',
            array( 'delice-attribution-card' ),
            $ver,
            'all'
        );

        wp_enqueue_style(
            'delice-print-no-image',
            DELICE_RECIPE_PLUGIN_URL . 'public/css/delice-print-no-image.css',
            array( 'delice-force-override' ),
            $ver,
            'print'
        );

        // Enqueue Font Awesome for icons (use subresource integrity in production ideally).
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            array(),
            '6.0.0'
        );

        wp_enqueue_script( 'jquery' );

        wp_enqueue_script(
            'delice-recipe-interactive',
            DELICE_RECIPE_PLUGIN_URL . 'public/js/delice-recipe-interactive.js',
            array( 'jquery' ),
            $ver,
            true
        );

        wp_enqueue_script(
            'delice-print-handler',
            DELICE_RECIPE_PLUGIN_URL . 'public/js/delice-print-handler.js',
            array(),
            $ver,
            true
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
        
        // Check recipe taxonomy archives (they always show recipe content).
        if ( is_tax( array( 'delice_cuisine', 'delice_course', 'delice_dietary', 'delice_keyword' ) ) ) {
            return true;
        }

        // Custom post type archives.
        if ( is_post_type_archive( 'delice_recipe' ) ) {
            return true;
        }

        return false;
    }
}
