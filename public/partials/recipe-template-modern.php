<?php
/**
 * Modern recipe template — "Editorial Bold"
 * Dark hero · vibrant orange accents · sticky two-column layout
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ── Data ───────────────────────────────────────────────────────────────────── */
$reviews_enabled     = get_option( 'delice_recipe_reviews_enabled', true );
$lang_texts          = Delice_Recipe_Language::get_all_texts();
$attribution_settings = get_option( 'delice_recipe_attribution_settings', array(
    'kitchen_name'        => '',
    'kitchen_url'         => '',
    'show_submitted_by'   => true,
    'show_tested_by'      => true,
    'default_author_name' => '',
) );

$recipe_title = get_the_title( $recipe_id );
$has_image    = has_post_thumbnail( $recipe_id );

/* Difficulty */
$difficulty_labels = array(
    'easy'   => __( 'Easy',   'delice-recipe-manager' ),
    'medium' => __( 'Medium', 'delice-recipe-manager' ),
    'hard'   => __( 'Hard',   'delice-recipe-manager' ),
);

/* Author */
$author = get_the_author_meta( 'display_name', get_post_field( 'post_author', $recipe_id ) );
if ( ! empty( $attribution_settings['default_author_name'] ) ) {
    $author = $attribution_settings['default_author_name'];
}

/* Nutrition */
$nutrition_raw = get_post_meta( $recipe_id, '_delice_recipe_nutrition', true );
$nutrition     = $nutrition_raw ? json_decode( wp_unslash( $nutrition_raw ), true ) : array();

/* Taxonomy */
$cuisine_terms = get_the_terms( $recipe_id, 'delice_cuisine' );
$course_terms  = get_the_terms( $recipe_id, 'delice_course' );
?>

<?php $drm_id = 'drm-' . absint( $recipe_id ); ?>
<style>
/* Scoped to this recipe instance — ID selector (1,x,0) beats any theme
   class-or-element rule including Pixwell's #main / #content selectors. */
#<?php echo $drm_id; ?> .delice-modern-layout {
    display:         flex   !important;
    flex-direction:  row    !important;
    flex-wrap:       nowrap !important;
    align-items:     flex-start !important;
    gap:             24px   !important;
    box-sizing:      border-box !important;
}
#<?php echo $drm_id; ?> .delice-modern-sidebar {
    width:           300px  !important;
    min-width:       300px  !important;
    max-width:       300px  !important;
    flex:            0 0 300px !important;
    float:           none   !important;
    margin:          0      !important;
    padding:         0      !important;
    box-sizing:      border-box !important;
}
#<?php echo $drm_id; ?> .delice-modern-main {
    flex:            1 1 auto !important;
    min-width:       0      !important;
    max-width:       none   !important;
    width:           auto   !important;
    float:           none   !important;
    margin:          0      !important;
    padding:         0      !important;
    box-sizing:      border-box !important;
}
@media (max-width: 680px) {
    #<?php echo $drm_id; ?> .delice-modern-layout {
        flex-direction: column !important;
    }
    #<?php echo $drm_id; ?> .delice-modern-sidebar {
        width:     100% !important;
        min-width: 0    !important;
        max-width: none !important;
        flex:      0 0 100% !important;
    }
}
</style>
<div id="<?php echo $drm_id; ?>" class="delice-recipe-wrapper delice-modern delice-recipe-container" data-recipe-id="<?php echo esc_attr( $recipe_id ); ?>">

    <!-- ═══ HERO ═══════════════════════════════════════════════════════════════ -->
    <div class="delice-modern-hero<?php echo $has_image && ! empty( $display_options['show_image'] ) ? ' delice-modern-hero--has-image' : ''; ?>">

        <?php if ( $has_image && ! empty( $display_options['show_image'] ) ) : ?>
            <div class="delice-modern-hero-image">
                <?php echo get_the_post_thumbnail( $recipe_id, 'large', array(
                    'class'   => 'delice-modern-img',
                    'loading' => 'lazy',
                    'alt'     => esc_attr( $recipe_title ),
                ) ); ?>
                <div class="delice-modern-hero-gradient"></div>
            </div>
        <?php endif; ?>

        <div class="delice-modern-hero-content">
            <?php if ( ! $hide_title ) : ?>
                <h2 class="delice-modern-title"><?php echo esc_html( $recipe_title ); ?></h2>
            <?php endif; ?>

            <!-- Quick meta badges -->
            <div class="delice-modern-meta-badges">
                <?php if ( $prep_time ) : ?>
                    <div class="delice-modern-badge">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <span class="delice-modern-badge-label"><?php echo esc_html( $lang_texts['prep_time'] ); ?></span>
                        <span class="delice-modern-badge-value"><?php echo esc_html( $prep_time ); ?> <?php echo esc_html( $lang_texts['min'] ); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ( $cook_time ) : ?>
                    <div class="delice-modern-badge">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M3 11l19-9-9 19-2-8-8-2z"/>
                        </svg>
                        <span class="delice-modern-badge-label"><?php echo esc_html( $lang_texts['cook_time'] ); ?></span>
                        <span class="delice-modern-badge-value"><?php echo esc_html( $cook_time ); ?> <?php echo esc_html( $lang_texts['min'] ); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ( $total_time ) : ?>
                    <div class="delice-modern-badge delice-modern-badge--total">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 14 14"/>
                        </svg>
                        <span class="delice-modern-badge-label"><?php echo esc_html( $lang_texts['total_time'] ); ?></span>
                        <span class="delice-modern-badge-value"><?php echo esc_html( $total_time ); ?> <?php echo esc_html( $lang_texts['min'] ); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ( $servings ) : ?>
                    <div class="delice-modern-badge">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <span class="delice-modern-badge-label"><?php echo esc_html( $lang_texts['servings'] ); ?></span>
                        <span class="delice-modern-badge-value"><?php echo esc_html( $servings ); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ( $calories ) : ?>
                    <div class="delice-modern-badge">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                        </svg>
                        <span class="delice-modern-badge-label"><?php echo esc_html( $lang_texts['calories'] ); ?></span>
                        <span class="delice-modern-badge-value"><?php echo esc_html( $calories ); ?> kcal</span>
                    </div>
                <?php endif; ?>

                <?php if ( $difficulty ) : ?>
                    <div class="delice-modern-badge delice-modern-badge--difficulty" data-difficulty="<?php echo esc_attr( $difficulty ); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        <span class="delice-modern-badge-label"><?php echo esc_html( $lang_texts['difficulty'] ); ?></span>
                        <span class="delice-modern-badge-value"><?php echo esc_html( $difficulty_labels[ $difficulty ] ?? ucfirst( $difficulty ) ); ?></span>
                    </div>
                <?php endif; ?>
            </div><!-- /.delice-modern-meta-badges -->
        </div><!-- /.delice-modern-hero-content -->
    </div><!-- /.delice-modern-hero -->

    <!-- ═══ TOOLBAR ══════════════════════════════════════════════════════════════ -->
    <div class="delice-modern-toolbar">

        <!-- Attribution -->
        <div class="delice-modern-author">
            <?php if ( ! empty( $attribution_settings['show_submitted_by'] ) ) : ?>
                <div class="delice-modern-author-avatar" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <div class="delice-modern-author-info">
                    <span class="delice-modern-author-label"><?php esc_html_e( 'Recipe by', 'delice-recipe-manager' ); ?></span>
                    <span class="delice-modern-author-name"><?php echo esc_html( $author ); ?></span>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $attribution_settings['show_tested_by'] ) && ! empty( $attribution_settings['kitchen_name'] ) ) : ?>
                <div class="delice-modern-author-divider" aria-hidden="true"></div>
                <div class="delice-modern-author-avatar delice-modern-author-avatar--verified" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
                <div class="delice-modern-author-info">
                    <span class="delice-modern-author-label"><?php esc_html_e( 'Tested by', 'delice-recipe-manager' ); ?></span>
                    <?php if ( ! empty( $attribution_settings['kitchen_url'] ) ) : ?>
                        <a href="<?php echo esc_url( $attribution_settings['kitchen_url'] ); ?>" class="delice-modern-author-name delice-modern-author-link" target="_blank" rel="noopener noreferrer">
                            <?php echo esc_html( $attribution_settings['kitchen_name'] ); ?>
                        </a>
                    <?php else : ?>
                        <span class="delice-modern-author-name"><?php echo esc_html( $attribution_settings['kitchen_name'] ); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div><!-- /.delice-modern-author -->

        <!-- Action buttons -->
        <div class="delice-modern-actions">
            <button class="delice-recipe-print-btn delice-modern-action-btn" type="button" aria-label="<?php esc_attr_e( 'Print recipe', 'delice-recipe-manager' ); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <polyline points="6,9 6,2 18,2 18,9"/>
                    <path d="M6,18H4a2,2,0,0,1-2-2V11a2,2,0,0,1,2-2H20a2,2,0,0,1,2,2v5a2,2,0,0,1-2,2H18"/>
                    <polyline points="6,14 18,14 18,22 6,22 6,14"/>
                </svg>
                <span><?php echo esc_html( $lang_texts['print'] ); ?></span>
            </button>

            <div class="delice-recipe-share-dropdown delice-modern-share-wrap">
                <button class="delice-recipe-share-btn delice-modern-action-btn" type="button" aria-label="<?php esc_attr_e( 'Share recipe', 'delice-recipe-manager' ); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="18" cy="5" r="3"/>
                        <circle cx="6" cy="12" r="3"/>
                        <circle cx="18" cy="19" r="3"/>
                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                    </svg>
                    <span><?php echo esc_html( $lang_texts['share'] ); ?></span>
                </button>
                <div class="delice-recipe-share-menu delice-modern-share-menu">
                    <a href="#" class="delice-recipe-share-item" data-platform="facebook">
                        <svg class="delice-recipe-share-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                        <?php esc_html_e( 'Facebook', 'delice-recipe-manager' ); ?>
                    </a>
                    <a href="#" class="delice-recipe-share-item" data-platform="twitter">
                        <svg class="delice-recipe-share-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
                        <?php esc_html_e( 'Twitter', 'delice-recipe-manager' ); ?>
                    </a>
                    <a href="#" class="delice-recipe-share-item" data-platform="pinterest">
                        <svg class="delice-recipe-share-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        <?php esc_html_e( 'Pinterest', 'delice-recipe-manager' ); ?>
                    </a>
                    <a href="#" class="delice-recipe-share-item" data-platform="whatsapp">
                        <svg class="delice-recipe-share-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                        <?php esc_html_e( 'WhatsApp', 'delice-recipe-manager' ); ?>
                    </a>
                    <a href="#" class="delice-recipe-share-item" data-platform="email">
                        <svg class="delice-recipe-share-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <?php esc_html_e( 'Email', 'delice-recipe-manager' ); ?>
                    </a>
                </div>
            </div><!-- /.delice-recipe-share-dropdown -->

            <?php if ( $reviews_enabled ) : ?>
                <button class="delice-recipe-rate-btn delice-modern-action-btn delice-modern-action-btn--rate" type="button" data-action="open-rating-modal" data-recipe-id="<?php echo esc_attr( $recipe_id ); ?>" aria-label="<?php esc_attr_e( 'Rate this recipe', 'delice-recipe-manager' ); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                    <span><?php echo esc_html( $lang_texts['rate'] ); ?></span>
                </button>
            <?php endif; ?>
        </div><!-- /.delice-modern-actions -->
    </div><!-- /.delice-modern-toolbar -->

    <!-- ═══ BODY ══════════════════════════════════════════════════════════════ -->
    <div class="delice-modern-body">

        <?php if ( ! empty( $ingredients ) ) : ?>
            <div class="delice-modern-layout">

                <!-- ── Ingredients sidebar ──────────────────────────────────── -->
                <div class="delice-modern-sidebar">

                    <div class="delice-modern-section">
                        <div class="delice-modern-section-header">
                            <h3 class="delice-modern-section-title">
                                <span class="delice-modern-section-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/>
                                    </svg>
                                </span>
                                <?php echo esc_html( $lang_texts['ingredients'] ); ?>
                            </h3>
                            <button class="delice-recipe-copy-ingredients delice-modern-copy-btn" type="button">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                </svg>
                                <?php echo esc_html( $lang_texts['copy'] ); ?>
                            </button>
                        </div>

                        <ul class="delice-modern-ingredients-list">
                            <?php foreach ( $ingredients as $ing ) :
                                $ing_id = 'ingr-' . esc_attr( $recipe_id . '-' . sanitize_title( $ing['name'] ?? 'item' ) );
                            ?>
                                <li class="delice-modern-ingredient delice-recipe-ingredient">
                                    <label class="delice-modern-ingredient-label" for="<?php echo esc_attr( $ing_id ); ?>">
                                        <input type="checkbox" class="delice-recipe-ingredient-checkbox" id="<?php echo esc_attr( $ing_id ); ?>">
                                        <span class="delice-modern-checkbox-mark" aria-hidden="true"></span>
                                        <span class="delice-modern-ingredient-name delice-recipe-ingredient-name"><?php echo esc_html( $ing['name'] ?? '' ); ?></span>
                                    </label>
                                    <?php if ( ! empty( $ing['amount'] ) || ! empty( $ing['unit'] ) ) : ?>
                                        <span class="delice-modern-ingredient-qty">
                                            <?php echo esc_html( trim( ( $ing['amount'] ?? '' ) . ' ' . ( $ing['unit'] ?? '' ) ) ); ?>
                                        </span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div><!-- /.delice-modern-section (ingredients) -->

                    <?php if ( ! empty( $nutrition ) ) : ?>
                        <div class="delice-modern-section delice-modern-nutrition">
                            <h3 class="delice-modern-section-title">
                                <span class="delice-modern-section-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
                                        <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                                        <line x1="6" y1="1" x2="6" y2="4"/>
                                        <line x1="10" y1="1" x2="10" y2="4"/>
                                        <line x1="14" y1="1" x2="14" y2="4"/>
                                    </svg>
                                </span>
                                <?php echo esc_html( $lang_texts['nutrition'] ?? __( 'Nutrition', 'delice-recipe-manager' ) ); ?>
                            </h3>
                            <div class="delice-modern-nutrition-grid">
                                <?php
                                $nutrition_icons = array(
                                    'calories' => '🔥',
                                    'protein'  => '💪',
                                    'fat'      => '🥑',
                                    'carbs'    => '🌾',
                                    'fiber'    => '🌿',
                                    'sugar'    => '🍬',
                                    'sodium'   => '🧂',
                                );
                                foreach ( $nutrition as $nutrient => $val ) : ?>
                                    <div class="delice-modern-nutrient">
                                        <span class="delice-modern-nutrient-icon" aria-hidden="true"><?php echo $nutrition_icons[ $nutrient ] ?? '•'; ?></span>
                                        <span class="delice-modern-nutrient-value"><?php echo esc_html( $val ); ?><small>g</small></span>
                                        <span class="delice-modern-nutrient-label"><?php echo esc_html( ucfirst( $nutrient ) ); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div><!-- /.delice-modern-nutrition -->
                    <?php endif; ?>

                </div><!-- /.delice-modern-sidebar -->

                <!-- ── Instructions main ───────────────────────────────────── -->
                <div class="delice-modern-main">

                    <?php if ( ! empty( $instructions ) ) : ?>
                        <div class="delice-modern-section">
                            <h3 class="delice-modern-section-title">
                                <span class="delice-modern-section-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="8" y1="6" x2="21" y2="6"/>
                                        <line x1="8" y1="12" x2="21" y2="12"/>
                                        <line x1="8" y1="18" x2="21" y2="18"/>
                                        <line x1="3" y1="6" x2="3.01" y2="6"/>
                                        <line x1="3" y1="12" x2="3.01" y2="12"/>
                                        <line x1="3" y1="18" x2="3.01" y2="18"/>
                                    </svg>
                                </span>
                                <?php echo esc_html( $lang_texts['instructions'] ); ?>
                            </h3>
                            <ol class="delice-modern-steps">
                                <?php foreach ( $instructions as $idx => $step ) :
                                    $text = preg_replace( '/^(\d+[\.\)\:]\s*)+/i', '', $step['text'] ?? '' );
                                    $text = trim( $text );
                                ?>
                                    <li class="delice-modern-step">
                                        <div class="delice-modern-step-num" aria-hidden="true"><?php echo absint( $idx + 1 ); ?></div>
                                        <div class="delice-modern-step-body">
                                            <p class="delice-modern-step-text"><?php echo esc_html( $text ); ?></p>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $notes ) ) : ?>
                        <div class="delice-modern-section delice-modern-notes">
                            <h3 class="delice-modern-section-title">
                                <span class="delice-modern-section-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                        <polyline points="10 9 9 9 8 9"/>
                                    </svg>
                                </span>
                                <?php echo esc_html( $lang_texts['notes'] ); ?>
                            </h3>
                            <div class="delice-modern-notes-text"><?php echo esc_html( $notes ); ?></div>
                        </div>
                    <?php endif; ?>

                </div><!-- /.delice-modern-main -->
            </div><!-- /.delice-modern-layout -->

        <?php else : ?>
            <!-- Full-width instructions when there are no ingredients -->
            <?php if ( ! empty( $instructions ) ) : ?>
                <div class="delice-modern-section">
                    <h3 class="delice-modern-section-title">
                        <span class="delice-modern-section-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="8" y1="6" x2="21" y2="6"/>
                                <line x1="8" y1="12" x2="21" y2="12"/>
                                <line x1="8" y1="18" x2="21" y2="18"/>
                                <line x1="3" y1="6" x2="3.01" y2="6"/>
                                <line x1="3" y1="12" x2="3.01" y2="12"/>
                                <line x1="3" y1="18" x2="3.01" y2="18"/>
                            </svg>
                        </span>
                        <?php echo esc_html( $lang_texts['instructions'] ); ?>
                    </h3>
                    <ol class="delice-modern-steps">
                        <?php foreach ( $instructions as $idx => $step ) :
                            $text = trim( preg_replace( '/^(\d+[\.\)\:]\s*)+/i', '', $step['text'] ?? '' ) );
                        ?>
                            <li class="delice-modern-step">
                                <div class="delice-modern-step-num" aria-hidden="true"><?php echo absint( $idx + 1 ); ?></div>
                                <div class="delice-modern-step-body">
                                    <p class="delice-modern-step-text"><?php echo esc_html( $text ); ?></p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $notes ) ) : ?>
                <div class="delice-modern-section delice-modern-notes">
                    <h3 class="delice-modern-section-title"><?php echo esc_html( $lang_texts['notes'] ); ?></h3>
                    <div class="delice-modern-notes-text"><?php echo esc_html( $notes ); ?></div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- ── FAQs ──────────────────────────────────────────────────────── -->
        <?php if ( ! empty( $faqs ) ) : ?>
            <div class="delice-modern-section delice-modern-faqs">
                <h3 class="delice-modern-section-title">
                    <span class="delice-modern-section-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </span>
                    <?php printf( esc_html__( 'FAQ for %s', 'delice-recipe-manager' ), esc_html( get_the_title( $recipe_id ) ) ); ?>
                </h3>
                <div class="delice-recipe-modern-faqs-list delice-modern-faq-list">
                    <?php foreach ( $faqs as $i => $faq ) : ?>
                        <div class="delice-recipe-modern-faq-item delice-modern-faq-item">
                            <button
                                class="delice-recipe-modern-faq-question delice-modern-faq-question"
                                type="button"
                                aria-expanded="false"
                                data-faq-index="<?php echo esc_attr( $i ); ?>"
                            >
                                <span><?php echo esc_html( $faq['question'] ); ?></span>
                                <span class="delice-recipe-modern-faq-toggle delice-modern-faq-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="6 9 12 15 18 9"/>
                                    </svg>
                                </span>
                            </button>
                            <div class="delice-recipe-modern-faq-answer delice-modern-faq-answer">
                                <p><?php echo esc_html( $faq['answer'] ); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div><!-- /.delice-modern-body -->

    <!-- ═══ REVIEWS ═══════════════════════════════════════════════════════════ -->
    <?php if ( $reviews_enabled ) : ?>
        <section id="reviewSection-<?php echo esc_attr( $recipe_id ); ?>" class="delice-modern-reviews delice-recipe-review-section">

            <div class="delice-modern-reviews-header">
                <h3><?php esc_html_e( 'Rate & Review', 'delice-recipe-manager' ); ?></h3>
                <p><?php esc_html_e( 'Share your experience and help others discover this recipe.', 'delice-recipe-manager' ); ?></p>
            </div>

            <!-- Selected rating display (shown after modal rating) -->
            <div class="delice-selected-rating-display">
                <div class="delice-rating-selected-info">
                    <span class="delice-rating-label"><?php esc_html_e( 'Your Rating:', 'delice-recipe-manager' ); ?></span>
                    <div class="delice-rating-stars-display">
                        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                            <span class="delice-display-star" data-rating="<?php echo esc_attr( $i ); ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <span class="delice-rating-text"><?php esc_html_e( 'Thank you!', 'delice-recipe-manager' ); ?></span>
                </div>
            </div>

            <!-- Inline rating stars -->
            <div class="delice-recipe-rating-container">
                <label class="delice-recipe-rating-label"><?php esc_html_e( 'Your Rating:', 'delice-recipe-manager' ); ?></label>
                <div class="delice-recipe-rating-stars" data-recipe-id="<?php echo esc_attr( $recipe_id ); ?>">
                    <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                        <span class="delice-rating-star" data-rating="<?php echo esc_attr( $i ); ?>">★</span>
                    <?php endfor; ?>
                </div>
                <span class="delice-recipe-rating-text"><?php esc_html_e( 'Click to rate', 'delice-recipe-manager' ); ?></span>
            </div>

            <!-- Review form -->
            <form class="delice-recipe-review-form delice-modern-review-form" data-recipe-id="<?php echo esc_attr( $recipe_id ); ?>">

                <div class="delice-recipe-review-comment">
                    <label for="review-comment-<?php echo esc_attr( $recipe_id ); ?>">
                        <?php esc_html_e( 'Your Review:', 'delice-recipe-manager' ); ?>
                    </label>
                    <textarea
                        id="review-comment-<?php echo esc_attr( $recipe_id ); ?>"
                        name="comment"
                        rows="4"
                        required
                        placeholder="<?php esc_attr_e( 'How did this recipe turn out? Share your tips and tweaks!', 'delice-recipe-manager' ); ?>"
                    ></textarea>
                </div>

                <div class="delice-recipe-review-image">
                    <label for="review-image-<?php echo esc_attr( $recipe_id ); ?>">
                        <?php esc_html_e( 'Add a Photo (Optional):', 'delice-recipe-manager' ); ?>
                    </label>
                    <div class="delice-recipe-file-upload-wrapper">
                        <input type="file" id="review-image-<?php echo esc_attr( $recipe_id ); ?>" name="review_image" accept="image/*">
                        <div class="delice-recipe-file-upload-text">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                <circle cx="12" cy="13" r="4"/>
                            </svg>
                            <span><?php esc_html_e( 'Upload your photo', 'delice-recipe-manager' ); ?></span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="delice-recipe-review-submit delice-modern-submit-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <line x1="22" y1="2" x2="11" y2="13"/>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                    <?php esc_html_e( 'Submit Review', 'delice-recipe-manager' ); ?>
                </button>
            </form>

            <!-- Success message -->
            <div class="delice-recipe-review-success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <p><?php esc_html_e( 'Thank you for your review!', 'delice-recipe-manager' ); ?></p>
            </div>

        </section>

        <section id="reviewsDisplay-<?php echo esc_attr( $recipe_id ); ?>" class="delice-recipe-reviews-display delice-modern-reviews-display"></section>
    <?php endif; ?>

    <!-- ═══ FOOTER ═══════════════════════════════════════════════════════════ -->
    <footer class="delice-modern-footer">
        <?php if ( ! is_wp_error( $cuisine_terms ) && ! empty( $cuisine_terms ) ) : ?>
            <span class="delice-modern-footer-tag">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="2" y1="12" x2="22" y2="12"/>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                </svg>
                <?php echo esc_html( $cuisine_terms[0]->name ); ?>
            </span>
        <?php endif; ?>
        <?php if ( ! is_wp_error( $course_terms ) && ! empty( $course_terms ) ) : ?>
            <span class="delice-modern-footer-tag">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
                    <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                    <line x1="6" y1="1" x2="6" y2="4"/>
                    <line x1="10" y1="1" x2="10" y2="4"/>
                    <line x1="14" y1="1" x2="14" y2="4"/>
                </svg>
                <?php echo esc_html( $course_terms[0]->name ); ?>
            </span>
        <?php endif; ?>
    </footer>

</div><!-- /.delice-modern -->
