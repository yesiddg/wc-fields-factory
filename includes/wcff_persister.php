<?php 

if (!defined('ABSPATH')) {exit;}

/**
 *
 * One of the core module which is responsible for mining the $_REQUEST object for custom fields
 * and retrive the value and store it as the meta on Cart Line Item.
 *
 * @author : Saravana Kumar K
 * @copyright : Sarkware Research & Development (OPC) Pvt Ltd
 *
 */

class wcff_persister {
    
    /* ID of the product that is being Added To Cart */
    private $pid;
    /* Cart item custom data holder */
    private $cart_item_data;
    
    /* Fields cloning flaq */
    private $is_cloning_enabled = "no";
    /* Holds product fields list (from all group) */
    private $product_field_groups = null;
    /* Holds admin fields list (from all group) */
    private $admin_field_groups = null;
    
    public function __construct() {}
    
    /**
     *
     * This method will be called whenever an Add To Cart operation performed<br/>
     * It does the Mining & extracting user submitted custo fields data and store them as Cart Item Data.
     *
     * @param array $_cart_item_data
     * @param integer $_product_id
     * @return array| unknown
     *
     */
    public function persist($_cart_item_data, $_product_id, $_variation_id = null) {

        if (isset($_cart_item_data["wcff_unique_key"]) || isset($_cart_item_data["bundled_by"])) {
            return $_cart_item_data;
        }
        
        $this->pid = $_product_id;
        $this->cart_item_data = $_cart_item_data;
        /* Make sure it is an Array */
        if (! is_array($this->cart_item_data)) {
            $this->cart_item_data = array();
        }
        
        $wccpf_options = wcff()->option->get_options();
        $this->is_cloning_enabled = isset($wccpf_options["fields_cloning"]) ? $wccpf_options["fields_cloning"] : "no";        
        $is_admin_module_enabled = isset($wccpf_options["enable_admin_field"]) ? $wccpf_options["enable_admin_field"] : "yes";
		$is_variable_module_enabled = isset($wccpf_options["enable_variable_field"]) ? $wccpf_options["enable_variable_field"] : "yes";

        /* Get the last used template from session */
        $template = "single-product";
        if (WC()->session) {
            $template = WC()->session->get("wcff_current_template", "single-product");
        }
        
        $this->product_field_groups = wcff()->dao->load_fields_groups_for_product($this->pid, 'wccpf', $template, "any");

        $this->admin_field_groups = array();
        if ($is_admin_module_enabled == "yes") {
            $this->admin_field_groups = wcff()->dao->load_fields_groups_for_product($this->pid, 'wccaf', $template, "any");
        }        
        
        /* If it is Variation products, then loads fields for Variations too */
        if (isset($_variation_id) && $_variation_id != null && $_variation_id != 0) {

            $wccvf_posts = array();
            $wccvf_posts = wcff()->dao->load_fields_groups_for_product($_variation_id, 'wccpf', "variable", "any");            
            $this->product_field_groups = array_merge( $this->product_field_groups, $wccvf_posts);           

            if ($is_variable_module_enabled == "yes") {
                $wccvf_posts = array();
                $wccvf_posts = wcff()->dao->load_fields_groups_for_product($_variation_id, 'wccvf', "any", "any");
                $this->product_field_groups = array_merge( $this->product_field_groups, $wccvf_posts);    
            }            
            
            if ($is_admin_module_enabled == "yes") {
                /* Also get the admin fields for variations */
                $wccaf_posts = wcff()->dao->load_fields_groups_for_product($_variation_id, 'wccaf', "variable", "any", true);          
                $this->admin_field_groups = array_merge($this->admin_field_groups, $wccaf_posts);
            }

        }

        $this->product_field_groups = array_unique($this->product_field_groups, SORT_REGULAR);
        $this->admin_field_groups = array_unique($this->admin_field_groups, SORT_REGULAR);
    
        if ($this->is_cloning_enabled == "no") {
            /* Persist Product Fields */
            $this->persist_fields($this->product_field_groups);            
            /* Persist Admin Fields that has been configured to show on Product Page */
            $this->persist_fields($this->admin_field_groups);
        } else {
            $quantity = intval($_REQUEST["quantity"]);
            for ($i = 1; $i <= $quantity; $i++) {
                /* Persist Product Fields */
                $this->persist_fields($this->product_field_groups, $i);
                /* Persist Admin Fields that has been configured to show on Product Page */
                $this->persist_fields($this->admin_field_groups, $i);
            }
        }     
       
        /* Return the prepared custom fields (key=>value) list */       
        return $this->cart_item_data;
        
    }
    
    /**
     * 
     * Mining the $_REQUEST object for Product Fields
     * 
     */
    private function persist_fields($_groups = array(), $_index = 0) {
        /*
         * Normal mining process on $_REQUEST object
         * Since we have field level cloning option we have to mine
         * even if cloning option is enabled
         */
        $key_suffix = $_index > 0 ? ("_". $_index) : "";        
        foreach ($_groups as $group) {
            if (count($group["fields"]) > 0) {
                foreach ($group["fields"] as $field) {
                    
                    /* name attr has been @depricated from 3.0.4 onwards */
                    $fname   = isset($field["key"]) ? ($field["key"] . $key_suffix) : ($field["name"] . $key_suffix);           
                    
                    if (isset($_REQUEST[$fname])) {
                        $this->persist_field($field, $_REQUEST[$fname], $key_suffix);
                    } else if (isset($_FILES[$fname])) {
                        $this->persist_field($field, $_FILES[$fname], $key_suffix);
                    }

                }
            }
        }
    }
    
    
    /**
     *
     * Does the extraction of custom fields data from $_REQUEST object<br/>
     * and store them as Cart Item Data
     *
     * @param object $_field
     * @param mixed $_val
     * @param string $_index
     *
     */
    private function persist_field($_field, $_val, $_index = "") {
        /* name attr has been @depricated from 3.04 onwards */
        $fkey   = isset($_field["key"]) ? $_field["key"] : $_field["name"];        
        /* Extract fee rules for later use */
        $fee_rules = isset($_field["fee_rules"]) ? $_field["fee_rules"] : array();
        /* Extract price rules for later use */
        $price_rules = isset($_field["pricing_rules"]) ? $_field["pricing_rules"] : array();
        
        if ($_field["type"] != "file") {
            $res = "";
            /* This option is used for select field, in that case we will store the Option's Label instead Value */
            $option_label = isset($_field["show_selected_val_lab"]) ? ($_field["show_selected_val_lab"] == "yes" ? true : false) : false;
            if ($_field["type"] == "select" && $option_label) {
                $get_option = explode(";", $_field["choices"]);
                for ($j = 0; $j < count($get_option); $j ++) {
                    $sin_option = explode("|", $get_option[$j]);
                    if ($sin_option[0] == $_val) {
                        $res = $sin_option[1];
                    }
                }
            } else {
                /* Other fields can be directly stored */
                $res = $_val;
            }
            /* Make sure the select field placeholder not there */
            if ($_field["type"] == "select" && $res == "wccpf_none") {
                return;
            }
            /* Make sure the value is valid (not empty) */
            if (is_array($res) || trim($res)) {
                $cif_data = array(
                    "field_key" => $fkey . $_index,
                    "field_val" => array(
                        "fname" => $fkey . $_index,
                        "ftype" => $_field["type"],
                        "user_val" => $res,
                        "fee_rules" => $fee_rules,
                        "pricing_rules" => $price_rules,
                        /* Applicable only for Date field */
                        "format" => ($_field["type"] == "datepicker") ? ($_field["date_format"] != "" ? $_field["date_format"] : "d-m-Y") : ""
                    )
                );
                /* Let other plugins override this value - if they wanted */
                if (has_filter("wcff_before_inserting_cart_data")) {
                    $cif_data = apply_filters("wcff_before_inserting_cart_data", $_field, $cif_data);
                }
                /* Well insert into cart data */
                $this->cart_item_data[$cif_data["field_key"]] = $cif_data["field_val"];
            }
        } else {
            /* Process file upload */
            $this->persist_file_field($_field, $_val, $_index);
        }
    }
    
    /**
     *
     * Upload the submitted file via custom File Field and store the meta in cart line item
     *
     * @param object $_field
     * @param object $_val ( $_FILE )
     * @param number $_index
     *
     */
    private function persist_file_field($_field, $_val, $_index = "") {
        /* name attr has been @depricated from 3.04 onwards */
        $fkey   = isset($_field["key"]) ? $_field["key"] : $_field["name"];        
        // upload directory
        if (isset($_field["upload_url"]) && !empty($_field["upload_url"])) {            
            Global $copy_field_upload_dir;
            $copy_field_upload_dir = $_field["upload_url"];
            add_filter('upload_dir', array($this, 'custom_upload_dir'));            
        }
        $res = array();
        $is_multi_file = isset($_field["multi_file"]) ? $_field["multi_file"] : "no";
        /* Handle the file upload */
        if ($is_multi_file == "yes") {
            /* fiels makes more sense then val */
            $files = $_val;
            foreach ($files['name'] as $key => $value) {
                if ($files['name'][$key]) {
                    $file = array(
                        'name' => $files['name'][$key],
                        'type' => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'error' => $files['error'][$key],
                        'size' => $files['size'][$key]
                    );
                    
                    $temp_res = $this->process_file_upload($file);
                    if (isset($temp_res['error'])) {
                        $res = $temp_res;
                        break;
                    } else {
                        $res[] = $temp_res;
                    }
                }
            }
        } else {
            $res = $this->process_file_upload($_val);
        }
        
        if (!isset($res['error'])) {
            /* File field doesn't support pricing and fee rules */
            $cif_data = array(
                "field_key" => $fkey . $_index,
                "field_val" => array(
                    "fname" => $fkey,
                    "ftype" => $_field["type"],
                    "user_val" => json_encode($res),
                    "fee_rules" => array(),
                    "pricing_rules" => array(),
                    /* Applicable only for Date field */
                    "format" => ""
                )
            );
            /* Let other plugins override this value - if they wanted */
            if (has_filter("wcff_before_inserting_cart_data")) {
                $cif_data = apply_filters("wcff_before_inserting_cart_data", $_field, $cif_data);
            }
            /* Well insert iinto cart data */
            $this->cart_item_data[$cif_data["field_key"]] = $cif_data["field_val"];
            do_action('wccpf_file_uploaded', $res);
        } else {
            wc_add_wp_error_notices($_field["message"], 'error');
        }
    }
    
    /**
     *
     * Helping method which does the actual uploading process<br/>
     * Using Wordpress's 'wp_handle_upload' method.
     *
     * @param $_FILE $_uploadedfile
     * @return array
     *
     */
    private function process_file_upload($_uploadedfile) {
        if (! function_exists('wp_handle_upload')) {
            require_once (ABSPATH . 'wp-admin/includes/file.php');
        }
        $movefile = wp_handle_upload($_uploadedfile, array(
            'test_form' => false
        ));
        return $movefile;
    }
    
    /**
     *
     * Handler for 'upload_dir' filter, where you can specify custom upload directory for your file upload
     *
     * @param  string $_urls
     * @return string
     *
     */
    public function custom_upload_dir($_urls) {
        Global $copy_field_upload_dir;
        $_urls['path'] = WP_CONTENT_DIR . '/' . $copy_field_upload_dir;
        $_urls['url'] = WP_CONTENT_URL . '/' . $copy_field_upload_dir;
        return $_urls;
    }
    
    /**
     * 
     * We will split the cart line item if user enabled the fields cloning option
     * When the ordered quantity is more then one
     * 
     */
    public function split_cart_line_item($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
              
        if ($this->is_cloning_enabled == "yes" && $quantity > 1) {         
            /* Backup the cart item data */
            $original_cart_item_data = $cart_item_data;
            
            // remove old cart item and add new
            if (WC()->cart->remove_cart_item($cart_item_key)) {
                for ($i = 1; $i <= $quantity; $i++) {
                    $this->cart_item_data = array();
                    $this->cart_item_data['wcff_unique_key'] = md5( microtime() . rand() . "wcff_cart_key_cloning" );
                    
                    /* Stripe down unwanted item data */
                    $this->extract_line_item_data($this->product_field_groups, $original_cart_item_data, $i, $quantity);
                    $this->extract_line_item_data($this->admin_field_groups, $original_cart_item_data, $i, $quantity);

                    WC()->cart->add_to_cart($product_id, 1, $variation_id, $variation, $this->cart_item_data);   
                }
            }
        }                       
         
    }   
    
    private function extract_line_item_data($_groups, $item_data, $index, $total) {
        
        foreach ($_groups as $group) {
            if (count($group["fields"]) > 0) {
                foreach ($group["fields"] as $field) {                    
                    /* name attr has been @depricated from 3.04 onwards */
                    $fkey   = isset($field["key"]) ? $field["key"] : $field["name"];                      
                    $field["cloneable"] = isset($field["cloneable"]) ? $field["cloneable"] : "yes";
                    
                    if (isset($item_data[$fkey ."_". $index])) {
                        $this->cart_item_data[$fkey ."_1"] = $item_data[$fkey ."_". $index];
                        $this->cart_item_data[$fkey ."_1"]["fname"] = $fkey;
                    }
                }
            }
        }       
        
    }
    
    function validate($_prod_id, $_name, $_value, $cart_key, $_variation_id = 0) {
        
        $is_passed = true;
        $is_admin  = false;
        
        $wccpf_options = wcff()->option->get_options ();
        $this->fields_cloning = isset($wccpf_options["fields_cloning"]) ? $wccpf_options["fields_cloning"] : "yes";

        $is_admin_module_enabled = isset($wccpf_options["enable_admin_field"]) ? $wccpf_options["enable_admin_field"] : "yes";
        //$is_variable_module_enabled = isset($wccpf_options["enable_variable_field"]) ? $wccpf_options["enable_variable_field"] : "yes";

        $this->product_fields = wcff()->dao->load_fields_for_product($_prod_id, 'wccpf');

        $this->admin_fields = array();
        if ($is_admin_module_enabled == "yes") {
            $this->admin_fields = wcff()->dao->load_fields_for_product($_prod_id, 'wccaf', 'woocommerce_product_options_general_product_data');
        }        
        
        $variation = null;
        global $woocommerce;
        $citems = $woocommerce->cart->get_cart();
        foreach( $citems as $cart_key_org => $cvalue ){
            if( $cvalue['product_id'] == $_prod_id && $cart_key == $cart_key_org && isset( $cvalue['variation_id'] ) ){
                $variation = $cvalue['variation_id'];
            }
        }
        if( $variation != null && !empty( $variation ) && $variation != 0 ){
            $this->product_fields = array_merge( $this->product_fields, wcff()->dao->load_fields_for_product($variation, 'wccpf', 'cart-page'));
            if ($is_admin_module_enabled == "yes") {
                $this->admin_fields = array_merge( $this->product_fields, wcff()->dao->load_fields_for_product($variation, 'wccaf', 'cart-page'));
            }
        }
        //$a_field 		= null;
        $fieldc  		= null;
        $fieldac		= null;
        $msg			= "";
        foreach ($this->product_fields as $val) {
            if(isset($val["wccpf_".$_name ])){
                $fieldc = $val["wccpf_".$_name];
            }
            if( $this->fields_cloning == "yes" ){
                if(isset($val["wccpf_".$_name."_1"])){
                    $fieldc = $val["wccpf_".$_name."_1"];
                }
            }
        }
        foreach ($this->admin_fields as $avalue) {
            if(isset($avalue["wccaf_".$_name])){
                $fieldac = $avalue["wccaf_".$_name];
            }
            if( $this->fields_cloning == "yes" ){
                if(isset($val["wccaf_".$_name."_1"])){
                    $fieldac = $val["wccaf_".$_name."_1"];
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
                    $res = wcff()->validator->validate_immediate($_prod_id, $field, $_name, $_value);
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
                $res = wcff()->validator->validate_immediate($_prod_id, $afield, $_name, $_value);
            }
            if (!$res) {
                $is_passed = false;
                $msg = $afield ["message"];
            }
        }
        return array("status" => $is_passed, "is_admin" => $is_admin, "msg" => $msg);
    }   
    
}

?>