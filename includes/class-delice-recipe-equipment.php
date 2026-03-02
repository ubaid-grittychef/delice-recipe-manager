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
     * The same keyword-matching engine used for ingredients is applied to
     * each equipment name.
     *
     * @param  int   $recipe_id  Post ID.
     * @return array             Equipment items, each optionally with affiliate_url + affiliate_store.
     */
    public static function get_with_affiliate( $recipe_id ) {
        $equipment = get_post_meta( absint( $recipe_id ), self::META_KEY, true );
        if ( ! is_array( $equipment ) || empty( $equipment ) ) return array();

        if ( ! class_exists( 'Delice_Affiliate_Manager' ) ) return $equipment;

        $settings = Delice_Affiliate_Manager::get_settings();
        if ( empty( $settings['enabled'] ) ) return $equipment;

        foreach ( $equipment as &$item ) {
            if ( ! empty( $item['product_url'] ) ) {
                // User pinned a specific product — link directly to it with the
                // correct affiliate tag for this recipe's language.
                $result = Delice_Affiliate_Manager::build_amazon_url( $item['product_url'], $recipe_id );
                $item['affiliate_url']   = $result['url'];
                $item['affiliate_store'] = $result['store'];
            } else {
                // Fall back to keyword-rule matching (same engine as ingredients).
                $match = Delice_Affiliate_Manager::match_ingredient( $item['name'] ?? '' );
                if ( $match ) {
                    $item['affiliate_url']   = $match['url'];
                    $item['affiliate_store'] = $match['store'];
                }
            }
        }
        unset( $item );

        return $equipment;
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
