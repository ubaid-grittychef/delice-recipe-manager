<?php
/**
 * Author Profiles Admin Page
 * 
 * @package Delice_Recipe_Manager
 * @since 1.1.0
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'delice_author_profiles';

// Check if table exists
$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) === $table;
if (!$table_exists) {
    ?>
    <div class="wrap">
        <h1><?php _e('Author Profiles', 'delice-recipe-manager'); ?></h1>
        <div class="notice notice-warning">
            <p><?php _e('Database tables not found. Please deactivate and reactivate the plugin.', 'delice-recipe-manager'); ?></p>
        </div>
    </div>
    <?php
    return;
}

// Get all users who can edit posts
$authors = get_users(array('who' => 'authors'));
$current_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : get_current_user_id();

// Get current profile
$profile_manager = new Delice_Author_Profile();
$profile = $profile_manager->get_profile($current_user_id);

// Handle form submission
if (isset($_POST['save_author_profile']) && wp_verify_nonce($_POST['_wpnonce'], 'save_author_profile')) {
    $profile_manager->save_profile($_POST);
    echo '<div class="notice notice-success"><p>' . __('Profile saved successfully!', 'delice-recipe-manager') . '</p></div>';
    $profile = $profile_manager->get_profile($current_user_id);
}
?>

<div class="wrap">
    <h1><?php _e('Author Profiles', 'delice-recipe-manager'); ?></h1>
    
    <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px;">
        <label for="author-select"><strong><?php _e('Select Author:', 'delice-recipe-manager'); ?></strong></label>
        <select id="author-select" onchange="window.location.href='?page=delice-author-profiles&user_id=' + this.value" style="margin-left: 10px;">
            <?php foreach ($authors as $author): ?>
                <option value="<?php echo $author->ID; ?>" <?php selected($current_user_id, $author->ID); ?>>
                    <?php echo esc_html($author->display_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <form method="post" class="delice-author-form">
        <?php wp_nonce_field('save_author_profile'); ?>
        <input type="hidden" name="user_id" value="<?php echo $current_user_id; ?>">
        
        <div class="form-row">
            <label><?php _e('Display Name', 'delice-recipe-manager'); ?></label>
            <input type="text" name="display_name" value="<?php echo esc_attr($profile['display_name']); ?>" required>
        </div>
        
        <div class="form-row">
            <label><?php _e('Bio', 'delice-recipe-manager'); ?></label>
            <textarea name="bio" rows="6"><?php echo esc_textarea($profile['bio']); ?></textarea>
            <p class="description"><?php _e('Write 200-300 words about your cooking experience', 'delice-recipe-manager'); ?></p>
        </div>
        
        <div class="form-row">
            <label><?php _e('Years of Experience', 'delice-recipe-manager'); ?></label>
            <input type="number" name="experience_years" value="<?php echo esc_attr($profile['experience_years']); ?>" min="0">
        </div>
        
        <div class="form-row">
            <label><?php _e('Credentials', 'delice-recipe-manager'); ?></label>
            <div id="credentials-list">
                <?php if (!empty($profile['credentials'])): ?>
                    <?php foreach ($profile['credentials'] as $index => $credential): ?>
                        <div class="credential-item">
                            <input type="text" name="credentials[]" value="<?php echo esc_attr($credential); ?>">
                            <button type="button" class="btn-remove" onclick="this.parentElement.remove()">Remove</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="btn-add-credential" onclick="addCredential()">+ Add Credential</button>
            <p class="description"><?php _e('e.g., "Le Cordon Bleu Graduate", "Certified Executive Chef"', 'delice-recipe-manager'); ?></p>
        </div>
        
        <div class="form-row">
            <label><?php _e('Specializations', 'delice-recipe-manager'); ?></label>
            <div id="specializations-list">
                <?php if (!empty($profile['specializations'])): ?>
                    <?php foreach ($profile['specializations'] as $index => $specialization): ?>
                        <div class="specialization-item">
                            <input type="text" name="specializations[]" value="<?php echo esc_attr($specialization); ?>">
                            <button type="button" class="btn-remove" onclick="this.parentElement.remove()">Remove</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="btn-add-specialization" onclick="addSpecialization()">+ Add Specialization</button>
            <p class="description"><?php _e('e.g., "French Cuisine", "Pastry & Desserts"', 'delice-recipe-manager'); ?></p>
        </div>
        
        <div class="form-row">
            <label>
                <input type="checkbox" name="verified" value="1" <?php checked($profile['verified'], 1); ?>>
                <?php _e('Verified Professional Chef', 'delice-recipe-manager'); ?>
            </label>
        </div>
        
        <button type="submit" name="save_author_profile" class="button button-primary button-large">
            <?php _e('Save Profile', 'delice-recipe-manager'); ?>
        </button>
    </form>
</div>

<script>
function addCredential() {
    const list = document.getElementById('credentials-list');
    const div = document.createElement('div');
    div.className = 'credential-item';
    div.innerHTML = '<input type="text" name="credentials[]" placeholder="Enter credential"><button type="button" class="btn-remove" onclick="this.parentElement.remove()">Remove</button>';
    list.appendChild(div);
}

function addSpecialization() {
    const list = document.getElementById('specializations-list');
    const div = document.createElement('div');
    div.className = 'specialization-item';
    div.innerHTML = '<input type="text" name="specializations[]" placeholder="Enter specialization"><button type="button" class="btn-remove" onclick="this.parentElement.remove()">Remove</button>';
    list.appendChild(div);
}
</script>

<style>
.credential-item, .specialization-item {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
    align-items: center;
}
.credential-item input, .specialization-item input {
    flex: 1;
}
</style>
