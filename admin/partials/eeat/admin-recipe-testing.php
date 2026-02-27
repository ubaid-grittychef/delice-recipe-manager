<?php
/**
 * Recipe Testing Admin Page
 * 
 * @package Delice_Recipe_Manager
 * @since 1.1.0
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'delice_recipe_testing';

// Check if table exists
$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) === $table;
if (!$table_exists) {
    ?>
    <div class="wrap">
        <h1><?php _e('Recipe Testing', 'delice-recipe-manager'); ?></h1>
        <div class="notice notice-warning">
            <p><?php _e('Database tables not found. Please deactivate and reactivate the plugin.', 'delice-recipe-manager'); ?></p>
        </div>
    </div>
    <?php
    return;
}

// Get all tests
$tests = $wpdb->get_results("SELECT t.*, p.post_title FROM $table t LEFT JOIN {$wpdb->posts} p ON t.recipe_id = p.ID ORDER BY t.created_at DESC LIMIT 100");

// Get all recipes for dropdown
$recipes = get_posts(array(
    'post_type' => array('delice_recipe', 'post'),
    'posts_per_page' => -1,
    'post_status' => 'any',
    'meta_query' => array(
        'relation' => 'OR',
        array('key' => '_delice_recipe_ingredients', 'compare' => 'EXISTS'),
        array('key' => '_delice_recipe_migrated', 'compare' => 'EXISTS')
    )
));
?>

<div class="wrap">
    <h1><?php _e('Recipe Testing', 'delice-recipe-manager'); ?></h1>
    
    <div class="submissions-table-wrapper">
        <h2><?php _e('All Recipe Tests', 'delice-recipe-manager'); ?></h2>
        
        <?php if (empty($tests)): ?>
            <p><?php _e('No recipe tests yet. Tests will appear here once recipes are marked as tested.', 'delice-recipe-manager'); ?></p>
        <?php else: ?>
            <table class="submissions-table wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Recipe', 'delice-recipe-manager'); ?></th>
                        <th><?php _e('Tester', 'delice-recipe-manager'); ?></th>
                        <th><?php _e('Test Date', 'delice-recipe-manager'); ?></th>
                        <th><?php _e('Success', 'delice-recipe-manager'); ?></th>
                        <th><?php _e('Difficulty', 'delice-recipe-manager'); ?></th>
                        <th><?php _e('Status', 'delice-recipe-manager'); ?></th>
                        <th><?php _e('Actions', 'delice-recipe-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tests as $test): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($test->post_title); ?></strong><br>
                                <small>ID: <?php echo $test->recipe_id; ?></small>
                            </td>
                            <td><?php echo esc_html($test->tester_name); ?></td>
                            <td><?php echo date('M d, Y', strtotime($test->test_date)); ?></td>
                            <td>
                                <span style="color: <?php echo $test->success_rating >= 4 ? '#28a745' : '#ffc107'; ?>">
                                    <?php echo str_repeat('★', $test->success_rating); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($test->difficulty_experienced); ?></td>
                            <td>
                                <?php if ($test->verified): ?>
                                    <span class="status-badge status-approved">Verified</span>
                                <?php else: ?>
                                    <span class="status-badge status-pending">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if (!$test->verified): ?>
                                        <button class="btn-approve" onclick="approveTest(<?php echo $test->id; ?>)">Approve</button>
                                    <?php endif; ?>
                                    <button class="btn-delete" onclick="deleteTest(<?php echo $test->id; ?>)">Delete</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
function approveTest(id) {
    if (!confirm('Approve this test?')) return;
    
    jQuery.post(ajaxurl, {
        action: 'delice_approve_recipe_test',
        nonce: '<?php echo wp_create_nonce('delice_eeat_nonce'); ?>',
        id: id
    }, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert('Error: ' + (response.data.message || 'Failed to approve'));
        }
    });
}

function deleteTest(id) {
    if (!confirm('Delete this test? This cannot be undone.')) return;
    
    jQuery.post(ajaxurl, {
        action: 'delice_delete_recipe_test',
        nonce: '<?php echo wp_create_nonce('delice_eeat_nonce'); ?>',
        id: id
    }, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert('Error: ' + (response.data.message || 'Failed to delete'));
        }
    });
}
</script>
