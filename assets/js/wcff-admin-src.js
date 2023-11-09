/**
 * @author  	: Saravana Kumar K
 * @author url 	: http://iamsark.com
 * @url			: http://sarkware.com/
 * @copyrights	: Sarkware Research & Development (OPC) Pvt Ltd
 * @purpose 	: wcff Controller Object.
 */
/**/
var wcffObj = null;
(function ($) {

	/* Holds the masker object */
	var mask = null;
	/**/
	var wcff = function () {

		/* used to holds next request's data (most likely to be transported to server) */
		this.request = null;
		/* used to holds last operation's response from server */
		this.response = null;
		/* to prevetn Ajax conflict. */
		this.ajaxFlaQ = true;
		/* Holds currently selected fields */
		this.activeField = null;
		/* Holds the pricing rules for the active record */
		this.pricingRules = [];
		/* Holds the fee rules for the active record */
		this.feeRules = [];
		/* Holds the fields rules for the active record */
		this.fieldRules = [];
		/* Holds the color to image mappping rule for the active record */
		this.colorImage = [];
		/* Global object for error information */
		this.val_error = { message: "", elem: $(""), flg: false };
		/* */
		this.postSubmit = false;
		/* Holds the wp media object */
		this.mediaFrame = null;
		/* The field that is being dragged */
		this.draggedField = null;
		/**/
		this.emptyNotice = $("#wcff-empty-field-set");
		/**/
		this.placeHolder = $("#wcff-add-field-placeholder");
		/* Active row reference */
		this.activeRow = null;
		/* Holds the Mask Object */
		this.mask = null;
		/* Used to holds the reference of an object which the ajax operation is being initiated */
		this.target = null;
		/* Fields which have been updated */
		this.dirtyFields = {};
		/* */
		this.configWidgets = {};
		/* Timer object used by the search module */
		this.searchTimer = null;
		/* Fields List - Used by the layout designer */
		this.fields = {};
		/* Holds the reference of the column on where the field about to be dropped */
		this.dropZone = null;
		/* Flaq for mouse down event */
		this.isMouseDown = false;
		/* Holds the reference of the Column that is being resized */
		this.targetDropZone = null;
		/* Holds the Offset Left property of the column that is being resized */
		this.targetDropZoneOffsetLeft = 0;
		/* Holds the layout meta object */
		this.layout = {};
		/* Used to holds the keys of the fields that has to be rendered on the layout designer */
		this.layoutFieldsKeys = [];
		/**/
		this.mapping_grid = null;
		/* Used to holds search field reference for varation level mapping operation */
		this.currentWccvfSearchField = null;
		/* Used to holds id of Varation, while varation level mapping operation  */
		this.currentVariation = null;
		/* Used to holds the parent of current variant */
		this.currentProduct = null;
		/* Holds the reference to the current active popup in variation mapping section */
		this.currentPopup = null;
		/* Holds the variation fields groups */
		this.wccvfPosts = null;
		/* */
		this.currentProductSearchField = null;

		this.initialize = function () {
			this.registerEvents();
			this.mask = new wcffMask();			
			/* Update the layout meta object */
			if ($("#wcff_layout_meta").length > 0) {
				this.layout = JSON.parse($("#wcff_layout_meta").val());
				/* Sanity Check */
				if (this.layout["columns"]) {
					if (Array.isArray(this.layout["columns"])) {
						this.layout["columns"] = {};						
					}
				} else {					
					this.layout["columns"] = {};
					this.layout["rows"] = [];
				}
			} else {
				this.layout = [];
			}

			/* Update admin menu current item for Checkout, Variation & Admin post edit */
			if (wcff_var.current_page == "wccaf" || 
				wcff_var.current_page == "wccvf" || 
				wcff_var.current_page == "wcccf") {
				$("#toplevel_page_edit-post_type-wccpf").removeClass("wp-not-current-submenu").addClass("wp-has-current-submenu");
                $("#toplevel_page_edit-post_type-wccpf > a").removeClass("wp-not-current-submenu").addClass("wp-has-current-submenu");
                if (wcff_var.current_page == "wccvf") {
                    /* Make the second sub item active */
                    $("#toplevel_page_edit-post_type-wccpf").find("> ul > li:nth-child(3)").addClass("current");
                }
			}            
		};

		/* Responsible for registering handlers for various DOM events */
		this.registerEvents = function () {

			$(document).click(function (e) {
				$("ul.wcff-variation-config-popup").hide();
				$("div.wcff-target-selector").hide();
			});

			$(document).keyup(function (e) {
				if (e.keyCode == 27) {
					$("ul.wcff-variation-config-popup").hide();
					if (wcffObj.currentWccvfSearchField) {
						wcffObj.mapping_grid.isReloading = true;
						wcffObj.mapping_grid.prepareRecords(wcffObj.mapping_grid.records);
					}
				}
			});

			$(document).on("click", "div.variation-config-ghost-back", this, function (e) {

				if (e.data.currentWccvfSearchField) {
					e.data.mapping_grid.isReloading = true;
					e.data.mapping_grid.prepareRecords(e.data.mapping_grid.records);
				}

				$("ul.wcff-variation-config-popup").hide();
				$("div.wcff-variation-mapper-for-variation").css("z-index", "9");
				e.data.currentPopup = null;
				e.data.currentProduct = null;
				e.data.currentVariation = null;
				e.data.currentWccvfSearchField = null;
				$(this).remove();
			});

			$(document).on("click", "ul.wcff-variation-config-popup, div.wcff-target-selector, td.condition_value_td, input.wcff-field-key-edit-txt", function (e) {
				e.stopPropagation();
			});			
			
			/* Drag & Drop event registrations for Field Selector */

			$(document).on("dragstart", "#wcff-fields-select-container a.wcff-drag-field", this, function (e) {
				e.data.draggedField = $(e.target);
				e.data.placeHolder.addClass("dropover");
			});

			$(document).on("dragend", "#wcff-fields-select-container a.wcff-drag-field", this, function (e) {
				e.data.draggedField = null;
				e.data.placeHolder.removeClass("dropover");
			});

			$(document).on("dragenter dragover dragend", "#wcff-add-field-placeholder", function (e) {
				e.preventDefault();
				e.stopPropagation();
			});

			$(document).on("drop dragdrop", "#wcff-add-field-placeholder", this, function (e) {
				e.stopPropagation();
				e.data.dropNewField();
			});

			$(document).on("dragstart", '.wcff-meta-row.active', function (e) {
				e.preventDefault();
				return false;
			});

			$(document).on("dragover", ".wcff-meta-row", this, function (e) {
				if (e.data.draggedField != "") {
					if ($(e.currentTarget).is(":first-child") && $(e.currentTarget).outerHeight() / 2 + e.clientY > $(e.currentTarget).offset().top) {
						$(e.currentTarget).before(e.data.placeHolder);
					} else {
						$(e.currentTarget).after(e.data.placeHolder);
					}
				}
			});

			/* Drag & Drop event registrations for Layout Designer */
			$(document).on("dragstart", "#wcff-layout-designer-field-list > a", this, function (e) {
				e.data.draggedField = $(e.target);
				e.data.constructDropZone();
			});

			$(document).on("dragend", "#wcff-layout-designer-field-list > a", this, function (e) {
				//e.data.draggedField = null;	
				e.data.destructDropZone();
			});

			$(document).on("dragenter dragover dragend", "div.wcff-layout-form-row > div", function (e) {
				e.preventDefault();
				e.stopPropagation();
			});

			$(document).on("drop dragdrop", "div.wcff-layout-form-row > div", this, function (e) {
				e.stopPropagation();
				if (e.data.draggedField) {
					var _meta = e.data.fields[e.data.draggedField.attr("data-fkey")];
					_meta["label_alignment"] = $("input[name=wcff_label_alignment_radio]:checked").val();
					e.data.prepareRequest("GET", "render_field", { meta: _meta }, e.data.activeRow);
					e.data.dock();
					e.data.dropZone = $(this);
					e.data.dropZone.removeClass().addClass("dropped");
				}
			});

			$(document).on("dragover", "div.wcff-layout-form-row > div", this, function (e) {
				if (e.data.draggedField != "") {
					$(this).addClass("hover");
				}
			});

			$(document).on("dragleave", "div.wcff-layout-form-row > div", this, function (e) {
				$(this).removeClass("hover");
			});

			/* Layout designer resizing related event handlers */
			$(document).on("mousedown", this, function (e) {
				if ($(e.target).hasClass("handlebar")) {
					e.data.isMouseDown = true;
					e.data.targetDropZone = $(e.target).prev();
					var rect = e.data.targetDropZone[0].getBoundingClientRect();
					e.data.targetDropZoneOffsetLeft = rect.x;
				}
			});

			$(document).on("mousemove", this, function (e) {
				if (e.data.isMouseDown) {
					e.data.targetDropZone[0].style.flexGrow = 0;
					e.data.targetDropZone[0].style.flexShrink = 0;
					var pointerRelativeXpos = e.clientX - e.data.targetDropZoneOffsetLeft;
					if ((pointerRelativeXpos - 4) > e.data.targetDropZone.parent().width()) {
						pointerRelativeXpos = e.data.targetDropZone.parent().width();
					}
					e.data.targetDropZone[0].style.flexBasis = (Math.max(80, pointerRelativeXpos - 4)) + 'px';

					var ratio = 0,
						me = e.data,
						rowWidth = e.data.targetDropZone.parent().width(),
						hBarCount = e.data.targetDropZone.parent().find("> div.handlebar").length,
						hWidth = (hBarCount * 6);

					e.data.targetDropZone.parent().find("> div.dropped").each(function (e) {
						ratio = ((parseInt($(this).width()) + parseInt(hBarCount > 0 ? (hWidth / hBarCount) : 0)) / rowWidth);
						me.layout.columns[$(this).attr("data-fkey")].width = (ratio * 100);
					});
				}
			});

			$(document).on("mouseup", this, function (e) {
				if (e.data.isMouseDown) {
					e.data.isMouseDown = false;
					e.data.layoutFormRow = null;
					e.data.targetDropZone = null;
				}
			});

			$(document).on("click", "div.dropped > a.delete-field", this, function (e) {
				/* Remove the handle bar */
				if ($(this).parent().prev().hasClass("handlebar")) {
					$(this).parent().prev().remove();
				}
				/* Also remove the next handle bar - for first item */
				if ($(this).parent().index() == 0 && $(this).parent().next().hasClass("handlebar")) {
					$(this).parent().next().remove();
				}
				var fkey = $(this).parent().attr("data-fkey");

				/* Remove the col from the DOM */
				$(this).parent().remove();
				/* Remove the col from Layout Object */
				if (e.data.layout.columns[fkey]) {
					delete e.data.layout.columns[fkey];
				}

				for (let i = 0; i < e.data.layout.rows.length; i++) {
					if (Array.isArray(e.data.layout.rows[i])) {
						let index = e.data.layout.rows[i].indexOf(fkey);
						if (index > -1) {
							e.data.layout.rows[i].splice(index, 1);
						}
					}
				}

				/* Clean empty row in the layout meta */
				for (let i = 0; i < e.data.layout.rows.length; i++) {
					if (Array.isArray(e.data.layout.rows[i]) && e.data.layout.rows[i].length == 0) {
						e.data.layout.rows.splice(i, 1);
					}
				}

				/* Also remove the row itself, if it doesn't has any fields */
				if ($("div.wcff-layout-form-row").length > 1) {
					$("div.wcff-layout-form-row").each(function () {
						/* Make sure it has at least one row */
						if ($(this).siblings().length > 0) {
							if ($(this).find("> div").length == 0) {
								$(this).remove();
							}
						}
					});
				}
				/* Restore the fields list */
				if (e.data.fields[fkey]) {
					/* Check if the field list is empty */
					if ($("#wcff-layout-designer-field-list > h3").length > 0) {
						$("#wcff-layout-designer-field-list > h3").remove();
					}
					$("#wcff-layout-designer-field-list").append($('<a href="#" draggable="true" data-fkey="' + fkey + '" data-type="' + e.data.fields[fkey]["type"] + '" title="' + e.data.fields[fkey]["label"] + '">' + e.data.fields[fkey]["label"] + '</a>'));
				}

				e.preventDefault();
			});

			$(document).on("click", "div.wcff-meta-row > table.wcff_table", this, function (e) {

				var me = e.data,
					isItSameRow = false,
					clickedRow = $(this);

				/* Before anything reset any existing active row */
				var previousActiveRow = $("div.wcff-meta-row.active");

				/* Check whether both item are same */
				if ($(this).parent().attr("data-key") == previousActiveRow.attr("data-key")) {
					isItSameRow = true;
				}

				if (previousActiveRow.length > 0) {
					previousActiveRow.find("div.wcff_fields_factory").toggle("slow", "swing", function () {
						/* Update the dirtyField */
						me.activeField = me.fetchFieldConfig();
						me.dirtyFields[me.activeField["key"]] = me.activeField;

						previousActiveRow.find("input[name=wcff-field-type-meta-label-temp]").parent().html($("input[name=wcff-field-type-meta-label-temp]").val());
						me.configWidgets[previousActiveRow.attr("data-key")] = previousActiveRow.find("div.wcff_fields_factory").clone();
						previousActiveRow.find("div.wcff_fields_factory").remove();
						previousActiveRow.removeClass("active");
						/* delegate to clicked row handler */
						if (!isItSameRow) {
							me.handleFieldConfigClick(clickedRow);
						}
					});
				} else {
					if (!isItSameRow) {
						e.data.handleFieldConfigClick(clickedRow);
					}
				}

			});

			$(document).on("click", "a.wcff-field-update-btn", this, function (e) {
				/* Make sure the active row is updated */
				e.data.activeRow = $(this).closest("div.wcff-meta-row");
				e.data.activeField = e.data.fetchFieldConfig();
				/* Update dirty fields pool */
				e.data.dirtyFields[e.data.activeField["key"]] = e.data.activeField;

				e.data.prepareRequest("PUT", "field", e.data.activeField, e.data.activeRow);
				e.data.mask.doMask(e.data.activeRow);
				e.data.dock();
				e.preventDefault();
			});

			$(document).on("click", "label.wcff-switch, div.wcff_fields_factory_config_container", this, function (e) {
				e.stopPropagation();
			});

			$(document).on("change", "label.wcff-switch input", this, function (e) {
				e.stopPropagation();
				var _status = $(this).is(":checked") ? true : false;
				e.data.mask.doMask($("#wcff-fields-set"));
				e.data.prepareRequest("PUT", "toggle_field", { key: $(this).parent().attr("data-key"), status: _status }, null);
				e.data.dock();
			});

			$(document).on("click", "a.wcff-field-delete", this, function (e) {
				uc = confirm("Are you sure, you want to delete this field.?");
				if (uc === true) {
					e.data.mask.doMask($(this).closest(".wcff_fields_factory_header"));
					e.data.prepareRequest("DELETE", "field", { field_key: $(this).attr("data-key") }, $(this));
					e.data.dock();
				}
				e.preventDefault();
				e.stopPropagation();
			});

			$(document).on("click", "div.wcff-factory-tab-header > a", this, function (e) {
				e.preventDefault();
				var ftype = e.data.activeField["type"],
					wrapper = $(this).closest(".wcff_fields_factory_config_container");

				wrapper.find("> div.wcff-factory-tab-header > a").removeClass();
				$(this).addClass("selected");
				wrapper.find("> div.wcff-factory-tab-container > div").fadeOut();
				wrapper.find($(this).attr("href")).fadeIn();

				if (ftype == "radio" || ftype == "select") {
					var rule_expected = wrapper.find("select[class*=choice-expected-value]");
					var defVal = "";
					for (var i = 0; i < rule_expected.length; i++) {
						defVal = $(rule_expected[i]).val();
						/* This is necessary as some where is getting replaced with ; */
						var choices = e.data.activeField["choices"].replace(/;/g, "\n");
						choices = choices.trim().split("\n");
						if (choices) {
							var html = "",
								opt = [];
							for (var j = 0; j < choices.length; j++) {
								opt = choices[j].split("|");
								html += '<option value="' + opt[0] + '">' + opt[1] + '</option>';
							}
							$(rule_expected[i]).html(html);
						}
						if (defVal != "") {
							$(rule_expected[i]).val(defVal);
						}
					}
				} else if (ftype == "colorpicker") {
					if (wrapper.find("[name=wcff-field-type-meta-palettes]").length != 0) {
						e.data.activeField["choices"] = wrapper.find("[name=wcff-field-type-meta-palettes]").val().trim().replace("\n", ",");
					}
				}
			});

			/* Click handler for Adding Condition */
			$(document).on("click", "a.condition-add-rule", this, function (e) {
				e.data.addCondition($(this));
				e.preventDefault();
			});

			/* Click handler for Removing Condition */
			$(document).on("click", "a.condition-remove-rule", this, function (e) {
				e.data.removeRule($(this));
				e.preventDefault();
			});

			/* Click handler for Adding Condition Group */
			$(document).on("click", "a.condition-add-group", this, function (e) {
				e.data.addConditionGroup($(this));
				e.preventDefault();
			});

			/* Click handler for Adding Location Rule */
			$(document).on("click", "a.location-add-rule", this, function (e) {
				e.data.addLocation($(this));
				e.preventDefault();
			});

			/* Click handler for Removing Location Rule */
			$(document).on("click", "a.location-remove-rule", this, function (e) {
				e.data.removeRule($(this));
				e.preventDefault();
			});

			/* Click handler for Adding Location Group Rule */
			$(document).on("click", "a.location-add-group", this, function (e) {
				e.data.addLocationGroup($(this));
				e.preventDefault();
			});

			/* Click handler for Pricing rule add button */
			$(document).on("click", ".wcff-add-price-rule-btn", this, function (e) {
				e.data.addFieldLevelRule($(this), "pricing");
			});

			/* Click handler for Pricing rule add button */
			$(document).on("click", ".wcff-add-fee-rule-btn", this, function (e) {
				e.data.addFieldLevelRule($(this), "fee");
			});

			/* Click handler for Field rule add button */
			$(document).on("click", ".wcff-add-field-rule-btn", this, function (e) {
				e.data.addFieldLevelRule($(this), "field");
			});

			/**/
			$(document).on("click", ".wcff-add-color-image-rule-btn", this, function (e) {
				e.data.addFieldLevelRule($(this), "color-image");
			});

			/* Change handler for Condition Param - it has to reload the target ( Product List, Cat List, Tag List ... ) */
			$(document).on("change", ".wcff_condition_param", this, function (e) {
				e.data.prepareRequest("GET", $(this).val(), {}, $(this));
				e.data.dock();
			});

			/* Condition param for variation product */
			$(document).on("change", ".variation_product_list", this, function (e) {
				e.data.prepareRequest("GET", "product_variation", { "product_id": $(this).val() }, $(this));
				e.data.dock();
			});

			/* Change handler for Location Param - it has to reload the target ( Tab List, Meta Box Context List ... ) */
			$(document).on("change", ".wcff_location_param", this, function (e) {
				e.data.prepareRequest("GET", $(this).val(), {}, $(this));
				e.data.dock();
			});

			$(document).on("click", ".wcff-rule-toggle > a", function (e) {
				$(this).parent().find("a").removeClass("selected");
				$(this).addClass("selected");

				if ($(this).parent().is(".amount-mode")) {
					var label = $(this).attr("data-tprice") === "cost" ? "Amount" : "Percentage";
					if ($(this).parent().is(".pricing-amount-mode")) {
						if (label === "Percentage") {
							$(this).closest("tr").find("a.price-rule-change").remove();
						} else {
							$(this).closest("tr").find("div.calculation-mode").append($('<a href="#" data-ptype="change" title="Replace the original product price with this amount" class="price-rule-change">Replace</a>'));
						}
						$(this).closest("tr").find(".wcff-pricing-rules-amount").prev().html(label);
					} else if ($(this).parent().is(".fee-amount-mode")) {
						$(this).closest("tr").find(".wcff-fee-rules-amount").prev().html(label);
					}
				}

				if ($(this).parent().is(".wcff-color-image-toggle")) {
					$(this).parent().parent().parent().find("div.wcff-image-selector-container").toggle();
					$(this).parent().parent().parent().find("div.wcff-url-selector-container").toggle();
				}
				e.preventDefault();
			});

			if ($("#wcff-fields-set").length != 0) {				
				$("#wcff-fields-set").sortable({
					update: function () {
						var order = wcff_var.post_type == "wcccf" ? 1 : 0;
						$('.wcff-meta-row:not([data-unremovable="true"][data-is_enable="false"])').each(function () {
							if (!$(this).is("#wcff-add-field-placeholder")) {
								$(this).find("input.wcff-field-order-index").val(order);
								$(this).find("span.wcff-field-order-number").text((wcff_var.post_type == "wcccf" ? order : (order + 1)));
								order++;
							}
						});
					},
					cancel: ".active, #wcff-add-field-placeholder, .wcff-field-config-drawer-opened, .wcff-field-delete, .wcff-meta-option"
				});								
			}

			$(document).on("click", "td.field-label input", function (e) {
				e.stopPropagation();
			});

			/* Keyup hanlder for Choices textarea - which is used to generate default options ( select, radio and check box ) */
			$(document).on( "keyup", "textarea.wcff-choices-textarea", this, function(e) {
				e.data.handleDefault($(this));
			});

			$(document).on("blur", "td.field-label input, div.wcff-field-types-meta input, div.wcff-field-types-meta textarea", this, function (e) {
				if (!$(this).hasClass("wcff-option-label-text") && !$(this).hasClass("wcff-option-value-text")) {
					e.data.updateField();
				}
			});

			/* Change event handler for validtaing Choice's label and value text bix - Choice Widget */
			$(document).on("change", ".wcff-option-value-text, .wcff-option-label-text", this, function (e) {
				if ($(this).val() == "") {
					$(this).addClass("invalid");
				} else {
					$(this).removeClass("invalid");
				}
			});

			/* Click handler for add option button - Choice Widget */
			$(document).on("click", "button.wcff-add-opt-btn", this, function (e) {
				e.data.addOption($(this));
				e.preventDefault();
				e.stopPropagation();
			});

			/* Click hanlder tab headers - specifically for datepicker config */
			$(document).on("click", "div.wcff-factory-tab-left-panel li", this, function (e) {
				$(this).parent().parent().next().find(">div").hide()
				$(this).parent().find("> li").removeClass();
				$(this).addClass("selected");
				$(this).parent().parent().next().find(">div:nth-child(" + ($(this).index() + 1) + ")").show();
			});

			/* Click hanlder for clearing Week ends and Week days radio buttons */
			$(document).on("click", "a.wcff-date-disable-radio-clear", this, function (e) {
				$(this).parent().prev().find("input").prop("checked", false);
				e.preventDefault();
			});

			/* Change event handler for File preview option radio button */
			$(document).on("change", "input[name=wcff-field-type-meta-img_is_prev]", this, function (e) {
				if ($(this).val() === "yes") {
					$("div[data-param=img_is_prev_width]").fadeIn();
				} else {
					$("div[data-param=img_is_prev_width]").fadeOut();
				}
				e.preventDefault();
			});

			/* Keyup hanlder for Choices textarea - which is used to generate default options ( select, radio and check box ) */
			$(document).on("keyup", "textarea.wcff-field-type-meta-choices", this, function (e) {
				e.data.handleDefault($(this));
			});

			$(document).on("change", ".wcff-color-image-select-container input[type=radio]", function () {
				if ($(this).is(":checked")) {
					$(this).closest(".wcff-color-image-select-container").find(".color-active").removeClass("color-active");
					$(this).closest(".wcff-color-image-select-container").find("input").prop("checked", false);
					$(this).prop("checked", true);
					$(this).parent().addClass("color-active");
				}
			});

			$(document).on("click", ".wcff-upload-custom-img", this, function (e) {
				e.preventDefault();
				var image_sel_holder = $(this).parent().parent(),
					image_prev = image_sel_holder.find(".wcff-prev-image"),
					image_url = image_sel_holder.find(".wcff-image-url-holder"),
					addImgLink = image_sel_holder.find(".wcff-upload-custom-img"),
					delImgLink = image_sel_holder.find(".wcff-delete-custom-img");
				// If the media frame already exists, reopen it.
				if (e.data.mediaFrame) {
					e.data.mediaFrame.open();
					return;
				}
				e.data.mediaFrame = wp.media({
					title: 'Select or Upload Media Of Your Chosen',
					button: {
						text: 'Use this Image'
					},
					multiple: false
				});
				e.data.mediaFrame.on('select', function () {
					var attachment = e.data.mediaFrame.state().get('selection').first().toJSON();
					image_prev.replaceWith('<img class="wcff-prev-image" src="' + attachment.url + '" alt="" style="width:80px;"/>');
					image_url.val(attachment.id);
					addImgLink.addClass('hidden');
					delImgLink.removeClass('hidden');
				});
				e.data.mediaFrame.open();
			});

			$(document).on("click", ".wcff-delete-custom-img", this, function (e) {
				e.preventDefault();
				var image_sel_holder = $(this).parent().parent(),
					image_prev = image_sel_holder.find(".wcff-prev-image"),
					image_url = image_sel_holder.find(".wcff-image-url-holder"),
					addImgLink = image_sel_holder.find(".wcff-upload-custom-img");
				image_prev.replaceWith('<img class="wcff-prev-image" src="' + wcff_var.plugin_dir + '/assets/img/placeholder-image.jpg" alt="" style="width:80px;"/>');
				$(this).addClass('hidden');
				addImgLink.removeClass('hidden');
				image_url.val('');
			});

			$(document).on("click", "div.wcff-variation-config-tab-header > a", this, function (e) {
				$("div.wcff-variation-config-tab-content > div").hide();
				$(this).siblings().removeClass("selected");

				$($(this).attr("href")).show();
				$(this).addClass("selected");

				$("a.wcff-variation-config-action-btn").hide();
				if ($(this).attr("href") == "#wcff-variation-config-fields") {
					$("a.wcff-variation-config-action-btn.new").show();
				} else {
					$("a.wcff-variation-config-action-btn.cancel").show();
					$("a.wcff-variation-config-action-btn.save").show();
					/* Instantiate wccvf_grid instance */
					e.data.mapping_grid = new wccvf_grid($, $("div.wcff-variation-config-mapping-content"));
					e.data.mapping_grid.init();
				}
				e.preventDefault();
			});

			$(document).on("click", "a.wcff-variation-config-action-btn", this, function (e) {				
				if (!$(this).hasClass("new")) {
					e.preventDefault();
				}
			});

			$(document).on("click", ".wcff-variation-config-search-field", this, function (e) {
				e.stopPropagation();
				/* Make sure the ajax flaq is true */
				if (!e.data.ajaxFlaQ) {
					return;
				}

				/* add back ghost for clarity */
				if ($("div.variation-config-ghost-back").length === 0) {
					$("body").prepend($('<div class="variation-config-ghost-back"></div>'));
				}

				let payload = {},
					pbox = $("#wcff-variation-config-product-select");				

				/* If it is variation search then make sure the product search box shown and has product selected */
				if ($(this).attr("data-type") == "variations" && pbox.find("> li").length > 1) {
					if (!pbox.is(":visible")) {
						pbox.fadeIn("normal");
					}
					/* Make sure it has a product selected */
					if (pbox.find("a.selected").length == 0) {
						alert("Please choose a Product First");
						$("#wcff-variation-config-product-search").focus();
						return;
					}
				} else if ($(this).attr("data-type") == "variations" && pbox.find("> li").length == 0) {
					alert("Please choose a Product First");
					$("#wcff-variation-config-product-search").trigger("click");
					$("#wcff-variation-config-product-search").focus();
					return;
				}

				/* Show the loading gif */
				$(this).parent().find(">img").show();

				if ($(this).attr("data-type") == "variations") {
					e.data.prepareRequest("GET", "search", { "search": $(this).val(), "post_type": $(this).attr("data-type"), "parent": pbox.find("a.selected").attr("data-id"), "context": "variable_mapping" }, $(this));
				} else {
					let ptype = $(this).attr("data-type");
					if ($(this).attr("data-type") == "wccvf-specific") {
						ptype = "wccvf";
						e.data.currentProduct = $(this).parent().attr("data-pid");
						e.data.currentVariation = $(this).parent().attr("data-vid");

						e.data.currentWccvfSearchField = $(this);
						e.data.currentWccvfSearchField.parent().css("z-index", "99999");

						e.data.mapping_grid.currentVariant = $(this).parent().attr("data-vid");
						e.data.mapping_grid.targetRow = $(this).closest("div.wccvf-data-grid-row").prev();
					} else {
						if ($(this).attr("data-type") == "wccvf-all") {
							ptype = "wccvf";
						}
						if (e.data.currentWccvfSearchField) {
							e.data.currentWccvfSearchField.parent().css("z-index", "9");
						}
						e.data.currentProduct = null;
						e.data.currentVariation = null;
						e.data.currentWccvfSearchField = null;
					}

					payload = {"search": $(this).val(), "post_type": ptype, "context": "variable_mapping"};
					if (ptype == "product_variation") {
						payload["page"] = 1;
					}

					e.data.prepareRequest("GET", "search", payload, $(this));
				}
				e.data.dock();

			});

			$(document).on("keydown", ".wcff-variation-config-search-field", this, function (e) {
				var req = null,
					me = e.data,
					payload = {};
				if (e.data.searchTimer) {
					clearTimeout(e.data.searchTimer);
				}

				/* Don't react for ESC key press */
				if (e.keyCode == 27) {
					return;
				}

				var pbox = $("#wcff-variation-config-product-select");
				/* If it is variation search then make sure the product search box shown and has product selected */
				if ($(this).attr("data-type") == "variations" && pbox.find("> li").length > 1) {
					if (!pbox.is(":visible")) {
						pbox.fadeIn("normal");
					}
					/* Make sure it has a product selected */
					if (pbox.find("a.selected").length == 0) {
						alert("Please choose a Product First");
						$("#wcff-variation-config-product-search").focus();
						return;
					}
				} else if ($(this).attr("data-type") == "variations" && pbox.find("> li").length == 0) {
					alert("Please choose a Product First");
					$("#wcff-variation-config-product-search").trigger("click");
					$("#wcff-variation-config-product-search").focus();
					return;
				}

				/* Show the loading gif */
				$(this).parent().find(">img").show();
				if ($(this).attr("data-type") == "variations") {
					req = e.data.prepareSearchRequest("GET", "search", { "search": $(this).val(), "post_type": $(this).attr("data-type"), "parent": pbox.find("a.selected").attr("data-id"), "context": "variable_mapping" }, $(this));
				} else {
					payload = {"search": $(this).val(), "post_type": $(this).attr("data-type"), "context": "variable_mapping"};
					if ($(this).attr("data-type") == "product_variation") {
						payload["page"] = 1;
					}
					req = e.data.prepareSearchRequest("GET", "search", payload, $(this));
				}
				e.data.searchTimer = setTimeout(function () {
					me.searchDock(req);
				}, 200);

			});

			$(document).on("click", "ul.wcff-variation-config-popup a", this, function (e) {
				e.preventDefault();
				/* Make sure the ajax flaq is true */
				if (!e.data.ajaxFlaQ) {
					return;
				}

				if ($(this).closest("ul").hasClass("individual")) {				
					let map = {};
					map[$(this).attr("data-id")] = [[{"context": "variations", "logic": "==", "endpoint": e.data.currentVariation}]];
					e.data.mask.doMask(e.data.currentWccvfSearchField.next());
					e.data.prepareRequest("POST", "variation_fields_map", { "rules": map, "product": $(this).closest("div.wcff-variation-mapper-for-variation").attr("data-pid") }, null);
					e.data.dock();
				} else {
					if ($(this).closest("ul").attr("data-type") == "product") {
						$(this).closest("ul").find("a").removeClass("selected");
						$(this).addClass("selected");
						$(this).closest("ul").next().show();
						e.data.prepareRequest("GET", "search", { "search": $("#wcff-variation-config-variation-search").val(), "post_type": "variations", "parent": $(this).attr("data-id"), "context": "variable_mapping" }, $(this));
						e.data.dock();
					} else {
						if ($(this).hasClass("selected")) {
							$(this).removeClass("selected");
						} else {
							$(this).addClass("selected");
						}
					}
				}
			});

			$(document).on("click", "#wcff-variation-config-map-btn", this, function (e) {
				e.data.handleVariationFieldsMap();
			});

			$(document).on("click", "a.wcff-field-clone", this, function (e) {
				e.data.prepareRequest("GET", "wcff_field_clone", { "fkey": $(this).attr("data-key") }, $(this));
				e.data.dock();
				e.preventDefault();
				e.stopPropagation();
			});

			$(document).on("change", "select.wcff-field-input-condition-value", this, function (e) {
				if ($(this).val() == "not-null") {
					$(this).closest("div.rule-section").next().fadeOut("normal");
					$(this).closest("div.rule-section").next().find("input.wcff-field-input-expected-value").val("");
				} else {
					$(this).closest("div.rule-section").next().fadeIn("normal");
				}
			});

			$(document).on("change", "input.wcff-group-authorized-only-radio", this, function (e) {
				if ($(this).val() === "yes") {
					$("#wcff-target-roles-container").fadeIn("normal");
				} else {
					$("#wcff-target-roles-container").fadeOut("normal");
				}
			});

			/**/
			$(document).on("change", "input.wcff-field-type-meta-show_on_product_page", this, function (e) {
				var display = "table-row";
				if ($(this).val() === "no") {
					display = "none";
				}
				$("div.wcff-field-types-meta").each(function () {
					var flaq = false;
					if ($(this).attr("data-param") === "visibility" ||						
						$(this).attr("data-param") === "login_user_field" ||
						$(this).attr("data-param") === "cart_editable" ||
						$(this).attr("data-param") === "cloneable" ||
						$(this).attr("data-param") === "show_as_read_only" ||
                        $(this).attr("data-param") === "hide_when_no_value" ||
						$(this).attr("data-param") === "show_with_value" ||
						$(this).attr("data-param") === "showin_value") {
						flaq = true;
					}
					if (flaq) {
						if (display == "none") {
							$(this).closest("tr").fadeOut();
						} else {
							$(this).closest("tr").fadeIn();
						}
						//$(this).closest("tr").css("display", display);
					}
				});
			});

			$(document).on("change", ".wcff-field-type-meta-login_user_field", this, function (e) {
				var display = ($(this).val() === "no") ? "none" : "table-row";
				$(this).closest(".wcff-meta-row").find("div[data-param=show_for_roles]").closest("tr").css("display", display);
			});

			$(document).on("click", "a.wcff-date-disable-radio-clear", this, function (e) {
				$(".wcff-field-type-meta-weekend_weekdays").prop("checked", false);
				e.preventDefault();
			});

			$(document).on("click", "div.wcff-factory-lister-tab-header a", this, function (e) {
				e.preventDefault();
				$(this).addClass("selected").siblings().removeClass();
				$(this).parent().next().find("> div").hide();
				$($(this).attr("href")).show();
				if ($(this).attr("href") == "#wcff-fields-layout-container") {
					/* Fetch fields list */
					e.data.prepareRequest("GET", "wcff_field_list", {}, $("#wcff-fields-layout-container"));
					e.data.dock();
				}
			});

			$(document).on("change", "input[name=wcff_use_custom_layout]", this, function (e) {
				if ($(this).is(":checked")) {
					$("#wcff-layout-designer-pad").css("opacity", "1").css("pointer-events", "auto");
					$("#wcff-layout-designer-field-list").css("opacity", "1").css("pointer-events", "auto");
				} else {
					$("#wcff-layout-designer-pad").css("opacity", ".5").css("pointer-events", "none");
					$("#wcff-layout-designer-field-list").css("opacity", ".5").css("pointer-events", "none");
				}
			});

			$(document).on("change", "input[name=options-render_method], input[name=wcff-default-choice]", this, function (e) {
				e.data.prepareRadioOptionPreviewView();
			});

			$(document).on("click", "a.wcff-button-remove", function (e) {

				if ($(this).parent().parent().find("div.wcff-pricing-row").length == 1 || $(this).parent().parent().find("div.wcff-fee-row").length == 1) {
					/* Show empty rule message */
					$(this).parent().parent().find("div.wcff-rule-container-is-empty").show();
				}

				$(this).parent().remove();
				e.preventDefault();
			});

			/* Timepicker addon option change event handler */
			$(document).on("change", "input[name=options-timepicker]", this, function (e) {
				var display = ($(this).val() === "no") ? "none" : "table-row";
				$("div[data-param=min_max_hours_minutes]").closest("tr").css("display", display);
			});

			/* Wccvf grid event - Product Click */
			$(document).on("click", "a.wccvf-grid-map-product-link", this, function (e) {
				e.data.mapping_grid.renderVariations($(this), false);
				e.preventDefault();
			});

			$(document).on("click", "a.wccvf-grid-map-variation-link", this, function (e) {
				e.data.currentProduct = $(this).attr("data-pid");
				e.data.currentVariation = $(this).attr("data-vid");
				e.data.mapping_grid.renderMappedGroups($(this));
				e.preventDefault();
			});

			$(document).on("click", "a.wccvf-grid-group-remove-btn", this, function (e) {
				e.preventDefault();
				/* Set the reloading flaq true, because thats what happens next */
				//e.data.mapping_grid.isReloading = true;
				//e.data.mapping_grid.reloadingFor = "remove";
				e.data.mapping_grid.currentVariant = $(this).attr("data-vid");
				e.data.mapping_grid.targetRow = $(this).closest("div.wccvf-data-grid-row").prev();
				e.data.mask.doMask(e.data.mapping_grid.gridTable);
				e.data.prepareRequest("DELETE", "mapping", { "pid": $(this).attr("data-gid"), "vid": $(this).attr("data-vid") }, $(this));
				e.data.dock();
			});

			$(document).on("click", "a.wccvf-grid-page-btn", this, function (e) {
				e.preventDefault();
				$(this).closest("ul").find("a").removeClass("current");
				//$(this).addClass("current");				
				e.data.mapping_grid.handlePageClick($(this).attr("data-page"));
			});

			$(document).on("keyup", "#wccvf-grid-search-map-txt", this, function (e) {
				e.data.mapping_grid.handleSearch($(this));
			});

			$(document).on("mousedown", "select.wcff_condition_value, select.variation_product_list", this, function(e) {

				if ($(this).hasClass("variation-select")) {
					return true;
				}

				/* Hide existing product selector widgets */
				$("div.wcff-target-selector").hide();

				let target_type = $(this).parent().prev().prev().find("select").val();
				if (target_type != "product_type") {
					e.preventDefault();
					this.blur();
   					window.focus();	
					e.data.currentProductSearchField = $(this);
                    if ($(this).parent().find("div.wcff-target-selector").length == 0) {
                        e.data.prepareTargetSelectorWidget($(this), target_type);						
                    } else {
						$(this).parent().find("div.wcff-target-selector").show();
					}		                    			
				}

			});

			$(document).on("click", "div.wcff-target-select-result > a", this, function(e) {				
				e.preventDefault();
			});

			/**
			 * 
			 * Pagination button handler for variation mapping product search popup
			 * 
			 */
			$(document).on("click", "li.variation-popup-pagination > button", this, function(e) {	
				/* Make sure the ajax flaq is true */
				if (!e.data.ajaxFlaQ) {
					return;
				}

				let page = parseInt($(this).parent().attr("data-page"));
				let searchTxt = $(this).parent().parent().prev().val();

				if ($(this).hasClass("prev")) {
					page--;
				} else {
					page++;
				}

				let payload = { 
					"search": searchTxt, 
					"post_type": "product_variation", 
					"parent": 0, 
					"page": page, 
					"context": "variation_mapping" 
				};

				e.data.mask.doMask($(this).closest("ul.wcff-variation-config-popup"));
				e.data.prepareRequest("GET", "search", payload, $(this).closest("ul.wcff-variation-config-popup"));
				e.data.dock();
				e.preventDefault();

			});

			/**
			 * 
			 * Pagination button handler for Target entity search widget
			 * 
			 */
			$(document).on("click", "div.wcff-target-select-pagination > button", this, function(e) {	
				/* Make sure the ajax flaq is true */
				if (!e.data.ajaxFlaQ) {
					return;
				}

				let page = parseInt($(this).parent().attr("data-page"));			
				let searchTxt = $(this).closest("div.wcff-target-selector").find("input.wcff-target-select-search").val();
				if ($(this).hasClass("prev")) {
					page--;
				} else {
					page++;
				}

				e.data.mask.doMask($(this).closest("div.wcff-target-selector"));
				let _type = $(this).closest("div.wcff-target-selector").attr("data-type");

				let payload = { 
					"search": searchTxt, 
					"post_type": _type, 
					"parent": 0, 
					"page": page, 
					"context": "product_mapping" 
				};
				
				if (_type == "product_cat" || _type == "product_tag") {
					payload["taxonomy"] = _type;
				}

				e.data.prepareRequest("GET", "search", payload, $(this).closest("div.wcff-target-selector"));
				e.data.dock();
				e.preventDefault();
			});

			$(document).on("keyup", ".wcff-target-select-search", this, function (e) {

				var req = null,
					me = e.data,
					_type = "",
					payload = {};
				if (e.data.searchTimer) {
					clearTimeout(e.data.searchTimer);
				}

				/* Don't react for ESC key press */
				if (e.keyCode == 27) {
					//e.data.currentProductSearchField.html('<option value="-1">All Products</option>');
					$(this).closest("div.wcff-target-selector").hide();
					return;
				}
				e.data.mask.doMask($(this).next());
				e.data.target = $(this).closest("div.wcff-target-selector");

				payload = { "search": $(this).val(), "post_type": "product", "page": 1, "context": "product_mapping" };

				_type = $(this).closest("div.wcff-target-selector").attr("data-type");
				payload["post_type"] = _type;
				if (_type == "product_cat" || _type == "product_tag") {
					payload["taxonomy"] = _type;
				}				

				req = e.data.prepareSearchRequest("GET", "search", payload);				
				e.data.searchTimer = setTimeout(function () {
					me.searchDock(req);
				}, 250);

			});

			$(document).on("click", "div.wcff-target-select-result > a", this, function(e) {
				e.data.currentProductSearchField.html('<option value="-1">All Products</option><option value="'+ $(this).attr("data-id") +'" selected>'+ $(this).text() +'</option>');
				e.data.currentProductSearchField.trigger("change");
				$(this).closest("div.wcff-target-selector").hide();
				e.preventDefault();
			});

			$(document).on("click", "div.wcff-meta-row.active label.wcff-field-name", this, function(e) {
				/* Make it editable */
				$(this).hide();
				$(this).after($('<input type="text" class="wcff-field-key-edit-txt" value="'+ $(this).text() +'"/>'));
				e.stopPropagation();
			});

			$(document).on("keydown", "input.wcff-field-key-edit-txt", this, function(e) {
				
				/* Replace the current key with this updated one */
				if (e.keyCode == 13) {

					let old_key = e.data.activeField["key"];				

					/* Make sure the active row is updated */
					e.data.activeRow = $(this).closest("div.wcff-meta-row");
					e.data.activeField = e.data.fetchFieldConfig();

					/* Update dirty fields pool */
					e.data.activeField["key"] = $(this).val();
					e.data.dirtyFields[e.data.activeField["key"]] = JSON.parse(JSON.stringify(e.data.activeField));

					/* Remove the old key */
					delete e.data.dirtyFields[old_key];
					
					$(this).prev().show().html($(this).val());
					$(this).remove();

					/* Update other attrbutes that uses field_key */
					e.data.activeRow.attr("data-key", e.data.activeField["key"]);
					e.data.activeRow.find("td.field-actions").find("a").attr("data-key", e.data.activeField["key"]);
					e.data.activeRow.find("td.field-actions").find("label.wcff-switch").attr("data-key", e.data.activeField["key"]);
					
					e.data.activeField["to_be_removed"] = old_key;
					e.data.prepareRequest("PUT", "field", e.data.activeField, e.data.activeRow);
					e.data.mask.doMask(e.data.activeRow);
					e.data.dock();

				}
				e.stopPropagation();
			});

			$(document).on("click", "input.wcff-upload-image-radio-btn", this, function (e) {

				var me = e.data;
				var btn = $(this);

				var custom_uploader = wp.media({
					title: 'Insert image',
					library: {
						type: 'image'
					},
					button: {
						text: 'Use this image'
					},
					multiple: false
				}).on('select', function () {					
					var attachment = custom_uploader.state().get('selection').first().toJSON();
					if (Array.isArray(me.activeField["images"]) || !me.activeField["images"]) {
						me.activeField["images"] = {};
					}
					me.activeField.images[btn.attr("data-option")] = {
						aid: attachment.id,
						url: attachment.url
					}
					me.prepareRadioOptionPreviewView();
				}).open();

			});

			$(document).on("click", "div.wcff-image-button-preview-wrapper > a", this, function (e) {
				e.preventDefault();
				var opt = $(this).parent().attr("data-option");
				if (e.data.activeField["images"] && e.data.activeField["images"][opt]) {
					delete e.data.activeField["images"][opt];
					e.data.prepareRadioOptionPreviewView();
				}
			});

			$(document).on("click", "button.wcff-factory-multilingual-label-btn, button.wcff-factory-multilingual-btn", function (e) {
				if ($(this).hasClass("wcff-factory-multilingual-btn")) {
					$(this).nextAll("div.wcff-locale-list-wrapper").first().toggle("normal");
				} else {
					$(this).next().toggle("normal");
				}
				e.preventDefault();
				e.stopPropagation();
			});

			$(document).on("change", "#wcff-option-render-label", this, function (e) {
				if ($(this).is(":checked")) {
					$("#wcff-preview-label-pos-select").show();
				} else {
					$("#wcff-preview-label-pos-select").hide();
				}
				e.data.prepareRadioOptionPreviewView();
			});

			$(document).on("change", "#wcff-render-option-label-position", this, function (e) {
				e.data.prepareRadioOptionPreviewView();
			});

			$(document).on("change", "textarea[name=wcff-field-type-meta-choices]", this, function (e) {
				/* Don't react for ESC key press */
				if (e.keyCode == 27) {
					return;
				}
			});

			$(document).on("change", "select.wcff_location_product_data_value", this, function(e) {
				if ($(this).val() == "wccaf_custom_product_data_tab") {
					$("#wccaf_custom_product_data_tab_title_container").show();
				} else {
					$("#wccaf_custom_product_data_tab_title_container").hide();
				}
			});

			/* Submit action handler for Wordpress Update button */
			$(document).on("submit", "form#post", this, function (e) {
				return e.data.onPostSubmit($(this));
			});

		};

        this.prepareTargetSelectorWidget = function(_item, _type) {

			let placeholder = "";
            let html = '<div class="wcff-target-selector '+ _type +'" data-type="'+ _type +'">';

			if (_type == "product") {
				placeholder = "product";
			} else if (_type == "product_cat") {
				placeholder = "category";
			} else if (_type == "product_tag") {
				placeholder = "tag";
			} else if (_type == "product_variation") {
				placeholder = "variable product";
			}

            html += '<input type="text" placeholder="Search '+ placeholder +' ..." class="wcff-target-select-search '+ _type +'"/>';
            html += '<div class="wcff-target-select-result">';      
			/* Result will be injected here */
            html += '</div>';
            html += '<div class="wcff-target-select-pagination">';
            html += '<button class="prev"><img src="' + wcff_var.plugin_dir + '/assets/img/prev.png" /></button>';
            html += '<button class="next"><img src="' + wcff_var.plugin_dir + '/assets/img/next.png" /></button>';
            html += '</div>';

            html += '</div>'; 
			html = $(html);

            _item.parent().append(html);
			setTimeout(() => {this.mask.doMask(html); html.find("input.wcff-target-select-search").focus();}, 100);			

			let payload = { 
				search: "", 
				post_type: _type, 
				parent: 0, 
				page: 1, 
				context: "product_mapping" 
			};
			
			if (_type == "product_cat" || _type == "product_tag") {
				payload["taxonomy"] = _type;
			}			

			this.prepareRequest("GET", "search", payload, html);
			this.dock();
        };

		this.prepareRadioOptionPreviewView = function () {

			$(".wcff-preview-choice-wrapper").closest("tr").show();

			$("#wcff-option-text-config-container").hide();
			$("#wcff-option-color-config-container").hide();
			$("#wcff-option-image-config-container").hide();
			$("div.wcff-preview-label-opt-container").hide();

			var i = 0,
				html = "",
				selected = "",
				options = $("textarea.wcff-field-type-meta-choices").val(),
				rOpt = $("input[name=options-render_method]:checked").val(),
				parent = $(".wcff-preview-choice-wrapper").closest(".wcff-meta-row"),
				dcontainer = parent.find(".wcff-default-option-holder"),
				default_val = dcontainer.find("input[type=radio]:checked").val(),
				show_label = $("#wcff-option-render-label").is(":checked"),
				label_pos = $("#wcff-render-option-label-position").val();

			options = options.trim();
			options = options.split("\n");

			if (rOpt == "text") {

				$("#wcff-option-text-config-container").show();

				html += '<ul class="wcff-color-preview-option-list">';

				for (i = 0; i < options.length; i++) {
					selected = "";
					keyval = options[i].split("|");
					if (keyval.length == 2 && keyval[0].trim() != "" && keyval[1].trim() != "") {
						
						if (default_val && default_val.trim() === keyval[0].trim()) {
							selected = 'class="selected"';
						}						
						html += '<li ' + selected + '>';
						html += '<div class="wcff-text-button-preview-wrapper">' + keyval[1].trim() + '</div>';
						html += '</li>';
					}
				}

				html += '</ul>';

				$("#wcff-option-text-config-container").html(html);

			} else if (rOpt == "color") {

				$("#wcff-option-color-config-container").show();
				$("div.wcff-preview-label-opt-container").show();

				html += '<ul class="wcff-color-preview-option-list">';
				for (i = 0; i < options.length; i++) {
					selected = "";
					keyval = options[i].split("|");
					if (keyval.length == 2 && keyval[0].trim() != "" && keyval[1].trim() != "") {

						if (default_val && default_val.trim() === keyval[0].trim()) {
							selected = 'class="selected"';
						}
						html += '<li ' + selected + '>';
						if (show_label && label_pos == "top") {
							html += '<label>' + keyval[1] + '</label>';
						}
						html += '<div class="wcff-color-button-preview-wrapper"><span style="background: ' + keyval[0].trim() + '"></span></div>';
						if (show_label && label_pos == "bottom") {
							html += '<label>' + keyval[1] + '</label>';
						}
						html += '</li>';

					}
				}
				html += '</ul>';
				$("#wcff-option-color-config-container").html(html);

			} else if (rOpt == "image") {

				$("#wcff-option-image-config-container").show();
				$("div.wcff-preview-label-opt-container").show();
				
				html += '<ul class="wcff-color-preview-option-list">';
				for (i = 0; i < options.length; i++) {
					selected = "";
					keyval = options[i].split("|");
					if (keyval.length == 2 && keyval[0].trim() != "" && keyval[1].trim() != "") {

						if (default_val && default_val.trim() === keyval[0].trim()) {
							selected = 'class="selected"';
						}

						html += '<li ' + selected + '>';
						if (show_label && label_pos == "top") {
							html += '<label>' + keyval[1] + '</label>';
						}

						if (this.activeField["images"] && this.activeField.images[keyval[0].trim()]) {
							html += '<div class="wcff-image-button-preview-wrapper" data-option="' + keyval[0].trim() + '">';
							html += '<a href="#" class="">x</a>';
							html += '<img src="' + this.activeField.images[keyval[0].trim()].url + '" />';
							html += '</div>';
						} else {
							html += '<div class="wcff-image-button-preview-wrapper">';
							html += '<input type="button" class="wcff-upload-image-radio-btn" data-option="' + keyval[0].trim() + '" value="Set\nImage"/>';
							html += '</div>';
						}

						if (show_label && label_pos == "bottom") {
							html += '<label>' + keyval[1] + '</label>';
						}
						html += '</li>';

					}
				}

				html += '</ul>';
				$("#wcff-option-image-config-container").html(html);

			} else {
				$(".wcff-preview-choice-wrapper").closest("tr").hide();
			}

		};

		this.handleFieldConfigClick = function (_row) {

			/* Update the active fields row reference */
			this.activeRow = _row.closest("div.wcff-meta-row");
			this.activeRow.addClass("opened active");

			/* Check the field's configuration view, whether its already attached */
			if (this.dirtyFields[this.activeRow.attr("data-key")]) {
				/* If the widget is visible, then close it and remove from dom, and store the widget */

				if (this.configWidgets[this.activeRow.attr("data-key")]) {
					/* Not on the dom */

					/* Make the label to editable state */
					this.activeRow.find(".wcff-field-label").html('<input type="text" name="wcff-field-type-meta-label-temp" value="' + this.activeRow.find(".wcff-field-label").text() + '" autocomplete="off">');

					/* Restore the config widget */
					this.activeRow.append(this.configWidgets[this.activeRow.attr("data-key")]);

					/* Remove the widget from pool */
					delete this.configWidgets[this.activeRow.attr("data-key")];

					/* Update active field reference */
					this.activeField = this.dirtyFields[this.activeRow.attr("data-key")];
					this.activeRow.find("div.wcff_fields_factory").toggle("slow", "swing", function () {
						/* Any defered house keeping work */
					});

				} else {
					/* It's in the dom */

					this.activeField = this.fetchFieldConfig();
					this.dirtyFields[this.activeField["key"]] = this.activeField;

					this.activeRow.removeClass("active");
					this.activeRow.find("input[name=wcff-field-type-meta-label-temp]").parent().html($("input[name=wcff-field-type-meta-label-temp]").val());
					/* Store the widget for later restore */
					this.configWidgets[e.data.activeRow.attr("data-key")] = this.activeRow.find("div.wcff_fields_factory").clone();
					/* Remove the widget from dom, to avoid radio button group collision */
					this.activeRow.find("div.wcff_fields_factory").remove();
					/* Reset the activeRow reference */
					this.activeRow = null;
					this.activeField = null;
				}

			} else {
				/* Config widget not yet loaded */
				this.mask.doMask($("#wcff-fields-set"));
				this.activeRow.find(".wcff-field-label").html('<input type="text" name="wcff-field-type-meta-label-temp" value="' + this.activeRow.find(".wcff-field-label").text() + '" autocomplete="off">');
				/* Configuration view not attached, so fetch it from the server */
				this.prepareRequest("GET", "field", { key: this.activeRow.attr("data-key"), type: this.activeRow.attr("data-type") }, this.activeRow);
				this.dock();
			}

		};

		this.addOption = function (_btn) {
			var value = _btn.prevAll("input.wcff-option-value-text").first(),
				label = _btn.prevAll("input.wcff-option-label-text").first();
			if (value.val() == "") {
				value.addClass("invalid");
				value.focus();
			} else {
				value.removeClass("invalid");
			}
			if (label.val() == "") {
				label.addClass("invalid");
				label.focus();
			} else {
				label.removeClass("invalid");
			}
			if (value.val() != "" && label.val() != "") {
				var opt_holder = _btn.closest(".wcff-meta-row").find("textarea[name=" + _btn.attr("data-target") + "]");
				/* Make sure the textarea has newline as last character
				 * As newline is used as delimitter */
				if (opt_holder.val() != "") {
					if (opt_holder.val().slice(-1) != "\n") {
						opt_holder.val(opt_holder.val() + "\n");
					}
				}
				opt_holder.val(opt_holder.val() + (value.val() + "|" + label.val()) + "\n");
				if (_btn.closest(".wcff-locale-block").length == 0) {
					this.activeField["choices"] = opt_holder.val();
				}
				/* Clear the fields */
				value.val("");
				label.val("");
				/* Set the focus to value box
				 * So that user can start input next option */
				value.focus();
				/**/
				this.handleDefault(_btn.closest(".wcff-meta-row").find("textarea[name=" + _btn.attr("data-target") + "]"));
			}
		};

		this.handleTargetProductSearch = function(_req, _res) {

			var _html = '',				
				records = _res.payload.records,
				total_page = Math.ceil(_res.payload.total / _res.payload.records_per_page);
			 	popup = this.target.find("div.wcff-target-select-result"),
				pagination = this.target.find(".wcff-target-select-pagination");

			if (!records) {
				records = [];
			}

			if (records.length > 0) {
				for (let i = 0; i < records.length; i++) {
					_html += '<a href="#" data-id="'+ records[i].id +'">'+ records[i].title +'</a>';
				}
			} else {
				_html = '<p>No record(s) found.!</p>';
			}			

			popup.html(_html);
			pagination.attr("data-page", _res.payload.page);
			/* Handle pagination */
			if (total_page > 1) {
				pagination.removeClass("disable");
				pagination.find("> button").removeClass("disable");
				if (_res.payload.page == 1) {
					/* Disable the prev button */					
					pagination.find("> button:first-child").addClass("disable");
				} else if (_res.payload.page == total_page) {
					/* Disable the next button */					
					pagination.find("> button:last-child").addClass("disable");
				}
			} else {
				pagination.addClass("disable");
			}

			/* Just in case */
			this.mask.doUnMask();	

		};

		this.handleSearch = function (_req, _res) {
			if (_req.payload && _req.payload["post_type"]) {
				var i = 0,
					j = 0,
					html = '',
					flaQ = null,
					sbox = null,
					popup = null,
					records = [];

				if (!_req.payload.parent) {
					if (_req.payload["post_type"] === "product_variation") {
						sbox = $("#wcff-variation-config-product-search");
						popup = $("#wcff-variation-config-product-select");
					} else if (_req.payload["post_type"] === "variations") {
						sbox = $("#wcff-variation-config-variation-search");
						popup = $("#wcff-variation-config-variation-select");
					} else if (_req.payload["post_type"] === "wccvf" && !this.currentWccvfSearchField) {
						sbox = $("#wcff-variation-config-group-search");
						popup = $("#wcff-variation-config-group-select");
					} else if (_req.payload["post_type"] === "wccvf" && this.currentWccvfSearchField) {
						sbox = this.currentWccvfSearchField;
						popup = this.currentWccvfSearchField.next();
					}
				} else {
					sbox = $("#wcff-variation-config-variation-search");
					popup = $("#wcff-variation-config-variation-select");
				}

				if (popup) {
					popup.show();

					/* Match the popup width to the corresponding search field */
					popup.width(popup.prev().outerWidth() - 2);

                    /* Remove first record - since it will have All - (Post Type) entry */
                    _res.payload.records.splice(0, 1);

					records = _res.payload.records;
					if (this.currentWccvfSearchField) {
						popup.next().hide();
						/* If it for varation level mapping - then we need eliminates the wccvf groups that already mapped */
						records = [];
						if (this.currentProduct && this.currentVariation) {
							groups = this.mapping_grid.records[this.currentProduct].variations[this.currentVariation].groups;
							for (i = 0; i < _res.payload.length; i++) {
								flaQ = true;
								for (j = 0; j < groups.length; j++) {
									if (_res.payload[i].id == groups[j].gid) {
										flaQ = false;
										break;
									}
								}
								if (flaQ) {
									records.push(_res.payload[i]);
								}
							}
						}
					} else {
						popup.closest("table").find("img.progress-img").hide();
					}

					/* If it is for variation then filter all variations which has mapping */
					if (_req.payload["post_type"] === "variations") {
						if (this.mapping_grid.records[this.request.payload.parent]) {
							let rIndex = [];
							let variations = this.mapping_grid.records[this.request.payload.parent].variations;
							for (i = 0; i < records.length; i++) {
								if (variations[records[i].id]) {
									rIndex.push(i);
								}
							}
							for (i = (rIndex.length - 1); i >= 0; i--) {
								records.splice(rIndex[i], 1);
							}
						}
					}

					if (records.length > 0) {
						for (i = 0; i < records.length; i++) {
							html += '<li><a href="" data-id="' + records[i].id + '">' + records[i].title + '</a></li>';
						}
					} else {
						html += '<li><p>Nothing left for mapping.!</p></li>';
					}

					/* If it is Variable Product list then init the pagination */
					if (_req.payload["post_type"] === "product_variation") {
						let total_page = Math.ceil(_res.payload.total / _res.payload.records_per_page);
						if (total_page > 1) {

							html += '<li class="variation-popup-pagination" data-page="'+ _res.payload.page +'">';
							html += '<button class="prev '+ ((_res.payload.page == 1) ? 'disable' : '') +'"><img src="' + wcff_var.plugin_dir + '/assets/img/prev.png" /></button>';
							html += '<button class="next '+ ((_res.payload.page == total_page) ? 'disable' : '') +'"><img src="' + wcff_var.plugin_dir + '/assets/img/next.png" /></button>';
						}						
					}

					popup.html(html);
				}
				/* Fix for empty search box - (Some time we have to trigger explicit for empty search) */
				if (sbox && sbox.val() == "" && _req.payload.search != "") {
					_req.payload.search = "";
					this.searchDock(_req);
				}
			}
		};

		this.reloadVariationLevelConfigPopup = function() {

			var i = 0,
				html = "",				
				flaQ = true,
				groups = [],
				popup = null,
				records = [];
			
			this.mask.doMask(this.currentWccvfSearchField.next());
			/* Updating mapping grid meta */
			this.mapping_grid.records = this.response.payload;						
			this.mapping_grid.products = Object.keys(this.mapping_grid.records);			
			this.mapping_grid.totalPages = Math.ceil(this.mapping_grid.products.length / this.mapping_grid.recordsPerPage);

			if (this.currentProduct && this.currentVariation) {
				popup = this.currentWccvfSearchField.next();				
				groups = this.mapping_grid.records[this.currentProduct].variations[this.currentVariation].groups;
				for (i = 0; i < this.wccvfPosts.length; i++) {
					flaQ = true;
					for (j = 0; j < groups.length; j++) {
						if (this.wccvfPosts[i].id == groups[j].gid) {
							flaQ = false;
							break;
						}
					}
					if (flaQ) {
						records.push(this.wccvfPosts[i]);
					}
				}
				if (records.length > 0) {
					for (i = 0; i < records.length; i++) {
						html += '<li><a href="" data-id="' + records[i].id + '">' + records[i].title + '</a></li>';
					}
				} else {
					html += '<li><p>Nothing left for mapping.!</p></li>';
				}
				popup.html(html);
			}
		};

		this.handleVariationFieldsMap = function () {
			var i = 0,
				map = {},
				rule = {},
				rules = [],
				wrapper = {},
				variations = [],
				selectedProductId,
				selectedProductTitle,
				selectedVariations = [],
				selectedWccvfGroups = [],
				selectedGroups = $("#wcff-variation-config-group-select a.selected"),
				selectedVariationsItems = $("#wcff-variation-config-variation-select a.selected");

			if (selectedGroups.length == 0) {
				alert("You have to select one or more Variations to Map");
				return;
			}
			if (selectedVariationsItems.length == 0) {
				alert("You have to select one or more Fields Group to Map");
				return;
			}
			selectedVariationsItems.each(function () {
				variations.push($(this).attr("data-id"));
				/* Used to push new values on wccvf grid records */
				selectedVariations.push({ vid: $(this).attr("data-id"), vtitle: $(this).text() });
			});
			selectedGroups.each(function () {
				rules = [];
				for (i = 0; i < variations.length; i++) {

					rule = [];
					wrapper = {};
					wrapper["context"] = "product_variation";
					wrapper["logic"] = "==";
					wrapper["endpoint"] = variations[i];
					rule.push(wrapper);
					rules.push(rule);

				}
				map[$(this).attr("data-id")] = rules;
				/* Used to update local wcvf grid records */
				selectedWccvfGroups.push({ gid: $(this).attr("data-id"), gtitle: $(this).text() });
			});

			/* Update wccvf grid records */
			selectedProductId = $("#wcff-variation-config-product-select a.selected").attr("data-id");
			selectedProductTitle = $("#wcff-variation-config-product-select a.selected").text();

			if (!this.mapping_grid.records[selectedProductId]) {
				this.mapping_grid.records[selectedProductId] = {
					product_title: selectedProductTitle,
					variations: {}
				};
			}

			for (i = 0; i < selectedVariations.length; i++) {
				this.mapping_grid.records[selectedProductId].variations[selectedVariations[i].vid] = {
					groups: selectedWccvfGroups,
					variation_title: selectedVariations[i].vtitle
				}
			}

			/* Reset the current variation property -  to prevent collision with variation level config mapping */
			this.currentVariation = null;
			this.currentWccvfSearchField = null;
			this.prepareRequest("POST", "variation_fields_map", { "rules": map, "product": $("#wcff-variation-config-product-select a.selected").attr("data-id") }, null);
			this.dock();
		};

		this.handleDefault = function (_option_field) {
			var html = '',
				keyval = [],
				default_val = null,
				options = _option_field.val(),
				parent_field = _option_field.closest(".wcff-meta-row"),
				dcontainer = parent_field.find(".wcff-default-option-holder");

			var locale = _option_field.attr('data-locale');
			var ftype = parent_field.attr("data-type");

			if (typeof locale !== typeof undefined && locale !== false) {
				dcontainer = parent_field.find(".wcff-default-option-holder-" + locale);
			}

			/* Shave of any unwanted character at both ends, includig \n */
			options = options.trim();
			options = options.split("\n");
			/* Handle the default option */
			if (ftype === "checkbox") {
				default_val = dcontainer.find("input[type=checkbox]:checked").map(function () {
					return this.value;
				}).get();
				/* Reset it */
				dcontainer.html("");
				html += '<ul>';
				for (var i = 0; i < options.length; i++) {
					keyval = options[i].split("|");
					if (keyval.length == 2 && keyval[0].trim() != "" && keyval[1].trim() != "") {
						if (default_val && default_val.indexOf(keyval[0]) > -1) {
							html += '<li><input type="checkbox" value="' + keyval[0] + '" checked /> ' + keyval[1] + '</li>';
						} else {
							html += '<li><input type="checkbox" value="' + keyval[0] + '" /> ' + keyval[1] + '</li>';
						}
					}
				}
				html += '</ul>';
				dcontainer.html(html);
			} else if (ftype === "radio") {
				default_val = dcontainer.find("input[type=radio]:checked").val();
				/* Reset it */
				dcontainer.html("");
				html += '<ul>';
				for (var i = 0; i < options.length; i++) {
					keyval = options[i].split("|");
					if (keyval.length == 2 && keyval[0].trim() != "" && keyval[1].trim() != "") {
						if (default_val && default_val === keyval[0]) {
							html += '<li><input name="wcff-default-choice" type="radio" value="' + keyval[0] + '" checked /> ' + keyval[1] + '</li>';
						} else {
							html += '<li><input name="wcff-default-choice" type="radio" value="' + keyval[0] + '" /> ' + keyval[1] + '</li>';
						}
					}
				}
				html += '</ul>';
				dcontainer.html(html);

				/* Handle render option */
				this.prepareRadioOptionPreviewView();

			} else {
				/* This must be select box */
				default_val = dcontainer.find("select").val();
				/* Reset it */
				dcontainer.html("");
				html += '<select>';
				html += '<option value="">-- Choose the default Option --</option>';
				for (var i = 0; i < options.length; i++) {
					keyval = options[i].split("|");
					if (keyval.length == 2 && keyval[0].trim() != "" && keyval[1].trim() != "") {
						if (default_val && default_val === keyval[0]) {
							html += '<option value="' + keyval[0] + '" selected >' + keyval[1] + '</option>';
						} else {
							html += '<option value="' + keyval[0] + '">' + keyval[1] + '</option>';
						}
					}
				}
				html += '</select>';
				dcontainer.html(html);
			}
		};

		this.addCondition = function (target) {
			var ruleTr = $('<tr></tr>');
			ruleTr.html(target.parent().parent().parent().find("tr").last().html());
			if (target.parent().parent().parent().children().length == 1) {
				ruleTr.find("td.remove").html('<a href="#" class="condition-remove-rule wcff-button-remove"></a>');
			}
			target.parent().parent().parent().append(ruleTr);
			ruleTr.find("select.wcff_condition_param").trigger("change");
		};

		this.addLocation = function (target) {
			var locationTr = $('<tr></tr>');
			locationTr.html(target.parent().parent().parent().find("tr").last().html());
			if (target.parent().parent().parent().children().length === 1) {
				locationTr.find("td.remove").html('<a href="#" class="location-remove-rule wcff-button-remove"></a>');
			}
			target.parent().parent().parent().append(locationTr);
			locationTr.find("select.wcff_location_param").trigger("change");
		};

		this.removeRule = function (target) {
			var parentTable = target.parent().parent().parent().parent(),
				rows = parentTable.find('tr');
			if (rows.size() === 1) {
				parentTable.parent().remove();
			} else {
				target.parent().parent().remove();
			}
		};

		this.addConditionGroup = function (target) {
			var groupDiv = $('div.wcff_logic_group:first').clone(true);
			var rulestr = groupDiv.find("tr");
			if (rulestr.size() > 1) {
				var firstTr = groupDiv.find("tr:first").clone(true);
				groupDiv.find("tbody").html("").append(firstTr);
			}
			groupDiv.find("h4").html("or");
			target.prev().before(groupDiv);
			groupDiv.find("td.remove").html('<a href="#" class="condition-remove-rule wcff-button-remove"></a>');
			groupDiv.find("select.wcff_condition_param").trigger("change");
		};

		this.addLocationGroup = function (target) {
			var groupDiv = $('div.wcff_location_logic_group:first').clone(true);
			var rulestr = groupDiv.find("tr");
			if (rulestr.size() > 1) {
				var firstTr = groupDiv.find("tr:first").clone(true);
				groupDiv.find("tbody").html("").append(firstTr);
			}
			groupDiv.find("h4").html("or");
			target.prev().before(groupDiv);
			groupDiv.find("td.remove").html('<a href="#" class="location-remove-rule wcff-button-remove"></a>');
			groupDiv.find("select.wcff_condition_param").trigger("change");
		};

		this.addFieldLevelRule = function (_btn, _type) {
			var html = '';
			if (_type !== "color-image") {
				if (this.activeField["type"] === "datepicker") {
					html = this.buildPricingWidgetDatePicker(_type);
				} else if (this.activeField["type"] === "checkbox") {
					html = this.buildPricingWidgetMultiChoices(_type);
				} else if (this.activeField["type"] === "radio"
					|| this.activeField["type"] === "select") {
					html = this.buildPricingWidgetChoice(_type);
				} else {
					html = this.buildPricingWidgetInput(_type);
				}
			} else {
				html = this.addColorImageMapper(_type);
			}
			if (html) {
				_btn.parent().find(".wcff-rule-container-is-empty").hide();
				_btn.parent().find(".wcff-rule-container").append($(html));
			} else {
				_btn.parent().find(".wcff-rule-container-is-empty").show();
			}
		};

		this.renderFieldLevelRules = function (_type, _obj, _aBtn) {
			var widget = "";
			if (this.activeField["type"] === "text" || this.activeField["type"] === "number" ||
				this.activeField["type"] === "textarea" || this.activeField["type"] === "file") {
				widget = $(this.buildPricingWidgetInput(_type));
				widget.find("select.wcff-" + _type + "-input-condition-value").val(_obj.logic);
				widget.find("input.wcff-" + _type + "-input-expected-value").val(this.unEscapeQuote(_obj.expected_value));
			} else if (this.activeField["type"] === "select" || this.activeField["type"] === "radio") {
				widget = $(this.buildPricingWidgetChoice(_type));
				widget.find("select.wcff-" + _type + "-choice-condition-value").val(_obj.logic);
				widget.find("select.wcff-" + _type + "-choice-expected-value").val(_obj.expected_value);
			} else if (this.activeField["type"] === "checkbox") {
				widget = $(this.buildPricingWidgetMultiChoices(_type));
				widget.find("select.wcff-" + _type + "-multi-choice-condition-value").val(_obj.logic);
				if (_obj.expected_value) {
					for (var j = 0; j < _obj.expected_value.length; j++) {
						widget.find("input[type=checkbox][value='" + _obj.expected_value[j] + "']").prop('checked', true);
					}
				}
			} else if (_type === "color-image") {
				widget = $(this.addColorImageMapper(_type));
				widget.find(".wcff-color-image-select-container input[value='" + _obj.expected_value + "']").parent().addClass("color-active").children().prop("checked", true);
				widget.find(".wcff-color-image-toggle a").removeClass("selected");
				widget.find(".wcff-color-image-toggle a[data-type='" + _obj["image_or_url"] + "']").addClass("selected");

				widget.find(".wcff-prev-image").attr("src", _obj["prev_image_url"]);
				widget.find(".wcff-image-url-holder").val(_obj["url"]);
				widget.find(".wcff-upload-custom-img").addClass("hidden");
				widget.find(".wcff-delete-custom-img").removeClass("hidden");
			} else {
				/* This must be date picker */
				widget = $(this.buildPricingWidgetDatePicker(_type));
				widget.find("ul.wcff-" + _type + "-date-type-header li").removeClass("selected");
				var pos = widget.find("ul.wcff-" + _type + "-date-type-header li[data-dtype='" + _obj.expected_value.dtype + "']").addClass("selected").index();
				widget.find("div.wcff-factory-tab-right-panel > div").hide();
				widget.find("div.wcff-factory-tab-right-panel > div:nth-child(" + (pos + 1) + ")").show();

				if (_obj.expected_value.dtype === "days" && _obj.expected_value && _obj.expected_value.value) {
					for (var k = 0; k < _obj.expected_value.value.length; k++) {
						widget.find("input[type=checkbox][value='" + _obj.expected_value.value[k] + "']").prop('checked', true);
					}
				} else if (_obj.expected_value.dtype === "specific-dates") {
					widget.find("textarea.wcff-field-type-meta-specific_dates").val(_obj.expected_value.value);
				} else if (_obj.expected_value.dtype === "weekends-weekdays") {
					widget.find("input[type=radio][value='" + _obj.expected_value.value + "']").prop('checked', true);
				} else {
					widget.find("textarea.wcff-field-type-meta-specific_date_each_months").val(_obj.expected_value.value);
				}
			}

			if (_type === "pricing") {
				widget.find("input.wcff-pricing-rules-title").val(this.unEscapeQuote(_obj.title));
				widget.find("div.calculation-mode > a").removeClass("selected");
				widget.find("div.calculation-mode > a[data-ptype=" + _obj.ptype + "]").addClass("selected");

				widget.find("div.amount-mode > a").removeClass("selected");
				widget.find("div.amount-mode > a[data-tprice=" + _obj.tprice + "]").addClass("selected");
			} else if (_type === "fee") {
				widget.find("input.wcff-fee-rules-title").val(this.unEscapeQuote(_obj.title));
				widget.find("div.amount-mode > a").removeClass("selected");
				widget.find("div.amount-mode > a[data-tprice=" + _obj.tprice + "]").addClass("selected");

				widget.find("div.calculation-mode > a").removeClass("selected");
				widget.find("div.calculation-mode > a[data-is_tx=" + _obj.is_tx + "]").addClass("selected");
			}

			widget.find("input.wcff-" + _type + "-rules-amount").val(_obj.amount);
			_aBtn.append(widget);
		};

		this.buildPricingWidgetInput = function (_type) {
			var html = '<div class="wcff-' + _type + '-row">';
			html += '<table class="wcff-' + _type + '-table"><tr>';
			/* Context section starts here */
			html += '<td class="context">';
			html += '<div class="rule-section">';

			if (this.activeField["type"] === "number") {
				html += '<label>If user entered number</label>';
			} else if (this.activeField["type"] === "colorpicker") {
				html += '<label>If user picked color</label>';
			} else {
				html += '<label>If user entered text</label>';
			}

			html += '<select class="wcff-' + _type + '-input-condition-value">';
			if (this.activeField["type"] === "number") {
				html += '<option value="equal">is equal to</option>';
				html += '<option value="not-equal">is not equal to</option>';
				html += '<option value="less-than">less than</option>';
				html += '<option value="less-than-equal">less than or equal to</option>';
				html += '<option value="greater-than">greater than</option>';
				html += '<option value="greater-than-equal">greater than or equal to</option>';				
			} else {
				html += '<option value="equal">is equal to</option>';
				html += '<option value="not-equal">is not equal to</option>';
			}
			html += '<option value="null">is null</option>';
			html += '<option value="not-null">is not null</option>';
			html += '</select>';

			html += '</div><div class="rule-section">';
			html += '<label>Expected value</label>';
			if (this.activeField["type"] != "colorpicker") {
				html += '<input type="' + (this.activeField["type"] == "textarea" ? "text" : this.activeField["type"]) + '" class="wcff-' + _type + '-input-expected-value" value="">';
			} else {
				html += '<input type="text" class="wcff-' + _type + '-input-expected-value" value="" placeholder="Expected Color.? (Use comma if more then one color value)" />';
			}

			html += '</div></td>';
			/* Context section ends here */

			if (_type !== "field") {
				/* Pricing section starts here */
				html += '<td class="pricing">' + this.buildAmountWidget(_type) + '</td>';
				/* Pricing section ends here */

				/* Mode section starts here */
				html += '<td class="mode">' + this.buildCalculationModeWidget(_type) + '</td></tr></table>';
				/* Mode section ends here */
			} else {
				/* Field rule setter widget */
				html += '<td class="field">' + this.buildFieldsRuleSetter() + '</td></tr></table>';
			}

			html += '<a href="#" class="pricing-remove-rule wcff-button-remove"></a>';
			html += '</div>';

			return html;
		};

		this.buildPricingWidgetChoice = function (_type) {
			var i = 0,
				opt = [],
				html = '',
				choices = [],
				isNumber = false,
				temp_choices = this.activeRow.find("textarea.wcff-field-type-meta-choices").val();

			if (temp_choices && temp_choices != "") {
				choices = temp_choices.trim().split("\n");
				isNumber = this.isNumberChoices(temp_choices);
			} else {
				alert("Please add some options to this " + this.activeField["type"] + " Field.!");
				return null;
			}

			html = '<div class="wcff-' + _type + '-row">';
			html += '<table class="wcff-' + _type + '-table"><tr>';

			/* Context section starts here */
			html += '<td class="context">';
			html += '<div class="rule-section">';
			html += '<label>If user\'s selected option</label>';

			html += '<select class="wcff-' + _type + '-choice-condition-value">';
			if (isNumber) {
				html += '<option value="equal">is equal to</option>';
				html += '<option value="not-equal">is not equal to</option>';
				html += '<option value="less-than">less than</option>';
				html += '<option value="less-than-equal">less than or equal to</option>';
				html += '<option value="greater-than">greater than</option>';
				html += '<option value="greater-than-equal">greater than or equal to</option>';
			} else {
				html += '<option value="equal">is equal to</option>';
				html += '<option value="not-equal">is not equal to</option>';
			}
			html += '</select></div>';
			html += '<div class="rule-section">';
			html += '<label>Expected option</label>';
			html += '<select class="wcff-' + _type + '-choice-expected-value">';

			if (choices) {
				for (i = 0; i < choices.length; i++) {
					opt = choices[i].split("|");
					html += '<option value="' + opt[0] + '">' + opt[1] + '</option>';
				}
			}
			html += '</select></div></td>';
			/* Context section ends here */

			if (_type !== "field") {
				/* Pricing section starts here */
				html += '<td class="pricing">' + this.buildAmountWidget(_type) + '</td>';
				/* Pricing section ends here */

				/* Mode section starts here */
				html += '<td class="mode">' + this.buildCalculationModeWidget(_type) + '</td></tr></table>';
				/* Mode section ends here */
			} else {
				/* Field rule setter widget */
				html += '<td class="field">' + this.buildFieldsRuleSetter() + '</td></tr></table>';
			}

			html += '<a href="#" class="pricing-remove-rule wcff-button-remove"></a>';
			html += '</div>';
			return html;
		};

		this.buildPricingWidgetMultiChoices = function (_type) {
			var i = 0,
				opt = [],
				html = '',
				choices = [],
				temp_choices = this.activeRow.find("textarea.wcff-field-type-meta-choices").val();

			if (temp_choices && temp_choices != "") {
				choices = temp_choices.trim().split("\n");
			} else {
				alert("Please add some options to this " + this.activeField["type"] + " Field.!");
				return null;
			}

			html = '<div class="wcff-' + _type + '-row">';
			html += '<table class="wcff-' + _type + '-table"><tr>';

			/* Context section starts here */
			html += '<td class="context">';
			html += '<div class="rule-section">';
			html += '<label>The option chosen by user</label>';
			html += '<select class="wcff-' + _type + '-multi-choice-condition-value">';
			html += '<option value="has-options">Checked</option>';
			html += '<option value="has-not-options">Not Checked</option>';
			//html += '<option value="is-also">is also these</option>';
			//html += '<option value="any-one-of">any of these</option>';	
			html += '</select></div>';
			html += '<div class="rule-section">';
			html += '<label>Expected option</label>';
			html += '<ul class="wcff-' + _type + '-multi-choices-ul">';
			for (i = 0; i < choices.length; i++) {
				opt = choices[i].split("|");
				html += '<li><label><input type="checkbox" name="wcff-' + _type + '-multi-choice-expected-value" value="' + opt[0] + '" /> ' + opt[1] + '</label></li>';
			}
			html += '</ul>';
			html += '</div></td>';
			/* Context section ends here */

			if (_type !== "field") {
				/* Pricing section starts here */
				html += '<td class="pricing">' + this.buildAmountWidget(_type) + '</td>';
				/* Pricing section ends here */

				/* Mode section starts here */
				html += '<td class="mode">' + this.buildCalculationModeWidget(_type) + '</td></tr></table>';
				/* Mode section ends here */
			} else {
				/* Field rule setter widget */
				html += '<td class="field">' + this.buildFieldsRuleSetter() + '</td></tr></table>';
			}

			html += '<a href="#" class="pricing-remove-rule wcff-button-remove"></a>';
			html += '</div>';
			return html;
		};

		this.buildPricingWidgetDatePicker = function (_type) {
			var html = '<div class="wcff-' + _type + '-row">';
			html += '<table class="wcff-' + _type + '-table data-picker-pricing-rule"><tr>';

			/* Context section starts here */
			html += '<td class="date-context">';
			html += '<div class="rule-section">';

			html += '<div class="wcff-factory-tab-container">';
			html += '<div class="wcff-factory-tab-left-panel">';
			html += '<ul class="wcff-' + _type + '-date-type-header">';
			html += '<li class="selected" data-dtype="days">Days</li>';
			html += '<li data-dtype="specific-dates">Specific Dates</li>';
			html += '<li data-dtype="weekends-weekdays">Weekends Or Weekdays</li>';
			html += '<li data-dtype="specific-dates-each-month">Specific Dates Each Months</li>';
			html += '</ul>';
			html += '</div>';
			html += '<div class="wcff-factory-tab-right-panel">';
			html += '<div class="wcff-factory-tab-content" style="display: block;">';
			html += '<div class="wcff-field-types-meta">';
			html += '<ul class="wcff-field-layout-horizontal">';
			html += '<li><label><input type="checkbox" name="wcff-field-type-meta-' + _type + '-disable_days[]" value="sunday"> Sunday</label></li>';
			html += '<li><label><input type="checkbox" name="wcff-field-type-meta-' + _type + '-disable_days[]" value="monday"> Monday</label></li>';
			html += '<li><label><input type="checkbox" name="wcff-field-type-meta-' + _type + '-disable_days[]" value="tuesday"> Tuesday</label></li>';
			html += '<li><label><input type="checkbox" name="wcff-field-type-meta-' + _type + '-disable_days[]" value="wednesday"> Wednesday</label></li>';
			html += '<li><label><input type="checkbox" name="wcff-field-type-meta-' + _type + '-disable_days[]" value="thursday"> Thursday</label></li>';
			html += '<li><label><input type="checkbox" name="wcff-field-type-meta-' + _type + '-disable_days[]" value="friday"> Friday</label></li>';
			html += '<li><label><input type="checkbox" name="wcff-field-type-meta-' + _type + '-disable_days[]" value="saturday"> Saturday</label></li>';
			html += '</ul>';
			html += '</div>';
			html += '</div>';
			html += '<div class="wcff-factory-tab-content" style="display: none;">';
			html += '<div class="wcff-field-types-meta">';
			html += '<textarea class="wcff-field-type-meta-specific_dates" placeholder="Format: MM-DD-YYYY Example: 1-22-2017,10-7-2017" rows="2"></textarea>';
			html += '</div>';
			html += '</div>';
			html += '<div class="wcff-factory-tab-content" style="display: none;">';
			html += '<div class="wcff-field-types-meta">';
			html += '<ul class="wcff-field-layout-horizontal">';
			html += '<li><label><input type="radio" name="wcff-field-type-meta-' + _type + '-weekend_weekdays" class="wcff-field-type-meta-weekend_weekdays" value="weekends"> Week Ends</label></li>';
			html += '<li><label><input type="radio" name="wcff-field-type-meta-' + _type + '-weekend_weekdays" class="wcff-field-type-meta-weekend_weekdays" value="weekdays"> Week Days</label></li>';
			html += '</ul>';
			html += '</div>';
			html += '<div class="wcff-field-types-meta" data-type="html"><a href="#" class="wcff-date-disable-radio-clear button">Clear</a></div>';
			html += '</div>';
			html += '<div class="wcff-factory-tab-content" style="display: none;">';
			html += '<div class="wcff-field-types-meta">';
			html += '<textarea class="wcff-field-type-meta-specific_date_each_months" placeholder="Example: 5,10,12" rows="2"></textarea>';
			html += '</div>';
			html += '</div>';
			html += '</div>';
			html += '</div>';

			html += '</div></td>';

			if (_type !== "field") {
				/* Pricing section starts here */
				html += '<td class="pricing">' + this.buildAmountWidget(_type) + '</td>';
				/* Pricing section ends here */

				/* Mode section starts here */
				html += '<td class="mode">' + this.buildCalculationModeWidget(_type) + '</td></tr></table>';
				/* Mode section ends here */
			} else {
				/* Field rule setter widget */
				html += '<td class="field">' + this.buildFieldsRuleSetter() + '</td></tr></table>';
			}

			html += '<a href="#" class="pricing-remove-rule wcff-button-remove"></a>';
			html += '</div>';
			return html;
		};

		this.buildAmountWidget = function (_type) {
			var html = '<div class="rule-section">';
			html += '<label>Title</label>';
			html += '<input type="text" class="wcff-' + _type + '-rules-title" value="">';
			html += '</div><div class="rule-section">';
			html += '<label>Amount</label>';
			html += '<input type="number" class="wcff-' + _type + '-rules-amount" value="" step="any">';
			html += '</div>';
			return html;
		};

		this.buildCalculationModeWidget = function (_type) {
			var html = '<div class="rule-section">';
			html += '<label>Amount Mode</label>';
			html += '<div class="wcff-rule-toggle amount-mode ' + _type + '-amount-mode">';
			html += '<a href="#" data-tprice="cost" title="Amount should be added or subtracted or replced with the Original Price" class="price-is-amount selected">Cost</a>';
			html += '<a href="#" data-tprice="percentage" title="Amount should act as a Percent - which will be added or subtracted with the Original Price" class="price-is-percentage">%</a>';
			html += '</div></div>';
			html += '<div class="rule-section">';
			html += '<label>Calculation Mode</label>';
			html += '<div class="wcff-rule-toggle calculation-mode">';
			if (_type === "pricing") {
				html += '<a href="#" data-ptype="add" title="Add this amount (or percent) with product original price" class="price-rule-add selected">Add</a>';
				html += '<a href="#" data-ptype="sub" title="Subtract this amount (or percent) with product original price" class="price-rule-add">Sub</a>';
				html += '<a href="#" data-ptype="change" title="Replace the original product price with this amount" class="price-rule-change">Replace</a>';
			} else {
				html += '<a href="#" data-is_tx="tax" title="Is taxable" class="fee-is-tax">Tax</a>';
				html += '<a href="#" data-is_tx="non_tax" title="Is non-taxable" class="fee-is-non_tax">Non Tax</a>';
			}
			html += '</div></div>';
			return html;
		};

		this.buildFieldsRuleSetter = function () {
			var i = 0,
				html = '',
				fieldKey = '',
				field_row = null,
				field_lists = $('#wcff-fields-set .wcff-meta-row:not(.active)');
			html += '<table class="wcff-fields-visibility-widget-table"><tbody>';
			for (i = 0; i < field_lists.length; i++) {
				field_row = $(field_lists[i]);
				fieldKey = field_row.attr("data-key");
				html += '<tr>';
				html += '<td class="toggle-field-label-col">';
				html += '<label>' + (field_row.find(".wcff-field-label").find("input").length != 0 ? field_row.find(".wcff-field-label").find("input").val() : field_row.find(".wcff-field-label").text()) + ' => <label>';
				html += '</td>';
				html += '<td class="toggle-widget-col">';
				html += '<div class="wcff-field-type-of-field-toggle fields-mode wcff-rule-toggle"><a href="#" data-field_label="' + fieldKey + '" data-vfield="show" title="Show Field" class="field-show">Show</a><a href="#" data-vfield="hide" data-field_label="' + fieldKey + '" title="Hide Field" class="field-hide">Hide</a><a href="#" data-vfield="Nill" data-field_label="' + fieldKey + '" title="No rule" class="field-nill-rule selected">Nill</a></div>';
				html += '</td>';
				html += '</tr>';
			}
			html += '</tbody></table>';
			return html;
		};

		this.addColorImageMapper = function (_type) {
			if (this.activeRow.find("[name=wcff-field-type-meta-palettes]").val() == "") {
				alert("Please add some colors to the palette option.!");
				//return;
			}
			var html = '<div class="wcff-' + _type + '-row">';
			html += '<table class="wcff-' + _type + '-table"><tr>';

			/* Context section starts here */
			html += '<td class="context">';
			html += '<div class="rule-section">';
			html += '<label>User\'s chosen color is</label>';
			html += '<select class="wcff-' + _type + '-input-condition-value">';
			html += '<option value="equal">is equal to</option>';
			html += '<option value="default">default image</option>';
			html += '</select></div>';
			html += '<div class="rule-section">';

			html += '<div class="wcff-color-image-select-container">';

			var p = 0,
				$hex = '',
				$split = '',
				colors = [],
				palette = this.activeRow.find("[name=wcff-field-type-meta-palettes]").val().trim().replace("\n", ",");

			colors = palette.split(",");
			for (p = 0; p < colors.length; p++) {
				$split = colors[p].trim();
				$hex = $split.length == 4 && $split.length >= 4 ? ('#' + $split[1] + $split[1] + $split[2] + $split[2] + $split[3] + $split[3]) : $split;
				html += '<label style="background-color: ' + $hex + '; "><input type="radio" value="' + $hex + '"></label>';
			}

			html += '</div>';
			html += '</div></td><td class="image">';
			html += '<div class="rule-section">';

			html += '<table class="img-mapper-upload-table"><tr>';
			html += '<td><label>Then replace the product image to =></label></td>';
			html += '<td><div class="rule-section">';
			html += '<div class="hide-if-no-js wcff-image-selector-container"><div class=""><img class="wcff-prev-image" src="' + wcff_var.plugin_dir + '/assets/img/placeholder-image.jpg" alt="" style="width:80px;"><input type="hidden" class="wcff-image-url-holder"></div><div class="">' +
				'<a class="wcff-upload-custom-img button"  href="#"> Add </a>' +
				'<a class="wcff-delete-custom-img hidden button" href="#"> Remove </a> </div></div><div class="wcff-url-selector-container" style="display:none;"><input type="text" class="wcff-product-color-url" placeholder="Paste another product url here"></div>';
			html += '</div></td>';
			html += '</tr></table>';

			html += '';
			//html += '<div class="wcff-color-image-toggle wcff-rule-toggle wcff-rule-placeholder-change"><a href="#" data-type="image" title="Select Image will change the product image" class="color-image-image selected">Image</a><a href="#" data-type="url" title="Put url it will goto that page" class="color-image-url">Url</a></div>';
			html += '</div>';
			html += '</td></tr></table></div>';
			return html;
		};

		this.dropNewField = function () {
			/* Prepare and append Field Entry Row */
			var field_type = this.draggedField.attr("value").trim(),
				fieldRow = '<div class="wcff-meta-row active wcff-field-config-drawer-opened" data-key="" data-type="' + field_type + '" data-unremovable="false" data-is_enable="true">';
			fieldRow += '<table class="wcff_table">';
			fieldRow += '<tbody>';
			fieldRow += '<tr>';
			fieldRow += '<td class="field-order wcff-sortable">';
			fieldRow += '<span class="wcff-field-order-number wcff-field-order">4</span>';
			fieldRow += '</td>';
			fieldRow += '<td class="field-label">';
			fieldRow += '<label class="wcff-field-label" data-key=""><input type="text" name="wcff-field-type-meta-label-temp" class="wcff-field-type-meta-label-temp" value="" autocomplete="off"></label></td>';
			fieldRow += '<td class="field-type">';
			fieldRow += '<label class="wcff-field-type"><span style="background: url(' + wcff_var.asset_url + '/img/' + field_type + '.png) no-repeat left;"></span>' + field_type + '</label>';
			fieldRow += '</td>';
			fieldRow += '<td class="field-actions">';
			fieldRow += '<div class="wcff-meta-option">';
			fieldRow += '<label class="wcff-switch" data-key=""> <input class="wcff-toggle-check" type="checkbox" checked=""> <span class="slider round"></span> </label>';
			fieldRow += '<a href="#" data-key="" class="wcff-field-delete button" style="display: none;">x</a>';
			fieldRow += '</div>';
			fieldRow += '</td>';
			fieldRow += '</tr>';
			fieldRow += '</tbody>';
			fieldRow += '</table>';
			fieldRow += '<input type="hidden" name="wcff-field-order-index" class="wcff-field-order-index" value="0">';
			fieldRow += '</div>';
			fieldRow = $(fieldRow);
			this.placeHolder.after(fieldRow);

			/* Update fields order property */
			var order = wcff_var.post_type == "wcccf" ? 1 : 0;
			$("div.wcff-meta-row").each(function () {
				if (!$(this).is("#wcff-add-field-placeholder")) {
					$(this).find("input.wcff-field-order-index").val(order);
					$(this).find("span.wcff-field-order-number").text((wcff_var.post_type == "wcccf" ? order : (order + 1)));
					order++;
				}
			});

			/* Well register this field on the server and get the configuration widget */
			this.prepareRequest("POST", "field", { type: field_type, order: fieldRow.find("input.wcff-field-order-index").val() }, fieldRow);
			this.mask.doMask(fieldRow);
			this.dock();
		};

		this.prepareConfigWidget = function (_is_new) {
			this.emptyNotice.hide();
			/* Product field related house keeping */
			if (wcff_var.post_type === "wccpf") {
				if (this.activeField["type"] === "file") {
					this.target.find("div[data-param=img_is_prev_width]").hide();
				}
				if (this.target.find(".wcff-factory-multilingual-label-btn").length > 0) {
					if (this.activeField["type"] === "hidden" || this.activeField["type"] === "label") {
						this.target.find(".wcff-factory-multilingual-label-btn").hide();
					} else {
						this.target.find(".wcff-factory-multilingual-label-btn").show();
					}
				}
			}
			/* Admin field related house keeping */
			if (wcff_var.post_type === "wccaf") {
				this.target.find("div.wcff-field-types-meta").each(function () {
					if ($(this).attr("data-param") === "visibility" ||						
						$(this).attr("data-param") === "login_user_field" ||
						$(this).attr("data-param") === "cart_editable" ||
						$(this).attr("data-param") === "cloneable" ||
						$(this).attr("data-param") === "show_as_read_only" ||
						$(this).attr("data-param") === "hide_when_no_value" ||
						$(this).attr("data-param") === "show_with_value" ||
						$(this).attr("data-param") === "showin_value") {
						$(this).closest("tr").hide();
					}

                    if (_is_new) {                        
                        $(this).find("input[name=options-order_meta][value='no']").prop("checked", true);
                    }
				});
				/* For url field we need to show the cloneable */
				if (this.activeField["type"] === "url") {
					this.target.find("div.wcff-field-types-meta").each(function () {
						if ($(this).attr("data-param") === "login_user_field" || $(this).attr("data-param") === "cloneable") {
							$(this).closest("tr").show();
						}
					});
				}

			}
		};

		/* Used for Layout designer */
		this.constructDropZone = function () {
			if ($("div.wcff-layout-form-row").length == 1) {
				/* Only one form row */
				if ($("div.wcff-layout-form-row > div").length > 0) {
					/* And it already has a field in it */
					/* Add one more column in it */
					$("div.wcff-layout-form-row").append($('<div class="dropzone"></div>'));		

					/* Also time to add a new row */
					$("#wcff-layout-designer-pad").append($('<div class="wcff-layout-form-row"><div class="dropzone"></div></div>'));					
				} else {
					/* Add one more column in it */
					$("div.wcff-layout-form-row").append($('<div class="dropzone"></div>'));
				}				
			} else {							
				$("div.wcff-layout-form-row").append($('<div class="dropzone"></div>'));
				$("#wcff-layout-designer-pad").append($('<div class="wcff-layout-form-row"><div class="dropzone"></div></div>'));				
			}

			/* Remove the width properties */
			$("div.wcff-layout-form-row > div.dropped").each(function() {
				$(this).attr("data-width", $(this).css("flex-basis"));
				$(this).css("flex-basis", "");
			});

			/* Update the layout meta object */
			this.layout.rows.push([]);
		};

		this.handleDropField = function (_payload) {
			me = this;
			/* Inject the field */
			this.dropZone.html(_payload);
			this.dropZone.attr("data-fkey", this.draggedField.attr("data-fkey"));
			/* Add remove button */
			this.dropZone.append($('<a href="#" class="delete-field" title="Remove">X</a>'));
			if (this.dropZone.parent().find("> div.dropped").length > 1) {
				/* Add resize handle */
				this.dropZone.before($('<div class="handlebar"></div>'));
			}
			/* Update layout meta */
			this.layout.rows[this.dropZone.parent().index()].push(this.draggedField.attr("data-fkey"));
			this.layout.columns[this.draggedField.attr("data-fkey")] = { width: 0 };
			/* Update the width of the field */
			var rowWidth = this.dropZone.parent().width();
			var hBarCount = this.dropZone.parent().find("> div.handlebar").length;
			var hWidth = (hBarCount * 6);


			this.dropZone.parent().find("> div.dropped").each(function () {
				var ratio = ((parseInt($(this).width()) + parseInt(hBarCount > 0 ? (hWidth / hBarCount) : 0)) / rowWidth);
				me.layout.columns[$(this).attr("data-fkey")].width = (ratio * 100);
			});

			/* Remove empty rows */
			for (var i = 0; i < this.layout.rows.length; i++) {
				if (this.layout.rows[i].length == 0) {
					this.layout.rows.splice(i, 1);
				}
			}
			
			/* Remove the dropped fields from fields list */
			this.draggedField.remove();
			/**/
			if ($("#wcff-layout-designer-field-list > a").length == 0) {
				$("#wcff-layout-designer-field-list").html('<h3>All fields are used.!</h3>');
			}
		};

		this.destructDropZone = function () {
			$("div.wcff-layout-form-row > div.dropzone").remove();

			if ($("div.wcff-layout-form-row").length > 1) {
				$("div.wcff-layout-form-row").each(function () {
					if ($(this).index() != 0 && $(this).find("> div").length == 0) {
						$(this).remove();
					}
				});
			}
		};

		this.renderSingleView = function () {
			var i = 0,
				j = 0,
				html = '',
				me = this,
				keyval = [],
				options = [],
				default_val = null,
				temp_holder = null,
				dcontainer = this.target.find(".wcff-default-option-holder");

			/* Scroll down to Field Factory Container */
			$('html,body').animate({ scrollTop: this.target.offset().top - 50 }, 'slow');

			/* Locales for Label */
			if (this.activeField["locale"]) {
				for (i = 0; i < wcff_var.locales.length; i++) {
					if (this.target.find("[name=wcff-field-type-meta-label-" + wcff_var.locales[i] + "]").length > 0) {
						if (this.activeField["locale"][wcff_var.locales[i]] && this.activeField["locale"][wcff_var.locales[i]]["label"]) {
							this.target.find("[name=wcff-field-type-meta-label-" + wcff_var.locales[i] + "]").val(this.activeField["locale"][wcff_var.locales[i]]["label"]);
						}
					}
				}
			}

			/* If it is Datepicker then reset the Disable Date widget */
			if (this.activeField["type"] === "datepicker") {
				$("div.wcff-factory-tab-right-panel").find("div.wcff-field-types-meta").each(function () {
					if ($(this).attr("data-param") !== "") {
						var param = $(this).attr("data-param");
						var type = $(this).attr("data-type");
						if (type === "checkbox" || type === "radio") {
							$(this).find("input[type=" + type + "]").prop('checked', false);
						} else {
							$(this).find(type).val("");
						}
					}
				});
			}

			/* Set the appropriate params with values */
			this.target.find(".wcff-field-types-meta-body div.wcff-field-types-meta").each(function () {
				if (me.activeField[$(this).attr("data-param")]) {
					if ($(this).attr("data-param") === "choices" || $(this).attr("data-param") === "palettes") {
						me.activeField[$(this).attr("data-param")] = me.activeField[$(this).attr("data-param")].replace(/;/g, "\n");
					}
					if ($(this).attr("data-type") === "checkbox") {
						var choices = me.activeField[$(this).attr("data-param")];
						if (choices) {
							for (i = 0; i < choices.length; i++) {
								$(this).find("input.wcff-field-type-meta-" + $(this).attr("data-param") + "[value='" + choices[i] + "']").prop('checked', true);
							}
						}
					} else if ($(this).attr("data-type") === "radio") {
						$(this).find(".wcff-field-type-meta-" + $(this).attr("data-param") + "[value='" + me.activeField[$(this).attr("data-param")] + "']").prop('checked', true);
						$(this).find(".wcff-field-type-meta-" + $(this).attr("data-param") + "[value='" + me.activeField[$(this).attr("data-param")] + "']").trigger("change");
					} else {
						if ($(this).attr("data-type") !== "html") {
							$(this).find(".wcff-field-type-meta-" + $(this).attr("data-param")).val(me.unEscapeQuote(me.activeField[$(this).attr("data-param")]));
						}
					}					
				}
			});

			/* Load locale related fields */
			if (me.activeField["locale"]) {
				for (i = 0; i < wcff_var.locales.length; i++) {				
					this.target.find("div.wcff-locale-block").each(function () {
						if ($(this).find("[name=wcff-field-type-meta-" + $(this).attr("data-param") + "-" + wcff_var.locales[i] + "]").length != 0) {
							if ($(this).attr("data-param") === "choices" && me.activeField["locale"][wcff_var.locales[i]] && me.activeField["locale"][wcff_var.locales[i]][$(this).attr("data-param")]) { console.log("Its a choice");
								me.activeField["locale"][wcff_var.locales[i]][$(this).attr("data-param")] = me.activeField["locale"][wcff_var.locales[i]][$(this).attr("data-param")].replace(/;/g, "\n");
							}
							if (me.activeField["locale"][wcff_var.locales[i]] && me.activeField["locale"][wcff_var.locales[i]][$(this).attr("data-param")]) {
								$(this).find("[name=wcff-field-type-meta-" + $(this).attr("data-param") + "-" + wcff_var.locales[i] + "]").val(me.activeField["locale"][wcff_var.locales[i]][$(this).attr("data-param")]);
							}
						}
					});			
				}
			}

			if (typeof this.activeField["login_user_field"] != "undefined" && this.activeField["login_user_field"] == "yes") {
				this.target.find("div.wcff-field-types-meta[data-param=show_for_roles]").closest("tr").show();
			}

			dcontainer.html("");
			/* Render default section */
			/* Default section handling for Check Box */
			if (this.activeField["type"] === "checkbox" && this.activeField["choices"]) {
				if (this.activeField["choices"] != "") {
					/* Prepare default value property */
					default_val = [];
					/* CHeck for this property, until V1.4.0 check box for Admin Fields doesn't has this property */
					if (this.activeField["default_value"]) {
						temp_holder = this.activeField["default_value"];
						/* This is for backward compatibility - <= V 1.4.0 */
						if (Object.prototype.toString.call(temp_holder) !== '[object Array]') {
							/* Since we haven't replaced the default value - as we used before */
							temp_holder = temp_holder.split(";");
							for (i = 0; i < temp_holder.length; i++) {
								keyval = temp_holder[i].trim().split("|");
								if (keyval.length === 2) {
									default_val.push(keyval[0].trim());
								}
							}
						} else {
							default_val = this.activeField["default_value"];
						}
					}
					options = this.activeField["choices"].split("\n");
					html = '<ul>';
					for (i = 0; i < options.length; i++) {
						keyval = options[i].split("|");
						if (keyval.length === 2) {
							if (default_val.indexOf(keyval[0]) > -1) {
								html += '<li><input type="checkbox" value="' + this.unEscapeQuote(keyval[0]) + '" checked /> ' + this.unEscapeQuote(keyval[1]) + '</li>';
							} else {
								html += '<li><input type="checkbox" value="' + this.unEscapeQuote(keyval[0]) + '" /> ' + this.unEscapeQuote(keyval[1]) + '</li>';
							}
						}
					}
					html += '</ul>';
					dcontainer.html(html);
					/* Now inflate the default value for locale */
					if (this.activeField["locale"]) {
						for (i = 0; i < wcff_var.locales.length; i++) {
							if (this.activeField["locale"][wcff_var.locales[i]] &&
								this.activeField["locale"][wcff_var.locales[i]]["choices"] &&
								this.activeField["locale"][wcff_var.locales[i]]["choices"] != "") {
								options = this.activeField["locale"][wcff_var.locales[i]]["choices"].split("\n");
								default_val = (this.activeField["locale"][wcff_var.locales[i]]["default_value"]) ? this.activeField["locale"][wcff_var.locales[i]]["default_value"] : "";

								html = '<ul>';
								for (j = 0; j < options.length; j++) {
									keyval = options[j].split("|");
									if (keyval.length === 2) {
										if (default_val.indexOf(keyval[0]) > -1) {
											html += '<li><input type="checkbox" value="' + this.unEscapeQuote(keyval[0]) + '" checked /> ' + this.unEscapeQuote(keyval[1]) + '</li>';
										} else {
											html += '<li><input type="checkbox" value="' + this.unEscapeQuote(keyval[0]) + '" /> ' + this.unEscapeQuote(keyval[1]) + '</li>';
										}
									}
								}
								html += '</ul>';
								this.target.find(".wcff-default-option-holder-" + wcff_var.locales[i]).html(html);
							}
						}
					}
				}
			}

			/* Default section handling for Radio Button */
			if (this.activeField["type"] === "radio" && this.activeField["choices"]) {
				if (this.activeField["choices"] != "") {
					/* Prepare default value property */
					default_val = "";
					if (this.activeField["default_value"]) {
						if (this.activeField["default_value"].indexOf("|") != -1) {
							/* This is for backward compatibility - <= V 1.4.0 */
							keyval = this.activeField["default_value"].trim().split("|");
							if (keyval.length === 2) {
								default_val = keyval[0];
							}
						} else {
							default_val = this.activeField["default_value"].trim();
						}
					}
					options = this.activeField["choices"].split("\n");
					html = '<ul>';
					for (i = 0; i < options.length; i++) {
						keyval = options[i].split("|");
						if (keyval.length === 2) {
							if (default_val === keyval[0]) {
								html += '<li><input name="wcff-default-choice" type="radio" value="' + this.unEscapeQuote(keyval[0]) + '" checked /> ' + this.unEscapeQuote(keyval[1]) + '</li>';
							} else {
								html += '<li><input name="wcff-default-choice" type="radio" value="' + this.unEscapeQuote(keyval[0]) + '" /> ' + this.unEscapeQuote(keyval[1]) + '</li>';
							}
						}
					}
					html += '</ul>';
					dcontainer.html(html);
					/* Now inflate the default value for locale */
					if (this.activeField["locale"]) {
						for (i = 0; i < wcff_var.locales.length; i++) {
							if (this.activeField["locale"][wcff_var.locales[i]] &&
								this.activeField["locale"][wcff_var.locales[i]]["choices"] &&
								this.activeField["locale"][wcff_var.locales[i]]["choices"] != "") {

								options = this.activeField["locale"][wcff_var.locales[i]]["choices"].split("\n");
								default_val = (this.activeField["locale"][wcff_var.locales[i]]["default_value"]) ? this.activeField["locale"][wcff_var.locales[i]]["default_value"] : "";

								html = '<ul>';
								for (j = 0; j < options.length; j++) {
									keyval = options[j].split("|");
									if (keyval.length === 2) {
										if (default_val === keyval[0]) {
											html += '<li><input name="wcff-default-choice-' + wcff_var.locales[i] + '" type="radio" value="' + this.unEscapeQuote(keyval[0]) + '" checked /> ' + this.unEscapeQuote(keyval[1]) + '</li>';
										} else {
											html += '<li><input name="wcff-default-choice-' + wcff_var.locales[i] + '" type="radio" value="' + this.unEscapeQuote(keyval[0]) + '" /> ' + this.unEscapeQuote(keyval[1]) + '</li>';
										}
									}
								}
								html += '</ul>';
								this.target.find(".wcff-default-option-holder-" + wcff_var.locales[i]).html(html);
							}
						}
					}

					/* */
					if (this.activeField["render_method"] && this.activeField["render_method"] != "none") {
						$("#wcff-render-option-label-position").val(this.activeField["preview_label_pos"]);
						if (this.activeField["show_preview_label"] == "yes") {
							$("#wcff-option-render-label").prop("checked", true);
						} else {
							$("#wcff-option-render-label").prop("checked", false);
						}
						$("#wcff-option-render-label").trigger("change");
					}
				}
			}

			/* Default section handling for Select */
			if (this.activeField["type"] === "select" && this.activeField["choices"]) {
				/* Prepare default value property */
				default_val = "";
				if (this.activeField["default_value"]) {
					if (this.activeField["default_value"].indexOf("|") != -1) {
						/* This is for backward compatibility - <= V 1.4.0 */
						keyval = this.activeField["default_value"].trim().split("|");
						if (keyval.length === 2) {
							default_val = keyval[0];
						}
					} else {
						default_val = this.activeField["default_value"].trim();
					}
				}
				options = this.activeField["choices"].split("\n");
				html = '<select>';
				html += '<option value="">-- Choose the default Option --</option>';
				for (i = 0; i < options.length; i++) {
					keyval = options[i].split("|");
					if (keyval.length === 2) {
						if (default_val === keyval[0]) {
							html += '<option value="' + this.unEscapeQuote(keyval[0]) + '" selected>' + this.unEscapeQuote(keyval[1]) + '</option>';
						} else {
							html += '<option value="' + this.unEscapeQuote(keyval[0]) + '">' + this.unEscapeQuote(keyval[1]) + '</option>';
						}
					}
				}
				html += '</select>';
				dcontainer.html(html);
				/* Now inflate the default value for locale */
				if (this.activeField["locale"]) {
					for (i = 0; i < wcff_var.locales.length; i++) {
						if (this.activeField["locale"][wcff_var.locales[i]] &&
							this.activeField["locale"][wcff_var.locales[i]]["choices"] &&
							this.activeField["locale"][wcff_var.locales[i]]["choices"] != "") {

							options = this.activeField["locale"][wcff_var.locales[i]]["choices"].split("\n");
							default_val = (this.activeField["locale"][wcff_var.locales[i]]["default_value"]) ? this.activeField["locale"][wcff_var.locales[i]]["default_value"] : "";

							html = '<select>';
							html += '<option value="">-- Choose the default Option --</option>';
							for (j = 0; j < options.length; j++) {
								keyval = options[j].split("|");
								if (keyval.length === 2) {
									if (default_val === keyval[0]) {
										html += '<option value="' + this.unEscapeQuote(keyval[0]) + '" selected>' + this.unEscapeQuote(keyval[1]) + '</option>';
									} else {
										html += '<option value="' + this.unEscapeQuote(keyval[0]) + '">' + this.unEscapeQuote(keyval[1]) + '</option>';
									}
								}
							}
							html += '</select>';
							this.target.find(".wcff-default-option-holder-" + wcff_var.locales[i]).html(html);
						}
					}
				}
			}

			/* Show or hide Img width config row - for file field */
			if (this.activeField["type"] === "file") {
				var isPrev = $("input[name=wcff-field-type-meta-img_is_prev]:checked").val();
				if (isPrev && isPrev === "yes") {
					$("div[data-param=img_is_prev_width]").show();
				} else {
					$("div[data-param=img_is_prev_width]").hide();
				}
			}

			if (this.activeField["type"] === "datepicker") {
				var isTimePicker = $("input[name=wcff-field-type-meta-timepicker]:checked").val();
				if (isTimePicker && isTimePicker === "yes") {
					$("div[data-param=min_max_hours_minutes]").closest("tr").css("display", "table-row");
				} else {
					$("div[data-param=min_max_hours_minutes]").closest("tr").css("display", "none");
				}
				/* Set the min max hours & minutes */
				if (this.activeField["min_max_hours_minutes"] && this.activeField["min_max_hours_minutes"] !== "") {
					var min_max = this.activeField["min_max_hours_minutes"].split("|");
					if (min_max instanceof Array) {
						if (min_max.length >= 1) {
							$("#wccpf-datepicker-min-max-hours").val(min_max[0])
						}
						if (min_max.length >= 2) {
							$("#wccpf-datepicker-min-max-minutes").val(min_max[1])
						}
					}
				}

				$('[data-box=#wcff-date-field-disable-past-future-dates]').trigger("click");
			}

			/* Show the roles selector config, if the field is private */
			var isPrivate = this.target.find("input[name=wcff-field-type-meta-login_user_field]:checked").val();
			if (isPrivate === "yes") {
				this.target.find(".div[data-param=show_for_roles]").closest("tr").css("display", "table-row");
			} else {
				this.target.find(".div[data-param=show_for_roles]").closest("tr").css("display", "none");
			}

			/* Render Pricing, Fee and Field rules */
			if (wcff_var.post_type === "wccpf" || wcff_var.post_type === "wccvf") {
				var pricing_rules = this.activeField["pricing_rules"];
				if (Object.prototype.toString.call(pricing_rules) === '[object Array]') {
					for (i = 0; i < pricing_rules.length; i++) {
						this.renderFieldLevelRules("pricing", pricing_rules[i], this.target.find(".wcff-add-price-rule-btn").parent().find(".wcff-rule-container"));
					}
					if (pricing_rules.length != 0) {
						this.target.find(".wcff-add-price-rule-btn").parent().find(".wcff-rule-container-is-empty").hide();
					}
				}
				var fee_rules = this.activeField["fee_rules"];
				if (Object.prototype.toString.call(fee_rules) === '[object Array]') {
					for (i = 0; i < fee_rules.length; i++) {
						this.renderFieldLevelRules("fee", fee_rules[i], this.target.find(".wcff-add-fee-rule-btn").parent().find(".wcff-rule-container"));
					}
					if (fee_rules.length != 0) {
						this.target.find(".wcff-add-fee-rule-btn").parent().find(".wcff-rule-container-is-empty").hide();
					}
				}
				var field_rules = this.activeField["field_rules"];
				if (Object.prototype.toString.call(field_rules) === '[object Array]') {
					for (i = 0; i < field_rules.length; i++) {
						this.renderFieldLevelRules("field", field_rules[i], this.target.find(".wcff-add-field-rule-btn").parent().find(".wcff-rule-container"));
					}
					var field_rules_count = this.target.find(".wcff-tab-rules-wrapper.field .wcff-field-row");
					for (var i = 0; i < field_rules_count.length; i++) {
						for (var j in this.activeField["field_rules"][i]["field_rules"]) {
							$(field_rules_count[i]).find("a[data-field_label='" + j + "']").siblings().removeClass("selected");
							$(field_rules_count[i]).find("a[data-field_label='" + j + "'][data-vfield=" + this.activeField["field_rules"][i]["field_rules"][j] + "]").addClass("selected");
						}
					}
					if (field_rules.length != 0) {
						this.target.find(".wcff-add-field-rule-btn").parent().find(".wcff-rule-container-is-empty").hide();
					}
				}
				if (this.activeField["type"] == "colorpicker" && this.activeField["palettes"]) {
					this.activeField["choices"] = this.activeField["palettes"].replace(/\n/g, ",");
				}
				var colorImage = this.activeField["color_image"];
				if (Object.prototype.toString.call(colorImage) === '[object Array]') {
					for (i = 0; i < colorImage.length; i++) {
						this.renderFieldLevelRules("color-image", colorImage[i], this.target.find(".wcff-add-color-image-rule-btn").parent().find(".wcff-rule-container"));
					}
					if (colorImage.length != 0) {
						this.target.find(".wcff-add-color-image-rule-btn").parent().find(".wcff-rule-container-is-empty").hide();
					}
				}
			}

			/* Hides the unnecessory config rows - ( only for Admin Fields ) */
			if (wcff_var.post_type === "wccaf") {
				if (this.activeField["show_on_product_page"]) {
					var display = "table-row";
					if (this.activeField["show_on_product_page"] === "no") {
						display = "none";
					}
					this.target.find("div.wcff-field-types-meta").each(function () {
						var flaq = false;
						if ($(this).attr("data-param") === "visibility" ||
							
							$(this).attr("data-param") === "login_user_field" ||
							$(this).attr("data-param") === "cart_editable" ||
							$(this).attr("data-param") === "cloneable" ||
							$(this).attr("data-param") === "show_as_read_only" ||
							$(this).attr("data-param") === "hide_when_no_value" ||
							$(this).attr("data-param") === "show_with_value" ||
							$(this).attr("data-param") === "showin_value") {
							flaq = true;
						}
						if (flaq) {
							$(this).closest("tr").css("display", display);
						}
					});
				}
			}

			/* Show pricing tab */
			if (this.activeField["type"] !== "email" && this.activeField["type"] !== "label" && this.activeField["type"] !== "hidden") {
				this.target.find(".wcff-factory-tab-header a[href='.wcff-factory-tab-pricing-rules'], .wcff-factory-tab-header a[href='.wcff-factory-tab-fields-rules']").show();
			} else {
				/* Pricing rules not applicable for the following field type 
				 * 1. File
				 * 2. Email
				 * 3. Hidden
				 * 4. Label */
				this.target.find(".wcff-factory-tab-header a[href='.wcff-factory-tab-pricing-rules'], .wcff-factory-tab-header a[href='.wcff-factory-tab-fields-rules']").hide();
			}

			if (this.activeField["type"] == "colorpicker" && this.activeField["show_palette_only"] == "yes") {
				this.target.find(".wcff-factory-tab-header").find("a[href='.wcff-factory-tab-color-image']").show();
			}
			
		};

		/* Calkled from config fields blur event, for real time updates */
		this.updateField = function () {
			this.activeField = this.fetchFieldConfig();
			this.dirtyFields[this.activeField["key"]] = this.activeField;
			/* Well register this field on the server and get the configuration widget */
			//this.prepareRequest("PUT", "field", this.activeField, this.activeRow);
			//this.mask.doMask(this.activeRow);
			//this.dock();
		};

		this.fetchFieldConfig = function () {

			var i = 0,
				me = this,
				fname = "",
				flabel = "",
				payload = {},
				resources = {},
				properties = {},
				dContainer = null,
				isTimePicker = null,
				min_max_hours = "0:23",
				min_max_minutes = "0:59";

			if (this.activeRow) {
				payload["key"] = this.activeRow.attr("data-key");
				payload["type"] = this.activeRow.attr("data-type");

				if (this.activeRow.find(".field-label .wcff-field-label input").length == 0) {
					flabel = this.activeRow.find(".field-label .wcff-field-label").text()
				} else {
					flabel = this.activeRow.find(".field-label .wcff-field-label input").val();
				}
				payload["label"] = this.escapeQuote(flabel);
				payload["order"] = this.activeRow.find("input.wcff-field-order-index").val();
				payload["is_enable"] = this.activeRow.attr("data-is_enable") == "true" ? true : false;
				/* Specific to Checkout fields - but its there for all types */
				payload["is_unremovable"] = this.activeRow.attr("data-unremovable") == "true" ? true : false;

                if (this.activeRow.find(".wcff-field-types-meta-body").length == 0) {                     
                    return false;
                }

				/* Fetching regular config meta starts here */
				this.activeRow.find(".wcff-field-types-meta-body div.wcff-field-types-meta").each(function () {
					if ($(this).attr("data-type") === "checkbox") {
						payload[$(this).attr("data-param")] = $(this).find("input.wcff-field-type-meta-" + $(this).attr("data-param") + ":checked").map(function () {
							return me.escapeQuote(this.value);
						}).get();
					} else if ($(this).attr("data-type") === "radio") {
						payload[$(this).attr("data-param")] = me.escapeQuote(me.activeRow.find("input[type=radio].wcff-field-type-meta-" + $(this).attr("data-param") + ":checked").val());
					} else {
						if ($(this).attr("data-type") !== "html") {
							payload[$(this).attr("data-param")] = me.escapeQuote(me.activeRow.find("[name=wcff-field-type-meta-" + $(this).attr("data-param") + "]").val());
							if ($(this).attr("data-param") === "choices" || $(this).attr("data-param") === "palettes") {
								payload[$(this).attr("data-param")] = payload[$(this).attr("data-param")].replace(/\n/g, ";");
							}
						}
					}
				});
				/* Fetching regular config meta ends here */

				/* Fetching date picker specific meta starts here */
				if (payload.type === "datepicker") {
					min_max_hours = "0:23";
					min_max_minutes = "0:59";
					isTimePicker = this.activeRow.find("input[name=options-timepicker]:checked").val();

					if (isTimePicker == "yes") {
						if (this.activeRow.find(".wccpf-datepicker-min-max-hours").val() != "") {
							min_max_hours = this.activeRow.find(".wccpf-datepicker-min-max-hours").val();
						}
						if (this.activeRow.find(".wccpf-datepicker-min-max-minutes").val() != "") {
							min_max_minutes = this.activeRow.find(".wccpf-datepicker-min-max-minutes").val();
						}
					}
					payload["timepicker"] = isTimePicker;
					payload["min_max_hours_minutes"] = min_max_hours + "|" + min_max_minutes;
				}
				/* Fetching date picker specific meta ends here */

				/* Fetching locale related config meta starts here */
				for (i = 0; i < wcff_var.locales.length; i++) {
					properties = {};
					this.activeRow.find("div.wcff-locale-block").each(function () {
						if ($(this).find("[name=wcff-field-type-meta-" + $(this).attr("data-param") + "-" + wcff_var.locales[i] + "]").length != 0) {
							properties[$(this).attr("data-param")] = $(this).find("[name=wcff-field-type-meta-" + $(this).attr("data-param") + "-" + wcff_var.locales[i] + "]").val();
							if ($(this).attr("data-param") === "choices") {
								properties[$(this).attr("data-param")] = properties[$(this).attr("data-param")].replace(/\n/g, ";");
							}
						}
					});
					resources[wcff_var.locales[i]] = properties;
				}
				/* Fetching locale related config meta ends here */

				/* Fetching default values related config meta starts here */
				dContainer = this.activeRow.find(".wcff-default-option-holder");
				if (payload.type === "checkbox") {
					payload["default_value"] = dContainer.find("input[type=checkbox]:checked").map(function () {
						return me.escapeQuote(this.value);
					}).get();
					/* Fetch default value for locale */
					for (i = 0; i < wcff_var.locales.length; i++) {
						resources[wcff_var.locales[i]]["default_value"] = this.activeRow.find(".wcff-default-option-holder-" + wcff_var.locales[i]).find("input[type=checkbox]:checked").map(function () {
							return me.escapeQuote(this.value);
						}).get();
					}
				}
				if (payload.type === "radio") {
					payload["default_value"] = this.escapeQuote(dContainer.find("input[type=radio]:checked").val());
					/* Fetch default value for locale */
					for (i = 0; i < wcff_var.locales.length; i++) {
						resources[wcff_var.locales[i]]["default_value"] = this.escapeQuote(this.activeRow.find(".wcff-default-option-holder-" + wcff_var.locales[i]).find("input[type=radio]:checked").val());
					}

					/* Fetch the render options */
					payload["show_preview_label"] = $("#wcff-option-render-label").is(":checked") ? "yes" : "no";
					if (payload["show_preview_label"] == "yes") {
						payload["preview_label_pos"] = $("#wcff-render-option-label-position").val();
					}

					if (!payload["images"]) {
						payload["images"] = {};
					}

					if (this.activeField["images"]) {
						payload["images"] = this.activeField["images"];
					}
				}
				if (payload.type === "select") {
					payload["default_value"] = this.escapeQuote(dContainer.find("select").val());
					/* Fetch default value for locale */
					for (i = 0; i < wcff_var.locales.length; i++) {
						resources[wcff_var.locales[i]]["default_value"] = this.escapeQuote(this.activeRow.find(".wcff-default-option-holder-" + wcff_var.locales[i]).find("select").val());
					}
				}
				/* Fetching default values related config meta ends here */

				/* Put the locale resource on payload object */
				payload["locale"] = resources;

				/* Fetch the Rules (Pricing, Fee, Fields and Color - Image Mapping) */
				this.activeRow.find("div.wcff-pricing-row").each(function () {
					me.fetchRules($(this), "pricing");
				});
				this.activeRow.find("div.wcff-fee-row").each(function () {
					me.fetchRules($(this), "fee");
				});
				this.activeRow.find("div.wcff-field-row").each(function () {
					me.fetchRules($(this), "field");
				});
				this.activeRow.find("div.wcff-color-image-row").each(function () {
					me.fetchRules($(this), "color-image");
				});

				if (this.pricingRules.length > 0) {
					payload["pricing_rules"] = JSON.parse(JSON.stringify(this.pricingRules));
					this.pricingRules = [];
				}
				if (this.feeRules.length > 0) {
					payload["fee_rules"] = JSON.parse(JSON.stringify(this.feeRules));					
					this.feeRules = [];
				}
				if (this.fieldRules.length > 0) {
					payload["field_rules"] = JSON.parse(JSON.stringify(this.fieldRules));
					this.fieldRules = [];
				}
				if (this.colorImage.length > 0) {
					payload["color_image"] = JSON.parse(JSON.stringify(this.colorImage));
					this.colorImage = [];
				}
			}

			return payload;
		};

		this.fetchRules = function (_current, _type) {
			var rule = {},
				me = this,
				dtype = "",
				pvalue = "",
				logic = "",
				amount = 0,
				ftype = _current.closest(".wcff-meta-row").attr("data-type"),
				ctype = _type == "pricing" ? "price" : _type;

			rule["expected_value"] = {};
			rule["amount"] = _current.find("input.wcff-" + _type + "-rules-amount").val();

			if (_type == "pricing") {
				rule["ptype"] = _current.find("div.calculation-mode > a.selected").data("ptype");
				rule["tprice"] = _current.find("div.amount-mode > a.selected").data("tprice");
			}

			if (_type == "fee") {
				rule["tprice"] = _current.find("div.amount-mode > a.selected").data("tprice");
				rule["is_tx"] = _current.find("div.calculation-mode > a.selected").data("is_tx");
			}

			if (_type === "fee") {
				rule["title"] = this.escapeQuote(_current.find("input.wcff-fee-rules-title").val());
				if (rule["title"] === "" || !rule["title"]) {
					return;
				}

			} else if (_type === "pricing") {
				rule["title"] = this.escapeQuote(_current.find("input.wcff-pricing-rules-title").val());
				if (rule["title"] === "" || !rule["title"]) {
					return;
				}
			} else if (_type === "color-image") {
				rule["prev_image_url"] = _current.find(".wcff-prev-image").attr("src");
				rule["image_or_url"] = _current.find(".wcff-color-image-toggle .selected").data("type");
				rule["url"] = (rule["image_or_url"] == "image" ? _current.find(".wcff-image-url-holder").val() : _current.find(".wcff-product-color-url").val());
				if (rule["url"].trim() == "" || rule["color"] == "") {
					this.val_error = { flg: true, message: "Please insert image or url in color image.", elem: _current.find(".wcff-color-image-toggle .selected") };
				}
			} else {
				var rules_for_field = _current.find("div.wcff-" + _type + "-type-of-" + ctype + "-toggle > a.selected");
				rule["field_rules"] = {};
				for (var i = 0; i < rules_for_field.length; i++) {
					rule["field_rules"][$(rules_for_field[i]).data("field_label")] = $(rules_for_field[i]).data("vfield");
				}
			}

			if (ftype === "datepicker") {
				dtype = _current.find("ul.wcff-" + _type + "-date-type-header > li.selected").attr("data-dtype");
				rule["expected_value"]["dtype"] = dtype;
				rule["expected_value"]["value"] = null;
				if (dtype === "days") {
					rule["expected_value"]["value"] = _current.find("input[type=checkbox]:checked").map(function () {
						return this.value;
					}).get();
				} else if (dtype === "specific-dates") {
					rule["expected_value"]["value"] = _current.find("textarea.wcff-field-type-meta-specific_dates").val();
				} else if (dtype === "weekends-weekdays") {
					rule["expected_value"]["value"] = _current.find(".wcff-field-type-meta-weekend_weekdays:checked").val();
				} else {
					rule["expected_value"]["value"] = _current.find("textarea.wcff-field-type-meta-specific_date_each_months").val();
				}

				if (rule["expected_value"]["value"] !== null && rule["amount"] !== "") {
					if (_type === "pricing") {
						this.pricingRules.push(rule);
					} else if (_type === "fee") {
						this.feeRules.push(rule);
					} else {
						this.fieldRules.push(rule);
					}
				}
			} else if (ftype === "select" || ftype === "radio") {
				pvalue = _current.find("select.wcff-" + _type + "-choice-expected-value").val();
				logic = _current.find("select.wcff-" + _type + "-choice-condition-value").val();

				if (pvalue !== "" && logic !== "" && rule["amount"] !== "") {
					rule["expected_value"] = pvalue;
					rule["logic"] = logic;
					if (_type === "pricing") {
						this.pricingRules.push(rule);
					} else if (_type === "fee") {
						this.feeRules.push(rule);
					} else {
						this.fieldRules.push(rule);
					}
				}
			} else if (ftype === "checkbox") {
				pvalue = [];
				pvalue = _current.find("input[type=checkbox]:checked").map(function () {
					return this.value;
				}).get();
				logic = _current.find("select.wcff-" + _type + "-multi-choice-condition-value").val();

				if (pvalue.length > 0 && logic !== "" && rule["amount"] !== "") {
					rule["expected_value"] = pvalue;
					rule["logic"] = logic;
					if (_type === "pricing") {
						this.pricingRules.push(rule);
					} else if (_type === "fee") {
						this.feeRules.push(rule);
					} else {
						this.fieldRules.push(rule);
					}
				}
			} else {
				pvalue = _current.find("input.wcff-" + _type + "-input-expected-value").val();
				logic = _current.find("select.wcff-" + _type + "-input-condition-value").val();
				if (_type === "color-image") {
					pvalue = _current.find(".wcff-color-image-select-container input:checked").val();
				}
				if (rule["amount"] !== "" || logic == "null") {
					rule["expected_value"] = pvalue;
					rule["logic"] = logic;
					if (_type === "pricing") {
						this.pricingRules.push(rule);
					} else if (_type === "fee") {
						this.feeRules.push(rule);
					} else if (_type === "color-image") {
						this.colorImage.push(rule);
					} else {
						this.fieldRules.push(rule);
					}
				}
			}
		};

		this.loadFieldList = function (_payload) {
			/* layout toggle switch */
			if (_payload.use_custom_layout == "yes") {
				$("input[name=wcff_use_custom_layout]").prop("checked", true);
				$("#wcff-layout-designer-pad").css("opacity", "1").css("pointer-events", "auto");
				$("#wcff-layout-designer-field-list").css("opacity", "1").css("pointer-events", "auto");
			} else {
				$("input[name=wcff_use_custom_layout]").prop("checked", false);
				$("#wcff-layout-designer-pad").css("opacity", ".5").css("pointer-events", "none");
				$("#wcff-layout-designer-field-list").css("opacity", ".5").css("pointer-events", "none");
			}

			if (_payload.fields) {
				var i = 0,
					isEmpty = true,
					keys = Object.keys(_payload.fields),
					container = $("#wcff-layout-designer-field-list");
				container.html("");

				this.fields = _payload.fields;
				this.layout = _payload.layout;

				if ($.isEmptyObject(this.layout) || (Array.isArray(this.layout) && this.layout.length == 0) || this.layout == "") {
					this.layout = {};
					this.layout["rows"] = [[]];
					this.layout["columns"] = {};
				} else {
					/* Sanity Check - Dirty code, needs to be updated later */
					if (this.layout["columns"]) {
						if (Array.isArray(this.layout["columns"])) {
							this.layout["columns"] = {};						
						}
					} else {					
						this.layout["columns"] = {};
						this.layout["rows"] = [];
					}
				}				

				/* Render Fields List */
				for (i = 0; i < keys.length; i++) {
					if (!this.layout.columns[keys[i]]) {
						isEmpty = false,
							container.append($('<a href="#" draggable="true" data-fkey="' + keys[i] + '" data-type="' + this.fields[keys[i]]["type"] + '" title="' + this.fields[keys[i]]["label"] + '">' + this.fields[keys[i]]["label"] + '</a>'));
					}
				}
				if (isEmpty) {
					if ($.isEmptyObject(this.fields)) {
						/* This means no fields created yet */
						container.html('<h3>Field List is Empty<br/>Please add some fields.!</h3>');
					} else {
						/* This means the field list is empty */
						container.html('<h3>All fields are used.!</h3>');
					}
				}
				
				this.renderLayoutDesigner();
			}
		};

		this.renderLayoutDesigner = function () {
			var i = 0,
				j = 0,
				html = '',
				lDpad = $("#wcff-layout-designer-pad");
			/* Clear the designer pad */
			lDpad.html("");
			if (!$.isEmptyObject(this.layout["columns"])) {
				/* Render the layout skeletton */
				for (i = 0; i < this.layout.rows.length; i++) {
					html = '<div class="wcff-layout-form-row">';
					for (j = 0; j < this.layout.rows[i].length; j++) {
						if (j != 0) {
							html += '<div class="handlebar"></div>';
						}
						html += '<div class="dropped" data-fkey="' + this.layout.rows[i][j] + '" style="flex-basis: ' + this.layout.columns[this.layout.rows[i][j]].width + '%;"></div>';
					}
					html += '</div>';
					lDpad.append($(html));
				}
				
				/* Prepare the fields key list */
				this.layoutFieldsKeys = Object.keys(this.layout.columns);

				/* Now start to render the fields */				
				this.prepareRequest("GET", "render_fields_for_designer", {
					"keys":this.layoutFieldsKeys, 
					"alignment": $("input[name=wcff_label_alignment_radio]:checked").val()
				}, null);
				/* Needs to clear the Ajax Flaq */
				this.ajaxFlaQ = true;
				this.dock();
			} else {
				lDpad.html('<div class="wcff-layout-form-row"></div>');
			}
		};

		this.renderLayoutField = function (_payload) {
			for (let i = 0; i < this.layoutFieldsKeys.length; i++) {
				var container = $('div.dropped[data-fkey=' + this.layoutFieldsKeys[i] + ']');
				if (container.length > 0 && _payload[this.layoutFieldsKeys[i]]) {
					container.html(_payload[this.layoutFieldsKeys[i]]);
					container.append($('<a href="#" class="delete-field" title="Remove">X</a>'));
				}
			}
		};

		this.onPostSubmit = function (_target) {

			var me = this,
                meta = {},
				rule = {},
				rules = [],                
				condition_rules_group = [];

			/* Collect condition rules */
			$(".wcff_logic_group").each(function () {
				rules = [];
				$(this).find("table.wcff_rules_table tr").each(function () {
					rule = {};
					rule["context"] = $(this).find("select.wcff_condition_param").val();
					rule["logic"] = $(this).find("select.wcff_condition_operator").val();
					rule["endpoint"] = $(this).find("select.wcff_condition_value").val();
					rules.push(rule);
				});
				condition_rules_group.push(rules);
			});

			/* Collect location rules */
			rule = {};
			rule["context"] = $("select.wcff_location_param").val();

			if (rule["context"] !== "location_product_data") {
				rule["endpoint"] = {
					"context": $(".wcff_location_metabox_context_value").val(),
					"priority": $(".wcff_location_metabox_priorities_value").val()
				}
			} else {
				rule["endpoint"] = $("select.wcff_location_product_data_value").val();
			}

			$("#wcff_condition_rules").val(JSON.stringify(condition_rules_group));
			if (!$.isEmptyObject(rule)) {
				$("#wcff_location_rules").val(JSON.stringify(rule));
			}

			/* Collect dirty fields config */
			$("div.wcff-meta-row.opened").each(function () {
				me.activeRow = $(this);
                meta = me.fetchFieldConfig();
                if (meta) {
                    me.dirtyFields[me.activeRow.attr("data-key")] = meta;
                }				
			});

			if (!$.isEmptyObject(this.dirtyFields)) {
				$("#wcff_dirty_fields_configuration").val(JSON.stringify(this.dirtyFields));
			} else {
				$("#wcff_dirty_fields_configuration").remove();
			}
			
			$("#wcff_layout_meta").val(JSON.stringify(this.layout));

			return true;
		};

		this.reloadHtml = function (_where) {
			_where.html(this.response.payload);
		};

		/* convert string to url slug */
		this.sanitizeStr = function (_str) {
			if (_str) {
				return _str.toLowerCase().replace(/[^\w ]+/g, '').replace(/ +/g, '_');
			}
			return _str;
		};

		this.escapeQuote = function (_str) {
			if (_str) {
				_str = _str.replace(/'/g, '&#39;');
				_str = _str.replace(/"/g, '&#34;');
			}
			return _str;
		};

		this.unEscapeQuote = function (_str) {
			if (_str) {
				_str = _str.replace(/&#39;/g, "'");
				_str = _str.replace(/&#34;/g, '"');
			}
			return _str;
		};

		/**
		 * Converts a string to its html characters completely.
		 *
		 * @param {String} _str String with unescaped HTML characters
		 **/
		this.encode = function (_str) {
			var buf = [];
			for (var i = _str.length - 1; i >= 0; i--) {
				buf.unshift(['&#', _str[i].charCodeAt(), ';'].join(''));
			}
			return buf.join('');
		};
		/**
		 * Converts an html characterSet into its original character.
		 *
		 * @param {String} _str htmlSet entities
		 **/
		this.decode = function (_str) {
			return _str.replace(/&#(\d+);/g, function (match, dec) {
				return String.fromCharCode(dec);
			});
		};

		this.isNumberChoices = function (_options) {
			var opt = [];
			var flaq = false;
			var choices = _options.split("\n");
			if (choices) {
				flaq = true;
				for (var i = 0; i < choices.length; i++) {
					if (isNaN(choices[i].split("|")[0])) {
						flaq = false;
						break;
					}
				}
			}
			return flaq;
		};

		this.getQueryParameter = function (_key) {
			var i,
				sParameterName,
				sPageURL = window.location.search.substring(1),
				sURLVariables = sPageURL.split('&');
			for (i = 0; i < sURLVariables.length; i++) {
				sParameterName = sURLVariables[i].split('=');
				if (sParameterName[0] === _key) {
					return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
				}
			}
			return null;
		};

		this.reloadMapping = function() {
			/* disable the ajax lock */
			this.ajaxFlaQ = true;
			this.mask.doUnMask(this.mapping_grid.gridTable);
			this.mapping_grid.bucket = this.response.payload;
			this.mapping_grid.prepareRecords(this.mapping_grid.bucket);
			/* Since the local records were out of date, needs to reload */
			this.mapping_grid.loadRecords();
		};

		/**
		 * 
		 * Prepare the Ajax Request Object
		 * 
		 */
		this.prepareRequest = function (_method, _context, _payload, _target) {
			var _post = 0,
				_post_type = "";
			if (typeof wcff_var !== 'undefined') {
				_post = wcff_var.post_id,
					_post_type = wcff_var.post_type;
			}

			/* Request Object */
			this.request = {
				method: _method,
				context: _context,
				post: _post,
				post_type: _post_type,
				payload: _payload
			};
			/* Update the ajax target reference */
			this.target = _target;
		};

		/**
		 * 
		 * Prepare the Ajax Response Object
		 * 
		 */
		this.prepareResponse = function (_status, _msg, _data) {
			this.response = {
				status: _status,
				message: _msg,
				payload: _data
			};
		};

		this.dock = function () {
			var me = this;
			/* see the ajax handler is free */
			if (!this.ajaxFlaQ) {
				return;
			}
			/* Pull the trigger */
			$.ajax({
				type: "POST",
				data: { action: "wcff_ajax", wcff_param: JSON.stringify(this.request) },
				dataType: "json",
				url: wcff_var.ajaxurl,
				beforeSend: function () {
					/* enable the ajax lock - actually it disable the dock */
					me.ajaxFlaQ = false;
				},
				success: function (data) {
					me.mask.doUnMask();
					me.prepareResponse(data.status, data.message, data.data);
					/* handle the response and route to appropriate target */
					if (me.response.status) {
						me.responseHandler();
					} else {
						/* alert the user that some thing went wrong */
						alert(data.message);
					}
					/* disable the ajax lock */
					me.ajaxFlaQ = true;
				},
				error: function (jqXHR, textStatus, errorThrown) {
					me.mask.doUnMask();
					alert(jqXHR, textStatus, errorThrown);
					/* disable the ajax lock */
					me.ajaxFlaQ = true;
				},
				complete: function () {
					/* Just in case */
					me.mask.doUnMask();					
				}
			});
		};

		this.prepareSearchRequest = function (_method, _context, _payload) {
			var _post = 0,
				_post_type = "";
			if (typeof wcff_var !== 'undefined') {
				_post = wcff_var.post_id,
					_post_type = wcff_var.post_type;
			}
			return {
				method: _method,
				context: _context,
				post: _post,
				post_type: _post_type,
				payload: _payload
			}
		};

		this.searchDock = function (_request) {
			var me = this;
			$.ajax({
				type: "POST",
				data: { action: "wcff_ajax", wcff_param: JSON.stringify(_request) },
				dataType: "json",
				url: wcff_var.ajaxurl,
				success: function (data) {
					var response = {
						status: data.status,
						message: data,
						payload: data.data
					};
					/* handle the response and route to appropriate target */
					if (response.status) {
						if (_request.payload["context"] == "product_mapping") {
							me.handleTargetProductSearch(_request, response);
						} else {
							me.handleSearch(_request, response);
						}						
					} else {
						/* alert the user that some thing went wrong */
						alert(response.message);
						/* Hide ghost back, if it visible */
						$("div.variation-config-ghost-back").trigger("click");
					}
				},
				error: function (jqXHR, textStatus, errorThrown) {
					alert(jqXHR, textStatus, errorThrown)
				}
			});
		};

		this.responseHandler = function () {
			if (this.request.context === "product" ||
				this.request.context === "product_cat" ||
				this.request.context === "product_tag" ||
				this.request.context === "product_type" ||
				this.request.context === "product_variation") {
				this.reloadHtml(this.target.parent().parent().find("td.condition_value_td"));
			} else if (this.request.context === "location_product_data" ||
				this.request.context === "location_product" ||
				this.request.context === "location_order" ||
				this.request.context === "location_product_cat") {
				this.reloadHtml(this.target.parent().parent().find("td.location_value_td"));
			} else if (this.request.method === "POST" && this.request.context === "field") {
				this.activeRow = this.target;
				this.activeField = this.response.payload.meta;
				this.target.append(this.response.payload.widget);
				this.target.attr("data-key", this.response.payload.id);
				this.target.find("label.wcff-switch").attr("data-key", this.response.payload.id);
				this.target.find("a.wcff-field-delete").show().attr("data-key", this.response.payload.id);
				this.target.find("div.wcff_fields_factory").toggle("slow", "swing");
				this.prepareConfigWidget(true);
				/* Store it on the dirty collection */
				this.dirtyFields[this.activeField.key] = this.fetchFieldConfig();
			} else if (this.request.method === "GET" && this.request.context === "field") {
				this.activeField = this.response.payload.meta;
				this.target.append(this.response.payload.widget);
				this.target.find("div.wcff_fields_factory").toggle("slow", "swing");
				this.prepareConfigWidget(false);
				this.renderSingleView();
				/* Store it on the dirty collection */
				this.dirtyFields[this.activeField.key] = this.activeField;
			} else if (this.request.method === "PUT" && this.request.context === "field") {
				/* Remove this field from dirtyFields object */
				if (this.dirtyFields[this.request.payload.key]) {
					//delete this.dirtyFields[this.activeField.id]					
				}
			} else if (this.request.method === "DELETE" && this.request.context === "field") {
				this.target.closest(".wcff-meta-row").remove();
				if ($("#wcff-fields-set .wcff-meta-row").length == 0) {
					this.emptyNotice.show();
				} else {
					var order = wcff_var.post_type == "wcccf" ? 1 : 0;
					$("div.wcff-meta-row").each(function () {
						if (!$(this).is("#wcff-add-field-placeholder")) {
							$(this).find("input.wcff-field-order-index").val(order);
							$(this).find("span.wcff-field-order-number").text((wcff_var.post_type == "wcccf" ? order : (order + 1)));
							order++;
						}
					});
				}
			} else if (this.request.method === "GET" && this.request.context === "search") {
				if (this.request.payload["post_type"] === "wccvf" && this.currentWccvfSearchField) {
					this.wccvfPosts = this.response.payload;
				}
				if (this.request.payload["context"] == "product_mapping") {
					this.handleTargetProductSearch(this.request, this.response);
				} else {
					this.handleSearch(this.request, this.response);
				}				
			} else if (this.request.context === "wcff_field_list") {
				this.loadFieldList(this.response.payload);
			} else if (this.request.context === "render_field") {
				this.handleDropField(this.response.payload);
			} else if (this.request.context === "render_fields_for_designer") {
				this.renderLayoutField(this.response.payload);
			} else if (this.request.context === "variation_fields_mapping_list") {
				this.mapping_grid.bucket = this.response.payload;
				this.mapping_grid.prepareRecords(this.mapping_grid.bucket);
			} else if (this.request.method === "DELETE" && this.request.context === "mapping") {				
				this.mapping_grid.isReloading = true;
				this.mapping_grid.reloadingFor = "remove";				
				this.mapping_grid.bucket = this.response.payload;				
				this.mapping_grid.prepareRecords(this.mapping_grid.bucket);
				/* Hide ghost back */
				$("div.variation-config-ghost-back").trigger("click");
			} else if (this.request.method === "POST" && this.request.context === "variation_fields_map") {
				if (!this.currentWccvfSearchField) {
					this.reloadMapping();
					/* Hide ghost back */
					$("div.variation-config-ghost-back").trigger("click");
				} else {
					this.mapping_grid.isReloading = true;
					this.mapping_grid.reloadingFor = "add";
					this.mapping_grid.bucket = this.response.payload;					
					this.reloadVariationLevelConfigPopup();					
				}									
			} else if (this.request.method === "GET" && this.request.context === "wcff_field_clone") {
				location.href="";
			} else {
				/* Ignore */
			}
			this.target = null;
		};

	};

	/* Masking object ( used to mask any container whichever being refreshed ) */
	var wcffMask = function () {
		this.top = 0;
		this.left = 0;
		this.bottom = 0;
		this.right = 0;

		this.target = null;
		this.mask = null;

		this.getPosition = function (target) {
			this.target = target;

			var position = this.target.position();
			var offset = this.target.offset();

			this.top = offset.top;
			this.left = offset.left;
			this.bottom = $(window).width() - position.left - this.target.width();
			this.right = $(window).height() - position.right - this.target.height();
		};

		this.doMask = function (_target) {
			if (_target) {
				this.target = _target;
				this.mask = $('<div class="wcff-dock-loader"></div>');
				this.target.append(this.mask);
				this.mask.css("left", "0px");
				this.mask.css("top", "0px");
				this.mask.css("right", this.target.innerWidth() + "px");
				this.mask.css("bottom", this.target.innerHeight() + "px");
				this.mask.css("width", this.target.innerWidth() + "px");
				this.mask.css("height", this.target.innerHeight() + "px");
			}
		};

		this.doUnMask = function () {
			if (this.mask) {
				this.mask.remove();
			}
		};
	};

	$.fn.visibleHeight = function () {
		var elBottom, elTop, scrollBot, scrollTop, visibleBottom, visibleTop;
		scrollTop = $(window).scrollTop();
		scrollBot = scrollTop + $(window).height();
		elTop = this.offset().top;
		elBottom = elTop + this.outerHeight();
		visibleTop = elTop < scrollTop ? scrollTop : elTop;
		visibleBottom = elBottom > scrollBot ? scrollBot : elBottom;
		return visibleBottom - visibleTop
	};

	$.fn.isExceedViewport = function () {
		return ((this.offset().top + this.outerHeight()) > $(window).height());
	};

	$(document).ready(function () {
		wcffObj = new wcff();
		wcffObj.initialize();
	});	

})(jQuery);