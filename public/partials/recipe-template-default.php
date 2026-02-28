
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

// Language texts (v3.6.0 — ensure available throughout template)
$lang_texts = Delice_Recipe_Language::get_all_texts();

// Dietary badge labels (v3.6.0)
$dietary_badge_labels = array(
    'vegetarian'  => 'Vegetarian',
    'vegan'       => 'Vegan',
    'gluten-free' => 'Gluten-Free',
    'dairy-free'  => 'Dairy-Free',
    'nut-free'    => 'Nut-Free',
    'low-carb'    => 'Low-Carb',
    'keto'        => 'Keto',
    'paleo'       => 'Paleo',
);
$dietary_meta = get_post_meta( $recipe_id, '_delice_recipe_dietary', true );
$dietary_meta = is_array( $dietary_meta ) ? $dietary_meta : array();

// Rating data (v3.6.0)
$rating_avg   = floatval( get_post_meta( $recipe_id, '_delice_recipe_rating_average', true ) );
$rating_count = intval( get_post_meta( $recipe_id, '_delice_recipe_rating_count', true ) );
?>

<?php $drd_id = 'drd-' . absint( $recipe_id ); ?>
<style>
/* ── Specificity shield: ID-scoped rules beat Pixwell #main/#content overrides ── */
#<?php echo $drd_id; ?>,
#<?php echo $drd_id; ?> * { box-sizing: border-box !important; }

/* ── Global element-level resets ── */
#<?php echo $drd_id; ?> ul,
#<?php echo $drd_id; ?> ol            { list-style: none !important; padding: 0 !important; margin: 0 !important; }
#<?php echo $drd_id; ?> li            { padding: 0 !important; margin: 0 !important; background: none !important; border: none !important; }
#<?php echo $drd_id; ?> li::before,
#<?php echo $drd_id; ?> li::after     { content: none !important; display: none !important; }
#<?php echo $drd_id; ?> p             { margin-top: 0 !important; margin-bottom: 0 !important; }
#<?php echo $drd_id; ?> svg           { display: inline-block !important; max-width: none !important; overflow: visible !important; flex-shrink: 0 !important; }
#<?php echo $drd_id; ?> img           { display: block !important; max-width: 100% !important; height: auto !important; }
#<?php echo $drd_id; ?> button        { font-family: inherit !important; cursor: pointer !important; }

/* ── Ingredient list (re-declare padding after global li reset) ── */
#<?php echo $drd_id; ?> .delice-recipe-ingredient { padding: 10px 0 !important; margin: 0 !important; border: none !important; border-bottom: 1px solid #f0f0f0 !important; background: none !important; display: flex !important; align-items: center !important; gap: 10px !important; }
#<?php echo $drd_id; ?> .delice-recipe-ingredient:last-child { border-bottom: none !important; }
#<?php echo $drd_id; ?> .delice-recipe-ingredient-checkbox { position: absolute !important; opacity: 0 !important; width: 0 !important; height: 0 !important; pointer-events: none !important; }

/* ── Instructions — explicit step-num span, not CSS counter (li::before reset kills counter) ── */
#<?php echo $drd_id; ?> .delice-recipe-instruction { display: flex !important; align-items: flex-start !important; gap: 16px !important; padding: 14px 0 !important; margin: 0 !important; border: none !important; border-bottom: 1px solid #f0f0f0 !important; background: none !important; }
#<?php echo $drd_id; ?> .delice-recipe-instruction:last-child { border-bottom: none !important; }
#<?php echo $drd_id; ?> .delice-recipe-step-num { display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0 !important; width: 32px !important; height: 32px !important; min-width: 32px !important; border-radius: 50% !important; background: #f97316 !important; color: #fff !important; font-weight: 700 !important; font-size: 14px !important; margin-top: 1px !important; }
#<?php echo $drd_id; ?> .delice-recipe-instruction-text { margin: 0 !important; line-height: 1.6 !important; flex: 1 !important; min-width: 0 !important; font-size: 15px !important; padding-top: 4px !important; }

/* ── FAQ accordion ── */
#<?php echo $drd_id; ?> .delice-recipe-modern-faq-answer { display: none !important; }
#<?php echo $drd_id; ?> .delice-recipe-modern-faq-item.faq-open .delice-recipe-modern-faq-answer { display: block !important; }

/* ── Two-column grid: ingredients (left) + instructions (right) ── */
#<?php echo $drd_id; ?> .delice-recipe-grid { display: grid !important; grid-template-columns: 2fr 3fr !important; gap: 28px !important; margin-bottom: 40px !important; align-items: start !important; }
#<?php echo $drd_id; ?> .delice-recipe-grid section.delice-recipe-ingredients,
#<?php echo $drd_id; ?> .delice-recipe-grid section.delice-recipe-instructions { margin: 0 !important; }

/* ── Semantic elements (theme styles header/section/footer directly) ── */
#<?php echo $drd_id; ?> header.delice-recipe-header { display: block !important; padding: 0 !important; margin: 0 !important; border: none !important; }
#<?php echo $drd_id; ?> section.delice-recipe-nutrition,
#<?php echo $drd_id; ?> section.delice-recipe-faqs,
#<?php echo $drd_id; ?> section.delice-recipe-review-section { display: block !important; margin: 0 !important; border: none !important; }
#<?php echo $drd_id; ?> footer.delice-recipe-footer { display: block !important; margin: 0 !important; border-top: none !important; padding: 12px 0 !important; }

/* ── Responsive grid ── */
@media (max-width: 768px) {
    #<?php echo $drd_id; ?> .delice-recipe-grid { grid-template-columns: 1fr !important; gap: 20px !important; }
}
</style>
<?php
// Visible breadcrumb (v3.6.0) — skip when Yoast/RankMath handle breadcrumbs — feature toggle v3.8.0
$drd_show_breadcrumb = ! isset( $display_options['show_breadcrumb'] ) || $display_options['show_breadcrumb'];
if ( $drd_show_breadcrumb && ! defined( 'WPSEO_VERSION' ) && ! defined( 'RANK_MATH_VERSION' ) ) :
    $bc_cuisine = get_the_terms( $recipe_id, 'delice_cuisine' );
    $bc_course  = get_the_terms( $recipe_id, 'delice_course' );
    $bc_mid     = ( ! is_wp_error( $bc_cuisine ) && ! empty( $bc_cuisine ) ) ? $bc_cuisine[0]
                : ( ( ! is_wp_error( $bc_course )  && ! empty( $bc_course )  ) ? $bc_course[0] : null );
?>
<nav class="delice-recipe-breadcrumb" aria-label="Breadcrumb">
  <ol class="delice-recipe-breadcrumb-list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( $lang_texts['home'] ); ?></a></li>
    <?php if ( $bc_mid ) : ?>
      <li><a href="<?php echo esc_url( get_term_link( $bc_mid ) ); ?>"><?php echo esc_html( $bc_mid->name ); ?></a></li>
    <?php endif; ?>
    <li aria-current="page"><?php echo esc_html( get_the_title( $recipe_id ) ); ?></li>
  </ol>
</nav>
<?php endif; ?>

<div class="delice-recipe-card">
<div id="<?php echo $drd_id; ?>" class="delice-recipe-container" data-recipe-id="<?php echo esc_attr($recipe_id); ?>">
  <!-- Header -->
  <header class="delice-recipe-header">
    <?php if (!$hide_title) : ?>
      <h2 class="delice-recipe-title"><?php echo esc_html( get_the_title( $recipe_id ) ); ?></h2>
    <?php endif; ?>
    
    <?php // $display_options is extracted from template vars — already available ?>

    <?php if ( !empty($display_options['show_image']) && has_post_thumbnail( $recipe_id ) ) : ?>
      <div class="delice-recipe-image-wrapper">
        <?php
        // WebP <picture> element (v3.8.0)
        $drd_thumb_id = get_post_thumbnail_id( $recipe_id );
        $drd_img_src  = $drd_thumb_id ? wp_get_attachment_image_src( $drd_thumb_id, 'large' ) : null;
        $drd_webp_url = '';
        if ( $drd_thumb_id && $drd_img_src ) {
            $drd_meta = wp_get_attachment_metadata( $drd_thumb_id );
            $drd_base = trailingslashit( dirname( wp_get_attachment_url( $drd_thumb_id ) ) );
            if ( isset( $drd_meta['sizes']['large']['sources']['image/webp']['file'] ) ) {
                $drd_webp_url = $drd_base . $drd_meta['sizes']['large']['sources']['image/webp']['file'];
            } elseif ( isset( $drd_meta['sources']['image/webp']['file'] ) ) {
                $drd_webp_url = $drd_base . $drd_meta['sources']['image/webp']['file'];
            }
        }
        if ( $drd_img_src ) :
        ?>
        <picture>
            <?php if ( $drd_webp_url ) : ?>
            <source srcset="<?php echo esc_url( $drd_webp_url ); ?>" type="image/webp">
            <?php endif; ?>
            <img src="<?php echo esc_url( $drd_img_src[0] ); ?>"
                 class="delice-recipe-image"
                 alt="<?php echo esc_attr( get_the_title( $recipe_id ) ); ?>"
                 fetchpriority="high"
                 width="<?php echo intval( $drd_img_src[1] ); ?>"
                 height="<?php echo intval( $drd_img_src[2] ); ?>">
        </picture>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ( $rating_count > 0 && ( ! isset( $display_options['show_rating'] ) || $display_options['show_rating'] ) ) : ?>
    <div class="delice-recipe-rating-summary" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
      <div class="delice-recipe-rating-stars-display" aria-hidden="true">
        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
          <span class="delice-rating-star-display<?php echo $i <= round( $rating_avg ) ? ' filled' : ''; ?>">★</span>
        <?php endfor; ?>
      </div>
      <span class="delice-recipe-rating-score" itemprop="ratingValue"><?php echo number_format( $rating_avg, 1 ); ?></span>
      <span class="delice-recipe-rating-count">(<span itemprop="ratingCount"><?php echo $rating_count; ?></span> <?php echo esc_html( $lang_texts['ratings'] ); ?>)</span>
      <meta itemprop="bestRating" content="5"><meta itemprop="worstRating" content="1">
    </div>
    <?php endif; ?>

    <?php if ( ( ! isset( $display_options['show_dietary_badges'] ) || $display_options['show_dietary_badges'] ) && ! empty( $dietary_meta ) ) : ?>
    <div class="delice-dietary-badges">
      <?php foreach ( $dietary_meta as $diet_key ) : if ( ! isset( $dietary_badge_labels[ $diet_key ] ) ) continue; ?>
        <span class="delice-dietary-badge delice-badge--<?php echo esc_attr( $diet_key ); ?>"><?php echo esc_html( $dietary_badge_labels[ $diet_key ] ); ?></span>
      <?php endforeach; ?>
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

    <!-- Cook Mode Button (v3.6.0) — feature toggle v3.8.0 -->
    <?php if ( ! isset( $display_options['show_cook_mode'] ) || $display_options['show_cook_mode'] ) : ?>
    <div class="delice-cook-mode-wrap">
        <button class="delice-cook-mode-btn delice-recipe-action-button" type="button" aria-pressed="false">
          <span class="delice-cook-mode-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" width="16" height="16">
              <path d="M12 2c0 0-4 4-4 8a4 4 0 0 0 8 0c0-4-4-8-4-8z"/><path d="M12 10c0 0-2 2-2 4a2 2 0 0 0 4 0c0-2-2-4-2-4z"/>
            </svg>
          </span>
          <span class="delice-cook-mode-label"><?php echo esc_html( $lang_texts['cook_mode_start'] ); ?></span>
        </button>
        <span class="delice-cook-mode-tip"><?php esc_html_e( 'Keeps your screen on while you cook', 'delice-recipe-manager' ); ?></span>
    </div>
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

      <!-- Last Updated (v3.6.0) — feature toggle v3.8.0 -->
      <?php
        $drd_published = get_the_date( 'M j, Y', $recipe_id );
        $drd_updated   = get_the_modified_date( 'M j, Y', $recipe_id );
        if ( ( ! isset( $display_options['show_last_updated'] ) || $display_options['show_last_updated'] ) && $drd_updated && $drd_updated !== $drd_published ) :
      ?>
        <div class="delice-recipe-meta-item delice-recipe-updated">
          <span class="delice-recipe-meta-label"><?php echo esc_html( $lang_texts['updated'] ); ?>:</span>
          <span class="delice-recipe-meta-value"><?php echo esc_html( $drd_updated ); ?></span>
        </div>
      <?php endif; ?>
    </div>

  <!-- Affiliate disclosure (top position) — v3.8.4 -->
  <?php
  $drd_aff = class_exists( 'Delice_Affiliate_Manager' )
      ? Delice_Affiliate_Manager::inject_links( is_array( $ingredients ) ? $ingredients : array(), absint( $recipe_id ) )
      : array( 'ingredients' => $ingredients, 'has_links' => false );
  $ingredients        = $drd_aff['ingredients'];
  $drd_has_aff        = $drd_aff['has_links'];
  $drd_aff_settings   = class_exists( 'Delice_Affiliate_Manager' ) ? Delice_Affiliate_Manager::get_settings() : array();
  $drd_aff_disc_pos   = $drd_aff_settings['disclosure_pos'] ?? 'top';
  if ( $drd_has_aff && $drd_aff_disc_pos === 'top' ) {
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo Delice_Affiliate_Manager::get_disclosure_html();
  }
  ?>

  <!-- Ingredients + Instructions (two-column grid) -->
  <div class="delice-recipe-grid">

  <!-- Ingredients -->
  <div class="delice-recipe-ingredients">
    <div class="delice-recipe-panel-header">
      <h3><?php echo esc_html( $lang_texts['ingredients'] ); ?></h3>
      <?php if ( $servings ) : ?>
      <div class="delice-servings-control" role="group" aria-label="<?php echo esc_attr( $lang_texts['servings'] ); ?>">
        <button class="delice-servings-btn delice-servings-minus" type="button" aria-label="Decrease servings" disabled>−</button>
        <span class="delice-servings-value" data-base="<?php echo esc_attr( intval( $servings ) ); ?>"><?php echo esc_html( intval( $servings ) ); ?></span>
        <button class="delice-servings-btn delice-servings-plus" type="button" aria-label="Increase servings">+</button>
        <span class="delice-servings-label"><?php echo esc_html( $lang_texts['servings'] ); ?></span>
        <span class="delice-servings-live" aria-live="polite" aria-atomic="true"><?php echo esc_html( intval( $servings ) ); ?></span>
      </div>
      <?php endif; ?>
    </div>
    <div class="delice-recipe-panel-body">
      <?php if ( ! empty( $ingredients ) && is_array( $ingredients ) ) : ?>
        <ul class="delice-recipe-ingredients-list">
          <?php foreach ( $ingredients as $ing ) :
            $drd_aff_url   = $ing['affiliate_url']   ?? '';
            $drd_aff_store = $ing['affiliate_store'] ?? '';
            $drd_open_tab  = ! empty( $drd_aff_settings['open_new_tab'] );
            $drd_btn_text  = esc_html( $drd_aff_settings['button_text'] ?? 'Buy' );
            if ( ! empty( $drd_aff_store ) && ! empty( $drd_aff_settings['show_store_name'] ) ) {
                $drd_btn_text .= ' · ' . esc_html( $drd_aff_store );
            }
          ?>
            <li class="delice-recipe-ingredient<?php echo $drd_aff_url ? ' delice-recipe-ingredient--linked' : ''; ?>">
              <input type="checkbox" class="delice-recipe-ingredient-checkbox" id="ingredient-<?php echo esc_attr($recipe_id . '-' . sanitize_title($ing['name'] ?? '')); ?>">
              <span class="delice-recipe-ingredient-name">
                <?php echo esc_html( $ing['name'] ?? '' ); ?>
              </span>
              <?php if ( !empty( $ing['amount'] ) || !empty( $ing['unit'] ) ) : ?>
                <span class="delice-recipe-ingredient-quantity"
                      data-base-amount="<?php echo esc_attr( $ing['amount'] ?? '' ); ?>"
                      data-base-unit="<?php echo esc_attr( $ing['unit'] ?? '' ); ?>">
                  <?php echo esc_html( trim( ($ing['amount'] ?? '') . ' ' . ($ing['unit'] ?? '') ) ); ?>
                </span>
              <?php endif; ?>
              <?php if ( $drd_aff_url ) : ?>
                <a href="<?php echo esc_url( $drd_aff_url ); ?>"
                   class="delice-aff-btn"
                   rel="<?php echo esc_attr( Delice_Affiliate_Manager::LINK_REL ); ?>"
                   <?php echo $drd_open_tab ? 'target="_blank"' : ''; ?>
                   aria-label="<?php echo esc_attr( $drd_btn_text . ' — ' . ( $ing['name'] ?? '' ) ); ?>">
                    <?php echo $drd_btn_text; ?>
                    <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 10L10 2M5 2h5v5"/></svg>
                </a>
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
    </div>
  </div>

  <!-- Instructions -->
  <div class="delice-recipe-instructions">
    <div class="delice-recipe-panel-header">
      <h3><?php echo esc_html( $lang_texts['instructions'] ); ?></h3>
    </div>
    <div class="delice-recipe-panel-body">
      <?php if ( ! empty( $instructions ) && is_array( $instructions ) ) : ?>
        <ol class="delice-recipe-instructions-list">
          <?php foreach ( $instructions as $index => $step ) :
            $step_text = $step['text'] ?? '';
            $step_text = preg_replace( '/^(\d+[\.\)\:]\s*)+/i', '', $step_text );
            $step_text = trim( $step_text );
          ?>
            <li class="delice-recipe-instruction">
              <span class="delice-recipe-step-num" aria-hidden="true"><?php echo absint( $index + 1 ); ?></span>
              <span class="delice-recipe-instruction-text"><?php echo esc_html( $step_text ); ?></span>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php else : ?>
        <p><?php esc_html_e( 'No instructions available.', 'delice-recipe-manager' ); ?></p>
      <?php endif; ?>
    </div>
  </div>

  </div><!-- /.delice-recipe-grid -->

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
  <?php if ( ! empty( $nutrition ) && ( ! isset( $display_options['show_nutrition'] ) || $display_options['show_nutrition'] ) ) : ?>
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
      <?php if ( ! isset( $display_options['show_nutrition_disclaimer'] ) || $display_options['show_nutrition_disclaimer'] ) : ?>
        <p class="delice-recipe-nutrition-disclaimer"><?php echo esc_html( $lang_texts['nutrition_disclaimer'] ); ?></p>
      <?php endif; ?>
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

  <!-- Reviews Section -->
  <?php if ($reviews_enabled) : ?>
    <section id="reviewSection-<?php echo esc_attr($recipe_id); ?>" class="delice-recipe-review-section">
      <div class="delice-recipe-review-header">
        <div class="delice-review-header-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2M7 2v20M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/>
            </svg>
        </div>
        <h3><?php esc_html_e( 'Did You Make This Recipe?', 'delice-recipe-manager' ); ?></h3>
        <p class="delice-recipe-review-subtitle"><?php esc_html_e( "We'd love to see your creation! Share your tips, tweaks, and a photo.", 'delice-recipe-manager' ); ?></p>
      </div>

      <!-- Selected Rating Display (shows after popup rating) -->
      <div class="delice-selected-rating-display">
        <div class="delice-rating-selected-info">
          <span class="delice-rating-label"><?php esc_html_e( 'Your Rating:', 'delice-recipe-manager' ); ?></span>
          <div class="delice-rating-stars-display">
            <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
              <span class="delice-display-star" data-rating="<?php echo esc_attr( $i ); ?>">★</span>
            <?php endfor; ?>
          </div>
          <span class="delice-rating-text"><?php esc_html_e( 'Thank you for rating!', 'delice-recipe-manager' ); ?></span>
        </div>
      </div>

      <!-- Rating Stars -->
      <div class="delice-recipe-rating-container">
        <label class="delice-recipe-rating-label"><?php esc_html_e( 'Your Rating:', 'delice-recipe-manager' ); ?></label>
        <div class="delice-recipe-rating-stars" data-recipe-id="<?php echo esc_attr( $recipe_id ); ?>">
          <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
            <span class="delice-rating-star" data-rating="<?php echo esc_attr( $i ); ?>">★</span>
          <?php endfor; ?>
        </div>
        <span class="delice-recipe-rating-text"><?php esc_html_e( 'Click to rate', 'delice-recipe-manager' ); ?></span>
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
            <input type="file" id="review-image-<?php echo esc_attr( $recipe_id ); ?>" name="review_image" accept="image/*">
            <div class="delice-recipe-file-upload-text">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="width:26px;height:26px;stroke:#94a3b8">
                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                <circle cx="12" cy="13" r="4"/>
              </svg>
              <span><?php esc_html_e( 'Choose photo or drag & drop', 'delice-recipe-manager' ); ?></span>
            </div>
          </div>
        </div>
        
        <button type="submit" class="delice-recipe-review-submit">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="width:16px;height:16px;stroke:#fff">
            <line x1="22" y1="2" x2="11" y2="13"/>
            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
          </svg>
          <?php esc_html_e( 'Submit Review', 'delice-recipe-manager' ); ?>
        </button>
      </form>

      <!-- Success Message -->
      <div class="delice-recipe-review-success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="width:22px;height:22px;flex-shrink:0;stroke:#22c55e">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
        <p><?php esc_html_e( 'Thank you for your review! It has been submitted successfully.', 'delice-recipe-manager' ); ?></p>
      </div>
    </section>

    <!-- Reviews Display -->
    <section id="reviewsDisplay-<?php echo esc_attr($recipe_id); ?>" class="delice-recipe-reviews-display">
      <!-- Existing reviews will be loaded here via AJAX -->
    </section>
  <?php endif; ?>

  <!-- Related Recipes (v3.6.0) — feature toggle v3.8.0 -->
  <?php if ( ( ! isset( $display_options['show_related_recipes'] ) || $display_options['show_related_recipes'] ) && class_exists( 'Delice_Recipe_Related' ) ) :
      Delice_Recipe_Related::render( $recipe_id, $lang_texts['related_recipes'] );
  endif; ?>

  <!-- Affiliate disclosure (bottom position) — v3.8.4 -->
  <?php if ( $drd_has_aff && $drd_aff_disc_pos === 'bottom' ) {
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo Delice_Affiliate_Manager::get_disclosure_html();
  } ?>

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
