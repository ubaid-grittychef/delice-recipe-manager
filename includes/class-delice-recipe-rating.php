<?php
/**
 * Recipe rating functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Delice_Recipe_Rating')) {
class Delice_Recipe_Rating {

    /**
     * Initialize the class
     */
    public function __construct() {
        // Nothing to do here
    }

    /**
     * Add rating display option
     */
    public function add_rating_display_option($options) {
        if (!isset($options['show_rating'])) {
            $options['show_rating'] = true;
        }
        return $options;
    }

    /**
     * Save recipe rating
     */
    public function save_rating() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delice_recipe_rating_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'delice-recipe-manager')));
            return;
        }

        // Get post data
        $recipe_id = isset($_POST['recipe_id']) ? absint($_POST['recipe_id']) : 0;
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;

        // Validate data
        if (!$recipe_id || $rating < 1 || $rating > 5) {
            wp_send_json_error(array('message' => __('Invalid rating data.', 'delice-recipe-manager')));
            return;
        }

        // Get user IP for unique rating check
        $user_ip = $this->get_user_ip();
        $user_id = get_current_user_id();
        
        // Get existing ratings
        $ratings = get_post_meta($recipe_id, '_delice_recipe_ratings', true);
        if (!is_array($ratings)) {
            $ratings = array();
        }
        
        // Check if user already rated
        $user_key = $user_id ? 'user_' . $user_id : 'ip_' . wp_hash( $user_ip );
        if (isset($ratings[$user_key])) {
            wp_send_json_error(array('message' => __('You have already rated this recipe.', 'delice-recipe-manager')));
            return;
        }
        
        // Add the new rating
        $ratings[$user_key] = $rating;
        
        // Update meta
        update_post_meta($recipe_id, '_delice_recipe_ratings', $ratings);
        
        // Calculate and save average
        $this->calculate_average_rating($recipe_id, $ratings);
        
        // Return success
        wp_send_json_success(array(
            'message' => __('Rating saved successfully.', 'delice-recipe-manager'),
            'average' => $this->get_recipe_rating($recipe_id)
        ));
    }
    
    /**
     * Calculate and save average rating
     */
    private function calculate_average_rating($recipe_id, $ratings) {
        if (empty($ratings)) {
            return;
        }
        
        $total = 0;
        $count = count($ratings);
        
        foreach ($ratings as $rating) {
            $total += $rating;
        }
        
        $average = $total / $count;
        
        // Save average and count
        update_post_meta($recipe_id, '_delice_recipe_rating_average', round($average, 1));
        update_post_meta($recipe_id, '_delice_recipe_rating_count', $count);
        
        return array(
            'average' => $average,
            'count' => $count
        );
    }
    
    /**
     * Get recipe rating data
     */
    public function get_recipe_rating($recipe_id) {
        $average = get_post_meta($recipe_id, '_delice_recipe_rating_average', true);
        $count = get_post_meta($recipe_id, '_delice_recipe_rating_count', true);
        
        if (empty($average)) {
            $average = 0;
        }
        
        if (empty($count)) {
            $count = 0;
        }
        
        return array(
            'average' => (float) $average,
            'count' => (int) $count
        );
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        // Use only REMOTE_ADDR — HTTP_CLIENT_IP and HTTP_X_FORWARDED_FOR are
        // user-controllable headers and trivially spoofed for duplicate-vote bypass.
        return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
    }
    
    /**
     * Display rating stars
     */
    public function display_rating_stars($recipe_id, $allow_rating = true) {
        $rating_data = $this->get_recipe_rating($recipe_id);
        $average = $rating_data['average'];
        $count = $rating_data['count'];
        
        $html = '<div class="delice-recipe-rating" data-recipe-id="' . esc_attr($recipe_id) . '">';
        
        // Display stars
        $html .= '<div class="delice-recipe-stars">';
        
        for ($i = 1; $i <= 5; $i++) {
            $active = $i <= $average ? ' active' : '';
            $half = ($i - 0.5) <= $average && $i > $average ? ' half' : '';
            $attr = $allow_rating ? ' data-rating="' . $i . '"' : '';
            
            $html .= '<span class="star' . $active . $half . '"' . $attr . '>';
            $html .= '<i class="fas fa-star"></i>';
            $html .= '</span>';
        }
        
        $html .= '</div>';
        
        // Display count
        if ($count > 0) {
            $html .= '<span class="delice-recipe-rating-count">';
            $html .= sprintf(_n('(%d rating)', '(%d ratings)', $count, 'delice-recipe-manager'), $count);
            $html .= '</span>';
        }
        
        // Add message container
        $html .= '<div class="delice-recipe-rating-message"></div>';
        
        $html .= '</div>';
        
        return $html;
    }
}
}
