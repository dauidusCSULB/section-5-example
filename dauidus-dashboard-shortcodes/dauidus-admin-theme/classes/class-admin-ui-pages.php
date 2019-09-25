<?php
class Kodeo_Admin_UI_Pages {
    private $default_args = array(
        'menu-title' => '',
		'tab-title' => '',
		'parent' => '',
		'has-tab' => true,
		'tab-side' => false,
		'network' => false
    );
    private $pages = false;

    private $optionsInstance;

    function __construct($optionsInstance) {
        $this->optionsInstance = $optionsInstance;

        // Setting Pages must be in sync with Kodeo_Admin_UI_Options::slugs
        $this->pages = array(
            'kodeo-admin-ui-options-general' => array_merge(
                $this->default_args, array(
                    'slug' => 'kodeo-admin-ui-options-general',
                    'tab' => 'general',
                    'parent' => 'options-general.php',
                    'menu-title' => _x( 'Admin UI', 'Page title in the menu', 'kodeo-admin-ui' ),
                    'tab-title' => _x( 'Theme Options', 'Option tab title', 'kodeo-admin-ui' ),
                    'title' => _x( 'Kodeo Admin UI', 'Option page title', 'kodeo-admin-ui' ),
                    'callback' => array($this, 'display_general_options_page' ),
                )
            ),
            'kodeo-admin-ui-options-customization' => array_merge(
                $this->default_args, array(
                    'slug' => 'kodeo-admin-ui-options-customization',
                    'tab' => 'customization',
                    'tab-title' => _x( 'Customization', 'Option tab title', 'kodeo-admin-ui' ),
                    'title' => _x( 'Kodeo Admin UI', 'Option page title', 'kodeo-admin-ui' ),
                    'callback' => array($this, 'display_general_options_page' ),
                )
            )
        );

        global $pagenow;
        if($pagenow === "options-general.php" && isset($_GET['tab']) && $_GET['tab'] === "customization") add_action('admin_enqueue_scripts', 'wp_enqueue_media');
    }

    public function get_page($slug) {
        if( !isset($this->pages[$slug]) )
            return null;
        else
            return $this->pages[$slug];
    }

    public function get_all_pages() {
        return $this->pages;
    }

    public function display_general_options_page() {
        $default_slug = 'kodeo-admin-ui-options-general';
        $tabs = $this->pages;
        $tab = (!isset($_GET['tab'])?'general':$_GET['tab']);
        $slug = 'kodeo-admin-ui-options-'.$tab;
        $page_info = $this->get_page( $slug );
        $sections = $this->optionsInstance->get_sections();
        $setting_pages = $this->optionsInstance->slugs;
		include( PLUGIN_INC_PATH . 'templates/page-options-general.php' );
    }
}
