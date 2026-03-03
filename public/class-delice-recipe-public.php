
<?php
/**
 * Public‐facing functionality for Délice Recipe Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class Delice_Recipe_Public {
    /**
     * Scripts handler instance
     */
    private $scripts;

    /**
     * Shortcode handler instance
     */
    private $shortcode;

    /**
     * Display handler instance
     */
    private $display;

    /**
     * Schema handler instance
     */
    private $schema;

    /**
     * Templates handler instance
     */
    private $templates;

    /**
     * Constructor: initialize components and hook everything
     */
    public function __construct() {
        // Prevent multiple initializations
        static $initialized = false;
        if ($initialized) {
            return;
        }
        $initialized = true;

        // Load dependencies
        $this->load_dependencies();

        // Initialize components
        $this->scripts = new Delice_Recipe_Scripts();
        $this->shortcode = new Delice_Recipe_Shortcode();
        $this->display = new Delice_Recipe_Display();
        $this->schema = new Delice_Recipe_Schema();
        $this->templates = new Delice_Recipe_Templates();

        // Hook everything up
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/includes/class-delice-recipe-asset-loader.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-delice-recipe-scripts.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-delice-recipe-shortcode.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-delice-recipe-display.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-delice-recipe-schema.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-delice-recipe-templates.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-delice-recipe-author-utils.php';
    }

    /**
     * Register all hooks for the public side
     */
    private function init_hooks() {
        // Assets - Load with HIGHEST priority to override theme/other plugins
        add_action('wp_enqueue_scripts', array($this->scripts, 'enqueue'), 9999);

        // Shortcode
        add_shortcode('delice_recipe', array($this->shortcode, 'process_shortcode'));

        // Content display
        add_filter('the_content', array($this->display, 'display_recipe_content'), 99);
        
        // Removed duplicate localize_rating_script to prevent nonce conflicts
    }
}
