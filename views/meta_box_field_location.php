<?php

if (!defined('ABSPATH')) { exit; }

global $post;
$fields_location = get_post_meta($post->ID, $post->post_type ."_field_location_on_product", true);
$fields_location_archive = get_post_meta($post->ID, $post->post_type ."_field_location_on_archive", true);

//$ptab_title = get_post_meta($post->ID, $post->post_type ."_product_tab_title", true);
//$ptab_priority = get_post_meta($post->ID, $post->post_type ."_product_tab_priority", true);

/* Product page location hooks list */
$single_product_template_locations = apply_filters("wcff_single_product_template_locations", array (
    "woocommerce_before_add_to_cart_button" => __("Before Add To Cart Button", "wc-fields-factory"),
    "woocommerce_after_add_to_cart_button" => __("After Add To Cart Button", "wc-fields-factory"),
    "woocommerce_before_add_to_cart_form" => __("Before Add To Cart Form", "wc-fields-factory"),
    "woocommerce_after_add_to_cart_form" => __("After Add To Cart Form", "wc-fields-factory"),
    "woocommerce_before_single_product_summary" => __("Before Product Summary", "wc-fields-factory"),
    "woocommerce_after_single_product_summary" => __("After Product Summary", "wc-fields-factory"),
    "woocommerce_single_product_summary" => __("Product Summary", "wc-fields-factory"),
    "woocommerce_product_meta_start" => __("Before Product Meta", "wc-fields-factory"),
    "woocommerce_product_meta_end" => __("After Product Meta", "wc-fields-factory"),
    "woocommerce_single_product_tab" => __("Product Tab", "wc-fields-factory")
));

/* Archive page location hooks list */
$archive_product_template_locations = apply_filters("wcff_archive_product_template_locations", array (
    "woocommerce_before_shop_loop_item" => __("Before Product Content", "wc-fields-factory"),
    "woocommerce_before_shop_loop_item_title" => __("Before Product Title", "wc-fields-factory"),
    "woocommerce_shop_loop_item_title" => __("After Product Title", "wc-fields-factory"),
    "woocommerce_after_shop_loop_item_title" => __("After Product Price", "wc-fields-factory"),
    "woocommerce_after_shop_loop_item" => __("After Product Content", "wc-fields-factory")
));		

?>

<div class="wcff_logic_wrapper">
	<table class="wcff_table">
		<tbody>
			<tr>
				<td class="summary">
					<label for="post_type"><?php esc_html_e("Rules", "wc-fields-factory"); ?></label>
					<p class="description"><?php esc_html_e("Select location for Archive product page and Single product page. Note: (On product page if you want use global setting to check \"Use global setting location\" and archive page don't want to show anywhere then check \"none\", <strong>Please don't use file field on archive page</strong>)", "wc-fields-factory"); ?></p>
				</td>
				<td>
					<div class="wcff-field-types-meta">
						<h3><?php esc_html_e("Single Product Page", "wc-fields-factory"); ?></h3>
						<ul class="wcff-field-layout-horizontal wcff-field-location-on-product">						
							
							<li><label style="color: #96588a; font-weight: bold;"><input type="radio" class="wcff-fields-location-radio" name="field_location_on_product" value="use_global_setting" <?php echo ($fields_location == "use_global_setting" || $fields_location == "") ? "checked" : ""; ?>/> <?php esc_html_e("Use global setting location", "wc-fields-factory"); ?></label></li>							
							
							<?php foreach ($single_product_template_locations as $hook => $title) : ?>							
							<li><label><input type="radio" class="wcff-fields-location-radio" name="field_location_on_product" value="<?php echo esc_attr($hook); ?>" <?php echo ($fields_location == $hook) ? "checked" : ""; ?>/> <?php echo esc_html($title); ?></label></li>								
							<?php endforeach; ?>
												
						</ul>						
					</div>					
					
					<?php if (get_current_screen()->id != "wccvf") : ?>	
					
					<div class="wcff-field-types-meta">
						<h3><?php esc_html_e('Archive Product Page', 'wc-fields-factory'); ?></h3>
						<ul class="wcff-field-layout-horizontal wcff-field-location-on-product">
							
							<li><label><input type="radio" class="wcff-fields-location-radio" name="field_location_on_archive" value="none" <?php echo ( $fields_location_archive == "none" || $fields_location_archive == "" ) ? "checked" : ""; ?>/> <?php esc_html_e("None", "wc-fields-factory"); ?></label></li>
							
							<?php foreach ($archive_product_template_locations as $hook => $title) : ?>							
							<li><label><input type="radio" class="wcff-fields-location-radio" name="field_location_on_archive" value="<?php echo esc_attr($hook); ?>" <?php echo ($fields_location_archive == $hook) ? "checked" : ""; ?>/> <?php echo esc_html_e($title); ?></label></li>
							<?php endforeach; ?>
							
						</ul>						
					</div>
					
					<?php endif; ?>
					
				</td>
			</tr>
		</tbody>
	</table>	
	
</div>
