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
$api_key = get_option('delice_recipe_ai_api_key', '');

// Attribution settings
$attribution_defaults = array(
    'kitchen_name' => '',
    'kitchen_url' => '',
    'show_submitted_by' => true,
    'show_tested_by' => true,
    'default_author_name' => '',
);
$attribution_settings = array_merge($attribution_defaults, get_option('delice_recipe_attribution_settings', array()));


?>

<div class="delice-admin-wrap">
    <!-- Top Navigation -->
    <nav class="delice-admin-nav">
        <div class="delice-admin-nav__inner">
            <div class="delice-admin-logo">Delice Recipe Manager</div>
            <div class="delice-admin-tabs">
                <button class="delice-admin-tab active" data-pane="dashboard"><?php _e('Dashboard', 'delice-recipe-manager'); ?></button>
                <button class="delice-admin-tab" data-pane="ai-generator"><?php _e('AI Generator', 'delice-recipe-manager'); ?></button>
                <button class="delice-admin-tab" data-pane="content"><?php _e('Content', 'delice-recipe-manager'); ?></button>
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
                    
                    <div class="delice-form-group">
                        <label class="delice-label"><?php _e('Target Language', 'delice-recipe-manager'); ?></label>
                        <select class="delice-select" name="target_language">
                            <option value="english"><?php _e('English', 'delice-recipe-manager'); ?></option>
                            <option value="french"><?php _e('French', 'delice-recipe-manager'); ?></option>
                            <option value="spanish"><?php _e('Spanish', 'delice-recipe-manager'); ?></option>
                            <option value="german"><?php _e('German', 'delice-recipe-manager'); ?></option>
                        </select>
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
                        
                        <div class="delice-sw-row">
                            <span class="delice-sw-row-label"><?php _e('Auto-generate images with DALL-E 3', 'delice-recipe-manager'); ?></span>
                            <label class="delice-sw">
                                <input type="checkbox" name="delice_recipe_enable_ai_images" value="1" <?php checked(get_option('delice_recipe_enable_ai_images', false), true); ?>>
                                <span class="delice-sw-slider"></span>
                            </label>
                        </div>
                        
                        <?php submit_button(__('Save API Settings', 'delice-recipe-manager'), 'primary', 'submit', false); ?>
                    </div>
                </form>
            </div>
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
                        <div class="delice-sw-group">
                            <div class="delice-sw-row">
                                <span class="delice-sw-row-label"><?php _e('Show "Submitted by" author attribution', 'delice-recipe-manager'); ?></span>
                                <label class="delice-sw">
                                    <input type="checkbox" name="delice_recipe_attribution_settings[show_submitted_by]" value="1" <?php checked(!empty($attribution_settings['show_submitted_by']), true); ?>>
                                    <span class="delice-sw-slider"></span>
                                </label>
                            </div>
                            <div class="delice-sw-row">
                                <span class="delice-sw-row-label"><?php _e('Show "Tested by" kitchen attribution', 'delice-recipe-manager'); ?></span>
                                <label class="delice-sw">
                                    <input type="checkbox" name="delice_recipe_attribution_settings[show_tested_by]" value="1" <?php checked(!empty($attribution_settings['show_tested_by']), true); ?>>
                                    <span class="delice-sw-slider"></span>
                                </label>
                            </div>
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
                        <p class="delice-section-desc">
                            <?php printf(
                                /* translators: %s: link to languages page */
                                __( 'Manage labels and translations in the <a href="%s">Languages</a> page.', 'delice-recipe-manager' ),
                                esc_url( admin_url( 'admin.php?page=delice-recipe-languages' ) )
                            ); ?>
                        </p>
                    </div>
                </div>
                
                <?php submit_button(); ?>
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
