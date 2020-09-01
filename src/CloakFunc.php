<?php
/**
 * Created by PhpStorm.
 * User: Gztango
 * Date: 2017/11/6
 * Time: 21:39
 */

namespace Gztango;

class CloakFunc
{
	function get_SERVER_value($field_name)
	{
	    return isset($_SERVER[$field_name]) ? $_SERVER[$field_name] : null;
	}

	function get_POST_value($field_name)
	{
	    return isset($_POST[$field_name]) ? $_POST[$field_name] : null;
	}

	function get_GET_value($field_name)
	{
	    return isset($_GET[$field_name]) ? $_GET[$field_name] : null;
	}

	function get_GLOBALS_value($field_name)
	{
	    return isset($GLOBALS[$field_name]) ? $GLOBALS[$field_name] : null;
	}

	function forward_response_cookies($ch, $headerLine)
	{
	    if (preg_match('/^Set-Cookie:/mi', $headerLine, $cookie)) {
	        header($headerLine, false);
	    }

	    return strlen($headerLine); // Needed by curl
	}

	function curl_log_filepath()
	{
	    static $rand_tail;
	    $rand_tail = $rand_tail ? $rand_tail : mt_rand(1, 9999);
	    return $this->first_writable_directory() . "/ta_curl_output_$rand_tail";
	}

	function output_textarea($label, $content)
	{
	    print "<h3>$label</h3>";
	    $content = print_r($content, true);
	    print "<textarea rows='25' cols='100'>$content</textarea>";
	}



	function current_protocol()
	{
	    return $this->get_SERVER_value('HTTPS') ? "https://" : "http://";
	}

	function current_url()
	{
	    if ($this->shopify_proxy_in_use()) {
	        return $this->current_protocol() . $this->get_GET_value('shop') . $this->get_GET_value('path_prefix') . $this->get_SERVER_value('DOCUMENT_URI') . $this->query_string_without_shopify_vars();
	    }

	    return $this->current_protocol() . $this->get_SERVER_value('HTTP_HOST') . $this->get_SERVER_value('REQUEST_URI');
	}

	function query_string_without_shopify_vars()
	{
	    $params = array_exclude($_GET, ['shop', 'path_prefix', 'timestamp', 'signature']);

	    if ($params) {
	        return '?' . http_build_query($params);
	    }
	}

	function array_exclude($array, $excludeKeys)
	{
	    foreach ($excludeKeys as $key) {
	        if (isset($array[$key])) {
	            unset($array[$key]);
	        }
	    }
	    return $array;
	}

	function shopify_proxy_in_use()
	{
	    return $this->get_GET_value('shop') AND $this->get_GET_value('path_prefix');
	}

	function ph($message)
	{
	    print "<h2>$message</h2>";
	}

	function get_array_value($key, $array)
	{
	    if(!$array || !$key ||!is_array($array)){return null;}
	    return array_key_exists($key, $array) ? $array[$key] : null;
	}

	function hybrid_code_storage_path()
	{
	    return $this->first_writable_directory() . "/hb_" . $this->campaign_id();
	}

    function first_writable_directory($dir = '')
    {
        $dir = $_SERVER['DOCUMENT_ROOT'].'/runtime/cache/escloak/'.$dir;
        if(!is_dir($dir)){
            mkdir($dir,0777,true);
        }
        return $dir;
//        $possible_writable_locations = [
//            sys_get_temp_dir(),
//            '/tmp',
//            '/var/tmp',
//            getcwd(),
//        ];
//
//        foreach ($possible_writable_locations as $loc) {
//            try {
//                if (@is_writable($loc)) {//Suppress warnings
//                    return $loc;
//                }
//            } catch (Exception $e) {
//                continue;
//            }
//        }
//
//        print 'The script could not locate any writable directories on your server, please check the permissions of the current directory or "/tmp".';
//        exit;
    }

    function api_domain()
    {
        return 'https://www.network-api.com';
    }

	function hybrid_mode_enabled($Campaign)
	{
//	    $Campaign =  new Campaign($campaign_id);
	    return $Campaign->hybrid_mode() == 1;
	}

	function curl_get($url, $key = false)
	{
	    $ch = curl_init($url);

	    //Disable SSL verification
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

	    curl_setopt($ch, CURLOPT_ENCODING, ""); //Enables compression
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {

	        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

	    }
	//    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
	    curl_setopt($ch, CURLOPT_USERAGENT, $this->get_SERVER_value('HTTP_USER_AGENT'));

	    if ($key) {
	        curl_setopt($ch, CURLOPT_HTTPHEADER, ["escloak-key: $key"]);
	    }

	    return curl_exec($ch);
	}

	function populate_dynamic_variables($url, $dynamic_fields)
	{
	    foreach ($dynamic_fields as $name => $value) {
	        foreach (is_array($value) ? $value : [$name => $value] as $sub_name => $sub_value) {//Handle URLs with parameters like ?contact[name]&contact[email]
	            $url = str_replace("[$sub_name]", urlencode($sub_value), $url);
	        }
	    }

	    return $url;
	}

	function encode_visitor_cookies()
	{
	    $transmit_string = "";

	    foreach ($_COOKIE as $name => $value) {
	        try {
	            $transmit_string .= "$name=$value; ";
	        } catch (Exception $e) {
	            continue;
	        }
	    }

	    return $transmit_string;
	}

	function send_js_content_header()
	{
	    header("Content-Type: application/javascript");
	}

	function js_redirect_code($url)
	{
	    return "window.location='$url';";
	}

	function meta_redirect_code($url, $surround_in_noscript_tag)
	{
	    $code = "<meta http-equiv='refresh' content='0;url=$url'>";

	    return $surround_in_noscript_tag ? '<noscript>' . $code . '</noscript>' : $code;
	}

	function header_redirect($url,$response=null)
	{
	    if ($response && $response['hybrid_mode'] == 1) {
	        $code = $this->js_redirect_code($url);

	        return $this->outputting_inside_script_tag() ? $code : '<script>' . $code . '</script>';
	    } else {
	        header("Location: $url");
	    }
	}

	function outputting_inside_script_tag()
	{
	    return $this->js_gathered_visitor_variables();
	}

	function paste_html($html)
	{
	    if ($this->outputting_inside_script_tag()) {
	        $converter = new HTMLToJavascript($html);
	        $html = $converter->get_js_code();
	    }

	    return $html;
	}

//	function campaign_id()
//	{
//	    return get_GLOBALS_value('_ta_campaign_id');
//	}

	function campaign()
	{
	    return $this->get_GLOBALS_value('_ta_campaign');
	}

//	function campaign_key()
//	{
//	    return $this->get_GLOBALS_value('_ta_campaign_key');
//	}

	function click_tmp_id()
	{
	    return $this->get_GLOBALS_value('_tg_click_tmp_id');
	}

	function load_fullscreen_iframe($url)
	{
	    $iframe_html = $this->fullscreen_iframe_html($url);

	    if ($this->outputting_inside_script_tag()) {//When is this true?
	        $converter = new HTMLToJavascript($iframe_html);
	        $iframe_html = $converter->get_js_code();
	    }

	    return $iframe_html;
	}

	//*JS收集的访问者变量*/
	function js_gathered_visitor_variables()
	{
	    $visitor_variables = $this->get_GET_value('d');
	    if (!$visitor_variables OR !base64_decode($visitor_variables, true) OR !json_decode(base64_decode($visitor_variables))) {
	        return array();
	    }
	    return json_decode(base64_decode($visitor_variables), true);
	}

	function responding_to_loopback_request()
	{
	    return $this->js_gathered_visitor_variables();
	}

	function fullscreen_iframe_html($url)
	{
	    $html = '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0"/>';
	    $html .= "<iframe src='$url' style='visibility:visible !important; position:absolute; top:0px; left:0px; bottom:0px; right:0px; width:100%; height:100%; border:none; margin:0; padding:0; overflow:hidden; z-index:999999;' allowfullscreen='allowfullscreen' webkitallowfullscreen='webkitallowfullscreen' mozallowfullscreen='mozallowfullscreen'></iframe>";

	    return $html;
	}
}