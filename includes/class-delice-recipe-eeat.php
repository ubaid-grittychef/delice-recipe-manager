<?php
/**
 * E-E-A-T Manager Class
 * 
 * Coordinates all Experience, Expertise, Authoritativeness, and Trustworthiness features
 * 
 * @package Delice_Recipe_Manager
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Delice_Recipe_EEAT {

    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Database version for migrations
     */
    const DB_VERSION = '1.0';
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Initialize immediately if already past plugins_loaded, otherwise hook in
        if (did_action('plugins_loaded')) {
            $this->init();
        } else {
            add_action('plugins_loaded', array($this, 'init'));
        }
    }

    /**
     * Initialize E-E-A-T features
     */
    public function init() {
        // Check if tables need updating
        $this->maybe_update_tables();
        
        // Load dependencies
        $this->load_dependencies();
        
        // Register hooks
        $this->register_hooks();
    }

    /**
     * Load required classes
     */
    private function load_dependencies() {
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/eeat/class-delice-recipe-experience.php';
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/eeat/class-delice-recipe-expertise.php';
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/eeat/class-delice-recipe-authority.php';
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/eeat/class-delice-recipe-trust.php';
        require_once DELICE_RECIPE_PLUGIN_DIR . 'includes/eeat/class-delice-author-profile.php';
        
        // Initialize singleton instances to register their hooks
        Delice_Recipe_Experience::get_instance();
        Delice_Recipe_Expertise::get_instance();
        Delice_Recipe_Authority::get_instance();
        Delice_Recipe_Trust::get_instance();
    }

    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        // Admin menu
        add_action('admin_menu', array($this, 'add_eeat_menu'), 25);
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // AJAX handlers
        $this->register_ajax_handlers();
    }

    /**
     * Create database tables for E-E-A-T features
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Recipe Testing Table
        $table_testing = $wpdb->prefix . 'delice_recipe_testing';
        $sql_testing = "CREATE TABLE $table_testing (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            recipe_id BIGINT(20) UNSIGNED NOT NULL,
            tester_id BIGINT(20) UNSIGNED DEFAULT NULL,
            tester_name VARCHAR(100) NOT NULL,
            tester_email VARCHAR(100) DEFAULT NULL,
            test_date DATE NOT NULL,
            success_rating TINYINT(1) NOT NULL,
            difficulty_experienced VARCHAR(20) DEFAULT NULL,
            time_actual_prep INT DEFAULT NULL,
            time_actual_cook INT DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            would_make_again BOOLEAN DEFAULT 1,
            photo_url VARCHAR(255) DEFAULT NULL,
            verified BOOLEAN DEFAULT 0,
            featured BOOLEAN DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY recipe_idx (recipe_id),
            KEY tester_idx (tester_id),
            KEY verified_idx (verified)
        ) $charset_collate;";
        dbDelta($sql_testing);
        
        // User Cooks Table (I Made This)
        $table_cooks = $wpdb->prefix . 'delice_user_cooks';
        $sql_cooks = "CREATE TABLE $table_cooks (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            recipe_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED DEFAULT NULL,
            user_email VARCHAR(100) DEFAULT NULL,
            user_name VARCHAR(100) NOT NULL,
            photo_url VARCHAR(255) DEFAULT NULL,
            cook_date DATE DEFAULT NULL,
            prep_time_actual INT DEFAULT NULL,
            cook_time_actual INT DEFAULT NULL,
            difficulty_rating VARCHAR(20) DEFAULT NULL,
            modifications TEXT DEFAULT NULL,
            success_rating TINYINT(1) DEFAULT NULL,
            would_recommend BOOLEAN DEFAULT 1,
            approved BOOLEAN DEFAULT 0,
            featured BOOLEAN DEFAULT 0,
            helpful_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY recipe_idx (recipe_id),
            KEY approved_idx (approved),
            KEY featured_idx (featured)
        ) $charset_collate;";
        dbDelta($sql_cooks);
        
        // Author Profiles Table
        $table_profiles = $wpdb->prefix . 'delice_author_profiles';
        $sql_profiles = "CREATE TABLE $table_profiles (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED UNIQUE NOT NULL,
            display_name VARCHAR(100) DEFAULT NULL,
            bio TEXT DEFAULT NULL,
            photo_url VARCHAR(255) DEFAULT NULL,
            credentials JSON DEFAULT NULL,
            experience_years INT DEFAULT 0,
            specializations JSON DEFAULT NULL,
            certifications JSON DEFAULT NULL,
            education JSON DEFAULT NULL,
            publications JSON DEFAULT NULL,
            awards JSON DEFAULT NULL,
            social_links JSON DEFAULT NULL,
            verified BOOLEAN DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_idx (user_id),
            KEY verified_idx (verified)
        ) $charset_collate;";
        dbDelta($sql_profiles);
        
        // Expert Endorsements Table
        $table_endorsements = $wpdb->prefix . 'delice_expert_endorsements';
        $sql_endorsements = "CREATE TABLE $table_endorsements (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            recipe_id BIGINT(20) UNSIGNED NOT NULL,
            expert_name VARCHAR(100) NOT NULL,
            expert_title VARCHAR(100) DEFAULT NULL,
            expert_credentials VARCHAR(255) DEFAULT NULL,
            expert_photo_url VARCHAR(255) DEFAULT NULL,
            endorsement_text TEXT DEFAULT NULL,
            endorsement_date DATE DEFAULT NULL,
            verified BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY recipe_idx (recipe_id)
        ) $charset_collate;";
        dbDelta($sql_endorsements);
        
        // Recipe History Table (Publication tracking)
        $table_history = $wpdb->prefix . 'delice_recipe_history';
        $sql_history = "CREATE TABLE $table_history (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            recipe_id BIGINT(20) UNSIGNED NOT NULL,
            version VARCHAR(10) NOT NULL,
            updated_by BIGINT(20) UNSIGNED NOT NULL,
            update_type VARCHAR(50) DEFAULT NULL,
            changes_summary TEXT DEFAULT NULL,
            updated_date DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY recipe_idx (recipe_id)
        ) $charset_collate;";
        dbDelta($sql_history);
        
        // Nutrition Reviews Table
        $table_nutrition = $wpdb->prefix . 'delice_nutrition_reviews';
        $sql_nutrition = "CREATE TABLE $table_nutrition (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            recipe_id BIGINT(20) UNSIGNED NOT NULL,
            nutritionist_id BIGINT(20) UNSIGNED NOT NULL,
            nutritionist_name VARCHAR(100) NOT NULL,
            nutritionist_credentials VARCHAR(255) DEFAULT NULL,
            review_text TEXT DEFAULT NULL,
            dietary_notes TEXT DEFAULT NULL,
            health_benefits TEXT DEFAULT NULL,
            allergen_warnings TEXT DEFAULT NULL,
            verified BOOLEAN DEFAULT 1,
            reviewed_date DATE DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY recipe_idx (recipe_id)
        ) $charset_collate;";
        dbDelta($sql_nutrition);
        
        // Update version
        update_option('delice_eeat_db_version', self::DB_VERSION);
    }

    /**
     * Check if tables need updating
     */
    private function maybe_update_tables() {
        $installed_version = get_option('delice_eeat_db_version', '0');
        
        if (version_compare($installed_version, self::DB_VERSION, '<')) {
            $this->create_tables();
        }
    }

    /**
     * Add E-E-A-T menu items to admin.
     *
     * The four EEAT sub-pages (Hub, Author Profiles, Recipe Testing, User Submissions)
     * are now embedded as tabs within Community and Settings > SEO, so we no
     * longer register them as standalone submenu entries.
     */
    public function add_eeat_menu() {
        // Standalone EEAT menu items removed — content is now accessible via:
        //   Community > Authors       (Author Profiles)
        //   Community > Submissions   (User Submissions)
        //   Tools     > Test Recipes  (Recipe Testing)
        //   Settings  > SEO tab       (E-E-A-T display toggles)
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on E-E-A-T pages
        $eeat_pages = array(
            'delice-recipes_page_delice-eeat-hub',
            'delice-recipes_page_delice-author-profiles',
            'delice-recipes_page_delice-recipe-testing',
            'delice-recipes_page_delice-user-submissions',
        );
        
        if (!in_array($hook, $eeat_pages) && strpos($hook, 'delice') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'delice-eeat-admin',
            DELICE_RECIPE_PLUGIN_URL . 'admin/css/delice-eeat-admin.css',
            array(),
            DELICE_RECIPE_VERSION
        );
        
        // JavaScript
        wp_enqueue_media(); // For image uploads
        
        wp_enqueue_script(
            'delice-eeat-admin',
            DELICE_RECIPE_PLUGIN_URL . 'admin/js/delice-eeat-admin.js',
            array('jquery', 'wp-util'),
            DELICE_RECIPE_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('delice-eeat-admin', 'deliceEEAT', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('delice_eeat_nonce'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this?', 'delice-recipe-manager'),
                'confirmApprove' => __('Approve this submission?', 'delice-recipe-manager'),
                'confirmReject' => __('Reject this submission?', 'delice-recipe-manager'),
                'saving' => __('Saving...', 'delice-recipe-manager'),
                'saved' => __('Saved!', 'delice-recipe-manager'),
                'error' => __('Error occurred', 'delice-recipe-manager'),
            )
        ));
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only load on single recipe pages
        if (!is_singular(array('delice_recipe', 'post'))) {
            return;
        }
        
        // Check if this is actually a recipe post (not just any post)
        global $post;
        if ($post) {
            $is_recipe = ($post->post_type === 'delice_recipe') || 
                         get_post_meta($post->ID, '_delice_recipe_migrated', true) ||
                         get_post_meta($post->ID, '_delice_recipe_ingredients', true);
            
            if (!$is_recipe) {
                return;
            }
        }
        
        // CSS
        wp_enqueue_style(
            'delice-eeat-public',
            DELICE_RECIPE_PLUGIN_URL . 'public/css/components/delice-eeat.css',
            array(),
            DELICE_RECIPE_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'delice-eeat-public',
            DELICE_RECIPE_PLUGIN_URL . 'public/js/delice-eeat-public.js',
            array('jquery'),
            DELICE_RECIPE_VERSION,
            true
        );
        
        wp_localize_script('delice-eeat-public', 'deliceEEATPublic', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('delice_eeat_public_nonce'),
            'isLoggedIn' => is_user_logged_in(),
            'userId' => get_current_user_id(),
        ));
    }

    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        // Author profile actions
        add_action('wp_ajax_delice_save_author_profile', array($this, 'ajax_save_author_profile'));
        add_action('wp_ajax_delice_get_author_profile', array($this, 'ajax_get_author_profile'));
        
        // Recipe testing actions
        add_action('wp_ajax_delice_save_recipe_test', array($this, 'ajax_save_recipe_test'));
        add_action('wp_ajax_delice_approve_recipe_test', array($this, 'ajax_approve_recipe_test'));
        add_action('wp_ajax_delice_delete_recipe_test', array($this, 'ajax_delete_recipe_test'));
        
        // User cook submissions (public + logged in)
        add_action('wp_ajax_delice_submit_user_cook', array($this, 'ajax_submit_user_cook'));
        add_action('wp_ajax_nopriv_delice_submit_user_cook', array($this, 'ajax_submit_user_cook'));
        add_action('wp_ajax_delice_approve_user_cook', array($this, 'ajax_approve_user_cook'));
        add_action('wp_ajax_delice_delete_user_cook', array($this, 'ajax_delete_user_cook'));
        
        // Expert endorsement actions
        add_action('wp_ajax_delice_save_endorsement', array($this, 'ajax_save_endorsement'));
        add_action('wp_ajax_delice_delete_endorsement', array($this, 'ajax_delete_endorsement'));
        
        // Nutrition review actions
        add_action('wp_ajax_delice_save_nutrition_review', array($this, 'ajax_save_nutrition_review'));
        
        // Recipe history actions
        add_action('wp_ajax_delice_add_recipe_version', array($this, 'ajax_add_recipe_version'));
        
        // Statistics
        add_action('wp_ajax_delice_get_eeat_stats', array($this, 'ajax_get_eeat_stats'));
    }

    /**
     * Display E-E-A-T Hub page
     */
    public function display_eeat_hub() {
        include DELICE_RECIPE_PLUGIN_DIR . 'admin/partials/eeat/admin-eeat-hub.php';
    }

    /**
     * Display Author Profiles page
     */
    public function display_author_profiles() {
        include DELICE_RECIPE_PLUGIN_DIR . 'admin/partials/eeat/admin-author-profiles.php';
    }

    /**
     * Display Recipe Testing page
     */
    public function display_recipe_testing() {
        include DELICE_RECIPE_PLUGIN_DIR . 'admin/partials/eeat/admin-recipe-testing.php';
    }

    /**
     * Display User Submissions page
     */
    public function display_user_submissions() {
        include DELICE_RECIPE_PLUGIN_DIR . 'admin/partials/eeat/admin-user-submissions.php';
    }

    /**
     * AJAX: Save author profile
     */
    public function ajax_save_author_profile() {
        check_ajax_referer('delice_eeat_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'delice-recipe-manager')));
        }
        
        $profile_manager = new Delice_Author_Profile();
        $result = $profile_manager->save_profile($_POST);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Profile saved successfully', 'delice-recipe-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save profile', 'delice-recipe-manager')));
        }
    }

    /**
     * AJAX: Get author profile
     */
    public function ajax_get_author_profile() {
        check_ajax_referer('delice_eeat_nonce', 'nonce');
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Invalid user ID', 'delice-recipe-manager')));
        }
        
        $profile_manager = new Delice_Author_Profile();
        $profile = $profile_manager->get_profile($user_id);
        
        wp_send_json_success($profile);
    }

    /**
     * AJAX: Submit user cook (I Made This)
     */
    public function ajax_submit_user_cook() {
        check_ajax_referer('delice_eeat_public_nonce', 'nonce');
        
        $experience_manager = Delice_Recipe_Experience::get_instance();
        $result = $experience_manager->submit_user_cook($_POST);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Thank you! Your submission is pending approval.', 'delice-recipe-manager')
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to submit', 'delice-recipe-manager')));
        }
    }

    /**
     * AJAX: Get E-E-A-T statistics
     */
    public function ajax_get_eeat_stats() {
        check_ajax_referer('delice_eeat_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'delice-recipe-manager')));
        }
        
        global $wpdb;
        
        $stats = array(
            'total_tests' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}delice_recipe_testing WHERE verified = 1"),
            'total_cooks' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}delice_user_cooks WHERE approved = 1"),
            'total_profiles' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}delice_author_profiles"),
            'verified_profiles' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}delice_author_profiles WHERE verified = 1"),
            'pending_submissions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}delice_user_cooks WHERE approved = 0"),
            'total_endorsements' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}delice_expert_endorsements"),
        );
        
        wp_send_json_success($stats);
    }

    /**
     * Placeholder AJAX handlers (will be implemented by specific classes)
     */
    public function ajax_save_recipe_test() {
        Delice_Recipe_Experience::get_instance()->ajax_save_recipe_test();
    }
    
    public function ajax_approve_recipe_test() {
        Delice_Recipe_Experience::get_instance()->ajax_approve_recipe_test();
    }
    
    public function ajax_delete_recipe_test() {
        Delice_Recipe_Experience::get_instance()->ajax_delete_recipe_test();
    }
    
    public function ajax_approve_user_cook() {
        Delice_Recipe_Experience::get_instance()->ajax_approve_user_cook();
    }
    
    public function ajax_delete_user_cook() {
        Delice_Recipe_Experience::get_instance()->ajax_delete_user_cook();
    }
    
    public function ajax_save_endorsement() {
        Delice_Recipe_Authority::get_instance()->ajax_save_endorsement();
    }
    
    public function ajax_delete_endorsement() {
        Delice_Recipe_Authority::get_instance()->ajax_delete_endorsement();
    }
    
    public function ajax_save_nutrition_review() {
        Delice_Recipe_Expertise::get_instance()->ajax_save_nutrition_review();
    }
    
    public function ajax_add_recipe_version() {
        Delice_Recipe_Authority::get_instance()->ajax_add_recipe_version();
    }
}

// Initialize
Delice_Recipe_EEAT::get_instance();
