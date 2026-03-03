<?php
/**
 * AI recipe generator functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Delice_Recipe_AI')) {
class Delice_Recipe_AI {

    /** @var string */
    private $api_key;

    /** @var int */
    private $max_retries = 3;

    /** @var int */
    private $cache_ttl = 43200; // 12 hours

    public function __construct() {
        // Load crypto utility
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/class-delice-recipe-crypto.php';
        
        // Decrypt API key if encrypted
        $stored_key = get_option('delice_recipe_ai_api_key','');
        $this->api_key = Delice_Recipe_Crypto::decrypt($stored_key);
        
        // Background image generation hook (cron callback)
        add_action('delice_recipe_generate_image_bg', [$this, 'generate_and_attach_image_bg'], 10, 2);
    }

    /**
     * Main entry: generate recipe data.
     * @param array $prompt ['keyword'=>string,'target_language'=>string,'variations'=>array]
     * @return array|WP_Error
     */
    public function generate_recipe($prompt) {
        if (empty($this->api_key)) {
            return new WP_Error('missing_api_key', __('API key not configured','delice-recipe-manager'));
        }

        $cache_key = 'delice_recipe_ai_'.md5(wp_json_encode($prompt));
        if(false !== ($cached = get_transient($cache_key))) {
            error_log('Delice Recipe AI: Using cached response.');
            return $cached;
        }

        try {
            $payload = $this->build_prompt($prompt);
            $raw     = $this->make_openai_request_with_retry($payload);

            if(is_wp_error($raw)) {
                return $raw;
            }

            $data = $this->parse_ai_response($raw);
            if(is_wp_error($data)) {
                return $data;
            }

            $valid = $this->validate_recipe_data($data);
            if(is_wp_error($valid)) {
                return $valid;
            }

            set_transient($cache_key, $data, $this->cache_ttl);
            return $data;

        } catch (Exception $e) {
            error_log('Delice Recipe AI Exception: ' . $e->getMessage());
            return new WP_Error('ai_exception', $e->getMessage());
        }
    }

    /**
     * Build the system/user prompt, explicitly forbidding markdown.
     */
    private function build_prompt($params) {
        $keyword  = sanitize_text_field($params['keyword'] ?? '');
        $lang     = sanitize_text_field($params['target_language'] ?? 'english');
        // Strip anything that isn't letters or spaces to prevent prompt injection.
        $lang     = trim( preg_replace( '/[^a-zA-Z\s]/', '', $lang ) ) ?: 'english';
        $vars     = $params['variations'] ?? [];

        $system = "You are a world-class French culinary expert who outputs *raw JSON only*, without any markdown fences or code blocks. Write it as if you were the author of the best recipe website in the world. If you present any uncommon ingredients, provide similar/alternatives in brackets. Imagine you were jamie oliver teaching his neighbour to cook, that type of tone, patience and detail. The recipe should be halal without mentioning the word halal anywhere in recipe and strictly follow dietery rules.";

        $user  = "Given ingredients and keywords: {$keyword}\n";
        $user .= "Generate a JSON object with fields:\n";
        
        $user .= "- title: You must add the word \"Recipe\" at the end. e.g. \"Banana shake Recipe\". If the target language is other than English, then the translated word for Recipe should be used, e.g. \"Recette\" for French. The title should be seo optimized and not random. If the input keyword is short than optmize is accordingly\n";
        
        $user .= "- description: Focus on making the description keyword rich using phrases and keywords like {How to make}, {how to cook}, {quick and easy} {recipe}. Be creative in your approach but focus on keywords. Max 280 characters\n";
        
        $user .= "- ingredients: Break the ingredients down as if you were teaching a regular home cook. The idea is that the reader will be cooking this recipe from scratch. [{name, amount, unit}]\n";
        
        $user .= "- instructions: Describe the cooking process with sequential steps to define each step, detailing each step in sequence. The idea is that the reader will be cooking this recipe from scratch. You are teaching a regular home cook. Imagine you were jamie oliver teaching his neighbour to cook, that type of tone, patience and detail. If a particular step requires some time, let it be known for example \"stir the sauce for 2 more minutes or until its silky smooth\". Make the recipe highly detailed and thorough. When you write the Step by Step Method, be detailed, as if the person cooking is relying on your instructions to get everything perfect. [{step:int, text:string}]\n";
        
        $user .= "- prep_time, cook_time, total_time (minutes)\n";
        $user .= "- servings, calories, difficulty (easy|medium|hard)\n";
        $user .= "- notes (chef's tips)\n";
        $user .= "- equipment: Array of kitchen tools and appliances needed. Only include items beyond basic knives/cutting boards [{name:string, notes:string, required:bool}]\n";
        $user .= "- nutrition: {protein:g, fat:g, carbs:g}\n";
        $user .= "- slug: Extremely Important: Present the slug as the dish name with 'recipe' at the end, without any full stops or periods. This will be used as a URL slug e.g. katsu-curry-recipe\n";
        
        // Add taxonomy fields
        $user .= "- cuisine: ONE of (italian, french, asian, mexican, american, mediterranean, indian, chinese, japanese, thai, middle-eastern, greek, spanish, german, british)\n";
        $user .= "- course: ONE of (appetizer, main-course, dessert, side-dish, soup, salad, breakfast, snack, beverage)\n";
        $user .= "- dietary: Array of applicable dietary types (vegetarian, vegan, gluten-free, dairy-free, low-carb, keto, paleo, healthy)\n";
        $user .= "- keywords: Array of 5-8 main ingredient keywords for tagging\n";
        
        // Add FAQs to the prompt
        $user .= "- faqs: Array of 5 FAQs with this structure: [{question, answer}]\n";
        $user .= "  - First FAQ question must be: What ingredients are used in [Recipe Title]?\n";
        $user .= "  - Second FAQ question must be: How to cook [Recipe Title] at home?\n";
        $user .= "  - Include 3 more relevant questions about cooking method, variations, or tips\n";
        $user .= "  - Keep answers concise (max 280 characters each)\n";
        $user .= "  - Second FAQ answer should start with 'Learn how to cook [Recipe Title]...'\n";
        
        $user .= "Do NOT wrap the JSON in ``` or any markdown. Return only the JSON object.";

        if(!empty($vars)) {
            foreach($vars as $v) {
                $user .= "\n" . sanitize_text_field($v);
            }
        }
        if('english'!==strtolower($lang)) {
            $user .= "\nReturn the JSON in {$lang}.";
        }

        return ['system'=>$system,'user'=>$user];
    }

    /**
     * Retry wrapper — preserves the last actual error message.
     */
    private function make_openai_request_with_retry($prompt) {
        $attempt    = 0;
        $backoff    = 500;
        $last_error = null;
        while ( $attempt++ < $this->max_retries ) {
            $res = $this->make_openai_request($prompt);
            if ( ! is_wp_error($res) ) {
                return $res;
            }
            $last_error = $res;
            $code = $res->get_error_code();
            if ( ! in_array( $code, ['rate_limit','network_error'], true ) ) {
                return $res;
            }
            // Only sleep between retries, not after the final attempt.
            if ( $attempt < $this->max_retries ) {
                usleep( $backoff * 1000 );
                $backoff *= 2;
            }
        }
        // Return the last real error so the caller gets the actual reason.
        return $last_error ?: new WP_Error( 'max_retries_exceeded', __( 'Failed after retries', 'delice-recipe-manager' ) );
    }

    /**
     * Return true if the given model supports response_format: json_object.
     * Only add the parameter for known-compatible models to avoid API 400 errors.
     */
    private function supports_json_mode( $model ) {
        $prefixes = [ 'gpt-4o', 'gpt-4-turbo', 'gpt-4-1106', 'gpt-3.5-turbo-1106', 'gpt-3.5-turbo-0125' ];
        foreach ( $prefixes as $prefix ) {
            if ( strpos( $model, $prefix ) !== false ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Single OpenAI call.
     */
    private function make_openai_request($prompt) {
        $url   = 'https://api.openai.com/v1/chat/completions';
        $model = get_option( 'delice_recipe_ai_model', 'gpt-4o' );
        $body  = [
            'model'       => $model,
            'messages'    => [
                [ 'role' => 'system', 'content' => $prompt['system'] ],
                [ 'role' => 'user',   'content' => $prompt['user']   ],
            ],
            'temperature' => 0.7,
            'max_tokens'  => 3500,
        ];
        // Force JSON output on models that support it — eliminates markdown-fence errors.
        if ( $this->supports_json_mode( $model ) ) {
            $body['response_format'] = [ 'type' => 'json_object' ];
        }
        $args = [
            'headers'=>[
                'Authorization'=>'Bearer '.$this->api_key,
                'Content-Type'=>'application/json',
            ],
            'body'=>wp_json_encode($body),
            'timeout'=>60,
            'method'=>'POST',
        ];

        $resp = wp_remote_request($url,$args);
        if(is_wp_error($resp)) {
            error_log('OpenAI Request Error: ' . $resp->get_error_message());
            return new WP_Error('network_error',$resp->get_error_message());
        }
        $code = wp_remote_retrieve_response_code($resp);
        $txt  = wp_remote_retrieve_body($resp);
        $data = json_decode($txt,true);

        if(429===$code) {
            $msg = $data['error']['message'] ?? __('Rate limit','delice-recipe-manager');
            return new WP_Error('rate_limit',$msg);
        }
        if(200!==$code) {
            $msg = $data['error']['message'] ?? __('API error','delice-recipe-manager');
            error_log('OpenAI API Error: ' . $msg . ' (Code: ' . $code . ')');
            return new WP_Error('api_error',$msg);
        }
        if(empty($data['choices'][0]['message']['content'])) {
            return new WP_Error('api_error',__('Empty response','delice-recipe-manager'));
        }
        // Detect token-limit truncation — truncated JSON always causes a Syntax error.
        $finish_reason = $data['choices'][0]['finish_reason'] ?? '';
        if ( 'length' === $finish_reason ) {
            error_log('Delice Recipe AI: Response truncated (finish_reason: length). Increase max_tokens or simplify the prompt.');
            return new WP_Error('truncated_response', __('Recipe response was cut off by the token limit. Please try again or use a simpler keyword.', 'delice-recipe-manager'));
        }
        return $data['choices'][0]['message']['content'];
    }

    /**
     * Strip code fences and extract JSON from the AI response.
     *
     * Uses a multi-pass strategy:
     *  1. Strip opening/closing markdown fences.
     *  2. Try parsing the whole string as JSON.
     *  3. If that fails, use a regex to extract the first top-level {...} block.
     *  4. Return a WP_Error only when all attempts fail.
     */
    private function parse_ai_response($raw) {
        $clean = trim($raw);

        // Pass 1: strip ``` json … ``` or ``` … ``` wrappers.
        // Use /s so . matches newlines; /m allows ^ and $ to match line boundaries.
        $clean = preg_replace('/^```(?:json)?\s*/im', '', $clean);
        $clean = preg_replace('/\s*```\s*$/im', '', $clean);
        $clean = trim($clean);

        // Pass 2: attempt a direct parse.
        $parsed = json_decode($clean, true);
        if (JSON_ERROR_NONE === json_last_error()) {
            return $parsed;
        }

        // Pass 3: AI may have prepended/appended prose — find the first { … } block.
        if (preg_match('/(\{[\s\S]*\})/s', $clean, $matches)) {
            $parsed = json_decode($matches[1], true);
            if (JSON_ERROR_NONE === json_last_error()) {
                return $parsed;
            }
        }

        // All passes failed.
        $err = json_last_error_msg();
        error_log("Delice Recipe AI Error: JSON parse error: {$err}. Raw start: " . substr($clean, 0, 500));
        return new WP_Error('json_error', sprintf(__('JSON decode error: %s', 'delice-recipe-manager'), $err));
    }

    /**
     * Ensure required fields.
     */
    private function validate_recipe_data($d) {
        foreach(['title','description','ingredients','instructions'] as $f) {
            if(empty($d[$f])) {
                return new WP_Error('validation_error',sprintf(__('Missing field: %s','delice-recipe-manager'),$f));
            }
        }
        if(!is_array($d['ingredients'])||!is_array($d['instructions'])) {
            return new WP_Error('validation_error',__('Ingredients/instructions format invalid','delice-recipe-manager'));
        }
        
        // Validate FAQs if present
        if(isset($d['faqs']) && !is_array($d['faqs'])) {
            return new WP_Error('validation_error',__('FAQs format invalid','delice-recipe-manager'));
        }
        
        return true;
    }

    /**
     * Create WP post & save all meta.
     */
    public function create_recipe_post($recipe_data, $auto_publish = false, $target_language = 'english') {
        $post_id = wp_insert_post([
            'post_title'=>sanitize_text_field($recipe_data['title']),
            'post_content'=>wp_kses_post($recipe_data['description']),
            'post_status'=>$auto_publish?'publish':'draft',
            'post_type'=>'delice_recipe',
        ]);
        if(is_wp_error($post_id)) return $post_id;

        // Save core meta
        update_post_meta($post_id,'_delice_recipe_ingredients',$recipe_data['ingredients']);
        update_post_meta($post_id,'_delice_recipe_instructions',$recipe_data['instructions']);
        update_post_meta($post_id,'_delice_recipe_prep_time',absint($recipe_data['prep_time']??0));
        update_post_meta($post_id,'_delice_recipe_cook_time',absint($recipe_data['cook_time']??0));
        update_post_meta($post_id,'_delice_recipe_total_time',absint($recipe_data['total_time']??0));
        update_post_meta($post_id,'_delice_recipe_servings',absint($recipe_data['servings']??1));
        update_post_meta($post_id,'_delice_recipe_calories',absint($recipe_data['calories']??0));
        update_post_meta($post_id,'_delice_recipe_difficulty',sanitize_text_field($recipe_data['difficulty']??'medium'));
        update_post_meta($post_id,'_delice_recipe_notes',sanitize_textarea_field($recipe_data['notes']??''));
        
        // Store recipe language metadata - CRITICAL FIX
        $locale = Delice_Recipe_Language::map_ai_language_to_locale($target_language);
        update_post_meta($post_id, '_delice_recipe_language', $locale);
        
        // Save FAQs if available
        if(!empty($recipe_data['faqs']) && is_array($recipe_data['faqs'])) {
            $sanitized_faqs = array();
            foreach($recipe_data['faqs'] as $faq) {
                if(!empty($faq['question']) && !empty($faq['answer'])) {
                    $sanitized_faqs[] = array(
                        'question' => sanitize_text_field($faq['question']),
                        'answer' => sanitize_textarea_field($faq['answer'])
                    );
                }
            }
            update_post_meta($post_id,'_delice_recipe_faqs', $sanitized_faqs);
        }
        
        // Save equipment
        if (!empty($recipe_data['equipment']) && is_array($recipe_data['equipment'])) {
            if (class_exists('Delice_Recipe_Equipment')) {
                $sanitized_equipment = Delice_Recipe_Equipment::sanitize($recipe_data['equipment']);
                if (!empty($sanitized_equipment)) {
                    update_post_meta($post_id, Delice_Recipe_Equipment::META_KEY, $sanitized_equipment);
                }
            }
        }

        if(!empty($recipe_data['nutrition'])&&is_array($recipe_data['nutrition'])) {
            update_post_meta($post_id,'_delice_recipe_nutrition',wp_json_encode($recipe_data['nutrition']));
        }
        
        // Assign taxonomies from AI data
        if(!empty($recipe_data['cuisine'])) {
            wp_set_object_terms($post_id, sanitize_text_field($recipe_data['cuisine']), 'delice_cuisine');
        }
        if(!empty($recipe_data['course'])) {
            wp_set_object_terms($post_id, sanitize_text_field($recipe_data['course']), 'delice_course');
        }
        if(!empty($recipe_data['dietary']) && is_array($recipe_data['dietary'])) {
            wp_set_object_terms($post_id, array_map('sanitize_text_field', $recipe_data['dietary']), 'delice_dietary');
        }
        if(!empty($recipe_data['keywords']) && is_array($recipe_data['keywords'])) {
            wp_set_object_terms($post_id, array_map('sanitize_text_field', $recipe_data['keywords']), 'delice_keyword');
        }
        
        // Schedule background image generation — avoids 30-60s blocking DALL-E call during AJAX.
        if (get_option('delice_recipe_enable_ai_images', false)) {
            wp_schedule_single_event(time(), 'delice_recipe_generate_image_bg', [$post_id, sanitize_text_field($recipe_data['title'])]);
            spawn_cron();
            error_log("Delice Recipe: AI image generation scheduled as background task for post {$post_id}.");
        }

        // Auto-migrate to standard post if the admin toggle is on.
        // This converts the delice_recipe post to a standard Post while
        // preserving all meta, taxonomies, and the featured image.
        if ( get_option( 'delice_recipe_auto_migrate_to_post', false ) ) {
            if ( class_exists( 'Delice_Recipe_Migration' ) ) {
                $migration   = new Delice_Recipe_Migration();
                $recipe_post = get_post( $post_id );
                if ( $recipe_post ) {
                    $migrated_id = $migration->migrate_single_recipe( $recipe_post );
                    if ( $migrated_id && ! is_wp_error( $migrated_id ) ) {
                        // Return the new standard post ID so callers get the
                        // correct edit/view URLs.
                        $post_id = $migrated_id;
                    }
                }
            }
        }

        return $post_id;
    }
    
    /**
     * WP cron callback: generate and attach featured image in background.
     *
     * @param int    $post_id
     * @param string $title
     */
    public function generate_and_attach_image_bg($post_id, $title) {
        error_log("Delice Recipe: Background image generation starting for post {$post_id}.");
        $image_id = $this->generate_recipe_image($title, $post_id);
        if ($image_id && !is_wp_error($image_id)) {
            set_post_thumbnail($post_id, $image_id);
            error_log("Delice Recipe: Background image set for post {$post_id} (attachment {$image_id}).");
        } elseif (is_wp_error($image_id)) {
            error_log('Delice Recipe: Background image generation failed: ' . $image_id->get_error_message());
        }
    }

    /**
     * Generate featured image using DALL-E 3
     */
    private function generate_recipe_image($title, $post_id) {
        if (empty($this->api_key)) {
            return new WP_Error('missing_api_key', __('API key not configured','delice-recipe-manager'));
        }
        
        try {
            // Create image prompt
            $style = get_option('delice_recipe_image_style', 'vivid');
            $size_option = get_option('delice_recipe_image_size', '1024x1024');
            
            // Map optimized sizes to DALL-E native sizes
            // DALL-E only supports: 1024x1024, 1792x1024, 1024x1792
            $size_mapping = array(
                '800x600'  => '1024x1024',
                '600x600'  => '1024x1024',
                '700x700'  => '1024x1024',
                '600x800'  => '1024x1024',
                '900x600'  => '1024x1024',
                // Native sizes (no mapping needed)
                '1024x1024' => '1024x1024',
                '1792x1024' => '1792x1024',
                '1024x1792' => '1024x1792',
            );
            
            // Get DALL-E size (always a native size)
            $dalle_size = isset($size_mapping[$size_option]) ? $size_mapping[$size_option] : '1024x1024';
            
            $prompt = $this->build_image_prompt($title);
            
            error_log("Delice Recipe: Generating image with size: {$dalle_size} (requested: {$size_option})");
            error_log("Delice Recipe: Using prompt: {$prompt}");
            
            // Call DALL-E API with native size
            $response = wp_remote_post('https://api.openai.com/v1/images/generations', [
                'timeout' => 60,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode([
                    'model' => 'dall-e-3',
                    'prompt' => $prompt,
                    'n' => 1,
                    'size' => $dalle_size,  // Use mapped DALL-E native size
                    'quality' => 'hd',
                    'style' => $style,
                ]),
            ]);
            
            if (is_wp_error($response)) {
                error_log('Delice Recipe: API request failed: ' . $response->get_error_message());
                return $response;
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (empty($data['data'][0]['url'])) {
                error_log('Delice Recipe: Invalid image response: ' . $body);
                return new WP_Error('invalid_response', __('Invalid image API response','delice-recipe-manager'));
            }
            
            $image_url = $data['data'][0]['url'];
            error_log("Delice Recipe: Image URL received: {$image_url}");
            
            // Download and upload to WordPress (resize happens there if needed)
            return $this->upload_image_to_wordpress($image_url, $title, $post_id);
            
        } catch (Exception $e) {
            error_log('Delice Recipe: Image generation exception: ' . $e->getMessage());
            return new WP_Error('image_exception', $e->getMessage());
        }
    }
    
    /**
     * Build DALL-E image prompt with dynamic professional food photography
     */
    private function build_image_prompt($title) {
        // Clean title - remove "Recipe", "Recette", etc.
        $clean_title = preg_replace('/\s+(Recipe|Recette|Receta|Rezept|Ricetta)$/i', '', $title);
        
        // Define shot types for variety
        $shot_types = [
            'overhead shot from directly above',
            '45-degree angle capturing depth and layers',
            'straight-on hero shot at eye level',
            'close-up detail shot highlighting textures',
            'three-quarter view showing dimension'
        ];
        
        // Define lighting styles
        $lighting_styles = [
            'soft diffused natural window light from the side',
            'dramatic backlighting creating rim lighting and depth',
            'warm golden hour lighting with soft shadows',
            'bright even studio lighting with minimal shadows',
            'moody side lighting with strong contrast'
        ];
        
        // Define background elements
        $backgrounds = [
            'rustic wooden table with scattered fresh ingredients',
            'dark slate surface with complementary garnishes',
            'light marble countertop with cooking utensils',
            'textured linen tablecloth with natural props',
            'weathered cutting board with herbs and spices'
        ];
        
        // Define plating styles
        $plating_styles = [
            'elegantly plated in a wide shallow bowl',
            'artfully arranged on a rustic ceramic plate',
            'casually presented in an authentic serving dish',
            'modern minimalist presentation on white plate',
            'family-style presentation in a traditional serving dish'
        ];
        
        // Define dynamic elements (optional)
        $dynamic_elements = [
            'with visible steam rising',
            'being drizzled with sauce',
            'with a fork lifting a portion',
            'freshly garnished with herbs being sprinkled',
            ''  // Sometimes no dynamic element
        ];
        
        // Randomly select elements for variety
        $shot = $shot_types[array_rand($shot_types)];
        $lighting = $lighting_styles[array_rand($lighting_styles)];
        $background = $backgrounds[array_rand($backgrounds)];
        $plating = $plating_styles[array_rand($plating_styles)];
        $dynamic = $dynamic_elements[array_rand($dynamic_elements)];
        
        // Build comprehensive prompt
        $prompt = "Professional food photography of {$clean_title}, {$shot}. ";
        $prompt .= "The dish is {$plating}, showcasing texture contrasts between crispy and tender elements, ";
        $prompt .= "glossy sauces and matte components, with vibrant natural colors. ";
        $prompt .= "Shot with {$lighting}, creating depth of field that keeps the dish sharp while gently blurring the background. ";
        $prompt .= "Styled on {$background}, with complementary garnishes and ingredients that enhance the visual story. ";
        
        if (!empty($dynamic)) {
            $prompt .= $dynamic . '. ';
        }
        
        $prompt .= "The composition captures the authentic preparation and presentation of this specific dish, ";
        $prompt .= "highlighting its unique characteristics, proper cooking method, and distinctive elements. ";
        $prompt .= "Editorial quality, restaurant-worthy presentation, no text, no logos, no branding, ";
        $prompt .= "photorealistic with rich details and appetizing appeal. ";
        $prompt .= "The image should look like it belongs in a premium food magazine or professional cookbook.";
        
        return $prompt;
    }
    
    /**
     * Download image and upload to WordPress media library with SEO optimization
     */
    private function upload_image_to_wordpress($image_url, $title, $post_id) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Download image
        $temp_file = download_url($image_url);
        
        if (is_wp_error($temp_file)) {
            return $temp_file;
        }
        
        // Create SEO-optimized filename
        // Remove "Recipe", "Recette", etc. and clean for filename
        $clean_title = preg_replace('/\s+(Recipe|Recette|Receta|Rezept|Ricetta)$/i', '', $title);
        $filename = sanitize_file_name($clean_title);
        $filename = strtolower($filename);
        $filename = preg_replace('/[^a-z0-9-]/', '-', $filename);
        $filename = preg_replace('/-+/', '-', $filename);
        $filename = trim($filename, '-');
        
        // Prepare file array
        $file_array = [
            'name' => $filename . '-recipe.png',  // SEO-friendly filename
            'tmp_name' => $temp_file,
        ];
        
        // Create SEO-optimized description
        $image_description = sprintf(
            'Professional food photography of %s. High-quality recipe image showing the finished dish with proper plating and presentation.',
            $clean_title
        );
        
        // Upload to WordPress
        $attachment_id = media_handle_sideload($file_array, $post_id, $title);
        
        // Clean up temp file
        if (file_exists($temp_file)) {
            @unlink($temp_file);
        }
        
        if (is_wp_error($attachment_id)) {
            error_log('Delice Recipe: WordPress upload failed: ' . $attachment_id->get_error_message());
            return $attachment_id;
        }
        
        // Add SEO metadata to the image
        // 1. Alt text (critical for SEO and accessibility)
        $alt_text = sprintf('%s - Recipe Photo', $clean_title);
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
        
        // 2. Image title
        wp_update_post([
            'ID' => $attachment_id,
            'post_title' => $clean_title . ' Recipe',
        ]);
        
        // 3. Image description (helps with image SEO)
        wp_update_post([
            'ID' => $attachment_id,
            'post_content' => $image_description,
        ]);
        
        // 4. Image caption (optional but good for SEO)
        wp_update_post([
            'ID' => $attachment_id,
            'post_excerpt' => sprintf('Delicious %s recipe with step-by-step instructions', $clean_title),
        ]);
        
        // 5. AUTO-RESIZE FOR OPTIMIZED SIZES
        $image_size_option = get_option('delice_recipe_image_size', '1024x1024');
        
        // Define which sizes need resizing
        $optimized_sizes = array(
            '800x600'  => array('width' => 800, 'height' => 600),
            '600x600'  => array('width' => 600, 'height' => 600),
            '700x700'  => array('width' => 700, 'height' => 700),
            '600x800'  => array('width' => 600, 'height' => 800),
            '900x600'  => array('width' => 900, 'height' => 600),
        );
        
        // Only resize if it's an optimized size
        if (isset($optimized_sizes[$image_size_option])) {
            $target_width = $optimized_sizes[$image_size_option]['width'];
            $target_height = $optimized_sizes[$image_size_option]['height'];
            
            error_log("Delice Recipe: Resizing to optimized size: {$target_width}×{$target_height}");
            
            $image_path = get_attached_file($attachment_id);
            
            if ($image_path && file_exists($image_path)) {
                $image = wp_get_image_editor($image_path);
                
                if (!is_wp_error($image)) {
                    // Resize
                    $resize_result = $image->resize($target_width, $target_height, true);
                    
                    if (!is_wp_error($resize_result)) {
                        // Save
                        $save_result = $image->save($image_path);
                        
                        if (!is_wp_error($save_result)) {
                            // Update metadata
                            require_once(ABSPATH . 'wp-admin/includes/image.php');
                            $metadata = wp_generate_attachment_metadata($attachment_id, $image_path);
                            wp_update_attachment_metadata($attachment_id, $metadata);
                            
                            error_log("Delice Recipe: Resize successful: {$target_width}×{$target_height}");
                        } else {
                            error_log('Delice Recipe: Save failed: ' . $save_result->get_error_message());
                        }
                    } else {
                        error_log('Delice Recipe: Resize failed: ' . $resize_result->get_error_message());
                    }
                } else {
                    error_log('Delice Recipe: Image editor failed: ' . $image->get_error_message());
                }
            }
        } else {
            error_log("Delice Recipe: Using native DALL-E size: {$image_size_option}");
        }
        
        error_log("Delice Recipe: Image uploaded successfully with SEO optimization (ID: {$attachment_id})");
        
        return $attachment_id;
    }

    /**
     * Clear AI-generated recipe cache.
     *
     * When $keywords is supplied the transient for all prompt combinations that
     * include that keyword string is deleted.  Because the full prompt array is
     * hashed we can only delete the exact match; for a broader purge pass null
     * to delete all plugin transients via a pattern scan.
     *
     * @param string|null $keywords Keyword string used when generating the recipe, or null for full purge.
     * @return bool
     */
    public function clear_cache( $keywords = null ) {
        global $wpdb;

        if ( $keywords ) {
            // Build the same base prompt array that generate_recipe() would use so
            // the MD5 key matches what was stored.
            $prompt_sample = array( 'keyword' => $keywords );
            $cache_key     = 'delice_recipe_ai_' . md5( wp_json_encode( $prompt_sample ) );
            return delete_transient( $cache_key );
        }

        // Full purge: remove all transients whose option name starts with our prefix.
        $prefix   = '_transient_delice_recipe_ai_';
        $like     = $wpdb->esc_like( $prefix ) . '%';
        $deleted  = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $like
            )
        );

        // Also remove the timeout entries.
        $timeout_prefix = '_transient_timeout_delice_recipe_ai_';
        $timeout_like   = $wpdb->esc_like( $timeout_prefix ) . '%';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $timeout_like
            )
        );

        return $deleted !== false;
    }
}
}
