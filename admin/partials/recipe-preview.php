
<?php
/**
 * Recipe preview template (admin)
 *
 * @var array $recipe_data The recipe data
 * @var int   $post_id     The recipe post ID
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Public CSS is enqueued on the AI Generator admin page via class-delice-recipe-admin.php
// so no inline <link> tag is needed here.

// 2) Ensure data arrays
if ( empty( $recipe_data['ingredients'] ) || ! is_array( $recipe_data['ingredients'] ) ) {
    $recipe_data['ingredients'] = array();
}
if ( empty( $recipe_data['instructions'] ) || ! is_array( $recipe_data['instructions'] ) ) {
    $recipe_data['instructions'] = array();
}

// 3) Admin‐only fallback from DB if needed
if ( is_admin() && $post_id > 0 ) {
    if ( empty( $recipe_data['ingredients'] ) ) {
        $ing = get_post_meta( $post_id, '_delice_recipe_ingredients', true );
        if ( is_array( $ing ) && ! empty( $ing ) ) {
            $recipe_data['ingredients'] = $ing;
        }
    }
    if ( empty( $recipe_data['instructions'] ) ) {
        $ins = get_post_meta( $post_id, '_delice_recipe_instructions', true );
        if ( is_array( $ins ) && ! empty( $ins ) ) {
            $recipe_data['instructions'] = $ins;
        }
    }
    // NEW: Get FAQs for preview
    if ( empty( $recipe_data['faqs'] ) || ! is_array( $recipe_data['faqs'] ) ) {
        $faqs = get_post_meta( $post_id, '_delice_recipe_faqs', true );
        if ( is_array( $faqs ) && ! empty( $faqs ) ) {
            $recipe_data['faqs'] = $faqs;
        } else {
            $recipe_data['faqs'] = array();
        }
    }
}

// 5) Difficulty labels & colors
$difficulty_labels = array(
    'easy'   => __( 'Easy',   'delice-recipe-manager' ),
    'medium' => __( 'Medium', 'delice-recipe-manager' ),
    'hard'   => __( 'Hard',   'delice-recipe-manager' ),
);
$difficulty_colors = array(
    'easy'   => '#28a745',
    'medium' => '#ffc107',
    'hard'   => '#dc3545',
);
$diff_label = '';
$diff_color = '';
if ( ! empty( $recipe_data['difficulty'] ) && isset( $difficulty_labels[ $recipe_data['difficulty'] ] ) ) {
    $d = $recipe_data['difficulty'];
    $diff_label = $difficulty_labels[ $d ];
    $diff_color = $difficulty_colors[ $d ];
}
?>

<div class="delice-recipe-preview-content">
  <!-- Schema Testing Tools for Admin -->
  <?php if ( is_admin() && $post_id > 0 ) : ?>
    <div style="background:#f5f5f5;border:1px solid #e0e0e0;padding:10px;margin-bottom:15px;">
      <h3><?php _e( 'Schema.org Testing', 'delice-recipe-manager' ); ?></h3>
      <p><?php _e( 'After publishing, verify your recipe schema using these tools:', 'delice-recipe-manager' ); ?></p>
      <a href="https://search.google.com/test/rich-results?url=<?php echo esc_url( get_permalink( $post_id ) ); ?>" target="_blank" class="button"><?php _e( 'Test with Google', 'delice-recipe-manager' ); ?></a>
      <a href="https://validator.schema.org/#url=<?php echo esc_url( get_permalink( $post_id ) ); ?>" target="_blank" class="button"><?php _e( 'Schema Validator', 'delice-recipe-manager' ); ?></a>
    </div>
  <?php endif; ?>

  <div class="delice-recipe-card">
    <!-- Header -->
    <div class="delice-recipe-header">
      <h2 class="delice-recipe-title"><?php echo esc_html( $recipe_data['title'] ); ?></h2>
      <?php if ( ! empty( $recipe_data['rating'] ) ) : ?>
      <div class="delice-recipe-rating">
        <div class="delice-recipe-stars">
          <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
            <span class="star <?php echo ( $i <= round( $recipe_data['rating'] ) ) ? 'active' : ''; ?>">
              <i class="fas fa-star"></i>
            </span>
          <?php endfor; ?>
        </div>
        <?php if ( ! empty( $recipe_data['rating_count'] ) ) : ?>
          <span class="delice-recipe-rating-count">(<?php echo esc_html( $recipe_data['rating_count'] ); ?>)</span>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Meta -->
    <div class="delice-recipe-meta">
      <?php if ( ! empty( $recipe_data['servings'] ) ) : ?>
      <div class="delice-recipe-meta-item">
        <span class="delice-recipe-meta-label"><?php _e( 'Servings', 'delice-recipe-manager' ); ?></span>
        <span class="delice-recipe-meta-value"><?php echo esc_html( $recipe_data['servings'] ); ?></span>
      </div>
      <?php endif; ?>

      <?php if ( ! empty( $recipe_data['prep_time'] ) ) : ?>
      <div class="delice-recipe-meta-item">
        <span class="delice-recipe-meta-label"><?php _e( 'Prep Time', 'delice-recipe-manager' ); ?></span>
        <span class="delice-recipe-meta-value"><?php echo esc_html( $recipe_data['prep_time'] ); ?> <?php _e( 'mins', 'delice-recipe-manager' ); ?></span>
      </div>
      <?php endif; ?>

      <?php if ( ! empty( $recipe_data['cook_time'] ) ) : ?>
      <div class="delice-recipe-meta-item">
        <span class="delice-recipe-meta-label"><?php _e( 'Cook Time', 'delice-recipe-manager' ); ?></span>
        <span class="delice-recipe-meta-value"><?php echo esc_html( $recipe_data['cook_time'] ); ?> <?php _e( 'mins', 'delice-recipe-manager' ); ?></span>
      </div>
      <?php endif; ?>

      <?php if ( ! empty( $recipe_data['total_time'] ) ) : ?>
      <div class="delice-recipe-meta-item">
        <span class="delice-recipe-meta-label"><?php _e( 'Total Time', 'delice-recipe-manager' ); ?></span>
        <span class="delice-recipe-meta-value"><?php echo esc_html( $recipe_data['total_time'] ); ?> <?php _e( 'mins', 'delice-recipe-manager' ); ?></span>
      </div>
      <?php endif; ?>

      <?php if ( ! empty( $recipe_data['calories'] ) ) : ?>
      <div class="delice-recipe-meta-item">
        <span class="delice-recipe-meta-label"><?php _e( 'Calories', 'delice-recipe-manager' ); ?></span>
        <span class="delice-recipe-meta-value"><?php echo esc_html( $recipe_data['calories'] ); ?> kcal</span>
      </div>
      <?php endif; ?>

      <?php if ( $diff_label ) : ?>
      <div class="delice-recipe-meta-item">
        <span class="delice-recipe-meta-label"><?php _e( 'Difficulty', 'delice-recipe-manager' ); ?></span>
        <span class="delice-recipe-meta-value" style="color:<?php echo esc_attr( $diff_color ); ?>;">
          <?php echo esc_html( $diff_label ); ?>
        </span>
      </div>
      <?php endif; ?>
    </div>

    <!-- Description -->
    <div class="delice-recipe-content">
      <?php if ( ! empty( $recipe_data['description'] ) ) : ?>
      <div class="delice-recipe-description">
        <?php echo wpautop( wp_kses_post( $recipe_data['description'] ) ); ?>
      </div>
      <?php endif; ?>

      <!-- Ingredients & Instructions -->
      <div class="delice-recipe-section-container">
        <section class="delice-recipe-section">
          <h3 class="delice-recipe-section-title"><?php _e( 'Ingredients', 'delice-recipe-manager' ); ?></h3>
          <?php if ( ! empty( $recipe_data['ingredients'] ) ) : ?>
            <ul class="delice-recipe-ingredients-list">
              <?php foreach ( $recipe_data['ingredients'] as $ing ) : ?>
                <li class="delice-recipe-ingredient">
                  <span class="delice-recipe-ingredient-name"><?php echo esc_html( $ing['name'] ); ?></span>
                  <?php if ( ! empty( $ing['amount'] ) || ! empty( $ing['unit'] ) ) : ?>
                    <span class="delice-recipe-ingredient-quantity">
                      <?php echo esc_html( trim( ( $ing['amount'] ?? '' ) . ' ' . ( $ing['unit'] ?? '' ) ) ); ?>
                    </span>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else : ?>
            <p class="no-data"><?php _e( 'No ingredients available.', 'delice-recipe-manager' ); ?></p>
          <?php endif; ?>
        </section>

        <section class="delice-recipe-section">
          <h3 class="delice-recipe-section-title"><?php _e( 'Instructions', 'delice-recipe-manager' ); ?></h3>
          <?php if ( ! empty( $recipe_data['instructions'] ) ) : 
            // sort by step number
            usort( $recipe_data['instructions'], function( $a, $b ) {
                return intval( $a['step'] ) - intval( $b['step'] );
            } );
          ?>
            <ol class="delice-recipe-instructions-list">
              <?php foreach ( $recipe_data['instructions'] as $inst ) : ?>
                <li class="delice-recipe-instruction">
                  <span class="delice-recipe-instruction-step"><?php echo esc_html( $inst['step'] ); ?></span>
                  <div class="delice-recipe-instruction-text"><?php echo wpautop( wp_kses_post( $inst['text'] ) ); ?></div>
                </li>
              <?php endforeach; ?>
            </ol>
          <?php else : ?>
            <p class="no-data"><?php _e( 'No instructions available.', 'delice-recipe-manager' ); ?></p>
          <?php endif; ?>
        </section>
      </div><!-- .delice-recipe-section-container -->

      <!-- Notes -->
      <?php if ( ! empty( $recipe_data['notes'] ) ) : ?>
      <div class="delice-recipe-notes">
        <h4><?php _e( 'Notes', 'delice-recipe-manager' ); ?></h4>
        <div class="delice-recipe-notes-content">
          <?php echo wpautop( wp_kses_post( $recipe_data['notes'] ) ); ?>
        </div>
      </div>
      <?php endif; ?>
    </div><!-- .delice-recipe-content -->

    <!-- Footer -->
    <div class="delice-recipe-footer">
      <div class="delice-recipe-footer-meta">
        <?php if ( ! empty( $recipe_data['cuisine'] ) ) : ?>
        <div class="delice-recipe-taxonomy">
          <span class="delice-recipe-taxonomy-label"><?php _e( 'Cuisine:', 'delice-recipe-manager' ); ?></span>
          <span class="delice-recipe-taxonomy-terms"><?php echo esc_html( $recipe_data['cuisine'] ); ?></span>
        </div>
        <?php endif; ?>
        <?php if ( ! empty( $recipe_data['course'] ) ) : ?>
        <div class="delice-recipe-taxonomy">
          <span class="delice-recipe-taxonomy-label"><?php _e( 'Course:', 'delice-recipe-manager' ); ?></span>
          <span class="delice-recipe-taxonomy-terms"><?php echo esc_html( $recipe_data['course'] ); ?></span>
        </div>
        <?php endif; ?>
        <?php if ( ! empty( $recipe_data['keywords'] ) && is_array( $recipe_data['keywords'] ) ) : ?>
        <div class="delice-recipe-taxonomy">
          <span class="delice-recipe-taxonomy-label"><?php _e( 'Keywords:', 'delice-recipe-manager' ); ?></span>
          <span class="delice-recipe-taxonomy-terms"><?php echo esc_html( implode( ', ', $recipe_data['keywords'] ) ); ?></span>
        </div>
        <?php endif; ?>
      </div>

      <button class="delice-recipe-print-button" onclick="window.print();">
        <span class="dashicons dashicons-printer"></span>
        <?php _e( 'Print Recipe', 'delice-recipe-manager' ); ?>
      </button>
    </div>
  </div><!-- .delice-recipe-card -->

  <!-- SEPARATE FAQ SECTION FOR PREVIEW -->
  <?php if ( ! empty( $recipe_data['faqs'] ) && is_array( $recipe_data['faqs'] ) ) : ?>
  <div class="delice-recipe-faq-section">
    <div class="delice-recipe-card">
      <h3 class="delice-recipe-section-title">
        <?php echo sprintf( __( 'FAQ for %s', 'delice-recipe-manager' ), esc_html( $recipe_data['title'] ) ); ?>
      </h3>
      <div class="delice-recipe-faqs-list">
        <?php foreach ( $recipe_data['faqs'] as $index => $faq ) : ?>
        <?php if ( ! empty( $faq['question'] ) && ! empty( $faq['answer'] ) ) : ?>
        <div class="delice-recipe-faq-item">
          <div class="delice-recipe-faq-question">
            <span><?php echo esc_html( $faq['question'] ); ?></span>
            <span class="delice-recipe-faq-toggle">+</span>
          </div>
          <div class="delice-recipe-faq-answer">
            <?php echo wpautop( wp_kses_post( $faq['answer'] ) ); ?>
          </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div><!-- .delice-recipe-preview-content -->
