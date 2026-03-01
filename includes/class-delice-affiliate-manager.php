<?php
/**
 * Delice Affiliate Manager — v3.8.5
 *
 * Three-layer system:
 *  1. Platforms  — connected affiliate networks (Amazon Associates, etc.)
 *  2. Rules      — keyword → platform/URL mappings
 *  3. Settings   — density caps, disclosure text, button label
 *
 * Google-compliance baked in:
 *  - All links carry rel="sponsored nofollow noopener noreferrer"
 *  - Links NEVER touch Schema.org JSON-LD output
 *  - FTC disclosure shown only when ≥1 link is on the page
 *  - Density cap + hard max prevent link-spam signals
 *  - Print stylesheet (recipe-affiliate.css) hides all affiliate elements
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Delice_Affiliate_Manager {

    const PLATFORMS_OPTION = 'delice_affiliate_platforms';
    const RULES_OPTION     = 'delice_affiliate_rules';
    const SETTINGS_OPTION  = 'delice_affiliate_settings';
    const OVERRIDE_META    = '_delice_affiliate_ingredients'; // per-recipe ingredient name override
    const LINK_REL         = 'sponsored nofollow noopener noreferrer';

    /** Amazon region → TLD map */
    const AMAZON_REGIONS = array(
        'us' => array( 'label' => 'United States (amazon.com)',       'tld' => 'com' ),
        'uk' => array( 'label' => 'United Kingdom (amazon.co.uk)',     'tld' => 'co.uk' ),
        'de' => array( 'label' => 'Germany (amazon.de)',               'tld' => 'de' ),
        'fr' => array( 'label' => 'France (amazon.fr)',                'tld' => 'fr' ),
        'it' => array( 'label' => 'Italy (amazon.it)',                 'tld' => 'it' ),
        'es' => array( 'label' => 'Spain (amazon.es)',                 'tld' => 'es' ),
        'ca' => array( 'label' => 'Canada (amazon.ca)',                'tld' => 'ca' ),
        'jp' => array( 'label' => 'Japan (amazon.co.jp)',              'tld' => 'co.jp' ),
        'in' => array( 'label' => 'India (amazon.in)',                 'tld' => 'in' ),
        'au' => array( 'label' => 'Australia (amazon.com.au)',         'tld' => 'com.au' ),
        'mx' => array( 'label' => 'Mexico (amazon.com.mx)',            'tld' => 'com.mx' ),
        'br' => array( 'label' => 'Brazil (amazon.com.br)',            'tld' => 'com.br' ),
        'nl' => array( 'label' => 'Netherlands (amazon.nl)',           'tld' => 'nl' ),
        'se' => array( 'label' => 'Sweden (amazon.se)',                'tld' => 'se' ),
        'sg' => array( 'label' => 'Singapore (amazon.sg)',             'tld' => 'sg' ),
        'ae' => array( 'label' => 'UAE (amazon.ae)',                   'tld' => 'ae' ),
        'sa' => array( 'label' => 'Saudi Arabia (amazon.sa)',          'tld' => 'sa' ),
        'pl' => array( 'label' => 'Poland (amazon.pl)',                'tld' => 'pl' ),
        'be' => array( 'label' => 'Belgium (amazon.com.be)',           'tld' => 'com.be' ),
        'eg' => array( 'label' => 'Egypt (amazon.eg)',                 'tld' => 'eg' ),
    );

    // ── Platforms ─────────────────────────────────────────────────────────────

    public static function get_platforms() {
        $p = get_option( self::PLATFORMS_OPTION, array() );
        return is_array( $p ) ? $p : array();
    }

    public static function find_platform( $id ) {
        foreach ( self::get_platforms() as $p ) {
            if ( ( $p['id'] ?? '' ) === $id ) return $p;
        }
        return null;
    }

    public static function sanitize_platforms( $raw ) {
        if ( ! is_array( $raw ) ) return array();
        $clean = array();
        $allowed_types = array( 'amazon', 'shareasale', 'cj', 'impact', 'custom' );
        foreach ( $raw as $p ) {
            if ( ! is_array( $p ) ) continue;
            $type = in_array( $p['type'] ?? '', $allowed_types, true ) ? $p['type'] : 'custom';
            $entry = array(
                'id'          => sanitize_key( $p['id'] ?? uniqid( 'plat_', true ) ),
                'type'        => $type,
                'name'        => sanitize_text_field( $p['name'] ?? '' ),
                'tracking_id' => sanitize_text_field( $p['tracking_id'] ?? '' ),
                'active'      => ! empty( $p['active'] ),
                'language'    => strtolower( sanitize_text_field( $p['language'] ?? '' ) ),
            );
            if ( $type === 'amazon' ) {
                $regions = array_keys( self::AMAZON_REGIONS );
                $entry['region'] = in_array( $p['region'] ?? '', $regions, true ) ? $p['region'] : 'us';
            }
            if ( in_array( $type, array( 'shareasale', 'cj', 'impact', 'custom' ), true ) ) {
                $entry['search_url'] = esc_url_raw( $p['search_url'] ?? '' );
            }
            if ( ! empty( $entry['name'] ) ) $clean[] = $entry;
        }
        return $clean;
    }

    // ── URL building ──────────────────────────────────────────────────────────

    /**
     * Build the affiliate URL for a given rule + matched platform.
     *
     * @param  array  $rule      Keyword rule row.
     * @param  array  $platform  Platform row.
     * @param  string $keyword   Raw ingredient name (used for Amazon search URLs).
     * @return string            Ready-to-use affiliate URL, or empty string.
     */
    public static function build_platform_url( array $rule, array $platform, $keyword ) {
        $type = $platform['type'] ?? 'custom';
        $tid  = $platform['tracking_id'] ?? '';

        // If rule has a fully custom URL, always prefer it
        $custom = $rule['custom_url'] ?? '';
        if ( ! empty( $custom ) ) {
            return esc_url_raw( $custom );
        }

        if ( $type === 'amazon' && ! empty( $tid ) ) {
            $region  = $platform['region'] ?? 'us';
            $regions = self::AMAZON_REGIONS;
            $tld     = isset( $regions[ $region ] ) ? $regions[ $region ]['tld'] : 'com';

            // Direct product (ASIN) link
            if ( ! empty( $rule['product_id'] ) ) {
                return esc_url_raw(
                    'https://www.amazon.' . $tld . '/dp/'
                    . rawurlencode( sanitize_text_field( $rule['product_id'] ) )
                    . '?tag=' . rawurlencode( $tid )
                );
            }

            // Amazon search link (ingredient keyword)
            return esc_url_raw(
                'https://www.amazon.' . $tld . '/s?k='
                . rawurlencode( $keyword )
                . '&tag=' . rawurlencode( $tid )
            );
        }

        if ( in_array( $type, array( 'shareasale', 'cj', 'impact', 'custom' ), true ) ) {
            $tmpl = $platform['search_url'] ?? '';
            if ( ! empty( $tmpl ) ) {
                return esc_url_raw( str_replace( '{keyword}', rawurlencode( $keyword ), $tmpl ) );
            }
        }

        return '';
    }

    // ── Settings ──────────────────────────────────────────────────────────────

    public static function get_settings() {
        $defaults = array(
            'enabled'         => false,
            'auto_link'       => false,
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

    // ── Rules ─────────────────────────────────────────────────────────────────

    public static function get_rules() {
        $rules = get_option( self::RULES_OPTION, array() );
        return is_array( $rules ) ? $rules : array();
    }

    // ── Matching engine ───────────────────────────────────────────────────────

    /**
     * Match a single ingredient name and return { url, store } or null.
     */
    public static function match_ingredient( $ingredient_name ) {
        $rules     = self::get_rules();
        $platforms = self::get_platforms();
        $needle    = mb_strtolower( trim( $ingredient_name ) );
        $best      = null;
        $best_score = -1;

        foreach ( $rules as $rule ) {
            if ( empty( $rule['active'] ) || empty( $rule['keyword'] ) ) continue;

            $kw    = mb_strtolower( trim( $rule['keyword'] ) );
            $kw_len = mb_strlen( $kw );
            $mt    = $rule['match_type'] ?? 'contains';
            $score = 0;

            if ( $mt === 'exact'    && $needle === $kw )                          $score = 30000 + $kw_len;
            elseif ( $mt === 'starts'   && strncmp( $needle, $kw, $kw_len ) === 0 ) $score = 20000 + $kw_len;
            elseif ( $mt === 'contains' && str_contains( $needle, $kw ) )           $score = 10000 + $kw_len;

            if ( $score <= $best_score ) continue;

            // Resolve the affiliate URL
            $platform_id = $rule['platform_id'] ?? '';
            $platform    = null;
            foreach ( $platforms as $p ) {
                if ( ( $p['id'] ?? '' ) === $platform_id && ! empty( $p['active'] ) ) {
                    $platform = $p;
                    break;
                }
            }

            $url = '';
            if ( $platform ) {
                $url = self::build_platform_url( $rule, $platform, $ingredient_name );
            } elseif ( ! empty( $rule['custom_url'] ) ) {
                $url = esc_url_raw( $rule['custom_url'] );
            }

            if ( empty( $url ) ) continue;

            $store = ! empty( $platform['name'] ) ? $platform['name'] : sanitize_text_field( $rule['store'] ?? '' );

            $best_score = $score;
            $best = array( 'url' => $url, 'store' => $store );
        }

        return $best;
    }

    // ── Override ingredients (for old / manually-created recipes) ────────────

    /**
     * Parse a newline- or comma-separated list of ingredient names into the
     * standard ingredient-array structure used by the rest of the system.
     *
     * @param  string $text Raw textarea value from the override meta field.
     * @return array        Array of [ 'name' => string, 'amount' => '', 'unit' => '' ]
     */
    public static function parse_text_ingredients( $text ) {
        $lines = preg_split( '/[\r\n,]+/', trim( $text ) );
        $out   = array();
        foreach ( $lines as $line ) {
            $name = sanitize_text_field( $line );
            if ( ! empty( $name ) ) {
                $out[] = array( 'name' => $name, 'amount' => '', 'unit' => '' );
            }
        }
        return $out;
    }

    /**
     * Return coverage data for every delice_recipe post — used by the Coverage tab.
     *
     * @return array[]  Each entry: id, title, edit_url, post_status, has_struct,
     *                  ingredient_count, has_override, override_text, match_count, status.
     */
    public static function get_recipe_coverage() {
        $posts = get_posts( array(
            'post_type'      => 'delice_recipe',
            'posts_per_page' => -1,
            'post_status'    => array( 'publish', 'draft', 'private', 'pending' ),
            'fields'         => 'ids',
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        ) );

        $coverage = array();
        foreach ( $posts as $pid ) {
            $structured  = get_post_meta( $pid, '_delice_recipe_ingredients', true );
            $override    = get_post_meta( $pid, self::OVERRIDE_META, true );
            $has_struct  = ! empty( $structured ) && is_array( $structured );
            $has_override = ! empty( trim( $override ?? '' ) );

            $ingredients = array();
            if ( $has_struct ) {
                $ingredients = $structured;
            } elseif ( $has_override ) {
                $ingredients = self::parse_text_ingredients( $override );
            }

            $match_count = 0;
            foreach ( $ingredients as $ing ) {
                if ( self::match_ingredient( $ing['name'] ?? '' ) ) {
                    $match_count++;
                }
            }

            if ( $match_count > 0 ) {
                $status = 'ready';
            } elseif ( $has_struct || $has_override ) {
                $status = 'no-match';
            } else {
                $status = 'needs-tags';
            }

            $coverage[] = array(
                'id'               => $pid,
                'title'            => get_the_title( $pid ),
                'edit_url'         => get_edit_post_link( $pid, 'raw' ),
                'post_status'      => get_post_status( $pid ),
                'has_struct'       => $has_struct,
                'ingredient_count' => count( $ingredients ),
                'has_override'     => $has_override,
                'override_text'    => trim( $override ?? '' ),
                'match_count'      => $match_count,
                'status'           => $status,
            );
        }
        return $coverage;
    }

    /**
     * Inject affiliate data into an ingredients array.
     *
     * @param  array $ingredients  Structured ingredient array (may be empty for old recipes).
     * @param  int   $recipe_id    Post ID — used to fetch the OVERRIDE_META fallback when
     *                             $ingredients is empty.
     * @return array { ingredients: array, has_links: bool }
     */
    public static function inject_links( array $ingredients, $recipe_id = 0 ) {
        $settings = self::get_settings();
        if ( empty( $settings['enabled'] ) ) {
            return array( 'ingredients' => $ingredients, 'has_links' => false );
        }

        // If no structured ingredients, fall back to the per-recipe override meta
        if ( empty( $ingredients ) && $recipe_id > 0 ) {
            $override = get_post_meta( absint( $recipe_id ), self::OVERRIDE_META, true );
            if ( ! empty( trim( $override ?? '' ) ) ) {
                $ingredients = self::parse_text_ingredients( $override );
            }
        }

        $total       = count( $ingredients );
        $max_links   = max( 1, intval( $settings['max_links'] ) );
        $density_cap = (int) ceil( $total * max( 1, intval( $settings['density_pct'] ) ) / 100 );
        $cap         = min( $max_links, $density_cap );
        $linked      = 0;

        // When auto_link is on, resolve the best Amazon platform for this recipe's language.
        $auto_amazon = null;
        if ( ! empty( $settings['auto_link'] ) ) {
            // Priority 1: the recipe's own language stored by the AI generator
            // (_delice_recipe_language holds a locale like fr_FR, es_ES, en_US, ar).
            $current_lang = '';
            if ( $recipe_id > 0 ) {
                $recipe_locale = get_post_meta( absint( $recipe_id ), '_delice_recipe_language', true );
                if ( $recipe_locale ) {
                    // fr_FR → fr,  es_ES → es,  ar → ar,  zh_CN → zh
                    $current_lang = strtolower( substr( $recipe_locale, 0, 2 ) );
                }
            }
            // Priority 2: WPML / Polylang / WP locale fallback
            if ( ! $current_lang ) {
                $current_lang = self::get_current_language();
            }

            $fallback = null;
            foreach ( self::get_platforms() as $platform ) {
                if ( empty( $platform['active'] )
                     || ( $platform['type'] ?? '' ) !== 'amazon'
                     || empty( $platform['tracking_id'] ) ) {
                    continue;
                }
                // Exact language match wins immediately.
                $plat_lang = $platform['language'] ?? '';
                if ( $plat_lang && strtolower( $plat_lang ) === $current_lang ) {
                    $auto_amazon = $platform;
                    break;
                }
                // Keep first active Amazon as fallback for unmatched languages.
                if ( ! $fallback ) {
                    $fallback = $platform;
                }
            }
            if ( ! $auto_amazon ) {
                $auto_amazon = $fallback;
            }
        }

        foreach ( $ingredients as &$ing ) {
            if ( $linked >= $cap ) break;
            $match = self::match_ingredient( $ing['name'] ?? '' );

            // Auto-link fallback: if no rule matched and auto_link + Amazon are active,
            // generate an Amazon search URL for the ingredient name automatically.
            if ( ! $match && $auto_amazon ) {
                $url = self::build_platform_url( array(), $auto_amazon, $ing['name'] ?? '' );
                if ( $url ) {
                    $match = array(
                        'url'   => $url,
                        'store' => $auto_amazon['name'],
                    );
                }
            }

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
            if ( empty( $keyword ) ) continue;
            $mt = in_array( $rule['match_type'] ?? '', array( 'exact', 'starts', 'contains' ), true )
                  ? $rule['match_type'] : 'contains';
            $custom = esc_url_raw( $rule['custom_url'] ?? '' );
            if ( ! empty( $custom ) && ! preg_match( '#^https?://#i', $custom ) ) $custom = '';

            $clean[] = array(
                'id'          => sanitize_key( $rule['id'] ?? uniqid( 'rule_', true ) ),
                'keyword'     => $keyword,
                'match_type'  => $mt,
                'platform_id' => sanitize_key( $rule['platform_id'] ?? '' ),
                'product_id'  => sanitize_text_field( $rule['product_id'] ?? '' ),
                'custom_url'  => $custom,
                'store'       => sanitize_text_field( $rule['store'] ?? '' ),
                'active'      => ! empty( $rule['active'] ),
            );
        }
        return $clean;
    }

    public static function sanitize_settings( $raw ) {
        if ( ! is_array( $raw ) ) return array();
        return array(
            'enabled'         => ! empty( $raw['enabled'] ),
            'auto_link'       => ! empty( $raw['auto_link'] ),
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

    // ── Language detection ────────────────────────────────────────────────────

    /**
     * Build an affiliate Amazon URL from a manually-supplied product URL.
     *
     * Strips any existing `tag=` parameter and appends the correct tracking ID
     * for the recipe's language (same platform resolution used in inject_links).
     *
     * @param  string $product_url  Raw Amazon product URL pasted by the user.
     * @param  int    $recipe_id    Recipe post ID (used for language detection).
     * @return array  { url: string, store: string }
     */
    public static function build_amazon_url( $product_url, $recipe_id = 0 ) {
        // Resolve the right Amazon platform the same way inject_links() does.
        $current_lang = '';
        if ( $recipe_id > 0 ) {
            $recipe_locale = get_post_meta( absint( $recipe_id ), '_delice_recipe_language', true );
            if ( $recipe_locale ) {
                $current_lang = strtolower( substr( $recipe_locale, 0, 2 ) );
            }
        }
        if ( ! $current_lang ) {
            $current_lang = self::get_current_language();
        }

        $platform = null;
        $fallback  = null;
        foreach ( self::get_platforms() as $p ) {
            if ( empty( $p['active'] ) || ( $p['type'] ?? '' ) !== 'amazon' || empty( $p['tracking_id'] ) ) {
                continue;
            }
            $plat_lang = $p['language'] ?? '';
            if ( $plat_lang && strtolower( $plat_lang ) === $current_lang ) {
                $platform = $p;
                break;
            }
            if ( ! $fallback ) {
                $fallback = $p;
            }
        }
        if ( ! $platform ) {
            $platform = $fallback;
        }

        if ( ! $platform ) {
            return array( 'url' => esc_url( $product_url ), 'store' => 'Amazon' );
        }

        // Strip any existing tag and append our tracking ID.
        $url   = remove_query_arg( 'tag', esc_url_raw( $product_url ) );
        $url   = add_query_arg( 'tag', rawurlencode( $platform['tracking_id'] ), $url );
        $store = ! empty( $platform['name'] ) ? $platform['name'] : 'Amazon';

        return array( 'url' => $url, 'store' => $store );
    }

    /**
     * Return the current 2-letter language code.
     *
     * Priority: WPML → Polylang → WordPress locale (fr_FR → fr).
     */
    private static function get_current_language() {
        // WPML
        if ( function_exists( 'apply_filters' ) ) {
            $wpml = apply_filters( 'wpml_current_language', null );
            if ( $wpml && is_string( $wpml ) ) {
                return strtolower( substr( $wpml, 0, 5 ) );
            }
        }
        // Polylang
        if ( function_exists( 'pll_current_language' ) ) {
            $pll = pll_current_language();
            if ( $pll && is_string( $pll ) ) {
                return strtolower( $pll );
            }
        }
        // WordPress locale (fr_FR → fr, en_US → en)
        return strtolower( substr( get_locale(), 0, 2 ) );
    }

    // ── Disclosure HTML ───────────────────────────────────────────────────────

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
