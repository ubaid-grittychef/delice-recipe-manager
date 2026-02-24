<?php
/**
 * AJAX handlers for Delice Recipe Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX handlers.
 *
 * Called once from Delice_Recipe_Manager::define_admin_hooks() – do NOT also
 * hook it to 'init' to prevent double-registration.
 */
function delice_register_ajax_handlers() {
    // Guard against double-registration (e.g. if the function is ever called
    // a second time accidentally).
    static $registered = false;
    if ( $registered ) {
        return;
    }
    $registered = true;

    add_action( 'wp_ajax_delice_generate_recipe',       'delice_ajax_generate_recipe' );
    add_action( 'wp_ajax_delice_generate_bulk_recipes', 'delice_ajax_generate_bulk_recipes' );
    add_action( 'wp_ajax_delice_purge_recipe_cache',    'delice_ajax_purge_recipe_cache' );

    // Migration AJAX handlers.
    add_action( 'wp_ajax_delice_migrate_recipes',       'delice_ajax_migrate_recipes' );
    add_action( 'wp_ajax_delice_migrate_single_recipe', 'delice_ajax_migrate_single_recipe' );
    add_action( 'wp_ajax_delice_rollback_migration',    'delice_ajax_rollback_migration' );
    add_action( 'wp_ajax_delice_migration_progress',    'delice_ajax_migration_progress' );

    // Review and Rating AJAX handlers.
    add_action( 'wp_ajax_delice_save_rating',           'delice_ajax_save_rating' );
    add_action( 'wp_ajax_nopriv_delice_save_rating',    'delice_ajax_save_rating' );
    add_action( 'wp_ajax_delice_save_review',           'delice_ajax_save_review' );
    add_action( 'wp_ajax_nopriv_delice_save_review',    'delice_ajax_save_review' );
    add_action( 'wp_ajax_delice_get_reviews',           'delice_ajax_get_reviews' );
    add_action( 'wp_ajax_nopriv_delice_get_reviews',    'delice_ajax_get_reviews' );
    add_action( 'wp_ajax_delice_approve_review',        'delice_ajax_approve_review' );
    add_action( 'wp_ajax_delice_delete_review',         'delice_ajax_delete_review' );

    // Settings AJAX handlers.
    add_action( 'wp_ajax_delice_update_reviews_setting', 'delice_ajax_update_reviews_setting' );

    // Import/Export AJAX handlers.
    add_action( 'wp_ajax_delice_export_recipes',  'delice_ajax_export_recipes' );
    add_action( 'wp_ajax_delice_export_settings', 'delice_ajax_export_settings' );
    add_action( 'wp_ajax_delice_import_recipes',  'delice_ajax_import_recipes' );
    add_action( 'wp_ajax_delice_import_settings', 'delice_ajax_import_settings' );

    // NOTE: Delice_Recipe_Reviews instantiation is deferred; it registers its
    // own admin_init hook and does a DB check via maybe_create_reviews_table on
    // 'init'.  We do NOT instantiate it here to avoid running a DB query on
    // every non-AJAX request.
}

/**
 * AJAX handler for generating a single recipe
 */
function delice_ajax_generate_recipe() {
    try {
        // Set timeout and memory limits
        set_time_limit(60);
        ini_set('memory_limit', '512M');
        
        // Check nonce - FIXED: Match the nonce sent from JavaScript
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delice_hybrid_nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed. Please refresh the page and try again.', 'delice-recipe-manager')));
        }
        
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have the necessary permissions.', 'delice-recipe-manager')));
        }
        
        // Get form data directly from POST (FormData sends as individual fields)
        $keywords_hidden = isset($_POST['keywords']) ? sanitize_text_field($_POST['keywords']) : '';
        $target_language = isset($_POST['target_language']) ? sanitize_text_field($_POST['target_language']) : 'en_US';
        $cuisine_type = isset($_POST['cuisine_type']) ? sanitize_text_field($_POST['cuisine_type']) : '';
        $recipe_type = isset($_POST['recipe_type']) ? sanitize_text_field($_POST['recipe_type']) : '';
        $variations = isset($_POST['variations']) ? (array)$_POST['variations'] : array();
        
        // Enhanced debugging
        error_log('AJAX Request received - action: delice_generate_recipe');
        error_log('Keywords: ' . $keywords_hidden);
        error_log('Language: ' . $target_language);
        
        // Validate keywords
        if (empty($keywords_hidden)) {
            error_log('Validation failed: Empty keywords');
            wp_send_json_error(array('message' => __('Please enter at least one recipe keyword.', 'delice-recipe-manager')));
            return;
        }
        
        // Prepare prompt data
        $prompt = array(
            'keyword' => $keywords_hidden,
            'target_language' => $target_language,
            'cuisine_type' => $cuisine_type,
            'recipe_type' => $recipe_type,
        );
        
        // Add variations if present
        if (!empty($variations)) {
            $prompt['variations'] = array_map('sanitize_text_field', $variations);
        }
        
        // Generate recipe with AI
        $ai = new Delice_Recipe_AI();
        $recipe_data = $ai->generate_recipe($prompt);
        
        if (is_wp_error($recipe_data)) {
            error_log('Delice Recipe AI Error: ' . $recipe_data->get_error_message());
            wp_send_json_error(array('message' => $recipe_data->get_error_message()));
        }
        
        // Create recipe post (published or draft) with language metadata
        $auto_publish = isset($_POST['auto_publish']) && $_POST['auto_publish'] === '1';
        $post_id = $ai->create_recipe_post($recipe_data, $auto_publish, $target_language);
        
        if (is_wp_error($post_id)) {
            error_log('Delice Recipe Post Error: ' . $post_id->get_error_message());
            wp_send_json_error(array('message' => $post_id->get_error_message()));
        }
        
        // Get preview HTML
        ob_start();
        include DELICE_RECIPE_PLUGIN_DIR . 'admin/partials/recipe-preview.php';
        $preview_html = ob_get_clean();
        
        // Send success response
        wp_send_json_success(array(
            'post_id' => $post_id,
            'preview' => $preview_html,
            'edit_url' => get_edit_post_link($post_id, ''),
            'title' => $recipe_data['title'],
        ));
        
    } catch (Exception $e) {
        error_log('Delice Recipe Generation Exception: ' . $e->getMessage());
        wp_send_json_error(array('message' => __('An unexpected error occurred. Please try again.', 'delice-recipe-manager')));
    }
}

/**
 * AJAX handler for generating bulk recipes
 */
function delice_ajax_generate_bulk_recipes() {
    try {
        // Set timeout and memory limits
        set_time_limit(120);
        ini_set('memory_limit', '512M');
        
        // Check nonce - FIXED: Match the nonce sent from JavaScript
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delice_hybrid_nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed. Please refresh the page and try again.', 'delice-recipe-manager')));
        }
        
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have the necessary permissions.', 'delice-recipe-manager')));
        }
        
        // Get parameters
        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        $target_language = isset($_POST['target_language']) ? sanitize_text_field($_POST['target_language']) : 'en_US';
        $auto_publish = isset($_POST['auto_publish']) && $_POST['auto_publish'] === '1';
        
        if (empty($keyword)) {
            wp_send_json_error(array('message' => __('Please enter a recipe keyword.', 'delice-recipe-manager')));
        }
        
        // Prepare prompt data
        $prompt = array(
            'keyword' => $keyword,
            'target_language' => $target_language,
        );
        
        // Generate recipe with AI
        $ai = new Delice_Recipe_AI();
        $recipe_data = $ai->generate_recipe($prompt);
        
        if (is_wp_error($recipe_data)) {
            error_log('Delice Recipe AI Bulk Error: ' . $recipe_data->get_error_message());
            wp_send_json_error(array('message' => $recipe_data->get_error_message()));
        }
        
        // Create recipe post (published or draft) with language metadata
        $post_id = $ai->create_recipe_post($recipe_data, $auto_publish, $target_language);
        
        if (is_wp_error($post_id)) {
            error_log('Delice Recipe Post Bulk Error: ' . $post_id->get_error_message());
            wp_send_json_error(array('message' => $post_id->get_error_message()));
        }
        
        // Send success response
        wp_send_json_success(array(
            'post_id' => $post_id,
            'title' => $recipe_data['title'],
            'edit_url' => get_edit_post_link($post_id, ''),
            'view_url' => get_permalink($post_id),
        ));
        
    } catch (Exception $e) {
        error_log('Delice Recipe Bulk Generation Exception: ' . $e->getMessage());
        wp_send_json_error(array('message' => __('An unexpected error occurred. Please try again.', 'delice-recipe-manager')));
    }
}

/**
 * AJAX handler for purging recipe cache
 */
function delice_ajax_purge_recipe_cache() {
    try {
        // Check nonce - FIXED: Use existing delice_recipe_nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delice_recipe_nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'delice-recipe-manager')));
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have the necessary permissions.', 'delice-recipe-manager')));
        }
        
        // Get keyword if specified
        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : null;
        
        // Purge cache
        $ai = new Delice_Recipe_AI();
        $result = $ai->clear_cache($keyword);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Recipe cache purged successfully.', 'delice-recipe-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to purge recipe cache.', 'delice-recipe-manager')));
        }
        
    } catch (Exception $e) {
        error_log('Delice Recipe Cache Purge Exception: ' . $e->getMessage());
        wp_send_json_error(array('message' => __('An unexpected error occurred.', 'delice-recipe-manager')));
    }
}

/**
 * AJAX handler for single recipe migration
 */
function delice_ajax_migrate_single_recipe() {
    try {
        // Verify nonce - check both possible nonces for compatibility
        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            if (wp_verify_nonce($_POST['nonce'], 'delice_hybrid_nonce')) {
                $nonce_valid = true;
            } elseif (wp_verify_nonce($_POST['nonce'], 'delice_recipe_nonce')) {
                $nonce_valid = true;
            }
        }
        
        if (!$nonce_valid) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'delice-recipe-manager')));
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Unauthorized access.', 'delice-recipe-manager')));
        }
        
        $recipe_id = isset($_POST['recipe_id']) ? intval($_POST['recipe_id']) : 0;
        $recipe = get_post($recipe_id);
        
        if (!$recipe || $recipe->post_type !== 'delice_recipe') {
            wp_send_json_error(array('message' => __('Invalid recipe.', 'delice-recipe-manager')));
        }
        
        $migration = new Delice_Recipe_Migration();
        $new_post_id = $migration->migrate_single_recipe($recipe);
        
        if ($new_post_id) {
            wp_send_json_success(array(
                'message' => __('Recipe migrated successfully.', 'delice-recipe-manager'),
                'new_post_id' => $new_post_id,
                'edit_url' => get_edit_post_link($new_post_id)
            ));
        } else {
            wp_send_json_error(array('message' => __('Migration failed.', 'delice-recipe-manager')));
        }
        
    } catch (Exception $e) {
        error_log('Single Migration Exception: ' . $e->getMessage());
        wp_send_json_error(array('message' => __('Migration failed: ', 'delice-recipe-manager') . $e->getMessage()));
    }
}

/**
 * AJAX handler for recipe migration
 */
function delice_ajax_migrate_recipes() {
    try {
        // Set higher limits for migration
        set_time_limit(300);
        ini_set('memory_limit', '1024M');
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delice_recipe_nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'delice-recipe-manager')));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized access.', 'delice-recipe-manager')));
        }

        // Get migration instance
        $migration = new Delice_Recipe_Migration();
        $offset = intval($_POST['offset'] ?? 0);

        // Create backup on first batch; abort if backup fails.
        if ($offset === 0) {
            $backup_ok = $migration->create_backup();
            if ( ! $backup_ok ) {
                wp_send_json_error(array('message' => __('Could not create a backup before migrating. Migration aborted.', 'delice-recipe-manager')));
            }
        }

        // Migrate batch
        $migrated = $migration->migrate_recipes_batch($offset);
        $stats = $migration->get_migration_stats();
        
        wp_send_json_success(array(
            'migrated' => $migrated,
            'stats' => $stats,
            'continue' => $stats['pending_migration'] > 0
        ));
        
    } catch (Exception $e) {
        error_log('Migration Exception: ' . $e->getMessage());
        wp_send_json_error(array('message' => __('Migration failed: ', 'delice-recipe-manager') . $e->getMessage()));
    }
}

/**
 * AJAX handler for migration rollback
 */
function delice_ajax_rollback_migration() {
    try {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delice_recipe_nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'delice-recipe-manager')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized access.', 'delice-recipe-manager')));
        }
        
        $migration = new Delice_Recipe_Migration();
        $migration->rollback_migration();
        
        wp_send_json_success(array(
            'message' => __('Migration rolled back successfully.', 'delice-recipe-manager')
        ));
        
    } catch (Exception $e) {
        error_log('Rollback Exception: ' . $e->getMessage());
        wp_send_json_error(array('message' => __('Rollback failed: ', 'delice-recipe-manager') . $e->getMessage()));
    }
}

/**
 * AJAX handler for migration progress
 */
function delice_ajax_migration_progress() {
    try {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delice_recipe_nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'delice-recipe-manager')));
        }

        // Verify capability – migration data is admin-only.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized access.', 'delice-recipe-manager' ) ) );
        }

        $migration = new Delice_Recipe_Migration();
        $stats = $migration->get_migration_stats();

        wp_send_json_success($stats);

    } catch (Exception $e) {
        error_log('Migration Progress Exception: ' . $e->getMessage());
        wp_send_json_error(array('message' => __('Failed to get migration progress.', 'delice-recipe-manager')));
    }
}

/**
 * AJAX handler for recipe rating - FIXED function name
 */
function delice_ajax_save_rating() {
    $reviews = new Delice_Recipe_Reviews();
    $reviews->save_rating();
}

/**
 * AJAX handler for recipe review - FIXED function name
 */
function delice_ajax_save_review() {
    $reviews = new Delice_Recipe_Reviews();
    $reviews->save_review();
}

/**
 * AJAX handler for getting reviews
 */
function delice_ajax_get_reviews() {
    $reviews = new Delice_Recipe_Reviews();
    $reviews->get_reviews();
}

/**
 * AJAX handler for approving review (admin)
 */
function delice_ajax_approve_review() {
    $reviews = new Delice_Recipe_Reviews();
    $reviews->approve_review();
}

/**
 * AJAX handler for deleting review (admin)
 */
function delice_ajax_delete_review() {
    $reviews = new Delice_Recipe_Reviews();
    $reviews->delete_review();
}

/**
 * AJAX handler for updating reviews setting
 */
function delice_ajax_update_reviews_setting() {
    try {
        // Check nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'delice_recipe_nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'delice-recipe-manager')));
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have the necessary permissions.', 'delice-recipe-manager')));
        }
        
        // Get the setting value
        $setting_value = isset($_POST['setting_value']) ? ($_POST['setting_value'] === '1') : false;
        
        // Update the option
        $result = update_option('delice_recipe_reviews_enabled', $setting_value);
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Reviews setting updated successfully.', 'delice-recipe-manager'),
                'value' => $setting_value
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to update setting.', 'delice-recipe-manager')));
        }
        
    } catch (Exception $e) {
        error_log('Update Reviews Setting Exception: ' . $e->getMessage());
        wp_send_json_error(array('message' => __('An unexpected error occurred.', 'delice-recipe-manager')));
    }
}

/**
 * AJAX handler for getting dashboard stats
 */
function delice_ajax_get_dashboard_stats() {
    check_ajax_referer('delice_recipe_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }
    
    $total_recipes = wp_count_posts('delice_recipe');
    $published = isset($total_recipes->publish) ? $total_recipes->publish : 0;
    $drafts = isset($total_recipes->draft) ? $total_recipes->draft : 0;
    
    wp_send_json_success(array(
        'total' => $published + $drafts,
        'published' => $published,
        'drafts' => $drafts,
        'views' => 0, // Can be implemented with a views tracking system
        'trends' => array(
            'total' => '',
            'published' => ''
        )
    ));
}
add_action('wp_ajax_delice_get_dashboard_stats', 'delice_ajax_get_dashboard_stats');

/**
 * AJAX handler for saving settings.
 *
 * NOTE: We intentionally do NOT write arbitrary option names from the request.
 * Instead we delegate to the typed section handler which whitelists every key.
 */
function delice_ajax_save_settings() {
    check_ajax_referer( 'delice_recipe_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorized' ) );
    }

    // Delegate to the section handler which applies per-key whitelists.
    $section  = isset( $_POST['section'] ) ? sanitize_text_field( wp_unslash( $_POST['section'] ) ) : '';
    $settings = isset( $_POST['settings'] ) && is_array( $_POST['settings'] )
        ? wp_unslash( $_POST['settings'] )
        : array();

    delice_save_section_settings( $section, $settings );

    wp_send_json_success( array( 'message' => 'Settings saved successfully' ) );
}
add_action( 'wp_ajax_delice_save_settings', 'delice_ajax_save_settings' );

/**
 * AJAX handler for saving toggle settings.
 *
 * Whitelisted option suffixes only to prevent arbitrary option overwrites.
 */
function delice_ajax_save_toggle() {
    check_ajax_referer( 'delice_recipe_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorized' ) );
    }

    // Explicitly whitelist allowed toggle settings.
    $allowed_settings = array(
        'reviews_enabled',
        'enable_ai_images',
        'auto_publish',
    );

    $setting = isset( $_POST['setting'] ) ? sanitize_key( wp_unslash( $_POST['setting'] ) ) : '';
    $value   = isset( $_POST['value'] ) ? intval( $_POST['value'] ) : 0;

    if ( $setting && in_array( $setting, $allowed_settings, true ) ) {
        update_option( 'delice_recipe_' . $setting, $value );
        wp_send_json_success( array( 'message' => 'Setting saved' ) );
    }

    wp_send_json_error( array( 'message' => 'Invalid setting' ) );
}
add_action( 'wp_ajax_delice_save_toggle', 'delice_ajax_save_toggle' );

/**
 * AJAX handler for toggling reviews
 */
function delice_ajax_toggle_reviews() {
    check_ajax_referer('delice_recipe_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }
    
    $enabled = isset($_POST['enabled']) ? intval($_POST['enabled']) : 0;
    update_option('delice_recipe_reviews_enabled', $enabled);
    
    wp_send_json_success(array('message' => 'Review system updated'));
}
add_action('wp_ajax_delice_toggle_reviews', 'delice_ajax_toggle_reviews');

/**
 * ========================================
 * HYBRID DESIGN AJAX HANDLERS
 * ========================================
 */

/**
 * Save a single named setting.
 *
 * All allowed setting names are whitelisted.  Unknown names are rejected.
 */
function delice_ajax_save_setting() {
    check_ajax_referer( 'delice_hybrid_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorized' ) );
    }

    $setting = isset( $_POST['setting'] ) ? sanitize_key( wp_unslash( $_POST['setting'] ) ) : '';
    $value   = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';

    if ( ! $setting ) {
        wp_send_json_error( array( 'message' => 'Invalid setting' ) );
    }

    // Display option booleans.
    $display_bool_keys = array(
        'show_image', 'show_servings', 'show_prep_time', 'show_cook_time',
        'show_total_time', 'show_calories', 'show_difficulty', 'show_rating',
        'show_ingredients', 'show_instructions', 'show_notes', 'show_faqs',
        'show_nutrition', 'show_print_button', 'show_attribution',
    );
    if ( in_array( $setting, $display_bool_keys, true ) ) {
        $options             = get_option( 'delice_recipe_display_options', array() );
        $options[ $setting ] = (bool) $value;
        update_option( 'delice_recipe_display_options', $options );
        wp_send_json_success( array( 'message' => 'Setting saved' ) );
    }

    // Schema booleans.
    if ( in_array( $setting, array( 'enable_schema', 'use_author' ), true ) ) {
        $schema_settings             = get_option( 'delice_recipe_schema_settings', array() );
        $schema_settings[ $setting ] = (bool) $value;
        update_option( 'delice_recipe_schema_settings', $schema_settings );
        wp_send_json_success( array( 'message' => 'Setting saved' ) );
    }

    // Attribution booleans.
    if ( in_array( $setting, array( 'show_submitted_by', 'show_tested_by' ), true ) ) {
        $attribution_settings             = get_option( 'delice_recipe_attribution_settings', array() );
        $attribution_settings[ $setting ] = (bool) $value;
        update_option( 'delice_recipe_attribution_settings', $attribution_settings );
        wp_send_json_success( array( 'message' => 'Setting saved' ) );
    }

    // Review booleans.
    if ( in_array( $setting, array( 'reviews_enabled', 'auto_approve', 'allow_anonymous', 'allow_images', 'require_email' ), true ) ) {
        $review_settings             = get_option( 'delice_recipe_review_settings', array() );
        $review_settings[ $setting ] = (bool) $value;
        update_option( 'delice_recipe_review_settings', $review_settings );
        wp_send_json_success( array( 'message' => 'Setting saved' ) );
    }

    // Scalar individual settings.
    $allowed_individual = array( 'reviews_enabled', 'enable_ai_images', 'selected_template', 'default_language', 'image_style', 'image_size' );
    if ( in_array( $setting, $allowed_individual, true ) ) {
        update_option( 'delice_recipe_' . $setting, $value );
        wp_send_json_success( array( 'message' => 'Setting saved' ) );
    }

    wp_send_json_error( array( 'message' => 'Unknown setting: ' . esc_html( $setting ) ) );
}
add_action( 'wp_ajax_delice_save_setting', 'delice_ajax_save_setting' );

/**
 * Save all settings – delegates to the whitelisted section handler.
 */
function delice_ajax_save_all_settings() {
    check_ajax_referer( 'delice_hybrid_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorized' ) );
    }

    $settings = isset( $_POST['settings'] ) && is_array( $_POST['settings'] )
        ? wp_unslash( $_POST['settings'] )
        : array();

    // Determine section from the keys present in the settings array.
    // For bulk saves we save each recognised group individually.
    $known_sections = array( 'display', 'templates', 'schema', 'attribution', 'languages', 'advanced', 'openai', 'reviews' );
    $section        = isset( $_POST['section'] ) ? sanitize_text_field( wp_unslash( $_POST['section'] ) ) : '';

    if ( $section && in_array( $section, $known_sections, true ) ) {
        delice_save_section_settings( $section, $settings );
    } else {
        // Fall back: save each known section that has matching keys.
        foreach ( $known_sections as $sec ) {
            delice_save_section_settings( $sec, $settings );
        }
    }

    wp_send_json_success( array( 'message' => 'Settings saved successfully' ) );
}
add_action( 'wp_ajax_delice_save_all_settings', 'delice_ajax_save_all_settings' );

/**
 * Shared, whitelisted section-save helper.
 *
 * Every writable key is explicitly declared here.  Unknown keys are ignored,
 * preventing arbitrary option-write attacks.
 *
 * @param string $section  Section name.
 * @param array  $settings Raw (but wp_unslashed) settings array.
 */
function delice_save_section_settings( $section, array $settings ) {
    switch ( $section ) {
        case 'display':
            $allowed_display_keys = array(
                'show_image', 'show_servings', 'show_prep_time', 'show_cook_time',
                'show_total_time', 'show_calories', 'show_difficulty', 'show_rating',
                'show_ingredients', 'show_instructions', 'show_notes', 'show_faqs',
                'show_nutrition', 'show_print_button', 'show_attribution',
            );
            $display_options = get_option( 'delice_recipe_display_options', array() );
            foreach ( $settings as $key => $value ) {
                $clean_key = sanitize_key( $key );
                if ( in_array( $clean_key, $allowed_display_keys, true ) ) {
                    $display_options[ $clean_key ] = (bool) $value;
                }
            }
            update_option( 'delice_recipe_display_options', $display_options );
            break;

        case 'templates':
            $allowed_templates = array( 'default', 'elegant' );
            if ( isset( $settings['delice_recipe_selected_template'] ) ) {
                $tpl = sanitize_text_field( $settings['delice_recipe_selected_template'] );
                if ( in_array( $tpl, $allowed_templates, true ) ) {
                    update_option( 'delice_recipe_selected_template', $tpl );
                }
            }
            break;

        case 'schema':
            $schema_settings = get_option( 'delice_recipe_schema_settings', array() );
            $bool_keys = array( 'enable_schema', 'use_author' );
            $text_keys = array( 'publisher_name', 'default_author' );
            $url_keys  = array( 'publisher_logo' );
            foreach ( $settings as $raw_key => $value ) {
                $key = str_replace( 'delice_recipe_schema_', '', sanitize_key( $raw_key ) );
                if ( in_array( $key, $bool_keys, true ) ) {
                    $schema_settings[ $key ] = (bool) $value;
                } elseif ( in_array( $key, $text_keys, true ) ) {
                    $schema_settings[ $key ] = sanitize_text_field( $value );
                } elseif ( in_array( $key, $url_keys, true ) ) {
                    $schema_settings[ $key ] = esc_url_raw( $value );
                }
            }
            update_option( 'delice_recipe_schema_settings', $schema_settings );
            break;

        case 'attribution':
            $attribution_settings = get_option( 'delice_recipe_attribution_settings', array() );
            $bool_keys = array( 'show_submitted_by', 'show_tested_by' );
            $text_keys = array( 'kitchen_name' );
            $url_keys  = array( 'kitchen_url' );
            foreach ( $settings as $raw_key => $value ) {
                $key = str_replace( 'delice_recipe_', '', sanitize_key( $raw_key ) );
                if ( in_array( $key, $bool_keys, true ) ) {
                    $attribution_settings[ $key ] = (bool) $value;
                } elseif ( in_array( $key, $text_keys, true ) ) {
                    $attribution_settings[ $key ] = sanitize_text_field( $value );
                } elseif ( in_array( $key, $url_keys, true ) ) {
                    $attribution_settings[ $key ] = esc_url_raw( $value );
                }
            }
            update_option( 'delice_recipe_attribution_settings', $attribution_settings );
            break;

        case 'languages':
            if ( isset( $settings['delice_default_language'] ) ) {
                update_option( 'delice_recipe_default_language', sanitize_text_field( $settings['delice_default_language'] ) );
            }
            if ( isset( $settings['translations'] ) && is_array( $settings['translations'] ) ) {
                $lang                   = get_option( 'delice_recipe_default_language', 'en_US' );
                $sanitized_translations = array_map( 'sanitize_text_field', $settings['translations'] );
                update_option( 'delice_recipe_translations_' . sanitize_key( $lang ), $sanitized_translations );
            }
            break;

        case 'advanced':
            // Only a specific, audited list of advanced options may be written.
            $allowed_advanced = array(
                'delice_recipe_default_language',
                'delice_recipe_reviews_enabled',
                'delice_recipe_enable_ai_images',
                'delice_recipe_image_style',
                'delice_recipe_image_size',
                'delice_recipe_selected_template',
            );
            foreach ( $settings as $raw_key => $value ) {
                $key = sanitize_key( $raw_key );
                if ( in_array( $key, $allowed_advanced, true ) ) {
                    update_option( $key, sanitize_text_field( $value ) );
                }
            }
            break;

        case 'openai':
            if ( isset( $settings['delice_recipe_ai_api_key'] ) ) {
                update_option( 'delice_recipe_ai_api_key', sanitize_text_field( $settings['delice_recipe_ai_api_key'] ) );
            }
            if ( isset( $settings['delice_recipe_enable_ai_images'] ) ) {
                update_option( 'delice_recipe_enable_ai_images', (bool) $settings['delice_recipe_enable_ai_images'] );
            }
            if ( isset( $settings['delice_recipe_image_style'] ) ) {
                $allowed_styles = array( 'vivid', 'natural' );
                $style = sanitize_text_field( $settings['delice_recipe_image_style'] );
                if ( in_array( $style, $allowed_styles, true ) ) {
                    update_option( 'delice_recipe_image_style', $style );
                }
            }
            if ( isset( $settings['delice_recipe_image_size'] ) ) {
                $allowed_sizes = array( '1024x1024', '1792x1024', '1024x1792', '800x600', '600x600', '700x700', '600x800', '900x600' );
                $size = sanitize_text_field( $settings['delice_recipe_image_size'] );
                if ( in_array( $size, $allowed_sizes, true ) ) {
                    update_option( 'delice_recipe_image_size', $size );
                }
            }
            break;

        case 'reviews':
            $review_settings = get_option( 'delice_recipe_review_settings', array() );
            $bool_keys = array( 'reviews_enabled', 'auto_approve', 'allow_anonymous', 'allow_images', 'require_email' );
            foreach ( $settings as $raw_key => $value ) {
                $key = str_replace( 'delice_recipe_', '', sanitize_key( $raw_key ) );
                if ( in_array( $key, $bool_keys, true ) ) {
                    $review_settings[ $key ] = (bool) $value;
                } elseif ( 'max_image_size' === $key ) {
                    $review_settings['max_image_size'] = max( 1, min( 50, intval( $value ) ) );
                }
            }
            update_option( 'delice_recipe_review_settings', $review_settings );
            break;

        // Unknown sections are silently ignored.
    }
}

/**
 * Save section settings AJAX handler.
 */
function delice_ajax_save_section() {
    check_ajax_referer( 'delice_hybrid_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorized' ) );
    }

    $section  = isset( $_POST['section'] ) ? sanitize_text_field( wp_unslash( $_POST['section'] ) ) : '';
    $settings = isset( $_POST['settings'] ) && is_array( $_POST['settings'] )
        ? wp_unslash( $_POST['settings'] )
        : array();

    $known_sections = array( 'display', 'templates', 'schema', 'attribution', 'languages', 'advanced', 'openai', 'reviews' );

    if ( ! in_array( $section, $known_sections, true ) ) {
        wp_send_json_error( array( 'message' => 'Invalid section' ) );
    }

    delice_save_section_settings( $section, $settings );

    wp_send_json_success( array( 'message' => 'Section saved successfully' ) );
}
add_action( 'wp_ajax_delice_save_section', 'delice_ajax_save_section' );

/**
 * Get translations for language
 */
function delice_ajax_get_translations() {
    check_ajax_referer('delice_hybrid_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }
    
    $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : 'en_US';
    $translations = get_option('delice_recipe_translations_' . $language, array());
    
    // Default translations if not set
    if (empty($translations)) {
        $translations = array(
            'ingredients' => 'Ingredients',
            'instructions' => 'Instructions',
            'servings' => 'Servings',
            'prep_time' => 'Prep Time',
            'cook_time' => 'Cook Time',
            'total_time' => 'Total Time',
            'difficulty' => 'Difficulty',
            'calories' => 'Calories',
            'notes' => 'Notes',
            'faqs' => 'FAQs',
            'print_button' => 'Print Recipe',
            'rating' => 'Rating',
            'reviews' => 'Reviews',
            'submitted_by' => 'Submitted by',
            'tested_by' => 'Tested by'
        );
    }
    
    wp_send_json_success($translations);
}

/**
 * Export recipes to JSON
 */
function delice_ajax_export_recipes() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delice_recipe_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'delice-recipe-manager')));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Unauthorized', 'delice-recipe-manager')));
    }
    
    require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-import-export.php';
    $importer = new Delice_Recipe_Import_Export();
    
    $export_type = isset($_POST['export_type']) ? sanitize_text_field($_POST['export_type']) : 'all';
    $recipe_ids = isset($_POST['recipe_ids']) ? array_map('intval', $_POST['recipe_ids']) : array();
    
    if ($export_type === 'all') {
        $data = $importer->export_recipes();
    } elseif ($export_type === 'selected') {
        $data = $importer->export_recipes($recipe_ids);
    } else {
        wp_send_json_error(array('message' => __('Invalid export type', 'delice-recipe-manager')));
    }
    
    wp_send_json_success(array(
        'data' => $data,
        'filename' => 'delice-recipes-export-' . date('Y-m-d-H-i-s') . '.json'
    ));
}

/**
 * Export settings to JSON
 */
function delice_ajax_export_settings() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delice_recipe_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'delice-recipe-manager')));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Unauthorized', 'delice-recipe-manager')));
    }
    
    require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-import-export.php';
    $importer = new Delice_Recipe_Import_Export();
    
    $data = $importer->export_settings();
    
    wp_send_json_success(array(
        'data' => $data,
        'filename' => 'delice-settings-export-' . date('Y-m-d-H-i-s') . '.json'
    ));
}

/**
 * Import recipes from JSON
 */
function delice_ajax_import_recipes() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delice_recipe_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'delice-recipe-manager')));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Unauthorized', 'delice-recipe-manager')));
    }
    
    // Get JSON data
    $json_data = isset($_POST['json_data']) ? wp_unslash($_POST['json_data']) : '';

    if (empty($json_data)) {
        wp_send_json_error(array('message' => __('No data provided', 'delice-recipe-manager')));
    }

    $import_data = json_decode($json_data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error(array('message' => __('Invalid JSON data', 'delice-recipe-manager')));
    }

    require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-import-export.php';
    $importer = new Delice_Recipe_Import_Export();
    
    $options = array(
        'skip_existing' => isset($_POST['skip_existing']) && $_POST['skip_existing'] === 'true',
        'update_existing' => isset($_POST['update_existing']) && $_POST['update_existing'] === 'true',
        'import_images' => isset($_POST['import_images']) && $_POST['import_images'] === 'true',
        'match_by' => isset($_POST['match_by']) ? sanitize_text_field($_POST['match_by']) : 'title'
    );
    
    $results = $importer->import_recipes($import_data, $options);
    
    if (is_wp_error($results)) {
        wp_send_json_error(array('message' => $results->get_error_message()));
    }
    
    wp_send_json_success($results);
}

/**
 * Import settings from JSON
 */
function delice_ajax_import_settings() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delice_recipe_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'delice-recipe-manager')));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Unauthorized', 'delice-recipe-manager')));
    }
    
    // Get JSON data
    $json_data = isset($_POST['json_data']) ? wp_unslash($_POST['json_data']) : '';

    if (empty($json_data)) {
        wp_send_json_error(array('message' => __('No data provided', 'delice-recipe-manager')));
    }

    $settings_data = json_decode($json_data, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error(array('message' => __('Invalid JSON data', 'delice-recipe-manager')));
    }
    
    require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-import-export.php';
    $importer = new Delice_Recipe_Import_Export();
    
    $merge = isset($_POST['merge']) && $_POST['merge'] === 'true';
    
    $result = $importer->import_settings($settings_data, $merge);
    
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }
    
    wp_send_json_success($result);
}
add_action('wp_ajax_delice_get_translations', 'delice_ajax_get_translations');

/**
 * Save Language Settings
 */
function delice_ajax_save_language_settings() {
    // Check nonce
    if (!isset($_POST['delice_recipe_language_nonce']) || !wp_verify_nonce($_POST['delice_recipe_language_nonce'], 'delice_recipe_language_settings')) {
        wp_send_json_error(array('message' => __('Security verification failed.', 'delice-recipe-manager')));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to save settings.', 'delice-recipe-manager')));
    }
    
    // Get the language manager instance
    $language = new Delice_Recipe_Language();
    
    // Save default language
    if (isset($_POST['default_language'])) {
        update_option('delice_recipe_default_language', sanitize_text_field($_POST['default_language']));
    }
    
    // Save enabled languages
    if (isset($_POST['enabled_languages']) && is_array($_POST['enabled_languages'])) {
        $enabled = array_map('sanitize_text_field', $_POST['enabled_languages']);
        update_option('delice_recipe_enabled_languages', $enabled);
    } else {
        update_option('delice_recipe_enabled_languages', array());
    }
    
    // Save language texts
    if (isset($_POST['language_texts']) && is_array($_POST['language_texts'])) {
        $language_texts = array();
        foreach ($_POST['language_texts'] as $lang_code => $texts) {
            $lang_code = sanitize_text_field($lang_code);
            $language_texts[$lang_code] = array();
            
            if (is_array($texts)) {
                foreach ($texts as $key => $value) {
                    $key = sanitize_text_field($key);
                    $value = sanitize_text_field($value);
                    $language_texts[$lang_code][$key] = $value;
                }
            }
        }
        update_option('delice_recipe_language_texts', $language_texts);
    }
    
    wp_send_json_success(array('message' => __('Language settings saved successfully!', 'delice-recipe-manager')));
}
add_action('wp_ajax_delice_save_language_settings', 'delice_ajax_save_language_settings');

/**
 * Save Display Setting (individual checkbox)
 */
function delice_ajax_save_display_setting() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delice_dashboard_nonce')) {
        wp_send_json_error(array('message' => __('Security verification failed.', 'delice-recipe-manager')));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to save settings.', 'delice-recipe-manager')));
    }
    
    $allowed_display_keys = array(
        'show_image', 'show_servings', 'show_prep_time', 'show_cook_time',
        'show_total_time', 'show_calories', 'show_difficulty', 'show_rating',
        'show_ingredients', 'show_instructions', 'show_notes', 'show_faqs',
        'show_nutrition', 'show_print_button', 'show_attribution',
    );

    $setting_key = isset( $_POST['setting_key'] ) ? sanitize_key( wp_unslash( $_POST['setting_key'] ) ) : '';
    $value       = isset( $_POST['value'] ) && ( $_POST['value'] === '1' || $_POST['value'] === 1 );

    if ( empty( $setting_key ) || ! in_array( $setting_key, $allowed_display_keys, true ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid setting key.', 'delice-recipe-manager' ) ) );
    }

    $display_options                 = get_option( 'delice_recipe_display_options', array() );
    $display_options[ $setting_key ] = $value;
    update_option( 'delice_recipe_display_options', $display_options );

    wp_send_json_success( array( 'message' => __( 'Display setting saved successfully!', 'delice-recipe-manager' ) ) );
}
add_action( 'wp_ajax_delice_save_display_setting', 'delice_ajax_save_display_setting' );

/**
 * Save Review Settings (entire form)
 */
function delice_ajax_save_review_settings() {
    // Check nonce
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'delice_recipe_review_settings')) {
        wp_send_json_error(array('message' => __('Security verification failed.', 'delice-recipe-manager')));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to save settings.', 'delice-recipe-manager')));
    }
    
    // Prepare review settings
    $review_settings = array(
        'auto_approve' => isset($_POST['auto_approve']) && $_POST['auto_approve'] === '1',
        'allow_anonymous' => isset($_POST['allow_anonymous']) && $_POST['allow_anonymous'] === '1',
        'require_email' => isset($_POST['require_email']) && $_POST['require_email'] === '1',
        'allow_images' => isset($_POST['allow_images']) && $_POST['allow_images'] === '1',
        'max_image_size' => isset($_POST['max_image_size']) ? intval($_POST['max_image_size']) : 5,
    );
    
    // Save settings
    update_option('delice_recipe_review_settings', $review_settings);
    
    wp_send_json_success(array('message' => __('Review settings saved successfully!', 'delice-recipe-manager')));
}
add_action('wp_ajax_delice_save_review_settings', 'delice_ajax_save_review_settings');

/**
 * Save Settings Hub Section – delegates to the shared whitelisted handler.
 */
function delice_ajax_save_settings_hub_section() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'delice_settings_hub_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'delice-recipe-manager' ) ) );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Unauthorized access.', 'delice-recipe-manager' ) ) );
    }

    $known_sections = array( 'display', 'templates', 'schema', 'attribution', 'languages', 'advanced', 'openai', 'reviews' );
    $section        = isset( $_POST['section'] ) ? sanitize_text_field( wp_unslash( $_POST['section'] ) ) : '';
    $settings       = isset( $_POST['settings'] ) && is_array( $_POST['settings'] )
        ? wp_unslash( $_POST['settings'] )
        : array();

    if ( ! in_array( $section, $known_sections, true ) || empty( $settings ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid section or data.', 'delice-recipe-manager' ) ) );
    }

    delice_save_section_settings( $section, $settings );
    wp_send_json_success( array( 'message' => __( 'Settings saved!', 'delice-recipe-manager' ) ) );
}
add_action( 'wp_ajax_delice_save_settings_hub_section', 'delice_ajax_save_settings_hub_section' );
