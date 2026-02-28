<?php
/**
 * Affiliate Links admin page — v3.8.4
 *
 * Section 1 — Global Settings (enable, density, disclosure)
 * Section 2 — Keyword Rules  (keyword → affiliate URL mapping table)
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Insufficient permissions.', 'delice-recipe-manager' ) );

$settings = Delice_Affiliate_Manager::get_settings();
$rules    = Delice_Affiliate_Manager::get_rules();
$saved    = isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'];
?>
<div class="wrap delice-affiliate-wrap">

<!-- Header -->
<div class="delice-aff-header">
    <div class="delice-aff-header-inner">
        <div class="delice-aff-header-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <circle cx="12" cy="12" r="10"/>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><circle cx="12" cy="17" r=".5" fill="currentColor"/>
            </svg>
        </div>
        <div>
            <h1><?php esc_html_e( 'Affiliate Links', 'delice-recipe-manager' ); ?></h1>
            <p><?php esc_html_e( 'Map ingredient keywords to affiliate URLs. Links are injected automatically into recipe templates with Google-compliant rel attributes and FTC disclosure.', 'delice-recipe-manager' ); ?></p>
        </div>
    </div>
</div>

<?php if ( $saved ) : ?>
<div class="notice notice-success is-dismissible" style="margin:0 0 16px;"><p><?php esc_html_e( 'Affiliate settings saved.', 'delice-recipe-manager' ); ?></p></div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════
     SECTION 1 — GLOBAL SETTINGS
     ═══════════════════════════════════════════════════════ -->
<form method="post" action="options.php">
<?php settings_fields( 'delice_affiliate_settings_group' ); ?>

<div class="drm-card drm-card--affiliate">
    <div class="drm-card-header">
        <h2><?php esc_html_e( 'Global Settings', 'delice-recipe-manager' ); ?></h2>
    </div>
    <div class="drm-card-body">

        <!-- Enable / disable -->
        <div class="drm-aff-field">
            <label class="drm-affiliate-label"><?php esc_html_e( 'Affiliate Links', 'delice-recipe-manager' ); ?></label>
            <label class="drm-toggle-row">
                <span class="drm-sw">
                    <input type="checkbox" name="delice_affiliate_settings[enabled]" value="1" <?php checked( $settings['enabled'] ); ?>>
                    <span class="drm-sw-slider"></span>
                </span>
                <?php esc_html_e( 'Enable affiliate link injection on recipe pages', 'delice-recipe-manager' ); ?>
            </label>
            <p class="description"><?php esc_html_e( 'When disabled, no links or disclosure appear on the frontend. All rules are preserved.', 'delice-recipe-manager' ); ?></p>
        </div>

        <hr class="drm-aff-divider">

        <!-- Density controls -->
        <div class="drm-aff-row-2col">
            <div class="drm-aff-field">
                <label class="drm-affiliate-label" for="aff-max-links"><?php esc_html_e( 'Max links per recipe', 'delice-recipe-manager' ); ?></label>
                <input type="number" id="aff-max-links" name="delice_affiliate_settings[max_links]"
                       value="<?php echo intval( $settings['max_links'] ); ?>" min="1" max="20" class="small-text">
                <p class="description"><?php esc_html_e( 'Hard cap. Prevents link-spam signals on short recipes. Recommended: 5.', 'delice-recipe-manager' ); ?></p>
            </div>
            <div class="drm-aff-field">
                <label class="drm-affiliate-label" for="aff-density"><?php esc_html_e( 'Max ingredient density (%)', 'delice-recipe-manager' ); ?></label>
                <input type="number" id="aff-density" name="delice_affiliate_settings[density_pct]"
                       value="<?php echo intval( $settings['density_pct'] ); ?>" min="1" max="100" class="small-text">
                <p class="description"><?php esc_html_e( '50 = at most half of ingredients can show a link. Whichever limit (max links or density) is lower wins.', 'delice-recipe-manager' ); ?></p>
            </div>
        </div>

        <hr class="drm-aff-divider">

        <!-- Button label + store name -->
        <div class="drm-aff-row-2col">
            <div class="drm-aff-field">
                <label class="drm-affiliate-label" for="aff-btn-text"><?php esc_html_e( 'Link button label', 'delice-recipe-manager' ); ?></label>
                <input type="text" id="aff-btn-text" name="delice_affiliate_settings[button_text]"
                       value="<?php echo esc_attr( $settings['button_text'] ); ?>" class="regular-text"
                       placeholder="Buy on Amazon">
                <p class="description"><?php esc_html_e( 'Shown inside the buy button. The store name is appended when the option below is enabled.', 'delice-recipe-manager' ); ?></p>
            </div>
            <div class="drm-aff-field">
                <label class="drm-affiliate-label"><?php esc_html_e( 'Show store name in button', 'delice-recipe-manager' ); ?></label>
                <label class="drm-toggle-row">
                    <span class="drm-sw">
                        <input type="checkbox" name="delice_affiliate_settings[show_store_name]" value="1" <?php checked( $settings['show_store_name'] ); ?>>
                        <span class="drm-sw-slider"></span>
                    </span>
                    <?php esc_html_e( 'Append store name — e.g. "Buy · Amazon"', 'delice-recipe-manager' ); ?>
                </label>
            </div>
        </div>

        <!-- Open in new tab -->
        <div class="drm-aff-field">
            <label class="drm-affiliate-label"><?php esc_html_e( 'Link target', 'delice-recipe-manager' ); ?></label>
            <label class="drm-toggle-row">
                <span class="drm-sw">
                    <input type="checkbox" name="delice_affiliate_settings[open_new_tab]" value="1" <?php checked( $settings['open_new_tab'] ); ?>>
                    <span class="drm-sw-slider"></span>
                </span>
                <?php esc_html_e( 'Open affiliate links in a new tab (recommended)', 'delice-recipe-manager' ); ?>
            </label>
        </div>

        <hr class="drm-aff-divider">

        <!-- Disclosure text -->
        <div class="drm-aff-field">
            <label class="drm-affiliate-label" for="aff-disclosure"><?php esc_html_e( 'Affiliate disclosure text', 'delice-recipe-manager' ); ?></label>
            <textarea id="aff-disclosure" name="delice_affiliate_settings[disclosure_text]"
                      rows="3" class="large-text"><?php echo esc_textarea( $settings['disclosure_text'] ); ?></textarea>
            <p class="description"><?php esc_html_e( 'Required by the FTC and recommended by Google. Must include the words "affiliate" and "commission". Shown on every recipe that contains at least one affiliate link.', 'delice-recipe-manager' ); ?></p>
        </div>

        <!-- Disclosure position -->
        <div class="drm-aff-field">
            <label class="drm-affiliate-label"><?php esc_html_e( 'Disclosure position', 'delice-recipe-manager' ); ?></label>
            <label style="display:flex;align-items:center;gap:8px;margin-bottom:8px;cursor:pointer;">
                <input type="radio" name="delice_affiliate_settings[disclosure_pos]" value="top" <?php checked( $settings['disclosure_pos'], 'top' ); ?>>
                <?php esc_html_e( 'Above ingredients (recommended — most visible to Google and readers)', 'delice-recipe-manager' ); ?>
            </label>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="radio" name="delice_affiliate_settings[disclosure_pos]" value="bottom" <?php checked( $settings['disclosure_pos'], 'bottom' ); ?>>
                <?php esc_html_e( 'Below the full recipe card', 'delice-recipe-manager' ); ?>
            </label>
        </div>

        <!-- Google compliance note -->
        <div class="drm-aff-compliance-note">
            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" width="16" height="16" style="flex-shrink:0;margin-top:1px;">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <span>
                <?php esc_html_e( 'All injected links carry rel="sponsored nofollow noopener noreferrer" automatically. Affiliate links are never added to Schema.org JSON-LD markup. Print stylesheets hide affiliate buttons. Disclosure is shown only when at least one link is present on the page.', 'delice-recipe-manager' ); ?>
            </span>
        </div>

    </div>
    <div class="drm-card-footer">
        <?php submit_button( __( 'Save Settings', 'delice-recipe-manager' ), 'primary', 'submit', false ); ?>
    </div>
</div>
</form>

<!-- ═══════════════════════════════════════════════════════
     SECTION 2 — KEYWORD RULES TABLE
     ═══════════════════════════════════════════════════════ -->
<form method="post" action="options.php" id="delice-affiliate-rules-form">
<?php settings_fields( 'delice_affiliate_rules_group' ); ?>

<div class="drm-card drm-card--affiliate" style="margin-top:24px;">
    <div class="drm-card-header">
        <h2><?php esc_html_e( 'Keyword Rules', 'delice-recipe-manager' ); ?></h2>
        <div style="display:flex;gap:8px;flex-shrink:0;">
            <button type="button" id="aff-add-rule" class="button button-secondary">
                + <?php esc_html_e( 'Add Rule', 'delice-recipe-manager' ); ?>
            </button>
            <label class="button button-secondary" for="aff-csv-import"
                   title="<?php esc_attr_e( 'CSV columns: keyword, url, store, match_type', 'delice-recipe-manager' ); ?>"
                   style="cursor:pointer;">
                <?php esc_html_e( 'Import CSV', 'delice-recipe-manager' ); ?>
                <input type="file" id="aff-csv-import" accept=".csv" style="display:none;">
            </label>
        </div>
    </div>

    <div class="drm-card-body" style="padding:0;">
        <div class="drm-aff-rules-hint">
            <?php esc_html_e( 'Match order: Exact → Starts with → Contains. Longest keyword wins within each tier. CSV format: keyword, affiliate_url, store_name, match_type (exact|starts|contains)', 'delice-recipe-manager' ); ?>
        </div>

        <div style="overflow-x:auto;">
            <table class="drm-aff-table" id="aff-rules-table">
                <thead>
                    <tr>
                        <th class="col-active"><?php esc_html_e( 'On', 'delice-recipe-manager' ); ?></th>
                        <th class="col-keyword"><?php esc_html_e( 'Keyword', 'delice-recipe-manager' ); ?></th>
                        <th class="col-url"><?php esc_html_e( 'Affiliate URL', 'delice-recipe-manager' ); ?></th>
                        <th class="col-store"><?php esc_html_e( 'Store', 'delice-recipe-manager' ); ?></th>
                        <th class="col-match"><?php esc_html_e( 'Match', 'delice-recipe-manager' ); ?></th>
                        <th class="col-del"><?php esc_html_e( 'Del', 'delice-recipe-manager' ); ?></th>
                    </tr>
                </thead>
                <tbody id="aff-rules-tbody">
                <?php foreach ( $rules as $i => $rule ) :
                    $id = esc_attr( $rule['id'] ?? 'aff_' . $i );
                    $mt = $rule['match_type'] ?? 'contains';
                ?>
                <tr class="aff-rule-row" data-id="<?php echo $id; ?>">
                    <td class="col-active">
                        <input type="hidden" name="delice_affiliate_rules[<?php echo $i; ?>][id]" value="<?php echo $id; ?>">
                        <label class="delice-sw">
                            <input type="checkbox" name="delice_affiliate_rules[<?php echo $i; ?>][active]" value="1" <?php checked( ! empty( $rule['active'] ) ); ?>>
                            <span class="delice-sw-slider"></span>
                        </label>
                    </td>
                    <td class="col-keyword">
                        <input type="text" name="delice_affiliate_rules[<?php echo $i; ?>][keyword]"
                               value="<?php echo esc_attr( $rule['keyword'] ?? '' ); ?>"
                               placeholder="<?php esc_attr_e( 'e.g. olive oil', 'delice-recipe-manager' ); ?>"
                               class="aff-input aff-input-keyword">
                    </td>
                    <td class="col-url">
                        <input type="url" name="delice_affiliate_rules[<?php echo $i; ?>][url]"
                               value="<?php echo esc_url( $rule['url'] ?? '' ); ?>"
                               placeholder="https://amzn.to/xxxxx"
                               class="aff-input aff-input-url">
                    </td>
                    <td class="col-store">
                        <input type="text" name="delice_affiliate_rules[<?php echo $i; ?>][store]"
                               value="<?php echo esc_attr( $rule['store'] ?? '' ); ?>"
                               placeholder="Amazon"
                               class="aff-input aff-input-store">
                    </td>
                    <td class="col-match">
                        <select name="delice_affiliate_rules[<?php echo $i; ?>][match_type]" class="aff-select">
                            <option value="contains" <?php selected( $mt, 'contains' ); ?>><?php esc_html_e( 'Contains', 'delice-recipe-manager' ); ?></option>
                            <option value="starts"   <?php selected( $mt, 'starts' );   ?>><?php esc_html_e( 'Starts with', 'delice-recipe-manager' ); ?></option>
                            <option value="exact"    <?php selected( $mt, 'exact' );    ?>><?php esc_html_e( 'Exact', 'delice-recipe-manager' ); ?></option>
                        </select>
                    </td>
                    <td class="col-del">
                        <button type="button" class="button-link aff-remove-row" title="<?php esc_attr_e( 'Remove', 'delice-recipe-manager' ); ?>" aria-label="<?php esc_attr_e( 'Remove rule', 'delice-recipe-manager' ); ?>">&#x2715;</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div id="aff-empty-state" class="drm-aff-empty<?php echo ! empty( $rules ) ? ' hidden' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 4v16m-8-8h16"/></svg>
                <p><?php esc_html_e( 'No rules yet. Click "Add Rule" or import a CSV to get started.', 'delice-recipe-manager' ); ?></p>
            </div>
        </div>
    </div>

    <div class="drm-card-footer">
        <?php submit_button( __( 'Save Rules', 'delice-recipe-manager' ), 'primary', 'submit', false ); ?>
        <span id="aff-rule-count" style="font-size:12px;color:#888;">
            <?php printf( esc_html( _n( '%d rule', '%d rules', count( $rules ), 'delice-recipe-manager' ) ), count( $rules ) ); ?>
        </span>
    </div>
</div>
</form>

</div><!-- /.wrap -->
