<?php 

if (!defined('ABSPATH')) {exit;}

/**
 *
 * Perform validation for product fields, especially at the time of Add To Cart
 *
 * @author : Saravana Kumar K
 * @copyright : Sarkware Research & Development (OPC) Pvt Ltd
 *
 */

class wcff_validator {
    
    private $pid = null;
    private $passed = null;
    private $file_size_ok = null;
    private $quantity = 0;
    
    private $is_cloning_enabled = "no";
    private $product_field_groups = null;
    private $admin_field_groups = null;
    
    public function __construct() {}
    
    public function validate($_pid, $_passed) {

        $this->pid = $_pid;
        $this->passed = $_passed;
        $this->file_size_ok = true;
        $this->quantity = isset($_REQUEST["quantity"]) ? intval($_REQUEST["quantity"]) : 1;

        if ($this->pid) {
            $wccpf_options = wcff()->option->get_options();
            $this->is_cloning_enabled = isset($wccpf_options["fields_cloning"]) ? $wccpf_options["fields_cloning"] : "no";
            $is_admin_module_enabled = isset($wccpf_options["enable_admin_field"]) ? $wccpf_options["enable_admin_field"] : "yes";
            $is_variable_module_enabled = isset($wccpf_options["enable_variable_field"]) ? $wccpf_options["enable_variable_field"] : "yes";

            /* Get the last used template from session */
            $template = WC()->session->get("wcff_current_template", "single-product");
            
            $this->product_field_groups = wcff()->dao->load_fields_groups_for_product($this->pid, 'wccpf', $template, "any");

            $this->admin_field_groups = array();
            if ($is_admin_module_enabled == "yes") {
                $this->admin_field_groups = wcff()->dao->load_fields_groups_for_product($this->pid, 'wccaf', $template, "any");
            }            
                    
            /* If it is Variation products, then loads fields for Variations too */
            if (isset($_REQUEST) && isset($_REQUEST["variation_id"]) && $_REQUEST["variation_id"] != 0 && !empty($_REQUEST["variation_id"])) {                    

                $wccvf_posts = wcff()->dao->load_fields_groups_for_product($_REQUEST["variation_id"], 'wccpf', $template, "any");
                $this->product_field_groups = array_merge( $this->product_field_groups, $wccvf_posts);   
                  
                if ($is_variable_module_enabled == "yes") {
                    $wccvf_posts = wcff()->dao->load_fields_groups_for_product($_REQUEST["variation_id"], 'wccvf', "any");                
                    $this->product_field_groups = array_merge($this->product_field_groups, $wccvf_posts);                
                }
                
            }
            
            /* Perform validation for product fields */
            $this->handle_validation();
        }
        
        /* Check for missing link */
        if (!is_bool($this->passed)) {
            $this->passed = true;
        }
        
        return $this->passed;
    }
    
    /**
     * 
     * Used from cart item splitter & cart editor
     * 
     */
    public function validate_helper($_prod_id, $field,  $_key, $_value, $cart_key, $_variation_id = 0) {
           
        $is_passed = true;
        $is_admin  = false;
                  
        if ($field != null) {           
            $res = true;
            $res_size_val = true;
            $field["required"] = isset ($field ["required"]) ? $field ["required"] : "no";
            if ($field ["required"] == "yes" || $field ["type"] == "file") {
                if ($field ["type"] != "file") {
                    $res = wcff()->validator->validate_field($_prod_id, $field, $_key, $_value);
                } else {
                    /* We dont support file field on cart editor */        
                }
            }
            if (!$res || ! $res_size_val) {
                $is_passed = false;
                $msg = ! $res ? $field ["message"] : "Upload size limit exceed, Allow size is " . $field ["max_file_size"] . "kb.!";
            }
        }
   
        return array("status" => $is_passed, "is_admin" => $is_admin, "msg" => $msg);
    }
    
    /**
     * 
     * Initiate the validation process, based on cloning option.
     * 
     */
    private function handle_validation() {
        
        /* Temp store groups for later use, especially on Cloned Fields Validation */
        $backup_product_field_groups = $this->product_field_groups;
        $backup_admin_field_groups = $this->admin_field_groups;
        
        if ($this->is_cloning_enabled == "no") {
            /* Before check validation to remove field rule not applicable field from product fields */
            $this->remove_field_if_rule_is_hidden();
            /* Perform validation on product fields */
            $this->validate_fields($this->product_field_groups);
            /* Perform validation for admin fields */
            $this->validate_fields($this->admin_field_groups);
        } else {
            /* Special care for cloning option */
            for ($i = 1; $i <= $this->quantity; $i++) {
                /* Restore the orinal list */
                $this->product_field_groups = $backup_product_field_groups;
                $this->admin_field_groups = $backup_admin_field_groups;                
                /* Before check validation to remove field rule not applicable field from product fields */
                $this->remove_field_if_rule_is_hidden($i);
                /* Perform validation on product fields */
                $this->validate_fields($this->product_field_groups, $i);
                /* Perform validation for admin fields */
                $this->validate_fields($this->admin_field_groups, $i);
            }
        }
        
    }
    
    /**
     * 
     * @param number $_index
     * 
     * Remove unused fields meta, from group list
     * This is needed if any field has Field Rules 
     * 
     */
    private function remove_field_if_rule_is_hidden($_index = 0) {  
        
        $key_suffix = $_index > 0 ? ("_". $_index) : "";
        
        foreach ($this->product_field_groups as $gpos => $group) {
            if (count($group["fields"]) > 0) {
                
                if ($_index > 1 && $group["is_clonable"] == "no") {
                    /**
                     * 
                     * Group clonable is disabled so no need to go further
                     * The group meta must be removed, as it is irrelevant 
                     * 
                     */
                    unset($this->product_field_groups[$gpos]);
                    continue;
                }              
                
                foreach ($group["fields"] as $fpos => $field) {
                    
                    $cloneable = isset($field["cloneable"]) ? $field["cloneable"] : "yes";
                    if ($_index > 1 && $cloneable == "no") {
                        /**
                         *
                         * Field clonable is disabled so no need to go further
                         * The field meta has to be removed, as it is irrelevant
                         * 
                         */
                        unset($group["fields"][$fpos]);
                        continue;
                    }
                    
                    if (isset($field["field_rules"]) && count($field["field_rules"])) {                        
                        /* name attr has been @depricated from 3.04 onwards */
                        $fname   = isset($field["key"]) ? $field["key"] : $field["name"];
                        /* Make sure this fields is in the REQUEST object */
                        if (isset($_REQUEST[$fname . $key_suffix])) {
                            $ftype   = $field["type"];
                            $dformat = isset($field["date_format"]) ? $field["date_format"] : "d-m-Y";
                            $uvalue  = isset($_REQUEST[$fname . $key_suffix]) ? $_REQUEST[$fname . $key_suffix] : "";
                            $p_rules = $field["field_rules"];
                            
                            foreach ($p_rules as $prule) {
                                if (wcff()->negotiator->check_rules($prule, $uvalue, $ftype, $dformat)) {
                                    foreach ($prule["field_rules"] as $fkey => $action) {
                                        if ($action == "hide") {
                                            for ($p = 0; $p < count($this->product_field_groups); $p++) {
                                                foreach ($this->product_field_groups[$p]["fields"] as $pos => $fmeta) {
                                                    $field_key = isset($fmeta["key"]) ? $fmeta["key"] : $fmeta["name"];
                                                    if ($field_key == $fkey) {
                                                        unset($this->product_field_groups[$p]["fields"][$pos]);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }                       
                    }
                }
            }
        }
        
    }
       
    /**
     *
     * Loop through all the Product Fields as well as Admin Fields
     * and perform the validtaion for each ( If configured so )
     *
     */
    private function validate_fields($groups = array(), $_index = 0) {
        
        $key_suffix = $_index > 0 ? ("_". $_index) : "";
        
        foreach ($groups as $group) {
            if (count($group["fields"]) > 0) {
                foreach ($group["fields"] as $field) {
                    $res = true;
                    $this->file_size_ok = true;
                    /* name attr has been @depricated from 3.04 onwards */
                    $fkey   = isset($field["key"]) ? $field["key"] : $field["name"];
                    /* Adding index suffix for cloning */
                    $fkey = $fkey . $key_suffix;
                    /* Make sure required meta exist */
                    $field["required"] = isset($field["required"]) ? $field["required"] : "no";
                    /* Proceed only if the field is mandatory */
                    if ($field["required"] == "yes" || $field["type"] == "file") {
                        if ($field["type"] != "file") {
                            $res = false;     
                            if (isset($_REQUEST[$fkey])) {
                                $res = call_user_func(array(
                                    $this,
                                    "validate_" . $field["type"] . "_field"
                                ), $field, $_REQUEST[$fkey]);
                            }
                            if (!$res) {
                                $this->passed = false;
                                wc_add_notice((!empty($field["message"]) ? $field["message"] : (__("Validation failed for ", "wc-fields-factory"). $field["label"])), 'error');
                            }
                        } else {
                            $res = false;     
                            if (isset($_FILES[$fkey])) {
                                $res = call_user_func(array(
                                    $this,
                                    "validate_" . $field["type"] . "_field"
                                ), $field, $_FILES[$fkey]);
                            }                
                            if (!$res) {
                                $this->passed = false;
                                if (!$this->file_size_ok) {
                                    wc_add_notice("Upload size limit exceed, Allow size is ". $field["max_file_size"] . "kb.!", 'error');
                                } else {
                                    wc_add_notice((!empty($field["message"]) ? $field["message"] : __("File upload missing.!", "wc-fields-factory")), 'error');
                                }
                            }
                        }
                    }                    
                }
            }
        }        
    }
    
    /**
     *
     * @param integer $_pid
     * @param array $_field
     * @param string $_name
     * @param string $_value
     * @return mixed|boolean
     *
     * Single field validator, Used from Cart Editor
     *
     */
    public function validate_field($_pid, $_field, $_name, $_value) {
        $this->pid = $_pid;
        $this->passed = true;
        $this->file_size_ok = true;
        if (method_exists($this, "validate_". $_field["type"]."_field")) {
            return call_user_func(array($this, "validate_". $_field["type"]."_field"), $_field, $_value);
        }
        return true;
    }
    
    /**
     *
     * Check whether the submitted text field has value
     *
     * @param object $_field
     * @param string $_val
     * @return boolean
     *
     */
    private function validate_text_field($_field, $_val) {
        return (isset($_val) && !empty($_val)) ? true : false;
    }
    
    /**
     *
     * Check whether the submitted textarea field has value
     *
     * @param object $_field
     * @param string $_val
     * @return boolean
     *
     */
    private function validate_textarea_field($_field, $_val) {
        return (isset($_val) && !empty($_val)) ? true : false;
    }
    
    /**
     *
     * Check whether the submitted number field has value
     *
     * @param object $_field
     * @param string $_val
     * @return boolean
     *
     */
    private function validate_number_field($_field, $_val) {
        return (isset($_val) && is_numeric($_val)) ? true : false;
    }
    
    /**
     *
     * Check whether the submitted email field has value
     * it also check for the email address format
     *
     * @param object $_field
     * @param string $_val
     * @return boolean
     *
     */
    private function validate_email_field($_field, $_val) {
        return (isset($_val) && !filter_var($_val, FILTER_VALIDATE_EMAIL) === false) ? true : false;
    }
    
    /**
     *
     * Check whether the submitted date field has value
     *
     * @param object $_field
     * @param string $_val
     * @return boolean
     *
     */
    private function validate_datepicker_field($_field, $_val) {
        return (isset($_val) && !empty($_val)) ? true : false;
    }
    
    /**
     *
     * Check whether the submitted color field has value
     *
     * @param object $_field
     * @param string $_val
     * @return boolean
     *
     */
    private function validate_colorpicker_field($_field, $_val) {
        return (isset($_val) && !empty($_val)) ? true : false;
    }
    
    /**
     *
     * Check whether the submitted checkbox field has a valid arrays of options
     *
     * @param object $_field
     * @param string $_val
     * @return boolean
     *
     */
    private function validate_checkbox_field($_field, $_val) {
        return (isset($_val) && !empty($_val)) ? true : false;
    }
    
    /**
     *
     * Check whether the submitted radio field has value
     *
     * @param object $_field
     * @param string $_val
     * @return boolean
     *
     */
    private function validate_radio_field($_field, $_val) {
        return (isset($_val) && !empty($_val)) ? true : false;
    }
    
    /**
     *
     * Check whether the submitted select field has value
     *
     * @param object $_field
     * @param string $_val
     * @return boolean
     *
     */
    private function validate_select_field($_field, $_val) {
        if (isset($_val) && !empty($_val)) {
            if ($_val != "wccpf_none") {
                return true;
            }
        }
        return false;
    }
    
    /**
     *
     * Check whether the submitted file field has value
     * it also check for two more aspect whether the submitted file has correct extension
     * as well as the size not exceed the specified max upload size.
     *
     * @param object $_field
     * @param string $_val
     * @return boolean
     *
     */
    private function validate_file_field($_field, $_val) {
        $res = true;
        $this->file_size_ok = true;
        $is_multi_file = isset($_field["multi_file"]) ? $_field["multi_file"] : "no";
        
        if ($is_multi_file == "yes") {
            $files = $_val;
            foreach ($files['name'] as $key => $value) {
                if ($files['name'][$key]) {
                    $file = array(
                        'name' => $files['name'][$key],
                        'type' => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'error' => $files['error'][$key],
                        'size' => $files['size'][$key]
                    );
                    $res = $this->validate_file_upload($file, $_field['filetypes'], $_field["required"]);
                    if ($res && isset($files["size"])) {
                        $this->file_size_ok = $this->validate_file_upload_max_size($_field, $files["size"][0]);
                    }
                    if (! $res || ! $this->file_size_ok) {
                        break;
                    }
                }
            }
        } else {
            $res = $this->validate_file_upload($_val, $_field['filetypes'], $_field["required"]);
            if ($res && isset($_val["size"])) {
                $this->file_size_ok = $this->validate_file_upload_max_size($_field, $_val["size"]);
            }
        }
        
        return ($res && $this->file_size_ok);
    }
    
    function validate_file_upload($_uploadedfile, $_file_types, $_mandatory) {
        $file_ok = false;
        $no_file = false;
        
        if (isset($_uploadedfile['error'])) {
            switch ($_uploadedfile['error']) {
                case UPLOAD_ERR_OK:
                    $file_ok = true;
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $no_file = true;
                    break;
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $file_ok = false;
                default:
                    $file_ok = false;
            }
        }
        
        if ($file_ok && ! $no_file) {
            
            $file_ok = false;
            $filename = $_uploadedfile['name'];
            $mime_type = $this->get_mime_type($_uploadedfile);
            
            if ($_file_types && $_file_types != "") {
                if ((strpos($_file_types, "image/") !== false) && (strpos($mime_type, "image/") !== false)) {
                    $file_ok = true;
                } else if ((strpos($_file_types, "audio/") !== false) && (strpos($mime_type, "audio/") !== false)) {
                    $file_ok = true;
                } else if ((strpos($_file_types, "video/") !== false) && (strpos($mime_type, "video/") !== false)) {
                    $file_ok = true;
                } else {
                    $allowed_types = explode(',', $_file_types);
                    if (is_array($allowed_types)) {
                        $ext = pathinfo($filename, PATHINFO_EXTENSION);
                        if (in_array("." . $ext, $allowed_types) || $ext == "php") {
                            $file_ok = true;
                        }
                    }
                }
            } else {
                $file_ok = true;
            }
        }
        
        if (! $no_file) {
            return $file_ok;
        }
        
        if ($_mandatory == "no") {
            return true;
        }
        
        return $file_ok;
    }
    
    function get_mime_type($_uploadedfile) {
        $mime_type = "";
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $_uploadedfile["tmp_name"]);
        } else {
            $mimeTypes = $this->mime_types();
            $filename = $_uploadedfile["name"];
            $extension = end(explode('.', $filename));
            $mime_type = $mimeTypes[$extension];
        }
        return $mime_type;
    }
    
    private function validate_file_upload_max_size($_field, $_file_size) {
        $size_ok = true;
        if (isset($_field["max_file_size"]) && $_field["max_file_size"] != "") {
            if (round($_field["max_file_size"]) < round($_file_size / 1024)) {
                $size_ok = false;
            }
        }
        return $size_ok;
    }
    
    /**
     * MIME list for file upload validation
     * @return string[]
     */
    private function mime_types() {
        return array(
            "323" => "text/h323",
            "acx" => "application/internet-property-stream",
            "ai" => "application/postscript",
            "aif" => "audio/x-aiff",
            "aifc" => "audio/x-aiff",
            "aiff" => "audio/x-aiff",
            "asf" => "video/x-ms-asf",
            "asr" => "video/x-ms-asf",
            "asx" => "video/x-ms-asf",
            "au" => "audio/basic",
            "avi" => "video/x-msvideo",
            "axs" => "application/olescript",
            "bas" => "text/plain",
            "bcpio" => "application/x-bcpio",
            "bin" => "application/octet-stream",
            "bmp" => "image/bmp",
            "c" => "text/plain",
            "cat" => "application/vnd.ms-pkiseccat",
            "cdf" => "application/x-cdf",
            "cer" => "application/x-x509-ca-cert",
            "class" => "application/octet-stream",
            "clp" => "application/x-msclip",
            "cmx" => "image/x-cmx",
            "cod" => "image/cis-cod",
            "cpio" => "application/x-cpio",
            "crd" => "application/x-mscardfile",
            "crl" => "application/pkix-crl",
            "crt" => "application/x-x509-ca-cert",
            "csh" => "application/x-csh",
            "css" => "text/css",
            "dcr" => "application/x-director",
            "der" => "application/x-x509-ca-cert",
            "dir" => "application/x-director",
            "dll" => "application/x-msdownload",
            "dms" => "application/octet-stream",
            "doc" => "application/msword",
            "dot" => "application/msword",
            "dvi" => "application/x-dvi",
            "dxr" => "application/x-director",
            "eps" => "application/postscript",
            "etx" => "text/x-setext",
            "evy" => "application/envoy",
            "exe" => "application/octet-stream",
            "fif" => "application/fractals",
            "flr" => "x-world/x-vrml",
            "gif" => "image/gif",
            "gtar" => "application/x-gtar",
            "gz" => "application/x-gzip",
            "h" => "text/plain",
            "hdf" => "application/x-hdf",
            "hlp" => "application/winhlp",
            "hqx" => "application/mac-binhex40",
            "hta" => "application/hta",
            "htc" => "text/x-component",
            "htm" => "text/html",
            "html" => "text/html",
            "htt" => "text/webviewhtml",
            "ico" => "image/x-icon",
            "ief" => "image/ief",
            "iii" => "application/x-iphone",
            "ins" => "application/x-internet-signup",
            "isp" => "application/x-internet-signup",
            "jfif" => "image/pipeg",
            "jpe" => "image/jpeg",
            "jpeg" => "image/jpeg",
            "jpg" => "image/jpeg",
            "png" => "image/png",
            "js" => "application/x-javascript",
            "latex" => "application/x-latex",
            "lha" => "application/octet-stream",
            "lsf" => "video/x-la-asf",
            "lsx" => "video/x-la-asf",
            "lzh" => "application/octet-stream",
            "m13" => "application/x-msmediaview",
            "m14" => "application/x-msmediaview",
            "m3u" => "audio/x-mpegurl",
            "man" => "application/x-troff-man",
            "mdb" => "application/x-msaccess",
            "me" => "application/x-troff-me",
            "mht" => "message/rfc822",
            "mhtml" => "message/rfc822",
            "mid" => "audio/mid",
            "mny" => "application/x-msmoney",
            "mov" => "video/quicktime",
            "movie" => "video/x-sgi-movie",
            "mp2" => "video/mpeg",
            "mp4" => "video/mp4",
            "avi" => "video/avi",
            "mp3" => "audio/mpeg",
            "mpa" => "video/mpeg",
            "mpe" => "video/mpeg",
            "mpeg" => "video/mpeg",
            "mpg" => "video/mpeg",
            "mpp" => "application/vnd.ms-project",
            "mpv2" => "video/mpeg",
            "ms" => "application/x-troff-ms",
            "mvb" => "application/x-msmediaview",
            "nws" => "message/rfc822",
            "oda" => "application/oda",
            "p10" => "application/pkcs10",
            "p12" => "application/x-pkcs12",
            "p7b" => "application/x-pkcs7-certificates",
            "p7c" => "application/x-pkcs7-mime",
            "p7m" => "application/x-pkcs7-mime",
            "p7r" => "application/x-pkcs7-certreqresp",
            "p7s" => "application/x-pkcs7-signature",
            "pbm" => "image/x-portable-bitmap",
            "pdf" => "application/pdf",
            "pfx" => "application/x-pkcs12",
            "pgm" => "image/x-portable-graymap",
            "pko" => "application/ynd.ms-pkipko",
            "pma" => "application/x-perfmon",
            "pmc" => "application/x-perfmon",
            "pml" => "application/x-perfmon",
            "pmr" => "application/x-perfmon",
            "pmw" => "application/x-perfmon",
            "pnm" => "image/x-portable-anymap",
            "pot" => "application/vnd.ms-powerpoint",
            "ppm" => "image/x-portable-pixmap",
            "pps" => "application/vnd.ms-powerpoint",
            "ppt" => "application/vnd.ms-powerpoint",
            "prf" => "application/pics-rules",
            "ps" => "application/postscript",
            "pub" => "application/x-mspublisher",
            "qt" => "video/quicktime",
            "ra" => "audio/x-pn-realaudio",
            "ram" => "audio/x-pn-realaudio",
            "ras" => "image/x-cmu-raster",
            "rgb" => "image/x-rgb",
            "rmi" => "audio/mid",
            "roff" => "application/x-troff",
            "rtf" => "application/rtf",
            "rtx" => "text/richtext",
            "scd" => "application/x-msschedule",
            "sct" => "text/scriptlet",
            "setpay" => "application/set-payment-initiation",
            "setreg" => "application/set-registration-initiation",
            "sh" => "application/x-sh",
            "shar" => "application/x-shar",
            "sit" => "application/x-stuffit",
            "snd" => "audio/basic",
            "spc" => "application/x-pkcs7-certificates",
            "spl" => "application/futuresplash",
            "src" => "application/x-wais-source",
            "sst" => "application/vnd.ms-pkicertstore",
            "stl" => "application/vnd.ms-pkistl",
            "stm" => "text/html",
            "svg" => "image/svg+xml",
            "sv4cpio" => "application/x-sv4cpio",
            "sv4crc" => "application/x-sv4crc",
            "t" => "application/x-troff",
            "tar" => "application/x-tar",
            "tcl" => "application/x-tcl",
            "tex" => "application/x-tex",
            "texi" => "application/x-texinfo",
            "texinfo" => "application/x-texinfo",
            "tgz" => "application/x-compressed",
            "tif" => "image/tiff",
            "tiff" => "image/tiff",
            "tr" => "application/x-troff",
            "trm" => "application/x-msterminal",
            "tsv" => "text/tab-separated-values",
            "txt" => "text/plain",
            "uls" => "text/iuls",
            "ustar" => "application/x-ustar",
            "vcf" => "text/x-vcard",
            "vrml" => "x-world/x-vrml",
            "wav" => "audio/x-wav",
            "wcm" => "application/vnd.ms-works",
            "wdb" => "application/vnd.ms-works",
            "wks" => "application/vnd.ms-works",
            "wmf" => "application/x-msmetafile",
            "wps" => "application/vnd.ms-works",
            "wri" => "application/x-mswrite",
            "wrl" => "x-world/x-vrml",
            "wrz" => "x-world/x-vrml",
            "xaf" => "x-world/x-vrml",
            "xbm" => "image/x-xbitmap",
            "xla" => "application/vnd.ms-excel",
            "xlc" => "application/vnd.ms-excel",
            "xlm" => "application/vnd.ms-excel",
            "xls" => "application/vnd.ms-excel",
            "xlsx" => "vnd.ms-excel",
            "xlt" => "application/vnd.ms-excel",
            "xlw" => "application/vnd.ms-excel",
            "xof" => "x-world/x-vrml",
            "xpm" => "image/x-xpixmap",
            "xwd" => "image/x-xwindowdump",
            "z" => "application/x-compress",
            "zip" => "application/zip"
        );
    }
    
}

?>