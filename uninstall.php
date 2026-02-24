<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Removes all plugin data: options, custom tables, and post meta.
 * Post meta removal is intentionally skipped unless the user has set
 * 'delice_recipe_delete_data_on_uninstall' to avoid accidental data loss.
 */

// If uninstall.php is not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Only perform full data removal if the administrator has explicitly opted in.
$delete_data = get_option( 'delice_recipe_delete_data_on_uninstall', false );

// Always remove plugin options.
$options_to_delete = array(
    'delice_recipe_version',
    'delice_recipe_selected_template',
    'delice_recipe_display_options',
    'delice_recipe_attribution_settings',
    'delice_recipe_schema_settings',
    'delice_recipe_ai_api_key',
    'delice_recipe_enable_ai_images',
    'delice_recipe_image_style',
    'delice_recipe_image_size',
    'delice_recipe_default_language',
    'delice_recipe_enabled_languages',
    'delice_recipe_language_texts',
    'delice_recipe_reviews_enabled',
    'delice_recipe_review_settings',
    'delice_recipe_delete_data_on_uninstall',
    'delice_eeat_db_version',
);

foreach ( $options_to_delete as $option ) {
    delete_option( $option );
}

// Remove language translation options (dynamic keys).
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE 'delice_recipe_translations_%'
        OR option_name LIKE 'delice_recipe_language_%'"
);

// Remove all AI-cache transients.
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_delice_recipe_ai_%'
        OR option_name LIKE '_transient_timeout_delice_recipe_ai_%'"
);

if ( $delete_data ) {
    // Drop custom database tables.
    $tables = array(
        $wpdb->prefix . 'delice_recipe_reviews',
        $wpdb->prefix . 'delice_eeat_author_profiles',
        $wpdb->prefix . 'delice_eeat_recipe_testing',
        $wpdb->prefix . 'delice_eeat_user_submissions',
    );

    foreach ( $tables as $table ) {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are hard-coded.
        $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
    }

    // Remove all post meta for custom post type and migrated posts.
    $meta_keys = array(
        '_delice_recipe_prep_time',
        '_delice_recipe_cook_time',
        '_delice_recipe_total_time',
        '_delice_recipe_servings',
        '_delice_recipe_calories',
        '_delice_recipe_difficulty',
        '_delice_recipe_notes',
        '_delice_recipe_ingredients',
        '_delice_recipe_instructions',
        '_delice_recipe_faqs',
        '_delice_recipe_nutrition',
        '_delice_recipe_rating_average',
        '_delice_recipe_rating_count',
        '_delice_recipe_migrated',
        '_delice_recipe_original_id',
        '_delice_migration_new_id',
        '_delice_recipe_language',
        '_delice_recipe_cuisine',
        '_delice_recipe_course',
        '_delice_recipe_keywords',
    );

    foreach ( $meta_keys as $meta_key ) {
        $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => $meta_key ), array( '%s' ) );
    }

    // Delete all recipe custom posts.
    $recipe_ids = $wpdb->get_col(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'delice_recipe'"
    );

    foreach ( $recipe_ids as $recipe_id ) {
        wp_delete_post( intval( $recipe_id ), true );
    }
}

// Flush rewrite rules to remove custom slugs.
flush_rewrite_rules();
