<?php
namespace Gztango;

use Doctrine\Common\Cache\FilesystemCache;

Class ESLoader
{
    private $visitor;
    private $visitor_properties;
    private $request;
    private $response;
    private $cloakFunc;
    private $campaign_id;
    private $sign_key;
    private $debug;
    private $click_tmp_id;
//    private $campaignInfo;
    private $Campaign;

    public function __construct($sing_key,$campaign_id,$debug)
    {
        $this->campaign_id = $campaign_id;
        $this->sign_key = $sing_key;
        $this->debug = $debug;
        $this->cloakFunc = new CloakFunc();
        $this->send_headers(); //设置无缓存浏览，如果是JS访问，设置JS访问头
        $this->send_404_if_prefetch_request(); //如果是查看源码，返回404
        $this->check_local_server_environment(); //检查本地服务器环境

        // $this->find_campaign_id($campaign_id); //获取广告活动ID

//        $this->load_campaign(); //加载广告活动信息
        $this->Campaign = new Campaign($campaign_id,$sing_key,$debug);
//        $this->campaignInfo = $Campaign->properties;
        $this->set_visitor();
        $this->build_api_request();
        $this->send_api_request();
        $this->parse_response();
        $this->run_debug_functions_if_enabled();
        if ($this->cloakFunc->get_array_value('cloak_reason', $this->response) === "" AND $this->should_send_hybrid_page()) {//Visitor passed non-JS tests
            $this->format_response_for_hybrid_mode();
        }
    }

    private function send_headers()
    {
        
        $this->send_no_cache_header();

        if ($this->cloakFunc->outputting_inside_script_tag()) {
            $this->cloakFunc->send_js_content_header();
        }
    }

    private function send_no_cache_header()
    {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
    }

    private function send_404_if_prefetch_request()
    {
        if ($this->cloakFunc->get_SERVER_value('HTTP_X_PURPOSE') == 'preview') {
            send_404();
            exit;
        }
    }

    private function check_local_server_environment()
    {
        if (http_response_code() == 404) {
            //Catch-all script triggered by 404, do not proceed
            if ($this->debug_mode_enabled()) {
                print "Server returned a 404, not proceeding with this request";
            }
            exit;
        }

        if ($this->campaign_key_missing_or_invalid()) {
            print "Your campaign key is missing or invalid, please double check it.";
            exit;
        }

        if ($this->debug_mode_enabled()) {
            print "<center><h2>Running in Debug Mode...</h2></center>";
            error_reporting(E_ALL);
            ini_set('display_startup_errors', 1);
            ini_set('display_errors', 1);

            if ($this->cf_ipv6_enabled()) {
                print "The script has detected you are using CloudFlare with IPv6 enabled which is generally not recommended. Please disable it in your CloudFlare admin panel by setting 'Network'->'IPv6 Compatibility' to 'Off' before proceeding. This error message should disappear automatically once IPV6 has been disabled.";
                exit;
            }
        }
    }

    function debug_mode_enabled()
    {
        return $this->debug;
    }

    //检查活动密钥是否丢失或无效
    private function campaign_key_missing_or_invalid()
    {
        return !preg_match('/^[a-zA-Z0-9]{32}$/i', $this->sign_key);
    }

    private function cf_ipv6_enabled()
    {
        return strpos($this->cloakFunc->get_SERVER_value('HTTP_CF_CONNECTING_IP'), ':') !== false;
    }

    private function find_campaign_id($hardcoded_campaign_id)
    {
        // New CampaignIDFinder($hardcoded_campaign_id);
        return $hardcoded_campaign_id;
    }






    private function set_visitor()
    {
        $this->visitor = New Visitor($this->campaign_id,$this->sign_key);
    }

    private function build_api_request()
    {
        $this->request['visitor'] = $this->visitor->get_fields();
        $this->request['browser_headers'] = $this->visitor->browser_headers();
        $this->request['client_server'] = $_SERVER;
    }

    private function send_api_request()
    {
         $domain = $this->cloakFunc->api_domain();
        $post_url = $domain."/cloak/byPhp?id=" . $this->campaign_id;
        $curl = New Curler($post_url, $this->request,$this->sign_key);
        $this->response = $curl->get_response();
    }

    private function parse_response()
    {

        $this->response = json_decode($this->response, true);
        $this->visitor_properties = isset($this->response['visitor']) ? $this->response['visitor'] : null;
        $this->click_tmp_id = $this->response['click_tmp_id'];
//        $GLOBALS['_tg_click_tmp_id'] = $this->response['click_tmp_id'];
    }

    private function run_debug_functions_if_enabled()
    {
        New Debugger($this->visitor, $this->request, $this->response,$this->debug,$this->sign_key,$this->campaign_id);
    }

    public function should_send_hybrid_page()
    {
        if (stripos($this->cloakFunc->get_SERVER_value('HTTP_USER_AGENT'), 'facebookexternalhit') !== false) {//FB bot doesn't follow meta/javascript
            return false;
        }
        return $this->Campaign->hybrid_mode() AND !$this->cloakFunc->js_gathered_visitor_variables() AND !$this->debug_mode_enabled();
    }

    private function format_response_for_hybrid_mode()
    {
        $this->response = array(
            'cloak_reason' => '',
            'action'       => 'load_hybrid_page',
            'url_num'      => 1,
            'url'          => $this->Campaign->cloaking_action() == 'none' ? null : $this->Campaign->safe_redirect_url(true),
            'visitor'      => array(), //Not available during hybrid page construction
        );
    }

    public function load_hybrid_page()
    {
        $dir = $this->cloakFunc->first_writable_directory();
//        $cache = new FilesystemCache($dir);
        $cloaking_id = $this->click_tmp_id;
        $cloaking_action = $this->Campaign->cloaking_action();
        $safe_redirect_url = $this->Campaign->safe_redirect_url(true);
        $pass_vars = [
            'c'  => mt_rand(10000000, 999999999), //Cache buster
            'k'  => $this->Campaign->cli_key(),
            'r'  => $this->cloakFunc->get_SERVER_value('HTTP_REFERER'),
            'su' => $this->cloakFunc->current_url(),
            'cr' => 'no_javascript'
        ];
        $pixel_payload = urlencode(base64_encode(json_encode($pass_vars)));
        $safe_redirect_url = $this->add_querystring_var($safe_redirect_url);
        $js_redirect = $this->cloakFunc->js_redirect_code($safe_redirect_url);
        require_once $dir.'hb_'.$this->campaign_id;
        switch ($cloaking_action) {
            case 'header_redirect':
                print "<script>" . $js_redirect . "</script>";
                exit;
            case 'iframe':
                print $this->cloakFunc->load_fullscreen_iframe($safe_redirect_url);
                exit;
            case 'paste_html':
                $url = $this->add_querystring_var($safe_redirect_url);
                $html = $this->cloakFunc->curl_get($url);
                print $this->replaceHtml($url,$html);
//                $response['output_html'] = $html;
//                print $this->cloakFunc->curl_get($safe_redirect_url);
                exit;
            case 'none':
                break;
        }
    }

    public function get_visitor()
    {
        return $this->visitor_properties;
    }

    public function suppress_response()
    {
        if ($this->Campaign->hybrid_mode() AND $this->cloakFunc->js_gathered_visitor_variables() AND $this->should_cloak()) {
            return true;
        }
    }

    public function should_cloak()
    {
        return $this->cloakFunc->get_array_value('cloak_reason', $this->get_response());
    }

    public function get_response()
    {
        $response = $this->response;
        unset($response['visitor']);
        return $this->insert_additional_response_fields($response);
    }

    private function insert_additional_response_fields($response)
    {
        $action = $this->cloakFunc->get_array_value('action', $response);
        $url = $this->cloakFunc->get_array_value('url', $response);
        return $this->insert_output_html_field($action, $url, $response);
    }

    private function insert_output_html_field($action, $url, $response)
    {
        if ($action == 'paste_html' AND $url) {
            $url = $this->add_querystring_var($url);
            $html = $this->cloakFunc->curl_get($url);
            $html = $this->replaceHtml($url,$html);
            $response['output_html'] = $html;
        }
        return $response;
    }

    function add_querystring_var($url) {
        $key = 'from_es';
        $value = 'v1.0';
        $url=preg_replace('/(.*)(?|&)'.$key.'=[^&]+?(&)(.*)/i','$1$2$4',$url.'&');
        $url=substr($url,0,-1);
        if(strpos($url,'?') === false){
            return ($url.'?'.$key.'='.$value);
        } else {
            return ($url.'&'.$key.'='.$value);
        }
    }

    function replaceHtml($surl,$content){
        $pattern = '/\'/';//正则将所有单引号替换为双引号
        $content = preg_replace($pattern, '"', $content);

        $pattern = '/"\/\//';
//正则将所有"//单引号替换为"http:// 解决部分源码不带http路径信息，采集后资源信息不完整但又不属于本域名下的内链，防止被下面替换问题
        $content = preg_replace($pattern, '"http://', $content);
        $content = $this->formaturl($content,$surl);//路径补全函数执行


//$pattern = $char . '//';
//$bei_th = $char . '/';

//$content = str_replace($pattern, $bei_th, $content);//非函数全部替换方式，处理双斜杠的异常问题
        $urlArr = parse_url($surl);
        $domain = substr($urlArr['host'],strpos($urlArr['host'],".")+1);
        $pattern = '/Copyright.*<.*>/';
        $content = preg_replace($pattern, 'Copyright ' . $domain . ' All rights reserved', $content);//版权信息替换插入


        $pattern = '/copyright.*<.*>/';
        $content = preg_replace($pattern, 'Copyright ' . $domain . ' All rights reserved', $content);//小写的版权信息匹配替换插入

//        $content = str_replace('http://www.xxx.com/', $domain_caiji, $content);//路径补全为xxx.com，用于下面识别替换


//        $preg = "/<script[\s\S]*?<\/script>/i";//过滤JS
//        $content = preg_replace($preg,"",$content,-1);    //第四个参数中的3表示替换3次，默认是-1，替换全部
        return $content;

    }

    function formaturl($l1, $l2) {
        if (preg_match_all ( "/(<script[^>]+src=\"([^\"]+)\"[^>]*>)|(<link[^>]+href=\"([^\"]+)\"[^>]*>)|(<img[^>]+src=\"([^\"]+)\"[^>]*>)|(<a[^>]+href=\"([^\"]+)\"[^>]*>)|(<img[^>]+src='([^']+)'[^>]*>)|(<a[^>]+href='([^']+)'[^>]*>)/i", $l1, $regs )) {
            foreach ( $regs [0] as $num => $url ) {
                $l1 = str_replace ( $url, $this->lIIIIl ( $url, $l2 ), $l1 );
            }
        }
        return $l1;
    }

    function lIIIIl($l1, $l2) {
        if (preg_match ( "/(.*)(href|src)\=(.+?)( |\/\>|\>).*/i", $l1, $regs )) {
            $I2 = $regs [3];
        }
        if (strlen ( $I2 ) > 0) {
            $I1 = str_replace ( chr ( 34 ), "", $I2 );
            $I1 = str_replace ( chr ( 39 ), "", $I1 );
        } else {
            return $l1;
        }
        $url_parsed = parse_url ( $l2 );
        $scheme = $url_parsed ["scheme"];
        if ($scheme != "") {
            $scheme = $scheme . "://";
        }
        $host = $url_parsed ["host"];
        $l3 = $scheme . $host;
        if (strlen ( $l3 ) == 0) {
            return $l1;
        }
        if(isset($url_parsed ["path"])){
            $path = dirname ( $url_parsed ["path"] );
            if ($path [0] == "\\") {
                $path = "";
            }
        }else{
            $path = "";
        }



        $pos = strpos ( $I1, "#" );
        if ($pos > 0)
            $I1 = substr ( $I1, 0, $pos );

        //判断类型
        if (preg_match ( "/^(http|https|ftp):(\/\/|\\\\)(([\w\/\\\+\-~`@:%])+\.)+([\w\/\\\.\=\?\+\-~`@\':!%#]|(&amp;)|&)+/i", $I1 )) {
            return $l1;
        } //http开头的url类型要跳过
        elseif ($I1 [0] == "/") {
            $I1 = $l3 . $I1;
        } //绝对路径
        elseif (substr ( $I1, 0, 3 ) == "../") { //相对路径
            while ( substr ( $I1, 0, 3 ) == "../" ) {
                $I1 = substr ( $I1, strlen ( $I1 ) - (strlen ( $I1 ) - 3), strlen ( $I1 ) - 3 );
                if (strlen ( $path ) > 0) {
                    $path = dirname ( $path );
                }
            }
            $I1 = $l3 . $path . "/" . $I1;
        } elseif (substr ( $I1, 0, 2 ) == "./") {
            $I1 = $l3 . $path . substr ( $I1, strlen ( $I1 ) - (strlen ( $I1 ) - 1), strlen ( $I1 ) - 1 );
        } elseif (strtolower ( substr ( $I1, 0, 7 ) ) == "mailto:" || strtolower ( substr ( $I1, 0, 11 ) ) == "javascript:") {
            return $l1;
        } else {
            $I1 = $l3 . $path . "/" . $I1;
        }
        return str_replace ( $I2, "\"$I1\"", $l1 );
    }
}







