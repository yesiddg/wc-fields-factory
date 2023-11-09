<?php
/**
 *
 * Plugin Name: WC Fields Factory
 * Plugin URI: https://wcfieldsfactory.com/
 * Description: Sell your products with personalised options. Add custom fields to your products, variations, checkout, order and your admin screens.
 * Version: 4.1.7
 * Author: Saravana Kumar K
 * Author URI: https://wcfieldsfactory.com/
 * License: GPL
 * Copyright: sarkware
 * WC tested up to: 7.5.1
 *
 */
if (!defined( 'ABSPATH' )) { exit; }

/**
 *
 * WC Fields Factory's Main Class
 *
 * @author 		Saravana Kumar K
 * @copyright 	Sarkware Research & Development (OPC) Pvt Ltd
 *
 */

include_once('includes/wcff_loader.php');

class wcff {
    
    var
    /* Version number and root path details - could be accessed by "wcff()->info" */
    $info,
    /* Data Access Object reference - could be accessed by "wcff()->dao" */
    $dao,
    /* Fields interface - could be accessed by "wcff()->field" */
    $field,
    /* Fields injector instance - could be accessed by "wcff()->injector" */
    $injector,
    /* Fields Persister instance (which mine the REQUEST object and store the custom fields as Cart Item Data) - could be accessed by "wcff()->persister" */
    $persister,
    /* Fields Data Renderer instance - on Cart & Checkout - could be accessed by "wcff()->renderer" */
    $renderer,
    /* Fields Editor instance - on Cart & Checkout (though editing option won't works on Checkout) - could be accessed by "wcff()->editor" */
    $editor,
    /* Used to split the cart item (if the quantity is more than one and cloning is enabled) */
    $splitter,
    /* Pricing & Fee handler instance - could be accessed by "wcff()->negotiator" */
    $negotiator,
    /* Order handler instance - could be accessed by "wcff()->order" */
    $order,
    /* Option object - could be accessed by "wcff()->option" */
    $option,
    /* Html builder object reference - could be accessed by "wcff()->builder" */
    $builder,
    /* Fields Validator instance - could be accessed by "wcff()->validator" */
    $validator,
    /* Fields Translator instance - could be accessed by "wcff()->locale" */
    $locale,
    /* Holds the Ajax request object comes from WC Fields Factory Admin Interfce - could be accessed by "wcff()->request" */
    $request,
    /* Holds the Ajax response object which will be sent back to Client - could be accessed by "wcff()->response" */
    $response,
    /* Loaded flaq */
    $loaded;
    
    public function __construct() {
        
        /* Put some most wanted values on info property */
        $this->info = array(
            'dir'				=> plugin_dir_url(__FILE__),
            'path'				=> plugin_dir_path(__FILE__),
            'assets'			=> plugin_dir_url(__FILE__) ."assets",
            'views'				=> plugin_dir_path(__FILE__) ."views",
            'inc'				=> plugin_dir_path(__FILE__) ."includes",
            'basename'          => plugin_basename(__FILE__),
            'version'			=> '4.1.7'
        );
        
        /* Deactivation hook for cleanup */
        register_deactivation_hook(__FILE__, array($this, 'on_wcff_deactivation'));
        
    }
    
    public function init() {
        $loader = new wcff_loader($this);
        add_action('init', array($loader, 'load'), 1);
    }
    
    public function on_wcff_deactivation() {
        
        /**
         *
         * Clean up the new meta keys added by V4.X.X
         * So that the DB set compatible with WCFF < V3.X.X
         *
         **/
        
        $options = get_option("wcff_options");
        $options =  is_array($options) ? $options : array();
        
        if (isset($options["version"])) {
            unset($options["version"]);
            unset($options["enable_custom_pricing"]);
        }
        update_option("wcff_options", $options);
        
        /* Now remove all new keys from fields group posts */
        /* Clean wccpf */
        $this->cleanup_wcff_post("wccpf");
        /* Clean wccaf */
        $this->cleanup_wcff_post("wccaf");
        /* Clean wccvf */
        $this->cleanup_wcff_post("wccvf");
        /* Clean wcccf */
        $this->cleanup_wcff_post("wcccf");
        
    }
    
    private function cleanup_wcff_post($_ptype) {
        
        /* Fetch the group posts */
        $group_posts = get_posts(
            array(
                "post_type" => $_ptype,
                "posts_per_page" => -1,
                "order" => "ASC"
            )
        );
        
        if (count($group_posts) > 0) {
            /* Loop through all group posts */
            foreach ($group_posts as $g_post) {
                
                delete_post_meta($g_post->ID, $_ptype. '_layout_meta');
                delete_post_meta($g_post->ID, $_ptype. '_show_group_title');
                delete_post_meta($g_post->ID, $_ptype. '_use_custom_layout');
                delete_post_meta($g_post->ID, $_ptype. '_target_stock_status');
                delete_post_meta($g_post->ID, $_ptype. '_product_tab_title');
                delete_post_meta($g_post->ID, $_ptype. '_product_tab_priority');
                delete_post_meta($g_post->ID, $_ptype. '_is_this_group_clonable');
                delete_post_meta($g_post->ID, $_ptype. '_fields_label_alignement');
                delete_post_meta($g_post->ID, $_ptype. '_custom_product_data_tab_title');
                delete_post_meta($g_post->ID, $_ptype. '_custom_product_data_tab_priority');                
                delete_post_meta($g_post->ID, $_ptype. '_is_this_group_for_authorized_only');
                delete_post_meta($g_post->ID, $_ptype. '_wcff_group_preference_target_roles');
                
                /* Get all custom meta */
                $fields = get_post_meta($g_post->ID);
                foreach ($fields as $fkey => $meta) {
                    $flaQ = false;
                    $field = json_decode($meta[0], true);
                    if (isset($field["type"]) && (isset($field["key"]) || isset($field["name"]))) {
                        
                        if ($field["type"] == "checkbox" && isset($field["pricing_rules"]) && is_array($field["pricing_rules"])) {
                            foreach ($field["pricing_rules"] as $pkey => $rule) {
                                if (isset($field["pricing_rules"][$pkey]["old_logic"])) {
                                    $field["pricing_rules"][$pkey]["logic"] = $field["pricing_rules"][$pkey]["old_logic"];
                                }
                            }
                            $flaQ = true;
                        }
                        if ($field["type"] == "checkbox" && isset($field["fee_rules"]) && is_array($field["fee_rules"])) {
                            foreach ($field["fee_rules"] as $pkey => $rule) {
                                if (isset($field["fee_rules"][$pkey]["old_logic"])) {
                                    $field["fee_rules"][$pkey]["logic"] = $field["fee_rules"][$pkey]["old_logic"];
                                }
                            }
                            $flaQ = true;
                        }
                        if ($field["type"] == "checkbox" && isset($field["field_rules"]) && is_array($field["field_rules"])) {
                            foreach ($field["field_rules"] as $pkey => $rule) {
                                if (isset($field["field_rules"][$pkey]["old_logic"])) {
                                    $field["field_rules"][$pkey]["logic"] = $field["field_rules"][$pkey]["old_logic"];
                                }
                            }
                            $flaQ = true;
                        }
                        /* Since V4 remove s the name properties we need add it back for backward compatibility */
                        if (!isset($field["name"])) {
                            $flaQ = true;
                            $field["name"] = $field["key"];
                        }
                        
                        if ($flaQ) {
                            update_post_meta($g_post->ID, $fkey, wp_slash(json_encode($field)));
                        }
                        
                    }
                }
                
                if ($_ptype == "wccaf" && isset($fields["wccaf_location_rules"])) {
                    $lrules = json_decode($fields["wccaf_location_rules"][0], true);
                    
                    if ($lrules && is_array($lrules)) {
                        $lrules = array(array($lrules));
                        update_post_meta($g_post->ID, "wccaf_location_rules", wp_slash(json_encode($lrules)));
                    }
                    
                }
                
            }
        }
        
    }
    
}

/**
 *
 * Returns the Main instance of WC Fields Factory
 *
 * Helper function for accessing Fields Factory Globally
 * Using this function other plugins & themes can access the WC Fields Factory. thus no need of Global Variable.
 *
 */
function wcff() {
    /* Expose WC Fields Factory to Global Space */
    global $wcff;
    /* Singleton instance of WC Fields Factory */
    if (!isset($wcff)) {
        $wcff = new wcff();
        $wcff->init();
    }
    return $wcff;
}

/* Well use 'plugins_loaded' hook to start WC Fields Factory */
wcff();

?>