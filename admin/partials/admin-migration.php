<?php
/**
 * Modern Migration Page
 * Professional interface for migrating recipes to WordPress posts
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$migration = new Delice_Recipe_Migration();
$stats = $migration->get_migration_stats();
?>

<div class="delice-migration-wrap">
    <div class="delice-migration-header">
        <h1 class="delice-migration-title"><?php _e('Recipe Migration', 'delice-recipe-manager'); ?></h1>
        <p class="delice-migration-subtitle"><?php _e('Convert your custom recipe posts to standard WordPress posts', 'delice-recipe-manager'); ?></p>
    </div>
    
    <!-- Stats Cards -->
    <div class="delice-migration-stats">
        <div class="delice-stat-card delice-stat-primary">
            <div class="delice-stat-icon">📚</div>
            <div class="delice-stat-content">
                <div class="delice-stat-value" id="total-recipes"><?php echo intval($stats['delice_recipes']); ?></div>
                <div class="delice-stat-label"><?php _e('Total Recipes', 'delice-recipe-manager'); ?></div>
            </div>
        </div>
        
        <div class="delice-stat-card delice-stat-success">
            <div class="delice-stat-icon">✅</div>
            <div class="delice-stat-content">
                <div class="delice-stat-value" id="migrated-recipes"><?php echo intval($stats['migrated_recipes']); ?></div>
                <div class="delice-stat-label"><?php _e('Migrated', 'delice-recipe-manager'); ?></div>
            </div>
        </div>
        
        <div class="delice-stat-card delice-stat-warning">
            <div class="delice-stat-icon">⏳</div>
            <div class="delice-stat-content">
                <div class="delice-stat-value" id="pending-recipes"><?php echo intval($stats['pending_migration']); ?></div>
                <div class="delice-stat-label"><?php _e('Pending', 'delice-recipe-manager'); ?></div>
            </div>
        </div>
        
        <div class="delice-stat-card delice-stat-info">
            <div class="delice-stat-icon">📊</div>
            <div class="delice-stat-content">
                <div class="delice-stat-value"><?php echo $stats['delice_recipes'] > 0 ? round(($stats['migrated_recipes'] / $stats['delice_recipes']) * 100) : 0; ?>%</div>
                <div class="delice-stat-label"><?php _e('Completed', 'delice-recipe-manager'); ?></div>
            </div>
        </div>
    </div>
    
    <?php if ($stats['pending_migration'] > 0) : ?>
    <!-- Migration Actions -->
    <div class="delice-migration-section">
        <h2 class="delice-section-title"><?php _e('Migration Actions', 'delice-recipe-manager'); ?></h2>
        
        <div class="delice-migration-options">
            <!-- Migrate All -->
            <div class="delice-migration-option">
                <div class="delice-option-header">
                    <h3><?php _e('Migrate All Recipes', 'delice-recipe-manager'); ?></h3>
                    <p><?php printf(__('Convert all %d pending recipes to WordPress posts', 'delice-recipe-manager'), $stats['pending_migration']); ?></p>
                </div>
                <div class="delice-option-actions">
                    <button type="button" class="delice-btn delice-btn-primary" id="migrate-all-btn">
                        <span>🚀</span>
                        <span><?php _e('Migrate All Recipes', 'delice-recipe-manager'); ?></span>
                    </button>
                </div>
            </div>
            
            <!-- Migrate Selected -->
            <div class="delice-migration-option">
                <div class="delice-option-header">
                    <h3><?php _e('Migrate Selected Recipes', 'delice-recipe-manager'); ?></h3>
                    <p><?php _e('Choose specific recipes to migrate', 'delice-recipe-manager'); ?></p>
                </div>
                <div class="delice-option-actions">
                    <button type="button" class="delice-btn delice-btn-secondary" id="show-recipes-btn">
                        <span>📋</span>
                        <span><?php _e('Select Recipes', 'delice-recipe-manager'); ?></span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Recipe List (Hidden by default) -->
        <div id="recipes-list" class="delice-recipes-list" style="display: none;">
            <div class="delice-list-header">
                <h3><?php _e('Select Recipes to Migrate', 'delice-recipe-manager'); ?></h3>
                <div class="delice-list-actions">
                    <button type="button" class="delice-btn delice-btn-sm delice-btn-secondary" id="select-all-btn"><?php _e('Select All', 'delice-recipe-manager'); ?></button>
                    <button type="button" class="delice-btn delice-btn-sm delice-btn-secondary" id="deselect-all-btn"><?php _e('Deselect All', 'delice-recipe-manager'); ?></button>
                </div>
            </div>
            
            <div class="delice-recipes-grid" id="recipes-grid">
                <?php
                $unmigrated = get_posts(array(
                    'post_type' => 'delice_recipe',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        'relation' => 'OR',
                        array(
                            'key' => '_delice_migration_new_id',
                            'compare' => 'NOT EXISTS'
                        ),
                        array(
                            'key' => '_delice_migration_new_id',
                            'value' => '',
                            'compare' => '='
                        )
                    )
                ));
                
                foreach ($unmigrated as $recipe) :
                    $thumbnail = get_the_post_thumbnail_url($recipe->ID, 'thumbnail');
                ?>
                <label class="delice-recipe-card">
                    <input type="checkbox" class="delice-recipe-checkbox" value="<?php echo $recipe->ID; ?>">
                    <div class="delice-recipe-card-content">
                        <?php if ($thumbnail) : ?>
                        <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($recipe->post_title); ?>" class="delice-recipe-thumb">
                        <?php else : ?>
                        <div class="delice-recipe-thumb delice-recipe-thumb-placeholder">
                            <span>🍽️</span>
                        </div>
                        <?php endif; ?>
                        <div class="delice-recipe-info">
                            <h4><?php echo esc_html($recipe->post_title); ?></h4>
                            <span class="delice-recipe-id">ID: <?php echo $recipe->ID; ?></span>
                        </div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
            
            <div class="delice-list-footer">
                <button type="button" class="delice-btn delice-btn-primary" id="migrate-selected-btn">
                    <span><?php _e('Migrate Selected', 'delice-recipe-manager'); ?></span>
                </button>
                <button type="button" class="delice-btn delice-btn-secondary" id="cancel-selection-btn">
                    <span><?php _e('Cancel', 'delice-recipe-manager'); ?></span>
                </button>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div id="migration-progress" class="delice-migration-progress" style="display: none;">
            <div class="delice-progress-header">
                <h3 id="progress-title"><?php _e('Migrating Recipes...', 'delice-recipe-manager'); ?></h3>
                <span id="progress-text">0 / 0</span>
            </div>
            <div class="delice-progress-bar">
                <div class="delice-progress-fill" id="progress-fill" style="width: 0%"></div>
            </div>
            <div id="progress-log" class="delice-progress-log"></div>
        </div>
    </div>
    <?php else : ?>
    <!-- All Migrated -->
    <div class="delice-migration-complete">
        <div class="delice-complete-icon">🎉</div>
        <h2><?php _e('All Recipes Migrated!', 'delice-recipe-manager'); ?></h2>
        <p><?php _e('All your recipes have been successfully converted to WordPress posts.', 'delice-recipe-manager'); ?></p>
        <div class="delice-complete-actions">
            <a href="<?php echo admin_url('edit.php'); ?>" class="delice-btn delice-btn-primary">
                <?php _e('View All Posts', 'delice-recipe-manager'); ?>
            </a>
            <a href="<?php echo admin_url('edit.php?post_type=delice_recipe'); ?>" class="delice-btn delice-btn-secondary">
                <?php _e('View Original Recipes', 'delice-recipe-manager'); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($stats['migrated_recipes'] > 0) : ?>
    <!-- Migrated Recipes Info -->
    <div class="delice-migration-section">
        <h2 class="delice-section-title"><?php _e('Migrated Recipes', 'delice-recipe-manager'); ?></h2>
        <div class="delice-migrated-info">
            <p><?php printf(__('%d recipes have been migrated to WordPress posts. You can find them in:', 'delice-recipe-manager'), $stats['migrated_recipes']); ?></p>
            <div class="delice-quick-links">
                <a href="<?php echo admin_url('edit.php'); ?>" class="delice-quick-link">
                    <span>📝</span>
                    <span><?php _e('All Posts', 'delice-recipe-manager'); ?></span>
                </a>
                <a href="<?php echo admin_url('edit.php?delice_recipe_filter=recipes_only'); ?>" class="delice-quick-link">
                    <span>🍽️</span>
                    <span><?php _e('Recipe Posts Only', 'delice-recipe-manager'); ?></span>
                </a>
                <a href="<?php echo admin_url('edit.php?delice_recipe_filter=migrated_only'); ?>" class="delice-quick-link">
                    <span>✅</span>
                    <span><?php _e('Migrated Recipes', 'delice-recipe-manager'); ?></span>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* Modern Migration Page Styles */
.delice-migration-wrap {
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 20px;
}

.delice-migration-header {
    text-align: center;
    margin-bottom: 40px;
}

.delice-migration-title {
    font-size: 32px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 8px 0;
}

.delice-migration-subtitle {
    font-size: 16px;
    color: #64748b;
    margin: 0;
}

/* Stats Cards */
.delice-migration-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.delice-stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.delice-stat-icon {
    font-size: 40px;
    line-height: 1;
}

.delice-stat-content {
    flex: 1;
}

.delice-stat-value {
    font-size: 32px;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 4px;
}

.delice-stat-label {
    font-size: 14px;
    color: #64748b;
}

.delice-stat-primary .delice-stat-value { color: #3b82f6; }
.delice-stat-success .delice-stat-value { color: #10b981; }
.delice-stat-warning .delice-stat-value { color: #f59e0b; }
.delice-stat-info .delice-stat-value { color: #8b5cf6; }

/* Migration Section */
.delice-migration-section {
    background: white;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.delice-section-title {
    font-size: 24px;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 20px 0;
}

/* Migration Options */
.delice-migration-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.delice-migration-option {
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
}

.delice-option-header h3 {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 8px 0;
}

.delice-option-header p {
    color: #64748b;
    margin: 0 0 16px 0;
}

/* Recipes List */
.delice-recipes-list {
    background: #f8fafc;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}

.delice-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.delice-list-header h3 {
    margin: 0;
    font-size: 18px;
}

.delice-list-actions {
    display: flex;
    gap: 8px;
}

.delice-recipes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.delice-recipe-card {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.delice-recipe-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
}

.delice-recipe-checkbox {
    position: absolute;
    opacity: 0;
}

.delice-recipe-checkbox:checked + .delice-recipe-card-content {
    border-color: #3b82f6;
    background: #eff6ff;
}

.delice-recipe-card-content {
    display: flex;
    gap: 12px;
    align-items: center;
    border: 2px solid transparent;
    border-radius: 6px;
    padding: 8px;
    transition: all 0.2s;
}

.delice-recipe-thumb {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
    flex-shrink: 0;
}

.delice-recipe-thumb-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    font-size: 24px;
}

.delice-recipe-info {
    flex: 1;
    min-width: 0;
}

.delice-recipe-info h4 {
    margin: 0 0 4px 0;
    font-size: 14px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.delice-recipe-id {
    font-size: 12px;
    color: #64748b;
}

.delice-list-footer {
    display: flex;
    gap: 12px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

/* Progress */
.delice-migration-progress {
    background: #f8fafc;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}

.delice-progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.delice-progress-header h3 {
    margin: 0;
    font-size: 16px;
}

.delice-progress-bar {
    height: 24px;
    background: #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 16px;
}

.delice-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
    transition: width 0.3s;
}

.delice-progress-log {
    max-height: 200px;
    overflow-y: auto;
    font-size: 13px;
    color: #64748b;
}

.delice-progress-log div {
    padding: 4px 0;
}

/* Complete State */
.delice-migration-complete {
    background: white;
    border-radius: 12px;
    padding: 60px 30px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.delice-complete-icon {
    font-size: 80px;
    margin-bottom: 20px;
}

.delice-migration-complete h2 {
    font-size: 28px;
    margin: 0 0 12px 0;
}

.delice-migration-complete p {
    color: #64748b;
    margin: 0 0 30px 0;
}

.delice-complete-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
}

/* Migrated Info */
.delice-migrated-info p {
    margin: 0 0 20px 0;
    color: #64748b;
}

.delice-quick-links {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.delice-quick-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    text-decoration: none;
    color: #1e293b;
    font-weight: 600;
    transition: all 0.2s;
}

.delice-quick-link:hover {
    border-color: #3b82f6;
    background: #eff6ff;
    transform: translateY(-2px);
}

.delice-quick-link span:first-child {
    font-size: 24px;
}

/* Buttons */
.delice-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.delice-btn-primary {
    background: #3b82f6;
    color: white;
}

.delice-btn-primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.delice-btn-secondary {
    background: #f1f5f9;
    color: #1e293b;
}

.delice-btn-secondary:hover {
    background: #e2e8f0;
}

.delice-btn-sm {
    padding: 8px 16px;
    font-size: 13px;
}

/* Responsive */
@media (max-width: 768px) {
    .delice-migration-stats {
        grid-template-columns: 1fr;
    }
    
    .delice-migration-options {
        grid-template-columns: 1fr;
    }
    
    .delice-recipes-grid {
        grid-template-columns: 1fr;
    }
    
    .delice-complete-actions {
        flex-direction: column;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    
    // Show recipes list
    $('#show-recipes-btn').on('click', function() {
        $('#recipes-list').slideDown();
        $(this).prop('disabled', true);
    });
    
    // Select all
    $('#select-all-btn').on('click', function() {
        $('.delice-recipe-checkbox').prop('checked', true).trigger('change');
    });
    
    // Deselect all
    $('#deselect-all-btn').on('click', function() {
        $('.delice-recipe-checkbox').prop('checked', false).trigger('change');
    });
    
    // Cancel selection
    $('#cancel-selection-btn').on('click', function() {
        $('#recipes-list').slideUp();
        $('#show-recipes-btn').prop('disabled', false);
        $('.delice-recipe-checkbox').prop('checked', false);
    });
    
    // Migrate all
    $('#migrate-all-btn').on('click', function() {
        if (!confirm('<?php _e("Migrate all pending recipes? This will create WordPress posts for all unmigrated recipes.", "delice-recipe-manager"); ?>')) {
            return;
        }
        
        var recipeIds = [];
        $('.delice-recipe-checkbox').each(function() {
            recipeIds.push($(this).val());
        });
        
        migrateRecipes(recipeIds);
    });
    
    // Migrate selected
    $('#migrate-selected-btn').on('click', function() {
        var recipeIds = [];
        $('.delice-recipe-checkbox:checked').each(function() {
            recipeIds.push($(this).val());
        });
        
        if (recipeIds.length === 0) {
            alert('<?php _e("Please select at least one recipe to migrate.", "delice-recipe-manager"); ?>');
            return;
        }
        
        if (!confirm('<?php _e("Migrate selected recipes?", "delice-recipe-manager"); ?>')) {
            return;
        }
        
        migrateRecipes(recipeIds);
    });
    
    // Migration function
    function migrateRecipes(recipeIds) {
        $('#migration-progress').show();
        $('#progress-fill').css('width', '0%');
        $('#progress-text').text('0 / ' + recipeIds.length);
        $('#progress-log').html('');
        
        var completed = 0;
        var failed = 0;
        
        function migrateNext(index) {
            if (index >= recipeIds.length) {
                $('#progress-title').text('<?php _e("Migration Complete!", "delice-recipe-manager"); ?>');
                $('#progress-log').prepend('<div style="color: #10b981; font-weight: 600;">✅ ' + completed + ' <?php _e("recipes migrated successfully", "delice-recipe-manager"); ?></div>');
                if (failed > 0) {
                    $('#progress-log').prepend('<div style="color: #ef4444; font-weight: 600;">❌ ' + failed + ' <?php _e("recipes failed", "delice-recipe-manager"); ?></div>');
                }
                
                setTimeout(function() {
                    location.reload();
                }, 2000);
                return;
            }
            
            var recipeId = recipeIds[index];
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delice_migrate_single_recipe',
                    recipe_id: recipeId,
                    nonce: '<?php echo wp_create_nonce("delice_hybrid_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        completed++;
                        $('#progress-log').prepend('<div style="color: #10b981;">✓ Recipe ID ' + recipeId + ' migrated</div>');
                    } else {
                        failed++;
                        $('#progress-log').prepend('<div style="color: #ef4444;">✗ Recipe ID ' + recipeId + ' failed: ' + (response.data.message || 'Unknown error') + '</div>');
                    }
                },
                error: function() {
                    failed++;
                    $('#progress-log').prepend('<div style="color: #ef4444;">✗ Recipe ID ' + recipeId + ' failed: Network error</div>');
                },
                complete: function() {
                    void(completed + failed); // no-op retained for reference; counts tracked individually above
                    var progress = ((index + 1) / recipeIds.length) * 100;
                    $('#progress-fill').css('width', progress + '%');
                    $('#progress-text').text((index + 1) + ' / ' + recipeIds.length);
                    
                    // Continue with next
                    migrateNext(index + 1);
                }
            });
        }
        
        migrateNext(0);
    }
});
</script>
