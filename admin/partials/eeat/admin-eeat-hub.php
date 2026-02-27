<?php
/**
 * E-E-A-T Hub Admin Page
 * 
 * @package Delice_Recipe_Manager
 * @since 1.1.0
 */

if (!defined('ABSPATH')) exit;

// Get statistics
global $wpdb;

// Check if tables exist
$_drm_t = $wpdb->prefix . 'delice_recipe_testing';
$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $_drm_t ) ) === $_drm_t;

if (!$table_exists) {
    ?>
    <div class="wrap">
        <h1><?php _e('E-E-A-T Features Dashboard', 'delice-recipe-manager'); ?></h1>
        <div class="notice notice-warning">
            <p><?php _e('E-E-A-T database tables not found. Please deactivate and reactivate the plugin to create the required tables.', 'delice-recipe-manager'); ?></p>
        </div>
    </div>
    <?php
    return;
}

$stats = array(
    'total_tests' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}delice_recipe_testing WHERE verified = 1") ?: 0,
    'total_cooks' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}delice_user_cooks WHERE approved = 1") ?: 0,
    'total_profiles' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}delice_author_profiles") ?: 0,
    'verified_profiles' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}delice_author_profiles WHERE verified = 1") ?: 0,
    'pending_submissions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}delice_user_cooks WHERE approved = 0") ?: 0,
    'total_endorsements' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}delice_expert_endorsements") ?: 0,
);
?>

<div class="wrap delice-eeat-dashboard">
    <h1><?php _e('E-E-A-T Features Dashboard', 'delice-recipe-manager'); ?></h1>
    
    <div class="delice-eeat-intro">
        <p><?php _e('Manage Experience, Expertise, Authoritativeness, and Trustworthiness features to improve your recipe SEO.', 'delice-recipe-manager'); ?></p>
    </div>
    
    <div class="delice-eeat-stats">
        <div class="eeat-stat-card">
            <span class="eeat-stat-value"><?php echo number_format($stats['total_tests']); ?></span>
            <span class="eeat-stat-label"><?php _e('Recipe Tests', 'delice-recipe-manager'); ?></span>
        </div>
        
        <div class="eeat-stat-card">
            <span class="eeat-stat-value"><?php echo number_format($stats['total_cooks']); ?></span>
            <span class="eeat-stat-label"><?php _e('User Cooks', 'delice-recipe-manager'); ?></span>
        </div>
        
        <div class="eeat-stat-card">
            <span class="eeat-stat-value"><?php echo number_format($stats['total_profiles']); ?></span>
            <span class="eeat-stat-label"><?php _e('Author Profiles', 'delice-recipe-manager'); ?></span>
        </div>
        
        <div class="eeat-stat-card">
            <span class="eeat-stat-value"><?php echo number_format($stats['verified_profiles']); ?></span>
            <span class="eeat-stat-label"><?php _e('Verified Authors', 'delice-recipe-manager'); ?></span>
        </div>
        
        <div class="eeat-stat-card">
            <span class="eeat-stat-value" style="color: #f39c12;"><?php echo number_format($stats['pending_submissions']); ?></span>
            <span class="eeat-stat-label"><?php _e('Pending Reviews', 'delice-recipe-manager'); ?></span>
        </div>
        
        <div class="eeat-stat-card">
            <span class="eeat-stat-value"><?php echo number_format($stats['total_endorsements']); ?></span>
            <span class="eeat-stat-label"><?php _e('Expert Endorsements', 'delice-recipe-manager'); ?></span>
        </div>
    </div>
    
    <div class="delice-eeat-quick-links" style="margin-top: 40px;">
        <h2><?php _e('Quick Actions', 'delice-recipe-manager'); ?></h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
            <div class="eeat-quick-link-card" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
                <h3><?php _e('👤 Author Profiles', 'delice-recipe-manager'); ?></h3>
                <p><?php _e('Manage author credentials and expertise', 'delice-recipe-manager'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=delice-author-profiles'); ?>" class="button button-primary"><?php _e('Manage Profiles', 'delice-recipe-manager'); ?></a>
            </div>
            
            <div class="eeat-quick-link-card" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
                <h3><?php _e('🧪 Recipe Testing', 'delice-recipe-manager'); ?></h3>
                <p><?php _e('Add and verify recipe tests', 'delice-recipe-manager'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=delice-recipe-testing'); ?>" class="button button-primary"><?php _e('Manage Tests', 'delice-recipe-manager'); ?></a>
            </div>
            
            <div class="eeat-quick-link-card" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
                <h3><?php _e('👨‍🍳 User Submissions', 'delice-recipe-manager'); ?></h3>
                <p><?php _e('Review and approve user cook submissions', 'delice-recipe-manager'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=delice-user-submissions'); ?>" class="button button-primary"><?php _e('Review Submissions', 'delice-recipe-manager'); ?></a>
            </div>
        </div>
    </div>
    
    <div style="margin-top: 40px; background: #f8f9fa; padding: 20px; border-radius: 8px;">
        <h2><?php _e('E-E-A-T Display Settings', 'delice-recipe-manager'); ?></h2>
        <p style="color: #666; margin-bottom: 20px;"><?php _e('Toggle individual E-E-A-T features on your recipe pages. All features are enabled by default.', 'delice-recipe-manager'); ?></p>
        
        <form method="post" action="options.php">
            <?php settings_fields('delice_eeat_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th colspan="2" style="background: #fff; padding: 15px; border-left: 4px solid #FF6B35;">
                        <h3 style="margin: 0; color: #1a1a1a;"><?php _e('🧪 Experience Features', 'delice-recipe-manager'); ?></h3>
                    </th>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Testing Badge', 'delice-recipe-manager'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="delice_eeat_show_testing_badge" value="1" <?php checked(get_option('delice_eeat_show_testing_badge', 1), 1); ?>>
                            <?php _e('Show recipe testing badge with success rate and test count', 'delice-recipe-manager'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('User Cook Gallery', 'delice-recipe-manager'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="delice_eeat_show_user_cooks" value="1" <?php checked(get_option('delice_eeat_show_user_cooks', 1), 1); ?>>
                            <?php _e('Display gallery of user cook submissions with photos and ratings', 'delice-recipe-manager'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('"I Made This" Button', 'delice-recipe-manager'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="delice_eeat_show_submit_button" value="1" <?php checked(get_option('delice_eeat_show_submit_button', 1), 1); ?>>
                            <?php _e('Show button allowing users to submit their cook attempts', 'delice-recipe-manager'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th colspan="2" style="background: #fff; padding: 15px; border-left: 4px solid #16a34a;">
                        <h3 style="margin: 0; color: #1a1a1a;"><?php _e('🎓 Expertise Features', 'delice-recipe-manager'); ?></h3>
                    </th>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Nutrition Expert Review', 'delice-recipe-manager'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="delice_eeat_show_nutrition_review" value="1" <?php checked(get_option('delice_eeat_show_nutrition_review', 1), 1); ?>>
                            <?php _e('Display nutritionist verification and professional review', 'delice-recipe-manager'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th colspan="2" style="background: #fff; padding: 15px; border-left: 4px solid #9333ea;">
                        <h3 style="margin: 0; color: #1a1a1a;"><?php _e('🏆 Authority Features', 'delice-recipe-manager'); ?></h3>
                    </th>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Expert Endorsements', 'delice-recipe-manager'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="delice_eeat_show_endorsements" value="1" <?php checked(get_option('delice_eeat_show_endorsements', 1), 1); ?>>
                            <?php _e('Show endorsements from culinary experts and chefs', 'delice-recipe-manager'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th colspan="2" style="background: #fff; padding: 15px; border-left: 4px solid #ffd700;">
                        <h3 style="margin: 0; color: #1a1a1a;"><?php _e('🛡️ Trust Features', 'delice-recipe-manager'); ?></h3>
                    </th>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Safety & Allergen Information', 'delice-recipe-manager'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="delice_eeat_show_safety_info" value="1" <?php checked(get_option('delice_eeat_show_safety_info', 1), 1); ?>>
                            <?php _e('Display allergen warnings, dietary tags, and food safety notes', 'delice-recipe-manager'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Save Settings', 'delice-recipe-manager')); ?>
        </form>
    </div>
</div>

<?php
// Register all granular settings
register_setting('delice_eeat_settings', 'delice_eeat_show_testing_badge');
register_setting('delice_eeat_settings', 'delice_eeat_show_user_cooks');
register_setting('delice_eeat_settings', 'delice_eeat_show_submit_button');
register_setting('delice_eeat_settings', 'delice_eeat_show_nutrition_review');
register_setting('delice_eeat_settings', 'delice_eeat_show_endorsements');
register_setting('delice_eeat_settings', 'delice_eeat_show_safety_info');
?>
