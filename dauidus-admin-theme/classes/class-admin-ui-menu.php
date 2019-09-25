<?php
class Kodeo_Admin_UI_Menu {
    private $pagesInstance;

    public function __construct($pagesInstance) {
        $this->pagesInstance = $pagesInstance;
    }

    function action_add_menu_entries() {
        // Kodeo Admin UI Pages
        $pages = $this->pagesInstance;
		foreach ( $pages->get_all_pages() as $page_info ) {
            if(!is_null($page_info['parent'])) {
                add_submenu_page(
                    $page_info['parent'],
                    $page_info['title'],
                    ( $page_info['menu-title'] ? $page_info['menu-title'] : $page_info['title'] ),
                    'administrator',
                    $page_info['slug'],
                    $page_info['callback']
                );
            }
		}

        // View Website Button
        /*
        add_menu_page(
			'Kodeo Admin UI View Site Button',
            __( 'View Website', 'kodeo-admin-ui' ),
			'read',
			'kodeo-home',
			'__return_false',
			'dashicons-admin-home',
			1
		);
		*/

        global $menu;
        // 0 = menu_title, 1 = capability, 2 = menu_slug, 3 = page_title, 4 = classes, 5 = hookname, 6 = icon_url

        foreach($menu as $key => $item) {
            if($item[2] == "kodeo-home") {
                $menu[$key][2] = home_url();
                break;
            }
        }
    }

    // Add numbers to certain menu items
	static function action_add_counters() {
		global $menu;

		foreach ($menu as $item_key => $item) {
            if( !isset($item[2]) ) continue;

			$item_slug = $item[2];
			$item_title = $item[0];

			// Only continue if it doesn't already have a number
			// Except if that number is 0 (comments awaiting moderation)
			if( strpos($item_title, '<') !== false && strpos($item_title, 'count-0') === false ) continue;

			if( strpos($item_slug, 'edit.php') !== false ) { // Post types: Get number of published posts
				$post_type = explode( 'post_type=', $item_slug );
				$post_type = isset( $post_type[1] ) ? $post_type[1] : 'post';
				$post_count = wp_count_posts( $post_type );
				$post_count = $post_count->publish;
			} elseif( $item_slug == 'upload.php' ) { // Media: Get total file count
				$post_count = get_children(
					array(
						'post_parent' => null,
						'post_type' => 'attachment',
						'fields' => 'ids'
					)
				);
				$post_count = count( $post_count );
			} elseif( $item_slug == 'edit-comments.php' ) { // Comments: Get total number of comments
				$post_count = wp_count_comments();
				$post_count = $post_count->total_comments;
			} elseif( $item_slug == 'users.php' ) { // Users: Get number of users
				$post_count = count_users();
				$post_count = $post_count['total_users'];
			} elseif( $item_slug == 'plugins.php' ) { // Plugins: Get number of plugins
				$post_count = get_plugins();
				$post_count = count( $post_count );
			} else {
				continue;
			}

			// Format & display
			$post_count_display = $post_count > 999 ? '999+' : $post_count;
			$menu[$item_key][0] .= '<span title="'. esc_attr($post_count.' '.$item_title) .'">'. $post_count_display .'</span>';
		}
	}
}
