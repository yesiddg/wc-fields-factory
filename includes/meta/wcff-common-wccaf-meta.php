<?php
return array(   
    array(
        "label" => __('Hide Field when no Value', 'wc-fields-factory'),
        "desc" => __('Do not show the field on front end if no value is set.', 'wc-fields-factory'),
        "type" => "radio",
        "param" => "hide_when_no_value",
        "layout" => "horizontal",
        "options" => array(
            array(
                "value" => "yes",
                "label" => __('Yes', 'wc-fields-factory'),
                "selected" => true
            ),
            array(
                "value" => "no",
                "label" => __('No', 'wc-fields-factory'),
                "selected" => false
            )
        ),
        "include_if_not" => array(
            "image",
            "url"
        ),
        "at_startup" => "hide",
        "translatable" => "no"
    ),
    array(
        "label" => __('Field with Value', 'wc-fields-factory'),
        "desc" => __('Show just an Empty Field (or) Field with Value (set by admin).<br/>User can override the value.<br/>This field will be carried out to Cart -> Checkout -> Order.', 'wc-fields-factory'),
        "type" => "radio",
        "param" => "show_with_value",
        "layout" => "horizontal",
        "options" => array(
            array(
                "value" => "yes",
                "label" => __('Yes', 'wc-fields-factory'),
                "selected" => true
            ),
            array(
                "value" => "no",
                "label" => __('No', 'wc-fields-factory'),
                "selected" => false
            )
        ),
        "include_if_not" => array(
            "image",
            "url"
        ),
        "at_startup" => "hide",
        "translatable" => "no"
    ),
    array(
        "label" => __('Read Only', 'wc-fields-factory'),
        "desc" => __('Show this field as readonly on front end product page.<br/>Field will be shown with value (set by admin), but user can\'t override.<br/>This field will be carried out to Cart -> Checkout -> Order.', 'wc-fields-factory'),
        "type" => "radio",
        "param" => "show_as_read_only",
        "layout" => "horizontal",
        "options" => array(
            array(
                "value" => "yes",
                "label" => __('Yes', 'wc-fields-factory'),
                "selected" => false
            ),
            array(
                "value" => "no",
                "label" => __('No', 'wc-fields-factory'),
                "selected" => true
            )
        ),
        "include_if_not" => array(
            "image",
            "url"
        ),
        "at_startup" => "hide",
        "translatable" => "no"
    ),
    array(
        "label" => __('Show Value alone', 'wc-fields-factory'),
        "desc" => __('Show field\'s value (set by admin) instead of field.?<br/>Field\'s label also won\'t be included.<br/>Useful to display additional message about product or variant.<br/>This option prevent this field to be carried out to Cart -> Checkout -> Order ', 'wc-fields-factory'),
        "type" => "radio",
        "param" => "showin_value",
        "layout" => "horizontal",
        "options" => array(
            array(
                "value" => "yes",
                "label" => __('Yes', 'wc-fields-factory'),
                "selected" => false
            ),
            array(
                "value" => "no",
                "label" => __('No', 'wc-fields-factory'),
                "selected" => true
            )
        ),
        "include_if_not" => array(
            "image",
            "url"
        ),
        "at_startup" => "hide",
        "translatable" => "no"
    ),
    array(
        "label" => __('Format', 'wc-fields-factory'),
        "desc" => __('What kind of data will be used on this field.', 'wc-fields-factory'),
        "type" => "radio",
        "param" => "value_type",
        "layout" => "horizontal",
        "options" => array(
            array(
                "value" => "text",
                "label" => __('Text', 'wc-fields-factory'),
                "selected" => true
            ),
            array(
                "value" => "price",
                "label" => __('Price', 'wc-fields-factory'),
                "selected" => false
            ),
            array(
                "value" => "decimal",
                "label" => __('Decimal', 'wc-fields-factory'),
                "selected" => false
            ),
            array(
                "value" => "stock",
                "label" => __('Stock', 'wc-fields-factory'),
                "selected" => false
            ),
            array(
                "value" => "url",
                "label" => __('Url', 'wc-fields-factory'),
                "selected" => false
            )
        ),
        "include_if_not" => array(
            "email",
            "number",
            "textarea",
            "checkbox",
            "radio",
            "select",
            "datepicker",
            "colorpicker",
            "image",
            "url"
        ),
        "at_startup" => "show",
        "translatable" => "no"
    ),
    array(
        "label" => __('Tips', 'wc-fields-factory'),
        "desc" => __('Whether to show tool tip icon or not', 'wc-fields-factory'),
        "type" => "radio",
        "param" => "desc_tip",
        "layout" => "horizontal",
        "options" => array(
            array(
                "value" => "yes",
                "label" => __('Yes', 'wc-fields-factory'),
                "selected" => false
            ),
            array(
                "value" => "no",
                "label" => __('No', 'wc-fields-factory'),
                "selected" => true
            )
        ),
        "include_if_not" => array(),
        "at_startup" => "show",
        "translatable" => "no"
    ),
    array(
        "label" => __('Description', 'wc-fields-factory'),
        "desc" => __('Description about this field, if user clicked tool tip icon', 'wc-fields-factory'),
        "type" => "textarea",
        "param" => "description",
        "placeholder" => "",
        "rows" => "3",
        "include_if_not" => array(),
        "at_startup" => "show",
        "translatable" => "no"
    )
);

?>