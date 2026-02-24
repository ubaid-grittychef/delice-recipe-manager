<?php
/**
 * The admin-specific functionality of the plugin
 */
class Delice_Recipe_Admin {

    /**
     * Initialize the class
     */
    public function __construct() {
        // Add compatibility for Block Editor
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        
        // Handle classic editor metaboxes save
        add_action('save_post_delice_recipe', array($this, 'save_recipe_meta'), 10, 2);
        add_action('save_post_post', array($this, 'save_recipe_meta'), 10, 2); // Support migrated recipes
        
        // Add admin menu items
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
        
        // Register plugin settings
        add_action('admin_init', array($this, 'register_settings'));

        // Handle "Clear Cache & Check Now" for the GitHub updater
        add_action('admin_init', array($this, 'handle_update_cache_clear'));
        
        // Add meta boxes for recipe post type
        add_action('add_meta_boxes', array($this, 'add_recipe_meta_boxes'));
        
        // Add admin notices for settings validation
        add_action('admin_notices', array($this, 'show_settings_notices'));
        
        // Hide third-party admin notices on plugin pages
        add_action('admin_head', array($this, 'hide_third_party_notices'));
    }
    
    /**
     * Hide third-party empty notices on plugin pages  
     */
    public function hide_third_party_notices() {
        // Get current screen
        $screen = get_current_screen();
        
        // Check if we're on a Delice Recipe page
        $delice_pages = array(
            'toplevel_page_delice-recipe-dashboard',
            'delice-recipes_page_delice-recipe-ai-generator',
            'delice-recipes_page_delice-recipe-settings',
            'delice-recipes_page_delice-recipe-languages',
            'delice-recipes_page_delice-recipe-import-export',
            'delice-recipes_page_delice-recipe-migration',
        );
        
        if ($screen && in_array($screen->id, $delice_pages)) {
            // NOTE: CSS and JS in separate files now handle empty notice removal
            // This keeps legitimate notices working while removing empty ones
        }
    }

    /**
     * Register the stylesheets for the admin area
     */
    public function enqueue_styles() {
        // Load original admin styles
        wp_enqueue_style('delice-recipe-admin', DELICE_RECIPE_PLUGIN_URL . 'admin/css/delice-recipe-admin.css', array(), DELICE_RECIPE_VERSION, 'all');
        
        // Load accessibility styles (ALWAYS - for WCAG compliance)
        wp_enqueue_style('delice-accessibility', DELICE_RECIPE_PLUGIN_URL . 'admin/css/delice-accessibility.css', array('delice-recipe-admin'), DELICE_RECIPE_VERSION, 'all');
        
        // Load clean notices CSS (removes only empty notices)
        wp_enqueue_style('delice-clean-notices', DELICE_RECIPE_PLUGIN_URL . 'admin/css/delice-clean-notices.css', array(), DELICE_RECIPE_VERSION, 'all');
        
        // Load modern design system on dashboard pages
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'delice-recipe') !== false) {
            wp_enqueue_style('delice-recipe-modern', DELICE_RECIPE_PLUGIN_URL . 'admin/css/delice-recipe-modern.css', array('delice-recipe-admin'), DELICE_RECIPE_VERSION, 'all');
            
            // NEW: Enqueue hybrid modern CSS for new pages
            wp_enqueue_style('delice-hybrid-modern', DELICE_RECIPE_PLUGIN_URL . 'admin/css/delice-hybrid-modern.css', array('delice-recipe-admin'), DELICE_RECIPE_VERSION, 'all');
        }
    }

    /**
     * Register the JavaScript for the admin area
     */
    public function enqueue_scripts() {
        wp_enqueue_script('delice-recipe-admin', DELICE_RECIPE_PLUGIN_URL . 'admin/js/delice-recipe-admin.js', array('jquery'), DELICE_RECIPE_VERSION, false);
        
        // Enqueue accessibility enhancements
        wp_enqueue_script('delice-accessibility', DELICE_RECIPE_PLUGIN_URL . 'admin/js/delice-accessibility.js', array('jquery'), DELICE_RECIPE_VERSION, true);
        
        // Enqueue clean notices script (removes only empty notices)
        wp_enqueue_script('delice-clean-notices', DELICE_RECIPE_PLUGIN_URL . 'admin/js/delice-clean-notices.js', array('jquery'), DELICE_RECIPE_VERSION, true);
        
        // Pass variables to our script
        wp_localize_script('delice-recipe-admin', 'deliceRecipe', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('delice_recipe_nonce'),
            'ingredientPlaceholder' => __('Ingredient', 'delice-recipe-manager'),
            'amountPlaceholder' => __('Amount', 'delice-recipe-manager'),
            'unitPlaceholder' => __('Unit', 'delice-recipe-manager'),
            'instructionPlaceholder' => __('Instruction', 'delice-recipe-manager'),
            'removeText' => __('Remove', 'delice-recipe-manager'),
        ));
        
        // Load modern JS on dashboard pages
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'delice-recipe') !== false) {
            wp_enqueue_script('delice-recipe-modern', DELICE_RECIPE_PLUGIN_URL . 'admin/js/delice-recipe-modern.js', array('jquery'), DELICE_RECIPE_VERSION, false);
            
            // Localize modern script
            wp_localize_script('delice-recipe-modern', 'deliceAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('delice_recipe_nonce'),
            ));
            
            // NEW: Enqueue hybrid modern JS for new pages
            wp_enqueue_script('delice-hybrid-modern', DELICE_RECIPE_PLUGIN_URL . 'admin/js/delice-hybrid-modern.js', array('jquery'), DELICE_RECIPE_VERSION, true);
            
            // Localize hybrid script
            wp_localize_script('delice-hybrid-modern', 'deliceHybridData', array(
                'nonce' => wp_create_nonce('delice_hybrid_nonce'),
                'ajaxurl' => admin_url('admin-ajax.php'),
                'adminUrl' => admin_url(),
            ));
        }
    }
    
    /**
     * Register scripts and styles for block editor
     */
    public function enqueue_block_editor_assets() {
        global $post;
        
        // Only load on our custom post type or migrated recipes
        if (!$post || (!$this->is_recipe_post($post))) {
            return;
        }
        
        // Add editor styles
        wp_enqueue_style(
            'delice-recipe-editor-styles',
            DELICE_RECIPE_PLUGIN_URL . 'admin/css/delice-recipe-admin.css',
            array('wp-edit-blocks'),
            DELICE_RECIPE_VERSION
        );
        
        // Add compatibility script for metaboxes if needed
        wp_enqueue_script(
            'delice-recipe-editor-compat',
            DELICE_RECIPE_PLUGIN_URL . 'admin/js/delice-recipe-admin.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'jquery'),
            DELICE_RECIPE_VERSION,
            true
        );
        
        // Add our recipe data for the editor
        $recipe_data = array(
            'postId' => $post->ID,
            'prepTime' => get_post_meta($post->ID, '_delice_recipe_prep_time', true),
            'cookTime' => get_post_meta($post->ID, '_delice_recipe_cook_time', true),
            'totalTime' => get_post_meta($post->ID, '_delice_recipe_total_time', true),
            'servings' => get_post_meta($post->ID, '_delice_recipe_servings', true),
            'calories' => get_post_meta($post->ID, '_delice_recipe_calories', true),
            'difficulty' => get_post_meta($post->ID, '_delice_recipe_difficulty', true),
            'notes' => get_post_meta($post->ID, '_delice_recipe_notes', true),
        );
        
        wp_localize_script(
            'delice-recipe-editor-compat',
            'deliceRecipeData',
            $recipe_data
        );
    }

    /**
     * Add menu items to the WordPress admin
     */
    public function add_plugin_admin_menu() {
        // Main menu
        add_menu_page(
            __('Delice Recipes', 'delice-recipe-manager'),
            __('Delice Recipes', 'delice-recipe-manager'),
            'manage_options',
            'delice-recipe-manager',
            array($this, 'display_plugin_admin_dashboard'),
            'dashicons-carrot',
            30
        );
        
        // Remove the duplicate "Delice Recipes" submenu that WordPress adds automatically
        // by renaming the first submenu
        remove_submenu_page('delice-recipe-manager', 'delice-recipe-manager');
        
        // Dashboard submenu
        add_submenu_page(
            'delice-recipe-manager',
            __('Dashboard', 'delice-recipe-manager'),
            __('Dashboard', 'delice-recipe-manager'),
            'manage_options',
            'delice-recipe-manager',
            array($this, 'display_plugin_admin_dashboard')
        );
        
        // AI Generator submenu
        add_submenu_page(
            'delice-recipe-manager',
            __('AI Generator', 'delice-recipe-manager'),
            __('AI Generator', 'delice-recipe-manager'),
            'manage_options',
            'delice-recipe-ai-generator',
            array($this, 'display_ai_generator_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'delice-recipe-manager',
            __('Settings', 'delice-recipe-manager'),
            __('Settings', 'delice-recipe-manager'),
            'manage_options',
            'delice-recipe-settings',
            array($this, 'display_settings_hub_page')
        );
        
        // Languages submenu
        add_submenu_page(
            'delice-recipe-manager',
            __('Languages', 'delice-recipe-manager'),
            __('Languages', 'delice-recipe-manager'),
            'manage_options',
            'delice-recipe-languages',
            array($this, 'display_recipe_languages_page')
        );
        
        // Reviews submenu
        add_submenu_page(
            'delice-recipe-manager',
            __('Reviews', 'delice-recipe-manager'),
            __('Reviews', 'delice-recipe-manager'),
            'manage_options',
            'delice-recipe-reviews',
            array($this, 'display_review_settings_page')
        );
        
        // Migration submenu
        add_submenu_page(
            'delice-recipe-manager',
            __('Migration', 'delice-recipe-manager'),
            __('Migration', 'delice-recipe-manager'),
            'manage_options',
            'delice-recipe-migration',
            array($this, 'display_recipe_migration_page')
        );
        
        // Import/Export submenu
        add_submenu_page(
            'delice-recipe-manager',
            __('Import/Export', 'delice-recipe-manager'),
            __('Import/Export', 'delice-recipe-manager'),
            'manage_options',
            'delice-recipe-import-export',
            array($this, 'display_import_export_page')
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        // Template settings
        register_setting(
            'delice_recipe_settings',
            'delice_recipe_selected_template',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'default',
            )
        );
        
        // Display options
        register_setting(
            'delice_recipe_settings',
            'delice_recipe_display_options',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_display_options'),
                'default' => array(
                    'show_image' => true,
                    'show_servings' => true,
                    'show_prep_time' => true,
                    'show_cook_time' => true,
                    'show_total_time' => true,
                    'show_calories' => true,
                    'show_difficulty' => true,
                    'show_rating' => true,
                    'show_ingredients' => true,
                    'show_instructions' => true,
                    'show_notes' => true,
                    'show_faqs' => true,
                ),
            )
        );
        
        // Attribution settings
        register_setting(
            'delice_recipe_settings',
            'delice_recipe_attribution_settings',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_attribution_settings'),
                'default' => array(
                    'kitchen_name' => '',
                    'kitchen_url' => '',
                    'show_submitted_by' => true,
                    'show_tested_by' => true,
                ),
            )
        );
        
        // Schema settings
        register_setting(
            'delice_recipe_settings',
            'delice_recipe_schema_settings',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_schema_settings'),
                'default' => array(
                    'enable_schema' => true,
                    'publisher_name' => get_bloginfo('name'),
                    'publisher_logo' => '',
                    'use_author' => true,
                    'default_author' => '',
                ),
            )
        );
        
        // AI API Key
        register_setting(
            'delice_recipe_settings',
            'delice_recipe_ai_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );
        
        // AI Image Generation Settings
        register_setting(
            'delice_recipe_settings',
            'delice_recipe_enable_ai_images',
            array(
                'type' => 'boolean',
                'sanitize_callback' => array($this, 'sanitize_boolean_setting'),
                'default' => false,
            )
        );
        
        register_setting(
            'delice_recipe_settings',
            'delice_recipe_image_style',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'vivid',
            )
        );
        
        register_setting(
            'delice_recipe_settings',
            'delice_recipe_image_size',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '1024x1024',
            )
        );
        
        // Language settings
        register_setting(
            'delice_recipe_settings',
            'delice_recipe_default_language',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'en_US',
            )
        );
        
        register_setting(
            'delice_recipe_settings',
            'delice_recipe_enabled_languages',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_languages_array'),
                'default' => array('en_US'),
            )
        );
        
        register_setting(
            'delice_recipe_settings',
            'delice_recipe_language_texts',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_language_texts'),
                'default' => array(),
            )
        );
        
        // Reviews settings
        register_setting(
            'delice_recipe_settings',
            'delice_recipe_reviews_enabled',
            array(
                'type' => 'boolean',
                'sanitize_callback' => array($this, 'sanitize_boolean_setting'),
                'default' => true,
            )
        );

        // GitHub auto-updater: Personal Access Token (for private repos)
        register_setting(
            'delice_recipe_settings',
            'delice_github_token',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );

    }

    /**
     * Handle the "Clear Cache & Check Now" GET action.
     *
     * Runs on its own admin_init callback so it is completely separate from
     * WordPress's settings-saving flow (which caused the "not allowed" error).
     */
    public function handle_update_cache_clear() {
        if ( ! isset( $_GET['action'] ) || 'delice_clear_update_cache' !== $_GET['action'] ) {
            return;
        }

        check_admin_referer( 'delice_clear_update_cache' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to do this.', 'delice-recipe-manager' ) );
        }

        $cache_key = 'delice_gh_updater_' . md5( plugin_basename( DELICE_RECIPE_PLUGIN_FILE ) );
        delete_transient( $cache_key );
        delete_site_transient( 'update_plugins' );

        wp_safe_redirect( admin_url( 'admin.php?page=delice-recipe-settings&delice_cache_cleared=1' ) );
        exit;
    }

    /**
     * Add meta boxes for recipe posts (both custom type and migrated).
     *
     * For the 'post' post type we only show recipe meta boxes when the post is
     * a migrated recipe (has recipe meta).  This avoids cluttering every
     * regular blog post with recipe fields.
     */
    public function add_recipe_meta_boxes() {
        global $post;

        // Always register for the custom post type.
        $post_types = array( 'delice_recipe' );

        // For regular posts only add boxes when the post carries recipe data.
        if ( $post && $post->post_type === 'post' && $this->is_recipe_post( $post ) ) {
            $post_types[] = 'post';
        }

        foreach ( $post_types as $post_type ) {
            $suffix = ( $post_type === 'post' ) ? '_post' : '';

            add_meta_box(
                'delice_recipe_details' . $suffix,
                __( 'Détails de la recette', 'delice-recipe-manager' ),
                array( $this, 'render_recipe_details_meta_box' ),
                $post_type,
                'normal',
                'high'
            );

            add_meta_box(
                'delice_recipe_ingredients' . $suffix,
                __( 'Ingrédients', 'delice-recipe-manager' ),
                array( $this, 'render_ingredients_meta_box' ),
                $post_type,
                'normal',
                'high'
            );

            add_meta_box(
                'delice_recipe_instructions' . $suffix,
                __( 'Instructions', 'delice-recipe-manager' ),
                array( $this, 'render_instructions_meta_box' ),
                $post_type,
                'normal',
                'high'
            );

            add_meta_box(
                'delice_recipe_notes' . $suffix,
                __( 'Notes', 'delice-recipe-manager' ),
                array( $this, 'render_notes_meta_box' ),
                $post_type,
                'normal',
                'default'
            );
        }

        // Taxonomy box only for the custom post type.
        add_meta_box(
            'delice_recipe_taxonomies',
            __( 'Categories & Tags', 'delice-recipe-manager' ),
            array( $this, 'render_taxonomies_meta_box' ),
            'delice_recipe',
            'side',
            'default'
        );
    }

    /**
     * Render recipe details meta box
     */
    public function render_recipe_details_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('delice_recipe_save_meta', 'delice_recipe_meta_nonce');
        
        // Get saved values
        $prep_time = get_post_meta($post->ID, '_delice_recipe_prep_time', true);
        $cook_time = get_post_meta($post->ID, '_delice_recipe_cook_time', true);
        $total_time = get_post_meta($post->ID, '_delice_recipe_total_time', true);
        $servings = get_post_meta($post->ID, '_delice_recipe_servings', true);
        $calories = get_post_meta($post->ID, '_delice_recipe_calories', true);
        $difficulty = get_post_meta($post->ID, '_delice_recipe_difficulty', true);
        
        // Output the fields
        ?>
        <div class="delice-recipe-meta-box">
            <div class="delice-recipe-field">
                <label for="delice_recipe_prep_time"><?php esc_html_e('Temps de préparation (minutes)', 'delice-recipe-manager'); ?></label>
                <input type="number" id="delice_recipe_prep_time" name="delice_recipe_prep_time" value="<?php echo esc_attr($prep_time); ?>" min="0">
            </div>

            <div class="delice-recipe-field">
                <label for="delice_recipe_cook_time"><?php esc_html_e('Temps de cuisson (minutes)', 'delice-recipe-manager'); ?></label>
                <input type="number" id="delice_recipe_cook_time" name="delice_recipe_cook_time" value="<?php echo esc_attr($cook_time); ?>" min="0">
            </div>

            <div class="delice-recipe-field">
                <label for="delice_recipe_total_time"><?php esc_html_e('Temps total (minutes)', 'delice-recipe-manager'); ?></label>
                <input type="number" id="delice_recipe_total_time" name="delice_recipe_total_time" value="<?php echo esc_attr($total_time); ?>" min="0">
            </div>

            <div class="delice-recipe-field">
                <label for="delice_recipe_servings"><?php esc_html_e('Nombre de portions', 'delice-recipe-manager'); ?></label>
                <input type="number" id="delice_recipe_servings" name="delice_recipe_servings" value="<?php echo esc_attr($servings); ?>" min="1">
            </div>

            <div class="delice-recipe-field">
                <label for="delice_recipe_calories"><?php esc_html_e('Calories par portion', 'delice-recipe-manager'); ?></label>
                <input type="number" id="delice_recipe_calories" name="delice_recipe_calories" value="<?php echo esc_attr($calories); ?>" min="0">
            </div>

            <div class="delice-recipe-field">
                <label for="delice_recipe_difficulty"><?php esc_html_e('Difficulté', 'delice-recipe-manager'); ?></label>
                <select id="delice_recipe_difficulty" name="delice_recipe_difficulty">
                    <option value="easy" <?php selected($difficulty, 'easy'); ?>><?php esc_html_e('Facile', 'delice-recipe-manager'); ?></option>
                    <option value="medium" <?php selected($difficulty, 'medium'); ?>><?php esc_html_e('Moyen', 'delice-recipe-manager'); ?></option>
                    <option value="hard" <?php selected($difficulty, 'hard'); ?>><?php esc_html_e('Difficile', 'delice-recipe-manager'); ?></option>
                </select>
            </div>
        </div>
        <?php
    }

    /**
     * Render ingredients meta box
     */
    public function render_ingredients_meta_box($post) {
        $ingredients = get_post_meta($post->ID, '_delice_recipe_ingredients', true);
        if (!is_array($ingredients)) {
            $ingredients = array(array('name' => '', 'amount' => '', 'unit' => ''));
        }
        ?>
        <div class="delice-recipe-meta-box">
            <div id="delice-recipe-ingredients-container">
                <?php foreach ($ingredients as $index => $ingredient) : ?>
                <div class="delice-recipe-ingredient-row">
                    <input type="text" class="ingredient-name" name="delice_recipe_ingredients[<?php echo absint( $index ); ?>][name]"
                           placeholder="<?php esc_attr_e('Ingrédient', 'delice-recipe-manager'); ?>"
                           value="<?php echo esc_attr( isset( $ingredient['name'] ) ? $ingredient['name'] : '' ); ?>">

                    <input type="text" class="ingredient-amount" name="delice_recipe_ingredients[<?php echo absint( $index ); ?>][amount]"
                           placeholder="<?php esc_attr_e('Quantité', 'delice-recipe-manager'); ?>"
                           value="<?php echo esc_attr( isset( $ingredient['amount'] ) ? $ingredient['amount'] : '' ); ?>">

                    <input type="text" class="ingredient-unit" name="delice_recipe_ingredients[<?php echo absint( $index ); ?>][unit]"
                           placeholder="<?php esc_attr_e('Unité', 'delice-recipe-manager'); ?>"
                           value="<?php echo esc_attr( isset( $ingredient['unit'] ) ? $ingredient['unit'] : '' ); ?>">

                    <button type="button" class="button remove-ingredient"><?php esc_html_e('Supprimer', 'delice-recipe-manager'); ?></button>
                </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" id="add-ingredient" class="button"><?php esc_html_e('Ajouter un ingrédient', 'delice-recipe-manager'); ?></button>
        </div>
        <?php
    }

    /**
     * Render instructions meta box
     */
    public function render_instructions_meta_box($post) {
        $instructions = get_post_meta($post->ID, '_delice_recipe_instructions', true);
        if (!is_array($instructions)) {
            $instructions = array(array('step' => 1, 'text' => ''));
        }
        ?>
        <div class="delice-recipe-meta-box">
            <div id="delice-recipe-instructions-container">
                <?php foreach ($instructions as $instruction) : ?>
                <?php $step = absint( isset( $instruction['step'] ) ? $instruction['step'] : 0 ); ?>
                <div class="delice-recipe-instruction-row">
                    <span class="instruction-step"><?php echo esc_html( $step ); ?></span>

                    <textarea class="instruction-text" name="delice_recipe_instructions[<?php echo esc_attr( $step ); ?>][text]"
                              placeholder="<?php esc_attr_e('Instruction', 'delice-recipe-manager'); ?>"><?php echo esc_textarea( isset( $instruction['text'] ) ? $instruction['text'] : '' ); ?></textarea>

                    <input type="hidden" name="delice_recipe_instructions[<?php echo esc_attr( $step ); ?>][step]" value="<?php echo esc_attr( $step ); ?>">

                    <button type="button" class="button remove-instruction"><?php esc_html_e('Supprimer', 'delice-recipe-manager'); ?></button>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="button" id="add-instruction" class="button"><?php esc_html_e('Ajouter une instruction', 'delice-recipe-manager'); ?></button>
        </div>
        <?php
    }

    /**
     * Render taxonomies meta box for manual override
     */
    public function render_taxonomies_meta_box($post) {
        $taxonomy_manager = new Delice_Recipe_Taxonomy_Manager();
        $available_taxonomies = $taxonomy_manager->get_available_taxonomies();
        
        // Get current terms
        $current_cuisine = wp_get_object_terms($post->ID, 'delice_cuisine', array('fields' => 'slugs'));
        $current_course = wp_get_object_terms($post->ID, 'delice_course', array('fields' => 'slugs'));
        $current_dietary = wp_get_object_terms($post->ID, 'delice_dietary', array('fields' => 'slugs'));
        
        ?>
        <div class="delice-recipe-meta-box">
            <p><small><?php _e('Categories are automatically assigned, but you can override them here.', 'delice-recipe-manager'); ?></small></p>
            
            <div class="delice-recipe-field">
                <label for="recipe_cuisine"><?php _e('Cuisine', 'delice-recipe-manager'); ?></label>
                <select id="recipe_cuisine" name="recipe_cuisine">
                    <option value=""><?php _e('Auto-detect', 'delice-recipe-manager'); ?></option>
                    <?php foreach ($available_taxonomies['cuisines'] as $slug => $name) : ?>
                        <option value="<?php echo esc_attr($slug); ?>" <?php selected(in_array($slug, $current_cuisine)); ?>>
                            <?php echo esc_html($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="delice-recipe-field">
                <label for="recipe_course"><?php _e('Course', 'delice-recipe-manager'); ?></label>
                <select id="recipe_course" name="recipe_course">
                    <option value=""><?php _e('Auto-detect', 'delice-recipe-manager'); ?></option>
                    <?php foreach ($available_taxonomies['courses'] as $slug => $name) : ?>
                        <option value="<?php echo esc_attr($slug); ?>" <?php selected(in_array($slug, $current_course)); ?>>
                            <?php echo esc_html($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="delice-recipe-field">
                <label><?php _e('Dietary Options', 'delice-recipe-manager'); ?></label>
                <?php foreach ($available_taxonomies['dietary'] as $slug => $name) : ?>
                    <label>
                        <input type="checkbox" name="recipe_dietary[]" value="<?php echo esc_attr($slug); ?>" 
                               <?php checked(in_array($slug, $current_dietary)); ?>>
                        <?php echo esc_html($name); ?>
                    </label><br>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render notes meta box
     */
    public function render_notes_meta_box($post) {
        $notes = get_post_meta($post->ID, '_delice_recipe_notes', true);
        ?>
        <div class="delice-recipe-meta-box">
            <textarea id="delice_recipe_notes" name="delice_recipe_notes" rows="4" style="width: 100%;"><?php echo esc_textarea($notes); ?></textarea>
            <p class="description"><?php _e('Notes supplémentaires, conseils ou variations pour cette recette.', 'delice-recipe-manager'); ?></p>
        </div>
        <?php
    }

    /**
     * Save recipe meta data
     */
    public function save_recipe_meta($post_id, $post = null) {
        // Only save for recipe posts (custom type or posts with recipe metadata)
        if (!$this->is_recipe_post($post)) {
            return;
        }
        
        // Check if our nonce is set and verify it
        if (!isset($_POST['delice_recipe_meta_nonce']) || !wp_verify_nonce($_POST['delice_recipe_meta_nonce'], 'delice_recipe_save_meta')) {
            return;
        }
        
        // Check if not an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // If it's a revision, don't save meta
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Save recipe details
        if (isset($_POST['delice_recipe_prep_time'])) {
            update_post_meta($post_id, '_delice_recipe_prep_time', absint($_POST['delice_recipe_prep_time']));
        }
        
        if (isset($_POST['delice_recipe_cook_time'])) {
            update_post_meta($post_id, '_delice_recipe_cook_time', absint($_POST['delice_recipe_cook_time']));
        }
        
        if (isset($_POST['delice_recipe_total_time'])) {
            update_post_meta($post_id, '_delice_recipe_total_time', absint($_POST['delice_recipe_total_time']));
        }
        
        if (isset($_POST['delice_recipe_servings'])) {
            update_post_meta($post_id, '_delice_recipe_servings', absint($_POST['delice_recipe_servings']));
        }
        
        if (isset($_POST['delice_recipe_calories'])) {
            update_post_meta($post_id, '_delice_recipe_calories', absint($_POST['delice_recipe_calories']));
        }
        
        if (isset($_POST['delice_recipe_difficulty'])) {
            update_post_meta($post_id, '_delice_recipe_difficulty', sanitize_text_field($_POST['delice_recipe_difficulty']));
        }
        
        // Save ingredients
        if (isset($_POST['delice_recipe_ingredients']) && is_array($_POST['delice_recipe_ingredients'])) {
            $ingredients = array();
            
            foreach ($_POST['delice_recipe_ingredients'] as $ingredient) {
                if (!empty($ingredient['name'])) {
                    $ingredients[] = array(
                        'name' => sanitize_text_field($ingredient['name']),
                        'amount' => sanitize_text_field($ingredient['amount']),
                        'unit' => sanitize_text_field($ingredient['unit']),
                    );
                }
            }
            
            update_post_meta($post_id, '_delice_recipe_ingredients', $ingredients);
        }
        
        // Save instructions
        if (isset($_POST['delice_recipe_instructions']) && is_array($_POST['delice_recipe_instructions'])) {
            $instructions = array();
            $step = 1;
            
            foreach ($_POST['delice_recipe_instructions'] as $instruction) {
                if (!empty($instruction['text'])) {
                    $instructions[] = array(
                        'step' => $step++,
                        'text' => sanitize_textarea_field($instruction['text']),
                    );
                }
            }
            
            update_post_meta($post_id, '_delice_recipe_instructions', $instructions);
        }
        
        // Save manual taxonomy overrides (only for custom post type)
        if ($post && $post->post_type === 'delice_recipe') {
            if (isset($_POST['recipe_cuisine']) && !empty($_POST['recipe_cuisine'])) {
                wp_set_object_terms($post_id, sanitize_text_field($_POST['recipe_cuisine']), 'delice_cuisine');
            }
            
            if (isset($_POST['recipe_course']) && !empty($_POST['recipe_course'])) {
                wp_set_object_terms($post_id, sanitize_text_field($_POST['recipe_course']), 'delice_course');
            }
            
            if (isset($_POST['recipe_dietary']) && is_array($_POST['recipe_dietary'])) {
                $dietary_terms = array_map('sanitize_text_field', $_POST['recipe_dietary']);
                wp_set_object_terms($post_id, $dietary_terms, 'delice_dietary');
            }
        }
        
        // Save notes
        if (isset($_POST['delice_recipe_notes'])) {
            update_post_meta($post_id, '_delice_recipe_notes', sanitize_textarea_field($_POST['delice_recipe_notes']));
        }
        
        // Mark as having recipe metadata if it's a regular post
        if ($post && $post->post_type === 'post') {
            update_post_meta($post_id, '_delice_recipe_migrated', '1');
        }
    }
    
    /**
     * Check if post is a recipe (custom type or migrated)
     */
    private function is_recipe_post($post) {
        if (!$post) {
            return false;
        }
        
        // Always true for custom post type
        if ($post->post_type === 'delice_recipe') {
            return true;
        }
        
        // Check if it's a regular post with recipe metadata
        if ($post->post_type === 'post') {
            $has_ingredients = get_post_meta($post->ID, '_delice_recipe_ingredients', true);
            $has_instructions = get_post_meta($post->ID, '_delice_recipe_instructions', true);
            $is_migrated = get_post_meta($post->ID, '_delice_recipe_migrated', true);
            
            return !empty($has_ingredients) || !empty($has_instructions) || $is_migrated === '1';
        }
        
        return false;
    }

    /**
     * Sanitize display options
     */
    public function sanitize_display_options($options) {
        $sanitized = array();
        
        $boolean_keys = array(
            'show_image', 
            'show_servings', 
            'show_prep_time', 
            'show_cook_time', 
            'show_total_time', 
            'show_calories', 
            'show_difficulty',
            'show_rating',
            'show_ingredients',
            'show_instructions',
            'show_notes',
            'show_faqs',
        );
        
        foreach ($boolean_keys as $key) {
            $sanitized[$key] = isset($options[$key]) ? (bool) $options[$key] : false;
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize attribution settings
     */
    public function sanitize_attribution_settings($settings) {
        $sanitized = array();
        
        $sanitized['kitchen_name'] = isset($settings['kitchen_name']) ? sanitize_text_field($settings['kitchen_name']) : '';
        $sanitized['kitchen_url'] = isset($settings['kitchen_url']) ? esc_url_raw($settings['kitchen_url']) : '';
        $sanitized['show_submitted_by'] = isset($settings['show_submitted_by']) ? (bool) $settings['show_submitted_by'] : true;
        $sanitized['show_tested_by'] = isset($settings['show_tested_by']) ? (bool) $settings['show_tested_by'] : true;
        
        return $sanitized;
    }
    
    /**
     * Sanitize schema settings
     */
    public function sanitize_schema_settings($settings) {
        $sanitized = array();
        
        $sanitized['enable_schema'] = isset($settings['enable_schema']) ? (bool) $settings['enable_schema'] : true;
        $sanitized['publisher_name'] = isset($settings['publisher_name']) ? sanitize_text_field($settings['publisher_name']) : get_bloginfo('name');
        $sanitized['publisher_logo'] = isset($settings['publisher_logo']) ? esc_url_raw($settings['publisher_logo']) : '';
        $sanitized['use_author'] = isset($settings['use_author']) ? (bool) $settings['use_author'] : true;
        $sanitized['default_author'] = isset($settings['default_author']) ? sanitize_text_field($settings['default_author']) : '';
        
        return $sanitized;
    }
    
    /**
     * Sanitize languages array
     */
    public function sanitize_languages_array($languages) {
        if (!is_array($languages)) {
            return array('en_US');
        }
        
        // Filter out any non-string values and sanitize
        $sanitized = array();
        foreach ($languages as $lang) {
            if (is_string($lang)) {
                $sanitized[] = sanitize_text_field($lang);
            }
        }
        
        // Ensure at least one language is always enabled
        if (empty($sanitized)) {
            return array('en_US');
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize language texts
     */
    public function sanitize_language_texts($texts) {
        if (!is_array($texts)) {
            return array();
        }
        
        $sanitized = array();
        foreach ($texts as $language => $strings) {
            if (is_array($strings)) {
                $sanitized[$language] = array();
                foreach ($strings as $key => $value) {
                    $sanitized[$language][$key] = sanitize_text_field($value);
                }
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize boolean setting
     */
    public function sanitize_boolean_setting($value) {
        return !empty($value) ? true : false;
    }

    /**
     * Show admin notices for settings validation
     */
    public function show_settings_notices() {
        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

        // Notice after "Clear Cache & Check Now".
        if ( isset( $_GET['delice_cache_cleared'] ) && '1' === $_GET['delice_cache_cleared'] && 'delice-recipe-settings' === $page ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e( 'Update cache cleared. Fresh release data has been fetched from GitHub.', 'delice-recipe-manager' ); ?></p>
            </div>
            <?php
        }

        if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] && $page === 'delice-recipe-settings' ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Settings saved successfully!', 'delice-recipe-manager'); ?></p>
            </div>
            <?php
            
            // Check for configuration issues
            $api_key = get_option('delice_recipe_ai_api_key', '');
            if (empty($api_key)) {
                ?>
                <div class="notice notice-warning">
                    <p><?php _e('Warning: No OpenAI API key configured. AI recipe generation will not work.', 'delice-recipe-manager'); ?></p>
                </div>
                <?php
            }
            
            $schema_settings = get_option('delice_recipe_schema_settings', array());
            if (empty($schema_settings['publisher_name'])) {
                ?>
                <div class="notice notice-info">
                    <p><?php _e('Info: Consider setting a publisher name for better SEO.', 'delice-recipe-manager'); ?></p>
                </div>
                <?php
            }
        }
    }

    /**
     * Display main dashboard page
     */
    public function display_plugin_admin_dashboard() {
        // Use modern dashboard with all features
        include_once DELICE_RECIPE_PLUGIN_DIR . 'admin/partials/admin-dashboard-modern.php';
    }

    /**
     * Display settings page
     */
    public function display_plugin_settings_page() {
        include_once DELICE_RECIPE_PLUGIN_DIR . 'admin/partials/admin-settings.php';
    }
    
    /**
     * Display Review Settings page
     */
    public function display_review_settings_page() {
        include_once DELICE_RECIPE_PLUGIN_DIR . 'admin/partials/admin-review-settings.php';
    }

    /**
     * Display AI recipe generator page
     */
    public function display_recipe_generator_page() {
        include_once DELICE_RECIPE_PLUGIN_DIR . 'admin/partials/admin-ai-generator.php';
    }
    
    /**
     * Display languages page
     */
    public function display_recipe_languages_page() {
        // Create the languages page
        include_once DELICE_RECIPE_PLUGIN_DIR . 'admin/partials/admin-languages.php';
    }
    
    /**
     * Display migration page
     */
    public function display_recipe_migration_page() {
        include_once DELICE_RECIPE_PLUGIN_DIR . 'admin/partials/admin-migration.php';
    }
    
    /**
     * Display Import/Export page
     */
    public function display_import_export_page() {
        include_once DELICE_RECIPE_PLUGIN_DIR . 'admin/partials/admin-import-export.php';
    }
    
    /**
     * Display AI Generator page (Hybrid Modern)
     */
    public function display_ai_generator_page() {
        include_once DELICE_RECIPE_PLUGIN_DIR . 'admin/partials/admin-ai-generator.php';
    }
    
    /**
     * Display Settings Hub page (Hybrid Modern)
     */
    public function display_settings_hub_page() {
        include_once DELICE_RECIPE_PLUGIN_DIR . 'admin/partials/admin-settings.php';
    }
}
