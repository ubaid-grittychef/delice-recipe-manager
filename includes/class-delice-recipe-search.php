<?php
/**
 * Recipe search enhancement features
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Delice_Recipe_Search')) {
class Delice_Recipe_Search {

    /**
     * Initialize the class
     */
    public function __construct() {
        // Nothing to do here
    }

    /**
     * Enhance recipe search query
     */
    public function enhance_recipe_search($query) {
        // Only modify search on frontend
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Only modify on search or when viewing recipe post type
        if (!$query->is_search() && !is_post_type_archive('delice_recipe') && !is_tax('delice_cuisine') && 
            !is_tax('delice_course') && !is_tax('delice_dietary') && !is_tax('delice_keyword')) {
            return;
        }
        
        // Get search query
        $search_term = $query->get('s');
        
        // Always include recipe post type in queries
        $post_types = $query->get('post_type');
        
        if (empty($post_types)) {
            $post_types = array('post', 'delice_recipe');
        } elseif (is_string($post_types)) {
            $post_types = array($post_types, 'delice_recipe');
        } elseif (is_array($post_types) && !in_array('delice_recipe', $post_types)) {
            $post_types[] = 'delice_recipe';
        }
        
        $query->set('post_type', $post_types);
        
        // Skip meta queries if no search term
        if (empty($search_term)) {
            return;
        }
        
        // Add meta query to search in recipe ingredients and instructions
        $meta_query = $query->get('meta_query');
        
        if (!is_array($meta_query)) {
            $meta_query = array();
        }
        
        // Add ingredient search
        $meta_query[] = array(
            'key' => '_delice_recipe_ingredients',
            'value' => $search_term,
            'compare' => 'LIKE'
        );
        
        // Add instruction search
        $meta_query[] = array(
            'key' => '_delice_recipe_instructions',
            'value' => $search_term,
            'compare' => 'LIKE'
        );
        
        // Add meta query relation
        $meta_query['relation'] = 'OR';
        
        $query->set('meta_query', $meta_query);
        
        // Add taxonomy query for recipe terms
        $tax_query = $query->get('tax_query');
        
        if (!is_array($tax_query)) {
            $tax_query = array();
        }
        
        // Add taxonomy queries
        $tax_query[] = array(
            'taxonomy' => 'delice_cuisine',
            'field' => 'name',
            'terms' => $search_term,
            'operator' => 'LIKE'
        );
        
        $tax_query[] = array(
            'taxonomy' => 'delice_course',
            'field' => 'name',
            'terms' => $search_term,
            'operator' => 'LIKE'
        );
        
        $tax_query[] = array(
            'taxonomy' => 'delice_keyword',
            'field' => 'name',
            'terms' => $search_term,
            'operator' => 'LIKE'
        );
        
        $tax_query[] = array(
            'taxonomy' => 'delice_dietary',
            'field' => 'name',
            'terms' => $search_term,
            'operator' => 'LIKE'
        );
        
        // Add taxonomy query relation
        $tax_query['relation'] = 'OR';
        
        $query->set('tax_query', $tax_query);
    }
    
    /**
     * Ajax recipe search
     */
    public function ajax_recipe_search() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delice_recipe_search_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'delice-recipe-manager')));
        }
        
        // Get search parameters
        $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $cuisine = isset($_POST['cuisine']) ? sanitize_text_field($_POST['cuisine']) : '';
        $course = isset($_POST['course']) ? sanitize_text_field($_POST['course']) : '';
        $dietary = isset($_POST['dietary']) ? sanitize_text_field($_POST['dietary']) : '';
        $difficulty = isset($_POST['difficulty']) ? sanitize_text_field($_POST['difficulty']) : '';
        
        // Setup query
        $args = array(
            'post_type' => 'delice_recipe',
            'post_status' => 'publish',
            'posts_per_page' => 12,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // Add search term
        if (!empty($search_term)) {
            $args['s'] = $search_term;
        }
        
        // Setup tax query
        $tax_query = array();
        
        // Add cuisine
        if (!empty($cuisine)) {
            $tax_query[] = array(
                'taxonomy' => 'delice_cuisine',
                'field' => 'slug',
                'terms' => $cuisine
            );
        }
        
        // Add course
        if (!empty($course)) {
            $tax_query[] = array(
                'taxonomy' => 'delice_course',
                'field' => 'slug',
                'terms' => $course
            );
        }
        
        // Add dietary
        if (!empty($dietary)) {
            $tax_query[] = array(
                'taxonomy' => 'delice_dietary',
                'field' => 'slug',
                'terms' => $dietary
            );
        }
        
        // Add tax query if not empty
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        // Add meta query for difficulty
        if (!empty($difficulty)) {
            $args['meta_query'] = array(
                array(
                    'key' => '_delice_recipe_difficulty',
                    'value' => $difficulty,
                    'compare' => '='
                )
            );
        }
        
        // Run query
        $query = new WP_Query($args);
        
        $results = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                // Get recipe data
                $recipe_id = get_the_ID();
                $prep_time = get_post_meta($recipe_id, '_delice_recipe_prep_time', true);
                $cook_time = get_post_meta($recipe_id, '_delice_recipe_cook_time', true);
                $total_time = get_post_meta($recipe_id, '_delice_recipe_total_time', true);
                $difficulty = get_post_meta($recipe_id, '_delice_recipe_difficulty', true);
                
                // Get rating
                $rating_instance = new Delice_Recipe_Rating();
                $rating_data = $rating_instance->get_recipe_rating($recipe_id);
                
                // Get thumbnail
                $thumbnail = get_the_post_thumbnail_url($recipe_id, 'medium');
                
                // Add to results
                $results[] = array(
                    'id' => $recipe_id,
                    'title' => get_the_title(),
                    'permalink' => get_permalink(),
                    'excerpt' => get_the_excerpt(),
                    'thumbnail' => $thumbnail ? $thumbnail : '',
                    'prep_time' => $prep_time,
                    'cook_time' => $cook_time,
                    'total_time' => $total_time,
                    'difficulty' => $difficulty,
                    'rating' => $rating_data
                );
            }
            
            wp_reset_postdata();
        }
        
        wp_send_json_success(array(
            'count' => count($results),
            'results' => $results
        ));
    }
}
}
