<?php
/**
 * Author utilities for recipe management
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Delice_Recipe_Author_Utils')) {
class Delice_Recipe_Author_Utils {

    /**
     * Get recipe author data
     */
    public static function get_recipe_author_data($recipe_id) {
        $post = get_post($recipe_id);
        if (!$post) {
            return array(
                'name' => '',
                'bio' => '',
                'avatar' => '',
                'url' => '',
            );
        }

        $author_id = $post->post_author;
        $attribution_settings = get_option('delice_recipe_attribution_settings', array());
        
        // Use default author name if set
        $author_name = '';
        if (!empty($attribution_settings['default_author_name'])) {
            $author_name = $attribution_settings['default_author_name'];
        } else {
            $author_name = get_the_author_meta('display_name', $author_id);
        }

        return array(
            'name' => $author_name,
            'bio' => get_the_author_meta('description', $author_id),
            'avatar' => get_avatar_url($author_id),
            'url' => get_author_posts_url($author_id),
        );
    }

    /**
     * Get kitchen attribution data
     */
    public static function get_kitchen_attribution($recipe_id) {
        $attribution_settings = get_option('delice_recipe_attribution_settings', array(
            'kitchen_name' => '',
            'kitchen_url' => '',
            'show_submitted_by' => true,
            'show_tested_by' => true,
        ));

        return $attribution_settings;
    }
}
}
