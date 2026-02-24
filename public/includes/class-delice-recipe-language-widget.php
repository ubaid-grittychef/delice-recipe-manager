<?php
/**
 * Language selector widget for frontend
 */
class Delice_Recipe_Language_Widget {

    public function __construct() {
        add_shortcode('delice_language_selector', array($this, 'render_language_selector'));
        add_action('wp_footer', array($this, 'add_language_selector_script'));
    }

    /**
     * Render language selector shortcode
     */
    public function render_language_selector($atts) {
        $atts = shortcode_atts(array(
            'style' => 'dropdown', // dropdown, buttons, flags
            'show_flags' => 'false',
            'class' => '',
        ), $atts);

        $enabled_languages = get_option('delice_recipe_enabled_languages', array('en_US'));
        $current_language = Delice_Recipe_Language::get_current_language();
        
        if (count($enabled_languages) <= 1) {
            return ''; // Don't show selector if only one language
        }

        $language_names = array(
            'en_US' => 'English',
            'fr_FR' => 'Français',
            'es_ES' => 'Español',
            'de_DE' => 'Deutsch',
            'it_IT' => 'Italiano',
            'pt_PT' => 'Português',
        );

        $output = '<div class="delice-language-selector ' . esc_attr($atts['class']) . '">';

        if ($atts['style'] === 'dropdown') {
            $output .= '<select id="delice-language-select" onchange="deliceChangeLanguage(this.value)">';
            foreach ($enabled_languages as $lang) {
                $selected = ($lang === $current_language) ? 'selected' : '';
                $name = isset($language_names[$lang]) ? $language_names[$lang] : $lang;
                $output .= '<option value="' . esc_attr($lang) . '" ' . $selected . '>' . esc_html($name) . '</option>';
            }
            $output .= '</select>';
        } else {
            foreach ($enabled_languages as $lang) {
                $active = ($lang === $current_language) ? 'active' : '';
                $name = isset($language_names[$lang]) ? $language_names[$lang] : $lang;
                $output .= '<button class="delice-lang-btn ' . $active . '" onclick="deliceChangeLanguage(\'' . esc_attr($lang) . '\')">';
                $output .= esc_html($name);
                $output .= '</button>';
            }
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Add JavaScript for language switching
     */
    public function add_language_selector_script() {
        ?>
        <script>
        function deliceChangeLanguage(lang) {
            var url = new URL(window.location);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        }
        </script>
        <style>
        .delice-language-selector {
            margin: 10px 0;
        }
        .delice-language-selector select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
        }
        .delice-lang-btn {
            margin: 0 5px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 4px;
        }
        .delice-lang-btn.active {
            background: #0073aa;
            color: white;
        }
        .delice-lang-btn:hover {
            background: #f0f0f0;
        }
        .delice-lang-btn.active:hover {
            background: #005a87;
        }
        </style>
        <?php
    }
}

// Initialize the widget
new Delice_Recipe_Language_Widget();