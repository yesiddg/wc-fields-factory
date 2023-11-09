<?php 

global $post;
global $wp_roles;

$all_roles = array();
foreach ($wp_roles->roles as $handle => $role) {
    $all_roles[] = $handle;
}

$group_clonable = get_post_meta($post->ID, $post->post_type ."_is_this_group_clonable", true);
$group_clonable = (!$group_clonable || $group_clonable == "") ? "yes" : $group_clonable;

$display_group_title = get_post_meta($post->ID, $post->post_type ."_show_group_title", true);
$display_group_title = (!$display_group_title || $display_group_title == "") ? "no" : $display_group_title;

$label_alignment = get_post_meta($post->ID, $post->post_type ."_fields_label_alignement", true);
$label_alignment = (!$label_alignment || $label_alignment == "") ? "left" : $label_alignment;


$authorized_only = get_post_meta($post->ID, $post->post_type ."_is_this_group_for_authorized_only", true);
$authorized_only = (!$authorized_only || $authorized_only == "") ? "no" : $authorized_only;

$targeted_roles = get_post_meta($post->ID, $post->post_type ."_wcff_group_preference_target_roles", true);
if (!$targeted_roles || $targeted_roles == "") {
    $targeted_roles = $all_roles;
} else {
    $targeted_roles = json_decode($targeted_roles, true);
}
$targeted_roles = (!$targeted_roles || $targeted_roles == "") ? $all_roles : $targeted_roles;


?>

<div class="wcff_logic_wrapper">
	<table class="wcff_table">
		<tr>
			<td class="summary">
				<label><?php esc_html_e( 'Cloning.?', 'wc-fields-factory' ); ?></label>
				<p class="description"><?php esc_html_e( 'Group level cloning, whether this fields group clonable.?', 'wc-fields-factory' ); ?></p>
			</td>
			<td>
				<div class="wcff-field-types-meta">
					<ul class="wcff-field-layout-horizontal">
						<li><label><input type="radio" class="wcff-group-clonable-radio" name="wcff_group_clonable_radio" value="yes" <?php echo ($group_clonable == "yes") ? "checked" : ""; ?>/> <?php esc_html_e( 'Yes', 'wc-fields-factory' ); ?></label></li>
						<li><label><input type="radio" class="wcff-group-clonable-radio" name="wcff_group_clonable_radio" value="no" <?php echo ($group_clonable == "no") ? "checked" : ""; ?>/> <?php esc_html_e( 'No', 'wc-fields-factory' ); ?></label></li>
					</ul>	
				</div>
			</td>
		</tr>
		<tr>
			<td class="summary">
				<label><?php esc_html_e( 'Display Group Title.?', 'wc-fields-factory' ); ?></label>
				<p class="description"><?php esc_html_e( 'Group level title, whether to show this (Title) fields group title on the front end.?', 'wc-fields-factory' ); ?></p>
			</td>
			<td>
				<div class="wcff-field-types-meta">
					<ul class="wcff-field-layout-horizontal">
						<li><label><input type="radio" class="wcff-group-title-radio" name="wcff_group_title_radio" value="yes" <?php echo ($display_group_title == "yes") ? "checked" : ""; ?>/> <?php esc_html_e( 'Yes', 'wc-fields-factory' ); ?></label></li>
						<li><label><input type="radio" class="wcff-group-title-radio" name="wcff_group_title_radio" value="no" <?php echo ($display_group_title == "no") ? "checked" : ""; ?>/> <?php esc_html_e( 'No', 'wc-fields-factory' ); ?></label></li>
					</ul>	
				</div>
			</td>
		</tr>
		<tr>
			<td class="summary">
				<label><?php esc_html_e( 'Label Alignment', 'wc-fields-factory' ); ?></label>
				<p class="description"><?php esc_html_e( 'Group level title, whether to show this (Title) fields group title on the front end.?', 'wc-fields-factory' ); ?></p>
			</td>
			<td>
				<div class="wcff-field-types-meta">
					<ul class="wcff-field-layout-horizontal">
						<li><label><input type="radio" class="wcff-label-alignment-radio" name="wcff_label_alignment_radio" value="left" <?php echo ($label_alignment == "left") ? "checked" : ""; ?>/> <?php esc_html_e( 'Left', 'wc-fields-factory' ); ?></label></li>
						<li><label><input type="radio" class="wcff-label-alignment-radio" name="wcff_label_alignment_radio" value="top" <?php echo ($label_alignment == "top") ? "checked" : ""; ?>/> <?php esc_html_e( 'Top', 'wc-fields-factory' ); ?></label></li>
					</ul>	
				</div>
			</td>
		</tr>
		
		<tr>
			<td class="summary">
				<label><?php esc_html_e( 'Authorized User(s) Only.?', 'wc-fields-factory' ); ?></label>
				<p class="description"><?php esc_html_e( 'Add rules to determines whether this fields group is for logged in users only, also you can target for specific roles', 'wc-fields-factory' ); ?></p>
			</td>
			<td>
				<ul class="wcff-field-layout-horizontal">
					<li><label><input type="radio" class="wcff-group-authorized-only-radio" name="wcff_group_authorized_only_radio" value="yes" <?php echo ($authorized_only == "yes") ? "checked" : ""; ?>/> <?php esc_html_e( 'Yes', 'wc-fields-factory' ); ?></label></li>
					<li><label><input type="radio" class="wcff-group-authorized-only-radio" name="wcff_group_authorized_only_radio" value="no" <?php echo ($authorized_only == "no") ? "checked" : ""; ?>/> <?php esc_html_e( 'No', 'wc-fields-factory' ); ?></label></li>
				</ul>
				<div id="wcff-target-roles-container" style="display: <?php echo ($authorized_only == "yes") ? 'block' : 'none'; ?>">				
					<ul class="wcff-field-layout-horizontal">				
					<?php		
    					foreach ($wp_roles->roles as $handle => $role) { ?>    					    
    					    <li><label><input type="checkbox" name="wcff_group_preference_target_roles[]" value="<?php echo $handle; ?>" <?php echo (in_array($handle, $targeted_roles) ? "checked" : "" )?>> <?php echo $role["name"]; ?></label></li>
    					    <?php 
    					}				
					?>
					</ul>
				</div>
			</td>
		</tr>
				
	</table>
</div>