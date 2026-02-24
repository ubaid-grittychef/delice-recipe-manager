<?php
/**
 * Self-hosted GitHub Auto-Updater for WP Delicious Recipe
 *
 * Hooks into WordPress's native update system so the plugin can receive
 * one-click updates directly from a GitHub repository — no third-party
 * service needed.  Works for both public repos (no token) and private
 * repos (Personal Access Token stored in wp_options).
 *
 * How it works:
 *  1. pre_set_site_transient_update_plugins  – Fetches the latest GitHub
 *     release, compares versions, and injects an update notice when a newer
 *     version is found.
 *  2. plugins_api                            – Supplies the "View details"
 *     popup in the WP admin (changelog, version, etc.).
 *  3. upgrader_pre_download                  – For private repos, intercepts
 *     the download and adds the Authorization header so the zip can be
 *     fetched without making the repo public.
 *  4. upgrader_process_complete              – Clears the cached API response
 *     after a successful update.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Delice_GitHub_Updater {

    /** WordPress plugin slug, e.g. "delice-recipe-manager/delice-recipe-manager.php" */
    private $slug;

    /** GitHub username / organisation */
    private $github_user;

    /** GitHub repository name */
    private $github_repo;

    /** Currently installed plugin version */
    private $version;

    /** Optional GitHub Personal Access Token (needed for private repos) */
    private $token;

    /** Transient key used to cache the API response */
    private $cache_key;

    /** How long to cache the API response (seconds).  Default: 12 hours. */
    private $cache_ttl = 43200;

    /**
     * @param string $plugin_file  Absolute path to the main plugin file (__FILE__).
     * @param string $github_user  GitHub username or organisation.
     * @param string $github_repo  Repository name.
     * @param string $version      Current plugin version string.
     */
    public function __construct( $plugin_file, $github_user, $github_repo, $version ) {
        $this->slug        = plugin_basename( $plugin_file );
        $this->github_user = sanitize_text_field( $github_user );
        $this->github_repo = sanitize_text_field( $github_repo );
        $this->version     = $version;
        $this->token       = get_option( 'delice_github_token', '' );
        $this->cache_key   = 'delice_gh_updater_' . md5( $this->slug );

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
        add_filter( 'plugins_api',                           array( $this, 'plugin_info' ), 20, 3 );
        add_filter( 'upgrader_pre_download',                 array( $this, 'pre_download' ), 10, 3 );
        add_filter( 'upgrader_source_selection',             array( $this, 'fix_source_directory' ), 10, 4 );
        add_action( 'upgrader_process_complete',             array( $this, 'purge_cache' ), 10, 2 );
    }

    // -------------------------------------------------------------------------
    // GitHub API
    // -------------------------------------------------------------------------

    /**
     * Fetch the latest release from the GitHub API.
     *
     * The response is cached in a transient for $cache_ttl seconds to avoid
     * hammering the API on every admin page load.
     *
     * @return object|false  Decoded JSON release object, or false on failure.
     */
    public function get_release_info() {
        $cached = get_transient( $this->cache_key );

        // A cached failure marker is stored as (object)['api_error' => <code>].
        // Return false for those so the rest of the code sees no release data,
        // but don't hammer the API again until the short-TTL entry expires.
        if ( false !== $cached ) {
            return isset( $cached->api_error ) ? false : $cached;
        }

        $url  = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/releases/latest";
        $args = array(
            'timeout' => 15,
            'headers' => array(
                'Accept'               => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent'           => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
            ),
        );

        if ( ! empty( $this->token ) ) {
            $args['headers']['Authorization'] = 'Bearer ' . $this->token;
        }

        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            // Network failure — retry after 5 minutes.
            set_transient( $this->cache_key, (object) array( 'api_error' => 0 ), 5 * MINUTE_IN_SECONDS );
            return false;
        }

        $code = (int) wp_remote_retrieve_response_code( $response );

        if ( 200 !== $code ) {
            // Rate-limited (429) or auth error: cache failure briefly to avoid
            // hammering the API.  Use 1 hour for rate limits, 5 min otherwise.
            $ttl = ( 429 === $code ) ? HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS;
            set_transient( $this->cache_key, (object) array( 'api_error' => $code ), $ttl );
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ) );

        if ( empty( $body ) || empty( $body->tag_name ) ) {
            set_transient( $this->cache_key, (object) array( 'api_error' => $code ), 5 * MINUTE_IN_SECONDS );
            return false;
        }

        set_transient( $this->cache_key, $body, $this->cache_ttl );
        return $body;
    }

    // -------------------------------------------------------------------------
    // WordPress hooks
    // -------------------------------------------------------------------------

    /**
     * Hook: pre_set_site_transient_update_plugins
     *
     * Checks whether a newer version exists on GitHub and, if so, injects it
     * into the update transient so WordPress displays an update notice.
     *
     * @param  object $transient  The update_plugins transient.
     * @return object
     */
    public function check_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $release = $this->get_release_info();
        if ( ! $release ) {
            return $transient;
        }

        $remote_version = ltrim( $release->tag_name, 'v' );

        if ( version_compare( $this->version, $remote_version, '<' ) ) {
            $transient->response[ $this->slug ] = (object) array(
                'id'          => $this->slug,
                'slug'        => dirname( $this->slug ),
                'plugin'      => $this->slug,
                'new_version' => $remote_version,
                'url'         => "https://github.com/{$this->github_user}/{$this->github_repo}",
                'package'     => $release->zipball_url,
                'icons'       => array(),
                'banners'     => array(),
                'tested'      => get_bloginfo( 'version' ),
                'requires_php'=> '7.4',
                'upgrade_notice' => ! empty( $release->name ) ? sanitize_text_field( $release->name ) : '',
            );
        } else {
            // Ensure the plugin is not stuck in "needs update" from a stale transient.
            unset( $transient->response[ $this->slug ] );
        }

        return $transient;
    }

    /**
     * Hook: plugins_api
     *
     * Provides plugin metadata for the "View details" popup in the admin.
     *
     * @param  false|object|array $result  Existing result.
     * @param  string             $action  Requested action.
     * @param  object             $args    Request args.
     * @return false|object
     */
    public function plugin_info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action ) {
            return $result;
        }

        if ( ! isset( $args->slug ) || $args->slug !== dirname( $this->slug ) ) {
            return $result;
        }

        $release = $this->get_release_info();
        if ( ! $release ) {
            return $result;
        }

        $remote_version = ltrim( $release->tag_name, 'v' );

        $changelog = ! empty( $release->body )
            ? '<pre>' . esc_html( $release->body ) . '</pre>'
            : '<p>' . esc_html__( 'See GitHub releases for the full changelog.', 'delice-recipe-manager' ) . '</p>';

        return (object) array(
            'name'              => 'WP Delicious Recipe',
            'slug'              => dirname( $this->slug ),
            'version'           => $remote_version,
            'author'            => 'Delice Team',
            'homepage'          => "https://github.com/{$this->github_user}/{$this->github_repo}",
            'short_description' => __( 'A powerful recipe manager plugin for WordPress.', 'delice-recipe-manager' ),
            'sections'          => array(
                'changelog' => $changelog,
            ),
            'download_link'     => $release->zipball_url,
            'last_updated'      => ! empty( $release->published_at ) ? $release->published_at : '',
            'tested'            => get_bloginfo( 'version' ),
            'requires_php'      => '7.4',
        );
    }

    /**
     * Hook: upgrader_pre_download
     *
     * For private repositories, WordPress cannot download the zip without an
     * Authorization header.  This filter intercepts the download, fetches the
     * file via wp_remote_get() with the PAT, saves it to a temp file, and
     * returns the local path so WordPress can continue the upgrade as normal.
     *
     * For public repositories (no token) this method does nothing and lets
     * WordPress handle the download itself.
     *
     * @param  bool|WP_Error $reply     Return value to short-circuit the download.
     * @param  string        $package   Download URL.
     * @param  object        $upgrader  WP_Upgrader instance.
     * @return bool|string|WP_Error
     */
    public function pre_download( $reply, $package, $upgrader ) {
        // Only act on packages from our repository.
        $our_repo_pattern = "api.github.com/repos/{$this->github_user}/{$this->github_repo}";
        if ( strpos( $package, $our_repo_pattern ) === false ) {
            return $reply;
        }

        // Public repo: let WordPress download normally.
        if ( empty( $this->token ) ) {
            return $reply;
        }

        // Private repo: download with auth header.
        $tmpfile = wp_tempnam( 'delice-update' );

        $response = wp_remote_get(
            $package,
            array(
                'timeout'  => 120,
                'stream'   => true,
                'filename' => $tmpfile,
                'headers'  => array(
                    'Accept'               => 'application/octet-stream',
                    'Authorization'        => 'Bearer ' . $this->token,
                    'X-GitHub-Api-Version' => '2022-11-28',
                    'User-Agent'           => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            @unlink( $tmpfile );
            return $response;
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            @unlink( $tmpfile );
            return new WP_Error(
                'delice_github_download_failed',
                /* translators: %d: HTTP status code */
                sprintf( __( 'GitHub download failed (HTTP %d). Check your Personal Access Token.', 'delice-recipe-manager' ), $code )
            );
        }

        return $tmpfile;
    }

    /**
     * Hook: upgrader_source_selection
     *
     * GitHub release zips extract to a directory named "{user}-{repo}-{hash}/"
     * instead of the plugin's own folder name.  WordPress would then install
     * the update under a different directory, leaving the old files in place.
     *
     * This filter detects that situation and renames the extracted directory
     * to the correct plugin folder so the update lands in the right place.
     *
     * @param  string|WP_Error $source        Path to the extracted source dir.
     * @param  string          $remote_source  Temp directory containing the source.
     * @param  WP_Upgrader     $upgrader       Upgrader instance.
     * @param  array           $hook_extra     Extra data (may include 'plugin').
     * @return string|WP_Error
     */
    public function fix_source_directory( $source, $remote_source, $upgrader, $hook_extra = array() ) {
        global $wp_filesystem;

        // If WordPress already knows this update is for a different plugin, bail.
        if ( isset( $hook_extra['plugin'] ) && $hook_extra['plugin'] !== $this->slug ) {
            return $source;
        }

        $plugin_dir  = dirname( $this->slug ); // e.g. "delice-recipe-manager"
        $source_base = basename( rtrim( $source, '/' ) );

        // Nothing to do — directory is already named correctly.
        if ( $source_base === $plugin_dir ) {
            return $source;
        }

        // Only act on directories that look like they came from our GitHub repo
        // (GitHub archives: "{user}-{repo}-{hash}").
        if ( strpos( $source_base, $this->github_repo ) === false ) {
            return $source;
        }

        if ( ! $wp_filesystem ) {
            return $source;
        }

        $target = trailingslashit( $remote_source ) . $plugin_dir;

        if ( ! $wp_filesystem->move( rtrim( $source, '/' ), $target ) ) {
            return new WP_Error(
                'delice_updater_rename_failed',
                __( 'Could not rename the update package. Please try again.', 'delice-recipe-manager' )
            );
        }

        return trailingslashit( $target );
    }

    /**
     * Hook: upgrader_process_complete
     *
     * Deletes the cached API response after a plugin update so the next
     * admin page load fetches fresh data from GitHub.
     *
     * @param WP_Upgrader $upgrader Upgrader instance.
     * @param array       $options  Upgrade options.
     */
    public function purge_cache( $upgrader, $options ) {
        if (
            isset( $options['action'], $options['type'] ) &&
            'update' === $options['action'] &&
            'plugin' === $options['type']
        ) {
            delete_transient( $this->cache_key );
        }
    }
}
