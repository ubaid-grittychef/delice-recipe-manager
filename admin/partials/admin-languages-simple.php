<?php
/**
 * Simple Clean Language Page - ONE Language Selector
 */
if (!defined('ABSPATH')) exit;

// Handle form submission
if (isset($_POST['delice_language_settings_submit'])) {
    check_admin_referer('delice_language_settings_nonce');
    
    // Save selected language
    $selected_language = isset($_POST['selected_language']) ? sanitize_text_field($_POST['selected_language']) : 'en_US';
    update_option('delice_recipe_selected_language', $selected_language);
    
    // Save translations for the selected language
    $translations = array();
    $fields = array('ingredients', 'instructions', 'servings', 'prep_time', 'cook_time', 'total_time', 'difficulty', 'calories', 'notes', 'faqs', 'print_button', 'rating', 'reviews', 'submitted_by', 'tested_by');
    
    foreach ($fields as $field) {
        if (isset($_POST["translation_{$field}"])) {
            $translations[$field] = sanitize_text_field($_POST["translation_{$field}"]);
        }
    }
    
    update_option("delice_recipe_translations_{$selected_language}", $translations);
    
    echo '<div class="delice-notice delice-notice-success"><p>' . __('Language settings saved successfully!', 'delice-recipe-manager') . '</p></div>';
}

// Get current settings
$selected_language = get_option('delice_recipe_selected_language', 'en_US');
$translations = get_option("delice_recipe_translations_{$selected_language}", array());

// Available languages
$languages = array(
    'en_US' => '🇺🇸 English (US)',
    'en_GB' => '🇬🇧 English (UK)',
    'fr_FR' => '🇫🇷 French',
    'es_ES' => '🇪🇸 Spanish',
    'de_DE' => '🇩🇪 German',
    'it_IT' => '🇮🇹 Italian',
    'pt_BR' => '🇧🇷 Portuguese',
    'ja' => '🇯🇵 Japanese',
    'zh_CN' => '🇨🇳 Chinese',
    'ru_RU' => '🇷🇺 Russian',
    'ar' => '🇸🇦 Arabic',
);

// Translation fields
$translation_fields = array(
    'ingredients' => 'Ingredients',
    'instructions' => 'Instructions',
    'servings' => 'Servings',
    'prep_time' => 'Prep Time',
    'cook_time' => 'Cook Time',
    'total_time' => 'Total Time',
    'difficulty' => 'Difficulty',
    'calories' => 'Calories',
    'notes' => 'Notes',
    'faqs' => 'FAQs',
    'print_button' => 'Print Button',
    'rating' => 'Rating',
    'reviews' => 'Reviews',
    'submitted_by' => 'Submitted By',
    'tested_by' => 'Tested By'
);
?>

<div class="wrap delice-settings-wrap">
    <h1><?php _e('Language Settings', 'delice-recipe-manager'); ?></h1>
    <p class="description"><?php _e('Select your site language and customize recipe labels', 'delice-recipe-manager'); ?></p>

    <form method="post" action="">
        <?php wp_nonce_field('delice_language_settings_nonce'); ?>

        <!-- Language Selector -->
        <div class="delice-card" style="margin-top:20px;">
            <h2><?php _e('Select Language', 'delice-recipe-manager'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Site Language', 'delice-recipe-manager'); ?></th>
                    <td>
                        <select name="selected_language" id="language-selector" class="regular-text" onchange="this.form.submit()">
                            <?php foreach ($languages as $code => $name): ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($selected_language, $code); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Choose the language for your recipe labels', 'delice-recipe-manager'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Translations -->
        <div class="delice-card" style="margin-top:20px;">
            <h2><?php echo esc_html($languages[$selected_language]); ?> - <?php _e('Custom Labels', 'delice-recipe-manager'); ?></h2>
            <p class="description"><?php _e('Customize the text labels that appear in your recipes', 'delice-recipe-manager'); ?></p>
            
            <table class="form-table" style="margin-top:20px;">
                <?php foreach ($translation_fields as $key => $label): ?>
                <tr>
                    <th scope="row">
                        <label for="translation_<?php echo esc_attr($key); ?>">
                            <?php echo esc_html($label); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" 
                               name="translation_<?php echo esc_attr($key); ?>" 
                               id="translation_<?php echo esc_attr($key); ?>" 
                               value="<?php echo esc_attr($translations[$key] ?? ''); ?>" 
                               class="regular-text"
                               placeholder="<?php echo esc_attr($label); ?>">
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <p class="submit">
            <button type="submit" name="delice_language_settings_submit" class="button button-primary button-large">
                <?php _e('Save Language Settings', 'delice-recipe-manager'); ?>
            </button>
        </p>
    </form>
</div>

<style>
.delice-settings-wrap {
    max-width: 900px;
}

.delice-card {
    background: white;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
    padding: 20px 30px;
    margin-bottom: 20px;
}

.delice-card h2 {
    margin: 0 0 15px 0;
    font-size: 18px;
    font-weight: 600;
}

.delice-notice {
    border-left: 4px solid #00a32a;
    background: #fff;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
    margin: 20px 0;
    padding: 12px;
}

.delice-notice-success {
    border-left-color: #00a32a;
}

.delice-notice p {
    margin: 0;
    font-weight: 500;
}

#language-selector {
    font-size: 16px;
    padding: 8px;
}
</style>
<?php
