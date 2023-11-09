<?php 

function wcff_enqueue_option_assets() {
	wp_register_style('wcff-style', esc_url(plugin_dir_url(__FILE__)) . '../assets/css/wcff-admin.css');
	wp_enqueue_style('wcff-style');
}
add_action('admin_enqueue_scripts', 'wcff_enqueue_option_assets');	

function wcff_render_option_page() {

    $wcff_options = wcff()->option->get_options();   	
	$variable_module = isset($wcff_options["enable_variable_field"]) ? $wcff_options["enable_variable_field"] : "yes";
	$admin_module = isset($wcff_options["enable_admin_field"]) ? $wcff_options["enable_admin_field"] : "yes";
	$checkout_module = isset($wcff_options["enable_checkout_field"]) ? $wcff_options["enable_checkout_field"] : "yes";	

    $show_custom_data = isset($wcff_options["show_custom_data"]) ? $wcff_options["show_custom_data"] : "yes";
    $fields_location = isset($wcff_options["field_location"]) ? $wcff_options["field_location"] : "woocommerce_before_add_to_cart_button";
    $fields_archive_location = isset($wcff_options["field_archive_location"]) ? $wcff_options["field_archive_location"] : "woocommerce_after_shop_loop_item_title";
    
    $ptab_title = isset($wcff_options["product_tab_title"]) ? $wcff_options["product_tab_title"] : "";
    $ptab_priority = isset($wcff_options["product_tab_priority"]) ? $wcff_options["product_tab_priority"] : 30;
    $is_show_login_user_only = isset($wcff_options["show_login_user_only"]) ? $wcff_options["show_login_user_only"] : "no";
    $edit_field_value_cart_page =  isset($wcff_options["edit_field_value_cart_page"]) ? $wcff_options["edit_field_value_cart_page"] : "no";
    $fields_on_archive = isset($wcff_options["fields_on_archive"]) ? $wcff_options["fields_on_archive"] : "no";
    $fields_cloning = isset($wcff_options["fields_cloning"]) ? $wcff_options["fields_cloning"] : "no";
    $cloning_title =  isset( $wcff_options["global_cloning_title"] ) ? $wcff_options["global_cloning_title"] : "";
    $show_field_group_title =  isset($wcff_options["show_group_title"]) ? $wcff_options["show_group_title"] : "no";
    $group_meta_on_cart = isset($wcff_options["group_meta_on_cart"]) ? $wcff_options["group_meta_on_cart"] : "no";
    $group_fields_on_cart = isset($wcff_options["group_fields_on_cart"]) ? $wcff_options["group_fields_on_cart"] : "no";
    $client_side_validation = isset($wcff_options["client_side_validation"]) ? $wcff_options["client_side_validation"] : "no";
    $client_side_validation_type = isset($wcff_options["client_side_validation_type"]) ? $wcff_options["client_side_validation_type"] : "submit";
    
    $custom_pricing_module = isset($wcff_options["enable_custom_pricing"]) ? $wcff_options["enable_custom_pricing"] : "yes";
    
    $wcff_ajax_pricing_rules = isset($wcff_options["enable_ajax_pricing_rules"]) ? $wcff_options["enable_ajax_pricing_rules"] : "disable";   
    $wcff_show_pricing_details_container = isset($wcff_options["pricing_rules_details"]) ? $wcff_options["pricing_rules_details"] : "show";
    $wcff_ajax_pricing_rules_price_container = isset($wcff_options["ajax_pricing_rules_price_container"]) ? $wcff_options["ajax_pricing_rules_price_container"] : "default";
  	$wcff_ajax_price_replace_container = isset($wcff_options["ajax_price_replace_container"]) ? $wcff_options["ajax_price_replace_container"] : "";
    $enable_multilingual = isset($wcff_options["enable_multilingual"]) ? $wcff_options["enable_multilingual"] : "no";
    $supported_locale = isset($wcff_options["supported_lang"]) ? $wcff_options["supported_lang"] : array(); 
	$default_locale = isset($wcff_options["default_locale"]) ? $wcff_options["default_locale"] : "en"; 

	$product_priority = isset($wcff_options["product_priority"]) ? $wcff_options["product_priority"] : 10; 
	$archive_priority = isset($wcff_options["archive_priority"]) ? $wcff_options["archive_priority"] : 10; 
	$custom_product_fields_location = isset($wcff_options["custom_product_fields_location"]) ? $wcff_options["custom_product_fields_location"] : ""; 
	$custom_archive_fields_location = isset($wcff_options["custom_archive_fields_location"]) ? $wcff_options["custom_archive_fields_location"] : ""; 
    
    ?>
    
	<?php if (isset($_GET["settings-updated"])) :?>
	<div id="message" class="updated fade"><p><strong><?php esc_html_e('Settings updated successfully.!', 'wc-fields-factory'); ?></strong></p></div>
	<?php endif; ?>

	<div class="wrap wcff-options-wrapper">		
		<h2><?php esc_html_e( 'WC Fields Factory Options', 'wc-fields-factory' ); ?></h2>
		<form action='options.php' method='post' class='wcff-options-form'>		
			<?php settings_fields('wcff_options'); ?>
				
			<?php if (isset($wcff_options["version"])) : ?>
				<!-- Hidden  setting for version - Since V 4.X.X -->
				<input type="hidden" name="wcff_options[version]" value="<?php echo esc_attr($wcff_options["version"]); ?>"/>
			<?php endif; ?>

			<table class="wcff-option-field-row wcff_table">			

				<tr>
					<td class="summary">
						<label><?php esc_html_e( 'Enable / Disable Modules', 'wc-fields-factory' ); ?></label>
						<p class="description"><?php esc_html_e( 'Option to disable unwanted feature for your instance.!', 'wc-fields-factory' ); ?></p>
					</td>
					<td>
						<div class="wcff-field-types-meta">							
							<div class="wcff-options-module-status">								
							
								<ul class="wcff-field-layout-horizontal">
									<li><h3>Variable Fields </h3></li>
									<li><label><input type="radio" name="wcff_options[enable_variable_field]" value="yes" <?php echo ($variable_module == "yes") ? "checked" : ""; ?>/> <?php esc_html_e( 'Enable', 'wc-fields-factory' ); ?></label></li>
									<li><label><input type="radio" name="wcff_options[enable_variable_field]" value="no" <?php echo ($variable_module == "no") ? "checked" : ""; ?>/> <?php esc_html_e( 'Disable', 'wc-fields-factory' ); ?></label></li>
								</ul>

								<ul class="wcff-field-layout-horizontal">
									<li><h3>Admin Fields </h3></li>
									<li><label><input type="radio" name="wcff_options[enable_admin_field]" value="yes" <?php echo ($admin_module == "yes") ? "checked" : ""; ?>/> <?php esc_html_e( 'Enable', 'wc-fields-factory' ); ?></label></li>
									<li><label><input type="radio" name="wcff_options[enable_admin_field]" value="no" <?php echo ($admin_module == "no") ? "checked" : ""; ?>/> <?php esc_html_e( 'Disable', 'wc-fields-factory' ); ?></label></li>
								</ul>

								<ul class="wcff-field-layout-horizontal">
									<li><h3>Checkout Fields </h3></li>
									<li><label><input type="radio" name="wcff_options[enable_checkout_field]" value="yes" <?php echo ($checkout_module == "yes") ? "checked" : ""; ?>/> <?php esc_html_e( 'Enable', 'wc-fields-factory' ); ?></label></li>
									<li><label><input type="radio" name="wcff_options[enable_checkout_field]" value="no" <?php echo ($checkout_module == "no") ? "checked" : ""; ?>/> <?php esc_html_e( 'Disable', 'wc-fields-factory' ); ?></label></li>
								</ul>

							</div>							
						</div>						
					</td>
				</tr>			

				<tr>
					<td class="summary">
						<label><?php esc_html_e( 'Display on Cart & Checkout', 'wc-fields-factory' ); ?></label>
						<p class="description"><?php esc_html_e( 'Display custom fields data on Cart & Checkout page.!', 'wc-fields-factory' ); ?></p>
					</td>
					<td>
						<div class="wcff-field-types-meta">
							<ul class="wcff-field-layout-horizontal">
								<li><label><input type="radio" name="wcff_options[show_custom_data]" value="yes" <?php echo ($show_custom_data == "yes") ? "checked" : ""; ?>/> <?php esc_html_e( 'Yes', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" name="wcff_options[show_custom_data]" value="no" <?php echo ($show_custom_data == "no") ? "checked" : ""; ?>/> <?php esc_html_e( 'No', 'wc-fields-factory' ); ?></label></li>
							</ul>						
						</div>
					</td>
				</tr>			
				<tr>
					<td class="summary">
						<label><?php esc_html_e( 'Fields Location', 'wc-fields-factory' ); ?></label>
						<p class="description"><?php esc_html_e( 'Choose where the fields should be displayed on product page', 'wc-fields-factory' ); ?></p>
					</td>
					<td>
						<div class="wcff-field-types-meta">
							<ul class="wcff-field-layout-horizontal wcff-field-location-on-product">
								<li><label><input type="radio" class="wcff-fields-product-location-radio" name="wcff_options[field_location]" value="woocommerce_before_add_to_cart_button" <?php echo ($fields_location == "woocommerce_before_add_to_cart_button") ? "checked" : ""; ?>/> <?php esc_html_e( 'Before Add To Cart Button', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" class="wcff-fields-product-location-radio" name="wcff_options[field_location]" value="woocommerce_after_add_to_cart_button" <?php echo ($fields_location == "woocommerce_after_add_to_cart_button") ? "checked" : ""; ?>/> <?php esc_html_e( 'After Add To Cart Button', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" class="wcff-fields-product-location-radio" name="wcff_options[field_location]" value="woocommerce_before_add_to_cart_form" <?php echo ($fields_location == "woocommerce_before_add_to_cart_form") ? "checked" : ""; ?>/> <?php esc_html_e( 'Before Add To Cart Form', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" class="wcff-fields-product-location-radio" name="wcff_options[field_location]" value="woocommerce_after_add_to_cart_form" <?php echo ($fields_location == "woocommerce_after_add_to_cart_form") ? "checked" : ""; ?>/> <?php esc_html_e( 'After Add To Cart Form', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" class="wcff-fields-product-location-radio" name="wcff_options[field_location]" value="woocommerce_before_single_product_summary" <?php echo ($fields_location == "woocommerce_before_single_product_summary") ? "checked" : ""; ?>/> <?php esc_html_e( 'Before Product Summary', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" class="wcff-fields-product-location-radio" name="wcff_options[field_location]" value="woocommerce_after_single_product_summary" <?php echo ($fields_location == "woocommerce_after_single_product_summary") ? "checked" : ""; ?>/> <?php esc_html_e( 'After Product Summary', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" class="wcff-fields-product-location-radio" name="wcff_options[field_location]" value="woocommerce_single_product_summary" <?php echo ($fields_location == "woocommerce_single_product_summary") ? "checked" : ""; ?>/> <?php esc_html_e( 'Product Summary', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" class="wcff-fields-product-location-radio" name="wcff_options[field_location]" value="woocommerce_single_product_tab" <?php echo ($fields_location == "woocommerce_single_product_tab") ? "checked" : ""; ?>/> <?php esc_html_e( 'Product Tab', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" class="wcff-fields-product-location-radio" name="wcff_options[field_location]" value="woocommerce_product_meta_start" <?php echo ($fields_location == "woocommerce_product_meta_start") ? "checked" : ""; ?>/> <?php esc_html_e( 'Before Product Meta', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" class="wcff-fields-product-location-radio" name="wcff_options[field_location]" value="woocommerce_product_meta_end" <?php echo ($fields_location == "woocommerce_product_meta_end") ? "checked" : ""; ?>/> <?php esc_html_e( 'After Product Meta', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" class="wcff-fields-product-location-radio" name="wcff_options[field_location]" value="woocommerce_product_custom_location" <?php echo ($fields_location == "woocommerce_product_custom_location") ? "checked" : ""; ?>/> <?php esc_html_e( 'Custom Location', 'wc-fields-factory' ); ?></label></li>								
							</ul>						
							<div id="wcff-fields-product-location-custom" style="display:<?php echo ($fields_location == "woocommerce_product_custom_location") ? "block" : "none"; ?>"><input type="type" name="wcff_options[custom_product_fields_location]" value="<?php echo $custom_product_fields_location; ?>" placeholder="Your custom product fields location action" /></div>
						</div>
					</td>
				</tr>	
				<tr id="wcff-product-tab-config">
					<td class="summary">
						<label><?php esc_html_e( 'Product Tab Config', 'wc-fields-factory' ); ?></label>
						<p class="description"><?php esc_html_e( 'New tab will be inserted on the Product Tab, and all the custom fields will be injected on it.<br/> Enter a title for that product tab and the priority ( 10,20 30... Enter 0 if you want this tab at first )', 'wc-fields-factory' ); ?></p>
					</td>
					<td>
						<div class="wcff-field-types-meta">							
							<label><?php esc_html_e('Tab Title', 'wc-fields-factory'); ?></label>
							<input type="text" name="wcff_options[product_tab_title]" placeholder="eg. Customize This Product" value="<?php echo esc_attr($ptab_title); ?>" />								
							<label><?php esc_html_e('Tab Priority', 'wc-fields-factory'); ?></label>
							<input type="number" name="wcff_options[product_tab_priority]" value="<?php echo esc_attr($ptab_priority); ?>" />													
						</div>
					</td>
				</tr>	
				<tr>
					<td class="summary">
						<label for="post_type"><?php esc_html_e( 'Product Location Priority', 'wc-fields-factory' ); ?></label>
						<p class="description"><?php esc_html_e( 'Set custom priority for product location action', 'wc-fields-factory' ); ?></p>
					</td>
					<td>
						<div class="wcff-field-types-meta">
						<input type="text" name="wcff_options[product_priority]" value="<?php echo esc_attr($product_priority); ?>" placeholder="Your custom priority number"/>
						</div>
					</td>
				</tr>	
				<tr>
					<td class="summary">
						<label for="post_type"><?php esc_html_e( 'Display on Archive', 'wc-fields-factory' ); ?></label>
						<p class="description"><?php esc_html_e( 'Display custom fields on Archive page as well', 'wc-fields-factory' ); ?></p>
					</td>
					<td>
						<div class="wcff-field-types-meta">
							<ul class="wcff-field-layout-horizontal">
								<li><label><input type="radio" class="wcff-fields-on-archive-radio" name="wcff_options[fields_on_archive]" value="yes" <?php echo ($fields_on_archive == "yes") ? "checked" : ""; ?>/> <?php esc_html_e( 'Yes', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" class="wcff-fields-on-archive-radio" name="wcff_options[fields_on_archive]" value="no" <?php echo ($fields_on_archive == "no") ? "checked" : ""; ?>/> <?php esc_html_e( 'No', 'wc-fields-factory' ); ?></label></li>
							</ul>						
						</div>
					</td>
				</tr>	
				
				<tr id="wcff-archive-location-option-tr" style="display: <?php echo ($fields_on_archive == "yes") ? "table-row" : "none"; ?>;">
					<td class="summary">
						<label><?php esc_html_e( 'Fields Location', 'wc-fields-factory' ); ?></label>
						<p class="description"><?php esc_html_e( 'Choose where the fields should be displayed on archive page', 'wc-fields-factory' ); ?></p>
					</td>
					<td>
						<div class="wcff-field-types-meta">
							<ul class="wcff-field-layout-horizontal wcff-field-location-on-archive">
								<li><label><input type="radio" class="wcff-fields-archive-location-radio" name="wcff_options[field_archive_location]" value="woocommerce_before_shop_loop_item" <?php echo ($fields_archive_location == "woocommerce_before_shop_loop_item") ? "checked" : ""; ?>/> <?php esc_html_e( 'Before Shop Loop Item', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" class="wcff-fields-archive-location-radio" name="wcff_options[field_archive_location]" value="woocommerce_before_shop_loop_item_title" <?php echo ($fields_archive_location == "woocommerce_before_shop_loop_item_title") ? "checked" : ""; ?>/> <?php esc_html_e( 'Before Shop Loop Item Title', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" class="wcff-fields-archive-location-radio" name="wcff_options[field_archive_location]" value="woocommerce_shop_loop_item_title" <?php echo ($fields_archive_location == "woocommerce_shop_loop_item_title") ? "checked" : ""; ?>/> <?php esc_html_e( 'Shop Loop Item Title', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" class="wcff-fields-archive-location-radio" name="wcff_options[field_archive_location]" value="woocommerce_after_shop_loop_item_title" <?php echo ($fields_archive_location == "woocommerce_after_shop_loop_item_title") ? "checked" : ""; ?>/> <?php esc_html_e( 'After Shop Loop Item Title', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" class="wcff-fields-archive-location-radio" name="wcff_options[field_archive_location]" value="woocommerce_after_shop_loop_item" <?php echo ($fields_archive_location == "woocommerce_after_shop_loop_item") ? "checked" : ""; ?>/> <?php esc_html_e( 'Affter Shop Loop Item', 'wc-fields-factory' ); ?></label></li>						
								<li><label><input type="radio" class="wcff-fields-archive-location-radio" name="wcff_options[field_archive_location]" value="woocommerce_archive_custom_location" <?php echo ($fields_archive_location == "woocommerce_archive_custom_location") ? "checked" : ""; ?>/> <?php esc_html_e( 'Custom Location', 'wc-fields-factory' ); ?></label></li>						
							</ul>	
							<div id="wcff-fields-archive-location-custom" style="display:<?php echo ($fields_archive_location == "woocommerce_archive_custom_location") ? "block" : "none"; ?>"><input type="type" name="wcff_options[custom_archive_fields_location]" value="<?php echo $custom_archive_fields_location; ?>" placeholder="Your custom archive fields location action" /></div>					
						</div>
					</td>
				</tr>

				<tr>
					<td class="summary">
						<label for="post_type"><?php esc_html_e( 'Product Location Priority', 'wc-fields-factory' ); ?></label>
						<p class="description"><?php esc_html_e( 'Set custom priority for product location action', 'wc-fields-factory' ); ?></p>
					</td>
					<td>
						<div class="wcff-field-types-meta">
						<input type="text" name="wcff_options[archive_priority]" value="<?php echo esc_attr($archive_priority); ?>" placeholder="Your custom priority number"/>
						</div>
					</td>
				</tr>	
					
				<tr>
					<td class="summary">
						<label><?php esc_html_e( 'Fields Cloning', 'wc-fields-factory' ); ?></label>
						<p class="description"><?php esc_html_e( 'Display custom fields per product count. Whenever user increases the product quantity, all custom fields will be cloned.!', 'wc-fields-factory' ); ?></p>
					</td>
					<td>
						<div class="wcff-field-types-meta">
							<ul class="wcff-field-layout-horizontal">
								<li><label><input type="radio" class="wcff-option-cloning-radio" name="wcff_options[fields_cloning]" value="yes" <?php echo ($fields_cloning == "yes") ? "checked" : ""; ?>/> <?php esc_html_e( 'Yes', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" class="wcff-option-cloning-radio" name="wcff_options[fields_cloning]" value="no" <?php echo ($fields_cloning == "no") ? "checked" : ""; ?>/> <?php esc_html_e( 'No', 'wc-fields-factory' ); ?></label></li>
							</ul>						
						</div>
					</td>
				</tr>	
				<tr id="wcff-option-cloning-title-row" style="display: <?php echo ($fields_cloning == "yes") ? 'table-row' : 'none'; ?>;">
					<td class="summary">
						<label for="post_type"><?php esc_html_e('Cloning Title', 'wc-fields-factory'); ?></label>
						<p class="description"><?php esc_html_e('If "Fields Cloning" enabled, then you can assign a title for fields group.<br/> This is will be used incase you are not opted for showing corresponding group titles', 'wc-fields-factory'); ?></p>
					</td>
					<td>
						<div class="wcff-field-types-meta">
							<input type="text" name="wcff_options[global_cloning_title]" value="<?php echo esc_attr($cloning_title); ?>" style="<?php echo (($enable_multilingual == "yes") && (count($supported_locale) > 0)) ? 'width: 93%;' : ''; ?>" placeholder="eg. Addiotnal Options : "/>
							<?php 
							if (($enable_multilingual == "yes") && (count($supported_locale) > 0)) { ?>
								<button class="wcff-factory-multilingual-btn" title="Open Multilingual Panel"><img src="<?php echo esc_url(get_home_url()) .'/wp-content/plugins/wc-fields-factory/assets/img/translate.png'; ?>" /></button>
								<?php 
							}													
							if ($enable_multilingual == "yes") {
								include_once( dirname(dirname(__FILE__)). '/includes/wcff_locale.php');
								$l_manager = new Wcff_Locale();
								$locales = $l_manager->get_locales();
								if (count($supported_locale) > 0) {
									echo '<div class="wcff-locale-list-wrapper" style="display: none;">';
									foreach ($supported_locale as $code) { 									
									    $grp_title = (isset($wcff_options["global_cloning_title". esc_attr($code)])) ? $wcff_options["global_cloning_title". esc_attr($code)] : ""; ?>			
									<label>Cloning Group Title for <?php echo esc_html($locales[$code]); ?></label>						
									<input type="text" name="wcff_options[global_cloning_title_<?php echo esc_attr($code); ?>]" value="<?php echo esc_attr($grp_title); ?>" />										
									<?php 
									}
									echo '<div>';
								}								
							}							
							?>																							
						</div>
					</td>
				</tr>							
				<tr style="display: none;">
					<td class="summary">
						<label><?php esc_html_e('Group Meta', 'wc-fields-factory'); ?></label>
						<p class="description"><?php esc_html_e('Custom meta data will be grouped and displayed in cart & checkout. won\'t work if group fields option choosed.', 'wc-fields-factory'); ?></p>
					</td>
					<td>
						<div class="wcff-field-types-meta">
							<ul class="wcff-field-layout-horizontal">
								<li><label><input type="radio" name="wcff_options[group_meta_on_cart]" value="yes" <?php echo ($group_meta_on_cart == "yes") ? "checked" : ""; ?>/> <?php esc_html_e('Yes', 'wc-fields-factory'); ?></label></li>
								<li><label><input type="radio" name="wcff_options[group_meta_on_cart]" value="no" <?php echo ($group_meta_on_cart == "no") ? "checked" : ""; ?>/> <?php esc_html_e('No', 'wc-fields-factory'); ?></label></li>
							</ul>						
						</div>
					</td>
				</tr>
				<tr style="display: none;">
					<td class="summary">
						<label><?php esc_html_e('Group Fields', 'wc-fields-factory'); ?></label>
						<p class="description"><?php esc_html_e('Custom fields will be grouped ( within each line item, per count ) and displayed in cart & checkout.', 'wc-fields-factory'); ?></p>
					</td>
					<td>
						<div class="wcff-field-types-meta">
							<ul class="wcff-field-layout-horizontal">
								<li><label><input type="radio" name="wcff_options[group_fields_on_cart]" value="yes" <?php echo ($group_fields_on_cart == "yes") ? "checked" : ""; ?>/> <?php esc_html_e( 'Yes', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" name="wcff_options[group_fields_on_cart]" value="no" <?php echo ($group_fields_on_cart == "no") ? "checked" : ""; ?>/> <?php esc_html_e( 'No', 'wc-fields-factory' ); ?></label></li>
							</ul>						
						</div>
					</td>
				</tr>	
				<tr style="display: none;">
					<td class="summary">
						<label><?php esc_html_e( 'Show Group Title', 'wc-fields-factory' ); ?></label>
						<p class="description"><?php esc_html_e( 'Whether to show the group title for each fields group.', 'wc-fields-factory' ); ?></p>
					</td>
					<td>
						<div class="wcff-field-types-meta">
							<ul class="wcff-field-layout-horizontal">
								<li><label><input type="radio" name="wcff_options[show_group_title]" value="yes" <?php echo ($show_field_group_title == "yes") ? "checked" : ""; ?>/> <?php esc_html_e( 'Yes', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" name="wcff_options[show_group_title]" value="no" <?php echo ($show_field_group_title == "no") ? "checked" : ""; ?>/> <?php esc_html_e( 'No', 'wc-fields-factory' ); ?></label></li>
							</ul>						
						</div>
					</td>
				</tr>					
				<tr>
					<td class="summary">
						<label><?php esc_html_e( 'Client Side Validation', 'wc-fields-factory' ); ?></label>
						<p class="description"><?php esc_html_e( 'Whether the validation should be done on Client Side.?', 'wc-fields-factory' ); ?></p>
					</td>
					<td>
						<div class="wcff-field-types-meta">							
							<ul class="wcff-field-layout-horizontal">
								<li><label><input type="radio" name="wcff_options[client_side_validation]" value="yes" <?php echo ($client_side_validation == "yes") ? "checked" : ""; ?>/> <?php esc_html_e( 'Yes', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" name="wcff_options[client_side_validation]" value="no" <?php echo ($client_side_validation == "no") ? "checked" : ""; ?>/> <?php esc_html_e( 'No', 'wc-fields-factory' ); ?></label></li>
							</ul>						
						</div>
					</td>
				</tr>		
				<tr>
					<td class="summary">
						<label><?php esc_html_e( 'Client Side Validation Type', 'wc-fields-factory' ); ?></label>
						<p class="description"><?php esc_html_e( 'Choose whether the validation done on field level ( on blur ) or while form submit', 'wc-fields-factory' ); ?></p>
					</td>
					<td>
						<div class="wcff-field-types-meta">							
							<ul class="wcff-field-layout-horizontal">
								<li><label><input type="radio" name="wcff_options[client_side_validation_type]" value="submit" <?php echo ($client_side_validation_type == "submit") ? "checked" : ""; ?>/> <?php esc_html_e( 'On Product Submit', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" name="wcff_options[client_side_validation_type]" value="blur" <?php echo ($client_side_validation_type == "blur") ? "checked" : ""; ?>/> <?php esc_html_e( 'On Blur [ + Product Submit ]', 'wc-fields-factory' ); ?></label></li>
							</ul>						
						</div>
					</td>
				</tr>	
				<tr>
					<td class="summary">
						<label><?php esc_html_e( 'Authorized Users Only', 'wc-fields-factory' ); ?></label>
						<p class="description"><?php esc_html_e( 'Show fields only if user has logged-in', 'wc-fields-factory' ); ?></p>
					</td>
					<td>
						<div class="wcff-field-types-meta">							
							<ul class="wcff-field-layout-horizontal">
								<li><label><input type="radio" name="wcff_options[show_login_user_only]" value="yes" <?php echo ($is_show_login_user_only == "yes") ? "checked" : ""; ?>/> <?php esc_html_e( 'Yes', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" name="wcff_options[show_login_user_only]" value="no" <?php echo ($is_show_login_user_only == "no") ? "checked" : ""; ?>/> <?php esc_html_e( 'No', 'wc-fields-factory' ); ?></label></li>
							</ul>						
						</div>
					</td>
				</tr>	
				<tr>
					<td class="summary">
						<label><?php esc_html_e( 'Enable Cart Editable', 'wc-fields-factory' ); ?></label>
						<p class="description"><?php esc_html_e( 'Make all fields editable on cart', 'wc-fields-factory' ); ?></p>
					</td>
					<td>
						<div class="wcff-field-types-meta">							
							<ul class="wcff-field-layout-horizontal">
								<li><label><input type="radio" name="wcff_options[edit_field_value_cart_page]" value="yes" <?php echo ($edit_field_value_cart_page == "yes") ? "checked" : ""; ?>/> <?php esc_html_e( 'Yes', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" name="wcff_options[edit_field_value_cart_page]" value="no" <?php echo ($edit_field_value_cart_page == "no") ? "checked" : ""; ?>/> <?php esc_html_e( 'No', 'wc-fields-factory' ); ?></label></li>
							</ul>						
						</div>
					</td>
				</tr>
				
				<tr>
					<td class="summary">
						<label><?php esc_html_e( 'Enable Custom Pricing', 'wc-fields-factory' ); ?></label>
						<p class="description"><?php esc_html_e( 'Enable custom pricing module', 'wc-fields-factory' ); ?></p>
					</td>
					<td>
						<div class="wcff-field-types-meta">							
							<ul class="wcff-field-layout-horizontal">
								<li><label><input type="radio" name="wcff_options[enable_custom_pricing]" value="yes" <?php echo ($custom_pricing_module == "yes") ? "checked" : ""; ?>/> <?php esc_html_e( 'Yes', 'wc-fields-factory' ); ?></label></li>
								<li><label><input type="radio" name="wcff_options[enable_custom_pricing]" value="no" <?php echo ($custom_pricing_module == "no") ? "checked" : ""; ?>/> <?php esc_html_e( 'No', 'wc-fields-factory' ); ?></label></li>
							</ul>						
						</div>
					</td>
				</tr>	
								
				<tr>
					<td class="summary">
						<label><?php esc_html_e( 'Pricing Rules Behaviour', 'wc-fields-factory' ); ?></label>
						<p class="description"><?php esc_html_e( 'Pricing rules setting.', 'wc-fields-factory' ); ?></p>
					</td>
					<td>
						<div class="wcff-field-types-meta">			
								<div class="wcff-factory-tab-container">
									<div class="wcff-factory-tab-left-panel">
										<ul>
											<li data-box="#wcff-real-time-pricing" class="selected"><label><?php esc_html_e( 'Update Price.?', 'wc-fields-factory' ); ?></label></li>											
											<li data-box="#wcff-pricing-title-shows"><label><?php esc_html_e( 'Price Rule Title', 'wc-fields-factory' ); ?></label></li>
											<li data-box="#wcff-wc-price-tag-selector"><label><?php esc_html_e( 'Price Tag Selector', 'wc-fields-factory' ); ?></label></li>
										</ul>
									</div>
									<div class="wcff-factory-tab-right-panel wcff-panel-tab-right-container">
										<div id="wcff-real-time-pricing" class="wcff-factory-tab-content" style="display: block;">
											<ul class="wcff-field-layout-horizontal">
												<li><label><input type="radio" name="wcff_options[enable_ajax_pricing_rules]" value="enable" <?php echo ($wcff_ajax_pricing_rules == "enable") ? "checked" : ""; ?>/> <?php esc_html_e( 'Yes', 'wc-fields-factory' ); ?></label></li>
												<li><label><input type="radio" name="wcff_options[enable_ajax_pricing_rules]" value="disable" <?php echo ($wcff_ajax_pricing_rules == "disable") ? "checked" : ""; ?>/> <?php esc_html_e( 'No', 'wc-fields-factory' ); ?></label></li>
											</ul>
											<p class="description"><?php esc_html_e( 'Updating the product price at the real time in product page.', 'wc-fields-factory' ); ?></p>
										</div>										
										<div id="wcff-pricing-title-shows" class="wcff-factory-tab-content">
											<div class="">												
    											<ul class="wcff-field-layout-horizontal">
    												<li><label><input type="radio" class="wcff-pricing-rules-container-option-radio" name="wcff_options[pricing_rules_details]" value="show" <?php echo ($wcff_show_pricing_details_container == "show") ? "checked" : ""; ?>/> <?php esc_html_e( 'Show', 'wc-fields-factory' ); ?></label></li>
    												<li><label><input type="radio" class="wcff-pricing-rules-title-option-radio" name="wcff_options[pricing_rules_details]" value="hide" <?php echo ($wcff_show_pricing_details_container == "hide") ? "checked" : ""; ?>/> <?php esc_html_e( 'Hide', 'wc-fields-factory' ); ?></label></li>
    											</ul>
												<p class="description"><?php esc_html_e( 'Pricing rule details, want to show in product, cart & checkout page.', 'wc-fields-factory' ); ?></p>
											</div>											
										</div>
										<div id="wcff-wc-price-tag-selector" class="wcff-factory-tab-content">
											<ul class="wcff-field-layout-horizontal">
												<li><label><input type="radio" class="wcff-pricing-rules-rules-price-container" name="wcff_options[ajax_pricing_rules_price_container]" value="default" <?php echo ($wcff_ajax_pricing_rules_price_container == "default") ? "checked" : ""; ?>/> <?php esc_html_e( 'Default', 'wc-fields-factory' ); ?></label></li>
												<li><label><input type="radio" class="wcff-pricing-rules-rules-price-container" name="wcff_options[ajax_pricing_rules_price_container]" value="custom" <?php echo ($wcff_ajax_pricing_rules_price_container == "custom") ? "checked" : ""; ?>/> <?php esc_html_e( 'Custom', 'wc-fields-factory' ); ?></label></li>
												<!--<li><label><input type="radio" class="wcff-pricing-rules-rules-price-container" name="wcff_options[ajax_pricing_rules_price_container]" value="both" <?php echo ($wcff_ajax_pricing_rules_price_container == "both") ? "checked" : ""; ?>/> <?php esc_html_e( 'Both', 'wc-fields-factory' ); ?></label></li> -->
											</ul>
											<div style="<?php echo ($wcff_ajax_pricing_rules_price_container == "default") ? "display: none;" : ""; ?>" id="wcff-pricing-rules-rules-price-container">
												<label><input type="text" name="wcff_options[ajax_price_replace_container]" placeholder="<?php esc_attr_e( 'Custom ID or Class', 'wc-fields-factory' ); ?>" value="<?php echo $wcff_ajax_price_replace_container; ?>" /><p class="description"><?php esc_html_e( 'If the pricing element has different id or class then the default woocommerce (prepend # for id and . for class)', 'wc-fields-factory' ); ?></p> </label>
											</div>
											<p class="description"><?php esc_html_e( 'In single product page replace old price into negotiated price element.', 'wc-fields-factory' ); ?></p>
										</div>
									</div>
								</div>
						</div>
					</td>
				</tr>
				<tr>
					<td class="summary">
						<label><?php esc_html_e( 'Enable Multilingual', 'wc-fields-factory' ); ?></label>
						<p class="description"><?php esc_html_e( 'Enable multi language option for fields labels, options, placeholders and validation messages', 'wc-fields-factory' ); ?></p>
					</td>
					
					<td>
						<ul class="wcff-field-layout-horizontal">
							<li><label><input type="radio" name="wcff_options[enable_multilingual]" value="yes" <?php echo ($enable_multilingual == "yes") ? "checked" : ""; ?> class="wcff-multilingual-option-radio" /> <?php esc_html_e( 'Yes', 'wc-fields-factory' ); ?></label></li>
							<li><label><input type="radio" name="wcff_options[enable_multilingual]" value="no" <?php echo ($enable_multilingual == "no") ? "checked" : ""; ?> class="wcff-multilingual-option-radio" /> <?php esc_html_e( 'No', 'wc-fields-factory' ); ?></label></li>
						</ul>	
						<div id="wcff-multilingual-locale-list" style="<?php echo ($enable_multilingual == "yes") ? "display: block;" : "display: none;"; ?>">

							<label><?php esc_html_e( 'Choose default language', 'wc-fields-factory' ); ?></label>

							<select id="wcff-option-default-locale-selector" name="wcff_options[default_locale]">
							<?php 
								$locales = wcff()->locale->get_locales();
								foreach ($locales as $code => $title) {
									$selected = ($default_locale == $code) ? 'selected="selected"' : '';
									echo '<option value="'. esc_attr($code) .'" '. $selected .'>'. esc_html($title) .'</option>';
								}
							?>
							</select>

							<label><?php esc_html_e( 'Choose supported languages', 'wc-fields-factory' ); ?></label>
							<ul class="wcff-field-layout-horizontal wcff-multilingual-choser-ul" id="wcff-multilingual-choser-ul">
							<?php 
								$locales = wcff()->locale->get_locales();
								foreach ($locales as $code => $title) {
									if ($default_locale != $code) {
										echo '<li><label><input type="checkbox" '. (in_array($code, $supported_locale) ? "checked" : "") .' name="wcff_options[supported_lang][]" value="'. esc_attr($code) .'"/> '. esc_html($title) .'</label></li>';
									}							    	
							    }
							?>							
							</ul>
						</div>						
					</td>
				</tr>	
										
			</table>			
			<p class="submit">
				<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'wc-fields-factory' ); ?>" />
			</p>
		</form>
	</div>
	
	<script type="text/javascript">
		(function($) {		
			$(document).ready(function() {

				$(".wcff-fields-product-location-radio").on("change", function() {					

					if ($(this).is(":checked") && $(this).val() == "woocommerce_product_custom_location") {
						$("#wcff-fields-product-location-custom").fadeIn("normal");
					} else {
						$("#wcff-fields-product-location-custom").fadeOut("normal");
					}				

				});

				$(".wcff-fields-archive-location-radio").on("change", function() {
					if ($(this).is(":checked") && $(this).val() == "woocommerce_archive_custom_location") { 
						$("#wcff-fields-archive-location-custom").fadeIn("normal");
					} else {
						$("#wcff-fields-archive-location-custom").fadeOut("normal");
					}	 				
				});

				$(".wcff-fields-on-archive-radio").on("change", function() {
					if ($(this).is(":checked") &&  $(this).val() === "yes") {
						$("#wcff-archive-location-option-tr").fadeIn();
					} else {
						$("#wcff-archive-location-option-tr").fadeOut();
					}
				});

				$(".wcff-multilingual-option-radio").on("change", function() {
					if ($(this).is(":checked") && $(this).val() === "yes") {
						$("#wcff-multilingual-locale-list").fadeIn();
					} else {
						$("#wcff-multilingual-locale-list").fadeOut();
					}
				});

				$(document).on("click", "button.wcff-factory-multilingual-btn", function(e) {
					$(this).next().toggle("normal");
					e.preventDefault();
					e.stopPropagation();
				});

				/* Click hanlder tab headers */
				$(document).on("click", "div.wcff-factory-tab-left-panel li", this, function(e) {					
					$(this).parent().parent().next().find(">div").hide()
					$(this).parent().find("> li").removeClass();
					$(this).addClass("selected");			
					$(this).parent().parent().next().find(">div:nth-child("+ ($(this).index() + 1) +")").show();
				});	

				$(".wcff-pricing-rules-title-option-radio").on("change", function() {
					if ($(this).is(":checked") && $(this).val() === "show") {
						$("#wcff-pricing-rules-title-option-field").fadeIn();
					} else {
						$("#wcff-pricing-rules-title-option-field").fadeOut();
					}
				});

				$(".wcff-pricing-rules-rules-price-container").on("change", function() {
					if ($(this).is(":checked") && ($(this).val() === "custom" || $(this).val() === "both")) {
						$("#wcff-pricing-rules-rules-price-container").fadeIn();
					} else {
						$("#wcff-pricing-rules-rules-price-container").fadeOut();
					}
				});

				$(document).on("change", "input.wcff-option-cloning-radio", function(e) {
					if ($(this).is(":checked") && $(this).val() == "yes") {
						$("#wcff-option-cloning-title-row").show();
					} else {
						$("#wcff-option-cloning-title-row").hide();
					}
				});

				$(document).on("change", "#wcff-option-default-locale-selector", function(e) {

					var request = {
						method	 	: "GET",
						context 	: "fetch_supported_locales",
						post 		: 0,
						post_type 	: "wccpf",
						payload 	: {"default_locale": $(this).val()},
					};

					$.ajax({  
						type       : "POST",  
						data       : {action : "wcff_ajax", wcff_param : JSON.stringify(request)},  
						dataType   : "json",  
						url        : "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",  
						beforeSend : function(){  

						},
						success    : function(_res) {	
							reloadSupportedLocaleList(_res.data);
						}, 
						error      : function(jqXHR, textStatus, errorThrown) {       
							alert(jqXHR, textStatus, errorThrown);
						}
					});

				});

			});

			function reloadSupportedLocaleList(_locales) {

				var _html = "",
					keys = [],
					checked = "",
					supportedLocales = jQuery("[name='wcff_options[supported_lang][]']:checked").map(function () {
					return this.value;
				}).get();
					
				keys = Object.keys(_locales);
				for (let i = 0; i < keys.length; i++) {
					checked = (supportedLocales.indexOf(keys[i]) != -1) ? "checked" : "";
					_html += '<li><label><input type="checkbox" '+ checked +' name="wcff_options[supported_lang][]" value="'+ keys[i] +'"/> '+ _locales[keys[i]] +'</label></li>';

				}

				$("#wcff-multilingual-choser-ul").html(_html);

			}

		})(jQuery);

	</script>
	
<?php 

}

?>