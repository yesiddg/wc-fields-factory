<?php 

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class wcff_post_list_table extends WP_List_Table {
	
    private $post_type = "";
	private $postPerPage = 50;
	
	public function __construct($_post_type = "wccpf") {
	   parent::__construct(
	       array(
	           'singular' => '',
	           'plural'   => 'posts',
	           'ajax'     => false,
	           'screen'   => get_current_screen()
	       )
	   );
	   $this->post_type = $_post_type;
	   
	   //add_action('manage_posts_extra_tablenav', array($this, 'inject_wcff_post_filters')); 
	   //add_filter('disable_months_dropdown', array($this, 'disable_month_filter'));
	   
	   add_filter("views_edit-wccvf", array($this, "prepare_wccvf_views"), 10, 1);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see WP_List_Table::get_bulk_actions()
	 */
	public function get_bulk_actions() {
	    return array(
	       'trash' => 'Move to Trash'
	     );
	}
	
	public function extra_tablenav($which) {
	    
	}
	
	public function disable_month_filter() {
	    return true;
	}
	
	public function prepare_wccvf_views($_views) {
	    
	    $all = get_posts(array('post_type' => "wccvf", 'posts_per_page' => -1));
	    $trashs = get_posts(array('post_type' => "wccvf", 'post_status' => "trash", 'posts_per_page' => -1));
	    $drafts = get_posts(array('post_type' => "wccvf", 'post_status' => "draft", 'posts_per_page' => -1));
	    $published = get_posts(array('post_type' => "wccvf", 'post_status' => "publish", 'posts_per_page' => -1));
	    
	    if (count($all) > 0) {
	        $_views["all"] = '<a href="edit.php?post_type=wccvf">All <span class="count">('. count($all) .')</span></a>'; 
	    }
	    if (count($trashs) > 0) {
	        $_views["trash"] = '<a href="edit.php?post_status=trash&amp;post_type=wccvf">Trash <span class="count">('. count($trashs) .')</span></a>';
	    }
	    if (count($drafts) > 0) {
	        $_views["draft"] = '<a href="edit.php?post_status=draft&amp;post_type=wccvf">Draft <span class="count">('. count($drafts) .')</span></a>';
	    }
	    if (count($published) > 0) {
	        $_views["published"] = '<a href="edit.php?post_status=publish&amp;post_type=wccvf">Published <span class="count">('. count($published) .')</span></a>';
	    }
	    
	    return $_views;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see WP_List_Table::prepare_items()
	 */
	public function prepare_items() { 
	    $status = isset($_GET["post_status"]) ? $_GET["post_status"] : 'publish';
	    $action = isset($_GET["action"]) ? $_GET["action"] : null;
	   
	    $columns = $this->get_columns();
	    $hidden = $this->get_hidden_columns();
	    $sortable = $this->get_sortable_columns();
	    
	    /* Fetch data */
	    $data = $this->load_wcff_group_posts($this->post_type, $status);
	    
	    usort( $data, array( &$this, 'sort_data' ) );
	    $currentPage = $this->get_pagenum();
	    $totalItems = count($data);
	    $this->set_pagination_args( array(
	        'total_items' => $totalItems,
	        'per_page'    => $this->postPerPage
	    ));
	    $data = array_slice($data,(($currentPage-1)*$this->postPerPage),$this->postPerPage);
	    $this->_column_headers = array($columns, $hidden, $sortable);
	    $this->items = $data;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see WP_List_Table::get_columns()
	 */
	public function get_columns() 	{
		$columns = array(
			'cb'		=> '<input type="checkbox" />',
			'id'          	=> 'ID',
			'title'       	=> 'Title',
			'fields'  => 'Fields'
		);
		return $columns;
	}
	
	/**
	 * 
	 * @return array
	 */
	public function get_hidden_columns() {
		return array("id");
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see WP_List_Table::get_sortable_columns()
	 */
	public function get_sortable_columns() {
		return array('title' => array('title', false));
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see WP_List_Table::column_default()
	 */
	public function column_default($post, $column_name) {
	    switch ($column_name) {
	        case 'id':
	            return $post['id'];
	            break;
	        case 'fields':
	        do_action("manage_{$this->post_type}_posts_custom_column", $column_name, $post['id']);
	            break;
	    }
	}
	
	public function column_cb($_item) {
		return sprintf(
		    '<label class="screen-reader-text" for="'. esc_attr($this->post_type .'_' . $_item['id']) . '">' . sprintf( __( 'Select %s' ), $_item['id'] ) . '</label>'
			. "<input type='checkbox' name='users[]' id='{$this->post_type}_{$_item['id']}' value='{$_item['id']}' />"
		);
	}
	
	public function column_title($_item) {
		$actions = array();
		$status = isset($_GET["post_status"]) ? $_GET["post_status"] : null;
		if (!$status || $status != "trash") {
			$actions['edit'] = '<a href="'. esc_url($_item["edit"]) .'" aria-label="Edit \"'. esc_attr($_item["title"]) .'\"">Edit</a>';
			$actions['trash'] = $_item["trash"];			
			$actions['clone_group'] = $_item["clone_group"];						
		} else {
			$actions['restore'] = $_item["untrash"];
			$actions['delete'] = $_item["delete"];
		}
		
		if (!$status || $status != "trash") {
			return ('<a class="row-title" href="'. esc_url($_item["edit"]) .'" aria-label="'. esc_attr($_item["title"]) .'">'. esc_html($_item["title"]) .'</a>') . $this->row_actions($actions);
		} else {
			return ('<strong><span>'. esc_html($_item["title"]) .'</span></strong>') . $this->row_actions($actions);
		}
		
	}
	
	/**
	 *
	 * Used to list all the custom variation fields group posts
	 *
	 * @return array
	 *
	 */
	private function load_wcff_group_posts($_post_type = 'post', $_status = 'publish') {
	    
	    $res = array();
	    $entry = array();		
		$posts = get_posts(array (
		      'post_type' => $_post_type,
		      'post_status' => $_status,
			  'posts_per_page' => $this->postPerPage
			)
		);
		
		$posts = $this->apply_wcff_filters($posts);
		
		foreach ($posts as $post) {
		    
		    $title = $post->post_title;
		    $post_type_object = get_post_type_object( $post->post_type );
		    $can_edit_post    = current_user_can( 'edit_post', $post->ID );
		    	    
		    $entry = array (
		        "id" => $post->ID,
		        "title" => $post->post_title,
		        "link" => get_post_permalink($post->ID),
		        "edit" => get_edit_post_link($post->ID)
		    );
		    
		    if ( current_user_can( 'delete_post', $post->ID ) ) {
		        if ( 'trash' === $post->post_status ) {
		            $entry['untrash'] = sprintf(
		                '<a href="%s">%s</a>',
		                wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-post_' . $post->ID ),		                
		                __( 'Restore' )
		            );
		        } elseif ( EMPTY_TRASH_DAYS ) {
		            $entry['trash'] = sprintf(
		                '<a href="%s" class="submitdelete">%s</a>',
		                get_delete_post_link( $post->ID ),		                
		                _x( 'Trash', 'verb' )
		            );
		        }
		        
		        if ( 'trash' === $post->post_status || ! EMPTY_TRASH_DAYS ) {
		            $entry['delete'] = sprintf(
		                '<a href="%s" class="submitdelete">%s</a>',
		                get_delete_post_link( $post->ID, '', true ),		                
		                __( 'Delete Permanently' )
		            );
		        }
		    }
		    
		    if ( is_post_type_viewable( $post_type_object ) ) {
		        if ( in_array( $post->post_status, array( 'pending', 'draft', 'future' ), true ) ) {
		            if ( $can_edit_post ) {
		                $preview_link    = get_preview_post_link( $post );
		                $entry['view'] = sprintf(
		                    '<a href="%s" rel="bookmark">%s</a>',
		                    esc_url( $preview_link ),		                    
		                    __( 'Preview' )
		                );
		            }
		        } elseif ( 'trash' !== $post->post_status ) {
		            $entry['view'] = sprintf(
		                '<a href="%s" rel="bookmark">%s</a>',
		                get_permalink( $post->ID ),		                
		                __( 'View' )
		            );
		        }
		    }
		    
		    if ( 'wp_block' === $post->post_type ) {
		        $entry['export'] = sprintf(
		            '<button type="button" class="wp-list-reusable-blocks__export button-link" data-id="%s">%s</button>',
		            $post->ID,		            
		            __( 'Export as JSON' )
		        );
		    }
		    
		    $entry['clone_group'] = '<a href="'. wp_nonce_url('?post_type=wccvf&amp;action=wcff_clone_group&amp;post='.$post->ID ) .'" class="wcff_clone_group" title="'. __('Duplicate this fields group', 'wc-fields-factory') .'">' . __('Clone', 'wc-fields-factory') . '</a>';
		    
		    $entry["fields_count"] = $this->get_fields_count($post->ID);		    
		    $res[] = $entry;
		    
		}
		
		return $res;
	}
	
	private function apply_wcff_filters($_posts) {
	    
	    global $post;
	    
	    if ((isset($_REQUEST["wcff_target_context_filter"]) && !empty($_REQUEST["wcff_target_context_filter"])) &&
	        (isset($_REQUEST["wcff_target_logic_filter"]) && !empty($_REQUEST["wcff_target_logic_filter"])) &&
	        (isset($_REQUEST["wcff_target_value_filter"]) && !empty($_REQUEST["wcff_target_value_filter"]))) {
            $res = array();    
            $rule = array(
                array(
                    array(
                        "context" => $_REQUEST["wcff_target_context_filter"],
                        "logic" => $_REQUEST["wcff_target_logic_filter"],
                        "endpoint" => $_REQUEST["wcff_target_value_filter"]
                    )
                )
            );
            
            foreach ($_posts as $p) {   
                setup_postdata($p);
                $post = $p;                
                if (has_term($_REQUEST["wcff_target_value_filter"], 'product_cat', $post->ID)) {
                    error_log("Has term passed");
                } else {
                    error_log("Has term failed");
                }              
                
                if (wcff()->dao->check_for_product($post->ID, $rule)) {
                    $res[] = $post;
                }
            }
            $_posts = $res;
        }
	    return $_posts;
	}
	
	private function load_value_filter($_context, $_selected_record) {
	    $html = '';
	    $records = array();
	    if ($_context == "products") {
	        $records = wcff()->dao->load_all_products();
	        array_unshift($records , array("id" => "", "title" => __("All Products", "wc-fields-factory")));
	    } else if ($_context == "product_categories") {
	        $records = wcff()->dao->load_product_categories();
	        array_unshift($records , array("id" => "", "title" => __("All Categories", "wc-fields-factory")));
	    } else if ($_context == "product_tags") {
	        $records = wcff()->dao->load_product_tags();
	        array_unshift($records , array("id" => "", "title" => __("All Tags", "wc-fields-factory")));
	    } else if ($_context == "product_types") {
	        $records = wcff()->dao->load_product_types();
	        array_unshift($records , array("id" => "", "title" => __("All Types", "wc-fields-factory")));
	    } else {
	        /* Ignore */
	    }
	    
	    foreach ($records as $record) {
	        $selected = ($record["id"] == $_selected_record) ? 'selected="selected"' : '';
	        $html .= '<option value="'. esc_attr($record["id"]) .'" '. $selected .'>'. esc_html($record["title"]) .'</option>';   
	    }
	    return $html;
	}
	
	private function sort_data($_a, $_b) {
		// Set defaults
		$orderby = 'title';
		$order = 'asc';
		// If orderby is set, use this as the sort column
		if(!empty($_REQUEST['orderby'])) {
			$orderby = $_REQUEST['orderby'];
		}
		// If order is set use this as the order
		if(!empty($_REQUEST['order'])) {
			$order = $_REQUEST['order'];
		}
		$result = strcmp( $_a[$orderby], $_b[$orderby] );
		if($order === 'asc') {
			return $result;
		}
		return -$result;
	}

}

?>