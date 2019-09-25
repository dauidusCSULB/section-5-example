<?php
class Kodeo_Admin_UI_Core {
    private $optionsInstance;

    /* Override Textdomain Variables */
	private $locale; // used to store the current locale e.g. "de_DE"
	private $overwrite_folder; // used to store the folder with the overwrite files
	private $is_multisite; // flag to check if running a multisite environment
	private $current_blog_id; // used to store the multisite blog_id

    function __construct($optionsInstance) {
        $this->optionsInstance = $optionsInstance;

		$this->locale = get_locale(); // get current locale
		$this->overwrite_folder = WP_LANG_DIR . '/overwrites/'; // set folder for overwrites
		$this->is_multisite = is_multisite(); // check if multisite
        // if it is a multisite, get the current blog_id
		if( $this->is_multisite ) $this->current_blog_id = get_current_blog_id();
    }

    public function remove_core_updates( $transient ) {
		include ABSPATH . WPINC . '/version.php';
		$current = new stdClass;
		$current->updates = array();
		$current->version_checked = $wp_version;
		$current->last_checked = ( time()-60 );

		return $current;
	}
    function configure_updates_and_notifications() {
        if( $this->optionsInstance->get_saved_option('updates-disable-completely') ) {
            // This prevents update check (HTTP queries)
            add_filter('pre_site_transient_update_core', array($this, 'remove_core_updates'));
            add_filter('pre_site_transient_update_plugins', array($this, 'remove_core_updates'));
            add_filter('pre_site_transient_update_themes', array($this, 'remove_core_updates'));
            // This gives out zeros for update counters
            add_filter('site_transient_update_core','__return_null');
            add_filter('site_transient_update_plugins','__return_null');
            add_filter('site_transient_update_themes','__return_null');
            return;
        }

        if( $this->optionsInstance->get_saved_option('disable-auto-updates') ) {
            // Disable all automatic updates
            add_filter( 'automatic_updater_disabled', '__return_true' );
        }
    }

    function remove_dashboard_meta() {
        remove_meta_box( 'dashboard_primary', 'dashboard', 'normal' );
        remove_meta_box( 'wpseo-dashboard-overview', 'dashboard', 'normal' );
        remove_meta_box( 'simple_history_dashboard_widget', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_serverinfo', 'dashboard', 'normal' );
    }

    /*
	 * Overwriting strings from all loaded textdomains, no matter if they are used in Core, Plugins or Themes
	 *
	 * The .mo file has to be named [domain]-[locale].mo
	 * e.g. for the plugin Jetpack with the textdomain "jetpack"
	 * and the locale "de_DE" is has to be jetpack-de_DE.mo
	 */
	function overwrite_textdomain( $override, $domain, $mofile ) {
		// if the filter was not called with an overwrite mofile, return false which will proceed with the mofile given and prevents an endless recursion
		if ( strpos( $mofile, $this->overwrite_folder ) !== false ) {
			return false;
		}

		// if an overwrite file exists, load it to overwrite the original strings
		$overwrite_mofile = $domain . '-' . $this->locale . '.mo';

		// check if a global overwrite mofile exists and load it
		$global_overwrite_file = $this->overwrite_folder . $overwrite_mofile;

		if ( file_exists( $global_overwrite_file ) ) {
			load_textdomain( $domain, $global_overwrite_file );
		}

		// check if a overwrite mofile for the current multisite blog exists and load it
		if ( $this->is_multisite ) {
			$current_blog_overwrite_file = $this->overwrite_folder . 'blogs.dir/' . $this->current_blog_id . '/' . $overwrite_mofile;

			if ( file_exists( $current_blog_overwrite_file ) ) {
				load_textdomain( $domain, $current_blog_overwrite_file );
			}
		}

		return false;
	}

    // Disable Autosave
    function disable_autosave() {
        wp_deregister_script('autosave');
    }

    function admin_footer_scripts_deactivate_postboxes_sortable() {
        ?><script>
            jQuery(document).ready(function($) {
                if(typeof postboxes === "object") {
                    postboxes['save_state'] = function() { return true; };
                    postboxes['save_order'] = function() { return true; };
                    $(".meta-box-sortables").sortable('cancel');
                    $(".meta-box-sortables").sortable('disable');
                }
            });
        </script><?php
    }

    function filter_custom_loginlogo_url($url) {
        return home_url();
    }

    function force_paste_as_plain_text( $mceInit ) {
		global $tinymce_version;

		if ( $tinymce_version[0] < 4 ) {
			$mceInit[ 'paste_text_sticky' ] = true;
			$mceInit[ 'paste_text_sticky_default' ] = true;
		} else {
			$mceInit[ 'paste_as_text' ] = true;
		}

		return $mceInit;
	}

	function load_paste_in_teeny( $plugins ) {
		return array_merge( $plugins, ['paste'] );
	}

	function remove_paste_as_plain_text_button( $buttons ) {
		if( ( $key = array_search( 'pastetext', $buttons ) ) !== false ) {
			unset( $buttons[ $key ] );
		}
		return $buttons;
	}

    function remove_disabled_widgets() {
        foreach($this->optionsInstance->wp_widgets as $key) {
            if($this->optionsInstance->get_saved_option($key) == 0) unregister_widget($key);
        }
    }
}
