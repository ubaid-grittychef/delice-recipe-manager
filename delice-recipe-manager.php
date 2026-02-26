<?php
/**
 * Plugin Name:       WP Delicious Recipe
 * Plugin URI:        https://github.com/ubaid-grittychef/delice-recipe-manager
 * Description:       A powerful recipe manager plugin for WordPress with AI generation, schema markup, and GitHub auto-updates.
 * Version:           3.5.1
 * Author:            Delice Team
 * Author URI:        https://github.com/ubaid-grittychef/delice-recipe-manager
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       delice-recipe-manager
 * Domain Path:       /languages
 * GitHub Plugin URI: ubaid-grittychef/delice-recipe-manager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'DELICE_RECIPE_VERSION',    '3.5.1' );
define( 'DELICE_RECIPE_DB_VERSION', '2.1.0' ); // bump when schema changes require an upgrade routine
define( 'DELICE_RECIPE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DELICE_RECIPE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DELICE_RECIPE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'DELICE_RECIPE_PLUGIN_FILE', __FILE__ );

/**
 * Bootstrap the self-hosted GitHub auto-updater.
 *
 * Loaded on 'init' (priority 1) so it runs before the rest of the plugin
 * but after WordPress has set up its update infrastructure.
 */
function delice_recipe_init_updater() {
    require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-updater.php';
    $GLOBALS['delice_gh_updater'] = new Delice_GitHub_Updater(
        DELICE_RECIPE_PLUGIN_FILE,
        'ubaid-grittychef',         // GitHub username
        'delice-recipe-manager',    // GitHub repository
        DELICE_RECIPE_VERSION
    );
}
add_action( 'init', 'delice_recipe_init_updater', 1 );

// Opt this plugin into WordPress automatic background updates.
add_filter( 'auto_update_plugin', function( $update, $item ) {
    return isset( $item->plugin ) && $item->plugin === plugin_basename( __FILE__ ) ? true : $update;
}, 10, 2 );

/**
 * Load plugin textdomain for translations
 */
function delice_recipe_load_textdomain() {
    load_plugin_textdomain(
        'delice-recipe-manager',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}
add_action( 'plugins_loaded', 'delice_recipe_load_textdomain' );

/**
 * Register all recipe meta fields for both post types, including arrays with full REST schema
 */
function delice_recipe_register_meta() {
    // Prevent duplicate registration
    static $meta_registered = false;
    if ($meta_registered) {
        return;
    }
    $meta_registered = true;

    // Define post types that support recipe meta
    $post_types = array('post', 'delice_recipe');
    
    // Capability required to write recipe meta via the REST API.
    $edit_recipe_meta = function( $allowed, $meta_key, $post_id ) {
        return current_user_can( 'edit_post', $post_id );
    };

    foreach ($post_types as $post_type) {
        // Numeric / string fields
        register_meta( $post_type, '_delice_recipe_prep_time', array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'number',
            'auth_callback' => $edit_recipe_meta,
        ) );
        register_meta( $post_type, '_delice_recipe_cook_time', array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'number',
            'auth_callback' => $edit_recipe_meta,
        ) );
        register_meta( $post_type, '_delice_recipe_total_time', array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'number',
            'auth_callback' => $edit_recipe_meta,
        ) );
        register_meta( $post_type, '_delice_recipe_servings', array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'number',
            'auth_callback' => $edit_recipe_meta,
        ) );
        register_meta( $post_type, '_delice_recipe_calories', array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'number',
            'auth_callback' => $edit_recipe_meta,
        ) );
        register_meta( $post_type, '_delice_recipe_difficulty', array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'string',
            'auth_callback' => $edit_recipe_meta,
        ) );
        register_meta( $post_type, '_delice_recipe_notes', array(
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'auth_callback'     => $edit_recipe_meta,
        ) );

        // Migration tracking fields
        register_meta( $post_type, '_delice_recipe_migrated', array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'string',
            'auth_callback' => $edit_recipe_meta,
        ) );
        register_meta( $post_type, '_delice_recipe_original_id', array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'number',
            'auth_callback' => $edit_recipe_meta,
        ) );
        register_meta( $post_type, '_delice_migration_new_id', array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'number',
            'auth_callback' => $edit_recipe_meta,
        ) );

        // Rating fields – read-only via REST (managed server-side only).
        register_meta( $post_type, '_delice_recipe_rating_average', array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'number',
            'auth_callback' => '__return_false', // computed automatically; no external writes.
        ) );
        register_meta( $post_type, '_delice_recipe_rating_count', array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'number',
            'auth_callback' => '__return_false',
        ) );

        // Nutrition stored as JSON in string
        register_meta( $post_type, '_delice_recipe_nutrition', array(
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => $edit_recipe_meta,
        ) );

        // Ingredients: array of objects { name, amount, unit }
        register_meta( $post_type, '_delice_recipe_ingredients', array(
            'single'        => true,
            'type'          => 'array',
            'auth_callback' => $edit_recipe_meta,
            'show_in_rest'  => array(
                'schema' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'name'   => array( 'type' => 'string' ),
                            'amount' => array( 'type' => 'string' ),
                            'unit'   => array( 'type' => 'string' ),
                        ),
                    ),
                ),
            ),
        ) );

        // Instructions: array of objects { step, text }
        register_meta( $post_type, '_delice_recipe_instructions', array(
            'single'        => true,
            'type'          => 'array',
            'auth_callback' => $edit_recipe_meta,
            'show_in_rest'  => array(
                'schema' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'step' => array( 'type' => 'integer' ),
                            'text' => array( 'type' => 'string' ),
                        ),
                    ),
                ),
            ),
        ) );

        // FAQs: array of objects { question, answer }
        register_meta( $post_type, '_delice_recipe_faqs', array(
            'single'        => true,
            'type'          => 'array',
            'auth_callback' => $edit_recipe_meta,
            'show_in_rest'  => array(
                'schema' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'question' => array( 'type' => 'string' ),
                            'answer'   => array( 'type' => 'string' ),
                        ),
                    ),
                ),
            ),
        ) );
    }
}
add_action( 'init', 'delice_recipe_register_meta' );

/**
 * AJAX nonce & script data in admin
 */
function delice_recipe_add_ajax_data() {
    $screen = get_current_screen();
    if ( ! $screen || false === strpos( $screen->id, 'delice-recipe' ) ) {
        return;
    }
    ?>
    <script>
    var deliceRecipe = deliceRecipe || {};
    deliceRecipe.ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
    deliceRecipe.nonce   = '<?php echo esc_js( wp_create_nonce( 'delice_hybrid_nonce' ) ); ?>';
    </script>
    <?php
}
add_action( 'admin_head', 'delice_recipe_add_ajax_data' );

/**
 * Script data for frontend - enhanced to detect migrated recipes
 */
function delice_recipe_frontend_script_data() {
    global $post;
    
    // Check if this is a recipe page (custom type or migrated)
    $is_recipe_page = false;
    
    if ($post) {
        // Check for custom post type
        if (is_singular('delice_recipe')) {
            $is_recipe_page = true;
        }
        // Check for migrated recipes (regular posts with recipe meta)
        elseif (is_singular('post')) {
            $has_ingredients = get_post_meta($post->ID, '_delice_recipe_ingredients', true);
            $has_instructions = get_post_meta($post->ID, '_delice_recipe_instructions', true);
            $is_migrated = get_post_meta($post->ID, '_delice_recipe_migrated', true);
            
            $is_recipe_page = !empty($has_ingredients) || !empty($has_instructions) || $is_migrated === '1';
        }
        // Check for shortcode usage
        elseif (has_shortcode($post->post_content, 'delice_recipe')) {
            $is_recipe_page = true;
        }
    }
    
    // Only add script data on recipe pages
    if (!$is_recipe_page) {
        return;
    }
    
    // Get the current language
    $language = isset($_GET['lang']) ? sanitize_text_field($_GET['lang']) : '';
    if (empty($language)) {
        $language = get_option('delice_recipe_default_language', 'en_US');
    }
    
    // Get language texts
    $language_texts = get_option('delice_recipe_language_texts', array());
    $texts = isset($language_texts[$language]) ? $language_texts[$language] : array();
    
    // Default texts
    $default_texts = array(
        'print' => 'Print Recipe',
        'copy' => 'Copy Ingredients',
        'copied' => 'Ingredients copied!',
        'ingredients' => 'Ingredients',
        'instructions' => 'Instructions',
        'notes' => 'Notes',
    );
    
    // Merge with defaults
    $texts = wp_parse_args($texts, $default_texts);
    
    ?>
    <script>
    var deliceRecipe = deliceRecipe || {};
    deliceRecipe.pluginUrl = '<?php echo esc_js(DELICE_RECIPE_PLUGIN_URL); ?>';
    deliceRecipe.language = '<?php echo esc_js($language); ?>';
    deliceRecipe.printText = '<?php echo esc_js($texts['print']); ?>';
    deliceRecipe.copyText = '<?php echo esc_js($texts['copy']); ?>';
    deliceRecipe.ingredientsCopiedText = '<?php echo esc_js($texts['copied']); ?>';
    deliceRecipe.ingredientsText = '<?php echo esc_js($texts['ingredients']); ?>';
    deliceRecipe.instructionsText = '<?php echo esc_js($texts['instructions']); ?>';
    deliceRecipe.notesText = '<?php echo esc_js($texts['notes']); ?>';
    </script>
    <?php
}
add_action('wp_head', 'delice_recipe_frontend_script_data');

/**
 * Initialize plugin - SINGLE INITIALIZATION POINT
 */
function delice_recipe_manager_init() {
    // Prevent duplicate initialization
    static $plugin_initialized = false;
    if ($plugin_initialized) {
        return;
    }
    $plugin_initialized = true;

    // Include required classes ONCE
    require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-manager.php';
    
    // Core plugin initialization
    $manager = new Delice_Recipe_Manager();
    $manager->run();

    // Initialize E-E-A-T features (v1.1.0+).
    // The class must be both loaded AND instantiated via get_instance() so that
    // its hooks (admin menu, assets, AJAX handlers) are registered.
    $eeat_file = DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-eeat.php';
    if ( file_exists( $eeat_file ) ) {
        require_once $eeat_file;
        Delice_Recipe_EEAT::get_instance();
    }
}
add_action( 'init', 'delice_recipe_manager_init', 5 );

/**
 * Plugin activation hook - flush rewrite rules
 */
function delice_recipe_plugin_activate() {
    // Register post types and taxonomies first
    require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-post-type.php';
    $pt = new Delice_Recipe_Post_Type();
    $pt->register_post_type();
    
    // Initialize URL handler
    require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-url-handler.php';
    new Delice_Recipe_URL_Handler();
    
    // Create E-E-A-T database tables (v1.1.0+)
    if (file_exists(DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-eeat.php')) {
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-eeat.php';
        $eeat = Delice_Recipe_EEAT::get_instance();
        $eeat->create_tables();
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'delice_recipe_plugin_activate' );

/**
 * Plugin deactivation hook - clean up rewrite rules
 */
function delice_recipe_plugin_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'delice_recipe_plugin_deactivate' );

/**
 * DB upgrade routine — runs on every admin page load but exits immediately
 * when the stored version already matches DELICE_RECIPE_DB_VERSION.
 * Add schema migration logic here when future versions need it.
 */
function delice_recipe_maybe_upgrade() {
    $stored = get_option( 'delice_recipe_db_version', '0.0.0' );
    if ( version_compare( $stored, DELICE_RECIPE_DB_VERSION, '>=' ) ) {
        return;
    }
    // Future upgrade steps go here (e.g. ALTER TABLE, new options defaults).
    update_option( 'delice_recipe_db_version', DELICE_RECIPE_DB_VERSION );
}
add_action( 'admin_init', 'delice_recipe_maybe_upgrade' );

/**
 * Plugin action links (settings)
 */
function delice_recipe_plugin_action_links( $links ) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        esc_url( admin_url('admin.php?page=delice-recipe-settings') ),
        esc_html__( 'Settings', 'delice-recipe-manager' )
    );
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . DELICE_RECIPE_PLUGIN_BASENAME, 'delice_recipe_plugin_action_links' );

/**
 * Debug info in footer
 */
function delice_recipe_debug_info() {
    if ( defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options') && isset($_GET['delice_debug']) ) {
        printf(
            '<div style="background:#f8f9fa;padding:15px;margin:20px;border:1px solid #ddd;">
                <h4>%s</h4>
                <p>Version: %s</p>
                <p>Shortcode exists: %s</p>
            </div>',
            esc_html__('Délice Recipe Debug Info:', 'delice-recipe-manager'),
            esc_html(DELICE_RECIPE_VERSION),
            shortcode_exists('delice_recipe') ? 'Yes' : 'No'
        );
    }
}
add_action( 'wp_footer', 'delice_recipe_debug_info' );

?>
