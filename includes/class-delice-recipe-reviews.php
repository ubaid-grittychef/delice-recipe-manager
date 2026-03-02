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

        // Prevent duplicate reviews (one per user/IP per recipe)
        if ( $this->user_has_rated( $recipe_id, $user_id, $user_ip ) ) {
            wp_send_json_error( array( 'message' => __( 'You have already submitted a review for this recipe.', 'delice-recipe-manager' ) ) );
        }

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
        // Verify nonce – even public read endpoints need CSRF protection.
        if ( ! check_ajax_referer( 'delice_recipe_rating_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'delice-recipe-manager' ) ) );
        }

        $recipe_id = isset($_POST['recipe_id']) ? absint($_POST['recipe_id']) : 0;

        if (!$recipe_id) {
            wp_send_json_error(array('message' => __('Invalid recipe.', 'delice-recipe-manager')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'delice_recipe_reviews';
        
        $reviews = $wpdb->get_results($wpdb->prepare(
            "SELECT id, recipe_id, user_name, user_email, rating, comment, image_url, created_at
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
            // When real user ratings exist, the seed is no longer active.
            if ( intval( $stats->count ) > 0 ) {
                delete_post_meta( $recipe_id, '_delice_recipe_is_seed_rating' );
            }
        }
    }
    
    /**
     * Handle image upload with strict MIME type validation.
     */
    private function handle_image_upload( $file, $recipe_id ) {
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        // Allowed MIME types – declared explicitly to pass to wp_handle_upload.
        $allowed_mimes = array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png'          => 'image/png',
            'gif'          => 'image/gif',
        );

        // Extension-based pre-check (fast fail before file read).
        $file_extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        $allowed_extensions = array( 'jpg', 'jpeg', 'png', 'gif' );
        if ( ! in_array( $file_extension, $allowed_extensions, true ) ) {
            return false;
        }

        // Get review settings for max size.
        $review_settings = get_option( 'delice_recipe_review_settings', array() );
        $max_mb = isset( $review_settings['max_image_size'] ) ? intval( $review_settings['max_image_size'] ) : 5;
        $max_bytes = $max_mb * 1024 * 1024;

        if ( $file['size'] > $max_bytes ) {
            return false;
        }

        // Use WordPress's own MIME sniffer via check_filetype_and_ext which
        // reads the actual file bytes rather than trusting the extension.
        if ( ! function_exists( 'wp_check_filetype_and_ext' ) ) {
            require_once ABSPATH . 'wp-includes/functions.php';
        }
        $check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed_mimes );
        if ( empty( $check['type'] ) || ! in_array( $check['type'], array_values( $allowed_mimes ), true ) ) {
            return false;
        }

        $upload_overrides = array(
            'test_form' => false,
            'mimes'     => $allowed_mimes,
        );

        $movefile = wp_handle_upload( $file, $upload_overrides );

        if ( $movefile && ! isset( $movefile['error'] ) ) {
            return $movefile['url'];
        }

        return false;
    }
    
    /**
     * Get user IP address.
     *
     * Uses REMOTE_ADDR as the authoritative source. Proxy headers
     * (HTTP_X_FORWARDED_FOR, HTTP_CLIENT_IP) are trivially spoofed and must
     * not be trusted for security decisions such as duplicate-vote detection.
     */
    private function get_user_ip() {
        return isset( $_SERVER['REMOTE_ADDR'] )
            ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
            : '0.0.0.0';
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

        $real_count = $stats ? intval( $stats->count ) : 0;

        // If no real user ratings yet and the recipe is marked as editor-tested,
        // return a legitimate seed rating from the recipe author.
        if ( $real_count === 0 && get_post_meta( $recipe_id, '_delice_recipe_author_tested', true ) ) {
            $author_id   = get_post_field( 'post_author', $recipe_id );
            $author_name = get_the_author_meta( 'display_name', $author_id );
            if ( empty( $author_name ) ) {
                $author_name = get_bloginfo( 'name' );
            }
            $seed_review              = new \stdClass();
            $seed_review->user_name  = $author_name;
            $seed_review->rating     = 5;
            $seed_review->comment    = __( 'Tested and approved by our kitchen team.', 'delice-recipe-manager' );
            $seed_review->created_at = get_post_field( 'post_date', $recipe_id );
            return array(
                'average' => 5.0,
                'count'   => 1,
                'reviews' => array( $seed_review ),
                'is_seed' => true,
            );
        }

        $reviews = $wpdb->get_results($wpdb->prepare(
            "SELECT id, user_name, rating, comment, created_at
             FROM $table_name
             WHERE recipe_id = %d AND status = 'approved' AND comment IS NOT NULL AND comment != ''
             ORDER BY created_at DESC
             LIMIT 5",
            $recipe_id
        ));

        return array(
            'average' => $real_count > 0 ? round( $stats->average, 1 ) : 0,
            'count'   => $real_count,
            'reviews' => $reviews,
            'is_seed' => false,
        );
    }
}
}
