<?php 

if (!defined('ABSPATH')) {exit;}
/**
 *
 * @author 	    : Saravana Kumar K
 * @copyright   : Sarkware Research & Development (OPC) Pvt Ltd
 *
 * Renders the custom fields values in the Cart Line Item<br>
 * It will mine the Caft Item Object for all the product, admin and custom pricing values<br>
 * and add those values as key value pairs into the Cart Data, which will be rendered by the WC Cart Template
 *
 */

class wcff_cart_renderer {
    
    /* Holds the mined custom fields key val pairs */
    private $wccpf_items;
    /* Cart data object supplied by the WC */
    private $cart_data;
    /* Cart line item object supplied by the WC */
    private $cart_item = null;
    
    /* Multilingual flag */
    private $multilingual;
    /* Visibility flag on Cart - ( Global Option ) */
    private $show_custom_data;
    /* Grouping option flag */
    private $group_meta_on_cart;
    
    /* Fields cloning flaq */
    private $is_cloning_enabled = "no";
    /* Holds product fields list (from all group) */
    private $product_field_groups = null;
    /* Holds admin fields list (from all group) */
    private $admin_field_groups = null;
    
    public function __construct() {}
    
    /**
     * 
     * Handler for 'woocommerce_get_item_data' action<br>
     * It gather all the custom fields values by using other helper methods<br>
     * and returns the the key value pair array
     * 
     * @param object $_cart_data
     * @param object $_cart_item
     * @return array
     * 
     */
    public function render_fields_data($_cart_data, $_cart_item = null) {
        $this->cart_data = $_cart_data;
        $this->cart_item = $_cart_item;
        $this->wccpf_items = array();
        
        /* Woo 2.4.2 updates */
        if (! empty($this->cart_data)) {
            $this->wccpf_items = $this->cart_data;
        }
        
        $wccpf_options = wcff()->option->get_options();
        $this->show_custom_data = isset($wccpf_options["show_custom_data"]) ? $wccpf_options["show_custom_data"] : "yes";
        $this->is_cloning_enabled = isset($wccpf_options["fields_cloning"]) ? $wccpf_options["fields_cloning"] : "no";
        $this->group_meta_on_cart = isset($wccpf_options["group_meta_on_cart"]) ? $wccpf_options["group_meta_on_cart"] : "no";
        $this->multilingual = isset($wccpf_options["enable_multilingual"]) ? $wccpf_options["enable_multilingual"] : "no";

        $is_admin_module_enabled = isset($wccpf_options["enable_admin_field"]) ? $wccpf_options["enable_admin_field"] : "yes";
		$is_variable_module_enabled = isset($wccpf_options["enable_variable_field"]) ? $wccpf_options["enable_variable_field"] : "yes";
        
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
        
        if( isset( $this->cart_item['variation_id'] ) && $this->cart_item['variation_id'] != 0 && !empty( $this->cart_item['variation_id'] ) ) {
            
            $wccvf_posts = array();
            $wccvf_posts = wcff()->dao->load_fields_groups_for_product($this->cart_item['variation_id'], 'wccpf', "variable", "any", false);            
            $this->product_field_groups = array_merge( $this->product_field_groups, $wccvf_posts);   
            
            if ($is_variable_module_enabled == "yes") {
                $wccvf_posts = array();
                $wccvf_posts = wcff()->dao->load_fields_groups_for_product($this->cart_item['variation_id'], 'wccvf', "any", "any", false);
                $this->product_field_groups = array_merge( $this->product_field_groups, $wccvf_posts); 
            }            
            
            if ($is_admin_module_enabled == "yes") {
                /* Also get the admin fields for variations */
                $wccaf_posts = wcff()->dao->load_fields_groups_for_product($this->cart_item['variation_id'], 'wccaf', "variable", "any", true);          
                $this->admin_field_groups = array_merge($this->admin_field_groups, $wccaf_posts);
            }
            
        }  
         
        $index = $this->is_cloning_enabled == "yes" ? 1 : 0;
        
        $this->product_field_groups = array_unique($this->product_field_groups, SORT_REGULAR);
        /* Render Product Fields */
        $this->render_fields($this->product_field_groups, $index);

        $this->admin_field_groups = array_unique($this->admin_field_groups, SORT_REGULAR);
        /* Render Admin Fields that has been configured to show on Product Page */
        $this->render_fields($this->admin_field_groups, $index);

        $show_price_rule_details = isset($wccpf_options["pricing_rules_details"]) ? $wccpf_options["pricing_rules_details"] : "hide";
        if ($show_price_rule_details == "show") {
            /* Mining procss for Custom Pricing */
            $this->render_pricing_rules_data();
        }  
       
        return $this->wccpf_items;
    }
    
    private function render_fields($_groups = array(), $_index = 0) {
        
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
                    /* name attr has been @depricated from 3.0.4 onwards */
                    $fname   = isset($field["key"]) ? ($field["key"] . $key_suffix) : ($field["name"] . $key_suffix);                             
                    if ($field["visibility"] == "yes" && isset($this->cart_item[$fname])) {
                        $this->render_data($field, $this->cart_item[$fname], (($_index > 0) ? " ".$_index : ""));
                    }                    
                }
            }
        }
        
    }
    
    /**
     *
     * Mine the Cart Line Item Object for Custom Pricing Rules
     *
     */
    private function render_pricing_rules_data() {    	
        foreach ($this->cart_item as $ckey => $cval) {
            if (strpos($ckey, "wccpf_pricing_applied_") !== false) {
                $prules = $this->cart_item[$ckey];
                if (isset($prules["title"]) && isset($prules["amount"])) {
                    $this->wccpf_items[] = array("name" => $prules["title"], "value" => $prules["amount"]);
                }
            }
        }    	
    }
    
    /**
     * 
     * Insert custom fields values as Key Val pairs into wccpf_items 
     * 
     * @param object $_field
     * @param string|number|array $_val
     * @param string $_index
     * 
     */
    private function render_data($_field, $_val, $_index = "") {
    	$value = null;
        if ($this->multilingual == "yes") {
        	/* Localize field */
        	$_field = wcff()->locale->localize_field($_field);
        }     	
        $_val = (($_val && isset($_val["user_val"])) ? $_val["user_val"] : $_val);
        if ($_field["type"] != "file" && $_field["type"] != "checkbox") {
        	$value = esc_html(stripslashes($_val));        	
        } else if($_field["type"] == "checkbox") {
    	    /* Since checkbox value is array, we have to deal it seperately */
        	$value = (is_array($_val) ? implode(", ", $_val) : esc_html(stripslashes($_val)));    	   
        } else {
            $is_multi_file = isset($_field["multi_file"]) ? $_field["multi_file"] : "no";
            if ($is_multi_file == "yes") {
                $fnames = array();
                $images = "";
                $farray = json_decode($_val, true);
                foreach ($farray as $fobj) {
                    $path_parts = pathinfo($fobj['file']);
                    $fnames[] = $path_parts["basename"];
                    if (@getimagesize($fobj["url"])) {
                        $images .= "<img src='". esc_url($fobj["url"]) ."' style='width: ". esc_attr($_field["img_is_prev_width"]) ."px' >";
                    }
                }
                if ($_field["img_is_prev"] == "yes" && @getimagesize($fobj["url"])) {
                	$value = $images;                    
                } else {
                	$value = implode(", ", $fnames);                    
                }
            } else {
                $fobj = json_decode($_val, true);
                $path_parts = pathinfo($fobj['file']);
                if ($_field["img_is_prev"] == "yes" && @getimagesize($fobj["url"])) {
                	$value = "<img src='". esc_url($fobj["url"]) ."' style='width: ". esc_attr($_field["img_is_prev_width"]) ."px' />";                    
                } else{
                	$value = $path_parts["basename"];                    
                }
            }
        }
        $cif_data = array(
        	"field_key" => ($_field["label"]),
        	"field_val" => $value
        );
        /* Let other plugins override this value - if they wanted */
        if (has_filter("wcff_before_rendering_cart_data")) {
            $cif_data = apply_filters("wcff_before_rendering_cart_data", $cif_data, $_field );
        }  
        $this->wccpf_items[] = array("name" => $cif_data["field_key"], "value" => $cif_data["field_val"]);
    }
    
}

?>