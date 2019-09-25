<?php
class Kodeo_Admin_UI_Toolbar {
    // Add Menu Expand and View Site Buttons to the Toolbar
    function action_add_toolbar_nodes( $wp_toolbar ) {
		if ( is_admin() ) {
            $wp_toolbar->add_node(
                array( 'id' => 'kodeo-menu-expand',
                      'title' => '<span class="dashicons dashicons-menu">Menu</span>' )
            );

            $wp_toolbar->add_node(
                array( 'id' => 'kodeo-view-site',
                  'title' => __( 'View Site', 'kodeo-admin-ui' ),
                  'href' => home_url() )
            );
        } else {
			$wp_toolbar->add_node(
				array('id' => 'kodeo-admin1',
					'title' => __('Manage', 'kodeo-admin-ui'),
					'href' => admin_url() . '/index.php?page=content_dashboard' )
			);
		}
    }

    // Add Toolbar Notifications If Enabled
    function action_add_notification_center( $wp_toolbar ) {
        $wp_toolbar->add_node(
			array( 'id' => 'kodeo-toolbar-notifications',
                  'title' => '<span class="dashicons dashicons-flag"></span> <span id="kodeo-notification-count">0</a>',
                  'parent' => 'top-secondary' )
        );
        $wp_toolbar->add_menu(
            array( 'id' => 'kodeo-notification-dummy',
                  'parent' => 'kodeo-toolbar-notifications',
                  'title' => '' )
        );
	}

    function action_move_updates_node( $wp_toolbar ) {
        if( current_user_can('update_plugins') ) {
            $node = $wp_toolbar->get_node('updates');
            if( !is_null($node) ) {
                $node->parent = "top-secondary";
                $wp_toolbar->add_node( $node );
            }
        } else {
            $wp_toolbar->remove_node('updates');
        }
    }

    function action_remove_toolbar_nodes( $wp_toolbar ) {
		// Remove the WP logo & dropdown menu
		$wp_toolbar->remove_node('wp-logo');

		// Remove the Comments button
        $wp_toolbar->remove_node('comments');

		// Remove the Site name & dropdown menu
		$wp_toolbar->remove_node('site-name');
	}

    function action_pretify_user( $wp_toolbar ) {
        $new_title = get_avatar( get_current_user_id(), '42' );
		$wp_toolbar->add_node(
			array(
				'id' => 'my-account',
				'title' => $new_title
			)
		);
	}

    // Determine wether to show or hide the admin toolbar depending on page & option
	function action_hide_front_toolbar() {
		show_admin_bar(false);
	}

    function action_add_sticky_editor_toolbar() {
        include PLUGIN_INC_PATH . 'templates/editor-toolbar.php';
    }
}
