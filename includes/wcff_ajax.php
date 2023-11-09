<?php 

if (!defined( 'ABSPATH' )) { exit; }
/**
 * 
 * Ajax handler for all WC Fields Related requests.
 * 
 * @author Saravana Kumar K
 * @copyright Sarkware Pvt Ltd
 *
 */
class wcff_ajax {
	
	public function __construct() {
		add_action("wp_ajax_wcff_ajax", array( $this, "listen" ) );
		add_action("wp_ajax_nopriv_wcff_ajax", array( $this, "listen" ) );
	}
	
	/**
	 * 
	 * Primary listener
	 * Origin for all wcff related Ajax requests
	 * Mostly comes from wp-admin wcff related screens
	 * All ajax request will have the following properties
	 * @param 	wcff()->request = {	 *
	                method		: Could be one of GET, POST, UPDATE or DELETE
	                context		: Context of the operation which it belongs. could be Product, Product Cat, Fields, Meta ...
	                post		    : ID of the current post
	                post_type	: CUrrent post type
	                payload		: Data sent by the Client. mostly JSON
	 * 			}
	 * All ajax response will have the following properties
	 * @param	wcff()->response = {
	 * 				status		: Status of the last operation - either TRUE or FALSE
	 * 				message		: Few words about the last operation, any status message ...
	 * 				data		    : The result of the last operation - could be json, html ...
	 * 			}
	 * 
	 */
	public function listen() {
		/* Parse the incoming request */
		wcff()->request = apply_filters( 'wcff_request', array() );
		/* Handle the request */
		$this->handleRequest();
		/* Respond the request */
		echo wcff()->response;
		/* end the request - response cycle */
		die();
	}
	
	/**
	 * 
	 * Called from listen method
	 * Primary handler for all wcff related Ajax request
	 * It drilled down the wcff()->request object and determine what operation has been requested by client
	 * Perform that operation and stores the rersponse on wcff()->response object
	 * 
	 */
	private function handleRequest() {
	    
	    $data = array();
	    $status = true;
	    $message = "";
	    $is_request_ok = true;    
	    
	    if (wcff()->request["method"] == null) {	        
	        $is_request_ok = false;
	        $message = "Method param missing.!";
	    } else if (wcff()->request["context"] == null) {
	        $is_request_ok = false;
	        $message = "Context param missing.!";
	    } else if (wcff()->request["post_type"] == null) {
	        $is_request_ok = false;
	        $message = "Post type param missing.!";
	    }
	    
	    if (!$is_request_ok) {
	        /**
	         * 
	         * Cannot continue without mandatory params
	         *  
	         **/
	        wcff()->response = apply_filters( 'wcff_response', false, $message, array() );
	        return;
	    }	
	    
	    /**
	     *
	     * Make sure the user has authorized 
	     * 
	     **/
	    if (!is_user_logged_in() 
	        && wcff()->request["context"] != "wcff_variation_fields" 
	        && wcff()->request["context"] != "wcff_render_field_on_cart_edit" 
	        && wcff()->request["context"] != "wcff_update_cart_field_data") {
	    	/**
	    	 * 
	    	 * User not authorized to perform this action 
	    	 * 
	    	 **/
	    	wcff()->response = apply_filters( 'wcff_response', false, "Not authorized", array());
	    	return;
	    }
	    
	    /**
	     * 
	     * Set the target post type 
	     * 
	     **/
	    wcff()->dao->set_current_post_type(wcff()->request["post_type"]);
	    
	    if (wcff()->request["method"] == "GET") {
	    	
	        if (wcff()->request["context"] == "product") {
	        	
	            /**
	             * 
	             * Request arrived for Product List 
	             * 
	             **/
	        	$data = wcff()->builder->build_products_selector("wcff_condition_value select");
	        	
	        } else if (wcff()->request["context"] == "product_cat") {
	        	
	        	/**
	        	 * 
	        	 * Request arrived for Product Cat List 
	        	 * 
	        	 **/
	        	$data = wcff()->builder->build_products_category_selector("wcff_condition_value select");
	        	
	        } else if (wcff()->request["context"] == "product_tag") {
	            
	            /**
	             * 
	             * Request arrived for Product Tag List 
	             * 
	             **/
	        	$data = wcff()->builder->build_products_tag_selector("wcff_condition_value select");
	        	
	        } else if (wcff()->request["context"] == "product_type") {
	            
	            /**
	             * 
	             * Request arrived for Product Type List 
	             * 
	             **/
	        	$data = wcff()->builder->build_products_type_selector("wcff_condition_value select");
	        	
	        }  else if (wcff()->request["context"] == "product_variation") {	            
	            
	            /**
	             * 
	             * Request arrived for Product Type List
	             *  
	             **/
	        	$parent_product = isset( wcff()->request["payload"]["product_id"] ) ? wcff()->request["payload"]["product_id"] : 0;
	        	$data = wcff()->builder->build_product_variations_selector("wcff_condition_value select", "", $parent_product);
	        	
	        } else if (wcff()->request["context"] == "location_product" ||  wcff()->request["context"] == "location_order" ||  wcff()->request["context"] == "location_product_cat") {	            
	            
	            /**
	             * 
	             * Request arrived for Metabox Context & Priority List
	             *  
	             **/
	        	$data = wcff()->builder->build_metabox_context_selector("wcff_location_metabox_context_value select");
	        	$data .= wcff()->builder->build_metabox_priority_selector("wcff_location_metabox_priorities_value select");
	        	
	        } else if (wcff()->request["context"] == "location_product_data") {	            
	            
	            /**
	             * 
	             * Request arrived for Product Tab List widget
	             *  
	             **/
	        	$data = wcff()->builder->build_products_tabs_selector("wcff_location_product_data_value select");
	        	
	        } else if (wcff()->request["context"] == "search") {
	            
	            /**
	             * 
	             * Returns the search result for requested post types
	             * 
	             **/        		

				if (wcff()->request["payload"]["post_type"] == "product") {
					/* product search */
					$data = wcff()->dao->search_products(wcff()->request["payload"]);
				} else if (wcff()->request["payload"]["post_type"] == "product_cat" || wcff()->request["payload"]["post_type"] == "product_tag") {
					/* For product cat & product tag search */
					$data = wcff()->dao->search_terms(wcff()->request["payload"]);
				} else if (wcff()->request["payload"]["post_type"] == "product_variation") {
					/* Variable parent products */
					$data = wcff()->dao->search_variation_products(wcff()->request["payload"]);
				}  else if (wcff()->request["payload"]["post_type"] == "variations") {
					/* Variable products */
					$data = wcff()->dao->search_variations(wcff()->request["payload"]);
				} else  {
					/* For everyhting else */
					$data = wcff()->dao->search_posts(wcff()->request["payload"]);
				}       	    
        	    
	        }  else if (wcff()->request["context"] == "field") {	        	
	        	
	        	/**
	        	 * 
	        	 * Return the config widget for a given field
	        	 * 
	        	 **/
	        	$data = wcff()->builder->build_factory_widget(
        			wcff()->request["payload"]["key"], 
        			wcff()->request["payload"]["type"], 
        			wcff()->request["post_type"],
        			wcff()->request["post"]
	        	);
	        	
	        } else if (wcff()->request["context"] == "wcff_field_list") {	        	
	        	
	        	/**
	        	 * 
	        	 * List of fields for a given group
	        	 *  
	        	 **/
	            $data = array();
	            $data["fields"] = wcff()->dao->load_fields(wcff()->request["post"]);
	            $data["layout"] = wcff()->dao->load_layout_meta(wcff()->request["post"]); 
	            $data["use_custom_layout"] = wcff()->dao->load_use_custom_layout(wcff()->request["post"]);
	            
	        } else if (wcff()->request["context"] == "wcff_meta_fields") {
	            
	            /**
	             * 
	             * Request arrived for Meta Fields for one of a wcff field 
	             * 
	             **/
	            $data = wcff()->builder->build_factory_fields(wcff()->request["payload"]["type"], wcff()->request["post_type"]);
	            
	        } else if (wcff()->request["context"] == "wcff_fields") {
	            
	            /**
	             * 
	             * get factory configuration meta values (saved as post meta) for given field 
	             * 
	             **/
	            $data = wcff()->dao->load_field(wcff()->request["post"], wcff()->request["payload"]["field_key"]);
	            if (!$data) {
	                $data = array();
	                $message = "Failed to load wcff meta";
	            }
	            
	        } else if (wcff()->request["context"] == "wcff_render_field_on_cart_edit") {
	            
	            /**
	             * 
	             * Get the field html to render for cart data 
	             * 
	             **/
	            $data = wcff()->editor->render_field_with_data(wcff()->request["payload"]);	
	            if (!is_array($data)) {
	            	$status = false;
	            	$message = "Internal error.!";
	            }
	            
	        } else if (wcff()->request["context"] == "wcff_variation_fields") {
	        	
	            if( isset( wcff()->request["payload"]["variation_id"] ) ){
	                $data = wcff()->injector->inject_variation_fields(wcff()->request["payload"]["variation_id"]);
	        	}
	        	
	        } else if (wcff()->request["context"] == "wcff_field_clone") {
	            
	            /**
	             * 
	             * Clone an existing field 
	             * 
	             **/
	        	$data = wcff()->dao->clone_field(wcff()->request["post"], wcff()->request["payload"]["fkey"]);
	        	
	        } else if (wcff()->request["context"] == "render_field" || wcff()->request["context"] == "render_field_for_designer") {
	            
	            /**
	             * 
	             * Used by the Layout designer 
				 * For single field rendering
	             * 
	             **/
	            $data = wcff()->builder->build_user_field(wcff()->request["payload"]["meta"], wcff()->request["post_type"]);
	            
	        } else if (wcff()->request["context"] == "render_fields_for_designer") {

				 /**
	             * 
	             * Used by the Layout designer 
				 * For rendering all fields at once
	             * 
	             **/
				$data = wcff()->builder->build_user_fields(wcff()->request["post"], wcff()->request["payload"]);

			} else if (wcff()->request["context"] == "variation_fields_mapping_list") {
	            
	            /**
	             *
	             * 
	             *
	             **/
	            $data = wcff()->dao->load_map_wccvf_variations();
	            
	        } else if (wcff()->request["context"] == "fetch_supported_locales") {

				/**
				 * 
				 * 
				 * 
				 */
				$data = wcff()->locale->get_supported_locales(wcff()->request["payload"]["default_locale"]);

			} else {
	            
	            /**
	             * 
	             * Unknown context 
	             * 
	             **/
	            $message = "Unknown Context";
	            
	        }
    	} else if (wcff()->request["method"] == "POST") { 
    		
    		if (wcff()->request["context"] == "field") {
    			
    			/**
    			 * 
    			 * Create a new field on a given type 
    			 * 
    			 **/
    			$id = wcff()->dao->create_field(wcff()->request["post"], wcff()->request["payload"]["type"], wcff()->request["payload"]["order"]);
    			if ($id) {
    				
    				/**
    				 * 
    				 * Now build the fields config widget and send it back to the client 
    				 * 
    				 **/
    				$data = wcff()->builder->build_factory_widget($id, wcff()->request["payload"]["type"], wcff()->request["post_type"]);
    				
    			} else {
    				$status = false;
    				$message = "Internal error.!";
    			}
    			
    		} else if (wcff()->request["context"] == "variation_fields_map") {
    		    
    		    /**
    		     * 
    		     * Request arrived for saving Variation Fields Mapping
    		     * 
    		     **/
				if (wcff()->dao->insert_wccvf_map_varioation_level(wcff()->request["payload"])) {
					$message = "Added Successfully ";
					$data = wcff()->dao->load_map_wccvf_variations();
				} else {
					$status = false;
					$message = "Failed to Insert Mapping";
				}    			
    			
    		} else if (wcff()->request["context"] == "wcff_fields") {	  
    		    $res = wcff()->dao->update_field(wcff()->request["post"], wcff()->request["payload"]);
    		    if ( $res["res"] ) {
    		        $message = $res["msg"];
	        	} else {
	        		$status = false;
	        		$message = $res["msg"];
	        	}
    		} else if(wcff()->request["context"] == "wcff_ask_rate_diss" ) {
    			$status = true;
    		    add_option( "wcff_ask_rate_dissmiss", "yes" );    		    
    		    $message = "Successfully disabled ask rate bar.";
    		} else {
    			
    			/**
    			 * 
    			 * Unknown context 
    			 * 
    			 **/
    			$message = "Unknown Context";
    			
    		}
    		
    	} else if (wcff()->request["method"] == "PUT") { 
    		if (wcff()->request["context"] == "field") {
    			$data = wcff()->dao->update_field(wcff()->request["post"], wcff()->request["payload"]);
    		} else if (wcff()->request["context"] == "toggle_field") {
    			$data = wcff()->dao->toggle_field(wcff()->request["post"], wcff()->request["payload"]["key"], wcff()->request["payload"]["status"]);
    		} else if (wcff()->request["context"] == "wcff_update_cart_field_data") {
           		
           		/**
           		 * 
           		 * Update the fields value, comes from the Cart Page Field Editor 
           		 * 
           		 **/
           		$data = wcff()->editor->update_field_value(wcff()->request["payload"]);
           		
    		} else if (wcff()->request["context"] == "insert_wccvf_mapping") {
    		    
    		    /**
    		     * 
    		     * Update wccvf condition rules fore variation level
    		     * 
    		     */
    		    $data = wcff()->dao->insert_wccvf_map_varioation_level(wcff()->request["payload"]);
    		    
    		} else {
           		
           		/**
           		 * 
           		 * Unknown context 
           		 * 
           		 **/
           		$message = "Unknown Context";
           		
           	}
    	} else if (wcff()->request["method"] == "DELETE") {  
    		if (wcff()->request["context"] == "field") {
    		    
    		    /**
    		     * 
    		     * Request arrived for deleting a field
    		     * 
    		     */
    			if (wcff()->dao->remove_field(wcff()->request["post"], wcff()->request["payload"]["field_key"])) {
    				$message = "Successfully removed";
    			} else {
    				$status = false;
    				$message = "Failed to remove the custom field";
    			}
    			
    		} else if (wcff()->request["context"] == "mapping") {
    		    
    		    /**
    		     * 
    		     * Request arrived for removing field mapping
    		     * 
    		     */  
    		    if (wcff()->dao->remove_wccvf_map_variation_level(wcff()->request["payload"])) {
    		        $message = "Successfully removed";	
					$data = wcff()->dao->load_map_wccvf_variations();				
    		    } else {
    		        $status = false;
    		        $message = "Failed to remove the field mapping";
    		    }
    		    
    		} else {
    			
    			/**
    			 * 
    			 * Unknown context 
    			 * 
    			 **/
    			$message = "Unknown Context";
    			
    		}
	    } else {
	   	    $message = "Unknown Request Type";
	    }
	    
	    /**
	     * 
	     * Store Status, Message and Data, which will be flushed out to client later 
	     * 
	     **/
	    wcff()->response = apply_filters( 'wcff_response', $status, $message, $data );
	    
	}
	
}

new wcff_ajax();

?>