<?php
/**
 * Manages recipe taxonomies and automatic categorization
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Delice_Recipe_Taxonomy_Manager')) {
class Delice_Recipe_Taxonomy_Manager {

    /**
     * Major cuisine categories
     */
    private $major_cuisines = array(
        'italian' => 'Italian',
        'french' => 'French', 
        'asian' => 'Asian',
        'mexican' => 'Mexican',
        'american' => 'American',
        'mediterranean' => 'Mediterranean',
        'indian' => 'Indian',
        'chinese' => 'Chinese',
        'japanese' => 'Japanese',
        'thai' => 'Thai',
        'middle-eastern' => 'Middle Eastern',
        'greek' => 'Greek',
        'spanish' => 'Spanish',
        'german' => 'German',
        'british' => 'British'
    );

    /**
     * Course types
     */
    private $course_types = array(
        'appetizer' => 'Appetizer',
        'main-course' => 'Main Course', 
        'dessert' => 'Dessert',
        'side-dish' => 'Side Dish',
        'soup' => 'Soup',
        'salad' => 'Salad',
        'breakfast' => 'Breakfast',
        'snack' => 'Snack',
        'beverage' => 'Beverage'
    );

    /**
     * Dietary categories
     */
    private $dietary_types = array(
        'vegetarian' => 'Vegetarian',
        'vegan' => 'Vegan',
        'gluten-free' => 'Gluten-Free',
        'dairy-free' => 'Dairy-Free',
        'low-carb' => 'Low-Carb',
        'keto' => 'Keto',
        'paleo' => 'Paleo',
        'healthy' => 'Healthy'
    );

    /**
     * Cuisine detection patterns
     */
    private $cuisine_patterns = array(
        'italian' => array('pasta', 'pizza', 'risotto', 'carbonara', 'bolognese', 'parmigiana', 'bruschetta', 'tiramisu', 'gelato'),
        'french' => array('croissant', 'baguette', 'ratatouille', 'coq au vin', 'bouillabaisse', 'crème brûlée', 'quiche', 'cassoulet'),
        'asian' => array('stir fry', 'noodles', 'curry', 'sushi', 'tempura', 'pad thai', 'kimchi', 'ramen'),
        'mexican' => array('tacos', 'burritos', 'quesadilla', 'guacamole', 'salsa', 'enchiladas', 'nachos', 'churros'),
        'american' => array('burger', 'bbq', 'mac and cheese', 'pancakes', 'wings', 'chili', 'cornbread'),
        'mediterranean' => array('hummus', 'falafel', 'tzatziki', 'olive', 'feta', 'pita', 'dolma'),
        'indian' => array('curry', 'tandoori', 'biryani', 'naan', 'samosa', 'dal', 'masala', 'chapati'),
        'chinese' => array('fried rice', 'dumpling', 'spring roll', 'sweet and sour', 'kung pao', 'chow mein'),
        'japanese' => array('sushi', 'sashimi', 'tempura', 'ramen', 'miso', 'teriyaki', 'udon', 'sake'),
        'thai' => array('pad thai', 'tom yum', 'green curry', 'massaman', 'som tam', 'mango sticky rice')
    );

    /**
     * Course detection patterns
     */
    private $course_patterns = array(
        'appetizer' => array('appetizer', 'starter', 'canapé', 'bruschetta', 'dip', 'spread'),
        'main-course' => array('main', 'entree', 'roast', 'grilled', 'braised', 'steak', 'chicken'),
        'dessert' => array('dessert', 'cake', 'pie', 'ice cream', 'pudding', 'cookie', 'chocolate'),
        'soup' => array('soup', 'broth', 'bisque', 'chowder', 'gazpacho', 'minestrone'),
        'salad' => array('salad', 'slaw', 'caesar', 'waldorf', 'caprese'),
        'breakfast' => array('breakfast', 'pancake', 'waffle', 'omelet', 'cereal', 'toast'),
        'side-dish' => array('side', 'vegetables', 'rice', 'potato', 'beans'),
        'snack' => array('snack', 'chips', 'nuts', 'trail mix', 'popcorn'),
        'beverage' => array('drink', 'smoothie', 'juice', 'coffee', 'tea', 'cocktail')
    );

    public function __construct() {
        add_action('init', array($this, 'create_default_terms'));
        add_action('save_post_delice_recipe', array($this, 'auto_assign_taxonomies'), 20, 2);
    }

    /**
     * Create default taxonomy terms
     */
    public function create_default_terms() {
        // Create default cuisines
        foreach ($this->major_cuisines as $slug => $name) {
            if (!term_exists($slug, 'delice_cuisine')) {
                wp_insert_term($name, 'delice_cuisine', array('slug' => $slug));
            }
        }

        // Create default course types
        foreach ($this->course_types as $slug => $name) {
            if (!term_exists($slug, 'delice_course')) {
                wp_insert_term($name, 'delice_course', array('slug' => $slug));
            }
        }

        // Create default dietary types
        foreach ($this->dietary_types as $slug => $name) {
            if (!term_exists($slug, 'delice_dietary')) {
                wp_insert_term($name, 'delice_dietary', array('slug' => $slug));
            }
        }
    }

    /**
     * Automatically assign taxonomies based on recipe content
     */
    public function auto_assign_taxonomies($post_id, $post) {
        // Skip if this is an autosave or revision
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        $title = strtolower($post->post_title);
        $content = strtolower($post->post_content);
        $ingredients = get_post_meta($post_id, '_delice_recipe_ingredients', true);
        
        // Combine all text for analysis
        $text_to_analyze = $title . ' ' . $content;
        if (is_array($ingredients)) {
            foreach ($ingredients as $ingredient) {
                if (isset($ingredient['name'])) {
                    $text_to_analyze .= ' ' . strtolower($ingredient['name']);
                }
            }
        }

        // Auto-assign cuisine
        $detected_cuisine = $this->detect_cuisine($text_to_analyze);
        if ($detected_cuisine && !wp_get_object_terms($post_id, 'delice_cuisine')) {
            wp_set_object_terms($post_id, $detected_cuisine, 'delice_cuisine');
        }

        // Auto-assign course
        $detected_course = $this->detect_course($text_to_analyze);
        if ($detected_course && !wp_get_object_terms($post_id, 'delice_course')) {
            wp_set_object_terms($post_id, $detected_course, 'delice_course');
        }

        // Auto-assign dietary restrictions
        $detected_dietary = $this->detect_dietary($text_to_analyze);
        if (!empty($detected_dietary) && !wp_get_object_terms($post_id, 'delice_dietary')) {
            wp_set_object_terms($post_id, $detected_dietary, 'delice_dietary');
        }

        // Auto-assign keywords from ingredients
        $keywords = $this->extract_keywords($text_to_analyze, $ingredients);
        if (!empty($keywords) && !wp_get_object_terms($post_id, 'delice_keyword')) {
            wp_set_object_terms($post_id, $keywords, 'delice_keyword');
        }
    }

    /**
     * Detect cuisine from text
     */
    private function detect_cuisine($text) {
        foreach ($this->cuisine_patterns as $cuisine => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos($text, $pattern) !== false) {
                    return $cuisine;
                }
            }
        }
        return null;
    }

    /**
     * Detect course from text
     */
    private function detect_course($text) {
        foreach ($this->course_patterns as $course => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos($text, $pattern) !== false) {
                    return $course;
                }
            }
        }
        return 'main-course'; // Default fallback
    }

    /**
     * Detect dietary restrictions
     */
    private function detect_dietary($text) {
        $dietary = array();
        
        // Check for vegetarian/vegan indicators
        if (strpos($text, 'meat') === false && strpos($text, 'chicken') === false && strpos($text, 'beef') === false) {
            if (strpos($text, 'cheese') === false && strpos($text, 'milk') === false && strpos($text, 'butter') === false) {
                $dietary[] = 'vegan';
            } else {
                $dietary[] = 'vegetarian';
            }
        }

        // Check for gluten-free
        if (strpos($text, 'flour') === false && strpos($text, 'bread') === false && strpos($text, 'pasta') === false) {
            $dietary[] = 'gluten-free';
        }

        return $dietary;
    }

    /**
     * Extract keywords from ingredients and title
     */
    private function extract_keywords($text, $ingredients) {
        $keywords = array();
        
        // Extract main ingredients as keywords
        if (is_array($ingredients)) {
            foreach ($ingredients as $ingredient) {
                if (isset($ingredient['name']) && !empty($ingredient['name'])) {
                    $name = trim(strtolower($ingredient['name']));
                    // Only add significant ingredients (more than 3 characters)
                    if (strlen($name) > 3) {
                        $keywords[] = $name;
                    }
                }
            }
        }

        // Limit to 10 keywords to avoid clutter
        return array_slice(array_unique($keywords), 0, 10);
    }

    /**
     * Get available taxonomies for admin interface
     */
    public function get_available_taxonomies() {
        return array(
            'cuisines' => $this->major_cuisines,
            'courses' => $this->course_types,
            'dietary' => $this->dietary_types
        );
    }
}
}
