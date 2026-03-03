<?php
/**
 * Register all actions, filters, and shortcodes for the plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Delice_Recipe_Loader')) {
class Delice_Recipe_Loader {

    /**
     * @var array
     */
    protected $actions;

    /**
     * @var array
     */
    protected $filters;

    /**
     * @var array
     */
    protected $shortcodes;

    /**
     * Initialize the collections used to maintain the actions, filters, and shortcodes
     */
    public function __construct() {
        $this->actions    = [];
        $this->filters    = [];
        $this->shortcodes = [];
    }

    /**
     * Add a new action to the collection to be registered with WordPress
     */
    public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->actions[] = compact( 'hook', 'component', 'callback', 'priority', 'accepted_args' );
    }

    /**
     * Add a new filter to the collection to be registered with WordPress
     */
    public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->filters[] = compact( 'hook', 'component', 'callback', 'priority', 'accepted_args' );
    }

    /**
     * Add a new shortcode to the collection to be registered with WordPress
     */
    public function add_shortcode( $tag, $component, $callback ) {
        $this->shortcodes[] = compact( 'tag', 'component', 'callback' );
    }

    /**
     * Register the filters, shortcodes, and actions with WordPress
     */
    public function run() {
        // Filters
        foreach ( $this->filters as $hook ) {
            add_filter(
                $hook['hook'],
                [ $hook['component'], $hook['callback'] ],
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        // Shortcodes
        foreach ( $this->shortcodes as $sc ) {
            add_shortcode(
                $sc['tag'],
                [ $sc['component'], $sc['callback'] ]
            );
        }

        // Actions
        foreach ( $this->actions as $hook ) {
            add_action(
                $hook['hook'],
                [ $hook['component'], $hook['callback'] ],
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}
}
