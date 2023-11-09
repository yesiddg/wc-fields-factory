<?php 
/**
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Research & Development (OPC) Pvt Ltd
 * 
 * @todo		: Wrapper module for all wccpf related Ajax request.
 * 				  All Ajax request target for wccpf will be converted to "wcff_request" object and
 * 				  made available to the context through "wcff()->request".
 * 
 */
if (!defined('ABSPATH')) { exit; }

class wcff_request {
	
	function __construct() {
		add_filter('wcff_request', array($this, 'prepare_request'));
	}
	
	function prepare_request() {
		if (isset($_REQUEST["wcff_param"])) {		    
			$payload = json_decode(str_replace('\"','"',$_REQUEST["wcff_param"]), true);	
			if ($payload) {
			    return array (
			        "method" 	=> isset($payload["method"]) ? $payload["method"] : null,
			        "context" 	=> isset($payload["context"]) ? $payload["context"] : null,
			        "post" 		=> isset($payload["post"]) ? $payload["post"] : null,
			        "post_type" => isset($payload["post_type"]) ? $payload["post_type"] : null,
			        "payload" 	=> isset($payload["payload"]) ? $payload["payload"] : null
			    );
			}
			wcff()->response = apply_filters( 'wcff_response', false, json_last_error_msg(), null );
			return false;
		} 
		wcff()->response = apply_filters( 'wcff_response', false, "wcff_param is missing.!", null );
		return false;
	}	
	
}

new wcff_request();

?>