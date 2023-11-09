<?php 

if (!defined('ABSPATH')) { exit; }

/**
 *
 * Cart Line Item price calculator.<br/>
 * Alter the existing line item price based on user values.<br/>
 * Also adds custom fee to the cart if configured so.
 *
 * @author Saravana Kumar K
 * @copyright Sarkware Research & Development (OPC) Pvt Ltd
 *
 */
class wcff_negotiator {

    /* Cart item custom data holder */
    private $cart_item;
    private $cart_item_key;
    
    public function __construct() {}
    
    /**
     *
     * Determine the line item price based on User submitted values ( while adding product to cart )<br/>
     * Loop through all the line item and calculate the product price based on Pricing Rules of each fields (if the criteria is matched)
     *
     * @param object $citem, string $cart_item_key
     *
     */
    
    public function handle_custom_pricing($citem, $cart_item_key) {
        
        $this->cart_item = $citem;
        $this->cart_item_key = $cart_item_key;

        $orgPrice = method_exists($this->cart_item["data"], "get_price") ? floatval ($this->cart_item['data']->get_price()) : floatval ($this->cart_item['data']->price);
        $orgPrice = $this->get_updated_price($orgPrice);
        
        /* Update the price */
        if (method_exists ($this->cart_item ["data"], "set_price")) {
            /* Woocommerce 3.0.6 + */
            $this->cart_item["data"]->set_price($orgPrice);
        } else {
            /* Woocommerece before 3.0.6 */
            $this->cart_item["data"]->price = $orgPrice;
        }

        return $this->cart_item;
        
    }  
    
    private function get_updated_price($orgPrice) {

        $replaced_price = 0;
        $additional_cost = 0;
        
        $basePrice = $orgPrice;       
        $customPrice = $orgPrice;

        foreach ($this->cart_item as $ckey => $cval) {
            if ((strpos($ckey, "wccpf_") !== false || strpos($ckey, "wccvf_") !== false) && isset($this->cart_item[$ckey]["pricing_rules"]) && $this->cart_item[$ckey]["user_val"]) {
                
                $ftype   = $this->cart_item [$ckey] ["ftype"];
                $dformat = $this->cart_item [$ckey] ["format"];
                $uvalue  = $this->cart_item [$ckey] ["user_val"];
                $p_rules = $this->cart_item [$ckey] ["pricing_rules"];
                
                foreach ($p_rules as $prule) {
                    if ($this->check_rules($prule, $uvalue, $ftype, $dformat)) {

                        $is_amount = isset($prule["tprice"]) && $prule["tprice"] == "cost" ? true : false;

                        /* Determine the price */
                        if ($is_amount) {

                            if (class_exists('WOOCS')) {
                                global $WOOCS;
                                if ($WOOCS->is_multiple_allowed) {
                                    $prule ['amount'] = $WOOCS->woocs_exchange_value(floatval($prule ['amount']));
                                }
                            }

                            if ($prule["ptype"] == "add") {
                                $customPrice = $customPrice + floatval ($prule["amount"]);

                                $additional_cost = $additional_cost + floatval ($prule["amount"]);

                            } else if ($prule["ptype"] == "sub") {
                                $customPrice = $customPrice - floatval ($prule["amount"]);

                                $additional_cost = $additional_cost - floatval ($prule["amount"]);
                            } else {                                
                                $customPrice = floatval($prule["amount"]);

                                $replaced_price = $replaced_price + floatval($prule["amount"]);
                            }
                        } else {
                            if ($prule ["ptype"] == "add") {
                                $additional_cost = $additional_cost + ((floatval($prule["amount"]) / 100) * $basePrice);
                            } else if ($prule["ptype"] == "sub") {
                                $additional_cost = $additional_cost - ((floatval($prule["amount"]) / 100) * $basePrice);
                            } else {                                
                                $replaced_price = $replaced_price + (floatval($prule["amount"]) / 100) * $basePrice;
                            }
                        }

                        /* Add pricing rules label - for user notification */
                        $this->cart_item["wccpf_pricing_applied_" . (strtolower(str_replace(" ", "_", $prule["title"])))] = array("title" => $prule["title"], "amount" => get_woocommerce_currency_symbol() . ($is_amount ? $prule["amount"] : ((floatval($prule["amount"]) / 100) * $basePrice)));
                    }
                }
                
                if ($replaced_price > 0) {
                    $orgPrice = $replaced_price + $additional_cost;
                } else {
                    $orgPrice = $basePrice + $additional_cost;
                }

                $orgPrice = apply_filters("wcff_negotiated_price", $orgPrice, $this->cart_item, $this->cart_item_key);
            }
        }

        return $orgPrice;

    }
    
    public function handle_tier_pricing($_new_price, $_cart_item, $_cart_item_key) {
        $this->cart_item = $_cart_item;
        $this->cart_item_key = $_cart_item_key;
        return $this->get_updated_price($_new_price);
    }
    
    /**
     *
     * Add custom fee to Cart, based on user submitted values (while adding product to cart).
     * Loop through all the line item and add the custom fee, based on Fee Rules of each fields (if the criteria is matched)
     *
     * @param object $_cart
     *
     */
    
    public function handle_custom_fee($_cart = null) {
        
        if ($_cart) {
            $cart = WC()->cart->get_cart();
            $cart_total = WC()->cart->cart_contents_total;
            foreach ($cart as $key => $citem) {
                foreach ($citem as $ckey => $cval) {
                    if (strpos($ckey, "wccpf_") !== false && isset($citem[$ckey]["fee_rules"]) && $citem[$ckey]["user_val"]) {
                        $ftype = $citem[$ckey]["ftype"];
                        $dformat = $citem[$ckey]["format"];
                        $uvalue = $citem[$ckey]["user_val"];
                        $f_rules = $citem[$ckey]["fee_rules"];
                        /* Iterate through the rules and update the price */
                        foreach ($f_rules as $frule) {
                            if ($this->check_rules($frule, $uvalue, $ftype, $dformat)) {
                                $is_tax  = isset( $frule["is_tx"] ) && $frule["is_tx"] == "non_tax" ? false : true;
                                $fee_amount = isset( $frule["tprice"] ) &&  $frule["tprice"] == "cost" ? $frule["amount"] : ( floatval ( $frule["amount"] ) / 100 ) * $cart_total;
                                WC()->cart->add_fee($frule["title"], $fee_amount, $is_tax, "");
                            }
                        }
                    }
                }
            }
        }
        
    }    
    
    /**
     *
     * Evoluate the rules (Pricing or Fee) of the given field against the submitted user value
     *
     * @param array $_rules
     * @param mixed $_value
     * @return boolean
     *
     */
    public function check_rules($_rule, $_value, $_ftype, $_dformat) {
        if (($_rule && isset($_rule["expected_value"]) && isset($_rule["logic"]) && ! empty($_value)) || $_ftype == "datepicker") {
            if ($_ftype != "checkbox" && $_ftype != "datepicker") {
                if ($_rule["logic"] == "equal") {
                    return ($_rule["expected_value"] == $_value);
                } else if ($_rule["logic"] == "not-equal") {
                    return ($_rule["expected_value"] != $_value);
                } else if ($_rule["logic"] == "greater-than" && is_numeric($_rule["expected_value"]) && is_numeric($_value)) {
                    return ($_value > $_rule["expected_value"]);
                } else if ($_rule["logic"] == "less-than" && is_numeric($_rule["expected_value"]) && is_numeric($_value)) {
                    return ($_value < $_rule["expected_value"]);
                } else if ($_rule["logic"] == "greater-than-equal" && is_numeric($_rule["expected_value"]) && is_numeric($_value)) {
                    return ($_value >= $_rule["expected_value"]);
                } else if ($_rule["logic"] == "less-than-equal" && is_numeric($_rule["expected_value"]) && is_numeric($_value)) {
                    return ($_value <= $_rule["expected_value"]);
                } else if( $_rule["logic"] == "not-null" ) {
                    $trimmed_value = trim( $_value );
                    if (!empty($trimmed_value)) {
                        return true;
                    } else {
                        return false;
                    }
                } else if( $_rule["logic"] == "null" ) {
                    return empty(trim( $_value ));
                }
            } else if ($_ftype == "checkbox") {
                /* This must be a check box field */
                if (is_array($_rule["expected_value"]) && is_array($_value)) {                    
                    if ($_rule["logic"] == "has-options") {
                        if (count($_value) >= count($_rule["expected_value"])) {
                            foreach ($_rule["expected_value"] as $e_val) {
                                if (! in_array($e_val, $_value)) {
                                    return false;
                                }
                            }
                            /* Well expected option(s) is chosen by the User */
                            return true;
                        }
                        /* Unlikely to match */
                        return false;
                    } else if ($_rule["logic"] == "has-not-options") {
                        foreach ($_rule["expected_value"] as $e_val) {
                            if (in_array($e_val, $_value)) {
                                return false;
                            }
                        }
                        /* Well expected option(s) is chosen by the User */ 
                        return true;
                    }            
                }
            } else if ($_ftype == "datepicker") {
                
                $user_date = DateTime::createFromFormat($_dformat, $_value);
                if ($user_date && isset($_rule["expected_value"]["dtype"]) && isset($_rule["expected_value"]["value"])) {
                    if ($_rule["expected_value"]["dtype"] == "days") {
                        /* If user chosed any specific day like "sunday", "monday" ... */
                        $day = $user_date->format('l');
                        if (is_array($_rule["expected_value"]["value"]) && in_array(strtolower($day), $_rule["expected_value"]["value"])) {
                            return true;
                        }
                    }
                    if ($_rule["expected_value"]["dtype"] == "specific-dates") {
                        /* Logic for any specific date matches ( Exact date ) */
                        $sdates = explode(",", (($_rule["expected_value"]["value"]) ? $_rule["expected_value"]["value"] : ""));
                        if (is_array($sdates)) {
                            foreach ($sdates as $sdate) {
                                $sdate = DateTime::createFromFormat("m-d-Y", trim($sdate));
                                if ($user_date->format("Y-m-d") == $sdate->format("Y-d-m")) {
                                    return true;
                                }
                            }
                        }
                    }
                    if ($_rule["expected_value"]["dtype"] == "weekends-weekdays") {
                        /* Logic for the weekends */
                        if ($_rule["expected_value"]["value"] == "weekends") {
                            if (strtolower($user_date->format('l')) == "saturday" || strtolower($user_date->format('l')) == "sunday") {
                                return true;
                            }
                        } else {
                            if (strtolower($user_date->format('l')) != "saturday" && strtolower($user_date->format('l')) != "sunday") {
                                return true;
                            }
                        }
                        
                    }
                    if ($_rule["expected_value"]["dtype"] == "specific-dates-each-month") {
                        /* Logic for the exact date of each month */
                        $sdates = explode(",", (($_rule["expected_value"]["value"]) ? $_rule["expected_value"]["value"] : ""));
                        
                        foreach ($sdates as $sdate) {
                            if (trim($sdate) == $user_date->format("j")) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        /* If not covered by any rules, it would be safe to return false */
        return false;
    }

}

?>