<?php
/**
 * Elegant recipe template — "Artisan Magazine"
 * Warm cream background · forest green headings · gold accents
 * Complete rewrite — no Font Awesome dependency, all bugs fixed
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ── Data ───────────────────────────────────────────────────────────────────── */
$reviews_enabled      = get_option( 'delice_recipe_reviews_enabled', true );
$lang_texts           = Delice_Recipe_Language::get_all_texts();
$attribution_settings = get_option( 'delice_recipe_attribution_settings', array(
    'kitchen_name'        => '',
    'kitchen_url'         => '',
    'show_submitted_by'   => true,
    'show_tested_by'      => true,
    'default_author_name' => '',
) );

$recipe_title   = get_the_title( $recipe_id );
// Use get_post_field() to read the raw excerpt directly from the DB.
// DO NOT use get_the_excerpt() here — it fires the get_the_excerpt filter
// which calls wp_trim_excerpt() → apply_filters('the_content') →
// display_recipe_content() → load_template() → this file again = infinite loop.
$recipe_excerpt = get_post_field( 'post_excerpt', $recipe_id );

/* Clean excerpt */
if ( $recipe_excerpt ) {
    $recipe_excerpt = preg_replace( '/\{[^}]*\}/', '', $recipe_excerpt );   // remove {placeholders}
    $recipe_excerpt = preg_replace( '/\[.*?\]/', '', $recipe_excerpt );      // remove [shortcodes]
    $recipe_excerpt = trim( preg_replace( '/\s+/', ' ', $recipe_excerpt ) );
}

/* Author — smart fallback chain */
$author = '';
if ( ! empty( $attribution_settings['default_author_name'] ) ) {
    $author = $attribution_settings['default_author_name'];
} elseif ( ! empty( $attribution_settings['kitchen_name'] ) ) {
    $author = $attribution_settings['kitchen_name'];
} else {
    $author_id   = get_post_field( 'post_author', $recipe_id );
    $author_data = get_userdata( $author_id );
    if ( $author_data ) {
        $full_name = trim( $author_data->first_name . ' ' . $author_data->last_name );
        if ( $full_name ) {
            $author = $full_name;
        } elseif ( ! filter_var( $author_data->display_name, FILTER_VALIDATE_EMAIL ) ) {
            $author = $author_data->display_name;
        } else {
            $author = $author_data->user_login;
        }
    }
}
if ( ! $author ) {
    $author = __( 'Recipe Author', 'delice-recipe-manager' );
}

/* Difficulty */
$difficulty_labels = array(
    'easy'   => __( 'Easy',   'delice-recipe-manager' ),
    'medium' => __( 'Medium', 'delice-recipe-manager' ),
    'hard'   => __( 'Hard',   'delice-recipe-manager' ),
);
$difficulty_label = $difficulty ? ( $difficulty_labels[ $difficulty ] ?? ucfirst( $difficulty ) ) : '';

/* Nutrition */
$nutrition_raw = get_post_meta( $recipe_id, '_delice_recipe_nutrition', true );
$nutrition     = $nutrition_raw ? json_decode( wp_unslash( $nutrition_raw ), true ) : array();

/* Taxonomy */
$cuisine_terms = get_the_terms( $recipe_id, 'delice_cuisine' );
$course_terms  = get_the_terms( $recipe_id, 'delice_course' );
$dietary_terms = get_the_terms( $recipe_id, 'delice_dietary' );

/* v3.6.0 — Dietary badges */
$dietary_badge_labels = array(
    'vegetarian'  => 'Vegetarian', 'vegan' => 'Vegan', 'gluten-free' => 'Gluten-Free',
    'dairy-free'  => 'Dairy-Free', 'nut-free' => 'Nut-Free', 'low-carb' => 'Low-Carb',
    'keto'        => 'Keto', 'paleo' => 'Paleo',
);
$dietary_meta = get_post_meta( $recipe_id, '_delice_recipe_dietary', true );
$dietary_meta = is_array( $dietary_meta ) ? $dietary_meta : array();

/* v3.6.0 — Rating summary */
$dre_rating_avg   = floatval( get_post_meta( $recipe_id, '_delice_recipe_rating_average', true ) );
$dre_rating_count = intval( get_post_meta( $recipe_id, '_delice_recipe_rating_count', true ) );
$dre_is_seed      = (bool) get_post_meta( $recipe_id, '_delice_recipe_is_seed_rating', true );
?>

<?php $dre_id = 'dre-' . absint( $recipe_id ); ?>
<style>
/* ── Specificity shield: ID-scoped rules beat Pixwell #main/#content overrides ── */
#<?php echo $dre_id; ?>,
#<?php echo $dre_id; ?> * { box-sizing: border-box !important; }
#<?php echo $dre_id; ?> { max-width: 860px !important; margin: 0 auto !important; overflow: hidden !important; display: block !important; }

/* ── Global element-level resets (themes add padding/margin/list-style to li/p/ul/svg/img) ── */
#<?php echo $dre_id; ?> ul,
#<?php echo $dre_id; ?> ol            { list-style: none !important; padding: 0 !important; margin: 0 !important; }
#<?php echo $dre_id; ?> li            { padding: 0 !important; margin: 0 !important; background: none !important; border: none !important; }
#<?php echo $dre_id; ?> li::before,
#<?php echo $dre_id; ?> li::after     { content: none !important; display: none !important; }
#<?php echo $dre_id; ?> p             { margin-top: 0 !important; margin-bottom: 0 !important; }
#<?php echo $dre_id; ?> svg           { display: inline-block !important; max-width: none !important; overflow: visible !important; flex-shrink: 0 !important; }
#<?php echo $dre_id; ?> img           { display: block !important; max-width: 100% !important; height: auto !important; }
#<?php echo $dre_id; ?> button        { font-family: inherit !important; cursor: pointer !important; }

/* ── Header & hero image ── */
#<?php echo $dre_id; ?> header.delice-elegant-header   { display: block !important; padding: 40px 48px 0 !important; text-align: center !important; margin: 0 !important; border: none !important; background: #faf7f2 !important; }
#<?php echo $dre_id; ?> .delice-elegant-hero-image     { overflow: hidden !important; margin: 0 -48px !important; display: block !important; line-height: 0 !important; }
#<?php echo $dre_id; ?> .delice-elegant-img            { width: 100% !important; max-height: 480px !important; object-fit: cover !important; display: block !important; border-radius: 0 !important; }
#<?php echo $dre_id; ?> .delice-elegant-difficulty-badge { position: absolute !important; top: 16px !important; right: 16px !important; }

/* ── Byline ── */
#<?php echo $dre_id; ?> .delice-elegant-byline         { display: flex !important; align-items: center !important; justify-content: center !important; flex-wrap: wrap !important; gap: 8px !important; margin-bottom: 28px !important; }
#<?php echo $dre_id; ?> .delice-elegant-byline-item    { display: inline-flex !important; align-items: center !important; gap: 5px !important; }

/* ── Meta bar ── */
#<?php echo $dre_id; ?> .delice-elegant-meta           { display: flex !important; align-items: stretch !important; flex-wrap: wrap !important; margin: 0 !important; padding: 0 !important; }
#<?php echo $dre_id; ?> .delice-elegant-meta-item      { display: flex !important; align-items: center !important; gap: 10px !important; flex: 1 !important; min-width: 100px !important; padding: 18px 24px !important; margin: 0 !important; border: none !important; border-right: 1px solid #e7e5e4 !important; }
#<?php echo $dre_id; ?> .delice-elegant-meta-item:last-child { border-right: none !important; }
#<?php echo $dre_id; ?> .delice-elegant-meta-item > div{ display: flex !important; flex-direction: column !important; gap: 2px !important; }

/* ── Actions ── */
#<?php echo $dre_id; ?> .delice-elegant-actions        { display: flex !important; align-items: center !important; justify-content: center !important; flex-wrap: wrap !important; gap: 10px !important; padding: 20px 48px !important; margin: 0 !important; }
#<?php echo $dre_id; ?> .delice-elegant-btn            { display: inline-flex !important; align-items: center !important; gap: 7px !important; padding: 10px 18px !important; border-radius: 24px !important; white-space: nowrap !important; }

/* ── Section titles — magazine-style with flanking rules ── */
#<?php echo $dre_id; ?> .delice-elegant-section-title  { display: flex !important; align-items: center !important; gap: 12px !important; margin: 0 0 24px !important; font-size: 1.05rem !important; font-weight: 700 !important; color: #166534 !important; text-transform: uppercase !important; letter-spacing: .06em !important; }
#<?php echo $dre_id; ?> .delice-elegant-section-title::before,
#<?php echo $dre_id; ?> .delice-elegant-section-title::after { content: '' !important; flex: 1 !important; border-top: 1px solid #d6d0c4 !important; }

/* ── Body sections — standalone ── */
#<?php echo $dre_id; ?> section.delice-elegant-ingredients { display: block !important; padding: 36px 48px !important; margin: 0 !important; border: none !important; background: #faf7f2 !important; }
#<?php echo $dre_id; ?> section.delice-elegant-instructions { display: block !important; padding: 36px 48px !important; margin: 0 !important; border: none !important; background: #faf7f2 !important; }
#<?php echo $dre_id; ?> .delice-elegant-equipment      { display: block !important; padding: 36px 48px !important; margin: 0 !important; background: #faf7f2 !important; }

/* ── Semantic section elements outside body (notes/nutrition/faqs) ── */
#<?php echo $dre_id; ?> section.delice-elegant-notes   { display: block !important; padding: 28px 48px !important; margin: 0 !important; border: none !important; }
#<?php echo $dre_id; ?> section.delice-elegant-nutrition { display: block !important; padding: 28px 48px !important; margin: 0 !important; border: none !important; }
#<?php echo $dre_id; ?> section.delice-elegant-faqs    { display: block !important; padding: 28px 48px !important; margin: 0 !important; border: none !important; }

/* ── Ingredients (re-declare padding after global li reset) ── */
#<?php echo $dre_id; ?> .delice-elegant-ingredients-list { list-style: none !important; display: grid !important; grid-template-columns: repeat(2, 1fr) !important; margin: 0 !important; padding: 0 !important; overflow: hidden !important; border-radius: 8px !important; }
#<?php echo $dre_id; ?> .delice-elegant-ingredient     { display: flex !important; align-items: center !important; justify-content: space-between !important; gap: 10px !important; padding: 11px 16px !important; margin: 0 !important; border: none !important; border-bottom: 1px solid #e7e5e4 !important; background: #fff !important; }
#<?php echo $dre_id; ?> .delice-elegant-ingredient:last-child,
#<?php echo $dre_id; ?> .delice-elegant-ingredient:nth-last-child(2):nth-child(odd) { border-bottom: none !important; }
#<?php echo $dre_id; ?> .delice-elegant-ingredient-inner{ display: flex !important; align-items: center !important; gap: 9px !important; flex: 1 !important; min-width: 0 !important; cursor: pointer !important; }
#<?php echo $dre_id; ?> .delice-elegant-checkbox       { position: absolute !important; opacity: 0 !important; width: 0 !important; height: 0 !important; pointer-events: none !important; }
#<?php echo $dre_id; ?> .delice-elegant-check-icon     { display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0 !important; width: 20px !important; height: 20px !important; min-width: 20px !important; border-radius: 4px !important; }
#<?php echo $dre_id; ?> .delice-elegant-ingredient-name { line-height: 1.4 !important; overflow: hidden !important; text-overflow: ellipsis !important; }
#<?php echo $dre_id; ?> .delice-elegant-ingredient-qty { white-space: nowrap !important; flex-shrink: 0 !important; border-radius: 4px !important; padding: 2px 8px !important; }

/* ── Affiliate buy-chip: lock icon to 13 px regardless of theme SVG rules ── */
#<?php echo $dre_id; ?> .delice-buy-chip-icon          { width: 13px !important; height: 13px !important; min-width: 13px !important; max-width: 13px !important; flex-shrink: 0 !important; }
#<?php echo $dre_id; ?> .delice-buy-chip               { display: inline-flex !important; align-items: center !important; gap: 4px !important; padding: 3px 9px 3px 7px !important; border-radius: 20px !important; border: 1.5px solid currentColor !important; font-size: 11px !important; font-weight: 600 !important; line-height: 1.4 !important; white-space: nowrap !important; text-decoration: none !important; flex-shrink: 0 !important; }
#<?php echo $dre_id; ?> .delice-buy-chips              { display: inline-flex !important; flex-wrap: wrap !important; gap: 5px !important; align-items: center !important; flex-shrink: 0 !important; }

/* ── Linked ingredient row: name on row 1, qty+chip on row 2 ─────────────────
 * In the 2-column grid each cell is ~50 % wide. A long qty badge ("1 medium,
 * finely chopped") plus the chip together overflow that width and get clipped
 * by overflow:hidden on the list container.  Wrapping the inner label to 100 %
 * and then letting qty + chip sit on a second line fixes the overflow cleanly.
 * ── */
#<?php echo $dre_id; ?> .delice-recipe-ingredient--linked                            { flex-wrap: wrap !important; row-gap: 4px !important; }
#<?php echo $dre_id; ?> .delice-recipe-ingredient--linked .delice-elegant-ingredient-inner { flex: 1 1 100% !important; min-width: 0 !important; }
#<?php echo $dre_id; ?> .delice-recipe-ingredient--linked .delice-elegant-ingredient-qty   { flex: 1 0 auto !important; }
#<?php echo $dre_id; ?> .delice-recipe-ingredient--linked .delice-buy-chip,
#<?php echo $dre_id; ?> .delice-recipe-ingredient--linked .delice-buy-chips                { flex-shrink: 0 !important; margin-left: auto !important; }

/* ── Steps (re-declare after global li reset) ── */
#<?php echo $dre_id; ?> .delice-elegant-steps          { list-style: none !important; display: flex !important; flex-direction: column !important; gap: 20px !important; margin: 0 !important; padding: 0 !important; }
#<?php echo $dre_id; ?> .delice-elegant-step           { display: flex !important; align-items: flex-start !important; gap: 18px !important; padding: 0 !important; margin: 0 !important; border: none !important; background: none !important; }
#<?php echo $dre_id; ?> .delice-elegant-step-num       { display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0 !important; width: 38px !important; height: 38px !important; min-width: 38px !important; border-radius: 50% !important; border: none !important; background: #166534 !important; color: #fff !important; box-shadow: 0 2px 8px rgba(22,101,52,.3) !important; margin-top: 2px !important; transition: background .2s !important; }
#<?php echo $dre_id; ?> .delice-elegant-step:hover .delice-elegant-step-num { background: #b45309 !important; box-shadow: 0 2px 10px rgba(180,83,9,.35) !important; }
#<?php echo $dre_id; ?> .delice-elegant-step-text      { margin: 0 !important; line-height: 1.8 !important; padding-top: 4px !important; }

/* ── Nutrition ── */
#<?php echo $dre_id; ?> .delice-elegant-nutrition-grid { display: grid !important; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)) !important; gap: 1px !important; background: #d6d0c4 !important; overflow: hidden !important; border-radius: 8px !important; }
#<?php echo $dre_id; ?> .delice-elegant-nutrient       { display: flex !important; flex-direction: column !important; align-items: center !important; gap: 4px !important; padding: 18px 12px !important; text-align: center !important; background: #faf7f2 !important; }
#<?php echo $dre_id; ?> .delice-recipe-nutrition-disclaimer { padding: 10px 20px 14px !important; }

/* ── FAQs ── */
#<?php echo $dre_id; ?> .delice-elegant-faq-question   { display: flex !important; align-items: center !important; justify-content: space-between !important; width: 100% !important; gap: 14px !important; padding: 16px 20px !important; background: none !important; border: none !important; text-align: left !important; color: #2d2d2d !important; }
#<?php echo $dre_id; ?> .delice-elegant-faq-toggle     { display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0 !important; width: 28px !important; height: 28px !important; min-width: 28px !important; border-radius: 50% !important; background: #f0ede6 !important; transition: background 0.2s, transform 0.25s !important; }
#<?php echo $dre_id; ?> .delice-recipe-modern-faq-item.faq-open .delice-elegant-faq-toggle { background: #5c7a3e !important; transform: rotate(180deg) !important; }
#<?php echo $dre_id; ?> .delice-elegant-faq-toggle svg { width: 14px !important; height: 14px !important; stroke: #666 !important; }
#<?php echo $dre_id; ?> .delice-recipe-modern-faq-item.faq-open .delice-elegant-faq-toggle svg { stroke: #fff !important; }
/* FAQ accordion — display controlled by JS (style.setProperty) — CSS here is a fallback */
#<?php echo $dre_id; ?> .delice-recipe-modern-faq-answer { display: none !important; overflow: hidden !important; }
#<?php echo $dre_id; ?> .delice-recipe-modern-faq-item.faq-open .delice-recipe-modern-faq-answer { display: block !important; overflow: visible !important; }
#<?php echo $dre_id; ?> .delice-elegant-faq-answer     { padding: 0 20px 18px !important; }

/* ── Semantic elements (theme styles article/header/section/footer directly) ── */
#<?php echo $dre_id; ?> footer.delice-elegant-footer   { display: flex !important; align-items: center !important; justify-content: center !important; flex-wrap: wrap !important; gap: 8px !important; padding: 18px 24px !important; margin: 0 !important; border-top: none !important; }
#<?php echo $dre_id; ?> .delice-elegant-tag            { display: inline-flex !important; align-items: center !important; border-radius: 20px !important; padding: 5px 14px !important; white-space: nowrap !important; }
#<?php echo $dre_id; ?> section.delice-elegant-reviews { padding: 36px 48px !important; margin: 0 !important; border: none !important; display: block !important; }
#<?php echo $dre_id; ?> .delice-elegant-reviews .delice-recipe-rating-container { display: flex !important; align-items: center !important; flex-wrap: wrap !important; justify-content: center !important; gap: 12px !important; }
#<?php echo $dre_id; ?> .delice-elegant-review-form    { display: flex !important; flex-direction: column !important; gap: 18px !important; padding: 24px !important; border-radius: 14px !important; }
#<?php echo $dre_id; ?> .delice-elegant-submit-btn     { display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 8px !important; border-radius: 24px !important; padding: 13px 32px !important; align-self: center !important; }

/* ── Jump to Recipe ── */
#<?php echo $dre_id; ?> .delice-recipe-jump-btn { display: inline-flex !important; align-items: center !important; gap: 6px !important; background: #166534 !important; color: #fff !important; border: none !important; border-radius: 20px !important; padding: 8px 18px !important; font-size: 12px !important; font-weight: 700 !important; text-decoration: none !important; margin-bottom: 16px !important; }
#<?php echo $dre_id; ?> .delice-recipe-jump-btn:hover { background: #14532d !important; color: #fff !important; }

/* ── Share dropdown ── */
#<?php echo $dre_id; ?> .delice-elegant-share-wrap     { position: relative !important; }

/* ── Responsive ── */
@media (max-width: 700px) {
    #<?php echo $dre_id; ?> header.delice-elegant-header { padding: 28px 24px 0 !important; }
    #<?php echo $dre_id; ?> .delice-elegant-hero-image { margin: 0 -24px !important; }
    #<?php echo $dre_id; ?> .delice-elegant-ingredients-list { grid-template-columns: 1fr !important; }
    #<?php echo $dre_id; ?> section.delice-elegant-ingredients,
    #<?php echo $dre_id; ?> section.delice-elegant-instructions,
    #<?php echo $dre_id; ?> .delice-elegant-equipment { padding: 24px !important; }
    #<?php echo $dre_id; ?> .delice-elegant-actions    { padding: 14px 24px !important; }
    #<?php echo $dre_id; ?> section.delice-elegant-notes,
    #<?php echo $dre_id; ?> section.delice-elegant-nutrition,
    #<?php echo $dre_id; ?> section.delice-elegant-faqs { padding: 20px 24px !important; }
    #<?php echo $dre_id; ?> section.delice-elegant-reviews { padding: 28px 24px !important; }
}
</style>
<?php
// Visible breadcrumb (v3.6.0) — respect show_breadcrumb toggle (v3.8.0)
$dre_show_breadcrumb = ! isset( $display_options['show_breadcrumb'] ) || $display_options['show_breadcrumb'];
if ( $dre_show_breadcrumb && ! defined( 'WPSEO_VERSION' ) && ! defined( 'RANK_MATH_VERSION' ) ) :
    $dre_bc_mid = ( ! is_wp_error( $cuisine_terms ) && ! empty( $cuisine_terms ) ) ? $cuisine_terms[0]
               : ( ( ! is_wp_error( $course_terms ) && ! empty( $course_terms ) ) ? $course_terms[0] : null );
?>
<nav class="delice-recipe-breadcrumb" aria-label="Breadcrumb">
  <ol class="delice-recipe-breadcrumb-list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( $lang_texts['home'] ); ?></a></li>
    <?php if ( $dre_bc_mid ) : ?>
      <li><a href="<?php echo esc_url( get_term_link( $dre_bc_mid ) ); ?>"><?php echo esc_html( $dre_bc_mid->name ); ?></a></li>
    <?php endif; ?>
    <li aria-current="page"><?php echo esc_html( $recipe_title ); ?></li>
  </ol>
</nav>
<?php endif; ?>
<div id="<?php echo $dre_id; ?>" class="delice-recipe-container delice-elegant" data-recipe-id="<?php echo esc_attr( $recipe_id ); ?>">

    <!-- ═══ HEADER ═══════════════════════════════════════════════════════════ -->
    <div class="delice-elegant-header">

        <?php if ( $recipe_excerpt ) : ?>
            <p class="delice-elegant-tagline"><?php echo esc_html( $recipe_excerpt ); ?></p>
        <?php endif; ?>

        <?php if ( ! $hide_title ) : ?>
            <h2 class="delice-elegant-title"><?php echo esc_html( $recipe_title ); ?></h2>
        <?php endif; ?>

        <!-- Byline -->
        <?php if ( ! empty( $attribution_settings['show_submitted_by'] ) || ( ! empty( $attribution_settings['show_tested_by'] ) && ! empty( $attribution_settings['kitchen_name'] ) ) ) : ?>
            <div class="delice-elegant-byline">
                <?php if ( ! empty( $attribution_settings['show_submitted_by'] ) ) : ?>
                    <span class="delice-elegant-byline-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <?php esc_html_e( 'By', 'delice-recipe-manager' ); ?> <strong><?php echo esc_html( $author ); ?></strong>
                    </span>
                <?php endif; ?>

                <?php if ( ! empty( $attribution_settings['show_tested_by'] ) && ! empty( $attribution_settings['kitchen_name'] ) ) : ?>
                    <span class="delice-elegant-byline-sep" aria-hidden="true">·</span>
                    <span class="delice-elegant-byline-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <?php esc_html_e( 'Tested by', 'delice-recipe-manager' ); ?>
                        <?php if ( ! empty( $attribution_settings['kitchen_url'] ) ) : ?>
                            <a href="<?php echo esc_url( $attribution_settings['kitchen_url'] ); ?>" target="_blank" rel="noopener noreferrer">
                                <strong><?php echo esc_html( $attribution_settings['kitchen_name'] ); ?></strong>
                            </a>
                        <?php else : ?>
                            <strong><?php echo esc_html( $attribution_settings['kitchen_name'] ); ?></strong>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Hero image — WebP <picture> element (v3.8.0) -->
        <?php if ( ! empty( $display_options['show_image'] ) && has_post_thumbnail( $recipe_id ) ) : ?>
            <div class="delice-elegant-hero-image">
                <?php
                $dre_thumb_id = get_post_thumbnail_id( $recipe_id );
                $dre_img_src  = $dre_thumb_id ? wp_get_attachment_image_src( $dre_thumb_id, 'large' ) : null;
                $dre_webp_url = '';
                if ( $dre_thumb_id && $dre_img_src ) {
                    $dre_meta = wp_get_attachment_metadata( $dre_thumb_id );
                    $dre_base = trailingslashit( dirname( wp_get_attachment_url( $dre_thumb_id ) ) );
                    if ( isset( $dre_meta['sizes']['large']['sources']['image/webp']['file'] ) ) {
                        $dre_webp_url = $dre_base . $dre_meta['sizes']['large']['sources']['image/webp']['file'];
                    } elseif ( isset( $dre_meta['sources']['image/webp']['file'] ) ) {
                        $dre_webp_url = $dre_base . $dre_meta['sources']['image/webp']['file'];
                    }
                }
                if ( $dre_img_src ) :
                ?>
                <picture>
                    <?php if ( $dre_webp_url ) : ?>
                    <source srcset="<?php echo esc_url( $dre_webp_url ); ?>" type="image/webp">
                    <?php endif; ?>
                    <img src="<?php echo esc_url( $dre_img_src[0] ); ?>"
                         class="delice-elegant-img"
                         alt="<?php echo esc_attr( $recipe_title ); ?>"
                         fetchpriority="high"
                         width="<?php echo intval( $dre_img_src[1] ); ?>"
                         height="<?php echo intval( $dre_img_src[2] ); ?>">
                </picture>
                <?php endif; ?>
                <?php if ( $difficulty ) : ?>
                    <span class="delice-elegant-difficulty-badge delice-elegant-difficulty-badge--<?php echo esc_attr( $difficulty ); ?>">
                        <?php echo esc_html( $difficulty_label ); ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ( $dre_rating_count > 0 && ( ! isset( $display_options['show_rating'] ) || $display_options['show_rating'] ) ) : ?>
        <div class="delice-recipe-rating-summary" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating" style="justify-content:center;margin-top:14px;">
          <div class="delice-recipe-rating-stars-display" aria-hidden="true">
            <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
              <span class="delice-rating-star-display<?php echo $i <= round( $dre_rating_avg ) ? ' filled' : ''; ?>">★</span>
            <?php endfor; ?>
          </div>
          <span class="delice-recipe-rating-score" itemprop="ratingValue"><?php echo number_format( $dre_rating_avg, 1 ); ?></span>
          <?php if ( $dre_is_seed ) : ?>
          <span class="delice-recipe-rating-count"><?php esc_html_e( 'Editor Tested', 'delice-recipe-manager' ); ?><meta itemprop="ratingCount" content="1"></span>
          <?php else : ?>
          <span class="delice-recipe-rating-count">(<span itemprop="ratingCount"><?php echo $dre_rating_count; ?></span> <?php echo esc_html( $lang_texts['ratings'] ); ?>)</span>
          <?php endif; ?>
          <meta itemprop="bestRating" content="5"><meta itemprop="worstRating" content="1">
        </div>
        <?php endif; ?>

        <?php if ( ( ! isset( $display_options['show_dietary_badges'] ) || $display_options['show_dietary_badges'] ) && ! empty( $dietary_meta ) ) : ?>
        <div class="delice-dietary-badges" style="justify-content:center;margin-top:10px;">
          <?php foreach ( $dietary_meta as $diet_key ) : if ( ! isset( $dietary_badge_labels[ $diet_key ] ) ) continue; ?>
            <span class="delice-dietary-badge delice-badge--<?php echo esc_attr( $diet_key ); ?>"><?php echo esc_html( $dietary_badge_labels[ $diet_key ] ); ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div><!-- /.delice-elegant-header -->

    <!-- ═══ META BAR ═════════════════════════════════════════════════════════ -->
    <?php
    $dre_show_prep     = $prep_time     && ( ! isset( $display_options['show_prep_time'] )   || $display_options['show_prep_time'] );
    $dre_show_cook     = $cook_time     && ( ! isset( $display_options['show_cook_time'] )   || $display_options['show_cook_time'] );
    $dre_show_total    = $total_time    && ( ! isset( $display_options['show_total_time'] )  || $display_options['show_total_time'] );
    $dre_show_servings = $servings      && ( ! isset( $display_options['show_servings'] )    || $display_options['show_servings'] );
    $dre_show_calories = $calories      && ( ! isset( $display_options['show_calories'] )    || $display_options['show_calories'] );
    $dre_show_diff     = $difficulty    && ( ! isset( $display_options['show_difficulty'] )  || $display_options['show_difficulty'] );
    ?>
    <?php if ( $dre_show_prep || $dre_show_cook || $dre_show_total || $dre_show_servings || $dre_show_calories ) : ?>
        <div class="delice-elegant-meta" role="list" aria-label="<?php esc_attr_e( 'Recipe details', 'delice-recipe-manager' ); ?>">

            <?php if ( $dre_show_prep ) : ?>
                <div class="delice-elegant-meta-item" role="listitem">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <div>
                        <span class="delice-elegant-meta-label"><?php echo esc_html( $lang_texts['prep_time'] ); ?></span>
                        <span class="delice-elegant-meta-value"><?php echo esc_html( $prep_time ); ?> <?php echo esc_html( $lang_texts['min'] ); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( $dre_show_cook ) : ?>
                <div class="delice-elegant-meta-item" role="listitem">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M3 11l19-9-9 19-2-8-8-2z"/>
                    </svg>
                    <div>
                        <span class="delice-elegant-meta-label"><?php echo esc_html( $lang_texts['cook_time'] ); ?></span>
                        <span class="delice-elegant-meta-value"><?php echo esc_html( $cook_time ); ?> <?php echo esc_html( $lang_texts['min'] ); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( $dre_show_total ) : ?>
                <div class="delice-elegant-meta-item delice-elegant-meta-item--total" role="listitem">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 14 14"/>
                    </svg>
                    <div>
                        <span class="delice-elegant-meta-label"><?php echo esc_html( $lang_texts['total_time'] ); ?></span>
                        <span class="delice-elegant-meta-value"><?php echo esc_html( $total_time ); ?> <?php echo esc_html( $lang_texts['min'] ); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( $dre_show_servings ) : ?>
                <div class="delice-elegant-meta-item" role="listitem">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <div>
                        <span class="delice-elegant-meta-label"><?php echo esc_html( $lang_texts['servings'] ); ?></span>
                        <span class="delice-elegant-meta-value"><?php echo esc_html( $servings ); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( $dre_show_calories ) : ?>
                <div class="delice-elegant-meta-item" role="listitem">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                    </svg>
                    <div>
                        <span class="delice-elegant-meta-label"><?php echo esc_html( $lang_texts['calories'] ); ?></span>
                        <span class="delice-elegant-meta-value"><?php echo esc_html( $calories ); ?> kcal</span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Last Updated (v3.6.0) — feature toggle v3.8.0 -->
            <?php
            $dre_pub = get_the_date( 'M j, Y', $recipe_id );
            $dre_upd = get_the_modified_date( 'M j, Y', $recipe_id );
            if ( ( ! isset( $display_options['show_last_updated'] ) || $display_options['show_last_updated'] ) && $dre_upd && $dre_upd !== $dre_pub ) : ?>
            <div class="delice-elegant-meta-item delice-recipe-updated" role="listitem">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4"/>
                </svg>
                <div>
                    <span class="delice-elegant-meta-label"><?php echo esc_html( $lang_texts['updated'] ); ?></span>
                    <span class="delice-elegant-meta-value"><?php echo esc_html( $dre_upd ); ?></span>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /.delice-elegant-meta -->
    <?php endif; ?>

    <!-- ═══ ACTIONS ══════════════════════════════════════════════════════════ -->
    <div class="delice-elegant-actions">
        <?php if ( ! isset( $display_options['show_print'] ) || $display_options['show_print'] ) : ?>
        <button class="delice-recipe-print-btn delice-elegant-btn" type="button" aria-label="<?php esc_attr_e( 'Print recipe', 'delice-recipe-manager' ); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="6,9 6,2 18,2 18,9"/>
                <path d="M6,18H4a2,2,0,0,1-2-2V11a2,2,0,0,1,2-2H20a2,2,0,0,1,2,2v5a2,2,0,0,1-2,2H18"/>
                <polyline points="6,14 18,14 18,22 6,22 6,14"/>
            </svg>
            <span><?php echo esc_html( $lang_texts['print'] ); ?></span>
        </button>
        <?php endif; ?>

        <?php if ( ! isset( $display_options['show_share'] ) || $display_options['show_share'] ) : ?>
        <div class="delice-recipe-share-dropdown delice-elegant-share-wrap">
            <button class="delice-recipe-share-btn delice-elegant-btn" type="button" aria-label="<?php esc_attr_e( 'Share recipe', 'delice-recipe-manager' ); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="18" cy="5" r="3"/>
                    <circle cx="6" cy="12" r="3"/>
                    <circle cx="18" cy="19" r="3"/>
                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                </svg>
                <span><?php echo esc_html( $lang_texts['share'] ); ?></span>
            </button>
            <div class="delice-recipe-share-menu delice-elegant-share-menu">
                <a href="#" class="delice-recipe-share-item" data-platform="facebook">
                    <svg class="delice-recipe-share-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    <?php esc_html_e( 'Facebook', 'delice-recipe-manager' ); ?>
                </a>
                <a href="#" class="delice-recipe-share-item" data-platform="twitter">
                    <svg class="delice-recipe-share-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
                    <?php esc_html_e( 'Twitter', 'delice-recipe-manager' ); ?>
                </a>
                <a href="#" class="delice-recipe-share-item" data-platform="pinterest">
                    <svg class="delice-recipe-share-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
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
        <?php endif; ?>

        <button class="delice-recipe-copy-ingredients delice-elegant-btn" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="9" y="9" width="13" height="13" rx="2"/>
                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
            </svg>
            <span><?php echo esc_html( $lang_texts['copy'] ); ?></span>
        </button>

        <?php if ( $reviews_enabled ) : ?>
            <button class="delice-recipe-rate-btn delice-elegant-btn delice-elegant-btn--rate" type="button" data-action="open-rating-modal" data-recipe-id="<?php echo esc_attr( $recipe_id ); ?>" aria-label="<?php esc_attr_e( 'Rate this recipe', 'delice-recipe-manager' ); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                <span><?php echo esc_html( $lang_texts['rate'] ); ?></span>
            </button>
        <?php endif; ?>

        <!-- Cook Mode (v3.6.0) — feature toggle v3.8.0 -->
        <?php if ( ! isset( $display_options['show_cook_mode'] ) || $display_options['show_cook_mode'] ) : ?>
        <div class="delice-cook-mode-wrap">
            <button class="delice-cook-mode-btn delice-elegant-btn" type="button" aria-pressed="false">
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
    </div><!-- /.delice-elegant-actions -->

    <hr class="delice-elegant-divider">

    <!-- ═══ BODY ══════════════════════════════════════════════════════════════ -->
    <?php
    // v3.8.4 — Affiliate link injection
    $dre_aff          = class_exists( 'Delice_Affiliate_Manager' )
        ? Delice_Affiliate_Manager::inject_links( is_array( $ingredients ) ? $ingredients : array(), absint( $recipe_id ) )
        : array( 'ingredients' => $ingredients, 'has_links' => false );
    $ingredients      = $dre_aff['ingredients'];
    $dre_has_aff      = $dre_aff['has_links'];
    $dre_aff_settings = class_exists( 'Delice_Affiliate_Manager' ) ? Delice_Affiliate_Manager::get_settings() : array();
    $dre_aff_disc_pos = $dre_aff_settings['disclosure_pos'] ?? 'top';
    if ( $dre_has_aff && $dre_aff_disc_pos === 'top' ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo Delice_Affiliate_Manager::get_disclosure_html();
    }

    $dre_body_first = true;

    // Equipment — v3.9.17
    if ( class_exists( 'Delice_Recipe_Equipment' ) &&
         ( ! isset( $display_options['show_equipment'] ) || ! empty( $display_options['show_equipment'] ) ) ) :
        $dre_equipment       = Delice_Recipe_Equipment::get_with_affiliate( $recipe_id );
        $dre_aff_settings_eq = $dre_aff_settings ?? array();
        if ( ! empty( $dre_equipment ) ) : ?>
    <style>
    #<?php echo $dre_id; ?> .delice-eq-grid{display:grid!important;grid-template-columns:repeat(auto-fill,minmax(200px,1fr))!important;gap:14px!important;padding:16px 0 24px!important;}
    #<?php echo $dre_id; ?> .delice-eq-card{display:flex!important;flex-direction:column!important;background:#faf8f5!important;border:1px solid #e7e5e4!important;border-radius:12px!important;overflow:hidden!important;transition:transform .2s,box-shadow .2s!important;}
    #<?php echo $dre_id; ?> .delice-eq-card:hover{transform:translateY(-3px)!important;box-shadow:0 10px 24px rgba(0,0,0,.08)!important;}
    #<?php echo $dre_id; ?> .delice-eq-card-top{padding:18px 16px 10px!important;display:flex!important;align-items:flex-start!important;gap:12px!important;flex:1!important;}
    #<?php echo $dre_id; ?> .delice-eq-icon{width:40px!important;height:40px!important;border-radius:50%!important;background:linear-gradient(135deg,#fdf4e7,#fde9c3)!important;display:flex!important;align-items:center!important;justify-content:center!important;flex-shrink:0!important;color:#a16207!important;}
    #<?php echo $dre_id; ?> .delice-eq-card-info{flex:1!important;min-width:0!important;}
    #<?php echo $dre_id; ?> .delice-eq-name{display:block!important;font-weight:600!important;font-size:14px!important;line-height:1.35!important;color:#292524!important;font-family:Georgia,serif!important;}
    #<?php echo $dre_id; ?> .delice-eq-notes{display:block!important;font-size:12px!important;color:#78716c!important;margin-top:4px!important;line-height:1.4!important;}
    #<?php echo $dre_id; ?> .delice-eq-badge-row{padding:0 16px 10px!important;}
    #<?php echo $dre_id; ?> .delice-eq-badge{display:inline-block!important;font-size:10px!important;font-weight:700!important;letter-spacing:.06em!important;text-transform:uppercase!important;padding:3px 9px!important;border-radius:20px!important;}
    #<?php echo $dre_id; ?> .delice-eq-badge--req{background:#fef3c7!important;color:#92400e!important;}
    #<?php echo $dre_id; ?> .delice-eq-badge--opt{background:#f5f5f4!important;color:#78716c!important;}
    #<?php echo $dre_id; ?> .delice-eq-btn-wrap{padding:0 16px 16px!important;}
    #<?php echo $dre_id; ?> .delice-eq-buy-btn{display:flex!important;align-items:center!important;justify-content:center!important;gap:7px!important;width:100%!important;box-sizing:border-box!important;padding:10px 14px!important;border-radius:8px!important;font-size:13px!important;font-weight:700!important;text-decoration:none!important;transition:transform .15s,filter .15s!important;white-space:nowrap!important;}
    #<?php echo $dre_id; ?> .delice-eq-buy-btn--amazon{background:linear-gradient(135deg,#ff9900,#e67700)!important;color:#111!important;box-shadow:0 3px 10px rgba(255,153,0,.4)!important;}
    #<?php echo $dre_id; ?> .delice-eq-buy-btn--shareasale{background:linear-gradient(135deg,#17b978,#0d9c63)!important;color:#fff!important;box-shadow:0 3px 10px rgba(23,185,120,.35)!important;}
    #<?php echo $dre_id; ?> .delice-eq-buy-btn--cj{background:linear-gradient(135deg,#0052b4,#003d8f)!important;color:#fff!important;box-shadow:0 3px 10px rgba(0,82,180,.3)!important;}
    #<?php echo $dre_id; ?> .delice-eq-buy-btn--impact{background:linear-gradient(135deg,#7c3aed,#5b21b6)!important;color:#fff!important;box-shadow:0 3px 10px rgba(124,58,237,.35)!important;}
    #<?php echo $dre_id; ?> .delice-eq-buy-btn--custom,#<?php echo $dre_id; ?> .delice-eq-buy-btn--default{background:linear-gradient(135deg,#57534e,#292524)!important;color:#fff!important;box-shadow:0 3px 10px rgba(41,37,36,.3)!important;}
    #<?php echo $dre_id; ?> .delice-eq-buy-btn:hover{transform:translateY(-1px)!important;filter:brightness(1.1)!important;}
    #<?php echo $dre_id; ?> .delice-eq-buy-btn svg{flex-shrink:0!important;}
    @media(max-width:600px){#<?php echo $dre_id; ?> .delice-eq-grid{grid-template-columns:repeat(2,1fr)!important;}}
    @media(max-width:380px){#<?php echo $dre_id; ?> .delice-eq-grid{grid-template-columns:1fr!important;}}
    </style>
    <div class="delice-elegant-section delice-elegant-equipment">
        <h3 class="delice-elegant-section-title">
            <span class="delice-elegant-section-ornament" aria-hidden="true">✦</span>
            <?php echo esc_html( $lang_texts['equipment'] ?? __( 'Equipment', 'delice-recipe-manager' ) ); ?>
        </h3>
        <div class="delice-eq-grid">
        <?php foreach ( $dre_equipment as $eq ) :
            $eq_url      = $eq['affiliate_url']   ?? '';
            $eq_store    = $eq['affiliate_store'] ?? '';
            $eq_open     = ! empty( $dre_aff_settings_eq['open_new_tab'] );
            $eq_btn_text = esc_html( $dre_aff_settings_eq['button_text'] ?? 'Shop Now' );
            $eq_platform = ! empty( $eq_store ) ? strtolower( preg_replace('/[^a-z0-9]/i', '', $eq_store) ) : 'default';
            $eq_btn_cls  = 'delice-eq-buy-btn delice-eq-buy-btn--' . esc_attr( $eq_platform );
        ?>
            <div class="delice-eq-card<?php echo $eq_url ? ' delice-eq-card--linked' : ''; ?>">
                <div class="delice-eq-card-top">
                    <div class="delice-eq-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    </div>
                    <div class="delice-eq-card-info">
                        <span class="delice-eq-name"><?php echo esc_html( $eq['name'] ); ?></span>
                        <?php if ( ! empty( $eq['notes'] ) ) : ?>
                        <span class="delice-eq-notes"><?php echo esc_html( $eq['notes'] ); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="delice-eq-badge-row">
                    <?php if ( ! empty( $eq['required'] ) ) : ?>
                    <span class="delice-eq-badge delice-eq-badge--req"><?php esc_html_e( 'Required', 'delice-recipe-manager' ); ?></span>
                    <?php else : ?>
                    <span class="delice-eq-badge delice-eq-badge--opt"><?php esc_html_e( 'Optional', 'delice-recipe-manager' ); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ( $eq_url ) : ?>
                <div class="delice-eq-btn-wrap">
                    <a href="<?php echo esc_url( $eq_url ); ?>"
                       class="<?php echo esc_attr( $eq_btn_cls ); ?>"
                       rel="<?php echo esc_attr( Delice_Affiliate_Manager::LINK_REL ); ?>"
                       <?php echo $eq_open ? 'target="_blank"' : ''; ?>
                       aria-label="<?php echo esc_attr( $eq_btn_text . ' — ' . $eq['name'] ); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        <?php echo $eq_btn_text; ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
        <?php
            $dre_body_first = false;
        endif;
    endif;

    if ( ! empty( $ingredients ) ) :
        if ( ! $dre_body_first ) echo '<hr class="delice-elegant-divider">';
        $dre_body_first = false;
    ?>
    <div id="delice-ingredients-<?php echo $dre_id; ?>" class="delice-elegant-section delice-elegant-ingredients">
        <h3 class="delice-elegant-section-title">
            <span class="delice-elegant-section-ornament" aria-hidden="true">✦</span>
            <?php echo esc_html( $lang_texts['ingredients'] ); ?>
            <span class="delice-elegant-section-ornament" aria-hidden="true">✦</span>
        </h3>
        <?php if ( $servings && ( ! isset( $display_options['show_servings'] ) || $display_options['show_servings'] ) ) : ?>
        <div style="display:flex;justify-content:center;margin-bottom:16px;">
          <div class="delice-servings-control" role="group" aria-label="<?php echo esc_attr( $lang_texts['servings'] ); ?>">
            <button class="delice-servings-btn delice-servings-minus" type="button" aria-label="Decrease servings" disabled>−</button>
            <span class="delice-servings-value" data-base="<?php echo esc_attr( intval( $servings ) ); ?>"><?php echo esc_html( intval( $servings ) ); ?></span>
            <button class="delice-servings-btn delice-servings-plus" type="button" aria-label="Increase servings">+</button>
            <span class="delice-servings-label"><?php echo esc_html( $lang_texts['servings'] ); ?></span>
            <span class="delice-servings-live" aria-live="polite" aria-atomic="true"><?php echo esc_html( intval( $servings ) ); ?></span>
          </div>
        </div>
        <?php endif; ?>
        <ul class="delice-elegant-ingredients-list">
            <?php foreach ( $ingredients as $ing ) :
                $ing_id        = 'ing-' . esc_attr( $recipe_id . '-' . sanitize_title( $ing['name'] ?? 'item' ) );
                $dre_aff_links = $ing['affiliate_links'] ?? array();
                $dre_has_aff   = ! empty( $dre_aff_links );
            ?>
                <li class="delice-elegant-ingredient delice-recipe-ingredient<?php echo $dre_has_aff ? ' delice-recipe-ingredient--linked' : ''; ?>">
                    <label class="delice-elegant-ingredient-inner" for="<?php echo esc_attr( $ing_id ); ?>">
                        <input type="checkbox" class="delice-recipe-ingredient-checkbox delice-elegant-checkbox" id="<?php echo esc_attr( $ing_id ); ?>">
                        <span class="delice-elegant-check-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="delice-elegant-ingredient-name delice-recipe-ingredient-name"><?php echo esc_html( $ing['name'] ?? '' ); ?></span>
                    </label>
                    <?php if ( ! empty( $ing['amount'] ) || ! empty( $ing['unit'] ) ) : ?>
                        <span class="delice-elegant-ingredient-qty"
                              data-base-amount="<?php echo esc_attr( $ing['amount'] ?? '' ); ?>"
                              data-base-unit="<?php echo esc_attr( $ing['unit'] ?? '' ); ?>">
                            <?php echo esc_html( trim( ( $ing['amount'] ?? '' ) . ' ' . ( $ing['unit'] ?? '' ) ) ); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ( $dre_has_aff ) :
                        echo Delice_Affiliate_Manager::render_ingredient_buttons( $dre_aff_links, $ing['name'] ?? '', $dre_aff_settings );
                    endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div><!-- /.delice-elegant-ingredients -->
    <?php endif;

    if ( ! empty( $instructions ) ) :
        if ( ! $dre_body_first ) echo '<hr class="delice-elegant-divider">';
        $dre_body_first = false;
    ?>
    <div class="delice-elegant-section delice-elegant-instructions">
        <h3 class="delice-elegant-section-title">
            <span class="delice-elegant-section-ornament" aria-hidden="true">✦</span>
            <?php echo esc_html( $lang_texts['instructions'] ); ?>
            <span class="delice-elegant-section-ornament" aria-hidden="true">✦</span>
        </h3>
        <ol class="delice-elegant-steps">
            <?php foreach ( $instructions as $idx => $step ) :
                $text = preg_replace( '/^(\d+[\.\)\:]\s*)+/i', '', $step['text'] ?? '' );
                $text = trim( $text );
            ?>
                <li class="delice-elegant-step">
                    <span class="delice-elegant-step-num" aria-hidden="true"><?php echo absint( $idx + 1 ); ?></span>
                    <p class="delice-elegant-step-text"><?php echo esc_html( $text ); ?></p>
                </li>
            <?php endforeach; ?>
        </ol>
    </div><!-- /.delice-elegant-instructions -->
    <?php endif; ?>

    <!-- ═══ NOTES ════════════════════════════════════════════════════════════ -->
    <?php if ( ! empty( $notes ) && ( ! isset( $display_options['show_notes'] ) || $display_options['show_notes'] ) ) : ?>
        <hr class="delice-elegant-divider">
        <div class="delice-elegant-section delice-elegant-notes">
            <h3 class="delice-elegant-section-title">
                <span class="delice-elegant-section-ornament" aria-hidden="true">✦</span>
                <?php echo esc_html( $lang_texts['notes'] ); ?>
                <span class="delice-elegant-section-ornament" aria-hidden="true">✦</span>
            </h3>
            <div class="delice-elegant-notes-text"><?php echo esc_html( $notes ); ?></div>
        </div><!-- /.delice-elegant-notes -->
    <?php endif; ?>

    <!-- ═══ NUTRITION ═════════════════════════════════════════════════════════ -->
    <?php if ( ! empty( $nutrition ) && ( ! isset( $display_options['show_nutrition'] ) || $display_options['show_nutrition'] ) ) : ?>
        <hr class="delice-elegant-divider">
        <div class="delice-elegant-section delice-elegant-nutrition">
            <h3 class="delice-elegant-section-title">
                <span class="delice-elegant-section-ornament" aria-hidden="true">✦</span>
                <?php echo esc_html( $lang_texts['nutrition'] ?? __( 'Nutrition', 'delice-recipe-manager' ) ); ?>
                <span class="delice-elegant-section-ornament" aria-hidden="true">✦</span>
            </h3>
            <div class="delice-elegant-nutrition-grid">
                <?php foreach ( $nutrition as $nutrient => $val ) : ?>
                    <div class="delice-elegant-nutrient">
                        <span class="delice-elegant-nutrient-value"><?php echo esc_html( $val ); ?><small>g</small></span>
                        <span class="delice-elegant-nutrient-label"><?php echo esc_html( ucfirst( $nutrient ) ); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="delice-recipe-nutrition-disclaimer"><?php echo esc_html( $lang_texts['nutrition_disclaimer'] ); ?></p>
        </div><!-- /.delice-elegant-nutrition -->
    <?php endif; ?>

    <!-- ═══ FAQs ══════════════════════════════════════════════════════════════ -->
    <?php if ( ! empty( $faqs ) && ( ! isset( $display_options['show_faqs'] ) || $display_options['show_faqs'] ) ) : ?>
        <hr class="delice-elegant-divider">
        <div class="delice-elegant-section delice-elegant-faqs">
            <h3 class="delice-elegant-section-title">
                <span class="delice-elegant-section-ornament" aria-hidden="true">✦</span>
                <?php printf( esc_html__( 'FAQ for %s', 'delice-recipe-manager' ), esc_html( get_the_title( $recipe_id ) ) ); ?>
                <span class="delice-elegant-section-ornament" aria-hidden="true">✦</span>
            </h3>
            <div class="delice-recipe-modern-faqs-list delice-elegant-faq-list">
                <?php foreach ( $faqs as $i => $faq ) : ?>
                    <div class="delice-recipe-modern-faq-item delice-elegant-faq-item">
                        <button
                            class="delice-recipe-modern-faq-question delice-elegant-faq-question"
                            type="button"
                            aria-expanded="false"
                            data-faq-index="<?php echo esc_attr( $i ); ?>"
                        >
                            <span><?php echo esc_html( $faq['question'] ); ?></span>
                            <span class="delice-recipe-modern-faq-toggle delice-elegant-faq-toggle" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </span>
                        </button>
                        <div class="delice-recipe-modern-faq-answer delice-elegant-faq-answer">
                            <p><?php echo esc_html( $faq['answer'] ); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div><!-- /.delice-elegant-faqs -->
    <?php endif; ?>

    <hr class="delice-elegant-divider">

    <!-- ═══ REVIEWS ═══════════════════════════════════════════════════════════ -->
    <?php if ( $reviews_enabled ) : ?>
        <div id="reviewSection-<?php echo esc_attr( $recipe_id ); ?>" class="delice-elegant-reviews delice-recipe-review-section">

            <div class="delice-recipe-review-header">
                <div class="delice-review-header-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                        <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2M7 2v20M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/>
                    </svg>
                </div>
                <h3><?php esc_html_e( 'Did You Make This Recipe?', 'delice-recipe-manager' ); ?></h3>
                <p class="delice-recipe-review-subtitle"><?php esc_html_e( "We'd love to see your creation! Share your tips, tweaks, and a photo.", 'delice-recipe-manager' ); ?></p>
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
                <label class="delice-recipe-rating-label"><?php esc_html_e( 'Rate this Recipe:', 'delice-recipe-manager' ); ?></label>
                <div class="delice-recipe-rating-stars" data-recipe-id="<?php echo esc_attr( $recipe_id ); ?>">
                    <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                        <span class="delice-rating-star" data-rating="<?php echo esc_attr( $i ); ?>">★</span>
                    <?php endfor; ?>
                </div>
                <span class="delice-recipe-rating-text"><?php esc_html_e( 'Select a rating', 'delice-recipe-manager' ); ?></span>
            </div>

            <!-- Review form -->
            <form class="delice-recipe-review-form delice-elegant-review-form" data-recipe-id="<?php echo esc_attr( $recipe_id ); ?>">

                <div class="delice-recipe-review-comment">
                    <label for="review-comment-<?php echo esc_attr( $recipe_id ); ?>">
                        <?php esc_html_e( 'Your Review:', 'delice-recipe-manager' ); ?>
                    </label>
                    <textarea
                        id="review-comment-<?php echo esc_attr( $recipe_id ); ?>"
                        name="comment"
                        rows="5"
                        required
                        placeholder="<?php esc_attr_e( 'Describe your cooking experience and any modifications you made...', 'delice-recipe-manager' ); ?>"
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
                            <span><?php esc_html_e( 'Showcase your creation', 'delice-recipe-manager' ); ?></span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="delice-recipe-review-submit delice-elegant-submit-btn">
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
                <p><?php esc_html_e( 'Your review has been submitted. Thank you!', 'delice-recipe-manager' ); ?></p>
            </div>

        </div><!-- /#reviewSection -->

        <div id="reviewsDisplay-<?php echo esc_attr( $recipe_id ); ?>" class="delice-recipe-reviews-display delice-elegant-reviews-display"></div>
    <?php endif; ?>

    <!-- ═══ RELATED RECIPES (v3.6.0) — feature toggle v3.8.0 ═══════════════ -->
    <?php if ( ( ! isset( $display_options['show_related_recipes'] ) || $display_options['show_related_recipes'] ) && class_exists( 'Delice_Recipe_Related' ) ) : ?>
    <div style="padding: 0 48px 8px;">
        <?php Delice_Recipe_Related::render( $recipe_id, $lang_texts['related_recipes'] ); ?>
    </div>
    <?php endif; ?>

    <!-- Affiliate disclosure (bottom position) — v3.8.4 -->
    <?php if ( $dre_has_aff && $dre_aff_disc_pos === 'bottom' ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo Delice_Affiliate_Manager::get_disclosure_html();
    } ?>

    <!-- ═══ FOOTER ═══════════════════════════════════════════════════════════ -->
    <div class="delice-elegant-footer">
        <?php if ( ! is_wp_error( $cuisine_terms ) && ! empty( $cuisine_terms ) ) : ?>
            <span class="delice-elegant-tag"><?php echo esc_html( $cuisine_terms[0]->name ); ?></span>
        <?php endif; ?>
        <?php if ( ! is_wp_error( $course_terms ) && ! empty( $course_terms ) ) : ?>
            <span class="delice-elegant-tag"><?php echo esc_html( $course_terms[0]->name ); ?></span>
        <?php endif; ?>
        <?php if ( ! is_wp_error( $dietary_terms ) && ! empty( $dietary_terms ) ) :
            foreach ( $dietary_terms as $dietary_term ) : ?>
                <span class="delice-elegant-tag delice-elegant-tag--dietary"><?php echo esc_html( $dietary_term->name ); ?></span>
            <?php endforeach;
        endif; ?>
    </div><!-- /.delice-elegant-footer -->

</div><!-- /.delice-elegant -->
