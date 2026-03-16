<?php
/**
 * Recipe nutritional information
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Delice_Recipe_Nutrition')) {
class Delice_Recipe_Nutrition {

    /**
     * Initialize the class
     */
    public function __construct() {
        // Nothing to do here
    }

    /**
     * Add nutritional information meta box
     */
    public function add_nutrition_meta_box() {
        add_meta_box(
            'delice_recipe_nutrition_meta',
            __('Nutritional Information', 'delice-recipe-manager'),
            array($this, 'render_nutrition_meta_box'),
            'delice_recipe',
            'normal',
            'default'
        );
    }

    /**
     * Render nutritional information meta box
     */
    public function render_nutrition_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('delice_recipe_nutrition_meta', 'delice_recipe_nutrition_nonce');
        
        // Get saved values — stored as JSON string; fall back gracefully for old array format.
        $nutrition_raw = get_post_meta( $post->ID, '_delice_recipe_nutrition', true );
        if ( is_string( $nutrition_raw ) && ! empty( $nutrition_raw ) ) {
            $nutrition = json_decode( wp_unslash( $nutrition_raw ), true );
            if ( ! is_array( $nutrition ) ) {
                $nutrition = array();
            }
        } elseif ( is_array( $nutrition_raw ) ) {
            // Legacy: was stored as serialized array — migrate transparently.
            $nutrition = $nutrition_raw;
        } else {
            $nutrition = array();
        }
        
        // Default values if empty
        $defaults = array(
            'calories' => '',
            'carbs' => '',
            'protein' => '',
            'fat' => '',
            'saturated_fat' => '',
            'sugar' => '',
            'fiber' => '',
            'sodium' => ''
        );
        
        $nutrition = wp_parse_args($nutrition, $defaults);
        
        // Start output
        ?>
        <table class="form-table">
            <tr>
                <th><label for="nutrition_calories"><?php _e('Calories', 'delice-recipe-manager'); ?></label></th>
                <td>
                    <input type="number" id="nutrition_calories" name="nutrition[calories]" value="<?php echo esc_attr($nutrition['calories']); ?>" class="small-text" />
                    <span class="description"><?php _e('kcal', 'delice-recipe-manager'); ?></span>
                </td>
            </tr>
            <tr>
                <th><label for="nutrition_carbs"><?php _e('Carbohydrates', 'delice-recipe-manager'); ?></label></th>
                <td>
                    <input type="number" id="nutrition_carbs" name="nutrition[carbs]" value="<?php echo esc_attr($nutrition['carbs']); ?>" class="small-text" step="0.1" />
                    <span class="description"><?php _e('g', 'delice-recipe-manager'); ?></span>
                </td>
            </tr>
            <tr>
                <th><label for="nutrition_protein"><?php _e('Protein', 'delice-recipe-manager'); ?></label></th>
                <td>
                    <input type="number" id="nutrition_protein" name="nutrition[protein]" value="<?php echo esc_attr($nutrition['protein']); ?>" class="small-text" step="0.1" />
                    <span class="description"><?php _e('g', 'delice-recipe-manager'); ?></span>
                </td>
            </tr>
            <tr>
                <th><label for="nutrition_fat"><?php _e('Fat', 'delice-recipe-manager'); ?></label></th>
                <td>
                    <input type="number" id="nutrition_fat" name="nutrition[fat]" value="<?php echo esc_attr($nutrition['fat']); ?>" class="small-text" step="0.1" />
                    <span class="description"><?php _e('g', 'delice-recipe-manager'); ?></span>
                </td>
            </tr>
            <tr>
                <th><label for="nutrition_saturated_fat"><?php _e('Saturated Fat', 'delice-recipe-manager'); ?></label></th>
                <td>
                    <input type="number" id="nutrition_saturated_fat" name="nutrition[saturated_fat]" value="<?php echo esc_attr($nutrition['saturated_fat']); ?>" class="small-text" step="0.1" />
                    <span class="description"><?php _e('g', 'delice-recipe-manager'); ?></span>
                </td>
            </tr>
            <tr>
                <th><label for="nutrition_sugar"><?php _e('Sugar', 'delice-recipe-manager'); ?></label></th>
                <td>
                    <input type="number" id="nutrition_sugar" name="nutrition[sugar]" value="<?php echo esc_attr($nutrition['sugar']); ?>" class="small-text" step="0.1" />
                    <span class="description"><?php _e('g', 'delice-recipe-manager'); ?></span>
                </td>
            </tr>
            <tr>
                <th><label for="nutrition_fiber"><?php _e('Fiber', 'delice-recipe-manager'); ?></label></th>
                <td>
                    <input type="number" id="nutrition_fiber" name="nutrition[fiber]" value="<?php echo esc_attr($nutrition['fiber']); ?>" class="small-text" step="0.1" />
                    <span class="description"><?php _e('g', 'delice-recipe-manager'); ?></span>
                </td>
            </tr>
            <tr>
                <th><label for="nutrition_sodium"><?php _e('Sodium', 'delice-recipe-manager'); ?></label></th>
                <td>
                    <input type="number" id="nutrition_sodium" name="nutrition[sodium]" value="<?php echo esc_attr($nutrition['sodium']); ?>" class="small-text" />
                    <span class="description"><?php _e('mg', 'delice-recipe-manager'); ?></span>
                </td>
            </tr>
        </table>
        <p class="description"><?php _e('Enter nutritional information per serving.', 'delice-recipe-manager'); ?></p>
        <p style="margin-top:12px;">
            <button type="button" id="delice-auto-calc-btn" class="button button-secondary">
                <?php esc_html_e( 'Auto-Calculate from Ingredients', 'delice-recipe-manager' ); ?>
            </button>
            <span id="delice-auto-calc-status" style="margin-left:10px;font-style:italic;"></span>
        </p>
        <?php
    }

    /**
     * Save nutritional information
     */
    public function save_nutrition_meta($post_id) {
        // Check if nonce is set
        if (!isset($_POST['delice_recipe_nutrition_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['delice_recipe_nutrition_nonce'], 'delice_recipe_nutrition_meta')) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check post type
        if ('delice_recipe' != get_post_type($post_id)) {
            return;
        }
        
        // Get nutrition data — validate per-field and store as JSON
        // (same format as AI-generated recipes so the schema reader is consistent).
        if (isset($_POST['nutrition']) && is_array($_POST['nutrition'])) {
            $allowed_keys = array( 'calories', 'carbs', 'protein', 'fat', 'saturated_fat', 'sugar', 'fiber', 'sodium' );
            $raw          = wp_unslash( $_POST['nutrition'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
            $nutrition    = array();

            foreach ( $allowed_keys as $key ) {
                if ( ! isset( $raw[ $key ] ) || $raw[ $key ] === '' ) {
                    continue;
                }
                // calories stored as integer; all others as float.
                $nutrition[ $key ] = ( $key === 'calories' ) ? absint( $raw[ $key ] ) : round( floatval( $raw[ $key ] ), 2 );
            }

            update_post_meta( $post_id, '_delice_recipe_nutrition', wp_json_encode( $nutrition ) );
        }
    }
    
    /**
     * Display nutritional information
     */
    public function display_nutrition($recipe_id) {
        $nutrition = get_post_meta($recipe_id, '_delice_recipe_nutrition', true);
        
        if (!is_array($nutrition) || empty($nutrition)) {
            return '';
        }
        
        $html = '<div class="delice-recipe-nutrition">';
        $html .= '<div class="delice-recipe-nutrition-title">';
        $html .= '<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none">
                    <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                    <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                    <line x1="6" y1="1" x2="6" y2="4"></line>
                    <line x1="10" y1="1" x2="10" y2="4"></line>
                    <line x1="14" y1="1" x2="14" y2="4"></line>
                </svg>';
        $html .= __('Nutritional Information', 'delice-recipe-manager');
        $html .= '</div>';
        
        $html .= '<div class="delice-recipe-nutrition-grid">';
        
        // Display calories
        if (!empty($nutrition['calories'])) {
            $html .= $this->nutrition_item(__('Calories', 'delice-recipe-manager'), $nutrition['calories'], __('kcal', 'delice-recipe-manager'));
        }
        
        // Display carbs
        if (!empty($nutrition['carbs'])) {
            $html .= $this->nutrition_item(__('Carbs', 'delice-recipe-manager'), $nutrition['carbs'], __('g', 'delice-recipe-manager'));
        }
        
        // Display protein
        if (!empty($nutrition['protein'])) {
            $html .= $this->nutrition_item(__('Protein', 'delice-recipe-manager'), $nutrition['protein'], __('g', 'delice-recipe-manager'));
        }
        
        // Display fat
        if (!empty($nutrition['fat'])) {
            $html .= $this->nutrition_item(__('Fat', 'delice-recipe-manager'), $nutrition['fat'], __('g', 'delice-recipe-manager'));
        }
        
        // Display saturated fat
        if (!empty($nutrition['saturated_fat'])) {
            $html .= $this->nutrition_item(__('Saturated Fat', 'delice-recipe-manager'), $nutrition['saturated_fat'], __('g', 'delice-recipe-manager'));
        }
        
        // Display sugar
        if (!empty($nutrition['sugar'])) {
            $html .= $this->nutrition_item(__('Sugar', 'delice-recipe-manager'), $nutrition['sugar'], __('g', 'delice-recipe-manager'));
        }
        
        // Display fiber
        if (!empty($nutrition['fiber'])) {
            $html .= $this->nutrition_item(__('Fiber', 'delice-recipe-manager'), $nutrition['fiber'], __('g', 'delice-recipe-manager'));
        }
        
        // Display sodium
        if (!empty($nutrition['sodium'])) {
            $html .= $this->nutrition_item(__('Sodium', 'delice-recipe-manager'), $nutrition['sodium'], __('mg', 'delice-recipe-manager'));
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    // ── v4.0.0 Auto Nutrition Calculation ────────────────────────────────────

    /**
     * AJAX handler: calculate nutrition via Edamam API.
     * Called by delice_ajax_auto_calculate_nutrition() in ajax-handlers.php.
     */
    public function ajax_auto_calculate_nutrition() {
        $servings = max( 1, absint( $_POST['servings'] ?? 1 ) );

        // Sanitize ingredients array
        $raw_ingredients = isset( $_POST['ingredients'] ) ? (array) $_POST['ingredients'] : array();
        $ingredients     = array_values( array_filter( array_map( 'sanitize_text_field', $raw_ingredients ) ) );

        if ( empty( $ingredients ) ) {
            wp_send_json_error( array( 'message' => __( 'No ingredients provided.', 'delice-recipe-manager' ) ) );
            return;
        }

        $result = $this->call_edamam_api( $ingredients );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
            return;
        }

        // Map Edamam totalNutrients keys → plugin field names, divide by servings
        $map = array(
            'calories'      => 'ENERC_KCAL',
            'carbs'         => 'CHOCDF',
            'protein'       => 'PROCNT',
            'fat'           => 'FAT',
            'saturated_fat' => 'FASAT',
            'sugar'         => 'SUGAR',
            'fiber'         => 'FIBTG',
            'sodium'        => 'NA',
        );

        $output = array();
        foreach ( $map as $field => $key ) {
            $qty = $result[ $key ]['quantity'] ?? 0;
            $output[ $field ] = round( $qty / $servings, $field === 'calories' ? 0 : 1 );
        }

        wp_send_json_success( $output );
    }

    /**
     * Call the Edamam Nutrition Analysis API.
     *
     * @param string[] $ingredient_strings  e.g. ["2 cups flour", "3 eggs"]
     * @return array|WP_Error  totalNutrients array on success, WP_Error on failure.
     */
    private function call_edamam_api( array $ingredient_strings ) {
        $app_id  = get_option( 'delice_recipe_edamam_app_id', '' );
        $api_key = get_option( 'delice_recipe_edamam_api_key', '' );

        if ( ! $app_id || ! $api_key ) {
            return new WP_Error(
                'no_keys',
                __( 'Edamam API credentials are not configured. Go to Settings → Nutrition API.', 'delice-recipe-manager' )
            );
        }

        $url = add_query_arg( array(
            'app_id'  => rawurlencode( $app_id ),
            'app_key' => rawurlencode( $api_key ),
        ), 'https://api.edamam.com/api/nutrition-details' );

        $response = wp_remote_post( $url, array(
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( array( 'ingr' => $ingredient_strings ) ),
            'timeout' => 20,
        ) );

        if ( is_wp_error( $response ) ) {
            return new WP_Error(
                'request_failed',
                sprintf(
                    __( 'API request failed: %s', 'delice-recipe-manager' ),
                    $response->get_error_message()
                )
            );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            return new WP_Error(
                'api_error',
                sprintf(
                    /* translators: %d: HTTP status code */
                    __( 'Edamam API returned HTTP %d. Check your credentials.', 'delice-recipe-manager' ),
                    $code
                )
            );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! isset( $body['totalNutrients'] ) ) {
            return new WP_Error(
                'parse_error',
                __( 'Unexpected API response. Could not read nutrition data.', 'delice-recipe-manager' )
            );
        }

        return $body['totalNutrients'];
    }

    // ── End v4.0.0 Auto Nutrition Calculation ─────────────────────────────────

    /**
     * Create nutrition item
     */
    private function nutrition_item($label, $value, $unit) {
        $html = '<div class="delice-recipe-nutrition-item">';
        $html .= '<span class="delice-recipe-nutrition-item-label">' . esc_html($label) . '</span>';
        $html .= '<span class="delice-recipe-nutrition-item-value">' . esc_html($value) . ' ' . esc_html($unit) . '</span>';
        $html .= '</div>';
        
        return $html;
    }
}
}
