
<?php
/**
 * Handles loading of CSS assets
 */
class Delice_Recipe_Styles extends Delice_Recipe_Asset_Loader {
    /**
     * Register and enqueue public CSS files
     */
    public function enqueue() {
        // First register the main CSS file with all necessary styles
        wp_register_style(
            'delice-recipe-public',
            $this->get_plugin_url() . 'public/css/delice-recipe-public.css',
            array(),
            $this->get_version()
        );
        
        // Then register the component CSS files
        $css_files = array(
            'base/recipe-base.css',
            'base/recipe-responsive.css',
            'components/recipe-header.css',
            'components/recipe-panels.css',
            'components/recipe-nutrition.css',
            'components/recipe-advanced-features.css',
            'components/recipe-footer.css',
            'components/recipe-action-buttons.css'
        );
    
        foreach ($css_files as $file) {
            wp_register_style(
                'delice-recipe-' . str_replace('/', '-', basename($file, '.css')),
                $this->get_plugin_url() . 'public/css/' . $file,
                array('delice-recipe-public'),
                $this->get_version()
            );
        }
        
        // Now enqueue all styles - FORCE LOAD to ensure they're always available
        wp_enqueue_style('delice-recipe-public');
        foreach ($css_files as $file) {
            wp_enqueue_style('delice-recipe-' . str_replace('/', '-', basename($file, '.css')));
        }
        
        // Add debug information for admins
        if (current_user_can('manage_options') && isset($_GET['delice_debug'])) {
            echo '<!-- Délice Recipe CSS files loaded -->';
            echo '<!-- Main CSS: ' . $this->get_plugin_url() . 'public/css/delice-recipe-public.css' . ' -->';
        }
    }
}
