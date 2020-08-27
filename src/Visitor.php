<?php
namespace ArenNg;

Class Visitor
{
    private $cloakFunc;
    private $campaign;

    public function __construct($campaign_id,$sign_key)
    {
        $this->cloakFunc = new CloakFunc();
        $this->campaign = new Campaign($campaign_id,$sign_key);
    }

    public function agent()
    {
        return $this->cloakFunc->get_SERVER_value('HTTP_USER_AGENT');
    }

    public function browser_headers()
    {
        $headers = array();

        foreach ($_SERVER as $name => $value) {
            if (preg_match('/^HTTP_/', $name)) {
                // convert HTTP_HEADER_NAME to header-name
                $name = strtr(substr($name, 5), '_', '-');
                $name = strtolower($name);
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    public function get_ip(){

        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {     //使用cloudflare 转发的IP地址
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } else {
            if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
                $ip = getenv('HTTP_CLIENT_IP');
            } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
                $ip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
                $ip = getenv('REMOTE_ADDR');
            } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }

//        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {     //使用cloudflare 转发的IP地址
//            $ip = get_SERVER_value('HTTP_CF_CONNECTING_IP');
//        } else {
//            $ip = get_SERVER_value('REMOTE_ADDR');
//        }
        return $ip;
    }

    public function get_fields()
    {
        $base_fields = array(
            'remote_addr' => $this->get_ip(),
            'v'           => 4, //Allows for unique remote behaviour only enabled in this version of the script
        );

        if ($this->custom_cloak_reason()) {
            $base_fields['custom_cloak_reason'] = $this->custom_cloak_reason();
        }

        if ($this->campaign->hybrid_mode() AND !$this->cloakFunc->js_gathered_visitor_variables()) {
            $base_fields['xi'] = 1; //Initial exploratory impression is being sent
        }

        return array_merge($base_fields, $this->cloakFunc->js_gathered_visitor_variables() ? $this->cloakFunc->js_gathered_visitor_variables() : $this->non_hybrid_fields());
    }

    public function custom_cloak_reason()
    {
        $custom_cloak_reason = $this->cloakFunc->get_array_value('cr', $_REQUEST);

        if ($custom_cloak_reason) {
            return base64_decode($custom_cloak_reason, true) ? base64_decode($custom_cloak_reason) : $custom_cloak_reason;
        }
    }

    private function non_hybrid_fields()
    {//Only used if JS-gathered variables are not present (i.e. not hybrid mode)
        return [
            'lp_url'   => $this->cloakFunc->current_url(),
            'referrer' => $this->cloakFunc->get_SERVER_value('HTTP_REFERER'),
        ];
    }
}