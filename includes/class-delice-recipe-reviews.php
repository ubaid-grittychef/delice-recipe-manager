<?php
/**
 * Recipe review system with comments and images
 */

if (!class_exists('Delice_Recipe_Reviews')) {
class Delice_Recipe_Reviews {
    
    /**
     * Initialize the class
     */
    public function __construct() {
        // Initialize hooks but don't duplicate AJAX handlers
        add_action('admin_init', array($this, 'register_settings'));
        
        // Create reviews table on activation
        add_action('init', array($this, 'maybe_create_reviews_table'));
    }
    
    /**
     * Register review settings
     */
    public function register_settings() {
        register_setting('delice_recipe_review_settings', 'delice_recipe_review_settings');
    }
    
    /**
     * Create reviews table on plugin activation
     */
    public function create_reviews_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'delice_recipe_reviews';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            recipe_id bigint(20) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            user_ip varchar(45) NOT NULL,
            user_name varchar(100) NOT NULL,
            user_email varchar(100) DEFAULT NULL,
            rating tinyint(1) NOT NULL,
            comment text DEFAULT NULL,
            image_url varchar(255) DEFAULT NULL,
            status varchar(20) DEFAULT 'approved',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY recipe_id (recipe_id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create reviews table if it doesn't exist
     */
    public function maybe_create_reviews_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'delice_recipe_reviews';
        
        // Secure table existence check
        $table_exists = ($wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $wpdb->esc_like($table_name)
        )) == $table_name);
        
        if (!$table_exists) {
            $this->create_reviews_table();
        }
    }
    
    /**
     * Save recipe rating - FIXED nonce verification
     */
    public function save_rating() {
        // Check nonce - using the actual nonce action that's sent
        if (!check_ajax_referer('delice_recipe_rating_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'delice-recipe-manager')));
        }

        // Get post data
        $recipe_id = isset($_POST['recipe_id']) ? absint($_POST['recipe_id']) : 0;
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;

        // Validate data
        if (!$recipe_id || $rating < 1 || $rating > 5) {
            wp_send_json_error(array('message' => __('Invalid rating data.', 'delice-recipe-manager')));
        }

        // Get user information
        $user_id = get_current_user_id();
        $user_ip = $this->get_user_ip();
        $user_name = $user_id ? get_userdata($user_id)->display_name : __('Anonymous', 'delice-recipe-manager');
        $user_email = $user_id ? get_userdata($user_id)->user_email : '';
        
        // Check if user already rated
        if ($this->user_has_rated($recipe_id, $user_id, $user_ip)) {
            wp_send_json_error(array('message' => __('You have already rated this recipe.', 'delice-recipe-manager')));
        }
        
        // Get review settings
        $review_settings = get_option('delice_recipe_review_settings', array('auto_approve' => true));
        $status = $review_settings['auto_approve'] ? 'approved' : 'pending';
        
        // Save rating to reviews table
        global $wpdb;
        $table_name = $wpdb->prefix . 'delice_recipe_reviews';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'recipe_id' => $recipe_id,
                'user_id' => $user_id,
                'user_ip' => $user_ip,
                'user_name' => $user_name,
                'user_email' => $user_email,
                'rating' => $rating,
                'status' => $status
            ),
            array('%d', '%d', '%s', '%s', '%s', '%d', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to save rating.', 'delice-recipe-manager')));
        }
        
        // Update average rating
        $this->update_recipe_rating_meta($recipe_id);
        
        // Return success with instruction to scroll to comment section
        wp_send_json_success(array(
            'message' => __('Rating saved! Now share your experience.', 'delice-recipe-manager'),
            'scroll_to_comments' => true,
            'review_id' => $wpdb->insert_id
        ));
    }
    
    /**
     * Save recipe review comment and image - FIXED nonce verification
     */
    public function save_review() {
        // Check nonce - using the actual nonce action that's sent
        if (!check_ajax_referer('delice_recipe_rating_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'delice-recipe-manager')));
        }

        $recipe_id = isset($_POST['recipe_id']) ? absint($_POST['recipe_id']) : 0;
        $comment = isset($_POST['comment']) ? sanitize_textarea_field($_POST['comment']) : '';
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        
        if (!$recipe_id) {
            wp_send_json_error(array('message' => __('Invalid recipe.', 'delice-recipe-manager')));
        }
        
        if (!$comment) {
            wp_send_json_error(array('message' => __('Please enter a comment.', 'delice-recipe-manager')));
        }
        
        if (!$rating || $rating < 1 || $rating > 5) {
            wp_send_json_error(array('message' => __('Please select a rating first.', 'delice-recipe-manager')));
        }
        
        // Get user information
        $user_id = get_current_user_id();
        $user_ip = $this->get_user_ip();
        $user_name = $user_id ? get_userdata($user_id)->display_name : __('Anonymous', 'delice-recipe-manager');
        $user_email = $user_id ? get_userdata($user_id)->user_email : '';
        
        // Handle image upload
        $image_url = '';
        if (isset($_FILES['review_image']) && $_FILES['review_image']['error'] === UPLOAD_ERR_OK) {
            $image_url = $this->handle_image_upload($_FILES['review_image'], $recipe_id);
            if (!$image_url) {
                wp_send_json_error(array('message' => __('Failed to upload image.', 'delice-recipe-manager')));
            }
        }
        
        // Get review settings
        $review_settings = get_option('delice_recipe_review_settings', array('auto_approve' => true));
        $status = $review_settings['auto_approve'] ? 'approved' : 'pending';
        
        // Save complete review to reviews table
        global $wpdb;
        $table_name = $wpdb->prefix . 'delice_recipe_reviews';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'recipe_id' => $recipe_id,
                'user_id' => $user_id,
                'user_ip' => $user_ip,
                'user_name' => $user_name,
                'user_email' => $user_email,
                'rating' => $rating,
                'comment' => $comment,
                'image_url' => $image_url,
                'status' => $status
            ),
            array('%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to save review.', 'delice-recipe-manager')));
        }
        
        // Update average rating
        $this->update_recipe_rating_meta($recipe_id);
        
        wp_send_json_success(array(
            'message' => __('Review saved successfully!', 'delice-recipe-manager'),
            'review_data' => $this->get_review_by_id($wpdb->insert_id)
        ));
    }
    
    /**
     * Get reviews for a recipe
     */
    public function get_reviews() {
        $recipe_id = isset($_POST['recipe_id']) ? absint($_POST['recipe_id']) : 0;
        
        if (!$recipe_id) {
            wp_send_json_error(array('message' => __('Invalid recipe.', 'delice-recipe-manager')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'delice_recipe_reviews';
        
        $reviews = $wpdb->get_results($wpdb->prepare(
            "SELECT id, recipe_id, user_name, user_email, rating, comment, created_at 
             FROM $table_name 
             WHERE recipe_id = %d AND status = 'approved' AND comment IS NOT NULL AND comment != '' 
             ORDER BY created_at DESC",
            $recipe_id
        ));
        
        $formatted_reviews = array();
        foreach ($reviews as $review) {
            $formatted_reviews[] = array(
                'id' => $review->id,
                'user_name' => $review->user_name,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'image_url' => $review->image_url,
                'date' => date('F j, Y', strtotime($review->created_at))
            );
        }
        
        wp_send_json_success(array('reviews' => $formatted_reviews));
    }
    
    /**
     * Approve a review (admin only)
     */
    public function approve_review() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'delice-recipe-manager')));
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'delice_admin_review_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'delice-recipe-manager')));
        }
        
        $review_id = intval($_POST['review_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'delice_recipe_reviews';
        
        $result = $wpdb->update(
            $table_name,
            array('status' => 'approved'),
            array('id' => $review_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Review approved', 'delice-recipe-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to approve review', 'delice-recipe-manager')));
        }
    }
    
    /**
     * Delete a review (admin only)
     */
    public function delete_review() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'delice-recipe-manager')));
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'delice_admin_review_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'delice-recipe-manager')));
        }
        
        $review_id = intval($_POST['review_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'delice_recipe_reviews';
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $review_id),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Review deleted', 'delice-recipe-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete review', 'delice-recipe-manager')));
        }
    }
    
    /**
     * Check if user has already rated a recipe
     */
    private function user_has_rated($recipe_id, $user_id, $user_ip) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'delice_recipe_reviews';
        
        if ($user_id) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE recipe_id = %d AND user_id = %d",
                $recipe_id, $user_id
            ));
        } else {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE recipe_id = %d AND user_ip = %s",
                $recipe_id, $user_ip
            ));
        }
        
        return $count > 0;
    }
    
    /**
     * Update recipe rating meta from reviews table
     */
    private function update_recipe_rating_meta($recipe_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'delice_recipe_reviews';
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(rating) as average, COUNT(*) as count FROM $table_name WHERE recipe_id = %d AND status = 'approved'",
            $recipe_id
        ));
        
        if ($stats) {
            update_post_meta($recipe_id, '_delice_recipe_rating_average', round($stats->average, 1));
            update_post_meta($recipe_id, '_delice_recipe_rating_count', $stats->count);
        }
    }
    
    /**
     * Handle image upload
     */
    private function handle_image_upload($file, $recipe_id) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        // Validate file type
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            return false;
        }
        
        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return false;
        }
        
        $upload_overrides = array(
            'test_form' => false,
            'mimes' => array(
                'jpg|jpeg|jpe' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif'
            )
        );
        
        $movefile = wp_handle_upload($file, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            return $movefile['url'];
        }
        
        return false;
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
    /**
     * Get review by ID
     */
    private function get_review_by_id($review_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'delice_recipe_reviews';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT id, recipe_id, user_id, user_name, user_email, rating, comment, status, created_at 
             FROM $table_name 
             WHERE id = %d",
            $review_id
        ));
    }
    
    /**
     * Get recipe rating data for schema
     */
    public function get_recipe_rating_data($recipe_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'delice_recipe_reviews';
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(rating) as average, COUNT(*) as count FROM $table_name WHERE recipe_id = %d AND status = 'approved'",
            $recipe_id
        ));
        
        $reviews = $wpdb->get_results($wpdb->prepare(
            "SELECT id, user_name, rating, comment, created_at 
             FROM $table_name 
             WHERE recipe_id = %d AND status = 'approved' AND comment IS NOT NULL AND comment != '' 
             ORDER BY created_at DESC 
             LIMIT 5",
            $recipe_id
        ));
        
        return array(
            'average' => $stats ? round($stats->average, 1) : 0,
            'count' => $stats ? $stats->count : 0,
            'reviews' => $reviews
        );
    }
}
}
