<?php
/**
 * Handles recipe migration from custom post type to standard posts
 */

if (!class_exists('Delice_Recipe_Migration')) {
class Delice_Recipe_Migration {
    
    private $batch_size = 10;
    
    public function __construct() {
        // Add admin functionality for better visibility
        add_action('admin_init', array($this, 'add_admin_functionality'));
        
        // Add bulk actions to recipe admin page
        add_filter('bulk_actions-edit-delice_recipe', array($this, 'add_bulk_actions'));
        add_filter('handle_bulk_actions-edit-delice_recipe', array($this, 'handle_bulk_actions'), 10, 3);
        
        // Add individual migration meta box
        add_action('add_meta_boxes', array($this, 'add_migration_meta_box'));
    }
    
    /**
     * Add bulk migration actions to recipe admin page
     */
    public function add_bulk_actions($bulk_actions) {
        $bulk_actions['migrate_to_posts'] = __('Migrate to Posts', 'delice-recipe-manager');
        return $bulk_actions;
    }
    
    /**
     * Handle bulk migration actions
     */
    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if ($doaction !== 'migrate_to_posts') {
            return $redirect_to;
        }
        
        if (!current_user_can('manage_options')) {
            return $redirect_to;
        }
        
        $migrated = 0;
        foreach ($post_ids as $post_id) {
            $recipe = get_post($post_id);
            if ($recipe && $recipe->post_type === 'delice_recipe') {
                // Check if not already migrated
                if (!get_post_meta($post_id, '_delice_migration_new_id', true)) {
                    $new_post_id = $this->migrate_single_recipe($recipe);
                    if ($new_post_id) {
                        $migrated++;
                    }
                }
            }
        }
        
        $redirect_to = add_query_arg('bulk_migrated', $migrated, $redirect_to);
        return $redirect_to;
    }
    
    /**
     * Add migration meta box to recipe edit screen
     */
    public function add_migration_meta_box() {
        add_meta_box(
            'delice_recipe_migration',
            __('Recipe Migration', 'delice-recipe-manager'),
            array($this, 'display_migration_meta_box'),
            'delice_recipe',
            'side',
            'high'
        );
    }
    
    /**
     * Display migration meta box content
     */
    public function display_migration_meta_box($post) {
        $migrated_id = get_post_meta($post->ID, '_delice_migration_new_id', true);
        
        if ($migrated_id) {
            $migrated_post = get_post($migrated_id);
            if ($migrated_post) {
                echo '<p><strong>' . __('Status:', 'delice-recipe-manager') . '</strong> ' . __('Migrated', 'delice-recipe-manager') . '</p>';
                echo '<p><a href="' . get_edit_post_link($migrated_id) . '" target="_blank">' . __('View Migrated Post', 'delice-recipe-manager') . '</a></p>';
            } else {
                echo '<p><strong>' . __('Status:', 'delice-recipe-manager') . '</strong> ' . __('Migration broken', 'delice-recipe-manager') . '</p>';
                echo '<button type="button" class="button migrate-single-recipe" data-recipe-id="' . $post->ID . '">' . __('Re-migrate Recipe', 'delice-recipe-manager') . '</button>';
            }
        } else {
            echo '<p><strong>' . __('Status:', 'delice-recipe-manager') . '</strong> ' . __('Not migrated', 'delice-recipe-manager') . '</p>';
            echo '<button type="button" class="button button-primary migrate-single-recipe" data-recipe-id="' . $post->ID . '">' . __('Migrate to Post', 'delice-recipe-manager') . '</button>';
        }
        
        wp_nonce_field('delice_recipe_migration', 'delice_recipe_migration_nonce');
        
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.migrate-single-recipe').on('click', function() {
                var recipeId = $(this).data('recipe-id');
                var $button = $(this);
                
                $button.prop('disabled', true).text('<?php _e('Migrating...', 'delice-recipe-manager'); ?>');
                
                $.post(ajaxurl, {
                    action: 'delice_migrate_single_recipe',
                    recipe_id: recipeId,
                    nonce: '<?php echo wp_create_nonce('delice_recipe_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php _e('Migration failed', 'delice-recipe-manager'); ?>');
                        $button.prop('disabled', false).text('<?php _e('Migrate to Post', 'delice-recipe-manager'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Add admin functionality for migrated recipes
     */
    public function add_admin_functionality() {
        // Add custom column to posts list
        add_filter('manage_posts_columns', array($this, 'add_recipe_column'));
        add_action('manage_posts_custom_column', array($this, 'display_recipe_column'), 10, 2);
        
        // Add custom column to recipes list  
        add_filter('manage_delice_recipe_posts_columns', array($this, 'add_migration_column'));
        add_action('manage_delice_recipe_posts_custom_column', array($this, 'display_migration_column'), 10, 2);
        
        // Add filter dropdown to posts list
        add_action('restrict_manage_posts', array($this, 'add_recipe_filter'));
        add_filter('parse_query', array($this, 'filter_posts_by_recipe'));
        
        // Add admin notices
        add_action('admin_notices', array($this, 'show_migration_notices'));
        add_action('admin_notices', array($this, 'show_bulk_migration_notice'));
    }
    
    /**
     * Add migration column to recipes list
     */
    public function add_migration_column($columns) {
        $columns['migration_status'] = __('Migration Status', 'delice-recipe-manager');
        return $columns;
    }
    
    /**
     * Display migration status column
     */
    public function display_migration_column($column, $post_id) {
        if ($column === 'migration_status') {
            $migrated_id = get_post_meta($post_id, '_delice_migration_new_id', true);
            
            if ($migrated_id) {
                $migrated_post = get_post($migrated_id);
                if ($migrated_post) {
                    echo '<span style="color: green;">✓ ' . __('Migrated', 'delice-recipe-manager') . '</span>';
                    echo '<br><small><a href="' . get_edit_post_link($migrated_id) . '" target="_blank">' . __('View Post', 'delice-recipe-manager') . '</a></small>';
                } else {
                    echo '<span style="color: red;">✗ ' . __('Broken', 'delice-recipe-manager') . '</span>';
                }
            } else {
                echo '<span style="color: #666;">— ' . __('Not migrated', 'delice-recipe-manager') . '</span>';
            }
        }
    }
    
    /**
     * Show bulk migration notice
     */
    public function show_bulk_migration_notice() {
        if (isset($_GET['bulk_migrated'])) {
            $migrated = intval($_GET['bulk_migrated']);
            if ($migrated > 0) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . sprintf(__('%d recipes migrated successfully.', 'delice-recipe-manager'), $migrated) . '</p>';
                echo '</div>';
            }
        }
    }
    
    /**
     * Add recipe column to posts list
     */
    public function add_recipe_column($columns) {
        $columns['delice_recipe'] = __('Recipe', 'delice-recipe-manager');
        return $columns;
    }
    
    /**
     * Display content for recipe column
     */
    public function display_recipe_column($column, $post_id) {
        if ($column === 'delice_recipe') {
            $is_migrated = get_post_meta($post_id, '_delice_recipe_migrated', true);
            $has_ingredients = get_post_meta($post_id, '_delice_recipe_ingredients', true);
            
            if ($is_migrated === '1' || !empty($has_ingredients)) {
                echo '<span style="background: #0073aa; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">';
                echo __('Recipe', 'delice-recipe-manager');
                echo '</span>';
                
                // Show original recipe ID if migrated
                $original_id = get_post_meta($post_id, '_delice_recipe_original_id', true);
                if ($original_id) {
                    echo '<br><small>' . sprintf(__('Migrated from #%d', 'delice-recipe-manager'), $original_id) . '</small>';
                }
            }
        }
    }
    
    /**
     * Add filter dropdown for recipes
     */
    public function add_recipe_filter() {
        global $typenow;
        
        if ($typenow === 'post') {
            $selected = isset($_GET['delice_recipe_filter']) ? $_GET['delice_recipe_filter'] : '';
            ?>
            <select name="delice_recipe_filter">
                <option value=""><?php _e('All Posts', 'delice-recipe-manager'); ?></option>
                <option value="recipes_only" <?php selected($selected, 'recipes_only'); ?>>
                    <?php _e('Recipes Only', 'delice-recipe-manager'); ?>
                </option>
                <option value="migrated_only" <?php selected($selected, 'migrated_only'); ?>>
                    <?php _e('Migrated Recipes Only', 'delice-recipe-manager'); ?>
                </option>
            </select>
            <?php
        }
    }
    
    /**
     * Filter posts by recipe type
     */
    public function filter_posts_by_recipe($query) {
        global $pagenow;
        
        if (!is_admin() || $pagenow !== 'edit.php' || !isset($_GET['delice_recipe_filter'])) {
            return;
        }
        
        $filter = $_GET['delice_recipe_filter'];
        
        if ($filter === 'recipes_only') {
            $meta_query = array(
                'relation' => 'OR',
                array(
                    'key' => '_delice_recipe_migrated',
                    'value' => '1',
                    'compare' => '='
                ),
                array(
                    'key' => '_delice_recipe_ingredients',
                    'compare' => 'EXISTS'
                )
            );
            $query->set('meta_query', $meta_query);
        } elseif ($filter === 'migrated_only') {
            $query->set('meta_key', '_delice_recipe_migrated');
            $query->set('meta_value', '1');
        }
    }
    
    /**
     * Show admin notices about migration
     */
    public function show_migration_notices() {
        $screen = get_current_screen();
        
        // Show notice on posts list page if there are migrated recipes
        if ($screen && $screen->id === 'edit-post') {
            $stats = $this->get_migration_stats();
            
            if ($stats['migrated_recipes'] > 0) {
                ?>
                <div class="notice notice-info is-dismissible">
                    <p>
                        <?php 
                        printf(
                            __('You have %d migrated recipes in your posts. Use the "Recipes Only" filter to view them easily.', 'delice-recipe-manager'),
                            $stats['migrated_recipes']
                        ); 
                        ?>
                        <a href="<?php echo admin_url('admin.php?page=delice-recipe-migration'); ?>" class="button-secondary" style="margin-left: 10px;">
                            <?php _e('View Migration Status', 'delice-recipe-manager'); ?>
                        </a>
                    </p>
                </div>
                <?php
            }
        }
    }
    
    /**
     * Get migration statistics with better detection for ALL post statuses
     */
    public function get_migration_stats() {
        global $wpdb;
        
        // Count ALL delice_recipe posts regardless of status
        $delice_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
            'delice_recipe'
        ));
        
        $migrated_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE p.post_type = %s AND pm.meta_key = %s AND pm.meta_value = %s",
            'post',
            '_delice_recipe_migrated',
            '1'
        ));
        
        // Count recipes that have been marked as migrated (have new_id meta)
        $recipes_with_migration = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE p.post_type = %s AND pm.meta_key = %s",
            'delice_recipe',
            '_delice_migration_new_id'
        ));
        
        // Get sample migrated post IDs for verification
        $sample_migrated = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE p.post_type = %s AND pm.meta_key = %s AND pm.meta_value = %s
             LIMIT %d",
            'post',
            '_delice_recipe_migrated',
            '1',
            5
        ));
        
        // Debug info
        $debug_recipes = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, post_title, post_status FROM {$wpdb->posts} WHERE post_type = %s LIMIT %d",
            'delice_recipe',
            10
        ));
        
        return array(
            'delice_recipes' => intval($delice_count),
            'migrated_recipes' => intval($migrated_count),
            'recipes_with_migration_meta' => intval($recipes_with_migration),
            'pending_migration' => max(0, intval($delice_count) - intval($recipes_with_migration)),
            'sample_migrated' => $sample_migrated,
            'debug_recipes' => $debug_recipes
        );
    }
    
    /**
     * Create backup before migration (made public for AJAX access)
     */
    public function create_backup() {
        $backup_data = array(
            'timestamp' => current_time('mysql'),
            'recipes' => array()
        );
        
        $recipes = get_posts(array(
            'post_type' => 'delice_recipe',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($recipes as $recipe) {
            $backup_data['recipes'][] = array(
                'post' => $recipe,
                'meta' => get_post_meta($recipe->ID)
            );
        }
        
        update_option('delice_recipe_migration_backup', $backup_data);
        return true;
    }
    
    /**
     * Migrate recipes in batches
     */
    public function migrate_recipes_batch($offset = 0) {
        $recipes = get_posts(array(
            'post_type' => 'delice_recipe',
            'posts_per_page' => $this->batch_size,
            'offset' => $offset,
            'post_status' => 'any'
        ));
        
        $migrated = 0;
        
        foreach ($recipes as $recipe) {
            // Check if already migrated
            if (get_post_meta($recipe->ID, '_delice_migration_new_id', true)) {
                continue;
            }
            
            $new_post_id = $this->migrate_single_recipe($recipe);
            if ($new_post_id) {
                $migrated++;
            }
        }
        
        return $migrated;
    }
    
    /**
     * Migrate a single recipe
     */
    public function migrate_single_recipe($recipe) {
        // Create new post
        $new_post = array(
            'post_title' => $recipe->post_title,
            'post_content' => $recipe->post_content,
            'post_excerpt' => $recipe->post_excerpt,
            'post_status' => $recipe->post_status,
            'post_type' => 'post',
            'post_author' => $recipe->post_author,
            'post_date' => $recipe->post_date,
            'post_date_gmt' => $recipe->post_date_gmt,
            'comment_status' => $recipe->comment_status,
            'ping_status' => $recipe->ping_status,
            'menu_order' => $recipe->menu_order
        );
        
        $new_post_id = wp_insert_post($new_post);
        
        if (is_wp_error($new_post_id)) {
            return false;
        }
        
        // Copy all meta data
        $meta_data = get_post_meta($recipe->ID);
        foreach ($meta_data as $key => $values) {
            foreach ($values as $value) {
                add_post_meta($new_post_id, $key, maybe_unserialize($value));
            }
        }
        
        // Mark as migrated recipe
        update_post_meta($new_post_id, '_delice_recipe_migrated', '1');
        update_post_meta($new_post_id, '_delice_recipe_original_id', $recipe->ID);
        
        // Add migration reference to original
        update_post_meta($recipe->ID, '_delice_migration_new_id', $new_post_id);
        
        // Copy featured image
        if (has_post_thumbnail($recipe->ID)) {
            $thumbnail_id = get_post_thumbnail_id($recipe->ID);
            set_post_thumbnail($new_post_id, $thumbnail_id);
        }
        
        // Copy taxonomies and ensure recipe categories
        $this->copy_taxonomies($recipe->ID, $new_post_id);
        
        // Add recipe category to ensure it's identifiable
        $this->ensure_recipe_category($new_post_id);
        
        return $new_post_id;
    }
    
    /**
     * Copy custom taxonomies to standard categories/tags
     */
    private function copy_taxonomies($old_id, $new_id) {
        // Map cuisine to categories
        $cuisines = wp_get_object_terms($old_id, 'delice_cuisine');
        if ($cuisines && !is_wp_error($cuisines)) {
            $category_ids = array();
            foreach ($cuisines as $cuisine) {
                $cat = get_category_by_slug('cuisine-' . $cuisine->slug);
                if (!$cat) {
                    $cat_id = wp_create_category('Cuisine: ' . $cuisine->name);
                } else {
                    $cat_id = $cat->term_id;
                }
                $category_ids[] = $cat_id;
            }
            wp_set_post_categories($new_id, $category_ids, true);
        }
        
        // Map course and dietary to tags
        $tags = array();
        
        $courses = wp_get_object_terms($old_id, 'delice_course');
        if ($courses && !is_wp_error($courses)) {
            foreach ($courses as $course) {
                $tags[] = 'course-' . $course->slug;
            }
        }
        
        $dietary = wp_get_object_terms($old_id, 'delice_dietary');
        if ($dietary && !is_wp_error($dietary)) {
            foreach ($dietary as $diet) {
                $tags[] = 'dietary-' . $diet->slug;
            }
        }
        
        if (!empty($tags)) {
            wp_set_post_tags($new_id, $tags, true);
        }
    }
    
    /**
     * Ensure migrated recipe has a recipe category
     */
    private function ensure_recipe_category($post_id) {
        // Create or get "Recipes" category
        $recipe_cat = get_category_by_slug('recipes');
        if (!$recipe_cat) {
            $recipe_cat_id = wp_create_category('Recipes');
        } else {
            $recipe_cat_id = $recipe_cat->term_id;
        }
        
        // Add to existing categories
        $existing_cats = wp_get_post_categories($post_id);
        $existing_cats[] = $recipe_cat_id;
        wp_set_post_categories($post_id, array_unique($existing_cats));
    }
    
    /**
     * Rollback migration (made public for AJAX access)
     */
    public function rollback_migration() {
        global $wpdb;
        
        // Delete migrated posts
        $migrated_posts = get_posts(array(
            'post_type' => 'post',
            'meta_key' => '_delice_recipe_migrated',
            'meta_value' => '1',
            'posts_per_page' => -1
        ));
        
        foreach ($migrated_posts as $post) {
            wp_delete_post($post->ID, true);
        }
        
        // Clean up migration meta from original posts
        $wpdb->delete($wpdb->postmeta, array('meta_key' => '_delice_migration_new_id'));
        
        delete_option('delice_recipe_migration_backup');
    }
}
}
