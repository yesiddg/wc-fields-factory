<?php 

if (!defined('ABSPATH')) {exit;}

/**
 *
 * This moduke is responsible for inserting product field values, admin field values and custom pricing label as order meta.
 *
 * @author Saravana Kumar K
 * @copyright Sarkware Research & Development (OPC) Pvt Ltd
 *
 */

class wcff_order_handler {
    
    /* Order line item ID */
    private $item_id;
    /* Order Line item Object */
    private $item_obj;    
    /* Fields cloning flag */
    private $is_cloning_enabled;
    /* Multilingual flag */
    private $multilingual;
    /* Holds Product fields list */
    private $product_field_groups = null;
    /* Holds Admin fields list */
    private $admin_field_groups = null;
    
    public function __construct() {}
    
    /**
     *
     * Handle 'woocommerce_new_order_item' action ( 'woocommerce_add_order_item_meta' for WC < 3.0.6 )<br>
     * Just delegates the task to other helper method for inserting product, admi and pricing values as order line item meta
     *
     * @param integer $_item_id
     * @param object $_values
     * @param string $_cart_item_key
     *
     */
    public function insert($_item_id, $_values, $_cart_item_key) {
        
        $this->item_id = $_item_id;        
        $wccpf_options = wcff()->option->get_options();
        $this->is_cloning_enabled = isset($wccpf_options["fields_cloning"]) ? $wccpf_options["fields_cloning"] : "no";
        $this->multilingual = isset($wccpf_options["enable_multilingual"]) ? $wccpf_options["enable_multilingual"] : "no";
        $is_admin_module_enabled = isset($wccpf_options["enable_admin_field"]) ? $wccpf_options["enable_admin_field"] : "yes";
		$is_variable_module_enabled = isset($wccpf_options["enable_variable_field"]) ? $wccpf_options["enable_variable_field"] : "yes";
        
        $this->item_obj = $_values;
        /* WC 3+ & Older versions - compatible */
        if (!version_compare(WC()->version, '3.0.0', '<') && isset($_values->legacy_values)) {         
            $this->item_obj = $_values->legacy_values;            
        }
        
        if (isset($this->item_obj["product_id"])) {
            
            $template = "single-product";
            if (WC()->session) {
                /* Get the last used template from session */
                $template = WC()->session->get("wcff_current_template", "single-product");
            }
            
            $this->product_field_groups = wcff()->dao->load_fields_groups_for_product($this->item_obj['product_id'], 'wccpf', $template, "any");

            $this->admin_field_groups = array();            
            if ($is_admin_module_enabled == "yes") {
                $this->admin_field_groups = wcff()->dao->load_fields_groups_for_product($this->item_obj['product_id'], 'wccaf', "any", "any");
            }            
            
            if (isset( $this->item_obj["variation_id"] ) && !empty( $this->item_obj["variation_id"] ) && $this->item_obj["variation_id"] != 0) {                
                $wccvf_posts = array();
                $wccvf_posts = wcff()->dao->load_fields_groups_for_product($this->item_obj['variation_id'], 'wccpf', "variable", "any");
                $this->product_field_groups = array_merge( $this->product_field_groups, $wccvf_posts);
                
                if ($is_variable_module_enabled == "yes") {
                    $wccvf_posts = array();
                    $wccvf_posts = wcff()->dao->load_fields_groups_for_product($this->item_obj['variation_id'], 'wccvf', "any", "any");
                    $this->product_field_groups = array_merge( $this->product_field_groups, $wccvf_posts); 
                }               
                
                if ($is_admin_module_enabled == "yes") {
                    /* Also get the admin fields for variations */
                    $wccaf_posts = wcff()->dao->load_fields_groups_for_product($this->item_obj['variation_id'], 'wccaf', "variable", "any", true);   
                    /* Now eliminate the duplicate fields - Parent & child may have same fields mapped - here child first priority */       
                    $this->removeDupliacteFromAdminFields($wccaf_posts);
                    $this->admin_field_groups = array_merge($this->admin_field_groups, $wccaf_posts);
                }
            }
            
            $this->product_field_groups = array_unique($this->product_field_groups, SORT_REGULAR);
            $this->admin_field_groups = array_unique($this->admin_field_groups, SORT_REGULAR);

            if ($this->is_cloning_enabled == "no") {
                /* Insert Product Fields */
                $this->insert_fields($this->product_field_groups, 0, "wccpf");
                /* Insert Admin Fields that has been configured to show on Product Page */
                $this->insert_fields($this->admin_field_groups, 0, "wccaf");                
            } else {
                $quantity = intval($this->item_obj["quantity"]);
                for ($i = 1; $i <= $quantity; $i++) {
                    /* Insert Product Fields */
                    $this->insert_fields($this->product_field_groups, $i, "wccpf");
                    /* Insert Admin Fields that has been configured to show on Product Page */
                    $this->insert_fields($this->admin_field_groups, $i, "wccaf");                    
                }
            }

            $show_price_rule_details = isset($wccpf_options["pricing_rules_details"]) ? $wccpf_options["pricing_rules_details"] : "hide";
            if ($show_price_rule_details == "show") {
                /**/
                $this->insert_pricing_rules_meta();
            }
            
        }
        
    }

    private function removeDupliacteFromAdminFields($_wccaf_posts) {
        /* Outter loop */
        foreach ($this->admin_field_groups as $gIndex => $oGroup) {
            if (count($oGroup["fields"]) > 0) {
                foreach ($oGroup["fields"] as $fIndex => $oField) {
                    /* Inner loop */
                    foreach ($_wccaf_posts as $iGroup) {
                        if ($oGroup["id"] == $iGroup["id"]) {
                            /* Parent & child has same group mapped */
                            foreach ($iGroup["fields"] as $iField) {
                                if ($iField["key"] == $oField["key"]) {
                                    /* Remove it from parent */
                                    unset($this->admin_field_groups[$gIndex]["fields"][$fIndex]);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    private function insert_fields($_groups = array(), $_index = 0, $_ptype = "wccpf") {
        /*
         * Normal mining process on $_REQUEST object
         * Since we have field level cloning option we have to mine
         * even if cloning option is enabled
         */
        $key_suffix = $_index > 0 ? ("_". $_index) : "";
        foreach ($_groups as $group) {
            if (count($group["fields"]) > 0) {
                foreach ($group["fields"] as $field) {        
                    $value = "";             
                    /* name attr has been @depricated from 3.04 onwards */
                    $fname = isset($field["key"]) ? ($field["key"] . $key_suffix) : ($field["name"] . $key_suffix);
                    if (isset($this->item_obj[$fname])) {
                        $this->insert_field($field, $this->item_obj[$fname], (($_index > 0) ? " ".$_index : ""));
                    } else {
                        /* Look for any admin fields that has to be as order meta (withour showing on front end) */
                        if ($_ptype == "wccaf" && isset($field["order_meta"]) && $field["order_meta"] == "yes") {

                            $fkey = isset($field["key"]) ? $field["key"] : $field["name"];
                            if (isset($this->item_obj["variation_id"]) && $this->item_obj["variation_id"] != 0) {
                                $value = get_post_meta($this->item_obj["variation_id"], $fkey, true);
                            } 
                            /* Fix for parent of varaible fields */                            
                            if (!$value || $value == "") {
                                $value = get_post_meta($this->item_obj["product_id"], $fkey, true);
                            }                        

                            /* If admin not set value, then usde the default value */
                            if (!$value && isset($field["default_value"])) {
                                $value = $field["default_value"];
                            }

                            /* Show the field with value */
                            if ($field["type"] == "checkbox" && is_string($value)) {                    
                                $value = json_decode($value, true);
                            }

                            $this->insert_field($field, $value, (($_index > 0) ? " ".$_index : ""));
                        } else if ($_ptype == "wccpf" && $field["type"] == "hidden" && $field["order_meta"] == "yes") {
                            /* for hidden, which mioght configured to add on order item only */
                            $this->insert_field($field, $field["placeholder"], (($_index > 0) ? " ".$_index : ""));
                        }
                    }            
                }
            }
        }
    }   
    
    /**
     *
     * Responsible for inserting Custom Pricing value as Order Line Item Meta<br>
     * It will mine the Order Item Object for Pricing Rules, once found the entry it will insert as Order Line Item Meta.
     *
     */
    private function insert_pricing_rules_meta() {
        
        foreach ($this->item_obj as $ckey => $cval) {
            if (strpos($ckey, "wccpf_pricing_applied_") !== false) {
                $prules = $this->item_obj[$ckey];
                if (isset($prules["title"]) && isset($prules["amount"])) {
                    $wcff_price_meta = array(
                        "prule_title" => $prules["title"],
                        "prule_amount" => $prules["amount"]
                    );
                    /* Let other plugins override this value - if they wanted */
                    if(has_filter("wcff_before_inserting_pricing_order_meta")) {
                        $wcff_price_meta = apply_filters("wcff_before_inserting_pricing_order_meta", $this->item_id, $prules, $wcff_price_meta);
                    }
                    wc_add_order_item_meta($this->item_id, $wcff_price_meta["prule_title"], $wcff_price_meta["prule_amount"]);
                }
            }
        }
        
    }
    
    /**
     *
     * Helper method which actually does the Order Line Item Meta Inserting Task
     *
     * @param object $_field
     * @param array|string|number $_val
     * @param string $_index
     *
     */
    private function insert_field($_field, $_val, $_index = "") {
        
        $value = null;
        if ($this->multilingual == "yes") {
            /* Localize field */
            $_field= wcff()->locale->localize_field($_field);
        }
        
        $_val = (($_val && isset($_val["user_val"])) ? $_val["user_val"] : $_val);
        if ($_field["type"] != "file" && $_field["type"] != "checkbox") {
            $value = stripslashes($_val);
        } else if($_field["type"] == "checkbox") {
            $value = (is_array($_val) ? implode(", ", $_val) : stripslashes($_val));
        } else {
            if ($_field["multi_file"] == "yes") {
                $furls = array();
                $farray = json_decode($_val, true);
                foreach ($farray as $fobj) {
                    $furls[] = $fobj["url"];
                }
                $value = implode(", ", $furls);
            } else {
                $fobj = json_decode($_val, true);
                $value = $fobj["url"];
            }
        }
        $wcff_order_item_meta = array(
            "field_key" => $_field["label"],
            "field_val" => $value
        );
        /* Let other plugins override this value - if they wanted */
        if(has_filter("wcff_before_inserting_order_item_meta")) {
            $wcff_order_item_meta= apply_filters("wcff_before_inserting_order_item_meta", $wcff_order_item_meta, $this->item_id, $_field);
        }
        wc_add_order_item_meta($this->item_id, $wcff_order_item_meta["field_key"], $wcff_order_item_meta["field_val"]);
        
    }
    
}

?>