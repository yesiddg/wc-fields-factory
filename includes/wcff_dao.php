<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 *
 * Data access layer for WC Fields Factory
 *
 * @author Saravana Kumar K
 * @copyright Sarkware Research & Development (OPC) Pvt Ltd
 *
 */
class wcff_dao {
	
	/*
	 *  
	 * Namespace for WCFF related post meta
	 * "wccpf_" for Custom product page Fields ( Front end product page )
	 * "wccaf_" for Custom admin page fields ( for Admin Products )
	 * "wccvf_" for Custom admin page fields ( for Variation Fields )
	 * "wcccf_" for Custom admin page fields ( for Checkout Fields )
	 * 
	 **/
	private $wcff_key_prefix = "wccpf_";
	
	/* Holds all the supported field's specific configuration meta */
	private $fields_meta = array();
	
	/* Holds all the configuration meta that are common to all fields ( both Product as well as Admin ) */
	private $common_meta = array();
	
	/* Holds all the configuration meta that are common to Admin Fields */
	private $wccaf_common_meta = array();
	
	/**/
	public $has_variable_tab_fields = false;

	/* Holds the product list */
	private $product_cache = array();

	/* Holds the product variable list */
	private $product_variable_cache = array();

	/* Holds the product cat list */
	private $product_cat_cache = array();

	/* Holds the product tag list */
	private $product_tag_cache = array();

	/* */
	private $records_per_page = 25;
	
	/*
	 * 
	 * Cusdtom post meta Keys that are used by WCFF for various purposes
	 * 
	 * */
	public $special_keys = array(
		'fee_rules',
	    'layout_meta',
		'field_rules',
		'group_rules',
		'pricing_rules',
		'location_rules',
		'condition_rules',	
	    'show_group_title',
	    'use_custom_layout',		
		'product_tab_title',		
		'target_stock_status',		
		'product_tab_priority',		
		'is_this_group_clonable',
		'custom_product_data_tab',
	    'fields_label_alignement',
		'field_location_on_product',
		'field_location_on_archive',	   
		'custom_product_data_tab_title',
		'custom_product_data_tab_priority',
	    'is_this_group_for_authorized_only',
	    'wcff_group_preference_target_roles'
	);
	
	public function __construct() {
		/* Wordpress's Save Post action hook
		 * This is where we would save all the rules for the Fields Group ( post ) that is being saved */
		add_action( 'save_post', array($this, 'on_save_post' ), 1, 3 );
	}
	
	/**
	 *
	 * Set the current post type properties,<br/>
	 * based on this only all the subsequent fields related operation will happen<br/>
	 * this option could be either 'wccpf' for product fields or 'wccaf' for admin fields.
	 *
	 * @param string $_type
	 *
	 */
	public function set_current_post_type($_type = "wccpf") {
		$this->wcff_key_prefix = $_type . "_";
	}
	
	/**
	 *
	 * Return the Fields config meta for Factory View<br/>
	 * Contains entire (specific to each fields) config meta list for each field type.
	 *
	 * @return array
	 *
	 */
	public function get_fields_meta() {
		/* Make sure the meta is loaded */
		$this->load_core_meta();
		return $this->fields_meta;
	}
	
	/**
	 *
	 * Return the Fields config common meta for Factory View<br/>
	 * Contains entire (common for all fields) config meta list for each field type.
	 *
	 * @return array
	 *
	 */
	public function get_fields_common_meta() {
		/* Make sure the meta is loaded */
		$this->load_core_meta();
		return $this->common_meta;
	}
	
	/**
	 *
	 * Return the Admin Fields config common meta for Factory View<br/>
	 * Contains entire (common for all admin fields) config meta list for each field type.
	 *
	 * @return array
	 *
	 */
	public function get_admin_fields_comman_meta() {
		/* Make sure the meta is loaded */
		$this->load_core_meta();
		return $this->wccaf_common_meta;
	}
	
	/**
	 *
	 * Loads Fields configuration meta from the file system<br>
	 * Fields specific configuration meta from 'meta/wcff-meta.php'<br>
	 * Common configuration meta from 'meta/wcff-common-meta.php'<br>
	 * Common admin configuration meta from 'meta/wcff-common-wccaf-meta.php'
	 *
	 */
	private function load_core_meta() {
	    /* Load core fields config meta */
	    if (!is_array($this->fields_meta) || empty( $this->fields_meta)) {
	        $this->fields_meta = include('meta/wcff-meta.php');
	    }
	    /* Load common config meta for all fields */
	    if (!is_array($this->common_meta) || empty($this->common_meta)) {
	        $this->common_meta = include('meta/wcff-common-meta.php');
	    }
	    /* Load common config meta for admin fields */
	    if (!is_array($this->wccaf_common_meta) || empty($this->wccaf_common_meta)) {
	        $this->wccaf_common_meta = include('meta/wcff-common-wccaf-meta.php');
	    }
	}
	
	/**
	 *
	 * Called whenever user 'Update' or 'Save' post from wp-admin single post view<br/>
	 * This is where the various (Product, Cat, Location ... ) rules for the fields group will be stored in their respective post meta.
	 *
	 * @param integer $_pid
	 * @param WP_Post $_post
	 * @param boolean $_update
	 * @return void|boolean
	 *
	 */
	public function on_save_post($_pid, $_post, $_update) {
		/* Maje sure the post types are valid */
		if (!$_pid || ! $_post || ($_post->post_type != "wccpf" && $_post->post_type != "wccaf" && $_post->post_type != "wccvf" && $_post->post_type != "wcccf")) {
			return false;
		}
		
		$_pid = absint($_pid);
		
		/* Prepare the post type prefix for meta key */
		$this->wcff_key_prefix = $_post->post_type . "_";
		
		/* Conditional rules - determine which fields group belongs to which products */
		if (isset($_REQUEST["wcff_condition_rules"])) {
			delete_post_meta($_pid, $this->wcff_key_prefix .'condition_rules');
			add_post_meta($_pid, $this->wcff_key_prefix .'condition_rules', $_REQUEST["wcff_condition_rules"]);
		}
		
		/* Location rules - specific to Admin Fields */
		if (isset($_REQUEST["wcff_location_rules"])) {
			delete_post_meta($_pid, $this->wcff_key_prefix .'location_rules');
			add_post_meta($_pid, $this->wcff_key_prefix .'location_rules', $_REQUEST["wcff_location_rules"]);
		}
		
		/**/
		if (isset($_REQUEST["wcff_layout_meta"])) {
		    delete_post_meta($_pid, $this->wcff_key_prefix .'layout_meta');
		    add_post_meta($_pid, $this->wcff_key_prefix .'layout_meta', $_REQUEST["wcff_layout_meta"]);
		}

		if (isset($_REQUEST["wcff_target_stock_status"])) {
			delete_post_meta($_pid, $this->wcff_key_prefix .'target_stock_status');
		    add_post_meta($_pid, $this->wcff_key_prefix .'target_stock_status', $_REQUEST["wcff_target_stock_status"]);
		}

		if (isset($_REQUEST["wcff_custom_product_data_tab_title"])) {
			delete_post_meta($_pid, $this->wcff_key_prefix .'custom_product_data_tab_title');
		    add_post_meta($_pid, $this->wcff_key_prefix .'custom_product_data_tab_title', trim($_REQUEST["wcff_custom_product_data_tab_title"]));
		}

		if (isset($_REQUEST["wcff_custom_product_data_tab_priority"])) {
			delete_post_meta($_pid, $this->wcff_key_prefix .'custom_product_data_tab_priority');
		    add_post_meta($_pid, $this->wcff_key_prefix .'custom_product_data_tab_priority', $_REQUEST["wcff_custom_product_data_tab_priority"]);
		}		
		
		if (isset($_REQUEST["wcff_use_custom_layout"])) {		    
		    delete_post_meta($_pid, $this->wcff_key_prefix .'use_custom_layout');
		    add_post_meta($_pid, $this->wcff_key_prefix .'use_custom_layout', "yes");
		} else {
		    delete_post_meta($_pid, $this->wcff_key_prefix .'use_custom_layout');
		    add_post_meta($_pid, $this->wcff_key_prefix .'use_custom_layout', "no");
		}
		
		/* Field location for each field's group */
		if (isset($_REQUEST["field_location_on_product"])) {
			delete_post_meta($_pid, $this->wcff_key_prefix .'field_location_on_product');
			add_post_meta($_pid, $this->wcff_key_prefix .'field_location_on_product', $_REQUEST["field_location_on_product"]);
			delete_post_meta($_pid, $this->wcff_key_prefix .'product_tab_title');
			delete_post_meta($_pid, $this->wcff_key_prefix .'product_tab_priority');
			if ($_REQUEST["field_location_on_product"] == "woocommerce_single_product_tab" && isset($_REQUEST["product_tab_config_title"])) {
				add_post_meta($_pid, $this->wcff_key_prefix .'product_tab_title', $_REQUEST["product_tab_config_title"]);
				add_post_meta($_pid, $this->wcff_key_prefix .'product_tab_priority', $_REQUEST["product_tab_config_priority"]);
			}
		}
		
		/* Field location for archive page */
		if (isset($_REQUEST["field_location_on_archive"])) {
			delete_post_meta($_pid, $this->wcff_key_prefix .'field_location_on_archive');
			add_post_meta($_pid, $this->wcff_key_prefix .'field_location_on_archive', $_REQUEST["field_location_on_archive"]);
		}
		
		/* Group level cloning option */
		if (isset($_REQUEST["wcff_group_clonable_radio"])) {
			delete_post_meta($_pid, $this->wcff_key_prefix .'is_this_group_clonable');
			add_post_meta($_pid, $this->wcff_key_prefix .'is_this_group_clonable', $_REQUEST["wcff_group_clonable_radio"]);			
		}
		
		/* Group title display option */
		if (isset($_REQUEST["wcff_group_title_radio"])) {
		    delete_post_meta($_pid, $this->wcff_key_prefix .'show_group_title');
		    add_post_meta($_pid, $this->wcff_key_prefix .'show_group_title', $_REQUEST["wcff_group_title_radio"]);
		}
		
		/**/
		if (isset($_REQUEST["wcff_label_alignment_radio"])) {
		    delete_post_meta($_pid, $this->wcff_key_prefix .'fields_label_alignement');
		    add_post_meta($_pid, $this->wcff_key_prefix .'fields_label_alignement', $_REQUEST["wcff_label_alignment_radio"]);
		}
		
		/* Authorized users only option */
		if (isset($_REQUEST["wcff_group_authorized_only_radio"])) {		    
		    delete_post_meta($_pid, $this->wcff_key_prefix .'is_this_group_for_authorized_only');
		    add_post_meta($_pid, $this->wcff_key_prefix .'is_this_group_for_authorized_only', $_REQUEST["wcff_group_authorized_only_radio"]);		
		}
		
		/* Target roles option */
		if (isset($_REQUEST["wcff_group_preference_target_roles"])) {
		    delete_post_meta($_pid, $this->wcff_key_prefix .'wcff_group_preference_target_roles');
		    add_post_meta($_pid, $this->wcff_key_prefix .'wcff_group_preference_target_roles', json_encode($_REQUEST["wcff_group_preference_target_roles"]));	
		}
		
		/* Update the fields order */
		$this->update_fields_order($_pid);
		
		/* Update the dirty fields meta */
		if (isset($_REQUEST["wcff_dirty_fields_configuration"]) && !empty($_REQUEST["wcff_dirty_fields_configuration"])) {   
			$fields = json_decode(str_replace('\"','"', $_REQUEST["wcff_dirty_fields_configuration"]), true);
		    foreach ($fields as $fkey => $meta) {
				if (!isset($meta["updated"])) {
					/* Only update if no 'updated' property found */
					$this->update_field($_pid, $meta);
				}		        
		    }		    
		}	
		
		return true;
	}
	
	/**
	 *
	 * Update the fields sequence order properties for all fields on a given group (represented by $_pid)<br/>
	 * Called when Fields Group got saved or updated.
	 *
	 * @param integer $_pid
	 * @return boolean
	 *
	 */
	public function update_fields_order($_pid = 0) { 
		$fields = $this->load_fields($_pid, false);
		/* Update each fields order property */
		foreach ($fields as $key => $field) {
			if (isset($_REQUEST[$key."_order"])) {
				$field["order"] = $_REQUEST[$key."_order"];
				update_post_meta($_pid, $key, wp_slash(json_encode($field)));
			}
		}
		
		return true;
	}
	
	/**
	 *
	 * Load conditional rules for given Fields Group Post
	 *
	 * @param integer $_pid
	 * @return mixed
	 *
	 */
	public function load_target_products_rules($_pid = 0) {
	    $condition = array();
	    if ($_pid) {
	        $_pid = absint( $_pid );
	        /* Since we have renamed 'group_rules' meta as 'condition_rules' we need to make sure it is upto date
	         * and we remove the old 'group_rules' meta as well
	         **/
	        $rules = get_post_meta($_pid, $this->wcff_key_prefix .'group_rules', true);
	        if ($rules && $rules != "") {
	            delete_post_meta($_pid, $this->wcff_key_prefix .'group_rules');
	            update_post_meta($_pid, $this->wcff_key_prefix .'condition_rules', $rules);
	        }
	        $condition = get_post_meta($_pid, $this->wcff_key_prefix .'condition_rules', true);
	        
	        if ($condition != "") {
	            $condition = json_decode($condition, true);
	        }
	    }
		return $condition;
	}
	
	public function load_layout_meta($_pid = 0) {
	    $layout = array();
	    if ($_pid) {
	        $_pid = absint($_pid);
	        $layout = get_post_meta($_pid, $this->wcff_key_prefix .'layout_meta', true);
	        if ($layout != "") {
	            $layout = json_decode($layout, true);
	        }
	    }	    
	    return $layout;
	}

	public function load_target_stock_status($_pid) {		
		$_pid = absint($_pid);
	    $target_stock_status = get_post_meta($_pid, ($this->wcff_key_prefix ."target_stock_status"), true);
	    return ($target_stock_status != "") ? $target_stock_status : "any";
	}

	public function load_custom_product_data_tab_title($_pid) {
		$_pid = absint($_pid);
	    return get_post_meta($_pid, ($this->wcff_key_prefix ."custom_product_data_tab_title"), true);	    
	}
	
	public function load_custom_product_data_tab_priority($_pid) {
		$_pid = absint($_pid);
		$product_data_tab_priority = get_post_meta($_pid, ($this->wcff_key_prefix ."custom_product_data_tab_priority"), true);
		return ($product_data_tab_priority != "") ? $product_data_tab_priority : 999;
	}

	public function load_use_custom_layout($_pid) {
	    $_pid = absint($_pid);
	    $use_custom_layout = get_post_meta($_pid, ($this->wcff_key_prefix ."use_custom_layout"), true);
	    return ($use_custom_layout != "") ? $use_custom_layout : "no";
	}
	
	/**
	 *
	 * Load locational rules for given Admin Fields Group Post
	 *
	 * @param integer $_pid
	 * @return mixed
	 *
	 */
	public function load_location_rules($_pid = 0) {
	    $location = "";
	    if ($_pid) {
	        $_pid = absint($_pid);
	        $location = get_post_meta($_pid, $this->wcff_key_prefix .'location_rules', true);
	    }		
		return $location;
	}
	
	/**
	 *
	 * Load locational rules for entire admin fields posts
	 *
	 * @return mixed
	 *
	 */
	public function load_all_wccaf_location_rules() {
		$location_rules = array();
		$wcffs = get_posts(array(
			'post_type' => "wccaf",
			'posts_per_page' => -1,
			'order' => 'ASC')
		);
		if (count($wcffs) > 0) {
			foreach ($wcffs as $wcff) {
				$temp_rules = get_post_meta($wcff->ID, 'wccaf_location_rules', true);
				$location_rules[] = json_decode($temp_rules, true);				
			}
		}
		
		return $location_rules;
	}
	
	/**
	 *
	 * Used to load all woocommerce products<br/>
	 * Used in "Conditions" Widget
	 *
	 * @return 	ARRAY of products ( ids & titles )
	 *
	 */
	public function load_all_products() {
		
		$productsList = array();

		if (!empty($this->product_cache)) {
			$productsList = $this->product_cache;
		} else {
			
			$products = get_posts(array(
				'post_type' => 'product',
				'posts_per_page' => -1,
				'order' => 'ASC')
			);
			
			if (count($products) > 0) {
				foreach ($products as $product) {
					$productsList[] = array("id" => $product->ID, "title" => $product->post_title);
				}
			}

			$this->product_cache = $productsList;

		}	
		
		return $productsList;
	}
	
	/**
	 *
	 * Used to load all woocommerce variable  products<br/>
	 * Used in "Conditions" Widget
	 *
	 * @return 	ARRAY of products ( ids & titles )
	 *
	 */
	public function load_variable_products() {

		$productsList = array();

		if (!empty($this->product_variable_cache)) {
			$productsList = $this->product_variable_cache;
		} else {

			$products = get_posts(array(
				'post_type' => 'product',
				'posts_per_page' => -1,
				'order' => 'ASC')
			);
			
			if (count($products) > 0) {
				$wcG3 = version_compare(WC()->version, '2.2.0', '<');
				foreach ($products as $product) {
					$product_ob = $wcG3 ? get_product($product->ID) : wc_get_product($product->ID);				
					if ($product_ob->is_type( 'variable' )){
						$productsList[] = array("id" => $product->ID, "title" => $product->post_title);
					}
				}
			}

			$this->product_variable_cache = $productsList;

		}		
		
		return $productsList;
	}
	
	/**
	 *
	 * Used to load all woocommerce product category<br/>
	 * Used in "Conditions" Widget
	 *
	 * @return 	ARRAY of product categories ( ids & titles )
	 *
	 */
	public function load_product_categories() {
		$product_cats = array();

		if (!empty($this->product_cat_cache)) {
			$product_cats = $this->product_cat_cache;
		} else {
			$pcat_terms = get_terms('product_cat', 'orderby=count&hide_empty=0');		
			foreach ($pcat_terms as $pterm) {
				$product_cats[] = array("id" => $pterm->slug, "title" => $pterm->name);
			}

			$this->product_cat_cache = $product_cats;
		}		
		
		return $product_cats;
	}
	
	/**
	 *
	 * Used to load all woocommerce product tags<br/>
	 * Used in "Conditions" Widget
	 *
	 * @return 	ARRAY of product tags ( ids & titles )
	 *
	 */
	public function load_product_tags() {
		$product_tags = array();

		if (!empty($this->product_tag_cache)) {
			$product_tags = $this->product_tag_cache;
		} else {
			$ptag_terms = get_terms('product_tag', 'orderby=count&hide_empty=0');		
			foreach ($ptag_terms as $pterm) {
				$product_tags[] = array("id" => $pterm->slug, "title" => $pterm->name);
			}

			$this->product_tag_cache = $product_tags;
		}	
		
		return $product_tags;
	}
	
	/**
	 *
	 * Used to load all woocommerce product types<br/>
	 * Used in "Conditions" Widget
	 *
	 * @return 	ARRAY of product types ( slugs & titles )
	 *
	 */
	public function load_product_types() {
		$product_types = array();
		$all_types = array (
			'simple'   => __( 'Simple product', 'woocommerce' ),
			'grouped'  => __( 'Grouped product', 'woocommerce' ),
			'external' => __( 'External/Affiliate product', 'woocommerce' ),
			'variable' => __( 'Variable product', 'woocommerce' )
		);
		
		foreach ($all_types as $key => $value) {
			$product_types[] = array("id" => $key, "title" => $value);
		}
		
		return apply_filters( 'wcff_product_types', $product_types );
	}
	
	/**
	 *
	 * Used to load all woocommerce product types<br/>
	 * Used in "Conditions" Widget
	 *
	 * @return 	ARRAY of product types ( slugs & titles )
	 *
	 */
	public function load_product_variations($parent = 0) {
		$products_variation_list = array();
		$variations = array();
		$arg = array (
			'post_type' => 'product_variation',
			'posts_per_page' => -1,
			'order' => 'ASC'
		);
		if ($parent != 0) {
			$arg['post_parent']  = $parent;
		}
		$variations = get_posts($arg);
		foreach ($variations as $product) {
			$variation = new WC_Product_Variation($product->ID);
            $variationName = implode(" | ", $variation->get_variation_attributes());
			$products_variation_list[] = array("id" => $product->ID, "title" => $variationName);
		}
		return $products_variation_list;
	}
	
	/**
	 *
	 * Used to load all woocommerce product tabs<br/>
	 * Used in "Location" Widget
	 *
	 * @return 	ARRAY of product tabs ( titles & tab slugs )
	 *
	 */
	public function load_product_tabs() {
		return apply_filters( 'wcff_product_tabs', array (
			"General Tab" => "woocommerce_product_options_general_product_data",
			"Inventory Tab" => "woocommerce_product_options_inventory_product_data",
			"Shipping Tab" => "woocommerce_product_options_shipping",
			"Attributes Tab" => "woocommerce_product_options_attributes",
			"Related Tab" => "woocommerce_product_options_related",
			"Advanced Tab" => "woocommerce_product_options_advanced",
			"Variable Tab" => "woocommerce_product_after_variable_attributes",
			"Create a Custom Tab" => "wccaf_custom_product_data_tab"
		));
	}
	
	/**
	 *
	 * Used to load all wp context used for meta box<br/>
	 * Used for laying Admin Fields
	 *
	 * @return 	ARRAY of meta contexts ( slugs & titles )
	 *
	 */
	public function load_metabox_contexts() {
		return apply_filters( 'wcff_metabox_contexts', array (
			"normal" => __( "Normal", "wc-fields-factory" ),
			"advanced" => __( "Advanced", "wc-fields-factory" ),
			"side" => __( "Side", "wc-fields-factory" )
		));
	}
	
	/**
	 *
	 * Used to load all wp priorities used for meta box<br/>
	 * Used for laying Admin Fields
	 *
	 * @return 	ARRAY of meta priorities ( slugs & titles )
	 *
	 */
	public function load_metabox_priorities() {		
		return apply_filters( 'wcff_metabox_priorities', array (
			"low" => __( "Low", "wc-fields-factory" ),
			"high" => __( "High", "wc-fields-factory" ),
			"core" => __( "Core", "wc-fields-factory" ),
			"default" => __( "Default", "wc-fields-factory" )
		));
	}
	
	/**
	 *
	 * Used to load all woocommerce form fields validation types, to built Checkout Fields
	 *
	 * @return ARRAY of validation types
	 *
	 */
	public function load_wcccf_validation_types() {
		return apply_filters( 'wcccf_validation_types', array (
			"required" => __( "Required", "wc-fields-factory" ),
			"phone" => __( "Phone", "wc-fields-factory" ),
			"email" => __( "Email", "wc-fields-factory" ),
			"postcode" => __( "Post Code", "wc-fields-factory" )
		));
	}
	
	public function load_target_contexts() {
	    return apply_filters( "wcff_target_context", array (
	        array("id" => "product", "title" => __("Product", "wc-fields-factory")),
	        array("id" => "product_cat", "title" => __("Product Category", "wc-fields-factory")),
	        array("id" => "product_tag", "title" => __("Product Tag", "wc-fields-factory")),
	        array("id" => "product_type", "title" => __("Product Type", "wc-fields-factory")),
	        array("id" => "product_variation", "title" => __("Product Variation", "wc-fields-factory"))
	    ));
	}
	
	public function load_target_logics() {
	    return apply_filters( "wcff_target_logic", array (
	        array("id"=>"==", "title"=>__("is equal to", "wc-fields-factory")),
	        array("id"=>"!=", "title"=>__("is not equal to", "wc-fields-factory"))
	    ));
	}
	
	public function search_posts($_payload = array()) {
	    
	    global $wpdb;
			
		$map_all = array();
		$page = isset($_payload["page"]) ? absint($_payload["page"]) : 0;
		$search = isset($_payload["search"]) ? $_payload["search"] : "";		
		$post_type = isset($_payload["post_type"]) ? $_payload["post_type"] : "";		
		$qry = $wpdb->prepare("SELECT ID, post_title FROM $wpdb->posts WHERE post_type='%s' AND post_status='publish' AND post_title LIKE '%s'", $post_type, '%'. $wpdb->esc_like($search) .'%');	

		if ($search == "") {
			$map_all = array("id" => -1, "title" => "All ". $post_type);
		}
		
		return $this->prepare_search_response($page, $qry, $map_all);

	}

	/**
	 * 
	 * Does search for simple products
	 * 
	 */
	public function search_products($_payload = array()) {
		
		global $wpdb;
		
		$map_all = array();
		$page = isset($_payload["page"]) ? absint($_payload["page"]) : 0;			
		$search = isset($_payload["search"]) ? $_payload["search"] : "";
		$qry = $wpdb->prepare("SELECT ID, post_title FROM $wpdb->posts WHERE post_type='product' AND post_status='publish' AND post_title LIKE '%s'", '%'. $wpdb->esc_like($search) .'%');	

		if ($search == "") {
			$map_all = array("id" => -1, "title" => "All Products");
		}

		return $this->prepare_search_response($page, $qry, $map_all);		

	}

	/**
	 * 
	 * Does search for variation products (parents)
	 * 
	 */
	public function search_variation_products($_payload = array()) {

		global $wpdb;
		
		$map_all = array();
		$page = isset($_payload["page"]) ? absint($_payload["page"]) : 0;					
		$search = isset($_payload["search"]) ? $_payload["search"] : "";
		$qry = $wpdb->prepare("SELECT ID, post_title FROM $wpdb->posts mp WHERE post_type = 'product' AND post_status='publish' AND post_title LIKE '%s' AND EXISTS (SELECT ID FROM $wpdb->posts WHERE post_type = 'product_variation' AND post_parent = mp.ID)", '%'. $wpdb->esc_like($search) .'%');

		if ($search == "") {
			$map_all = array("id" => 0, "title" => "All Products");
		}

		return $this->prepare_search_response($page, $qry, $map_all);

	}

	/**
	 * 
	 * Does search for variations of particular product
	 * 
	 */
	public function search_variations($_payload = array()) {
		
		global $wpdb;
		
		$map_all = array();
		$page = isset($_payload["page"]) ? absint($_payload["page"]) : 0;					
		$search = isset($_payload["search"]) ? $_payload["search"] : "";
		$parent = isset($_payload["parent"]) ? $_payload["parent"] : 0;
		$qry = $wpdb->prepare("SELECT ID, post_name FROM $wpdb->posts mp WHERE post_type = 'product_variation' AND post_parent=%s AND post_status='publish' AND post_title LIKE '%s'", $parent, '%'. $wpdb->esc_like($search) .'%');
		
		if ($search == "") {
			$map_all = array("id" => -1, "title" => "All Variations");
		}

		return $this->prepare_search_response($page, $qry, $map_all);

	}

	/**
	 * 
	 * Does search for Product Categories & Tags
	 * 
	 */
	public function search_terms($_payload = array()) {

		global $wpdb;
		$res = array();		
		$terms = array();

		if (isset($_payload["taxonomy"]) && !empty($_payload["taxonomy"])) {			
			$page = isset($_payload["page"]) ? $_payload["page"] : 0;			
			$search = isset($_payload["search"]) ? $_payload["search"] : "";								
		}

		$total = 0;		
		$page = absint($page);		
		$offset = ($page * $this->records_per_page) - $this->records_per_page; 

		$qry = $wpdb->prepare("SELECT $wpdb->terms.term_id, $wpdb->terms.name FROM $wpdb->terms INNER JOIN $wpdb->term_taxonomy ON $wpdb->terms.term_id = $wpdb->term_taxonomy.term_id WHERE $wpdb->terms.name LIKE '%s' AND $wpdb->term_taxonomy.taxonomy='%s'", '%'. $wpdb->esc_like($search) .'%', $_payload["taxonomy"]);			
		if ($page > 0) {
			/* Needs pagintation */
			$tQry = "SELECT COUNT(1) FROM (${qry}) AS combined_table";
			$total = $wpdb->get_var($tQry);
			$terms = $wpdb->get_results($qry .' ORDER BY term_id LIMIT '. $offset .', '. $this->records_per_page, OBJECT);
		} else {
			/* No need to paging */
			$terms = $wpdb->get_results($qry);
		}

		if ($search == "") {
			if ($_payload["taxonomy"] == "product_cat") {
				$res[] = array("id" => -1, "title" => "All Categories");
			} 
			if ($_payload["taxonomy"] == "product_tag") {
				$res[] = array("id" => -1, "title" => "All Tags");
			}			
		}
		
		if (is_array($terms)) {
			foreach ($terms as $term) {
				$res[] = array(	"id" => $term->{"term_id"}, "title" => $term->{"name"});
			}
		}
		
		return $this->prepare_page_response($page, $total, $res);		

	}	

	public function insert_wccvf_map_varioation_level($_payload = array()) {
	    
	    $res = false;	    
	    if (!isset($_payload["rules"]) || !is_array($_payload["rules"]) || empty($_payload["rules"])) {	        
	        return false;
	    }

		foreach ($_payload["rules"] as $gpid => $new_rules) {
			$all_rules = $this->load_target_products_rules($gpid);
			if (!is_array($all_rules)) {
				$all_rules = array();
			}
			/* Append the new rule entry */	        
			foreach ($new_rules as $new_rule) {
				if (!in_array($new_rule, $all_rules)) {
					$all_rules[] =$new_rule;			
				}			 
			}
			/* Update the condition rules */
	        delete_post_meta($gpid, $this->wcff_key_prefix .'condition_rules');
	        $res = add_post_meta($gpid, $this->wcff_key_prefix .'condition_rules', json_encode($all_rules));
		}	    
	   
	    return $res ? true : false;
	    
	}
	
	public function remove_wccvf_map_variation_level($_payload = array()) {
	    
	    if (!isset($_payload["pid"]) && empty($_payload["pid"])) {
	        return false;
	    }
	    if (!isset($_payload["vid"]) || empty($_payload["vid"])) {
	        return false;
	    } 

	    /* Retrive the current condition  rules */
	    $all_rules = $this->load_target_products_rules($_payload["pid"]);

	    if (is_array($all_rules)) {
	        foreach ($all_rules as $aIndex => $rules) {
	            foreach ($rules as $rIndex => $rule) {	                
	                if ($rule["endpoint"] == $_payload["vid"]) {
	                    unset($all_rules[$aIndex][$rIndex]);   
	                }	         			
	            }
				/* Remove the group if it is empty */
				if (empty($all_rules[$aIndex])) {
					unset($all_rules[$aIndex]);
				}
	        }
	    }
		
	    /* Update the condition rules */
	    delete_post_meta($_payload["pid"], $this->wcff_key_prefix .'condition_rules');
	    return add_post_meta($_payload["pid"], $this->wcff_key_prefix .'condition_rules', json_encode($all_rules));
	    
	}
	
	/**
	 * 
	 * This routine does some reverse pulkling data extraction
	 * 
	 * @return array
	 */
	public function load_map_wccvf_variations() {
		
		$flag = false;
		$result = array();
		$vproducts = array();
		
		$all_rules = array();
		$wccvf_rules = array();		
		
		$wcG3 = version_compare(WC()->version, '2.2.0', '<');		
		$products = get_posts(array('post_type' => 'product', 'posts_per_page' => -1));				
		$wccvfs = get_posts(array('post_type' => "wccvf", 'posts_per_page' => -1));
		
		/**
		 * 
		 * Step 1.  
		 * Consolidating all rules, from all wccvf post 
		 * 
		 */		
		foreach ($wccvfs as $wccvf) {
		    	    
		    $wccvf_rules = get_post_meta($wccvf->ID, 'wccvf_condition_rules', true);
		    if ($wccvf_rules != null) {
		        $wccvf_rules = json_decode($wccvf_rules, true);
		        
		        /* Well this is a two dimentional array */
		        foreach ($wccvf_rules as $rules) {
		            if (is_array($rules)) {
		                foreach ($rules as $rule) {
		                    if (is_array($rule)) {
		                        
		                        $rule["group_id"] = $wccvf->ID;
		                        $rule["group_title"] = $wccvf->post_title;
		                        $all_rules[] = $rule;
		                        
		                    }
		                }
		            }
		        }
		    }		    		    
		    
		}
	
		/**
		 * 
		 * Step 2.
		 * Fetching all products with variations.
		 * 
		 */
		foreach ($products as $post) {
		    
		    $product = $wcG3 ? get_product($post->ID) : wc_get_product($post->ID);
		    if ($product->is_type( 'variable' )) {
		        $wp_query = new WP_Query(array(
		            'post_type'      	=> 'product_variation',
		            'post_status'    	=> 'publish',
		            'post_parent'		=> $post->ID,
		            'posts_per_page' 	=> -1,
		            'fields'         	=> array('ID', 'post_title')
		        ));
		        $vproducts[] = array("id" => $post->ID, "title" => $post->post_title, "variations" => $wp_query->posts);
		    }
		    
		}
	    
		foreach ($vproducts as $product) {
		    if (is_array($product["variations"])) {
		        $variations = array();
		        
		        foreach ($product["variations"] as $variation) {
		            $flag = false;
		            $fgroups = array();
		            
		            foreach ($all_rules as $rule) {
		                if (absint($rule["endpoint"]) == absint($variation->ID)) {
		                    $flag = true;
		                    $fgroups[] = array("gid" => $rule["group_id"], "gtitle" => $rule["group_title"]);
		                }
		                if ($flag) {
		                    $variations[$variation->ID] = array("variation_title" => $variation->post_excerpt, "groups" => $fgroups);
		                }
		            }
		            if (!empty($variations)) {
		                $result[$product["id"]] = array("product_title" => $product["title"], "variations" => $variations);
		            }
		        }
		        
		    }
		}
		
		return $result;
		
	}
		
	public function check_product_for_variation_mappings($_pid, $_type) {
	    
	    if ($_pid) {
	       
	        $_pid = absint($_pid);        
	        $this->wcff_key_prefix = $_type . "_";
	        
	        $wp_query = new WP_Query(array(
	            'post_type'      	=> 'product_variation',
	            'post_status'    	=> 'publish',
	            'post_parent'		=> $_pid,
	            'posts_per_page' 	=> -1,
	            'fields'         	=> array('ID', 'post_title')
	        ));
	        
	        $wccvfs = get_posts(array('post_type' => $_type, 'posts_per_page' => -1));	        
	        foreach ($wccvfs as $wccvf) {
	            
	            $wccvf_rules = get_post_meta($wccvf->ID, $_type .'_condition_rules', true);
	            if ($wccvf_rules != null) {
	                $wccvf_rules = json_decode($wccvf_rules, true);
	                
	                /* Well this is a two dimentional array */
	                foreach ($wccvf_rules as $rules) {
	                    if (is_array($rules)) {
	                        foreach ($rules as $rule) {
	                            if (is_array($rule)) {	                                
	                                foreach ($wp_query->posts as $variation) {
	                                    if (isset($rule["endpoint"]) && absint($rule["endpoint"]) == absint($variation->ID)) {
	                                        $this->has_variable_tab_fields = true;
	                                        return true;
	                                    }	
	                                }	                                
	                            }
	                        }
	                    }
	                }
	            }
	        }	        
	    }
	    
	    return false;
	    
	}
	
	/**
	 *
	 * This function is used to load all wcff fields (actualy post meta) for a single WCFF post<br/>
	 * Mostly used in editing wccpf fields in admin screen
	 *
	 * @param 	integer	$pid	- WCFF Post Id
	 * @param  boolean	$sort   - Whether returning fields should be sorted
	 * @return 	array
	 *
	 */
	public function load_fields($_pid = 0, $_sort = true) {
	    
		$fields = array();		
		if ($_pid) {
		    $_pid = absint($_pid);		
		    $meta = get_post_meta($_pid);
		    
		    $excluded_keys = $this->prepare_special_keys();
		    foreach ($meta as $key => $val) {
		        /* Exclude special purpose custom meta */
		        if (!in_array($key, $excluded_keys) && (strpos($key, $this->wcff_key_prefix) === 0)) {
		            $fields[$key] = json_decode($val[0], true);
		        }
		    }
		    
		    if ($_sort) {
		        $this->usort_by_column($fields, "order");
		    }
		}
		
		return $fields;
		
	}
	
	/**
	 *
	 * Loads all fields of the given Fields Group Post
	 *
	 * @param number $_pid
	 * @param string $_mkey
	 * @return mixed
	 *
	 */
	public function load_field($_pid = 0, $_mkey = "") {
		$_pid = absint($_pid);
		$post = get_post($_pid);
		$field = get_post_meta($_pid, $_mkey, true);
		if ($field === "") {
		    $field = "{}";
		} 
		$field = json_decode($field, true);
		return apply_filters( $post->post_type .'_field', $field, $_pid, $_mkey );
	}
	
	/**
	 * 
	 * Create a Unique ID for the field and store with initial data
	 * 
	 * @param number $_pid
	 * @param string $_type
	 * @return string|boolean
	 */
	public function create_field($_pid, $_type, $_order) {
		$_pid = absint($_pid);

		$id = $this->generate_unique_id();
		$id = apply_filters("wcff_new_field_id", $id, $_pid, $_type);

		$meta = array (
			"id" => $id,
			"type" => $_type,
			"label" => "",
			"order" => $_order,
			"status" => true
		);		
		if (add_post_meta($_pid, ($this->wcff_key_prefix . $id), wp_slash(json_encode($meta)))) {
			return ($this->wcff_key_prefix . $id);
		}		
		return false;
	}
	
	public function update_field($_pid, $_payload) {
		$msg = "";
		$res = true;
		$_pid = absint($_pid);
		if (isset($_payload["key"])) {
		    delete_post_meta($_pid, $_payload["key"]);
			if (add_post_meta($_pid,  $_payload["key"], wp_slash(json_encode($_payload))) == false) {
				$res = false;
				$msg = __( "Failed to update the custom field", "wc-fields-factory" );
			}
		}	
		if (isset($_payload["to_be_removed"])) {
			delete_post_meta($_pid, $_payload["to_be_removed"]);
		}
		return array("res" => $res, "msg" => $msg);
	}
	
	public function toggle_field($_pid, $_key, $_status) {
		$msg = "";
		$res = true;
		$meta_val = get_post_meta($_pid, $_key, true);
		if ($meta_val && !empty($meta_val)) {
			$field = json_decode(wp_unslash($meta_val), true);
			if (isset($field["is_enable"])) {
				$field["is_enable"] = $_status;
				delete_post_meta($_pid, $_key);
				if (add_post_meta($_pid, $_key, wp_slash(json_encode($field))) == false) {
					$res = false;
					$msg = __( "Failed to update.!", "wc-fields-factory" );
				}
			} else {
				$res = false;
				$msg = __( "Failed to update, Key is missing.!", "wc-fields-factory" );
			}
		} else {
			$res = false;
			$msg = __( "Failed to update, Meta is empty.!", "wc-fields-factory" );
		}
		return array("res" => $res, "msg" => $msg);
	}
	
	public function clone_group($_pid = 0, $_post_type = "") {
		global $wpdb;		
		$_pid = ($_pid == 0) ? (isset($_REQUEST["post"]) ? $_REQUEST["post"] : 0) : 0;
		$_post_type = ($_post_type == "") ? (isset($_REQUEST["post_type"]) ? $_REQUEST["post_type"] : "") : "";	
				
		if (isset($_pid) && $_pid > 0) {
		    
		    // Get the post as an array
		    $clone = get_post($_pid, 'ARRAY_A');		    
		    
		    unset( $clone['ID'] );
		    unset( $clone['guid'] );
		    unset( $clone['comment_count'] );
		    
		    $clone['post_title'] = "Copy - ". wp_kses_post($clone['post_title']);
		    $clone['post_name'] = sanitize_title($clone['post_name']);
		    $clone['post_status'] = $clone["post_status"];		    
		    $clone['post_type'] = $clone["post_type"];
		    $clone['post_author'] = wp_get_current_user()->ID;
		    
		    $clone['post_date'] = date('Y-m-d H:i:s', current_time('timestamp',0));
		    $clone['post_date_gmt'] = date('Y-m-d H:i:s', current_time('timestamp',1));
		    $clone['post_modified'] = date('Y-m-d H:i:s', current_time('timestamp',0));
		    $clone['post_modified_gmt'] = date('Y-m-d H:i:s', current_time('timestamp',1));		    
		    $clone['post_content'] = str_replace( array( '\r\n', '\r', '\n' ), '<br />', wp_kses_post( $clone['post_content'] ) );
		    
		    $clone_id = wp_insert_post($clone, true);
		    if (!is_wp_error($clone_id)) {
		        $custom_fields = get_post_custom($_pid);
		        foreach ($custom_fields as $key => $meta) {		            
		            if (strpos($key, $_post_type."_") === 0) {
		                $field = json_decode($meta[0], true);
		                if (isset($field["key"])) {
		                    $key = $_post_type ."_". $this->generate_unique_id();
		                    $field["key"] = $key;
		                    if (isset($field["field_rules"])) {
		                        unset($field["field_rules"]);
		                    }
		                    if (isset($field["pricing_rules"])) {
		                        unset($field["pricing_rules"]);
		                    }
		                    
		                    $data = array(
		                        'post_id' 		=> intval($clone_id),
		                        'meta_key' 		=> sanitize_text_field($key),
		                        'meta_value' 	=> json_encode($field)
		                    );
		                    
		                    $wpdb->insert( $wpdb->prefix.'postmeta', $data, array('%d','%s','%s'));
		                    
		                    continue;
		                }
		            }
		            
		            if (is_array($meta) && count($meta) > 0) {
		                $data = array(
		                    'post_id' 		=> intval($clone_id),
		                    'meta_key' 		=> sanitize_text_field($key),
		                    'meta_value' 	=> $meta[0],
		                );
		                $wpdb->insert( $wpdb->prefix.'postmeta', $data, array('%d','%s','%s'));
		            }
		        }		         
		    }
		}
		if ($_post_type != "wccvf") {
		    wp_redirect( admin_url('edit.php?post_type='. rawurlencode( $_post_type)));
		} else {
			wp_redirect( admin_url('edit.php?post_type=wccpf&page=variation_fields_config'));
		}		
		exit;
	}
	
	public function clone_field($_pid, $_fkey) {
		$_pid = absint($_pid);
		$id = $this->generate_unique_id();
		$id = apply_filters("wcff_new_field_id", $id, $_pid, null);
		$cloned = $this->load_field($_pid, $_fkey);		
		if (is_array($cloned)) {
			$cloned["id"] = $id;
			$cloned["label"] = "Copy - ". $cloned["label"];
			if (add_post_meta($_pid, ($this->wcff_key_prefix . $id), wp_slash(json_encode($cloned)))) {
				return ($this->wcff_key_prefix . $id);
			}
		}
		return false;
	}
	
	/**
	 *
	 * Remove the given field from Fields Group Post
	 *
	 * @param number $_pid
	 * @param string $_mkey
	 * @return boolean
	 *
	 */
	public function remove_field($_pid, $_mkey) {
	    if ($_pid) {
	        $_pid = absint($_pid);
	        $post = get_post($_pid);
	        do_action($post->post_type .'_before_remove_field', $_mkey, $_pid);
	        /* Update the layout meta */
	        $layout = $this->load_layout_meta($_pid);
	        if (!empty($layout)) {
	            /* Row update */
	            foreach ($layout["rows"] as $rIndex => $row) {
	                foreach($row as $fIndex => $fkey) {
	                    if ($_mkey == $fkey) {
	                        if (count($row) == 1) {
	                            /* Could be only one field */
	                            unset($layout["rows"][$rIndex]);
	                        } else {
	                            $current_field_width = floatval($layout["columns"][$_mkey]["width"]);
	                            /* Could be first field */
	                            if ($fIndex == 0) {
	                                $next_field_width = floatval($layout["columns"][$layout["rows"][$rIndex][$fIndex+1]]["width"]);
	                                $layout["columns"][$layout["rows"][$rIndex][$fIndex+1]]["width"] = ($current_field_width + $next_field_width);
	                            } else {
	                                /* Could be last or middle */
	                                $prev_field_width = floatval($layout["columns"][$layout["rows"][$rIndex][$fIndex-1]]["width"]);
	                                $layout["columns"][$layout["rows"][$rIndex][$fIndex-1]]["width"] = ($current_field_width + $prev_field_width);
	                            }
	                            unset($layout["rows"][$rIndex][$fIndex]);
	                        }
	                    }
	                }
	            }
	            /* Column update */
	            unset($layout["columns"][$_mkey]);
	            
	            delete_post_meta($_pid, $this->wcff_key_prefix .'layout_meta');
	            add_post_meta($_pid, $this->wcff_key_prefix .'layout_meta', json_encode($layout));
	        }
	        
	        return delete_post_meta($_pid, $_mkey);
	    }
	    
		return false;
	}
	
	/**
	 * 
	 * @param integer $_pid
	 * @param string $_type
	 * @param string $_template
	 * @param string $_fields_location
	 * @param boolean $_is_variation_template - Used to indicate that the admin template is for variation section
	 * @param string $_custom_tab_title - Added for admin fields to be put on custom product data tabs
	 * 
	 */
	public function load_fields_groups_for_product($_pid, $_type = "wccpf", $_template = "single-product", $_fields_location = "", $_is_variation_template = false, $_custom_tab_title = "") {
	    /* Holds custom post meta */
	    $meta = array();
	    /* Holds the fields list */
	    $fields = array();
	    /**/
	    $groups = array();
	    /* Holds the final list of fields */
	    $all_fields = array();	    
	    /* Location rules flag */
	    $location_passed = false;	
	    /* Condition rules flag */
	    $target_product_passed = false;
	    /**/
	    $admin_target_location = array();
	        	    
	    $_pid = absint($_pid);

	    $this->wcff_key_prefix = $_type . "_";	    
	    $wcff_options = wcff()->option->get_options();
	    
	    /* Reset variable tab field flaq */
	    $this->has_variable_tab_fields = false;
	    	    
	    /* Special keys that is not part of fields meta */
	    $excluded_keys = $this->prepare_special_keys();
	    
	    /* Fields on archive template Flaq */
	    $fields_on_archive = isset($wcff_options["fields_on_archive"]) ? $wcff_options["fields_on_archive"] : "no";
	    
	    /* Fields location on single product page */
	    $global_location_single = isset($wcff_options["field_location"]) ? $wcff_options["field_location"] : "woocommerce_before_add_to_cart_button";
		/* Check user wants custom location */
		if ($global_location_single == "woocommerce_product_custom_location") {
			$global_location_single = isset($wcff_options["custom_product_fields_location"]) ? $wcff_options["custom_product_fields_location"] : "";
		}
	  
	    /* Fields location for archive product */
	    $global_location_archive = isset($wcff_options["field_archive_location"]) ? $wcff_options["field_archive_location"] : "woocommerce_before_shop_loop_item";
		/* Check user wants custom location */
	    if ($global_location_archive == "woocommerce_archive_custom_location") {
			$global_location_archive = isset($wcff_options["custom_archive_fields_location"]) ? $wcff_options["custom_archive_fields_location"] : "";
		}

	    /* Check whether the request for Archive template and fields on archive is enabled */
	    if ($_template == "archive-product" && $fields_on_archive == "no") {
	        /* No need to go further */
	        return apply_filters( 'wcff_fields_for_product', array(), $_pid, $_type, $_template, $_fields_location );
	    }
	    
	    /* Fetch the group posts */
	    $group_posts = get_posts(
	        array(
	            "post_type" => $_type, 
	            "posts_per_page" => -1,	
	            "order" => "ASC",
				"post_status" => array('publish')	            
	        )
	    );	    
	    	    
	    if (count($group_posts) > 0) {
	        /* Loop through all group posts */
	        foreach ($group_posts as $g_post) {	            
				
	            $all_fields = array();	       
				/* Reset field location flaq */
				$location_passed = false;
				/* Reset target flaq */
				$target_product_passed = false;
	            /* Get all custom meta */
	            $fields = get_post_meta($g_post->ID);
	            
	            /* Check whether this group is for Authorized users only */
	            $authorized_only = get_post_meta($g_post->ID, $this->wcff_key_prefix."is_this_group_for_authorized_only", true);
	            $authorized_only = (!$authorized_only || $authorized_only == "") ? "no" : $authorized_only;
	            if ($authorized_only == "yes" && !is_user_logged_in()) {
	                continue;
	            }
	            
	            /* Retrive the group level role assignment */
	            $targeted_roles = get_post_meta($g_post->ID, $this->wcff_key_prefix ."wcff_group_preference_target_roles", true);
	            
	            if ($targeted_roles) {
	                $targeted_roles = json_decode($targeted_roles, true);
	            } else {
	                $targeted_roles = array();
	            }            
	            
	            /* If it is for authorized only fields, then check for the roles */
	            if ($authorized_only == "yes" && !$this->check_for_roles($targeted_roles)) {
	                continue;
	            }
	            	 
	            if ($_template != "any" && $_template != "variable") {
	                /* Check for single-product location rule */
	                if ($_template == "single-product") {
	                    /* Group level Location */
	                    $field_group_location_single = get_post_meta($g_post->ID, $this->wcff_key_prefix."field_location_on_product", true);
	                    $field_group_location_single = empty($field_group_location_single) ? "use_global_setting" : $field_group_location_single;
	                    
	                    if ($field_group_location_single == "use_global_setting") {
	                        if ($_fields_location == "any" || $global_location_single == $_fields_location) {
	                            $location_passed = true;
	                        }	                        
	                    } else if ($_fields_location == "any" || $field_group_location_single == $_fields_location) {
	                        $location_passed = true;
	                    } else {
	                        /* Ignore */
	                        $location_passed = false;
	                    }	                    
	                } else if ($_template == "archive-product") {
	                   /* Check for archive-product location rule */	                
	                    $field_group_location_archive = get_post_meta($g_post->ID, $this->wcff_key_prefix."field_location_on_archive", true);
	                    $field_group_location_archive = empty( $field_group_location_archive ) ? "none" : $field_group_location_archive;
	                    
	                    if ($field_group_location_archive == "use_global_setting") {
	                        if ($_fields_location == "any" || $global_location_archive == $_fields_location) {
	                            $location_passed = true;
	                        }
	                    } else if ($_fields_location == "any" || $global_location_archive == $_fields_location) {
	                        $location_passed = true;
	                    } else {
	                        /* Ignore */
	                        $location_passed = false;
	                    }
	                } else if ($_template == "admin") {
	                    $location_passed = true;
	                    $field_group_locations_admin = get_post_meta($g_post->ID, $this->wcff_key_prefix."location_rules", true);                    
	                    $field_group_locations_admin = json_decode( $field_group_locations_admin, true );
	                    if ($_fields_location != "any") {
	                        $location_passed = $this->check_for_location($g_post->ID, $field_group_locations_admin, $_fields_location, $_custom_tab_title);
	                    }	                            
	                }
	            } else {
	                $location_passed = true;
	            }
	            
	            if ($_type == "wccaf") {
	                $admin_target_location = get_post_meta($g_post->ID, $_type .'_location_rules', true);	                
	                $admin_target_location = json_decode($admin_target_location, true);
	            }	            
	            
	            /* Check for 'variation_tab' location, needs to exclude admin fields which has location of variable tab */
	            if ($_type == "wccaf" && ($_template == "single-product" || $_template == "archive-product")) {                
	                if ($admin_target_location["endpoint"] == "woocommerce_product_after_variable_attributes") {	                    
	                    $location_passed = false;
	                    $this->has_variable_tab_fields = true;
	                }                
	            }
	            
	            /* Needs to includes admin fields which has variable tab as target location */
	            if ($_type == "wccaf" && $_is_variation_template) {
	                $location_passed = false;
	                if ($admin_target_location["endpoint"] == "woocommerce_product_after_variable_attributes") {
	                    $location_passed = true;	                  
	                } 
	            }
	            
	            /* Finally check for the target products */
	            $product_map_rules = $this->load_target_products_rules($g_post->ID);
	            
	            if (is_array($product_map_rules)) {					
					if ($_pid > 0) {
						$target_product_passed = $this->check_for_product($_pid, $product_map_rules, $_type);
					}	                
	            } else {
					$target_product_passed = false;
				}     
				
				/* Check for product stock status - from V 4.1.5
				   Now its available for wccpf only */
				if ($_type == "wccpf") {
					$pdct = wc_get_product($_pid);
					$target_status = $this->load_target_stock_status($g_post->ID);
					if ($pdct && $target_status != "any") {
						$stock_status = "any";
						if (method_exists($pdct, 'get_stock_status')) {
							$stock_status = $pdct->get_stock_status();
						} else {
							$stock_status = $pdct->stock_status;
						}
						if ($target_status != $stock_status) {
							$target_product_passed = false;
						}
					}
				}				

	            /* By passing flaq for variation fields (especially from admin fields group - variable tab) */
	            if (($target_product_passed || $_is_variation_template) && $location_passed) {
	                /* Well prepare the field list */
	                foreach ($fields as $key => $meta) {
	                    /* Exclude special purpose custom meta */
	                    if (!in_array($key, $excluded_keys) && (strpos($key, $this->wcff_key_prefix) === 0)) {
	                        
	                        $field = json_decode($meta[0], true);
							if (!isset($field["key"])) {
								continue;
							}
	                        
	                        /* If it is admin field and the template is for front end, then check for the "show_on_product_page" flaq */
	                        if (($_type == "wccaf" && isset($field["show_on_product_page"])) && ($_template == "single-product" || $_template == "archive-product" || $_template == "variable")) {
	                            if ($field["show_on_product_page"] == "no" && $field["type"] != "url") {
	                                continue;
	                            }	                            
	                        }
	                        
	                        /* Check for authorized user only flaq */
	                        $authorized_only = isset($field["login_user_field"]) ? $field["login_user_field"] : "no";	                        
	                        if ($authorized_only == "yes") {
	                            $targeted_roles = isset($field["show_for_roles"]) ? $field["show_for_roles"] : array();	                            
	                            /* If it is for authorized only fields, then check for the roles */
	                            if (!$this->check_for_roles($targeted_roles)) {
	                                continue;
	                            }
	                        }                    
	                        
	                        if(isset($field["is_enable"])) {
	                            if ($field["is_enable"]) {
	                                $all_fields[] = $field;
	                            }	                            
	                        } else {
	                            $all_fields[] = $field;
	                        }
	                    }
	                }
	                
	                /* Sort the fields */
	                $this->usort_by_column($all_fields, "order");
	                
	                $groups[] = array(
	                    "id" => $g_post->ID,
	                    "type" => $_type,
	                    "fields" => $all_fields,
	                    "title" =>  get_the_title($g_post->ID),
	                    "layout" => $this->load_layout_meta($g_post->ID),
	                    "use_custom_layout" => $this->load_use_custom_layout($g_post->ID),
	                    "show_title" => get_post_meta($g_post->ID, ($this->wcff_key_prefix ."show_group_title"), true),
	                    "is_clonable" => get_post_meta($g_post->ID, ($this->wcff_key_prefix ."is_this_group_clonable"), true),	                    
	                    "label_alignment" => get_post_meta($g_post->ID, ($this->wcff_key_prefix ."fields_label_alignement"), true),
	                    "template_single_location" => get_post_meta($g_post->ID, ($this->wcff_key_prefix ."field_location_on_product"), true),
                        "template_archive_location" => get_post_meta($g_post->ID, ($this->wcff_key_prefix ."field_location_on_archive"), true)
	                );
	            }
	            
	        }
	    }
		
	    return apply_filters('wcff_fields_for_product', $groups, $_pid, $_type, $_template, $_fields_location);			    
	}
	
	
	/**
	 *
	 * WCFF Product Mapping Rules Engine, This is function used to determine whether or not to include<br/>
	 * a particular wccpf group fields to a particular Product
	 *
	 * @param 	integer		$_pid	- Product Id
	 * @param 	array 		$_groups
	 * @return 	boolean
	 *
	 */
	public function check_for_product($_pid, $_groups, $_type="wccpf") {
		
		$matches = array();
		$final_matches = array();

		$post_type = "";
		$p = get_post($_pid);
		if ($p && $p->post_type) {
			$post_type = $p->post_type;
		}

		foreach ($_groups as $rules) {

			$ands = array();
			foreach ($rules as $rule) {
			    
				/* Special case scenario only for Product Variations */
			    if (wcff()->request && $rule["context" ] != "product_variation" && wcff()->request["context"] == "wcff_variation_fields") {
			        return false;
			    }

				if ($rule["context"] == "product" && $post_type == "product") {
					if ($rule["endpoint"] == -1) {
						$ands[] = ($rule["logic"] == "==");
					} else {
						if ($rule["logic"] == "==") {
							$ands[] = ($_pid == $rule["endpoint"]);
						} else {
							$ands[] = ($_pid != $rule["endpoint"]);
						}
					}
				} else if ($rule["context"] == "product_variation" && $post_type == "product_variation") {
					if ($rule["endpoint"] == -1) {
						/* enpoint -1 not applicable for wccvf */
						if ($_type != "wccvf") {
							if (get_post_type($_pid) == "product_variation") {
								$ands[] = ($rule["logic"] == "==");
							} else {
								$ands[] = false;
							}
						} else {
							$ands[] = false;
						}
					} else {
						if ($rule["logic"] == "==") {
							if (get_post_type($_pid) == "product_variation") {
								$ands[] = ($_pid == $rule["endpoint"]);
							} else {
								$ands[] = false;
							}
						} else {
							if (get_post_type($_pid) == "product_variation") {
								$ands[] = ($_pid != $rule["endpoint"]);
							} else {
								$ands[] = false;
							}
						}
					}
				} else if ($rule["context"] == "product_cat") {
					if ($rule["endpoint"] == -1) {
						$ands[] = ($rule["logic"] == "==");
					} else {
						if ($rule["logic"] == "==") {
							$ands[] = has_term($rule["endpoint"], 'product_cat', $_pid);
						} else {
							$ands[] = !has_term($rule["endpoint"], 'product_cat', $_pid);
						}
					}
				} else if ($rule["context"] == "product_tag") {
					if ($rule["endpoint"] == -1) {
						$ands[] = ($rule["logic"] == "==");
					} else {
						if ($rule["logic"] == "==") {
							$ands[] = has_term($rule["endpoint"], 'product_tag', $_pid);
						} else {
							$ands[] = !has_term($rule["endpoint"], 'product_tag', $_pid);
						}
					}
				} else if ($rule["context"] == "product_type") {
					if ($rule["endpoint"] == -1) {
						$ands[] = ($rule["logic"] == "==");
					} else {
					    $ptype = wp_get_object_terms($_pid, 'product_type');
					    if (!empty($ptype)) {
					        $ands[] = ($ptype[0]->slug == $rule["endpoint"]);
					    }						
					}
				}

			}

			$matches[] = $ands;

		}		

		foreach ($matches as $match) {
			if (!empty($match)) {
				$final_matches[] = !in_array(false, $match);
			}			
		}	
		
		return in_array(true, $final_matches);

	}
	
	/**
	 *
	 * WCFF Location Rules Engine, This is function used to determine where does the  particular wccaf fields group<br/>
	 * to be placed. in the product view, product cat view or one of any product data sections ( Tabs )<br/>
	 * applicable only for wccaf post_type.
	 *
	 * @param integer $_pid
	 * @param array	$_groups
	 * @param string $_location
	 *
	 */
	public function check_for_location($_gpid, $_rule, $_location, $_custom_target_tab_title) {
			
	    if ($_rule["context"] == "location_product_data") {
			if ($_location != "wccaf_custom_product_data_tab") {
				if ($_rule["endpoint"] == $_location) {
					return true;
				}
			} else {
				/* Needs to put in the fields on custom tabs */
				$_custom_tab_title = $this->load_custom_product_data_tab_title($_gpid);
				if ($_custom_tab_title == $_custom_target_tab_title) {
					return true;
				}
			}	        
		}
		if (($_rule["context"] == "location_product" || $_rule["context"] == "location_order") && $_location == "admin_head-post.php") {
			return true;  
		} 
		if ($_rule["context"] == "location_product_cat" && ($_location == "product_cat_add_form_fields" || $_location == "product_cat_edit_form_fields"))  {
			return true;
		}

		return false;
	}
	
	private function check_for_roles($_targeted_roles) {	    
	    
	    global $wp_roles;
	    
	    $all_roles = array();
	    foreach ($wp_roles->roles as $handle => $role) {
	        $all_roles[] = $handle;
	    }
	    
	    if (!$_targeted_roles || empty($_targeted_roles)) {
	        $_targeted_roles = $all_roles;
	    }
	    
	    $user = wp_get_current_user();	    
	    $intersect = array_intersect($_targeted_roles, (array) $user->roles);
	    return (count($intersect) > 0);
	    
	}
	
	/**
	 *
	 * Order the array for the given property.
	 *
	 * @param array $_arr
	 * @param string $_col
	 * @param string $_dir
	 *
	 */
	public function usort_by_column(&$_arr, $_col, $_dir = SORT_ASC) {
		$sort_col = array();
		foreach ($_arr as $key=> $row) {
			$sort_col[$key] = $row[$_col];
		}
		array_multisort($sort_col, $_dir, $_arr);
	}

	/**
	 * 
	 */
	public function migrate_for_version_4xxx() {
	    
	    /* No longer needed */
	    return;
		
		/* Check wccpf */		
		$this->migrate_fields("wccpf");		
		/* Check wccaf */
		$this->migrate_fields("wccaf");
		/* Check wccvf */
		$this->migrate_fields("wccvf");
		/* Check wcccf */
		//$this->migrate_fields("wcccf");

		wcff()->option->update_option("version", wcff()->info["version"]);
		wcff()->option->update_option("enable_custom_pricing", "yes");

	}

	public function get_wcff_special_keys() {
		return $this->special_keys;
	}

	private function migrate_fields($_ptype = "") {
		
		$this->wcff_key_prefix = $_ptype . "_";
		/* Special keys that is not part of fields meta */
	    $excluded_keys = $this->prepare_special_keys();

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
	                 
	            /* Get all custom meta */
	            $fields = get_post_meta($g_post->ID);

				foreach ($fields as $fkey => $meta) {   
					/* Exclude special purpose custom meta */
					if (!in_array($fkey, $excluded_keys) && (strpos($fkey, $this->wcff_key_prefix) === 0)) {
						
						$field = json_decode($meta[0], true);

						/* Check is_enabled property */
						if (!isset($field["is_enable"]) || !$field["is_enable"]) {						
							$field["is_enable"] = true;
						}
						/* Check key property */
						if (!isset($field["key"]) && isset($field["name"])) {
							$field["key"] = $field["name"];
						}

						if ($field["type"] == "checkbox" && isset($field["pricing_rules"]) && is_array($field["pricing_rules"])) {							
							foreach ($field["pricing_rules"] as $pkey => $rule) {
								$field["pricing_rules"][$pkey]["old_logic"] = $field["pricing_rules"][$pkey]["logic"];
								$field["pricing_rules"][$pkey]["logic"] = "has-options";
							}							
						}
						if ($field["type"] == "checkbox" && isset($field["fee_rules"]) && is_array($field["fee_rules"])) {								
							foreach ($field["fee_rules"] as $pkey => $rule) {
								$field["fee_rules"][$pkey]["old_logic"] = $field["fee_rules"][$pkey]["logic"];
								$field["fee_rules"][$pkey]["logic"] = "has-options";
							}							
						}
						if ($field["type"] == "checkbox" && isset($field["field_rules"]) && is_array($field["field_rules"])) {							
							foreach ($field["field_rules"] as $pkey => $rule) {
								$field["field_rules"][$pkey]["old_logic"] = $field["field_rules"][$pkey]["logic"];
								$field["field_rules"][$pkey]["logic"] = "has-options";
							}							
						}

						if (isset($field["field_rules"]) && is_array($field["field_rules"])) {
							foreach ($field["field_rules"] as $pkey => $rule) {
								if (isset($rule["field_rules"])) {
									foreach ($rule["field_rules"] as $frkey => $fval) {

										$fname = $frkey;
										/* Prior to V4 check box key has suffix of [] - so we need to remove this */
										if ($this->endsWith($frkey, "[]")) {
											$fname = substr($frkey, 0, strlen($frkey) - 2);
											$field["field_rules"][$pkey]["field_rules"][$fname] = $fval;
											if ($frkey != $fname) {
												unset($field["field_rules"][$pkey]["field_rules"][$frkey]);
											}
										}																

									}
								}
							}
						}
						
						update_post_meta($g_post->ID, $fkey, wp_slash(json_encode($field)));												
					}
				}

				/* Update the admin location rules for wccaf */
				if ($_ptype == "wccaf" && isset($fields["wccaf_location_rules"])) {

					$lrules = json_decode($fields["wccaf_location_rules"][0], true);
					if (is_array($lrules[0]) && is_array($lrules[0][0])) {
						update_post_meta($g_post->ID, "wccaf_location_rules", wp_slash(json_encode($lrules[0][0])));
					}					

				}

			}
		}

	}
	
	function endsWith($haystack, $needle) {
		return substr_compare($haystack, $needle, -strlen($needle)) === 0;
	}

	private function prepare_search_response($page, $qry, $all_map=array()) {

		global $wpdb;
		$total = 0;
		$res = array();
		$offset = ($page * $this->records_per_page) - $this->records_per_page; 

		if ($page > 0) {
			/* Needs pagintation */
			$tQry = "SELECT COUNT(1) FROM (${qry}) AS combined_table";
			$total = $wpdb->get_var($tQry);
			$qry = $wpdb->prepare(($qry ." ORDER BY ID DESC LIMIT %d, %d"), $offset, $this->records_per_page);
			$posts = $wpdb->get_results($qry, OBJECT);
		} else {
			/* No need to paging */
			$posts = $wpdb->get_results($qry);
		}	
		
		if (!empty($all_map)) {
			$res[] = $all_map;
		}

		if (is_array($posts)) {
			foreach ($posts as $post) {
				if (property_exists($post, "post_title")) {
					$res[] = array("id" => $post->ID, "title" => $post->post_title);
				} else {
					$variation = new WC_Product_Variation($post->{"ID"});
                    $variationName = implode(" | ", $variation->get_variation_attributes());
					$res[] = array("id" => $post->ID, "title" => $variationName);					
				}
			}
		} else {
			$page = 1;
			$total = 0;
		}
		
		return $this->prepare_page_response($page, $total, $res);

	}
	
	private function prepare_page_response($_page, $_total, $_records) {
		return array(
			"page" => $_page,
			"total" => $_total,
			"records_per_page" => $this->records_per_page,
			"records" => $_records
		);
	}

	/**
	 * 
	 * @return string
	 */
	private function generate_unique_id() {
		$token = '';
		$token_length = 12;
		$alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$alphabet .= "abcdefghijklmnopqrstuvwxyz";
		$alphabet .= "0123456789";
		$alphabetLength = strlen($alphabet);		
		for ($i = 0; $i < $token_length; $i++) {
			$randomKey = $this->get_random_number(0, $alphabetLength);
			$token .= $alphabet[$randomKey];
		}
		return $token;
	}
	
	/**
	 * 
	 * @param number $_min
	 * @param number $_max
	 * @return number
	 */
	private function get_random_number($_min = 0, $_max = 0) {
		$range = ($_max - $_min);
		if ($range < 0) {
			return $_min;
		}
		$log = log($range, 2);
		$bytes = (int) ($log / 8) + 1;
		$bits = (int) $log + 1;
		$filter = (int) (1 << $bits) - 1;
		do {
			$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
			$rnd = $rnd & $filter;
		} while ($rnd >= $range);
		return ($_min + $rnd);
	}
	
	private function prepare_special_keys($_post = "") {
	    $excluded_keys = array();
	    if ($this->wcff_key_prefix != "") {
	        foreach ($this->special_keys as $key) {
	            $excluded_keys[] = $this->wcff_key_prefix . $key;
	        }
	    }
	    return $excluded_keys;
	}

}

?>