
<?php
/**
 * Template for displaying recipe in elegant style - Enhanced with improved reviews
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Check if reviews feature is enabled
$reviews_enabled = get_option('delice_recipe_reviews_enabled', true);

// Get post data
$recipe_post = get_post($recipe_id);
$recipe_title = get_the_title($recipe_id);
$recipe_excerpt = get_the_excerpt($recipe_id);

// Clean up excerpt - remove placeholder brackets and shortcodes
if ($recipe_excerpt) {
    $recipe_excerpt = preg_replace('/\[.*?\]/', '', $recipe_excerpt);
    $recipe_excerpt = strip_shortcodes($recipe_excerpt);
    $recipe_excerpt = trim($recipe_excerpt);
    // Remove placeholder brackets completely
    $recipe_excerpt = str_replace(['[', ']'], '', $recipe_excerpt);
}

// Enhanced excerpt cleaning to remove bracket placeholders
function delice_clean_excerpt_elegant($excerpt) {
    if (empty($excerpt)) {
        return '';
    }
    
    // Remove bracket placeholders like {how to make}, {chicken roast recipe}, etc.
    $excerpt = preg_replace('/\{[^}]*\}/', '', $excerpt);
    
    // Remove parentheses placeholders like (recipe variation), (cooking method), etc.
    $excerpt = preg_replace('/\([^)]*recipe[^)]*\)/i', '', $excerpt);
    $excerpt = preg_replace('/\([^)]*cooking[^)]*\)/i', '', $excerpt);
    $excerpt = preg_replace('/\([^)]*method[^)]*\)/i', '', $excerpt);
    
    // Clean up extra spaces and trim
    $excerpt = preg_replace('/\s+/', ' ', $excerpt);
    $excerpt = trim($excerpt);
    
    return $excerpt;
}

// Get attribution settings
$attribution_settings = get_option('delice_recipe_attribution_settings', array());
$author_name = get_the_author_meta('display_name', $recipe_post->post_author);

// Get ingredients and instructions directly from database
$ingredients = get_post_meta($recipe_id, '_delice_recipe_ingredients', true);
$instructions = get_post_meta($recipe_id, '_delice_recipe_instructions', true);

// If ingredients are not found, create default data
if (empty($ingredients) || !is_array($ingredients)) {
    $ingredients = array(
        array('name' => 'Sample ingredient', 'amount' => '1', 'unit' => 'cup')
    );
}

// If instructions are not found, create default data
if (empty($instructions) || !is_array($instructions)) {
    $instructions = array(
        array('step' => '1', 'text' => 'Sample instruction step.')
    );
}

// FIXED: Enhanced author name logic to use custom author from settings
function delice_get_recipe_author_elegant($post_id) {
    // Get attribution settings
    $attribution_settings = get_option('delice_recipe_attribution_settings', array());
    
    // First check for custom author in recipe meta
    $custom_author = get_post_meta($post_id, '_delice_recipe_author', true);
    if (!empty($custom_author)) {
        return $custom_author;
    }
    
    // Check admin settings for default author name - THIS IS THE KEY FIX
    if (!empty($attribution_settings['default_author_name'])) {
        return $attribution_settings['default_author_name'];
    }
    
    // Check for kitchen name as author fallback
    if (!empty($attribution_settings['kitchen_name'])) {
        return $attribution_settings['kitchen_name'];
    }
    
    // Last resort - get post author but avoid showing email
    $author_id = get_post_field('post_author', $post_id);
    $author_data = get_userdata($author_id);
    
    if ($author_data) {
        // Try first + last name combination first
        if (!empty($author_data->first_name) || !empty($author_data->last_name)) {
            $full_name = trim($author_data->first_name . ' ' . $author_data->last_name);
            if (!empty($full_name)) {
                return $full_name;
            }
        }
        
        // Only use display name if it's not an email
        if (!empty($author_data->display_name) && !filter_var($author_data->display_name, FILTER_VALIDATE_EMAIL)) {
            return $author_data->display_name;
        }
        
        // Use login name instead of email as final fallback
        if (!filter_var($author_data->user_login, FILTER_VALIDATE_EMAIL)) {
            return $author_data->user_login;
        }
    }
    
    return __('Recipe Author', 'delice-recipe-manager');
}

// Get featured image
$featured_image = get_the_post_thumbnail_url($recipe_id, 'large');

// Get display options
$display_options = get_post_meta($recipe_id, '_delice_recipe_display_options', true);
$display_options = !empty($display_options) ? $display_options : array();

// Get difficulty and prep time
$difficulty = get_post_meta($recipe_id, '_delice_recipe_difficulty', true);
$prep_time = get_post_meta($recipe_id, '_delice_recipe_prep_time', true);
$servings = get_post_meta($recipe_id, '_delice_recipe_servings', true);

// Remove old language texts - using class method instead
?>

<!-- Recipe Container -->
<div class="delice-recipe-container delice-recipe-elegant" data-recipe-id="<?php echo esc_attr($recipe_id); ?>">
    <!-- Recipe Header -->
    <div class="delice-recipe-header">
        <h1 class="delice-recipe-title"><?php echo esc_html($recipe_title); ?></h1>
        
        <?php if ($recipe_excerpt): ?>
            <div class="delice-recipe-excerpt">
                <?php echo wp_kses_post(wpautop(delice_clean_excerpt_elegant($recipe_excerpt))); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($featured_image): ?>
            <div class="delice-recipe-featured-image">
                <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($recipe_title); ?>" />
            </div>
        <?php endif; ?>
    </div>

    <!-- Recipe Attribution -->
    <div class="delice-recipe-attribution">
        <?php if ($attribution_settings['show_submitted_by']): ?>
            <div class="delice-attribution-item">
                <span class="delice-attribution-label">
                    <i class="fas fa-user"></i>
                    <?php _e('Submitted by:', 'delice-recipe-manager'); ?>
                </span>
                <span class="delice-attribution-value">
                    <?php echo esc_html(delice_get_recipe_author_elegant($recipe_id)); ?>
                </span>
            </div>
        <?php endif; ?>
        
        <?php if ($attribution_settings['show_tested_by'] && !empty($attribution_settings['kitchen_name'])): ?>
            <div class="delice-attribution-item">
                <span class="delice-attribution-label">
                    <i class="fas fa-check-circle"></i>
                    <?php _e('Tested by:', 'delice-recipe-manager'); ?>
                </span>
                <span class="delice-attribution-value">
                    <?php if (!empty($attribution_settings['kitchen_url'])): ?>
                        <a href="<?php echo esc_url($attribution_settings['kitchen_url']); ?>" target="_blank" rel="noopener">
                            <?php echo esc_html($attribution_settings['kitchen_name']); ?>
                        </a>
                    <?php else: ?>
                        <?php echo esc_html($attribution_settings['kitchen_name']); ?>
                    <?php endif; ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recipe Action Buttons -->
    <div class="delice-recipe-actions">
        <button class="delice-recipe-action-btn print-recipe" data-recipe-id="<?php echo esc_attr($recipe_id); ?>">
            <i class="fas fa-print"></i>
            <span><?php echo esc_html(Delice_Recipe_Language::get_text('print', 'Print Recipe')); ?></span>
        </button>
        <button class="delice-recipe-action-btn copy-ingredients" data-recipe-id="<?php echo esc_attr($recipe_id); ?>">
            <i class="fas fa-copy"></i>
            <span><?php echo esc_html(Delice_Recipe_Language::get_text('copy', 'Copy Ingredients')); ?></span>
        </button>
        <?php if ($reviews_enabled): ?>
        <button class="delice-recipe-action-btn delice-recipe-rate-btn" data-recipe-id="<?php echo esc_attr($recipe_id); ?>">
            <i class="fas fa-star"></i>
            <span><?php echo esc_html(Delice_Recipe_Language::get_text('rate', 'Rate this Recipe')); ?></span>
        </button>
        <?php endif; ?>
    </div>

    <!-- Main Recipe Card -->
    <div class="delice-recipe-card">
        <div class="delice-recipe-elegant">
            <?php if ($display_options['show_image'] && has_post_thumbnail($recipe_id)) : ?>
                <div class="delice-recipe-elegant-image">
                    <?php echo get_the_post_thumbnail($recipe_id, 'large'); ?>
                    
                    <?php if ($display_options['show_difficulty'] && $difficulty) : ?>
                        <div class="delice-recipe-elegant-difficulty">
                            <?php echo esc_html(ucfirst($difficulty)); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="delice-recipe-elegant-meta">
                <?php if ($display_options['show_prep_time'] && $prep_time) : ?>
                    <div class="delice-recipe-elegant-meta-item">
                        <div class="delice-recipe-elegant-meta-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="delice-recipe-elegant-meta-text">
                            <span class="delice-recipe-elegant-meta-label"><?php echo esc_html(Delice_Recipe_Language::get_text('prep_time', 'Preparation')); ?></span>
                            <span class="delice-recipe-elegant-meta-value"><?php echo esc_html($prep_time); ?> <?php echo esc_html(Delice_Recipe_Language::get_text('min', 'min')); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($display_options['show_servings'] && $servings) : ?>
                    <div class="delice-recipe-elegant-meta-item">
                        <div class="delice-recipe-elegant-meta-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="delice-recipe-elegant-meta-text">
                            <span class="delice-recipe-elegant-meta-label"><?php echo esc_html(Delice_Recipe_Language::get_text('servings', 'Servings')); ?></span>
                            <span class="delice-recipe-elegant-meta-value"><?php echo esc_html($servings); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="delice-recipe-elegant-body">
                <div class="delice-recipe-elegant-sidebar">
                    <div class="delice-recipe-elegant-section">
                        <div class="delice-recipe-ingredients-header">
                            <h3 class="delice-recipe-elegant-section-title">
                                <i class="fas fa-shopping-basket"></i>
                                <?php echo esc_html(Delice_Recipe_Language::get_text('ingredients', 'Ingredients')); ?>
                            </h3>
                            
                            <?php if ($servings) : ?>
                                <div class="delice-recipe-servings-adjuster">
                                    <span class="delice-recipe-servings-display">
                                        <?php echo esc_html(Delice_Recipe_Language::get_text('servings', 'Servings')); ?>:
                                    </span>
                                    <button class="delice-recipe-servings-btn" type="button">-</button>
                                    <span class="delice-recipe-servings-value" data-base-servings="<?php echo esc_attr($servings); ?>"><?php echo esc_html($servings); ?></span>
                                    <button class="delice-recipe-servings-btn" type="button">+</button>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <ul class="delice-recipe-elegant-ingredients">
                            <?php foreach ($ingredients as $ingredient) : ?>
                                <li class="delice-recipe-elegant-ingredient delice-recipe-ingredient">
                                    <span class="delice-recipe-elegant-ingredient-name"><?php echo esc_html($ingredient['name'] ?? ''); ?></span>
                                    <?php if (!empty($ingredient['amount']) || !empty($ingredient['unit'])) : ?>
                                        <span class="delice-recipe-elegant-ingredient-quantity delice-recipe-ingredient-quantity" 
                                              data-original-amount="<?php echo esc_attr($ingredient['amount'] ?? ''); ?>" 
                                              data-unit="<?php echo esc_attr($ingredient['unit'] ?? ''); ?>">
                                            <?php echo esc_html(($ingredient['amount'] ?? '') . ' ' . ($ingredient['unit'] ?? '')); ?>
                                        </span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                
                <div class="delice-recipe-elegant-main">
                    <div class="delice-recipe-elegant-section">
                        <h3 class="delice-recipe-elegant-section-title">
                            <i class="fas fa-list-ol"></i>
                            <?php echo esc_html(Delice_Recipe_Language::get_text('instructions', 'Instructions')); ?>
                        </h3>
                        <ol class="delice-recipe-elegant-instructions">
                            <?php foreach ($instructions as $instruction) : ?>
                                <li class="delice-recipe-elegant-instruction">
                                    <span class="delice-recipe-elegant-instruction-step"><?php echo esc_html($instruction['step'] ?? ''); ?></span>
                                    <p class="delice-recipe-elegant-instruction-text"><?php echo esc_html($instruction['text'] ?? ''); ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
            </div>
            
            <!-- Rating and Review Action Buttons -->
            <div class="delice-recipe-action-buttons">
                <button type="button" class="delice-recipe-action-button" onclick="window.print()">
                    <i class="fas fa-print"></i>
                    <span><?php echo esc_html(Delice_Recipe_Language::get_text('print', 'Print')); ?></span>
                </button>
                
                <button type="button" class="delice-recipe-action-button delice-recipe-rate-btn" data-recipe-id="<?php echo esc_attr($recipe_id); ?>">
                    <i class="fas fa-star"></i>
                    <span><?php echo esc_html(Delice_Recipe_Language::get_text('rate', 'Rate this Recipe')); ?></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Enhanced Reviews Section - Positioned after main content -->
    <?php if ($reviews_enabled): ?>
    <section id="reviewSection-<?php echo esc_attr($recipe_id); ?>" class="delice-recipe-review-section delice-recipe-elegant-reviews">
        <div class="delice-recipe-review-header">
            <h3><?php _e('Share Your Culinary Experience', 'delice-recipe-manager'); ?></h3>
            <p class="delice-recipe-review-subtitle"><?php _e('Your feedback helps fellow food enthusiasts perfect this recipe.', 'delice-recipe-manager'); ?></p>
        </div>

        <!-- Selected Rating Display (shows after popup rating) -->
        <div class="delice-selected-rating-display" style="display: none;">
            <div class="delice-rating-selected-info">
                <span class="delice-rating-label"><?php _e('Your Rating:', 'delice-recipe-manager'); ?></span>
                <div class="delice-rating-stars-display">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star delice-display-star" data-rating="<?php echo $i; ?>"></i>
                    <?php endfor; ?>
                </div>
                <span class="delice-rating-text"><?php _e('Thank you for rating!', 'delice-recipe-manager'); ?></span>
            </div>
        </div>

        <!-- Rating Stars (hidden after popup rating) -->
        <div class="delice-recipe-rating-container">
            <label class="delice-recipe-rating-label"><?php _e('Rate This Recipe:', 'delice-recipe-manager'); ?></label>
            <div class="delice-recipe-rating-stars" data-recipe-id="<?php echo esc_attr($recipe_id); ?>">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star delice-rating-star" data-rating="<?php echo $i; ?>"></i>
                <?php endfor; ?>
            </div>
            <span class="delice-recipe-rating-text"><?php _e('Select your rating', 'delice-recipe-manager'); ?></span>
        </div>

        <!-- Review Form -->
        <form class="delice-recipe-review-form" data-recipe-id="<?php echo esc_attr($recipe_id); ?>">
            <div class="delice-recipe-review-comment">
                <label for="review-comment-<?php echo esc_attr($recipe_id); ?>">
                    <?php _e('Your Review:', 'delice-recipe-manager'); ?>
                </label>
                <textarea 
                    id="review-comment-<?php echo esc_attr($recipe_id); ?>"
                    name="comment" 
                    placeholder="<?php esc_attr_e('Describe your cooking experience, any modifications you made, or tips for fellow cooks...', 'delice-recipe-manager'); ?>"
                    rows="5"
                    required
                ></textarea>
            </div>
            
            <div class="delice-recipe-review-image">
                <label for="review-image-<?php echo esc_attr($recipe_id); ?>">
                    <?php _e('Add Your Photo (Optional):', 'delice-recipe-manager'); ?>
                </label>
                <div class="delice-recipe-file-upload-wrapper">
                    <input 
                        type="file" 
                        id="review-image-<?php echo esc_attr($recipe_id); ?>"
                        name="review_image" 
                        accept="image/*"
                    />
                    <div class="delice-recipe-file-upload-text">
                        <i class="fas fa-camera"></i>
                        <span><?php _e('Showcase your culinary creation', 'delice-recipe-manager'); ?></span>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="delice-recipe-review-submit">
                <i class="fas fa-paper-plane"></i>
                <?php _e('Submit Review', 'delice-recipe-manager'); ?>
            </button>
        </form>

        <!-- Success Message -->
        <div class="delice-recipe-review-success" style="display: none;">
            <i class="fas fa-check-circle"></i>
            <p><?php _e('Your review has been submitted with elegance and grace!', 'delice-recipe-manager'); ?></p>
        </div>
    </section>

    <!-- Reviews Display -->
    <section id="reviewsDisplay-<?php echo esc_attr($recipe_id); ?>" class="delice-recipe-reviews-display delice-recipe-elegant-reviews-display">
        <!-- Reviews will be loaded here via AJAX -->
    </section>
    <?php endif; ?>

    <!-- Notes Section -->
    <div class="delice-recipe-notes-section">
        <h3><?php _e('Notes', 'delice-recipe-manager'); ?></h3>
        <div class="delice-recipe-notes">
            <?php echo esc_html(get_post_meta($recipe_id, '_delice_recipe_notes', true)); ?>
        </div>
    </div>
</div>
