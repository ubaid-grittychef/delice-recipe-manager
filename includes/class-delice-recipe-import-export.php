<?php
/**
 * Delice Recipe Import/Export Handler
 * 
 * Handles importing and exporting recipes in JSON format
 * 
 * @package Delice_Recipe_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}


if (!class_exists('Delice_Recipe_Import_Export')) {
class Delice_Recipe_Import_Export {
    
    /**
     * Export single recipe to JSON
     */
    public function export_recipe($recipe_id) {
        $recipe = get_post($recipe_id);
        
        if (!$recipe || $recipe->post_type !== 'delice_recipe') {
            return new WP_Error('invalid_recipe', __('Invalid recipe ID', 'delice-recipe-manager'));
        }
        
        $export_data = array(
            'version' => '1.0',
            'exported_at' => current_time('mysql'),
            'recipe' => array(
                'title' => $recipe->post_title,
                'content' => $recipe->post_content,
                'status' => $recipe->post_status,
                'author' => get_the_author_meta('user_login', $recipe->post_author),
                'date' => $recipe->post_date,
                'meta' => $this->get_recipe_meta($recipe_id),
                'taxonomies' => $this->get_recipe_taxonomies($recipe_id),
                'featured_image' => $this->get_featured_image_data($recipe_id),
            )
        );
        
        return $export_data;
    }
    
    /**
     * Export multiple recipes to JSON
     */
    public function export_recipes($recipe_ids = array()) {
        if ( ! current_user_can('edit_posts') ) {
            return new WP_Error('unauthorized', __('You do not have permission to export recipes.', 'delice-recipe-manager'));
        }

        if (empty($recipe_ids)) {
            // Export all recipes
            $recipe_ids = get_posts(array(
                'post_type' => 'delice_recipe',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'post_status' => array('publish', 'draft', 'pending')
            ));
        }
        
        $recipes = array();
        foreach ($recipe_ids as $recipe_id) {
            $recipe_data = $this->export_recipe($recipe_id);
            if (!is_wp_error($recipe_data)) {
                $recipes[] = $recipe_data['recipe'];
            }
        }
        
        return array(
            'version' => '1.0',
            'exported_at' => current_time('mysql'),
            'total_recipes' => count($recipes),
            'recipes' => $recipes
        );
    }
    
    /**
     * Export settings to JSON
     */
    public function export_settings() {
        $settings = array(
            'version' => '1.0',
            'exported_at' => current_time('mysql'),
            'settings' => array(
                'display_options' => get_option('delice_recipe_display_options', array()),
                'selected_template' => get_option('delice_recipe_selected_template', 'default'),
                'schema_settings' => get_option('delice_recipe_schema_settings', array()),
                'attribution_settings' => get_option('delice_recipe_attribution_settings', array()),
                'default_language' => get_option('delice_recipe_default_language', 'en_US'),
                'enabled_languages' => get_option('delice_recipe_enabled_languages', array()),
                // ai_api_key intentionally excluded — never export credentials.
                'enable_ai_images' => get_option('delice_recipe_enable_ai_images', false),
                'review_settings' => get_option('delice_recipe_review_settings', array()),
                'default_cuisine' => get_option('delice_recipe_default_cuisine', ''),
                'default_method' => get_option('delice_recipe_default_method', ''),
                'default_servings' => get_option('delice_recipe_default_servings', '4'),
            ),
            'translations' => $this->export_translations()
        );
        
        return $settings;
    }
    
    /**
     * Import recipe from JSON data
     */
    public function import_recipe($recipe_data, $options = array()) {
        $defaults = array(
            'skip_existing' => false,
            'update_existing' => false,
            'import_images' => true,
            'match_by' => 'title' // title, slug, or id
        );
        
        $options = wp_parse_args($options, $defaults);
        
        // Check if recipe exists
        if ($options['skip_existing'] || $options['update_existing']) {
            $existing_id = $this->find_existing_recipe($recipe_data, $options['match_by']);
            
            if ($existing_id) {
                if ($options['skip_existing']) {
                    return array(
                        'success' => false,
                        'message' => __('Recipe already exists (skipped)', 'delice-recipe-manager'),
                        'recipe_id' => $existing_id
                    );
                }
            }
        }
        
        // Prepare post data
        $post_data = array(
            'post_title' => sanitize_text_field($recipe_data['title']),
            'post_content' => wp_kses_post($recipe_data['content']),
            'post_status' => sanitize_text_field($recipe_data['status']),
            'post_type' => 'delice_recipe',
        );
        
        // Get or create author
        if (!empty($recipe_data['author'])) {
            $author = get_user_by('login', $recipe_data['author']);
            if ($author) {
                $post_data['post_author'] = $author->ID;
            }
        }
        
        // Insert or update recipe
        if (isset($existing_id) && $options['update_existing']) {
            $post_data['ID'] = $existing_id;
            $recipe_id = wp_update_post($post_data);
        } else {
            $recipe_id = wp_insert_post($post_data);
        }
        
        if (is_wp_error($recipe_id)) {
            return array(
                'success' => false,
                'message' => $recipe_id->get_error_message()
            );
        }
        
        // Import meta data
        if (!empty($recipe_data['meta'])) {
            $this->import_recipe_meta($recipe_id, $recipe_data['meta']);
        }
        
        // Import taxonomies
        if (!empty($recipe_data['taxonomies'])) {
            $this->import_recipe_taxonomies($recipe_id, $recipe_data['taxonomies']);
        }
        
        // Import featured image
        if ($options['import_images'] && !empty($recipe_data['featured_image'])) {
            $this->import_featured_image($recipe_id, $recipe_data['featured_image']);
        }
        
        return array(
            'success' => true,
            'message' => __('Recipe imported successfully', 'delice-recipe-manager'),
            'recipe_id' => $recipe_id
        );
    }
    
    /**
     * Import multiple recipes from JSON
     */
    public function import_recipes($import_data, $options = array()) {
        if (empty($import_data['recipes']) || !is_array($import_data['recipes'])) {
            return new WP_Error('invalid_data', __('Invalid import data', 'delice-recipe-manager'));
        }
        
        $results = array(
            'total' => count($import_data['recipes']),
            'imported' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => array()
        );
        
        foreach ($import_data['recipes'] as $recipe_data) {
            $result = $this->import_recipe($recipe_data, $options);
            
            if ($result['success']) {
                $results['imported']++;
            } else {
                if (strpos($result['message'], 'skipped') !== false) {
                    $results['skipped']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = $result['message'];
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Import settings from JSON
     */
    public function import_settings($settings_data, $merge = false) {
        if (empty($settings_data['settings'])) {
            return new WP_Error('invalid_data', __('Invalid settings data', 'delice-recipe-manager'));
        }
        
        $settings = $settings_data['settings'];
        
        // Import each setting
        if ($merge) {
            // Merge with existing settings
            foreach ($settings as $key => $value) {
                $option_name = 'delice_recipe_' . $key;
                
                if ($key === 'display_options' || $key === 'schema_settings' || 
                    $key === 'attribution_settings' || $key === 'review_settings') {
                    // Merge arrays
                    $existing = get_option($option_name, array());
                    $merged = array_merge($existing, $value);
                    update_option($option_name, $merged);
                } else {
                    // Only update if not empty
                    if (!empty($value)) {
                        update_option($option_name, $value);
                    }
                }
            }
        } else {
            // Replace all settings
            foreach ($settings as $key => $value) {
                $option_name = 'delice_recipe_' . $key;
                update_option($option_name, $value);
            }
        }
        
        // Import translations
        if (!empty($settings_data['translations'])) {
            $this->import_translations($settings_data['translations'], $merge);
        }
        
        return array(
            'success' => true,
            'message' => __('Settings imported successfully', 'delice-recipe-manager')
        );
    }
    
    /**
     * Get recipe meta data
     */
    private function get_recipe_meta($recipe_id) {
        $meta_keys = array(
            '_delice_recipe_servings',
            '_delice_recipe_prep_time',
            '_delice_recipe_cook_time',
            '_delice_recipe_total_time',
            '_delice_recipe_difficulty',
            '_delice_recipe_calories',
            '_delice_recipe_cuisine',
            '_delice_recipe_cooking_method',
            '_delice_recipe_ingredients',
            '_delice_recipe_instructions',
            '_delice_recipe_notes',
            '_delice_recipe_faqs',
            '_delice_recipe_submitted_by',
            '_delice_recipe_tested_by',
            '_delice_recipe_nutrition',
        );
        
        $meta_data = array();
        foreach ($meta_keys as $key) {
            $value = get_post_meta($recipe_id, $key, true);
            if (!empty($value)) {
                $meta_data[$key] = $value;
            }
        }
        
        return $meta_data;
    }
    
    /**
     * Get recipe taxonomies
     */
    private function get_recipe_taxonomies($recipe_id) {
        $taxonomies = get_object_taxonomies('delice_recipe');
        $tax_data = array();
        
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_post_terms($recipe_id, $taxonomy);
            if (!is_wp_error($terms) && !empty($terms)) {
                $tax_data[$taxonomy] = array_map(function($term) {
                    return array(
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'description' => $term->description
                    );
                }, $terms);
            }
        }
        
        return $tax_data;
    }
    
    /**
     * Get featured image data
     */
    private function get_featured_image_data($recipe_id) {
        $thumbnail_id = get_post_thumbnail_id($recipe_id);
        if (!$thumbnail_id) {
            return null;
        }
        
        return array(
            'url' => wp_get_attachment_url($thumbnail_id),
            'alt' => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true),
            'caption' => wp_get_attachment_caption($thumbnail_id)
        );
    }
    
    /**
     * Export translations
     */
    private function export_translations() {
        $languages = get_option('delice_recipe_enabled_languages', array('en_US'));
        $translations = array();
        
        foreach ($languages as $lang) {
            $translations[$lang] = get_option('delice_recipe_translations_' . $lang, array());
        }
        
        return $translations;
    }
    
    /**
     * Import recipe meta
     */
    private function import_recipe_meta($recipe_id, $meta_data) {
        foreach ($meta_data as $key => $value) {
            update_post_meta($recipe_id, sanitize_key($key), $value);
        }
    }
    
    /**
     * Import recipe taxonomies
     */
    private function import_recipe_taxonomies($recipe_id, $tax_data) {
        foreach ($tax_data as $taxonomy => $terms) {
            $term_ids = array();
            
            foreach ($terms as $term_data) {
                $term = term_exists($term_data['slug'], $taxonomy);
                
                if (!$term) {
                    $term = wp_insert_term(
                        $term_data['name'],
                        $taxonomy,
                        array(
                            'slug' => $term_data['slug'],
                            'description' => $term_data['description']
                        )
                    );
                }
                
                if (!is_wp_error($term)) {
                    $term_ids[] = is_array($term) ? $term['term_id'] : $term;
                }
            }
            
            wp_set_object_terms($recipe_id, $term_ids, $taxonomy);
        }
    }
    
    /**
     * Import featured image
     */
    private function import_featured_image($recipe_id, $image_data) {
        if (empty($image_data['url'])) {
            return;
        }
        
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $attachment_id = media_sideload_image($image_data['url'], $recipe_id, null, 'id');
        
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($recipe_id, $attachment_id);
            
            if (!empty($image_data['alt'])) {
                update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($image_data['alt']));
            }
        }
    }
    
    /**
     * Import translations
     */
    private function import_translations($translations, $merge = false) {
        foreach ($translations as $lang => $translation_data) {
            $option_name = 'delice_recipe_translations_' . $lang;
            
            if ($merge) {
                $existing = get_option($option_name, array());
                $merged = array_merge($existing, $translation_data);
                update_option($option_name, $merged);
            } else {
                update_option($option_name, $translation_data);
            }
        }
    }
    
    /**
     * Find existing recipe
     */
    private function find_existing_recipe($recipe_data, $match_by) {
        global $wpdb;
        
        switch ($match_by) {
            case 'title':
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} 
                     WHERE post_title = %s AND post_type = %s",
                    $recipe_data['title'],
                    'delice_recipe'
                ));
                return $existing ? intval($existing) : null;
                
            case 'slug':
                $slug = sanitize_title($recipe_data['title']);
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} 
                     WHERE post_name = %s AND post_type = %s",
                    $slug,
                    'delice_recipe'
                ));
                return $existing ? intval($existing) : null;
                
            default:
                return null;
        }
    }
}
}
