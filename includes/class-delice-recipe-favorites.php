<?php
/**
 * User Favorites / Saved Recipes (v4.0.0)
 *
 * Stores favourite recipe IDs in user meta for logged-in users.
 * Anonymous users handle state client-side via localStorage.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Delice_Recipe_Favorites' ) ) {

class Delice_Recipe_Favorites {

    const META_KEY = '_delice_favorite_recipes';

    public function __construct() {
        add_action( 'wp_ajax_delice_toggle_favorite',        array( $this, 'ajax_toggle_favorite' ) );
        add_action( 'wp_ajax_nopriv_delice_toggle_favorite', array( $this, 'ajax_toggle_favorite' ) );
        add_action( 'wp_ajax_delice_get_favorites',          array( $this, 'ajax_get_favorites' ) );
    }

    /**
     * Toggle a recipe in/out of the current user's favourites.
     * For anonymous users, returns a signal so the client handles localStorage.
     */
    public function ajax_toggle_favorite() {
        if ( ! check_ajax_referer( 'delice_favorites_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
            return;
        }

        $recipe_id = absint( $_POST['recipe_id'] ?? 0 );
        if ( ! $recipe_id ) {
            wp_send_json_error( array( 'message' => 'Invalid recipe ID.' ) );
            return;
        }

        $user_id = get_current_user_id();

        // Anonymous — client handles localStorage
        if ( ! $user_id ) {
            wp_send_json_success( array( 'favorited' => null, 'anonymous' => true ) );
            return;
        }

        $favorites  = self::get_user_favorites( $user_id );
        $key        = array_search( $recipe_id, $favorites, true );
        $favorited  = false;

        if ( $key !== false ) {
            array_splice( $favorites, $key, 1 );
        } else {
            $favorites[] = $recipe_id;
            $favorited   = true;
        }

        self::set_user_favorites( $user_id, $favorites );

        wp_send_json_success( array(
            'favorited' => $favorited,
            'count'     => count( $favorites ),
        ) );
    }

    /**
     * Return all favourite recipe IDs for the current logged-in user.
     */
    public function ajax_get_favorites() {
        if ( ! check_ajax_referer( 'delice_favorites_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
            return;
        }

        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            wp_send_json_error( array( 'message' => 'Not logged in.' ) );
            return;
        }

        wp_send_json_success( array(
            'favorites' => self::get_user_favorites( $user_id ),
        ) );
    }

    /**
     * Return an array of recipe IDs saved by $user_id.
     *
     * @param int $user_id
     * @return int[]
     */
    public static function get_user_favorites( $user_id ) {
        $raw = get_user_meta( (int) $user_id, self::META_KEY, true );
        if ( ! is_array( $raw ) ) {
            return array();
        }
        return array_map( 'absint', $raw );
    }

    /**
     * Persist the favourites array for $user_id.
     *
     * @param int   $user_id
     * @param int[] $ids
     */
    public static function set_user_favorites( $user_id, array $ids ) {
        $ids = array_values( array_unique( array_map( 'absint', $ids ) ) );
        update_user_meta( (int) $user_id, self::META_KEY, $ids );
    }
}

} // end class_exists
