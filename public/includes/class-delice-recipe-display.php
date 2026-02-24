
<?php
/**
 * Handles recipe display in posts and pages - Updated with template selection
 */
class Delice_Recipe_Display {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Constructor logic if needed
    }
    
    /**
     * Display recipe content in posts
     */
    public function display_recipe_content($content) {
        
        if (!is_singular('delice_recipe') && !is_singular('post')) {
            return $content;
        }
        
        global $post;
        if (!$post) {
            return $content;
        }
        
        // Check if post has recipe data
        $has_ingredients = get_post_meta($post->ID, '_delice_recipe_ingredients', true);
        $has_instructions = get_post_meta($post->ID, '_delice_recipe_instructions', true);
        
        if (empty($has_ingredients) && empty($has_instructions)) {
            return $content;
        }
        
        // Load template system and render recipe using selected template
        $templates = new Delice_Recipe_Templates();
        $recipe_html = $templates->load_template(null, $post->ID); // null = use selected template
        
        return $content . $recipe_html;
    }
}
