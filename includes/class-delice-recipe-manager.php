<?php
/**
 * The main plugin class - Fixed architecture and dependencies
 */

if (!class_exists('Delice_Recipe_Manager')) {
class Delice_Recipe_Manager {

    /**
     * @var Delice_Recipe_Loader
     */
    protected $loader;

    /**
     * Initialize the plugin
     */
    public function __construct() {
        $this->load_dependencies();
        
        // Load language utilities
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-language.php';
        require_once DELICE_RECIPE_PLUGIN_DIR . 'public/includes/class-delice-recipe-language-widget.php';
        
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->register_post_types();
        $this->register_recipe_rating();
        $this->register_recipe_schema();
        $this->register_url_handler();
        $this->register_taxonomy_manager();
        $this->register_migration();
        $this->register_review_admin_hooks(); // ADDED
    }

    private function load_dependencies() {
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-loader.php';
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-accessibility.php'; // ACCESSIBILITY
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-post-type.php';
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-url-handler.php';
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-taxonomy-manager.php';
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-migration.php';
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-reviews.php';
        require_once DELICE_RECIPE_PLUGIN_DIR . 'admin/class-delice-recipe-admin.php';
        require_once DELICE_RECIPE_PLUGIN_DIR . 'admin/ajax-handlers.php';
        require_once DELICE_RECIPE_PLUGIN_DIR . 'public/class-delice-recipe-public.php';
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-ai.php';
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-rating.php';
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-nutrition.php';
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-search.php';
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-templates.php';
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-schema.php';

        $this->loader = new Delice_Recipe_Loader();
    }

    private function define_admin_hooks() {
        $admin     = new Delice_Recipe_Admin();
        $nutrition = new Delice_Recipe_Nutrition();

        // Admin assets – enqueue_styles / enqueue_scripts are NOT registered in
        // the constructor so they go through the loader as normal.
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');

        // admin_menu, admin_init, add_meta_boxes, and save_post_* are already
        // hooked in Delice_Recipe_Admin::__construct().  Skip them here.

        // Meta boxes.
        // NOTE: add_recipe_meta_boxes and save_recipe_meta are ALREADY registered
        // inside Delice_Recipe_Admin::__construct() (via save_post_delice_recipe /
        // save_post_post hooks).  We must NOT add them again here through the
        // loader to prevent duplicate saves and extra DB calls.
        $this->loader->add_action('add_meta_boxes', $nutrition, 'add_nutrition_meta_box');
        $this->loader->add_action('save_post',      $nutrition, 'save_nutrition_meta');

        // AJAX
        delice_register_ajax_handlers();
    }

    private function define_public_hooks() {
        // Initialize public functionality - SINGLE POINT ONLY
        new Delice_Recipe_Public();
    }

    private function register_post_types() {
        $pt = new Delice_Recipe_Post_Type();
        $this->loader->add_action('init', $pt, 'register_post_type');
    }

    private function register_recipe_rating() {
        $rating = new Delice_Recipe_Rating();
        $this->loader->add_filter('delice_recipe_display_options', $rating, 'add_rating_display_option');
    }

    private function register_recipe_schema() {
        $schema = new Delice_Recipe_Schema();
        // Schema class registers its own hooks
    }

    private function register_url_handler() {
        new Delice_Recipe_URL_Handler();
    }

    private function register_taxonomy_manager() {
        new Delice_Recipe_Taxonomy_Manager();
    }
    
    private function register_migration() {
        new Delice_Recipe_Migration();
    }

    private function register_review_admin_hooks() {
        // Reviews menu is now registered in class-delice-recipe-admin.php
        // This function kept for potential future review-related hooks
    }

    public function run() {
        $this->loader->run();
    }
}
}
