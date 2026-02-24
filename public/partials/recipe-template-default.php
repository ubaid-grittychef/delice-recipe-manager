
<?php
/**
 * Default recipe card partial - Enhanced with improved review section
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Auto-hide title if this is a singular recipe page to avoid duplication
$hide_title = is_singular('delice_recipe');

// Check if reviews feature is enabled
$reviews_enabled = get_option('delice_recipe_reviews_enabled', true);

// Get difficulty labels and colors
$difficulty_labels = array(
    'easy' => __('Easy', 'delice-recipe-manager'),
    'medium' => __('Medium', 'delice-recipe-manager'),
    'hard' => __('Hard', 'delice-recipe-manager'),
);

$difficulty_colors = array(
    'easy' => '#22c55e',
    'medium' => '#f59e0b', 
    'hard' => '#ef4444',
);

// Get attribution settings
$attribution_settings = get_option('delice_recipe_attribution_settings', array(
    'kitchen_name' => '',
    'kitchen_url' => '',
    'show_submitted_by' => true,
    'show_tested_by' => true,
));
?>

<div class="delice-recipe-card">
<div class="delice-recipe-container" data-recipe-id="<?php echo esc_attr($recipe_id); ?>">
  <!-- Header -->
  <header class="delice-recipe-header">
    <?php if (!$hide_title) : ?>
      <h2 class="delice-recipe-title"><?php echo esc_html( get_the_title( $recipe_id ) ); ?></h2>
    <?php endif; ?>
    
    <?php 
    // Get display options to respect settings
    $display_options = get_option('delice_recipe_display_options', array('show_image' => true));
    ?>
    
    <?php if ( !empty($display_options['show_image']) && has_post_thumbnail( $recipe_id ) ) : ?>
      <div class="delice-recipe-image-wrapper">
        <?php echo get_the_post_thumbnail( $recipe_id, 'large', [
          'class'   => 'delice-recipe-image',
          'loading' => 'lazy',
        ] ); ?>
      </div>
    <?php endif; ?>
  </header>
    
  <!-- Attribution - Modern Card Design -->
  <?php if ( $attribution_settings['show_submitted_by'] || $attribution_settings['show_tested_by'] ) : ?>
    <div class="delice-recipe-attribution-section">
      <div class="delice-recipe-attribution-card">
        <?php 
        if ( $attribution_settings['show_submitted_by'] ) : 
          $author = get_the_author_meta('display_name', get_post_field('post_author', $recipe_id));
        ?>
          <div class="delice-attribution-item">
            <div class="delice-attribution-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
            </div>
            <div class="delice-attribution-content">
              <span class="delice-attribution-label">Recipe by</span>
              <span class="delice-attribution-value"><?php echo esc_html($author); ?></span>
            </div>
          </div>
        <?php endif; ?>
        
        <?php if ( $attribution_settings['show_tested_by'] && !empty($attribution_settings['kitchen_name']) ) : ?>
          <div class="delice-attribution-divider"></div>
          <div class="delice-attribution-item">
            <div class="delice-attribution-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            </div>
            <div class="delice-attribution-content">
              <span class="delice-attribution-label">Tested by</span>
              <?php if ( !empty($attribution_settings['kitchen_url']) ) : ?>
                <a href="<?php echo esc_url($attribution_settings['kitchen_url']); ?>" class="delice-attribution-value delice-attribution-link" target="_blank" rel="noopener">
                  <?php echo esc_html($attribution_settings['kitchen_name']); ?>
                </a>
              <?php else : ?>
                <span class="delice-attribution-value"><?php echo esc_html($attribution_settings['kitchen_name']); ?></span>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Action Buttons - Properly positioned after image -->
  <div class="delice-recipe-action-buttons">
    <!-- Print Button -->
    <button class="delice-recipe-print-btn delice-recipe-action-button">
      <svg class="delice-recipe-action-button-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
        <polyline points="6,9 6,2 18,2 18,9"></polyline>
        <path d="M6,18H4a2,2,0,0,1-2-2V11a2,2,0,0,1,2-2H20a2,2,0,0,1,2,2v5a2,2,0,0,1-2,2H18"></path>
        <polyline points="6,14 18,14 18,22 6,22 6,14"></polyline>
      </svg>
      <span class="delice-recipe-action-button-text"><?php echo esc_html( $lang_texts['print'] ); ?></span>
    </button>

    <!-- Share Button with dropdown -->
    <div class="delice-recipe-share-dropdown">
      <button class="delice-recipe-share-btn delice-recipe-action-button">
        <svg class="delice-recipe-action-button-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
          <circle cx="18" cy="5" r="3"></circle>
          <circle cx="6" cy="12" r="3"></circle>
          <circle cx="18" cy="19" r="3"></circle>
          <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
          <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
        </svg>
        <span class="delice-recipe-action-button-text"><?php echo esc_html( $lang_texts['share'] ); ?></span>
      </button>
      
      <div class="delice-recipe-share-menu">
        <a href="#" class="delice-recipe-share-item" data-platform="facebook">
          <svg class="delice-recipe-share-item-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
          </svg>
          <?php esc_html_e('Facebook', 'delice-recipe-manager'); ?>
        </a>
        <a href="#" class="delice-recipe-share-item" data-platform="twitter">
          <svg class="delice-recipe-share-item-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
            <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path>
          </svg>
          <?php esc_html_e('Twitter', 'delice-recipe-manager'); ?>
        </a>
        <a href="#" class="delice-recipe-share-item" data-platform="pinterest">
          <svg class="delice-recipe-share-item-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
          </svg>
          <?php esc_html_e('Pinterest', 'delice-recipe-manager'); ?>
        </a>
        <a href="#" class="delice-recipe-share-item" data-platform="whatsapp">
          <svg class="delice-recipe-share-item-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
          </svg>
          <?php esc_html_e('WhatsApp', 'delice-recipe-manager'); ?>
        </a>
        <a href="#" class="delice-recipe-share-item" data-platform="email">
          <svg class="delice-recipe-share-item-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2z"></path>
            <polyline points="22,6 12,13 2,6"></polyline>
          </svg>
          <?php esc_html_e('Email', 'delice-recipe-manager'); ?>
        </a>
      </div>
    </div>

    <!-- Rate Button -->
    <?php if ($reviews_enabled) : ?>
      <button class="delice-recipe-rate-btn delice-recipe-action-button" data-action="open-rating-modal" data-recipe-id="<?php echo esc_attr($recipe_id); ?>">
        <svg class="delice-recipe-action-button-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
          <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
        </svg>
        <span class="delice-recipe-action-button-text"><?php echo esc_html( $lang_texts['rate'] ); ?></span>
      </button>
    <?php endif; ?>
  </div>

  <!-- Meta bar -->
  <div class="delice-recipe-meta">
      <?php 
      // Get language texts for UI elements
      $lang_texts = Delice_Recipe_Language::get_all_texts();
      
      // Check if recipe language matches current language
      $recipe_language = Delice_Recipe_Language::get_recipe_language($recipe_id);
      $current_language = Delice_Recipe_Language::get_current_language();
      $is_different_language = ($recipe_language !== $current_language);
      
      // Available languages for display
      $available_languages = array(
          'en_US' => 'English (US)',
          'en_GB' => 'English (UK)',
          'fr_FR' => 'French',
          'es_ES' => 'Spanish',
          'de_DE' => 'German',
          'it_IT' => 'Italian',
          'pt_BR' => 'Portuguese (Brazil)',
          'ja' => 'Japanese',
          'zh_CN' => 'Chinese (Simplified)',
          'ru_RU' => 'Russian',
          'ar' => 'Arabic',
      );
      ?>
      
      <?php if ( $servings ) : ?>
        <div class="delice-recipe-meta-item servings">
          <span class="delice-recipe-meta-label"><?php echo esc_html( $lang_texts['servings'] ); ?>:</span>
          <span class="delice-recipe-meta-value"><?php echo esc_html( $servings ); ?></span>
        </div>
      <?php endif; ?>
      <?php if ( $prep_time ) : ?>
        <div class="delice-recipe-meta-item prep-time">
          <span class="delice-recipe-meta-label"><?php echo esc_html( $lang_texts['prep_time'] ); ?>:</span>
          <span class="delice-recipe-meta-value"><?php echo esc_html( $prep_time ); ?> <?php echo esc_html( $lang_texts['min'] ); ?></span>
        </div>
      <?php endif; ?>
      <?php if ( $cook_time ) : ?>
        <div class="delice-recipe-meta-item cook-time">
          <span class="delice-recipe-meta-label"><?php echo esc_html( $lang_texts['cook_time'] ); ?>:</span>
          <span class="delice-recipe-meta-value"><?php echo esc_html( $cook_time ); ?> <?php echo esc_html( $lang_texts['min'] ); ?></span>
        </div>
      <?php endif; ?>
      <?php if ( $total_time ) : ?>
        <div class="delice-recipe-meta-item total-time">
          <span class="delice-recipe-meta-label"><?php echo esc_html( $lang_texts['total_time'] ); ?>:</span>
          <span class="delice-recipe-meta-value"><?php echo esc_html( $total_time ); ?> <?php echo esc_html( $lang_texts['min'] ); ?></span>
        </div>
      <?php endif; ?>
      <?php if ( $calories ) : ?>
        <div class="delice-recipe-meta-item calories">
          <span class="delice-recipe-meta-label"><?php echo esc_html( $lang_texts['calories'] ); ?>:</span>
          <span class="delice-recipe-meta-value"><?php echo esc_html( $calories ); ?> kcal</span>
        </div>
      <?php endif; ?>

      <!-- Difficulty with colored border -->
      <?php if ( $difficulty ) : 
        $color = esc_attr( $difficulty_colors[ $difficulty ] ?? '#000' );
        $label = esc_html( $difficulty_labels[ $difficulty ] ?? ucfirst( $difficulty ) );
      ?>
        <div class="delice-recipe-meta-item"
            >
          <span class="delice-recipe-meta-label"><?php echo esc_html( $lang_texts['difficulty'] ); ?></span>
          <span class="delice-recipe-meta-value"><?php echo $label; ?></span>
        </div>
      <?php endif; ?>
    </div>

  <!-- Ingredients -->
  <section class="delice-recipe-ingredients">
    <h3><?php echo esc_html( $lang_texts['ingredients'] ); ?></h3>
      <?php if ( ! empty( $ingredients ) && is_array( $ingredients ) ) : ?>
        <ul class="delice-recipe-ingredients-list">
          <?php foreach ( $ingredients as $ing ) : ?>
            <li class="delice-recipe-ingredient">
              <input type="checkbox" class="delice-recipe-ingredient-checkbox" id="ingredient-<?php echo esc_attr($recipe_id . '-' . sanitize_title($ing['name'] ?? '')); ?>">
              <span class="delice-recipe-ingredient-name">
                <?php echo esc_html( $ing['name'] ?? '' ); ?>
              </span>
              <?php if ( !empty( $ing['amount'] ) || !empty( $ing['unit'] ) ) : ?>
                <span class="delice-recipe-ingredient-quantity">
                  <?php echo esc_html( trim( ($ing['amount'] ?? '') . ' ' . ($ing['unit'] ?? '') ) ); ?>
                </span>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
        
        <button class="delice-recipe-copy-ingredients" type="button">
          <?php echo esc_html( $lang_texts['copy'] ); ?>
        </button>
      <?php else : ?>
        <p><?php esc_html_e( 'No ingredients available.', 'delice-recipe-manager' ); ?></p>
      <?php endif; ?>
  </section>

  <!-- Instructions -->
  <section class="delice-recipe-instructions">
      <h3><?php echo esc_html( $lang_texts['instructions'] ); ?></h3>
      <?php if ( ! empty( $instructions ) && is_array( $instructions ) ) : ?>
        <ol class="delice-recipe-instructions-list">
          <?php foreach ( $instructions as $index => $step ) : 
            // Aggressively remove ALL number patterns from start of text
            $step_text = $step['text'] ?? '';
            $step_text = preg_replace('/^(\d+[\.\)\:]\s*)+/', '', $step_text); // Remove "1. " "1) " "1: "
            $step_text = preg_replace('/^(Step\s+)?\d+[\.\)\:]\s*/i', '', $step_text); // Remove "Step 1. "
            $step_text = trim($step_text);
          ?>
            <li class="delice-recipe-instruction">
              <span class="delice-recipe-instruction-text"><?php echo esc_html( $step_text ); ?></span>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php else : ?>
        <p><?php esc_html_e( 'No instructions available.', 'delice-recipe-manager' ); ?></p>
      <?php endif; ?>
  </section>

  <!-- Notes -->
  <?php if ( ! empty( $notes ) ) : ?>
    <div class="delice-recipe-notes">
      <h4><?php echo esc_html( $lang_texts['notes'] ); ?></h4>
      <p><?php echo esc_html( $notes ); ?></p>
    </div>
  <?php endif; ?>

  <!-- Nutrition Panel -->
  <?php
    $nutrition_raw = get_post_meta( $recipe_id, '_delice_recipe_nutrition', true );
    $nutrition     = $nutrition_raw ? json_decode( wp_unslash( $nutrition_raw ), true ) : [];
  ?>
  <?php if ( ! empty( $nutrition ) ) : ?>
    <section class="delice-recipe-nutrition">
      <h4><?php echo esc_html( $lang_texts['nutrition'] ?? 'Nutrition Information' ); ?></h4>
      <div class="delice-recipe-nutrition-grid">
        <?php foreach ( $nutrition as $label => $val ) : ?>
          <div class="delice-recipe-nutrition-item">
            <span class="delice-recipe-nutrition-label"><?php echo esc_html( ucfirst( $label ) ); ?></span>
            <span class="delice-recipe-nutrition-value"><?php echo esc_html( $val ); ?>g</span>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- FAQs Section with fixed structure for JavaScript -->
  <?php if ( ! empty( $faqs ) && is_array( $faqs ) ) : ?>
    <section class="delice-recipe-faqs">
      <h2 class="delice-recipe-faqs-title">
        <?php printf( esc_html__( 'FAQ for %s', 'delice-recipe-manager' ), get_the_title( $recipe_id ) ); ?>
      </h2>
      
      <div class="delice-recipe-modern-faqs-list">
        <?php foreach ( $faqs as $index => $faq ) : ?>
          <div class="delice-recipe-modern-faq-item">
            <button 
              class="delice-recipe-modern-faq-question" 
              type="button"
              aria-expanded="false"
              data-faq-index="<?php echo esc_attr( $index ); ?>"
            >
              <span><?php echo esc_html( $faq['question'] ); ?></span>
              <span class="delice-recipe-modern-faq-toggle">+</span>
            </button>
            <div class="delice-recipe-modern-faq-answer">
              <p><?php echo esc_html( $faq['answer'] ); ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- Print/Share Buttons at Bottom -->
  <div class="delice-recipe-bottom-actions">
    <!-- Print Button -->
    <button class="delice-recipe-print-btn delice-recipe-action-button">
      <svg class="delice-recipe-action-button-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
        <polyline points="6,9 6,2 18,2 18,9"></polyline>
        <path d="M6,18H4a2,2,0,0,1-2-2V11a2,2,0,0,1,2-2H20a2,2,0,0,1,2,2v5a2,2,0,0,1-2,2H18"></path>
        <polyline points="6,14 18,14 18,22 6,22 6,14"></polyline>
      </svg>
      <span class="delice-recipe-action-button-text"><?php echo esc_html( $lang_texts['print'] ); ?></span>
    </button>

    <!-- Share Button with dropdown -->
    <div class="delice-recipe-share-dropdown">
      <button class="delice-recipe-share-btn delice-recipe-action-button">
        <svg class="delice-recipe-action-button-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
          <circle cx="18" cy="5" r="3"></circle>
          <circle cx="6" cy="12" r="3"></circle>
          <circle cx="18" cy="19" r="3"></circle>
          <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
          <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
        </svg>
        <span class="delice-recipe-action-button-text"><?php echo esc_html( $lang_texts['share'] ); ?></span>
      </button>
      
      <div class="delice-recipe-share-menu">
        <a href="#" class="delice-recipe-share-item" data-platform="facebook">
          <svg class="delice-recipe-share-item-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
          </svg>
          <?php esc_html_e('Facebook', 'delice-recipe-manager'); ?>
        </a>
        <a href="#" class="delice-recipe-share-item" data-platform="twitter">
          <svg class="delice-recipe-share-item-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
            <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path>
          </svg>
          <?php esc_html_e('Twitter', 'delice-recipe-manager'); ?>
        </a>
        <a href="#" class="delice-recipe-share-item" data-platform="pinterest">
          <svg class="delice-recipe-share-item-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
          </svg>
          <?php esc_html_e('Pinterest', 'delice-recipe-manager'); ?>
        </a>
        <a href="#" class="delice-recipe-share-item" data-platform="whatsapp">
          <svg class="delice-recipe-share-item-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
          </svg>
          <?php esc_html_e('WhatsApp', 'delice-recipe-manager'); ?>
        </a>
        <a href="#" class="delice-recipe-share-item" data-platform="email">
          <svg class="delice-recipe-share-item-icon" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2z"></path>
            <polyline points="22,6 12,13 2,6"></polyline>
          </svg>
          <?php esc_html_e('Email', 'delice-recipe-manager'); ?>
        </a>
      </div>
    </div>
  </div>

  <!-- Enhanced Reviews Section - Positioned after FAQs -->
  <?php if ($reviews_enabled) : ?>
    <section id="reviewSection-<?php echo esc_attr($recipe_id); ?>" class="delice-recipe-review-section">
      <div class="delice-recipe-review-header">
        <h3><?php _e('Rate & Review This Recipe', 'delice-recipe-manager'); ?></h3>
        <p class="delice-recipe-review-subtitle"><?php _e('Share your experience and help others make this recipe better!', 'delice-recipe-manager'); ?></p>
      </div>

      <!-- Selected Rating Display (shows after popup rating) -->
      <div class="delice-selected-rating-display">
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
        <label class="delice-recipe-rating-label"><?php _e('Your Rating:', 'delice-recipe-manager'); ?></label>
        <div class="delice-recipe-rating-stars" data-recipe-id="<?php echo esc_attr($recipe_id); ?>">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <i class="fas fa-star delice-rating-star" data-rating="<?php echo $i; ?>"></i>
          <?php endfor; ?>
        </div>
        <span class="delice-recipe-rating-text"><?php _e('Click to rate', 'delice-recipe-manager'); ?></span>
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
            placeholder="<?php esc_attr_e('Tell us about your experience with this recipe. What did you like? Any tips or modifications?', 'delice-recipe-manager'); ?>"
            rows="4"
            required
          ></textarea>
        </div>
        
        <div class="delice-recipe-review-image">
          <label for="review-image-<?php echo esc_attr($recipe_id); ?>">
            <?php _e('Add a Photo (Optional):', 'delice-recipe-manager'); ?>
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
              <span><?php _e('Choose photo or drag & drop', 'delice-recipe-manager'); ?></span>
            </div>
          </div>
        </div>
        
        <button type="submit" class="delice-recipe-review-submit">
          <i class="fas fa-paper-plane"></i>
          <?php _e('Submit Review', 'delice-recipe-manager'); ?>
        </button>
      </form>

      <!-- Success Message -->
      <div class="delice-recipe-review-success">
        <i class="fas fa-check-circle"></i>
        <p><?php _e('Thank you for your review! It has been submitted successfully.', 'delice-recipe-manager'); ?></p>
      </div>
    </section>

    <!-- Reviews Display -->
    <section id="reviewsDisplay-<?php echo esc_attr($recipe_id); ?>" class="delice-recipe-reviews-display">
      <!-- Existing reviews will be loaded here via AJAX -->
    </section>
  <?php endif; ?>

  <!-- Footer -->
  <footer class="delice-recipe-footer">
    <div class="delice-recipe-footer-meta">
      <?php
        $terms = get_the_terms( $recipe_id, 'delice_cuisine' );
        if ( !is_wp_error( $terms ) && !empty( $terms ) && is_array( $terms ) ) {
          echo '<span>' . esc_html__( 'Cuisine:', 'delice-recipe-manager' ) . ' '
               . esc_html( $terms[0]->name ) . '</span>';
        }
      ?>
    </div>
  </footer>
</div>
</div>
