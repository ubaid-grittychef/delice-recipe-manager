
<?php
/**
 * Abstract base class for asset loading
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class Delice_Recipe_Asset_Loader {
    /**
     * Get the plugin URL
     */
    protected function get_plugin_url() {
        return DELICE_RECIPE_PLUGIN_URL;
    }

    /**
     * Get the plugin version
     */
    protected function get_version() {
        return DELICE_RECIPE_VERSION;
    }

    /**
     * Abstract method for enqueueing assets
     */
    abstract public function enqueue();
    
    /**
     * Get FAQ CSS styles
     */
    protected function get_faq_css() {
        return "
        .delice-recipe-faqs {
            margin: 2em 0;
        }
        .delice-recipe-faqs-title {
            font-size: 1.5em;
            margin-bottom: 1em;
            color: #333;
        }
        .delice-recipe-faq-item {
            margin-bottom: 1em;
            border-bottom: 1px solid #eee;
            padding-bottom: 1em;
        }
        .delice-recipe-faq-question {
            font-size: 1.2em;
            margin: 0 0 0.5em;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #444;
        }
        .delice-recipe-faq-toggle {
            font-size: 1.2em;
            color: #888;
        }
        .delice-recipe-faq-answer {
            padding: 0.5em 0 0.5em 1em;
            border-left: 3px solid #f8f8f8;
        }
        
        /* Elegant template styles */
        .delice-recipe-elegant-faqs {
            margin-top: 2em;
        }
        .delice-recipe-elegant-faq-item {
            background-color: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 1em;
            overflow: hidden;
        }
        .delice-recipe-elegant-faq-question {
            background-color: #f3f3f3;
            padding: 1em;
            margin: 0;
            font-size: 1.1em;
            color: #333;
        }
        .delice-recipe-elegant-faq-answer {
            padding: 1em;
        }
        
        /* Print styles */
        @media print {
            .delice-recipe-faq-answer {
                display: block !important;
            }
        }
        ";
    }

    /**
     * Check if file exists before enqueueing
     */
    protected function enqueue_if_exists($handle, $src, $deps = array(), $type = 'style') {
        $file_path = str_replace(DELICE_RECIPE_PLUGIN_URL, DELICE_RECIPE_PLUGIN_DIR, $src);
        
        if (file_exists($file_path)) {
            if ($type === 'style') {
                wp_enqueue_style($handle, $src, $deps, $this->get_version());
            } else {
                wp_enqueue_script($handle, $src, $deps, $this->get_version(), true);
            }
            return true;
        }
        
        error_log("Delice Recipe: Asset file not found: $file_path");
        return false;
    }
}
