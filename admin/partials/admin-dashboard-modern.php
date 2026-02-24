<?php
/**
 * Modern Admin Dashboard with Backend Connections
 * Provides a clean, minimalist interface with all features
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get real data from database
global $wpdb;

// Get recipe counts
$total_recipes = wp_count_posts('delice_recipe');
$published_count = isset($total_recipes->publish) ? $total_recipes->publish : 0;
$draft_count = isset($total_recipes->draft) ? $total_recipes->draft : 0;
$pending_count = isset($total_recipes->pending) ? $total_recipes->pending : 0;

// Get migration stats
$migration = new Delice_Recipe_Migration();
$migration_stats = $migration->get_migration_stats();

// Get recent recipes
$recent_recipes = get_posts(array(
    'post_type' => 'delice_recipe',
    'posts_per_page' => 10,
    'orderby' => 'date',
    'order' => 'DESC',
    'post_status' => array('publish', 'draft', 'pending')
));

// Get all settings
$reviews_enabled = get_option('delice_recipe_reviews_enabled', true);
$selected_template = get_option('delice_recipe_selected_template', 'default');
$api_key = get_option('delice_recipe_ai_api_key', '');
$ai_images_enabled = get_option('delice_recipe_enable_ai_images', false);

// Display options
$display_options = get_option('delice_recipe_display_options', array(
    'show_image' => true,
    'show_servings' => true,
    'show_prep_time' => true,
    'show_cook_time' => true,
    'show_total_time' => true,
    'show_calories' => true,
    'show_difficulty' => true,
    'show_rating' => true,
    'show_ingredients' => true,
    'show_instructions' => true,
    'show_notes' => true,
    'show_faqs' => true,
));

// Attribution settings
$attribution_defaults = array(
    'kitchen_name' => '',
    'kitchen_url' => '',
    'show_submitted_by' => true,
    'show_tested_by' => true,
    'default_author_name' => '',
);
$attribution_settings = array_merge($attribution_defaults, get_option('delice_recipe_attribution_settings', array()));

// Schema settings
$schema_defaults = array(
    'enable_schema' => true,
    'publisher_name' => get_bloginfo('name'),
    'publisher_logo' => '',
    'use_author' => true,
    'default_author' => '',
);
$schema_settings = array_merge($schema_defaults, get_option('delice_recipe_schema_settings', array()));

// Language settings
$enabled_languages = get_option('delice_recipe_enabled_languages', array('en_US'));
$default_language = get_option('delice_recipe_default_language', 'en_US');

$available_languages = array(
    'en_US' => __('English (US)', 'delice-recipe-manager'),
    'en_GB' => __('English (UK)', 'delice-recipe-manager'),
    'fr_FR' => __('French', 'delice-recipe-manager'),
    'es_ES' => __('Spanish', 'delice-recipe-manager'),
    'de_DE' => __('German', 'delice-recipe-manager'),
    'it_IT' => __('Italian', 'delice-recipe-manager'),
    'pt_BR' => __('Portuguese (Brazil)', 'delice-recipe-manager'),
    'ja' => __('Japanese', 'delice-recipe-manager'),
    'zh_CN' => __('Chinese (Simplified)', 'delice-recipe-manager'),
    'ru_RU' => __('Russian', 'delice-recipe-manager'),
    'ar' => __('Arabic', 'delice-recipe-manager'),
);

// Review stats
$reviews_table = $wpdb->prefix . 'delice_recipe_reviews';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$reviews_table'") == $reviews_table;

if ($table_exists) {
    $total_reviews = $wpdb->get_var("SELECT COUNT(*) FROM $reviews_table");
    $approved_reviews = $wpdb->get_var("SELECT COUNT(*) FROM $reviews_table WHERE status = 'approved'");
    $pending_reviews = $wpdb->get_var("SELECT COUNT(*) FROM $reviews_table WHERE status = 'pending'");
} else {
    $total_reviews = $approved_reviews = $pending_reviews = 0;
}

// Get review settings
$review_settings = get_option('delice_recipe_review_settings', array(
    'auto_approve' => true,
    'allow_anonymous' => true,
    'max_image_size' => 2
));

?>

<div class="delice-admin-wrap">
    <!-- Top Navigation -->
    <nav class="delice-admin-nav">
        <div class="delice-admin-nav__inner">
            <div class="delice-admin-logo">Delice Recipe Manager</div>
            <div class="delice-admin-tabs">
                <button class="delice-admin-tab active" data-pane="dashboard"><?php _e('Dashboard', 'delice-recipe-manager'); ?></button>
                <button class="delice-admin-tab" data-pane="ai-generator"><?php _e('AI Generator', 'delice-recipe-manager'); ?></button>
                <button class="delice-admin-tab" data-pane="settings"><?php _e('Settings', 'delice-recipe-manager'); ?></button>
                <button class="delice-admin-tab" data-pane="content"><?php _e('Content', 'delice-recipe-manager'); ?></button>
                <button class="delice-admin-tab" data-pane="reviews"><?php _e('Reviews', 'delice-recipe-manager'); ?></button>
                <button class="delice-admin-tab" data-pane="migration"><?php _e('Migration', 'delice-recipe-manager'); ?></button>
            </div>
            <a href="<?php echo admin_url('post-new.php?post_type=delice_recipe'); ?>" class="delice-btn delice-btn-primary delice-btn-sm">+ <?php _e('New Recipe', 'delice-recipe-manager'); ?></a>
        </div>
    </nav>
    
    <div class="delice-admin-content">
        
        <!-- DASHBOARD PANE -->
        <div id="dashboard" class="delice-admin-pane active">
            <div class="delice-page-header">
                <div>
                    <h1 class="delice-page-title"><?php _e('Dashboard', 'delice-recipe-manager'); ?></h1>
                    <p class="delice-page-subtitle"><?php _e('Overview of your recipe collection', 'delice-recipe-manager'); ?></p>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="delice-stats-grid">
                <div class="delice-stat-card delice-stat-card--primary">
                    <div class="delice-stat-value delice-stat-total"><?php echo $published_count + $draft_count + $pending_count; ?></div>
                    <div class="delice-stat-label"><?php _e('Total Recipes', 'delice-recipe-manager'); ?></div>
                </div>
                <div class="delice-stat-card delice-stat-card--success">
                    <div class="delice-stat-value delice-stat-published"><?php echo $published_count; ?></div>
                    <div class="delice-stat-label"><?php _e('Published', 'delice-recipe-manager'); ?></div>
                </div>
                <div class="delice-stat-card delice-stat-card--warning">
                    <div class="delice-stat-value delice-stat-drafts"><?php echo $draft_count; ?></div>
                    <div class="delice-stat-label"><?php _e('Drafts', 'delice-recipe-manager'); ?></div>
                </div>
                <?php if ($pending_count > 0): ?>
                <div class="delice-stat-card delice-stat-card--info">
                    <div class="delice-stat-value"><?php echo $pending_count; ?></div>
                    <div class="delice-stat-label"><?php _e('Pending', 'delice-recipe-manager'); ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Recent Recipes -->
            <div class="delice-section">
                <div class="delice-section-header">
                    <h3 class="delice-section-title"><?php _e('Recent Recipes', 'delice-recipe-manager'); ?></h3>
                </div>
                
                <?php if (!empty($recent_recipes)): ?>
                <div class="delice-table-wrap">
                    <table class="delice-table">
                        <thead>
                            <tr>
                                <th><?php _e('Recipe', 'delice-recipe-manager'); ?></th>
                                <th><?php _e('Date', 'delice-recipe-manager'); ?></th>
                                <th><?php _e('Status', 'delice-recipe-manager'); ?></th>
                                <th><?php _e('Migration', 'delice-recipe-manager'); ?></th>
                                <th><?php _e('Actions', 'delice-recipe-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_recipes as $recipe): 
                                $migrated_id = get_post_meta($recipe->ID, '_delice_migration_new_id', true);
                                $status_obj = get_post_status_object($recipe->post_status);
                                $status_label = $status_obj ? $status_obj->label : $recipe->post_status;
                                $status_class = ($recipe->post_status === 'publish') ? 'success' : 'warning';
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html(get_the_title($recipe->ID)); ?></strong></td>
                                <td><?php echo get_the_date('', $recipe->ID); ?></td>
                                <td><span class="delice-badge delice-badge-<?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                                <td>
                                    <?php if ($migrated_id): ?>
                                        <span class="delice-badge delice-badge-info"><?php _e('Migrated', 'delice-recipe-manager'); ?></span>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($recipe->ID); ?>" class="delice-btn delice-btn-secondary delice-btn-sm"><?php _e('Edit', 'delice-recipe-manager'); ?></a>
                                    <?php if ($recipe->post_status === 'publish'): ?>
                                    <a href="<?php echo get_permalink($recipe->ID); ?>" class="delice-btn delice-btn-secondary delice-btn-sm" target="_blank"><?php _e('View', 'delice-recipe-manager'); ?></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p><?php _e('No recipes found. Create your first recipe!', 'delice-recipe-manager'); ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Quick Settings -->
            <div class="delice-section">
                <div class="delice-section-header">
                    <h3 class="delice-section-title"><?php _e('Quick Settings', 'delice-recipe-manager'); ?></h3>
                </div>
                <div class="delice-toggle-group">
                    <div class="delice-toggle <?php echo $reviews_enabled ? 'active' : ''; ?>" data-setting="reviews_enabled">
                        <div class="delice-toggle-dot"></div>
                    </div>
                    <span><?php _e('Enable review system', 'delice-recipe-manager'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- AI GENERATOR PANE -->
        <div id="ai-generator" class="delice-admin-pane">
            <div class="delice-page-header">
                <div>
                    <h1 class="delice-page-title"><?php _e('AI Generator', 'delice-recipe-manager'); ?></h1>
                    <p class="delice-page-subtitle"><?php _e('Create recipes with artificial intelligence', 'delice-recipe-manager'); ?></p>
                </div>
            </div>
            
            <?php if (empty($api_key)): ?>
            <div class="delice-notice delice-notice-error">
                <strong><?php _e('OpenAI API Key Required', 'delice-recipe-manager'); ?></strong><br>
                <?php _e('Please configure your OpenAI API key in the settings below to use the AI generator.', 'delice-recipe-manager'); ?>
            </div>
            <?php endif; ?>
            
            <div class="delice-section">
                <div class="delice-section-header">
                    <h3 class="delice-section-title"><?php _e('Generate Recipe', 'delice-recipe-manager'); ?></h3>
                    <p class="delice-section-desc"><?php _e('Enter keywords and AI will create a complete recipe', 'delice-recipe-manager'); ?></p>
                </div>
                
                <div class="delice-form-grid">
                    <div class="delice-form-group">
                        <label class="delice-label"><?php _e('Generation Mode', 'delice-recipe-manager'); ?></label>
                        <select class="delice-select" name="generation_mode">
                            <option value="single"><?php _e('Single Recipe', 'delice-recipe-manager'); ?></option>
                            <option value="bulk"><?php _e('Bulk Generation (up to 100)', 'delice-recipe-manager'); ?></option>
                        </select>
                    </div>
                    
                    <div class="delice-form-group">
                        <label class="delice-label"><?php _e('Recipe Keywords', 'delice-recipe-manager'); ?></label>
                        <input type="text" class="delice-input" name="keywords" placeholder="<?php _e('e.g. Spicy Thai Curry', 'delice-recipe-manager'); ?>">
                        <span class="delice-hint"><?php _e('Enter keywords describing the recipe you want to generate', 'delice-recipe-manager'); ?></span>
                    </div>
                    
                    <div class="delice-form-group">
                        <label class="delice-label"><?php _e('Variations (Optional)', 'delice-recipe-manager'); ?></label>
                        <div class="delice-checkbox-group">
                            <label class="delice-checkbox-item">
                                <input type="checkbox" class="delice-checkbox" name="variation_vegan" value="1">
                                <span class="delice-checkbox-label"><?php _e('Make it vegan', 'delice-recipe-manager'); ?></span>
                            </label>
                            <label class="delice-checkbox-item">
                                <input type="checkbox" class="delice-checkbox" name="variation_quick" value="1">
                                <span class="delice-checkbox-label"><?php _e('Quick weeknight dinner', 'delice-recipe-manager'); ?></span>
                            </label>
                            <label class="delice-checkbox-item">
                                <input type="checkbox" class="delice-checkbox" name="variation_glutenfree" value="1">
                                <span class="delice-checkbox-label"><?php _e('Gluten-free', 'delice-recipe-manager'); ?></span>
                            </label>
                            <label class="delice-checkbox-item">
                                <input type="checkbox" class="delice-checkbox" name="variation_lowcal" value="1">
                                <span class="delice-checkbox-label"><?php _e('Low calorie', 'delice-recipe-manager'); ?></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="delice-grid-2">
                        <div class="delice-form-group">
                            <label class="delice-label"><?php _e('Target Language', 'delice-recipe-manager'); ?></label>
                            <select class="delice-select" name="target_language">
                                <option value="english"><?php _e('English', 'delice-recipe-manager'); ?></option>
                                <option value="french"><?php _e('French', 'delice-recipe-manager'); ?></option>
                                <option value="spanish"><?php _e('Spanish', 'delice-recipe-manager'); ?></option>
                                <option value="german"><?php _e('German', 'delice-recipe-manager'); ?></option>
                            </select>
                        </div>
                        
                        <div class="delice-form-group">
                            <label class="delice-label"><?php _e('Cuisine', 'delice-recipe-manager'); ?></label>
                            <select class="delice-select" name="cuisine">
                                <option value=""><?php _e('Any', 'delice-recipe-manager'); ?></option>
                                <option value="italian"><?php _e('Italian', 'delice-recipe-manager'); ?></option>
                                <option value="french"><?php _e('French', 'delice-recipe-manager'); ?></option>
                                <option value="mexican"><?php _e('Mexican', 'delice-recipe-manager'); ?></option>
                                <option value="chinese"><?php _e('Chinese', 'delice-recipe-manager'); ?></option>
                                <option value="indian"><?php _e('Indian', 'delice-recipe-manager'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <a href="<?php echo admin_url('admin.php?page=delice-recipe-generator'); ?>" class="delice-btn delice-btn-primary"><?php _e('Open Full AI Generator', 'delice-recipe-manager'); ?></a>
                </div>
            </div>
            
            <div class="delice-section">
                <div class="delice-section-header">
                    <h3 class="delice-section-title"><?php _e('OpenAI Configuration', 'delice-recipe-manager'); ?></h3>
                    <p class="delice-section-desc"><?php _e('Configure your API key for AI generation', 'delice-recipe-manager'); ?></p>
                </div>
                
                <form method="post" action="options.php">
                    <?php settings_fields('delice_recipe_settings'); ?>
                    <div class="delice-form-grid">
                        <div class="delice-form-group">
                            <label class="delice-label"><?php _e('API Key', 'delice-recipe-manager'); ?></label>
                            <input type="password" class="delice-input" name="delice_recipe_ai_api_key" value="<?php echo esc_attr($api_key); ?>" placeholder="sk-...">
                            <span class="delice-hint"><?php _e('Get your key from', 'delice-recipe-manager'); ?> <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com/api-keys</a></span>
                        </div>
                        
                        <div class="delice-toggle-group">
                            <div class="delice-toggle <?php echo $ai_images_enabled ? 'active' : ''; ?>">
                                <div class="delice-toggle-dot"></div>
                            </div>
                            <span><?php _e('Auto-generate images with DALL-E 3', 'delice-recipe-manager'); ?></span>
                            <input type="hidden" name="delice_recipe_enable_ai_images" value="<?php echo $ai_images_enabled ? '1' : '0'; ?>">
                        </div>
                        
                        <?php submit_button(__('Save API Settings', 'delice-recipe-manager'), 'primary', 'submit', false); ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- SETTINGS PANE -->
        <div id="settings" class="delice-admin-pane">
            <div class="delice-page-header">
                <div>
                    <h1 class="delice-page-title"><?php _e('Settings', 'delice-recipe-manager'); ?></h1>
                    <p class="delice-page-subtitle"><?php _e('Configure your recipe plugin', 'delice-recipe-manager'); ?></p>
                </div>
                <button class="delice-btn delice-btn-primary delice-save-settings" data-section="settings"><?php _e('Save Changes', 'delice-recipe-manager'); ?></button>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields('delice_recipe_settings'); ?>
                
                <!-- Template Selection -->
                <div class="delice-section">
                    <div class="delice-section-header">
                        <h3 class="delice-section-title"><?php _e('Template Selection', 'delice-recipe-manager'); ?></h3>
                        <p class="delice-section-desc"><?php _e('Choose which template to use for displaying recipes', 'delice-recipe-manager'); ?></p>
                    </div>
                    
                    <div class="delice-form-group">
                        <label class="delice-label"><?php _e('Recipe Template', 'delice-recipe-manager'); ?></label>
                        <select class="delice-select" name="delice_recipe_selected_template">
                            <option value="default" <?php selected($selected_template, 'default'); ?>><?php _e('Default', 'delice-recipe-manager'); ?></option>
                            <option value="modern" <?php selected($selected_template, 'modern'); ?>><?php _e('Modern', 'delice-recipe-manager'); ?></option>
                            <option value="elegant" <?php selected($selected_template, 'elegant'); ?>><?php _e('Elegant', 'delice-recipe-manager'); ?></option>
                        </select>
                    </div>
                </div>
                
                <!-- Display Settings -->
                <div class="delice-section">
                    <div class="delice-section-header">
                        <h3 class="delice-section-title"><?php _e('Display Settings', 'delice-recipe-manager'); ?></h3>
                        <p class="delice-section-desc"><?php _e('Choose which elements to display in recipes', 'delice-recipe-manager'); ?></p>
                    </div>
                    
                    <div class="delice-checkbox-group">
                        <label class="delice-checkbox-item">
                            <input type="checkbox" class="delice-checkbox" name="delice_recipe_display_options[show_image]" value="1" <?php checked(!empty($display_options['show_image']), true); ?>>
                            <span class="delice-checkbox-label"><?php _e('Show main image', 'delice-recipe-manager'); ?></span>
                        </label>
                        <label class="delice-checkbox-item">
                            <input type="checkbox" class="delice-checkbox" name="delice_recipe_display_options[show_servings]" value="1" <?php checked(!empty($display_options['show_servings']), true); ?>>
                            <span class="delice-checkbox-label"><?php _e('Show servings', 'delice-recipe-manager'); ?></span>
                        </label>
                        <label class="delice-checkbox-item">
                            <input type="checkbox" class="delice-checkbox" name="delice_recipe_display_options[show_prep_time]" value="1" <?php checked(!empty($display_options['show_prep_time']), true); ?>>
                            <span class="delice-checkbox-label"><?php _e('Show prep time', 'delice-recipe-manager'); ?></span>
                        </label>
                        <label class="delice-checkbox-item">
                            <input type="checkbox" class="delice-checkbox" name="delice_recipe_display_options[show_cook_time]" value="1" <?php checked(!empty($display_options['show_cook_time']), true); ?>>
                            <span class="delice-checkbox-label"><?php _e('Show cook time', 'delice-recipe-manager'); ?></span>
                        </label>
                        <label class="delice-checkbox-item">
                            <input type="checkbox" class="delice-checkbox" name="delice_recipe_display_options[show_total_time]" value="1" <?php checked(!empty($display_options['show_total_time']), true); ?>>
                            <span class="delice-checkbox-label"><?php _e('Show total time', 'delice-recipe-manager'); ?></span>
                        </label>
                        <label class="delice-checkbox-item">
                            <input type="checkbox" class="delice-checkbox" name="delice_recipe_display_options[show_calories]" value="1" <?php checked(!empty($display_options['show_calories']), true); ?>>
                            <span class="delice-checkbox-label"><?php _e('Show calories', 'delice-recipe-manager'); ?></span>
                        </label>
                        <label class="delice-checkbox-item">
                            <input type="checkbox" class="delice-checkbox" name="delice_recipe_display_options[show_difficulty]" value="1" <?php checked(!empty($display_options['show_difficulty']), true); ?>>
                            <span class="delice-checkbox-label"><?php _e('Show difficulty', 'delice-recipe-manager'); ?></span>
                        </label>
                        <label class="delice-checkbox-item">
                            <input type="checkbox" class="delice-checkbox" name="delice_recipe_display_options[show_rating]" value="1" <?php checked(!empty($display_options['show_rating']), true); ?>>
                            <span class="delice-checkbox-label"><?php _e('Show rating system', 'delice-recipe-manager'); ?></span>
                        </label>
                        <label class="delice-checkbox-item">
                            <input type="checkbox" class="delice-checkbox" name="delice_recipe_display_options[show_notes]" value="1" <?php checked(!empty($display_options['show_notes']), true); ?>>
                            <span class="delice-checkbox-label"><?php _e('Show notes', 'delice-recipe-manager'); ?></span>
                        </label>
                        <label class="delice-checkbox-item">
                            <input type="checkbox" class="delice-checkbox" name="delice_recipe_display_options[show_faqs]" value="1" <?php checked(!empty($display_options['show_faqs']), true); ?>>
                            <span class="delice-checkbox-label"><?php _e('Show FAQs', 'delice-recipe-manager'); ?></span>
                        </label>
                    </div>
                </div>
                
                <!-- Schema.org Settings -->
                <div class="delice-section">
                    <div class="delice-section-header">
                        <h3 class="delice-section-title"><?php _e('Schema.org Settings', 'delice-recipe-manager'); ?></h3>
                        <p class="delice-section-desc"><?php _e('Configure structured data for better SEO', 'delice-recipe-manager'); ?></p>
                    </div>
                    
                    <div class="delice-form-grid">
                        <div class="delice-toggle-group">
                            <div class="delice-toggle <?php echo $schema_settings['enable_schema'] ? 'active' : ''; ?>">
                                <div class="delice-toggle-dot"></div>
                            </div>
                            <span><?php _e('Enable structured data for recipes', 'delice-recipe-manager'); ?></span>
                            <input type="hidden" name="delice_recipe_schema_settings[enable_schema]" value="<?php echo $schema_settings['enable_schema'] ? '1' : '0'; ?>">
                        </div>
                        
                        <div class="delice-form-group">
                            <label class="delice-label"><?php _e('Publisher Name', 'delice-recipe-manager'); ?></label>
                            <input type="text" class="delice-input" name="delice_recipe_schema_settings[publisher_name]" value="<?php echo esc_attr($schema_settings['publisher_name']); ?>" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                        </div>
                        
                        <div class="delice-form-group">
                            <label class="delice-label"><?php _e('Publisher Logo URL', 'delice-recipe-manager'); ?></label>
                            <input type="url" class="delice-input" name="delice_recipe_schema_settings[publisher_logo]" value="<?php echo esc_url($schema_settings['publisher_logo']); ?>" placeholder="https://example.com/logo.png">
                            <span class="delice-hint"><?php _e('Logo should be at least 112x112px', 'delice-recipe-manager'); ?></span>
                        </div>
                        
                        <div class="delice-toggle-group">
                            <div class="delice-toggle <?php echo $schema_settings['use_author'] ? 'active' : ''; ?>">
                                <div class="delice-toggle-dot"></div>
                            </div>
                            <span><?php _e('Use post author as recipe author', 'delice-recipe-manager'); ?></span>
                            <input type="hidden" name="delice_recipe_schema_settings[use_author]" value="<?php echo $schema_settings['use_author'] ? '1' : '0'; ?>">
                        </div>
                    </div>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <!-- CONTENT PANE (Attribution + Languages) -->
        <div id="content" class="delice-admin-pane">
            <div class="delice-page-header">
                <div>
                    <h1 class="delice-page-title"><?php _e('Content Settings', 'delice-recipe-manager'); ?></h1>
                    <p class="delice-page-subtitle"><?php _e('Attribution and language configuration', 'delice-recipe-manager'); ?></p>
                </div>
                <button class="delice-btn delice-btn-primary delice-save-settings" data-section="content"><?php _e('Save Changes', 'delice-recipe-manager'); ?></button>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields('delice_recipe_settings'); ?>
                
                <!-- Attribution -->
                <div class="delice-section">
                    <div class="delice-section-header">
                        <h3 class="delice-section-title"><?php _e('Recipe Attribution', 'delice-recipe-manager'); ?></h3>
                        <p class="delice-section-desc"><?php _e('Configure how recipe attribution is displayed', 'delice-recipe-manager'); ?></p>
                    </div>
                    
                    <div class="delice-form-grid">
                        <div class="delice-checkbox-group">
                            <label class="delice-checkbox-item">
                                <input type="checkbox" class="delice-checkbox" name="delice_recipe_attribution_settings[show_submitted_by]" value="1" <?php checked(!empty($attribution_settings['show_submitted_by']), true); ?>>
                                <span class="delice-checkbox-label"><?php _e('Show "Submitted by" author attribution', 'delice-recipe-manager'); ?></span>
                            </label>
                            <label class="delice-checkbox-item">
                                <input type="checkbox" class="delice-checkbox" name="delice_recipe_attribution_settings[show_tested_by]" value="1" <?php checked(!empty($attribution_settings['show_tested_by']), true); ?>>
                                <span class="delice-checkbox-label"><?php _e('Show "Tested by" kitchen attribution', 'delice-recipe-manager'); ?></span>
                            </label>
                        </div>
                        
                        <div class="delice-form-group">
                            <label class="delice-label"><?php _e('Kitchen Name', 'delice-recipe-manager'); ?></label>
                            <input type="text" class="delice-input" name="delice_recipe_attribution_settings[kitchen_name]" value="<?php echo esc_attr($attribution_settings['kitchen_name']); ?>" placeholder="<?php _e('Delice Recipe Kitchen', 'delice-recipe-manager'); ?>">
                        </div>
                        
                        <div class="delice-form-group">
                            <label class="delice-label"><?php _e('Kitchen URL', 'delice-recipe-manager'); ?></label>
                            <input type="url" class="delice-input" name="delice_recipe_attribution_settings[kitchen_url]" value="<?php echo esc_url($attribution_settings['kitchen_url']); ?>" placeholder="https://example.com/kitchen">
                        </div>
                        
                        <div class="delice-form-group">
                            <label class="delice-label"><?php _e('Default Author Name', 'delice-recipe-manager'); ?></label>
                            <input type="text" class="delice-input" name="delice_recipe_attribution_settings[default_author_name]" value="<?php echo esc_attr($attribution_settings['default_author_name']); ?>" placeholder="<?php _e('Chef Sarah', 'delice-recipe-manager'); ?>">
                            <span class="delice-hint"><?php _e('Used when no custom author is set', 'delice-recipe-manager'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Languages -->
                <div class="delice-section">
                    <div class="delice-section-header">
                        <h3 class="delice-section-title"><?php _e('Languages', 'delice-recipe-manager'); ?></h3>
                        <p class="delice-section-desc"><?php _e('Select which languages are available', 'delice-recipe-manager'); ?></p>
                    </div>
                    
                    <div class="delice-form-grid">
                        <div class="delice-form-group">
                            <label class="delice-label"><?php _e('Default Language', 'delice-recipe-manager'); ?></label>
                            <select class="delice-select" name="default_language">
                                <?php foreach ($available_languages as $code => $name): ?>
                                <option value="<?php echo esc_attr($code); ?>" <?php selected($default_language, $code); ?>><?php echo esc_html($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="delice-form-group">
                            <label class="delice-label"><?php _e('Enabled Languages', 'delice-recipe-manager'); ?></label>
                            <div class="delice-lang-grid">
                                <?php foreach ($available_languages as $code => $name): 
                                    $is_enabled = in_array($code, $enabled_languages);
                                ?>
                                <div class="delice-lang-card <?php echo $is_enabled ? 'selected' : ''; ?>">
                                    <input type="checkbox" name="enabled_languages[]" value="<?php echo esc_attr($code); ?>" <?php checked($is_enabled, true); ?>>
                                    <?php echo esc_html($name); ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <!-- REVIEWS PANE -->
        <div id="reviews" class="delice-admin-pane">
            <div class="delice-page-header">
                <div>
                    <h1 class="delice-page-title"><?php _e('Reviews', 'delice-recipe-manager'); ?></h1>
                    <p class="delice-page-subtitle"><?php _e('Manage recipe reviews and ratings', 'delice-recipe-manager'); ?></p>
                </div>
                <button class="delice-btn delice-btn-primary delice-save-settings" data-section="reviews"><?php _e('Save Changes', 'delice-recipe-manager'); ?></button>
            </div>
            
            <!-- Stats -->
            <div class="delice-stats-grid">
                <div class="delice-stat-card delice-stat-card--primary">
                    <div class="delice-stat-value"><?php echo intval($total_reviews); ?></div>
                    <div class="delice-stat-label"><?php _e('Total Reviews', 'delice-recipe-manager'); ?></div>
                </div>
                <div class="delice-stat-card delice-stat-card--success">
                    <div class="delice-stat-value"><?php echo intval($approved_reviews); ?></div>
                    <div class="delice-stat-label"><?php _e('Approved', 'delice-recipe-manager'); ?></div>
                </div>
                <div class="delice-stat-card delice-stat-card--warning">
                    <div class="delice-stat-value"><?php echo intval($pending_reviews); ?></div>
                    <div class="delice-stat-label"><?php _e('Pending', 'delice-recipe-manager'); ?></div>
                </div>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('delice_recipe_review_settings', '_wpnonce'); ?>
                
                <div class="delice-section">
                    <div class="delice-section-header">
                        <h3 class="delice-section-title"><?php _e('Review Settings', 'delice-recipe-manager'); ?></h3>
                    </div>
                    
                    <div class="delice-form-grid">
                        <div class="delice-toggle-group">
                            <div class="delice-toggle <?php echo $reviews_enabled ? 'active' : ''; ?>">
                                <div class="delice-toggle-dot"></div>
                            </div>
                            <span><?php _e('Enable review system', 'delice-recipe-manager'); ?></span>
                            <input type="hidden" name="reviews_enabled" value="<?php echo $reviews_enabled ? '1' : '0'; ?>">
                        </div>
                        
                        <div class="delice-checkbox-group">
                            <label class="delice-checkbox-item">
                                <input type="checkbox" class="delice-checkbox" name="auto_approve" value="1" <?php checked($review_settings['auto_approve'], true); ?>>
                                <span class="delice-checkbox-label"><?php _e('Auto-approve reviews', 'delice-recipe-manager'); ?></span>
                            </label>
                            <label class="delice-checkbox-item">
                                <input type="checkbox" class="delice-checkbox" name="allow_anonymous" value="1" <?php checked($review_settings['allow_anonymous'], true); ?>>
                                <span class="delice-checkbox-label"><?php _e('Allow anonymous reviews', 'delice-recipe-manager'); ?></span>
                            </label>
                        </div>
                        
                        <div class="delice-form-group">
                            <label class="delice-label"><?php _e('Max Image Size (MB)', 'delice-recipe-manager'); ?></label>
                            <input type="number" class="delice-input" name="max_image_size" value="<?php echo intval($review_settings['max_image_size']); ?>" min="1" max="10">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="delice-btn delice-btn-primary"><?php _e('Save Review Settings', 'delice-recipe-manager'); ?></button>
            </form>
        </div>
        
        <!-- MIGRATION PANE -->
        <div id="migration" class="delice-admin-pane">
            <div class="delice-page-header">
                <div>
                    <h1 class="delice-page-title"><?php _e('Migration', 'delice-recipe-manager'); ?></h1>
                    <p class="delice-page-subtitle"><?php _e('Convert recipes to WordPress posts', 'delice-recipe-manager'); ?></p>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="delice-stats-grid">
                <div class="delice-stat-card delice-stat-card--primary">
                    <div class="delice-stat-value"><?php echo $migration_stats['delice_recipes']; ?></div>
                    <div class="delice-stat-label"><?php _e('Total Recipes', 'delice-recipe-manager'); ?></div>
                </div>
                <div class="delice-stat-card delice-stat-card--success">
                    <div class="delice-stat-value"><?php echo $migration_stats['migrated_recipes']; ?></div>
                    <div class="delice-stat-label"><?php _e('Migrated', 'delice-recipe-manager'); ?></div>
                </div>
                <div class="delice-stat-card delice-stat-card--warning">
                    <div class="delice-stat-value"><?php echo $migration_stats['pending_migration']; ?></div>
                    <div class="delice-stat-label"><?php _e('Pending', 'delice-recipe-manager'); ?></div>
                </div>
            </div>
            
            <?php if ($migration_stats['pending_migration'] > 0): ?>
            <div class="delice-section">
                <div class="delice-section-header">
                    <h3 class="delice-section-title"><?php _e('Migration Actions', 'delice-recipe-manager'); ?></h3>
                    <p class="delice-section-desc"><?php _e('Convert your recipes to standard WordPress posts', 'delice-recipe-manager'); ?></p>
                </div>
                
                <div style="display: flex; gap: 12px; margin-bottom: 20px;">
                    <a href="<?php echo admin_url('admin.php?page=delice-recipe-migration'); ?>" class="delice-btn delice-btn-primary"><?php _e('Open Migration Tool', 'delice-recipe-manager'); ?></a>
                    <a href="<?php echo admin_url('edit.php?post_type=delice_recipe'); ?>" class="delice-btn delice-btn-secondary"><?php _e('View All Recipes', 'delice-recipe-manager'); ?></a>
                </div>
            </div>
            <?php else: ?>
            <div class="delice-section">
                <div class="delice-section-header">
                    <h3 class="delice-section-title"><?php _e('Migration Complete', 'delice-recipe-manager'); ?></h3>
                </div>
                <p><?php _e('All recipes have been migrated successfully!', 'delice-recipe-manager'); ?></p>
                <a href="<?php echo admin_url('edit.php'); ?>" class="delice-btn delice-btn-primary"><?php _e('View Migrated Posts', 'delice-recipe-manager'); ?></a>
            </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>
