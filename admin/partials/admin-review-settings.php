<?php
/**
 * Modern Review Settings Page - Consistent with Plugin Theme
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Handle settings save
if (isset($_POST['submit']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'delice_recipe_review_settings')) {
    $review_settings = array(
        'reviews_enabled' => isset($_POST['reviews_enabled']) ? 1 : 0,
        'auto_approve' => isset($_POST['auto_approve']) ? 1 : 0,
        'allow_anonymous' => isset($_POST['allow_anonymous']) ? 1 : 0,
        'require_email' => isset($_POST['require_email']) ? 1 : 0,
        'allow_images' => isset($_POST['allow_images']) ? 1 : 0,
        'max_image_size' => isset($_POST['max_image_size']) ? absint($_POST['max_image_size']) : 2,
        'allow_editing' => isset($_POST['allow_editing']) ? 1 : 0,
        'edit_time_limit' => isset($_POST['edit_time_limit']) ? absint($_POST['edit_time_limit']) : 30
    );
    
    update_option('delice_recipe_review_settings', $review_settings);
    echo '<div class="delice-admin-notice delice-admin-notice-success"><p>' . esc_html__('Review settings saved successfully!', 'delice-recipe-manager') . '</p></div>';
}

// Get current settings
$review_defaults = array(
    'reviews_enabled' => 1,
    'auto_approve' => 1,
    'allow_anonymous' => 1,
    'require_email' => 1,
    'allow_images' => 0,
    'max_image_size' => 2,
    'allow_editing' => 1,
    'edit_time_limit' => 30
);
$review_settings = array_merge($review_defaults, get_option('delice_recipe_review_settings', array()));

// Get review stats
global $wpdb;
$reviews_table = $wpdb->prefix . 'delice_recipe_reviews';

// Check if table exists
$table_exists = ($wpdb->get_var($wpdb->prepare(
    "SHOW TABLES LIKE %s",
    $wpdb->esc_like($reviews_table)
)) == $reviews_table);

$stats = array(
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'average_rating' => 0
);

if ($table_exists) {
    $safe_table = esc_sql($reviews_table);
    
    $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM `{$safe_table}`");
    $stats['pending'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM `{$safe_table}` WHERE status = %s",
        'pending'
    ));
    $stats['approved'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM `{$safe_table}` WHERE status = %s",
        'approved'
    ));
    $stats['average_rating'] = $wpdb->get_var( $wpdb->prepare( "SELECT AVG(rating) FROM `{$safe_table}` WHERE status = %s", 'approved' ) );
    $stats['average_rating'] = $stats['average_rating'] ? round($stats['average_rating'], 1) : 0;
}
?>

<div class="delice-modern-wrap">
    <!-- Header -->
    <div class="delice-page-header">
        <h1 class="delice-page-title"><?php _e('Review Settings', 'delice-recipe-manager'); ?></h1>
        <p class="delice-page-subtitle"><?php _e('Configure how users can review your recipes', 'delice-recipe-manager'); ?></p>
    </div>

    <!-- Stats Cards -->
    <div class="delice-stats-grid">
        <div class="delice-stat-card">
            <div class="delice-stat-icon delice-stat-icon-blue">📊</div>
            <div class="delice-stat-content">
                <div class="delice-stat-value"><?php echo intval($stats['total']); ?></div>
                <div class="delice-stat-label"><?php _e('Total Reviews', 'delice-recipe-manager'); ?></div>
            </div>
        </div>

        <div class="delice-stat-card">
            <div class="delice-stat-icon delice-stat-icon-green">✅</div>
            <div class="delice-stat-content">
                <div class="delice-stat-value"><?php echo intval($stats['approved']); ?></div>
                <div class="delice-stat-label"><?php _e('Approved', 'delice-recipe-manager'); ?></div>
            </div>
        </div>

        <div class="delice-stat-card">
            <div class="delice-stat-icon delice-stat-icon-orange">⏳</div>
            <div class="delice-stat-content">
                <div class="delice-stat-value"><?php echo intval($stats['pending']); ?></div>
                <div class="delice-stat-label"><?php _e('Pending', 'delice-recipe-manager'); ?></div>
            </div>
        </div>

        <div class="delice-stat-card">
            <div class="delice-stat-icon delice-stat-icon-yellow">⭐</div>
            <div class="delice-stat-content">
                <div class="delice-stat-value"><?php echo number_format($stats['average_rating'], 1); ?></div>
                <div class="delice-stat-label"><?php _e('Average Rating', 'delice-recipe-manager'); ?></div>
            </div>
        </div>
    </div>

    <form method="post" action="" id="review-settings-form">
        <?php wp_nonce_field('delice_recipe_review_settings'); ?>

        <!-- General Settings -->
        <div class="delice-card">
            <div class="delice-card-header">
                <h2 class="delice-card-title"><?php _e('General Settings', 'delice-recipe-manager'); ?></h2>
                <p class="delice-card-desc"><?php _e('Control basic review functionality', 'delice-recipe-manager'); ?></p>
            </div>
            <div class="delice-card-body">
                <div class="delice-settings-group">
                    <div class="delice-setting-row">
                        <div class="delice-setting-info">
                            <div class="delice-setting-label"><?php _e('Enable Reviews', 'delice-recipe-manager'); ?></div>
                            <div class="delice-setting-desc"><?php _e('Allow users to submit reviews for recipes', 'delice-recipe-manager'); ?></div>
                        </div>
                        <div class="delice-setting-control">
                            <label class="delice-toggle-switch">
                                <input type="checkbox" name="reviews_enabled" value="1" <?php checked($review_settings['reviews_enabled'], 1); ?>>
                                <span class="delice-toggle-slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="delice-setting-row">
                        <div class="delice-setting-info">
                            <div class="delice-setting-label"><?php _e('Auto-Approve Reviews', 'delice-recipe-manager'); ?></div>
                            <div class="delice-setting-desc"><?php _e('Automatically approve new reviews without moderation', 'delice-recipe-manager'); ?></div>
                        </div>
                        <div class="delice-setting-control">
                            <label class="delice-toggle-switch">
                                <input type="checkbox" name="auto_approve" value="1" <?php checked($review_settings['auto_approve'], 1); ?>>
                                <span class="delice-toggle-slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="delice-setting-row">
                        <div class="delice-setting-info">
                            <div class="delice-setting-label"><?php _e('Allow Anonymous Reviews', 'delice-recipe-manager'); ?></div>
                            <div class="delice-setting-desc"><?php _e('Let users submit reviews without logging in', 'delice-recipe-manager'); ?></div>
                        </div>
                        <div class="delice-setting-control">
                            <label class="delice-toggle-switch">
                                <input type="checkbox" name="allow_anonymous" value="1" <?php checked($review_settings['allow_anonymous'], 1); ?>>
                                <span class="delice-toggle-slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="delice-setting-row">
                        <div class="delice-setting-info">
                            <div class="delice-setting-label"><?php _e('Require Email Address', 'delice-recipe-manager'); ?></div>
                            <div class="delice-setting-desc"><?php _e('Require email for anonymous reviews (recommended)', 'delice-recipe-manager'); ?></div>
                        </div>
                        <div class="delice-setting-control">
                            <label class="delice-toggle-switch">
                                <input type="checkbox" name="require_email" value="1" <?php checked($review_settings['require_email'], 1); ?>>
                                <span class="delice-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Settings -->
        <div class="delice-card">
            <div class="delice-card-header">
                <h2 class="delice-card-title"><?php _e('Image Settings', 'delice-recipe-manager'); ?></h2>
                <p class="delice-card-desc"><?php _e('Configure review image uploads', 'delice-recipe-manager'); ?></p>
            </div>
            <div class="delice-card-body">
                <div class="delice-settings-group">
                    <div class="delice-setting-row">
                        <div class="delice-setting-info">
                            <div class="delice-setting-label"><?php _e('Allow Image Uploads', 'delice-recipe-manager'); ?></div>
                            <div class="delice-setting-desc"><?php _e('Let users attach photos to their reviews', 'delice-recipe-manager'); ?></div>
                        </div>
                        <div class="delice-setting-control">
                            <label class="delice-toggle-switch">
                                <input type="checkbox" name="allow_images" value="1" <?php checked($review_settings['allow_images'], 1); ?>>
                                <span class="delice-toggle-slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="delice-setting-row">
                        <div class="delice-setting-info">
                            <div class="delice-setting-label"><?php _e('Maximum Image Size', 'delice-recipe-manager'); ?></div>
                            <div class="delice-setting-desc"><?php _e('Maximum file size for uploaded images (in MB)', 'delice-recipe-manager'); ?></div>
                        </div>
                        <div class="delice-setting-control">
                            <select name="max_image_size" class="delice-select">
                                <option value="1" <?php selected($review_settings['max_image_size'], 1); ?>>1 MB</option>
                                <option value="2" <?php selected($review_settings['max_image_size'], 2); ?>>2 MB</option>
                                <option value="3" <?php selected($review_settings['max_image_size'], 3); ?>>3 MB</option>
                                <option value="5" <?php selected($review_settings['max_image_size'], 5); ?>>5 MB</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Editing Settings -->
        <div class="delice-card">
            <div class="delice-card-header">
                <h2 class="delice-card-title"><?php _e('Editing Settings', 'delice-recipe-manager'); ?></h2>
                <p class="delice-card-desc"><?php _e('Allow users to edit their reviews', 'delice-recipe-manager'); ?></p>
            </div>
            <div class="delice-card-body">
                <div class="delice-settings-group">
                    <div class="delice-setting-row">
                        <div class="delice-setting-info">
                            <div class="delice-setting-label"><?php _e('Allow Review Editing', 'delice-recipe-manager'); ?></div>
                            <div class="delice-setting-desc"><?php _e('Let users edit their submitted reviews', 'delice-recipe-manager'); ?></div>
                        </div>
                        <div class="delice-setting-control">
                            <label class="delice-toggle-switch">
                                <input type="checkbox" name="allow_editing" value="1" <?php checked($review_settings['allow_editing'], 1); ?>>
                                <span class="delice-toggle-slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="delice-setting-row">
                        <div class="delice-setting-info">
                            <div class="delice-setting-label"><?php _e('Edit Time Limit', 'delice-recipe-manager'); ?></div>
                            <div class="delice-setting-desc"><?php _e('How long users can edit after posting (in minutes)', 'delice-recipe-manager'); ?></div>
                        </div>
                        <div class="delice-setting-control">
                            <select name="edit_time_limit" class="delice-select">
                                <option value="15" <?php selected($review_settings['edit_time_limit'], 15); ?>>15 minutes</option>
                                <option value="30" <?php selected($review_settings['edit_time_limit'], 30); ?>>30 minutes</option>
                                <option value="60" <?php selected($review_settings['edit_time_limit'], 60); ?>>1 hour</option>
                                <option value="1440" <?php selected($review_settings['edit_time_limit'], 1440); ?>>24 hours</option>
                                <option value="0" <?php selected($review_settings['edit_time_limit'], 0); ?>>No limit</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="delice-form-actions">
            <button type="submit" name="submit" class="delice-btn delice-btn-primary delice-btn-large">
                <span><?php _e('Save Review Settings', 'delice-recipe-manager'); ?></span>
            </button>
        </div>
    </form>
</div>

<style>
.delice-modern-wrap {
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 20px;
}

.delice-page-header {
    margin-bottom: 30px;
}

.delice-page-title {
    font-size: 32px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 8px 0;
}

.delice-page-subtitle {
    font-size: 16px;
    color: #64748b;
    margin: 0;
}

/* Stats Grid */
.delice-stats-grid {
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
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    font-size: 28px;
    flex-shrink: 0;
}

.delice-stat-icon-blue { background: #eff6ff; }
.delice-stat-icon-green { background: #f0fdf4; }
.delice-stat-icon-orange { background: #fff7ed; }
.delice-stat-icon-yellow { background: #fefce8; }

.delice-stat-content {
    flex: 1;
}

.delice-stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1;
    margin-bottom: 4px;
}

.delice-stat-label {
    font-size: 14px;
    color: #64748b;
}

/* Card Styles */
.delice-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
    overflow: hidden;
}

.delice-card-header {
    padding: 24px 30px;
    border-bottom: 1px solid #e2e8f0;
}

.delice-card-title {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 4px 0;
}

.delice-card-desc {
    font-size: 14px;
    color: #64748b;
    margin: 0;
}

.delice-card-body {
    padding: 30px;
}

/* Settings Rows */
.delice-settings-group {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.delice-setting-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
}

.delice-setting-info {
    flex: 1;
    margin-right: 20px;
}

.delice-setting-label {
    font-size: 15px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 4px;
}

.delice-setting-desc {
    font-size: 13px;
    color: #64748b;
}

.delice-setting-control {
    flex-shrink: 0;
}

/* Toggle Switch */
.delice-toggle-switch {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 26px;
}

.delice-toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.delice-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e1;
    transition: 0.3s;
    border-radius: 26px;
}

.delice-toggle-slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

input:checked + .delice-toggle-slider {
    background-color: #10b981;
}

input:checked + .delice-toggle-slider:before {
    transform: translateX(22px);
}

/* Select */
.delice-select {
    padding: 8px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 14px;
    min-width: 150px;
    background: white;
}

.delice-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Form Actions */
.delice-form-actions {
    display: flex;
    justify-content: flex-end;
    padding: 24px 30px;
    background: #f8fafc;
    border-radius: 12px;
}

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

.delice-btn-large {
    padding: 14px 32px;
    font-size: 16px;
}

/* Admin Notice */
.delice-admin-notice {
    background: white;
    border-left: 4px solid #3b82f6;
    padding: 16px 20px;
    margin: 20px 0;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.delice-admin-notice-success {
    border-left-color: #10b981;
}

.delice-admin-notice p {
    margin: 0;
    color: #1e293b;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 768px) {
    .delice-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .delice-setting-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .delice-setting-info {
        margin-right: 0;
    }
}
</style>
<?php
