<?php
/**
 * Delice Recipe Equipment Manager — v3.9.0
 *
 * Handles kitchen equipment per recipe:
 *  - Storage: _delice_recipe_equipment meta (array of { name, notes, required, product_url })
 *  - AI extraction: reads Instructions text → OpenAI → returns equipment list
 *  - Affiliate matching: direct product_url (preferred) or keyword-rule fallback
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Delice_Recipe_Equipment {

    const META_KEY = '_delice_recipe_equipment';

    // ── Sanitisation ──────────────────────────────────────────────────────────

    /**
     * Sanitize raw POST array before saving to DB.
     *
     * @param  mixed $raw  Unsanitized input (should be array).
     * @return array       Clean array of equipment items.
     */
    public static function sanitize( $raw ) {
        if ( ! is_array( $raw ) ) return array();
        $clean = array();
        foreach ( $raw as $item ) {
            if ( ! is_array( $item ) ) continue;
            $name = sanitize_text_field( $item['name'] ?? '' );
            if ( empty( $name ) ) continue;
            $clean[] = array(
                'name'        => $name,
                'notes'       => sanitize_text_field( $item['notes'] ?? '' ),
                'required'    => ! empty( $item['required'] ),
                'product_url' => esc_url_raw( $item['product_url'] ?? '' ),
            );
        }
        return $clean;
    }

    // ── Affiliate matching ────────────────────────────────────────────────────

    /**
     * Return the equipment array for a recipe with affiliate URLs attached.
     * Uses the same multi-platform matching engine as ingredients.
     *
     * @param  int   $recipe_id  Post ID.
     * @return array             Equipment items, each optionally with affiliate_links, affiliate_url, affiliate_store.
     */
    public static function get_with_affiliate( $recipe_id ) {
        $equipment = get_post_meta( absint( $recipe_id ), self::META_KEY, true );
        if ( ! is_array( $equipment ) || empty( $equipment ) ) return array();

        if ( ! class_exists( 'Delice_Affiliate_Manager' ) ) return $equipment;

        $settings = Delice_Affiliate_Manager::get_settings();
        if ( empty( $settings['enabled'] ) ) return $equipment;

        // Get auto-link Amazon platform for this recipe's language (fallback support)
        $auto_amazon = null;
        if ( ! empty( $settings['auto_link'] ) ) {
            $auto_amazon = self::get_auto_amazon_platform( $recipe_id );
        }

        foreach ( $equipment as &$item ) {
            if ( ! empty( $item['product_url'] ) ) {
                // User pinned a specific product URL
                // Try to add affiliate tracking if it's an Amazon URL
                if ( strpos( $item['product_url'], 'amazon' ) !== false ) {
                    $result = Delice_Affiliate_Manager::build_amazon_url( $item['product_url'], $recipe_id );
                    $item['affiliate_url']   = $result['url'];
                    $item['affiliate_store'] = $result['store'];
                    $item['affiliate_links'] = array( array(
                        'url'   => $result['url'],
                        'store' => $result['store'],
                        'type'  => 'amazon',
                    ) );
                } else {
                    // Non-Amazon URL - use as-is (custom affiliate URL)
                    $item['affiliate_url']   = $item['product_url'];
                    $item['affiliate_store'] = __( 'Store', 'delice-recipe-manager' );
                    $item['affiliate_links'] = array( array(
                        'url'   => $item['product_url'],
                        'store' => __( 'Store', 'delice-recipe-manager' ),
                        'type'  => 'custom',
                    ) );
                }
            } else {
                // Fall back to keyword-rule matching with multi-platform support
                $all_links = Delice_Affiliate_Manager::match_ingredient_all_platforms( $item['name'] ?? '', $auto_amazon );
                
                if ( ! empty( $all_links ) ) {
                    $item['affiliate_links'] = $all_links;
                    $item['affiliate_url']   = $all_links[0]['url'];
                    $item['affiliate_store'] = $all_links[0]['store'];
                }
            }
        }
        unset( $item );

        return $equipment;
    }

    /**
     * Get the best Amazon platform for auto-linking based on recipe language.
     *
     * @param  int $recipe_id
     * @return array|null
     */
    private static function get_auto_amazon_platform( $recipe_id ) {
        $current_lang = '';
        $recipe_locale = get_post_meta( absint( $recipe_id ), '_delice_recipe_language', true );
        if ( $recipe_locale ) {
            $current_lang = strtolower( substr( $recipe_locale, 0, 2 ) );
        }
        
        if ( ! $current_lang ) {
            $current_lang = strtolower( substr( get_locale(), 0, 2 ) );
        }

        $fallback = null;
        foreach ( Delice_Affiliate_Manager::get_platforms() as $platform ) {
            if ( empty( $platform['active'] )
                 || ( $platform['type'] ?? '' ) !== 'amazon'
                 || empty( $platform['tracking_id'] ) ) {
                continue;
            }
            
            $plat_lang = $platform['language'] ?? '';
            if ( $plat_lang && strtolower( $plat_lang ) === $current_lang ) {
                return $platform;
            }
            
            if ( ! $fallback ) {
                $fallback = $platform;
            }
        }

        return $fallback;
    }

    // ── AI extraction ─────────────────────────────────────────────────────────

    /**
     * Extract kitchen equipment from recipe instructions text via OpenAI.
     *
     * Uses the same API key as the AI recipe generator. On success returns a
     * sanitized equipment array; on failure returns null.
     *
     * @param  string      $instructions_text  Plain-text instructions.
     * @return array|null
     */
    public static function extract_from_instructions( $instructions_text ) {
        $api_key = get_option( 'delice_recipe_ai_api_key', '' );
        if ( empty( $api_key ) ) return null;

        $system = 'You are a culinary expert. Extract ONLY kitchen tools, appliances, and equipment mentioned in the recipe instructions. Return a valid JSON array of objects with these exact keys: name (string), notes (string — optional, e.g. "or use a blender"), required (boolean). Do not include knives or cutting boards unless explicitly named. Return ONLY the JSON array with no extra text.';

        $prompt = "Recipe instructions:\n\n" . wp_strip_all_tags( $instructions_text );

        $body = array(
            'model'       => 'gpt-4o-mini',
            'messages'    => array(
                array( 'role' => 'system', 'content' => $system ),
                array( 'role' => 'user',   'content' => $prompt ),
            ),
            'temperature' => 0.2,
            'max_tokens'  => 512,
        );

        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) return null;
        if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) return null;

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        $text = $data['choices'][0]['message']['content'] ?? '';

        // Extract the first JSON array from the response
        if ( preg_match( '/\[[\s\S]*\]/u', $text, $m ) ) {
            $parsed = json_decode( $m[0], true );
            if ( is_array( $parsed ) ) {
                return self::sanitize( $parsed );
            }
        }
        return null;
    }
}
