<?php 

global $post_type;
if ($post_type == "wccpf" || $post_type == "wccaf" || $post_type == "wccvf") { ?>
					
<script type="text/javascript">

(function($) {	
	
	$(document).ready(function(){		
		var wrapper = $('<div class="wcff-post-listing-column"></div>');
		wrapper.append( $('<div class="wcff-left-column"></div>') );
		$("#posts-filter, .subsubsub").wrapAll( wrapper );
			
		var wcff_message_box = '<div class="wcff-message-box">';
		wcff_message_box += '<div class="wcff-msg-header"><h3><?php _e( 'WC Fields Factory', 'wc-fields-factory' ); ?> <span><?php echo esc_attr(wcff()->info["version"]); ?></span></h3></div>';
		wcff_message_box += '<div class="wcff-msg-content">';
		wcff_message_box += '<h5><?php _e( 'Documentations', 'wc-fields-factory' ); ?></h5>';
		wcff_message_box += '<a href="https://wcfieldsfactory.com/user-guide/fields-for-woocommerce-products/" title="<?php _e( 'Product Fields', 'wc-fields-factory' ); ?>" target="_blank"><?php _e( 'Product Fields', 'wc-fields-factory' ); ?></a>';
		wcff_message_box += '<a href="https://wcfieldsfactory.com/user-guide/fields-for-woocommerce-variations/" title="<?php _e( 'Variation Fields', 'wc-fields-factory' ); ?>" target="_blank"><?php _e( 'Variation Fields', 'wc-fields-factory' ); ?></a>';
		wcff_message_box += '<a href="https://wcfieldsfactory.com/user-guide/fields-for-woocommerce-admin/" title="<?php _e( 'Admin Fields', 'wc-fields-factory' ); ?>" target="_blank"><?php _e( 'Admin Fields', 'wc-fields-factory' ); ?></a>';
		wcff_message_box += '<a href="https://wcfieldsfactory.com/user-guide/custom-pricing-cart-fee/" title="<?php _e( 'Pricing & Fee Rules', 'wc-fields-factory' ); ?>" target="_blank"><?php _e( 'Pricing & Fee Rules', 'wc-fields-factory' ); ?></a>';
		wcff_message_box += '<a href="https://wcfieldsfactory.com/user-guide/internationalization/" title="<?php _e( 'Multilingual Setup', 'wc-fields-factory' ); ?>" target="_blank"><?php _e( 'Multilingual Setup', 'wc-fields-factory' ); ?></a>';				
		
		wcff_message_box += '</div>';
		wcff_message_box += '<div class="wcff-msg-footer">';
		wcff_message_box += '<a href="https://sarkware.com" title="Sarkware" target="_blank">';
		wcff_message_box += '<img src="<?php echo esc_url(wcff()->info["dir"]); ?>/assets/img/sarkware.png" alt="Sarkware" /> by Sarkware';
		wcff_message_box += '</a>';
		wcff_message_box += '</div>';		
		
		$(".wcff-post-listing-column").append( $('<div class="wcff-right-column">'+ wcff_message_box +'</div>') );
	});
	
})(jQuery);

</script>

<style type="text/css">
	#posts-filter p.search-box { display:none; }
</style>
    							
    	<?php		
}

?>