<?php
/**
 * Language utilities for recipe management
 */

if (!class_exists('Delice_Recipe_Language')) {
class Delice_Recipe_Language {

    /**
     * Get current language
     */
    public static function get_current_language() {
        // Check URL parameter first
        if (isset($_GET['lang'])) {
            $lang = sanitize_text_field($_GET['lang']);
            $enabled_languages = get_option('delice_recipe_enabled_languages', array('en_US'));
            if (in_array($lang, $enabled_languages)) {
                return $lang;
            }
        }
        
        // Fall back to default language
        return get_option('delice_recipe_default_language', 'en_US');
    }

    /**
     * Get language text for a specific key
     */
    public static function get_text($key, $default = '') {
        $language = self::get_current_language();
        $language_texts = get_option('delice_recipe_language_texts', array());
        
        if (isset($language_texts[$language][$key])) {
            return $language_texts[$language][$key];
        }
        
        // Fall back to English if available
        if ($language !== 'en_US' && isset($language_texts['en_US'][$key])) {
            return $language_texts['en_US'][$key];
        }
        
        return $default;
    }

    /**
     * Get all language texts for current language
     */
    public static function get_all_texts() {
        $language = self::get_current_language();
        $language_texts = get_option('delice_recipe_language_texts', array());
        
        $texts = isset($language_texts[$language]) ? $language_texts[$language] : array();
        
        // Merge with defaults to ensure all keys exist
        $defaults = array(
            'servings' => 'Servings',
            'prep_time' => 'Prep Time',
            'cook_time' => 'Cook Time',
            'total_time' => 'Total Time',
            'calories' => 'Calories',
            'difficulty' => 'Difficulty',
            'ingredients' => 'Ingredients',
            'instructions' => 'Instructions',
            'notes' => 'Notes',
            'faqs' => 'Frequently Asked Questions',
            'print' => 'Print Recipe',
            'copy' => 'Copy Ingredients',
            'share' => 'Share',
            'rate' => 'Rate this Recipe',
            'submitted_by' => 'Submitted by',
            'tested_by' => 'Tested by',
            'min' => 'min',
            'mins' => 'mins'
        );
        
        return array_merge($defaults, $texts);
    }

    /**
     * Map AI generation language names to locale codes
     */
    public static function map_ai_language_to_locale($ai_language) {
        $mapping = array(
            'english' => 'en_US',
            'french' => 'fr_FR',
            'spanish' => 'es_ES',
            'german' => 'de_DE',
            'italian' => 'it_IT',
            'portuguese' => 'pt_BR',
            'japanese' => 'ja',
            'chinese' => 'zh_CN',
            'russian' => 'ru_RU',
            'arabic' => 'ar'
        );
        
        $normalized = strtolower(trim($ai_language));
        return isset($mapping[$normalized]) ? $mapping[$normalized] : 'en_US';
    }

    /**
     * Map locale codes to AI generation language names
     */
    public static function map_locale_to_ai_language($locale) {
        $mapping = array(
            'en_US' => 'english',
            'en_GB' => 'english',
            'fr_FR' => 'french',
            'es_ES' => 'spanish',
            'de_DE' => 'german',
            'it_IT' => 'italian',
            'pt_BR' => 'portuguese',
            'ja' => 'japanese',
            'zh_CN' => 'chinese',
            'ru_RU' => 'russian',
            'ar' => 'arabic'
        );
        
        return isset($mapping[$locale]) ? $mapping[$locale] : 'english';
    }

    /**
     * Get recipe's stored language, fallback to current language
     */
    public static function get_recipe_language($recipe_id) {
        $recipe_language = get_post_meta($recipe_id, '_delice_recipe_language', true);
        if (!empty($recipe_language)) {
            return $recipe_language;
        }
        return self::get_current_language();
    }

    /**
     * Check if recipe language matches current language
     */
    public static function is_recipe_in_current_language($recipe_id) {
        $recipe_language = self::get_recipe_language($recipe_id);
        $current_language = self::get_current_language();
        return $recipe_language === $current_language;
    }

    /**
     * Get frontend script data for language support
     */
    public static function get_frontend_script_data() {
        $texts = self::get_all_texts();
        
        return array(
            'language' => self::get_current_language(),
            'texts' => $texts,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('delice_recipe_nonce')
        );
    }
}}
