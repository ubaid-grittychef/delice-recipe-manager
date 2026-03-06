<?php
/**
 * Affiliate Links admin page — v3.8.5
 * Tabs: Platforms | Keywords | Settings
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Insufficient permissions.', 'delice-recipe-manager' ) );

$active_tab   = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'platforms';
$valid_tabs   = array( 'platforms', 'keywords', 'coverage', 'settings' );
if ( ! in_array( $active_tab, $valid_tabs, true ) ) $active_tab = 'platforms';
$tab_url      = admin_url( 'admin.php?page=delice-recipe-affiliate&tab=' );
$saved        = isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'];

$platforms    = Delice_Affiliate_Manager::get_platforms();
$rules        = Delice_Affiliate_Manager::get_rules();
$settings     = Delice_Affiliate_Manager::get_settings();
$aff_enabled  = ! empty( $settings['enabled'] );
$regions      = Delice_Affiliate_Manager::AMAZON_REGIONS;

// Build a quick platform index for the Keywords tab dropdowns
$platform_map = array();
foreach ( $platforms as $p ) { $platform_map[ $p['id'] ] = $p; }

// Connected platform counts
$connected_count = count( array_filter( $platforms, fn( $p ) => ! empty( $p['active'] ) ) );

// Recipe coverage data (only loaded for Coverage tab and stats)
$coverage_data    = Delice_Affiliate_Manager::get_recipe_coverage();
$cov_ready        = count( array_filter( $coverage_data, fn( $r ) => $r['status'] === 'ready' ) );
$cov_no_match     = count( array_filter( $coverage_data, fn( $r ) => $r['status'] === 'no-match' ) );
$cov_needs_tags   = count( array_filter( $coverage_data, fn( $r ) => $r['status'] === 'needs-tags' ) );
$cov_total        = count( $coverage_data );
$aff_tags_nonce   = wp_create_nonce( 'delice_aff_tags_nonce' );
?>
<style>
/* ── Affiliate Links page styles (v3.8.5) ───────────────────────────────── */
.drm-aff-wrap { max-width: 960px; }
.drm-aff-wrap .nav-tab-wrapper { margin-bottom: 0; border-bottom: 1px solid #c3c4c7; }
.drm-aff-tab-panel { display: none; }
.drm-aff-tab-panel.is-active { display: block; }

/* Card */
.drm-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 0;
    margin: 16px 0;
}
.drm-card-header {
    padding: 12px 20px;
    border-bottom: 1px solid #f0f0f1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
}
.drm-card-header-left { display: flex; align-items: center; gap: 10px; }
.drm-card-header h2 { margin: 0; font-size: 14px; font-weight: 600; color: #1d2327; }
.drm-card-badge {
    font-size: 11px; font-weight: 500;
    background: #f0f6fc; color: #0073aa;
    border: 1px solid #c5d9ed;
    border-radius: 3px; padding: 1px 6px; line-height: 1.6;
}
.drm-card-body { padding: 12px 20px 20px; }
.drm-card-footer {
    padding: 12px 20px;
    border-top: 1px solid #f0f0f1;
    display: flex; align-items: center; gap: 12px;
    background: #fafafa;
}

/* Toggle switch */
.drm-sw { position:relative;display:inline-block;width:40px;height:22px;flex-shrink:0; }
.drm-sw input { opacity:0;width:0;height:0;position:absolute; }
.drm-sw-slider { position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#c3c4c7;border-radius:22px;transition:.2s; }
.drm-sw-slider:before { position:absolute;content:"";height:16px;width:16px;left:3px;bottom:3px;background:#fff;border-radius:50%;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:.2s; }
.drm-sw input:checked + .drm-sw-slider { background:#0073aa; }
.drm-sw input:checked + .drm-sw-slider:before { transform:translateX(18px); }
.drm-sw input:focus + .drm-sw-slider { box-shadow:0 0 0 2px #bee3f8; }
.drm-toggle-row { display:flex; align-items:center; gap:10px; font-size:13px; cursor:pointer; }

/* Page header */
.drm-aff-page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin: 0 0 8px; flex-wrap: wrap; gap: 10px;
}
.drm-aff-page-header h1 { margin: 0; }
.drm-aff-master-toggle {
    display: flex; align-items: center; gap: 10px;
    background: #fff; border: 1px solid #c3c4c7;
    border-radius: 4px; padding: 8px 14px;
    font-size: 13px; font-weight: 600;
}
.drm-aff-master-toggle.is-on { border-color: #0073aa; color: #0073aa; background:#f0f6fc; }

/* Status pill */
.drm-pill { display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:2px 8px;border-radius:10px; }
.drm-pill-green  { background:#edfaef;color:#008a20; }
.drm-pill-grey   { background:#f0f0f1;color:#646970; }
.drm-pill-blue   { background:#f0f6fc;color:#0073aa; }
.drm-pill-orange { background:#fcf9e8;color:#996800; }
.drm-pill-dot { width:6px;height:6px;border-radius:50%;background:currentColor; }

/* ── Platform cards grid ─────────────────────────────────────────────────── */
.drm-platform-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 14px;
    padding: 4px 0;
}
.drm-platform-card {
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
.drm-platform-card.is-connected { border-color: #0073aa; }
.drm-platform-card-header {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid #f0f0f1;
}
.drm-platform-logo {
    width: 36px; height: 36px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: 18px;
}
.drm-platform-logo--amazon  { background: #ff9900; }
.drm-platform-logo--share   { background: #e8321a; }
.drm-platform-logo--cj      { background: #003087; }
.drm-platform-logo--impact  { background: #5a2fc2; }
.drm-platform-logo--custom  { background: #374151; }
.drm-platform-logo svg      { width: 20px; height: 20px; }

.drm-platform-name { font-weight: 700; font-size: 13px; color: #1d2327; }
.drm-platform-desc { font-size: 11px; color: #8c8f94; margin-top: 2px; }

.drm-platform-body { padding: 14px 16px; }
.drm-platform-field { margin-bottom: 10px; }
.drm-platform-field:last-child { margin-bottom: 0; }
.drm-platform-field label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px; }
.drm-platform-field input[type="text"],
.drm-platform-field select {
    width: 100%; padding: 6px 9px; border: 1px solid #ddd; border-radius: 4px;
    font-size: 13px; background: #fff; box-sizing: border-box;
}
.drm-platform-field input[type="text"]:focus,
.drm-platform-field select:focus {
    border-color: #0073aa; box-shadow: 0 0 0 2px rgba(0,115,170,.15); outline: none;
}
.drm-platform-field .description { font-size: 11px; color: #8c8f94; margin-top: 3px; }

.drm-platform-footer {
    padding: 10px 16px;
    border-top: 1px solid #f0f0f1;
    background: #fafafa;
    display: flex; align-items: center; justify-content: space-between; gap: 8px;
}
.drm-platform-footer .drm-toggle-row { font-size: 12px; font-weight: 600; }

/* Custom platform list */
.drm-custom-platforms { display: flex; flex-direction: column; gap: 10px; }
.drm-custom-platform-row {
    display: grid;
    grid-template-columns: 1fr 2fr auto;
    gap: 10px; align-items: start;
    padding: 12px; border: 1px solid #e2e8f0; border-radius: 6px; background: #fafafa;
}
.drm-custom-platform-row input[type="text"],
.drm-custom-platform-row input[type="url"] {
    width: 100%; padding: 6px 9px; border: 1px solid #ddd; border-radius: 4px;
    font-size: 13px; box-sizing: border-box;
}
.drm-remove-platform { color: #cc1818; cursor: pointer; border: none; background: transparent; font-size: 18px; line-height: 1; padding: 4px 8px; border-radius: 3px; }
.drm-remove-platform:hover { background: #fde8e8; }

/* ── Keywords table ──────────────────────────────────────────────────────── */
.drm-aff-table-wrap { overflow-x: auto; }
.drm-aff-table { width: 100%; border-collapse: collapse; }
.drm-aff-table thead th {
    background: #f8f9fa;
    border-bottom: 2px solid #e2e8f0;
    padding: 9px 10px;
    text-align: left;
    font-size: 11px; font-weight: 700; color: #374151;
    text-transform: uppercase; letter-spacing: .05em; white-space: nowrap;
}
.drm-aff-table tbody tr { border-bottom: 1px solid #f0f0f0; }
.drm-aff-table tbody tr:hover { background: #fafbfc; }
.drm-aff-table tbody td { padding: 8px 10px; vertical-align: middle; }

.col-on     { width: 48px; text-align: center; }
.col-kw     { width: 17%; }
.col-mt     { width: 12%; }
.col-plat   { width: 19%; }
.col-pid    { width: 13%; }
.col-url    { width: 22%; }
.col-del    { width: 40px; text-align: center; }

.drm-aff-input, .drm-aff-select {
    width: 100%; padding: 5px 8px; border: 1px solid #ddd; border-radius: 4px;
    font-size: 12px; background: #fff; box-sizing: border-box;
}
.drm-aff-input:focus, .drm-aff-select:focus {
    border-color: #0073aa; box-shadow: 0 0 0 2px rgba(0,115,170,.12); outline: none;
}
.drm-row-del { color:#cc1818;border:none;background:transparent;font-size:17px;cursor:pointer;padding:3px 6px;border-radius:3px; }
.drm-row-del:hover { background:#fde8e8; }

.drm-aff-hint { padding: 10px 16px; background: #f8f9fa; border-bottom: 1px solid #e2e8f0; font-size: 12px; color: #555; line-height: 1.6; }
.drm-aff-empty { padding: 36px 20px; text-align: center; color: #8a8a8a; }
.drm-aff-empty svg { width: 32px; height: 32px; stroke: #ccc; display: block; margin: 0 auto 10px; }
.drm-aff-empty.hidden { display: none; }

/* Row flash */
@keyframes drm-row-flash { from { background: #e8f4fd; } to { background: transparent; } }
.drm-rule-row--new { animation: drm-row-flash .7s ease-out; }

/* ── Settings tab ────────────────────────────────────────────────────────── */
.drm-aff-field { margin-bottom: 18px; }
.drm-aff-field:last-child { margin-bottom: 0; }
.drm-aff-field-label { display: block; font-size: 13px; font-weight: 600; color: #1d2327; margin-bottom: 6px; }
.drm-aff-field .description { font-size: 12px; color: #8c8f94; margin-top: 4px; }
.drm-aff-divider { border: none; border-top: 1px solid #f0f0f0; margin: 18px 0; }
.drm-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
@media (max-width: 760px) {
    .drm-2col { grid-template-columns: 1fr; }
    .drm-platform-grid { grid-template-columns: 1fr; }
    .drm-custom-platform-row { grid-template-columns: 1fr; }
}

/* Compliance note */
.drm-compliance-note {
    display: flex; align-items: flex-start; gap: 9px;
    background: #f0f7ff; border: 1px solid #bee3f8; border-radius: 5px;
    padding: 11px 14px; font-size: 12px; color: #1a56a0; line-height: 1.55; margin-top: 16px;
}
.drm-compliance-note svg { flex-shrink: 0; fill: #1a56a0; margin-top: 1px; }

/* No platforms warning */
.drm-aff-no-platform-warn {
    display: flex; align-items: center; gap: 8px;
    background: #fcf9e8; border: 1px solid #f0c036;
    border-radius: 5px; padding: 10px 14px; font-size: 12px; color: #996800; margin-bottom: 12px;
}

@media (max-width: 760px) { .drm-aff-table { font-size: 12px; } }

/* ── Coverage tab ────────────────────────────────────────────────────────── */
.drm-cov-stats {
    display: flex; gap: 12px; flex-wrap: wrap; margin: 16px 0 4px;
}
.drm-cov-stat {
    flex: 1; min-width: 120px; background: #fff; border: 1px solid #e2e8f0;
    border-radius: 6px; padding: 14px 18px; text-align: center;
}
.drm-cov-stat-num { display: block; font-size: 28px; font-weight: 700; line-height: 1.1; }
.drm-cov-stat-lbl { font-size: 11px; color: #8c8f94; margin-top: 4px; text-transform: uppercase; letter-spacing: .05em; }
.drm-cov-stat--ready   .drm-cov-stat-num { color: #008a20; }
.drm-cov-stat--nomatch .drm-cov-stat-num { color: #996800; }
.drm-cov-stat--needs   .drm-cov-stat-num { color: #cc1818; }

.drm-cov-table { width: 100%; border-collapse: collapse; }
.drm-cov-table thead th {
    background: #f8f9fa; border-bottom: 2px solid #e2e8f0;
    padding: 9px 12px; text-align: left;
    font-size: 11px; font-weight: 700; color: #374151;
    text-transform: uppercase; letter-spacing: .05em; white-space: nowrap;
}
.drm-cov-table tbody td { padding: 9px 12px; vertical-align: top; border-bottom: 1px solid #f0f0f0; font-size: 13px; }
.drm-cov-table tbody tr:last-child td { border-bottom: none; }
.drm-cov-table tbody tr:hover td { background: #fafbfc; }
.drm-cov-table tbody tr[data-status="needs-tags"] td { background: #fff9f9; }
.drm-cov-table tbody tr[data-status="needs-tags"]:hover td { background: #fff3f3; }

.drm-cov-title { font-weight: 600; color: #1d2327; }
.drm-cov-title a { text-decoration: none; color: inherit; }
.drm-cov-title a:hover { color: #0073aa; }
.drm-cov-source-badge {
    display: inline-block; font-size: 10px; font-weight: 600;
    padding: 1px 5px; border-radius: 3px; margin-left: 5px; vertical-align: middle;
}
.drm-cov-source-badge--struct  { background: #f0f6fc; color: #0073aa; }
.drm-cov-source-badge--override { background: #fdf6e3; color: #996800; }
.drm-cov-source-badge--none    { background: #f9f0f0; color: #cc1818; }

.drm-cov-tag-wrap { display: flex; flex-direction: column; gap: 5px; }
.drm-cov-tags {
    width: 100%; min-height: 52px; max-height: 120px; resize: vertical;
    font-family: monospace; font-size: 12px; line-height: 1.5;
    padding: 5px 8px; border: 1px solid #ddd; border-radius: 4px;
    box-sizing: border-box;
}
.drm-cov-tags:focus { border-color: #0073aa; outline: none; box-shadow: 0 0 0 2px rgba(0,115,170,.12); }
.drm-cov-save {
    align-self: flex-end; font-size: 11px !important;
    padding: 3px 10px !important; height: auto !important;
}
.drm-cov-save.is-saved { color: #008a20 !important; border-color: #008a20 !important; }

.drm-cov-filter { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; margin-bottom: 4px; }
.drm-cov-filter-btn {
    font-size: 12px; padding: 3px 10px; border: 1px solid #c3c4c7;
    border-radius: 3px; background: #fff; cursor: pointer; color: #1d2327;
}
.drm-cov-filter-btn.is-active { background: #0073aa; color: #fff; border-color: #0073aa; }

@media (max-width: 760px) { .drm-cov-stats { flex-direction: column; } }
.drm-cov-note {
    font-size: 12px; color: #646970; line-height: 1.6;
    background: #f6f7f7; border-radius: 4px; padding: 10px 14px; margin: 12px 0 0;
}
</style>

<div class="wrap drm-aff-wrap">

    <!-- Page header -->
    <div class="drm-aff-page-header">
        <h1>
            <?php esc_html_e( 'Affiliate Links', 'delice-recipe-manager' ); ?>
            <span style="font-size:13px;font-weight:400;color:#8c8f94;margin-left:8px;">v<?php echo esc_html( DELICE_RECIPE_VERSION ); ?></span>
        </h1>
        <div class="drm-aff-master-toggle<?php echo $aff_enabled ? ' is-on' : ''; ?>">
            <?php if ( $aff_enabled ) : ?>
                <span class="drm-pill drm-pill-green"><span class="drm-pill-dot"></span> <?php esc_html_e( 'Active', 'delice-recipe-manager' ); ?></span>
            <?php else : ?>
                <span class="drm-pill drm-pill-grey"><span class="drm-pill-dot"></span> <?php esc_html_e( 'Disabled', 'delice-recipe-manager' ); ?></span>
            <?php endif; ?>
            <?php printf(
                esc_html( _n( '%d platform connected', '%d platforms connected', $connected_count, 'delice-recipe-manager' ) ),
                $connected_count
            ); ?>
            &nbsp;·&nbsp;
            <?php printf(
                esc_html( _n( '%d keyword rule', '%d keyword rules', count( $rules ), 'delice-recipe-manager' ) ),
                count( $rules )
            ); ?>
            &nbsp;·&nbsp;
            <?php printf(
                /* translators: %d = number of recipes ready */
                esc_html__( '%d/%d recipes ready', 'delice-recipe-manager' ),
                $cov_ready, $cov_total
            ); ?>
        </div>
    </div>

    <?php if ( $saved ) : ?>
    <div class="notice notice-success is-dismissible" style="margin:0 0 12px;"><p><?php esc_html_e( 'Saved.', 'delice-recipe-manager' ); ?></p></div>
    <?php endif; ?>

    <!-- Tab nav -->
    <nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Affiliate Links sections', 'delice-recipe-manager' ); ?>">
        <a href="<?php echo esc_url( $tab_url . 'platforms' ); ?>"
           class="nav-tab<?php echo $active_tab === 'platforms' ? ' nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Platforms', 'delice-recipe-manager' ); ?>
            <?php if ( $connected_count > 0 ) : ?><span class="drm-card-badge" style="margin-left:6px;"><?php echo intval( $connected_count ); ?></span><?php endif; ?>
        </a>
        <a href="<?php echo esc_url( $tab_url . 'keywords' ); ?>"
           class="nav-tab<?php echo $active_tab === 'keywords' ? ' nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Keyword Rules', 'delice-recipe-manager' ); ?>
            <?php if ( count( $rules ) > 0 ) : ?><span class="drm-card-badge" style="margin-left:6px;"><?php echo count( $rules ); ?></span><?php endif; ?>
        </a>
        <a href="<?php echo esc_url( $tab_url . 'coverage' ); ?>"
           class="nav-tab<?php echo $active_tab === 'coverage' ? ' nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Recipe Coverage', 'delice-recipe-manager' ); ?>
            <?php if ( $cov_needs_tags > 0 ) : ?>
                <span class="drm-card-badge" style="margin-left:6px;background:#fff0f0;color:#cc1818;border-color:#f5c6cb;">
                    <?php echo intval( $cov_needs_tags ); ?> <?php esc_html_e( 'need tags', 'delice-recipe-manager' ); ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="<?php echo esc_url( $tab_url . 'settings' ); ?>"
           class="nav-tab<?php echo $active_tab === 'settings' ? ' nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Settings', 'delice-recipe-manager' ); ?>
        </a>
    </nav>

<!-- ═══════════════════════════════════════════════════════════════════
     TAB: PLATFORMS
     ═══════════════════════════════════════════════════════════════════ -->
<div id="tab-platforms" class="drm-aff-tab-panel<?php echo $active_tab === 'platforms' ? ' is-active' : ''; ?>">
<form method="post" action="options.php">
<?php settings_fields( 'delice_affiliate_platforms_group' ); ?>

<!-- Amazon Associates -->
<?php
$amazon_platform = null;
foreach ( $platforms as $p ) { if ( ( $p['type'] ?? '' ) === 'amazon' ) { $amazon_platform = $p; break; } }
$amazon_region   = $amazon_platform['region']       ?? 'us';
$amazon_tag      = $amazon_platform['tracking_id']  ?? '';
$amazon_active   = ! empty( $amazon_platform['active'] );
$amazon_id       = $amazon_platform['id']           ?? '';
$amazon_lang     = $amazon_platform['language']     ?? '';

// All additional Amazon platforms (every Amazon entry after the first).
$extra_amazons = array();
$seen_first    = false;
foreach ( $platforms as $p ) {
    if ( ( $p['type'] ?? '' ) !== 'amazon' ) continue;
    if ( ! $seen_first ) { $seen_first = true; continue; } // skip the first (main) one
    $extra_amazons[] = $p;
}
?>
<div class="drm-card">
    <div class="drm-card-header">
        <div class="drm-card-header-left">
            <h2><?php esc_html_e( 'Amazon Associates', 'delice-recipe-manager' ); ?></h2>
            <?php if ( ! empty( $amazon_tag ) ) : ?>
                <span class="drm-pill drm-pill-green"><span class="drm-pill-dot"></span><?php esc_html_e( 'Connected', 'delice-recipe-manager' ); ?></span>
            <?php else : ?>
                <span class="drm-pill drm-pill-grey"><?php esc_html_e( 'Not connected', 'delice-recipe-manager' ); ?></span>
            <?php endif; ?>
        </div>
        <span style="font-size:12px;color:#8c8f94;"><?php esc_html_e( 'Earn 1–10% on qualifying purchases', 'delice-recipe-manager' ); ?></span>
    </div>
    <div class="drm-card-body">
        <input type="hidden" name="delice_affiliate_platforms[0][id]"   value="<?php echo $amazon_id ? esc_attr( $amazon_id ) : 'plat_amazon'; ?>">
        <input type="hidden" name="delice_affiliate_platforms[0][type]" value="amazon">
        <input type="hidden" name="delice_affiliate_platforms[0][name]" value="Amazon">

        <div class="drm-2col">
            <div class="drm-aff-field">
                <label class="drm-aff-field-label" for="amazon-tag"><?php esc_html_e( 'Tracking Tag (Associates ID)', 'delice-recipe-manager' ); ?></label>
                <input type="text" id="amazon-tag"
                       name="delice_affiliate_platforms[0][tracking_id]"
                       value="<?php echo esc_attr( $amazon_tag ); ?>"
                       placeholder="yoursite-20"
                       class="regular-text">
                <p class="description">
                    <?php esc_html_e( 'Found in your Amazon Associates account under "Account Settings → Tracking ID". Format: yoursite-20', 'delice-recipe-manager' ); ?>
                    <a href="https://affiliate-program.amazon.com" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Associates →', 'delice-recipe-manager' ); ?></a>
                </p>
            </div>
            <div class="drm-aff-field">
                <label class="drm-aff-field-label" for="amazon-region"><?php esc_html_e( 'Amazon Marketplace', 'delice-recipe-manager' ); ?></label>
                <select id="amazon-region" name="delice_affiliate_platforms[0][region]">
                    <?php foreach ( $regions as $code => $data ) : ?>
                        <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $amazon_region, $code ); ?>>
                            <?php echo esc_html( $data['label'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e( 'Match the Amazon site where your Associates account is registered.', 'delice-recipe-manager' ); ?></p>
            </div>
        </div>

        <div class="drm-aff-field" style="margin-top:10px;">
            <label class="drm-aff-field-label" for="amazon-lang"><?php esc_html_e( 'Language code (optional)', 'delice-recipe-manager' ); ?></label>
            <input type="text" id="amazon-lang"
                   name="delice_affiliate_platforms[0][language]"
                   value="<?php echo esc_attr( $amazon_lang ); ?>"
                   placeholder="en" class="small-text" maxlength="10">
            <p class="description"><?php esc_html_e( 'Used by Auto-link to route the right language to this marketplace. E.g. "en" for English pages, "fr" for French. Leave blank for default/fallback.', 'delice-recipe-manager' ); ?></p>
        </div>

        <?php if ( ! empty( $amazon_tag ) ) : ?>
        <div style="margin-top:12px;padding:10px 14px;background:#f8f9fa;border-radius:5px;font-size:12px;color:#555;">
            <?php
            $regions_arr = $regions;
            $tld = $regions_arr[ $amazon_region ]['tld'] ?? 'com';
            printf(
                esc_html__( 'Example search link: %s', 'delice-recipe-manager' ),
                '<code>https://www.amazon.' . esc_html( $tld ) . '/s?k=olive+oil&amp;tag=' . esc_html( $amazon_tag ) . '</code>'
            );
            ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="drm-card-footer">
        <label class="drm-toggle-row">
            <span class="drm-sw">
                <input type="checkbox" name="delice_affiliate_platforms[0][active]" value="1" <?php checked( $amazon_active ); ?>>
                <span class="drm-sw-slider"></span>
            </span>
            <?php esc_html_e( 'Enable Amazon links on recipes', 'delice-recipe-manager' ); ?>
        </label>
    </div>
</div>

<!-- Additional Amazon Marketplaces (multilingual support) -->
<?php $extra_base_idx = 50; /* form indexes 50+ are reserved for extra Amazon entries */ ?>
<div class="drm-card" id="drm-extra-amazon-card">
    <div class="drm-card-header">
        <div class="drm-card-header-left">
            <h2><?php esc_html_e( 'Additional Amazon Marketplaces', 'delice-recipe-manager' ); ?></h2>
            <span class="drm-card-badge"><?php esc_html_e( 'Multilingual auto-link routing', 'delice-recipe-manager' ); ?></span>
        </div>
    </div>
    <div class="drm-card-body">
        <p class="description" style="margin-bottom:14px;"><?php esc_html_e( 'Add one row per additional language. When Auto-link is on, each page\'s language is detected (WPML / Polylang / WP locale) and the matching marketplace is used automatically. The main Amazon entry above acts as the default fallback.', 'delice-recipe-manager' ); ?></p>

        <table class="widefat striped" id="drm-extra-amazon-table" style="font-size:13px;">
            <thead>
                <tr>
                    <th style="width:90px;"><?php esc_html_e( 'Language', 'delice-recipe-manager' ); ?></th>
                    <th><?php esc_html_e( 'Marketplace', 'delice-recipe-manager' ); ?></th>
                    <th><?php esc_html_e( 'Associates ID', 'delice-recipe-manager' ); ?></th>
                    <th style="width:70px;"><?php esc_html_e( 'Active', 'delice-recipe-manager' ); ?></th>
                    <th style="width:50px;"></th>
                </tr>
            </thead>
            <tbody id="drm-extra-amazon-rows">
            <?php foreach ( $extra_amazons as $ea_i => $ea ) :
                $ea_fi = $extra_base_idx + $ea_i;
            ?>
                <tr class="drm-extra-amazon-row">
                    <td>
                        <input type="hidden" name="delice_affiliate_platforms[<?php echo $ea_fi; ?>][id]"   value="<?php echo esc_attr( $ea['id'] ); ?>">
                        <input type="hidden" name="delice_affiliate_platforms[<?php echo $ea_fi; ?>][type]" value="amazon">
                        <input type="hidden" name="delice_affiliate_platforms[<?php echo $ea_fi; ?>][name]" value="Amazon">
                        <input type="text" name="delice_affiliate_platforms[<?php echo $ea_fi; ?>][language]"
                               value="<?php echo esc_attr( $ea['language'] ?? '' ); ?>"
                               placeholder="fr" class="small-text" maxlength="10"
                               title="<?php esc_attr_e( '2-letter language code, e.g. fr, de, ar', 'delice-recipe-manager' ); ?>">
                    </td>
                    <td>
                        <select name="delice_affiliate_platforms[<?php echo $ea_fi; ?>][region]" style="width:100%;">
                            <?php foreach ( $regions as $rc => $rd ) : ?>
                                <option value="<?php echo esc_attr( $rc ); ?>" <?php selected( $ea['region'] ?? 'us', $rc ); ?>><?php echo esc_html( $rd['label'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="delice_affiliate_platforms[<?php echo $ea_fi; ?>][tracking_id]"
                               value="<?php echo esc_attr( $ea['tracking_id'] ?? '' ); ?>"
                               placeholder="yoursite-fr-21" class="regular-text">
                    </td>
                    <td style="text-align:center;">
                        <label class="drm-sw" title="<?php esc_attr_e( 'Enable', 'delice-recipe-manager' ); ?>">
                            <input type="checkbox" name="delice_affiliate_platforms[<?php echo $ea_fi; ?>][active]" value="1" <?php checked( ! empty( $ea['active'] ) ); ?>>
                            <span class="drm-sw-slider"></span>
                        </label>
                    </td>
                    <td><button type="button" class="button button-small drm-remove-amazon-row" title="<?php esc_attr_e( 'Remove', 'delice-recipe-manager' ); ?>">✕</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <p style="margin-top:12px;">
            <button type="button" id="drm-add-amazon-row" class="button"><?php esc_html_e( '+ Add Marketplace', 'delice-recipe-manager' ); ?></button>
        </p>
    </div>
</div>

<script>
(function(){
    var counter = <?php echo (int) ( $extra_base_idx + count( $extra_amazons ) ); ?>;
    var regions = <?php echo wp_json_encode( array_map( fn( $r ) => $r['label'], $regions ) ); ?>;
    var regionCodes = <?php echo wp_json_encode( array_keys( $regions ) ); ?>;

    function buildRegionOptions( selectedCode ) {
        var html = '';
        for ( var i = 0; i < regionCodes.length; i++ ) {
            html += '<option value="' + regionCodes[i] + '"' + ( regionCodes[i] === selectedCode ? ' selected' : '' ) + '>' + regions[ regionCodes[i] ] + '<\/option>';
        }
        return html;
    }

    document.getElementById('drm-add-amazon-row').addEventListener('click', function(){
        var idx = counter++;
        var row = document.createElement('tr');
        row.className = 'drm-extra-amazon-row';
        row.innerHTML =
            '<td>' +
                '<input type="hidden" name="delice_affiliate_platforms[' + idx + '][id]" value="plat_amazon_' + idx + '">' +
                '<input type="hidden" name="delice_affiliate_platforms[' + idx + '][type]" value="amazon">' +
                '<input type="hidden" name="delice_affiliate_platforms[' + idx + '][name]" value="Amazon">' +
                '<input type="text" name="delice_affiliate_platforms[' + idx + '][language]" placeholder="fr" class="small-text" maxlength="10">' +
            '<\/td>' +
            '<td><select name="delice_affiliate_platforms[' + idx + '][region]" style="width:100%;">' + buildRegionOptions('us') + '<\/select><\/td>' +
            '<td><input type="text" name="delice_affiliate_platforms[' + idx + '][tracking_id]" placeholder="yoursite-fr-21" class="regular-text"><\/td>' +
            '<td style="text-align:center;"><label class="drm-sw"><input type="checkbox" name="delice_affiliate_platforms[' + idx + '][active]" value="1" checked><span class="drm-sw-slider"><\/span><\/label><\/td>' +
            '<td><button type="button" class="button button-small drm-remove-amazon-row">✕<\/button><\/td>';
        document.getElementById('drm-extra-amazon-rows').appendChild(row);
    });

    document.getElementById('drm-extra-amazon-rows').addEventListener('click', function(e){
        if ( e.target.classList.contains('drm-remove-amazon-row') ) {
            e.target.closest('tr').remove();
        }
    });
})();
</script>

<!-- Other Platforms (ShareASale, CJ, Impact, Custom) -->
<?php
$other_types = array(
    'shareasale' => array(
        'label'       => 'ShareASale',
        'color'       => '#e8321a',
        'letter'      => 'S',
        'commission'  => 'Up to 30% — Fashion, Home, Food & more',
        'id_label'    => 'Affiliate ID',
        'id_help'     => 'Your 7-digit ShareASale affiliate ID (found in account → Profile)',
        'url_label'   => 'Deep-link base URL (optional)',
        'url_help'    => 'e.g. https://www.shareasale.com/r.cfm?u=XXXXXXX&b=YYYYYYY — or use {keyword} placeholder for search',
        'signup_url'  => 'https://www.shareasale.com/info/affiliate.cfm',
    ),
    'cj' => array(
        'label'       => 'CJ Affiliate',
        'color'       => '#003087',
        'letter'      => 'C',
        'commission'  => 'Variable — Williams-Sonoma, Sur La Table, KitchenAid',
        'id_label'    => 'CJ Publisher ID',
        'id_help'     => 'Your publisher ID from the CJ account dashboard',
        'url_label'   => 'Search URL template',
        'url_help'    => 'Use {keyword} as a placeholder, e.g. https://www.partner.example.com/search?q={keyword}&affid=XXXX',
        'signup_url'  => 'https://signup.cj.com/member/signup/publisher/',
    ),
    'impact' => array(
        'label'       => 'Impact.com',
        'color'       => '#5a2fc2',
        'letter'      => 'I',
        'commission'  => 'Variable — Instacart, Thrive Market, iHerb & more',
        'id_label'    => 'Account SID',
        'id_help'     => 'Your Impact account SID from the API settings page',
        'url_label'   => 'Search URL template',
        'url_help'    => 'Use {keyword} as placeholder for ingredient keyword',
        'signup_url'  => 'https://app.impact.com/signup/publisher',
    ),
);

// Index existing other-platform entries by type for pre-fill
$existing_by_type = array();
foreach ( $platforms as $idx => $p ) {
    if ( isset( $other_types[ $p['type'] ] ) ) {
        $existing_by_type[ $p['type'] ] = array( 'platform' => $p, 'index' => $idx );
    }
}

// Custom platforms (type = 'custom')
$custom_platforms = array_values( array_filter( $platforms, fn( $p ) => ( $p['type'] ?? '' ) === 'custom' ) );

$form_index = 1; // 0 = Amazon above
?>

<div class="drm-card">
    <div class="drm-card-header">
        <div class="drm-card-header-left">
            <h2><?php esc_html_e( 'Other Networks', 'delice-recipe-manager' ); ?></h2>
            <span class="drm-card-badge"><?php esc_html_e( 'ShareASale · CJ Affiliate · Impact', 'delice-recipe-manager' ); ?></span>
        </div>
    </div>
    <div class="drm-card-body">
        <div class="drm-platform-grid">
        <?php foreach ( $other_types as $type_key => $type_info ) :
            $existing   = $existing_by_type[ $type_key ] ?? null;
            $ep         = $existing ? $existing['platform'] : array();
            $fi         = $form_index++;
            $ep_id      = $ep['id'] ?? 'plat_' . $type_key;
            $ep_tid     = $ep['tracking_id'] ?? '';
            $ep_url     = $ep['search_url']  ?? '';
            $ep_active  = ! empty( $ep['active'] );
        ?>
        <div class="drm-platform-card<?php echo ! empty( $ep_tid ) ? ' is-connected' : ''; ?>">
            <div class="drm-platform-card-header">
                <div class="drm-platform-logo drm-platform-logo--<?php echo esc_attr( array_keys( $other_types )[ array_search( $type_key, array_keys( $other_types ) ) ] === 'shareasale' ? 'share' : $type_key ); ?>"
                     style="background:<?php echo esc_attr( $type_info['color'] ); ?>;color:#fff;font-weight:800;font-size:16px;">
                    <?php echo esc_html( $type_info['letter'] ); ?>
                </div>
                <div>
                    <div class="drm-platform-name"><?php echo esc_html( $type_info['label'] ); ?></div>
                    <div class="drm-platform-desc"><?php echo esc_html( $type_info['commission'] ); ?></div>
                </div>
            </div>
            <div class="drm-platform-body">
                <input type="hidden" name="delice_affiliate_platforms[<?php echo $fi; ?>][id]"   value="<?php echo esc_attr( $ep_id ); ?>">
                <input type="hidden" name="delice_affiliate_platforms[<?php echo $fi; ?>][type]" value="<?php echo esc_attr( $type_key ); ?>">
                <input type="hidden" name="delice_affiliate_platforms[<?php echo $fi; ?>][name]" value="<?php echo esc_attr( $type_info['label'] ); ?>">

                <div class="drm-platform-field">
                    <label><?php echo esc_html( $type_info['id_label'] ); ?></label>
                    <input type="text" name="delice_affiliate_platforms[<?php echo $fi; ?>][tracking_id]"
                           value="<?php echo esc_attr( $ep_tid ); ?>" placeholder="Your ID">
                    <p class="description"><?php echo esc_html( $type_info['id_help'] ); ?></p>
                </div>
                <div class="drm-platform-field">
                    <label><?php echo esc_html( $type_info['url_label'] ); ?></label>
                    <input type="text" name="delice_affiliate_platforms[<?php echo $fi; ?>][search_url]"
                           value="<?php echo esc_attr( $ep_url ); ?>"
                           placeholder="https://.../{keyword}...">
                    <p class="description"><?php echo esc_html( $type_info['url_help'] ); ?></p>
                </div>
            </div>
            <div class="drm-platform-footer">
                <label class="drm-toggle-row">
                    <span class="drm-sw">
                        <input type="checkbox" name="delice_affiliate_platforms[<?php echo $fi; ?>][active]" value="1" <?php checked( $ep_active ); ?>>
                        <span class="drm-sw-slider"></span>
                    </span>
                    <?php esc_html_e( 'Active', 'delice-recipe-manager' ); ?>
                </label>
                <a href="<?php echo esc_url( $type_info['signup_url'] ); ?>" target="_blank" rel="noopener noreferrer" class="button button-small">
                    <?php esc_html_e( 'Sign up →', 'delice-recipe-manager' ); ?>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        </div><!-- /.drm-platform-grid -->
    </div>
</div>

<!-- Custom Platforms -->
<div class="drm-card">
    <div class="drm-card-header">
        <div class="drm-card-header-left">
            <h2><?php esc_html_e( 'Custom Platforms', 'delice-recipe-manager' ); ?></h2>
            <span class="drm-card-badge"><?php esc_html_e( 'Any store with a search URL', 'delice-recipe-manager' ); ?></span>
        </div>
        <button type="button" id="drm-add-custom-platform" class="button button-secondary">
            + <?php esc_html_e( 'Add Platform', 'delice-recipe-manager' ); ?>
        </button>
    </div>
    <div class="drm-card-body">
        <p style="font-size:12px;color:#8c8f94;margin-top:0;">
            <?php esc_html_e( 'Use {keyword} as a placeholder in the search URL — it will be replaced with the ingredient name.', 'delice-recipe-manager' ); ?>
            <?php esc_html_e( 'Example: https://www.instacart.com/store/search?q={keyword}&affiliate_id=XXXXX', 'delice-recipe-manager' ); ?>
        </p>
        <div class="drm-custom-platforms" id="drm-custom-platforms-list">
        <?php foreach ( $custom_platforms as $cp ) :
            $cp_fi = $form_index++;
            $cp_id = esc_attr( $cp['id'] ?? 'plat_custom_' . $cp_fi );
        ?>
            <div class="drm-custom-platform-row">
                <div>
                    <label style="font-size:11px;font-weight:600;color:#374151;"><?php esc_html_e( 'Platform name', 'delice-recipe-manager' ); ?></label>
                    <input type="hidden" name="delice_affiliate_platforms[<?php echo $cp_fi; ?>][id]"   value="<?php echo $cp_id; ?>">
                    <input type="hidden" name="delice_affiliate_platforms[<?php echo $cp_fi; ?>][type]" value="custom">
                    <input type="text" name="delice_affiliate_platforms[<?php echo $cp_fi; ?>][name]"
                           value="<?php echo esc_attr( $cp['name'] ?? '' ); ?>"
                           placeholder="<?php esc_attr_e( 'e.g. Instacart', 'delice-recipe-manager' ); ?>">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:#374151;"><?php esc_html_e( 'Search URL (use {keyword})', 'delice-recipe-manager' ); ?></label>
                    <input type="url" name="delice_affiliate_platforms[<?php echo $cp_fi; ?>][search_url]"
                           value="<?php echo esc_url( $cp['search_url'] ?? '' ); ?>"
                           placeholder="https://.../{keyword}">
                </div>
                <div style="padding-top:18px;">
                    <button type="button" class="drm-remove-platform" title="<?php esc_attr_e( 'Remove', 'delice-recipe-manager' ); ?>">&#x2715;</button>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <div id="drm-custom-empty" style="<?php echo ! empty( $custom_platforms ) ? 'display:none;' : ''; ?>font-size:12px;color:#8c8f94;padding:8px 0;">
            <?php esc_html_e( 'No custom platforms yet. Click "Add Platform" to add any store.', 'delice-recipe-manager' ); ?>
        </div>
    </div>
    <div class="drm-card-footer">
        <?php submit_button( __( 'Save Platforms', 'delice-recipe-manager' ), 'primary', 'submit', false ); ?>
    </div>
</div>
</form>
</div><!-- /#tab-platforms -->

<!-- ═══════════════════════════════════════════════════════════════════
     TAB: KEYWORD RULES
     ═══════════════════════════════════════════════════════════════════ -->
<div id="tab-keywords" class="drm-aff-tab-panel<?php echo $active_tab === 'keywords' ? ' is-active' : ''; ?>">
<form method="post" action="options.php" id="drm-rules-form">
<?php settings_fields( 'delice_affiliate_rules_group' ); ?>

<?php if ( empty( $platforms ) ) : ?>
<div class="drm-aff-no-platform-warn">
    <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
    <?php printf(
        wp_kses( __( 'No platforms connected yet. <a href="%s">Connect a platform</a> on the Platforms tab first, then come back to create keyword rules.', 'delice-recipe-manager' ),
        array( 'a' => array( 'href' => array() ) ) ),
        esc_url( $tab_url . 'platforms' )
    ); ?>
</div>
<?php endif; ?>

<div class="drm-card">
    <div class="drm-card-header">
        <div class="drm-card-header-left">
            <h2><?php esc_html_e( 'Keyword Rules', 'delice-recipe-manager' ); ?></h2>
            <span class="drm-card-badge"><?php printf( esc_html( _n( '%d rule', '%d rules', count( $rules ), 'delice-recipe-manager' ) ), count( $rules ) ); ?></span>
        </div>
        <div style="display:flex;gap:8px;flex-shrink:0;">
            <button type="button" id="drm-add-rule" class="button button-secondary">
                + <?php esc_html_e( 'Add Rule', 'delice-recipe-manager' ); ?>
            </button>
            <label class="button button-secondary" for="drm-csv-import" style="cursor:pointer;"
                   title="<?php esc_attr_e( 'CSV: keyword, platform_id, product_id, custom_url, match_type', 'delice-recipe-manager' ); ?>">
                <?php esc_html_e( 'Import CSV', 'delice-recipe-manager' ); ?>
                <input type="file" id="drm-csv-import" accept=".csv" style="display:none;">
            </label>
        </div>
    </div>

    <div class="drm-aff-hint">
        <?php esc_html_e( 'Each ingredient is matched against these keywords. Match priority: Exact > Starts with > Contains. Longest keyword wins within each tier.', 'delice-recipe-manager' ); ?>
        &nbsp;·&nbsp;
        <?php esc_html_e( 'For Amazon: leave "Direct product URL" empty to auto-build a search link, or enter an ASIN for a direct product page.', 'delice-recipe-manager' ); ?>
    </div>

    <div class="drm-aff-table-wrap">
        <table class="drm-aff-table" id="drm-rules-table">
            <thead>
                <tr>
                    <th class="col-on"><?php esc_html_e( 'On', 'delice-recipe-manager' ); ?></th>
                    <th class="col-kw"><?php esc_html_e( 'Keyword', 'delice-recipe-manager' ); ?></th>
                    <th class="col-mt"><?php esc_html_e( 'Match', 'delice-recipe-manager' ); ?></th>
                    <th class="col-plat"><?php esc_html_e( 'Platform', 'delice-recipe-manager' ); ?></th>
                    <th class="col-pid"><?php esc_html_e( 'ASIN / Product ID', 'delice-recipe-manager' ); ?></th>
                    <th class="col-url"><?php esc_html_e( 'Custom URL (overrides)', 'delice-recipe-manager' ); ?></th>
                    <th class="col-del"></th>
                </tr>
            </thead>
            <tbody id="drm-rules-tbody">
            <?php foreach ( $rules as $i => $rule ) :
                $rid  = esc_attr( $rule['id'] ?? 'rule_' . $i );
                $mt   = $rule['match_type'] ?? 'contains';
                $plid = $rule['platform_id'] ?? '';
            ?>
            <tr class="drm-rule-row" data-id="<?php echo $rid; ?>">
                <td class="col-on">
                    <input type="hidden" name="delice_affiliate_rules[<?php echo $i; ?>][id]" value="<?php echo $rid; ?>">
                    <label class="drm-sw">
                        <input type="checkbox" name="delice_affiliate_rules[<?php echo $i; ?>][active]" value="1" <?php checked( ! empty( $rule['active'] ) ); ?>>
                        <span class="drm-sw-slider"></span>
                    </label>
                </td>
                <td class="col-kw">
                    <input type="text" name="delice_affiliate_rules[<?php echo $i; ?>][keyword]"
                           value="<?php echo esc_attr( $rule['keyword'] ?? '' ); ?>"
                           placeholder="<?php esc_attr_e( 'e.g. olive oil', 'delice-recipe-manager' ); ?>"
                           class="drm-aff-input">
                </td>
                <td class="col-mt">
                    <select name="delice_affiliate_rules[<?php echo $i; ?>][match_type]" class="drm-aff-select">
                        <option value="contains" <?php selected( $mt, 'contains' ); ?>><?php esc_html_e( 'Contains', 'delice-recipe-manager' ); ?></option>
                        <option value="starts"   <?php selected( $mt, 'starts' );   ?>><?php esc_html_e( 'Starts with', 'delice-recipe-manager' ); ?></option>
                        <option value="exact"    <?php selected( $mt, 'exact' );    ?>><?php esc_html_e( 'Exact', 'delice-recipe-manager' ); ?></option>
                    </select>
                </td>
                <td class="col-plat">
                    <select name="delice_affiliate_rules[<?php echo $i; ?>][platform_id]" class="drm-aff-select drm-platform-select">
                        <option value=""><?php esc_html_e( '— Select —', 'delice-recipe-manager' ); ?></option>
                        <?php foreach ( $platforms as $plat ) : ?>
                            <option value="<?php echo esc_attr( $plat['id'] ); ?>"
                                    data-type="<?php echo esc_attr( $plat['type'] ); ?>"
                                    <?php selected( $plid, $plat['id'] ); ?>>
                                <?php echo esc_html( $plat['name'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td class="col-pid">
                    <input type="text" name="delice_affiliate_rules[<?php echo $i; ?>][product_id]"
                           value="<?php echo esc_attr( $rule['product_id'] ?? '' ); ?>"
                           placeholder="B07XXXXX"
                           class="drm-aff-input drm-asin-field">
                </td>
                <td class="col-url">
                    <input type="url" name="delice_affiliate_rules[<?php echo $i; ?>][custom_url]"
                           value="<?php echo esc_url( $rule['custom_url'] ?? '' ); ?>"
                           placeholder="https://..."
                           class="drm-aff-input">
                </td>
                <td class="col-del">
                    <button type="button" class="drm-row-del" aria-label="<?php esc_attr_e( 'Remove rule', 'delice-recipe-manager' ); ?>">&#x2715;</button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div id="drm-rules-empty" class="drm-aff-empty<?php echo ! empty( $rules ) ? ' hidden' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 4v16m-8-8h16"/></svg>
            <p><?php esc_html_e( 'No rules yet. Click "Add Rule" or import a CSV.', 'delice-recipe-manager' ); ?></p>
        </div>
    </div>

    <div class="drm-card-footer">
        <?php submit_button( __( 'Save Rules', 'delice-recipe-manager' ), 'primary', 'submit', false ); ?>
        <span id="drm-rule-count" style="font-size:12px;color:#8c8f94;">
            <?php printf( esc_html( _n( '%d rule', '%d rules', count( $rules ), 'delice-recipe-manager' ) ), count( $rules ) ); ?>
        </span>
    </div>
</div>

<!-- Pass platforms JSON to JS for the add-row builder -->
<script>
window.drmPlatforms = <?php echo wp_json_encode( array_values( $platforms ) ); ?>;
</script>
</form>
</div><!-- /#tab-keywords -->

<!-- ═══════════════════════════════════════════════════════════════════
     TAB: COVERAGE
     ═══════════════════════════════════════════════════════════════════ -->
<div id="tab-coverage" class="drm-aff-tab-panel<?php echo $active_tab === 'coverage' ? ' is-active' : ''; ?>">

    <!-- Stats row -->
    <div class="drm-cov-stats">
        <div class="drm-cov-stat drm-cov-stat--ready">
            <span class="drm-cov-stat-num" id="drm-stat-ready"><?php echo intval( $cov_ready ); ?></span>
            <span class="drm-cov-stat-lbl"><?php esc_html_e( 'Ready', 'delice-recipe-manager' ); ?></span>
        </div>
        <div class="drm-cov-stat drm-cov-stat--nomatch">
            <span class="drm-cov-stat-num" id="drm-stat-nomatch"><?php echo intval( $cov_no_match ); ?></span>
            <span class="drm-cov-stat-lbl"><?php esc_html_e( 'No keyword match', 'delice-recipe-manager' ); ?></span>
        </div>
        <div class="drm-cov-stat drm-cov-stat--needs">
            <span class="drm-cov-stat-num" id="drm-stat-needs"><?php echo intval( $cov_needs_tags ); ?></span>
            <span class="drm-cov-stat-lbl"><?php esc_html_e( 'Needs ingredient tags', 'delice-recipe-manager' ); ?></span>
        </div>
        <div class="drm-cov-stat" style="border-color:#c3c4c7;">
            <span class="drm-cov-stat-num" id="drm-stat-total" style="color:#1d2327;"><?php echo intval( $cov_total ); ?></span>
            <span class="drm-cov-stat-lbl"><?php esc_html_e( 'Total recipes', 'delice-recipe-manager' ); ?></span>
        </div>
    </div>

    <div class="drm-cov-note">
        <strong><?php esc_html_e( 'How ingredient tags work:', 'delice-recipe-manager' ); ?></strong>
        <?php esc_html_e( 'For recipes made by another plugin (or built manually), paste ingredient names — one per line — in the "Affiliate Tags Override" column. The affiliate engine will match them against your keyword rules exactly as if they were structured ingredients. Structured ingredients from this plugin always take priority over the override.', 'delice-recipe-manager' ); ?>
    </div>

    <!-- Filter bar -->
    <div class="drm-cov-filter" style="margin-top:16px;">
        <span style="font-size:12px;font-weight:600;color:#646970;"><?php esc_html_e( 'Filter:', 'delice-recipe-manager' ); ?></span>
        <button type="button" class="drm-cov-filter-btn is-active" data-filter="all"><?php esc_html_e( 'All', 'delice-recipe-manager' ); ?></button>
        <button type="button" class="drm-cov-filter-btn" data-filter="needs-tags"><?php esc_html_e( 'Needs Tags', 'delice-recipe-manager' ); ?></button>
        <button type="button" class="drm-cov-filter-btn" data-filter="no-match"><?php esc_html_e( 'No Match', 'delice-recipe-manager' ); ?></button>
        <button type="button" class="drm-cov-filter-btn" data-filter="ready"><?php esc_html_e( 'Ready', 'delice-recipe-manager' ); ?></button>
    </div>

    <div class="drm-card" style="margin-top:8px;">
        <div class="drm-card-header">
            <div class="drm-card-header-left">
                <h2><?php esc_html_e( 'Recipe Coverage', 'delice-recipe-manager' ); ?></h2>
                <span class="drm-card-badge"><?php printf( esc_html__( '%d recipes', 'delice-recipe-manager' ), intval( $cov_total ) ); ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <button type="button" id="drm-cov-scan" class="button button-secondary">
                    <?php esc_html_e( 'Scan Recipes', 'delice-recipe-manager' ); ?>
                </button>
                <span id="drm-cov-scan-status" style="font-size:12px;color:#646970;"></span>
            </div>
        </div>
        <div style="overflow-x:auto;">
        <?php if ( empty( $coverage_data ) ) : ?>
            <div class="drm-aff-empty" style="padding:40px 20px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                <p><?php esc_html_e( 'No recipes found. Create or import some recipes first.', 'delice-recipe-manager' ); ?></p>
            </div>
        <?php else : ?>
        <table class="drm-cov-table" id="drm-cov-table">
            <thead>
                <tr>
                    <th style="width:100px;"><?php esc_html_e( 'Status', 'delice-recipe-manager' ); ?></th>
                    <th><?php esc_html_e( 'Recipe', 'delice-recipe-manager' ); ?></th>
                    <th style="width:90px;text-align:center;"><?php esc_html_e( 'Ingredients', 'delice-recipe-manager' ); ?></th>
                    <th style="width:80px;text-align:center;"><?php esc_html_e( 'Matched', 'delice-recipe-manager' ); ?></th>
                    <th style="width:260px;"><?php esc_html_e( 'Affiliate Tags Override', 'delice-recipe-manager' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $coverage_data as $recipe ) :
                $status_label = array(
                    'ready'      => __( 'Ready', 'delice-recipe-manager' ),
                    'no-match'   => __( 'No Match', 'delice-recipe-manager' ),
                    'needs-tags' => __( 'Needs Tags', 'delice-recipe-manager' ),
                )[ $recipe['status'] ] ?? $recipe['status'];
                $status_pill = array(
                    'ready'      => 'drm-pill-green',
                    'no-match'   => 'drm-pill-orange',
                    'needs-tags' => 'drm-pill-grey',
                )[ $recipe['status'] ] ?? 'drm-pill-grey';
                $post_state = $recipe['post_status'] !== 'publish' ? ' (' . esc_html( $recipe['post_status'] ) . ')' : '';
            ?>
            <tr class="drm-cov-row" data-status="<?php echo esc_attr( $recipe['status'] ); ?>">
                <td>
                    <span class="drm-pill <?php echo esc_attr( $status_pill ); ?>">
                        <span class="drm-pill-dot"></span>
                        <span class="drm-pill-text"><?php echo esc_html( $status_label ); ?></span>
                    </span>
                </td>
                <td>
                    <div class="drm-cov-title">
                        <a href="<?php echo esc_url( $recipe['edit_url'] ); ?>" target="_blank">
                            <?php echo esc_html( $recipe['title'] ); ?>
                        </a><?php echo esc_html( $post_state ); ?>
                    </div>
                    <?php if ( $recipe['has_struct'] ) : ?>
                        <span class="drm-cov-source-badge drm-cov-source-badge--struct"><?php esc_html_e( 'Structured', 'delice-recipe-manager' ); ?></span>
                    <?php elseif ( $recipe['has_override'] ) : ?>
                        <span class="drm-cov-source-badge drm-cov-source-badge--override"><?php esc_html_e( 'Override', 'delice-recipe-manager' ); ?></span>
                    <?php else : ?>
                        <span class="drm-cov-source-badge drm-cov-source-badge--none"><?php esc_html_e( 'No data', 'delice-recipe-manager' ); ?></span>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;font-weight:600;"><?php echo intval( $recipe['ingredient_count'] ); ?></td>
                <td class="drm-cov-match-count" style="text-align:center;font-weight:600;color:<?php echo $recipe['match_count'] > 0 ? '#008a20' : '#8c8f94'; ?>;">
                    <?php echo intval( $recipe['match_count'] ); ?>
                </td>
                <td>
                    <div class="drm-cov-tag-wrap">
                        <textarea class="drm-cov-tags"
                                  rows="<?php echo $recipe['status'] === 'needs-tags' ? 3 : 2; ?>"
                                  placeholder="<?php esc_attr_e( "olive oil\nbutter\ngarlic", 'delice-recipe-manager' ); ?>"
                                  <?php echo $recipe['has_struct'] ? 'title="' . esc_attr__( 'Structured ingredients take priority — override only used when no structured data exists.', 'delice-recipe-manager' ) . '"' : ''; ?>
                                  ><?php echo esc_textarea( $recipe['override_text'] ); ?></textarea>
                        <button type="button"
                                class="button button-small drm-cov-save"
                                data-post-id="<?php echo intval( $recipe['id'] ); ?>">
                            <?php esc_html_e( 'Save', 'delice-recipe-manager' ); ?>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        </div><!-- /overflow-x:auto -->
    </div><!-- /.drm-card -->

    <!-- Pass nonce + ajax URL + delice recipes list to JS -->
    <script>
    window.drmAffTagsNonce  = '<?php echo esc_js( $aff_tags_nonce ); ?>';
    window.drmAjaxUrl       = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
    window.drmDeliceRecipes = <?php
        $drm_all = get_posts( array(
            'post_type'      => 'delice_recipe',
            'posts_per_page' => -1,
            'post_status'    => array( 'publish', 'draft', 'private' ),
            'fields'         => 'ids',
        ) );
        $drm_list = array();
        foreach ( $drm_all as $drm_pid ) {
            $drm_list[] = array( 'id' => $drm_pid, 'title' => get_the_title( $drm_pid ) );
        }
        echo wp_json_encode( $drm_list );
    ?>;
    </script>

    <!-- WP Recipe Maker bulk import card — v3.9.16 -->
    <?php if ( post_type_exists( 'wprm_recipe' ) ) : ?>
    <div class="drm-card" style="margin-top:24px;border-top:3px solid #2271b1;">
        <div class="drm-card-header">
            <div class="drm-card-header-left">
                <h2><?php esc_html_e( 'WP Recipe Maker — Bulk Affiliate Import', 'delice-recipe-manager' ); ?></h2>
                <span class="drm-card-badge" style="background:#e8f0fe;color:#1a56db;"><?php esc_html_e( 'WPRM detected', 'delice-recipe-manager' ); ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                <button type="button" id="drm-wprm-scan" class="button button-primary">
                    <?php esc_html_e( 'Scan WP Recipe Maker recipes', 'delice-recipe-manager' ); ?>
                </button>
                <span id="drm-wprm-status" style="font-size:13px;color:#646970;"></span>
            </div>
        </div>
        <div class="drm-card-body">
            <p style="margin:0 0 0;color:#50575e;font-size:13px;">
                <?php esc_html_e( 'Scan your WP Recipe Maker recipes and bulk-import their ingredient lists as Affiliate Tags Overrides on the matching Delice recipes. Matching is done by post title (case-insensitive). For recipes that do not auto-match, choose the correct Delice recipe from the dropdown.', 'delice-recipe-manager' ); ?>
            </p>
        </div>
        <div id="drm-wprm-results" style="display:none;">
            <div style="overflow-x:auto;">
            <table class="drm-cov-table" style="min-width:760px;">
                <thead>
                    <tr>
                        <th style="width:36px;text-align:center;">
                            <input type="checkbox" id="drm-wprm-select-all" title="<?php esc_attr_e( 'Select all', 'delice-recipe-manager' ); ?>">
                        </th>
                        <th><?php esc_html_e( 'WPRM Recipe', 'delice-recipe-manager' ); ?></th>
                        <th style="width:80px;text-align:center;"><?php esc_html_e( '# Ings', 'delice-recipe-manager' ); ?></th>
                        <th style="width:220px;"><?php esc_html_e( 'Delice Recipe Match', 'delice-recipe-manager' ); ?></th>
                        <th><?php esc_html_e( 'Ingredient Tags Preview', 'delice-recipe-manager' ); ?></th>
                    </tr>
                </thead>
                <tbody id="drm-wprm-tbody"></tbody>
            </table>
            </div>
            <div class="drm-card-footer">
                <?php /* disabled attr removed — JS enables this after a successful scan */ ?>
                <button type="button" id="drm-wprm-import" class="button button-primary" disabled>
                    <?php esc_html_e( 'Import Selected', 'delice-recipe-manager' ); ?>
                </button>
                <span id="drm-wprm-import-status" style="font-size:13px;color:#646970;"></span>
            </div>
        </div>
    </div>
    <?php else : ?>
    <div class="drm-card" style="margin-top:24px;border-top:3px solid #c3c4c7;opacity:.8;">
        <div class="drm-card-header">
            <div class="drm-card-header-left">
                <h2 style="color:#646970;"><?php esc_html_e( 'WP Recipe Maker — Bulk Affiliate Import', 'delice-recipe-manager' ); ?></h2>
                <span class="drm-card-badge" style="background:#f0f0f1;color:#646970;"><?php esc_html_e( 'WPRM not detected', 'delice-recipe-manager' ); ?></span>
            </div>
            <div style="flex-shrink:0;">
                <button type="button" class="button" disabled title="<?php esc_attr_e( 'Install WP Recipe Maker to enable this feature', 'delice-recipe-manager' ); ?>">
                    <?php esc_html_e( 'Scan WP Recipe Maker recipes', 'delice-recipe-manager' ); ?>
                </button>
            </div>
        </div>
        <div class="drm-card-body">
            <p style="margin:0;color:#646970;font-size:13px;">
                <?php esc_html_e( 'WP Recipe Maker is not active on this site. Install and activate it to bulk-import ingredient tags from your WPRM recipes into Delice affiliate overrides.', 'delice-recipe-manager' ); ?>
            </p>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /#tab-coverage -->

<!-- ═══════════════════════════════════════════════════════════════════
     TAB: SETTINGS
     ═══════════════════════════════════════════════════════════════════ -->
<div id="tab-settings" class="drm-aff-tab-panel<?php echo $active_tab === 'settings' ? ' is-active' : ''; ?>">
<form method="post" action="options.php">
<?php settings_fields( 'delice_affiliate_settings_group' ); ?>

<div class="drm-card">
    <div class="drm-card-header">
        <h2><?php esc_html_e( 'Global Settings', 'delice-recipe-manager' ); ?></h2>
    </div>
    <div class="drm-card-body">

        <div class="drm-aff-field">
            <label class="drm-aff-field-label"><?php esc_html_e( 'Affiliate Links', 'delice-recipe-manager' ); ?></label>
            <label class="drm-toggle-row">
                <span class="drm-sw">
                    <input type="checkbox" name="delice_affiliate_settings[enabled]" value="1" <?php checked( $settings['enabled'] ); ?>>
                    <span class="drm-sw-slider"></span>
                </span>
                <?php esc_html_e( 'Enable affiliate link injection on all recipe pages', 'delice-recipe-manager' ); ?>
            </label>
            <p class="description"><?php esc_html_e( 'When disabled, no links or disclosure appear on the frontend. All rules and platform connections are preserved.', 'delice-recipe-manager' ); ?></p>
        </div>

        <div class="drm-aff-field">
            <label class="drm-aff-field-label"><?php esc_html_e( 'Auto-link All Ingredients', 'delice-recipe-manager' ); ?></label>
            <label class="drm-toggle-row">
                <span class="drm-sw">
                    <input type="checkbox" name="delice_affiliate_settings[auto_link]" value="1" <?php checked( $settings['auto_link'] ); ?>>
                    <span class="drm-sw-slider"></span>
                </span>
                <?php esc_html_e( 'Automatically link every ingredient to Amazon — no keyword rules needed', 'delice-recipe-manager' ); ?>
            </label>
            <p class="description"><?php esc_html_e( 'When on, any ingredient without a matching keyword rule falls back to an Amazon search link using your Associates ID. Requires an active Amazon platform above. Manual rules still take priority and can point to specific products.', 'delice-recipe-manager' ); ?></p>
        </div>

        <hr class="drm-aff-divider">

        <div class="drm-2col">
            <div class="drm-aff-field">
                <label class="drm-aff-field-label" for="s-max-links"><?php esc_html_e( 'Max links per recipe', 'delice-recipe-manager' ); ?></label>
                <input type="number" id="s-max-links" name="delice_affiliate_settings[max_links]"
                       value="<?php echo intval( $settings['max_links'] ); ?>" min="1" max="20" class="small-text">
                <p class="description"><?php esc_html_e( 'Hard cap — prevents Google link-spam signals on short recipes. Recommended: 5.', 'delice-recipe-manager' ); ?></p>
            </div>
            <div class="drm-aff-field">
                <label class="drm-aff-field-label" for="s-density"><?php esc_html_e( 'Max ingredient density (%)', 'delice-recipe-manager' ); ?></label>
                <input type="number" id="s-density" name="delice_affiliate_settings[density_pct]"
                       value="<?php echo intval( $settings['density_pct'] ); ?>" min="1" max="100" class="small-text">
                <p class="description"><?php esc_html_e( '50 = max half of ingredients can have links. Lower limit (max links vs density) wins.', 'delice-recipe-manager' ); ?></p>
            </div>
        </div>

        <hr class="drm-aff-divider">

        <div class="drm-2col">
            <div class="drm-aff-field">
                <label class="drm-aff-field-label" for="s-btn-text"><?php esc_html_e( 'Button label', 'delice-recipe-manager' ); ?></label>
                <input type="text" id="s-btn-text" name="delice_affiliate_settings[button_text]"
                       value="<?php echo esc_attr( $settings['button_text'] ); ?>"
                       class="regular-text" placeholder="Buy">
                <p class="description"><?php esc_html_e( 'Text shown inside the buy button. Platform name appended when option below is on.', 'delice-recipe-manager' ); ?></p>
            </div>
            <div class="drm-aff-field">
                <label class="drm-aff-field-label"><?php esc_html_e( 'Show platform name', 'delice-recipe-manager' ); ?></label>
                <label class="drm-toggle-row">
                    <span class="drm-sw">
                        <input type="checkbox" name="delice_affiliate_settings[show_store_name]" value="1" <?php checked( $settings['show_store_name'] ); ?>>
                        <span class="drm-sw-slider"></span>
                    </span>
                    <?php esc_html_e( 'Append platform name — e.g. "Buy · Amazon"', 'delice-recipe-manager' ); ?>
                </label>
            </div>
        </div>

        <div class="drm-aff-field">
            <label class="drm-aff-field-label"><?php esc_html_e( 'Link target', 'delice-recipe-manager' ); ?></label>
            <label class="drm-toggle-row">
                <span class="drm-sw">
                    <input type="checkbox" name="delice_affiliate_settings[open_new_tab]" value="1" <?php checked( $settings['open_new_tab'] ); ?>>
                    <span class="drm-sw-slider"></span>
                </span>
                <?php esc_html_e( 'Open affiliate links in a new tab (recommended)', 'delice-recipe-manager' ); ?>
            </label>
        </div>

        <hr class="drm-aff-divider">

        <div class="drm-aff-field">
            <label class="drm-aff-field-label" for="s-disclosure"><?php esc_html_e( 'Affiliate disclosure text', 'delice-recipe-manager' ); ?></label>
            <textarea id="s-disclosure" name="delice_affiliate_settings[disclosure_text]"
                      rows="3" class="large-text"><?php echo esc_textarea( $settings['disclosure_text'] ); ?></textarea>
            <p class="description"><?php esc_html_e( 'Required by the FTC and Google. Must include "affiliate" and "commission". Only shown on recipe pages that actually have at least one affiliate link.', 'delice-recipe-manager' ); ?></p>
        </div>

        <div class="drm-aff-field">
            <label class="drm-aff-field-label"><?php esc_html_e( 'Disclosure position', 'delice-recipe-manager' ); ?></label>
            <label style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                <input type="radio" name="delice_affiliate_settings[disclosure_pos]" value="top" <?php checked( $settings['disclosure_pos'], 'top' ); ?>>
                <?php esc_html_e( 'Above ingredients (recommended — most visible)', 'delice-recipe-manager' ); ?>
            </label>
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="radio" name="delice_affiliate_settings[disclosure_pos]" value="bottom" <?php checked( $settings['disclosure_pos'], 'bottom' ); ?>>
                <?php esc_html_e( 'Below the recipe card', 'delice-recipe-manager' ); ?>
            </label>
        </div>

        <div class="drm-compliance-note">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            <span><?php esc_html_e( 'All affiliate links automatically carry rel="sponsored nofollow noopener noreferrer". Links are never added to Schema.org markup. Print stylesheets hide all affiliate buttons and disclosure banners.', 'delice-recipe-manager' ); ?></span>
        </div>
    </div>
    <div class="drm-card-footer">
        <?php submit_button( __( 'Save Settings', 'delice-recipe-manager' ), 'primary', 'submit', false ); ?>
    </div>
</div>
</form>
</div><!-- /#tab-settings -->

</div><!-- /.wrap -->
