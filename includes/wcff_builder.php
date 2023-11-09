<?php

if (!defined('ABSPATH')) { exit; }

/**
 * 
 * One of the core module, responsible for generating everything that related to Fields Factory UI<br>
 * Privides methods for generating Fields configuration related meta fields.<br>
 * Provides methods for generating Product Fields, Admin Field and other UI widgets.
 * 
 * @author 		: Saravana Kumar K
 * @copyright 	: Sarkware Research & Development (OPC) Pvt Ltd
 *
 */
class wcff_builder {
    
	private $fields_values = null;
	private $wccpf_options = array();
	private $is_multilingual = "no";
	private $supported_locale = array();
	
	/**
	 * Used to denote the mode, which the fields are rendered.
	 * front (or) back
	 */
	private $fields_mode = "front";
	
    public function __construct() {}
    
    /**
     *
     * Generate Product List using Select tag ( id => Title )<br>
     * Mostly used in Product Rules widget.
     *
     * @param string $_class
     * @param string $_active
     * @return string
     *
     */
    public function build_products_selector($_class, $_active = "") {

        $html = '<select class="' . esc_attr($_class) . ' select">';        

        if ($_active == "" || $_active == -1) {
            $html .= '<option value="-1" selected="selected">'. __( "All Products", "wc-fields-factory" ) .'</option>';
        } else {

            $_active = absint($_active);
            $html .= '<option value="-1">'. __( "All Products", "wc-fields-factory" ) .'</option>';

            if ($_active > 0) {
                $product = wc_get_product($_active);
                if ($product) {
                    $html .= '<option value="' . esc_attr($product->get_id()) . '" selected="selected">' . esc_html($product->get_title()) . '</option>';
                }                
            }            
            
        }

        $html .= '</select>';    	
    	return $html;

    }
    
    /**
     *
     * Generate Product Category List using Select tag ( id => Title )<br>
     * Mostly used in Product Rules widget.
     *
     * @param string $_class
     * @param string $_active
     * @return string
     *
     */
    public function build_products_category_selector($_class, $_active = "") {

        $html = '<select class="' . esc_attr($_class) . ' select">';

        if ($_active == "" || $_active == -1) {
            $html .= '<option value="-1" selected="selected">'. __("All Categories", "wc-fields-factory") .'</option>';
        } else {
            $_active = absint($_active);
            $html .= '<option value="-1">'. __( "All Categories", "wc-fields-factory" ) .'</option>';
            if ($_active > 0) {
                $term = get_term($_active , 'product_cat');
                if ($term) {
                    $html .= '<option value="' . esc_attr($term->term_id) . '" selected="selected">' . esc_html($term->name) . '</option>';
                }
            }
        }

    	$html .= '</select>';
    	return $html;

    }
    
    /**
     *
     * Generate Product Tag List using Select tag ( id => Title )<br>
     * Mostly used in Product Rules widget.
     *
     * @param string $_class
     * @param string $_active
     * @return string
     *
     */
    public function build_products_tag_selector($_class, $_active = "") {

        $html = '<select class="' . esc_attr($_class) . ' select">';
        
        if ($_active == "" || $_active == -1) {
            $html .= '<option value="-1" selected="selected">'. __("All Tags", "wc-fields-factory") .'</option>';
        } else {
            $_active = absint($_active);
            $html .= '<option value="-1">'. __( "All Tags", "wc-fields-factory" ) .'</option>';
            if ($_active > 0) {
                $term = get_term($_active , 'product_tag');
                if ($term) {
                    $html .= '<option value="' . esc_attr($term->term_id) . '" selected="selected">' . esc_html($term->name) . '</option>';
                }
            }
        }

    	$html .= '</select>';
    	return $html;

    }
    
    /**
     *
     * Generate Product Type List using Select tag ( id => Title )<br>
     * Mostly used in Product Rules widget.
     *
     * @param string $_class
     * @param string $_active
     * @return string
     *
     */
    public function build_products_type_selector($_class, $_active = "") {
    	$ptypes = wcff()->dao->load_product_types();
    	$html = '<select class="' . esc_attr($_class) . ' select">';
    	$html .= '<option value="-1">'. __("All Types", "wc-fields-factory") .'</option>';
    	if (count($ptypes) > 0) {
    		foreach ($ptypes as $ptype) {
    			$selected = ($ptype["id"] == $_active) ? 'selected="selected"' : '';
    			$html .= '<option value="' . esc_attr($ptype["id"]) . '" ' . $selected . '>' . esc_html($ptype["title"]) . '</option>';
    		}
    	}
    	$html .= '</select>';
    	return $html;
    }
    
    
    /**
     *
     * Generate Product Type List using Select tag ( id => Title )<br>
     * Mostly used in Product Rules widget.
     *
     * @param string $_class
     * @param string $_active
     * @return string
     *
     */
    public function build_product_variations_selector($_class, $_active = "", $_prod_id = 0) {

        $html = '<select class="variation_product_list">';
        if ($_prod_id == 0 && ($_active == "" || $_active == -1)) {
            $html .= '<option value="0" selected="selected">'. __("All Products", "wc-fields-factory") .'</option>';
        } else {            
            
            if ($_prod_id == 0 && ($_active != "" && $_active != -1)) {
                $_active = absint($_active);
                $pv = wc_get_product($_active);
                if ($pv) {
                    $_prod_id = $pv->get_parent_id();
                }                
            }
            if ($_prod_id > 0) {
                $product = wc_get_product($_prod_id);
                if ($product) {
                    $html .= '<option value="' . esc_attr($product->get_id()) . '" selected="selected">' . esc_html($product->get_title()) . '</option>';
                }                
            }
            
        }        
        $html .= '</select>';

        $html .= '<select class="' . esc_attr($_class) . ' select variation-select">';
        if ($_prod_id == 0) {
            $html .= '<option value="-1" selected="selected">'. __("All Variations", "wc-fields-factory") .'</option>';
        } else {
            $_active = absint($_active);
            $html .= '<option value="-1">'. __( "All Variations", "wc-fields-factory" ) .'</option>';            
            $ptypes = wcff()->dao->load_product_variations($_prod_id);
            if (count($ptypes) > 0) {
                foreach ($ptypes as $ptype) {
                    $selected = ($ptype["id"] == $_active) ? 'selected="selected"' : '';
                    $html .= '<option value="' . esc_attr($ptype["id"]) . '" ' . $selected . '>' . esc_html($ptype["title"]) . '</option>';
                }
            }            
        }
        $html .= '</select>';
    	
    	return $html;
    }
    
    /**
     *
     * Generate Single Product Page's Tab List using Select tag ( Slug => Title )<br>
     * Mostly used in Location Rules widget.
     *
     * @param string $_class
     * @param string $_active
     * @return string
     *
     */
    public function build_products_tabs_selector($_class, $_active = "") {
    	$ptabs = wcff()->dao->load_product_tabs();
    	$html = '<select class="' . esc_attr($_class) . ' select">';
    	if (count($ptabs) > 0) {
    		foreach ($ptabs as $pttitle => $ptvalue) {
    			$selected = ($ptvalue == $_active) ? 'selected="selected"' : '';
    			$html .= '<option value="' . esc_attr($ptvalue) . '" ' . $selected . '>' . esc_html($pttitle) . '</option>';
    		}
    	}
    	$html .= '</select>';
    	return $html;
    }
    
    /**
     *
     * Generate Product Tab List using Select tag ( Slug => Title )<br>
     * Mostly used in Location Rules widget.
     *
     * @param string $_class
     * @param string $_active
     * @return string
     *
     */
    public function build_metabox_context_selector($_class, $_active = "") {
    	$mcontexts = wcff()->dao->load_metabox_contexts();
    	$html = '<select class="' . esc_attr($_class) . ' select">';
    	if (count($mcontexts) > 0) {
    		foreach ($mcontexts as $mckey => $mcvalue) {
    			$selected = ($mckey == $_active) ? 'selected="selected"' : '';
    			$html .= '<option value="' . esc_attr($mckey) . '" ' . $selected . '>' . esc_html($mcvalue) . '</option>';
    		}
    	}
    	$html .= '</select>';
    	return $html;
    }
    
    /**
     *
     * Generate Priority List using Select tag ( Slug => Title )<br>
     * Mostly used in Location Rules widget.
     *
     * @param string $_class
     * @param string $_active
     * @return string
     *
     */
    public function build_metabox_priority_selector($_class, $_active = "") {
    	$mpriorities = wcff()->dao->load_metabox_priorities();
    	$html = '<select class="' . esc_attr($_class) . ' select">';
    	if (count($mpriorities) > 0) {
    		foreach ($mpriorities as $mpkey => $mpvalue) {
    			$selected = ($mpkey == $_active) ? 'selected="selected"' : '';
    			$html .= '<option value="' . esc_attr($mpkey) . '" ' . $selected . '>' . esc_html($mpvalue) . '</option>';
    		}
    	}
    	$html .= '</select>';
    	return $html;
    }
       
    /**
     *
     * Generate Fields List for given wcff post ( Post type could be 'wccpf' or 'wccaf', or 'wccvf' )
     *
     * @param object $_fields
     * @return string
     *
     */
    public function build_wcff_fields_lister($_fields) {
    	$it = 1;   	
    	$wccpf_options = wcff()->option->get_options();
    	$supported_locale = isset($wccpf_options["supported_lang"]) ? $wccpf_options["supported_lang"] : array();
    	$is_multilingual = isset($wccpf_options["enable_multilingual"]) ? $wccpf_options["enable_multilingual"] : "no";
    	
    	global $post;
    	
    	ob_start();
    	
    	foreach ($_fields as $key => $field) :

    		$field_toggle = isset( $field["is_enable"] ) ? 'data-is_enable="'.( $field["is_enable"] ? "true" : "false"  ).'"' : ''; ?>
    		
    		<div class="wcff-meta-row" data-key="<?php echo esc_attr($key); ?>" data-type="<?php echo esc_attr($field["type"]); ?>" data-unremovable="<?php echo esc_attr(isset($field["is_unremovable"]) && $field["is_unremovable"] ? "true" : "false"); ?>" <?php echo $field_toggle; ?>>
				<table class="wcff_table">
					<tbody>
						<tr>
							<td class="field-order wcff-sortable">
								<span class="wcff-field-order-number wcff-field-order"><?php echo esc_html($it++); ?></span>
							</td>
							<td class="field-label">
    							<label class="wcff-field-label" data-key="<?php echo esc_attr($key); ?>"><?php echo esc_html($field["label"]); ?></label>
    							
    							<?php if($is_multilingual == "yes" && count($supported_locale) > 0) : ?>
    							
    								<button class="wcff-factory-multilingual-label-btn" title="Open Multilingual Panel"><img src="<?php echo (esc_url(wcff()->info["assets"] ."/img/translate.png")); ?>"/></button>
    								<div class="wcff-factory-locale-label-dialog">    							
    								<?php 
    									$locales = wcff()->locale->get_locales();
    									foreach ($supported_locale as $code) : ?>    								
    									<div class="wcff-locale-block" data-param="label">
    										<label><?php esc_html_e( 'Label for', 'wc-fields-factory' ); ?> <?php echo esc_html($locales[$code]); ?></label>
    										<input type="text"  name="wcff-field-type-meta-label-<?php echo esc_attr($code); ?>" class="wcff-field-type-meta-label-<?php echo esc_attr($code); ?>" value="" />
    									</div>    			 					
    			 					<?php endforeach; ?>
    								</div>
    							<?php endif; ?>
    						</td>	
                            <td class="field-name">                                                               
                                <label class="wcff-field-name"><?php echo esc_html(isset($field["key"]) ? $field["key"] : ""); ?></label>
                            </td>
							<td class="field-type">
								<label class="wcff-field-type"><span style="background: url(<?php echo esc_url(wcff()->info["assets"] .'/img/'.$field["type"].'.png'); ?>) no-repeat left;"></span><?php echo esc_html($field["type"]); ?></label>
							</td>
							<td class="field-actions">
								<div class="wcff-meta-option">
									<?php $checked = "checked"; 
									if (isset($field["is_enable"])) {
										$checked = $field["is_enable"] ? "checked" : ""; 
									}									
									?>    		
    								<label class="wcff-switch" data-key="<?php echo esc_attr($key); ?>" title="Disable this field"> <input class="wcff-toggle-check" type="checkbox" <?php echo $checked; ?>> <span class="slider round"></span> </label>
    								<?php if ($post->post_type != "wcccf") : ?>
    								<a href="#" class="wcff-field-clone button" data-key="<?php echo esc_attr($key); ?>" title="Clone this Field"><img src="<?php echo esc_url(wcff()->info["assets"] .'/img/clone.png'); ?>" /></a>
    								<?php endif; ?>
    								<?php if (!isset($field["is_unremovable"]) || !$field["is_unremovable"]) : ?>    
    								<a href="#" data-key="<?php echo esc_attr ($key); ?>" class="wcff-field-delete button" title="Delete this field">x</a>
    								<?php endif; ?>
    							</div>
							</td>
						</tr>
					</tbody>
				</table>
				<input type="hidden" name="<?php echo esc_attr($key); ?>_order" class="wcff-field-order-index" value="<?php echo esc_attr($field["order"]); ?>" />
			</div>
    	<?php 
    	endforeach;
    	
    	return ob_get_clean();
    }
    
    /**
     *
     * Generate the fields configuration widget for a given Field Type<br>
     * Heavily relies on 'factory_meta_loop' method.
     *
     * @param string $_fkey
     * @param string $_ftype
     * @param string $_ptype
     * @param integer $_post
     * @return string|boolean
     *
     */
    public function build_factory_widget($_fkey, $_ftype = "text", $_ptype = "wccpf", $_post = null) {
    	$this->fields_values = null;
    	$fields_meta = wcff()->dao->get_fields_meta();
    	$common_meta = apply_filters("before_render_common_meta", wcff()->dao->get_fields_common_meta(), $_ptype, $_ftype );
    	$wccaf_common_meta = apply_filters("before_render_admin_common_meta", wcff()->dao->get_admin_fields_comman_meta(), $_ptype, $_ftype );
    	/* Load the options */
    	$this->wccpf_options = wcff()->option->get_options();
    	$this->is_multilingual = isset($this->wccpf_options["enable_multilingual"]) ? $this->wccpf_options["enable_multilingual"] : "no";
    	$this->supported_locale = isset($this->wccpf_options["supported_lang"]) ? $this->wccpf_options["supported_lang"] : array();
    	
    	if ($_fkey && $_post) {
    	    $this->fields_values = wcff()->dao->load_field($_post, $_fkey); 
    		
    	}
    	
    	/* Lets begin */
    	if (isset($fields_meta[$_ftype])) {
    		$html = '';
    		$fields_meta[$_ftype] = apply_filters( "before_render_field_meta", $fields_meta[$_ftype], $_ptype, $_ftype, $_post, $_fkey );    		
    		/* Make sure whether this field is supported for the given Post Type */
    		if (in_array($_ptype, $fields_meta[$_ftype]["support"])) {
    			if (isset($fields_meta[$_ftype]["document"]) && ! empty($fields_meta[$_ftype]["document"])) {
    				/* Insert a config row for Documentation Link */
    				$html .= '<tr>';
    				/* Left container TD starts here */
    				$html .= '<td class="summary">';
    				$html .= '<label>Documentation</label>';
    				$html .= '<p class="description">Reference documentation for ' . esc_html($fields_meta[$_ftype]["title"]) . '</p>';
    				$html .= '</td>';
    				/* Left container TD ends here */
    				/* Right container TD starts here */
    				$html .= '<td>';
    				$html .= '<a href="' . esc_url($fields_meta[$_ftype]["document"]) . '" target="_blank" title="Click here for documentation">How to use this.?</a>';
    				$html .= '<a href="#" class="wcff-field-update-btn button button-primary button-large">Update Field</a>';
    				$html .= '</td>';
    				/* Right container TD ends here */
    				$html .= '<tr>';
    			}
    			/* Field's specific metas */
    			$html .= $this->factory_meta_loop($fields_meta[$_ftype]["meta"], $_ftype, $_ptype);
    			/* Include common meta */
    			$html .= $this->factory_meta_loop($common_meta, $_ftype, $_ptype);
    			/* Include common meta specif to Admin Field */
    			if ($_ptype == "wccaf") {
    				$html .= $this->factory_meta_loop($wccaf_common_meta, $_ftype, $_ptype);
    			}
    			
    			/* Now we have the complete set of HTML elements generated for Fields Meta
    			 * Let's wrap it with config container along with other configurations */
    			
    			$pricing_config_tab = "";
    			$fields_rule_config_tab = "";
    			$color_to_image_config_tab = "";
    			$meta_config_tab = $this->get_config_field_meta_tab($html);
    			
    			if ($_ftype != "email" && $_ftype != "label" && $_ftype != "hidden" && $_ftype != "file") {
    				$pricing_config_tab = $this->get_config_pricing_rules_tab();
    				$fields_rule_config_tab = $this->get_config_field_rules_tab();
    			}
    			
    			if ($_ftype == "colorpicker") {
    				$color_to_image_config_tab = $this->get_config_image_for_color_tab();
    			}
    			    			
    			$config_widget = $this->get_config_tab_container(
    				$_ptype, 
    				$_ftype, 
    				$meta_config_tab, 
    				$pricing_config_tab, 
    				$fields_rule_config_tab, 
    				$color_to_image_config_tab
    			);  
    			
    			/* Fill the field values - if it is a newly created one */
    			if (!$this->fields_values) {
    				$this->fields_values = array (
    					"id" => $_fkey,  
    				    "key" => $_fkey,
    					"type" => $_ftype
    				);
    			}
    			
    			return array (
    				"id" => $_fkey,    			    
    				"meta" => $this->fields_values,
    				"widget" => $config_widget
    			);
    		}
    	}
    	return false;
    }
    
    /**
     *
     * Iterate through field's config meta and generate the factory widget for a given fields type.
     *
     * @param array $_metas
     * @param string $_ftype
     * @param string $_ptype
     * @return string
     *
     */
    private function factory_meta_loop($_metas = array(), $_ftype = "text", $_ptype = "wccpf") {
    	$html = '';
    	/* Iterate over all the meta for and construct the HTML */
    	foreach ($_metas as $meta) {
    		/* Special property used only on Textarea method */
    		$meta["ftype"] = $_ftype;

            /* Radio button Render Method config not needed for wccaf */
            if ($_ftype == "radio" && $_ptype == "wccaf" && ($meta["param"] == "render_method" || $meta["param"] == "option_preview")) {
                continue;
            }

    		/* Make sure this attribute is available for this field type */
    		if (isset($meta["include_if_not"]) && !empty($meta["include_if_not"]) && in_array ($_ftype, $meta["include_if_not"])) {
    			continue;
    		}
    		if ($_ptype == "wccaf" && isset ($meta["param"]) && $meta["param"] == "visibility" && $_ftype != "image") {
    			/*
    			 * This meta has to be inserted above the visibility config
    			 * Only for the Admin Fields, since the sequence is impossible with normal flow
    			 * we are forced to hard code it here
    			 */
    			$html .= $this->build_factory_meta_wrapper(array (
    				"label" => __ ( 'Show on Product Page', 'wc-fields-factory' ),
    				"desc" => __ ( 'Whether to show this custom admin field on front end product page.', 'wc-fields-factory' ),
    				"type" => "radio",
    				"param" => "show_on_product_page",
    				"layout" => "vertical",
    				"options" => array (
    					array (
    						"value" => "yes",
    						"label" => __ ( 'Show in Product Page', 'wc-fields-factory' ),
    						"selected" => false
    					),
    					array (
    						"value" => "no",
    						"label" => __ ( 'Hide in Product Page', 'wc-fields-factory' ),
    						"selected" => true
    					)
    				),
    				"include_if_not" => array (
    					"image"
    				),
    				"at_startup" => "show",
    				"translatable" => "no"
    			), $_ptype);
    		}
    		/* Well time to wrap it */
    		$html .= $this->build_factory_meta_wrapper($meta, $_ptype);
    		/*
    		 * Include the role list selector config
    		 * after the "Logged in Users Only" config
    		 */
    		if (isset($meta["param"]) && $meta["param"] == "login_user_field") {
    			global $wp_roles;
    			$role_list = array();
    			foreach ($wp_roles->roles as $handle => $role) {
    				$role_list[] = array (
    					"value" => $handle,
    					"label" => $role["name"]
    				);
    			}
    			$html .= $this->build_factory_meta_wrapper(array (
    				"label" => __ ( 'Target Roles', 'wc-fields-factory' ),
    				"desc" => __ ( 'Show this field if only the logged in user has the following roles.', 'wc-fields-factory' ),
    				"type" => "checkbox",
    				"param" => "show_for_roles",
    				"layout" => "horizontal",
    				"options" => $role_list,
    				"include_if_not" => array (
    					"image"
    				),
    				"at_startup" => "hide",
    				"translatable" => "no"
    			), $_ptype);
    		}
    		
    		if (isset($meta["param"]) && $meta["param"] == "timepicker") {
    			$html .= $this->build_factory_meta_wrapper(array (
    				"label" => __ ( 'Allowed Hours & Minutes', 'wc-fields-factory' ),
    				"desc" => __ ( 'Specify minimum and maximum hours & minutes that user can select from.', 'wc-fields-factory' ),
    				"type" => "html",
    				"param" => "min_max_hours_minutes",
    				"html" => '<div class="wccpf-datepicker-min-max-wrapper"><input type="text" id="wccpf-datepicker-min-max-hours" placeholder="0:23" value=""/> <strong>:</strong> <input type="text" id="wccpf-datepicker-min-max-minutes" placeholder="0:59" value=""/></div>',
    				"include_if_not" => array (),
    				"at_startup" => "hide",
    				"translatable" => "no"
    			), $_ptype);
    		}
    	}
    	
    	return $html;
    }
    
    /**
     *
     * @param object $_meta
     * @param string $_ptype
     * @return string
     *
     */
    private function build_factory_meta_wrapper($_meta, $_ptype) {
    	/* Meta row TR starts here */
    	$html = '<tr style="' . esc_attr((($_ptype == "wccaf" || $_meta["param"] == "show_for_roles" || $_meta["param"] == "min_max_hours_minutes") && isset($_meta["at_startup"]) && $_meta["at_startup"] == "hide") ? "display:none;" : "") . '">';
    	
    	/* Left container TD starts here */
    	$html .= '<td class="summary">';
    	$html .= '<label>' . esc_html($_meta["label"]) . '</label>';
    	$html .= '<p class="description">' . esc_html($_meta["desc"]) . '</p>';
    	$html .= '</td>';
    	/* Left container TD ends here */
    	
    	/* Add a padding right for the translate button - if the field is translatable */
    	
    	$padding_right = ($this->is_multilingual == "yes" && $_meta["translatable"] == "yes") ? 'padding-right: 60px;' : '';
    	/* Right container TD starts here */
    	$html .= '<td style="' . $padding_right . '">';
    	
    	if ($this->is_multilingual == "yes" && $_meta["translatable"] == "yes") {
    		$html .= '<button class="wcff-factory-multilingual-btn" title="Open Multilingual Panel"><img src="' . (esc_url(wcff()->info["dir"] . "assets/img/translate.png")) . '"/></button>';
    	}
    	
    	if ($_meta["type"] != "tab") {
    		/* Meta field's wrapper starts here */
    	    $html .= '<div class="wcff-field-types-meta" data-type="' . esc_attr($_meta["type"]) . '" data-param="' . esc_attr($_meta["param"]) . '">';
    		$html .= $this->build_factory_meta_field($_meta, $_ptype);
    		$html .= '</div>';
    		/* Meta field's wrapper ends here */
    		
    		/* If this confog option is translatable then add those fields as well */
    		if ($this->is_multilingual == "yes" && count($this->supported_locale) > 0 && $_meta["translatable"] == "yes") {
    			$locales = wcff()->locale->get_locales();
    			$html .= '<div class="wcff-locale-list-wrapper" style="display: none;">';
    			if ($_meta["param"] != "default_value") {
    				foreach ($this->supported_locale as $code) {
    				    $html .= '<div class="wcff-locale-block" data-param="' . esc_attr($_meta["param"]) . '">';
    					$html .= '<label>' . esc_html($_meta["label"] . ' for ' . $locales[$code]) . '</label>';
    					if ($_meta["type"] == "text") {
    					    $html .= '<input type="text" name="wcff-field-type-meta-' . esc_attr($_meta["param"] . '-' . $code) . '" class="wcff-field-type-meta-' . esc_attr($_meta["param"] . '-' . $code) . '" value="" />';
    					} else {
    						if ($_meta["ftype"] != "label") {
    							/* This must for the Choices option */
    							$html .= '<table class="wcff-choice-factory-container">';
    							$html .= '<tbody>';
    							$html .= '<tr>';
    							$html .= '<td class="field">';
    							$html .= '<div class="wcff-locale-block" data-param="' . esc_attr($_meta["param"]) . '">';
    							$html .= '<textarea name="wcff-field-type-meta-' . esc_attr($_meta["param"] . '-' . $code) . '" data-locale="' . esc_attr($code) . '" class="wcff-choices-textarea"></textarea>';
    							$html .= '</div>';
    							$html .= '</td>';
    							$html .= '<td class="factory">';
    							$html .= '<input type="text" class="wcff-option-value-text" placeholder="Type the ' . esc_attr($locales[$code]) . ' Value">';
    							$html .= '<input type="text" class="wcff-option-label-text" placeholder="Type the ' . esc_attr($locales[$code]) . ' Label">';
    							$html .= '<button class="wcff-add-opt-btn" data-target="wcff-field-type-meta-' . esc_attr($_meta["param"] . '-' . $code) . '" data-target-param="' . esc_attr($_meta["param"]) . '" data-ftype="' . esc_attr($_meta["ftype"]) . '">Add Option</button>';
    							$html .= '</td>';
    							$html .= '</tr>';
    							$html .= '</tbody>';
    							$html .= '</table>';
    						} else {
    						    $html .= '<div class="wcff-locale-block" data-param="' . esc_attr($_meta["param"]) . '">';
    						    $html .= '<textarea name="wcff-field-type-meta-' . esc_attr($_meta["param"] . '-' . $code) . '" data-locale="' . esc_attr($code) . '" class="wcff-label-message-textarea"></textarea>';
    							$html .= '</div>';
    						}
    					}
    					$html .= '</div>';
    				}
    			} else {
    				if ($_meta["ftype"] == "select" || $_meta["ftype"] == "radio" || $_meta["ftype"] == "checkbox") {
    					/*
    					 * Since we are using real time option creation for default_value param
    					 * We just need to put warpper and let the client side module handle the rest
    					 */
    					foreach ($this->supported_locale as $code) {
    						$html .= '<div>';
    						$html .= '<label>' . esc_html($_meta["label"] . ' for ' . $locales[$code]) . '</label>';
    						$html .= '<div class="wcff-default-choice-wrapper wcff-default-option-holder-' . esc_attr($code) . '"></div>';
    						$html .= '</div>';
    					}
    				} else {
    					foreach ($this->supported_locale as $code) {
    					    $html .= '<div class="wcff-locale-block" data-param="' . esc_attr($_meta["param"]) . '">';
    						$html .= '<label>' . esc_html($_meta["label"] . ' for ' . $locales[$code]) . '</label>';
    						$html .= '<input type="text" name="wcff-field-type-meta-' . esc_attr($_meta["param"] . '-' . $code) . '" class="wcff-field-type-meta-' . esc_attr($_meta["param"] . '-' . $code) . '" value="" />';
    						$html .= '</div>';
    					}
    				}
    			}
    			$html .= '</div>';
    		}
    		
    		/* Some times there are two fields for the same meta attribute */
    		if (isset($_meta["additonal"])) {
    			/* Meta field's wrapper starts here */
    		    $html .= '<div class="wcff-field-types-meta" data-type="' . esc_attr($_meta["additonal"]["type"]) . '" data-param="' . esc_attr($_meta["additonal"]["param"]) . '">';
    			$html .= $this->build_factory_meta_field($_meta["additonal"], $_ptype);
    			$html .= '</div>';
    			/* Meta field's wrapper ends here */
    		}
    	} else {
    		$html .= $this->build_factory_meta_tab_widget($_meta, $_ptype);
    	}
    	
    	$html .= '</td>';
    	/* Right container TD ends here */
    	
    	$html .= '</tr>';
    	/* Meta row TR ends here */
    	
    	return $html;
    }
    
    /**
     *
     * Helper method which delegateS the task to other method to generate fields for Factory Widget
     *
     * @param object $_meta
     * @param string $_ptype
     * @return string
     *
     */
    private function build_factory_meta_field($_meta, $_ptype) {
    	$html = '';
    	/* Meta field starts here */
    	if ($_meta["type"] == "text" || $_meta["type"] == "email" || $_meta["type"] == "number" || $_meta["type"] == "password") {
    		$html = $this->build_factory_meta_input_field($_meta, $_ptype);
    	} else if ($_meta["type"] == "textarea") {
    		$html = $this->build_factory_meta_textarea_field($_meta, $_ptype);
    	} else if ($_meta["type"] == "radio" || $_meta["type"] == "checkbox") {
    		$html = $this->build_factory_meta_option_field($_meta, $_ptype);
    	} else if ($_meta["type"] == "select") {
    		$html = $this->build_factory_meta_select_field($_meta, $_ptype);
    	} else if ($_meta["type"] == "html") {
    		$html = $_meta["html"];
    	} else {
    		/* Unlikely */
    		$html = '';
    	}
    	/* Meta field ends here */
    	return $html;
    }
    
    /**
     *
     * Generate Input fields for Fcatory Widget
     *
     * @param object $_meta
     * @param string $_ptype
     * @return string
     *
     */
    private function build_factory_meta_input_field($_meta, $_ptype="wccpf") {
    	$value = "";
    	if ($this->fields_values && $this->fields_values[$_meta["param"]]) {
    		$value = $this->fields_values[$_meta["param"]];
    	}
    	return '<input type="'. esc_attr($_meta["type"]) .'" name="wcff-field-type-meta-'. esc_attr($_meta["param"]) .'" class="wcff-field-type-meta-'. esc_attr($_meta["param"]) .'" placeholder="'. esc_attr($_meta["placeholder"]) .'" value="'. esc_attr($value) .'" />';
    }
    
    /**
     *
     * Generate Textarea field for Factory Widget
     *
     * @param object $_meta
     * @param string $_ptype
     * @return string
     *
     */
    private function build_factory_meta_textarea_field($_meta, $_ptype = "wccpf") {
    	$html = '';
    	$value = "";
    	if ($this->fields_values && isset($this->fields_values[$_meta["param"]])) {
    		$value = $this->fields_values[$_meta["param"]];
    	}
    	if ($_meta["param"] == "choices" && ($_meta["ftype"] == "radio" || $_meta["ftype"] == "checkbox" || $_meta["ftype"] == "select")) {
    		$html = '<table class="wcff-choice-factory-container">';
    		$html .= '<tr>';
    		$html .= '<td class="field">';
    		$html .= '<textarea name="wcff-field-type-meta-' . esc_attr($_meta["param"]) . '" class="wcff-field-type-meta-' . esc_attr($_meta["param"]) . '" class="wcff-choices-textarea" placeholder="' . esc_attr($_meta["placeholder"]) . '" rows="' . esc_attr($_meta["rows"]) . '"></textarea>';
    		$html .= '</td>';
    		$html .= '<td class="factory">';
    		
    		$html .= '<input type="text" class="wcff-option-value-text" placeholder="Type the Value" />';
    		$html .= '<input type="text" class="wcff-option-label-text" placeholder="Type the Label" />';
    		$html .= '<button class="wcff-add-opt-btn" data-target="wcff-field-type-meta-' . esc_attr($_meta["param"]) . '" data-target-param="' . esc_attr($_meta["param"]) . '" data-ftype="' . esc_attr($_meta["ftype"]) . '">Add Option</button>';
    		
    		$html .= '</td>';
    		$html .= '</tr>';
    		$html .= '</table>';
    	} else {
    	    $html = '<textarea name="wcff-field-type-meta-' . esc_attr($_meta["param"]) . '" class="wcff-field-type-meta-' . esc_attr($_meta["param"]) . '" placeholder="' . esc_attr($_meta["placeholder"]) . '" rows="' . esc_attr($_meta["rows"]) . '">'. esc_html($value) .'</textarea>';
    	}
    	return $html;
    }
    
    /**
     *
     * Generate Check Box as well as Radio Button fields for Factory Widget
     *
     * @param object $_meta
     * @param string $_ptype
     * @return string
     *
     */
    private function build_factory_meta_option_field($_meta, $_ptype = "wccpf") {
        $name = ($_meta["type"] == "radio") ? 'name="options-'. esc_attr($_meta["param"]) .'"' : '';
    	$html = '<ul class="wcff-field-layout-' . $_meta["layout"] . '">';
    	foreach ($_meta["options"] as $option) {
    		$checked = '';
    		if ($this->fields_values && isset($this->fields_values[$_meta["param"]])) {
    			if ($_meta["type"] == "checkbox") {
    				/* We have an array situation here */
    				if (in_array($option["value"], $this->fields_values[$_meta["param"]])) {
    					$checked = 'checked';
    				}
    			} else {
    				/* Straight forward comparision */
    				if ($option["value"] == $this->fields_values[$_meta["param"]]) {
    					$checked = 'checked';
    				}
    			}
    		} else {
    			if (isset($option["selected"]) && $option["selected"]) {
    				$checked = 'checked';
    			}
    		}
    		$html .= '<li><label><input '. $name .' type="' . esc_attr($_meta["type"]) . '" class="wcff-field-type-meta-' . esc_attr($_meta["param"]) . '" value="' . esc_attr($option["value"]) . '" ' . $checked . ' /> ' . esc_html($option["label"]) . '</label></li>';
    	}
    	$html .= '</ul>';
    	return $html;
    }
    
    /**
     *
     * Generate Select field for Factory Widget
     *
     * @param object $_meta
     * @param string $_ptype
     * @return string
     *
     */
    private function build_factory_meta_select_field($_meta, $_ptype = "wccpf") {
        $html = '<select name="wcff-field-type-meta-' . esc_attr($_meta["param"]) . '" class="wcff-field-type-meta-' . esc_attr($_meta["param"]) . '">';
    	foreach ($_meta["options"] as $option) {
    		$selected = '';
    		if ($this->fields_values && isset($this->fields_values[$_meta["param"]])) {
    			if ($option["value"] == $this->fields_values[$_meta["param"]]) {
    				$selected = 'selected';
    			}    			
    		} else {
    			if (isset($option["selected"]) && $option["selected"]) {
    				$selected = 'selected';
    			}
    		}    		
    		$html .= '<option value="' . esc_attr($option["value"]) . '" ' . $selected . '>' . esc_html($option["label"]) . '</option>';
    	}
    	$html .= '</select>';
    	return $html;
    }
    
    /**
     *
     * Generate Tab widget for Factory Widget<br>
     * Like the one used in the Date Field config
     *
     * @param object $_meta
     * @param string $_ptype
     * @return string
     *
     */
    private function build_factory_meta_tab_widget($_meta, $_ptype = "wccpf") {
    	/* Accordian wrapper starts here */
    	$html = '<div class="wcff-factory-tab-container">';
    	
    	/* Left side header panel starts here */
    	$html .= '<div class="wcff-factory-tab-left-panel">';
    	$html .= '<ul>';
    	foreach ($_meta["tabs"] as $tab) {
    	    $html .= '<li data-box="' . esc_attr($tab["header"]["target"]) . '" class="' . esc_attr($tab["header"]["css_class"]) . '">' . esc_html($tab["header"]["title"]) . '</li>';
    	}
    	$html .= '</ul>';
    	$html .= '</div>';
    	/* Left side header anel ends here */
    	
    	/* Left side header panel starts here */
    	$html .= '<div class="wcff-factory-tab-right-panel">';
    	foreach ($_meta["tabs"] as $tab) {
    		/* Tab content section starts here */
    	    $html .= '<div id="' . esc_attr($tab["content"]["container"]) . '" class="wcff-factory-tab-content">';
    		
    		foreach ($tab["content"]["fields"] as $field) {
    			/* Meta field's wrapper starts here */
    		    $html .= '<div class="wcff-field-types-meta" data-type="' . esc_attr($field["type"]) . '" data-param="' . esc_attr($field["param"]) . '">';
    			$html .= $this->build_factory_meta_field($field, $_ptype);
    			$html .= '</div>';
    			/* Meta field's wrapper ends here */
    		}
    		
    		$html .= '</div>';
    		/* Tab content section ends here */
    	}
    	$html .= '</div>';
    	/* Left side header anel ends here */
    	
    	$html .= '</div>';
    	/* Accordian wrapper ends here */
    	return $html;
    }
    
    public function get_config_tab_container($_post_type, $_field_type, $_meta_config, 
    		$_pricing_config, $_fields_rule_config, $_color_config) { ob_start(); ?>    	
    	<div class="wcff_fields_factory wcff_fields_factory_config_wrapper" action="POST" style="display: none;">
        	<div class="wcff_fields_factory_config_container">
        		<?php if ($_post_type == "wccpf" || $_post_type == "wccvf") : ?>
            	<div class="wcff-factory-tab-header">
        			<a href=".wcff-factory-tab-fields-meta" class="selected"><?php esc_html_e( 'Field Meta', 'wc-fields-factory' ); ?></a>		
        			<?php if ($_field_type != "email" && $_field_type != "label" && $_field_type != "hidden" && $_field_type != "file") : ?>
        			<a href=".wcff-factory-tab-pricing-rules"><?php esc_html_e( 'Pricing Rules', 'wc-fields-factory' ); ?></a>	
        			<a href=".wcff-factory-tab-fields-rules"><?php esc_html_e( 'Field Rules', 'wc-fields-factory' ); ?></a>
        			<?php endif; ?>
        			<?php if ($_field_type == "colorpicker") : ?>
                	<a href=".wcff-factory-tab-color-image"><?php esc_html_e( 'Colors to Images', 'wc-fields-factory' ); ?></a>
                	<?php endif; ?>
        		</div>
        		<?php endif; ?>
        		<div class="wcff-factory-tab-container">
        			<!-- Fields Meta Config Container-->
        			<?php echo $_meta_config; ?>        			
        			<!-- Fields Pricing config Container -->
        			<?php echo $_pricing_config; ?>
        			<!-- Fields Rules Container -->
        			<?php echo $_fields_rule_config; ?>
        			<!-- Color to Image imapping Container -->
        			<?php echo $_color_config; ?>
        		</div>
        	</div>
        	
        	<table class="wcff_table wcff-fields-update-footer">
        		<tr>
        			<td class="summary"></td>
        			<td class=""><a href="#" class="wcff-field-update-btn button button-primary button-large">Update Field</a></td>
        		</tr>        	
        	</table>
        </div>    	
    	<?php 
    	return ob_get_clean();
    }
    
    public function get_config_field_meta_tab($_html) { ob_start(); ?>
    	<div class="wcff-field-types-meta-container wcff-factory-tab-child wcff-factory-tab-fields-meta" style="display:block;">
    		<table class="wcff_table">
    			<tbody class="wcff-field-types-meta-body">				
    			<!-- Meta config fields will be included here -->
    			<?php echo $_html; ?>	
    			</tbody>
    		</table>
    	</div>
    	<?php 
    	return ob_get_clean();	
    }
    
    public function get_config_pricing_rules_tab() { ob_start(); ?>
    	<div class="wcff-factory-tab-child wcff-factory-tab-pricing-rules">			
    		<table class="wcff_table">
    			<tbody class="wcff-field-types-meta-body">
    				<tr>
    					<td class="summary">
    						<label for="post_type"><a href="https://sarkware.com/pricing-fee-rules-wc-fields-factory/" target="_blank" title="<?php esc_html_e( 'Documentation', 'wc-fields-factory' ); ?>"><?php esc_html_e( 'Click here for Documentation', 'wc-fields-factory' ); ?></a></label>
    						<br/>
    						<label for="post_type"><?php esc_html_e( 'Pricing Rules', 'wc-fields-factory' ); ?></label>
    						<p class="description"><?php esc_html_e( 'Change the product price whenever user submit the product along with this field', 'wc-fields-factory' ); ?></p>
    						<br/>
    						<label for="post_type"><?php esc_html_e( 'How it works', 'wc-fields-factory' ); ?></label>
    						<p class="description"><?php esc_html_e( 'Use "Add Pricing Rule" button to add add a rule, specify the field value and the corresponding price, when the user submit the field with the given value while adding to cart, then the given price will be applied to the submitted product', 'wc-fields-factory' ); ?></p>
    						<br/>
    						<label for="post_type"><?php esc_html_e( 'Pricing Type', 'wc-fields-factory' ); ?></label>
    						<p class="description"><?php esc_html_e( '<strong>Add :</strong> this option will add the given price with the product amount<br/><strong>Change :</strong> this option will replace the product original price with the given one', 'wc-fields-factory' ); ?></p>							
    					</td>
    					<td style="vertical-align: top;"  class="wcff-content-config-cell">
    						<div class="wcff-tab-rules-wrapper price" class="wcff-factory-pricing-rules-wrapper">	
                                <div class="wcff-parent-rule-title"><?php esc_html_e( 'Pricing Rules', 'wc-fields-factory' ); ?></div>
                                <div class="wcff-rule-container">                                	
                                   	<div class="wcff-rule-container-is-empty"><?php esc_html_e( 'Pricing rule is empty!', 'wc-fields-factory' ); ?></div>                                   	
                                </div>																
    							<input type="button" class="wcff-add-price-rule-btn button" value="<?php esc_attr_e( 'Add Pricing Rule', 'wc-fields-factory' ); ?>">
    						</div>
    						<div class="wcff-tab-rules-wrapper fee" class="wcff-factory-fee-rules-wrapper">	
                                <div class="wcff-parent-rule-title"><?php esc_html_e( 'Fee Rules', 'wc-fields-factory' ); ?></div>	
                                <div class="wcff-rule-container">
                                    <div class="wcff-rule-container-is-empty"><?php esc_html_e( 'Fee rule is empty!', 'wc-fields-factory' ); ?></div>
                                </div>													
    							<input type="button" class="wcff-add-fee-rule-btn button" class="button" value="<?php esc_attr_e( 'Add Fee Rule', 'wc-fields-factory' ); ?>">
    						</div>
    						<input type="hidden" name="wcff_pricing_rules" class="wcff_pricing_rules" value="" />
    						<input type="hidden" name="wcff_fee_rules" class="wcff_fee_rules" value="" />
    					</td>
    				</tr>					
    			</tbody>
    		</table>		
    	</div>
    	<?php 
    	return ob_get_clean();
    }
    
    public function get_config_field_rules_tab() { ob_start(); ?>
    	<div class="wcff-factory-tab-child wcff-factory-tab-fields-rules">			
    		<table class="wcff_table">
    			<tbody class="wcff-field-types-meta-body">
    				<tr>
    					<td class="summary">
    						<label for="post_type"><a href="https://sarkware.com/field-rule-wc-fields-factory/" target="_blank" title="<?php esc_attr_e( 'Documentation', 'wc-fields-factory' ); ?>"><?php esc_html_e( 'Click here for Documentation', 'wc-fields-factory' ); ?></a></label>
    						<br/>
    						<label for="post_type"><?php esc_html_e( 'Field Rules', 'wc-fields-factory' ); ?></label>
    						<p class="description"><?php esc_html_e( 'Hide or show fields based on user interaction.', 'wc-fields-factory' ); ?></p>
    						<br/>
    						<label for="post_type"><?php esc_html_e( 'How it works', 'wc-fields-factory' ); ?></label>
    						<p class="description"><?php esc_html_e( 'Use &apos;Add Field rule&apos; to add a field rule, specify the field value and select a condition. Then choose which are the field want to hide or show.', 'wc-fields-factory' ); ?></p>
    						<br/>
    						<label for="post_type"><?php esc_html_e( 'Rule Type', 'wc-fields-factory' ); ?></label>
    						<p class="description"><?php esc_html_e( '<strong>Hide :</strong> Field will be hidden if the condition met. <br/><strong>Show :</strong> Field will be visible if the condition met.<br/><strong>Nill :</strong> Doesn&apos;t affect.', 'wc-fields-factory' ); ?></p>							
    					</td>
    					<td style="vertical-align: top;" class="wcff-content-config-cell">
    						<div class="wcff-tab-rules-wrapper field">		
                               <div class="wcff-parent-rule-title"><?php esc_html_e( 'Field Rules', 'wc-fields-factory' ); ?></div>	
                               <div class="wcff-rule-container">
                                   <div class="wcff-rule-container-is-empty"><?php esc_html_e( 'Field rule is empty!', 'wc-fields-factory' ); ?></div>
                               </div>																											
    							<input type="button" class="wcff-add-field-rule-btn button wcff-add-field-rule-btn" value="<?php esc_attr_e( 'Add Field Rule', 'wc-fields-factory' ); ?>">
    						</div>
    					</td>
    				</tr>					
    			</tbody>
    		</table>		
    	</div>
    	<?php 
    	return ob_get_clean();
    }
    
    public function get_config_image_for_color_tab() { ob_start(); ?>
    	<div class="wcff-factory-tab-child wcff-factory-tab-color-image" style="display:none;">
           <table class="wcff_table">
    			<tbody class="wcff-field-types-meta-body">
    				<tr>
    					<td class="summary">
    						<label for="post_type"><a href="https://sarkware.com/field-rule-wc-fields-factory/" target="_blank" title="<?php esc_attr_e( 'Documentation', 'wc-fields-factory' ); ?>"><?php esc_html_e( 'Click here for Documentation', 'wc-fields-factory' ); ?></a></label>
    						<br/>
    						<label for="post_type"><?php esc_html_e( 'Product Image', 'wc-fields-factory' ); ?></label>
    						<p class="description"><?php esc_html_e( 'Choose your color pallet and perticular color based image.', 'wc-fields-factory' ); ?></p>
                            <br/>
    						<label for="post_type"><?php esc_html_e( 'Choose Option', 'wc-fields-factory' ); ?></label>
    						<p class="description"><?php esc_html_e( 'Choose image or color related another product.', 'wc-fields-factory' ); ?></p>
    					</td>
    					<td style="vertical-align: top;" class="wcff-content-config-cell">
    						<div class="wcff-tab-rules-wrapper color-image">		
                               <div class="wcff-parent-rule-title"><?php esc_html_e( 'Color Image', 'wc-fields-factory' ); ?></div>	
                               <div class="wcff-rule-container">
                                   <div class="wcff-rule-container-is-empty"><?php esc_html_e( 'Product Image rule is empty!', 'wc-fields-factory' ); ?></div>
                               </div>
    							<input type="button" class="wcff-add-color-image-rule-btn button wcff-add-color-image-rule-btn" value="<?php esc_attr_e( 'Add Field Rule', 'wc-fields-factory' ); ?>">
    						</div>
    					</td>
    				</tr>					
    			</tbody>
    		</table>		
    	</div>
    	<?php 
    	return ob_get_clean();
    }

    /**
     * 
     * Used by layout designer
     * Responsible for rendering all fields for the layout designer to inflate the fields
     * 
     */
    public function build_user_fields($_pid, $_payload = array()) {
        
        $_pid = abs($_pid);
        $widgets = array();        

        if (is_array($_payload["keys"])) {
            for ($i = 0; $i < count($_payload["keys"]); $i++) {
                $field = get_post_meta($_pid, $_payload["keys"][$i], true);
                $field = json_decode($field, true);
                if (isset($field["key"]) || isset($field["name"])) {                    
                    $field["label_alignment"] = $_payload["alignment"];                    
                    $widgets[$_payload["keys"][$i]] = $this->build_user_field($field);
                }
            }
        }

        return $widgets;

    }
    
    /**
     *
     * Primary handler for generating Fields, which will be injected into Single Product Page<br>
     * It evoluate the field's meta and delegate the task to various helper methods the get the fields HTML<br>
     * Even the Admin fields which some times injected into Product Page also achived via this method<br>
     * If the fields not need to be wrapped with the default fields wrapper then call this method with @$_wrapper=false
     *
     * @param object $_meta
     * @param string $_ptype
     * @param string $_wrapper
     * @return string|string|mixed
     *
     */
    public function build_user_field($_meta, $_ptype = "wccpf", $_global_clonable = "no", $_group_clonable = "no", $_wrapper = true) { 
        
        $html = ''; 
        
        /* Update the mode */
        $this->fields_mode = "front";
        
        /* Load the config option object */
        $wccpf_options = wcff()->option->get_options();
        
        /* Whether to add numeric index to the name attribute ( yes incase of fields cloning enabled ) */
        $name_index = ($_global_clonable == "yes" && $_ptype != "wcccf") ? "_1" : ""; 
        
        $readonly = isset($_meta["show_as_read_only"]) ? $_meta["show_as_read_only"] : "no";
        $readonly = ($readonly == "yes") ? "disabled" : "";
        
        $cloneable = isset($_meta["cloneable"]) ? $_meta["cloneable"] : "yes";
        $cloneable = ($_group_clonable == "yes") ? ('data-cloneable="'. $cloneable .'"') : '';
        
        $show_as_value = isset($_meta["showin_value"]) ? $_meta["showin_value"] : "no";
        $is_private = isset($_meta["login_user_field"]) ? $_meta["login_user_field"] : "no";
        $field_class = isset($_meta["field_class"]) ? $_meta["field_class"] : "";

        /* check is pricing rules is availbe */
        $has_pricing_rule = isset( $_meta["pricing_rules"] ) && isset($wccpf_options["enable_ajax_pricing_rules"]) && $wccpf_options["enable_ajax_pricing_rules"] == "enable" ? "yes" : "no";
        if ($is_private == "yes" && ! is_user_logged_in()) {
            /* Well looks like this field is available only for logged in users */
            return "";
        }
        
        /* Check for roles */
        if ($is_private == "yes" && (isset($_meta["show_for_roles"]) && is_array($_meta["show_for_roles"]) && !empty($_meta["show_for_roles"]))) {
            $can = false;
            foreach ($_meta["show_for_roles"] as $role) {
                if (current_user_can($role)) {
                    $can = true;
                }
            }
            if (!$can) {
                /* User not have the role */
                return "";
            }
        }
        
        /* This option specific to admin fields opted to show on product page */
        if ($show_as_value == "no") {
        
            /* Identify the field's type and start rendering */
            if ($_meta["type"] == "text" || $_meta["type"] == "email" || $_meta["type"] == "number" || $_meta["type"] == "datepicker" || $_meta["type"] == "colorpicker") {
                $html = $this->build_input_field($_meta, $_ptype, $field_class, $name_index, $readonly, $cloneable, $_wrapper, $has_pricing_rule);
            } else if ($_meta["type"] == "textarea") {
                $html = $this->build_textarea_field($_meta, $_ptype, $field_class, $name_index, $readonly, $cloneable, $_wrapper, $has_pricing_rule);
            } else if ($_meta["type"] == "radio") {
                $radio_type = isset($_meta["render_method"]) ? $_meta["render_method"] : "none";
                if ($radio_type == "text") {
                    $html = $this->build_text_radio_choices($_meta, $_ptype, $field_class, $name_index, $readonly, $cloneable, $_wrapper, $has_pricing_rule);
                } else if ($radio_type == "color") {
                    $html = $this->build_color_radio_choices($_meta, $_ptype, $field_class, $name_index, $readonly, $cloneable, $_wrapper, $has_pricing_rule);
                } else if ($radio_type == "image") {
                    $html = $this->build_image_radio_choices($_meta, $_ptype, $field_class, $name_index, $readonly, $cloneable, $_wrapper, $has_pricing_rule);
                } else {
                    $html = $this->build_radio_field($_meta, $_ptype, $field_class, $name_index, $readonly, $cloneable, $_wrapper, $has_pricing_rule);
                }                
            } else if ($_meta["type"] == "checkbox") {
                $html = $this->build_checkbox_field($_meta, $_ptype, $field_class, $name_index, $readonly, $cloneable, $_wrapper, $has_pricing_rule);
            } else if ($_meta["type"] == "select") {
                $html = $this->build_select_field($_meta, $_ptype, $field_class, $name_index, $readonly, $cloneable, $_wrapper, $has_pricing_rule);
            } else if ($_meta["type"] == "file") {
                $html = $this->build_file_field($_meta, $_ptype, $field_class, $name_index, $readonly, $cloneable, $_wrapper, $has_pricing_rule);
            } else if ($_meta["type"] == "hidden") {
                $html = $this->build_input_field($_meta, $_ptype, $field_class, $name_index, $readonly, $cloneable, false);
            } else if ($_meta["type"] == "url") {
                $html = $this->build_url_field($_meta, $_ptype, $field_class, $name_index, $readonly, $cloneable, $_wrapper);
            } else if ($_meta["type"] == "label") {
                $html = $this->build_label_field($_meta, $_ptype, $field_class, $name_index, $cloneable);
            } else if ($_meta["type"] == "html") {
                $html = $_meta["html"];
            } else {
                /* Unlikely */
                $html = '';
            }
            
        } else {
            
            /*
             * 
             * Show the raw value instead of as a field
             * Used for the Admin Field showing on product page
             *              
             */   
            
            $value = "";
            
            if ($_ptype != "wccaf") {
                if (isset($_meta["default_value"])) {
                    $value = esc_attr($_meta["default_value"]);
                }
            } else {
                if (isset($_meta['value'])) {
                    $value = $_meta['value'];
                }
            }
            
            if ($_meta["type"] != "colorpicker") {
                if ($_meta["type"] != "checkbox") {
                    $html = '<p class="wcff-value-only-tag '. esc_attr($field_class) .'">'. esc_html($value) .'</p>';
                } else {
                    if(!is_array($value)) {
                        $value = array();
                    }
                    $html = '<p class="wcff-value-only-tag '. esc_attr($field_class) .'">'. esc_html(implode(", ", $value)) .'</p>';
                }                
            } else {
                $defaultcolor = isset($_meta["default_value"]) ? $_meta["default_value"] : "#000";
                $html = ($_meta["hex_color_show_in"] == "yes") ? '<span class="wcff-color-picker-color-show" color-code="' . esc_attr($defaultcolor) . '" style="padding: 0px 15px;background-color: ' . esc_attr($defaultcolor) . '"; ></span>' : esc_attr($defaultcolor);
            }

            if ($_wrapper) {
                $html = $this->built_field_wrapper($html, $_meta, $_ptype, $name_index);
            }
            
        }       
        
        /* Final html tag */
        return $html;
        
    }
    
    /**
     *
     * Primary haandler for generating fields, which will be injected into the Product Admin Page, Product Variable Section and Product Category Page.<br>
     * It evoluate the field's meta and delegate the task to various helper methods the get the fields HTML
     *
     * @param object $_meta
     * @return string|mixed
     *
     */
    public function build_admin_field($_meta) {
        
        $html = '';
        
        /* Update the mode */
        $this->fields_mode = "back";
        
        $field_class = isset($_meta["field_class"]) ? $_meta["field_class"] : "";
        /* Add a special class if only for textarea field */
        $field_class .= ($_meta["type"] == "textarea") ? " short" : "";
        /* Add a special class if only for radio field */
        $field_class .= ($_meta["type"] == "radio") ? " select short" : "";
        
        /* Identify the field's type and start rendering */
        if ($_meta["type"] == "text" || $_meta["type"] == "email" || $_meta["type"] == "number" || $_meta["type"] == "datepicker" || $_meta["type"] == "colorpicker") {
            $html = $this->build_input_field($_meta, "wccaf", $field_class, "", "no", "", true);
        } else if ($_meta["type"] == "textarea") {
            $html = $this->build_textarea_field($_meta, "wccaf", $field_class, "", "no", "", true);
        } else if ($_meta["type"] == "radio") {
            $html = $this->build_radio_field($_meta, "wccaf", $field_class, "", "no", "", true);
        } else if ($_meta["type"] == "checkbox") {
            $html = $this->build_checkbox_field($_meta, "wccaf", $field_class, "", "no", "", true);
        } else if ($_meta["type"] == "select") {
            $html = $this->build_select_field($_meta, "wccaf", $field_class, "", "no", "", true);
        } else if ($_meta["type"] == "image") {
            $html = $this->build_image_field($_meta);
        } else if ($_meta["type"] == "url") {
            $html = $this->build_url_field($_meta, "wccaf", $field_class, "", "no", "", true);
        } else if ($_meta["type"] == "html") {
            $html = $_meta["html"];
        } else {
            /* Unlikely */
            $html = '';
        }
        /* Final html tag */
        return $html;
        
    }
    
    /**
     *
     * Helper method for generating Input Fields for both Product as well as Admin
     *
     * @param object $_meta			     : Field's meta
     * @param string $_ptype			     : Post type ( could be 'wccpf' or 'wccaf' )
     * @param string $_class			     : Custom css class for this field
     * @param string $_index			     : If cloning option enabled then this will the Name Index - which will be suffixed with fields name attribute
     * @param string $_readonly			 : Whether this field is read only
     * @param string $_cloneable          : Whether this field is cloneable
     * @param string $_wrapper			 : Whether this field has to wrapped up\
     * @param string $_has_pricing_rules	: Whether this field has pricing rules
     * @return string
     *
     */
    private function build_input_field($_meta, $_ptype = "wccpf", $_class = "", $_index = "", $_readonly = "", $_cloneable = "", $_wrapper = true, $_has_pricing_rules = "no") {
        
        $html = "";        
        $value = "";
        $placeholder = "";
        
        if (!isset($_meta["key"])) {
            return $html;
        }
                
        if ($_ptype != "wccaf") {            
            if (isset($_meta["default_value"])) {
                $value = esc_attr($_meta["default_value"]);
            }            
        } else {            
            if (isset($_meta['value'])) {
                $value = esc_attr($_meta['value']);
            }
        }
        
        if (isset($_meta["placeholder"]) && (! empty($_meta["placeholder"]))) {
            if (isset($_meta["required"]) && $_meta["required"] == "yes") {
                $placeholder = 'placeholder="' . esc_attr($_meta["placeholder"]) . '"';
            } else {
                $placeholder = 'placeholder="' . esc_attr($_meta["placeholder"]) . '"';
            }
        }
                
        $min = (isset($_meta["min"]) && ! empty($_meta["min"])) ? ('min="' . esc_attr($_meta["min"]) . '"') : '';
        $max = (isset($_meta["max"]) && ! empty($_meta["max"])) ? ('max="' . esc_attr($_meta["max"]) . '"') : '';
        $step = (isset($_meta["step"]) && ! empty($_meta["step"])) ? ('step="' . esc_attr($_meta["step"]) . '"') : '';
        $maxlength = (isset($_meta["maxlength"]) && ! empty($_meta["maxlength"])) ? ('maxlength="' . esc_attr($_meta["maxlength"]) . '"') : '';
        $has_field_rules = isset($_meta["field_rules"]) && is_array($_meta["field_rules"]) && count($_meta["field_rules"]) != 0 ? "yes" : "no";
        
        /* Some fields doesn't has required config option */
        $_meta["required"] = isset($_meta["required"]) ? $_meta["required"] : "no";

        /* Prepare the common attributes */
        if ($_ptype == "wccaf" && $_meta["type"] == "text") {

            $_meta['placeholder'] = isset($_meta['placeholder']) ? $_meta['placeholder'] : '';
            $_meta['key'] = isset($_meta['key']) ? $_meta['key'] : $_meta['id'];
            $_meta['value_type'] = isset($_meta['value_type']) ? $_meta['value_type'] : 'text';
            $data_type = empty($_meta['data_type']) ? '' : $_meta['data_type'];
            switch ($data_type) {
                case 'price':
                    $_class .= ' wc_input_price';
                    $value = wc_format_localized_price($value);
                    break;
                case 'decimal':
                    $_class .= ' wc_input_decimal';
                    $value = wc_format_localized_decimal($value);
                    break;
                case 'stock':
                    $_class .= ' wc_input_stock';
                    $value = wc_stock_amount($value);
                    break;
                case 'url':
                    $_class .= ' wc_input_url';
                    $value = esc_url($value);
                    break;
                default:
                    break;
            }

        }

        /* Common for all fields */
        $attrs = ' name="'. esc_attr($_meta["name"] . $_index) .'" data-fkey="'. esc_attr($_meta["key"]) .'" '. $placeholder .' data-mandatory="' . esc_attr($_meta["required"]) . '" '. $_readonly .' data-field-type="'. esc_attr($_meta["type"]) .'" autocomplete="off" ';
        if (isset($_meta["for_front_end"])) {
            /* Specific for front end */
            $attrs .= ' data-has_field_rules="'. esc_attr($has_field_rules) .'" data-has_pricing_rules="'. esc_attr($_has_pricing_rules) .'" '. $_cloneable .' class="wccpf-field ' . $_class .' ';

        } else {
            /* Specific for backend end */
            $attrs .= ' class="'. $_ptype .'-field '. $_class .' ';            
        }   
        
        /* Css class specic to color & date picker */
        if ($_meta["type"] == "colorpicker") {
            $attrs .= (isset($_meta["for_front_end"]) ? 'wccpf' : $_ptype) .'-color-'. esc_attr($_meta["admin_class"]);
        } else if ($_meta["type"] == "datepicker") {
            $attrs .= (isset($_meta["for_front_end"]) ? 'wccpf' : $_ptype) .'-datepicker';
        }
        $attrs .= '" ';


        if ($_meta["type"] != "colorpicker") {            
            /* Specific for color picker */
            $attrs .= ' value="'. esc_attr($value) .'" ';
        } else {
            $attrs .= ' value="'. (($value && $value != "") ? esc_attr($value) : "#000") .'" ';
        }
       
        /* Construct the input field */       
        if ($_meta["type"] == "text") {

            $html = '<input type="text" '. $attrs .' '. $maxlength .' data-pattern="mandatory" />';            

        } else if ($_meta["type"] == "number") {

            $html = '<input type="number" '. $attrs .' '. $min .' '. $max .' '. $step .' data-pattern="number" />';            

        } else if ($_meta["type"] == "email") {

            $html = '<input type="email" '. $attrs .' data-pattern="mandatory" />';            

        } else if ($_meta["type"] == "datepicker") {

            $dformat = "";
            if (isset($_meta["date_format"]) && $_meta["date_format"] != "") {
                $dformat = $this->convert_php_jquery_datepicker_format(esc_attr($_meta["date_format"]));
            } else {
                $dformat = $this->convert_php_jquery_datepicker_format("d-m-Y");
            }
            
            $html = '<input type="text" '. $attrs .' data-date-format="'. esc_attr($dformat) .'" data-pattern="mandatory" />';          
            
        } else if ($_meta["type"] == "colorpicker") {

            $html = '<input type="text" '. $attrs .' data-pattern="mandatory" />'; 
            
        } else if ($_meta["type"] == "password") {

            $html = '<input type="password" '. $attrs .' data-pattern="password" />';

        } else if ($_meta["type"] == "hidden") {

            $html = '<input type="hidden" data-fkey="'. esc_attr($_meta["key"]) .'" name="' . esc_attr($_meta["key"] . $_index) . '" ' . $_cloneable . ' value="' . (isset($_meta["placeholder"]) ? esc_attr($_meta["placeholder"]) : "") . '" />';

        } else {
            $html = '';
        }
                
        /* Add wrapper around the field, based on the user options */
        if ($_wrapper) {
            $html = $this->built_field_wrapper($html, $_meta, $_ptype, $_index);
        }
        return $html;
        
    }
    
    /**
     *
     * Helper method for generating Textarea Field for both Product as well as Admin
     *
     * @param object $_meta			     : Field's meta
     * @param string $_ptype			     : Post type ( could be 'wccpf' or 'wccaf' )
     * @param string $_class			     : Custom css class for this field
     * @param string $_index			 : If cloning option enabled then this will the Name Index - which will be suffixed with fields name attribute
     * @param string $_readonly			 : Whether this field is read only
     * @param string $_cloneable          : Whether this field is cloneable
     * @param string $_wrapper			 : Whether this field has to wrapped up
     * @param string $_has_pricing_rules	: Whether this field has pricing rules
     * @return string
     *
     */
    private function build_textarea_field($_meta, $_ptype = "wccpf", $_class = "", $_index = "", $_readonly = "", $_cloneable = "", $_wrapper = true, $_has_pricing_rules = "no") {
        
        $html = '';
        $rows = (isset($_meta["rows"]) && ! empty($_meta["rows"])) ? ('rows="' . esc_attr(trim($_meta["rows"])) . '"') : '';
        $maxlength = (isset($_meta["maxlength"]) && ! empty($_meta["maxlength"])) ? ('maxlength="' . esc_attr(trim($_meta["maxlength"])) . '"') : '';
        $placeholder = (isset($_meta["placeholder"]) && ! empty($_meta["placeholder"])) ? ('placeholder="' . esc_attr(trim($_meta["placeholder"])) . '"') : '';
        $has_field_rules = isset( $_meta["field_rules"] ) && is_array( $_meta["field_rules"] ) && count( $_meta["field_rules"] ) != 0 ? "yes" : "no";
        
        /* Common for all fields */
        $attrs = ' name="'. esc_attr($_meta["name"] . $_index) .'" data-fkey="'. esc_attr($_meta["key"]) .'" '. $placeholder .' data-mandatory="' . esc_attr($_meta["required"]) . '" '. $_readonly .' data-field-type="'. esc_attr($_meta["type"]) .'" autocomplete="off" ';
        if (isset($_meta["for_front_end"])) {
            /* Specific for front end */
            $attrs .= ' data-has_field_rules="'.$has_field_rules.'" data-has_pricing_rules="'.$_has_pricing_rules.'" '. $_cloneable .' class="wccpf-field ' . esc_attr($_class) . '" ';
        } else {
            $attrs .= ' class="'. esc_attr($_ptype .'-field ' . $_class) . '" ';
        }

        $html = '<textarea '. $attrs .' '. $rows .' '. $maxlength .' data-pattern="mandatory" >'. (($_ptype != "wccaf") ? esc_html($_meta["default_value"]) : esc_html($_meta['value'])) .'</textarea>';
        
        /* Add wrapper around the field, based on the user options */
        if ($_wrapper) {
            $html = $this->built_field_wrapper($html, $_meta, $_ptype, $_index);
        }
        return $html;
        
    }
    
    /**
     *
     * Helper method for generating Radio Buttons Field for both Product as well as Admin
     *
     * @param object $_meta			    : Field's meta
     * @param string $_ptype			    : Post type ( could be 'wccpf' or 'wccaf' )
     * @param string $_class			    : Custom css class for this field
     * @param string $_index			    : If cloning option enabled then this will the Name Index - which will be suffixed with fields name attribute
     * @param string $_readonly			: Whether this field is read only
     * @param string $_cloneable         : Whether this field is cloneable
     * @param string $_wrapper			: Whether this field has to wrapped up
     * @param string $_has_pricing_rules	: Whether this field has pricing rules
     * @return string
     *
     */
    private function build_radio_field($_meta, $_ptype = "wccpf", $_class = "", $_index = "", $_readonly = "", $_cloneable = "", $_wrapper = true, $_has_pricing_rules = "no") {
        
        $html = '';        
        $has_field_rules = isset( $_meta["field_rules"] ) && is_array( $_meta["field_rules"] ) && count( $_meta["field_rules"] ) != 0 ? "yes" : "no";
        
        /* For admin field, we don't need <ul> wrapper */
        if ($_ptype != "wccaf") {
            $html = '<ul class="' . ((isset($_meta['layout']) && $_meta['layout'] == "horizontal") ? "wccpf-field-layout-horizontal" : "wccpf-field-layout-vertical") . '" ' . $_cloneable . '>';
        }
        $choices = explode(";", ((isset($_meta["choices"]) && ! empty($_meta["choices"])) ? $_meta["choices"] : ""));
        $_meta["default_value"] = (isset($_meta["default_value"]) && ! empty($_meta["default_value"])) ? trim($_meta["default_value"]) : "";

        /* Common for all fields */
        $common_attrs = ' name="'. esc_attr($_meta["name"] . $_index) .'" data-fkey="'. esc_attr($_meta["key"]) .'" data-mandatory="'. esc_attr($_meta["required"]) .'" data-pattern="mandatory" '. $_readonly .' data-field-type="'. esc_attr($_meta["type"]) .'" ';
        if (isset($_meta["for_front_end"])) {
            /* Specific for front end */
            $common_attrs .= ' data-has_field_rules="'.$has_field_rules.'" data-has_pricing_rules="'.$_has_pricing_rules.'" '. $_cloneable .' class="wccpf-field ' . esc_attr($_class) . '" ';
        } else {
            $common_attrs .= ' class="'. esc_attr($_ptype .'-field ' . $_class) . '" ';
        }

        foreach ($choices as $choice) {
            $attr = '';
            $key_val = explode("|", $choice);
            /* It has to be two items ( Value => Label ), otherwise don't proceed */
            if (count($key_val) == 2) {
                if ($_ptype != "wccaf") {
                    /* Since version 2.0.0 - Default value will be absolute value not as key|val pair */
                    if (strpos($_meta["default_value"], "|") !== false) {
                        /* Compatibility for <= V 1.4.0 */
                        if ($choice == $_meta["default_value"]) {
                            $attr = 'checked="checked"';
                        }
                    } else {
                        /*
                         * For product fields from V 2.0.0
                         * For admin fields, which will be displyed as Product Fields
                         */
                        if ($key_val[0] == $_meta["default_value"]) {
                            $attr = 'checked="checked"';
                        }
                    }
                } else {
                    if ($key_val[0] == $_meta["value"]) {
                        $attr = 'checked="checked"';
                    }
                }
                /* For admin field, we don't need <li></li> wrapper */
                $html .= (($_ptype != "wccaf") ? '<li>' : '') .'<label class="wcff-option-wrapper-label"><input type="radio" '. $common_attrs .' value="'. esc_attr(trim($key_val[0])) .'" '. $attr .' /> ' . esc_html(trim($key_val[1])) . '</label>' . (($_ptype != "wccaf") ? '</li>' : '');
            }           
        }
        
        /* For admin field, we don't need <ul> wrapper */
        $html .= ($_ptype != "wccaf") ? '</ul>' : '';
        
        /* Add wrapper around the field, based on the user options */
        if ($_wrapper) {
            $html = $this->built_field_wrapper($html, $_meta, $_ptype, $_index);
        }
        return $html;
        
    }

    private function build_text_radio_choices($_meta, $_ptype = "wccpf", $_class = "", $_index = "", $_readonly = "", $_cloneable = "", $_wrapper = true, $_has_pricing_rules = "no") {

        $html = '';        
        $has_field_rules = isset( $_meta["field_rules"] ) && is_array( $_meta["field_rules"] ) && count( $_meta["field_rules"] ) != 0 ? "yes" : "no";
        
        $html = '<ul class="' . ((isset($_meta['layout']) && $_meta['layout'] == "horizontal") ? "wccpf-field-layout-horizontal" : "wccpf-field-layout-vertical") . ' wccpf-text-radio-choices-container" ' . $_cloneable . '>';

        $choices = explode(";", ((isset($_meta["choices"]) && ! empty($_meta["choices"])) ? $_meta["choices"] : ""));
        $_meta["default_value"] = (isset($_meta["default_value"]) && ! empty($_meta["default_value"])) ? trim($_meta["default_value"]) : "";

        /* Common for all fields */
        $common_attrs = ' name="'. esc_attr($_meta["name"] . $_index) .'" data-fkey="'. esc_attr($_meta["key"]) .'" data-mandatory="'. esc_attr($_meta["required"]) .'" data-pattern="mandatory" '. $_readonly .' data-field-type="'. esc_attr($_meta["type"]) .'" ';
        if (isset($_meta["for_front_end"])) {
            /* Specific for front end */
            $common_attrs .= ' data-has_field_rules="'.$has_field_rules.'" data-has_pricing_rules="'.$_has_pricing_rules.'" '. $_cloneable .' class="wccpf-field ' . esc_attr($_class) . '" ';
        } else {
            $common_attrs .= ' class="'. esc_attr($_ptype .'-field ' . $_class) . '" ';
        }

        foreach ($choices as $choice) {
            $attr = '';
            $key_val = explode("|", $choice);
            /* It has to be two items ( Value => Label ), otherwise don't proceed */
            if (count($key_val) == 2) {

                /* Since version 2.0.0 - Default value will be absolute value not as key|val pair */
                if (strpos($_meta["default_value"], "|") !== false) {
                    /* Compatibility for <= V 1.4.0 */
                    if ($choice == $_meta["default_value"]) {
                        $attr = 'checked="checked"';
                    }
                } else {
                    /*
                     * For product fields from V 2.0.0
                     * For admin fields, which will be displyed as Product Fields
                     */
                    if ($key_val[0] == $_meta["default_value"]) {
                        $attr = 'checked="checked"';
                    }
                }

                $html .= '<li><label class="wccpf-text-radio-btn-wrapper '. ($attr != "" ? "active" : "") .'">';                
                $html .= '<input type="radio" '. $common_attrs .' value="' . esc_attr(trim($key_val[0])) . '"' . $attr .' /><span>'. esc_attr(trim($key_val[1])) .'</span>';                
                $html .= '</label></li>'; 

            }
        }

        /* For admin field, we don't need <ul> wrapper */
        $html .= '</ul>';
        
        /* Add wrapper around the field, based on the user options */
        if ($_wrapper) {
            $html = $this->built_field_wrapper($html, $_meta, $_ptype, $_index);
        }
        return $html;

    }

    private function build_color_radio_choices($_meta, $_ptype = "wccpf", $_class = "", $_index = "", $_readonly = "", $_cloneable = "", $_wrapper = true, $_has_pricing_rules = "no") {
        
        $html = '';        
        $has_field_rules = isset( $_meta["field_rules"] ) && is_array( $_meta["field_rules"] ) && count( $_meta["field_rules"] ) != 0 ? "yes" : "no";
        
        $html = '<ul class="' . ((isset($_meta['layout']) && $_meta['layout'] == "horizontal") ? "wccpf-field-layout-horizontal" : "wccpf-field-layout-vertical") . ' wccpf-color-radio-choices-container" ' . $_cloneable . '>';

        $choices = explode(";", ((isset($_meta["choices"]) && ! empty($_meta["choices"])) ? $_meta["choices"] : ""));
        $_meta["default_value"] = (isset($_meta["default_value"]) && ! empty($_meta["default_value"])) ? trim($_meta["default_value"]) : "";

        /* Common for all fields */
        $common_attrs = ' name="'. esc_attr($_meta["name"] . $_index) .'" data-fkey="'. esc_attr($_meta["key"]) .'" data-mandatory="'. esc_attr($_meta["required"]) .'" data-pattern="mandatory" '. $_readonly .' data-field-type="'. esc_attr($_meta["type"]) .'" ';
        if (isset($_meta["for_front_end"])) {
            /* Specific for front end */
            $common_attrs .= ' data-has_field_rules="'. esc_attr($has_field_rules) .'" data-has_pricing_rules="'. esc_attr($_has_pricing_rules) .'" '. $_cloneable .' class="wccpf-field ' . esc_attr($_class) . '" ';
        } else {
            $common_attrs .= ' class="'. esc_attr($_ptype .'-field ' . $_class) . '" ';
        }

        foreach ($choices as $choice) {
            $attr = '';
            $key_val = explode("|", $choice);
            /* It has to be two items ( Value => Label ), otherwise don't proceed */
            if (count($key_val) == 2) {

                /* Since version 2.0.0 - Default value will be absolute value not as key|val pair */
                if (strpos($_meta["default_value"], "|") !== false) {
                    /* Compatibility for <= V 1.4.0 */
                    if ($choice == $_meta["default_value"]) {
                        $attr = 'checked="checked"';
                    }
                } else {
                    /*
                     * For product fields from V 2.0.0
                     * For admin fields, which will be displyed as Product Fields
                     */
                    if ($key_val[0] == $_meta["default_value"]) {
                        $attr = 'checked="checked"';
                    }
                }

                $html .= '<li><label class="wccpf-color-radio-btn-wrapper '. ($attr != "" ? "active" : "") .'">';
                if ($_meta["show_preview_label"] == "yes" && $_meta["preview_label_pos"] == "top") {
                    $html .= '<p>'. esc_html(trim($key_val[1])) .'</p>';
                }

                $html .= '<input type="radio" '. $common_attrs .' value="'. esc_attr(trim($key_val[0])) .'" '. $attr .' /><span style="background: '. esc_attr(trim($key_val[0])) .'"></span>';

                if ($_meta["show_preview_label"] == "yes" && $_meta["preview_label_pos"] == "bottom") {
                    $html .= '<p>'. esc_html(trim($key_val[1])) .'</p>';
                }
                $html .= '</label></li>';                

            }          
        }

        /* For admin field, we don't need <ul> wrapper */
        $html .= '</ul>';
        
        /* Add wrapper around the field, based on the user options */
        if ($_wrapper) {
            $html = $this->built_field_wrapper($html, $_meta, $_ptype, $_index);
        }
        return $html;

    }

    private function build_image_radio_choices($_meta, $_ptype = "wccpf", $_class = "", $_index = "", $_readonly = "", $_cloneable = "", $_wrapper = true, $_has_pricing_rules = "no") {
        
        $html = '';        
        $has_field_rules = isset( $_meta["field_rules"] ) && is_array( $_meta["field_rules"] ) && count( $_meta["field_rules"] ) != 0 ? "yes" : "no";
        
        $html = '<ul class="' . ((isset($_meta['layout']) && $_meta['layout'] == "horizontal") ? "wccpf-field-layout-horizontal" : "wccpf-field-layout-vertical") . ' wccpf-image-radio-choices-container" ' . $_cloneable . '>';

        $choices = explode(";", ((isset($_meta["choices"]) && ! empty($_meta["choices"])) ? $_meta["choices"] : ""));
        $_meta["default_value"] = (isset($_meta["default_value"]) && ! empty($_meta["default_value"])) ? trim($_meta["default_value"]) : "";

        /* Common for all fields */
        $common_attrs = ' name="'. esc_attr($_meta["name"] . $_index) .'" data-fkey="'. esc_attr($_meta["key"]) .'" data-mandatory="'. esc_attr($_meta["required"]) .'" data-pattern="mandatory" '. $_readonly .' data-field-type="'. esc_attr($_meta["type"]) .'" ';
        if (isset($_meta["for_front_end"])) {
            /* Specific for front end */
            $common_attrs .= ' data-has_field_rules="'.$has_field_rules.'" data-has_pricing_rules="'.$_has_pricing_rules.'" '. $_cloneable .' class="wccpf-field ' . esc_attr($_class) . '" ';
        } else {
            $common_attrs .= ' class="'. esc_attr($_ptype .'-field ' . $_class) . '" ';
        }

        foreach ($choices as $choice) {
            $attr = '';
            $key_val = explode("|", $choice);
            /* It has to be two items ( Value => Label ), otherwise don't proceed */
            if (count($key_val) == 2) {

                /* Since version 2.0.0 - Default value will be absolute value not as key|val pair */
                if (strpos($_meta["default_value"], "|") !== false) {
                    /* Compatibility for <= V 1.4.0 */
                    if ($choice == $_meta["default_value"]) {
                        $attr = 'checked="checked"';
                    }
                } else {
                    /*
                     * For product fields from V 2.0.0
                     * For admin fields, which will be displyed as Product Fields
                     */
                    if ($key_val[0] == $_meta["default_value"]) {
                        $attr = 'checked="checked"';
                    }
                }

                if (!isset($_meta["images"]) || !isset($_meta["images"][trim($key_val[0])])) {
                    continue;
                }

                $html .= '<li><label class="wccpf-image-radio-btn-wrapper '. ($attr != "" ? "active" : "") .'">';
                if ($_meta["show_preview_label"] == "yes" && $_meta["preview_label_pos"] == "top") {
                    $html .= '<p>'. esc_html(trim($key_val[1])) .'</p>';
                }

                $html .= '<input type="radio" '. $common_attrs .' value="'. esc_attr(trim($key_val[0])) .'" '. $attr .' /><img src="'. esc_url($_meta["images"][trim($key_val[0])]["url"]) .'" />';

                if ($_meta["show_preview_label"] == "yes" && $_meta["preview_label_pos"] == "bottom") {
                    $html .= '<p>'. esc_html(trim($key_val[1])) .'</p>';
                }
                $html .= '</label></li>';   

            }
        }

        /* For admin field, we don't need <ul> wrapper */
        $html .= '</ul>';
        
        /* Add wrapper around the field, based on the user options */
        if ($_wrapper) {
            $html = $this->built_field_wrapper($html, $_meta, $_ptype, $_index);
        }
        return $html;

    }
    
    /**
     *
     * Helper method for generating Checkboxs Field for both Product as well as Admin
     *
     * @param object $_meta			     : Field's meta
     * @param string $_ptype			     : Post type ( could be 'wccpf' or 'wccaf' )
     * @param string $_class			     : Custom css class for this field
     * @param string $_index			     : If cloning option enabled then this will the Name Index - which will be suffixed with fields name attribute     
     * @param string $_readonly			 : Whether this field is read only
     * @param string $_cloneable          : Whether this field is cloneable
     * @param string $_wrapper			 : Whether this field has to wrapped up
     * @param string $_has_pricing_rules	: Whether this field has pricing rules
     * @return string
     *
     */
    private function build_checkbox_field($_meta, $_ptype = "wccpf", $_class = "", $_index = "", $_readonly = "", $_cloneable = "", $_wrapper = true, $_has_pricing_rules = "no") {
        
        $html = '';
        $defaults = array();        
        $has_field_rules = isset( $_meta["field_rules"] ) && is_array( $_meta["field_rules"] ) && count( $_meta["field_rules"] ) != 0 ? "yes" : "no";
        
        /* For admin field, we don't need <ul> wrapper */
        if ($_ptype != "wccaf") {
            $html = '<ul class="' . (($_meta['layout'] == "horizontal") ? "wccpf-field-layout-horizontal" : "wccpf-field-layout-vertical") . '" ' . $_cloneable . '>';
        }
        $choices = explode(";", ((isset($_meta["choices"]) && ! empty($_meta["choices"])) ? $_meta["choices"] : ""));
        if ($_ptype != "wccaf") {
            /* Since version 2.0.0 - Default value will be absolute value not as key|val pair */
            if (is_array($_meta["default_value"])) {
                $defaults = $_meta["default_value"];
            } else {
                /* Compatibility mode for <= V 1.4.0 */
                $temp_opts = ($_meta["default_value"] != "") ? explode(";", $_meta["default_value"]) : array();
                foreach ($temp_opts as $opts) {
                    $opt = explode("|", $opts);
                    if (count($opt) == 2) {
                        $defaults[] = $opt[0];
                    }
                }
            }
        } else {
            /* This is going to be always an Array ( Value only Array ) */
            if (isset($_meta["value"])) {
                $defaults = $_meta["value"];
            } else {
                $defaults = array();                
            }            
        }

        /* Common for all fields */
        $common_attrs = ' name="'. esc_attr($_meta["name"] . $_index) .'[]" data-fkey="'. esc_attr($_meta["key"]) .'" data-mandatory="'. esc_attr($_meta["required"]) .'" data-pattern="mandatory" '. $_readonly .' data-field-type="'. esc_attr($_meta["type"]) .'" ';
        if (isset($_meta["for_front_end"])) {
            /* Specific for front end */
            $common_attrs .= ' data-has_field_rules="'.$has_field_rules.'" data-has_pricing_rules="'.$_has_pricing_rules.'" '. $_cloneable .' class="wccpf-field ' . esc_attr($_class) . '" ';
        } else {
            $common_attrs .= ' class="'. esc_attr($_ptype .'-field ' . $_class) . '" ';
        }

        foreach ($choices as $choice) {
            $attr = '';
            $key_val = explode("|", $choice);
            /* It has to be two items ( Value => Label ), otherwise don't proceed */
            if (count($key_val) == 2) {
                if (in_array(trim($key_val[0]), $defaults)) {
                    $attr = 'checked';
                }
                /* For admin field, we don't need <li></li> wrapper */
                $html .= (($_ptype != "wccaf") ? '<li>' : '') . '<label class="wcff-option-wrapper-label"><input type="checkbox" '. $common_attrs .' value="'. esc_attr(trim($key_val[0])) .'" '. $attr .' /> ' . esc_attr(trim($key_val[1])) .'</label>'. (($_ptype != "wccaf") ? '</li>' : '');
            }
        }
        /* For admin field, we don't need <ul> wrapper */
        $html .= ($_ptype != "wccaf") ? '</ul>' : '';
        
        /* Add wrapper around the field, based on the user options */
        if ($_wrapper) {
            $html = $this->built_field_wrapper($html, $_meta, $_ptype, $_index);
        }
        return $html;
        
    }
    
    /**
     *
     * Helper method for generating Select Field for both Product as well as Admin
     *
     * @param object $_meta			     : Field's meta
     * @param string $_ptype			     : Post type ( could be 'wccpf' or 'wccaf' )
     * @param string $_class			     : Custom css class for this field
     * @param string $_index			     : If cloning option enabled then this will the Name Index - which will be suffixed with fields name attribute     
     * @param string $_readonly			 : Whether this field is read only
     * @param string $_cloneable          : Whether this field is cloneable
     * @param string $_wrapper			 : Whether this field has to wrapped up
     * @param string $_wrapper			 : Whether this field has to wrapped up
     * @param string $_has_pricing_rules	: Whether this field has pricing rules
     * @return string
     *
     */
    private function build_select_field($_meta, $_ptype = "wccpf", $_class = "", $_index = "", $_readonly = "", $_cloneable = "", $_wrapper = true, $_has_pricing_rules = "no") {
        
        $html = '';        
        $has_field_rules = isset( $_meta["field_rules"] ) && is_array( $_meta["field_rules"] ) && count( $_meta["field_rules"] ) != 0 ? "yes" : "no";
        
        if (isset($_meta["for_front_end"])) {
             /* Specific for front end */
            $html = '<select data-has_field_rules="'. esc_attr($has_field_rules) .'"  data-has_pricing_rules="'. esc_attr($_has_pricing_rules) .'" data-fkey="'. esc_attr($_meta["key"]) .'"  class="wccpf-field ' . esc_attr($_class) . '" name="' . esc_attr($_meta["name"] . $_index) . '" data-field-type="'. esc_attr($_meta["type"]) .'" data-pattern="mandatory" data-mandatory="' . esc_attr($_meta["required"]) . '" ' . $_cloneable . ' ' . $_readonly . ' >';
        } else {
             /* Specific for admin page */
            $html = '<select data-fkey="'. esc_attr($_meta["key"]) .'"  class="' . esc_attr($_ptype) . '-field ' . esc_attr($_class) . '" name="' . esc_attr($_meta["name"]) . '" data-field-type="'. esc_attr($_meta["type"]) .'" data-pattern="mandatory" data-mandatory="' . esc_attr($_meta["required"]) . '" ' . $_readonly . ' >';
        }       
        
        $choices = explode(";", ((isset($_meta["choices"]) && ! empty($_meta["choices"])) ? $_meta["choices"] : ""));
        $_meta["default_value"] = (isset($_meta["default_value"]) && ! empty($_meta["default_value"])) ? trim($_meta["default_value"]) : "";
        
        /* Placeholder option */
        if (isset($_meta["placeholder"]) && !empty($_meta["placeholder"])) {
            $html .= '<option value="wccpf_none">' . esc_html($_meta["placeholder"]) . '</option>';
        }
        $choices = apply_filters( "wcff_select_option_before_rendering", $choices, $_meta["key"] );
        foreach ($choices as $choice) {
            $attr = '';
            $key_val = explode("|", $choice);
            /* It has to be two items ( Value => Label ), otherwise don't proceed */
            if (count($key_val) == 2) {
                if ($_ptype != "wccaf") {
                    /* Since version 2.0.0 - Default value will be absolute value, not as key|val pair */
                    if (strpos($_meta["default_value"], "|") !== false) {
                        /* Compatibility for <= V 1.4.0 */
                        if ($choice == $_meta["default_value"]) {
                            $attr = 'selected';
                        }
                    } else {
                        /*
                         * For product fields from V 2.0.0
                         * For admin fields, which will be displyed as Product Fields
                         */
                        if (trim($key_val[0]) == $_meta["default_value"]) {
                            $attr = 'selected';
                        }
                    }
                } else {
                    if ($key_val[0] == $_meta["value"]) {
                        $attr = 'selected';
                    }
                }
                $html .= '<option value="' . esc_attr(trim($key_val[0])) . '" ' . $attr . '>' . esc_html(trim($key_val[1])) . '</option>';
            }
        }
        $html .= '</select>';
        
        /* Add wrapper around the field, based on the user options */
        if ($_wrapper) {
            $html = $this->built_field_wrapper($html, $_meta, $_ptype, $_index);
        }
        return $html;
        
    }
    
    /**
     *
     * Helper method for generating File Input Field for both Product as well as Admin
     *
     * @param object $_meta			     : Field's meta
     * @param string $_ptype			     : Post type ( could be 'wccpf' or 'wccaf' )
     * @param string $_class			     : Custom css class for this field
     * @param string $_index			     : If cloning option enabled then this will the Name Index - which will be suffixed with fields name attribute
     * @param string $_show_as_value	     : If admin wants to display the value instead as field ( Only for Admin Fields )
     * @param string $_readonly			 : Whether this field is read only
     * @param string $_cloneable          : Whether this field is cloneable
     * @param string $_wrapper			 : Whether this field has to wrapped up
     * @return string
     *
     */
    private function build_file_field($_meta, $_ptype = "wccpf", $_class = "", $_index = "", $_show_value = "no", $_readonly = "", $_cloneable = "", $_wrapper = true, $_is_pricing_rule = "no") {
        /*
         * Show as value option not available for FILE field
         * since file field not supported for Admin Field  */
        
        $_index .= (isset($_meta["multi_file"]) && $_meta["multi_file"] == "yes") ? "[]" : "";
        $accept = (isset($_meta["filetypes"]) && ! empty($_meta["filetypes"])) ? ('accept="' . esc_attr(trim($_meta["filetypes"])) . '"') : '';
        $multifile = (isset($_meta["multi_file"]) && $_meta["multi_file"] == "yes") ? 'multiple="multiple"' : '';
        $maxsize = (isset($_meta["max_file_size"]) && ! empty($_meta["max_file_size"])) ? ('max-size="' . esc_attr(trim($_meta["max_file_size"])) . '"') : '';
        $preview = (isset($_meta["img_is_prev"]) && $_meta["img_is_prev"] == "yes") ? "yes" : "no";
        $preview_width = (isset($_meta["img_is_prev_width"]) && $_meta["img_is_prev_width"] != "") ? $_meta["img_is_prev_width"] : "65px";
        $has_field_rules = isset( $_meta["field_rules"] ) && is_array( $_meta["field_rules"] ) && count( $_meta["field_rules"] ) != 0 ? "yes" : "no";
        /* Construct the field */
        $html = '<input type="file" data-has_field_rules="'. esc_attr($has_field_rules) .'"  data-has_pricing_rules="'. esc_attr($_is_pricing_rule) .'" ' . $maxsize . ' data-fkey="'. esc_attr($_meta["key"]) .'" class="wccpf-field ' . esc_attr($_class) . '" name="' . esc_attr($_meta["name"] . $_index) . '" ' . $accept . ' ' . $multifile . ' data-field-type="'. esc_attr($_meta["type"]) .'" data-pattern="mandatory" data-mandatory="' . esc_attr($_meta["required"]) . '" ' . $_cloneable . ' ' . $_readonly . ' data-preview="'. esc_attr($preview) .'" data-preview-width="'. esc_attr($preview_width) .'" />';
        /* Add wrapper around the field, based on the user options */
        if ($_wrapper) {
            $html = $this->built_field_wrapper($html, $_meta, $_ptype, $_index);
        }
        return $html;
    }
    
    /**
     *
     * Helper method used to generate Label Widget
     *
     * @param object $_meta
     * @param string $_class
     * @return string
     *
     */
    private function build_label_field($_meta, $_ptype, $_class = "", $_index = "", $_cloneable = "" ) {
        $_meta["message"] = isset($_meta["message"]) ? $_meta["message"] : "";
        $_meta["message_type"] = isset($_meta["message_type"]) ? $_meta["message_type"] : "info";
        
        /* Is init field show or hide */
        $onload_field = (isset($_meta["initial_show"]) && $_meta["initial_show"] == "no" ) ? "display: none;" : "";
        if ($_meta["message"] != "") {
            $html = '<div style="'.$onload_field.'" data-labelfield="'. esc_attr($_meta["key"]) .'" data-fkey="'. esc_attr($_meta["key"]) .'" class="wcff-label wccpf_fields_table' . esc_attr($_class) . ' wcff-label-' . esc_attr($_meta["message_type"]) . '" '. $_cloneable .'><span class="wccpf-field label" data-fkey="'. esc_attr($_meta["key"]) .'">' . html_entity_decode($_meta["message"]) . '</span><input type="hidden" name="' . esc_attr($_meta["name"] . $_index) . '"></div>';
            if($_ptype == "wcccf"){
                $html = $this->built_field_wrapper($html, $_meta, $_ptype, $_index);
            }
            return $html;
        }
        return "";
    }
    
    /**
     *
     * Helper method used to build Image Uploader widget for Admin Fields
     *
     * @param object $_meta
     * @return string
     *
     */
    private function build_image_field($_meta) {
        global $content_width, $_wp_additional_image_sizes;
        $html = '';
        $has_image = false;
        $thumbnail_html = "";
        $content_width = 64;
        $old_content_width = $content_width;
        $image_wrapper_class = "wccaf-image-field-wrapper";
        
        $location_class = str_replace(".php", "", $_meta["location"]);
        
        $_meta["upload_btn_label"] = (isset($_meta["upload_btn_label"]) && ! empty($_meta["upload_btn_label"])) ? $_meta["upload_btn_label"] : "Upload";
        $_meta["media_browser_title"] = (isset($_meta["media_browser_title"]) && ! empty($_meta["media_browser_title"])) ? $_meta["media_browser_title"] : "Choose an Image";
        $_meta["upload_probe_text"] = (isset($_meta["upload_probe_text"]) && ! empty($_meta["upload_probe_text"])) ? $_meta["upload_probe_text"] : "You haven't set an image yet";
        
        if (isset($_meta["value"]) && ! empty($_meta["value"])) {
            if (! isset($_wp_additional_image_sizes['thumbnail'])) {
                $thumbnail_html = wp_get_attachment_image($_meta["value"], array(
                    $content_width,
                    $content_width
                ));
            } else {
                $thumbnail_html = wp_get_attachment_image($_meta["value"], 'thumbnail');
            }
            if (! empty($thumbnail_html)) {
                $has_image = true;
                $image_wrapper_class = "wccaf-image-field-wrapper has_image";
            }
            $content_width = $old_content_width;
        }
        
        if ($_meta["location"] != "product_cat_add_form_fields" && $_meta["location"] != "product_cat_edit_form_fields") {
            $html = '<div class="form-field ' . esc_attr($_meta['key'] . "_field " . $image_wrapper_class . ' ' . $location_class) . '">';
            $html .= '<label>' . esc_html($_meta['label']) . '</label>';
        } else if ($_meta["location"] == "product_cat_add_form_fields") {
            $html .= '<div class="form-field ' . $location_class . ' ' . esc_attr($_meta['key']) . "_field " . $image_wrapper_class . '">';
            $html .= '<label class="wcff-admin-field-label" for="' . esc_attr($_meta['key']) . '">' . wp_kses_post($_meta['label']) . ((isset($_meta["required"]) && $_meta["required"] == "yes") ? ' <span>*</span>' : '') . '</label>';
        } else {
            $html .= '<tr class="form-field ' . esc_attr($_meta['key'] . "_field " . $image_wrapper_class . ' ' . $location_class) . '">';
            $html .= '<th scope="row" valign="top"><label class="wcff-admin-field-label" for="' . esc_attr($_meta['key']) . '">' . wp_kses_post($_meta['label']) . ((isset($_meta["required"]) && $_meta["required"] == "yes") ? ' <span>*</span>' : '') . '</label></th>';
            $html .= '<td>';
        }
        
        /* Image preview section start */
        
        if (! empty($thumbnail_html)) {
            $html .= $thumbnail_html;
        } else {
            $html .= '<img src="" style="width:' . esc_attr($content_width) . 'px;height:auto;border:0;display:none;" />';
        }
        $html .= '<a href="#" class="wccaf-image-remove-btn"></a>';
        $html .= '<input type="hidden" id="' . esc_attr($_meta["name"]) . '" name="' . esc_attr($_meta["name"]) . '" value="" />';
        
        /* Image preview section end */
        /* Upload section start */
        
        $html .= '<p class="wccaf-img-field-btn-wrapper" style="display: ' . ($has_image ? "none" : "block") . '">';
        $html .= '<span>' . esc_html($_meta["upload_probe_text"]) . '</span>';
        $html .= '<input type="button" class="button wcff_upload_image_button" data-uploader_title="' . esc_attr($_meta["media_browser_title"]) . '" value="' . esc_attr($_meta["upload_btn_label"]) . '" />';
        $html .= '</p>';
        
        /* Upload section end */
        
        if ($_meta["location"] != "product_cat_add_form_fields" && $_meta["location"] != "product_cat_edit_form_fields") {
            $html .= '</div>';
        } else if ($_meta["location"] == "product_cat_add_form_fields") {
            $html .= '</div>';
        } else {
            $html .= '</td>';
            $html .= '</tr>';
        }
        return $html;
    }
    
    /**
     *
     * Helper method used to generate URL field for both Product as well as Admin
     *
     * @param object $_meta
     * @param string $_ptype
     * @param string $_class
     * @param object $_index
     * @param string $_show_value
     * @param string $_readonly
     * @param string $_cloneable
     * @param string $_wrapper
     * @return string
     *
     */
    private function build_url_field($_meta, $_ptype = "wccpf", $_class = "", $_index = "", $_show_value = "no", $_readonly = "", $_cloneable = "", $_wrapper = true) {
        $html = '';
        if ($_ptype != "wccaf") {
            if (isset($_meta["value"]) && $_meta["value"] != "") {
                $visual_type = (isset($_meta["view_in"]) && ! empty($_meta["view_in"])) ? $_meta["view_in"] : "link";
                $open_tab = (isset($_meta["tab_open"]) && ! empty($_meta["tab_open"])) ? $_meta["tab_open"] : "_blank";
                if ($visual_type == "link") {
                    /* Admin wants this url to be displayed as LINK */
                    $html = '<a href="' . esc_url($_meta["value"]) . '" class="' . esc_attr($_class) . '" target="' . esc_attr($open_tab) . '" title="' . esc_attr($_meta["tool_tip"]) . '" ' . $_cloneable . ' >' . esc_html($_meta["link_name"]) . '</a>';
                } else {
                    /* Admin wants this url to be displayed as Button */
                    $html = '<button onclick="window.open(\'' . esc_url($_meta["value"]) . '\', \'' . esc_attr($open_tab) . '\' )"  title="' . esc_attr($_meta["tool_tip"]) . '" class="' . esc_attr($_class) . '" ' . $_cloneable . ' >' . esc_html($_meta["link_name"]) . '</button>';
                }
            } else {
                /* This means url value is empty so no need render the field */
                $_wrapper = false;
            }
        } else {
            $html .= '<input type="text" name="' . esc_attr($_meta['name']) . '" class="wccaf-field short" id="' . esc_attr($_meta['name']) . '" placeholder="http://example.com" wccaf-type="url" value="' . esc_attr($_meta['value']) . '" wccaf-pattern="mandatory" wccaf-mandatory="">';
        }
        /* Add wrapper around the field, based on the user options */
        if ($_wrapper) {
            $html = $this->built_field_wrapper($html, $_meta, $_ptype, $_index);
        }
        return $html;
    }
    
    /**
     *
     * Helper method used to generate Wrapper around for both Product as well Admin<br>
     * It also decides the wrapper type, based on the Fields parent post type and Location
     *
     * @param object $_html
     * @param object $_meta
     * @param object $_ptype
     * @param object $_index
     * @return string
     *
     */
    private function built_field_wrapper($_html, $_meta, $_ptype, $_index) {
        
        $html = '';
        
        if ($this->fields_mode == "front" && $_ptype == "wccaf") {
            /* This means we are rendering admin fields for front end */
            $_ptype = "wccpf";
        }
        
        if ($_ptype != "wccaf" && $_ptype != "wcccf" ) {
            /*
             * Add the validation message section
             * URL field doesn't need any validation message
             */
            if ($_meta["type"] != "url") {
                $_html .= '<span class="wccpf-validation-message">' . (isset($_meta["message"]) ? esc_html($_meta["message"]) : "") . '</span>';
            }
            
            /* Check for the custom wrapper action registered */ 
            if (has_filter('wccpf_custom_field_wrapper')) {
                $html = apply_filters('wccpf_custom_field_wrapper', $_html, $_meta, $_ptype, $_index);
            } else if (has_filter('wccpf_before_field_rendering') && has_filter('wccpf_after_field_rendering')) {
                $before = apply_filters('wccpf_before_field_rendering', $_meta);
                $after = apply_filters('wccpf_after_field_rendering', $_meta);
                $html = $before . $_html . $after;
            } else {
                /* Special property for URL field alone */
                $show_label = isset($_meta["show_label"]) ? $_meta["show_label"] : "yes";
                /* Label location */
                $label_alignment = (isset($_meta["label_alignment"]) && !empty($_meta["label_alignment"])) ? $_meta["label_alignment"] : "left";
                /* Default field wrapper */
                $wrapper_class = (isset($_meta["field_class"]) && !empty($_meta["field_class"])) ? $_meta["field_class"] : $_meta["key"];
                /* Is init field show or hide */
                $onload_field = (isset($_meta["initial_show"]) && $_meta["initial_show"] == "no" ) ? "display: none;" : "";
                $html = '<table style="'. esc_attr($onload_field) .'" class="wccpf_fields_table ' . esc_attr(apply_filters('wccpf_fields_container_class', '')) . ' '. esc_attr($wrapper_class) .'-wrapper">';
                $html .= '<tbody>';
                $html .= '<tr>';
                
                $label = "";
                if ($show_label == "yes") {
                    $label = '<label for="' . esc_attr($_meta["key"] . $_index) . '">' . esc_html($_meta["label"]) . '' . ((isset($_meta["required"]) && $_meta["required"] == "yes") ? ' <span>*</span>' : '') . '</label>';
                }            
                if ($label_alignment == "left") {
                    $html .= '<td class="wccpf_label">'. $label .'</td>';
                }
                $html .= '<td class="wccpf_value '. esc_attr($label_alignment) .'">'. ($label_alignment == "top" ? $label : ""). $_html . '</td>';
                $html .= '</tr>';
                $html .= '</tbody>';
                $html .= '</table>';
            }
        } else if( $_ptype == "wcccf" ){
            $requere = isset( $_meta["required"] ) && $_meta["required"]  ? '<abbr class="required" title="required">*</abbr>' : ' <span class="optional">(optional)</span>';
            $label = $_meta["type"] != "label" ? '<label for="'. esc_attr( $_meta["key"] ) .'" class=""> '. esc_attr( $_meta["label"] ) ."&nbsp;" . $requere . '</label>' : "";
            $html .= '<div class="form-row form-row-wide wcff-checkout-field-container address-field" id="'. esc_attr( $_meta["key"] ).'" data-priority="' . esc_attr( $_meta["order"] ) . '">'.$label.$_html.'</div>';
        } else {
            if ($_meta["location"] != "product_cat_add_form_fields" && $_meta["location"] != "product_cat_edit_form_fields") {
                $html .= '<p class="form-field ' . esc_attr($_meta['key']) . '_field ' . $_meta["location"] . '">';
                $html .= '<label class="wcff-admin-field-label" for="' . esc_attr($_meta['key']) . '">' . wp_kses_post($_meta['label']) . ((isset($_meta["required"]) && $_meta["required"] == "yes") ? ' <span>*</span>' : '') . '</label>';
                /* Insert the actual field here */
                $html .= $_html;
                
                if (! empty($_meta['description'])) {
                    if (isset($_meta['desc_tip']) && "no" != $_meta['desc_tip']) {
                        $html .= '<img class="help_tip" data-tip="' . wp_kses_post($_meta['description']) . '" src="' . esc_url(wcff()->info["dir"]) . '/assets/img/help.png" height="16" width="16" />';
                    } else {
                        $html .= '<span class="description">' . wp_kses_post($_meta['description']) . '</span>';
                    }
                }
                $html .= '<span class="wccaf-validation-message">' . (isset($_meta["message"]) ? $_meta["message"] : "") . '</span>';
                $html .= '</p>';
            } else if ($_meta["location"] == "product_cat_add_form_fields") {
                $html .= '<div class="form-field ' . $_meta["location"] . '">';
                $html .= '<label class="wcff-admin-field-label" for="' . esc_attr($_meta['key']) . '">' . wp_kses_post($_meta['label']) . ((isset($_meta["required"]) && $_meta["required"] == "yes") ? ' <span>*</span>' : '') . '</label>';
                
                /* Insert the actual field here */
                $html .= $_html;
                
                if (! empty($_meta['description'])) {
                    $html .= '<p class="description">' . wp_kses_post($_meta['description']) . '</p>';
                }
                $html .= '<span class="wccaf-validation-message">' . (isset($_meta["message"]) ? esc_html($_meta["message"]) : "") . '</span>';
                $html .= '</div>';
            } else {
                $html .= '<tr class="form-field">';
                $html .= '<th scope="row" valign="top"><label class="wcff-admin-field-label" for="' . esc_attr($_meta['key']) . '">' . wp_kses_post($_meta['label']) . ((isset($_meta["required"]) && $_meta["required"] == "yes") ? ' <span>*</span>' : '') . '</label></th>';
                $html .= '<td>';
                
                /* Insert the actual field here */
                $html .= $_html;
                
                if (! empty($_meta['description'])) {
                    $html .= '<p class="description">' . wp_kses_post($_meta['description']) . '</p>';
                }
                $html .= '<span class="wccaf-validation-message">' . (isset($_meta["message"]) ? esc_html($_meta["message"]) : "") . '</span>';
                $html .= '</td>';
                $html .= '</tr>';
            }
        }
        return $html;
    }
    
    /**
     *
     * Convert php dateformat into jQuery UI Date Picker compatible format
     * Taken from : https://stackoverflow.com/questions/16702398/convert-a-php-date-format-to-a-jqueryui-datepicker-date-format
     *
     * @author Tristan Jahier
     * @param string $_php_format
     * @return string|mixed
     */
    public function convert_php_jquery_datepicker_format($_php_format) {
        $SYMBOLS = array(
            // Day
            'd' => 'dd',
            'D' => 'D',
            'j' => 'd',
            'l' => 'DD',
            'N' => '',
            'S' => '',
            'w' => '',
            'z' => 'o',
            // Week
            'W' => '',
            // Month
            'F' => 'MM',
            'm' => 'mm',
            'M' => 'M',
            'n' => 'm',
            't' => '',
            // Year
            'L' => '',
            'o' => '',
            'Y' => 'yy',
            'y' => 'y',
            // Time
            'a' => '',
            'A' => '',
            'B' => '',
            'g' => '',
            'G' => '',
            'h' => '',
            'H' => '',
            'i' => '',
            's' => '',
            'u' => ''
        );
        $jqueryui_format = "";
        $escaping = false;
        for($i = 0; $i < strlen($_php_format); $i++) {
            $char = $_php_format[$i];
            if($char === '\\') {
                $i++;
                if($escaping) {
                    $jqueryui_format .= $_php_format[$i];
                } else {
                    $jqueryui_format .= '\'' . $_php_format[$i];
                }
                $escaping = true;
            } else {
                if($escaping) {
                    $jqueryui_format .= "'";
                    $escaping = false;
                }
                if(isset($SYMBOLS[$char])) {
                    $jqueryui_format .= $SYMBOLS[$char];
                } else {
                    $jqueryui_format .= $char;
                }
            }
        }
        return $jqueryui_format;
    }    
    
}

?>