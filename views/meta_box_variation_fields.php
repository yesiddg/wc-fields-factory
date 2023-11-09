<?php 

function render_variation_fields_config_view() { 

    global $post_type, $post_type_object;

    $post_type = "wccvf";
    $post_type_object = get_post_type_object($post_type);;
    
    get_current_screen()->id = "edit-{$post_type}";
    get_current_screen()->base = "edit";
    get_current_screen()->parent_base = "edit";
    get_current_screen()->post_type = "{$post_type}";
    
    set_current_screen();
    
    do_action( "load-edit.php" );
    
    ?>
	<div class="wrap">
	
		<h1 class="wp-heading-inline">Variable Field Groups</h1>
		<a href="<?php echo esc_url(get_home_url()); ?>/wp-admin/post-new.php?post_type=wccvf" class="page-title-action">Add New</a>
		<hr class="wp-header-end">
	
		<div class="wcff-post-listing-column">		
			<div class="wcff-left-column">
			
        		<div class="wcff-variation-config-view">
        				
        			<div class="wcff-variation-config-tab-header">
        				<a href="#wcff-variation-config-fields" class="selected">Variable Field Groups</a>
        				<a href="#wcff-variation-config-mapping">Mapping</a>
        				
        				<div id="wcff-variation-config-action-bar">
        					<!--  <a href="<?php echo esc_url(get_home_url()); ?>/wp-admin/post-new.php?post_type=wccvf" class="wcff-variation-config-action-btn new">Add Fields Group</a>-->        									 
        				</div>						
        			</div>		
        			
        			<div class="wcff-variation-config-tab-content">
        				<div id="wcff-variation-config-fields" style="display: block;">	
        					<form id="posts-filter" method="get">
        						
        						<?php 	
        						
        						$lister = new wcff_post_list_table("wccvf");
        						$post_type_object = get_post_type_object("wccvf");
        						$lister->search_box( $post_type_object->labels->search_items, 'post' ); ?>
        
        						<input type="hidden" name="post_status" class="post_status_page" value="<?php echo ! empty($_REQUEST['post_status']) ? esc_attr($_REQUEST['post_status']) : 'all'; ?>" />
        						<input type="hidden" name="post_type" class="post_type_page" value="wccvf" />
        						
        						<?php if ( ! empty( $_REQUEST['author'] ) ) { ?>
        						<input type="hidden" name="author" value="<?php echo esc_attr( $_REQUEST['author'] ); ?>" />
        						<?php } ?>
        						
        						<?php if ( ! empty( $_REQUEST['show_sticky'] ) ) { ?>
        						<input type="hidden" name="show_sticky" value="1" />
        						<?php } ?>
        							
        						<?php 	
        						//wcff()->dao->load_map_wccvf_variations();
        						$lister->prepare_items();
        						wp_enqueue_script( 'inline-edit-post' );
        						$lister->views();
        						$lister->render_views();
        						$lister->display(); ?>
        						
        					</form>
        				</div>
        				<div id="wcff-variation-config-mapping">
        					<div class="wcff-variation-config-mapping-header">
        						<table>
        							<tr>
        								<td>
        									<div>
        										<input type="text" id="wcff-variation-config-product-search" class="wcff-variation-config-search-field" placeholder="Search Product ..." data-type="product_variation" />
        										<ul id="wcff-variation-config-product-select" class="wcff-variation-config-popup" data-type="product"></ul>
        										<img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/img/spinner.gif'); ?>" class="progress-img" alt="loading">
        									</div>
        								</td>
        								<td>
        									<div>
        										<input type="text" id="wcff-variation-config-variation-search" class="wcff-variation-config-search-field" placeholder="Search Variations ..." data-type="variations" />
        										<ul id="wcff-variation-config-variation-select" class="wcff-variation-config-popup" data-type="product_variation"></ul>
        										<img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/img/spinner.gif'); ?>" class="progress-img" alt="loading">
        									</div>
        								</td>
        								<td>
        									<div>
        										<input type="text" id="wcff-variation-config-group-search" class="wcff-variation-config-search-field" placeholder="Search Field Groups ..." data-type="wccvf-all" />
        										<ul id="wcff-variation-config-group-select" class="wcff-variation-config-popup" data-type="wccvf-all"></ul>
        										<img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/img/spinner.gif'); ?>" class="progress-img" alt="loading">
        									</div>
        								</td>
        								<td>
        									<button id="wcff-variation-config-map-btn" class="button button-primary">
        										<img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/img/giphy.gif'); ?>" class="progress-img" alt="loading"> Insert Mapping
        									</button>
        								</td>
        							</tr>
        						</table>
        					</div>
        					<div class="wcff-variation-config-mapping-content">
        					
        					</div>
        				</div>
        			</div>
        		
        		</div>
		
			</div>			
			<div class="wcff-right-column">

    			<div class="wcff-message-box">
    				<div class="wcff-msg-header">
    					<h3>WC Fields Factory <span><?php echo esc_attr(wcff()->info["version"]); ?></span></h3>
    				</div>
    				<div class="wcff-msg-content">
    					<h5>Documentations</h5>
    					<a href="https://sarkware.com/wc-fields-factory-a-wordpress-plugin-to-add-custom-fields-to-woocommerce-product-page/" title="Product Fields" target="_blank">Product Fields</a>
    					<a href="https://sarkware.com/add-custom-fields-woocommerce-admin-products-admin-product-category-admin-product-tabs-using-wc-fields-factory/" title="Admin Fields" target="_blank">Admin Fields</a>
    					<a href="https://sarkware.com/pricing-fee-rules-wc-fields-factory/" title="Pricing &amp; Fee Rules" target="_blank">Pricing &amp; Fee Rules</a>
    					<a href="https://sarkware.com/multilingual-wc-fields-factory/" title="Multilingual Setup" target="_blank">Multilingual Setup</a>
    					<a href="https://sarkware.com/wc-fields-factory-api/" title="WC Fields Factory APIs" target="_blank">WC Fields Factory APIs</a>
    					<a href="https://sarkware.com/woocommerce-change-product-price-dynamically-while-adding-to-cart-without-using-plugins#override-price-wc-fields-factory" title="Override Product Prices" target="_blank">Override Product Prices</a>
    					<a href="https://sarkware.com/how-to-change-wc-fields-factory-custom-product-fields-rendering-behavior/" title="Rendering Behaviour" target="_blank">Rendering Behaviour</a>
    				</div>
    				<div class="wcff-msg-footer">
    					<a href="https://sarkware.com" title="Sarkware" target="_blank"><img src="<?php echo esc_url(wcff()->info["dir"]. "/assets/img/sarkware.png"); ?>" alt="Sarkware"> by Sarkware</a>
    				</div>
    			</div>
    
    		</div>		
		</div>
		
		<script type="text/javascript">
		var wcff_var = {
				post: 0,
				post_type : "wccvf",
				nonce  : "<?php echo wp_create_nonce(get_current_screen()->id .'_nonce'); ?>",
				admin_url : "<?php echo esc_url(admin_url()); ?>",
				ajaxurl : "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
				version : "<?php echo esc_attr(wcff()->info["version"]); ?>",	
				plugin_dir: "<?php echo esc_url(plugins_url("", __dir__)); ?>",
				asset_url: "<?php echo esc_url(wcff()->info["assets"]); ?>"
			};		
		</script>
		<script type="text/javascript" src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/js/wcff-admin.js'); ?>"></script>
		<script type="text/javascript" src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/js/wccvf-grid.js'); ?>"></script>
		
	</div>
	<?php 
}

?>
