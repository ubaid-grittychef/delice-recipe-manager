
<?php
/**
 * Public asset loader for CSS and JS
 */

if (!defined('ABSPATH')) {
    exit;
}

class Delice_Recipe_Assets extends Delice_Recipe_Asset_Loader {
    /**
     * Enqueue stylesheets
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'delice-recipe-public',
            $this->get_plugin_url() . 'public/css/delice-recipe-public.css',
            array(),
            $this->get_version()
        );
        
        // Add FAQ styles
        wp_add_inline_style('delice-recipe-public', $this->get_faq_css());
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'delice-recipe-public',
            $this->get_plugin_url() . 'public/js/delice-recipe-public.js',
            array('jquery'),
            $this->get_version(),
            true
        );
    }
    
    /**
     * Add critical CSS inline in the head
     */
    public function inline_critical_css() {
        $critical_css = "
            .delice-recipe-card {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
                max-width: 100%;
                margin: 2em auto;
                padding: 1.5em;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                background-color: #fff;
            }
            .delice-recipe-title {
                font-size: 1.8em;
                margin-top: 0;
            }
            [x-cloak] { display: none !important; }
        ";
        
        echo '<style id="delice-recipe-critical-css">' . $critical_css . '</style>';
    }
    
    /**
     * Get FAQ styles
     * @return string CSS for FAQ styling
     * 
     * Changed from private to protected to match parent class declaration
     */
    protected function get_faq_css() {
        return "
            .delice-recipe-faqs {
                margin-top: 2em;
                padding: 1.5em;
                background-color: #f9f9f9;
                border-radius: 8px;
            }
            .delice-recipe-faqs-title {
                font-size: 1.6em;
                margin-bottom: 1em;
                border-bottom: 2px solid #eaeaea;
                padding-bottom: 0.5em;
            }
            .delice-recipe-faq-item {
                margin-bottom: 1em;
                border-bottom: 1px solid #eee;
                padding-bottom: 1em;
            }
            .delice-recipe-faq-question {
                font-size: 1.2em;
                margin-bottom: 0.5em;
                font-weight: 600;
                cursor: pointer;
                display: flex;
                justify-content: space-between;
            }
            .delice-recipe-faq-toggle {
                font-size: 1.2em;
                line-height: 1;
                font-weight: bold;
            }
            .delice-recipe-faq-answer {
                padding: 0.5em 0;
            }
            
            /* SEPARATE FAQ SECTION STYLES */
            .delice-recipe-faq-section {
                margin-top: 3em;
                padding: 2em 0;
                background-color: #f8f9fa;
                border-top: 2px solid #e9ecef;
            }
            .delice-recipe-faq-section .delice-recipe-modern-content {
                max-width: 800px;
                margin: 0 auto;
                padding: 0 1.5em;
            }
            .delice-recipe-faq-section .delice-recipe-modern-section-title {
                font-size: 1.8em;
                margin-bottom: 1.5em;
                color: #2c3e50;
                border-bottom: 3px solid #3498db;
                padding-bottom: 0.5em;
            }
            
            /* Elegant template specific styles */
            .delice-recipe-elegant-faqs {
                margin-top: 2em;
                padding: 1.5em;
                background-color: #f8f8f8;
                border-radius: 8px;
            }
            .delice-recipe-elegant-faq-item {
                margin-bottom: 1.5em;
                border-left: 3px solid #ddd;
                padding-left: 1em;
            }
            .delice-recipe-elegant-faq-question {
                font-size: 1.2em;
                color: #333;
                margin-bottom: 0.5em;
            }
            .delice-recipe-elegant-faq-answer {
                font-size: 1em;
                color: #555;
            }
            
            /* Modern template specific styles */
            .delice-recipe-modern-faqs {
                margin-top: 2em;
                padding: 1.5em;
                background-color: #ffffff;
                border-radius: 12px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.08);
                border: 1px solid #e1e8ed;
            }
            .delice-recipe-modern-faqs-list {
                margin-top: 1.5em;
            }
            .delice-recipe-modern-faq-item {
                margin-bottom: 8px;
                border: 1px solid #e8e8e8;
                border-radius: 8px;
                overflow: hidden;
                background: white;
            }
            .delice-recipe-modern-faq-question {
                width: 100%;
                background: transparent;
                font-size: 1em;
                color: #1a1a1a;
                font-weight: 600;
                cursor: pointer;
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 14px 18px;
                border: none;
                text-align: left;
                gap: 12px;
            }
            .delice-recipe-modern-faq-toggle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                min-width: 24px;
                width: 24px;
                height: 24px;
                font-size: 18px;
                line-height: 1;
                color: #FF6B35;
                transition: transform 0.25s ease;
            }
            .faq-open .delice-recipe-modern-faq-toggle {
                transform: rotate(45deg);
            }
            .delice-recipe-modern-faq-answer {
                color: #555;
                font-size: 0.9375em;
                line-height: 1.7;
                background-color: #fafafa;
                border-top: 1px solid #e8e8e8;
            }
            
            /* Responsive design for FAQ section */
            @media (max-width: 768px) {
                .delice-recipe-faq-section {
                    margin-top: 2em;
                    padding: 1.5em 0;
                }
                .delice-recipe-faq-section .delice-recipe-modern-content {
                    padding: 0 1em;
                }
                .delice-recipe-modern-faq-question {
                    font-size: 1.1em;
                }
                .delice-recipe-modern-faq-answer {
                    font-size: 0.95em;
                    padding: 0.8em 1em;
                }
            }
        ";
    }
    
    /**
     * Implement abstract method from parent class
     */
    public function enqueue() {
        $this->enqueue_styles();
        $this->enqueue_scripts();
    }
}
