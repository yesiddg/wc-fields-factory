<?php

if (!defined('ABSPATH')) {exit;}
/**
 *
 * @author 	    : Saravana Kumar K
 * @copyright   : Sarkware Research & Development (OPC) Pvt Ltd
 *
 * This module is responsible for loading and initializing various components of WC Fields Factory
 *
 */


include_once('wcff_post_list_table.php');
include_once('wcff_setup.php');

class wcff_loader {
    
    private $wcff;    
    private $setup;
    private $wcff_options;
    private $admin_module = "yes";
    private $checkout_module = "yes";	
    
    public function __construct($_wcff) {
        $this->wcff = $_wcff;
        $this->setup = new wcff_setup();
    }
    
    /**
     * This has two primary responsible
     * 1. Initialize all the custom post types that WC Fields Factory needed
     * 2. Initialize menu and submenu on wp-admin page
     */
    public function load() {
        
        if (!$this->wcff->loaded) {
            
            /* Make sure woocommerce installed and activated */
            if (function_exists('WC')) {

                $this->wcff_options = get_option("wcff_options");
	            $this->wcff_options =  is_array($this->wcff_options) ? $this->wcff_options : array();   
                $this->admin_module = isset($this->wcff_options["enable_admin_field"]) ? $this->wcff_options["enable_admin_field"] : "yes";
                $this->checkout_module = isset($this->wcff_options["enable_checkout_field"]) ? $this->wcff_options["enable_checkout_field"] : "yes";	

                /* Register wcff core post types */
                $this->setup->register_wcff_post_types();                
                /* Load the necessary fiels to prepare the Env */
                $this->load_environment();
                
                if (is_admin()) {
                    /* Initiate wcff admin module */
                    $this->setup->init_wcff_admin();
                }
                include_once('wcff_product_fields.php');
                
                /* Flaq that marks wcff instance exists */
                $this->wcff->loaded = true;
            } else {
                add_action('admin_notices', array($this, 'wcff_woocommerce_not_found_notice'));
            }
            
        }
    }
    
    private function load_environment() {

        include_once('wcff_request.php');
        include_once('wcff_response.php');
        include_once('wcff_dao.php');
        include_once('wcff_builder.php');
        include_once('wcff_validator.php');
        include_once('wcff_options.php');
        include_once('wcff_ajax.php');
        include_once('wcff_injector.php');
        include_once('wcff_cart_renderer.php');
        include_once('wcff_cart_editor.php');
        include_once('wcff_negotiator.php');
        include_once('wcff_persister.php');
        include_once('wcff_order_handler.php');
        include_once('wcff_order_fields.php');
        include_once('wcff_locale.php');               
        include_once('wcff_checkout_fields.php');        
        
        if (is_admin() || is_user_logged_in()) {
            include_once('wcff_post_handler.php');
            if ($this->admin_module == "yes") {
                include_once('wcff_admin_fields.php');
            }            
            include_once(plugin_dir_path( __FILE__). '../views/meta_box_option.php');
            include_once(plugin_dir_path( __FILE__). '../views/meta_box_variation_fields.php');
        }
        
        $this->init_wcff_env();
        /* DB sanity check, since V 4.X.X */
        $this->db_sanity_check();

    }
    
    private function init_wcff_env() {
        
        /* Instanciate Data Access Object */
        $this->wcff->dao = new wcff_dao();
        /* Instanciate UI builder object */
        $this->wcff->builder = new wcff_builder();
        /* Instanciate WCFF options */
        $this->wcff->option = new wcff_options();
        /* Instanciate Fields Injector object */
        $this->wcff->injector = new wcff_injector();
        /* Instanciate Fields Validator */
        $this->wcff->validator = new wcff_validator();
        /* Instanciate Fields Persister object */
        $this->wcff->persister = new wcff_persister();
        /* Instanciate Cart & CheckOut Data Render object */
        $this->wcff->renderer = new wcff_cart_renderer();
        /* Instanciate Cart Fields Editor Object */
        $this->wcff->editor = new wcff_cart_editor();        
        /* Instanciate Order Handler object */
        $this->wcff->order = new wcff_order_handler();
        /* Instanciate Pricing & Fee handler object */
        $this->wcff->negotiator = new wcff_negotiator();
        /* Instanciate Multilingual object */
        $this->wcff->locale = new wcff_locale();
        
        if (version_compare(WC()->version, '3.2.0', '>') && $this->checkout_module == "yes") {
            /* Instanciate CheckoutFields object */
            $this->wcff->checkout = new wcff_checkout_fields();
        }
        
    }
    
    public function wcff_woocommerce_not_found_notice() { ?>
        <div class="error">
            <p><?php _e('WC Fields Factory requires WooCommerce, Please make sure it is installed and activated.', 'wc-fields-factory'); ?></p>
        </div>
    	<?php
    }

    private function db_sanity_check() {

        $wcff_options = wcff()->option->get_options();
        /* If the wcff option not have "version" property 
        that means the installation is prior to V4XXX */
        if (!isset($wcff_options["version"])) {
            wcff()->dao->migrate_for_version_4xxx();
        }
        
    }
    
}

?>