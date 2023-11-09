<?php
/*
*  Meta box - Custom Product Fields
*  Template for creating or updating custom product fields
*/

if (!defined('ABSPATH')) { exit; }

global $post; ?>

<div class="wcff-fatory-lister-tab">

	<?php if ($post->post_type != "wcccf") : ?>

	<div class="wcff-factory-lister-tab-header">
		<a href="#wcff-fields-lister-container" title="Fields" class="selected">Fields</a>
		<a href="#wcff-fields-layout-container" title="Layout">Layout</a>
	</div>
	
	<?php endif; ?>
	
	<div class="wcff-factory-lister-tab-content">
		<div id="wcff-fields-lister-container" style="display: block;">
			<!-- Fields Header -->
            <div class="fields_header">
            	<table class="wcff_table">
            		<thead>
            			<tr>
            				<th class="field-order"></th>
            				<th class="field-label"><?php esc_html_e( 'Field Label', 'wc-fields-factory' ); ?></th>
							<th class="field-name"><?php esc_html_e( 'Field Key', 'wc-fields-factory' ); ?></th>
            				<th class="field-type"><?php esc_html_e( 'Field Type', 'wc-fields-factory' ); ?></th>		
            				<th class="field-actions"><?php esc_html_e( 'Actions', 'wc-fields-factory' ); ?></th>			
            			</tr>
            		</thead>
            	</table>
            </div>
            <!-- / Fields Header -->
            
            <div class="fields">            	
            	<div id="wcff-fields-set" class="sortable ui-sortable">
            	<div id="wcff-add-field-placeholder">
            		<img src="<?php echo esc_url(wcff()->info["assets"]); ?>/img/add.png" alt="Add Field" />
            		<span class="wcff-add-here-label"><strong><?php esc_html_e( 'Drop here.!', 'wc-fields-factory' ); ?></strong></span>
            		<br>
            		<?php esc_html_e( '--- Drog any field from the field type box (right side) and drop here. ---', 'wc-fields-factory' ); ?>
            	</div>	
            		<?php
            			$fields = null;
            			wcff()->dao->set_current_post_type($post->post_type);			
            			$fields = wcff()->dao->load_fields($post->ID);
            
            			if (is_array($fields)) {		
            				do_action("wcff_before_load_field_list", $post, $fields);
            				echo wcff()->builder->build_wcff_fields_lister($fields);
            				do_action("wcff_after_load_field_list", $post, $fields);
            			} else {
            				$fields = array();	
            			}			
            		?>
            		
            	</div>
            	
            	<div id="wcff-empty-field-set" style="display:<?php echo count($fields) < 1 ? 'block' : 'none'; ?>">
            		<?php
            		     if ($post->post_type == "wccpf") {
            		         esc_html_e('Zero product fields.!', 'wc-fields-factory');
            		     } else if($post->post_type == "wccaf") {
            		         esc_html_e('Zero admin fields.!', 'wc-fields-factory');
            		     } else if($post->post_type == "wccvf") {
            		         esc_html_e('Zero variation fields.!', 'wc-fields-factory');
            		     } else if($post->post_type == "wcccf") {
            		         esc_html_e('Zero checkout fields.!', 'wc-fields-factory');
            		     } else {
            		         /* Ignore */
            		     }
            		?>
            	</div>	
            </div>		
		</div>
		<div id="wcff-fields-layout-container">
			<?php 
			
			$layout_meta = wcff()->dao->load_layout_meta($post->ID);
			$use_custom_layout = wcff()->dao->load_use_custom_layout($post->ID);
			
			?>
			<table class="wcff-layout-designer">
				<tr>
					<td class="field-list-col">
						<div class="wcff-layout-pref-row">        						
        						<label class="wcff-toggle-switch wcff-toggle-switch-left-right">
                                	<input class="wcff-toggle-switch-input" name="wcff_use_custom_layout" type="checkbox" <?php echo $use_custom_layout == "yes" ? "checked" : ""; ?>/>
                                	<span class="wcff-toggle-switch-label" data-on="On" data-off="Off"></span> 
                                	<span class="wcff-toggle-switch-handle"></span> 
                                </label>
        					</div>
						<div id="wcff-layout-designer-field-list">
        					
        				</div>
					</td>
					<td class="designer-col">
						<div id="wcff-layout-designer-pad">
        					<div class="wcff-layout-form-row">
        						
        					</div>	
        				</div>	
					</td>					
				</tr>
			</table>
			<input type="hidden" id="wcff_layout_meta" name="wcff_layout_meta" value='<?php echo esc_attr(json_encode($layout_meta)); ?>' />
		</div>		
	</div>
	
</div>


<!-- Hidden Fields -->
<input type="hidden" name="wcff_nonce" value="<?php echo wp_create_nonce( 'field_group' ); ?>" />
<input type="hidden" name="wcff_dirty_fields_configuration" id="wcff_dirty_fields_configuration" value=""/>
<!-- / Hidden Fields -->
