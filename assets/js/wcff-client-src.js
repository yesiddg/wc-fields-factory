/**
 * 
 * @author 		: Saravana Kumar K
 * @copyright 	: Sarkware Research & Development (OPC) Pvt Ltd
 * 
 * Wcff client controller module
 * 
 */

 var wcffEditorObj = null,
  	wcffValidatorObj = null,
 	wcffFieldsRulerObj = null,
 	wcffPricingRulerObj = null;

 (function($) {	
	
	var 
		/* Mask object for showing loading spinner */
		mask = null,
		/* Flaq for syncronized Ajax Request Handling */
		ajaxFlaQ = true,
		/* Used to holds the object that is being send to server */
		request = null,
		/* Used to holds the response from the server */
		response = null;	
	
	/* JS array compare */
	Array.prototype.equals = function (array) {
	    if (!array)
	        return false; 
	    if (this.length != array.length)
	        return false;
	    for (var i = 0, l=this.length; i < l; i++) {
	        if (this[i] instanceof Array && array[i] instanceof Array) {
	            if (!this[i].equals(array[i]))
	                return false;       
	        }           
	        else if (this[i] != array[i]) { 
	            return false;   
	        }           
	    }       
	    return true;
	}
	Object.defineProperty(Array.prototype, "equals", {enumerable: false});
	
	/**
	 * 
	 * money formater borrowed from : https://stackoverflow.com/questions/149055/how-to-format-numbers-as-currency-string/149099#149099
	 * 
	 */
	Number.prototype.formatMoney = function(decPlaces, thouSeparator, decSeparator) {
	    var n = this,
	        decPlaces = isNaN(decPlaces = Math.abs(decPlaces)) ? 2 : decPlaces,
	        decSeparator = decSeparator == undefined ? "." : decSeparator,
	        thouSeparator = thouSeparator == undefined ? "," : thouSeparator,
	        sign = n < 0 ? "-" : "",
	        i = parseInt(n = Math.abs(+n || 0).toFixed(decPlaces)) + "",
	        j = (j = i.length) > 3 ? j % 3 : 0;
	    return sign + (j ? i.substr(0, j) + thouSeparator : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thouSeparator) + (decPlaces ? decSeparator + Math.abs(n - i).toFixed(decPlaces).slice(2) : "");
	};
	
	/**
	 * 
	 * Wcff Field Ruler Module
	 * 
	 */
	var wcffFieldRuler = function() {
		
		this.init = function() {
			$(document).on("change", "[data-has_field_rules=yes]", this, function(e) {
				e.data.handleFieldChangeEvent($(this));
			});		
			$("[data-has_field_rules=yes]").trigger("change");
		};
		
		this.handleFieldChangeEvent = function(_field) {
			var i = 0,
				j = 0,
				me = this,
				days = [],
				date = "",
				value = "",
				fkeys = [],	
				dates = [],
				flaQ = false,
				chosen_date = "",
				common_items = [],
				fkey = _field.attr( "data-fkey"),	
				ftype = _field.attr("data-field-type"),						
				container = _field.closest("div.wcff-fields-group"),
				custom_layout = container.attr("data-custom-layout");
			
			if (ftype == "radio") {
				value = _field.closest("ul").find("input[type=radio]:checked").val();
			} else if (ftype == "checkbox") {
				value = _field.closest("ul").find("input[type=checkbox]:checked").map(function() {
				    return me.escapeQuote(this.value);
				}).get();				
			} else if (ftype == "datepicker") {
				day = ["sunday","monday","tuesday","wednesday","thursday","friday","saturday"];
				chosen_date = _field.datepicker("getDate");
			} else if (ftype == "text" || ftype == "number" 
					|| ftype == "select" || ftype == "textarea" 
					|| ftype == "colorpicker") {
				value = _field.val();
			}

			if (wcff_fields_rules_meta[fkey]) {   
				for (i = 0; i < wcff_fields_rules_meta[fkey].length; i++) {
					if (wcff_fields_rules_meta[fkey][i].logic == "equal" && wcff_fields_rules_meta[fkey][i].expected_value == value) {
						this.handleFieldsVisibility(container, wcff_fields_rules_meta[fkey][i].field_rules);
					} else if (wcff_fields_rules_meta[fkey][i].logic == "not-equal" && wcff_fields_rules_meta[fkey][i].expected_value != value) {
						this.handleFieldsVisibility(container, wcff_fields_rules_meta[fkey][i].field_rules);
					} else if (wcff_fields_rules_meta[fkey][i].logic == "greater-than-equal" && wcff_fields_rules_meta[fkey][i].expected_value <= value) {
						this.handleFieldsVisibility(container, wcff_fields_rules_meta[fkey][i].field_rules);
					} else if (wcff_fields_rules_meta[fkey][i].logic == "greater-than" && wcff_fields_rules_meta[fkey][i].expected_value < value) {
						this.handleFieldsVisibility(container, wcff_fields_rules_meta[fkey][i].field_rules);
					} else if (wcff_fields_rules_meta[fkey][i].logic == "less-than-equal" && wcff_fields_rules_meta[fkey][i].expected_value >= value) {
						this.handleFieldsVisibility(container, wcff_fields_rules_meta[fkey][i].field_rules);
					} else if (wcff_fields_rules_meta[fkey][i].logic == "less-than" && wcff_fields_rules_meta[fkey][i].expected_value > value) {
						this.handleFieldsVisibility(container, wcff_fields_rules_meta[fkey][i].field_rules);
					} else if (wcff_fields_rules_meta[fkey][i].logic == "is-only" && wcff_fields_rules_meta[fkey][i].expected_value.equals(value)) {
						this.handleFieldsVisibility(container, wcff_fields_rules_meta[fkey][i].field_rules);
					} else if (wcff_fields_rules_meta[fkey][i].logic == "is-also" && wcff_fields_rules_meta[fkey][i].expected_value.some(r=> value.includes(r))) {
						this.handleFieldsVisibility(container, wcff_fields_rules_meta[fkey][i].field_rules);
					} else if (wcff_fields_rules_meta[fkey][i].logic == "any-one-of") {
						var common_items = this.fetchCommonItems(wcff_fields_rules_meta[fkey][i].expected_value, value);
						if (common_items.length <= wcff_fields_rules_meta[fkey][i].expected_value.length) {
							flaQ = true;
							for (j = 0; j < common_items.length; j++) {
								if (wcff_fields_rules_meta[fkey][i].expected_value.indexOf(common_items[j]) === -1) {
									flaQ = false;
								}
							}
							if (flaQ) {
								this.handleFieldsVisibility(container, wcff_fields_rules_meta[fkey][i].field_rules);
							}
						}
					} else if (wcff_fields_rules_meta[fkey][i].logic == "has-options" && wcff_fields_rules_meta[fkey][i].expected_value.some(r=> value.includes(r))) {
						this.handleFieldsVisibility(container, wcff_fields_rules_meta[fkey][i].field_rules);
					} else if (wcff_fields_rules_meta[fkey][i].logic == "has-not-options" && !wcff_fields_rules_meta[fkey][i].expected_value.some(r=> value.includes(r))) {
						this.handleFieldsVisibility(container, wcff_fields_rules_meta[fkey][i].field_rules);
					} else if (wcff_fields_rules_meta[fkey][i].logic == "days" && Array.isArray(wcff_fields_rules_meta[fkey][i].expected_value)) {
						if (wcff_fields_rules_meta[fkey][i].expected_value.indexOf(chosen_date.getDay()) != -1) {
							this.handleFieldsVisibility(container, wcff_fields_rules_meta[fkey][i].field_rules);
						}
					} else if (wcff_fields_rules_meta[fkey][i].logic == "specific-dates" && wcff_fields_rules_meta[fkey][i].expected_value != "") {
						dates = wcff_fields_rules_meta[fkey][i].expected_value.split(",");
						for (j = 0; j < dates.length; j++) {
							date = dates[j].trim().split("-");
							if ((parseInt(date[0].trim()) == (chosen_date.getMonth()+1)) && (parseInt(date[1].trim()) == chosen_date.getDate()) && (parseInt(date[2].trim()) == chosen_date.getFullYear())) {
								this.handleFieldsVisibility(container, wcff_fields_rules_meta[fkey][i].field_rules);
							}
						}
					} else if (wcff_fields_rules_meta[fkey][i].logic == "weekends-weekdays") {
						if (wcff_fields_rules_meta[fkey][i].expected_value == "weekends") {
							if( chosen_date.getDay() == 6 || chosen_date.getDay() == 0 ) {
								this.handleFieldsVisibility(container, wcff_fields_rules_meta[fkey][i].field_rules);
							}
						} else {
							if( chosen_date.getDay() != 6 || chosen_date.getDay() != 0 ) {
								this.handleFieldsVisibility(container, wcff_fields_rules_meta[fkey][i].field_rules);
							}
						}
					} else if (wcff_fields_rules_meta[fkey][i].logic == "specific-dates-each-month") {
						dates = wcff_fields_rules_meta[fkey][i].expected_value.split(",");
						for (j = 0; j < dates.length; j++) {
							if (parseInt(dates[j].trim()) == chosen_date.getDay()) {
								this.handleFieldsVisibility(container, wcff_fields_rules_meta[fkey][i].field_rules);
							}
						}
					} else if (wcff_fields_rules_meta[fkey][i].logic == "not-null" && value) {
						this.handleFieldsVisibility(container, wcff_fields_rules_meta[fkey][i].field_rules);
					} else if (wcff_fields_rules_meta[fkey][i].logic == "null" && (value === "" || value == null)) {
						this.handleFieldsVisibility(container, wcff_fields_rules_meta[fkey][i].field_rules);
					}					
				}					
			}
		}
			
		this.handleFieldsVisibility = function(_container, _rules) {
			var parent = null, 
				layout = _container.attr("data-custom-layout");
			_container.find(".wccpf-field ").each(function () {

				if (layout == "yes") {
					parent = $(this).closest("div.wcff-layout-form-col");
				} else if ($(this).parent().hasClass("wcff-label")) {
					parent = $(this).parent();
				} else {
					parent = $(this).closest("table.wccpf_fields_table");	
				}

				if (_rules[$(this).attr("data-fkey")]) {
					if (_rules[$(this).attr("data-fkey")] == "show") {
						parent.show();
						parent.removeClass("wcff_is_hidden_from_field_rule");
					} else if(_rules[$(this).attr("data-fkey")] == "hide") {
						parent.hide();
						parent.addClass("wcff_is_hidden_from_field_rule");
					}
				}
			});
		};
		
		this.fetchCommonItems = function(_a1, _a2) {
			return $.grep(_a1, function(element) {
			    return $.inArray(element, _a2 ) !== -1;
			});
		};
		
		this.escapeQuote = function(_str) {	
			if (_str) {
				_str = _str.replace( /'/g, '&#39;' );
				_str = _str.replace( /"/g, '&#34;' );
			}			
			return _str;
		};
		
		this.unEscapeQuote = function(_str) {
			if (_str) {
				_str = _str.replace( /&#39;/g, "'" );
				_str = _str.replace( /&#34;/g, '"' );
			}
			return _str;
		};
	};
	
	/**
	 * 
	 * Wcff Cloning module
	 * 
	 */
	var wcffCloner = function() {
		
		this.init = function() {
			$(document).on("change", "input[name=quantity]", this, function(e) {
				
				var qty = parseInt($(this).val());
				var prev_qty = parseInt($("#wccpf_fields_clone_count").val());
				$("#wccpf_fields_clone_count").val(qty);
				
				if (prev_qty < qty) {
					var i = 0,
						j = 0,
						x = 0,
						me = e.data,
						cloned = null,
						group = null,
						groups = null,
						wrapper = null,
						cloneable = false;					
						
					wrapper = $(".wccpf-fields-group-container");
                    for (j = 0; j < wrapper.length; j++) {							
                        group = $(wrapper[j]).find("> div:not(.cloned)");

                        if (group && group.length > 0) {
                            
                            if (group.attr("data-group-clonable") == "no") {
                                continue;
                            }

                            cloned = null;

                            for (i = prev_qty; i < qty; i++) {
                            
                                cloned = group.clone(true); 
                                cloned.addClass("cloned");
                                cloned.find("script").remove();				
                                cloned.find("div.sp-replacer").remove();
                                cloned.find("span.wccpf-fields-group-title-index").html(i + 1);
                                cloned.find(".hasDatepicker").attr( "id", "" );
                                cloned.find(".hasDatepicker").removeClass( "hasDatepicker" );                                                     
                                                    
                                cloned.find(".wccpf-field").each(function() {
                                    if (!$(this).hasClass("label")) {
                                        me.updateFieldIndex(i, $(this));
                                        if ($(this).attr("data-field-type") == "checkbox" || $(this).attr("data-field-type") == "radio") {
                                            $(this).prop("checked", false);
                                        } else {
                                            $(this).val("");	
                                        }										
                                    }                                    
                                });
                                
                                cloned.find(".wccaf-field").each(function() {
                                    if (!$(this).hasClass("label")) {
                                        me.updateFieldIndex(i, $(this));	
                                        if ($(this).attr("data-field-type") == "checkbox" || $(this).attr("data-field-type") == "radio") {
                                            $(this).prop("checked", false);
                                        } else {
                                            $(this).val("");	
                                        }	
                                    }                                    
                                });
                                                    
                                cloned.find(".wccvf-field").each(function() {
                                    if (!$(this).hasClass("label")) {
                                        me.updateFieldIndex(i, $(this));
                                        if ($(this).attr("data-field-type") == "checkbox" || $(this).attr("data-field-type") == "radio") {
                                            $(this).prop("checked", false);
                                        } else {
                                            $(this).val("");	
                                        }		
                                    }                                    
                                });
                            
                                /* Check for the label field - since label is using different class */
                                cloned.find(".wcff-label").each(function() {
                                    cloneable = $(this).attr('data-cloneable');	
                                    var label_name_attr = $(this).find("input").attr( "name" ).slice( 0, -1 ) + i;
                                    $(this).find("input").attr( "name", label_name_attr );
                                    if (typeof cloneable === typeof undefined || cloneable === false) {
                                        $(this).remove();
                                    }
                                });
                            
                                /* Remove empty columns and rows */
                                cloned.find("div[class=wcff-layout-form-col]:not(:has(*))").remove();
                                cloned.find("div[class=wcff-layout-form-row]:not(:has(*))").remove();								
                                
                                $(wrapper[j]).append(cloned);
                                /* Trigger the color picker init function */
                                setTimeout( function(){ 
                                    init_color_pickers();
                                    //group.find( '[data-has_field_rules="yes"]' ).trigger( "change" );
                                }, 500 );							
                        }
                    }							
                }
					
				} else {					
					//$("div.wccpf-fields-group:eq("+ ( product_count - 1 ) +")").nextAll().remove();	
					var diff = prev_qty - qty;
					wrapper = $(".wccpf-fields-group-container");
					for (j = 0; j < wrapper.length; j++) {
						groups = $(wrapper[j]).find("> div");
						for (x = 0; x < diff; x++) {
							wrapper.find("> div:nth-child(" + (prev_qty - x) + ")").remove();
						}											
					}					
				}				
			});
			/* Trigger to change event - fix for min product quantity */
			setTimeout(function(){ $("input[name=quantity]").trigger("change"); }, 300);
		};
		
		this.updateFieldIndex = function(_index, _field) {
			
			/* Clonable flaq */
			var cloneable = _field.attr('data-cloneable');
			if (_field.attr( "data-field-type" ) === "checkbox" || _field.attr( "data-field-type" ) === "radio") {
				cloneable = _field.closest("ul").attr('data-cloneable');
			}
			
            if (typeof cloneable === 'undefined') {
                cloneable = "yes";
            }

			/* Check if the field is allowed to clone */
			if (cloneable !== "no") {
				
				var name_attr = _field.attr("name");					
				if( name_attr.indexOf("[]") != -1 ) {
					var temp_name = name_attr.substring( 0, name_attr.lastIndexOf("_") );							
					name_attr = temp_name + "_" + (_index + 1) + "[]";						
				} else {
					name_attr = name_attr.slice( 0, -1 ) + (_index + 1);
				}
				_field.attr( "name", name_attr );
				
			} else {
				/* Otherwise remove from cloned */								
				_field.closest("table.wccpf_fields_table").remove();																
			}
			
		};
	};
	
	/**
	 * 
	 * Wcff validation module
	 * 
	 */
	var wcffValidator = function() {
				
		this.isValid = true;	
			
		this.init = function() {						
			$(document).on("submit", "form.cart", this, function(e) {
				var me = e.data; 
				e.data.isValid = true;				
				$(this).find(".wccpf-field").each(function() {
					if ($(this).attr("data-mandatory") === "yes") {
						me.doValidate($(this));
					}					
				});					
				return e.data.isValid;                 
			});
			if (wccpf_opt.validation_type === "blur") {
				$( document ).on( "blur", ".wccpf-field", this, function(e) {	
					if ($(this).attr("data-mandatory") === "yes") {
						e.data.doValidate($(this));
					}
				});
			}
		};
		
		this.doValidate = function(field) {
			if (field.attr("data-field-type") !== "radio" && field.attr("data-field-type") !== "checkbox" && field.attr("data-field-type") !== "file") {
				if (field.attr("data-field-type") !== "select") {
					if (this.doPatterns(field.attr("data-pattern"), field.val())) {						
						field.nextAll(".wccpf-validation-message").hide();
					} else {						
						this.isValid = false;
						field.nextAll(".wccpf-validation-message").css("display", "block");
					}
				} else {
					if (field.val() !== "" && field.val() !== "wccpf_none") {
						field.nextAll(".wccpf-validation-message").hide();
					} else {
						this.isValid = false;
						field.nextAll(".wccpf-validation-message").css("display", "block");
					}
				}							
			} else if (field.attr("data-field-type") === "radio") {				
				if (field.closest("td.wccpf_value").find("input[type=radio]").is(':checked')) {
					field.closest("td.wccpf_value").find("span.wccpf-validation-message").hide();
				} else {
					field.closest("td.wccpf_value").find("span.wccpf-validation-message").css("display", "block");
					this.isValid = false;					
				}	 			
			} else if (field.attr("data-field-type") === "checkbox") {			
				var values = field.closest("td.wccpf_value").find("input[type=checkbox]:checked").map(function() {
				    return this.value;
				}).get();
				if (values.length === 0) {
					field.closest("td.wccpf_value").find("span.wccpf-validation-message").css("display", "block");
					this.isValid = false;
				} else {						
					field.closest("td.wccpf_value").find("span.wccpf-validation-message").hide();
				}			
			} else if (field.attr("data-field-type") === "file") {		                
				if (field.val() == "") {
					field.next().css("display", "block");
					this.isValid = false;
				} else {
					field.next().hide();
				}									
			}
			return this.isValid;
		}
		
		this.doPatterns = function(patt, val) {
			
			var pattern = {
				mandatory	: /\S/, 
				number		: /^-?\d+\.?\d*$/,
				email		: /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i,	      	
			};			    

			if (patt && pattern[patt]) {
				return pattern[patt].test(val);	
			}

			return true;
		    
		};
		
	};
	
	/* Masking object ( used to mask any container whichever being refreshed ) */
	var wcffMask = function() {
		this.top = 0;
		this.left = 0;
		this.bottom = 0;
		this.right = 0;
		
		this.target = null;
		this.mask = null;
		
		this.getPosition = function( target ) {
			this.target = target;		
			
			var position = this.target.position();
			var offset = this.target.offset();
		
			this.top = offset.top;
			this.left = offset.left;
			this.bottom = $(window).width() - position.left - this.target.width();
			this.right = $(window).height() - position.right - this.target.height();
		};

		this.doMask = function(_target) {
			if (_target) {
				this.target = _target;			
				this.mask = $('<div class="wcff-dock-loader"></div>');						
				this.target.append(this.mask);
				this.mask.css("left", "0px");
				this.mask.css("top", "0px");
				this.mask.css("right", this.target.innerWidth()+"px");
				this.mask.css("bottom", this.target.innerHeight()+"px");
				this.mask.css("width", this.target.innerWidth()+"px");
				this.mask.css("height", this.target.innerHeight()+"px");
			}			
		};

		this.doUnMask = function() {
			if (this.mask) {
				this.mask.remove();
			}			
		};
	};
	
	var wcffPricingHandler = function() {
		
		/* Used for check box rules */
		this.appliedRules = {};
		
		this.init = function() {
			this.registerEvents();
		};
		
		this.registerEvents = function() {				
			
			/**
			 * 
			 * Change event handler for fields which have pricing rules
			 * 
			 */
		
			$(document).on("change", "[data-has_pricing_rules=yes]", this, function(e) {
				e.data.updatePrice($(this));
				e.data.refreshPricingRuleTitles($(this));
			});		
							
		};

		/* Responsible for updating applied pricing rule title */
		this.refreshPricingRuleTitles = function(_target) {

			var me = this,
				rules = [],
				fields = [],				
				ptitle = "",				
				parent = null,
				prule_data = [],
				title_container = null;
			
			/* Determine the pricing title parent container parent */
			if (typeof _target !== "undefined") {					
				parent = _target.closest( "div.wcff-fields-group" );					
			} else {					
				parent = $( "div.wccpf-fields-container" ); 
			}

			$("div.wcff-fields-group").each(function() {

				ptitle = "";
				fields = $(this).find('[data-has_pricing_rules="yes"]');

				/* Collect the pricing rules */
				prule_data = me.getFieldsPricingRules(fields);				

				if (Array.isArray(prule_data) && prule_data.length > 0) {

					for (let i = 0; i < prule_data.length; i++) {

						if (wcff_pricing_rules_meta && wcff_pricing_rules_meta[prule_data[i]["fkey"]]) {
							rules = wcff_pricing_rules_meta[prule_data[i]["fkey"]];
							for (let j = 0; j < rules.length; j++) {
								
								if (me.checkPricingRules(rules[j], prule_data[i]["fval"], prule_data[i]["ftype"], prule_data[i]["dformat"])) {
									if(rules[j]["tprice"] == "cost") {
										ptitle += rules[j]["title"] +" : "+  wccpf_opt.currency + rules[j]["amount"] +"<br/>";
									} else {
										ptitle += rules[j]["title"] +" : "+  rules[j]["amount"] +"%<br/>";
									}
									
								}
							}
						}

					}

				}				
				
				/* Update pricing title - if needed */
				if (wccpf_opt.price_details == "show" && ptitle != "") {
					title_container = $(this).find(".wcff_pricing_rules_title_container");			
					if (title_container.length == 0) {
						$(this).append( '<div class="wcff_pricing_rules_title_container">'+ptitle+'</div>' );
					} else {
						title_container.html(ptitle);
						title_container.show();
					}
				} else {
					$(this).find(".wcff_pricing_rules_title_container").hide();					
				}

			});

		};
		
		/* Responsible for updating product price */
		this.updatePrice = function(_target) {
			
			var fields = [],
				prule_data = [];
			
			/* Collect fields which has pricing rules */
			if (wccpf_opt["is_page"] == "archive") {								
				fields = _target.closest("li.product").find("[data-is_pricing_rules=yes]");				
			} else {								
				fields = $('[data-has_pricing_rules="yes"]');							
			}
			
			/* Collect the pricing rules */
			prule_data = this.getFieldsPricingRules(fields);
			
			if (Array.isArray(prule_data) && prule_data.length > 0) {
				this.determineThePrice(prule_data, _target);
			}
			
		};

		this.getFieldsPricingRules = function(_fields) {

			var fkey = "",
				fvalue = "",
				prule_data = [],
				currentField = null;

			for (let i = 0; i < _fields.length; i++) {
				currentField = $(_fields[i]);

				if (currentField.is(":visible") || (currentField.is(".wccpf-color") && currentField.closest("table").is(":visible") && !currentField.closest("table").is(".wcff_is_hidden_from_field_rule"))) {
					
					fkey = currentField.attr("data-fkey");

					if (currentField.is("[type=checkbox]")) {
						fvalue = [];
						if (currentField.is(":checked")) {
							fvalue = [currentField.val()];
						}
					} else if (currentField.is("[type=radio]")) {
						fvalue = "";
						if (currentField.is(":checked")) {
							fvalue = currentField.val();
						}
					} else {
						fvalue = currentField.val();
					}

					prule_data.push({"fkey": fkey, "fval": fvalue, "ftype": currentField.attr("data-field-type"), "dformat": currentField.attr("data-date-format")});
				}
			}

			return prule_data;

		};
		
		this.determineThePrice = function(_fields, _target) {
			
			var i, j,				
				rules,
				childs,    
                decimals,
				keys = [],
				pcontainer,
                phtml = "",
				ptitle = "",                
				variations = [],
				base_price = 0,
				additonal_cost = 0,
				replace_amount = 0;
			
			if (wcff_is_variable == "yes") {
				if ($("input[name=variation_id]").val() != "") {
					variations = $("form.variations_form").attr("data-product_variations");
					variations = JSON.parse(variations);
					keys = Object.keys(variations);
					for (i = 0; i < keys.length; i++) {
						if (variations[keys[i]]["variation_id"] == $("input[name=variation_id]").val()) {
							base_price = variations[keys[i]]["display_regular_price"];
						}
					}
				} else {
					/* Nothing to do */
					return;
				}
			} else {
				base_price = wcff_product_price;
			}
			
			for (i = 0; i < _fields.length; i++) {		
				if (wcff_pricing_rules_meta && wcff_pricing_rules_meta[_fields[i]["fkey"]]) {
					rules = wcff_pricing_rules_meta[_fields[i]["fkey"]];
					for (j = 0; j < rules.length; j++) {
						
						if (this.checkPricingRules(rules[j], _fields[i]["fval"], _fields[i]["ftype"], _fields[i]["dformat"])) {			
							
							if (rules[j]["tprice"] == "cost") {					
								/* Cost mode */
								if (rules[j]["ptype"] == "add") {
									additonal_cost += parseFloat(rules[j]["amount"]);
								} else if (rules[j]["ptype"] == "sub") {
									additonal_cost -= parseFloat(rules[j]["amount"]);
								} else {
									/* Replace */
									replace_amount += parseFloat(rules[j]["amount"]);
								}						
							} else {						
								/* Percent mode */
								if (rules[j]["ptype"] == "add") {							
									additonal_cost += ((parseFloat(rules[j]["amount"]) / 100) * base_price);
								} else if (rules[j]["ptype"] == "sub") {
									additonal_cost -= ((parseFloat(rules[j]["amount"]) / 100) * base_price);
								} else {
									/* Replace */
									replace_amount += ((parseFloat(rules[j]["amount"]) / 100) * base_price);
								}						
							}

							ptitle += rules[j]["title"] +" : "+  wccpf_opt.currency + rules[j]["amount"] +"<br/>";
						}
					}
				}
			}	
			
			if (replace_amount > 0) {
				base_price = additonal_cost + replace_amount;		 
			} else {
				base_price = base_price + additonal_cost + replace_amount;		
			}	
				
			if (wccpf_opt.real_time_price_update == "enable") {

                decimals = wccpf_opt.number_of_decimal;
                if (wccpf_opt.trim_zeros == "yes") {
                    decimals = 0;
                }

                if (wccpf_opt.currency_position == "left") {
                    phtml = '<bdi><span class="woocommerce-Price-currencySymbol">' + wccpf_opt.currency + '</span>'+ (base_price).formatMoney(decimals, wccpf_opt.thousand_seperator, wccpf_opt.decimal_seperator) +'</bdi>';
                } else if (wccpf_opt.currency_position == "right") {
                    phtml = '<bdi>'+ (base_price).formatMoney(decimals, wccpf_opt.thousand_seperator, wccpf_opt.decimal_seperator) +'<span class="woocommerce-Price-currencySymbol">' + wccpf_opt.currency + '</span></bdi>';
                } else if (wccpf_opt.currency_position == "left_space") {
                    phtml = '<bdi><span class="woocommerce-Price-currencySymbol">' + wccpf_opt.currency + '</span>&nbsp;'+ (base_price).formatMoney(decimals, wccpf_opt.thousand_seperator, wccpf_opt.decimal_seperator) +'</bdi>';
                } else if (wccpf_opt.currency_position == "right_space") {
                    phtml = '<bdi>'+ (base_price).formatMoney(decimals, wccpf_opt.thousand_seperator, wccpf_opt.decimal_seperator) +'&nbsp;<span class="woocommerce-Price-currencySymbol">' + wccpf_opt.currency + '</span></bdi>';
                }

				if (wccpf_opt.price_container_is == "default") {    
					pcontainer = (wcff_is_variable == "yes") ? $("form.variations_form span.amount") : $("div.summary span.amount");							    
                    pcontainer.html(phtml);
				} else {
					if($(wccpf_opt.price_container).length > 0) {
						$(wccpf_opt.price_container).html('<span class="woocommerce-Price-amount amount">' +phtml +'</span>');
					}
				}		
			}											
			
		};
		
		this.checkPricingRules = function(_rule,_value, _ftype, _dformat) {
			
			var i, 
				day,
				sdate,
				sdates;	
						
			if ((_rule && _rule["expected_value"] && _rule["logic"] && _value != "") || _ftype == "datepicker") {
				if (_ftype != "checkbox" && _ftype != "datepicker") {
	                if (_rule["logic"] == "equal") {
	                    return (_rule["expected_value"] == _value);
	                } else if (_rule["logic"] == "not-equal") {
	                    return (_rule["expected_value"] != _value);
	                } else if (_rule["logic"] == "greater-than") {
	                    return (parseFloat(_value) > parseFloat(_rule["expected_value"]));
	                } else if (_rule["logic"] == "less-than") {
	                    return (parseFloat(_value) < parseFloat(_rule["expected_value"]));
	                } else if (_rule["logic"] == "greater-than-equal") {
	                    return (parseFloat(_value) >= parseFloat(_rule["expected_value"]));
	                } else if (_rule["logic"] == "less-than-equal") {
	                    return (parseFloat(_value) <= parseFloat(_rule["expected_value"]));
	                } else if (_rule["logic"] == "not-null" ) {                    
	                    if (_value.trim() != ""){
	                	    return true;
	                	} else {
	                	    return false;
	                	}
	                }
	            } else if (_ftype == "checkbox") {	
	                /* This must be a check box field */
	                if (Array.isArray(_rule["expected_value"]) && Array.isArray(_value)) {		
						if (_rule["logic"] == "has-options") {							
							if (_value.length >= _rule["expected_value"].length) {
								 /* Now check for the individual options are matching */
	                        	for (i = 0; i < _rule["expected_value"].length; i++) {
	                        		if (_value.indexOf(_rule["expected_value"][i]) == -1) {
	                        			/* Well has exact quantity on both side but with one or more different values */
	                        			return false;
	                        		}
	                        	}   
							} else {
								return false;
							}
							
							return true;
						}
				    }
	            } else if (_ftype == "datepicker") {
		
					if (_value == "") {
						return false;
					}
				
					/* Date format conversion for moment js */
					var year_format_length = (_dformat.match(/y/gi)||[]).length;
				
					if (year_format_length == 2) {
						_dformat = _dformat.replace("yy", "yyyy");
					} else if (year_format_length == 1) {
						_dformat = _dformat.replace("y", "yy");
					}
				
	            	const user_date = moment(_value, _dformat.toUpperCase());            	  
	                if (user_date && _rule["expected_value"]["dtype"] && _rule["expected_value"]["value"]) { 
	                    if (_rule["expected_value"]["dtype"] == "days") {
	                        /* If user chosed any specific day like "sunday", "monday" ... */
	                    	day = user_date.format("dddd");                    	
	                    	if (Array.isArray(_rule["expected_value"]["value"]) && _rule["expected_value"]["value"].indexOf(day.toLowerCase()) != -1) {
	                    		return true;
	                    	}                    	
	                    } 
	                    if (_rule["expected_value"]["dtype"] == "specific-dates") {           
	                        /* Logic for any specific date matches ( Exact date ) */
	                    	sdates = _rule["expected_value"]["value"].split(",");                        
	                    	if (Array.isArray(sdates)) {
	                    		for (i = 0; i < sdates.length; i++) {
	                    			sdate = moment(sdates[i].trim(), "M-D-YYYY");
	                    			if (user_date.format("M-D-YYYY") == sdate.format("M-D-YYYY")) {
	                    				return true; 
	                    			}
	                    		}
	                    	}                    	                        
	                    } 
	                    if (_rule["expected_value"]["dtype"] == "weekends-weekdays") {   
	                    	/* Logic for the weekends */
	                    	if (_rule["expected_value"]["value"] == "weekends") {
	                    		if (user_date.format("dddd").toLowerCase() == "saturday" || user_date.format("dddd").toLowerCase() == "sunday") {
	                    			return true;
	                    		}
	                    	} else {
	                    		if (user_date.format("dddd").toLowerCase() != "saturday" && user_date.format("dddd").toLowerCase() != "sunday") {
	                    			return true;
	                    		}
	                    	}
	                    }                    
	                    if (_rule["expected_value"]["dtype"] == "specific-dates-each-month") {   
	                    	sdates = _rule["expected_value"]["value"].split(",");
	                    	for (i = 0; i < sdates.length; i++) {
	                    		if (sdates[i].trim() == user_date.format("D")) {
	                    			return true;
	                    		}
	                    	}
	                    }                    
	                }
	            }		
			}
			
			return false;			
		};
		
	};
	
	var wcffCartEditor = function() {
		
		/* self object */
		var self = this;
		
		this.init = function() {
			this.registerEvent();
		};
		
		this.registerEvent = function() {
			
			/* Double clikc handler for vcart field - which will show the editor window for that field */
			$(document).on("dblclick", "li.wcff_cart_editor_field", this, function(e) {	
				if ($("div.wccpf-cart-edit-wrapper").length > 0) {
					/* Do nothing since already one field is in edit mode */
					return;
				}
				var target = $(this);	
				target.closest("ul.wccpf-is-editable-yes").removeClass("wccpf-is-editable-yes");
				if (!target.find("input, select, textarea, label").length != 0 && target.is(".wcff_cart_editor_field")) {					
					e.data.getFieldForEdit(target);
				}				
			});
								
			/* Click event hanlder cart field Update button */
			$(document).on("click", ".wccpf-update-cart-field-btn", this, function(e) {
				e.data.updateField($(this));
				e.preventDefault();
			});
				
			/* Click event hanlder for Cart Editor close button */
			$(document).on("click", "#wccpf-cart-editor-close-btn", function(e) {
				var editor = $(this).parent();
				editor.closest("ul.wccpf-cart-editor-ul").addClass("wccpf-is-editable-yes");
				editor.prev().show();
				editor.remove();
				mask.doUnMask();
				e.preventDefault();
			});
			
			/* Key down event handler - for ESC key 
			 * If pressed the editor window will be closed */
			$(window).on("keydown", function(e) {
				var keyCode = (e.keyCode ? e.keyCode : e.which);   
				var editor = $("div.wccpf-cart-edit-wrapper");
				if (keyCode === 27 && editor.length > 0) {
					editor.closest("ul.wccpf-cart-editor-ul").addClass("wccpf-is-editable-yes");
					editor.prev().show();
					editor.remove();
				}
			});
			
			$(document).on("change", "[data-is_pricing_rules=yes]", function(e) {
				self.updateNegotiatePrice($(this));
			});
			
			// on load pring negotiation
			setTimeout(function() {
				$('[data-has_field_rules="yes"]').trigger("change");
				if (wccpf_opt["is_page"] != "archive") {
					self.updateNegotiatePrice();
				}
			}, 180);
					
			$(document).on("change", "input[name=variation_id]", function() {
				var variation_id = $("input[name=variation_id]").val();
				if (variation_id.trim() != "") {					
					prepareRequest("wcff_variation_fields", "GET", {"variation_id" : $("input[name=variation_id]").val()});
					dock("wcff_variation_fields");					
				} else {
					$(".wcff-variation-field").html("");
					self.updateNegotiatePrice($(this));
				}
			});
			
		};
		
		this.getFieldForEdit = function(_target) {
								
			/* Retrieve the value (for color picker it is different, if store admin chosen to display as color itself) */
			var fieldValue = (_target.find(".wcff-color-picker-color-show").length != 0) ? _target.find(".wcff-color-picker-color-show").css("background-color") : $.trim(_target.find("p").text());			
			var payload = { 
				product_id: _target.attr("data-productid"), 
				product_cart_id: _target.attr("data-itemkey"), 
				data: { 
					value: fieldValue,
					field: _target.attr("data-field"),
					name: _target.attr("data-fieldname")					 
				} 
			};
			
			prepareRequest("wcff_render_field_on_cart_edit", "GET", payload);
			dock("inflate_field_for_edit", _target);
			
		};
		
		this.updateNegotiatePrice = function(_target) {

            if (!_target) {
                return;
            }
			
			var currentField = $(""),
				is_field_cloneable = "no",
				is_globe_cloneable	= wccpf_opt.cloning == "yes" ? "yes" : "no",
				dataObj = wccpf_opt["is_page"] == "archive" ? _target.closest("li.product").find("[data-is_pricing_rules=yes]")  : $("[data-is_pricing_rules=yes]"),			
				prod_id = wccpf_opt["is_page"] == "archive" ? _target.closest("li.product").find("a.add_to_cart_button").attr("data-product_id") : $("input[name=add-to-cart]").length != 0 ? $("input[name=add-to-cart]").val() :  $("button[name=add-to-cart]").val(),
				data	= {"_product_id" : prod_id, "_variation_id" : $("input[name=variation_id]").val(), "_fields_data" : []},
				variation_not_null =  $("input[name=variation_id]").length != 0 && ($("input[name=variation_id]").val().trim() == "" || $("input[name=variation_id]").val().trim() == "0") ? false : true;
			
			if (variation_not_null) {
				for (var i = 0; i < dataObj.length; i++) {
					currentField = $(dataObj[i]);
					if (currentField.is(":visible") || (currentField.is(".wccpf-color") && currentField.closest("table").is(":visible") && !currentField.closest("table").is( ".wcff_is_hidden_from_field_rule"))) {
						is_field_cloneable = is_globe_cloneable == "yes" ? currentField.is("[type=radio]") || currentField.is("[type=checkbox]") ? currentField.closest("ul").data("cloneable") : currentField.data("cloneable") : is_globe_cloneable;
						var field_name  = currentField.is("[type=checkbox]") ? currentField.attr("name").replace("[", "").replace("]", "") : currentField.attr("name"),
							field_value = currentField.is("[type=checkbox]") ? currentField.prop("checked") ? [currentField.val()] : [] : currentField.is("[type=radio]") ? currentField.is(":checked") ? currentField.val() : "" : currentField.val();
						data._fields_data.push({"is_clonable" : is_field_cloneable, "name" : field_name, "value" : field_value});
					}
				}
				//prepareRequest("wcff_ajax_get_negotiated_price", "GET", data);
				//dock("wcff_ajax_get_negotiated_price", _target);
			}
			
		};
		
		this.updateField = function(_btn) {
			var payload,
			fvalue = null,
			validator = new wcffValidator(),
			field_key = _btn.closest( "div.wccpf-cart-edit-wrapper" ).attr( "data-field" ),
			field_name = _btn.closest( "div.wccpf-cart-edit-wrapper" ).attr( "data-field_name" ),
			field_type = _btn.closest( "div.wccpf-cart-edit-wrapper" ).attr( "data-field_type" ),
			productId = _btn.closest( "div.wccpf-cart-edit-wrapper" ).attr( "data-product_id" ),
			cartItemKey = _btn.closest( "div.wccpf-cart-edit-wrapper" ).attr( "data-item_key" );		
			
			if (field_type === "radio") {
				validator.doValidate( _btn.closest( "div.wccpf-cart-edit-wrapper" ).find( "input" ) );				
				fvalue = _btn.closest( "div.wccpf-cart-edit-wrapper" ).find( "input:checked" ).val();								
			} else if (field_type === "checkbox") {
				validator.doValidate( _btn.closest( "div.wccpf-cart-edit-wrapper" ).find( "input" ) );
				fvalue = _btn.closest( "div.wccpf-cart-edit-wrapper" ).find("input:checked").map(function() {
				    return this.value;
				}).get();
			} else {				
				validator.doValidate( _btn.closest( "div.wccpf-cart-edit-wrapper" ).find( ".wccpf-field" ) );
				fvalue = _btn.closest( "div.wccpf-cart-edit-wrapper" ).find( ".wccpf-field" ).val();
			}			
			
			if (validator.isValid) {
				/* Initiate the ajax Request */
				payload = { 
					product_id : productId, 
					cart_item_key : cartItemKey,
					data : { 
						field: field_key, 
						name: field_name, 
						value: fvalue, 
						field_type : field_type
					}
				}
				prepareRequest( "wcff_update_cart_field_data", "PUT", payload );
				dock( "update_cart_field_data", _btn );
			}		
		};
		
		this.responseHandler = function(_action, _target) {
			
			if (!response.status) {
				/* Something went wrong - Do nothing */
				return;
			}			
			
			if (_action === "inflate_field_for_edit" && response.payload) {
				var wrapper = '';
				/* Get the reference of head tag, we might need to inject some script tag there
				 * incase if the data being edited is either datepicker or color picker */
				var dHeader = $("head");
				/* Find the last td of the field wrapper to add update button */
				var editFieldHtml = $(response.payload.html).find("td:last");
				/* Construct update button */
				var updateBtn = '<button data-color_show="'+ response.payload.color_showin +'" class="button wccpf-update-cart-field-btn">Update</button>';
				
				if (response.payload.field_type !== "file") {		
					wrapper = '<div class="wccpf-cart-edit-wrapper wccpf-cart-edit-'+ response.payload.field_type +'-wrapper" data-field_type="'+ response.payload.field_type +'" data-field="'+ _target.attr("data-field") +'" data-field_name="'+ _target.attr("data-fieldname") +'" data-product_id="'+ _target.attr("data-productid") +'" data-item_key="'+ _target.attr("data-itemkey") +'">';
					wrapper += '<a href="#" id="wccpf-cart-editor-close-btn" title="Close Editor"></a>';
					wrapper += (editFieldHtml.html() + updateBtn);
					wrapper += '<div>';
					wrapper = $(wrapper);
					_target.hide();
					_target.parent().append(wrapper);
				}				
				if( response.payload.field_type == "email" || response.payload.field_type == "text" || response.payload.field_type == "number" || response.payload.field_type == "textarea" ){
					//_target.parent().find( ".wccpf-field" ).val( this.request.payload.data.value );
					wrapper.find("input").trigger( "focus" );
				} else if( response.payload.field_type == "colorpicker" ){
					dHeader.append( response.payload.script );
				} else if( response.payload.field_type == "datepicker" ){
					_target.parent().find( ".wccpf-field" ).val( request.payload.data.value );
					if( dHeader.find( "script[data-type=wpff-datepicker-script]" ).length == 0 ){
						dHeader.append( response.payload.script );
					}
					dHeader.append( $( response.payload.html )[2] );
				}
			} else if( _action == "update_cart_field_data" ){
				if( response.payload.status ) {
					if (response.payload.field_type !== "colorpicker") {							
						_target.closest( "div.wccpf-cart-edit-wrapper" ).parent().find("li.wcff_cart_editor_field").show().html( '<p>'+ decodeURI( response.payload.value ) +'</p>' );
					} else {
						if (_target.closest( "div.wccpf-cart-edit-wrapper" ).parent().find("li.wcff_cart_editor_field").attr("data-color-box") === "yes") {
							_target.closest( "div.wccpf-cart-edit-wrapper" ).parent().find("li.wcff_cart_editor_field").show().html( '<p><span class="wcff-color-picker-color-show" style="background: '+ decodeURI( response.payload.value ) + ';"></span></p>' );
						} else {
							_target.closest( "div.wccpf-cart-edit-wrapper" ).parent().find("li.wcff_cart_editor_field").show().html( '<p>'+ decodeURI( response.payload.value ) +'</p>' );
						}
					}					
					_target.closest( "ul.wccpf-cart-editor-ul" ).addClass("wccpf-is-editable-yes");
					_target.closest( "div.wccpf-cart-edit-wrapper" ).remove();
				} else {
					_target.prev().html( response.payload.message ).show();
				}
			} else if( _action == "wcff_ajax_get_negotiated_price" ){
				var parent = typeof _target == "undefined" ? $( "div.product" ) : wccpf_opt.is_page == "single" ? _target.closest( "div.product" ) :  _target.closest( "li.product" );
				if( response.payload.status ) {
					var wcff_p_title_container = parent.find( ".wcff_pricing_rules_title_container" ),
						p_title_html = "";
					if( wccpf_opt.ajax_pricing_rules_title.trim() == "show" && response.payload.data["data_title"].length != 0 ){
						p_title_html += '<h4 class="wcff_pricing_rules_title_container">'+wccpf_opt.ajax_pricing_rules_title_header.trim()+'</h4>';
					}
					p_title_html += "<table><tbody>";
					for( var i = 0; i < response.payload.data["data_title"].length; i++ ){
						p_title_html += "<tr><td>"+response.payload.data["data_title"][i]["title"]+"</td><td>"+response.payload.data["data_title"][i]["amount"]+"</td></tr>";
					}
					p_title_html += "</table></tbody>";
					if ( wccpf_opt.price_details == "show" ) {
						if ( wcff_p_title_container.length != 0 ) {
							wcff_p_title_container.html( p_title_html );
							// If negotiate price are empty - remove price titles 
							if( response.payload.data.data_title.length == 0 ){
								$( ".wcff_pricing_rules_title_container" ).hide();
							} else {
								$( ".wcff_pricing_rules_title_container" ).show();
							}
						} else {
							parent.find( ".wccpf_fields_table:last" ).parent().after( '<div class="wcff_pricing_rules_title_container">'+p_title_html+'</div>' );
						}
					}
					if( wccpf_opt["is_page"] == "archive" ){
						parent.find( "span.price span.amount:last" ).replaceWith( response.payload.data["amount"] );
					} else {
						if( wccpf_opt.ajax_pricing_rules_price_container_is == "default" || wccpf_opt.ajax_pricing_rules_price_container_is == "both" ){
							if( $( ".summary.entry-summary .woocommerce-variation-price:visible" ).length != 0 ){
								$( ".summary.entry-summary .woocommerce-variation-price" ).html( response.payload.data["amount"] )
							} else {
								$( ".summary.entry-summary .price .woocommerce-Price-amount" ).replaceWith( response.payload.data["amount"] );
							}
							if( wccpf_opt.ajax_pricing_rules_price_container_is == "both" ){
								$( wccpf_opt.ajax_price_replace_container ).html( response.payload.data["amount"] );
							}
						} else {
							$( wccpf_opt.ajax_price_replace_container ).html( response.payload.data["amount"] );
						}
					}
				} else {
					
				}
				$( ".woocommerce-variation-add-to-cart .button, button[name=add-to-cart]" ).removeClass( "disabled" );
			} else if( _action == "wcff_variation_fields" ){
				var variation_container = $( ".wcff-variation-field" );
				variation_container.html( "" );
				if( variation_container.length != 0 ){
					var variation_fields = response.payload.data;
					for( var i = 0; i < variation_fields.length; i++ ){
						if( variation_fields[i]["location"] == "color_picker_scripts" ){
							$( "body" ).append(variation_fields[i]["html"]);
						} else {
							$( ".wcff-variation-field[data-area='"+variation_fields[i]["location"]+"']" ).append(variation_fields[i]["html"]);
						}
					}
				} 
				var variation_container = $( ".wcff-variation-cloning-field-container" );
				for( var i = 0; i < variation_container.length; i++ ){
					var container = $( variation_container[i] );
					if( container.find( ".wcff-variation-field" ).children().length == 0 ){
						container.hide();
					} else {
						container.show();
					}
				}
				// trigger init field rule
				$( '[data-has_field_rules="yes"]' ).trigger( "change" );
				
				//self.updateNegotiatePrice();
			}
		};
		
	};
	
	function init_color_pickers() {
		var i = 0,
			j = 0,
			config = {},
			palette = [],
			keys = Object.keys(wcff_color_picker_meta);
		for (i = 0; i < keys.length; i++) {	
			config = {};
			palette = [];
			config["color"] = wcff_color_picker_meta[keys[i]]["default_value"];
			config["preferredFormat"] = wcff_color_picker_meta[keys[i]]["color_format"];			
			if (wcff_color_picker_meta[keys[i]]["palettes"] && wcff_color_picker_meta[keys[i]]["palettes"].length > 0) {				
				config["showPalette"] = true;
				if (wcff_color_picker_meta[keys[i]]["show_palette_only"] == "yes") {
					config["showPaletteOnly"] = true;
				}
				
				for (j = 0; j < wcff_color_picker_meta[keys[i]]["palettes"].length; j++) {
					palette.push(wcff_color_picker_meta[keys[i]]["palettes"][j].split(','));
				}
				config["palette"] = palette;
			}			

			if( wcff_color_picker_meta[keys[i]]["show_palette_only"] != "yes" && wcff_color_picker_meta[keys[i]]["color_text_field"] == "yes") {
				config["showInput"] = true;
			}

			$("input.wccpf-color-"+ keys[i]).spectrum(config);
		}
	}
	
	function renderVariationFields() {
		var i = 0,
			keys = [];			
			
		/* Hide the spinner */
		$("div.wccvf-loading-spinner").remove();
		/* Enable the variation selects */
		$("table.variations select").prop("disabled", false);
		/* Remove loading class */
		$("#wcff-variation-fields").removeClass("loading");
		
		if(!$("input[name=variation_id]").val()) {
			return;
		}	
				
		/* Inject widget */
		$("#wcff-variation-fields").html(response.payload.html);
		
		/* Parse the meta */
		response.payload.meta = JSON.parse(response.payload.meta);
		
		/* Inject meta */
		if (wcff_date_picker_meta) {
			wcff_date_picker_meta = Array.isArray(wcff_date_picker_meta) ? {} : wcff_date_picker_meta;
			keys = Object.keys(response.payload.meta.date_picker_meta);
			for (i = 0; i < keys.length; i++) {
				wcff_date_picker_meta[keys[i]] = response.payload.meta.date_picker_meta[keys[i]];
			}
		}
		if (wcff_color_picker_meta) {
			wcff_color_picker_meta = Array.isArray(wcff_color_picker_meta) ? {} : wcff_color_picker_meta;
			keys = Object.keys(response.payload.meta.color_picker_meta);
			for (i = 0; i < keys.length; i++) {
				wcff_color_picker_meta[keys[i]] = response.payload.meta.color_picker_meta[keys[i]];
			}
			/* Refresh the color picker widgets */
			init_color_pickers();
		}		
		if (wcff_fields_rules_meta) {
			wcff_fields_rules_meta = Array.isArray(wcff_fields_rules_meta) ? {} : wcff_fields_rules_meta;
			keys = Object.keys(response.payload.meta.fields_rules_meta);
			for (i = 0; i < keys.length; i++) {
				wcff_fields_rules_meta[keys[i]] = response.payload.meta.fields_rules_meta[keys[i]];
			}
		}
		if (wcff_pricing_rules_meta) {
			wcff_pricing_rules_meta = (Array.isArray(wcff_pricing_rules_meta) && wcff_pricing_rules_meta.length == 0) ? {} : wcff_pricing_rules_meta;
			keys = Object.keys(response.payload.meta.pricing_rules_meta);
			for (i = 0; i < keys.length; i++) {
				wcff_pricing_rules_meta[keys[i]] = response.payload.meta.pricing_rules_meta[keys[i]];				
			}
		}
		
		setTimeout(function() {
			wcffPricingRulerObj.updatePrice();
		}, 200);	
	}
	
	/* Request object for all the wcff cart related Ajax operation */
	function prepareRequest(_request, _method, _data, _post) {
		request = {
			method	 	: _method,
			context 	: _request,
			post 		: _post,
			post_type 	: "wccpf",
			payload 	: _data,
		};
	}
	
	/* Ajax response wrapper object */
	function prepareResponse(_status, _msg, _data) {
		response = {
			status : _status,
			message : _msg,
			payload : _data
		};
	}
	
	function dock(_action, _target, is_file) {		
		/* see the ajax handler is free */
		if (!ajaxFlaQ) {
			return;
		}
		$.ajax({  
			type       : "POST",  
			data       : { action : "wcff_ajax", wcff_param : JSON.stringify(request) },  
			dataType   : "json",  
			url        : woocommerce_params.ajax_url,  
			beforeSend : function(){  				
				/* enable the ajax lock - actually it disable the dock */
				ajaxFlaQ = false;	
				/* If target is there, then mask it */
				if (mask && _target) {
					mask.doMask(_target);
				}
			},  
			success    : function(data) {				
				/* disable the ajax lock */
				ajaxFlaQ = true;				
				prepareResponse(data.status, data.message, data.data);		               

				/* handle the response and route to appropriate target */
				if (response.status) {
					responseHandler(_action, _target);
				} else {
					/* alert the user that some thing went wrong */
					//me.responseHandler( _action, _target );
				}				
			},  
			error      : function(jqXHR, textStatus, errorThrown) {                
				/* disable the ajax lock */
				ajaxFlaQ = true;
			},
			complete   : function() {
				mask.doUnMask();
			}   
		});		
	}
	
	function responseHandler(_action, _target) {
		
		if (!response.status) {
			/* Something went wrong - Do nothing */
			return;
		}
		
		if (_action === "wcff_variation_fields") {
			renderVariationFields();
		} else {
			if (wcffEditorObj) {
				wcffEditorObj.responseHandler(_action, _target);
			}			
		}
		
	}
	
	/**
	 * 
	 * Datepicker init handler
	 * 
	 */
	$(document).on("focus", "input.wccpf-datepicker", function() {   

		/* Fields key used to get the meta */
		var m, d, y,
			config = {},
			meta = null,
			hours = [],
			minutes = [],
			hour_min = [],
			weekenddate = null,
			currentdate = null,
			disableDates = "",
			allowed_dates = "",			
			fkey = $(this).attr("data-fkey");

		var today = new Date();

		/* Make sure the datepicker has meta */
		if (wcff_date_picker_meta && wcff_date_picker_meta[fkey]) {
			meta = wcff_date_picker_meta[fkey];
			
			/* Set localize option */
			if (typeof $ != "undefined" && typeof $.datepicker != "undefined") {
				if (meta["localize"] != "none" && meta["localize"] != "en") {
					$.datepicker.setDefaults($.extend({}, $.datepicker.regional[meta["localize"]]));
				} else {
					$.datepicker.setDefaults($.extend({}, $.datepicker.regional["en-GB"]));
				}
			}
			
			/* Check for timepicker */
			if (meta["field"]["timepicker"] && meta["field"]["timepicker"] === "yes") {
				/* Time picker related config */
				config["controlType"] = "select";
				config["oneLine"] = true;
				config["timeFormat"] = "hh:mm tt";				
				/* Min Max hours and Minutes */
				if (meta["field"]["min_max_hours_minutes"] && meta["field"]["min_max_hours_minutes"] !== "") {
					hour_min = meta["field"]["min_max_hours_minutes"].split("|");
					if (hour_min.length === 2) {
						if (hour_min[0] !== "") {
							hours = hour_min[0].split(":");
							if (hours.length === 2) {
								config["hourMin"] = hours[0];
								config["hourMax"] = hours[1];
							}							
						}
						if (hour_min[1] !== "") {
							minutes = hour_min[1].split(":");
							if (minutes.length === 2) {
								config["minuteMin"] = minutes[0];
								config["minuteMax"] = minutes[1];
							}
						}
					}
				}				
			}
			
			/* Date format */
			config["dateFormat"] = meta["dateFormat"];
			
			if (meta["field"]["display_in_dropdown"] && meta["field"]["display_in_dropdown"] === "yes") {
				config["changeMonth"] = true;
				config["changeYear"] = true;
				config["yearRange"] = meta["year_range"];
			}
			
			if (meta["field"]["disable_date"] && meta["field"]["disable_date"] !== "") {
				if ("future" === meta["field"]["disable_date"]) {
					config["maxDate"] = 0;
				}
				if ("past" === meta["field"]["disable_date"]) {
					config["minDate"] = new Date();
				}
			}
			
			if (meta["field"]["disable_next_x_day"] && meta["field"]["disable_next_x_day"] != "") {
				/* Consider takiong account of already disabled dates */
				let cDay = today.getDay(),				
					disableNextXDay = parseInt(meta["field"]["disable_next_x_day"], 10);				

				if (meta["field"]["weekend_weekdays"] && meta["field"]["weekend_weekdays"] == "weekends") {					
					if((cDay == 6 || cDay == 0)) {
						if (cDay == 6) {
							disableNextXDay = disableNextXDay + 2;
						} else if (cDay == 0) {
							disableNextXDay = disableNextXDay + 1;
						}					
					} else {						
						/* calculate the offset */
						let tDay = cDay + disableNextXDay;
						if (tDay > 5) {							
							disableNextXDay = disableNextXDay + 2;
						}
					}					
				}
				config["minDate"] = "+'"+ disableNextXDay +"'d";
			}
			
			if (meta["field"]["allow_next_x_years"] && meta["field"]["allow_next_x_years"] != "" ||
				meta["field"]["allow_next_x_months"] && meta["field"]["allow_next_x_months"] != "" ||
				meta["field"]["allow_next_x_weeks"] && meta["field"]["allow_next_x_weeks"] != "" ||
				meta["field"]["allow_next_x_days"] && meta["field"]["allow_next_x_days"] != "") {
				
				allowed_dates = "";
				if (meta["field"]["allow_next_x_years"] && meta["field"]["allow_next_x_years"] != "") {
					allowed_dates += "+"+ meta["field"]["allow_next_x_years"].trim() +"y ";
				}
				if (meta["field"]["allow_next_x_months"] && meta["field"]["allow_next_x_months"] != "") {
					allowed_dates += "+"+ meta["field"]["allow_next_x_months"].trim() +"m ";
				}
				if (meta["field"]["allow_next_x_weeks"] && meta["field"]["allow_next_x_weeks"] != "") {
					allowed_dates += "+"+ meta["field"]["allow_next_x_weeks"].trim() +"w ";
				}
				if (meta["field"]["allow_next_x_days"] && meta["field"]["allow_next_x_days"] != "") {
					allowed_dates += "+"+ meta["field"]["allow_next_x_days"].trim() +"d";
				}
				config["minDate"] = 0;
				config["maxDate"] = allowed_dates.trim();				
			}
			
			config["onSelect"] = function(dateText) {	
				$(this).trigger("change");						
			    $(this).next().hide();
			};
			
			config["beforeShowDay"] = function(date) {
				var i = 0,
					test = "",
					day = date.getDay(),
					disableDays = "",
					disableDateAll = "";
				
				if (meta["field"]["disable_days"] && meta["field"]["disable_days"].length > 0) {				
						day = date.getDay(),
						disableDays = meta["field"]["disable_days"];
					for (i = 0; i < disableDays.length; i++) {
						test = disableDays[i]
					 	test = test == "sunday" ? 0 : test == "monday" ? 1 : test == "tuesday" ? 2 : test == "wednesday" ? 3 : test == "thursday" ? 4 : test == "friday" ? 5 : test == "saturday" ? 6 : "";
				        if (day == test) {									        
				            return [false];
				        }
					}						
				}
				
				if (meta["field"]["specific_date_all_months"] && meta["field"]["specific_date_all_months"] != "") {			 		
			 			disableDateAll = meta["field"]["specific_date_all_months"].split(",");			 			
			 		for (var i = 0; i < disableDateAll.length; i++) {
						if (parseInt(disableDateAll[i].trim()) == date.getDate()){
							return [false];
						}					
			 		}
				}
				
				if (meta["field"]["specific_dates"] && meta["field"]["specific_dates"] != "") {
					disableDates = meta["field"]["specific_dates"].split(",");
					/* Sanitize the dates */
					for (var i = 0; i < disableDates.length; i++) {	
						disableDates[i] = disableDates[i].trim();
					}
					/* Form the date string to compare */							
					m = date.getMonth();
					d = date.getDate();
					y = date.getFullYear();
					currentdate = ( m + 1 ) + '-' + d + '-' + y ;
					/* Make dicision */	
					if ($.inArray(currentdate, disableDates) != -1) {
						return [false];
					}				
				}	
				
				if (meta["field"]["disable_next_x_day"] && meta["field"]["disable_next_x_day"] != "") {

					


				}
				
				if (meta["field"]["weekend_weekdays"] && meta["field"]["display_in_dropdown"] != "") {
					if (meta["field"]["weekend_weekdays"] == "weekdays"){
						//weekdays disable callback
						weekenddate = $.datepicker.noWeekends(date);
						return [!weekenddate[0]];
					} else if (meta["field"]["weekend_weekdays"] == "weekends") {
						//weekend disable callback						
						return $.datepicker.noWeekends(date);
					}
				}	
				
				return [true];
			};
		}
		
		if (meta["field"]["timepicker"] && meta["field"]["timepicker"] === "yes") {
			$(this).datetimepicker(config);
		} else {
			$(this).datepicker(config);   
		}
	});
	
	/**
	 * 
	 * Variation change handler
	 * 
	 */
	$(document).on("change", "input[name=variation_id]", function() {
		
		var variation_id = $("input[name=variation_id]").val();
		if( variation_id.trim() != "" ) {		 			
			
			/* Fetch variable fields */
			if ($("#wcff-variation-fields").length > 0) {
				
				/* Disable the variation selects */
				$("table.variations select").prop("disabled", true);
			
				$("#wcff-variation-fields").addClass("loading");
				$("a.reset_variations").after($('<div class="wccvf-loading-spinner"></div>'));
				
				prepareRequest("wcff_variation_fields", "GET", {"variation_id" : variation_id}, "");
				dock("wcff_variation_fields", $("#wcff-variation-fields"));
					
			}
								
		} else {
			$("#wcff-variation-fields").html("");			
		}
		
	});
	
	$(document).on("reset_data", function() {
		$("#wcff-variation-fields").html("");		
	});	
	
	/**
	 * 
	 * Last minute cleanup operations, before the Product Form submitted for Add to Cart
	 * 
	 */
	$(document).on( "submit", "form.cart", function() {		console.log("on submit called");		
		
		// To remove hidden field table
		$(".wcff_is_hidden_from_field_rule").remove();

		if (typeof(wccpf_opt.location) !== "undefined") {			
			var me = $(this);		
			$(".wccpf_fields_table").each(function() {

                /* Make sure it is not from related products - If show archive enabled then this happens */
                let liParent = $(this).closest("li.product");
                if (liParent && liParent.length > 0) {
                    return;
                }

				if ($(this).closest("form.cart").length == 0) {
					var cloned = $(this).clone(true);
					cloned.css("display", "none");
					
					/* Since selected flaq doesn't carry over by Clone method, we have to do it manually */
					/* carry all field value to server */
					if ($(this).find(".wccpf-field").attr("data-field-type") === "select") {
						cloned.find("select.wccpf-field").val($(this).find("select.wccpf-field").val());
					}
					me.append(cloned);
				}
			});			
		} 		
		
	});

	function wcff_get_fields_value(product_fields, parent) {
				
		var data = {},
		single_field = $("");
		
		for (var i = 0; i < product_fields.length; i++) {			
			single_field = $(product_fields[i]);			
			if (single_field.closest(".wcff_is_hidden_from_field_rule").length == 0) {

				if (wcffValidatorObj && single_field.attr("data-mandatory") === "yes") {					
					wcffValidatorObj.doValidate(single_field);					
				}
				
				if (!single_field.is("[type=checkbox]") && !single_field.is("[type=radio]")) {
					data[single_field.attr("name")] = parent.find('[name="'+single_field.attr("name")+'"]').val();
				} else if (single_field.is("[type=radio]")) {                    
                    data[single_field.attr("name")] = parent.find('[name="'+single_field.attr("name")+'"]:checked').val();
                } else if(single_field.is("[type=checkbox]") && single_field.is(":checked")) {					
                    var key = single_field.attr("name").replace("[]", "");
					if (typeof data[key] == "undefined") {
						data[key] = [];
					}
					data[key].push(single_field.val());
				}
				 
			}			
		}

		return data;
	}
	
	$(document).ready(function() {
		
		/* initiate mask object */
		mask = new wcffMask();
		
		if (typeof wccpf_opt != "undefined") {
			/* Initialize fields cloner module */
			if (typeof(wccpf_opt.cloning) !== "undefined" && wccpf_opt.cloning === "yes") {
				var wcffClonerObj = new wcffCloner();
				wcffClonerObj.init();
			}		
			/* Initialize validation module */
			if (typeof(wccpf_opt.validation) !== "undefined" && wccpf_opt.validation === "yes") {			
				wcffValidatorObj = new wcffValidator();
				wcffValidatorObj.init();
			}
		}
		
		/* Initialize fields ruler module */
		wcffFieldsRulerObj = new wcffFieldRuler();
		wcffFieldsRulerObj.init();
		
		/* Initialize pricing handler */
		wcffPricingRulerObj = new wcffPricingHandler();
		wcffPricingRulerObj.init();	
		
		/* Initialize cart editor handler */
		if ($(".single-product").length != 0 || (typeof(wccpf_opt.editable) !== "undefined" && wccpf_opt.editable === "yes") || $("[data-is_pricing_rules=yes]").length != 0) {
			wcffEditorObj = new wcffCartEditor();
			wcffEditorObj.init();
		} else {
			var editors = $("li.wcff_cart_editor_field");
			editors.removeClass("wcff_cart_editor_field").removeAttr('title data-field data-fieldname data-productid data-itemkey');
			editors.closest(".wccpf-is-editable-yes").removeClass('wccpf-is-editable-yes');
		}	
		
		/* Initialize color picker fields */
		if (typeof wcff_color_picker_meta !== 'undefined' && !$.isEmptyObject(wcff_color_picker_meta)) {
			init_color_pickers();
		}
		
        prepareRequest("wcff_variation_fields", "GET", {"variation_id" : 0});
		dock("wcff_variation_fields");

		if (wccpf_opt.is_page === "archive") {								
			if (wccpf_opt.is_ajax_add_to_cart == "no") {
				$(document).on('click', ".add_to_cart_button:not(.product_type_variable)", function(e) {
					var parent =  $(this).closest("li.product"),
						product_fields = parent.find(".wccpf_fields_table:not(.wcff_is_hidden_from_field_rule) .wccpf-field"),
						data = wcff_get_fields_value(product_fields, parent),
						query_string = "";
					for (var j in data) {
						query_string += "&"+j+"="+data[j];
					}
					if (query_string != "") {
						$(this).attr( "href", $(this).attr("href")+query_string);
					}
				});				
			}			
		}

		$(document).on( "change", ".wccpf-field", function( e ) {
			var target = $( this ),
				prevExt = ['jpeg', 'jpg', 'png', 'gif', 'bmp'];

			if(target.is( "input[type=file]" ) && target.attr("data-preview") === "yes") {
				if ( $.inArray( target.val().split('.').pop().toLowerCase(), prevExt ) !== -1 ) {
			        if( !target.next().is( ".wcff_image_prev_shop_continer" ) ) {
			        	   	target.after( '<div class="wcff_image_prev_shop_continer" style="width:'+ target.attr("data-preview-width") +'"></div>' );
			        }		          
		        	    var html = "";
		        	    for( var i = 0; i < target[0].files.length; i++ ) {
		        		   html += '<img class="wcff-prev-shop-image" src="'+ URL.createObjectURL( target[0].files[i] ) +'">';
		        		   target[0].files[i].name = target[0].files[i].name.replace(/'|$|,/g, '');
		        		   target[0].files[i].name = target[0].files[i].name.replace('$', '');
		        	    }
		        	    target.next( ".wcff_image_prev_shop_continer" ).html( html );			           
			    }
			}
		});

		$(document).on("change", ".wccpf-color-radio-btn-wrapper input[type=radio]", function() {
			$(".wccpf-color-radio-btn-wrapper").removeClass("active");
			$(".wccpf-color-radio-btn-wrapper").each(function() {
				if ($(this).find("input[type=radio]").is(":checked")) {
					$(this).addClass("active");
				}
			});
		});

		$(document).on("change", ".wccpf-image-radio-btn-wrapper input[type=radio]", function() {
			$(".wccpf-image-radio-btn-wrapper").removeClass("active");
			$(".wccpf-image-radio-btn-wrapper").each(function() {
				if ($(this).find("input[type=radio]").is(":checked")) {
					$(this).addClass("active");
				}
			});
		});

		$(document).on("change", ".wccpf-text-radio-btn-wrapper input[type=radio]", function() {
			$(".wccpf-text-radio-btn-wrapper").removeClass("active");
			$(".wccpf-text-radio-btn-wrapper").each(function() {
				if ($(this).find("input[type=radio]").is(":checked")) {
					$(this).addClass("active");
				}
			});
		});

		$(document.body).on('adding_to_cart', function(e, _btn, _data) {

            let parent = null;
            /* Check for _btn is valid jquery object */
            if (_btn && !_btn instanceof jQuery) {
				_btn = $(_btn);
			} 
            /* No need to go further if it is not jQUery object */
            if (_btn instanceof jQuery) {
                /* 1 attempt if it is from archive page */
                parent = _btn.closest("li.product");                                 
                if (!parent || parent.length == 0) {
                    /* 2 attempt if it is from single product page */
                    parent = _btn.closest("form.cart");
                }
                if (parent && !parent instanceof jQuery) {
                    parent = $(parent);
                }
                if (parent && parent.length > 0) {
                    let product_fields = parent.find(".wccpf_fields_table .wccpf-field");		
                    let data = wcff_get_fields_value(product_fields, parent);
                    $.extend(_data, data);
                }			
            }

		});
		
		// on load pring negotiation
		setTimeout(function() {
			//$('[data-has_field_rules="yes"]').trigger("change");
			if (wccpf_opt["is_page"] != "archive") {
				wcffPricingRulerObj.updatePrice();
			}
		}, 200);

	});
		
})(jQuery);