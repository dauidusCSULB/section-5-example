<?php
class Kodeo_Admin_UI_Post_Sorting {
    private $enabled_types;
    // private $enabled_taxonomies;
    private $locked_types;
    // private $locked_taxonomies;

    function __construct() {
        // Extend the MySQL table, if required
        // The same slug is used, as in the plugin Simple Custom Post Order, since the table structure is the same
        // if (!get_option('scporder_install')) $this->extend_mysql();

        add_action('admin_init', array($this, 'refresh'));

        add_action('wp_ajax_update-menu-order', array($this, 'update_menu_order'));
        // add_action('wp_ajax_update-menu-order-tags', array($this, 'update_menu_order_tags'));
        add_action('wp_ajax_toggle-menu-order-lock', array($this, 'toggle_menu_order_lock'));

        add_action('pre_get_posts', array($this, 'pre_get_posts'));
        // add_filter('get_terms_orderby', array($this, 'get_terms_orderby'), 10, 3);
        // add_filter('wp_get_object_terms', array($this, 'get_object_terms'), 10, 3);
        // add_filter('get_terms', array($this, 'get_object_terms'), 10, 3);

        add_filter('get_previous_post_where', array($this, 'previous_post_where'));
        add_filter('get_previous_post_sort', array($this, 'previous_post_sort'));
        add_filter('get_next_post_where', array($this, 'next_post_where'));
        add_filter('get_next_post_sort', array($this, 'next_post_sort'));

        add_action('load-edit.php', array($this, 'add_footer_script'));

        $this->enabled_types = get_option( 'kaui_post_sorting_enabled_types', array() );
        // $this->enabled_taxonomies = get_option( 'kaui_post_sorting_enabled_taxonomies', array() );
        $this->locked_types = get_option( 'kaui_post_sorting_locked_types', array() );
        // $this->locked_taxonomies = get_option( 'kaui_post_sorting_locked_taxonomies', array() );
    }

    function get_all_settings() {
        return array(
            'enabled_types' => $this->enabled_types,
            //'enabled_taxonomies' => $this->enabled_taxonomies,
            'locked_types' => $this->locked_types,
            //'locked_taxonomies' => $this->locked_taxonomies,
        );
    }

    function update( $post_data ) {
        if(isset($post_data['post_types'])) {
            $this->enabled_types = is_array($post_data['post_types']) ? $post_data['post_types'] : array();
            update_option( 'kaui_post_sorting_enabled_types', $this->enabled_types );
        }
        if(isset($post_data['taxonomies'])) {
            $this->enabled_taxonomies = is_array($post_data['taxonomies']) ? $post_data['taxonomies'] : array();
            update_option( 'kaui_post_sorting_enabled_taxonomies', $this->enabled_taxonomies );
        }
    }

    function refresh() {
        global $wpdb;

        foreach($this->enabled_types as $object) {
            $result = $wpdb->get_results("SELECT count(*) as cnt, max(menu_order) as max, min(menu_order) as min FROM {$wpdb->posts} WHERE post_type = '{$object}' AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')");

            if($result[0]->cnt === 0 || $result[0]->cnt === $result[0]->max) continue;

            $results = $wpdb->get_results("SELECT ID FROM {$wpdb->posts} WHERE post_type = '{$object}' AND post_status IN ('publish', 'pending', 'draft', 'private', 'future') ORDER BY menu_order ASC");
            foreach ($results as $key => $result) {
                $wpdb->update($wpdb->posts, array('menu_order' => $key + 1), array('ID' => $result->ID));
            }
        }

        /* foreach ($this->enabled_taxonomies as $taxonomy) {
            $result = $wpdb->get_results("
				SELECT count(*) as cnt, max(term_order) as max, min(term_order) as min
				FROM $wpdb->terms AS terms
				INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id )
				WHERE term_taxonomy.taxonomy = '" . $taxonomy . "'
			");
            if ($result[0]->cnt == 0 || $result[0]->cnt == $result[0]->max)
                continue;

            $results = $wpdb->get_results("
				SELECT terms.term_id
				FROM $wpdb->terms AS terms
				INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id )
				WHERE term_taxonomy.taxonomy = '" . $taxonomy . "'
				ORDER BY term_order ASC
			");
            foreach ($results as $key => $result) {
                $wpdb->update($wpdb->terms, array('term_order' => $key + 1), array('term_id' => $result->term_id));
            }
        } */
    }

    /* function extend_mysql() {
        global $wpdb;
        $result = $wpdb->query("DESCRIBE $wpdb->terms `term_order`");
        if (!$result) {
            $query = "ALTER TABLE $wpdb->terms ADD `term_order` INT( 4 ) NULL DEFAULT '0'";
            $result = $wpdb->query($query);
        }
        update_option('scporder_install', 1);
    } */

    function update_menu_order() {
        global $wpdb;

        parse_str($_POST['order'], $data);

        if (!is_array($data))
            return false;

        $id_arr = array();
        foreach ($data as $key => $values) {
            foreach ($values as $position => $id) {
                $id_arr[] = $id;
            }
        }

        $menu_order_arr = array();
        foreach ($id_arr as $key => $id) {
            $menu_order_arr[] = (int) $wpdb->get_var("SELECT menu_order FROM $wpdb->posts WHERE ID = " . intval($id));
        }

        sort($menu_order_arr);

        foreach ($data as $key => $values) {
            foreach ($values as $position => $id) {
                $wpdb->update($wpdb->posts, array('menu_order' => $menu_order_arr[$position]), array('ID' => intval($id)));
            }
        }
    }

    /* function update_menu_order_tags() {
        global $wpdb;

        parse_str($_POST['order'], $data);

        if (!is_array($data))
            return false;

        $id_arr = array();
        foreach ($data as $key => $values) {
            foreach ($values as $position => $id) {
                $id_arr[] = $id;
            }
        }

        $menu_order_arr = array();
        foreach ($id_arr as $key => $id) {
            $results = $wpdb->get_results("SELECT term_order FROM $wpdb->terms WHERE term_id = " . intval($id));
            foreach ($results as $result) {
                $menu_order_arr[] = $result->term_order;
            }
        }
        sort($menu_order_arr);

        foreach ($data as $key => $values) {
            foreach ($values as $position => $id) {
                $wpdb->update($wpdb->terms, array('term_order' => $menu_order_arr[$position]), array('term_id' => intval($id)));
            }
        }
    } */

    function toggle_menu_order_lock() {
        if(isset($_POST['post_type']) && !empty($_POST['post_type'])) {
            global $wpdb;
            $key = array_search($_POST['post_type'], $this->locked_types);
            if($key===false) {
                $this->locked_types[] = $_POST['post_type'];
            } else {
                array_splice($this->locked_types, $key, 1);
            }
            update_option( 'kaui_post_sorting_locked_types', $this->locked_types );
            echo '1';
        } else {
            echo '0';
        }
        wp_die();
    }

    private function _is_type_supported( $type ) {
        if($type === 'attachment') return false;
        $post_type = get_post_type_object( $type );
        return (is_object($post_type) && in_array($post_type->name, $this->enabled_types));
    }

    private function _is_taxonomy_supported( $taxonomy ) {
        if($taxonomy === 'post_format') return false;
        $taxonomy_object = get_taxonomy( $type );
        return (is_object($taxonomy_object) && in_array($taxonomy_object->name, $this->enabled_taxonomies));
    }

    function pre_get_posts($wp_query) {
        if(!isset($wp_query->query['post_type']) || isset($_GET['orderby'])) return false;
        if( !$this->_is_type_supported($wp_query->query['post_type']) ) return false;

        if(is_admin()) {
            $wp_query->set('orderby', 'menu_order');
            $wp_query->set('order', 'ASC');
        } else {
            if (isset($wp_query->query['suppress_filters'])) {
                if ($wp_query->get('orderby') === 'date') $wp_query->set('orderby', 'menu_order');
                if ($wp_query->get('order') === 'DESC') $wp_query->set('order', 'ASC');
            } else {
                if (!$wp_query->get('orderby')) $wp_query->set('orderby', 'menu_order');
                if (!$wp_query->get('order')) $wp_query->set('order', 'ASC');
            }
        }
    }

    /* function get_terms_orderby($orderby, $args) {
        if(is_admin() || !isset($args['taxonomy'])) return $orderby;

        $taxonomy = $args['taxonomy'];
        if($this->_is_taxonomy_supported($taxonomy)) $orderby = 't.term_order';

        return $orderby;
    }

    function get_object_terms($terms) {
        if(is_admin() && isset($_GET['orderby'])) return $terms;

        foreach($terms as $key => $term) {
            if(is_object($term) && isset($term->taxonomy)) {
                $taxonomy = $term->taxonomy;
                if(!$this->_is_taxonomy_supported($taxonomy)) return $terms;
            } else {
                return $terms;
            }
        }

        usort($terms, array($this, 'sort_terms'));
        return $terms;
    }

    function sort_terms($a, $b) {
        return ($a->term_order === $b->term_order) ? 0 : (
            ($a->term_order < $b->term_order ) ? -1 : 1
        );
    }*/

    function previous_post_where($where) {
        global $post;

        if(isset($post->post_type) && $this->_is_type_supported($post->post_type)) {
            $current_menu_order = $post->menu_order;
            $where = "WHERE p.menu_order > '" . $current_menu_order . "' AND p.post_type = '" . $post->post_type . "' AND p.post_status = 'publish'";
        }
        return $where;
    }

    function previous_post_sort($orderby) {
        global $post;

        if(isset($post->post_type) && $this->_is_type_supported($post->post_type)) {
            $orderby = 'ORDER BY p.menu_order ASC LIMIT 1';
        }
        return $orderby;
    }

    function next_post_where($where) {
        global $post;


        if(isset($post->post_type) && $this->_is_type_supported($post->post_type)) {
            $current_menu_order = $post->menu_order;
            $where = "WHERE p.menu_order < '" . $current_menu_order . "' AND p.post_type = '" . $post->post_type . "' AND p.post_status = 'publish'";
        }
        return $where;
    }

    function next_post_sort($orderby) {
        global $post;

        if(isset($post->post_type) && $this->_is_type_supported($post->post_type)) {
            $orderby = 'ORDER BY p.menu_order DESC LIMIT 1';
        }
        return $orderby;
    }

    function add_footer_script() {
        add_action('in_admin_footer', array($this, 'print_footer_script'));
    }

    function print_footer_script() {
        $screen = get_current_screen();
        $messages = array(
            'off' => 'Manuelle Sortierung aktivieren',
            'on' => 'Manuelle Sortierung sperren',
        );
        if(is_object($screen) && $this->_is_type_supported($screen->post_type)):
?><script>
(function($){
    var $target = $('#posts-filter .tablenav.bottom > div');
    var $button, state;
    if($target.length > 0) {
        var lock_button = document.createElement('div');
        var $lock_button = $(lock_button);
        state = '<?=(!in_array($screen->post_type, $this->locked_types)?'on':'off');?>';
        $lock_button.html('<button type="button" class="button sortlock" data-message-on="<?=$messages['on']?>" data-message-off="<?=$messages['off']?>" data-state="'+state+'"></button>');
        $lock_button.insertAfter($target.last());
        $button = $lock_button.find('.button');
        $button.html('<span class="dashicons dashicons-'+(state==='on'?'lock':'unlock')+'"></span> ' + $button.data('message-' + state));

        $(document).ready(function() {
            init_kaui_post_sorting();
            if(state === 'on') {
                enable_kaui_post_sorting();
            } else {
                disable_kaui_post_sorting();
            }
        });

        var toggle_sorting_lock = function() {
            $.post(ajaxurl, {action: 'toggle-menu-order-lock', post_type: '<?=$screen->post_type?>'}, function(result) {
                if(result === '1') {
                    if(state === 'on') {
                        state = 'off';
                        disable_kaui_post_sorting();
                    } else {
                        state = 'on';
                        enable_kaui_post_sorting();
                    }
                    $button.html('<span class="dashicons dashicons-'+(state==='on'?'lock':'unlock')+'"></span> ' + $button.data('message-' + state));
                } else {
                    alert('Error. Please try again.');
                }
            });
        };
        $button.on('click', toggle_sorting_lock);
    }
})(jQuery);
</script><?php

        endif;
    }
}
