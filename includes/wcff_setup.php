<?php
/**
 * @author 		: Saravana Kumar K
 * @author url  : http://iamsark.com
 * @copyright	: sarkware.com
 *
 * This module is responsible for loading and initializing various components of WC Fields Factory
 *
 */

if (!defined('ABSPATH')) { exit; }

class wcff_setup {
    
    public $archive = null;
    
    public function __construct() {}
    
    public function init_wcff_admin() {

        $this->register_admin_assets();
        add_action('admin_menu', array($this,'register_admin_menus'));
        //add_action('admin_notices', array($this,'wcff_ask_rating'));
        add_filter('plugin_action_links_' . wcff()->info["basename"], array($this, 'wcff_plugin_setting'));
        add_filter('page_row_actions', array($this, 'add_cloning_link'), 10, 2);
        add_action('admin_action_wcff_clone_group', array(wcff()->dao, 'clone_group'), 10);
        add_action('manage_posts_extra_tablenav', array($this, 'inject_wcff_post_filters')); 
        add_filter('disable_months_dropdown', array($this, 'disable_month_filter'));
        add_filter('parse_query', array($this, 'intercept_wp_query'));
        add_filter('the_posts', array($this, 'apply_wcff_filters'), 10, 2);

        add_action('plugins_loaded', array($this, 'db_sanity_check'));
        add_action('upgrader_process_complete', array($this, 'after_wcff_updated'), 10, 2);

    }
    
    /**
     * Does the regiteration for core custom post types
     *
     */
    public function register_wcff_post_types() {

        $wcff_options = get_option("wcff_options");
	    $wcff_options =  is_array($wcff_options) ? $wcff_options : array();        
        $variable_module = isset($wcff_options["enable_variable_field"]) ? $wcff_options["enable_variable_field"] : "yes";
        $admin_module = isset($wcff_options["enable_admin_field"]) ? $wcff_options["enable_admin_field"] : "yes";
        $checkout_module = isset($wcff_options["enable_checkout_field"]) ? $wcff_options["enable_checkout_field"] : "yes";	

       
        /* Labels for wccpf post type */
        $wccpf_labels = array (
            'name'                  => _x('Product Field Groups', 'Post type general name', 'wc-fields-factory'),
            'singular_name'         => _x('Product Fields', 'Post type singular name', 'wc-fields-factory'),
            'menu_name'             => _x('Product Fields', 'Admin Menu text', 'wc-fields-factory'),
            'name_admin_bar'        => _x('Product Fields', 'Add New on Toolbar', 'wc-fields-factory'),
            'add_new'               => __('Add New', 'wc-fields-factory'),
            'add_new_item'          => __('Add New Product Fields Group', 'wc-fields-factory'),
            'new_item'              => __('New Product Field Group', 'wc-fields-factory'),
            'edit_item'             => __('Edit Product Field Group', 'wc-fields-factory'),
            'view_item'             => __('View Product Field Group', 'wc-fields-factory'),
            'all_items'             => __('', 'wc-fields-factory'),
            'search_items'          => __('Search Product Fields Group', 'wc-fields-factory'),
            'parent_item_colon'     => __('Parent Product Field Group:', 'wc-fields-factory'),
            'not_found'             => __('No product field group found.', 'wc-fields-factory'),
            'not_found_in_trash'    => __('No product field groups found in Trash.', 'wc-fields-factory')
        );
                
        /* Post type arguments of wccpf post type */
        $wccpf_args = array (
            'labels'                => $wccpf_labels,
            'public'                => false,
            'show_ui'               => true,
            '_builtin'              => false,
            'capability_type'       => 'page',
            'hierarchical'          => true,              
            'rewrite'               => false,            
            'query_var'             => 'wccpf',                      
            'supports'              => array('title'),
            'show_in_menu'          => false
        );
        
        /* Register wccpf post type, which responsible for Wc Custom Product Fields */
        register_post_type('wccpf', $wccpf_args);
       
             
        if ($admin_module == "yes") {
            /* Labels for wccaf post type */
            $wccaf_labels = array (
                'name'                  => _x('Admin Field Groups', 'Post type general name', 'wc-fields-factory'),
                'singular_name'         => _x('Admin Fields', 'Post type singular name', 'wc-fields-factory'),
                'menu_name'             => _x('Admin Fields', 'Admin Menu text', 'wc-fields-factory'),
                'name_admin_bar'        => _x('Admin Fields', 'Add New on Toolbar', 'wc-fields-factory'),
                'add_new'               => __('Add New', 'wc-fields-factory'),
                'add_new_item'          => __('Add New Admin Field Group', 'wc-fields-factory'),
                'new_item'              => __('New Admin Field Group', 'wc-fields-factory'),
                'edit_item'             => __('Edit Admin Field Group', 'wc-fields-factory'),
                'view_item'             => __('View Admin Field Group', 'wc-fields-factory'),
                'all_items'             => __('', 'wc-fields-factory'),
                'search_items'          => __('Search Admin Field Group', 'wc-fields-factory'),
                'parent_item_colon'     => __('Parent Admin Field Group:', 'wc-fields-factory'),
                'not_found'             => __('No admin field group found.', 'wc-fields-factory'),
                'not_found_in_trash'    => __('No admin field groups found in Trash.', 'wc-fields-factory')
            );        
            
            /* Post type arguments of wccaf post type */
            $wccaf_args = array (
                'labels'                => $wccaf_labels,
                'public'                => false,
                'show_ui'               => true,
                '_builtin'              => false,
                'capability_type'       => 'page',
                'hierarchical'          => true,              
                'rewrite'               => false,            
                'query_var'             => 'wccaf',                      
                'supports'              => array('title'),
                'show_in_menu'          => false
            );
            
            /* Register wccaf post type, which responsible for Wc Custom Admin Fields */
            register_post_type('wccaf', $wccaf_args);
        }       
        
        if ($variable_module == "yes") {
            /* Labels for wccaf post type */
            $wccvf_labels = array (
                'name'                  => _x('Variation Field Groups', 'Post type general name', 'wc-fields-factory'),
                'singular_name'         => _x('Variation Fields', 'Post type singular name', 'wc-fields-factory'),
                'menu_name'             => _x('Variation Fields', 'Admin Menu text', 'wc-fields-factory'),
                'name_admin_bar'        => _x('Variation Fields', 'Add New on Toolbar', 'wc-fields-factory'),
                'add_new'               => __('Add New', 'wc-fields-factory'),
                'add_new_item'          => __('Add New Variation Field Group', 'wc-fields-factory'),
                'new_item'              => __('New Variation Field Group', 'wc-fields-factory'),
                'edit_item'             => __('Edit Variation Field Group', 'wc-fields-factory'),
                'view_item'             => __('View Variation Field Group', 'wc-fields-factory'),
                'all_items'             => __('', 'wc-fields-factory'),
                'search_items'          => __('Search Variation Field Group', 'wc-fields-factory'),
                'parent_item_colon'     => __('Parent Variation Field Group:', 'wc-fields-factory'),
                'not_found'             => __('No variation field group found.', 'wc-fields-factory'),
                'not_found_in_trash'    => __('No variation field groups found in Trash.', 'wc-fields-factory')
            );
            
            /* Post type arguments of wccaf post type */
            $wccvf_args = array (
                'labels'                => $wccvf_labels,
                'public'                => false,
                'show_ui'               => true,
                '_builtin'              => false,
                'capability_type'       => 'page',
                'hierarchical'          => true,              
                'rewrite'               => false,            
                'query_var'             => 'wccvf',                      
                'supports'              => array('title'),
                'show_in_menu'          => false
            );
            
            /* Register wccvf post type, which responsible for Wc Custom Variation Fields */
            register_post_type('wccvf', $wccvf_args);
        }        
          
        if ($checkout_module == "yes") {
            /* Labels for wccaf post type */
            $wcccf_labels = array (
                'name'                  => _x('Checkout Field Groups', 'Post type general name', 'wc-fields-factory'),
                'singular_name'         => _x('Checkout Fields', 'Post type singular name', 'wc-fields-factory'),
                'menu_name'             => _x('Checkout Fields', 'Admin Menu text', 'wc-fields-factory'),
                'name_admin_bar'        => _x('Checkout Fields', 'Add New on Toolbar', 'wc-fields-factory'),
                'add_new'               => __('Add New', 'wc-fields-factory'),
                'add_new_item'          => __('Add New WC Custom Checkout Field Group', 'wc-fields-factory'),
                'new_item'              => __('New Checkout Field Group', 'wc-fields-factory'),
                'edit_item'             => __('Edit Checkout Field Group', 'wc-fields-factory'),
                'view_item'             => __('View Checkout Field Group', 'wc-fields-factory'),
                'all_items'             => __('', 'wc-fields-factory'),
                'search_items'          => __('Search Checkout Field Group', 'wc-fields-factory'),
                'parent_item_colon'     => __('Parent Checkout Field Group:', 'wc-fields-factory'),
                'not_found'             => __('No checkout field group found.', 'wc-fields-factory'),
                'not_found_in_trash'    => __('No checkout field groups found in Trash.', 'wc-fields-factory')
            );
            
            /* Post type arguments of wccaf post type */
            $wcccf_args = array (
                'labels'                => $wcccf_labels,
                'public'                => false,
                'show_ui'               => true,
                '_builtin'              => false,
                'capability_type'       => 'page',
                'hierarchical'          => true,
                'rewrite'               => false,
                'query_var'             => "wcccf",
                'supports'              => array( 'title' ),
                'show_in_menu'	        => false,
                'capability_type'       => 'post',
                'capabilities'          => array(
                    'create_posts' => 'do_not_allow',
                    'delete_posts' => 'do_not_allow'
                ),
                'map_meta_cap'          => true
            );
            
            /* Register wccaf post type, which responsible for Wc Custom Admin Fields */
            register_post_type('wcccf', $wcccf_args);
        }
        
    }
    
    /**
     * Responsible for inserting Admin menu and submenu
     *
     */
    public function register_admin_menus() {

        $wcff_options = get_option("wcff_options");
	    $wcff_options =  is_array($wcff_options) ? $wcff_options : array();
        $variable_module = isset($wcff_options["enable_variable_field"]) ? $wcff_options["enable_variable_field"] : "yes";
        $admin_module = isset($wcff_options["enable_admin_field"]) ? $wcff_options["enable_admin_field"] : "yes";
        $checkout_module = isset($wcff_options["enable_checkout_field"]) ? $wcff_options["enable_checkout_field"] : "yes";	
      
        /* This is the main menu entry for WC Fields Factory */
        add_menu_page(
            __("WC Fields Factory", "wc-fields-factory"),
            __("Fields Factory", "wc-fields-factory"),
            "manage_woocommerce",
            "edit.php?post_type=wccpf",
            false,
            esc_url(wcff()->info['dir'] .'assets/img/icon.png?v='. wcff()->info['version']),
            '55.5'
        );               
       
        /* Sub menu for Product Fields */
        add_submenu_page(
            "edit.php?post_type=wccpf",
            __("Product Fields", "wc-fields-factory"),
            __("Product Fields", "wc-fields-factory"),
            "manage_woocommerce",
            "edit.php?post_type=wccpf"
        );    

        if ($variable_module == "yes") {
            /* Sub menu for Variation Fields */
            add_submenu_page(
                "edit.php?post_type=wccpf",
                __("Variation Fields", "wc-fields-factory"),
                __("Variation Fields", "wc-fields-factory"),
                "manage_woocommerce",
                "variation_fields_config",
                "render_variation_fields_config_view"
            );
        }        
        
        if ($admin_module == "yes") {
            /* Sub menu for Admin Fields */
            add_submenu_page(
                "edit.php?post_type=wccpf",
                __("Admin Fields", "wc-fields-factory"),
                __("Admin Fields", "wc-fields-factory"),
                "manage_woocommerce",
                "edit.php?post_type=wccaf"
            );
        }        
        
        if ($checkout_module == "yes") {
            if (function_exists('WC') && version_compare(WC()->version, '3.2.0', '>')) {
                /* Sub menu for Checkout Fields */
                add_submenu_page(
                    "edit.php?post_type=wccpf",
                    __("Checkout Fields", "wc-fields-factory"),
                    __("Checkout Fields", "wc-fields-factory"),
                    "manage_woocommerce",
                    "edit.php?post_type=wcccf"
                );
            }
        }        
        
        /* Sub menu for Option page */
        add_submenu_page(
            "edit.php?post_type=wccpf",
            __("Wc Fields Factory Options", "wc-fields-factory"),
            __("Settings", "wc-fields-factory"),
            "manage_woocommerce",
            "wcff_settings",
            "wcff_render_option_page"
        );
    }
    
    /**
     * Register WC Fields Factory related script & css for wp-admin page.
     */
    public function register_admin_assets() {
        wp_register_script( 'wcff-script', wcff()->info['dir'] . 'assets/js/wcff-admin.js', 'jquery', wcff()->info['version'] );
        wp_register_style( 'wcff-style', wcff()->info['dir'] . 'assets/css/wcff-admin.css', array(), wcff()->info['version'] );
    }
    
    /**
     * Display rating link for WC Fields Factory on the admin page.
     */
    public function wcff_ask_rating() {
        $note_options = get_option( "wcff_ask_rate_dissmiss" );
        if ((get_current_screen()->post_type == "wccpf" ||
            get_current_screen()->post_type == "wccvf" ||
            get_current_screen()->post_type == "wccaf" ||
            get_current_screen()->post_type == "wcccf") && empty($note_options)):
            ?>
            <div data-dismissible="disable-done-notice-forever" class="notice notice-success is-dismissible">
                <p><?php _e('Please rate and review WC Fields Factory to', 'wc-fields-factory') .'<a href="https://wordpress.org/support/plugin/wc-fields-factory/reviews/?rate=5#new-post" target="_blank">'. _e('click', 'wc-fields-factory') .'</a><a class="wcff-ask-rate-diss" href="#">'. _e('Dismiss', 'wc-fields-factory') .'</a>'; ?></p>
            </div>
            <?php
        endif;
    }
    
    /**
     * 
     * Add WC Fields Factory Settings page link to the plugin archive page.
     * 
     * @param array $links
     * @return array
     * 
     */
    public function wcff_plugin_setting($_links) {
        $wcff_setting_links = array (
            'settings' => '<a href="' . admin_url('/edit.php?post_type=wccpf&page=wcff_settings') . '" aria-label="' . esc_attr__('settings', 'wc-fields-factory') . '">' . esc_html__('Settings', 'wc-fields-factory') . '</a>',
        );
        return array_merge($wcff_setting_links, $_links);
    }
    
    public function disable_month_filter() {
        return true;
    }
    
    public function inject_wcff_post_filters($_which) {        
        if ($_which == "top" &&
           (get_current_screen()->post_type == "wccpf" ||
            get_current_screen()->post_type == "wccvf" ||
            get_current_screen()->post_type == "wccaf" ||
            get_current_screen()->post_type == "wcccf")) {
                
            $selected = '';
            $logics = wcff()->dao->load_target_logics();
            array_unshift($logics , array("id" => "", "title" => __("Select Logic", "wc-fields-factory")));
            $selected_logic = isset($_GET["wcff_target_logic_filter"]) ? $_GET["wcff_target_logic_filter"] : "";
            
            $contexts = wcff()->dao->load_target_contexts();
            array_unshift($contexts , array("id" => "", "title" => __("Select Target", "wc-fields-factory")));
            $selected_context = isset($_GET["wcff_target_context_filter"]) ? $_GET["wcff_target_context_filter"] : "";
            
            $selected_record = isset($_GET["wcff_target_value_filter"]) ? $_GET["wcff_target_value_filter"] : ""; ?>
            
        <div class="alignleft actions">
			<select name="wcff_target_context_filter" id="wcff_target_context_filter">
				<?php foreach ($contexts as $context) {
				    $selected = ($context["id"] == $selected_context) ? 'selected="selected"' : '';
				    echo '<option value="'. esc_attr($context["id"]) .'" '. $selected .'>'. esc_html($context["title"]) .'</option>';													
				} ?>																			
			</select>				
			<select name="wcff_target_logic_filter" id="wcff_target_logic_filter">
				<?php foreach ($logics as $logic) {
				    $selected = ($logic["id"] == $selected_logic) ? 'selected="selected"' : '';
				    echo '<option value="'. esc_attr($logic["id"]) .'" '. $selected .'>'. esc_html($logic["title"]) .'</option>';													
				} ?>												
			</select>
			<select name="wcff_target_value_filter" id="wcff_target_value_filter">
				<?php			
				if ($selected_context != "") {
				    echo $this->load_value_filter($selected_context, $selected_record);
				}				
				?>
			</select>
			<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter Field's Group">		
		</div>
		
		<script type="text/javascript">
			(function($) {
				$(document).on("change", "#wcff_target_context_filter", function() {
					$.ajax({  
						type       : "POST",  
						data       : {
							action: "wcff_ajax", 
							wcff_param: JSON.stringify({
								"method": "GET",
								"context": $(this).val(),
								"post": 0,
								"post_type": "<?php echo get_current_screen()->post_type; ?>",
								"payload":{}
							})
						},  
						dataType   : "json",  
						url        : '<?php echo esc_url(admin_url( 'admin-ajax.php', 'relative' )); ?>',  
						beforeSend : function() {},  
						success    : function(response) {	
							if (response.status) {
								var resWidget = $(response.data);
								$("#wcff_target_value_filter").html(resWidget.html());
							}														
						},  
						error      : function(jqXHR, textStatus, errorThrown) {},
						complete   : function() {} 
					});						
				});					
			})(jQuery);
		</script>
            
    	<?php
        }       
	}
	
	/* Clear the fields */
	public function intercept_wp_query($_query) {

        if ($_query->query && isset($_query->query["post_type"])) {
            if ($_query->query["post_type"] == "wccpf" ||
                $_query->query["post_type"] == "wccvf" ||
                $_query->query["post_type"] == "wccaf" ||
                $_query->query["post_type"] == "wcccf") {
                $_query->query_vars["fields"] = "";
            }
        }

	}
	
	/**
	 * 
	 * @param array $_posts
	 * @return array
	 */
	public function apply_wcff_filters($_posts, $_query) {     
	    if ((isset($_GET["wcff_target_context_filter"]) && !empty($_GET["wcff_target_context_filter"])) &&
	        (isset($_GET["wcff_target_logic_filter"]) && !empty($_GET["wcff_target_logic_filter"])) &&
	        (isset($_GET["wcff_target_value_filter"]) && !empty($_GET["wcff_target_value_filter"]))) {            	   
	        if (is_admin() AND $_query->query['post_type'] == 'wccpf') {	            
	            $res = array();	            
	            foreach ($_posts as $post) {	 
	                $flaQ = false;
	                $all_rules = wcff()->dao->load_target_products_rules($post->ID);	                
	                foreach ($all_rules as $rules) {
	                    foreach ($rules as $rule) {	                        
	                        if ($_GET["wcff_target_context_filter"] == "product" && $rule["logic"] == $_GET["wcff_target_logic_filter"]) {	                        
	                            if (absint($_GET["wcff_target_value_filter"]) == absint($rule["endpoint"]) || $rule["endpoint"] == -1) {
	                                $flaQ = true;
	                            }
	                        } else {
	                            if ($_GET["wcff_target_context_filter"] == $rule["context"] && 
	                                $rule["logic"] == $_GET["wcff_target_logic_filter"]) {
	                                if ($_GET["wcff_target_value_filter"] == $rule["endpoint"] || $rule["endpoint"] == -1) {
	                                    $flaQ = true;
                                    }	                                
	                            }
	                        }	                        
	                    }
	                }
	                if ($flaQ) {
	                    $res[] = $post;
	                }
	            }
	            $_posts = $res;
            }	            
        }
	    return $_posts;
	}
	
	/**
	 * 
	 * @param string $_context
	 * @param mixed $_selected_record
	 * @return string
	 */
	private function load_value_filter($_context, $_selected_record) {
	    $html = '';
	    $records = array();
	    if ($_context == "product") {
	        $records = wcff()->dao->load_all_products();
	        array_unshift($records , array("id" => "", "title" => __("All Products", "wc-fields-factory")));
	    } else if ($_context == "product_cat") {
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
    
    /**
     * 
     * Add 'Clone' link to the wcff (wccpf, wccvf & wccaf) post's row actions 
     * 
     * @param array $_actions
     * @param object $_post
     * @return array
     * 
     */
    public function add_cloning_link($_actions, $_post) {
    	if ($_post->post_type =="wccpf" || $_post->post_type =="wccaf" || $_post->post_type =="wccvf") {
    		/* Remove quick edit link - as it is not necessary here */
    		unset($_actions["inline hide-if-no-js"]);
    		$_actions['clone_group'] = '<a href="'. wp_nonce_url('?post_type='. $_post->post_type .'&amp;action=wcff_clone_group&amp;post='.$_post->ID ) .'" class="wcff_clone_group" title="'. __('Duplicate this fields group', 'wc-fields-factory') .'">' . __('Clone', 'wc-fields-factory') . '</a>';
    	}
    	return $_actions;
    }

    public function db_sanity_check() {

        $wcff_options = wcff()->option->get_options();
        /* If the wcff oiption not have "version" property 
        that means the installation is prior to V4XXX */
        if (!isset($wcff_options["version"])) {
            wcff()->dao->migrate_for_version_4xxx();
        }
        
    }

    public function after_wcff_updated($_upgrader, $_options) {

        if ($_options['action'] == 'update' && $_options['type'] == 'plugin' ) {
            foreach($_options['plugins'] as $plugin) {
                if ($plugin == wcff()->info['basename']) {                    
                    wcff()->dao->migrate_for_version_4xxx();
                }
            }
        }

    }
    
}

new wcff_setup();

?>