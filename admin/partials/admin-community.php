<?php
/**
 * Community page — Reviews, Submissions, Authors
 *
 * Tabs:
 *  - reviews     → admin-review-settings.php
 *  - submissions → eeat/admin-user-submissions.php
 *  - authors     → eeat/admin-author-profiles.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'reviews';
$valid_tabs = array( 'reviews', 'submissions', 'authors' );
if ( ! in_array( $active_tab, $valid_tabs, true ) ) {
    $active_tab = 'reviews';
}
$tab_url = admin_url( 'admin.php?page=delice-recipe-community&tab=' );
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Community', 'delice-recipe-manager' ); ?></h1>

    <nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Community sections', 'delice-recipe-manager' ); ?>">
        <a href="<?php echo esc_url( $tab_url . 'reviews' ); ?>" class="nav-tab<?php echo $active_tab === 'reviews' ? ' nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Reviews', 'delice-recipe-manager' ); ?>
        </a>
        <a href="<?php echo esc_url( $tab_url . 'submissions' ); ?>" class="nav-tab<?php echo $active_tab === 'submissions' ? ' nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Submissions', 'delice-recipe-manager' ); ?>
        </a>
        <a href="<?php echo esc_url( $tab_url . 'authors' ); ?>" class="nav-tab<?php echo $active_tab === 'authors' ? ' nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Authors', 'delice-recipe-manager' ); ?>
        </a>
    </nav>

    <div style="padding-top: 16px;">
        <?php
        switch ( $active_tab ) {
            case 'submissions':
                include DELICE_RECIPE_PLUGIN_DIR . 'admin/partials/eeat/admin-user-submissions.php';
                break;
            case 'authors':
                include DELICE_RECIPE_PLUGIN_DIR . 'admin/partials/eeat/admin-author-profiles.php';
                break;
            default: // reviews
                include DELICE_RECIPE_PLUGIN_DIR . 'admin/partials/admin-review-settings.php';
                break;
        }
        ?>
    </div>
</div>
