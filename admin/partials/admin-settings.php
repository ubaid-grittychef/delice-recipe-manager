<div class="wrap">
    <h1><?php _e('Delice Recipe Manager Settings', 'delice-recipe-manager'); ?></h1>
    
    <form method="post" action="options.php">
        <?php 
        settings_fields('delice_recipe_settings'); 
        do_settings_sections('delice_recipe_settings'); 
        ?>
        
        <div class="delice-recipe-settings-container">
            <div class="delice-recipe-settings-section">
                <h2><?php _e('Template Selection', 'delice-recipe-manager'); ?></h2>
                <p><?php _e('Choose which template to use for displaying recipes on your site.', 'delice-recipe-manager'); ?></p>
                
                <?php
                $selected_template = get_option('delice_recipe_selected_template', 'default');
                $available_templates = array(
                    'default' => __('Default', 'delice-recipe-manager'),
                    'modern' => __('Modern', 'delice-recipe-manager'),
                    'elegant' => __('Elegant', 'delice-recipe-manager'),
                );
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Recipe Template', 'delice-recipe-manager'); ?></th>
                        <td>
                            <select name="delice_recipe_selected_template" id="delice_recipe_selected_template">
                                <?php foreach ($available_templates as $template_key => $template_name) : ?>
                                <option value="<?php echo esc_attr($template_key); ?>" <?php selected($selected_template, $template_key); ?>>
                                    <?php echo esc_html($template_name); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php _e('Select the template style you want to use for displaying recipes.', 'delice-recipe-manager'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="delice-recipe-settings-section">
                <h2><?php _e('Display Settings', 'delice-recipe-manager'); ?></h2>
                <?php
                // wp_parse_args merges saved option WITH defaults so new keys (v3.8.0+)
                // that weren't present in an older saved option still default to enabled.
                $display_option_defaults = array(
                    'show_image'              => true,
                    'show_servings'           => true,
                    'show_prep_time'          => true,
                    'show_cook_time'          => true,
                    'show_total_time'         => true,
                    'show_calories'           => true,
                    'show_difficulty'         => true,
                    'show_rating'             => true,
                    'show_nutrition'          => true,
                    'show_ingredients'        => true,
                    'show_instructions'       => true,
                    'show_notes'              => true,
                    'show_faqs'               => true,
                    'show_print'              => true,
                    'show_share'              => true,
                    // v3.6.0 / v3.8.0 feature toggles — all on by default
                    'show_jump_btn'           => true,
                    'show_cook_mode'          => true,
                    'show_dietary_badges'     => true,
                    'show_breadcrumb'         => true,
                    'show_related_recipes'    => true,
                    'show_nutrition_disclaimer' => true,
                    'show_last_updated'       => true,
                    'show_og_meta'            => true,
                );
                $display_options = wp_parse_args(
                    get_option( 'delice_recipe_display_options', array() ),
                    $display_option_defaults
                );
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Optional elements to display', 'delice-recipe-manager'); ?><br><small style="font-weight:normal;color:#666;"><?php _e('(Core elements like ingredients, instructions, times, and servings are always shown)', 'delice-recipe-manager'); ?></small></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="delice_recipe_display_options[show_image]" value="1" <?php checked(!empty($display_options['show_image']), true); ?>>
                                    <?php _e('Featured image', 'delice-recipe-manager'); ?>
                                </label><br>
                                
                                <label>
                                    <input type="checkbox" name="delice_recipe_display_options[show_rating]" value="1" <?php checked(!empty($display_options['show_rating']), true); ?>>
                                    <?php _e('Rating system', 'delice-recipe-manager'); ?>
                                </label><br>
                                
                                <label>
                                    <input type="checkbox" name="delice_recipe_display_options[show_notes]" value="1" <?php checked(!empty($display_options['show_notes']), true); ?>>
                                    <?php _e('Chef notes section', 'delice-recipe-manager'); ?>
                                </label><br>
                                
                                <label>
                                    <input type="checkbox" name="delice_recipe_display_options[show_faqs]" value="1" <?php checked(!empty($display_options['show_faqs']), true); ?>>
                                    <?php _e('FAQ section', 'delice-recipe-manager'); ?>
                                </label><br>
                                
                                <label>
                                    <input type="checkbox" name="delice_recipe_display_options[show_print]" value="1" <?php checked(!empty($display_options['show_print']), true); ?>>
                                    <?php _e('Print button', 'delice-recipe-manager'); ?>
                                </label><br>
                                
                                <label>
                                    <input type="checkbox" name="delice_recipe_display_options[show_share]" value="1" <?php checked(!empty($display_options['show_share']), true); ?>>
                                    <?php _e('Social share buttons', 'delice-recipe-manager'); ?>
                                </label>
                            </fieldset>
                            <p class="description">
                                <?php _e('Choose which elements to display in your recipe templates. Unchecked items will be hidden.', 'delice-recipe-manager'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Feature Controls', 'delice-recipe-manager'); ?><br><small style="font-weight:normal;color:#666;"><?php _e('(v3.6.0+ features)', 'delice-recipe-manager'); ?></small></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="delice_recipe_display_options[show_jump_btn]" value="1" <?php checked(!empty($display_options['show_jump_btn']), true); ?>>
                                    <?php _e('Jump to Recipe button', 'delice-recipe-manager'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="delice_recipe_display_options[show_cook_mode]" value="1" <?php checked(!empty($display_options['show_cook_mode']), true); ?>>
                                    <?php _e('Cook Mode button (keeps screen awake while cooking)', 'delice-recipe-manager'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="delice_recipe_display_options[show_dietary_badges]" value="1" <?php checked(!empty($display_options['show_dietary_badges']), true); ?>>
                                    <?php _e('Dietary badges (Vegan, Gluten-Free, etc.)', 'delice-recipe-manager'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="delice_recipe_display_options[show_breadcrumb]" value="1" <?php checked(!empty($display_options['show_breadcrumb']), true); ?>>
                                    <?php _e('Breadcrumb navigation (skipped when Yoast/RankMath active)', 'delice-recipe-manager'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="delice_recipe_display_options[show_related_recipes]" value="1" <?php checked(!empty($display_options['show_related_recipes']), true); ?>>
                                    <?php _e('Related recipes section', 'delice-recipe-manager'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="delice_recipe_display_options[show_nutrition_disclaimer]" value="1" <?php checked(!empty($display_options['show_nutrition_disclaimer']), true); ?>>
                                    <?php _e('Nutrition disclaimer text', 'delice-recipe-manager'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="delice_recipe_display_options[show_last_updated]" value="1" <?php checked(!empty($display_options['show_last_updated']), true); ?>>
                                    <?php _e('Last Updated date badge', 'delice-recipe-manager'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="delice_recipe_display_options[show_og_meta]" value="1" <?php checked(!empty($display_options['show_og_meta']), true); ?>>
                                    <?php _e('Open Graph / Twitter Card meta tags in &lt;head&gt; (skipped when Yoast/RankMath active)', 'delice-recipe-manager'); ?>
                                </label>
                            </fieldset>
                            <p class="description">
                                <?php _e('Toggle advanced features added in v3.6.0+. Disable any feature you do not need.', 'delice-recipe-manager'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="delice-recipe-settings-section">
                <h2><?php _e('Recipe Attribution', 'delice-recipe-manager'); ?></h2>
                <p><?php _e('Configure how recipe attribution is displayed on your recipes.', 'delice-recipe-manager'); ?></p>
                
                <?php
                $attribution_defaults = array(
                    'kitchen_name' => '',
                    'kitchen_url' => '',
                    'show_submitted_by' => true,
                    'show_tested_by' => true,
                    'default_author_name' => '',
                );
                $attribution_settings = array_merge($attribution_defaults, get_option('delice_recipe_attribution_settings', array()));
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Attribution Display', 'delice-recipe-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="delice_recipe_attribution_settings[show_submitted_by]" value="1" <?php checked(!empty($attribution_settings['show_submitted_by']), true); ?>>
                                <?php _e('Show "Submitted by" author attribution', 'delice-recipe-manager'); ?>
                            </label><br><br>
                            
                            <label>
                                <input type="checkbox" name="delice_recipe_attribution_settings[show_tested_by]" value="1" <?php checked(!empty($attribution_settings['show_tested_by']), true); ?>>
                                <?php _e('Show "Tested by" kitchen attribution', 'delice-recipe-manager'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Default Author Name', 'delice-recipe-manager'); ?></th>
                        <td>
                            <input type="text" name="delice_recipe_attribution_settings[default_author_name]" value="<?php echo esc_attr($attribution_settings['default_author_name']); ?>" class="regular-text" placeholder="e.g. Chef Sarah">
                            <p class="description">
                                <?php _e('This name will be used when no custom author is set for a recipe. Leave empty to use WordPress user data.', 'delice-recipe-manager'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Kitchen Information', 'delice-recipe-manager'); ?></th>
                        <td>
                            <label>
                                <span><?php _e('Kitchen name:', 'delice-recipe-manager'); ?></span>
                                <input type="text" name="delice_recipe_attribution_settings[kitchen_name]" value="<?php echo esc_attr($attribution_settings['kitchen_name']); ?>" class="regular-text" placeholder="e.g. Delice Recipe Kitchen">
                            </label><br><br>
                            
                            <label>
                                <span><?php _e('Kitchen page URL:', 'delice-recipe-manager'); ?></span>
                                <input type="url" name="delice_recipe_attribution_settings[kitchen_url]" value="<?php echo esc_url($attribution_settings['kitchen_url']); ?>" class="regular-text" placeholder="https://example.com/kitchen">
                            </label>
                            <p class="description">
                                <?php _e('Enter the URL where users should be directed when clicking on the "Tested by" link.', 'delice-recipe-manager'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="delice-recipe-settings-section">
                <h2><?php _e('Schema.org Settings', 'delice-recipe-manager'); ?></h2>
                <p><?php _e('Configure settings for recipe structured data markup.', 'delice-recipe-manager'); ?></p>
                
                <?php
                $schema_settings = get_option('delice_recipe_schema_settings', array(
                    'enable_schema' => true,
                    'publisher_name' => get_bloginfo('name'),
                    'publisher_logo' => '',
                    'use_author' => true,
                    'default_author' => '',
                ));
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Recipe Schema', 'delice-recipe-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="delice_recipe_schema_settings[enable_schema]" value="1" <?php checked(!empty($schema_settings['enable_schema']), true); ?>>
                                <?php _e('Enable structured data for recipes', 'delice-recipe-manager'); ?>
                            </label>
                            <p class="description">
                                <?php _e('This adds JSON-LD markup to your recipe pages to help search engines understand your content and display rich results.', 'delice-recipe-manager'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Publisher Information', 'delice-recipe-manager'); ?></th>
                        <td>
                            <label>
                                <span><?php _e('Publisher name:', 'delice-recipe-manager'); ?></span>
                                <input type="text" name="delice_recipe_schema_settings[publisher_name]" value="<?php echo esc_attr($schema_settings['publisher_name']); ?>" class="regular-text">
                            </label><br><br>
                            
                            <label>
                                <span><?php _e('Publisher logo URL:', 'delice-recipe-manager'); ?></span>
                                <input type="url" name="delice_recipe_schema_settings[publisher_logo]" value="<?php echo esc_url($schema_settings['publisher_logo']); ?>" class="regular-text">
                            </label>
                            <p class="description">
                                <?php _e('If left empty, your site logo will be used. Logo should be at least 112x112px.', 'delice-recipe-manager'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Author Settings', 'delice-recipe-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="delice_recipe_schema_settings[use_author]" value="1" <?php checked(!empty($schema_settings['use_author']), true); ?>>
                                <?php _e('Use post author as recipe author', 'delice-recipe-manager'); ?>
                            </label><br><br>
                            
                            <label>
                                <span><?php _e('Default author name:', 'delice-recipe-manager'); ?></span>
                                <input type="text" name="delice_recipe_schema_settings[default_author]" value="<?php echo esc_attr($schema_settings['default_author']); ?>" class="regular-text">
                            </label>
                            <p class="description">
                                <?php _e('Used when no author is set or when "Use post author" is disabled.', 'delice-recipe-manager'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Validation', 'delice-recipe-manager'); ?></th>
                        <td>
                            <p><?php _e('Test your structured data with these tools:', 'delice-recipe-manager'); ?></p>
                            <ul>
                                <li><a href="https://search.google.com/test/rich-results" target="_blank"><?php _e('Google Rich Results Test', 'delice-recipe-manager'); ?></a></li>
                                <li><a href="https://validator.schema.org/" target="_blank"><?php _e('Schema.org Validator', 'delice-recipe-manager'); ?></a></li>
                            </ul>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="delice-recipe-settings-section">
                <h2><?php _e('AI Integration', 'delice-recipe-manager'); ?></h2>
                <p><?php _e('Configure your API key for the AI recipe generator.', 'delice-recipe-manager'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="delice_recipe_ai_api_key"><?php _e('OpenAI API Key', 'delice-recipe-manager'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="delice_recipe_ai_api_key" name="delice_recipe_ai_api_key" 
                                   value="<?php echo esc_attr(get_option('delice_recipe_ai_api_key', '')); ?>" 
                                   class="regular-text">
                            <p class="description">
                                <?php _e('Enter your OpenAI API key to enable AI-powered recipe generation.', 'delice-recipe-manager'); ?>
                                <br><?php _e('Get your API key from: https://platform.openai.com/api-keys', 'delice-recipe-manager'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Auto-migrate to Post', 'delice-recipe-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="delice_recipe_auto_migrate_to_post" value="1"
                                       <?php checked( get_option( 'delice_recipe_auto_migrate_to_post', false ), true ); ?>>
                                <?php _e( 'Automatically save generated recipes as standard WordPress posts', 'delice-recipe-manager' ); ?>
                            </label>
                            <p class="description">
                                <?php _e( 'When enabled, every AI-generated recipe is immediately migrated from the custom recipe post type to a standard <strong>Post</strong>. All recipe data (ingredients, instructions, nutrition, etc.) is preserved. The recipe will appear in your main blog feed and will be eligible for Related Recipes on other posts.', 'delice-recipe-manager' ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('AI Image Generation', 'delice-recipe-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="delice_recipe_enable_ai_images" value="1"
                                       <?php checked(get_option('delice_recipe_enable_ai_images', false), true); ?>>
                                <?php _e('Automatically generate featured images with DALL-E 3', 'delice-recipe-manager'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, a high-quality recipe image will be generated and set as the featured image for each recipe. This uses DALL-E 3 and requires your OpenAI API key.', 'delice-recipe-manager'); ?>
                                <br><strong><?php _e('Note:', 'delice-recipe-manager'); ?></strong> <?php _e('Image generation costs $0.04 per image (HD quality, 1024x1024). Disable this if you prefer to add images manually.', 'delice-recipe-manager'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Image Style', 'delice-recipe-manager'); ?></th>
                        <td>
                            <?php $image_style = get_option('delice_recipe_image_style', 'vivid'); ?>
                            <select name="delice_recipe_image_style" id="delice_recipe_image_style">
                                <option value="vivid" <?php selected($image_style, 'vivid'); ?>>
                                    <?php _e('Vivid (More dramatic, vibrant colors)', 'delice-recipe-manager'); ?>
                                </option>
                                <option value="natural" <?php selected($image_style, 'natural'); ?>>
                                    <?php _e('Natural (More realistic, subtle)', 'delice-recipe-manager'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('Choose the style of generated images. Vivid creates more dramatic food photography, while Natural creates more realistic images.', 'delice-recipe-manager'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Image Size', 'delice-recipe-manager'); ?></th>
                        <td>
                            <?php $image_size = get_option('delice_recipe_image_size', '1024x1024'); ?>
                            <select name="delice_recipe_image_size" id="delice_recipe_image_size">
                                <optgroup label="<?php _e('DALL-E Native Sizes (No Resize)', 'delice-recipe-manager'); ?>">
                                    <option value="1024x1024" <?php selected($image_size, '1024x1024'); ?>>
                                        <?php _e('Square - 1024×1024 (High Quality)', 'delice-recipe-manager'); ?>
                                    </option>
                                    <option value="1792x1024" <?php selected($image_size, '1792x1024'); ?>>
                                        <?php _e('Landscape - 1792×1024 (Wide)', 'delice-recipe-manager'); ?>
                                    </option>
                                    <option value="1024x1792" <?php selected($image_size, '1024x1792'); ?>>
                                        <?php _e('Portrait - 1024×1792 (Tall)', 'delice-recipe-manager'); ?>
                                    </option>
                                </optgroup>
                                <optgroup label="<?php _e('Optimized Sizes (Auto-Resized)', 'delice-recipe-manager'); ?>">
                                    <option value="800x600" <?php selected($image_size, '800x600'); ?>>
                                        <?php _e('Landscape - 800×600 (Recommended)', 'delice-recipe-manager'); ?>
                                    </option>
                                    <option value="600x600" <?php selected($image_size, '600x600'); ?>>
                                        <?php _e('Square - 600×600 (Small)', 'delice-recipe-manager'); ?>
                                    </option>
                                    <option value="700x700" <?php selected($image_size, '700x700'); ?>>
                                        <?php _e('Square - 700×700 (Medium)', 'delice-recipe-manager'); ?>
                                    </option>
                                    <option value="600x800" <?php selected($image_size, '600x800'); ?>>
                                        <?php _e('Portrait - 600×800 (Vertical)', 'delice-recipe-manager'); ?>
                                    </option>
                                    <option value="900x600" <?php selected($image_size, '900x600'); ?>>
                                        <?php _e('Landscape - 900×600 (Wide)', 'delice-recipe-manager'); ?>
                                    </option>
                                </optgroup>
                            </select>
                            <p class="description">
                                <?php _e('<strong>Native Sizes:</strong> Generated directly by DALL-E 3, no processing (1-4 MB files).', 'delice-recipe-manager'); ?><br>
                                <?php _e('<strong>Optimized Sizes:</strong> Generated at 1024×1024, then auto-resized for faster loading (200-500 KB files).', 'delice-recipe-manager'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="delice-recipe-settings-section">
                <h2><?php _e('Recipe Reviews', 'delice-recipe-manager'); ?></h2>
                <p><?php _e('Configure the review and rating system for your recipes.', 'delice-recipe-manager'); ?></p>
                
                <?php $reviews_enabled = get_option('delice_recipe_reviews_enabled', true); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Reviews', 'delice-recipe-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="delice_recipe_reviews_enabled" value="1" <?php checked($reviews_enabled, true); ?>>
                                <?php _e('Enable recipe reviews and ratings', 'delice-recipe-manager'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, visitors can rate and review your recipes. When disabled, all review sections will be completely hidden from recipe pages.', 'delice-recipe-manager'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="delice-recipe-settings-section">
                <h2><?php _e('GitHub Auto-Updates', 'delice-recipe-manager'); ?></h2>
                <p>
                    <?php _e( 'This plugin updates itself directly from its GitHub repository — no third-party service needed.', 'delice-recipe-manager' ); ?>
                    <?php _e( 'Public repositories work without any configuration. For private repositories, paste a Personal Access Token (PAT) below.', 'delice-recipe-manager' ); ?>
                </p>

                <?php
                // Show current update status — always fetch live so clearing the
                // cache immediately shows fresh data (result is re-cached for 12 h).
                // get_release_info() returns false on error and caches a short-lived
                // failure marker so the API is not hammered on repeated page loads.
                $cache_key   = 'delice_gh_updater_' . md5( plugin_basename( DELICE_RECIPE_PLUGIN_FILE ) );
                $raw_cached  = get_transient( $cache_key ); // read before the live call overwrites
                $api_error   = ( $raw_cached && isset( $raw_cached->api_error ) ) ? (int) $raw_cached->api_error : null;

                $release     = isset( $GLOBALS['delice_gh_updater'] )
                    ? $GLOBALS['delice_gh_updater']->get_release_info()
                    : ( ( $raw_cached && ! isset( $raw_cached->api_error ) ) ? $raw_cached : false );
                $remote_ver  = $release ? ltrim( $release->tag_name, 'v' ) : null;
                $current_ver = DELICE_RECIPE_VERSION;
                $has_update  = $remote_ver && version_compare( $current_ver, $remote_ver, '<' );
                ?>

                <div style="margin-bottom:12px;">
                    <strong><?php _e( 'Current version:', 'delice-recipe-manager' ); ?></strong>
                    <?php echo esc_html( $current_ver ); ?>

                    <?php if ( $remote_ver ) : ?>
                        &nbsp;&mdash;&nbsp;
                        <strong><?php _e( 'Latest on GitHub:', 'delice-recipe-manager' ); ?></strong>
                        <?php echo esc_html( $remote_ver ); ?>
                        <?php if ( $has_update ) : ?>
                            <span style="color:#d63638;font-weight:600;">&nbsp;&#8593; <?php _e( 'Update available!', 'delice-recipe-manager' ); ?></span>
                        <?php else : ?>
                            <span style="color:#008a20;font-weight:600;">&nbsp;&#10003; <?php _e( 'Up to date', 'delice-recipe-manager' ); ?></span>
                        <?php endif; ?>
                    <?php elseif ( null !== $api_error ) : ?>
                        &nbsp;&mdash;&nbsp;
                        <?php if ( 429 === $api_error ) : ?>
                            <span style="color:#d63638;">&#9888; <?php _e( 'GitHub API rate limit reached. Retry in ~1 hour or add a Personal Access Token.', 'delice-recipe-manager' ); ?></span>
                        <?php elseif ( 401 === $api_error ) : ?>
                            <span style="color:#d63638;">&#9888; <?php _e( 'GitHub token is invalid or expired. Please update your Personal Access Token.', 'delice-recipe-manager' ); ?></span>
                        <?php elseif ( 403 === $api_error ) : ?>
                            <span style="color:#d63638;">&#9888; <?php _e( 'GitHub token lacks permissions. It needs the <code>repo</code> or <code>contents:read</code> scope.', 'delice-recipe-manager' ); ?></span>
                        <?php elseif ( 404 === $api_error ) : ?>
                            <span style="color:#d63638;">&#9888; <?php _e( 'Repository or plugin file not found on the main branch. Check that the repository name is correct.', 'delice-recipe-manager' ); ?></span>
                        <?php else : ?>
                            <span style="color:#d63638;">&#9888; <?php printf( esc_html__( 'GitHub API error (HTTP %d). Click "Clear Cache &amp; Check Now" to retry.', 'delice-recipe-manager' ), $api_error ); ?></span>
                        <?php endif; ?>
                    <?php else : ?>
                        &nbsp;&mdash;&nbsp;<em><?php _e( 'Not yet checked. Click "Clear Cache &amp; Check Now" to fetch the latest release from GitHub.', 'delice-recipe-manager' ); ?></em>
                    <?php endif; ?>
                </div>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="delice_github_token"><?php _e( 'GitHub Personal Access Token', 'delice-recipe-manager' ); ?></label>
                        </th>
                        <td>
                            <input
                                type="password"
                                id="delice_github_token"
                                name="delice_github_token"
                                value="<?php echo esc_attr( get_option( 'delice_github_token', '' ) ); ?>"
                                class="regular-text"
                                autocomplete="new-password"
                            >
                            <button type="button"
                                    onclick="var f=document.getElementById('delice_github_token');f.type=f.type==='password'?'text':'password';"
                                    class="button button-secondary" style="vertical-align:middle;">
                                <?php _e( 'Show / Hide', 'delice-recipe-manager' ); ?>
                            </button>
                            <p class="description">
                                <?php _e( '<strong>Leave blank for public repositories.</strong> For private repositories, create a token with <code>repo</code> scope (classic PAT) or <code>contents: read</code> scope (fine-grained PAT) and paste it here.', 'delice-recipe-manager' ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e( 'Repository', 'delice-recipe-manager' ); ?></th>
                        <td>
                            <code>ubaid-grittychef/delice-recipe-manager</code>
                            <p class="description">
                                <?php _e( 'Updates are fetched directly from the <strong>main branch</strong>. To push an update: bump the <code>Version:</code> line in <code>delice-recipe-manager.php</code> and push to main. No releases or tags needed.', 'delice-recipe-manager' ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e( 'Force Update Check', 'delice-recipe-manager' ); ?></th>
                        <td>
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=delice-recipe-settings&action=delice_clear_update_cache' ), 'delice_clear_update_cache' ) ); ?>"
                               class="button button-secondary">
                                <?php _e( 'Clear Cache &amp; Check Now', 'delice-recipe-manager' ); ?>
                            </a>
                            <p class="description">
                                <?php _e( 'GitHub API responses are cached for 12 hours. Click to clear the cache and fetch the latest release immediately.', 'delice-recipe-manager' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="delice-recipe-settings-section">
                <h2><?php _e('Reset Settings', 'delice-recipe-manager'); ?></h2>
                <p><?php _e('Reset all plugin settings to their default values.', 'delice-recipe-manager'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Reset Options', 'delice-recipe-manager'); ?></th>
                        <td>
                            <button type="button" id="reset-settings" class="button button-secondary">
                                <?php _e('Reset All Settings', 'delice-recipe-manager'); ?>
                            </button>
                            <p class="description">
                                <?php _e('Warning: This will reset all plugin settings to their default values. This action cannot be undone.', 'delice-recipe-manager'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php submit_button(); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#reset-settings').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to reset all settings? This action cannot be undone.', 'delice-recipe-manager'); ?>')) {
            // Clear all settings by setting them to empty/default values
            $('input[type="checkbox"]').prop('checked', false);
            $('input[type="text"], input[type="url"], input[type="password"]').val('');
            $('select').prop('selectedIndex', 0);
            
            // Set some defaults
            $('input[name="delice_recipe_display_options[show_image]"]').prop('checked', true);
            $('input[name="delice_recipe_display_options[show_ingredients]"]').prop('checked', true);
            $('input[name="delice_recipe_display_options[show_instructions]"]').prop('checked', true);
            $('input[name="delice_recipe_schema_settings[enable_schema]"]').prop('checked', true);
            
            alert('<?php _e('Settings have been reset. Click "Save Changes" to apply.', 'delice-recipe-manager'); ?>');
        }
    });
});
</script>

<style>
.delice-recipe-settings-container {
    max-width: 1000px;
}

.delice-recipe-settings-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.delice-recipe-settings-section h2 {
    margin-top: 0;
    color: #0073aa;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.form-table th {
    width: 200px;
    vertical-align: top;
    padding-top: 15px;
}

.form-table td {
    vertical-align: top;
    padding-top: 10px;
}

.form-table fieldset label {
    display: block;
    margin: 5px 0;
}

.description {
    font-style: italic;
    color: #666;
    margin-top: 5px !important;
}
</style>
