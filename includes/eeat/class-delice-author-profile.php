<?php
/**
 * Author Profile Class
 * 
 * Manages author profiles, credentials, and expertise information
 * 
 * @package Delice_Recipe_Manager
 * @since 1.1.0
 */

if (!defined('ABSPATH')) exit;

class Delice_Author_Profile {
    
    public function get_profile($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'delice_author_profiles';
        
        $profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id), ARRAY_A);
        
        if (!$profile) {
            $user = get_userdata($user_id);
            if (!$user) {
                return null;
            }
            
            return array(
                'user_id' => $user_id,
                'display_name' => $user->display_name,
                'bio' => get_user_meta($user_id, 'description', true),
                'photo_url' => get_avatar_url($user_id),
                'credentials' => array(),
                'experience_years' => 0,
                'specializations' => array(),
                'certifications' => array(),
                'verified' => false
            );
        }
        
        // Decode JSON fields
        $profile['credentials'] = json_decode($profile['credentials'], true) ?: array();
        $profile['specializations'] = json_decode($profile['specializations'], true) ?: array();
        $profile['certifications'] = json_decode($profile['certifications'], true) ?: array();
        $profile['education'] = json_decode($profile['education'], true) ?: array();
        $profile['publications'] = json_decode($profile['publications'], true) ?: array();
        $profile['awards'] = json_decode($profile['awards'], true) ?: array();
        
        return $profile;
    }

    public function save_profile($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'delice_author_profiles';
        
        $user_id = intval($data['user_id']);
        
        if (!$user_id) {
            return false;
        }
        
        // Prepare data
        $profile_data = array(
            'user_id' => $user_id,
            'display_name' => isset($data['display_name']) ? sanitize_text_field($data['display_name']) : '',
            'bio' => isset($data['bio']) ? sanitize_textarea_field($data['bio']) : '',
            'experience_years' => isset($data['experience_years']) ? intval($data['experience_years']) : 0,
            'verified' => isset($data['verified']) ? 1 : 0
        );
        
        // Handle JSON fields
        if (isset($data['credentials']) && is_array($data['credentials'])) {
            $profile_data['credentials'] = json_encode(array_map('sanitize_text_field', array_filter($data['credentials'])));
        }
        
        if (isset($data['specializations']) && is_array($data['specializations'])) {
            $profile_data['specializations'] = json_encode(array_map('sanitize_text_field', array_filter($data['specializations'])));
        }
        
        if (isset($data['certifications']) && is_array($data['certifications'])) {
            $profile_data['certifications'] = json_encode(array_map('sanitize_text_field', array_filter($data['certifications'])));
        }
        
        // Check if profile exists
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE user_id = %d", $user_id));
        
        if ($existing) {
            return $wpdb->update($table, $profile_data, array('user_id' => $user_id));
        } else {
            return $wpdb->insert($table, $profile_data);
        }
    }

    public function get_author_stats($user_id) {
        global $wpdb;
        
        // Count recipes
        $recipe_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author = %d AND post_status = 'publish' AND (post_type = 'delice_recipe' OR post_type = 'post')",
            $user_id
        ));
        
        // Average rating
        $avg_rating = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(CAST(pm.meta_value AS DECIMAL(3,2))) 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_author = %d AND pm.meta_key = '_delice_recipe_rating_average' AND p.post_status = 'publish'",
            $user_id
        ));
        
        // Total cooks
        $total_cooks = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}delice_user_cooks uc
            INNER JOIN {$wpdb->posts} p ON uc.recipe_id = p.ID
            WHERE p.post_author = %d AND uc.approved = 1",
            $user_id
        ));
        
        return array(
            'recipe_count' => $recipe_count,
            'avg_rating' => $avg_rating ? round($avg_rating, 1) : 0,
            'total_cooks' => $total_cooks
        );
    }
}
