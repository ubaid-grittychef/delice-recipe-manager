<?php
/**
 * Recipe Schema Generator Class
 *
 * Handles the generation of structured data schema.org markup for recipes
 */

if (!class_exists('Delice_Recipe_Schema')) {
class Delice_Recipe_Schema {
    /**
     * Track which recipe IDs have had schema output
     *
     * @var int[]
     */
    private $output_recipes = array();
    
    /**
     * Store FAQ schema for later output
     * 
     * @var array
     */
    private $faq_schema = null;

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize tracker once per request
        $this->output_recipes = array();

        // Register schema options in settings
        add_filter( 'delice_recipe_display_options', array( $this, 'add_schema_display_options' ) );

        // Hook schema output once per request
        static $hooked = false;
        if ( ! $hooked ) {
            add_action( 'wp_head', array( $this, 'output_recipe_schema' ), 100 );
            add_action( 'wp_head', array( $this, 'output_recipe_meta_tags' ), 5 );
            // Yoast SEO integration
            add_filter( 'wpseo_title',    array( $this, 'filter_yoast_title' ) );
            add_filter( 'wpseo_metadesc', array( $this, 'filter_yoast_metadesc' ) );
            // RankMath integration
            add_filter( 'rank_math/frontend/title',       array( $this, 'filter_yoast_title' ) );
            add_filter( 'rank_math/frontend/description', array( $this, 'filter_yoast_metadesc' ) );
            $hooked = true;
        }
    }

    /**
     * Add schema options to display settings
     */
    public function add_schema_display_options( $options ) {
        if ( ! isset( $options['schema'] ) ) {
            $options['schema'] = array(
                'enabled'          => true,
                'publisher_name'   => get_bloginfo( 'name' ),
                'publisher_logo'   => '',
                'use_author'       => true,
                'default_author'   => '',
                'enable_nutrition' => true,
            );
        }
        return $options;
    }

    /**
     * Generate schema for a recipe
     */
    public function generate_recipe_schema( $recipe_id, $args = array() ) {
        $recipe_id = absint( $recipe_id );
        if ( ! $recipe_id ) {
            return array();
        }

        $recipe_post = get_post( $recipe_id );
        if ( ! $recipe_post ) {
            return array();
        }
        // Accept both the custom post type and regular posts that carry recipe meta (migrated recipes).
        $allowed_types = array( 'delice_recipe', 'post' );
        if ( ! in_array( $recipe_post->post_type, $allowed_types, true ) ) {
            return array();
        }

        try {
            // Extract recipe data from meta
            $ingredients  = get_post_meta( $recipe_id, '_delice_recipe_ingredients', true );
            $instructions = get_post_meta( $recipe_id, '_delice_recipe_instructions', true );
            $prep_time    = get_post_meta( $recipe_id, '_delice_recipe_prep_time', true );
            $cook_time    = get_post_meta( $recipe_id, '_delice_recipe_cook_time', true );
            $total_time   = get_post_meta( $recipe_id, '_delice_recipe_total_time', true );
            $servings     = get_post_meta( $recipe_id, '_delice_recipe_servings', true );
            $calories     = get_post_meta( $recipe_id, '_delice_recipe_calories', true );
            $difficulty   = get_post_meta( $recipe_id, '_delice_recipe_difficulty', true );
            $faqs         = get_post_meta( $recipe_id, '_delice_recipe_faqs', true );

            $ingredients  = is_array( $ingredients ) ? $ingredients : array();
            $instructions = is_array( $instructions ) ? $instructions : array();
            $faqs         = is_array( $faqs ) ? $faqs : array();

            // Debug for admin users
            if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) {
                error_log('Schema - Recipe ID: ' . $recipe_id);
                error_log('Schema - FAQs: ' . (is_array($faqs) ? count($faqs) . ' items' : 'Not array'));
            }

            // Get review data from new review system
            $review_system = new Delice_Recipe_Reviews();
            $review_data = $review_system->get_recipe_rating_data($recipe_id);

            $rating_average = floatval( get_post_meta( $recipe_id, '_delice_recipe_rating_average', true ) );
            $rating_count   = intval( get_post_meta( $recipe_id, '_delice_recipe_rating_count', true ) );

            $nutrition     = array();
            $nutrition_raw = get_post_meta( $recipe_id, '_delice_recipe_nutrition', true );
            if ( is_string( $nutrition_raw ) && ! empty( $nutrition_raw ) ) {
                $decoded = json_decode( wp_unslash( $nutrition_raw ), true );
                if ( is_array( $decoded ) ) {
                    $nutrition = $decoded;
                }
            }

            // Schema settings
            $settings       = get_option( 'delice_recipe_schema_settings', array() );
            $publisher_name = ! empty( $settings['publisher_name'] ) ? $settings['publisher_name'] : get_bloginfo( 'name' );
            $publisher_logo = ! empty( $settings['publisher_logo'] ) ? $settings['publisher_logo'] : '';

            if ( empty( $publisher_logo ) && has_custom_logo() ) {
                $logo_id    = get_theme_mod( 'custom_logo' );
                $logo_image = wp_get_attachment_image_src( $logo_id, 'full' );
                if ( $logo_image ) {
                    $publisher_logo = $logo_image[0];
                }
            }

            $use_author     = isset( $settings['use_author'] ) ? (bool) $settings['use_author'] : true;
            $default_author = isset( $settings['default_author'] ) ? $settings['default_author'] : '';

            if ( $use_author ) {
                $author = get_the_author_meta( 'display_name', $recipe_post->post_author );
                if ( empty( $author ) && ! empty( $default_author ) ) {
                    $author = $default_author;
                }
            } else {
                $author = ! empty( $default_author ) ? $default_author : get_bloginfo( 'name' );
            }

            // Build base schema array
            $schema = array(
                '@context'      => 'https://schema.org/',
                '@type'         => 'Recipe',
                'name'          => get_the_title( $recipe_id ),
                'datePublished' => get_the_date( 'c', $recipe_id ),
            );

            if ( ! empty( $recipe_post->post_excerpt ) ) {
                $schema['description'] = $recipe_post->post_excerpt;
            } elseif ( ! empty( $recipe_post->post_content ) ) {
                $schema['description'] = wp_trim_words( $recipe_post->post_content, 55, '...' );
            }

            if ( ! empty( $author ) ) {
                $schema['author'] = array(
                    '@type' => 'Person',
                    'name'  => $author,
                );
            }

            if ( ! empty( $publisher_name ) ) {
                $schema['publisher'] = array(
                    '@type' => 'Organization',
                    'name'  => $publisher_name,
                );
                if ( ! empty( $publisher_logo ) ) {
                    $schema['publisher']['logo'] = array(
                        '@type' => 'ImageObject',
                        'url'   => $publisher_logo,
                    );
                }
            }

            if ( has_post_thumbnail( $recipe_id ) ) {
                $thumb_id = get_post_thumbnail_id( $recipe_id );
                // Provide all three aspect ratios Google uses in different SERP contexts.
                $img_full   = wp_get_attachment_image_src( $thumb_id, 'full' );
                $img_16x9   = wp_get_attachment_image_src( $thumb_id, array( 1200, 675 ) );
                $img_4x3    = wp_get_attachment_image_src( $thumb_id, array( 1200, 900 ) );
                $img_1x1    = wp_get_attachment_image_src( $thumb_id, array( 1200, 1200 ) );
                $urls = array_unique( array_filter( array(
                    $img_16x9 ? $img_16x9[0] : null,
                    $img_4x3  ? $img_4x3[0]  : null,
                    $img_1x1  ? $img_1x1[0]  : null,
                    $img_full ? $img_full[0]  : null,
                ) ) );
                $schema['image'] = count( $urls ) === 1 ? reset( $urls ) : array_values( $urls );
            }

            $schema['dateModified'] = get_the_modified_date( 'c', $recipe_id );

            if ( $prep_time )  {
                $schema['prepTime'] = 'PT' . intval( $prep_time ) . 'M';
            }
            if ( $cook_time )  {
                $schema['cookTime'] = 'PT' . intval( $cook_time ) . 'M';
            }
            if ( $total_time ) {
                $schema['totalTime'] = 'PT' . intval( $total_time ) . 'M';
            }

            if ( $servings ) {
                $schema['recipeYield'] = $servings . ' servings';
            }

            if ( $difficulty ) {
                $schema['keywords'] = ucfirst( $difficulty ) . ' recipe';
            }

            if ( $ingredients ) {
                $schema['recipeIngredient'] = array();
                foreach ( $ingredients as $ing ) {
                    if ( empty( $ing['name'] ) ) {
                        continue;
                    }
                    $text = $ing['name'];
                    if ( ! empty( $ing['amount'] ) || ! empty( $ing['unit'] ) ) {
                        $text = trim( ($ing['amount'] ?? '') . ' ' . ($ing['unit'] ?? '') ) . ' ' . $text;
                    }
                    $schema['recipeIngredient'][] = trim( $text );
                }
            }

            if ( $instructions ) {
                usort( $instructions, function( $a, $b ) {
                    return intval( $a['step'] ?? 0 ) - intval( $b['step'] ?? 0 );
                });
                $schema['recipeInstructions'] = array();
                foreach ( $instructions as $inst ) {
                    if ( empty( $inst['text'] ) ) {
                        continue;
                    }
                    $schema['recipeInstructions'][] = array(
                        '@type' => 'HowToStep',
                        'text'  => wp_strip_all_tags( $inst['text'] ),
                    );
                }
            }

            if ( $calories || $nutrition ) {
                $schema['nutrition'] = array( '@type' => 'NutritionInformation' );
                if ( $calories ) {
                    $schema['nutrition']['calories'] = $calories . ' calories';
                }
                $nutrition_map = array(
                    'fat'           => 'fatContent',
                    'saturatedFat'  => 'saturatedFatContent',
                    'carbohydrates' => 'carbohydrateContent',
                    'sugar'         => 'sugarContent',
                    'fiber'         => 'fiberContent',
                    'protein'       => 'proteinContent',
                    'sodium'        => 'sodiumContent',
                    'cholesterol'   => 'cholesterolContent',
                );
                foreach ( $nutrition_map as $key => $field ) {
                    if ( isset( $nutrition[$key] ) && $nutrition[$key] !== '' ) {
                        $unit = in_array( $key, array('sodium','cholesterol') ) ? 'mg' : 'g';
                        $schema['nutrition'][$field] = $nutrition[$key] . $unit;
                    }
                }
            }

            if ( $review_data['count'] > 0 && $review_data['average'] > 0 ) {
                $schema['aggregateRating'] = array(
                    '@type'       => 'AggregateRating',
                    'ratingValue' => number_format( $review_data['average'], 1 ),
                    'ratingCount' => $review_data['count'],
                    'bestRating'  => '5',
                    'worstRating' => '1',
                );

                // Add individual reviews to schema
                if (!empty($review_data['reviews'])) {
                    $schema['review'] = array();
                    foreach ($review_data['reviews'] as $review) {
                        $schema['review'][] = array(
                            '@type' => 'Review',
                            'author' => array(
                                '@type' => 'Person',
                                'name' => $review->user_name
                            ),
                            'reviewRating' => array(
                                '@type' => 'Rating',
                                'ratingValue' => $review->rating,
                                'bestRating' => '5',
                                'worstRating' => '1'
                            ),
                            'reviewBody' => $review->comment,
                            'datePublished' => date('c', strtotime($review->created_at))
                        );
                    }
                }
            }

            // Read cuisine from taxonomy terms (not meta – the plugin stores them as terms).
            $cuisine_terms = wp_get_object_terms( $recipe_id, 'delice_cuisine', array( 'fields' => 'names' ) );
            if ( ! is_wp_error( $cuisine_terms ) && ! empty( $cuisine_terms ) ) {
                $schema['recipeCuisine'] = implode( ', ', $cuisine_terms );
            }

            // Read course/category from taxonomy terms.
            $course_terms = wp_get_object_terms( $recipe_id, 'delice_course', array( 'fields' => 'names' ) );
            if ( ! is_wp_error( $course_terms ) && ! empty( $course_terms ) ) {
                $schema['recipeCategory'] = implode( ', ', $course_terms );
            }

            // Read keyword terms from taxonomy – note the assignment must be
            // wrapped in parentheses to avoid PHP operator-precedence bug.
            $keywords = get_post_meta( $recipe_id, '_delice_recipe_keywords', true );
            if ( $keywords && is_array( $keywords ) ) {
                $joined = implode( ', ', array_map( 'sanitize_text_field', $keywords ) );
                if ( isset( $schema['keywords'] ) ) {
                    $schema['keywords'] .= ', ' . $joined;
                } else {
                    $schema['keywords'] = $joined;
                }
            }

            // Also pull keyword taxonomy terms.
            $keyword_terms = wp_get_object_terms( $recipe_id, 'delice_keyword', array( 'fields' => 'names' ) );
            if ( ! is_wp_error( $keyword_terms ) && ! empty( $keyword_terms ) ) {
                $term_str = implode( ', ', $keyword_terms );
                if ( isset( $schema['keywords'] ) && $schema['keywords'] ) {
                    $schema['keywords'] .= ', ' . $term_str;
                } else {
                    $schema['keywords'] = $term_str;
                }
            }

            // Add FAQPage schema if FAQs exist
            if (!empty($faqs)) {
                // Create FAQPage schema
                $faq_schema = array(
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    'mainEntity' => array()
                );
                
                foreach ($faqs as $faq) {
                    if (!empty($faq['question']) && !empty($faq['answer'])) {
                        $faq_schema['mainEntity'][] = array(
                            '@type' => 'Question',
                            'name' => wp_strip_all_tags($faq['question']),
                            'acceptedAnswer' => array(
                                '@type' => 'Answer',
                                'text' => wp_strip_all_tags($faq['answer'])
                            )
                        );
                    }
                }
                
                // Store FAQPage schema for output
                $this->faq_schema = $faq_schema;

                // Debug for admin users
                if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) {
                    error_log('FAQPage schema created with ' . count($faq_schema['mainEntity']) . ' questions');
                }
            }

            return apply_filters( 'delice_recipe_schema_data', $schema, $recipe_id, $args );

        } catch ( Exception $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Delice Recipe Schema Error: ' . $e->getMessage() );
            }
            return array();
        }
    }

    /**
     * Output schema in head for single recipe page
     */
    public function output_recipe_schema() {
        static $called = false;
        if ( $called ) {
            return;
        }
        $called = true;

        try {
            $post_id = get_the_ID();
            if ( ! $post_id ) {
                return;
            }

            $options = apply_filters( 'delice_recipe_display_options', array() );
            if ( empty( $options['schema']['enabled'] ) ) {
                return;
            }

            if ( is_singular( 'delice_recipe' ) ) {
                $this->output_single_recipe_schema( $post_id );
                return;
            }

            if ( is_singular() ) {
                $post = get_post( $post_id );
                if ( ! $post ) {
                    return;
                }
                $this->extract_and_output_shortcode_schemas( $post->post_content );
            }
            
            // Output FAQ schema if exists
            if (isset($this->faq_schema) && !empty($this->faq_schema['mainEntity'])) {
                $json = wp_json_encode($this->faq_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                if ($json !== false && json_last_error() === JSON_ERROR_NONE) {
                    echo "\n<!-- Delice Recipe FAQ Schema.org markup -->\n";
                    echo '<script type="application/ld+json">' . $json . '</script>' . "\n";
                }
            }
        } catch ( Exception $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Delice Recipe Schema Error: ' . $e->getMessage() );
            }
        }
    }

    /**
     * Output schema for a single recipe, prevents duplicates
     */
    public function output_single_recipe_schema( $recipe_id ) {
        $recipe_id = absint( $recipe_id );
        if ( in_array( $recipe_id, $this->output_recipes, true ) ) {
            return;
        }

        $schema = $this->generate_recipe_schema( $recipe_id );
        if ( empty( $schema ) ) {
            return;
        }

        $json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
        if ( $json === false || json_last_error() !== JSON_ERROR_NONE ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Delice Recipe Schema JSON Error: ' . json_last_error_msg() );
            }
            return;
        }

        $this->output_recipes[] = $recipe_id;
        echo "\n<!-- Delice Recipe Schema.org markup for recipe #{$recipe_id} -->\n";
        echo '<script type="application/ld+json">' . $json . '</script>' . "\n";

        // Output FAQ schema if exists (for this specific recipe)
        if (isset($this->faq_schema) && !empty($this->faq_schema['mainEntity'])) {
            $json = wp_json_encode($this->faq_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            if ($json !== false && json_last_error() === JSON_ERROR_NONE) {
                echo "\n<!-- Delice Recipe FAQ Schema.org markup for recipe #{$recipe_id} -->\n";
                echo '<script type="application/ld+json">' . $json . '</script>' . "\n";
            }
        }
    }

    /**
     * Find recipe shortcodes & output schema once per ID
     */
    private function extract_and_output_shortcode_schemas( $content ) {
        $pattern = '/\[delice_recipe\s+(?:[^\]]*\s)?id=["\']?(\d+)["\']?/i';
        preg_match_all( $pattern, $content, $matches );
        if ( empty( $matches[1] ) ) {
            return;
        }
        $ids = array_unique( array_map( 'absint', $matches[1] ) );
        foreach ( $ids as $id ) {
            $this->output_single_recipe_schema( $id );
        }
    }

    /**
     * Output <meta name="description"> for recipe pages.
     * Runs at priority 5 so it appears before the main schema block.
     * Defers to Yoast/RankMath when those plugins are active.
     */
    public function output_recipe_meta_tags() {
        // Skip if a dedicated SEO plugin handles meta tags.
        if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
            return;
        }

        $recipe_id = $this->get_current_recipe_id();
        if ( ! $recipe_id ) {
            return;
        }

        $description = $this->build_recipe_description( $recipe_id );
        if ( empty( $description ) ) {
            return;
        }

        echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
    }

    /**
     * Filter Yoast SEO title for recipe single pages.
     */
    public function filter_yoast_title( $title ) {
        $recipe_id = $this->get_current_recipe_id();
        if ( ! $recipe_id ) {
            return $title;
        }
        $recipe_title = get_the_title( $recipe_id );
        $site_name    = get_bloginfo( 'name' );
        return $recipe_title . ' Recipe | ' . $site_name;
    }

    /**
     * Filter Yoast/RankMath meta description for recipe single pages.
     */
    public function filter_yoast_metadesc( $desc ) {
        // Only override if the description is empty or the default.
        if ( ! empty( $desc ) ) {
            return $desc;
        }
        $recipe_id = $this->get_current_recipe_id();
        if ( ! $recipe_id ) {
            return $desc;
        }
        return $this->build_recipe_description( $recipe_id );
    }

    /**
     * Return the current recipe ID if we are on a recipe page.
     */
    private function get_current_recipe_id() {
        if ( ! is_singular() ) {
            return 0;
        }
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return 0;
        }
        if ( get_post_type( $post_id ) === 'delice_recipe' ) {
            return $post_id;
        }
        return 0;
    }

    /**
     * Build a concise meta description from recipe data.
     * Format: "[Recipe Name] – [excerpt/description]. [time] · [servings]."
     */
    private function build_recipe_description( $recipe_id ) {
        $post        = get_post( $recipe_id );
        $title       = get_the_title( $recipe_id );
        $prep_time   = get_post_meta( $recipe_id, '_delice_recipe_prep_time', true );
        $cook_time   = get_post_meta( $recipe_id, '_delice_recipe_cook_time', true );
        $servings    = get_post_meta( $recipe_id, '_delice_recipe_servings', true );
        $total_time  = get_post_meta( $recipe_id, '_delice_recipe_total_time', true );

        // Raw description: prefer excerpt, fall back to trimmed content.
        $desc = '';
        if ( $post && ! empty( $post->post_excerpt ) ) {
            $desc = $post->post_excerpt;
        } elseif ( $post && ! empty( $post->post_content ) ) {
            $desc = wp_trim_words( wp_strip_all_tags( $post->post_content ), 25, '' );
        }

        // Build details suffix.
        $details = array();
        if ( $total_time ) {
            $details[] = 'Ready in ' . intval( $total_time ) . ' min';
        } elseif ( $prep_time || $cook_time ) {
            $mins = intval( $prep_time ) + intval( $cook_time );
            if ( $mins ) {
                $details[] = 'Ready in ' . $mins . ' min';
            }
        }
        if ( $servings ) {
            $details[] = 'Serves ' . $servings;
        }

        $parts = array();
        if ( ! empty( $desc ) ) {
            $parts[] = rtrim( $desc, '.' ) . '.';
        }
        if ( ! empty( $details ) ) {
            $parts[] = implode( ' · ', $details ) . '.';
        }

        $full = implode( ' ', $parts );
        // Trim to 155 chars for search result display.
        if ( mb_strlen( $full ) > 155 ) {
            $full = mb_substr( $full, 0, 152 ) . '...';
        }

        return $full;
    }
}
}
