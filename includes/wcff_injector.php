<?php 

if (!defined('ABSPATH')) { exit; }

/**
 * 
 * @author 	    : Saravana Kumar K
 * @copyright 	: Sarkware Research & Development (OPC) Pvt Ltd
 *
 */

class wcff_injector {
	
	/* Current product object 
	 * The product that is being viewed by the user */
	private $product = null;
	
	/**/
	private $variation_id = null;
	/* Fields group index property */
	private $group_index = 1;
	/* Global cloning property */
	private $is_cloning_enabled = "no";
	/* Multilingual property */
	private $is_multilingual_enabled = "no";
	/* Holds the Product Field Groups list */
	private $product_field_groups = null;
	/* Holds the Admin Field Groups list */
	private $admin_field_groups = null;
	/* Total color picker instance count */
	private $color_picker_count = 0;	
	/* Used to determine whether the cloning hidden count field injected or not */
	private $cloning_helper_input_inserted = false;
	/* Holds the meta list of all the date fields that is being injected */
	public $date_fields = array();
	/* Holds the meta list of all the color fields that is being injected */
	public $color_fields = array();	
	/* Holds the field rules list of all the fields that is being injected */
	private $fields_rules = array();
	/* Holds the pricing rules list of all the fields that is being injected */
	private $pricing_rules = array();
		
	/* Default constructor */
	public function __construct() {}
	
	public function inject_product_fields($_location, $_template = 'single-product') {
		
		Global $product;
		
		$cloning_title = "";		
		$this->product = $product;		
		$this->variation_id = null;
		
		$product_id = $this->get_product_id($this->product);
		$wcff_options = wcff()->option->get_options();

		$is_admin_module_enabled = isset($wcff_options["enable_admin_field"]) ? $wcff_options["enable_admin_field"] : "yes";
		$this->is_cloning_enabled = isset($wcff_options["fields_cloning"]) ? $wcff_options["fields_cloning"] : "no";
		$this->is_multilingual_enabled = isset($wcff_options["enable_multilingual"]) ? $wcff_options["enable_multilingual"] : "no";
		
		if (isset($wcff_options["global_cloning_title"]) && $wcff_options["global_cloning_title"] != "") {
		    $cloning_title = $wcff_options["global_cloning_title"];
		} else {
		    $cloning_title = "Additional Options";
		}
		
		/* Translate cloning title - if multilingual option enabled */
		if ($this->is_multilingual_enabled == "yes") {
			$current_locale = wcff()->locale->detrmine_current_locale();
			if ($current_locale != "en" && isset($wcff_options["global_cloning_title_". $current_locale]) && ! empty($wcff_options["global_cloning_title_". $current_locale])) {
			    $cloning_title = $wcff_options["global_cloning_title_". $current_locale];
			}
		}
		
		/* Let other plugins change the Cloning Title */
		if (has_filter('wcff_cloning_fields_group_title')) {
		    $cloning_title = apply_filters('wcff_cloning_fields_group_title', $cloning_title);
		}
		
		$this->product_field_groups = wcff()->dao->load_fields_groups_for_product($product_id, 'wccpf', $_template, $_location);
		$any_product_fields_to_render = $this->has_any_fields_to_render($this->product_field_groups);

		$this->admin_field_groups = array();
		$any_admin_fields_to_render = false;
		if ($is_admin_module_enabled == "yes") {
			$this->admin_field_groups = wcff()->dao->load_fields_groups_for_product($product_id, 'wccaf', $_template, $_location);	
			$any_admin_fields_to_render = $this->has_any_fields_to_render($this->admin_field_groups);
		}		
		
		do_action('wccpf_before_render_start', $_location, $_template);
				
		/* Inject label field - whichever comes at top */
		$this->handle_label_field("beginning");
		
		if ($any_product_fields_to_render) {		  
		    $this->fields_render_loop($this->product_field_groups, $_location, $cloning_title);		    
		}	
		
		if ($any_admin_fields_to_render) {
		    $this->fields_render_loop($this->admin_field_groups, $_location, $cloning_title);
		}
		
		/* Inject label field - whichever comes at top */
		$this->handle_label_field("end");
		
		do_action('wccpf_after_render_end', $_location, $_template);	
		
		/* Store the template in session, used later in validation */
		if (WC()->session) {
		    WC()->session->set("wcff_current_template", $_template);
		}	
		
		/* If the location is "woocommerce_single_product_tab" and there is no fields to render then make sure the tab is hidden */
		if ($_location == "woocommerce_single_product_tab" && !$any_product_fields_to_render && !$any_admin_fields_to_render) {
			echo '<style>li.wccpf_fields_tab_tab {display: none !important;}</style>';
		}
		
	}
	
	public function inject_placeholder_for_variation_fields() {
	    
	    Global $product;
	    $product_id = $this->get_product_id($product);

	    if ($product->is_type('variable')) {  
			
			wcff()->dao->check_product_for_variation_mappings($product_id, 'wccpf');
			wcff()->dao->check_product_for_variation_mappings($product_id, 'wccaf');
			wcff()->dao->check_product_for_variation_mappings($product_id, 'wccvf');
	        
	        /* By executing above two statement, it will set the 'has_variable_tab_fields' flaq on the dao module */	        
	        if (wcff()->dao->has_variable_tab_fields) {
	            echo '<div id="wcff-variation-fields" class="wcff-variation-fields" data-area="'. esc_attr(current_action()) .'"></div>';
	        }

	    }
	    
	}
	
	/**
	 * 
	 * @param integer $_variation_id
	 * @return string
	 * 
	 */
	public function inject_variation_fields($_variation_id) {
	    
	    $html = '';
	    $cloning_title = '';
	    $this->variation_id = $_variation_id;
	    $wcff_options = wcff()->option->get_options();
	    
	    if (isset($wcff_options["global_cloning_title"]) && $wcff_options["global_cloning_title"] != "") {
	        $cloning_title = $wcff_options["global_cloning_title"];
	    } else {
	        $cloning_title = "Additional Options";
	    }

		/* Translate cloning title - if multilingual option enabled */
		if ($this->is_multilingual_enabled == "yes") {
			$current_locale = wcff()->locale->detrmine_current_locale();
			if ($current_locale != "en" && isset($wcff_options["global_cloning_title_". $current_locale]) && ! empty($wcff_options["global_cloning_title_". $current_locale])) {
			    $cloning_title = $wcff_options["global_cloning_title_". $current_locale];
			}
		}
		
		/* Let other plugins change the Cloning Title */
		if (has_filter('wcff_cloning_fields_group_title')) {
		    $cloning_title = apply_filters('wcff_cloning_fields_group_title', $cloning_title);
		}
	    
	    $this->is_cloning_enabled = isset( $wcff_options["fields_cloning"] ) ? $wcff_options["fields_cloning"] : "no";
	    $this->is_multilingual_enabled = isset($wcff_options["enable_multilingual"]) ? $wcff_options["enable_multilingual"] : "no";

		$is_admin_module_enabled = isset($wcff_options["enable_admin_field"]) ? $wcff_options["enable_admin_field"] : "yes";
		$is_variable_module_enabled = isset($wcff_options["enable_variable_field"]) ? $wcff_options["enable_variable_field"] : "yes";
	    	    
	    
		$wccpf_posts = wcff()->dao->load_fields_groups_for_product($_variation_id, 'wccpf', "variable", "any", false);
	    if ($this->has_any_fields_to_render($wccpf_posts)) {
	        $html .= $this->fields_render_loop($wccpf_posts, "any", $cloning_title, false);
	    }

		if ($is_admin_module_enabled == "yes") {
			$wccaf_posts = wcff()->dao->load_fields_groups_for_product($_variation_id, 'wccaf', "variable", "any", true);
			if ($this->has_any_fields_to_render($wccaf_posts)) {
				$html .= $this->fields_render_loop($wccaf_posts, "any", $cloning_title, false);
			}
		}		    

		if ($is_variable_module_enabled == "yes") {
			$wccvf_posts = wcff()->dao->load_fields_groups_for_product($_variation_id, 'wccvf', "any", "any", false);
			if ($this->has_any_fields_to_render($wccvf_posts)) {
				$html .= $this->fields_render_loop($wccvf_posts, "any", $cloning_title, false);
			}
		}		

		$variation = wc_get_product($_variation_id);
		if ($variation) {
			$this->product = wc_get_product($variation->get_parent_id());
		}		
	    
	    return array(
	        "html" => $html,
	        "meta" => $this->enqueue_wcff_client_side_meta(false)
	    );	    
	    
	}
	
	/**
	 * 
	 * @param array $_groups
	 * @param string $_location
	 * @param string $cloning_title
	 * @param boolean $_echo
	 * @return string
	 * 
	 */
	private function fields_render_loop($_groups, $_location, $cloning_title, $_echo = true) {

	    /* Start of the global container */
	    $html = '<div class="wccpf-fields-container '. esc_attr($_location) .'">';
	    
	    foreach ($_groups as $group) {
	        if (count($group["fields"]) > 0) {
	            

				/* Fill out the V4 properties with default value */
				if (!isset($group["is_clonable"])) {
					$group["is_clonable"] = "yes";
				}	
				if (!isset($group["show_title"])) {
					$group["show_title"] = "no";
				}			
				if (!isset($group["label_alignment"])) {
					$group["label_alignment"] = "left";
				}				

	            do_action('wccpf_before_group_render_start', $_location, $group);
	            
	            /* Start of the group wrapper */
	            $html .= '<div class="wccpf-fields-group-container">';
	            
	            /* Check for the cloning */
	            if ($this->is_cloning_enabled == "yes" && $group["is_clonable"] == "yes") {
	                /* Start of the cloning container */
	                $html .= '<div class="wccpf-fields-group-clone-container">';
	            }
	            
	            $show_group_index = apply_filters("wccpf_display_group_index_on_cloning", true);
	            
	            /* Check for the group title */
	            if ($group["show_title"] == "yes") {
	                $html .= '<h4 class="wccpf-group-title-h4">'. esc_html($group["title"]);
	                if ($this->is_cloning_enabled == "yes" && $group["is_clonable"] == "yes") {	                    
	                    if ($show_group_index) {
	                        $html .= ' <span class="wccpf-fields-group-title-index">1</span>';
	                    }	                    
	                }
	                $html .= '</h4>';
	            } else {
	                if ($this->is_cloning_enabled == "yes" && $group["is_clonable"] == "yes") {
	                    $html .= '<h4 class="wccpf-group-title-h4">'. esc_html($cloning_title);
	                    if ($show_group_index) {
	                        $html .= ' <span class="wccpf-fields-group-title-index">1</span>';
	                    }	
	                    $html .= '</h4>';	                   
	                }
	            }
	            
	            /* Inject the fields */
	            if ($group["use_custom_layout"] == "no") {
	                $html .= $this->render_product_fields($group);
	            } else {
	                $html .= $this->render_product_fields_with_custom_layout($group);
	            }
	            
	            if ($this->is_cloning_enabled == "yes" && $group["is_clonable"] == "yes") {
	                /* End of cloning container */
	                $html .= '</div>';
	            }
	            
	            /* End of the group wrapper */
	            $html .= '</div>';
	            
	            do_action('wccpf_after_group_render_end', $_location, $group);
	        }
	    }
	    
	    if ($this->is_cloning_enabled == "yes" && !$this->cloning_helper_input_inserted) {
	        $html .= '<input type="hidden" id="wccpf_fields_clone_count" value="1" />';
	        $this->cloning_helper_input_inserted = true;
	    }
	    
	    /* End of the global container */	   
	    $html .= '</div>';
	    
	    if ($_echo) {
	        echo $html;
	    } else {
	        return $html;
	    }
	}
	
	/**
	 * 
	 * @param array $_group
	 * @return string
	 * 
	 */
	private function render_product_fields($_group) {
	    $pHtml = "";	    
	    if (count($_group["fields"]) > 0) {	        
	        $pHtml = '<div class="wcff-fields-group" data-custom-layout="'. esc_attr($_group["use_custom_layout"]) .'" data-group-clonable="'. esc_attr($_group["is_clonable"]) .'">';	        
	        foreach ($_group["fields"] as $field) {                
                if (!isset( $field["type"] )){
                    continue;
                }
                if ($field["type"] == "label" && $field["position"] != "normal") {
                    continue;
                }
                if ($this->is_multilingual_enabled == "yes") {
                    /* Localize field */
                    $field = wcff()->locale->localize_field($field);
                }
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

                if (WC()->session && WC()->session->__isset("wcff_validation_failed")) {
                    /* Last add to cart operation failed
                     * Try to restore the fields old value */
                    $index = "";
                    if ($this->is_cloning_enabled == "yes" && $_group["is_clonable"] == "yes") {
                        $index= "_1";
                    }
                    if (isset($_REQUEST[$field["key"] . $index])) {
                        $field["default_value"] = $_REQUEST[$field["key"] . $index];
                    }
                    
                    /* Reset the validation failed flaq */
                    WC()->session->__unset("wcff_validation_failed");
                }
                
                /* Put value for admin and variable fields */
                if ($_group["type"] == "wccaf") {                    
                    
                    /* Also check for the 'show_with_value' option, - this option included after 4.0.0 */
                    if (!isset($field["show_with_value"])) {
                        $field["show_with_value"] = "yes";
                    }  

                    if ($field["show_with_value"] == "yes") {

						$value = "";

                        /* Retrive the value (set by admin) */
						if ($this->variation_id) {
							$value = get_post_meta($this->variation_id, $field["key"], true);
						}
						/* Fix for parent of varaible fields */                            
						if (!$value || $value == "") {
							$value = get_post_meta($this->get_product_id($this->product), $field["key"], true);
						}
                        
						if (!$value && isset($field["default_value"])) {
							$value =  $field["default_value"];
						}
                        
                        /* Show the field with value */
                        if ($field["type"] != "checkbox") {
                            $field["value"] = $value;
                        } else {
							if ($value && is_string($value)) {
								$field["value"] = explode(',', $value);								
							}                            
                        }   

                    }  
					
					$hide_field_when_no_value = isset($field["hide_when_no_value"]) ? $field["hide_when_no_value"] : "yes";					
					if ($hide_field_when_no_value == "yes" && isset($field["value"])) {
						/* Hide when value is not set */
						/* Check for empty array */
						if (is_array($field["value"]) && empty($field["value"])) {
							continue;
						}
						/* Check for other fields */
						if ($field["value"] == "") {
							continue;
                        }
					}
                    
                }                
                
                /* Inject label alignment property */
                $field["label_alignment"] = isset($_group["label_alignment"]) ? $_group["label_alignment"] : "left";
                
                /* Collecting Field rules meta */
                if (isset($field["field_rules"]) && is_array($field["field_rules"]) && count($field["field_rules"]) != 0) {					
                    $this->fields_rules[$field["key"]] = $field["field_rules"];                    
                }
                
                /* Collecting Pricing rules meta */
                if (isset($field["pricing_rules"]) && is_array($field["pricing_rules"]) && count($field["pricing_rules"]) != 0) {
                    $this->pricing_rules[$field["key"]] = $field["pricing_rules"]; 
                }
                if (has_filter('wccpf_field_meta')) {
                    $field = apply_filters('wccpf_field_meta', $field, $this->get_product_id($this->product));
                }
                /* generate html for wccpf fields */
				/* Change wccaf to wccpf whe the field type is URL */
				if ($field["type"] == "url") {
					$_group["type"] = "wccpf";
				}

				/* To mark all fields attribute with wccpf suffix - since all three fields types will be going through this line */			
				$field["for_front_end"] = true;
				/* This is neccessary for name and fkey attr */
				$field["name"] = $field["key"];

                $html = wcff()->builder->build_user_field($field, $_group["type"], $this->is_cloning_enabled, $_group["is_clonable"]);
                                
                /* Allow third party apps logic to render wccpf fields with their own wish */
                if (has_filter('wccpf_before_fields_rendering')) {
                    $html = apply_filters('wccpf_before_fields_rendering', $field, $html);
                }
                
                do_action('wccpf_before_field_start', $field);
                
                $pHtml .= $html;
                
                do_action('wccpf_after_field_end', $field);               
            }
            $pHtml .= '</div>';
        }   
    
        return $pHtml;
	}
	
	/**
	 * 
	 * @param array $_group
	 * @return string
	 * 
	 */
	private function render_product_fields_with_custom_layout($_group) {
	    
	    wcff()->dao->set_current_post_type($_group["type"]);
	    $layout = wcff()->dao->load_layout_meta($_group["id"]); 
	    
	    $html = '<div class="wcff-fields-group" data-custom-layout="'. esc_attr($_group["use_custom_layout"]) .'" data-group-clonable="'. esc_attr($_group["is_clonable"]) .'">';
	    foreach ($layout["rows"] as $row) {
	        
	        if (!$this->determine_row_has_fields($row, $_group["fields"])) {
	            continue;
	        }
	        
	        $html .= '<div class="wcff-layout-form-row">';
	        foreach($row as $fkey) {

	            $html .= '<div class="wcff-layout-form-col" style="flex-basis: '. esc_attr($layout["columns"][$fkey]["width"]) .'%;">';
	                
    	            $field = $this->get_field_meta($fkey, $_group["fields"]);    	            
    	            if ($field) {
    	                if ($this->is_multilingual_enabled == "yes") {
    	                    /* Localize field */
    	                    $field = wcff()->locale->localize_field($field);
    	                }
    	                
						if ($field["type"] == "colorpicker") {
							$this->color_fields[] = $field;
							$field["admin_class"] = $field["key"];
						}
						if ($field["type"] == "datepicker") {
							$this->date_fields[] = $field;
							$field["admin_class"] = $field["key"];
						}
    	                
    	                if (WC()->session && WC()->session->__isset("wcff_validation_failed")) {
    	                    /* Last add to cart operation failed
    	                     * Try to restore the fields old value */
    	                    $index = "";
    	                    if ($this->is_cloning_enabled == "yes") {
    	                        $index= "_1";
    	                    }
    	                    if (isset($_REQUEST[$field["key"] . $index])) {
    	                        $field["default_value"] = $_REQUEST[$field["key"] . $index];
    	                    }
    	                    
    	                    /* Reset the validation failed flaq */
    	                    WC()->session->__unset("wcff_validation_failed");
    	                }
    	                
    	                /* Put value for admin and variable fields */
    	                if ($_group["type"] == "wccaf") {    	                    
    	                    
    	                    /* Check for the 'show_with_value' option, - this option included after 4.0.0 */
    	                    if (!isset($field["show_with_value"])) {
    	                        $field["show_with_value"] = "yes";
    	                    }
    	                    
    	                    if ($field["show_with_value"] == "yes") {

    	                        $value = "";
						
								/* Retrive the value (set by admin) */
								if ($this->variation_id) {
									$value = get_post_meta($this->variation_id, $field["key"], true);
								}
								/* Fix for parent of varaible fields */                            
								if (!$value || $value == "") {
									$value = get_post_meta($this->get_product_id($this->product), $field["key"], true);
								}

								if (!$value) {
									$value =  $field["default_value"];
								}
    	                        /* Show the field with value */
    	                        if ($field["type"] != "checkbox") {
    	                            $field["value"] = $value;
    	                        } else {
    	                            if ($value && is_string($value)) {
										$field["value"] = explode(',', $value);								
									}
    	                        }
    	                    }  	
							
							$hide_field_when_no_value = isset($field["hide_when_no_value"]) ? $field["hide_when_no_value"] : "yes";					
							if ($hide_field_when_no_value == "yes" && isset($field["value"])) {
								/* Hide when value is not set */
								/* Check for empty array */
								if (is_array($field["value"]) && empty($field["value"])) {
									continue;
								}
								/* Check for other fields */
								if ($field["value"] == "") {
									continue;
								}
							}
    	                        	                    
    	                }
    	                
    	                /* Inject label alignment property */
						$field["label_alignment"] = isset($_group["label_alignment"]) ? $_group["label_alignment"] : "left";
						
						/* Collecting Field rules meta */
						if (isset($field["field_rules"]) && is_array($field["field_rules"]) && count($field["field_rules"]) != 0) {					
							$this->fields_rules[$field["key"]] = $field["field_rules"];                    
						}
						
						/* Collecting Pricing rules meta */
						if (isset($field["pricing_rules"]) && is_array($field["pricing_rules"]) && count($field["pricing_rules"]) != 0) {
							$this->pricing_rules[$field["key"]] = $field["pricing_rules"]; 
						}
						if (has_filter('wccpf_field_meta')) {
							$field = apply_filters('wccpf_field_meta', $field, $this->get_product_id($this->product));
						}
						/* generate html for wccpf fields */
						/* Change wccaf to wccpf whe the field type is URL */
						if ($field["type"] == "url") {
							$_group["type"] = "wccpf";
						}

						/* To mark all fields attribute with wccpf suffix - since all three fields types will be going through this line */			
						$field["for_front_end"] = true;
						/* This is neccessary for name and fkey attr */
						$field["name"] = $field["key"];
						
    	                $html .= wcff()->builder->build_user_field($field, $_group["type"], $this->is_cloning_enabled, $_group["is_clonable"]);
    	            }
	            
	            $html .= '</div>';
	        }
	        $html .= '</div>';
	    }
	    $html .= '</div>';
	    return $html;
	}
	
	/**
	 * 
	 * @param string $_key
	 * @param object $_fields
	 * @return mixed|boolean
	 * 
	 */
	private function get_field_meta($_key, $_fields) {
	    foreach ($_fields as $field) {
	        if ($field["key"] == $_key) {
	            return $field;
	        }
	    }	    
	    return false;
	}
	
	/**
	 *  
	 * @param array $_fkeys
	 * @param object $_fields
	 * @return boolean
	 * 
	 */
	private function determine_row_has_fields ($_fkeys, $_fields) {
	    if (is_array($_fkeys)) {
	        foreach ($_fkeys as $_fkey) {
	            foreach ($_fields as $field) {
	                if ($field["key"] == $_fkey) {
	                    return true;
	                }
	            }
	        }
	    }	    
	    return false;
	}
	
	/**
	 * 
	 * @param string $position
	 * 
	 */
	private function handle_label_field($position = "beginning") {	    
	    foreach ($this->product_field_groups as $group) {
	        if (isset($group["fields"]) && count($group["fields"]) > 0) {
	            foreach ($group["fields"] as $field) {
	                if ($field["type"] == "label" && $field["position"] == $position) {	  
						if ($this->is_multilingual_enabled == "yes") {
					        /* Localize field */
					        $field = wcff()->locale->localize_field($field);
					    }                  
	                    /* generate html for wccpf fields */
						/* This is neccessary for name and fkey attr */
						$field["name"] = $field["key"];
	                    $html = wcff()->builder->build_user_field($field, "wccpf");
	                    /* Allow third party apps logic to render wccpf fields with their own wish */
	                    if (has_filter('wccpf_before_fields_rendering')) {
	                        $html = apply_filters('wccpf_before_fields_rendering', $field, $html);
	                    }
	                    
	                    do_action('wccpf_before_field_start', $field);
	                    
	                    echo $html;
	                    
	                    do_action('wccpf_after_field_end', $field);	                    
	                }
	            }
	        }
	    }
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
	
	/**
	 *
	 * Enqueue assets for Front end Product Page
	 *
	 * @param boolean $isdate_css
	 *
	 */
	public function enqueue_client_side_assets($isdate_css = false) { 
	    if (is_product() || is_cart() || is_checkout() || is_product_taxonomy() || is_shop() || is_singular("courses")) :
    	$wccpf_options = wcff()->option->get_options();
    	$field_glob_location = isset($wccpf_options["field_location"]) ? $wccpf_options["field_location"] : "woocommerce_before_add_to_cart_button"; ?>
		     
        <script type="text/javascript">	       
	    var wccpf_opt = {
	    	editable : "<?php echo esc_html(isset( $wccpf_options["edit_field_value_cart_page"] ) ? $wccpf_options["edit_field_value_cart_page"] : "no"); ?>",
	        cloning : "<?php echo esc_html(isset( $wccpf_options["fields_cloning"] ) ? $wccpf_options["fields_cloning"] : "no"); ?>",
	        location : "<?php echo esc_html($field_glob_location); ?>",
	        validation : "<?php echo esc_html(isset( $wccpf_options["client_side_validation"] ) ? $wccpf_options["client_side_validation"] : "no"); ?>",
	        validation_type : "<?php echo esc_html(isset( $wccpf_options["client_side_validation_type"] ) ? $wccpf_options["client_side_validation_type"] : "submit"); ?>",	        	        
			real_time_price_update : "<?php echo esc_html(isset( $wccpf_options["enable_ajax_pricing_rules"] ) ? $wccpf_options["enable_ajax_pricing_rules"] : "disable"); ?>",
		    price_container_is : "<?php echo esc_html(isset( $wccpf_options["ajax_pricing_rules_price_container"] ) ? $wccpf_options["ajax_pricing_rules_price_container"] : "default"); ?>",
	        price_container : "<?php echo esc_html(isset( $wccpf_options["ajax_price_replace_container"] ) ? $wccpf_options["ajax_price_replace_container"] : ""); ?>",
	        price_details : "<?php echo esc_html(isset( $wccpf_options["pricing_rules_details"] ) ? $wccpf_options["pricing_rules_details"] : "hide"); ?>",			
	        color_picker_functions : [],
			currency: "<?php echo get_woocommerce_currency_symbol(); ?>",
			currency_position: "<?php echo get_option('woocommerce_currency_pos'); ?>",
			number_of_decimal: <?php echo get_option('woocommerce_price_num_decimals'); ?>,
			thousand_seperator: "<?php echo get_option('woocommerce_price_thousand_sep'); ?>",
			decimal_seperator: "<?php echo get_option('woocommerce_price_decimal_sep'); ?>",
			trim_zeros: "<?php echo (apply_filters('woocommerce_price_trim_zeros', false) ? "yes" : "no"); ?>",
	        is_ajax_add_to_cart : "<?php echo get_option( 'woocommerce_enable_ajax_add_to_cart' ); ?>",
	        is_page : "<?php echo ( is_product() ? "single" : "archive" ); ?>"
	    };
	    </script>	
	
		<?php
	        
		// Jquery ui and time picker style
		wp_enqueue_style("wcff-jquery-ui-style", esc_url(wcff()->info['dir'] .'assets/css/jquery-ui.css'));
		wp_enqueue_style("wcff-timepicker-style", esc_url(wcff()->info['dir'] .'assets/css/jquery-ui-timepicker-addon.css'));
		
		// Jquery init
		wp_enqueue_script("jquery");
		// jquery UI Core
		wp_enqueue_script('jquery-ui-core');
		// Jquery Date pciker
		wp_enqueue_script('jquery-ui-datepicker');
		
		// Jquery Multi-Language 
		wp_enqueue_script('jquery-ui-i18n', esc_url(wcff()->info['dir'] .'assets/js/jquery-ui-i18n.min.js?v='. wcff()->info["version"]));
		// Jquery Time Picker script
		wp_enqueue_script('jquery-ui-timepicker-addon', esc_url(wcff()->info['dir'].'assets/js/jquery-ui-timepicker-addon.min.js?v='. wcff()->info["version"]));
		/* Moment for date parsing */
		wp_enqueue_script('moment', esc_url(wcff()->info['dir'].'assets/js/moment.min.js?v='. wcff()->info["version"]));
		// Color Picker css
		wp_enqueue_style("wcff-colorpicker-style", esc_url(wcff()->info['dir'].'assets/css/spectrum.css?v='. wcff()->info["version"]));
		// Color Picker Script
		wp_enqueue_script('wcff-colorpicker-script', esc_url(wcff()->info['dir'].'assets/js/spectrum.js?v='. wcff()->info["version"]));
		// wcff Client css 
		wp_enqueue_style("wcff-client-style", esc_url(wcff()->info['dir'].'assets/css/wcff-client.css?v='. wcff()->info["version"]));
		//wcff Client Script
		wp_enqueue_script('wcff-client-script', esc_url(wcff()->info['dir'].'assets/js/wcff-client.js?v='. wcff()->info["version"]));
			
		?>
			
    	<?php if(is_shop()): ?>    		
    		<script>    		
    			/* Fix for the chinese character appearing on the datepicker */
    			jQuery(document).ready(function(jQuery){
    				jQuery.datepicker.setDefaults(jQuery.datepicker.regional["en"]);
        		});
        		
    			jQuery( document ).on( "click", ".wccpf_fields_table ", function(e){
        			var target = jQuery( e.target );
        			if( !target.is( ".wccpf_fields_table" ) && !target.is("input[type='checkbox']") && !target.is("input[type='radio']") && !( target.is( "label" ) && target.find("input[type='checkbox'],input[type='radio'],input[type='file']").length != 0 ) ){
						return false;
					}
            	});
    		</script>
    	<?php endif; ?>
	<?php endif; 
	
	   $this->enqueue_wcff_client_side_meta(true);
	
	}
	
	/**
	 * 
	 * Additional fiedls meta for Client Side rendering
	 * For special fields like DatePicker & Color picker
	 * Also meta for Fields Rules akso will be injected into DOM ENV
	 * 
	 */
	public function enqueue_wcff_client_side_meta($_echo = true) { 
		Global $product;
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
            $picker_meta["admin_class"] = isset($field["admin_class"]) ? $field["admin_class"] : "";

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
        
        if ($_echo) { ?>
            <script type="text/javascript">
			<?php 
			 	if (is_product() && $product) : ?> 
					var wcff_is_variable = "<?php echo esc_html(($product->is_type('variable')) ? "yes" : "no"); ?>";
				    var wcff_product_price = <?php echo esc_html($product->get_price()); ?>;
				<?php endif; ?>
            		var wcff_date_picker_meta = <?php echo wp_json_encode($date_bucket); ?>;
            		var wcff_color_picker_meta = <?php echo wp_json_encode($color_bucket); ?>;
            		var wcff_fields_rules_meta = <?php echo wp_json_encode($this->fields_rules); ?>;
            		var wcff_pricing_rules_meta = <?php echo wp_json_encode($this->pricing_rules); ?>;
            	</script>
        	<?php 
			      	  
        } else {
            $meta = array(
                "date_picker_meta" => $date_bucket,
                "color_picker_meta" => $color_bucket,
                "fields_rules_meta" => $this->fields_rules,
                "pricing_rules_meta" => $this->pricing_rules
            );
            return wp_json_encode($meta);
        }        
	}
	
	/**
	 * 
	 * Loop through all groups and determine whether any group has show_title enabled.
	 * TRUE : Use global cloning title
	 * FALSE : Use group's title instead  
	 * 
	 * @return boolean
	 */
	private function use_global_cloning_title() {
	    foreach ($this->product_field_groups as $group) {
	        if (isset($group["fields"]) && count($group["fields"]) > 0) {	            
	            if ($group["show_title"] == "yes" && $group["is_clonable"] == "yes") {
	                return false;
	            }	            
	        }
	    }
	    return true;
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
	
}

?>