<?php
class Kodeo_Admin_UI {
	// The loader that's responsible for maintaining and registering all hooks that power the plugin.
	private $loader;

	// The unique identifier of this plugin.
	private $plugin_name;

	// The current version of the plugin.
	private $version;

    // Options Class Instance
    private $optionsInstance;

    // Other Instances
    private $instances;

    // Textdomain
    private $textdomain;

	// Define the core functionality of the plugin.
	public function __construct($plugin_name, $plugin_version) {
        if(!isset($plugin_name) || !isset($plugin_version)) wp_die("Cannot Initiate Kodeo Admin UI");

		$this->plugin_name = $plugin_name;
		$this->version = $plugin_version;
        $this->instances = array();
        $this->textdomain = 'kodeo-admin-ui';
	}

    private $error;
    function error_notice() {
        ?><div class="notice notice-error">
            <p><?=sprintf(__( 'Kodeo Admin UI requires %s %s or higher.', 'kodeo-admin-ui' ), $this->error[0], $this->error[1])?></p>
        </div><?php
    }

    //PHP and WP Version Check
    function version_check() {
        if(defined('PLUGIN_MIN_WP_VER')) {
            if( version_compare( $GLOBALS['wp_version'], PLUGIN_MIN_WP_VER, '<' ) ) {
                $this->error = array('WordPress', PLUGIN_MIN_WP_VER);
                add_action('admin_notices', array($this, 'error_notice'));
                return false;
            }
        }
        if(defined('PLUGIN_MIN_PHP_VER')) {
            if( version_compare( phpversion(), PLUGIN_MIN_PHP_VER, '<' ) ) {
                $this->error = array('PHP', PLUGIN_MIN_PHP_VER);
                add_action('admin_notices', array($this, 'error_notice'));
                return false;
            }
        }
        return true;
    }

	// Load the required dependencies for this plugin.
	private function load_dependencies() {
        if(!defined('PLUGIN_INC_PATH')) exit;

		require PLUGIN_INC_PATH . 'classes/class-admin-ui-loader.php';
        require PLUGIN_INC_PATH . 'classes/class-admin-ui-options.php';
        require PLUGIN_INC_PATH . 'classes/class-admin-ui-core.php';
        require PLUGIN_INC_PATH . 'classes/class-admin-ui-pages.php';
		require PLUGIN_INC_PATH . 'classes/class-admin-ui-menu.php';
        require PLUGIN_INC_PATH . 'classes/class-admin-ui-toolbar.php';

		$this->loader = new Kodeo_Admin_UI_Loader();
	}

	// Register all of the hooks related to the admin area functionality
	private function define_hooks() {
        $options = new Kodeo_Admin_UI_Options();
        $core = new Kodeo_Admin_UI_Core($options);
        $pages = new Kodeo_Admin_UI_Pages($options);
       	$menu = new Kodeo_Admin_UI_Menu($pages);
        $toolbar = new Kodeo_Admin_UI_Toolbar();
        $this->optionsInstance = $options;

        $this->loader->add_action( 'admin_enqueue_scripts', $this, 'action_enqueue_scripts', 1 );
        if( $options->get_saved_option('enable-admin-theme') ) {
            // this function also removes a wp action
            if(is_admin()) {
                $this->loader->add_action( 'admin_enqueue_scripts', $this, 'action_enqueue_styles' );
            } else {
                $this->loader->add_action( 'wp_head', $this, 'action_print_frontend_styles' );
                $this->loader->add_action( 'wp_enqueue_scripts', $this, 'action_enqueue_front_styles' );
                $this->loader->add_action( 'login_enqueue_scripts', $this, 'action_enqueue_login_styles' );
            }
        }
        if(is_admin()) {
            $this->loader->add_filter( 'admin_body_class', $this, 'filter_add_admin_body_classes' );
            $this->loader->add_action( 'admin_head', $this, 'action_apply_custom_css', 999 );
        } else {
            $this->loader->add_filter( 'body_class', $this, 'filter_add_body_classes' );
            $this->loader->add_action( 'login_head', $this, 'action_apply_custom_login_logo' );
            $this->loader->add_action( 'login_head', $this, 'action_apply_custom_login_css', 999 );
        }

        if(get_locale() === 'de_DE_formal') $this->loader->add_action( 'override_load_textdomain', $this, 'load_fallback_textdomain', 10, 3 );

        // KAU-0.3.11: temporarily disabled
		// $this->loader->add_filter( 'init', $this, 'filter_add_wp_admin_protection' );

        $core->configure_updates_and_notifications();
        $this->loader->add_filter( 'login_headerurl', $core, 'filter_custom_loginlogo_url' );
        $this->loader->add_action( 'admin_init', $core, 'remove_dashboard_meta' );
        $this->loader->add_action( 'override_load_textdomain', $core, 'overwrite_textdomain', 10, 3 );
        if( $options->get_saved_option('deactivate-postboxes-sortable') ) {
            $this->loader->add_action( 'admin_print_footer_scripts', $core, 'admin_footer_scripts_deactivate_postboxes_sortable', 900 );
        }
        if( $options->get_saved_option('editor-past-plaintext') ) {
            add_filter( 'tiny_mce_before_init', array( $core, 'force_paste_as_plain_text' ) );
            add_filter( 'teeny_mce_before_init', array( $core, 'force_paste_as_plain_text' ) );
            add_filter( 'teeny_mce_plugins', array( $core, 'load_paste_in_teeny' ) );
            add_filter( 'mce_buttons_2', array( $core, 'remove_paste_as_plain_text_button' ) );
        }
		if( $options->get_saved_option('disable-autosave') ) {
            $this->loader->add_action( 'wp_print_scripts', $core, 'disable_autosave' );
        }
        $this->loader->add_action( 'widgets_init', $core, 'remove_disabled_widgets' );

        $this->loader->add_action( 'admin_init', $options, 'action_register_settings' );

        $this->loader->add_action( 'admin_menu', $menu, 'action_add_menu_entries', 500 );
        if( $options->get_saved_option('enable-admin-theme') ) {
            $this->loader->add_action( 'admin_menu', $menu, 'action_add_counters', 501 );
        }

        if ( is_admin() ) {
            if( $options->get_saved_option('show-toolbar-notifications') && $options->get_saved_option('enable-admin-theme') )
                $this->loader->add_action( 'admin_bar_menu', $toolbar, 'action_add_notification_center', 90 );

            if( $options->get_saved_option('show-additional-editor-toolbar') )
                $this->loader->add_action( 'edit_form_after_title', $toolbar, 'action_add_sticky_editor_toolbar' );
        }
        $this->loader->add_action( 'admin_bar_menu', $toolbar, 'action_add_toolbar_nodes', 0 );
        $this->loader->add_action( 'admin_bar_menu', $toolbar, 'action_move_updates_node', 80 );
        $this->loader->add_action( 'admin_bar_menu', $toolbar, 'action_remove_toolbar_nodes', 999 );
        $this->loader->add_action( 'admin_bar_menu', $toolbar, 'action_pretify_user' );

        if( $options->get_saved_option('feature-post-order') ) {
            require PLUGIN_INC_PATH . 'classes/class-admin-ui-post-sorting.php';
            $this->instances['post-sorting'] = new Kodeo_Admin_UI_Post_Sorting();
        }

        $this->loader->add_action( 'init', $this, 'load_textdomain' );
	}

	function action_enqueue_styles() {
        wp_enqueue_style('thickbox');
        global $pagenow;
        if($pagenow === 'customize.php') {
            wp_enqueue_style( 'admin-ui', PLUGIN_URL.'assets/css/customizer.css', array(), $this->version, 'all' );
        } else {
            wp_enqueue_style( 'admin-ui', PLUGIN_URL.'assets/css/admin.css', array(), $this->version, 'all' );
        }
		if(!$this->optionsInstance->get_saved_option('system-font') ) {
			wp_enqueue_style( 'kodeo-admin-ui-font', PLUGIN_URL.'assets/css/font.css', array(), $this->version, 'all' );
		};
	}
    function action_enqueue_scripts() {
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script( 'kodeo-admin-ui-js', PLUGIN_URL.'assets/js/kodeo-admin-ui.js', array( 'jquery', 'jquery-ui-sortable', 'thickbox' ), $this->version );
		wp_localize_script( 'kodeo-admin-ui-js', 'l10n',
			array(
				'screenOptions' => __( 'Screen Options', 'kodeo-admin-ui' ),
				'help' => __( 'Help', 'kodeo-admin-ui' ),
                'searchPlaceholder' => __( 'Search', 'kodeo-admin-ui' ),
			)
		);
    }
    function action_apply_custom_css() {
        $css = $this->optionsInstance->get_saved_option('custom-css');
        if(!empty($css)) echo '<style>'.PHP_EOL.str_replace("<", "&lt;", $css).PHP_EOL.'</style>';
    }
    function action_apply_custom_login_css() {
        $css = $this->optionsInstance->get_saved_option('custom-login-css');
        if(!empty($css)) echo '<style>'.PHP_EOL.str_replace("<", "&lt;", $css).PHP_EOL.'</style>';
    }
    function action_apply_custom_login_logo() {
        $logo = $this->optionsInstance->get_saved_option('custom-login-logo');
        if(!empty($logo)) {
            list($url, $w, $h) = wp_get_attachment_image_src($logo, 'medium');

    		?>
    		<style>
    		#login h1 a {
    			background-image: url('<?=$url?>');
    			width: <?=$w?>px;
    			height: <?=$h?>px;
                max-width: 300px;
                max-height: 300px;
    		}
    		</style>
    		<?php
        }
    }
    function action_enqueue_front_styles() {
        wp_enqueue_style('kodeo-admin-ui', PLUGIN_URL.'assets/css/frontend.css', array(), $this->version, 'all' );
		if(!$this->optionsInstance->get_saved_option('system-font') ) {
			wp_enqueue_style( 'kodeo-admin-ui-font', PLUGIN_URL.'assets/css/font.css', array(), $this->version, 'all' );
		};
        remove_action( 'wp_head', '_admin_bar_bump_cb' );
    }
    function action_enqueue_login_styles() {
        wp_enqueue_style( 'kodeo-admin-ui-login', PLUGIN_URL.'assets/css/login.css', array(), $this->version, 'all' );
		if(!$this->optionsInstance->get_saved_option('system-font') ) {
			wp_enqueue_style( 'kodeo-admin-ui-font', PLUGIN_URL.'assets/css/font.css', array(), $this->version, 'all' );
		};
	    wp_enqueue_script( 'kodeo-admin-ui-js', PLUGIN_URL.'assets/js/kodeo-admin-ui.js', array( 'jquery', 'jquery-ui-sortable', 'thickbox' ), $this->version );
        wp_localize_script( 'kodeo-admin-ui-js', 'l10n',
			array(
				'login' => _x( 'Username or Email Address', 'Login Screen Placeholder', 'kodeo-admin-ui' ),
				'pass' => _x( 'Password', 'Login Screen Placeholder', 'kodeo-admin-ui' ),
			)
		);
    }

    private function _add_role_class( $classes ) {
		$user = wp_get_current_user();
	    $role = ( array ) $user->roles;
        $classes[] = 'role-'. $role[0];
        return $classes;
    }

    function filter_add_body_classes( $body_classes ) {
        global $post;
        if ( isset( $post ) ) $body_classes[] = $post->post_type . '-' . $post->post_name;

        $body_classes = $this->_add_role_class( $body_classes );
        if( $this->optionsInstance->get_saved_option('frontend-mini-toolbar') ) $body_classes[] = "kaui-mini-toolbar";

		return $body_classes;
    }
    function filter_add_admin_body_classes( $body_classes ) {
		$new_classes = array('kaui');
        $new_classes = $this->_add_role_class( $new_classes );

        if( $this->optionsInstance->get_saved_option('enable-admin-theme') ) $new_classes[] = "kodeo-admin-ui-theme";
        if( $this->optionsInstance->get_saved_option('auto-collapse-submenus') ) $new_classes[] = "auto-collapse-submenus";
        if( !$this->optionsInstance->get_saved_option('show-menu-separators') ) $new_classes[] = "hide-menu-separators";
        if( !$this->optionsInstance->get_saved_option('show-menu-icons') ) $new_classes[] = "hide-menu-icons";
        if( !$this->optionsInstance->get_saved_option('show-menu-counters') ) $new_classes[] = "hide-menu-counters";
        if( $this->optionsInstance->get_saved_option('fixed-admin-toolbar') ) $new_classes[] = "fixed-admin-toolbar";
        if( $this->optionsInstance->get_saved_option('show-search-in-toolbar') ) $new_classes[] = "toolbar-search";
		if( $this->optionsInstance->get_saved_option('show-toolbar-notifications') ) $new_classes[] = "toolbar-notifications";
        if( $this->optionsInstance->get_saved_option('show-help-screen-options-modal') ) $new_classes[] = "help-screen-options-modal";
        if( $this->optionsInstance->get_saved_option('dashboard-cols-limit') ) $new_classes[] = "dashboard-cols-limit";
        if( $this->optionsInstance->get_saved_option('widget-cols-limit') ) $new_classes[] = "widget-cols-limit";
        if( $this->optionsInstance->get_saved_option('deactivate-postboxes-sortable') ) $new_classes[] = "fixed-pb";
        if( $this->optionsInstance->get_saved_option('feature-post-order') ) $new_classes[] = "kaui-post-sorting";

        return $body_classes .' '. implode( ' ', $new_classes ) .' ';
    }

    function action_print_frontend_styles() {
        // Standard admin bar only and only if showing
        if( !is_admin_bar_showing() || $this->optionsInstance->get_saved_option('frontend-mini-toolbar') ) return;

        echo '<style>
        html { margin-top: 42px !important; }
        * html body { margin-top: 42px !important; }
	    @media ( max-width: 782px ) {
            html { margin-top: 40px !important; }
            * html body { margin-top: 40px !important; }
        }'. PHP_EOL .'</style>';
    }

    // Run the loader to execute all of the hooks with WordPress.
	public function run() {
        if(!$this->version_check()) return;
        $this->load_dependencies();
		$this->define_hooks();

		$this->loader->run();
	}

	// The name of the plugin used to uniquely identify it within the context of WordPress and to define internationalization functionality.
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	// The reference to the class that orchestrates the hooks with the plugin.
	public function get_loader() {
		return $this->loader;
	}

	// Retrieve the version number of the plugin.
	public function get_version() {
		return $this->version;
	}

    // Return an instance, if it is initialized
    public function get_instance( $name ) {
        return isset($this->instances[$name]) ? $this->instances[$name] : null;
    }

    public function load_textdomain() {
        load_plugin_textdomain($this->textdomain, false, basename(PLUGIN_PATH) . '/languages' );
    }

    // Load the German Translation
    public function load_fallback_textdomain( $override, $domain, $mofile ) {
        $de_mofile = plugin_dir_path( dirname(__FILE__) ) . 'languages/'.$this->textdomain.'-de_DE.mo';
        if($domain === $this->textdomain && is_readable($de_mofile) && $mofile !== $de_mofile) {
            load_textdomain( $this->textdomain, $de_mofile );
        }

		return false;
	}
}
