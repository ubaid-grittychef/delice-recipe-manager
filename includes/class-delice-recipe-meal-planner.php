<?php
/**
 * Delice Recipe Manager — Meal Planner (v4.1.0)
 *
 * Stores per-user weekly meal plans in a custom DB table.
 * Provides AJAX endpoints for add/remove/list and a [delice_meal_planner] shortcode.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Delice_Recipe_Meal_Planner {

    const TABLE_SUFFIX = 'delice_meal_plans';

    public function __construct() {
        add_action( 'wp_ajax_delice_meal_plan_list',   array( $this, 'ajax_list' ) );
        add_action( 'wp_ajax_delice_meal_plan_add',    array( $this, 'ajax_add' ) );
        add_action( 'wp_ajax_delice_meal_plan_remove', array( $this, 'ajax_remove' ) );
        add_action( 'wp_ajax_delice_meal_plan_search', array( $this, 'ajax_search_recipes' ) );
        add_shortcode( 'delice_meal_planner', array( $this, 'render_shortcode' ) );
    }

    // ── DB table ───────────────────────────────────────────────────────────

    public static function create_table() {
        global $wpdb;
        $table   = $wpdb->prefix . self::TABLE_SUFFIX;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id     BIGINT(20) UNSIGNED NOT NULL,
            recipe_id   BIGINT(20) UNSIGNED NOT NULL,
            plan_date   DATE NOT NULL,
            meal_slot   VARCHAR(20) NOT NULL DEFAULT 'dinner',
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_date (user_id, plan_date),
            KEY recipe (recipe_id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    // ── AJAX: list meals for a date range ──────────────────────────────────

    public function ajax_list() {
        check_ajax_referer( 'delice_meal_planner_nonce', 'nonce' );
        $user_id = get_current_user_id();
        if ( ! $user_id ) { wp_send_json_error( 'Not logged in' ); }

        $start = sanitize_text_field( $_POST['start'] ?? '' );
        $end   = sanitize_text_field( $_POST['end']   ?? '' );
        if ( ! $start || ! $end ) { wp_send_json_error( 'Missing dates' ); }

        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_SUFFIX;

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, recipe_id, plan_date, meal_slot FROM $table
             WHERE user_id = %d AND plan_date BETWEEN %s AND %s
             ORDER BY plan_date ASC, FIELD(meal_slot,'breakfast','lunch','dinner','snack')",
            $user_id, $start, $end
        ) );

        $meals = array();
        foreach ( $rows as $row ) {
            $meals[] = array(
                'id'        => intval( $row->id ),
                'recipeId'  => intval( $row->recipe_id ),
                'title'     => get_the_title( $row->recipe_id ),
                'thumbnail' => get_the_post_thumbnail_url( $row->recipe_id, 'thumbnail' ) ?: '',
                'date'      => $row->plan_date,
                'slot'      => $row->meal_slot,
                'editUrl'   => get_edit_post_link( $row->recipe_id, 'raw' ),
                'viewUrl'   => get_permalink( $row->recipe_id ),
            );
        }

        wp_send_json_success( $meals );
    }

    // ── AJAX: add a meal ───────────────────────────────────────────────────

    public function ajax_add() {
        check_ajax_referer( 'delice_meal_planner_nonce', 'nonce' );
        $user_id = get_current_user_id();
        if ( ! $user_id ) { wp_send_json_error( 'Not logged in' ); }

        $recipe_id = absint( $_POST['recipe_id'] ?? 0 );
        $date      = sanitize_text_field( $_POST['date'] ?? '' );
        $slot      = sanitize_key( $_POST['slot'] ?? 'dinner' );

        if ( ! $recipe_id || ! $date ) { wp_send_json_error( 'Missing data' ); }
        if ( ! in_array( $slot, array( 'breakfast', 'lunch', 'dinner', 'snack' ), true ) ) {
            $slot = 'dinner';
        }

        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_SUFFIX;

        $wpdb->insert( $table, array(
            'user_id'   => $user_id,
            'recipe_id' => $recipe_id,
            'plan_date' => $date,
            'meal_slot' => $slot,
        ), array( '%d', '%d', '%s', '%s' ) );

        wp_send_json_success( array( 'id' => $wpdb->insert_id ) );
    }

    // ── AJAX: remove a meal ────────────────────────────────────────────────

    public function ajax_remove() {
        check_ajax_referer( 'delice_meal_planner_nonce', 'nonce' );
        $user_id = get_current_user_id();
        if ( ! $user_id ) { wp_send_json_error( 'Not logged in' ); }

        $id = absint( $_POST['meal_id'] ?? 0 );
        if ( ! $id ) { wp_send_json_error( 'Missing meal ID' ); }

        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_SUFFIX;

        $wpdb->delete( $table, array( 'id' => $id, 'user_id' => $user_id ), array( '%d', '%d' ) );
        wp_send_json_success();
    }

    // ── Shortcode ──────────────────────────────────────────────────────────

    public function render_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p class="delice-mp-login">' . esc_html__( 'Please log in to use the meal planner.', 'delice-recipe-manager' ) . '</p>';
        }

        $ver = defined( 'DELICE_RECIPE_VERSION' ) ? DELICE_RECIPE_VERSION : '1.0.0';

        wp_enqueue_style(
            'delice-meal-planner',
            DELICE_RECIPE_PLUGIN_URL . 'public/css/components/recipe-meal-planner.css',
            array(),
            $ver
        );
        wp_enqueue_script(
            'delice-meal-planner',
            DELICE_RECIPE_PLUGIN_URL . 'public/js/delice-meal-planner.js',
            array( 'jquery' ),
            $ver,
            true
        );
        wp_localize_script( 'delice-meal-planner', 'deliceMealPlannerData', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'delice_meal_planner_nonce' ),
            'strings' => array(
                'breakfast' => __( 'Breakfast', 'delice-recipe-manager' ),
                'lunch'     => __( 'Lunch', 'delice-recipe-manager' ),
                'dinner'    => __( 'Dinner', 'delice-recipe-manager' ),
                'snack'     => __( 'Snack', 'delice-recipe-manager' ),
                'addMeal'   => __( 'Add Recipe', 'delice-recipe-manager' ),
                'remove'    => __( 'Remove', 'delice-recipe-manager' ),
                'noMeals'   => __( 'No meals planned', 'delice-recipe-manager' ),
                'search'    => __( 'Search recipes...', 'delice-recipe-manager' ),
                'today'     => __( 'Today', 'delice-recipe-manager' ),
                'week'      => __( 'Week of', 'delice-recipe-manager' ),
            ),
        ) );

        return '<div id="delice-meal-planner" class="delice-meal-planner"></div>';
    }

    // ── AJAX: search recipes for the picker ────────────────────────────────

    public function ajax_search_recipes() {
        check_ajax_referer( 'delice_meal_planner_nonce', 'nonce' );
        if ( ! get_current_user_id() ) { wp_send_json_error( 'Not logged in' ); }

        $q = sanitize_text_field( $_POST['query'] ?? '' );
        $args = array(
            'post_type'      => array( 'delice_recipe', 'post' ),
            'post_status'    => 'publish',
            's'              => $q,
            'posts_per_page' => 10,
            'meta_query'     => array(
                'relation' => 'OR',
                array( 'key' => '_delice_recipe_ingredients', 'compare' => 'EXISTS' ),
            ),
        );

        $posts   = get_posts( $args );
        $results = array();
        foreach ( $posts as $p ) {
            $results[] = array(
                'id'        => $p->ID,
                'title'     => $p->post_title,
                'thumbnail' => get_the_post_thumbnail_url( $p->ID, 'thumbnail' ) ?: '',
            );
        }
        wp_send_json_success( $results );
    }
}
