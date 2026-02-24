<?php
/**
 * Recipe post type registration - Fixed constructor
 */

if (!class_exists('Delice_Recipe_Post_Type')) {
class Delice_Recipe_Post_Type {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Constructor logic if needed
    }
    
    /**
     * Register recipe post type and taxonomies
     */
    public function register_post_type() {
        
        $labels = array(
            'name' => _x('Recipes', 'Post Type General Name', 'delice-recipe-manager'),
            'singular_name' => _x('Recipe', 'Post Type Singular Name', 'delice-recipe-manager'),
            'menu_name' => __('Recipes', 'delice-recipe-manager'),
            'add_new_item' => __('Add New Recipe', 'delice-recipe-manager'),
            'edit_item' => __('Edit Recipe', 'delice-recipe-manager'),
        );
        
        $args = array(
            'label' => __('Recipe', 'delice-recipe-manager'),
            'labels' => $labels,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-carrot',
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => 'post',
            'show_in_rest' => true,
            'rewrite' => array(
                'slug' => 'recipe',
                'with_front' => false,
                'feeds' => true,
                'pages' => true,
            ),
            'taxonomies' => array('delice_cuisine', 'delice_course', 'delice_dietary', 'delice_keyword'),
        );
        
        register_post_type('delice_recipe', $args);
        
        // Register taxonomies
        $this->register_taxonomies();
    }
    
    /**
     * Register recipe taxonomies
     */
    public function register_taxonomies() {
        
        // Register Cuisine taxonomy
        register_taxonomy('delice_cuisine', 'delice_recipe', array(
            'labels' => array(
                'name' => __('Cuisines', 'delice-recipe-manager'),
                'singular_name' => __('Cuisine', 'delice-recipe-manager'),
                'menu_name' => __('Cuisines', 'delice-recipe-manager'),
                'add_new_item' => __('Add New Cuisine', 'delice-recipe-manager'),
                'edit_item' => __('Edit Cuisine', 'delice-recipe-manager'),
            ),
            'public' => true,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'cuisine'),
        ));
        
        // Register Course taxonomy
        register_taxonomy('delice_course', 'delice_recipe', array(
            'labels' => array(
                'name' => __('Courses', 'delice-recipe-manager'),
                'singular_name' => __('Course', 'delice-recipe-manager'),
                'menu_name' => __('Courses', 'delice-recipe-manager'),
                'add_new_item' => __('Add New Course', 'delice-recipe-manager'),
                'edit_item' => __('Edit Course', 'delice-recipe-manager'),
            ),
            'public' => true,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'course'),
        ));
        
        // Register Dietary taxonomy
        register_taxonomy('delice_dietary', 'delice_recipe', array(
            'labels' => array(
                'name' => __('Dietary', 'delice-recipe-manager'),
                'singular_name' => __('Dietary Restriction', 'delice-recipe-manager'),
                'menu_name' => __('Dietary', 'delice-recipe-manager'),
                'add_new_item' => __('Add New Dietary Restriction', 'delice-recipe-manager'),
                'edit_item' => __('Edit Dietary Restriction', 'delice-recipe-manager'),
            ),
            'public' => true,
            'hierarchical' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'dietary'),
        ));
        
        // Register Keywords taxonomy
        register_taxonomy('delice_keyword', 'delice_recipe', array(
            'labels' => array(
                'name' => __('Recipe Keywords', 'delice-recipe-manager'),
                'singular_name' => __('Keyword', 'delice-recipe-manager'),
                'menu_name' => __('Keywords', 'delice-recipe-manager'),
                'add_new_item' => __('Add New Keyword', 'delice-recipe-manager'),
                'edit_item' => __('Edit Keyword', 'delice-recipe-manager'),
            ),
            'public' => true,
            'hierarchical' => false,
            'show_ui' => true,
            'show_admin_column' => false,
            'show_in_nav_menus' => false,
            'show_tagcloud' => false,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'keyword'),
        ));
    }
}
}
