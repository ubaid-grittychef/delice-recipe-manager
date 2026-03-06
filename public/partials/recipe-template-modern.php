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

/* v3.6.0 — Dietary badges */
$dietary_badge_labels = array(
    'vegetarian'  => 'Vegetarian', 'vegan' => 'Vegan', 'gluten-free' => 'Gluten-Free',
    'dairy-free'  => 'Dairy-Free', 'nut-free' => 'Nut-Free', 'low-carb' => 'Low-Carb',
    'keto'        => 'Keto', 'paleo' => 'Paleo',
);
$dietary_meta = get_post_meta( $recipe_id, '_delice_recipe_dietary', true );
$dietary_meta = is_array( $dietary_meta ) ? $dietary_meta : array();

/* v3.6.0 — Rating summary */
$drm_rating_avg   = floatval( get_post_meta( $recipe_id, '_delice_recipe_rating_average', true ) );
$drm_rating_count = intval( get_post_meta( $recipe_id, '_delice_recipe_rating_count', true ) );
$drm_is_seed      = (bool) get_post_meta( $recipe_id, '_delice_recipe_is_seed_rating', true );
?>

<?php $drm_id = 'drm-' . absint( $recipe_id ); ?>
<style>
/* ── Specificity shield: ID-scoped rules beat Pixwell #main/#content overrides ── */
#<?php echo $drm_id; ?>,
#<?php echo $drm_id; ?> * { box-sizing: border-box !important; }
#<?php echo $drm_id; ?> { max-width: 960px !important; margin: 0 auto !important; overflow: hidden !important; display: block !important; }

/* ── Global element-level resets (themes add padding/margin/list-style to li/p/ul/svg/img) ── */
#<?php echo $drm_id; ?> ul,
#<?php echo $drm_id; ?> ol            { list-style: none !important; padding: 0 !important; margin: 0 !important; }
#<?php echo $drm_id; ?> li            { padding: 0 !important; margin: 0 !important; background: none !important; border: none !important; }
#<?php echo $drm_id; ?> li::before,
#<?php echo $drm_id; ?> li::after     { content: none !important; display: none !important; }
#<?php echo $drm_id; ?> p             { margin-top: 0 !important; margin-bottom: 0 !important; }
#<?php echo $drm_id; ?> svg           { display: inline-block !important; max-width: none !important; overflow: visible !important; flex-shrink: 0 !important; }
#<?php echo $drm_id; ?> img           { display: block !important; max-width: 100% !important; height: auto !important; }
#<?php echo $drm_id; ?> button        { font-family: inherit !important; cursor: pointer !important; }

/* ── Hero ── */
#<?php echo $drm_id; ?> .delice-modern-hero            { display: flex !important; align-items: flex-end !important; position: relative !important; min-height: 280px !important; }
#<?php echo $drm_id; ?> .delice-modern-hero-image      { position: absolute !important; inset: 0 !important; overflow: hidden !important; }
#<?php echo $drm_id; ?> .delice-modern-img             { width: 100% !important; height: 100% !important; object-fit: cover !important; display: block !important; }
#<?php echo $drm_id; ?> .delice-modern-hero-content    { position: relative !important; z-index: 1 !important; width: 100% !important; padding: 32px 36px 28px !important; }
#<?php echo $drm_id; ?> .delice-modern-meta-badges     { display: flex !important; flex-wrap: wrap !important; gap: 10px !important; margin: 0 !important; padding: 0 !important; }
#<?php echo $drm_id; ?> .delice-modern-badge           { display: flex !important; flex-direction: column !important; align-items: center !important; padding: 10px 16px !important; min-width: 80px !important; border-radius: 10px !important; text-align: center !important; gap: 3px !important; }

/* ── Toolbar ── */
#<?php echo $drm_id; ?> .delice-modern-toolbar         { display: flex !important; align-items: center !important; justify-content: space-between !important; flex-wrap: wrap !important; padding: 14px 24px !important; margin: 0 !important; }
#<?php echo $drm_id; ?> .delice-modern-author          { display: flex !important; align-items: center !important; flex-wrap: wrap !important; gap: 10px !important; }
#<?php echo $drm_id; ?> .delice-modern-author-avatar   { display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0 !important; width: 36px !important; height: 36px !important; border-radius: 50% !important; }
#<?php echo $drm_id; ?> .delice-modern-author-info     { display: flex !important; flex-direction: column !important; gap: 1px !important; }
#<?php echo $drm_id; ?> .delice-modern-actions         { display: flex !important; align-items: center !important; flex-wrap: wrap !important; gap: 8px !important; }
#<?php echo $drm_id; ?> .delice-modern-action-btn      { display: inline-flex !important; align-items: center !important; gap: 6px !important; padding: 8px 14px !important; border-radius: 8px !important; white-space: nowrap !important; }

/* ── Body & sections ── */
#<?php echo $drm_id; ?> .delice-modern-body            { padding: 28px 24px !important; background: #f8fafc !important; }
#<?php echo $drm_id; ?> .delice-modern-section         { display: block !important; overflow: hidden !important; margin-bottom: 20px !important; border-radius: 12px !important; }
/* ── Two-column layout: ingredients (left) + instructions (right) ── */
#<?php echo $drm_id; ?> .delice-modern-cols            { display: grid !important; grid-template-columns: 5fr 7fr !important; gap: 20px !important; margin-bottom: 20px !important; align-items: start !important; }
#<?php echo $drm_id; ?> .delice-modern-cols .delice-modern-section { margin-bottom: 0 !important; }
#<?php echo $drm_id; ?> .delice-modern-section-header  { display: flex !important; align-items: center !important; justify-content: space-between !important; padding: 18px 20px 14px !important; margin: 0 !important; }
#<?php echo $drm_id; ?> .delice-modern-section-title   { display: flex !important; align-items: center !important; gap: 10px !important; padding: 18px 20px 14px !important; margin: 0 !important; }
#<?php echo $drm_id; ?> .delice-modern-section-header .delice-modern-section-title { padding: 0 !important; border-bottom: none !important; }
#<?php echo $drm_id; ?> .delice-modern-section-icon    { display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0 !important; width: 32px !important; height: 32px !important; min-width: 32px !important; border-radius: 8px !important; }
#<?php echo $drm_id; ?> .delice-modern-copy-btn        { display: inline-flex !important; align-items: center !important; gap: 5px !important; padding: 5px 10px !important; border-radius: 6px !important; white-space: nowrap !important; flex-shrink: 0 !important; }

/* ── Ingredients (re-declare padding after global li reset) ── */
#<?php echo $drm_id; ?> .delice-modern-ingredients-list { list-style: none !important; padding: 8px 0 12px !important; margin: 0 !important; }
#<?php echo $drm_id; ?> .delice-modern-ingredient      { display: flex !important; align-items: center !important; justify-content: space-between !important; gap: 12px !important; padding: 9px 20px !important; margin: 0 !important; border: none !important; border-bottom: 1px solid #f8fafc !important; background: none !important; }
#<?php echo $drm_id; ?> .delice-modern-ingredient:last-child { border-bottom: none !important; }
#<?php echo $drm_id; ?> .delice-modern-ingredient-label{ display: flex !important; align-items: center !important; gap: 10px !important; flex: 1 !important; min-width: 0 !important; cursor: pointer !important; }
#<?php echo $drm_id; ?> .delice-recipe-ingredient-checkbox { position: absolute !important; opacity: 0 !important; width: 0 !important; height: 0 !important; pointer-events: none !important; }
#<?php echo $drm_id; ?> .delice-modern-checkbox-mark   { display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0 !important; width: 20px !important; height: 20px !important; min-width: 20px !important; border-radius: 5px !important; }
#<?php echo $drm_id; ?> .delice-modern-ingredient-name { line-height: 1.4 !important; overflow: hidden !important; text-overflow: ellipsis !important; }
#<?php echo $drm_id; ?> .delice-modern-ingredient-qty  { white-space: nowrap !important; flex-shrink: 0 !important; border-radius: 5px !important; padding: 3px 8px !important; }

/* ── Steps (re-declare padding after global li reset) ── */
#<?php echo $drm_id; ?> .delice-modern-steps           { list-style: none !important; margin: 0 !important; padding: 12px 20px 20px !important; }
#<?php echo $drm_id; ?> .delice-modern-step            { display: flex !important; align-items: flex-start !important; gap: 16px !important; padding: 14px 0 !important; margin: 0 !important; border: none !important; border-bottom: 1px dashed #f8fafc !important; background: none !important; }
#<?php echo $drm_id; ?> .delice-modern-step:last-child { border-bottom: none !important; padding-bottom: 4px !important; }
#<?php echo $drm_id; ?> .delice-modern-step-num        { display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0 !important; width: 36px !important; height: 36px !important; min-width: 36px !important; border-radius: 50% !important; }
#<?php echo $drm_id; ?> .delice-modern-step-body       { flex: 1 !important; min-width: 0 !important; }
#<?php echo $drm_id; ?> .delice-modern-step-text       { margin: 0 !important; line-height: 1.7 !important; }

/* ── Nutrition ── */
#<?php echo $drm_id; ?> .delice-modern-nutrition-grid  { display: grid !important; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)) !important; gap: 1px !important; background: #e9ecef !important; }
#<?php echo $drm_id; ?> .delice-modern-nutrient        { display: flex !important; flex-direction: column !important; align-items: center !important; padding: 14px 10px !important; text-align: center !important; gap: 3px !important; background: #fff !important; }
#<?php echo $drm_id; ?> .delice-recipe-nutrition-disclaimer { padding: 10px 20px 14px !important; }

/* ── FAQs ── */
#<?php echo $drm_id; ?> .delice-modern-faq-question    { display: flex !important; align-items: center !important; justify-content: space-between !important; width: 100% !important; gap: 12px !important; padding: 14px 20px !important; background: none !important; border: none !important; text-align: left !important; color: #1a1a1a !important; }
#<?php echo $drm_id; ?> .delice-modern-faq-icon        { display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0 !important; width: 28px !important; height: 28px !important; min-width: 28px !important; border-radius: 50% !important; background: #f0f0f0 !important; transition: background 0.2s, transform 0.25s !important; }
#<?php echo $drm_id; ?> .delice-recipe-modern-faq-item.faq-open .delice-modern-faq-icon { background: #FF6B35 !important; transform: rotate(180deg) !important; }
#<?php echo $drm_id; ?> .delice-modern-faq-icon svg    { width: 14px !important; height: 14px !important; stroke: #555 !important; }
#<?php echo $drm_id; ?> .delice-recipe-modern-faq-item.faq-open .delice-modern-faq-icon svg { stroke: #fff !important; }
/* FAQ accordion — display controlled by JS (style.setProperty) — CSS here is a fallback */
#<?php echo $drm_id; ?> .delice-recipe-modern-faq-answer { display: none !important; overflow: hidden !important; }
#<?php echo $drm_id; ?> .delice-recipe-modern-faq-item.faq-open .delice-recipe-modern-faq-answer { display: block !important; overflow: visible !important; }
#<?php echo $drm_id; ?> .delice-modern-faq-answer      { padding: 0 20px 16px !important; }

/* ── Semantic elements (theme styles footer/section/article directly) ── */
#<?php echo $drm_id; ?> footer.delice-modern-footer    { display: flex !important; flex-wrap: wrap !important; align-items: center !important; gap: 8px !important; padding: 16px 24px !important; margin: 0 !important; border-top: none !important; }
#<?php echo $drm_id; ?> .delice-modern-footer-tag      { display: inline-flex !important; align-items: center !important; gap: 6px !important; border-radius: 20px !important; padding: 5px 12px !important; white-space: nowrap !important; }
#<?php echo $drm_id; ?> section.delice-modern-reviews  { margin: 24px 0 0 !important; padding: 0 !important; border: none !important; display: block !important; overflow: hidden !important; border-radius: 12px !important; }
#<?php echo $drm_id; ?> .delice-modern-review-form     { display: flex !important; flex-direction: column !important; gap: 16px !important; padding: 16px 28px 24px !important; }
#<?php echo $drm_id; ?> .delice-modern-submit-btn      { display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 8px !important; border-radius: 8px !important; padding: 13px 28px !important; }

/* ── Responsive ── */
@media (max-width: 680px) {
    #<?php echo $drm_id; ?> .delice-modern-hero-content { padding: 24px 20px 20px !important; }
    #<?php echo $drm_id; ?> .delice-modern-toolbar  { flex-direction: column !important; align-items: flex-start !important; padding: 12px 16px !important; }
    #<?php echo $drm_id; ?> .delice-modern-actions  { width: 100% !important; justify-content: flex-start !important; }
    #<?php echo $drm_id; ?> .delice-modern-body     { padding: 16px !important; }
    #<?php echo $drm_id; ?> .delice-modern-cols     { grid-template-columns: 1fr !important; gap: 16px !important; }
}

/* ══════════════════════════════════════════════════════════════
   EDITORIAL BOLD DESIGN — Modern Template
   Dark headers · high-contrast · magazine editorial aesthetic
   ══════════════════════════════════════════════════════════════ */

/* ── Section cards: sharp edge with left accent ── */
#<?php echo $drm_id; ?> .delice-modern-section {
    border-radius: 10px !important;
    border: none !important;
    box-shadow: 0 3px 16px rgba(0,0,0,.10) !important;
    overflow: hidden !important;
}

/* ── Ingredients section: dark charcoal header ── */
#<?php echo $drm_id; ?> .delice-modern-section--ingredients .delice-modern-section-header {
    background: #1e293b !important;
    padding: 16px 20px 14px !important;
    border-bottom: 3px solid #f97316 !important;
}
#<?php echo $drm_id; ?> .delice-modern-section--ingredients .delice-modern-section-title {
    color: #fff !important;
    font-size: 15px !important;
    font-weight: 800 !important;
    letter-spacing: .04em !important;
    text-transform: uppercase !important;
    padding: 0 !important;
}
#<?php echo $drm_id; ?> .delice-modern-section--ingredients .delice-modern-section-icon {
    background: rgba(249,115,22,.25) !important;
}
#<?php echo $drm_id; ?> .delice-modern-section--ingredients .delice-modern-section-icon svg {
    stroke: #f97316 !important;
}

/* ── Instructions section: dark navy header ── */
#<?php echo $drm_id; ?> .delice-modern-section--instructions .delice-modern-section-title {
    background: #0f172a !important;
    padding: 16px 20px !important;
    color: #fff !important;
    font-size: 15px !important;
    font-weight: 800 !important;
    letter-spacing: .04em !important;
    text-transform: uppercase !important;
    border-bottom: 3px solid #0ea5e9 !important;
    margin: 0 !important;
}
#<?php echo $drm_id; ?> .delice-modern-section--instructions .delice-modern-section-icon {
    background: rgba(14,165,233,.25) !important;
}
#<?php echo $drm_id; ?> .delice-modern-section--instructions .delice-modern-section-icon svg {
    stroke: #7dd3fc !important;
}

/* ── Servings control on dark bg: white text ── */
#<?php echo $drm_id; ?> .delice-modern-section--ingredients .delice-servings-control {
    border-color: rgba(255,255,255,.3) !important;
    background: rgba(255,255,255,.1) !important;
}
#<?php echo $drm_id; ?> .delice-modern-section--ingredients .delice-servings-btn {
    color: #fdba74 !important;
}
#<?php echo $drm_id; ?> .delice-modern-section--ingredients .delice-servings-btn:hover:not(:disabled) {
    background: #f97316 !important;
    color: #fff !important;
}
#<?php echo $drm_id; ?> .delice-modern-section--ingredients .delice-servings-value {
    color: #fff !important;
    font-weight: 700 !important;
}
#<?php echo $drm_id; ?> .delice-modern-section--ingredients .delice-servings-label {
    color: rgba(255,255,255,.7) !important;
}

/* ── Copy button on dark bg ── */
#<?php echo $drm_id; ?> .delice-modern-section--ingredients .delice-modern-copy-btn {
    background: rgba(249,115,22,.2) !important;
    color: #fdba74 !important;
    border: 1px solid rgba(249,115,22,.4) !important;
    font-size: 12px !important;
    font-weight: 600 !important;
}
#<?php echo $drm_id; ?> .delice-modern-section--ingredients .delice-modern-copy-btn:hover {
    background: #f97316 !important;
    color: #fff !important;
    border-color: #f97316 !important;
}

/* ── Ingredient list: clean white bg, alternating rows ── */
#<?php echo $drm_id; ?> .delice-modern-ingredients-list {
    padding: 4px 0 8px !important;
    background: #fff !important;
}
#<?php echo $drm_id; ?> .delice-modern-ingredient {
    padding: 10px 18px !important;
    border-bottom: 1px solid #f1f5f9 !important;
    gap: 10px !important;
    align-items: center !important;
    background: #fff !important;
}
#<?php echo $drm_id; ?> .delice-modern-ingredient:nth-child(even) {
    background: #f8fafc !important;
}
#<?php echo $drm_id; ?> .delice-modern-ingredient:last-child {
    border-bottom: none !important;
}
#<?php echo $drm_id; ?> .delice-modern-ingredient-label {
    gap: 9px !important;
}

/* ── Ingredient quantity: bold orange badge ── */
#<?php echo $drm_id; ?> .delice-modern-ingredient-qty {
    background: #f97316 !important;
    color: #fff !important;
    font-size: 11.5px !important;
    font-weight: 700 !important;
    padding: 4px 10px !important;
    border-radius: 20px !important;
    white-space: nowrap !important;
    flex-shrink: 0 !important;
    line-height: 1.5 !important;
}

/* ── Ingredient name: medium weight dark ── */
#<?php echo $drm_id; ?> .delice-modern-ingredient-name {
    font-size: 14px !important;
    font-weight: 500 !important;
    color: #1e293b !important;
    line-height: 1.4 !important;
}

/* ── Checkbox mark: orange square indicator ── */
#<?php echo $drm_id; ?> .delice-modern-checkbox-mark {
    width: 18px !important;
    height: 18px !important;
    min-width: 18px !important;
    border: 2px solid #cbd5e1 !important;
    border-radius: 4px !important;
    background: #fff !important;
    flex-shrink: 0 !important;
    transition: background .15s, border-color .15s !important;
}

/* ── Instruction steps: white bg, bold numbered cards ── */
#<?php echo $drm_id; ?> .delice-modern-steps {
    background: #fff !important;
    padding: 8px 20px 20px !important;
}
#<?php echo $drm_id; ?> .delice-modern-step {
    padding: 14px 0 !important;
    border-bottom: 1px solid #f1f5f9 !important;
    gap: 16px !important;
    background: none !important;
}
#<?php echo $drm_id; ?> .delice-modern-step:last-child {
    border-bottom: none !important;
    padding-bottom: 6px !important;
}
#<?php echo $drm_id; ?> .delice-modern-step-num {
    width: 34px !important;
    height: 34px !important;
    min-width: 34px !important;
    background: #0f172a !important;
    color: #7dd3fc !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    font-weight: 900 !important;
    font-style: italic !important;
    letter-spacing: -.02em !important;
    box-shadow: none !important;
}
#<?php echo $drm_id; ?> .delice-modern-step-text {
    font-size: 14px !important;
    line-height: 1.7 !important;
    color: #334155 !important;
}
</style>
<?php
// Visible breadcrumb (v3.6.0) — respect show_breadcrumb toggle (v3.8.0)
$drm_show_breadcrumb = ! isset( $display_options['show_breadcrumb'] ) || $display_options['show_breadcrumb'];
if ( $drm_show_breadcrumb && ! defined( 'WPSEO_VERSION' ) && ! defined( 'RANK_MATH_VERSION' ) ) :
    $drm_bc_mid = ( ! is_wp_error( $cuisine_terms ) && ! empty( $cuisine_terms ) ) ? $cuisine_terms[0]
               : ( ( ! is_wp_error( $course_terms ) && ! empty( $course_terms ) ) ? $course_terms[0] : null );
?>
<nav class="delice-recipe-breadcrumb" aria-label="Breadcrumb">
  <ol class="delice-recipe-breadcrumb-list">
    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( $lang_texts['home'] ); ?></a></li>
    <?php if ( $drm_bc_mid ) : ?>
      <li><a href="<?php echo esc_url( get_term_link( $drm_bc_mid ) ); ?>"><?php echo esc_html( $drm_bc_mid->name ); ?></a></li>
    <?php endif; ?>
    <li aria-current="page"><?php echo esc_html( $recipe_title ); ?></li>
  </ol>
</nav>
<?php endif; ?>
<div id="<?php echo $drm_id; ?>" class="delice-recipe-wrapper delice-modern delice-recipe-container" data-recipe-id="<?php echo esc_attr( $recipe_id ); ?>">

    <!-- ═══ HERO ═══════════════════════════════════════════════════════════════ -->
    <div class="delice-modern-hero<?php echo $has_image && ! empty( $display_options['show_image'] ) ? ' delice-modern-hero--has-image' : ''; ?>">

        <?php if ( $has_image && ! empty( $display_options['show_image'] ) ) : ?>
            <div class="delice-modern-hero-image">
                <?php
                // WebP <picture> element (v3.8.0)
                $drm_thumb_id  = get_post_thumbnail_id( $recipe_id );
                $drm_img_src   = $drm_thumb_id ? wp_get_attachment_image_src( $drm_thumb_id, 'large' ) : null;
                $drm_webp_url  = '';
                if ( $drm_thumb_id && $drm_img_src ) {
                    $drm_meta = wp_get_attachment_metadata( $drm_thumb_id );
                    $drm_base = trailingslashit( dirname( wp_get_attachment_url( $drm_thumb_id ) ) );
                    if ( isset( $drm_meta['sizes']['large']['sources']['image/webp']['file'] ) ) {
                        $drm_webp_url = $drm_base . $drm_meta['sizes']['large']['sources']['image/webp']['file'];
                    } elseif ( isset( $drm_meta['sources']['image/webp']['file'] ) ) {
                        $drm_webp_url = $drm_base . $drm_meta['sources']['image/webp']['file'];
                    }
                }
                if ( $drm_img_src ) :
                ?>
                <picture>
                    <?php if ( $drm_webp_url ) : ?>
                    <source srcset="<?php echo esc_url( $drm_webp_url ); ?>" type="image/webp">
                    <?php endif; ?>
                    <img src="<?php echo esc_url( $drm_img_src[0] ); ?>"
                         class="delice-modern-img"
                         alt="<?php echo esc_attr( $recipe_title ); ?>"
                         fetchpriority="high"
                         width="<?php echo intval( $drm_img_src[1] ); ?>"
                         height="<?php echo intval( $drm_img_src[2] ); ?>">
                </picture>
                <?php endif; ?>
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

            <?php if ( $drm_rating_count > 0 && ( ! isset( $display_options['show_rating'] ) || $display_options['show_rating'] ) ) : ?>
            <div class="delice-recipe-rating-summary" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
              <div class="delice-recipe-rating-stars-display" aria-hidden="true">
                <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                  <span class="delice-rating-star-display<?php echo $i <= round( $drm_rating_avg ) ? ' filled' : ''; ?>">★</span>
                <?php endfor; ?>
              </div>
              <span class="delice-recipe-rating-score" itemprop="ratingValue"><?php echo number_format( $drm_rating_avg, 1 ); ?></span>
              <?php if ( $drm_is_seed ) : ?>
              <span class="delice-recipe-rating-count"><?php esc_html_e( 'Editor Tested', 'delice-recipe-manager' ); ?><meta itemprop="ratingCount" content="1"></span>
              <?php else : ?>
              <span class="delice-recipe-rating-count">(<span itemprop="ratingCount"><?php echo $drm_rating_count; ?></span> <?php echo esc_html( $lang_texts['ratings'] ); ?>)</span>
              <?php endif; ?>
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

            <!-- Cook Mode (v3.6.0) — feature toggle v3.8.0 -->
            <?php if ( ! isset( $display_options['show_cook_mode'] ) || $display_options['show_cook_mode'] ) : ?>
            <div class="delice-cook-mode-wrap">
                <button class="delice-cook-mode-btn delice-modern-action-btn" type="button" aria-pressed="false">
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

            <!-- Last Updated (v3.6.0) — feature toggle v3.8.0 -->
            <?php
            $drm_pub = get_the_date( 'M j, Y', $recipe_id );
            $drm_upd = get_the_modified_date( 'M j, Y', $recipe_id );
            if ( ( ! isset( $display_options['show_last_updated'] ) || $display_options['show_last_updated'] ) && $drm_upd && $drm_upd !== $drm_pub ) : ?>
            <span class="delice-modern-updated-badge">
                <?php echo esc_html( $lang_texts['updated'] ); ?>: <?php echo esc_html( $drm_upd ); ?>
            </span>
            <?php endif; ?>
        </div><!-- /.delice-modern-actions -->
    </div><!-- /.delice-modern-toolbar -->

    <!-- ═══ BODY ══════════════════════════════════════════════════════════════ -->
    <div class="delice-modern-body">

        <?php
        // v3.8.4 — Affiliate link injection
        $drm_aff          = class_exists( 'Delice_Affiliate_Manager' )
            ? Delice_Affiliate_Manager::inject_links( is_array( $ingredients ) ? $ingredients : array(), absint( $recipe_id ) )
            : array( 'ingredients' => $ingredients, 'has_links' => false );
        $ingredients      = $drm_aff['ingredients'];
        $drm_has_aff      = $drm_aff['has_links'];
        $drm_aff_settings = class_exists( 'Delice_Affiliate_Manager' ) ? Delice_Affiliate_Manager::get_settings() : array();
        $drm_aff_disc_pos = $drm_aff_settings['disclosure_pos'] ?? 'top';
        if ( $drm_has_aff && $drm_aff_disc_pos === 'top' ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo Delice_Affiliate_Manager::get_disclosure_html();
        }

        $has_ing  = ! empty( $ingredients );
        $has_inst = ! empty( $instructions );
        $two_col  = $has_ing && $has_inst;
        ?>

        <!-- ── Equipment — v3.9.17 ──────────────────────────────────────────── -->
        <?php
        if ( class_exists( 'Delice_Recipe_Equipment' ) &&
             ( ! isset( $display_options['show_equipment'] ) || ! empty( $display_options['show_equipment'] ) ) ) :
            $drm_equipment       = Delice_Recipe_Equipment::get_with_affiliate( $recipe_id );
            $drm_aff_settings_eq = $drm_aff_settings ?? array();
            if ( ! empty( $drm_equipment ) ) :
        ?>
        <style>
        #<?php echo $drm_id; ?> .delice-eq-grid{display:grid!important;grid-template-columns:repeat(auto-fill,minmax(190px,1fr))!important;gap:14px!important;padding:14px 0 22px!important;}
        #<?php echo $drm_id; ?> .delice-eq-card{display:flex!important;flex-direction:column!important;background:#fff!important;border:1px solid #e5e7eb!important;border-radius:14px!important;overflow:hidden!important;transition:transform .2s,box-shadow .2s!important;}
        #<?php echo $drm_id; ?> .delice-eq-card:hover{transform:translateY(-3px)!important;box-shadow:0 10px 28px rgba(0,0,0,.1)!important;}
        #<?php echo $drm_id; ?> .delice-eq-card-top{padding:18px 16px 10px!important;display:flex!important;align-items:flex-start!important;gap:12px!important;flex:1!important;}
        #<?php echo $drm_id; ?> .delice-eq-icon{width:42px!important;height:42px!important;border-radius:50%!important;background:linear-gradient(135deg,#fff7ed,#fed7aa)!important;display:flex!important;align-items:center!important;justify-content:center!important;flex-shrink:0!important;color:#ea580c!important;}
        #<?php echo $drm_id; ?> .delice-eq-card-info{flex:1!important;min-width:0!important;}
        #<?php echo $drm_id; ?> .delice-eq-name{display:block!important;font-weight:600!important;font-size:14px!important;line-height:1.35!important;color:#111827!important;}
        #<?php echo $drm_id; ?> .delice-eq-notes{display:block!important;font-size:12px!important;color:#6b7280!important;margin-top:4px!important;line-height:1.4!important;}
        #<?php echo $drm_id; ?> .delice-eq-badge-row{padding:0 16px 10px!important;}
        #<?php echo $drm_id; ?> .delice-eq-badge{display:inline-block!important;font-size:10px!important;font-weight:700!important;letter-spacing:.05em!important;text-transform:uppercase!important;padding:3px 9px!important;border-radius:20px!important;}
        #<?php echo $drm_id; ?> .delice-eq-badge--req{background:#fef3c7!important;color:#92400e!important;}
        #<?php echo $drm_id; ?> .delice-eq-badge--opt{background:#f3f4f6!important;color:#6b7280!important;}
        #<?php echo $drm_id; ?> .delice-eq-btn-wrap{padding:0 16px 16px!important;}
        #<?php echo $drm_id; ?> .delice-eq-buy-btn{display:flex!important;align-items:center!important;justify-content:center!important;gap:7px!important;width:100%!important;box-sizing:border-box!important;padding:10px 14px!important;border-radius:9px!important;font-size:13px!important;font-weight:700!important;text-decoration:none!important;transition:transform .15s,filter .15s!important;white-space:nowrap!important;}
        #<?php echo $drm_id; ?> .delice-eq-buy-btn--amazon{background:linear-gradient(135deg,#ff9900,#e67700)!important;color:#111!important;box-shadow:0 3px 10px rgba(255,153,0,.4)!important;}
        #<?php echo $drm_id; ?> .delice-eq-buy-btn--shareasale{background:linear-gradient(135deg,#17b978,#0d9c63)!important;color:#fff!important;box-shadow:0 3px 10px rgba(23,185,120,.35)!important;}
        #<?php echo $drm_id; ?> .delice-eq-buy-btn--cj{background:linear-gradient(135deg,#0052b4,#003d8f)!important;color:#fff!important;box-shadow:0 3px 10px rgba(0,82,180,.3)!important;}
        #<?php echo $drm_id; ?> .delice-eq-buy-btn--impact{background:linear-gradient(135deg,#7c3aed,#5b21b6)!important;color:#fff!important;box-shadow:0 3px 10px rgba(124,58,237,.35)!important;}
        #<?php echo $drm_id; ?> .delice-eq-buy-btn--custom,#<?php echo $drm_id; ?> .delice-eq-buy-btn--default{background:linear-gradient(135deg,#334155,#1e293b)!important;color:#fff!important;box-shadow:0 3px 10px rgba(30,41,59,.3)!important;}
        #<?php echo $drm_id; ?> .delice-eq-buy-btn:hover{transform:translateY(-1px)!important;filter:brightness(1.1)!important;}
        #<?php echo $drm_id; ?> .delice-eq-buy-btn svg{flex-shrink:0!important;}
        @media(max-width:600px){#<?php echo $drm_id; ?> .delice-eq-grid{grid-template-columns:repeat(2,1fr)!important;}}
        @media(max-width:380px){#<?php echo $drm_id; ?> .delice-eq-grid{grid-template-columns:1fr!important;}}
        </style>
        <div class="delice-modern-section delice-modern-section--equipment">
            <div class="delice-modern-section-header">
                <h3 class="delice-modern-section-title">
                    <span class="delice-modern-section-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    </span>
                    <?php echo esc_html( $lang_texts['equipment'] ?? __( 'Equipment', 'delice-recipe-manager' ) ); ?>
                </h3>
            </div>
            <div class="delice-eq-grid">
            <?php foreach ( $drm_equipment as $eq ) :
                $eq_url      = $eq['affiliate_url']   ?? '';
                $eq_store    = $eq['affiliate_store'] ?? '';
                $eq_open     = ! empty( $drm_aff_settings_eq['open_new_tab'] );
                $eq_btn_text = esc_html( $drm_aff_settings_eq['button_text'] ?? 'Shop Now' );
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
        <?php endif; endif; ?>

        <?php if ( $two_col ) echo '<div class="delice-modern-cols">'; ?>

        <!-- ── Ingredients ───────────────────────────────────────────────────── -->
        <?php if ( $has_ing ) : ?>
            <div class="delice-modern-section delice-modern-section--ingredients">
                <div class="delice-modern-section-header">
                    <h3 class="delice-modern-section-title">
                        <span class="delice-modern-section-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/>
                            </svg>
                        </span>
                        <?php echo esc_html( $lang_texts['ingredients'] ); ?>
                    </h3>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <?php if ( $servings ) : ?>
                    <div class="delice-servings-control" role="group" aria-label="<?php echo esc_attr( $lang_texts['servings'] ); ?>">
                        <button class="delice-servings-btn delice-servings-minus" type="button" aria-label="Decrease servings" disabled>−</button>
                        <span class="delice-servings-value" data-base="<?php echo esc_attr( intval( $servings ) ); ?>"><?php echo esc_html( intval( $servings ) ); ?></span>
                        <button class="delice-servings-btn delice-servings-plus" type="button" aria-label="Increase servings">+</button>
                        <span class="delice-servings-label"><?php echo esc_html( $lang_texts['servings'] ); ?></span>
                        <span class="delice-servings-live" aria-live="polite" aria-atomic="true"><?php echo esc_html( intval( $servings ) ); ?></span>
                    </div>
                    <?php endif; ?>
                    <button class="delice-recipe-copy-ingredients delice-modern-copy-btn" type="button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                        </svg>
                        <?php echo esc_html( $lang_texts['copy'] ); ?>
                    </button>
                    </div>
                </div>
                <ul class="delice-modern-ingredients-list">
                    <?php foreach ( $ingredients as $ing ) :
                        $ing_id       = 'ingr-' . esc_attr( $recipe_id . '-' . sanitize_title( $ing['name'] ?? 'item' ) );
                        $drm_aff_links = $ing['affiliate_links'] ?? array();
                        $drm_has_aff   = ! empty( $drm_aff_links );
                    ?>
                        <li class="delice-modern-ingredient delice-recipe-ingredient<?php echo $drm_has_aff ? ' delice-recipe-ingredient--linked' : ''; ?>">
                            <label class="delice-modern-ingredient-label" for="<?php echo esc_attr( $ing_id ); ?>">
                                <input type="checkbox" class="delice-recipe-ingredient-checkbox" id="<?php echo esc_attr( $ing_id ); ?>">
                                <span class="delice-modern-checkbox-mark" aria-hidden="true"></span>
                                <span class="delice-modern-ingredient-name delice-recipe-ingredient-name"><?php echo esc_html( $ing['name'] ?? '' ); ?></span>
                            </label>
                            <?php if ( ! empty( $ing['amount'] ) || ! empty( $ing['unit'] ) ) : ?>
                                <span class="delice-modern-ingredient-qty"
                                      data-base-amount="<?php echo esc_attr( $ing['amount'] ?? '' ); ?>"
                                      data-base-unit="<?php echo esc_attr( $ing['unit'] ?? '' ); ?>">
                                    <?php echo esc_html( trim( ( $ing['amount'] ?? '' ) . ' ' . ( $ing['unit'] ?? '' ) ) ); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ( $drm_has_aff ) :
                                echo Delice_Affiliate_Manager::render_ingredient_buttons( $drm_aff_links, $ing['name'] ?? '', $drm_aff_settings );
                            endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div><!-- /.delice-modern-section (ingredients) -->
        <?php endif; ?>

        <!-- ── Instructions ──────────────────────────────────────────────────── -->
        <?php if ( $has_inst ) : ?>
            <div class="delice-modern-section delice-modern-section--instructions">
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

        <?php if ( $two_col ) echo '</div><!-- /.delice-modern-cols -->'; ?>

        <!-- ── Nutrition ──────────────────────────────────────────────────────── -->
        <?php if ( ! empty( $nutrition ) && ( ! isset( $display_options['show_nutrition'] ) || $display_options['show_nutrition'] ) ) : ?>
            <div class="delice-modern-section delice-modern-section--nutrition delice-modern-nutrition">
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
                <?php if ( ! isset( $display_options['show_nutrition_disclaimer'] ) || $display_options['show_nutrition_disclaimer'] ) : ?>
                    <p class="delice-recipe-nutrition-disclaimer"><?php echo esc_html( $lang_texts['nutrition_disclaimer'] ); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ── Notes ─────────────────────────────────────────────────────────── -->
        <?php if ( ! empty( $notes ) ) : ?>
            <div class="delice-modern-section delice-modern-section--notes delice-modern-notes">
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

        <!-- ── FAQs ──────────────────────────────────────────────────────── -->
        <?php if ( ! empty( $faqs ) ) : ?>
            <div class="delice-modern-section delice-modern-section--faqs delice-modern-faqs">
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

    <!-- ═══ RELATED RECIPES (v3.6.0) — feature toggle v3.8.0 ═══════════════ -->
    <?php if ( ( ! isset( $display_options['show_related_recipes'] ) || $display_options['show_related_recipes'] ) && class_exists( 'Delice_Recipe_Related' ) ) : ?>
    <div style="padding: 0 24px 8px;">
        <?php Delice_Recipe_Related::render( $recipe_id, $lang_texts['related_recipes'] ); ?>
    </div>
    <?php endif; ?>

    <!-- ═══ REVIEWS ═══════════════════════════════════════════════════════════ -->
    <?php if ( $reviews_enabled ) : ?>
        <section id="reviewSection-<?php echo esc_attr( $recipe_id ); ?>" class="delice-modern-reviews delice-recipe-review-section">

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

    <!-- Affiliate disclosure (bottom position) — v3.8.4 -->
    <?php if ( $drm_has_aff && $drm_aff_disc_pos === 'bottom' ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo Delice_Affiliate_Manager::get_disclosure_html();
    } ?>

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
