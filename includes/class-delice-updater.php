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
 *  1. pre_set_site_transient_update_plugins  – Reads the Version: header from
 *     the main branch plugin file, compares versions, and injects an update
 *     notice when a newer version is found (fires when WP rewrites the
 *     transient, ~every 12 h).
 *  2. site_transient_update_plugins          – Re-injects the update on every
 *     transient READ using only locally cached data, so the "Update Available"
 *     badge appears immediately without waiting for the next WP update cycle.
 *  3. plugins_api                            – Supplies the "View details"
 *     popup in the WP admin (changelog, version, etc.).
 *  4. upgrader_pre_download                  – For private repos, intercepts
 *     the download and adds the Authorization header so the zip can be
 *     fetched without making the repo public.
 *  5. upgrader_source_selection              – Renames the extracted GitHub
 *     archive directory to match the expected plugin folder name.
 *  6. upgrader_process_complete              – Clears the cached API response
 *     after a successful update.
 *
 * Update workflow (no GitHub Releases or tags needed):
 *  1. Bump the Version: line in the plugin's main PHP file.
 *  2. Push to the main branch.
 *  3. WordPress detects the new version on the next check (or immediately
 *     after clicking "Clear Cache & Check Now") and shows "Update Now".
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
        add_filter( 'site_transient_update_plugins',         array( $this, 'inject_update_on_read' ) );
        add_filter( 'plugins_api',                           array( $this, 'plugin_info' ), 20, 3 );
        add_filter( 'upgrader_pre_download',                 array( $this, 'pre_download' ), 10, 3 );
        add_filter( 'upgrader_source_selection',             array( $this, 'fix_source_directory' ), 10, 4 );
        add_action( 'upgrader_process_complete',             array( $this, 'purge_cache' ), 10, 2 );
    }

    // -------------------------------------------------------------------------
    // GitHub API
    // -------------------------------------------------------------------------

    /**
     * Fetch the remote version by reading the Version: header from the main
     * branch plugin file directly — no GitHub Releases or tags needed.
     *
     * For public repos the raw.githubusercontent.com URL is used (no rate
     * limit concerns).  For private repos the GitHub Contents API is used
     * with the stored Personal Access Token.
     *
     * The response is cached in a transient for $cache_ttl seconds.
     *
     * @return object|false  Release-like object with tag_name and zipball_url, or false on failure.
     */
    public function get_release_info() {
        $cached = get_transient( $this->cache_key );

        // A cached failure marker is stored as (object)['api_error' => <code>].
        if ( false !== $cached ) {
            return isset( $cached->api_error ) ? false : $cached;
        }

        // The main plugin file name, e.g. "delice-recipe-manager.php".
        $plugin_file = basename( $this->slug );

        if ( ! empty( $this->token ) ) {
            // Private repo: GitHub Contents API returns the file as base64 JSON.
            $url  = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/contents/{$plugin_file}?ref=main";
            $args = array(
                'timeout' => 15,
                'headers' => array(
                    'Accept'               => 'application/vnd.github+json',
                    'Authorization'        => 'Bearer ' . $this->token,
                    'X-GitHub-Api-Version' => '2022-11-28',
                    'User-Agent'           => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
                ),
            );
        } else {
            // Public repo: raw file, no authentication required.
            $url  = "https://raw.githubusercontent.com/{$this->github_user}/{$this->github_repo}/main/{$plugin_file}";
            $args = array(
                'timeout' => 15,
                'headers' => array(
                    'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
                ),
            );
        }

        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            set_transient( $this->cache_key, (object) array( 'api_error' => 0 ), 5 * MINUTE_IN_SECONDS );
            return false;
        }

        $code = (int) wp_remote_retrieve_response_code( $response );

        if ( 200 !== $code ) {
            $ttl = ( 429 === $code ) ? HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS;
            set_transient( $this->cache_key, (object) array( 'api_error' => $code ), $ttl );
            return false;
        }

        $body = wp_remote_retrieve_body( $response );

        if ( ! empty( $this->token ) ) {
            // Contents API wraps the file in JSON with base64 content.
            $data = json_decode( $body );
            if ( ! $data || empty( $data->content ) ) {
                set_transient( $this->cache_key, (object) array( 'api_error' => 200 ), 5 * MINUTE_IN_SECONDS );
                return false;
            }
            $file_content = base64_decode( str_replace( "\n", '', $data->content ) );
        } else {
            $file_content = $body;
        }

        // Pull the Version: value out of the WordPress plugin header comment.
        if ( ! preg_match( '/^\s*\*\s*Version:\s*(.+)$/mi', $file_content, $matches ) ) {
            set_transient( $this->cache_key, (object) array( 'api_error' => 200 ), 5 * MINUTE_IN_SECONDS );
            return false;
        }

        $remote_version = trim( $matches[1] );

        // Build a release-like object so the rest of the code is unchanged.
        $release = (object) array(
            'tag_name'     => $remote_version,
            'zipball_url'  => "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/zipball/main",
            'name'         => "Version {$remote_version}",
            'body'         => '',
            'published_at' => '',
        );

        set_transient( $this->cache_key, $release, $this->cache_ttl );
        return $release;
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
     * Hook: site_transient_update_plugins  (READ filter)
     *
     * pre_set_site_transient_update_plugins only fires when WordPress
     * rewrites the transient — roughly every 12 hours.  Between rewrites,
     * WordPress reads the same stale transient on every admin page and the
     * update badge never appears for our plugin even when a newer release
     * exists on GitHub.
     *
     * This filter fires on every READ of the transient.  It re-injects the
     * update entry from our locally cached release data (no new API call),
     * so the "Update Available" badge and the "Update Now" button appear as
     * soon as the GitHub release is first detected — no 12-hour wait.
     *
     * @param  object $transient  The update_plugins transient.
     * @return object
     */
    public function inject_update_on_read( $transient ) {
        if ( ! is_object( $transient ) ) {
            return $transient;
        }

        $release = $this->get_release_info();
        if ( ! $release ) {
            return $transient;
        }

        $remote_version = ltrim( $release->tag_name, 'v' );

        if ( version_compare( $this->version, $remote_version, '<' ) ) {
            if ( ! isset( $transient->response ) ) {
                $transient->response = array();
            }
            $transient->response[ $this->slug ] = (object) array(
                'id'             => $this->slug,
                'slug'           => dirname( $this->slug ),
                'plugin'         => $this->slug,
                'new_version'    => $remote_version,
                'url'            => "https://github.com/{$this->github_user}/{$this->github_repo}",
                'package'        => $release->zipball_url,
                'icons'          => array(),
                'banners'        => array(),
                'tested'         => get_bloginfo( 'version' ),
                'requires_php'   => '7.4',
                'upgrade_notice' => ! empty( $release->name ) ? sanitize_text_field( $release->name ) : '',
            );
        } else {
            // Ensure the plugin is not stuck showing a stale update badge.
            if ( isset( $transient->response[ $this->slug ] ) ) {
                unset( $transient->response[ $this->slug ] );
            }
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
                    'Accept'               => 'application/vnd.github+json',
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
