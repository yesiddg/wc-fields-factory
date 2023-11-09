<?php 
/**
 * @author		: Saravana Kumar K
 * @copyright	: Sarkware Research & Development (OPC) Pvt Ltd
 * 
 * @todo		: Wcff option page renderer
 * 
 */

if (is_admin()) {
    add_action("admin_init", "wcff_register_options");
}

function wcff_register_options() {
    register_setting("wcff_options", "wcff_options");
}

/* Wrapper class for getting wcff options */
class wcff_options {

	public function __construct() {}

	public function get_options() {
	    
	    $options = get_option("wcff_options");
	    $options =  is_array($options) ? $options : array();

	    /**
	     * 
	     * @since V3.0.4
	     * @depricated_options
	     * We are renaming the option key as 'wcff_options' instead of 'wccpf_options'
	     * 
	     */
	    
	    /* Making sure the transition is smooth */
	    $depricated_options = get_option('wccpf_options');
	    if ($depricated_options !== false) {
	        /**
	         * @since V3.0.4
	         * @fields_group_title depricated
	         * instead use @global_cloning_title
	         **/
	        if (isset($depricated_options["fields_group_title"])) {
	            $depricated_options["global_cloning_title"] = $depricated_options["fields_group_title"];
	            unset($depricated_options["fields_group_title"]);
	        }
	        
	        update_option("wcff_options", $depricated_options);
	        delete_option("wccpf_options");
	        $options = $depricated_options;	        
	    }
		/* Allow other modules to supply additonal options 
		 * or to modify the existing options */
		return apply_filters("wcff_options", $options);

	}

	public function update_option($_key, $_value) {

		$options = get_option("wcff_options");
	    $options =  is_array($options) ? $options : array();

		$options[$_key] = $_value;
		update_option("wcff_options", $options);

	}

}

?>