<?php
/**
 * Recipe Authority Class
 * 
 * Handles expert endorsements, publication history, and authoritative content
 * 
 * @package Delice_Recipe_Manager
 * @since 1.1.0
 */

if (!defined('ABSPATH')) exit;

class Delice_Recipe_Authority {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_authority_meta_boxes'));
        add_filter('the_content', array($this, 'append_authority_content'), 17);
    }

    public function add_authority_meta_boxes() {
        add_meta_box(
            'delice_recipe_authority',
            __('🏆 Authority & Endorsements', 'delice-recipe-manager'),
            array($this, 'render_authority_meta_box'),
            array('delice_recipe', 'post'),
            'side',
            'default'
        );
    }

    public function render_authority_meta_box($post) {
        global $wpdb;
        $table = $wpdb->prefix . 'delice_expert_endorsements';
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE recipe_id = %d", $post->ID));
        
        echo '<p><strong>' . __('Expert Endorsements:', 'delice-recipe-manager') . '</strong> ' . $count . '</p>';
        echo '<p><a href="' . admin_url('admin.php?page=delice-eeat-hub&tab=endorsements&recipe_id=' . $post->ID) . '" class="button">' . __('Manage Endorsements', 'delice-recipe-manager') . '</a></p>';
    }

    public function append_authority_content($content) {
        if (!is_singular(array('delice_recipe', 'post'))) return $content;
        
        global $post;
        
        // Safety check
        if (!$post || !isset($post->ID)) {
            return $content;
        }
        
        // Check individual toggle for endorsements
        if (!get_option('delice_eeat_show_endorsements', 1)) {
            return $content;
        }
        
        $endorsements = $this->get_recipe_endorsements($post->ID);
        
        if (empty($endorsements)) return $content;
        
        $html = '<div class="delice-endorsements-section">';
        $html .= '<h3>' . __('Expert Endorsements', 'delice-recipe-manager') . '</h3>';
        
        foreach ($endorsements as $endorsement) {
            $html .= '<div class="delice-endorsement">';
            if ($endorsement->expert_photo_url) {
                $html .= '<img src="' . esc_url($endorsement->expert_photo_url) . '" alt="" class="expert-photo">';
            }
            $html .= '<div class="endorsement-content">';
            $html .= '<strong>' . esc_html($endorsement->expert_name) . '</strong>';
            if ($endorsement->expert_title) {
                $html .= ' <span class="expert-title">' . esc_html($endorsement->expert_title) . '</span>';
            }
            if ($endorsement->endorsement_text) {
                $html .= '<blockquote>' . esc_html($endorsement->endorsement_text) . '</blockquote>';
            }
            $html .= '</div></div>';
        }
        $html .= '</div>';
        
        return $content . $html;
    }

    public function get_recipe_endorsements($recipe_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'delice_expert_endorsements';
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE recipe_id = %d AND verified = 1", $recipe_id));
    }

    public function ajax_save_endorsement() {
        check_ajax_referer('delice_eeat_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();
        
        global $wpdb;
        $data = array(
            'recipe_id' => intval($_POST['recipe_id']),
            'expert_name' => sanitize_text_field($_POST['expert_name']),
            'expert_title' => sanitize_text_field($_POST['expert_title']),
            'expert_credentials' => sanitize_text_field($_POST['expert_credentials']),
            'endorsement_text' => sanitize_textarea_field($_POST['endorsement_text']),
            'verified' => 1
        );
        
        $wpdb->insert($wpdb->prefix . 'delice_expert_endorsements', $data);
        wp_send_json_success();
    }

    public function ajax_delete_endorsement() {
        check_ajax_referer('delice_eeat_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();
        
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'delice_expert_endorsements', array('id' => intval($_POST['id'])));
        wp_send_json_success();
    }

    public function ajax_add_recipe_version() {
        check_ajax_referer('delice_eeat_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error();
        
        global $wpdb;
        $data = array(
            'recipe_id' => intval($_POST['recipe_id']),
            'version' => sanitize_text_field($_POST['version']),
            'updated_by' => get_current_user_id(),
            'update_type' => sanitize_text_field($_POST['update_type']),
            'changes_summary' => sanitize_textarea_field($_POST['changes_summary']),
            'updated_date' => current_time('mysql')
        );
        
        $wpdb->insert($wpdb->prefix . 'delice_recipe_history', $data);
        wp_send_json_success();
    }
}
