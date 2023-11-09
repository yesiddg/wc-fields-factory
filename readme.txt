=== WC Fields Factory ===
Contributors: sarkware, sarkparanjothi, mycholan
Tags: wc fields factory, custom product fields, custom admin fields, overriding product price, custom woocommerce fee, customize woocommerce product page, add custom fields to woocommerce product page, custom fields validations, wmpl compatibility 
Requires at least: 3.5
Tested up to: 6.1.1
Stable tag: 4.1.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Sell your products with personalised options. Add custom fields to your products, variations, checkout, order and your admin screens.

== Description ==

It's a woocommerce extension, which helps you to collect extra options from user. The extra options will be carried over to cart, checkout, order & email.

Using it's simple field configuration views, you can easily create and publish custom fields to your wooocmmerce products.
There is a dedicated drag & drop based form designer, you can customize how the fields should be positioned in the product page.

WC Fields Factory is fully unlocked.
There is no premium version, all the field types as well as features are free and always will be.!

https://www.youtube.com/watch?v=d_HgptezlfY

==Supported Field Types==

* <strong>File Upload</strong> : Single and multiple file uploads. supports major file formats (img, video, pdf ...)
* <strong>Color Picker</strong> : Supports user defined palettes as well as raw color picker.
* <strong>Date Picker</strong> : jQuery UI datepicker.
* <strong>Check Box</strong> : Checkbox list for selecting multi option.
* <strong>Radio Button</strong> : Single option selector.
* <strong>Color Swatch</strong> : Sub type of Radio button, radio buttons can be shown as color swatches.
* <strong>Image Button</strong> : Sub type of Radio button, radio buttons can be shown as image button selector.
* <strong>Drop Down</strong> : To show dropdown list.
* <strong>Text Area</strong> : To collect more than one line of text.
* <strong>Text</strong> : To collect simple text.
* <strong>Number</strong> : To collect number alone.
* <strong>Email</strong> : To collect email address
* <strong>Label</strong> : To show product related highlighted message
* <strong>Image</strong> : Wp Media Upload button (for admin fields only)
* <strong>URL</strong> : Set custom links on product page (for admin fields only)
* <strong>Hidden</strong> : Hidden information only for the eye of admin 

==Features==

<strong>Custom Pricing & Fee Rules</strong>
Change product's price based on custom fields value dynamically. 
Price can be added, subtracted or replaced. 
Calculation mode can be fixed or percentage Value.
You can also add cart Fee based on custom fields value dynamically.

<strong>Custom Fields Rules</strong>
Make field visible or hidden based on other fields value. 

<strong>Variation Fields</strong>
Create, manage & publish custom fields for woocommerce product variations.
There is a dedicated configuration view for mapping custom fields to product variations (From V4.0.0)

<strong>Checkout Fields</strong>
Using Wc Fields Factory you can customize checkout forms (billing & shipping).
You can add new fields to address forms or you show/hide existing address fields.
You can also add custom fields to other part of checkout page as well.

<strong>Admin Fields</strong>
WC Fields Factory allows you to assign fields for back end product admin screens. 
Fields for Product Admin View, Product Variations Admin View & Product Category Admin View.
Admin can show these fields to front end product page as well (to show some predefined value)
Can add custom fields to product variation admin view as well.

<strong>Fields Cloning</strong>
Allows to collect extra options per quantity.

<strong>Role Based Fields</strong>
Make fields visible to only authorized users (based on roles)

<strong>Validations</strong>
Dual layer validations, for real time (client side) as well as server side.

<strong>Form Designer</strong>
Custom built form designer exclusively for rendering the fields.

<strong>WC's Rest API support</strong>
WC Fields Factory expose custom fields to wooCommerce rest api (for both products as well as variations end point)
Also added support for CoCart Headless ecommerce plugin.

= Documentation =
* [Product Fields](https://wcfieldsfactory.com/user-guide/fields-for-woocommerce-products/)
* [Variation Fields](https://wcfieldsfactory.com/user-guide/fields-for-woocommerce-variations/)
* [Admin Fields](https://wcfieldsfactory.com/user-guide/fields-for-woocommerce-admin/)
* [Pricing & Fee Rules](https://wcfieldsfactory.com/user-guide/custom-pricing-cart-fee/)
* [Multilingual](https://sarkware.com/multilingual-wc-fields-factory/)
* [Troubleshoot](https://sarkware.com/troubleshoot-wc-fields-factory/)
* [WC Fields Factory APIs](https://sarkware.com/wc-fields-factory-api/)
* [Overriding Product Prices](https://sarkware.com/woocommerce-change-product-price-dynamically-while-adding-to-cart-without-using-plugins/#override-price-wc-fields-factory)
* [Customize Rendering Behavior](https://wcfieldsfactory.com/developer-reference/change-fields-rendering-behaviour/)

== Installation ==
1. Ensure you have latest version of WooCommerce plugin installed ( 2.2 or above )
2. Unzip and upload contents of the plugin to your /wp-content/plugins/ directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Use the "Add New" button from "Fields Factory" menu in the wp-admin to create custom fields for woocommerce product page

== Screenshots ==
1. Single Product Page with Custom Fields
1. Wcff Product Custom Fields List
2. Wcff Factory View
3. Wcff Rules
4. Wcff Pricing Rules View
5. Wcff Fee Rules View

== Changelog ==

= 4.1.6 =
* Updated with proper escaping on all outputs

= 4.1.6 =
* Sql injection vulnerability fix added. 

= 4.1.5 =
* Support added for putting admin fields on dedicated product data tab
* Added condition for showing fields based product stock status
* Pricing rules not working for variation fields issue - fixed
* Pricing rule not working for number field - fixed
* Product bundle compatibility issue - fixed
* Currency format from woocommerce setting page (on real time pricing update)
* Applied pricing rules title can be shown or hidden on Product, Cart & Checkout Page
* Borders around fields has been removed (Can be overriden usiong theme's style.css)
* Php 8.X.X depricated warnings has been fixed
* Fields rules disappeared issue - fixed

= 4.1.4 =
* Option added for not showing Admin field on front end, when no value set 
* Enable / disable - fields type module
* Datepicker disable next x days(add if any disabled date) - implementation done
* Color picker recursive palette issue fix
* Fix for validation failed issue on fields which are hidden by field's rules

= 4.1.3 =
* Support for Woocommerce currency switcher added.
* Ajax add to cart JS issue fix.
* Variation fields duplication issue fix.
* Fields mapping search result ORDER BY DESC added.
* Text Area field height issue fixed.
* Group level Product tab config has been removed, which is introduced some unnecessary duplicate fields issue.
* Tutor LMS support added (Course template should be compatible with woocommerce)

= 4.1.2 =
* Custom meta added by other plugin not showing issue - fixed.
* Admin field by default won't be added as Order Meta.
* Duplicate values cleared when cloning happening.
* Duplicate fields entries on Cart, Checkout & Order (Especially on variation product) - fixed.

= 4.1.1 =
* Date & Color Picker field issue on admin field - fixed.
* Order fields on email issue - fixed.
* Order fields duplicate in variation product line item - fixed.
* Ajax adding to cart issue - fixed.
* Client side validation issue - fixed.

= 4.1.0 =
* Ajax add to cart support added (theme should fire 'adding_to_cart' custom events, otherwise it won't work)
* Product FIeld module loading squance modified - to fix the issue on Ajax add to cart.
* Order fields on custom order view issue fixed.

= 4.0.9 =
* Order fields on email issue - fix
* Variation fields init issue - fix
* Variation title issue on Target Product Select Box issue - fix

= 4.0.8 =
* Fix for client side JS issue

= 4.0.7 =
* Option added for update fields key
* Option added for show/hide field on customer email
* Fields for woocommerce order feasture added (for updating order with custom fields)
* Custom checkout fields on email - issue fixed (now it is included in the email - can be configured not to)
* Product selector pagination added for performance
* Archive page JS script update for ajax add to cart
* Removed unecessary filter whichs affected performace - which leads to crash
* Fields show for empty variation - issue fixed

= 4.0.6 =
* Select field clone (for outter form location) issue fixed
* added wccpf_custom_field_wrapper filter for adding custom field wrapper

= 4.0.5 =
* Admin Menus (Product Fieldsm Admin Fields, Variable Fields & Checkout Fields) not showing issue solved
* Support for Tier Pricing Table addon
* Admin checkbox field on product page issue solved
* Admin URL field on product page issue solved
* Field Key column has been added on the backend field listing page

= 4.0.4 =
* Duplicate fields issue solved
* Empty product data tab issue solved
* FF logo css issue on admin menu solved
* Custom upload directory option issue solved
* Custom checkout field validation issue solved
* Admin fields on order only (for variable product) issue solved

= 4.0.3 =
* Contains fix for missing field's configuration when updating.
* Check box rules has been updated. Now it has Checked & Un Checked rules for matching.

= 4.0.2 =
* Contains fix for duplicate fields.

= 4.0.1 =
* wcff_new_field_id filter added for overriding field id.
* Unnecessary meta list box reemoved

= 4.0.0 =
* Image switcher for Color Field feature has been removed (as WC itself has variation images)
* Pricing rules for Checkbox has been modified (now it supports only 'has-options')
* Image radio button feature added
* Color radio button feature added
* Drag & Drop based Layout Designer for Fields Sequence Arrangement
* Fields for variations, not a new feature but now it has it's own menu and dedicated view for mapping variations to fields
* Fields are now exposed on WC rest api (product & variable)
* Added support for CoCart – Headless ecommerce
* Admin fields for variations can also be displayed on front end variable products.
* Fields label placement option added - you can place the label on left side or top of the field
* Group level cloning option added (Fields Factory already have fields level cloning option)
* Now supports to display Group level title (The custom Fields group Post's title)
* Each field's has dedicated enable/disable toggle & Clone buttons
* Use wordpress default enqueue for Jquery UI
* Fix weired character because of i18n jquery datepicker js
* Fix for validation module return Empty (neither TRUE nor FALSE)
* Each fields config has it's own update button
* Control authorized only fields on Group level 
* Option added for enable/disable custom pricing module
* Option added for assigning custom priority for field location hook
* Option added for assigning custom hook for fields location
* Additional post filters added for easier fields management 
* Ground level code restructuring for improved stability
* Compatibility with latest WC & WP

= 3.0.4 =
* PHP V8, Wordpress 5.8.2 and Woocommerce 6.0.0 upgrade compatibility
* Minor bug fixes

= 3.0.3 =
* Php 5.4 version conflict - fixed
* Date picker front end valitation - fixed
* wcff-admin.css style chache - fixed

= 3.0.2 =
* Archive page admin and product fields with optional
* Pricing rule % without calculated value show to user - fixed
* Cart editor validation - fixed
* Field cloning with pricing rule - split cart item
* Cart editor with pricing rule - fixed
* Pricing and fee rule decimal number enabled
* wcff_before_rendering_cart_data - miss value passed - fixed
* wcff_before_inserting_order_item_meta - miss value passed - fixed
* Added an option for pricing details info show/hide
* JavaScript errors related to timepicker/datepicker - fixed

= 3.0.1 =
* Product tab not working fixed
* Cart editor validation fixed
* Jquery exception handled
* Checkout field without state validation issue fixed
* Checkout default field label language issue fixed.
* Checkout hidden field not  working - fixed
* Variation field initial field rule not working - fixed

= 3.0.0 =
* Group wise field location for product and admin fields.
* Unnessary sortable on field config - fixed
* Cart update not working after wordpress update - fixed
* Price rule product ajax price replace improved
* Role based field default hide - fixed
* Color-field text input for user can type or paste color code - optional
* Required field with hidden valitation miss match - fixed
* Fields not in cart form, fields value not carry on cart - fixed
* Color-field extra option for change product image color based
* Variation fields show with this appropriate location on product page
* Product image not showing after update fixed
* variation fields showing only when user login - fixed
* label not cloning - fixed
* label not woking with fields rule - fixed
* Added checkout fields for billing, shipping and custom.
* Manipulate woocommerce billing and shipping fields.
* Admin side field config UI Changes ( Look & feel and can view & edit multiple field config same time ).
* Added wcff setting page link on Installed plugin page.
* hidden field value not carry on cart - fixed

= 2.0.8 = 
* Fields for Variations
* Ability to show & hide fields based on user interaction
* Updating the product price on single product page (Based on pricing rules) 
* Percentage option for fees and price rules 
* NOT NULL rule added for Pricing & Fee
* Select option before rendering filter added
* Date disable next x days added
* Field show on Before Product Meta and After Product Meta added
* Filters added for wcff_realtime_negotiate_price_after_calculation and wcff_negotiate_price_after_calculation

= 2.0.7 = 
* Pricing rules re-modify
* Interfering with wooCommerce bookings and cart totals - fixed
* Woocommerce dynamic pricing interferes with fields factory - fixed

= 2.0.6 = 
* Pricing rules issue fixed

= 2.0.5 = 
* Admin field checkbox issue fixed
* Admin field readonly issue fixed
* Timepicker not working issue fixed
* Client side validation issue fixed
* Translation config added for Cloning Group Title (Setting Page)
* 'wccpf_cloning_fields_group_title' filter added to override the cloning group title text
* Empty editor wrapper on Cart Line Items issue fixed
* Datepicker issue Cart Editor fixed
* Placeholder option added for Select Field.
* Priority for woocommerce releated hook changed (To prevent overridden by other plugins).
* Minimum Maximum hours & Minute option added for Time Picker

= 2.0.4 =
* Color picker validation issue fix
* Wrapper class for each field's wrapper added 
* Suppressed unnecessary warning messages (like accessing undefined index).

= 2.0.3 =
* Parse error: syntax error, unexpected ‘[‘ fixed

= 2.0.2 =
* Date picker issue fix

= 2.0.1 =
* Call to undefined function WC() fixed 
* "wp_register_style" was called incorrectly, warning message (along with other warning message) fixed

= 2.0.0 =
* Pricing & Fee rules for custom fields, now you change product price based on fields value.
* Multilingual support added (right now it support WPML).
* Field level cloning option (Exclude field from cloning).
* Show fields based on user roles.
* Fields value retained whenever validation is failed.
* Option factory widget added for Check, Radio and Select box.
* Default option will be the actual tag (genrated from the choices param on real time).
* \' \" escaping issue resolved.
* HTML tags on label message issue resolved. 
* Enable plugin access to Woocommerce Shop Manager role.
* Date picker and Colorpicker issue on Variation tab & Product cat page fixed.
* Now cloned fields (Also if you enabled Editable on Cart option) will be rendered on cart & check out page by the Field Factory itself, so exsisting users might experiance some styling changes on Cart & Checkout.
* Replaced all "/" with "_" on WC Fields Factory related actions and filters. (eg. "wccpf/before/field/start" has become 'wccpf_before_field_start')

= 1.4.0 =
* White screen of death issue solved

= 1.3.9 =
* WC 3+ compatibility updates
* woocommerce_add_order_item_meta ( woo 3.0.6 ) update
* Option to change file upload directory ( Within wp-contents only )
* New field type for Admin Fields ( for posting URLs )
* Multi check box option for Admin Field
* Conflict resolved with Ticket Plus plugin
* Returns label instead of value for select field - option added
* Admin field display issue on cart - solved ( happened only when Fields Cloning Enabled )
* Export order meta option added for 'WooCommerce Simply Order Export' plugin ( more plugin support coming soon )

= 1.3.8 =
* Cart editing issue fixed ( Earlier, editing not worked for cloning fields )
* Cart editing option added for both Global as well as individual fields ( By default it will be non editable )
* Custom css class option has been added for all fields
* Minor code tuning to suppress unnecessary warnings

= 1.3.7 =
* Cart page field editing option added ( Except file upload all other fields can be edited )
* Image preview option on Cart & Checkout page added ( Only for File upload with Image type )
* Option added for showing admin field's value instead of field itself on front end ( Default option will be field )

= 1.3.6 =
* Issue fixed on Product pages generated by Visual Composer fixed
* wp_register_style & wp_enqueue_style warning fixed
* File upload restriction issue fixed
* File upload max size option included
* Additional options for Disable dates in Date picker ( Disable Week days, Week end, Specific dates, specific dates for all months )
* Color picker fields now displaying color palette instead of raw value
* Default color picker value issue fixed
* Showing fields for logged in users option added ( for both Globally or Field wise )
* Allowing decimal type on Number field issue fixed ( You can now give 'auto' on 'step count' option )
 
= 1.3.5 =
* File upload validation issue fixed
* New field ( Image Upload ) has been added ( available only for Admin Fields )
* Now you can display your custom fields under Product Tab ( New Product Tab will be created, you have to enable it via WCFF Settings Screen )
* Single & Double quotes escaping problem fix ( on Fields Label )
* Year range option has been added for Date Picker ( '-50:+0',-100:+100 or absolute 1985:2065 )
* Date picker default language added ( English/US )
* Variable product Admin Fields saving issue fix
* Client side validation on blur settings added ( now you can specify whether the validation done on on submit or on field out focus )
* Show fields group title on Front End ( Post Title ( Fields group ) will be displayed )
* Number field validation Reg Exp fix ( Client Side )
* WCFF option access has been centralized ( now you can add 'wcff_options' filter to update options before it reaches to WCFF )
* Woocommerce ( If it is not activated yet ) not found alert added ( It's funny that I didn't checked this far, but this plugin will work even without woocommerce but there won't be much use then )
* Overly mask will be displayed while trying to edit or remove fields meta ( on wp-admin screen )

= 1.3.4 =
* Default color option for Color Field
* Admin Select field shows wrong value on Product Front End page issue fixed
* i18n support for Field's Label ( now you can create fields on Arabic, Chinese, korean .... ) 

= 1.3.3 =
* Validation error fix for Admin Field ( "this field can't be empty" is shown )

= 1.3.2 =
* fix for : Undefined variable ( Trying to get property of non-object ): product in /wc-fields-factory/classes/wcff-product-form.php on line 247

= 1.3.1 =
* Product rules error fixed
* Datepicker on chinese language issue fixed
* Checkout order review table heading spell mistakes fixed
* Rendering admin fields on product front end support added ( By default it's not, you will have to enable the option for each fields - for product page, cart & checkout page and order meta )
* Fields location not supported fix ( now you can use 'woocommerce_before_add_to_cart_form', 'woocommerce_after_add_to_cart_form', 'woocommerce_before_single_product_summary', 'woocommerce_after_single_product_summary' and 'woocommerce_single_product_summary' )

= 1.3.0 =
* Fields update issue fixed.
* File validation issue ( Fatal error: Call to undefined function finfo_open() ) fixed.

= 1.2.9 =
* Admin fields validation ( for mandatory ) added.
* File types server side validation - fixed.
* Validation $passed var usage - added.
* wccpf_unique_key conditional - removed ( as it no longer needed ).
* Time picker option added.
* Localization ( multi language support ) for datepicker added.
* Show dropdowns for month and year - datepicker.
* Uncaught ReferenceError: wcff_fields_cloning is not defined - fixed.
* Enque script without protocol ( caused issue over https ) - fixed.
* Show & hide on cart & checkoput pge option added for hidden field
* from V1.2.9, we are using Fileinfo module to validate file uploads ( using their mime types )
  PHP 5.3.0 and later have Fileinfo built in, but on Windows you must enable it manually in your php.ini


= 1.2.8 =
* "Display on Cart & Checkout" option on Setting page - issue fixed.

= 1.2.7 =
* Check box field's choice option not updated - issue fixed.

= 1.2.6 =
* Product rules broken issue fixed. 

= 1.2.5 =
* Two new fields has been added. Label ( you can now display custom message on product page ) & Hidden fields
* Client side validation included ( by default it's disabled, you will have to enable it through settings pags )
* Validation error message for each field, will be shown at the bottom of each fields.
* wccaf post type introduced ( custom fields for backend admin prducts section )
* Now you can add custom fields for back end as well ( on Product Data tabs, like you can add extra fields on general, inventory, shipping, variables, attributes tabs too )
* Multi file uploads support added ( for file field )
* Support for rules by tags & rules by product types added
* Order Item Meta visibility option added
* Datepicker disable dates issue solved
* Fields cancel button issue ( on the edit screen ) solved
* "Allowed File Types" in the File field, you will have to prefix DOT for all extensions 
* Entire plugin code has been re structured, proper namespace added for all files & classes, more comments added

= 1.2.4 =
* Fix for "Fields Group Title showing on all products since the V1.2.3"
* Wrapper added for each field groups

= 1.2.3 =
* Multiple colour pickers issue fix
* wccpf_init_color_pickers undefined issue fix
* Group title index will be hidden if product count is 1
* Minimum product quantity issue fix
* File type validation issue fix
* "Zero fields message" while deleting custom fields ( on wp-admin )

= 1.2.2 =
* Fields cloning option added ( Fields per count, If customer increase product count custom fields also cloned )
* Visibility of custom meta can be set ( show or hide on cart & checkout page )

* Setting page added
* Visibility Option - you can set custom data visibility globally ( applicable for all custom fields - created by this plugin )
* Field Location - you can specifiy where the custom fields should be included.
* Enable or Disbale - fields cloning option.
* Grouping the meta on cart & checkout page, option added.
* Grouping custom fields on cart & checkout page, option added.
* Set label for fields group
* Option to disable past or future dates
* Option to disbale particular week days
* Read only option added for Datepicker textbox ( usefull for mobile view )
* heigher value z-index applied for datepickers
* Pallete option added to color picker
* Option to show only palette or along with color picker
* Color format option added

= 1.2.1 =
* Add to cart validation issue fixed

= 1.2.0 =
* Woocommerce 2.4.X compatible 
* File upload field type added
* Internationalization ( i18n ) support added

= 1.1.6 =
* fixed "Missing argument" error log warning message

= 1.1.5 =
* Select field with variable product - issue fixed
* Order conflict while updating fields - issue fixed
* Newline character ( for select, checkbox and radio ) - issue fixed

= 1.1.4 =
* utf-8 encoding issue fixed
* Internationalization support.

= 1.1.3 =
* Order meta ( as well as email ) not added Issue fixed  

= 1.1.2 =
* Removed unnecessary hooks ( 'woocommerce_add_to_cart', 'woocommerce_cart_item_name' and 'woocommerce_checkout_cart_item_quantity' ) 
  yes they no longer required.
* Now custom fields data has been saved in session through 'woocommerce_add_cart_item_data' hook
* Custom fields rendered on cart & checkout page using 'woocommerce_get_item_data' ( actually rendered via 'cart-item-data.php' template )

= 1.1.1 =
* Color picker field type added

= 1.1.0 =
* Date picker field type added

= 1.0.4 =
* Validation issue fixed.
* Issue fixed ( warning log for each non mandatory custom fields ).
* Some css changes ( only class name ) to avoid collision with Bootstrap. 

= 1.0.3 =
* Hiding empty fields from cart table, checkout order review table and order meta.

= 1.0.2 =
* Issue fixing with "ACF" meta key namespace collition. 

= 1.0.1 =
* "wccpf/before/field/rendering" and "wccpf/after/field/rendering" actions has been added to customize wccpf fields rendering

= 1.0.0 =
* First Public Release.