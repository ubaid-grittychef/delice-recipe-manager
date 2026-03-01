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
        // Check URL parameter first (for multilingual setups)
        if ( isset( $_GET['lang'] ) ) {
            $lang = sanitize_key( $_GET['lang'] );
            // Accept any valid locale code (e.g. en_US, fr_FR, ja)
            if ( preg_match( '/^[a-z]{2,3}(_[A-Z]{2})?$/', $lang ) ) {
                return $lang;
            }
        }

        // Read from the option key the admin Settings > Languages tab saves to
        $selected = get_option( 'delice_recipe_selected_language', '' );
        if ( $selected ) {
            return $selected;
        }

        // Backward compat: old option key used by legacy admin forms
        return get_option( 'delice_recipe_default_language', 'en_US' );
    }

    /**
     * Get language text for a specific key
     */
    public static function get_text( $key, $default = '' ) {
        $texts = self::get_all_texts();

        if ( isset( $texts[ $key ] ) && $texts[ $key ] !== '' ) {
            return $texts[ $key ];
        }

        return $default;
    }

    /**
     * Get all language texts for current language
     */
    public static function get_all_texts() {
        $language = self::get_current_language();

        // Read from new option key (set by admin Settings > Languages tab)
        $texts = get_option( "delice_recipe_translations_{$language}", array() );

        // Backward compat: try old nested option format used by legacy admin forms
        if ( empty( $texts ) ) {
            $legacy = get_option( 'delice_recipe_language_texts', array() );
            if ( isset( $legacy[ $language ] ) ) {
                $texts = (array) $legacy[ $language ];
            }
        }

        // Normalize key aliases: admin form saves print_button/rating, defaults use print/rate
        $key_aliases = array(
            'print_button' => 'print',
            'rating'       => 'rate',
        );
        foreach ( $key_aliases as $alias => $canonical ) {
            if ( isset( $texts[ $alias ] ) && ! isset( $texts[ $canonical ] ) ) {
                $texts[ $canonical ] = $texts[ $alias ];
            }
        }

        // Merge with defaults to ensure all keys exist
        $defaults = array(
            'servings'             => 'Servings',
            'prep_time'            => 'Prep Time',
            'cook_time'            => 'Cook Time',
            'total_time'           => 'Total Time',
            'calories'             => 'Calories',
            'difficulty'           => 'Difficulty',
            'ingredients'          => 'Ingredients',
            'instructions'         => 'Instructions',
            'notes'                => 'Notes',
            'faqs'                 => 'Frequently Asked Questions',
            'print'                => 'Print Recipe',
            'copy'                 => 'Copy Ingredients',
            'share'                => 'Share',
            'rate'                 => 'Rate this Recipe',
            'submitted_by'         => 'Submitted by',
            'tested_by'            => 'Tested by',
            'min'                  => 'min',
            'mins'                 => 'mins',
            'updated'              => 'Updated',
            'nutrition_disclaimer' => 'Nutrition values are estimates and may vary based on ingredients used.',
            'home'                 => 'Home',
            'ratings'              => 'ratings',
            'jump_to_recipe'       => 'Jump to Recipe',
            'cook_mode_start'      => 'Start Cooking',
            'cook_mode_stop'       => 'Stop Cooking',
            'start_timer'          => 'Start Timer',
            'timer_done'           => 'Timer done!',
            'related_recipes'      => 'You Might Also Like',
        );

        return array_merge( $defaults, $texts );
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
