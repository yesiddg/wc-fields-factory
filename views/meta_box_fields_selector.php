<?php
/*
*  Meta box - Custom Product Fields
*  Template for creating or updating custom product fields
*/

if (!defined('ABSPATH')) { exit; }

global $post;
$fields = array();

if( $post->post_type != "wccaf" ) {
	$fields = apply_filters( "wccpf_fields_factory_supported_fields", array (
		array("id" => "text", "title" => __( 'Text', 'wc-fields-factory' )),
		array("id" => "number", "title" => __( 'Number', 'wc-fields-factory' )),
		array("id" => "email", "title" => __( 'Email', 'wc-fields-factory' )),
		array("id" => "hidden", "title" => __( 'Hidden', 'wc-fields-factory' )),
		array("id" => "label", "title" => __( 'Label', 'wc-fields-factory' )),
		array("id" => "textarea", "title" => __( 'Text Area', 'wc-fields-factory' )),
		array("id" => "checkbox", "title" => __( 'Check Box', 'wc-fields-factory' )),
		array("id" => "radio", "title" => __( 'Radio Button', 'wc-fields-factory' )),
		array("id" => "select", "title" => __( 'Select', 'wc-fields-factory' )),
		array("id" => "datepicker", "title" => __( 'Date Picker', 'wc-fields-factory' )),
		array("id" => "colorpicker", "title" => __( 'Color Picker', 'wc-fields-factory' )),
		array("id" => "file", "title" => __( 'File', 'wc-fields-factory' ))
	));
} else {
	$fields = apply_filters( "wccaf_fields_factory_supported_fields", array (
		array("id" => "text", "title" => __( 'Text', 'wc-fields-factory' )),
		array("id" => "number", "title" => __( 'Number', 'wc-fields-factory' )),
		array("id" => "email", "title" => __( 'Email', 'wc-fields-factory' )),
		array("id" => "textarea", "title" => __( 'Text Area', 'wc-fields-factory' )),
		array("id" => "checkbox", "title" => __( 'Check Box', 'wc-fields-factory' )),
		array("id" => "radio", "title" => __( 'Radio Button', 'wc-fields-factory' )),
		array("id" => "select", "title" => __( 'Select', 'wc-fields-factory' )),
		array("id" => "datepicker", "title" => __( 'Date Picker', 'wc-fields-factory' )),
		array("id" => "colorpicker", "title" => __( 'Color Picker', 'wc-fields-factory' )),
		array("id" => "image", "title" => __( 'Image', 'wc-fields-factory' )),
		array("id" => "url", "title" => __( 'Url', 'wc-fields-factory' ))
	));
}

?>

<!-- Hidden Fields -->
<div style="display:none;">
	<input type="hidden" name="wcff_nonce" value="<?php echo esc_attr(wp_create_nonce('field_group')); ?>" />
</div>
<!-- / Hidden Fields -->


<div class="fields_select">
	<div id="wcff-fields-select-container">
		<ul class="select">
			<?php foreach ($fields as $field) : ?>
			<li><a draggable="true" class="wcff-drag-field" href="#" value="<?php echo esc_attr($field["id"]); ?>"><span><img src="<?php echo esc_url(wcff()->info["assets"]) .'/img/'. $field["id"] .'.png'; ?>"></span><?php echo esc_html($field["title"]); ?></a></li>
			<?php endforeach;?>								
		</ul>
	</div>
</div>


