<?php 

if (!defined('ABSPATH')) { exit; }

/**
 * 
 * One of the core module, which is responsible for the registering necessary hooks for the lifecycle of<br><br>
 * 1. Injecting Fields on Single Product Page<br>
 * 2. Add To Cart handler<br>
 * 3. Rendering Fields on Admin Product Overview, Cart & Checkout Page<br>
 * 4. Edit fields on Cart Page<br>
 * 5. Pricing & Fee handler<br>
 * 6. Order Meta Handler
 *
 * @author 	    : Paranjothi G
 * @copyright   : Sarkware Research & Development (OPC) Pvt Ltd
 *
 */
 
class wcff_admin_fields {	
	
	/*  */
	private $location;

	/* */
	private $admin_fields_groups = array();	
	
	/*  */
	private $is_image_field_there = false;

	/* Holds the meta list of all the date fields that is being injected */
	public $date_fields = array();

	/* Holds the meta list of all the color fields that is being injected */
	public $color_fields = array();	

	/* */
	private $product_custom_tabs = array();

	public function __construct() {
        
	    if (is_admin()) {

	        $admin_field_locations = array(
	            "woocommerce_product_options_general_product_data",
	            "woocommerce_product_options_inventory_product_data" ,
	            "woocommerce_product_options_shipping",
	            "woocommerce_product_options_attributes",
	            "woocommerce_product_options_related",
	            "woocommerce_product_options_advanced",
	            "product_cat_add_form_fields",
	            "product_cat_edit_form_fields",
	            "admin_head-post.php"
	        );
	        
	        /* Register field group wise placement */
	        for ($i = 0; $i < count($admin_field_locations); $i++) {	            
	            /* Inject fields on single product page */
	            add_action($admin_field_locations[$i], array($this, 'admin_product_template_fields_injector'));	            
	        }
		        
	        /* Better to enqueue script here itself
	         * even if no fields on product view, since variable product fields
	         * will be injected through ajax, we have no way to enqueue scripts on ajax response  */
	        add_action ('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

			add_action('admin_footer', array($this, 'wccaf_back_end_enqueue_scripts'));
	        
	        add_action ('save_post', array($this, 'save_wccaf_product_fields'), 1, 3);
	        
	        add_action ('edited_product_cat', array($this, 'save_wccaf_product_cat_fields'));
	        add_action ('create_product_cat', array($this, 'save_wccaf_product_cat_fields'));
	        
	        add_action ('woocommerce_product_after_variable_attributes', array($this, 'inject_wccaf_on_product_variable_section'), 10, 3);
	        add_action ('woocommerce_save_product_variation', array($this, 'save_wccaf_product_variable_fields'), 99, 2);
			add_filter ("woocommerce_email_order_meta_fields", array($this, "custom_order_fields_on_order_email"), 10, 3);

			add_filter('woocommerce_product_data_tabs', array($this, 'inject_product_custom_product_data_tabs'));
			add_action('woocommerce_product_data_panels', array($this, 'add_custom_product_data_tabs_containers'));
	    }	 
		
		/* Custom order fields render hook for customer order view - (My Account page) */
		add_action ('woocommerce_after_order_details', array($this, "custom_order_fields_on_customer_order_view"), 10);
	    
    }

	public function inject_product_custom_product_data_tabs($_tabs = array()) {

		global $post;
		$this->product_custom_tabs = array();

		if ($post) {			
			$this->admin_fields_groups = wcff()->dao->load_fields_groups_for_product($post->ID, 'wccaf', "admin", "any");
			if (is_array($this->admin_fields_groups)) {
				foreach ($this->admin_fields_groups as $group) {					
					$admin_target_location = get_post_meta($group["id"], 'wccaf_location_rules', true);	                
	                $admin_target_location = json_decode($admin_target_location, true);
					if ( $admin_target_location["context"] == "location_product_data" && $admin_target_location["endpoint"] == "wccaf_custom_product_data_tab") {
						$this->product_custom_tabs[] = array(
							"title" => wcff()->dao->load_custom_product_data_tab_title($group["id"]),
							"priority" => wcff()->dao->load_custom_product_data_tab_priority($group["id"])
						);
					}					
				}
			}			
		}

		if (!empty($this->product_custom_tabs)) {
			foreach ($this->product_custom_tabs as $tab) {
				$_tabs[sanitize_title($tab["title"])] = array (
					'label'  => __($tab["title"], 'wc-fields-factory'),
					'target' => sanitize_title($tab["title"]),
					'priority' => $tab["priority"],
					'class'  => array(),
				);
			}			
		}

		return $_tabs;

	}

	public function add_custom_product_data_tabs_containers() {

		global $post;
		if ($post && !empty($this->product_custom_tabs)) {
			foreach ($this->product_custom_tabs as $tab) {

				echo '<div id="'. sanitize_title($tab["title"]) .'" class="panel woocommerce_options_panel">';
				
				$this->admin_fields_groups = wcff()->dao->load_fields_groups_for_product($post->ID, 'wccaf', "admin", "wccaf_custom_product_data_tab", false, $tab["title"]);            
            	/* Inject the custom fields into the single product page */            
            	$this->inject_wccaf();			

				echo '</div>';

			}
		}
		
	}
	
	public function save_wccaf_product_fields($_post_id, $_post, $update) {
	
		if ($_post->post_type == "product") {
			$this->admin_fields_groups = wcff()->dao->load_fields_groups_for_product($_post_id, 'wccaf', "admin", "any");
		} else if ($_post->post_type == "shop_order") {
			/* Update the location property manually */
			$this->location = "admin_head-post.php";
			$this->prepare_field_for_order_view($_post_id);
		} else {
			return;
		}	   

	    foreach ($this->admin_fields_groups as $group) {	        
	        if (count($group["fields"]) > 0) {
	            foreach ($group["fields"] as $field) {
	                /* If all checkbox is unchecked then the fields itself won;t be presented in the REQUEST object
	                 * But we need to clear the existing meta for checkbox field */
	                if (isset($_REQUEST[$field["key"]])) {
	                    $this->persist($_post_id, $field, $_REQUEST[$field["key"]], "product");
	                } else if (!isset($_REQUEST[$field["key"]]) && $field["type"] == "checkbox") {
	                    $this->persist($_post_id, $field, array(), "product");
	                }
	            }
	        }	        
	    }
	
	}

	public function save_wccaf_product_cat_fields($_term_id) {
	    
	    global $post;	   
	    $this->admin_fields_groups = wcff()->dao->load_fields_groups_for_product((($post) ? $post->ID : 0), 'wccaf', "admin", "product_cat_edit_form_fields");
	    
	    foreach ($this->admin_fields_groups as $group) {	        
	        if (count($group["fields"]) > 0) {
	            foreach ($group["fields"] as $field) {
	                /* If all checkbox is unchecked then the fields itself won;t be presented in the REQUEST object
	                 * But we need to clear the existing meta for checkbox field */
	                if (isset($_REQUEST[$field["key"]])) {
	                    $this->persist($_term_id, $field, $_REQUEST[$field["key"]], "cat");
	                } else if (!isset($_REQUEST[$field["key"]]) && $field["type"] == "checkbox") {
	                    $this->persist($_term_id, $field, array(), "cat");
	                }
	            }
	        }	        
	    }	    
					
	}
	
	public function save_wccaf_product_variable_fields($_variant_id, $_i) {
	    
	    global $post;
	    $parent_post_id = -1;
	    if (!$post) {
	        $parent_post_id = wp_get_post_parent_id($_variant_id);
	    } else {
	        $parent_post_id = $post->ID;
	    }
	    $this->admin_fields_groups = wcff()->dao->load_fields_groups_for_product($parent_post_id, 'wccaf', "admin", "woocommerce_product_after_variable_attributes");
		$wccaf_posts = wcff()->dao->load_fields_groups_for_product($_variant_id, 'wccaf', "admin", "woocommerce_product_after_variable_attributes");
		$this->admin_fields_groups = array_merge($this->admin_fields_groups, $wccaf_posts);
	    
	    foreach ($this->admin_fields_groups as $group) {
	        
	        if (count($group["fields"]) > 0) {
	            foreach ($group["fields"] as $field) {
	                /* If all checkbox is unchecked then the fields itself won;t be presented in the REQUEST object
	                 * But we need to clear the existing meta for checkbox field */
	                if (isset($_REQUEST[$field["key"]][$_i])) {
	                    $this->persist($_variant_id, $field, $_REQUEST[$field["key"]][$_i], "variable");
	                } else if (!isset($_REQUEST[$field["key"]]) && $field["type"] == "checkbox") {
	                    $this->persist($_variant_id, $field, array(), "variable");
	                }
	            }
	        }
	        
	    }
	    
	}
	
	private function persist($_id, $_meta, $_val, $_type) {
	    $_val = is_array($_val) ? implode(",", $_val) : $_val;
	    if ($_type != "cat") {    	
	        update_post_meta($_id, $_meta["key"], $_val);
	    } else {
	        update_option("taxonomy_product_cat_". $_id . $_meta["key"], $_val);
	    }
	}

    /**
	 *  
	 * add wcff related assets
	 *  
	 */
	public function wcff_load_client_assets(){
		wcff()->injector->enqueue_client_side_assets();		
    }

    /**
     * To check admin page is
     * @param $scr_id: string
     * @return boolean
     */
    private function check_screen( $scr_id ) {
		if( $scr_id == "wccpf-options" ) {
			return ( ( get_current_screen() -> id == "wccpf" ) || ( get_current_screen() -> id == "wccaf" ) || ( get_current_screen() -> id == "wcccf" ) || get_current_screen() -> id == "wccpf-options" );
		}
		return get_current_screen() -> id == $scr_id;
    }
    
    /**
     * Insert admin asserts
     * 
     */
    public function enqueue_admin_assets() {
		if ($this->check_screen("product") || $this->check_screen("shop_order") || $this->check_screen("edit-product_cat")) {
			wp_register_style( 'wccaf-spectrum-css', esc_url(wcff()->info['dir']) . 'assets/css/spectrum.css' );
			wp_register_style( 'wccaf-timepicker-css', esc_url(wcff()->info['dir']) . 'assets/css/jquery-ui-timepicker-addon.css' );
			wp_enqueue_style( 'wccaf-spectrum-css' );
			wp_enqueue_style( 'wccaf-timepicker-css' );
			wp_register_script( 'wccaf-color-picker', esc_url(wcff()->info['dir']) . 'assets/js/spectrum.js' );
			wp_enqueue_script( 'wccaf-color-picker' );
			/* Wordpress by default won't enqueue datepicker script on Taxonomy pages */
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-core' );
			wp_enqueue_script( 'jquery-ui-datepicker' );			
			wp_register_script( 'wccaf-datepicker-i18n', esc_url(wcff()->info['dir']) . 'assets/js/jquery-ui-i18n.min.js' );
			wp_register_script( 'wccaf-datetime-picker', esc_url(wcff()->info['dir']) . 'assets/js/jquery-ui-timepicker-addon.min.js' );
			wp_enqueue_script( 'wccaf-datetime-picker' );
			wp_enqueue_script( 'wccaf-datepicker-i18n' );
		}	
	}
    

    /**
	 * 
	 * Add html custom attributes list for sanitization
	 * 
	 * @return void|boolean
	 *   
	 */
	private function add_wcff_html_attributes() {
		global $allowedposttags;
		if (isset($allowedposttags)) {
			if (isset($allowedposttags["li"])) {
				$allowedposttags["li"]["data-itemkey"] = true;
				$allowedposttags["li"]["data-productid"] = true;
				$allowedposttags["li"]["data-fieldname"] = true;
				$allowedposttags["li"]["data-field"] = true;
			}
			if (isset($allowedposttags["div"])) {
				$allowedposttags["div"]["data-cloneable"] = true;
			}
		}
	}
    
    public function admin_product_template_fields_injector( $_term = null ) {

		global $post;
		
		if ($post != null && ($post->post_type == "wccpf" || $post->post_type == "wccaf" || $post->post_type == "wccvf" || $post->post_type == "wcccf")) {
		    return;
		}
		
		$this->location = current_action();				
				
        if ($this->check_screen("product") || $this->check_screen("edit-product_cat")) {

			$this->admin_fields_groups = wcff()->dao->load_fields_groups_for_product((($post) ? $post->ID : 0), 'wccaf', "admin", $this->location);
            
            /* Inject the custom fields into the single product page */
            if ($this->check_screen("edit-product_cat")) {
                $this->inject_wccaf($_term);
            } else {
                $this->inject_wccaf();
			}
			
			if ($this->location == "product_cat_add_form_fields") {
				/* Form clearance script */
				$this->wccaf_product_cat_form_clear();
			}
			
		} else if ($this->check_screen("shop_order")) {
			
			/* Check for shop order screen - to inject fields for order view */		
			$this->prepare_field_for_order_view();
			$this->inject_wccaf();
						
		}  
		
    }

	public function custom_order_fields_on_order_email($_fields, $_sent_to_admin, $_order) {

		if (empty($_fields) || !is_array($_fields)) {
			$_fields = array();
		}

		/* Update the location property manually */
		$this->location = "admin_head-post.php";
		$custom_fields = $this->prepare_field_for_order_view($_order->get_id(), true);

        foreach( $custom_fields as $group ) {
				
			if (count($group["fields"]) > 0) {
				foreach ($group["fields"] as $field) { 
					if (is_array($field) && isset($field["label"]) && isset($field["key"])) {
						$send = isset($field["email_meta"]) ? $field["email_meta"] : "yes";
						if ($send == "yes") {
							$_fields[] = array(
								"label" => $field["label"],
								"value" => get_post_meta($_order->get_id(), $field["key"], true)
							);
						}   
					}
				}

			}
		}
        
        return $_fields;

    }

	public function custom_order_fields_on_customer_order_view($_order) {

		/* Update the location property manually */
		$this->location = "admin_head-post.php";
		$custom_fields = $this->prepare_field_for_order_view($_order->get_id(), true);

		$notEMpty = false;
		$html = '<div class="wcff-checkout-custom-order-fields">';
		foreach( $custom_fields as $group ) {				
			if (count($group["fields"]) > 0) {
				foreach ($group["fields"] as $field) { 
					if (is_array($field) && isset($field["label"]) && isset($field["key"])) {
						$send = isset($field["email_meta"]) ? $field["email_meta"] : "yes";						
				        if ($send == "yes") {
            				$html .= '<p><strong>'. esc_html($field["label"]) .': </strong> '. esc_html(get_post_meta($_order->get_id(), $field["key"], true)) .'</p>';
						}
						$notEMpty = true;
					}
				}
			}		
		}
		$html .= '</div>';

        if ($notEMpty) {
            echo $html;
        }

	}

	private function prepare_field_for_order_view($_post_id = 0, $_return = false) {	

		$target_products = array();

		if ($_post_id != 0) {
			$post_id = $_post_id;
		} else {
			global $post;
			$post_id = $post->ID;
		}		
		
		/* Check for shop order screen - to inject fields for order view */		
		if ($post_id > 0) {

			$order = wc_get_order($post_id);
			if ($order) {
				foreach ($order->get_items() as $key => $item) {
					if (isset($item["product_id"])) {
						$target_products[] = $item["product_id"];
					}
					if (isset($item["variation_id"]) && !empty($item["variation_id"]) && $item["variation_id"] != 0) {
						$target_products[] = $item["variation_id"];
					}
				}
			}
			/* Now get the fields */
			$groups = array();
			foreach($target_products as $pid) {
				$gs = wcff()->dao->load_fields_groups_for_product($pid, 'wccaf', "admin", $this->location);				
				$gs = $this->remove_duplicate_groups($groups, $gs);
				$groups = array_merge($groups, $gs);
			}

			if ($_return) {
				return $groups;
			}
			$this->admin_fields_groups = $groups;
			
		}		

	}

	private function remove_duplicate_groups($_groups, $_gs) {

		$indexes = array();
		foreach ($_groups as $group) {
			foreach ($_gs as $i => $g) {
				if ($group["id"] == $g["id"]) {
					$indexes[] = $i;	
				}
			}
		}

		foreach ($indexes as $i) {
			unset($_gs[$i]);
		}

		return $_gs;

	}

    private function inject_wccaf($_term = null) {
		
        global $post;			
			
		if ($this->location != "admin_head-post.php") {
			
			do_action('wccaf_before_fields_start');

			foreach( $this->admin_fields_groups as $group ) {
				
			    if (count($group["fields"]) > 0) {
			        foreach ($group["fields"] as $field) { 
			            
			            $field["location"] = $this->location;
			            
			            /*
			             * This is not necessary here, but variation fields have some issues, so we have to do this in all places
			             * Since CSS class name connot contains special characters especially [ ]
			             *  
			             **/
						if ($field["type"] == "colorpicker") {                       
							$this->color_fields[] = $field;                
							$field["admin_class"] = $field["key"];
						}
						if ($field["type"] == "datepicker") {
							$this->date_fields[] = $field;
							$field["admin_class"] = $field["key"];
						}
			             
			             /* Retrive the value for this field */
			             $field["value"] = $this->determine_field_value($field, (($_term != null && isset($_term->term_id)) ? $_term->term_id : (($post) ? $post->ID : 0)));
			             
			             do_action('wccaf_before_field_start', $field);
			             
						 /* This is neccessary for name and fkey attr */
						 $field["name"] = $field["key"];

			             /* generate html for wccaf fields */
			             echo wcff()->builder->build_admin_field($field);
			             
			             do_action('wccaf_after_field_end', $field);
			            
			             if ($field["type"] == "image") {
			                 $this->is_image_field_there = true;
			             }
			             
			        }
			    }
			    
			}
			
			do_action('wccaf_after_fields_end');	
			
		} else {
			$added = false;
			$location_group = wcff()->dao->load_all_wccaf_location_rules();
			foreach ($location_group as $lrule) {				
				if ($lrule["context"] == "location_product" || $lrule["context"] == "location_order" || $lrule["context"] == "location_product_cat") {	
					$title = "";				    
				    if ($this->has_any_fields_to_render($this->admin_fields_groups)) {
						if ($this->check_screen("product")) {
							$title = "Product Options";
						} else if ($this->check_screen("edit-product_cat")) {
							$title = "Product Category Options";
						} else if ($this->check_screen("shop_order")) {
							$title = "Order Options";
						}
				        add_meta_box('wccaf_meta_box', $title, array($this, "inject_wccaf_meta_box"), get_current_screen() -> id, $lrule["endpoint"]["context"], $lrule["endpoint"]["priority"], array('fields' => $this->admin_fields_groups, 'location' => $this->location, "term" => $_term));
				        $added = true;
				        break;
				    }				    
				}				
				if ($added) {
					break;
				}
			}
		}
	}
	
	public function inject_wccaf_meta_box($_post, $_margs) {		
	    
	    if (isset($_margs["args"]["fields"])) {
	        
	        do_action('wccaf_before_fields_start');
	        
	        foreach ($_margs["args"]["fields"] as $group) {
	            
	            if (count($group["fields"]) > 0) {
	                foreach ($group["fields"] as $field) { 
	                    
	                    $field["location"] = $_margs["args"]["location"];	                    
	                    /*
	                     * This is not necessary here, but variation fields have some issues, so we have to do this in all places
	                     * Since CSS class name connot contains special characters especially [ ] */	                    
						if ($field["type"] == "colorpicker") {                       
							$this->color_fields[] = $field;                
							$field["admin_class"] = $field["key"];
						}
						if ($field["type"] == "datepicker") {
							$this->date_fields[] = $field;
							$field["admin_class"] = $field["key"];
						}
	                    
	                    /* Retrive the value for this field */
	                    if ($_margs["args"]["location"] != "product_cat_edit_form_fields") {
	                        $field["value"] = $this->determine_field_value($field, $_post->ID);
	                    } else {
	                        if (isset($_margs["args"]["term"]) && $_margs["args"]["term"]->term_id) {
	                            $field["value"] = $this->determine_field_value($field, $_margs["args"]["term"]->term_id);
	                        } else {
	                            $field["value"] = ($field["type"] != "checkbox") ? "" : array();
	                        }
	                    }
	                    
	                    do_action('wccaf_before_field_start', $field);
	                    
						/* This is neccessary for name and fkey attr */
						$field["name"] = $field["key"];

	                    /* generate html for wccaf fields */
	                    echo wcff()->builder->build_admin_field($field);
	                    
	                    do_action('wccaf_after_field_end', $field);
	                    
	                    if ($field["type"] == "image") {
	                        $this->is_image_field_there = true;
	                    }
	                    
	                }
	            }          
	        }
	        
	        do_action('wccaf_after_fields_end');
	        
	    }
	    
	}
	
	public function inject_wccaf_on_product_variable_section($_loop, $variation_data, $_variation) {
	    global $post;
	    $this->location = "woocommerce_product_after_variable_attributes";	   
	    $this->admin_fields_groups = wcff()->dao->load_fields_groups_for_product((($post) ? $post->ID : 0), 'wccaf', "admin", $this->location);

		$wccvf_posts = wcff()->dao->load_fields_groups_for_product($_variation->ID, 'wccaf', "admin", $this->location);
		$this->admin_fields_groups = array_merge( $this->admin_fields_groups, $wccvf_posts);     
	    
	    do_action('wccaf_before_fields_start');
	    
	    foreach( $this->admin_fields_groups as $group ) {
	        
	        if (count($group["fields"]) > 0) {
	            foreach ($group["fields"] as $field) {
	                
	                $field["location"] = $this->location;
	                /* Since CSS class name connot contains special characters especially [ ] */	                
	                if ($field["type"] == "colorpicker") {                       
						$this->color_fields[] = $field;                
						$field["admin_class"] = $field["key"];
					}
					if ($field["type"] == "datepicker") {
						$this->date_fields[] = $field;
						$field["admin_class"] = $field["key"];
					}

	                /* Retrive the value for this field */
	                $field["value"] = $this->determine_field_value($field, $_variation->ID);
	                
	                /* Prepare the name property */	                					
					$field["name"] = $field["key"] ."[". $_loop ."]";
	                
	                do_action('wccaf_before_field_start', $field);
	                
	                /* generate html for wccaf fields */
	                echo wcff()->builder->build_admin_field($field);
	                
	                do_action('wccaf_after_field_end', $field);	                
	         
	                if ($field["type"] == "image") {
	                    $this->is_image_field_there = true;
	                }
	                
	            }
	        }
	    }
	
	    do_action('wccaf_after_fields_end');	    
	    
	}
	
	/**
	 * To get admin field value for product overview
	 */
	private function determine_field_value($_meta, $_id = 0) {	    
	    $mval = false;
	    $meta_exist = false; 
	    if ($_meta["location"] != "product_cat_edit_form_fields") {
	    	if (metadata_exists("post", $_id, $_meta["key"])) {
	    	   	$meta_exist = true;
	    		/* Well get the value */
	            $mval = get_post_meta($_id, $_meta["key"], true);	            
	            /* Incase of checkbox - the values has to be deserialzed as Array */
	            if ($_meta["type"] == "checkbox" && is_string($mval)) {
	                $mval = explode(',', $mval);
	            }
	    	} else {	            
	            /* This will make sure the following section fill with default value instead */
	            $mval = false;	            
	        }
	    } else {
	        $mval = get_option("taxonomy_product_cat_". $_id . $_meta["key"]);
	        /* Incase of checkbox - the values has to be deserialzed as Array */
	        if ($_meta["type"] == "checkbox" && is_string($mval)) {
	            $mval = explode(',', $mval);
	        }
	    }
	    /* We can trust this since we never use boolean value for any meta
	     * instead we use 'yes' or 'no' values */	    
	    if ( $meta_exist == false && $mval == false ) {
	        /* Value is not there - probably this field is not yet saved */
	        if ($_meta["type"] == "checkbox") {
	            $d_choices = array();
	            if (is_array($_meta["default_value"])) {
	                $d_choices = $_meta["default_value"];
	            } else {
	                if ($_meta["default_value"] != "") {
	                    $choices = explode(";", $_meta["default_value"]);
	                    foreach ($choices as $choice) {
	                    	$d_value = explode("|", $choice);
	                    	$d_choices[] = $d_value[0];
	                    }
	                }
	            }	            	            
	            $mval = $d_choices;
	        } else if ($_meta["type"] == "radio" || $_meta["type"] == "select") {
	            $mval = "";
	            if (isset($_meta["default_value"]) && $_meta["default_value"] != "") {
	            	$d_value = explode("|", $_meta["default_value"]);
	            	$mval = $d_value[0];
	            }
	        } else {
	            /* For rest of the fields - no problem */
	        	$mval = isset($_meta["default_value"]) ? $_meta["default_value"] : "";
	        }
	    }
	    
	    if ( $meta_exist && ( $mval == false || $mval == null || $mval == "") && $_meta["type"] == "checkbox" ) {
	    	$mval = array();
	    }	    
	   
	    return $mval;
	}

	/**
	 *
	 * @param object $_groups
	 * @return boolean
	 *
	 */
	private function has_any_fields_to_render($_groups) {
	    $flaQ = false;
	    foreach ($_groups as $group) {
	        if (isset($group["fields"]) && count($group["fields"]) > 0) {
	            $flaQ = true;
	            break;
	        }
	    }
	    return $flaQ;
	}
	
    public function wccaf_back_end_enqueue_scripts() {
		
		/* 
		 * Check whether this is for Variable Product Parent page
		 * This means we need to put fields meta for date & color picker for variable fields
		 **/
		
		global $post;
		if ($post) {
			$product = wc_get_product($post->ID);
			if ($product && $product->is_type('variable')) {
				$variations = $product->get_available_variations();			
				$wccaf_posts = wcff()->dao->load_fields_groups_for_product($post->ID, 'wccaf', "admin", "woocommerce_product_after_variable_attributes");
				if (is_array($variations)) {

					foreach ($variations as $variation) {
						$wccvf_posts = wcff()->dao->load_fields_groups_for_product($variation["variation_id"], 'wccaf', "admin", "woocommerce_product_after_variable_attributes");
						$wccaf_posts = array_merge($wccaf_posts, $wccvf_posts);
					}

					foreach($wccaf_posts as $group) {	        
						if (count($group["fields"]) > 0) {
							foreach ($group["fields"] as $field) {
								
								$field["location"] = $this->location;
								/* Since CSS class name connot contains special characters especially [ ] */	                
								if ($field["type"] == "colorpicker") {                       
									$this->color_fields[] = $field;                									
								}
								if ($field["type"] == "datepicker") {
									$this->date_fields[] = $field;								
								}

								if ($field["type"] == "image") {
									$this->is_image_field_there = true;
								}

							}
						}
					}

				}
			}			
		}

		$date_bucket = array();
	    $color_bucket = array();	

		foreach ($this->color_fields as $field) {                               
            $picker_meta = array();
            $picker_meta["color_format"] = isset($field["color_format"]) ? $field["color_format"] : "hex";
            $picker_meta["default_value"] = isset($field["default_value"]) ? $field["default_value"] : "#000";
            $picker_meta["show_palette_only"] = $field["show_palette_only"];
                
            if (isset($field["palettes"]) && $field["palettes"] != "") {
                $picker_meta["palettes"] = explode(";", $field["palettes"]);
            }	                    
            if (isset($field["color_image"]) && is_array($field["color_image"])) {
                $picker_meta["color_image"] = $field["color_image"];
			}	     
			
			if(isset($field["color_text_field"])){
				$picker_meta["color_text_field"] = $field["color_text_field"];
			}
            
            if (!empty($picker_meta)) {
                $color_bucket[$field["key"]] = $picker_meta;
            }       
        }

        foreach ($this->date_fields as $field) {
            $picker_meta = array();
            
            $localize = "none";
            $year_range = "-10:+10";            
            if (isset($field["language"]) && !empty($field["language"]) && $field["language"] != "default") {
                $localize = esc_attr($field["language"]);
            }
            if (isset($field["dropdown_year_range"]) && !empty($field["dropdown_year_range"])) {
                $year_range = esc_attr($field["dropdown_year_range"]);
            }
            
            /* Determine the current locale */
            $current_locale = wcff()->locale->detrmine_current_locale();
            /*If admin hadn't set locale, then try to determine */
            $localize = ($localize == "none") ? $current_locale : $localize;
            
            $picker_meta["localize"] = $localize;
            $picker_meta["year_range"] = $year_range;
            //$picker_meta["admin_class"] = $field["admin_class"];

            if (isset($field["date_format"]) && $field["date_format"] != "") {
                $picker_meta["dateFormat"] = wcff()->builder->convert_php_jquery_datepicker_format(esc_attr($field["date_format"])) ."'";
            } else {
                $picker_meta["dateFormat"] = wcff()->builder->convert_php_jquery_datepicker_format("d-m-Y") ."'";
            }	
            
            $picker_meta["field"] = $field;            
            if (!empty($picker_meta)) {
                $date_bucket[$field["key"]] = $picker_meta;
            } 
        }

		?>

		<script type="text/javascript">				

			var wcff_date_picker_meta = <?php echo wp_json_encode($date_bucket); ?>;
			var wcff_color_picker_meta = <?php echo wp_json_encode($color_bucket); ?>;            		

			(function($) {

				<?php if (!empty($this->date_fields)) : ?>

				/**
				 * 
				 * Datepicker init handler
				 * 
				 */
				$(document).on("focus", "input.wccaf-datepicker", function() {
					/* Fields key used to get the meta */
					var m, d, y,
						config = {},
						meta = null,
						hours = [],
						minutes = [],
						hour_min = [],
						weekenddate = null,
						currentdate = null,
						disableDates = "",
						allowed_dates = "",			
						fkey = $(this).attr("data-fkey");

					/* Make sure the datepicker has meta */
					if (!wcff_date_picker_meta || !wcff_date_picker_meta[fkey]) {
						return;
					}

					meta = wcff_date_picker_meta[fkey];					
						
					/* Set localize option */
					if (typeof $ != "undefined" && typeof $.datepicker != "undefined") {
						if (meta["localize"] != "none" && meta["localize"] != "en") {
							$.datepicker.setDefaults($.extend({}, $.datepicker.regional[meta["localize"]]));
						} else {
							$.datepicker.setDefaults($.extend({}, $.datepicker.regional["en-GB"]));
						}
					}
					
					/* Check for timepicker */
					if (meta["field"]["timepicker"] && meta["field"]["timepicker"] === "yes") {
						/* Time picker related config */
						config["controlType"] = "select";
						config["oneLine"] = true;
						config["timeFormat"] = "hh:mm tt";				
						/* Min Max hours and Minutes */
						if (meta["field"]["min_max_hours_minutes"] && meta["field"]["min_max_hours_minutes"] !== "") {
							hour_min = meta["field"]["min_max_hours_minutes"].split("|");
							if (hour_min.length === 2) {
								if (hour_min[0] !== "") {
									hours = hour_min[0].split(":");
									if (hours.length === 2) {
										config["hourMin"] = hours[0];
										config["hourMax"] = hours[1];
									}							
								}
								if (hour_min[1] !== "") {
									minutes = hour_min[1].split(":");
									if (minutes.length === 2) {
										config["minuteMin"] = minutes[0];
										config["minuteMax"] = minutes[1];
									}
								}
							}
						}				
					}
					
					/* Date format */
					config["dateFormat"] = meta["dateFormat"];
					
					if (meta["field"]["display_in_dropdown"] && meta["field"]["display_in_dropdown"] === "yes") {
						config["changeMonth"] = true;
						config["changeYear"] = true;
						config["yearRange"] = meta["year_range"];
					}
					
					if (meta["field"]["disable_date"] && meta["field"]["disable_date"] !== "") {
						if ("future" === meta["field"]["disable_date"]) {
							config["maxDate"] = 0;
						}
						if ("past" === meta["field"]["disable_date"]) {
							config["minDate"] = new Date();
						}
					}
					
					if (meta["field"]["disable_next_x_day"] && meta["field"]["disable_next_x_day"] != ""){
						config["minDate"] = "+'"+ meta["field"]["disable_next_x_day"] +"'d";
					}
					
					if (meta["field"]["allow_next_x_years"] && meta["field"]["allow_next_x_years"] != "" ||
						meta["field"]["allow_next_x_months"] && meta["field"]["allow_next_x_months"] != "" ||
						meta["field"]["allow_next_x_weeks"] && meta["field"]["allow_next_x_weeks"] != "" ||
						meta["field"]["allow_next_x_days"] && meta["field"]["allow_next_x_days"] != "") {
						
						allowed_dates = "";
						if (meta["field"]["allow_next_x_years"] && meta["field"]["allow_next_x_years"] != "") {
							allowed_dates += "+"+ meta["field"]["allow_next_x_years"].trim() +"y ";
						}
						if (meta["field"]["allow_next_x_months"] && meta["field"]["allow_next_x_months"] != "") {
							allowed_dates += "+"+ meta["field"]["allow_next_x_months"].trim() +"m ";
						}
						if (meta["field"]["allow_next_x_weeks"] && meta["field"]["allow_next_x_weeks"] != "") {
							allowed_dates += "+"+ meta["field"]["allow_next_x_weeks"].trim() +"w ";
						}
						if (meta["field"]["allow_next_x_days"] && meta["field"]["allow_next_x_days"] != "") {
							allowed_dates += "+"+ meta["field"]["allow_next_x_days"].trim() +"d";
						}
						config["minDate"] = 0;
						config["maxDate"] = allowed_dates.trim();				
					}
					
					config["onSelect"] = function(dateText) {	
						$(this).trigger("change");						
						$(this).next().hide();
					};
					
					config["beforeShowDay"] = function(date) {
						var i = 0,
							test = "",
							day = date.getDay(),
							disableDays = "",
							disableDateAll = "";
						
						if (meta["field"]["disable_days"] && meta["field"]["disable_days"].length > 0) {				
								day = date.getDay(),
								disableDays = meta["field"]["disable_days"];
							for (i = 0; i < disableDays.length; i++) {
								test = disableDays[i]
								test = test == "sunday" ? 0 : test == "monday" ? 1 : test == "tuesday" ? 2 : test == "wednesday" ? 3 : test == "thursday" ? 4 : test == "friday" ? 5 : test == "saturday" ? 6 : "";
								if (day == test) {									        
									return [false];
								}
							}						
						}
						
						if (meta["field"]["specific_date_all_months"] && meta["field"]["specific_date_all_months"] != "") {			 		
								disableDateAll = meta["field"]["specific_date_all_months"].split(",");			 			
							for (var i = 0; i < disableDateAll.length; i++) {
								if (parseInt(disableDateAll[i].trim()) == date.getDate()){
									return [false];
								}					
							}
						}
						
						if (meta["field"]["specific_dates"] && meta["field"]["specific_dates"] != "") {
							disableDates = meta["field"]["specific_dates"].split(",");
							/* Sanitize the dates */
							for (var i = 0; i < disableDates.length; i++) {	
								disableDates[i] = disableDates[i].trim();
							}
							/* Form the date string to compare */							
							m = date.getMonth();
							d = date.getDate();
							y = date.getFullYear();
							currentdate = ( m + 1 ) + '-' + d + '-' + y ;
							/* Make dicision */	
							if ($.inArray(currentdate, disableDates) != -1) {
								return [false];
							}				
						}	
						
						if (meta["field"]["disable_next_x_day"] && meta["field"]["disable_next_x_day"] != "") {}
						
						if (meta["field"]["weekend_weekdays"] && meta["field"]["display_in_dropdown"] != "") {
							if (meta["field"]["weekend_weekdays"] == "weekdays"){
								//weekdays disable callback
								weekenddate = $.datepicker.noWeekends(date);
								return [!weekenddate[0]];
							} else if (meta["field"]["weekend_weekdays"] == "weekends") {
								//weekend disable callback						
								return $.datepicker.noWeekends(date);
							}
						}	
						
						return [true];
					};
					
					
					if (meta["field"]["timepicker"] && meta["field"]["timepicker"] === "yes") {
						$(this).datetimepicker(config);
					} else {
						$(this).datepicker(config);
					}
				});

				<?php endif; ?>

				<?php if (!empty($this->color_fields)) : ?>

					function init_color_pickers() {
						var i = 0,
							j = 0,
							config = {},
							palette = [],
							keys = Object.keys(wcff_color_picker_meta);
						for (i = 0; i < keys.length; i++) {	
							config = {}
							config["color"] = wcff_color_picker_meta[keys[i]]["default_value"];
							config["preferredFormat"] = wcff_color_picker_meta[keys[i]]["color_format"];			
							if (wcff_color_picker_meta[keys[i]]["palettes"] && wcff_color_picker_meta[keys[i]]["palettes"].length > 0) {				
								config["showPalette"] = true;
								if (wcff_color_picker_meta[keys[i]]["show_palette_only"] == "yes") {
									config["showPaletteOnly"] = true;
								}
								
								for (j = 0; j < wcff_color_picker_meta[keys[i]]["palettes"].length; j++) {
									palette.push(wcff_color_picker_meta[keys[i]]["palettes"][j].split(','));
								}
								config["palette"] = palette;
							}			

							if( wcff_color_picker_meta[keys[i]]["show_palette_only"] != "yes" && wcff_color_picker_meta[keys[i]]["color_text_field"] == "yes") {
								config["showInput"] = true;
							}

							$("input.wccaf-color-"+ keys[i]).spectrum(config);
						}
					}

					$(document).ready(function() { init_color_pickers(); });
					$(document).on("woocommerce_variations_loaded", function() { init_color_pickers(); });

				<?php endif; ?>

				<?php if ($this->is_image_field_there) : ?>

				$( document ).on( "click", ".wcff_upload_image_button", function() {
					
					var btn = $( this );
					var ifield = btn.parent().prev().prev().prev();
					var ufield = btn.parent().prev();
					var pfield = btn.closest(".wccaf-image-field-wrapper");				

					let wcff_media_uploader = wp.media.frames.file_frame = wp.media({
						title: btn.data( 'uploader_title' ),					  	
						multiple: false
					});

					wcff_media_uploader.on( 'select', function() {
						var attachment = wcff_media_uploader.state().get('selection').first().toJSON();						
						ufield.val( attachment.id );
						if( attachment.sizes["thumbnail"].url != "" ) {
							ifield.attr( 'src',attachment.sizes["thumbnail"].url );
						} else {
							ifield.attr( 'src',attachment.url );
						}
						btn.parent().hide();						
						ifield.show();						
						pfield.removeClass( "has_image" ).addClass( "has_image" );
						pfield.closest("div.woocommerce_variation").addClass("variation-needs-update");
						$("button.save-variation-changes").prop("disabled", false);
					});

					wcff_media_uploader.open();					
					
				});

				$( document ).on( 'click', 'a.wccaf-image-remove-btn', function(e) {
					
					$( this ).next().val( '' );
					$( this ).prev().attr( 'src', '' );
					$( this ).prev().hide();
					$( this ).next().next().show();
					$( this ).closest(".wccaf-image-field-wrapper").removeClass( "has_image" );	
					$( this ).closest("div.woocommerce_variation").addClass("variation-needs-update");
					$("button.save-variation-changes").prop("disabled", false);

					e.preventDefault();
				});

				<?php endif; ?>
				
			})(jQuery);

		</script>
			
		<?php 

		$this->wccaf_fields_validation();

	}
	
	private function wccaf_fields_validation() { ?>
		<script type="text/javascript">

			/* Validation flag */
			var wccaf_is_valid = true;
			
			(function($) {
				
				$( document ).on( "blur", ".wccaf-field", function(e) {
					var me = $(this);	
					setTimeout(function() {
						doValidate( me );
						$("input[name=save]").removeClass("disabled");
						$("input[name=save]").parent().find(".spinner").hide();
					}, 500);														
				});	
				
				$(document).on("submit", "#post", function() {	 
					wccaf_is_valid = true;
					$( ".wccaf-field" ).each(function(){
						/**
						 * If the fields are shown in General Tab, and user tries to add an variable product
						 * in which case the General Tab itself in hidden, so for those knids of reason
						 * its better to check the vivibility of the field before applying validation rules */
						if ($(this).is(":visible")) {
							doValidate( $(this) );
						}						
					});				

					/**
					 * Incase if validation failed then 
					 * Remove the disabled class of the wordpress publish button.
					 * Also hide the spinner icon as well.
					 */
					if (!wccaf_is_valid) {
						$("#publishing-action").find("#publish").removeClass("disabled");
						$("#publishing-action").find("span.spinner").removeClass("is-active");
					}
					/* Return 'true' or 'false' */			
					return wccaf_is_valid;
					//return false;				
				});

				function doValidate( field ) {
					if( field.attr("data-field-type") != "radio" && field.attr("data-field-type") != "checkbox" ) {					
						if( field.attr("data-mandatory") == "yes" ) {						
							if( doPatterns( field.attr("data-pattern"), field.val() ) ) {
								field.parent().find("span.wccaf-validation-message").hide();
							} else {		
								wccaf_is_valid = false;
								field.parent().find("span.wccaf-validation-message").css("display", "block");
								/* Scroll down to this field so that admin can aware that field value is missing */
								$('html,body').animate(
									{ scrollTop: field.parent().offset().top - 50  },
									'slow'
								);
							}
						}
					} else {
						if( field.attr("data-mandatory") == "yes" ) {	
							if( $("input[name="+ field.attr("name") +"]").is(':checked') ) {
								field.parent().find("span.wccaf-validation-message").hide();								
							} else {
								wccaf_is_valid = false;
								field.parent().find("span.wccaf-validation-message").css("display", "block");
								/* Scroll down to this field so that admin can aware that field value is missing */
								$('html,body').animate(
									{ scrollTop: field.parent().offset().top - 50  },
									'slow'
								);
							}	 
						}
					}
				}				
				
				function doPatterns( patt, val ) {
					var pattern = {
						mandatory	: /\S/, 
						number		: /^\d*$/,
						email		: /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i,	      	
					};			    
				    return pattern[ patt ].test(val);	
				}
				
			})(jQuery);
		</script>		
	<?php 
	}
	
	/**
	 * 
	 * Since wordpress term creat form uses Ajax to submit fields
	 * We need to clear our custom fields manualy once the term is submited 
	 * 
	 */
	private function wccaf_product_cat_form_clear() { ?>
	    
	    <script type="text/javascript">
	    (function($) {
	    		$( document ).ajaxComplete( function( event, request, options ) {
				if ( request && 4 === request.readyState && 200 === request.status
					&& options.data && 0 <= options.data.indexOf( 'action=add-tag' ) ) {

					var res = wpAjax.parseAjaxResponse( request.responseXML, 'ajax-response' );
					if ( ! res || res.errors ) {
						return;
					}
					// Clear wccaf fields
					$(".wccaf-field").each(function() {
						if ($(this).attr("wccaf-type") === "text" ||
								$(this).attr("wccaf-type") === "number" ||
								$(this).attr("wccaf-type") === "email" ||
								$(this).attr("wccaf-type") === "hidden" ||
								$(this).attr("wccaf-type") === "textarea" ||
								$(this).attr("wccaf-type") === "select" ||
								$(this).attr("wccaf-type") === "url") {
							$(this).val("");
						} else if($(this).attr("wccaf-type") === "radio" ||
								$(this).attr("wccaf-type") === "checkbox") {
							$(this).prop("checked", false);
						} else if($(this).attr("wccaf-type") === "image") {
							
						}
					});

					$("div.wccaf-image-field-wrapper.has_image").find("input[type=hidden]").val("");
					$("div.wccaf-image-field-wrapper.has_image").find("img").hide();
					$("div.wccaf-image-field-wrapper.has_image").find(".wccaf-img-field-btn-wrapper").show();
					$("div.wccaf-image-field-wrapper.has_image").removeClass("has_image");
					
					return;
				}
			} );
		})(jQuery);	    
	    </script>
	    
	    <?php 
	}

	/**
	 *
	 * @param WC_Product $_product
	 * @return integer
	 *
	 * Wrapper method for getting Wc Product object's ID attribute
	 *
	 */
	private function get_product_id($_product){
		if ($_product) {
			return method_exists($_product, 'get_id') ? $_product->get_id() : $_product->id;
		}
	    return null;
	}
}


new wcff_admin_fields();