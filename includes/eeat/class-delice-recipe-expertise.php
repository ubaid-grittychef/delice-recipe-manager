<?php
/**
 * Recipe Expertise Class
 * 
 * Handles author expertise, certifications, and nutrition reviews
 * 
 * @package Delice_Recipe_Manager
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Delice_Recipe_Expertise {

    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_expertise_meta_boxes'));
        add_action('save_post', array($this, 'save_expertise_meta'), 10, 2);
        add_filter('the_content', array($this, 'append_expertise_content'), 16);
    }

    public function add_expertise_meta_boxes() {
        add_meta_box(
            'delice_recipe_expertise',
            __('🎓 Expertise & Credentials', 'delice-recipe-manager'),
            array($this, 'render_expertise_meta_box'),
            array('delice_recipe', 'post'),
            'side',
            'default'
        );
    }

    public function render_expertise_meta_box($post) {
        wp_nonce_field('delice_expertise_meta_box', 'delice_expertise_meta_box_nonce');
        
        $professional_tested = get_post_meta($post->ID, '_delice_professional_tested', true);
        $nutrition_verified = get_post_meta($post->ID, '_delice_nutrition_verified', true);
        $expert_reviewed = get_post_meta($post->ID, '_delice_expert_reviewed', true);
        
        ?>
        <p>
            <label>
                <input type="checkbox" name="delice_professional_tested" value="1" <?php checked($professional_tested, '1'); ?>>
                <?php _e('Professionally tested', 'delice-recipe-manager'); ?>
            </label>
        </p>
        
        <p>
            <label>
                <input type="checkbox" name="delice_nutrition_verified" value="1" <?php checked($nutrition_verified, '1'); ?>>
                <?php _e('Nutrition verified by expert', 'delice-recipe-manager'); ?>
            </label>
        </p>
        
        <p>
            <label>
                <input type="checkbox" name="delice_expert_reviewed" value="1" <?php checked($expert_reviewed, '1'); ?>>
                <?php _e('Reviewed by culinary expert', 'delice-recipe-manager'); ?>
            </label>
        </p>
        
        <hr>
        
        <p>
            <a href="<?php echo admin_url('admin.php?page=delice-author-profiles&user_id=' . $post->post_author); ?>" class="button">
                <?php _e('Manage Author Profile', 'delice-recipe-manager'); ?>
            </a>
        </p>
        <?php
    }

    public function save_expertise_meta($post_id, $post) {
        if (!isset($_POST['delice_expertise_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['delice_expertise_meta_box_nonce'], 'delice_expertise_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        update_post_meta($post_id, '_delice_professional_tested', isset($_POST['delice_professional_tested']) ? '1' : '0');
        update_post_meta($post_id, '_delice_nutrition_verified', isset($_POST['delice_nutrition_verified']) ? '1' : '0');
        update_post_meta($post_id, '_delice_expert_reviewed', isset($_POST['delice_expert_reviewed']) ? '1' : '0');
    }

    public function append_expertise_content($content) {
        if (!is_singular(array('delice_recipe', 'post'))) {
            return $content;
        }
        
        global $post;
        
        // Safety check
        if (!$post || !isset($post->ID)) {
            return $content;
        }
        
        $expertise_content = '';
        
        // Only show nutrition review if enabled (individual toggle)
        if (get_option('delice_eeat_show_nutrition_review', 1)) {
            $expertise_content .= $this->render_nutrition_review($post->ID);
        }
        
        return $content . $expertise_content;
    }

    public function render_nutrition_review($recipe_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'delice_nutrition_reviews';
        
        $review = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE recipe_id = %d AND verified = 1 ORDER BY reviewed_date DESC LIMIT 1",
            $recipe_id
        ));
        
        if (!$review) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="delice-nutrition-expert-review">
            <h3 class="section-title"><?php _e('Nutritionist Review', 'delice-recipe-manager'); ?></h3>
            
            <div class="nutrition-review-content">
                <div class="expert-badge">
                    <span class="badge-icon">✓</span>
                    <span class="badge-text"><?php _e('Reviewed by Certified Nutritionist', 'delice-recipe-manager'); ?></span>
                </div>
                
                <div class="expert-info">
                    <strong><?php echo esc_html($review->nutritionist_name); ?></strong>
                    <?php if ($review->nutritionist_credentials): ?>
                        <span class="credentials"><?php echo esc_html($review->nutritionist_credentials); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if ($review->review_text): ?>
                    <blockquote class="expert-quote">
                        <?php echo esc_html($review->review_text); ?>
                    </blockquote>
                <?php endif; ?>
                
                <?php if ($review->health_benefits): ?>
                    <div class="health-benefits">
                        <h4><?php _e('Health Benefits:', 'delice-recipe-manager'); ?></h4>
                        <p><?php echo esc_html($review->health_benefits); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_save_nutrition_review() {
        check_ajax_referer('delice_eeat_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'delice-recipe-manager')));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'delice_nutrition_reviews';
        
        $data = array(
            'recipe_id' => intval($_POST['recipe_id']),
            'nutritionist_id' => get_current_user_id(),
            'nutritionist_name' => sanitize_text_field($_POST['nutritionist_name']),
            'nutritionist_credentials' => sanitize_text_field($_POST['nutritionist_credentials']),
            'review_text' => sanitize_textarea_field($_POST['review_text']),
            'dietary_notes' => sanitize_textarea_field($_POST['dietary_notes']),
            'health_benefits' => sanitize_textarea_field($_POST['health_benefits']),
            'allergen_warnings' => sanitize_textarea_field($_POST['allergen_warnings']),
            'verified' => 1,
            'reviewed_date' => current_time('mysql', 1),
        );
        
        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Review saved successfully', 'delice-recipe-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save review', 'delice-recipe-manager')));
        }
    }
}
