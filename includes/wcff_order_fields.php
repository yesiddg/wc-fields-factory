<?php

if (!defined('ABSPATH')) {exit;}

/**
 *
 * This module is handling two things
 * Fields visibility on the woocommerce customer email and customer order page (My Account)
 * Manage custom fields wooocmmmerce orders (Order Level Meta - only for store admin)
 *
 * @author Saravana Kumar K
 * @copyright Sarkware Research & Development (OPC) Pvt Ltd
 *
 */

class wcff_order_fields {

    /**
     * 
     * Holds admin fields from all admin field groups
     * 
     */
    private $fields = array();

    public function __construct() {        
        add_filter('woocommerce_order_item_get_formatted_meta_data', array($this, 'handle_fields_visibility'), 10, 2);
    }

    public function handle_fields_visibility($_fields, $_item) {
        
        $res = array();
        $wccpf_options = wcff()->option->get_options();
        $is_admin_module_enabled = isset($wccpf_options["enable_admin_field"]) ? $wccpf_options["enable_admin_field"] : "yes";
		$is_variable_module_enabled = isset($wccpf_options["enable_variable_field"]) ? $wccpf_options["enable_variable_field"] : "yes";

        if (!$this->fields || empty($this->fields)) {
            $this->fields = array();
            /* prepare the admin fields */
            $this->prepare_fields("wccpf");
            if ($is_variable_module_enabled == "yes") {
                $this->prepare_fields("wccvf");
            }            
            if ($is_admin_module_enabled == "yes") {
                $this->prepare_fields("wccaf");
            }            
        }

        if (!is_admin()) {
            foreach ($_fields as $id => $obj) {
                if ($this->is_visible($obj->key)) {
                    $res[$id] = $obj;
                }
            }
        } else {
            $res = $_fields;
        }

        return $res;

    }    

    private function prepare_fields($_ptype) {
        
        $excluded_keys = $this->get_excluded_keys($_ptype);

        /* Fetch the group posts */
	    $group_posts = get_posts(
	        array(
	            "post_type" => $_ptype, 
	            "posts_per_page" => -1,	
	            "order" => "ASC",
				"post_status" => array('publish')	            
	        )
	    );	    
	    	    
	    if (count($group_posts) > 0) {
	        /* Loop through all group posts */
	        foreach ($group_posts as $g_post) {	    			
	            /* Get all custom meta */
	            $fields = get_post_meta($g_post->ID);
                foreach ($fields as $key => $meta) {
                    /* Exclude special purpose custom meta */
                    if (!in_array($key, $excluded_keys) && (strpos($key, $_ptype ."_") === 0)) {
                        $field = json_decode($meta[0], true);
                        if (isset($field["label"])) {
                            $this->fields[$field["label"]] = $field;    
                        }                        
                    }
                }
            }
        }

    }

    private function is_visible($_key) {

        if (isset($this->fields[$_key])) {
            if (isset($this->fields[$_key]["email_meta"]) && $this->fields[$_key]["email_meta"] == "no") {
                return false;
            }
        }        
        return true;

    }

    private function get_excluded_keys($_ptype) {

        $excluded_keys = array();
        $special_keys = wcff()->dao->get_wcff_special_keys();
        	    
        foreach ($special_keys as $key) {
            $excluded_keys[] = $_ptype ."_" . $key;
        }
	
	    return $excluded_keys;

    }

}

new wcff_order_fields();

?>