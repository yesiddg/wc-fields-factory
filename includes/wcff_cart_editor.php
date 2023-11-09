<?php 

if (!defined('ABSPATH')) { exit; }
/**
 * 
 * Responsible for rendering Custom data on Cart & Checkout<br/>
 * If either fields cloning is enabled or Editable option is enabled (or both)
 * 
 * @author Saravana Kumar K
 * @copyright Sarkware Pvt Ltd
 *
 */
class wcff_cart_editor {
    
	/* Holds the supplied html of the callback (may have Title or Quantity) */
    private $html;
    /* Holds the Cart Item Object */
    private $cart_item = null;
    /* Holds the Cart Item Key */
    private $cart_item_key = null;
    /* Flaq that tells whether we are in Cart or Checkout */
    private $is_review_table;
    
    /* Holds the generated html of custom field */
    private $meta_html;
    /*Cloning flag */
    private $is_cloning_enabled;
    /* Visibility flaq */
    private $show_custom_data;
    /* Fields group title (on cart & check out) */
    private $fields_group_title;
    /* Multilingual flag */
    private $multilingual;
    
    /* Datepicker flaq - to include date picker releated scripts */
    private $is_datepicker_there = false;
    /* Colorpicker flaq - to include Spectrum related scripts */
    private $is_colorpicker_there = false;
    
    /* Holds the all product fields list (Across the Product Fields Post) */
    private $product_field_groups = null;
    /* Holds the all admin fields list (Across the Admin Fields Post) */
    private $admin_field_groups = null;
    
    public function __construct() {}
    
    /**
     * 
     * Generate custom fields list with value (Label => User Value) and append with<br/>
     * Product Title (if it is Cart) or Quantity (If it is Checkout)<br/>
     * It also enqueue necessary JS script for Fields Editior (to update fields value on Cart).
     * 
     * @param string $_html
     * @param object $_cart_item
     * @param string $_cart_item_key
     * @param boolean $_is_review_table
     * @return string | Unknown
     * 
     */
    public function render_fields_data($_html, $_cart_item, $_cart_item_key, $_is_review_table) {
        
		$this->html = $_html;
        $this->cart_item = $_cart_item;
        $this->cart_item_key = $_cart_item_key;
        $this->is_review_table = $_is_review_table;
       
        $this->meta_html = "";
        $wccpf_options = wcff()->option->get_options();
        $this->is_cloning_enabled= isset($wccpf_options["fields_cloning"]) ? $wccpf_options["fields_cloning"] : "yes";
        $this->multilingual = isset($wccpf_options["enable_multilingual"]) ? $wccpf_options["enable_multilingual"] : "no";
        
        $pricing_rule_details = isset($wccpf_options["pricing_rules_details"]) && $wccpf_options["pricing_rules_details"] == "show" ? true : false;
        $pricing_rule_title_show = isset($wccpf_options["ajax_pricing_rules_title"]) && $wccpf_options["ajax_pricing_rules_title"] == "show" ? true : false;
        $pricing_rule_title = $pricing_rule_title_show && isset($wccpf_options["ajax_pricing_rules_title_header"]) ? $wccpf_options["ajax_pricing_rules_title_header"] : "";

		$is_admin_module_enabled = isset($wccpf_options["enable_admin_field"]) ? $wccpf_options["enable_admin_field"] : "yes";
		$is_variable_module_enabled = isset($wccpf_options["enable_variable_field"]) ? $wccpf_options["enable_variable_field"] : "yes";
        
        if (isset($wccpf_options["fields_group_title"]) && $wccpf_options["fields_group_title"] != "") {
            $this->fields_group_title = $wccpf_options["fields_group_title"];
        } else {
            $this->fields_group_title = "Additional Options ";
        }
        
        if (isset($this->cart_item['product_id'])) {
            /* Get the last used template from session */
			$template = "single-product";
        	if (WC()->session) {
            	$template = WC()->session->get("wcff_current_template", "single-product");        
			}
            
            $this->product_field_groups = wcff()->dao->load_fields_groups_for_product($this->cart_item['product_id'], 'wccpf', $template, "any");

			$this->admin_field_groups = array();
			if ($is_admin_module_enabled == "yes") {
				$this->admin_field_groups = wcff()->dao->load_fields_groups_for_product($this->cart_item['product_id'], 'wccaf', $template, "any");
			}            
            
            if (isset($this->cart_item['variation_id']) && !empty($this->cart_item['variation_id']) && $this->cart_item['variation_id'] != 0) {
				$wccvf_posts = array();
				$wccvf_posts = wcff()->dao->load_fields_groups_for_product($this->cart_item['variation_id'], 'wccpf', "variable", "any");
            	$this->product_field_groups = array_merge($this->product_field_groups, $wccvf_posts);   
				
				if ($is_variable_module_enabled == "yes") {
					$wccvf_posts = array();
            		$wccvf_posts = wcff()->dao->load_fields_groups_for_product($this->cart_item['variation_id'], 'wccvf', "any", "any");
            		$this->product_field_groups = array_merge($this->product_field_groups, $wccvf_posts);
				}				

				if ($is_admin_module_enabled == "yes") {
					/* Also get the admin fields for variations */
					$wccaf_posts = wcff()->dao->load_fields_groups_for_product($this->cart_item['variation_id'], 'wccaf', "variable", "any", true);          
					$this->admin_field_groups = array_merge($this->admin_field_groups, $wccaf_posts);
				}
            }

			$this->product_field_groups = array_unique($this->product_field_groups, SORT_REGULAR);
			$this->admin_field_groups = array_unique($this->admin_field_groups, SORT_REGULAR);

            if (isset($this->cart_item["quantity"])) {

				$index = $this->is_cloning_enabled == "yes" ? 1 : 0;
				$this->render_fields($this->product_field_groups, $index);
				$this->render_fields($this->admin_field_groups, $index);

				if ($this->meta_html != "") {
					/* Editor wrapper */
					$this->meta_html = '<div class="wccpf-cart-data-editor">'. $this->meta_html .'</div>';					
				}
            	
            	/* Before start to render, make sure there are pricing rules to render */
            	if ($this->determine_pricing_there_to_render() && $pricing_rule_details ) {
            		/* Pricing wrapper start */
            		$this->meta_html .= '<div class="wccpf-pricing-group-on-cart">';
            		/**/
            		if( $pricing_rule_title != "" ) {
            		    $this->meta_html .= '<h4 class="wcff_pricing_rules_title_container">'. esc_html($pricing_rule_title) .'</h4>';
            		}
            		$this->render_pricing_rules_data();
            		/* Pricing wrapper end */
            		$this->meta_html .= '</div>';
            	}                
            }
        }
        
        if (!$this->is_review_table) {
            $this->html = $this->html . $this->meta_html;
        } else {
       		$this->html = $this->meta_html . $this->html;
        }
        
        /* Return the generated html */
        return $this->html;
    }  
       
    /**
     * 
     * Mine the Cart Item object for Product Fields, and construct the Html out of it to render on Cart & Checkout
     * 
     */
    private function render_fields($_groups = array(), $_index = 0) {

		$field_editors = "";

		/*
         * Normal mining process on $_REQUEST object
         * Since we have field level cloning option we have to mine
         * even if cloning option is enabled
         */
        $key_suffix = $_index > 0 ? ("_". $_index) : "";

		foreach ($_groups as $group) {
            if (count($group["fields"]) > 0) {
                foreach ($group["fields"] as $field) { 
                    $field["visibility"] = isset($field["visibility"]) ? $field["visibility"] : "yes";                   
                    /* name attr has been @depricated from 3.04 onwards */
                    $fname   = isset($field["key"]) ? ($field["key"] . $key_suffix) : ($field["name"] . $key_suffix);                             
                    if ($field["visibility"] == "yes" && isset($this->cart_item[$fname])) {                        
						$field_editors .= $this->render_data($field, $this->cart_item [$fname]);
                    }                    
                }
            }
        }
				
		if ($field_editors != "") {
			/* Field's group wrapper start */
			$this->meta_html .= '<fieldset>';
			/* Sandwich the editors within wrapper */
			$this->meta_html .= $field_editors;
			/* Field's group wrapper end */
			$this->meta_html .= '</fieldset>';
		}	
	
	}  
    
    /**
     * 
     * Mine the Car Item object for any applied pricing rules<br/>
     * If found any then extract it and generate a html to render it on Cart & Checkout
     * 
     */
    private function render_pricing_rules_data() {
    	    $is_there = false;
    	    foreach ($this->cart_item as $ckey => $cval) {
    		    if (strpos($ckey, "wccpf_pricing_applied_") !== false) {
    			    $is_there = true;
    			    break;
    		    }
    	    }
    	    
        	if ($is_there) {        		
        		foreach ($this->cart_item as $ckey => $cval) {
        			if (strpos($ckey, "wccpf_pricing_applied_") !== false) {
        				$prules = $this->cart_item[$ckey];
        				if (isset($prules["title"]) && isset($prules["amount"])) {
        				    $this->meta_html .= '<ul class="wccpf-pricing-rule-ul">';
        					$this->meta_html .= '<li>'. esc_html($prules["title"]) .' : </li>';
        					$this->meta_html .= '<li> '. esc_html($prules["amount"]) .'</li>';
        					$this->meta_html .= '</ul>';        					
        				}
        			}
        		}        		
        	}    	
    }
    
    /**
     * 
     * Helper method which actualy generate the HTML for custom data.
     * 
     * @param object $_field
     * @param string|number|array $_val
     * @param string $_index
     * 
     */
	private function render_data($_field, $_val, $_index = "") {
		if ($this->multilingual == "yes") {
			/* Localize field */
			$_field = wcff ()->locale->localize_field ( $_field );
		}
		$_val = (($_val && isset ( $_val ["user_val"] )) ? $_val ["user_val"] : $_val);
		$is_editable = isset ( $_field ["cart_editable"] ) ? $_field ["cart_editable"] : "no";
		$editable_class = ($is_editable == "yes") ? "wcff_cart_editor_field" : "";
		$tooltip = ($is_editable == "yes") ? 'title="Double click to edit"' : '';
		
		$is_editable = (is_checkout()) ? "no" : $is_editable;
		
		$meta_html = '<ul class="wccpf-cart-editor-ul wccpf-is-editable-'. esc_attr($is_editable) .'">';
		$meta_html .= '<li>' . esc_html($_field ["label"]) . ' : </li>';
		
		if ($_field ["type"] != "file" && $_field ["type"] != "checkbox" && $_field ["type"] != "colorpicker") {
		    $meta_html .= '<li class="' . esc_attr($editable_class) . '" ' . $tooltip . ' data-field="' . esc_attr($_field ["key"]) . '" data-fieldkey="' . esc_attr($_field ["key"] . $_index) . '" data-productid="' . esc_attr ( $this->cart_item ["product_id"] ) . '" data-itemkey="' . esc_attr ( $this->cart_item_key ) . '">' . wp_kses_post ( wpautop ( stripslashes ( $_val ) ) ) . '</li>';
		} else if ($_field ["type"] == "checkbox") {
		    $meta_html .= '<li class="' . esc_attr($editable_class) . '" ' . $tooltip . ' data-field="' . esc_attr($_field ["key"]) . '" data-fieldkey="' . esc_attr($_field ["key"] . $_index) . '" data-productid="' . esc_attr ( $this->cart_item ["product_id"] ) . '" data-itemkey="' . esc_attr ( $this->cart_item_key ) . '">' . wp_kses_post ( wpautop ( (is_array ( $_val ) ? implode ( ",", $_val ) : stripslashes ( $_val )) ) ) . '</li>';
		} else if ($_field ["type"] == "colorpicker") {
			$color_val = "";
			$show_as_color = 'data-color-box="no"';
			if (isset ( $_field ["hex_color_show_in"] ) && $_field ["hex_color_show_in"] == "yes") {
				if (strpos ( $_val, "wcff-color-picker-color-show" ) == false) {
				    $color_val = '<span class="wcff-color-picker-color-show" code="' . esc_attr($_val) . '" style="background-color: ' . esc_attr($_val) . '"></span>';
				} else {
					$color_val = $_val;
				}
				$show_as_color = 'data-color-box="yes"';
			} else {
				$color_val = wp_kses_post ( wpautop ( $_val ) );
			}
			$meta_html .= '<li class="' . esc_attr($editable_class) . '" ' . $tooltip . ' data-field="' . esc_attr($_field ["key"]) . '" data-fieldkey="' . esc_attr($_field ["key"] . $_index) . '" data-productid="' . esc_attr ( $this->cart_item ["product_id"] ) . '" data-itemkey="' . esc_attr ( $this->cart_item_key ) .'" '. $show_as_color .'>' . $color_val . '</li>';
		} else {
			$is_multi_file = isset ( $_field ["multi_file"] ) ? $_field ["multi_file"] : "no";
			if ($is_multi_file == "yes") {
				$fkeys = array ();
				$farray = json_decode ( $_val, true );
				foreach ( $farray as $fobj ) {
					$path_parts = pathinfo ( $fobj ['file'] );
					if (isset($path_parts["basekey"])) {
						$fkeys[] = $path_parts["basekey"];
					} elseif (isset($path_parts["basename"])) {
						$fkeys[] = $path_parts["basename"];
					}					
				}
				$meta_html .= '<li class="wcff_field_cart_updater_clone" data-field="' . esc_attr($_field ["key"]) . '" data-fieldkey="' . esc_attr($_field ["key"] . $_index) . '" data-productid="' . esc_attr ( $this->cart_item ["product_id"] ) . '" data-itemkey="' . esc_attr ( $this->cart_item_key ) . '">' . wp_kses_post ( implode ( ", ", $fkeys ) ) . '</li>';
			} else {
				$fobj = json_decode ( $_val, true );
				$path_parts = pathinfo ( $fobj ['file'] );
				if ($_field ["img_is_prev"] == "yes" && @getimagesize ( $fobj ["url"] )) {
				    $meta_html .= '<li data-field="' . esc_attr($_field ["key"]) . '" data-fieldkey="' . esc_attr($_field ["key"] . $_index) . '" data-productid="' . esc_attr ( $this->cart_item ["product_id"] ) . '" data-itemkey="' . esc_attr ( $this->cart_item_key ) . '"><img src="' . esc_url($fobj ["url"]) . '" style="width:' . esc_attr($_field ["img_is_prev_width"]) . 'px;"></li>';
				} else {
				    $meta_html .= '<li class="wcff_field_cart_updater_clone" data-field="' . esc_attr($_field ["key"]) . '" data-fieldkey="' . esc_attr($_field ["key"] . $_index) . '" data-productid="' . esc_attr ( $this->cart_item ["product_id"] ) . '" data-itemkey="' . esc_attr ( $this->cart_item_key ) . '">' . wp_kses_post ( stripslashes ( $path_parts ["basename"] ) ) . '</li>';
				}
			}
		}
		$meta_html .= '</ul>';
		
		/* Let other plugins override this value - if they wanted */		
		if (has_filter("wcff_before_rendering_cart_editor")) {
			$meta_html = apply_filters("wcff_before_rendering_cart_editor", $_field, $meta_html);
		}  
		
		if ($_field ["type"] == "datepicker") {
			$this->is_datepicker_there = true;
		}
		if ($_field ["type"] == "colorpicker") {
			$this->is_colorpicker_there = true;
		}
		
		return $meta_html;
	}
	
	private function determine_fields_there_to_render() {
	    foreach ( $this->product_field_groups as $group ) {		    
		    if (count($group["fields"]) > 0) {
		        foreach ($group["fields"] as $field) {	            
                    $field ["visibility"] = isset ( $field ["visibility"] ) ? $field ["visibility"] : "yes";
                    if ($field ["visibility"] == "yes" && isset ( $this->cart_item [$field ["key"]] )) {
                        return true;
                    }
                }		         
		    }			
		}	
		
		foreach ($this->admin_field_groups as $group) {	
		    if (count($group["fields"]) > 0) {
		        foreach ($group["fields"] as $afield) {	
		            $afield["visibility"] = isset($afield["visibility"]) ? $afield["visibility"] : "yes";
		            if ($afield["visibility"] == "yes" && isset($this->cart_item[$afield["key"]])) {
		                return true;
		            }
		        }
		    }		    
		}	 
		if ($this->fields_cloning == "yes") {
			$pcount = intval ( $this->cart_item ["quantity"] );
			for($i = 1; $i <= $pcount; $i ++) {
			    foreach ( $this->product_field_groups as $group ) {				    
				    if (count($group["fields"]) > 0) {
				        foreach ($group["fields"] as $field) {
		                    $field ["cloneable"] = isset ( $field ["cloneable"] ) ? $field ["cloneable"] : "yes";
		                    $field ["visibility"] = isset ( $field ["visibility"] ) ? $field ["visibility"] : "yes";
		                    if ($field ["cloneable"] == "yes" && $field ["visibility"] == "yes" && isset ( $this->cart_item [$field ["key"] . "_" . $i] )) {
		                        return true;
		                    }
		                }				 
				    }
				}
				foreach ($this->admin_field_groups as $group) {				    
				    if (count($group["fields"]) > 0) {
				        foreach ($group["fields"] as $afield) {
		                    $afield["visibility"] = isset($afield["visibility"]) ? $afield["visibility"] : "yes";
		                    $afield["cloneable"] = isset($afield["cloneable"]) ? $afield["cloneable"] : "yes";
		                    if ($afield["cloneable"] == "yes" && $afield["visibility"] == "yes" && isset($this->cart_item[$afield["key"] . "_" . $i])) {
		                        return true;
		                    }
		                }				  
				    }				
				}
			}
		}
		return false;
	}
	
	private function determine_pricing_there_to_render() {
		foreach ($this->cart_item as $ckey => $cval) {
			if (strpos($ckey, "wccpf_pricing_applied_") !== false) {
				return true;
			}
		}
	}
    
    /**
     *
     * Used to render actual field with data on a Cart Item (In Cart Page itself)
     * Used for editing custom fields value in Cart page
     *
     * When user double click on any values on the cart line item, Fields Factory's client module will trigger an Ajax request with the following details
     * {
     *      product_id: "",
     *      product_cart_id: "",
     *      check_edit: "",
     *      data: {
     *          key: "fields_key",
     *          value: "fields_current_value"
     *      }
     * }
     *
     * @param object $_ci_fdata
     * @return string
     *
     */
    public function render_field_with_data($_payload) {
        $res = null;
        /* Holds the target field's config meta */
        $field = null;
        global $woocommerce;
       
        /* Get the last used template from session */
        $template = WC()->session->get("wcff_current_template", "single-product");   
		
		$wccpf_options = wcff()->option->get_options();
		$is_admin_module_enabled = isset($wccpf_options["enable_admin_field"]) ? $wccpf_options["enable_admin_field"] : "yes";
		$is_variable_module_enabled = isset($wccpf_options["enable_variable_field"]) ? $wccpf_options["enable_variable_field"] : "yes";
                
        $this->product_field_groups = wcff()->dao->load_fields_groups_for_product($_payload['product_id'], 'wccpf', $template, "any");

		$this->admin_field_groups = array();
		if ($is_admin_module_enabled == "yes") {
			$this->admin_field_groups = wcff()->dao->load_fields_groups_for_product($_payload['product_id'], 'wccaf', $template, "any");
		}        

        $variation = null;
        $citems = $woocommerce->cart->get_cart();        
        foreach( $citems as $cart_key => $cvalue ) {
        	if( $cart_key == $_payload['product_cart_id'] && $cvalue['product_id'] == $_payload['product_id'] && isset( $cvalue['variation_id'] ) ){
        		$variation = $cvalue['variation_id'];
        	}
        }
        if( $variation != null && !empty( $variation ) && $variation != 0 ) {        	
        	$this->product_field_groups = array_merge( $this->product_field_groups, wcff()->dao->load_fields_groups_for_product($cvalue['variation_id'], 'wccpf', $template, 'any' ));
			if ($is_variable_module_enabled == "yes") {
				$this->product_field_groups = array_merge( $this->product_field_groups, wcff()->dao->load_fields_groups_for_product($cvalue['variation_id'], 'wccvf', $template, 'any' ));
			}			
        }
        
		$this->product_field_groups = array_unique($this->product_field_groups, SORT_REGULAR);
		$this->admin_field_groups = array_unique($this->admin_field_groups, SORT_REGULAR);

        foreach ( $this->product_field_groups as $group ) {
            if (count($group["fields"]) > 0) {
                foreach ($group["fields"] as $fmeta) {                    
                    if ($fmeta["key"] == $_payload["data"]["field"]) {
                        $field = $fmeta;
                    }                    
                }
            }
        }
        
        if ($this->multilingual == "yes") {
            /* Localize field */
            $field = wcff()->locale->localize_field($field);
        }
        
        /* Continue only when we have the valid field's config meta */
        if ($field != null) {
            $editable = isset($field["cart_editable"]) ? $field["cart_editable"] : "no";
            if ($editable == "yes") {
                /* Set the "default_value" with user entered value */
            	$field["default_value"] = isset($_payload['data'][ 'value' ]) ? (($field["type"] == "checkbox") ? explode(",", $_payload['data'][ 'value' ]) : $_payload['data'][ 'value' ]) : $field["default_value"];
                $res= $this->render_field($field, $_payload['product_id']);
            }
        }
        
        return $res;
    }
    
    private function render_field($_field, $_product_id, $_cvalue="") {
        $script = "";
        $is_this_colorpicker = false;
        if ($_field["type"] == "colorpicker") {
            $is_this_colorpicker = true;
            $_field["admin_class"] = $_field["key"];
            $script .= $this->initialize_color_picker_field($_field, $_product_id, $_field["default_value"]);
        }
        if ($_field["type"] == "datepicker") {
        	$_field["admin_class"] = $_field["key"];
            $script .= $this->initialize_datepicker_field($_field, "wccpf");
        }
		/* To mark all fields attribute with wccpf suffix - since all three fields types will be going through this line */			
        $_field["for_front_end"] = true;
        $html = wcff()->builder->build_user_field($_field, "wccpf");
        return array("status" => true, "field_type" => $_field["type"], "html" => $html, "script" => $script, "color_showin" => $is_this_colorpicker);
    }
    
    /**
     *
     * Used to update the value of the custom fields
     * Used for editing custom fields value in Cart page
     *
     */
    public function update_field_value($_payload) {
        
        $return_value = "";
        $saveval = $_payload["data"]["value"];
        $validate = $this->validate_wccpf($_payload["product_id"], $_payload["data"]["field"], $_payload["data"]["value"], $_payload["cart_item_key"]);
        
        if (isset($_payload["data"]["color_showin"])) {
            if ($_payload["data"]["color_showin"]) {
                $saveval = urldecode($_payload["data"]["value"]);
            }
        }
        if (!$validate["status"]){
            return array("status" => false, "message" => $validate["msg"]);
        } else {

			$wccpf_options = wcff()->option->get_options();
        	$is_cloning_enabled= isset($wccpf_options["fields_cloning"]) ? $wccpf_options["fields_cloning"] : "yes";
			$key_suffix = $is_cloning_enabled == "yes" ? "_1" : "";

            if ($_payload["data"]["field_type"] != "file") {
            	if (isset(WC()->cart->cart_contents[$_payload['cart_item_key']][$_payload["data"]["field"] . $key_suffix][ "user_val" ])
            		&& isset(WC()->cart->cart_contents[$_payload['cart_item_key']][$_payload["data"]["field"] . $key_suffix][ "ftype" ])) {
            				/* To remove old pricing rule label on cart */
            				$pricing_rules = WC()->cart->cart_contents[$_payload['cart_item_key']][$_payload["data"]["field"] . $key_suffix];
            				for( $i = 0; $i < sizeof( $pricing_rules[ "pricing_rules" ] ); $i++ ) {
            					if( isset( WC()->cart->cart_contents[$_payload['cart_item_key']]["wccpf_pricing_applied_" . strtolower(str_replace(" ", "_", $pricing_rules[ "pricing_rules" ][$i]["title"])) ] ) ){
            						unset( WC()->cart->cart_contents[$_payload['cart_item_key']]["wccpf_pricing_applied_" . strtolower(str_replace(" ", "_", $pricing_rules[ "pricing_rules" ][$i]["title"]))]);
            					}
            				}
            			WC()->cart->cart_contents[$_payload['cart_item_key']][$_payload["data"]["field"] . $key_suffix][ "user_val" ] = $saveval;
            			$return_value = WC()->cart->cart_contents[$_payload['cart_item_key']][$_payload["data"]["field"] . $key_suffix][ "user_val" ];
            	} else {
            		WC()->cart->cart_contents[$_payload['cart_item_key']][$_payload["data"]["field"] . $key_suffix] = $saveval;
            		$return_value = WC()->cart->cart_contents[$_payload['cart_item_key']][$_payload["data"]["field"] . $key_suffix];
            	}
            	
            }

            WC()->cart->set_session();
            // Recaulculate pricing rule
            wcff()->negotiator->handle_custom_pricing( wc()->cart->cart_contents[$_payload['cart_item_key']], $_payload['cart_item_key'] );
            return array("status" => true, "value" => $return_value, "field_type" => $_payload["data"]["field_type"]);
        }
        
    }
    
    function validate_wccpf($_prod_id, $_key, $_value, $cart_key, $_variation_id = 0) {
        
        $is_passed = true;
        $is_admin  = false;
        
        /* Get the last used template from session */
        $template = WC()->session->get("wcff_current_template", "single-product");
		$wccpf_options = wcff()->option->get_options();
		$is_admin_module_enabled = isset($wccpf_options["enable_admin_field"]) ? $wccpf_options["enable_admin_field"] : "yes";
		
        $this->product_field_groups = wcff()->dao->load_fields_groups_for_product($_prod_id, 'wccpf', $template, "any");

		$this->admin_field_groups = array();
		if ($is_admin_module_enabled == "yes") {
			$this->admin_field_groups = wcff()->dao->load_fields_groups_for_product($_prod_id, 'wccaf', $template, "any");
		}        
        
        $variation = null;
        global $woocommerce;
        $citems = $woocommerce->cart->get_cart();
        foreach( $citems as $cart_key_org => $cvalue ){
            if( $cvalue['product_id'] == $_prod_id && $cart_key == $cart_key_org && isset( $cvalue['variation_id'] ) ){
                $variation = $cvalue['variation_id'];
            }
        }
        if( $variation != null && !empty( $variation ) && $variation != 0 ) {
            $this->product_field_groups = array_merge( $this->product_field_groups, wcff()->dao->load_fields_groups_for_product($cvalue['variation_id'], 'wccpf', $template, 'cart-page' ));
        }
        
        $msg			= "";
        $fieldc  		= null;
        $fieldac		= null;        
        
        foreach ( $this->product_field_groups as $group ) {
            if (count($group["fields"]) > 0) {
                foreach ($group["fields"] as $field) {                   
                    if ($field["key"] == $_key) {
                        $fieldc = $field;
                    }                    
                }
            }
        }
        
        foreach ( $this->admin_field_groups as $group ) {
            if (count($group["fields"]) > 0) {
                foreach ($group["fields"] as $field) {
                    if ($field["key"] == $_key) {
                        $fieldac = $field;
                    }
                }
            }
        }       

        if ($fieldc != null) {
            $field = $fieldc;
            $res = true;
            $res_size_val = true;
            $field["required"] = isset ($field ["required"]) ? $field ["required"] : "no";
            if ($field ["required"] == "yes" || $field ["type"] == "file") {
                if ($field ["type"] != "file") {
                    $res = wcff()->validator->validate_helper($_prod_id, $field, $_key, $_value, $cvalue['variation_id']);
                } else {
                    
                }
            }
            if (!$res || ! $res_size_val) {
                $is_passed = false;
                $msg = ! $res ? $field ["message"] : "Upload size limit exceed, Allow size is " . $field ["max_file_size"] . "kb.!";
            }
        }
        
        if ($fieldac != null) {
            $is_admin = true;
            $afield = $fieldac;
            $res = true;
            $afield ["show_on_product_page"] = isset ($afield ["show_on_product_page"]) ? $afield ["show_on_product_page"] : "no";
            if ($afield ["show_on_product_page"] == "yes" && $afield ["required"] == "yes") {
                $res = wcff()->validator->validate_helper($_prod_id, $afield, $_key, $_value, $cvalue['variation_id']);
            }
            if (!$res) {
                $is_passed = false;
                $msg = $afield ["message"];
            }
        }
        
        return array("status" => $is_passed, "is_admin" => $is_admin, "msg" => $msg);
        
    }
    
    
    /* To remove unwanted fields from product field */
    private function remove_field_rule_is_hidden(){
        for( $x = 0; $x < count( $this->product_field_groups ); $x++ ) {
            foreach ( $this->product_field_groups[$x] as $fields) {
                if( isset( $fields["field_rules"] ) && count( $fields["field_rules"] ) ){
                    $fkey   = $fields["key"];
                    $ftype   = $fields["type"];
                    $dformat = isset( $fields["format"] ) ? $fields["format"] : "";
                    $uvalue  = isset( $_REQUEST[$fkey] ) ? $_REQUEST[$fkey] : "";
                    $p_rules = $fields["field_rules"];
                    /* Iterate through the rules and update the price */
                    foreach ( $p_rules as $prule ) {
                        if ( !wcff()->negotiator->check_rules ( $prule, $uvalue, $ftype, $dformat ) ) {
                            foreach( $prule["field_rules"] as $each_f_k => $each_f_v ){
                                if( $each_f_v == "show" ){
                                    for( $p = 0; $p < count( $this->product_field_groups ); $p++ ){
                                        foreach ( $this->product_field_groups[$p] as $key_infield => $infield) {
                                            if( $infield["key"] == $each_f_k ){
                                                unset( $this->product_field_groups[$p][$key_infield] );
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
 
    
    /**
     *
     * @param WC Product $_product
     * @return integer
     *
     * Wrapper method for getting Wc Product object's ID attribute
     *
     */
    private function get_product_id($_product){
        return method_exists($_product, 'get_id') ? $_product->get_id() : $_product->id;
    }
    
    
    private function initialize_datepicker_field($_field, $_post_type) {
        	$localize = "none";
        	$year_range = "-10:+10";        	
        	if ( isset( $_field["language"] ) && !empty( $_field["language"] ) && $_field["language"] != "default") {
        		$localize = $_field["language"];
        	}
        	if (isset($_field["dropdown_year_range"]) && !empty($_field["dropdown_year_range"])) {
        		$year_range = $_field["dropdown_year_range"];
        	}
        ob_start(); ?>
    	
		<script type="text/javascript">		
		(function($) {
			$(document).ready(function() {
			<?php			
			if ($localize != "none") { ?>
				/* Datepicker User configured localization */	
				if( typeof $.datepicker != "undefined" ){						
    				var options = $.extend({}, $.datepicker.regional["<?php echo esc_attr($localize); ?>"]);
    				$.datepicker.setDefaults(options);
				}
			<?php 
			} else { ?>
				/* Datepicker default configuration */		
				if( typeof $.datepicker != "undefined" ){										
    				var options = $.extend({}, $.datepicker.regional["en-GB"]);
    					$.datepicker.setDefaults(options);
				}
			<?php 
			}				
			?>
			
				$("body").on("focus", ".<?php echo $_post_type; ?>-datepicker-<?php echo esc_attr($_field["key"]); ?>", function(){
					
				<?php if (isset($_field["timepicker"]) && $_field["timepicker"] == "yes") : ?>
					$(this).datetimepicker({
				<?php else : ?>
					$(this).datepicker({
				<?php endif; ?>											
				<?php			
					if (isset($_field["date_format"]) && $_field["date_format"] != "") {
						echo "dateFormat:'". esc_html( $_field["date_format"] ) ."'";
					} else {
						echo "dateFormat:'dd-mm-yy'";
					}	
						
					if (isset($_field["display_in_dropdown"]) && !empty($_field["display_in_dropdown"])) {
						if ($_field["display_in_dropdown"] == "yes") {
							echo ",changeMonth: true";
							echo ",changeYear: true";
							echo ",yearRange:'". esc_html($year_range) ."'";
						}
					}
					if (isset($_field["disable_date"]) && !empty($_field["disable_date"])) {
						if ("future" == $_field["disable_date"]) {
							echo ",maxDate: 0";
						}
						if ("past" == $_field["disable_date"]) {
							echo ",minDate: new Date()";
						}											
					}
					if (isset($_field["allow_next_x_years"]) && !empty($_field["allow_next_x_years"]) ||
						isset($_field["allow_next_x_months"]) && !empty($_field["allow_next_x_months"]) ||
						isset($_field["allow_next_x_weeks"]) && !empty($_field["allow_next_x_weeks"]) ||
						isset($_field["allow_next_x_days"]) && !empty($_field["allow_next_x_days"]) ) {
						$allowed_dates = "";
						if (isset($_field["allow_next_x_years"]) && !empty($_field["allow_next_x_years"]) && is_numeric($_field["allow_next_x_years"])) {
						    $allowed_dates .= "+". trim($_field["allow_next_x_years"]) ."y ";
						}
						if (isset($_field["allow_next_x_months"]) && !empty($_field["allow_next_x_months"]) && is_numeric($_field["allow_next_x_months"])) {
						    $allowed_dates .= "+". trim($_field["allow_next_x_months"]) ."m ";
						}
						if (isset($_field["allow_next_x_weeks"]) && !empty($_field["allow_next_x_weeks"]) && is_numeric($_field["allow_next_x_weeks"])) {
						    $allowed_dates .= "+". trim($_field["allow_next_x_weeks"]) ."w ";
						}
						if (isset($_field["allow_next_x_days"]) && !empty($_field["allow_next_x_days"]) && is_numeric($_field["allow_next_x_days"])) {
						    $allowed_dates .= "+". trim($_field["allow_next_x_days"]) ."d";
						}
						echo ",minDate: 0";
						echo ",maxDate: \"". esc_html(trim($allowed_dates)) ."\"";
					}
					/* Hooks up a call back for 'beforeShowDay' */
					echo ",beforeShowDay: disableDates";					
				?>					
						,onSelect: function( dateText ) {							
						    $( this ).next().hide();
						}								 
					});
				});		
				
				function disableDates( date ) {	
					<?php if (is_array($_field["disable_days"]) && count($_field["disable_days"]) > 0) { ?>
							 var disableDays = <?php echo wp_json_encode($_field["disable_days"]); ?>;
							 var day 	= date.getDay();
							 for (var i = 0; i < disableDays.length; i++) {
									 var test = disableDays[i]
								 		 test = test == "sunday" ? 0 : test == "monday" ? 1 : test == "tuesday" ? 2 : test == "wednesday" ? 3 : test == "thursday" ? 4 : test == "friday" ? 5 : test == "saturday" ? 6 : "";
							        if ( day == test ) {									        
							            return [false];
							        }
							 }						
					<?php } ?>	
					<?php if (isset($_field["specific_date_all_months"]) && !empty($_field["specific_date_all_months"])){ ?>
					 		var disableDateAll = <?php echo '"'. esc_html($_field["specific_date_all_months"]) .'"'; ?>;
					 			disableDateAll = disableDateAll.split(",");
					 		for (var i = 0; i < disableDateAll.length; i++) {
								if (parseInt(disableDateAll[i].trim()) == date.getDate()){
									return [false];
								}					
					 		}
					<?php } ?>						
					<?php if (isset($_field["specific_dates"]) && !empty($_field["specific_dates"])) { ?>
								var disableDates = <?php echo "'". esc_html($_field["specific_dates"]) ."'"; ?>;
									disableDates = disableDates.split(",");
									/* Sanitize the dates */
									for (var i = 0; i < disableDates.length; i++) {	
										disableDates[i] = disableDates[i].trim();
									}		
									/* Form the date string to compare */							
								var m = date.getMonth(),
									d = date.getDate(),
									y = date.getFullYear(),
									currentdate = ( m + 1 ) + '-' + d + '-' + y ;
								/* Make dicision */								
								if ( $.inArray( currentdate, disableDates ) != -1 ) {
									return [false];
								}
								
					<?php } ?>					
					<?php if (isset($_field["weekend_weekdays"]) && !empty($_field["display_in_dropdown"])) { ?>
							<?php if ($_field["weekend_weekdays"] == "weekdays"){ ?>
								//weekdays disable callback
								var weekenddate = $.datepicker.noWeekends(date);
								var disableweek = [!weekenddate[0]]; 
								return disableweek;
							<?php } else if ($_field["weekend_weekdays"] == "weekends") { ?>
								//weekend disable callback
								var weekenddate = $.datepicker.noWeekends(date);
								return weekenddate; 
							<?php } ?>							
					<?php }  ?>						
					return [true];
				}
							
			});
		})(jQuery);
		</script>
		
		<?php
		return ob_get_clean();
	}
    
	private function initialize_color_picker_field($_field, $product_id, $color_code) {
    		
		$palettes = null;
		$palette_attr = "";
		$colorformat = isset ( $_field ["color_format"] ) ? $_field ["color_format"] : "hex";
		$defaultcolor = isset ( $_field ["default_value"] ) ? $_field ["default_value"] : "#000";
		$defaultcolor = $color_code != null ? $color_code : $defaultcolor;
		
		if (isset ( $_field ["palettes"] ) && $_field ["palettes"] != "") {
			$palettes = explode ( ";", $_field ["palettes"] );
			$palette_attr = ",palette : [";
			$indexY = 0;
			foreach ( $palettes as $palette ) {
				$indexX = 0;
				$comma = ($indexY == 0) ? "" : ",";
				$palette_attr .= $comma . "[";
				$colors = explode ( ",", $palette );
				foreach ( $colors as $color ) {
					$comma = ($indexX == 0) ? "" : ",";
					$palette_attr .= $comma . "'" . $color . "'";
					$indexX ++;
				}
				$palette_attr .= "]";
				$indexY++;
			}
			$palette_attr .= "]";
		}
		ob_start(); ?>

		<script type="text/javascript">

        	(function($) {
        		$(document).ready(function() {
	        		$(".wccpf-color-<?php echo esc_attr( $_field["key"] ); ?>").spectrum({
	        		color: "<?php echo esc_html($defaultcolor); ?>" 
		        	,preferredFormat: "<?php echo esc_html($colorformat); ?>"
					<?php if( is_array( $palettes ) && count( $palettes ) > 0 ) : ?>
					<?php echo esc_html($palette_attr); ?>
					<?php if( $_field["show_palette_only"] == "yes" ) : ?>
					,showPaletteOnly: true
					<?php else: ?>
					<?php if( isset( $_field["color_text_field"] ) && $_field["color_text_field"] == "yes" ): ?>
					,showInput: true
					<?php endif;  ?>
					<?php endif;  ?>
					,showPalette: true
					<?php $palette_attr; ?>
					
					<?php endif; ?>					
	        		});	
        		});
           	})(jQuery);
        	
        </script>
        
		<?php
		return ob_get_clean();
	}
}

?>