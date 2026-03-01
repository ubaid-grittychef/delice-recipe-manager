<?php
/**
 * Tools page — Test Recipes, Import/Export, Migration
 *
 * Tabs:
 *  - testing      → eeat/admin-recipe-testing.php
 *  - import-export → admin-import-export.php
 *  - migration    → admin-migration.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'testing';
$valid_tabs = array( 'testing', 'import-export', 'migration' );
if ( ! in_array( $active_tab, $valid_tabs, true ) ) {
    $active_tab = 'testing';
}
$tab_url = admin_url( 'admin.php?page=delice-recipe-tools&tab=' );
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Tools', 'delice-recipe-manager' ); ?></h1>

    <nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Tools sections', 'delice-recipe-manager' ); ?>">
        <a href="<?php echo esc_url( $tab_url . 'testing' ); ?>" class="nav-tab<?php echo $active_tab === 'testing' ? ' nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Test Recipes', 'delice-recipe-manager' ); ?>
        </a>
        <a href="<?php echo esc_url( $tab_url . 'import-export' ); ?>" class="nav-tab<?php echo $active_tab === 'import-export' ? ' nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Import / Export', 'delice-recipe-manager' ); ?>
        </a>
        <a href="<?php echo esc_url( $tab_url . 'migration' ); ?>" class="nav-tab<?php echo $active_tab === 'migration' ? ' nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Migration', 'delice-recipe-manager' ); ?>
        </a>
    </nav>

    <div style="padding-top: 16px;">
        <?php
        switch ( $active_tab ) {
            case 'import-export':
                include DELICE_RECIPE_PLUGIN_DIR . 'admin/partials/admin-import-export.php';
                break;
            case 'migration':
                include DELICE_RECIPE_PLUGIN_DIR . 'admin/partials/admin-migration.php';
                break;
            default: // testing
                include DELICE_RECIPE_PLUGIN_DIR . 'admin/partials/eeat/admin-recipe-testing.php';
                break;
        }
        ?>
    </div>
</div>
