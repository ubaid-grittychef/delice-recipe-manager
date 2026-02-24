<?php
/**
 * Recipe Experience Class
 * 
 * Handles recipe testing, user cook submissions, and experience tracking
 * 
 * @package Delice_Recipe_Manager
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Delice_Recipe_Experience {

    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_experience_meta_boxes'));
        add_action('save_post', array($this, 'save_experience_meta'), 10, 2);
        
        // Frontend display
        add_filter('the_content', array($this, 'append_experience_content'), 15);
        
        // Shortcodes
        add_shortcode('delice_testing_badge', array($this, 'testing_badge_shortcode'));
        add_shortcode('delice_user_cooks', array($this, 'user_cooks_shortcode'));
        add_shortcode('delice_submit_cook', array($this, 'submit_cook_form_shortcode'));
    }

    /**
     * Add meta boxes for experience features
     */
    public function add_experience_meta_boxes() {
        add_meta_box(
            'delice_recipe_testing',
            __('🧪 Recipe Testing', 'delice-recipe-manager'),
            array($this, 'render_testing_meta_box'),
            array('delice_recipe', 'post'),
            'side',
            'default'
        );
    }

    /**
     * Render testing meta box
     */
    public function render_testing_meta_box($post) {
        wp_nonce_field('delice_testing_meta_box', 'delice_testing_meta_box_nonce');
        
        $tested = get_post_meta($post->ID, '_delice_recipe_tested', true);
        $test_count = get_post_meta($post->ID, '_delice_recipe_test_count', true) ?: 0;
        $kitchen_tested = get_post_meta($post->ID, '_delice_kitchen_tested', true);
        
        ?>
        <div class="delice-testing-meta-box">
            <p>
                <label>
                    <input type="checkbox" name="delice_recipe_tested" value="1" <?php checked($tested, '1'); ?>>
                    <?php _e('Recipe has been tested', 'delice-recipe-manager'); ?>
                </label>
            </p>
            
            <p>
                <label>
                    <input type="checkbox" name="delice_kitchen_tested" value="1" <?php checked($kitchen_tested, '1'); ?>>
                    <?php _e('Professional kitchen tested', 'delice-recipe-manager'); ?>
                </label>
            </p>
            
            <p>
                <label><?php _e('Number of tests:', 'delice-recipe-manager'); ?></label>
                <input type="number" name="delice_recipe_test_count" value="<?php echo esc_attr($test_count); ?>" min="0" style="width: 80px;">
            </p>
            
            <hr>
            
            <div class="delice-testing-stats">
                <?php
                global $wpdb;
                $table = $wpdb->prefix . 'delice_recipe_testing';
                
                $verified_tests = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table WHERE recipe_id = %d AND verified = 1",
                    $post->ID
                ));
                
                $avg_success = $wpdb->get_var($wpdb->prepare(
                    "SELECT AVG(success_rating) FROM $table WHERE recipe_id = %d AND verified = 1",
                    $post->ID
                ));
                
                $would_make_again = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table WHERE recipe_id = %d AND verified = 1 AND would_make_again = 1",
                    $post->ID
                ));
                
                $total_cooks = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}delice_user_cooks WHERE recipe_id = %d AND approved = 1",
                    $post->ID
                ));
                ?>
                
                <p><strong><?php _e('Verified Tests:', 'delice-recipe-manager'); ?></strong> <?php echo $verified_tests; ?></p>
                
                <?php if ($verified_tests > 0): ?>
                    <p><strong><?php _e('Success Rate:', 'delice-recipe-manager'); ?></strong> <?php echo round($avg_success, 1); ?>/5</p>
                    <p><strong><?php _e('Would Make Again:', 'delice-recipe-manager'); ?></strong> <?php echo round(($would_make_again / $verified_tests) * 100); ?>%</p>
                <?php endif; ?>
                
                <p><strong><?php _e('User Submissions:', 'delice-recipe-manager'); ?></strong> <?php echo $total_cooks; ?></p>
            </div>
            
            <p style="margin-top: 15px;">
                <a href="<?php echo admin_url('admin.php?page=delice-recipe-testing&recipe_id=' . $post->ID); ?>" class="button">
                    <?php _e('Manage Tests', 'delice-recipe-manager'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Save experience meta
     */
    public function save_experience_meta($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['delice_testing_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['delice_testing_meta_box_nonce'], 'delice_testing_meta_box')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save tested status
        update_post_meta($post_id, '_delice_recipe_tested', isset($_POST['delice_recipe_tested']) ? '1' : '0');
        
        // Save kitchen tested status
        update_post_meta($post_id, '_delice_kitchen_tested', isset($_POST['delice_kitchen_tested']) ? '1' : '0');
        
        // Save test count
        if (isset($_POST['delice_recipe_test_count'])) {
            update_post_meta($post_id, '_delice_recipe_test_count', absint($_POST['delice_recipe_test_count']));
        }
    }

    /**
     * Append experience content to recipe
     */
    public function append_experience_content($content) {
        if (!is_singular(array('delice_recipe', 'post'))) {
            return $content;
        }
        
        global $post;
        
        // Safety check
        if (!$post || !isset($post->ID)) {
            return $content;
        }
        
        $experience_content = '';
        
        // Add testing badge (individual toggle)
        if (get_option('delice_eeat_show_testing_badge', 1)) {
            $experience_content .= $this->render_testing_badge($post->ID);
        }
        
        // Add user cooks gallery (individual toggle)
        if (get_option('delice_eeat_show_user_cooks', 1)) {
            $experience_content .= $this->render_user_cooks_gallery($post->ID);
        }
        
        // Add submit cook form (individual toggle)
        if (get_option('delice_eeat_show_submit_button', 1)) {
            $experience_content .= $this->render_submit_cook_form($post->ID);
        }
        
        return $content . $experience_content;
    }

    /**
     * Render testing badge
     */
    public function render_testing_badge($recipe_id) {
        global $wpdb;
        
        $tested = get_post_meta($recipe_id, '_delice_recipe_tested', true);
        $kitchen_tested = get_post_meta($recipe_id, '_delice_kitchen_tested', true);
        
        if (!$tested && !$kitchen_tested) {
            return '';
        }
        
        $table = $wpdb->prefix . 'delice_recipe_testing';
        
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_tests,
                AVG(success_rating) as avg_success,
                SUM(CASE WHEN would_make_again = 1 THEN 1 ELSE 0 END) as would_make_again_count
            FROM $table 
            WHERE recipe_id = %d AND verified = 1
        ", $recipe_id));
        
        ob_start();
        ?>
        <div class="delice-testing-badge-section">
            <div class="delice-testing-badge">
                <div class="badge-header">
                    <span class="badge-icon">✓</span>
                    <span class="badge-title">
                        <?php if ($kitchen_tested): ?>
                            <?php _e('Professional Kitchen Tested', 'delice-recipe-manager'); ?>
                        <?php else: ?>
                            <?php _e('Recipe Tested', 'delice-recipe-manager'); ?>
                        <?php endif; ?>
                    </span>
                </div>
                
                <?php if ($stats && $stats->total_tests > 0): ?>
                    <div class="badge-stats">
                        <div class="stat">
                            <span class="stat-value"><?php echo $stats->total_tests; ?></span>
                            <span class="stat-label"><?php _e('Times Tested', 'delice-recipe-manager'); ?></span>
                        </div>
                        <div class="stat">
                            <span class="stat-value"><?php echo round($stats->avg_success, 1); ?>/5</span>
                            <span class="stat-label"><?php _e('Success Rate', 'delice-recipe-manager'); ?></span>
                        </div>
                        <div class="stat">
                            <span class="stat-value"><?php echo round(($stats->would_make_again_count / $stats->total_tests) * 100); ?>%</span>
                            <span class="stat-label"><?php _e('Would Make Again', 'delice-recipe-manager'); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render user cooks gallery
     */
    public function render_user_cooks_gallery($recipe_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'delice_user_cooks';
        
        $cooks = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table 
            WHERE recipe_id = %d AND approved = 1 AND photo_url IS NOT NULL
            ORDER BY created_at DESC
            LIMIT 12
        ", $recipe_id));
        
        if (empty($cooks)) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="delice-user-cooks-section">
            <h3 class="section-title">
                <span class="icon">👨‍🍳</span>
                <?php printf(_n('%d Person Made This', '%d People Made This', count($cooks), 'delice-recipe-manager'), count($cooks)); ?>
            </h3>
            
            <div class="delice-user-cooks-gallery">
                <?php foreach ($cooks as $cook): ?>
                    <div class="cook-item">
                        <?php if ($cook->photo_url): ?>
                            <div class="cook-photo">
                                <img src="<?php echo esc_url($cook->photo_url); ?>" alt="<?php echo esc_attr($cook->user_name); ?>">
                            </div>
                        <?php endif; ?>
                        
                        <div class="cook-info">
                            <strong class="cook-name"><?php echo esc_html($cook->user_name); ?></strong>
                            
                            <?php if ($cook->success_rating): ?>
                                <div class="cook-rating">
                                    <?php echo str_repeat('★', $cook->success_rating) . str_repeat('☆', 5 - $cook->success_rating); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($cook->modifications): ?>
                                <p class="cook-note"><?php echo esc_html(wp_trim_words($cook->modifications, 15)); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render submit cook form
     */
    public function render_submit_cook_form($recipe_id) {
        ob_start();
        ?>
        <div class="delice-submit-cook-section">
            <h3 class="section-title"><?php _e('Did You Make This Recipe?', 'delice-recipe-manager'); ?></h3>
            <p class="section-description"><?php _e('Share your experience with others!', 'delice-recipe-manager'); ?></p>
            
            <button type="button" class="delice-submit-cook-button" data-recipe-id="<?php echo $recipe_id; ?>">
                <?php _e('Share Your Cook', 'delice-recipe-manager'); ?>
            </button>
        </div>
        
        <!-- Modal will be rendered by JavaScript -->
        <?php
        return ob_get_clean();
    }

    /**
     * Submit user cook (AJAX handler)
     */
    public function submit_user_cook($data) {
        global $wpdb;
        
        $recipe_id = isset($data['recipe_id']) ? intval($data['recipe_id']) : 0;
        
        if (!$recipe_id) {
            return false;
        }
        
        // Prepare data
        $insert_data = array(
            'recipe_id' => $recipe_id,
            'user_name' => sanitize_text_field($data['user_name']),
            'user_email' => sanitize_email($data['user_email']),
            'success_rating' => isset($data['rating']) ? intval($data['rating']) : null,
            'modifications' => isset($data['modifications']) ? sanitize_textarea_field($data['modifications']) : null,
            'would_recommend' => isset($data['would_recommend']) ? 1 : 0,
            'approved' => 0, // Pending approval
            'created_at' => current_time('mysql')
        );
        
        // Handle logged-in user
        if (is_user_logged_in()) {
            $insert_data['user_id'] = get_current_user_id();
        }
        
        // Handle photo upload
        if (isset($data['photo']) && !empty($data['photo'])) {
            $photo_url = $this->handle_photo_upload($data['photo']);
            if ($photo_url) {
                $insert_data['photo_url'] = $photo_url;
            }
        }
        
        $table = $wpdb->prefix . 'delice_user_cooks';
        return $wpdb->insert($table, $insert_data);
    }

    /**
     * Handle photo upload
     */
    private function handle_photo_upload($photo_data) {
        // This will be handled via JavaScript FileReader and AJAX
        // For now, return the data URL or handle server-side upload
        return $photo_data;
    }

    /**
     * AJAX: Save recipe test
     */
    public function ajax_save_recipe_test() {
        check_ajax_referer('delice_eeat_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'delice-recipe-manager')));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'delice_recipe_testing';
        
        $data = array(
            'recipe_id' => intval($_POST['recipe_id']),
            'tester_name' => sanitize_text_field($_POST['tester_name']),
            'tester_email' => sanitize_email($_POST['tester_email']),
            'test_date' => sanitize_text_field($_POST['test_date']),
            'success_rating' => intval($_POST['success_rating']),
            'difficulty_experienced' => sanitize_text_field($_POST['difficulty_experienced']),
            'time_actual_prep' => intval($_POST['time_actual_prep']),
            'time_actual_cook' => intval($_POST['time_actual_cook']),
            'notes' => sanitize_textarea_field($_POST['notes']),
            'would_make_again' => isset($_POST['would_make_again']) ? 1 : 0,
            'verified' => isset($_POST['verified']) ? 1 : 0,
        );
        
        if (isset($_POST['id']) && $_POST['id']) {
            // Update existing
            $result = $wpdb->update($table, $data, array('id' => intval($_POST['id'])));
        } else {
            // Insert new
            $result = $wpdb->insert($table, $data);
        }
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Test saved successfully', 'delice-recipe-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save test', 'delice-recipe-manager')));
        }
    }

    /**
     * AJAX: Approve recipe test
     */
    public function ajax_approve_recipe_test() {
        check_ajax_referer('delice_eeat_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'delice-recipe-manager')));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'delice_recipe_testing';
        $id = intval($_POST['id']);
        
        $result = $wpdb->update($table, array('verified' => 1), array('id' => $id));
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Test approved', 'delice-recipe-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to approve', 'delice-recipe-manager')));
        }
    }

    /**
     * AJAX: Delete recipe test
     */
    public function ajax_delete_recipe_test() {
        check_ajax_referer('delice_eeat_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'delice-recipe-manager')));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'delice_recipe_testing';
        $id = intval($_POST['id']);
        
        $result = $wpdb->delete($table, array('id' => $id));
        
        if ($result) {
            wp_send_json_success(array('message' => __('Test deleted', 'delice-recipe-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete', 'delice-recipe-manager')));
        }
    }

    /**
     * AJAX: Approve user cook
     */
    public function ajax_approve_user_cook() {
        check_ajax_referer('delice_eeat_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'delice-recipe-manager')));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'delice_user_cooks';
        $id = intval($_POST['id']);
        
        $result = $wpdb->update($table, array('approved' => 1), array('id' => $id));
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Submission approved', 'delice-recipe-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to approve', 'delice-recipe-manager')));
        }
    }

    /**
     * AJAX: Delete user cook
     */
    public function ajax_delete_user_cook() {
        check_ajax_referer('delice_eeat_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'delice-recipe-manager')));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'delice_user_cooks';
        $id = intval($_POST['id']);
        
        $result = $wpdb->delete($table, array('id' => $id));
        
        if ($result) {
            wp_send_json_success(array('message' => __('Submission deleted', 'delice-recipe-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete', 'delice-recipe-manager')));
        }
    }

    /**
     * Testing badge shortcode
     */
    public function testing_badge_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => get_the_ID()
        ), $atts);
        
        return $this->render_testing_badge($atts['id']);
    }

    /**
     * User cooks shortcode
     */
    public function user_cooks_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => get_the_ID()
        ), $atts);
        
        return $this->render_user_cooks_gallery($atts['id']);
    }

    /**
     * Submit cook form shortcode
     */
    public function submit_cook_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => get_the_ID()
        ), $atts);
        
        return $this->render_submit_cook_form($atts['id']);
    }
}
