<?php
/**
 * Related Recipes — smart reciprocal interlinking
 *
 * Algorithm: recipes that share the most taxonomy terms with the current recipe
 * are ranked highest. Because the ranking is based on shared terms (symmetric),
 * if Recipe B appears in Recipe A's related list, Recipe A will also appear in
 * Recipe B's list — giving natural bidirectional interlinking.
 *
 * Manual override: set `_delice_related_recipes` post meta to an array of IDs
 * to pin specific related recipes and skip the auto-query.
 *
 * Results are cached per recipe ID for 12 hours via transients, and invalidated
 * on save_post.
 *
 * v3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Delice_Recipe_Related' ) ) :

class Delice_Recipe_Related {

    const TRANSIENT_PREFIX = 'delice_related_';
    const CACHE_TTL        = 43200; // 12 hours

    /**
     * Register hooks.
     */
    public static function init() {
        add_action( 'save_post', array( __CLASS__, 'clear_cache' ) );
    }

    /**
     * Clear transient when a recipe is saved.
     *
     * @param int $post_id
     */
    public static function clear_cache( $post_id ) {
        delete_transient( self::TRANSIENT_PREFIX . absint( $post_id ) );
    }

    /**
     * Get related recipes for a given recipe ID.
     *
     * @param int $recipe_id
     * @param int $limit     Max number of related recipes (default 3).
     * @return WP_Post[]
     */
    public static function get( $recipe_id, $limit = 3 ) {
        $recipe_id = absint( $recipe_id );
        if ( ! $recipe_id ) return array();

        $cache_key = self::TRANSIENT_PREFIX . $recipe_id;
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) return $cached;

        $results = self::resolve( $recipe_id, $limit );
        set_transient( $cache_key, $results, self::CACHE_TTL );
        return $results;
    }

    /**
     * Internal resolver — checks manual override first, then runs auto-query.
     */
    private static function resolve( $recipe_id, $limit ) {
        // Manual override wins.
        $manual = get_post_meta( $recipe_id, '_delice_related_recipes', true );
        if ( is_array( $manual ) && count( $manual ) >= 2 ) {
            $posts = array();
            foreach ( array_slice( $manual, 0, $limit ) as $id ) {
                $p = get_post( absint( $id ) );
                if ( $p && $p->post_status === 'publish' ) {
                    $posts[] = $p;
                }
            }
            if ( ! empty( $posts ) ) return $posts;
        }

        return self::auto_query( $recipe_id, $limit );
    }

    /**
     * Query recipes by taxonomy overlap (cuisine × 3 + course × 2 + keyword × 1).
     * Falls back to recency if no taxonomy match exists.
     */
    private static function auto_query( $recipe_id, $limit ) {
        $taxonomies = array( 'delice_cuisine', 'delice_course', 'delice_keyword' );
        $weights    = array( 'delice_cuisine' => 3, 'delice_course' => 2, 'delice_keyword' => 1 );

        // Gather all term IDs for this recipe across the relevant taxonomies.
        $tax_query_parts = array( 'relation' => 'OR' );
        $found_terms     = false;

        foreach ( $taxonomies as $tax ) {
            $terms = wp_get_object_terms( $recipe_id, $tax, array( 'fields' => 'ids' ) );
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                $tax_query_parts[] = array(
                    'taxonomy' => $tax,
                    'field'    => 'term_id',
                    'terms'    => $terms,
                );
                $found_terms = true;
            }
        }

        // Base args — fetch more than needed so we can rank and slice.
        $args = array(
            'post_type'           => array( 'delice_recipe' ),
            'post_status'         => 'publish',
            'posts_per_page'      => max( 12, $limit * 4 ),
            'post__not_in'        => array( $recipe_id ),
            'no_found_rows'       => true,
            'ignore_sticky_posts' => true,
        );

        if ( $found_terms ) {
            $args['tax_query'] = $tax_query_parts;
        }

        $query = new WP_Query( $args );
        $posts = $query->posts;
        wp_reset_postdata();

        if ( empty( $posts ) ) {
            // Absolute fallback — just return latest recipes.
            return self::latest_recipes( $recipe_id, $limit );
        }

        // Score each post by weighted taxonomy overlap.
        $scored = array();
        foreach ( $posts as $post ) {
            $score = 0;
            foreach ( $taxonomies as $tax ) {
                $current_terms  = wp_get_object_terms( $recipe_id, $tax, array( 'fields' => 'ids' ) );
                $candidate_terms = wp_get_object_terms( $post->ID, $tax, array( 'fields' => 'ids' ) );
                if ( is_wp_error( $current_terms ) || is_wp_error( $candidate_terms ) ) continue;
                $shared = count( array_intersect( $current_terms, $candidate_terms ) );
                $score += $shared * $weights[ $tax ];
            }
            $scored[] = array( 'post' => $post, 'score' => $score );
        }

        usort( $scored, function ( $a, $b ) {
            return $b['score'] - $a['score'];
        } );

        $result = array();
        foreach ( array_slice( $scored, 0, $limit ) as $item ) {
            $result[] = $item['post'];
        }
        return $result;
    }

    /**
     * Fallback: return most recent published recipes excluding the current one.
     */
    private static function latest_recipes( $recipe_id, $limit ) {
        $query = new WP_Query( array(
            'post_type'           => 'delice_recipe',
            'post_status'         => 'publish',
            'posts_per_page'      => $limit,
            'post__not_in'        => array( $recipe_id ),
            'orderby'             => 'date',
            'order'               => 'DESC',
            'no_found_rows'       => true,
            'ignore_sticky_posts' => true,
        ) );
        $posts = $query->posts;
        wp_reset_postdata();
        return $posts;
    }

    /**
     * Render the related recipes HTML block.
     *
     * @param int    $recipe_id
     * @param string $heading   Translated heading text.
     */
    public static function render( $recipe_id, $heading = 'You Might Also Like' ) {
        $related = self::get( $recipe_id );
        if ( empty( $related ) ) return;

        echo '<div class="delice-related-recipes">';
        echo '<h3 class="delice-related-recipes-heading">' . esc_html( $heading ) . '</h3>';
        echo '<div class="delice-related-recipes-grid">';

        foreach ( $related as $rel_post ) {
            $rid        = $rel_post->ID;
            $title      = get_the_title( $rid );
            $link       = get_permalink( $rid );
            $total_time = get_post_meta( $rid, '_delice_recipe_total_time', true );
            $rating_avg = floatval( get_post_meta( $rid, '_delice_recipe_rating_average', true ) );
            $rating_cnt = intval( get_post_meta( $rid, '_delice_recipe_rating_count', true ) );

            echo '<a href="' . esc_url( $link ) . '" class="delice-related-recipe-card" aria-label="' . esc_attr( $title ) . '">';

            // Thumbnail
            echo '<div class="delice-related-recipe-img">';
            if ( has_post_thumbnail( $rid ) ) {
                echo get_the_post_thumbnail( $rid, 'medium', array(
                    'alt'     => esc_attr( $title ),
                    'loading' => 'lazy',
                ) );
            } else {
                echo '<div class="delice-related-no-img">🍽</div>';
            }
            echo '</div>';

            // Info
            echo '<div class="delice-related-recipe-info">';
            echo '<h4 class="delice-related-recipe-title">' . esc_html( $title ) . '</h4>';
            echo '<div class="delice-related-recipe-meta">';
            if ( $total_time ) {
                echo '<span>⏱ ' . esc_html( $total_time ) . ' min</span>';
            }
            if ( $rating_cnt > 0 ) {
                echo '<span class="delice-related-recipe-rating">★ ' . esc_html( number_format( $rating_avg, 1 ) ) . '</span>';
            }
            echo '</div>';
            echo '</div>';

            echo '</a>';
        }

        echo '</div>';
        echo '</div>';
    }
}

Delice_Recipe_Related::init();

endif;
