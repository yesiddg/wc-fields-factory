<?php 
/**
 * 
 * @author 		  : Saravana Kumar K
 * @copyright	  : Sarkware Research & Development (OPC) Pvt Ltd
 * 
 * @todo		  : One of the core class which generates all WCFF related meta boxs in Admin Screen
 *
 */
if (!defined('ABSPATH')) { exit; }

class wcff_post_handler {
    
    function __construct() {       

    	add_action('admin_head-post.php', array($this, 'wcff_post_single_view'));
        add_action('admin_head-post-new.php',  array($this, 'wcff_post_single_view'));
        add_action('wcff_admin_head', array($this, 'wcff_admin_head'));
        add_filter('manage_edit-wccpf_columns', array($this, 'wcff_columns'));
        add_action('manage_wccpf_posts_custom_column', array($this, 'wcff_post_listing'), 10, 2);
        add_filter('manage_edit-wccaf_columns', array($this, 'wcff_columns'));
        add_action('manage_wccaf_posts_custom_column', array($this, 'wcff_post_listing'), 10, 2);
        add_filter('manage_edit-wccvf_columns', array($this, 'wcff_columns'));
        add_action('manage_wccvf_posts_custom_column', array($this, 'wcff_post_listing'), 10, 2);
        add_action('admin_head-edit.php', array($this, 'wcff_post_admin_listing'));        
        add_action('admin_enqueue_scripts', array($this, 'wcff_admin_enqueue_scripts'));

        add_action("add_meta_boxes", array($this, "wcff_inject_fields_list_meta_box"), 1);
        add_action("add_meta_boxes", array($this, "wcff_inject_factory_meta_box"), 10);
        add_action("add_meta_boxes", array($this, "wcff_inject_field_selector_meta_box"), 10);        
        add_action("add_meta_boxes", array($this, "wcff_inject_admin_location_meta_box"), 40);
        add_action("add_meta_boxes", array($this, "wcff_inject_group_preference_meta_box"), 40);
        add_action("add_meta_boxes", array($this, "wcff_inject_target_product_meta_box"), 100);
        add_action("add_meta_boxes", array($this, "wcff_inject_group_Level_location_meta_box"), 200);

    }

    function wcff_inject_fields_list_meta_box() {
        if ($this->is_wcff_post_screen()) {
            /**
             * 
             * Determine the title for the Field Lister Widget
             * 
             */
            $fields_meta_title = __( "Product Fields", "wc-fields-factory" );
            if ($this->wcff_check_screen("wccaf")) {
            	$fields_meta_title = __( "Admin Fields", "wc-fields-factory" );
            } else if ($this->wcff_check_screen("wccvf")) {
            	$fields_meta_title = __( "Variation Fields", "wc-fields-factory" );
            } else if ($this->wcff_check_screen("wcccf")) {
            	$fields_meta_title = __( "Checkout Fields", "wc-fields-factory" );
            }            
            /**
             * 
             * Fields Lister widget
             * 
             */
            add_meta_box('wcff_fields', $fields_meta_title, array($this, 'inject_fields_meta_box'), get_current_screen()->id, 'normal', 'high');
        }
    }

    function wcff_inject_factory_meta_box() {
        if ($this->is_wcff_post_screen()) {
            /**
             * 
             * Field configuration widget holder
             * 
             */
            add_meta_box('wcff_factory', __( "Fields Factory", "wc-fields-factory" ), array($this, 'inject_factory_meta_box'), get_current_screen()->id, 'normal', 'high');
        }
    }

    function wcff_inject_field_selector_meta_box() {
        if ($this->is_wcff_post_screen()) {
            /**
             * 
             * Fields type selector widget
             * 
             */
            add_meta_box('wcff_fields_selector', __("Fields Type", "wc-fields-factory"), array($this, 'inject_field_selector_meta_box'), get_current_screen()->id, 'side', 'high');
        }
    }

    function wcff_inject_admin_location_meta_box() {
        if ($this->is_wcff_post_screen()) {
            /**
             *
             * Location selector for Admin Fields
             *
             */
            if ($this->wcff_check_screen("wccaf")) {
                add_meta_box('wcff_locations', __( "Admin Fields Location", "wc-fields-factory" ), array($this, 'inject_admin_locations_meta_box'), get_current_screen()->id, 'normal', 'high');
            } 
        }
    }

    function wcff_inject_group_preference_meta_box() {
        if ($this->is_wcff_post_screen()) {
            /**
             * 
             * Fields group's preference widget
             * Not needed for Checkout Fields
             * 
             */
            if (!$this->wcff_check_screen("wcccf")) {
                $priority = $this->wcff_check_screen("wccaf") ? 'low' : 'high';
                $title = $this->wcff_check_screen("wccaf") ? __("Preferences &nbsp; <span>(Used when the fields are opted to show on Product Page)</span>", "wc-fields-factory" ) : __("Preferences", "wc-fields-factory" );
                add_meta_box('wcff_group_preference', $title, array($this, 'inject_group_preference_meta_box'), get_current_screen()->id, 'normal', $priority);
            }
        }
    }

    function wcff_inject_target_product_meta_box() {
        if ($this->is_wcff_post_screen()) {
            /**
             * 
             * Target product mapper widget
             * Not needed for Variable & Checkout Fields
             * 
             */
            if (!$this->wcff_check_screen("wccvf") && !$this->wcff_check_screen("wcccf")) {                
                add_meta_box('wcff_target_products', __("Target Product(s)", "wc-fields-factory" ), array($this, 'inject_target_products_meta_box'), get_current_screen()->id, 'normal', 'high');
            }
        }
    }

    function wcff_inject_group_Level_location_meta_box() {
        if ($this->is_wcff_post_screen()) {
            /**
             * 
             * Location selector for Product & Variable Fields
             * This widget will be used for the Admin Fields as well (Only for whichever admin fields shown on product page)
             * 
             */
            if (!$this->wcff_check_screen("wcccf") && !$this->wcff_check_screen("wccvf")) {
                $title = $this->wcff_check_screen( "wccaf") ? __(" User Fields Location &nbsp;<span>(Used when the fields are opted to show on Product Page)</span>", "wc-fields-factory" ) : __("Fields Location", "wc-fields-factory" );
                add_meta_box('wcff_product_field_location', $title, array($this, 'inject_wcff_product_field_location'), get_current_screen()->id, 'normal', 'low');
            }    
        }
    }

    function is_wcff_post_screen() {
        if ($this->wcff_check_screen("wccpf") || 
            $this->wcff_check_screen("wccaf") || 
            $this->wcff_check_screen("wccvf") || 
            $this->wcff_check_screen("wcccf")) {
            return true;
        }
        return false;
    }
    
    /**
     *
     * Injecting meta box for listing Custom Fields List.
     * Injecting meta box for factory section
     * Injecting meta box for Fields Rules section
     * Injecting meta box for Fields location Rules section
     *
     */
    function wcff_post_single_view() {    	                   
        if ($this->is_wcff_post_screen()) {          
            do_action( 'wcff_admin_head' );
        }        
        //add_meta_box("wcff_meta_list", "Meta List", array($this, "wccf_meta_listing"), get_current_screen()->id, 'normal', 'low'); 
    }
    
    function wccf_meta_listing() {
        include(wcff()->info['views'] .'/meta_box_metalist.php');        
    }
    
    /**
     *
     * Inject check box for bulk edit actions
     *
     * @param variant $_columns
     * @return string[]
     *
     */
    function wcff_columns( $_columns ) {    	
    	$_columns = array(
    		'cb' => '<input type="checkbox" />',
    		'title' => __( 'Title' ),
    		'fields' => __( 'Fields' )
    	);
    	return $_columns;
    }
    
    /**
     *
     * Returns the Fileds Count for a given Fields Group
     *
     * @param string $column
     * @param integer $post_id
     *
     */
    function wcff_post_listing($_column, $_pid) {
    	switch ($_column) {
    		case 'fields' :
    			$count =0;
    			$keys = get_post_custom_keys($_pid);

    			if ($keys) {
    				foreach ($keys as $key) {
    					if ((strpos($key, 'wccpf_') !== false || 
    						  strpos($key, 'wccaf_') !== false || 
    						  strpos($key, 'wccvf_') !== false) &&    					    
    						  (strpos($key, 'fee_rules') === false &&     						      
    						   strpos($key, 'field_rules') === false &&    						      
    						   strpos($key, 'group_rules') === false &&    						    
    						   strpos($key, 'layout_meta') === false &&
    						   strpos($key, 'pricing_rules') === false &&    						      
    						   strpos($key, 'location_rules') === false &&   						      
    						   strpos($key, 'condition_rules') === false &&    						      
    						   strpos($key, 'show_group_title') === false &&    						      
    						   strpos($key, 'target_stock_status') === false &&
    						   strpos($key, 'use_custom_layout') === false &&
    						   strpos($key, 'product_tab_title') === false &&    						      
    						   strpos($key, 'product_tab_priority') === false &&   
    						   strpos($key, 'is_this_group_clonable') === false &&
    						   strpos($key, 'fields_label_alignement') === false &&
    						   strpos($key, 'field_location_on_product') === false &&
    						   strpos($key, 'field_location_on_archive') === false &&
    						   strpos($key, 'is_this_group_clonable') === false &&
                               strpos($key, 'custom_product_data_tab_title') === false &&
                               strpos($key, 'custom_product_data_tab_priority') === false &&
    						   strpos($key, 'is_this_group_for_authorized_only') === false &&
    						   strpos($key, 'wcff_group_preference_target_roles') === false)) {
    						$count++;   						
    					}
    				}
    			}
    			echo $count;
    		break;
    	}    	
    }
    
    /**
     * Call back handler for injecting Fields List Meta Box
     */
    function inject_fields_meta_box() {
    	if ($this->wcff_check_screen("wccpf") || 
    		$this->wcff_check_screen("wccaf") || 
    		$this->wcff_check_screen("wccvf") || 
    		$this->wcff_check_screen("wcccf")) {
    		include(wcff()->info['views'] .'/meta_box_fields.php');
    	}
    }
    
    /**
     * 
     * Call back handler for injecting Factory View Meta Box
     * 
     */
    function inject_factory_meta_box() {
    	include(wcff()->info['views'] .'/meta_box_factory.php');
    }
    
    /**
     * 
     * Call back handler for injecting Fields Target Products Meta Box
     * 
     */
    function inject_target_products_meta_box() {
    	include(wcff()->info['views'] .'/meta_box_target_products.php');
    }
    
    /**
     * 
     * Call back handler for injecting Location Rules Meta Box
     * 
     */
    function inject_admin_locations_meta_box() {
    	include(wcff()->info['views'] .'/meta_box_admin_fields_locations.php');
    }
    
    /**
     * 
     * Call back handler for injecting Fields Selector Meta Box
     * 
     */
    function inject_field_selector_meta_box() {
    	include( wcff()->info['views'] .'/meta_box_fields_selector.php' );
    }
    
    /**
     * 
     * Call back handler for Group level Fields Location Rules Meta Box
     * 
     */
    function inject_wcff_product_field_location() {
    	include(wcff()->info['views'] .'/meta_box_field_location.php');
    }
    
    /**
     * 
     * Call back handler for Group level Preference Meta Box
     * 
     */
    function inject_group_preference_meta_box() {
    	include(wcff()->info['views'] .'/meta_box_group_preference.php');
    }
    
    /**
     *
     * Check the current admin screen name
     *
     * @param string $_scr_id
     * @return boolean
     *
     */
    function wcff_check_screen( $_scr_id ) {
    	if ($_scr_id == "wccpf-options") {
    		return ((get_current_screen()->id == "wccpf") || 
    				 (get_current_screen()->id == "wccaf") || 
    				 (get_current_screen()->id == "wccvf") || 
    				 (get_current_screen()->id == "wcccf") || 
    				 (get_current_screen()->id == "wccpf-options"));
    	}
    	return get_current_screen()->id == $_scr_id;
    }
    
    /**
     * 
     * Enqueue admin functionality related resources
     * 
     */
    function wcff_admin_enqueue_scripts() {
    	if ($this->wcff_check_screen("wccpf") || 
    		$this->wcff_check_screen("wccaf") || 
    		$this->wcff_check_screen("wccvf") || 
    		$this->wcff_check_screen("wcccf")) {
    		wp_enqueue_script('jquery-ui-core');
    		wp_enqueue_script('jquery-ui-tabs');
    		wp_enqueue_script('jquery-ui-sortable');
    		wp_enqueue_script('wp-color-picker');
    		wp_enqueue_script('wcff-script');
    		wp_enqueue_style(array(
    			'thickbox',
    			'wp-color-picker',
    			'wcff-style'
    		));
    		wp_enqueue_media();
    	}        
    }
    
    /**
     * 
     * Inject wcff_var, which will be used by the JS modules
     * 
     */
    function wcff_admin_head() {
    	global $post;
    	$wccpf_options = wcff()->option->get_options();
    	$supported_locale = isset($wccpf_options["supported_lang"]) ? $wccpf_options["supported_lang"] : array(); ?>
<script type="text/javascript">
var wcff_var = {
	post_id : <?php echo $post->ID; ?>,
	post_type : "<?php echo $post->post_type; ?>",
	nonce  : "<?php echo wp_create_nonce(get_current_screen()->id .'_nonce'); ?>",
	admin_url : "<?php echo esc_url(admin_url()); ?>",
	ajaxurl : "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
	version : "<?php echo wcff()->info["version"]; ?>",	
	locales: <?php echo json_encode($supported_locale); ?>,
	plugin_dir: "<?php echo esc_url(plugins_url("", __dir__)); ?>",
	asset_url: "<?php echo esc_url(wcff()->info["assets"]); ?>",
    current_page: "<?php echo get_current_screen()->id; ?>"
};		
</script>
<?php
	}
	
	/**
	 *
	 * Inject About Box on group wcff post listing pages
	 *
	 * @param string $_hook_suffix
	 *
	 */
	function wcff_post_admin_listing($_hook_suffix) {
	    include(wcff()->info["views"]. '/meta_box_sarkware.php');
	}
    
}

new wcff_post_handler();

?>
