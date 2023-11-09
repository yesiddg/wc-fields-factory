<?php 

if (!defined('ABSPATH')) { exit; }

/**
 * 
 * One of the core module, which is responsible for the registering necessary hooks for the lifecycle of<br><br>
 * 1. Injecting Fields on Single Product Page<br>
 * 2. Add To Cart handler<br>
 * 3. Rendering Fields on Cart & Checkout Page<br>
 * 4. Edit fields on Cart Page<br>
 * 5. Pricing & Fee handler<br>
 * 6. Order Meta Handler
 *
 * @author 	    : Saravana Kumar K
 * @copyright   : Sarkware Research & Development (OPC) Pvt Ltd
 *
 */

class wcff_product_fields {

	/* Flag for tab location fields */
	private $is_tab_fields = false;
	/**/
	private $is_native_add_to_cart = true;
	
	public function __construct() {
		$this->registerHooks();
	}
	
	public function registerHooks() {
		
		$wcff_options = get_option("wcff_options");
	    $wcff_options =  is_array($wcff_options) ? $wcff_options : array();
		
		$show_custom_data = isset($wcff_options["show_custom_data"]) ? $wcff_options["show_custom_data"] : "yes";
		$fields_on_archive = isset($wcff_options["fields_on_archive"]) ? $wcff_options["fields_on_archive"] : "no";
		$cart_editable = isset($wcff_options["edit_field_value_cart_page"]) ? $wcff_options["edit_field_value_cart_page"] : "no";		
		$is_pricing_module_enabled = isset($wcff_options["enable_custom_pricing"]) ? $wcff_options["enable_custom_pricing"] : "yes";

		$product_location_priority = isset($wcff_options["product_priority"]) ? intVal($wcff_options["product_priority"]) : 10;
		$archive_location_priority = isset($wcff_options["archive_priority"]) ? intVal($wcff_options["archive_priority"]) : 10;		

		/* Fields location on single product page */
	    $global_location_single = isset($wcff_options["field_location"]) ? $wcff_options["field_location"] : "woocommerce_before_add_to_cart_button";	  
	    /* Fields location for archive product */
	    $global_location_archive = isset($wcff_options["field_archive_location"]) ? $wcff_options["field_archive_location"] : "woocommerce_before_shop_loop_item";

		$product_custom_location = "";
		if ($global_location_single == "woocommerce_product_custom_location") {
			$product_custom_location = isset($wcff_options["custom_product_fields_location"]) ? $wcff_options["custom_product_fields_location"] : "";
		}

		$archive_custom_location = "";
		if ($global_location_archive == "woocommerce_archive_custom_location") {
			$archive_custom_location = isset($wcff_options["custom_archive_fields_location"]) ? $wcff_options["custom_archive_fields_location"] : "";
		}

		/* Product page location hooks list */
		$product_field_locations = apply_filters('wcff_single_product_template_locations', array(
			"woocommerce_before_add_to_cart_button", 
			"woocommerce_after_add_to_cart_button", 
			"woocommerce_before_add_to_cart_form", 
			"woocommerce_after_add_to_cart_form", 
			"woocommerce_before_single_product_summary", 
			"woocommerce_after_single_product_summary", 
			"woocommerce_single_product_summary",
			"woocommerce_single_product_tab",
			"woocommerce_product_meta_start", 
			"woocommerce_product_meta_end"
		));
		
		/* Archive page location hooks list */
		$archive_field_locations = apply_filters('wcff_archive_product_template_locations', array(
			"woocommerce_before_shop_loop_item", 
			"woocommerce_before_shop_loop_item_title", 
			"woocommerce_shop_loop_item_title", 
			"woocommerce_after_shop_loop_item_title", 
			"woocommerce_after_shop_loop_item"
		));		
		
		/** STEP 1 - Fields Injections **/
		
		add_action('wp_footer', array($this, 'wcff_load_client_assets'));
		
		/* Add extra html tag and attributes for wordpress tags sanitize rules */
		$this->add_wcff_html_attributes();		
		
		/* Register field group wise placement */
		for ($i = 0; $i < count($product_field_locations); $i++) {	
			if ($product_field_locations[$i] != "woocommerce_single_product_tab") {
				/* Inject fields on single product page */
				add_action($product_field_locations[$i], array($this, 'single_product_template_fields_injector'), $product_location_priority);	
			} else {
				/* If admin wants to inject the custom fields on a seperate tab ( on the single product page ) */
				add_filter('woocommerce_product_tabs', array($this, 'single_product_template_tab_fields_injector'), $product_location_priority);
			}					
		}

		if ($product_custom_location != "") {
			add_action($product_custom_location, array($this, 'single_product_template_fields_injector'), $product_location_priority);	
		}		
		
		if ($fields_on_archive == "yes") {
			/* Register field group wise placement on archive page */
			for ($i = 0; $i < count($archive_field_locations); $i++) {
				add_action($archive_field_locations[$i], array($this, 'archive_template_fields_injector'), $archive_location_priority);
			}
		}

		if ($archive_custom_location != "") {
			add_action($archive_custom_location, array($this, 'archive_template_fields_injector'), $archive_location_priority);	
		}
		
		/* To add a hidden fields with product price in the archive page */
		add_action( 'woocommerce_after_shop_loop_item_title', array($this, 'inject_hidden_price_tag_on_archive'), 10 );
		
		/* Inject a place holder for Variation Fields */
		add_action('woocommerce_before_add_to_cart_button', array(wcff()->injector, 'inject_placeholder_for_variation_fields'), 1);
		
		/* Meta datas that needed for client side logics */
		//add_action('woocommerce_after_main_content', function () {wcff()->injector->enqueue_wcff_client_side_meta(true); });
		
		/** STEP 2 - Validation **/
		
		/* Register validation handler for add to cart action */
		add_filter('woocommerce_add_to_cart_validation', array($this, 'fields_validator'), 99, 2);
		
		/** STEP 3 - Data Capture **/
		
		/* Register handler for handling add to cart action, this is where all the custom fields
		 * that is being submitted by the users will be persisted */
		add_filter('woocommerce_add_cart_item_data', array($this, 'fields_persister' ), 10, 3);
		
		//split cloning cart item
		add_action('woocommerce_add_to_cart',  array($this, 'split_cart_item_for_cloning'), 999, 6);
		
		
		/** STEP 4 - Data Render **/
		
		/* Register handler for rendering custom field on cart page
		 * Before that make sure admin wants to display the data on Cart & Checkout */
		if ($show_custom_data == "yes") {
		    if ($cart_editable == "yes") { 
				/* If this is the case then we are responsible for rendering custom field data
				 * into the cart and checkout */
				add_filter('woocommerce_cart_item_name', array($this, 'fields_cloning_cart_handler'), 999, 3);
				add_filter('woocommerce_checkout_cart_item_quantity', array($this, 'fields_cloning_checkout_handler'), 999, 3);
			} else {
				/* Here we are using woocommerce default line item attribute render method
				 * Just have to supply the field's key value, rest will be handled by the woocommerec itself */
				add_filter('woocommerce_get_item_data', array($this, 'cart_data_handler'), 999, 2);				
			}
		}
		
		/** STEP 5 - Custom Pricing **/		
		
		if ($is_pricing_module_enabled == "yes") {
		    /* Register handler for Pricing rules 1*/
		    /* Pricing issue new fixing */
		    add_filter('woocommerce_add_cart_item',  array($this, 'pricing_rules_handler'), 999, 2);
		    add_filter('woocommerce_get_cart_item_from_session',  array($this, 'pricing_rules_handler'), 999, 2);
		    
		    /* Register handler for Fee rules */
		    add_action('woocommerce_cart_calculate_fees', array($this, 'fee_rules_handler'), 999);

			/* Add support for tier pricing addon */
			add_filter('tier_pricing_table/cart/product_cart_price', array($this, 'tier_pricing_rule_handler'), 999, 3);
		}		
		
		/** STEP 6 - Order Meta **/
		
		/* WC 3.0.6 update */
		if (version_compare(WC()->version, '3.0.0', '<')) {
			add_action('woocommerce_add_order_item_meta', array($this, 'fields_order_meta_handler'), 99, 3);
		} else {
			add_action('woocommerce_new_order_item', array($this, 'fields_order_meta_handler'), 99, 3);
		}

		/* Adding support for  */		

		/** Adding support for GoCart **/
		add_filter('cocart_prepare_product_object_v2', array($this, 'expose_product_fields_for_rest_api'), 99);
		add_filter('cocart_prepare_product_object', array($this, 'expose_product_fields_for_rest_api'), 99);

		add_filter('cocart_prepare_product_variation_object_v2', array($this, 'expose_variation_fields_for_rest_api'), 99);
		add_filter('cocart_prepare_product_variation_object', array($this, 'expose_variation_fields_for_rest_api'), 99);

	}
	
	/**
	 * Product fields injector handler
	 */
	public function single_product_template_fields_injector() {
		if (is_product() || is_singular("courses")) {
			/* Inject the custom fields into the single product page */
			$action_name = $this->is_tab_fields ? "woocommerce_single_product_tab" : current_action();
			/* Initiate Fields Injection */
			wcff()->injector->inject_product_fields($action_name, "single-product");
			/* Reset the tab flag */
			$this->is_tab_fields = false;
		}		
	}
	
	/**
	 * 
	 * Create a new tab item and delegate the task to fields injector
	 * 
	 * @param array $_tabs
	 * @return array
	 * 
	 */
	public function single_product_template_tab_fields_injector($_tabs=array()) {
		if (is_product()) {
			$wcff_options = wcff()->option->get_options();
			$this->is_tab_fields = true;
			
			$tab_title = "";		
			if (isset($wcff_options["product_tab_title"]) && !empty($wcff_options["product_tab_title"])) {
				$tab_title = $wcff_options["product_tab_title"];
			}
			
			$tab_priority = 999;
			if (isset($wcff_options["product_tab_priority"]) && !empty($wcff_options["product_tab_priority"])) {
				$tab_priority = $wcff_options["product_tab_priority"];
			}
			
			$_tabs['wccpf_fields_tab'] = array(
				'title' => $tab_title,
				'priority' => $tab_priority,
				'callback' => array($this, 'single_product_template_fields_injector')
			);
			return $_tabs;
		}
	    
		return "";
	}
	
	public function archive_template_fields_injector() {
		if (is_woocommerce()) {
			/* Initiate Fields Injection */ 
			wcff()->injector->inject_product_fields(current_action(), "archive-product");
		}	    
	}
	
	public function inject_hidden_price_tag_on_archive() {
	    global $product;
	    if (is_archive() && $product) {
	        echo '<input type="hidden" class="wccpf_archive_price_tag" value="'. $product->get_price() .'"/>';
	        echo '<input type="hidden" class="wccpf_archive_is_variable_tag" value="'. ($product->is_type('variable') ? "yes" : "no") .'"/>'; 
	    }
	}
	
	/**
	 *
	 * Call the validation module to perform validation on Product as well as Admin Fields
	 *
	 * @param boolean $_passed
	 * @param integer $_pid
	 * @return boolean
	 *
	 */
	public function fields_validator($_passed, $_pid = null) {
	    /* Delegate the task to Validation module */
	    $is_ok = wcff()->validator->validate($_pid, $_passed);		
	    if(!$is_ok) {			
			WC()->session->set("wcff_validation_failed", true);
		}
		return $is_ok;
	}
	
	/**
	 * 
	 * Cart data persist handler
	 * 
	 * @param object $_cart_item_data
	 * @param integer $_product_id
	 * @param integer $_variation_id
	 * @return object
	 * 
	 */
	public function fields_persister($_cart_item_data, $_product_id, $_variation_id) {
		/* Delegate the task to Persister module */
		if ($this->is_native_add_to_cart) {
			return wcff()->persister->persist($_cart_item_data, $_product_id, $_variation_id);
		} else {
			return $_cart_item_data;
		}
	}
	
	/**
	 * 
	 * Price rule handler
	 * 
	 * @param object $citem
	 * @param string $cart_item_key
	 * @return object
	 * 
	 */
	public function pricing_rules_handler($_citem, $_cart_item_key) {
		return wcff()->negotiator->handle_custom_pricing($_citem, $_cart_item_key);
	}
	
	/**
	 * 
	 * Fee rule handler
	 * 
	 * @param object $_cart
	 * 
	 */
	public function fee_rules_handler($_cart = null) {
		wcff()->negotiator->handle_custom_fee($_cart);
	}


	public function tier_pricing_rule_handler($_new_price, $_cart_item, $_key) {
		return wcff()->negotiator->handle_tier_pricing($_new_price, $_cart_item, $_key);
	}
	
	/**
	 * 
	 * Custom data render on Cart Page - handler
	 * 
	 * @param object $_cart_data
	 * @param object $_cart_item
	 * @return object
	 * 
	 */
	public function cart_data_handler($_cart_data, $_cart_item = null) {
		return wcff()->renderer->render_fields_data($_cart_data, $_cart_item);
	}
	
	/**
	 * 
	 * Custom data render on Cart Page - handler (for cloning)
	 * 
	 * @param string $_title
	 * @param object $_cart_item
	 * @param string $_cart_item_key
	 * @return string
	 * 
	 */
	public function fields_cloning_cart_handler($_title = null, $_cart_item = null, $_cart_item_key = null) {
		if (is_cart()) {
			return wcff()->editor->render_fields_data($_title, $_cart_item, $_cart_item_key, false);
		}
		return $_title;
	}
	
	/**
	 * 
	 * Custom data render on Checkout Page - handler (for cloning)
	 * 
	 * @param integer $_quantity
	 * @param object $_cart_item
	 * @param string $_cart_item_key
	 * @return string
	 * 
	 */
	public function fields_cloning_checkout_handler($_quantity = null, $_cart_item = null, $_cart_item_key = null) {
		return wcff()->editor->render_fields_data($_quantity, $_cart_item, $_cart_item_key, true);
	}
	
	/**
	 * 
	 * Order meta handler
	 * 
	 * @param integer $_item_id
	 * @param string $_values
	 * @param string $_cart_item_key
	 * 
	 */
	public function fields_order_meta_handler($_item_id, $_values, $_cart_item_key) {
		wcff()->order->insert($_item_id, $_values, $_cart_item_key);
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
	 * 
	 * Cloning fields split order item
	 * 
	 * @param string $cart_item_key
	 * @param integer $product_id
	 * @param integer $quantity
	 * @param integer $variation_id
	 * @param object $variation
	 * @param object $cart_item_data
	 * 
	 */
	public function split_cart_item_for_cloning($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
		if ($this->is_native_add_to_cart) {
			$this->is_native_add_to_cart = false;
			wcff()->persister->split_cart_line_item($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data);
		}
	}

	public function expose_product_fields_for_rest_api($_response, $object = array(), $request = array()) {

		$data = $_response->get_data();

		if ($data && isset($data["id"])) {
			/* Fetch product fields */
			$product_fields = wcff()->dao->load_fields_groups_for_product($data["id"], 'wccpf', "any", "any");
			/* Fetch admin fields */
			$admin_fields = wcff()->dao->load_fields_groups_for_product($data["id"], 'wccaf', 'any', "any");
			/* Add the custom fields to the product */
			$data["product_fields"]["wcff"] = array_merge($product_fields, $admin_fields);	
			
			if ($data["type"] == "variable" && $data["variations"]) {

				if (count(array_filter(array_keys($data["variations"]), 'is_string')) > 0) {
					for ($i = 0; $i < count($data["variations"]); $i++) {

						/* Fetch fields for variation */
						if ($data["variations"][$i] && isset($data["variations"][$i]["id"])) {
							$vid = $data["variations"][$i]["id"];
							$wccaf_posts = wcff()->dao->load_fields_groups_for_product($vid, 'wccaf', "variable", "any", true);
							$wccpf_posts = wcff()->dao->load_fields_groups_for_product($vid, 'wccpf', "variable", "any", false);
							$wccvf_posts = wcff()->dao->load_fields_groups_for_product($vid, 'wccvf', "any", "any", false);
	
							$wccpf_posts = array_merge($wccaf_posts, $wccpf_posts);
							$wccpf_posts = array_merge($wccpf_posts, $wccvf_posts);
	
							$data["variations"][$i]["product_fields"]["wcff"] = $wccpf_posts;
						}					
	
					}
				}				
			}	
		}	

		$_response->set_data($data);
	
		return $_response;

	}

	public function expose_variation_fields_for_rest_api($_response, $object = array(), $request = array()) {

		$data = $_response->get_data();

		if ($data && isset($data["id"])) {

			$wccaf_posts = wcff()->dao->load_fields_groups_for_product($data["id"], 'wccaf', "variable", "any", true);
			$wccpf_posts = wcff()->dao->load_fields_groups_for_product($data["id"], 'wccpf', "variable", "any", false);
			$wccvf_posts = wcff()->dao->load_fields_groups_for_product($data["id"], 'wccvf', "any", "any", false);

			$wccpf_posts = array_merge($wccaf_posts, $wccpf_posts);
			$wccpf_posts = array_merge($wccpf_posts, $wccvf_posts);

			$data["product_fields"]["wcff"] = $wccpf_posts;

		}

		$_response->set_data($data);
		return $_response;

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
		if (!isset( $allowedposttags)) {
			return false;
		}
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

new wcff_product_fields();

?>