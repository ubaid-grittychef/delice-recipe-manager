<?php
/**
 * Delice Affiliate Manager
 *
 * Stores keyword→affiliate-URL rules and provides a matching engine
 * that injects links into the ingredient array at template render time.
 *
 * Google-compliance enforced here:
 *  - All affiliate links carry rel="sponsored nofollow noopener noreferrer"
 *  - Links are NEVER injected into Schema.org JSON-LD markup
 *  - FTC disclosure text is surfaced when any live links are present
 *  - Link-density cap prevents over-monetisation signals
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Delice_Affiliate_Manager {

    const RULES_OPTION    = 'delice_affiliate_rules';
    const SETTINGS_OPTION = 'delice_affiliate_settings';
    const LINK_REL        = 'sponsored nofollow noopener noreferrer';

    // ── Settings ──────────────────────────────────────────────────────────────

    public static function get_settings() {
        $defaults = array(
            'enabled'         => false,
            'max_links'       => 5,
            'density_pct'     => 50,
            'open_new_tab'    => true,
            'disclosure_text' => 'This recipe contains affiliate links. If you purchase through these links we may earn a small commission at no extra cost to you.',
            'disclosure_pos'  => 'top',
            'button_text'     => 'Buy',
            'show_store_name' => true,
        );
        return wp_parse_args( get_option( self::SETTINGS_OPTION, array() ), $defaults );
    }

    public static function get_rules() {
        $rules = get_option( self::RULES_OPTION, array() );
        return is_array( $rules ) ? $rules : array();
    }

    // ── Matching engine ───────────────────────────────────────────────────────

    /**
     * Find the best-matching active rule for a single ingredient name.
     * Priority: exact > starts_with > contains. Longest keyword wins within tier.
     *
     * @return array|null  { url, store } or null
     */
    public static function match_ingredient( $ingredient_name ) {
        $rules  = self::get_rules();
        $needle = mb_strtolower( trim( $ingredient_name ) );
        $best   = null;
        $best_score = -1;

        foreach ( $rules as $rule ) {
            if ( empty( $rule['active'] ) || empty( $rule['keyword'] ) || empty( $rule['url'] ) ) {
                continue;
            }
            $kw    = mb_strtolower( trim( $rule['keyword'] ) );
            $kw_len = mb_strlen( $kw );
            $mt    = $rule['match_type'] ?? 'contains';
            $score = 0;

            if ( $mt === 'exact' && $needle === $kw ) {
                $score = 30000 + $kw_len;
            } elseif ( $mt === 'starts' && strncmp( $needle, $kw, $kw_len ) === 0 ) {
                $score = 20000 + $kw_len;
            } elseif ( $mt === 'contains' && str_contains( $needle, $kw ) ) {
                $score = 10000 + $kw_len;
            }

            if ( $score > $best_score ) {
                $best_score = $score;
                $best = array(
                    'url'   => esc_url_raw( $rule['url'] ),
                    'store' => sanitize_text_field( $rule['store'] ?? '' ),
                );
            }
        }
        return $best;
    }

    /**
     * Inject affiliate link data into an ingredients array.
     * Respects max_links and density_pct. Adds affiliate_url + affiliate_store keys.
     *
     * @param  array $ingredients  { name, amount, unit }[]
     * @return array               { ingredients: array, has_links: bool }
     */
    public static function inject_links( array $ingredients ) {
        $settings = self::get_settings();
        if ( empty( $settings['enabled'] ) ) {
            return array( 'ingredients' => $ingredients, 'has_links' => false );
        }

        $total       = count( $ingredients );
        $max_links   = max( 1, intval( $settings['max_links'] ) );
        $density_cap = (int) ceil( $total * max( 1, intval( $settings['density_pct'] ) ) / 100 );
        $cap         = min( $max_links, $density_cap );
        $linked      = 0;

        foreach ( $ingredients as &$ing ) {
            if ( $linked >= $cap ) break;
            $match = self::match_ingredient( $ing['name'] ?? '' );
            if ( $match ) {
                $ing['affiliate_url']   = $match['url'];
                $ing['affiliate_store'] = $match['store'];
                $linked++;
            }
        }
        unset( $ing );

        return array( 'ingredients' => $ingredients, 'has_links' => $linked > 0 );
    }

    // ── Sanitisation ──────────────────────────────────────────────────────────

    public static function sanitize_rules( $raw ) {
        if ( ! is_array( $raw ) ) return array();
        $clean = array();
        foreach ( $raw as $rule ) {
            if ( ! is_array( $rule ) ) continue;
            $keyword = sanitize_text_field( $rule['keyword'] ?? '' );
            $url     = esc_url_raw( $rule['url'] ?? '' );
            if ( empty( $keyword ) || empty( $url ) ) continue;
            if ( ! preg_match( '#^https?://#i', $url ) ) continue;
            $mt = in_array( $rule['match_type'] ?? '', array( 'exact', 'starts', 'contains' ), true )
                  ? $rule['match_type'] : 'contains';
            $clean[] = array(
                'id'         => sanitize_key( $rule['id'] ?? uniqid( 'aff_', true ) ),
                'keyword'    => $keyword,
                'url'        => $url,
                'store'      => sanitize_text_field( $rule['store'] ?? '' ),
                'match_type' => $mt,
                'active'     => ! empty( $rule['active'] ),
            );
        }
        return $clean;
    }

    public static function sanitize_settings( $raw ) {
        if ( ! is_array( $raw ) ) return array();
        return array(
            'enabled'         => ! empty( $raw['enabled'] ),
            'max_links'       => max( 1, min( 20, intval( $raw['max_links'] ?? 5 ) ) ),
            'density_pct'     => max( 1, min( 100, intval( $raw['density_pct'] ?? 50 ) ) ),
            'open_new_tab'    => ! empty( $raw['open_new_tab'] ),
            'disclosure_text' => sanitize_textarea_field( $raw['disclosure_text'] ?? '' ),
            'disclosure_pos'  => in_array( $raw['disclosure_pos'] ?? '', array( 'top', 'bottom' ), true )
                                   ? $raw['disclosure_pos'] : 'top',
            'button_text'     => sanitize_text_field( $raw['button_text'] ?? 'Buy' ),
            'show_store_name' => ! empty( $raw['show_store_name'] ),
        );
    }

    // ── Disclosure HTML ───────────────────────────────────────────────────────

    /**
     * Return the disclosure banner HTML. Empty string when disabled / no text.
     */
    public static function get_disclosure_html() {
        $settings = self::get_settings();
        $text     = trim( $settings['disclosure_text'] );
        if ( empty( $settings['enabled'] ) || empty( $text ) ) return '';

        return '<div class="delice-affiliate-disclosure" role="note">'
             . '<svg class="delice-aff-disc-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" width="16" height="16">'
             . '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>'
             . '</svg>'
             . '<span>' . esc_html( $text ) . '</span>'
             . '</div>';
    }
}
