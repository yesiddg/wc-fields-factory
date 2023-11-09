<?php

if (!defined( 'ABSPATH')) { exit; }

$index = 0;
global $post;

$locations = apply_filters( "wcff/location/context", array(
	array("id" => "location_product_data", "title" => __("Product Tabs", "wc-fields-factory")),
	array("id" => "location_product", "title" => __("Product View", "wc-fields-factory")),
	array("id" => "location_order", "title" => __("Order View", "wc-fields-factory")),
	array("id" => "location_product_cat", "title" => __("Product Category View", "wc-fields-factory"))	
));

$rule = wcff()->dao->load_location_rules($post->ID);
$rule = json_decode( $rule, true );

?>

<div class="wcff_location_logic_wrapper">
	<table class="wcff_table">
		<tbody>
			<tr>
				<td class="summary">
					<label for="post_type"><?php esc_html_e( 'Rules', 'wc-fields-factory' ); ?></label>
					<p class="description"><?php esc_html_e( 'Add rules to determines which products or product categories will have this custom fields group', 'wc-fields-factory' ); ?></p>
				</td>
				<td>
					<div class="wcff_location_logic_groups">				
					
					<?php if ($rule) { ?>
																			
						<div class="wcff_location_logic_group"> 
							<h4><?php echo esc_html_e( 'Place this fields on', 'wc-fields-factory' ); ?></h4>
							<table class="wcff_table wcff_location_rules_table">
								<tbody>									
									<tr>
										<td>
											<select class="wcff_location_param select">
											<?php
											foreach ($locations as $location) {
												$selected = ($location["id"] == $rule["context"]) ? 'selected="selected"' : '';
												echo '<option value="'. esc_attr($location["id"]) .'" '. $selected .'>'. esc_html($location["title"]) .'</option>';
											}
											?>																			
											</select>
										</td>										
										<td class="location_value_td">
											<?php 												
											if (is_array($rule["endpoint"])) {	
												echo wcff()->builder->build_metabox_context_selector("wcff_location_metabox_context_value", $rule["endpoint"]["context"]);
												echo wcff()->builder->build_metabox_priority_selector("wcff_location_metabox_priorities_value", $rule["endpoint"]["priority"]);											
											} else {																		
												echo wcff()->builder->build_products_tabs_selector("wcff_location_product_data_value", $rule["endpoint"]);												
											}											
											?>																				
										</td>
									</tr>									
								</tbody>
							</table>

							<div id="wccaf_custom_product_data_tab_title_container" style="display: <?php echo (!is_array($rule["endpoint"]) && $rule["endpoint"] == "wccaf_custom_product_data_tab") ? "block" : "none"; ?>;">
								<label>Title for Custom Product Data Tab</label>&nbsp;&nbsp;
								<input type="text" name="wcff_custom_product_data_tab_title" id="wccaf_custom_product_data_tab_title" placeholder="Title" value="<?php echo esc_attr(wcff()->dao->load_custom_product_data_tab_title($post->ID)); ?>" />
								<input type="number" name="wcff_custom_product_data_tab_priority" id="wccaf_custom_product_data_tab_priority" placeholder="Priority" value="<?php echo esc_attr(wcff()->dao->load_custom_product_data_tab_priority($post->ID)); ?>" />
							</div>

						</div>					
					
					<?php } else { ?>					
						<div class="wcff_location_logic_group"> 
							<h4><?php esc_html_e( 'Place this admin fields group on the following locations', 'wc-fields-factory' ); ?></h4>
							<table class="wcff_table wcff_location_rules_table">
								<tbody>
									<tr>
										<td>
											<select class="wcff_location_param select">
												<?php foreach ($locations as $location) : ?>
													<option value="<?php echo esc_attr($location["id"]); ?>"><?php echo esc_html($location["title"]); ?></option>
												<?php endforeach; ?>																																				
											</select>
										</td>										
										<td class="location_value_td">
											<?php echo wcff()->builder->build_products_tabs_selector("wcff_location_product_data_value"); ?>											
										</td>										
									</tr>
								</tbody>
							</table>							
						</div>				
					<?php } ?>
						<!-- 
						<h4>or</h4>
						<a href="#" class="location-add-group button"><?php esc_html_e( 'Add location group', 'wc-fields-factory' ); ?></a>
						 -->	
					</div>
				</td>
			</tr>
		</tbody>
	</table>
	<input type="hidden" name="wcff_location_rules" id="wcff_location_rules" value="Sample Rules"/>
</div>