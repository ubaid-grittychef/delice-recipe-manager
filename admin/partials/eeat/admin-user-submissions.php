<?php
/**
 * User Submissions Admin Page
 * 
 * @package Delice_Recipe_Manager
 * @since 1.1.0
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'delice_user_cooks';

// Check if table exists
$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) === $table;
if (!$table_exists) {
    ?>
    <div class="wrap">
        <h1><?php _e('User Submissions', 'delice-recipe-manager'); ?></h1>
        <div class="notice notice-warning">
            <p><?php _e('Database tables not found. Please deactivate and reactivate the plugin.', 'delice-recipe-manager'); ?></p>
        </div>
    </div>
    <?php
    return;
}

// Get filter — sanitize and whitelist allowed values
$_allowed_filters = array( 'pending', 'approved', 'all' );
$filter = isset( $_GET['filter'] ) ? sanitize_key( $_GET['filter'] ) : 'pending';
if ( ! in_array( $filter, $_allowed_filters, true ) ) {
    $filter = 'pending';
}

// Build query using prepared statements
if ( $filter === 'pending' ) {
    $submissions = $wpdb->get_results( $wpdb->prepare(
        "SELECT c.*, p.post_title FROM {$table} c LEFT JOIN {$wpdb->posts} p ON c.recipe_id = p.ID WHERE c.approved = %d ORDER BY c.created_at DESC LIMIT 100",
        0
    ) );
} elseif ( $filter === 'approved' ) {
    $submissions = $wpdb->get_results( $wpdb->prepare(
        "SELECT c.*, p.post_title FROM {$table} c LEFT JOIN {$wpdb->posts} p ON c.recipe_id = p.ID WHERE c.approved = %d ORDER BY c.created_at DESC LIMIT 100",
        1
    ) );
} else {
    $submissions = $wpdb->get_results(
        "SELECT c.*, p.post_title FROM {$table} c LEFT JOIN {$wpdb->posts} p ON c.recipe_id = p.ID ORDER BY c.created_at DESC LIMIT 100"
    );
}

$pending_count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE approved = 0") ?: 0;
$approved_count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE approved = 1") ?: 0;
?>

<div class="wrap">
    <h1><?php _e('User Submissions', 'delice-recipe-manager'); ?></h1>
    
    <ul class="subsubsub">
        <li>
            <a href="?page=delice-user-submissions&filter=pending" <?php echo $filter === 'pending' ? 'class="current"' : ''; ?>>
                Pending <span class="count">(<?php echo $pending_count; ?>)</span>
            </a> |
        </li>
        <li>
            <a href="?page=delice-user-submissions&filter=approved" <?php echo $filter === 'approved' ? 'class="current"' : ''; ?>>
                Approved <span class="count">(<?php echo $approved_count; ?>)</span>
            </a> |
        </li>
        <li>
            <a href="?page=delice-user-submissions&filter=all" <?php echo $filter === 'all' ? 'class="current"' : ''; ?>>
                All <span class="count">(<?php echo $pending_count + $approved_count; ?>)</span>
            </a>
        </li>
    </ul>
    
    <div class="submissions-table-wrapper" style="clear: both; margin-top: 20px;">
        <?php if (empty($submissions)): ?>
            <p><?php _e('No submissions found.', 'delice-recipe-manager'); ?></p>
        <?php else: ?>
            <table class="submissions-table wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 80px;"><?php _e('Photo', 'delice-recipe-manager'); ?></th>
                        <th><?php _e('Recipe', 'delice-recipe-manager'); ?></th>
                        <th><?php _e('User', 'delice-recipe-manager'); ?></th>
                        <th><?php _e('Rating', 'delice-recipe-manager'); ?></th>
                        <th><?php _e('Comment', 'delice-recipe-manager'); ?></th>
                        <th><?php _e('Date', 'delice-recipe-manager'); ?></th>
                        <th><?php _e('Status', 'delice-recipe-manager'); ?></th>
                        <th><?php _e('Actions', 'delice-recipe-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td>
                                <?php if ($submission->photo_url): ?>
                                    <img src="<?php echo esc_url($submission->photo_url); ?>" class="submission-photo" alt="">
                                <?php else: ?>
                                    <div style="width: 60px; height: 60px; background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                        <span style="color: #999;">No photo</span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo esc_html($submission->post_title); ?></strong><br>
                                <small>ID: <?php echo $submission->recipe_id; ?></small>
                            </td>
                            <td>
                                <strong><?php echo esc_html($submission->user_name); ?></strong><br>
                                <?php if ($submission->user_email): ?>
                                    <small><?php echo esc_html($submission->user_email); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($submission->success_rating): ?>
                                    <span style="color: #ffc107;">
                                        <?php echo str_repeat('★', $submission->success_rating); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #ccc;">No rating</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($submission->modifications): ?>
                                    <?php echo esc_html(wp_trim_words($submission->modifications, 10)); ?>
                                <?php else: ?>
                                    <em>No comment</em>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($submission->created_at)); ?></td>
                            <td>
                                <?php if ($submission->approved): ?>
                                    <span class="status-badge status-approved">Approved</span>
                                <?php else: ?>
                                    <span class="status-badge status-pending">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if (!$submission->approved): ?>
                                        <button class="btn-approve" onclick="approveSubmission(<?php echo $submission->id; ?>)">Approve</button>
                                    <?php endif; ?>
                                    <button class="btn-delete" onclick="deleteSubmission(<?php echo $submission->id; ?>)">Delete</button>
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
function approveSubmission(id) {
    if (!confirm('Approve this submission?')) return;
    
    jQuery.post(ajaxurl, {
        action: 'delice_approve_user_cook',
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

function deleteSubmission(id) {
    if (!confirm('Delete this submission? This cannot be undone.')) return;
    
    jQuery.post(ajaxurl, {
        action: 'delice_delete_user_cook',
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
