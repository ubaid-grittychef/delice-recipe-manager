<?php
/**
 * Settings page — v3.8.2
 * Market-standard tabbed layout: General · Attribution · SEO & Schema ·
 *   AI Generator · Reviews · Updates
 *
 * All settings live in one <form> → options.php so WordPress handles saving,
 * nonces, and sanitisation via register_setting() / sanitize callbacks.
 * Tabs are JS-toggled (no page reload needed).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Active tab (default: general) ────────────────────────────────────────────
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
$valid_tabs = array( 'general', 'attribution', 'seo', 'ai', 'reviews', 'updates' );
if ( ! in_array( $active_tab, $valid_tabs, true ) ) {
    $active_tab = 'general';
}
$tab_url = admin_url( 'admin.php?page=delice-recipe-settings&tab=' );

// ── Load saved options with defaults merged in (wp_parse_args) ───────────────
$display_defaults = array(
    'show_image'               => true,
    'show_servings'            => true,
    'show_prep_time'           => true,
    'show_cook_time'           => true,
    'show_total_time'          => true,
    'show_calories'            => true,
    'show_difficulty'          => true,
    'show_rating'              => true,
    'show_nutrition'           => true,
    'show_ingredients'         => true,
    'show_instructions'        => true,
    'show_notes'               => true,
    'show_faqs'                => true,
    'show_print'               => true,
    'show_share'               => true,
    'show_jump_btn'            => true,
    'show_cook_mode'           => true,
    'show_dietary_badges'      => true,
    'show_breadcrumb'          => true,
    'show_related_recipes'     => true,
    'show_nutrition_disclaimer' => true,
    'show_last_updated'        => true,
    'show_og_meta'             => true,
);
$display_options = wp_parse_args( get_option( 'delice_recipe_display_options', array() ), $display_defaults );

$attribution_defaults = array(
    'kitchen_name'         => '',
    'kitchen_url'          => '',
    'show_submitted_by'    => true,
    'show_tested_by'       => true,
    'default_author_name'  => '',
);
$attribution = wp_parse_args( get_option( 'delice_recipe_attribution_settings', array() ), $attribution_defaults );

$schema_defaults = array(
    'enable_schema'    => true,
    'publisher_name'   => get_bloginfo( 'name' ),
    'publisher_logo'   => '',
    'use_author'       => true,
    'default_author'   => '',
);
$schema = wp_parse_args( get_option( 'delice_recipe_schema_settings', array() ), $schema_defaults );

$selected_template = get_option( 'delice_recipe_selected_template', 'default' );
$reviews_enabled   = get_option( 'delice_recipe_reviews_enabled', true );
$ai_api_key        = get_option( 'delice_recipe_ai_api_key', '' );
$auto_migrate      = get_option( 'delice_recipe_auto_migrate_to_post', false );
$enable_ai_images  = get_option( 'delice_recipe_enable_ai_images', false );
$image_style       = get_option( 'delice_recipe_image_style', 'vivid' );
$image_size        = get_option( 'delice_recipe_image_size', '1024x1024' );
$github_token      = get_option( 'delice_github_token', '' );

// ── GitHub update status ──────────────────────────────────────────────────────
$cache_key   = 'delice_gh_updater_' . md5( plugin_basename( DELICE_RECIPE_PLUGIN_FILE ) );
$raw_cached  = get_transient( $cache_key );
$api_error   = ( $raw_cached && isset( $raw_cached->api_error ) ) ? (int) $raw_cached->api_error : null;
$release     = isset( $GLOBALS['delice_gh_updater'] )
    ? $GLOBALS['delice_gh_updater']->get_release_info()
    : ( ( $raw_cached && ! isset( $raw_cached->api_error ) ) ? $raw_cached : false );
$remote_ver  = $release ? ltrim( $release->tag_name, 'v' ) : null;
$current_ver = DELICE_RECIPE_VERSION;
$has_update  = $remote_ver && version_compare( $current_ver, $remote_ver, '<' );
?>

<style>
/* ── Settings page styles ──────────────────────────────────────────────────── */
.drm-settings-wrap { max-width: 920px; }

/* Nav tabs — extend WP's own styles */
.drm-settings-wrap .nav-tab-wrapper { margin-bottom: 0; border-bottom: 1px solid #c3c4c7; }

/* Postbox-style card for each tab panel */
.drm-tab-panel { display: none; }
.drm-tab-panel.is-active { display: block; }

.drm-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 3px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 0;
    margin: 16px 0;
}
.drm-card-header {
    padding: 12px 20px;
    border-bottom: 1px solid #f0f0f1;
    display: flex;
    align-items: center;
    gap: 10px;
}
.drm-card-header h2 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    color: #1d2327;
}
.drm-card-header .drm-card-badge {
    font-size: 11px;
    font-weight: 500;
    background: #f0f6fc;
    color: #0073aa;
    border: 1px solid #c5d9ed;
    border-radius: 3px;
    padding: 1px 6px;
    line-height: 1.6;
}
.drm-card-body { padding: 4px 20px 16px; }
.drm-card-body .form-table { margin: 0; }
.drm-card-body .form-table th { width: 220px; padding-left: 0; }

/* Two-column toggle grid */
.drm-toggle-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2px 32px;
    padding: 4px 0;
}
.drm-toggle-grid label {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 6px 0;
    font-size: 13px;
    color: #1d2327;
    cursor: pointer;
}
.drm-toggle-grid label input[type="checkbox"] { margin-top: 1px; flex-shrink: 0; }
.drm-toggle-grid .drm-toggle-desc { font-size: 11px; color: #8c8f94; margin-top: 1px; }

/* Template cards */
.drm-template-cards { display: flex; gap: 12px; flex-wrap: wrap; padding: 8px 0; }
.drm-template-card {
    border: 2px solid #c3c4c7;
    border-radius: 5px;
    padding: 14px 20px;
    cursor: pointer;
    min-width: 160px;
    background: #fafafa;
    transition: border-color .15s, background .15s;
}
.drm-template-card:has(input:checked),
.drm-template-card.is-selected { border-color: #0073aa; background: #f0f6fc; }
.drm-template-card input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
.drm-template-card-name { font-weight: 600; font-size: 13px; color: #1d2327; }
.drm-template-card-desc { font-size: 11px; color: #8c8f94; margin-top: 3px; }

/* Status pills */
.drm-status { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 11px; }
.drm-status.ok  { background: #edfaef; color: #008a20; }
.drm-status.warn { background: #fcf0f1; color: #d63638; }
.drm-status.info { background: #f0f6fc; color: #0073aa; }

/* Sticky save bar */
.drm-save-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 3px;
    padding: 10px 16px;
    margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,.07);
    position: sticky;
    top: 32px;
    z-index: 100;
}
.drm-save-bar .drm-save-left { display: flex; align-items: center; gap: 10px; }

/* Section divider */
.drm-section-divider { margin: 0 0 0; border: none; border-top: 1px solid #f0f0f1; }

@media (max-width: 760px) {
    .drm-toggle-grid { grid-template-columns: 1fr; }
    .drm-template-cards { flex-direction: column; }
}
</style>

<div class="wrap drm-settings-wrap">
    <h1><?php esc_html_e( 'Delice Recipe Settings', 'delice-recipe-manager' ); ?>
        <span style="font-size:13px;font-weight:400;color:#8c8f94;margin-left:8px;">v<?php echo esc_html( $current_ver ); ?></span>
    </h1>

    <form method="post" action="options.php" id="drm-settings-form">
        <?php
        settings_fields( 'delice_recipe_settings' );
        do_settings_sections( 'delice_recipe_settings' );
        ?>

        <!-- ── Sticky save bar ────────────────────────────────────────────── -->
        <div class="drm-save-bar">
            <div class="drm-save-left">
                <input type="submit" name="submit" id="submit-top" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'delice-recipe-manager' ); ?>">
                <span id="drm-unsaved-notice" style="display:none;color:#996800;font-size:12px;">
                    <?php esc_html_e( '● Unsaved changes', 'delice-recipe-manager' ); ?>
                </span>
            </div>
            <div>
                <?php if ( $has_update ) : ?>
                    <span class="drm-status warn">&#8593; <?php esc_html_e( 'Update available', 'delice-recipe-manager' ); ?> &mdash; v<?php echo esc_html( $remote_ver ); ?></span>
                <?php elseif ( $remote_ver ) : ?>
                    <span class="drm-status ok">&#10003; <?php esc_html_e( 'Up to date', 'delice-recipe-manager' ); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Tab navigation ─────────────────────────────────────────────── -->
        <nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Settings sections', 'delice-recipe-manager' ); ?>">
            <a href="<?php echo esc_url( $tab_url . 'general' ); ?>" class="nav-tab<?php echo $active_tab === 'general'     ? ' nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'General', 'delice-recipe-manager' ); ?>
            </a>
            <a href="<?php echo esc_url( $tab_url . 'attribution' ); ?>" class="nav-tab<?php echo $active_tab === 'attribution' ? ' nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Attribution', 'delice-recipe-manager' ); ?>
            </a>
            <a href="<?php echo esc_url( $tab_url . 'seo' ); ?>" class="nav-tab<?php echo $active_tab === 'seo'          ? ' nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'SEO &amp; Schema', 'delice-recipe-manager' ); ?>
            </a>
            <a href="<?php echo esc_url( $tab_url . 'ai' ); ?>" class="nav-tab<?php echo $active_tab === 'ai'           ? ' nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'AI Generator', 'delice-recipe-manager' ); ?>
            </a>
            <a href="<?php echo esc_url( $tab_url . 'reviews' ); ?>" class="nav-tab<?php echo $active_tab === 'reviews'      ? ' nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Reviews', 'delice-recipe-manager' ); ?>
            </a>
            <a href="<?php echo esc_url( $tab_url . 'updates' ); ?>" class="nav-tab<?php echo $active_tab === 'updates'      ? ' nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Updates', 'delice-recipe-manager' ); ?>
                <?php if ( $has_update ) : ?><span style="display:inline-block;width:8px;height:8px;background:#d63638;border-radius:50%;margin-left:4px;vertical-align:middle;"></span><?php endif; ?>
            </a>
        </nav>

        <div style="padding-top: 8px;">

        <!-- ════════════════════════════════════════════════════════════════════
             TAB: GENERAL
        ═══════════════════════════════════════════════════════════════════════ -->
        <div id="tab-general" class="drm-tab-panel<?php echo $active_tab === 'general' ? ' is-active' : ''; ?>">

            <!-- Template Selection -->
            <div class="drm-card">
                <div class="drm-card-header">
                    <h2><?php esc_html_e( 'Recipe Template', 'delice-recipe-manager' ); ?></h2>
                </div>
                <div class="drm-card-body">
                    <p class="description" style="margin-bottom:14px;"><?php esc_html_e( 'Choose the visual style for displaying recipes on the front end.', 'delice-recipe-manager' ); ?></p>
                    <div class="drm-template-cards" role="radiogroup" aria-label="<?php esc_attr_e( 'Template', 'delice-recipe-manager' ); ?>">
                        <?php
                        $templates = array(
                            'default' => array(
                                'label' => __( 'Default', 'delice-recipe-manager' ),
                                'desc'  => __( 'Clean two-column card layout. Best for most themes.', 'delice-recipe-manager' ),
                            ),
                            'modern'  => array(
                                'label' => __( 'Modern', 'delice-recipe-manager' ),
                                'desc'  => __( 'Hero image, side-by-side columns, pill badges.', 'delice-recipe-manager' ),
                            ),
                            'elegant' => array(
                                'label' => __( 'Elegant', 'delice-recipe-manager' ),
                                'desc'  => __( 'Editorial style with decorative dividers.', 'delice-recipe-manager' ),
                            ),
                        );
                        foreach ( $templates as $key => $tmpl ) :
                            $checked = ( $selected_template === $key );
                        ?>
                        <label class="drm-template-card<?php echo $checked ? ' is-selected' : ''; ?>" for="template-<?php echo esc_attr( $key ); ?>">
                            <input type="radio" id="template-<?php echo esc_attr( $key ); ?>"
                                   name="delice_recipe_selected_template"
                                   value="<?php echo esc_attr( $key ); ?>"
                                   <?php checked( $selected_template, $key ); ?>>
                            <div class="drm-template-card-name"><?php echo esc_html( $tmpl['label'] ); ?></div>
                            <div class="drm-template-card-desc"><?php echo esc_html( $tmpl['desc'] ); ?></div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Display Elements -->
            <div class="drm-card">
                <div class="drm-card-header">
                    <h2><?php esc_html_e( 'Display Elements', 'delice-recipe-manager' ); ?></h2>
                    <span class="drm-card-badge"><?php esc_html_e( 'All three templates', 'delice-recipe-manager' ); ?></span>
                </div>
                <div class="drm-card-body">
                    <p class="description" style="margin-bottom:12px;"><?php esc_html_e( 'Control which recipe fields and sections are visible on the front end. Unchecked items are completely hidden.', 'delice-recipe-manager' ); ?></p>

                    <p style="font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#8c8f94;margin:12px 0 6px;"><?php esc_html_e( 'Meta Bar', 'delice-recipe-manager' ); ?></p>
                    <div class="drm-toggle-grid">
                        <label><input type="checkbox" name="delice_recipe_display_options[show_image]"       value="1" <?php checked( ! empty( $display_options['show_image'] ) ); ?>> <?php esc_html_e( 'Featured image', 'delice-recipe-manager' ); ?></label>
                        <label><input type="checkbox" name="delice_recipe_display_options[show_servings]"    value="1" <?php checked( ! empty( $display_options['show_servings'] ) ); ?>> <?php esc_html_e( 'Servings', 'delice-recipe-manager' ); ?></label>
                        <label><input type="checkbox" name="delice_recipe_display_options[show_prep_time]"   value="1" <?php checked( ! empty( $display_options['show_prep_time'] ) ); ?>> <?php esc_html_e( 'Prep time', 'delice-recipe-manager' ); ?></label>
                        <label><input type="checkbox" name="delice_recipe_display_options[show_cook_time]"   value="1" <?php checked( ! empty( $display_options['show_cook_time'] ) ); ?>> <?php esc_html_e( 'Cook time', 'delice-recipe-manager' ); ?></label>
                        <label><input type="checkbox" name="delice_recipe_display_options[show_total_time]"  value="1" <?php checked( ! empty( $display_options['show_total_time'] ) ); ?>> <?php esc_html_e( 'Total time', 'delice-recipe-manager' ); ?></label>
                        <label><input type="checkbox" name="delice_recipe_display_options[show_calories]"    value="1" <?php checked( ! empty( $display_options['show_calories'] ) ); ?>> <?php esc_html_e( 'Calories', 'delice-recipe-manager' ); ?></label>
                        <label><input type="checkbox" name="delice_recipe_display_options[show_difficulty]"  value="1" <?php checked( ! empty( $display_options['show_difficulty'] ) ); ?>> <?php esc_html_e( 'Difficulty badge', 'delice-recipe-manager' ); ?></label>
                        <label><input type="checkbox" name="delice_recipe_display_options[show_last_updated]" value="1" <?php checked( ! empty( $display_options['show_last_updated'] ) ); ?>> <?php esc_html_e( 'Last Updated date', 'delice-recipe-manager' ); ?></label>
                    </div>

                    <hr class="drm-section-divider" style="margin:14px 0;">
                    <p style="font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#8c8f94;margin:0 0 6px;"><?php esc_html_e( 'Content Sections', 'delice-recipe-manager' ); ?></p>
                    <div class="drm-toggle-grid">
                        <label><input type="checkbox" name="delice_recipe_display_options[show_ingredients]"  value="1" <?php checked( ! empty( $display_options['show_ingredients'] ) ); ?>> <?php esc_html_e( 'Ingredients', 'delice-recipe-manager' ); ?></label>
                        <label><input type="checkbox" name="delice_recipe_display_options[show_instructions]" value="1" <?php checked( ! empty( $display_options['show_instructions'] ) ); ?>> <?php esc_html_e( 'Instructions', 'delice-recipe-manager' ); ?></label>
                        <label><input type="checkbox" name="delice_recipe_display_options[show_nutrition]"    value="1" <?php checked( ! empty( $display_options['show_nutrition'] ) ); ?>> <?php esc_html_e( 'Nutrition panel', 'delice-recipe-manager' ); ?></label>
                        <label><input type="checkbox" name="delice_recipe_display_options[show_nutrition_disclaimer]" value="1" <?php checked( ! empty( $display_options['show_nutrition_disclaimer'] ) ); ?>> <?php esc_html_e( 'Nutrition disclaimer text', 'delice-recipe-manager' ); ?></label>
                        <label><input type="checkbox" name="delice_recipe_display_options[show_notes]"        value="1" <?php checked( ! empty( $display_options['show_notes'] ) ); ?>> <?php esc_html_e( 'Chef notes', 'delice-recipe-manager' ); ?></label>
                        <label><input type="checkbox" name="delice_recipe_display_options[show_faqs]"         value="1" <?php checked( ! empty( $display_options['show_faqs'] ) ); ?>> <?php esc_html_e( 'FAQ section', 'delice-recipe-manager' ); ?></label>
                        <label><input type="checkbox" name="delice_recipe_display_options[show_rating]"       value="1" <?php checked( ! empty( $display_options['show_rating'] ) ); ?>> <?php esc_html_e( 'Rating summary', 'delice-recipe-manager' ); ?></label>
                        <label><input type="checkbox" name="delice_recipe_display_options[show_related_recipes]" value="1" <?php checked( ! empty( $display_options['show_related_recipes'] ) ); ?>> <?php esc_html_e( 'Related recipes', 'delice-recipe-manager' ); ?></label>
                    </div>

                    <hr class="drm-section-divider" style="margin:14px 0;">
                    <p style="font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#8c8f94;margin:0 0 6px;"><?php esc_html_e( 'Toolbar Buttons', 'delice-recipe-manager' ); ?></p>
                    <div class="drm-toggle-grid">
                        <label><input type="checkbox" name="delice_recipe_display_options[show_print]"        value="1" <?php checked( ! empty( $display_options['show_print'] ) ); ?>> <?php esc_html_e( 'Print button', 'delice-recipe-manager' ); ?></label>
                        <label><input type="checkbox" name="delice_recipe_display_options[show_share]"        value="1" <?php checked( ! empty( $display_options['show_share'] ) ); ?>> <?php esc_html_e( 'Share button', 'delice-recipe-manager' ); ?></label>
                        <label><input type="checkbox" name="delice_recipe_display_options[show_jump_btn]"     value="1" <?php checked( ! empty( $display_options['show_jump_btn'] ) ); ?>> <?php esc_html_e( 'Jump to Recipe button', 'delice-recipe-manager' ); ?></label>
                        <label><input type="checkbox" name="delice_recipe_display_options[show_cook_mode]"    value="1" <?php checked( ! empty( $display_options['show_cook_mode'] ) ); ?>>
                            <span><?php esc_html_e( 'Cook Mode button', 'delice-recipe-manager' ); ?><br><span class="drm-toggle-desc"><?php esc_html_e( 'Keeps screen awake while cooking (Wake Lock API)', 'delice-recipe-manager' ); ?></span></span>
                        </label>
                    </div>

                    <hr class="drm-section-divider" style="margin:14px 0;">
                    <p style="font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#8c8f94;margin:0 0 6px;"><?php esc_html_e( 'Extras', 'delice-recipe-manager' ); ?></p>
                    <div class="drm-toggle-grid">
                        <label><input type="checkbox" name="delice_recipe_display_options[show_dietary_badges]" value="1" <?php checked( ! empty( $display_options['show_dietary_badges'] ) ); ?>> <?php esc_html_e( 'Dietary badges (Vegan, Gluten-Free…)', 'delice-recipe-manager' ); ?></label>
                        <label><input type="checkbox" name="delice_recipe_display_options[show_breadcrumb]"   value="1" <?php checked( ! empty( $display_options['show_breadcrumb'] ) ); ?>>
                            <span><?php esc_html_e( 'Breadcrumb navigation', 'delice-recipe-manager' ); ?><br><span class="drm-toggle-desc"><?php esc_html_e( 'Auto-hidden when Yoast or RankMath is active', 'delice-recipe-manager' ); ?></span></span>
                        </label>
                    </div>
                </div>
            </div>

        </div><!-- /#tab-general -->


        <!-- ════════════════════════════════════════════════════════════════════
             TAB: ATTRIBUTION
        ═══════════════════════════════════════════════════════════════════════ -->
        <div id="tab-attribution" class="drm-tab-panel<?php echo $active_tab === 'attribution' ? ' is-active' : ''; ?>">

            <div class="drm-card">
                <div class="drm-card-header">
                    <h2><?php esc_html_e( 'Author Attribution', 'delice-recipe-manager' ); ?></h2>
                </div>
                <div class="drm-card-body">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Show labels', 'delice-recipe-manager' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="delice_recipe_attribution_settings[show_submitted_by]" value="1" <?php checked( ! empty( $attribution['show_submitted_by'] ) ); ?>>
                                        <?php esc_html_e( 'Show "Recipe by" (submitted by) author line', 'delice-recipe-manager' ); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="delice_recipe_attribution_settings[show_tested_by]" value="1" <?php checked( ! empty( $attribution['show_tested_by'] ) ); ?>>
                                        <?php esc_html_e( 'Show "Tested by" kitchen attribution line', 'delice-recipe-manager' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="drm-default-author"><?php esc_html_e( 'Default author name', 'delice-recipe-manager' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="drm-default-author"
                                       name="delice_recipe_attribution_settings[default_author_name]"
                                       value="<?php echo esc_attr( $attribution['default_author_name'] ); ?>"
                                       class="regular-text"
                                       placeholder="<?php esc_attr_e( 'e.g. Chef Sarah', 'delice-recipe-manager' ); ?>">
                                <p class="description"><?php esc_html_e( 'Used when no per-recipe author is set. Leave blank to fall back to the WordPress post author.', 'delice-recipe-manager' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="drm-card">
                <div class="drm-card-header">
                    <h2><?php esc_html_e( 'Kitchen / Test Lab', 'delice-recipe-manager' ); ?></h2>
                </div>
                <div class="drm-card-body">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label for="drm-kitchen-name"><?php esc_html_e( 'Kitchen name', 'delice-recipe-manager' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="drm-kitchen-name"
                                       name="delice_recipe_attribution_settings[kitchen_name]"
                                       value="<?php echo esc_attr( $attribution['kitchen_name'] ); ?>"
                                       class="regular-text"
                                       placeholder="<?php esc_attr_e( 'e.g. Delice Recipe Kitchen', 'delice-recipe-manager' ); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="drm-kitchen-url"><?php esc_html_e( 'Kitchen page URL', 'delice-recipe-manager' ); ?></label>
                            </th>
                            <td>
                                <input type="url" id="drm-kitchen-url"
                                       name="delice_recipe_attribution_settings[kitchen_url]"
                                       value="<?php echo esc_url( $attribution['kitchen_url'] ); ?>"
                                       class="regular-text"
                                       placeholder="https://example.com/kitchen">
                                <p class="description"><?php esc_html_e( 'The "Tested by" text will link here. Leave blank for no link.', 'delice-recipe-manager' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

        </div><!-- /#tab-attribution -->


        <!-- ════════════════════════════════════════════════════════════════════
             TAB: SEO & SCHEMA
        ═══════════════════════════════════════════════════════════════════════ -->
        <div id="tab-seo" class="drm-tab-panel<?php echo $active_tab === 'seo' ? ' is-active' : ''; ?>">

            <div class="drm-card">
                <div class="drm-card-header">
                    <h2><?php esc_html_e( 'Structured Data (Schema.org)', 'delice-recipe-manager' ); ?></h2>
                </div>
                <div class="drm-card-body">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'JSON-LD markup', 'delice-recipe-manager' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="delice_recipe_schema_settings[enable_schema]" value="1" <?php checked( ! empty( $schema['enable_schema'] ) ); ?>>
                                    <?php esc_html_e( 'Output Recipe schema.org JSON-LD in &lt;head&gt;', 'delice-recipe-manager' ); ?>
                                </label>
                                <p class="description"><?php esc_html_e( 'Enables Google Recipe rich results (star ratings, cook time, calories in search).', 'delice-recipe-manager' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="drm-publisher-name"><?php esc_html_e( 'Publisher name', 'delice-recipe-manager' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="drm-publisher-name"
                                       name="delice_recipe_schema_settings[publisher_name]"
                                       value="<?php echo esc_attr( $schema['publisher_name'] ); ?>"
                                       class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="drm-publisher-logo"><?php esc_html_e( 'Publisher logo URL', 'delice-recipe-manager' ); ?></label>
                            </th>
                            <td>
                                <input type="url" id="drm-publisher-logo"
                                       name="delice_recipe_schema_settings[publisher_logo]"
                                       value="<?php echo esc_url( $schema['publisher_logo'] ); ?>"
                                       class="regular-text">
                                <p class="description"><?php esc_html_e( 'Minimum 112×112 px. Leave blank to use your site logo.', 'delice-recipe-manager' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Recipe author', 'delice-recipe-manager' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="delice_recipe_schema_settings[use_author]" value="1" <?php checked( ! empty( $schema['use_author'] ) ); ?>>
                                    <?php esc_html_e( 'Use WordPress post author as recipe author in schema', 'delice-recipe-manager' ); ?>
                                </label><br><br>
                                <label for="drm-schema-default-author"><?php esc_html_e( 'Fallback author name:', 'delice-recipe-manager' ); ?></label>
                                <input type="text" id="drm-schema-default-author"
                                       name="delice_recipe_schema_settings[default_author]"
                                       value="<?php echo esc_attr( $schema['default_author'] ); ?>"
                                       class="regular-text" style="margin-left:8px;">
                                <p class="description"><?php esc_html_e( 'Used in schema when "use post author" is off or no author is set.', 'delice-recipe-manager' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="drm-card">
                <div class="drm-card-header">
                    <h2><?php esc_html_e( 'Open Graph &amp; Social Meta', 'delice-recipe-manager' ); ?></h2>
                </div>
                <div class="drm-card-body">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'OG / Twitter Card tags', 'delice-recipe-manager' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="delice_recipe_display_options[show_og_meta]" value="1" <?php checked( ! empty( $display_options['show_og_meta'] ) ); ?>>
                                    <?php esc_html_e( 'Output Open Graph and Twitter Card &lt;meta&gt; tags', 'delice-recipe-manager' ); ?>
                                </label>
                                <p class="description"><?php esc_html_e( 'Automatically disabled when Yoast SEO or RankMath is active (those plugins handle social meta themselves).', 'delice-recipe-manager' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="drm-card">
                <div class="drm-card-header">
                    <h2><?php esc_html_e( 'Validation Tools', 'delice-recipe-manager' ); ?></h2>
                </div>
                <div class="drm-card-body" style="padding-top:12px;">
                    <p><?php esc_html_e( 'Test your structured data with the following external tools:', 'delice-recipe-manager' ); ?></p>
                    <ul style="margin:0;padding-left:20px;list-style:disc;">
                        <li><a href="https://search.google.com/test/rich-results" target="_blank" rel="noopener"><?php esc_html_e( 'Google Rich Results Test', 'delice-recipe-manager' ); ?> &#8599;</a></li>
                        <li><a href="https://validator.schema.org/" target="_blank" rel="noopener"><?php esc_html_e( 'Schema.org Validator', 'delice-recipe-manager' ); ?> &#8599;</a></li>
                    </ul>
                </div>
            </div>

        </div><!-- /#tab-seo -->


        <!-- ════════════════════════════════════════════════════════════════════
             TAB: AI GENERATOR
        ═══════════════════════════════════════════════════════════════════════ -->
        <div id="tab-ai" class="drm-tab-panel<?php echo $active_tab === 'ai' ? ' is-active' : ''; ?>">

            <div class="drm-card">
                <div class="drm-card-header">
                    <h2><?php esc_html_e( 'OpenAI Connection', 'delice-recipe-manager' ); ?></h2>
                </div>
                <div class="drm-card-body">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label for="drm-ai-api-key"><?php esc_html_e( 'API Key', 'delice-recipe-manager' ); ?></label>
                            </th>
                            <td>
                                <input type="password" id="drm-ai-api-key"
                                       name="delice_recipe_ai_api_key"
                                       value="<?php echo esc_attr( $ai_api_key ); ?>"
                                       class="regular-text"
                                       autocomplete="new-password">
                                <button type="button" class="button button-secondary"
                                        onclick="var f=document.getElementById('drm-ai-api-key');f.type=f.type==='password'?'text':'password';">
                                    <?php esc_html_e( 'Show / Hide', 'delice-recipe-manager' ); ?>
                                </button>
                                <p class="description">
                                    <?php esc_html_e( 'Required for AI recipe generation and DALL-E image creation.', 'delice-recipe-manager' ); ?>
                                    <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener"><?php esc_html_e( 'Get your API key &#8599;', 'delice-recipe-manager' ); ?></a>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Auto-migrate to Post', 'delice-recipe-manager' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="delice_recipe_auto_migrate_to_post" value="1" <?php checked( $auto_migrate, true ); ?>>
                                    <?php esc_html_e( 'Save generated recipes as standard WordPress Posts', 'delice-recipe-manager' ); ?>
                                </label>
                                <p class="description"><?php esc_html_e( 'When enabled, each generated recipe is immediately converted from the custom post type to a standard Post. Useful for embedding recipes inline with blog content.', 'delice-recipe-manager' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="drm-card">
                <div class="drm-card-header">
                    <h2><?php esc_html_e( 'AI Image Generation', 'delice-recipe-manager' ); ?></h2>
                    <span class="drm-card-badge">DALL-E 3</span>
                </div>
                <div class="drm-card-body">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Enable', 'delice-recipe-manager' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="delice_recipe_enable_ai_images" value="1" <?php checked( $enable_ai_images, true ); ?>>
                                    <?php esc_html_e( 'Auto-generate a featured image with DALL-E 3 for each recipe', 'delice-recipe-manager' ); ?>
                                </label>
                                <p class="description">
                                    <strong><?php esc_html_e( 'Cost:', 'delice-recipe-manager' ); ?></strong>
                                    <?php esc_html_e( '~$0.04 per image (HD 1024×1024). Disable to add images manually.', 'delice-recipe-manager' ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="drm-image-style"><?php esc_html_e( 'Image style', 'delice-recipe-manager' ); ?></label>
                            </th>
                            <td>
                                <select id="drm-image-style" name="delice_recipe_image_style">
                                    <option value="vivid"   <?php selected( $image_style, 'vivid' ); ?>><?php esc_html_e( 'Vivid — dramatic, vibrant food photography', 'delice-recipe-manager' ); ?></option>
                                    <option value="natural" <?php selected( $image_style, 'natural' ); ?>><?php esc_html_e( 'Natural — realistic, subtle tones', 'delice-recipe-manager' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="drm-image-size"><?php esc_html_e( 'Image size', 'delice-recipe-manager' ); ?></label>
                            </th>
                            <td>
                                <select id="drm-image-size" name="delice_recipe_image_size">
                                    <optgroup label="<?php esc_attr_e( 'DALL-E Native (no resize, 1–4 MB)', 'delice-recipe-manager' ); ?>">
                                        <option value="1024x1024" <?php selected( $image_size, '1024x1024' ); ?>><?php esc_html_e( 'Square 1024×1024', 'delice-recipe-manager' ); ?></option>
                                        <option value="1792x1024" <?php selected( $image_size, '1792x1024' ); ?>><?php esc_html_e( 'Landscape 1792×1024', 'delice-recipe-manager' ); ?></option>
                                        <option value="1024x1792" <?php selected( $image_size, '1024x1792' ); ?>><?php esc_html_e( 'Portrait 1024×1792', 'delice-recipe-manager' ); ?></option>
                                    </optgroup>
                                    <optgroup label="<?php esc_attr_e( 'Auto-resized (faster, 200–500 KB)', 'delice-recipe-manager' ); ?>">
                                        <option value="800x600" <?php selected( $image_size, '800x600' ); ?>><?php esc_html_e( 'Landscape 800×600 — Recommended', 'delice-recipe-manager' ); ?></option>
                                        <option value="900x600" <?php selected( $image_size, '900x600' ); ?>><?php esc_html_e( 'Landscape 900×600', 'delice-recipe-manager' ); ?></option>
                                        <option value="700x700" <?php selected( $image_size, '700x700' ); ?>><?php esc_html_e( 'Square 700×700', 'delice-recipe-manager' ); ?></option>
                                        <option value="600x600" <?php selected( $image_size, '600x600' ); ?>><?php esc_html_e( 'Square 600×600', 'delice-recipe-manager' ); ?></option>
                                        <option value="600x800" <?php selected( $image_size, '600x800' ); ?>><?php esc_html_e( 'Portrait 600×800', 'delice-recipe-manager' ); ?></option>
                                    </optgroup>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

        </div><!-- /#tab-ai -->


        <!-- ════════════════════════════════════════════════════════════════════
             TAB: REVIEWS
        ═══════════════════════════════════════════════════════════════════════ -->
        <div id="tab-reviews" class="drm-tab-panel<?php echo $active_tab === 'reviews' ? ' is-active' : ''; ?>">

            <div class="drm-card">
                <div class="drm-card-header">
                    <h2><?php esc_html_e( 'Recipe Reviews &amp; Ratings', 'delice-recipe-manager' ); ?></h2>
                </div>
                <div class="drm-card-body">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Enable reviews', 'delice-recipe-manager' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="delice_recipe_reviews_enabled" value="1" <?php checked( $reviews_enabled, true ); ?>>
                                    <?php esc_html_e( 'Allow visitors to rate and review recipes', 'delice-recipe-manager' ); ?>
                                </label>
                                <p class="description"><?php esc_html_e( 'When disabled, all review sections and rating stars are completely removed from recipe pages.', 'delice-recipe-manager' ); ?></p>
                            </td>
                        </tr>
                    </table>
                    <p style="margin-top:12px;">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=delice-recipe-reviews' ) ); ?>" class="button button-secondary">
                            <?php esc_html_e( 'Manage Reviews &amp; Advanced Settings &#8594;', 'delice-recipe-manager' ); ?>
                        </a>
                    </p>
                </div>
            </div>

        </div><!-- /#tab-reviews -->


        <!-- ════════════════════════════════════════════════════════════════════
             TAB: UPDATES
        ═══════════════════════════════════════════════════════════════════════ -->
        <div id="tab-updates" class="drm-tab-panel<?php echo $active_tab === 'updates' ? ' is-active' : ''; ?>">

            <div class="drm-card">
                <div class="drm-card-header">
                    <h2><?php esc_html_e( 'GitHub Auto-Updates', 'delice-recipe-manager' ); ?></h2>
                </div>
                <div class="drm-card-body">

                    <!-- Version status banner -->
                    <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:3px;margin-bottom:16px;background:<?php echo $has_update ? '#fcf0f1' : ( $remote_ver ? '#edfaef' : '#f6f7f7' ); ?>;border:1px solid <?php echo $has_update ? '#f9c5c6' : ( $remote_ver ? '#c5e8cb' : '#dcdcde' ); ?>;">
                        <div>
                            <strong><?php esc_html_e( 'Installed:', 'delice-recipe-manager' ); ?></strong>
                            <code>v<?php echo esc_html( $current_ver ); ?></code>
                        </div>
                        <?php if ( $remote_ver ) : ?>
                            <div>
                                <strong><?php esc_html_e( 'Latest on GitHub:', 'delice-recipe-manager' ); ?></strong>
                                <code>v<?php echo esc_html( $remote_ver ); ?></code>
                            </div>
                            <?php if ( $has_update ) : ?>
                                <span class="drm-status warn">&#8593; <?php esc_html_e( 'Update available', 'delice-recipe-manager' ); ?></span>
                            <?php else : ?>
                                <span class="drm-status ok">&#10003; <?php esc_html_e( 'Up to date', 'delice-recipe-manager' ); ?></span>
                            <?php endif; ?>
                        <?php elseif ( null !== $api_error ) : ?>
                            <span class="drm-status warn">
                                <?php
                                $err_msgs = array(
                                    429 => __( 'GitHub API rate limit hit. Retry in ~1 hour or add a PAT.', 'delice-recipe-manager' ),
                                    401 => __( 'Token invalid or expired.', 'delice-recipe-manager' ),
                                    403 => __( 'Token lacks permissions (needs repo / contents:read scope).', 'delice-recipe-manager' ),
                                    404 => __( 'Repository or plugin file not found.', 'delice-recipe-manager' ),
                                );
                                echo isset( $err_msgs[ $api_error ] )
                                    ? esc_html( $err_msgs[ $api_error ] )
                                    : esc_html( sprintf( __( 'GitHub API error (HTTP %d)', 'delice-recipe-manager' ), $api_error ) );
                                ?>
                            </span>
                        <?php else : ?>
                            <span class="drm-status info"><?php esc_html_e( 'Not checked yet', 'delice-recipe-manager' ); ?></span>
                        <?php endif; ?>
                    </div>

                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label for="drm-github-token"><?php esc_html_e( 'Personal Access Token', 'delice-recipe-manager' ); ?></label>
                            </th>
                            <td>
                                <input type="password" id="drm-github-token"
                                       name="delice_github_token"
                                       value="<?php echo esc_attr( $github_token ); ?>"
                                       class="regular-text"
                                       autocomplete="new-password">
                                <button type="button" class="button button-secondary"
                                        onclick="var f=document.getElementById('drm-github-token');f.type=f.type==='password'?'text':'password';">
                                    <?php esc_html_e( 'Show / Hide', 'delice-recipe-manager' ); ?>
                                </button>
                                <p class="description">
                                    <?php esc_html_e( 'Required only for private repositories. Leave blank for public repos.', 'delice-recipe-manager' ); ?>
                                    <?php esc_html_e( 'Classic PAT needs', 'delice-recipe-manager' ); ?> <code>repo</code> <?php esc_html_e( 'scope. Fine-grained PAT needs', 'delice-recipe-manager' ); ?> <code>contents: read</code>.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Repository', 'delice-recipe-manager' ); ?></th>
                            <td>
                                <code>ubaid-grittychef/delice-recipe-manager</code>
                                <p class="description"><?php esc_html_e( 'Updates are fetched from the main branch. Bump the Version: header in the plugin file and push to trigger an update.', 'delice-recipe-manager' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Force check', 'delice-recipe-manager' ); ?></th>
                            <td>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=delice-recipe-settings&tab=updates&action=delice_clear_update_cache' ), 'delice_clear_update_cache' ) ); ?>" class="button button-secondary">
                                    <?php esc_html_e( 'Clear Cache &amp; Check Now', 'delice-recipe-manager' ); ?>
                                </a>
                                <p class="description"><?php esc_html_e( 'GitHub API responses are cached for 12 hours.', 'delice-recipe-manager' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

        </div><!-- /#tab-updates -->

        </div><!-- /tab panels wrapper -->

        <div style="margin-top:8px;">
            <?php submit_button( __( 'Save Changes', 'delice-recipe-manager' ), 'primary', 'submit', true ); ?>
        </div>

    </form>
</div><!-- /.wrap -->

<script>
(function() {
    // Mark form dirty on any change
    var dirty = false;
    document.getElementById('drm-settings-form').addEventListener('change', function() {
        dirty = true;
        document.getElementById('drm-unsaved-notice').style.display = 'inline';
    });
    document.getElementById('drm-settings-form').addEventListener('submit', function() {
        dirty = false;
    });

    // Template card visual selection
    document.querySelectorAll('.drm-template-card input[type="radio"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.drm-template-card').forEach(function(c) { c.classList.remove('is-selected'); });
            radio.closest('.drm-template-card').classList.add('is-selected');
        });
    });
})();
</script>
