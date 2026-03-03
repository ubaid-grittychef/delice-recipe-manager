<?php
/**
 * Handle recipe template management - Enhanced with reviews control
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Delice_Recipe_Templates')) {
class Delice_Recipe_Templates {
    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Register settings with WordPress
     */
    public function register_settings() {
        // Register template selection setting
        register_setting('delice_recipe_settings', 'delice_recipe_selected_template', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_template_selection'),
            'default' => 'default'
        ));

        // Register reviews feature toggle
        register_setting('delice_recipe_settings', 'delice_recipe_reviews_enabled', array(
            'type' => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_boolean'),
            'default' => true
        ));

        // Register attribution settings
        register_setting('delice_recipe_settings', 'delice_recipe_attribution_settings', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_attribution_settings'),
            'default' => array(
                'kitchen_name' => '',
                'kitchen_url' => '',
                'show_submitted_by' => true,
                'show_tested_by' => true,
            )
        ));

        // Register display options
        register_setting('delice_recipe_settings', 'delice_recipe_display_options', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_display_options'),
            'default' => $this->get_default_display_options()
        ));
    }

    /**
     * Sanitize boolean values
     */
    public function sanitize_boolean($input) {
        return !empty($input);
    }

    /**
     * Sanitize template selection
     */
    public function sanitize_template_selection($input) {
        $available = array_keys($this->get_available_templates());
        return in_array($input, $available, true) ? $input : 'default';
    }

    /**
     * Enqueue CSS and JS assets
     */
    public function enqueue_assets() {
        if (is_singular('delice_recipe') || is_page() || is_single()) {
            // Check if files exist before enqueueing
            $attribution_css = DELICE_RECIPE_PLUGIN_DIR . 'public/css/components/recipe-attribution.css';
            if (file_exists($attribution_css)) {
                wp_enqueue_style(
                    'delice-recipe-attribution',
                    DELICE_RECIPE_PLUGIN_URL . 'public/css/components/recipe-attribution.css',
                    array(),
                    DELICE_RECIPE_VERSION
                );
            }

            $action_buttons_css = DELICE_RECIPE_PLUGIN_DIR . 'public/css/components/recipe-action-buttons.css';
            if (file_exists($action_buttons_css)) {
                wp_enqueue_style(
                    'delice-recipe-action-buttons',
                    DELICE_RECIPE_PLUGIN_URL . 'public/css/components/recipe-action-buttons.css',
                    array(),
                    DELICE_RECIPE_VERSION
                );
            }

            // Enhanced reviews CSS
            $reviews_css = DELICE_RECIPE_PLUGIN_DIR . 'public/css/components/recipe-reviews.css';
            if (file_exists($reviews_css)) {
                wp_enqueue_style(
                    'delice-recipe-reviews',
                    DELICE_RECIPE_PLUGIN_URL . 'public/css/components/recipe-reviews.css',
                    array(),
                    DELICE_RECIPE_VERSION
                );
            }

            // Affiliate links CSS (v3.8.4)
            $affiliate_css = DELICE_RECIPE_PLUGIN_DIR . 'public/css/components/recipe-affiliate.css';
            if ( file_exists( $affiliate_css ) ) {
                wp_enqueue_style(
                    'delice-recipe-affiliate',
                    DELICE_RECIPE_PLUGIN_URL . 'public/css/components/recipe-affiliate.css',
                    array(),
                    DELICE_RECIPE_VERSION
                );
            }

            // Load template-specific stylesheet
            $selected_template = $this->get_selected_template();
            if ( $selected_template === 'modern' ) {
                $modern_css = DELICE_RECIPE_PLUGIN_DIR . 'public/css/delice-modern.css';
                if ( file_exists( $modern_css ) ) {
                    wp_enqueue_style(
                        'delice-recipe-modern-template',
                        DELICE_RECIPE_PLUGIN_URL . 'public/css/delice-modern.css',
                        array(),
                        DELICE_RECIPE_VERSION
                    );
                }
            } elseif ( $selected_template === 'elegant' ) {
                $elegant_css = DELICE_RECIPE_PLUGIN_DIR . 'public/css/delice-elegant.css';
                if ( file_exists( $elegant_css ) ) {
                    wp_enqueue_style(
                        'delice-recipe-elegant-template',
                        DELICE_RECIPE_PLUGIN_URL . 'public/css/delice-elegant.css',
                        array(),
                        DELICE_RECIPE_VERSION
                    );
                }
            }

            $action_buttons_js = DELICE_RECIPE_PLUGIN_DIR . 'public/js/delice-recipe-action-buttons.js';
            if (file_exists($action_buttons_js)) {
                wp_enqueue_script(
                    'delice-recipe-action-buttons',
                    DELICE_RECIPE_PLUGIN_URL . 'public/js/delice-recipe-action-buttons.js',
                    array('jquery'),
                    DELICE_RECIPE_VERSION,
                    true
                );
            }

            // Enhanced reviews JS
            $reviews_enabled = get_option('delice_recipe_reviews_enabled', true);
            if ($reviews_enabled) {
                $rating_js = DELICE_RECIPE_PLUGIN_DIR . 'public/js/delice-recipe-rating.js';
                if (file_exists($rating_js)) {
                    wp_enqueue_script(
                        'delice-recipe-rating',
                        DELICE_RECIPE_PLUGIN_URL . 'public/js/delice-recipe-rating.js',
                        array('jquery'),
                        DELICE_RECIPE_VERSION,
                        true
                    );

                    // Localize script data
                    wp_localize_script('delice-recipe-rating', 'deliceRecipeData', array(
                        'ajaxurl' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce('delice_recipe_rating_nonce'),
                        'strings' => array(
                            'rating_required' => __('Please select a rating first.', 'delice-recipe-manager'),
                            'comment_required' => __('Please enter a comment.', 'delice-recipe-manager'),
                            'submitting' => __('Submitting...', 'delice-recipe-manager'),
                            'rating_success' => __('Rating saved successfully!', 'delice-recipe-manager'),
                            'review_success' => __('Review submitted successfully!', 'delice-recipe-manager'),
                            'error_occurred' => __('An error occurred. Please try again.', 'delice-recipe-manager'),
                        )
                    ));
                }
            }
        }
    }

    /**
     * Sanitize attribution settings
     */
    public function sanitize_attribution_settings($input) {
        $sanitized = array();
        
        $sanitized['kitchen_name'] = sanitize_text_field($input['kitchen_name'] ?? '');
        $sanitized['kitchen_url'] = esc_url_raw($input['kitchen_url'] ?? '');
        $sanitized['show_submitted_by'] = !empty($input['show_submitted_by']);
        $sanitized['show_tested_by'] = !empty($input['show_tested_by']);
        $sanitized['default_author_name'] = sanitize_text_field($input['default_author_name'] ?? '');
        
        return $sanitized;
    }

    /**
     * Sanitize display options
     */
    public function sanitize_display_options($input) {
        $sanitized = array();
        $defaults = $this->get_default_display_options();
        
        foreach ($defaults as $key => $default) {
            $sanitized[$key] = !empty($input[$key]);
        }
        
        return $sanitized;
    }

    /**
     * Get available templates
     */
    public function get_available_templates() {
        return array(
            'default' => __('Default', 'delice-recipe-manager'),
            'modern' => __('Modern', 'delice-recipe-manager'),
            'elegant' => __('Elegant', 'delice-recipe-manager'),
        );
    }

    /**
     * Get selected template
     */
    public function get_selected_template() {
        return get_option('delice_recipe_selected_template', 'default');
    }

    /**
     * Get default display options
     */
    public function get_default_display_options() {
        return array(
            'show_image'               => true,
            'show_servings'            => true,
            'show_prep_time'           => true,
            'show_cook_time'           => true,
            'show_total_time'          => true,
            'show_calories'            => true,
            'show_difficulty'          => true,
            'show_rating'              => true,
            'show_nutrition'           => true,
            'show_ingredients'         => true,
            'show_instructions'        => true,
            'show_notes'               => true,
            'show_faqs'                => true,
            'show_equipment'           => true,
            // v3.8.0 feature toggles — all on by default
            'show_jump_btn'            => true,
            'show_cook_mode'           => true,
            'show_dietary_badges'      => true,
            'show_breadcrumb'          => true,
            'show_related_recipes'     => true,
            'show_nutrition_disclaimer' => true,
            'show_last_updated'        => true,
            'show_og_meta'             => true,
        );
    }

    /**
     * Render recipe template - NEW METHOD
     */
    public function render_recipe($recipe_id, $args = array()) {
        return $this->load_template(null, $recipe_id, $args);
    }

    /**
     * Load template with proper data handling and selected template
     */
    public function load_template($template_name = null, $recipe_id, $args = array()) {
        // Validate recipe ID
        if (!$recipe_id || !get_post($recipe_id)) {
            error_log("Delice Recipe: Invalid recipe ID: $recipe_id");
            return '<p>' . __('Recipe not found.', 'delice-recipe-manager') . '</p>';
        }

        // Use selected template if none specified
        if ($template_name === null) {
            $template_name = $this->get_selected_template();
        }

        // Sanitize template name
        $template_name = sanitize_key($template_name);
        $available = array_keys($this->get_available_templates());
        if (!in_array($template_name, $available, true)) {
            $template_name = 'default';
        }

        // Get template file with fallback
        $template_file = DELICE_RECIPE_PLUGIN_DIR . "public/partials/recipe-template-{$template_name}.php";
        if (!file_exists($template_file)) {
            $template_file = DELICE_RECIPE_PLUGIN_DIR . 'public/partials/recipe-template-default.php';
            if (!file_exists($template_file)) {
                error_log("Delice Recipe: Template file not found: $template_file");
                return '<p>' . __('Recipe template not found.', 'delice-recipe-manager') . '</p>';
            }
        }

        // Get recipe data from database with validation
        $ingredients = get_post_meta($recipe_id, '_delice_recipe_ingredients', true);
        $instructions = get_post_meta($recipe_id, '_delice_recipe_instructions', true);
        $faqs = get_post_meta($recipe_id, '_delice_recipe_faqs', true);

        // Ensure arrays and standardize data structure
        $ingredients = $this->standardize_ingredients($ingredients);
        $instructions = $this->standardize_instructions($instructions);
        $faqs = $this->standardize_faqs($faqs);

        // Get other meta data
        $prep_time = get_post_meta($recipe_id, '_delice_recipe_prep_time', true);
        $cook_time = get_post_meta($recipe_id, '_delice_recipe_cook_time', true);
        $total_time = get_post_meta($recipe_id, '_delice_recipe_total_time', true);
        $servings = get_post_meta($recipe_id, '_delice_recipe_servings', true);
        $calories = get_post_meta($recipe_id, '_delice_recipe_calories', true);
        $difficulty = get_post_meta($recipe_id, '_delice_recipe_difficulty', true);
        $notes = get_post_meta($recipe_id, '_delice_recipe_notes', true);

        // Get display options
        $display_options = get_option('delice_recipe_display_options', $this->get_default_display_options());

        // Equipment — v3.9.0
        $equipment = array();
        if ( class_exists( 'Delice_Recipe_Equipment' ) ) {
            $equipment = get_post_meta( $recipe_id, Delice_Recipe_Equipment::META_KEY, true );
            if ( ! is_array( $equipment ) ) $equipment = array();
        }

        // Prepare template variables
        $template_vars = array_merge($args, array(
            'recipe_id' => $recipe_id,
            'ingredients' => $ingredients,
            'instructions' => $instructions,
            'faqs' => $faqs,
            'prep_time' => $prep_time,
            'cook_time' => $cook_time,
            'total_time' => $total_time,
            'servings' => $servings,
            'calories' => $calories,
            'difficulty' => $difficulty,
            'notes' => $notes,
            'equipment' => $equipment,
            'display_options' => $display_options,
            'hide_title' => false,
        ));

        // Extract variables and render template
        extract($template_vars);
        ob_start();
        
        // Ensure language class is loaded
        if (!class_exists('Delice_Recipe_Language')) {
            require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-language.php';
        }
        
        echo '<div data-recipe-id="' . esc_attr($recipe_id) . '">';
        include $template_file;
        echo '</div>';
        
        return ob_get_clean();
    }

    /**
     * Standardize ingredients data structure
     */
    private function standardize_ingredients($ingredients) {
        if (!is_array($ingredients)) {
            return array();
        }

        $standardized = array();
        foreach ($ingredients as $ingredient) {
            if (is_string($ingredient)) {
                // Convert string to standard format
                $standardized[] = array(
                    'name' => $ingredient,
                    'amount' => '',
                    'unit' => '',
                );
            } elseif (is_array($ingredient)) {
                $standardized[] = array(
                    'name' => $ingredient['name'] ?? '',
                    'amount' => $ingredient['amount'] ?? '',
                    'unit' => $ingredient['unit'] ?? '',
                );
            }
        }

        return $standardized;
    }

    /**
     * Standardize instructions data structure
     */
    private function standardize_instructions($instructions) {
        if (!is_array($instructions)) {
            return array();
        }

        $standardized = array();
        foreach ($instructions as $index => $instruction) {
            if (is_string($instruction)) {
                // Convert string to standard format
                $standardized[] = array(
                    'step' => $index + 1,
                    'text' => $instruction,
                );
            } elseif (is_array($instruction)) {
                $standardized[] = array(
                    'step' => $instruction['step'] ?? $index + 1,
                    'text' => $instruction['text'] ?? '',
                );
            }
        }

        return $standardized;
    }

    /**
     * Standardize FAQs data structure
     */
    private function standardize_faqs($faqs) {
        if (!is_array($faqs)) {
            return array();
        }

        $standardized = array();
        foreach ($faqs as $faq) {
            if (is_array($faq) && isset($faq['question']) && isset($faq['answer'])) {
                $standardized[] = array(
                    'question' => $faq['question'],
                    'answer' => $faq['answer'],
                );
            }
        }

        return $standardized;
    }
}
}
