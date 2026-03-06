<?php
/**
 * Handles clean recipe URLs and redirects
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Delice_Recipe_URL_Handler')) {
class Delice_Recipe_URL_Handler {

    public function __construct() {
        add_action('init', array($this, 'add_custom_rewrite_rules'), 20);
        add_filter('post_type_link', array($this, 'custom_recipe_permalink'), 10, 2);
        add_action('template_redirect', array($this, 'handle_old_url_redirects'));
        add_filter('request', array($this, 'parse_recipe_request'));
    }

    /**
     * Add custom rewrite rules for clean recipe URLs
     */
    public function add_custom_rewrite_rules() {
        // Recipe archive page
        add_rewrite_rule(
            '^recipes/?$',
            'index.php?post_type=delice_recipe',
            'top'
        );

        // Recipe archive pagination
        add_rewrite_rule(
            '^recipes/page/([0-9]{1,})/?$',
            'index.php?post_type=delice_recipe&paged=$matches[1]',
            'top'
        );

        // Individual recipe posts at root level - make this more specific to avoid conflicts
        add_rewrite_rule(
            '^([^/]+)/?$',
            'index.php?recipe_slug=$matches[1]',
            'bottom'
        );
        
        // Add custom query var
        add_rewrite_tag('%recipe_slug%', '([^&]+)');
    }

    /**
     * Custom permalink structure for recipes
     */
    public function custom_recipe_permalink($post_link, $post) {
        if ($post->post_type !== 'delice_recipe') {
            return $post_link;
        }

        // Generate clean slug from title
        $slug = $this->generate_clean_slug($post->post_title);
        return home_url('/' . $slug . '/');
    }

    /**
     * Generate clean slug from recipe title
     */
    private function generate_clean_slug($title) {
        // Remove periods, special characters, and create SEO-friendly slug
        $slug = strtolower($title);
        $slug = preg_replace('/[^\w\s-]/', '', $slug); // Remove special chars except spaces and hyphens
        $slug = preg_replace('/[\s_]+/', '-', $slug);  // Replace spaces with hyphens
        $slug = trim($slug, '-');                      // Remove leading/trailing hyphens
        
        return $slug;
    }

    /**
     * Handle redirects from old recette URLs to new clean URLs
     */
    public function handle_old_url_redirects() {
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // Check if this is an old recette URL
        if (strpos($request_uri, '/recette/') !== false) {
            // Extract the recipe slug from the old URL
            $parts = explode('/recette/', $request_uri);
            if (isset($parts[1])) {
                $old_slug = trim($parts[1], '/');
                
                // Find the recipe post by slug
                $recipe = get_page_by_path($old_slug, OBJECT, 'delice_recipe');
                if ($recipe) {
                    $new_url = $this->custom_recipe_permalink('', $recipe);
                    wp_redirect($new_url, 301);
                    exit;
                }
            }
        }
    }

    /**
     * Parse recipe requests to ensure proper post type handling
     */
    public function parse_recipe_request($query_vars) {
        // Handle our custom recipe_slug query var
        if (isset($query_vars['recipe_slug']) && !empty($query_vars['recipe_slug'])) {
            $recipe_slug = $query_vars['recipe_slug'];
            
            // First check if this slug belongs to a recipe
            $recipe = get_page_by_path($recipe_slug, OBJECT, 'delice_recipe');
            if ($recipe) {
                $query_vars['post_type'] = 'delice_recipe';
                $query_vars['name'] = $recipe_slug;
                unset($query_vars['recipe_slug']);
                return $query_vars;
            }
            
            // If no recipe found, let WordPress handle it normally (could be page/post)
            unset($query_vars['recipe_slug']);
        }

        // If we have a 'name' query var but no post_type, check if it's a recipe
        if (isset($query_vars['name']) && !isset($query_vars['post_type'])) {
            $recipe = get_page_by_path($query_vars['name'], OBJECT, 'delice_recipe');
            if ($recipe) {
                $query_vars['post_type'] = 'delice_recipe';
            }
        }

        return $query_vars;
    }

    /**
     * Flush rewrite rules on activation
     */
    public static function flush_rewrite_rules() {
        flush_rewrite_rules();
    }
}
}
