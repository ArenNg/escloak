<?php
namespace ArenNg;
use think\Cache;
class Campaign
{
    private $properties;
    private $debug;
    private $campaign_id;

    public function __construct($campaign_id,$sign_key,$debug = false)
    {
        $this->debug = $debug;
        $this->campaign_id = $campaign_id;
        $this->sign_key = $sign_key;
        $this->load_campaign();
    }

    private function load_campaign()
    {
        $campaignCacheData = Cache::get('escloak_campaign_'.$this->campaign_id);
        if($campaignCacheData){
            $campaignData = unserialize($campaignCacheData);
        }else{
            $campaignApiData = $this->fetch_campaign_data_from_server();
            $campaignData = json_decode($campaignApiData,true);
            $campaignCacheData = serialize($campaignData);
            Cache::set('escloak_campaign_'.$this->campaign_id,$campaignCacheData);
        }
//        var_dump($campaignData);exit;
        $this->properties = $campaignData;
    }

    private function fetch_campaign_data_from_server()
    {
        $cloakFunc = new CloakFunc();
        $domain = $cloakFunc->api_domain();
        $url = "{$domain}/cloak/getCampaign?id=" . $this->campaign_id;
        $campaign_data = $cloakFunc->curl_get($url, $this->sign_key);
        if ($this->debug AND (!$campaign_data OR !json_decode($campaign_data))) {
            $cloakFunc->output_textarea("Warning! Missing/invalid campaign info fetched from $url:", $campaign_data);
            exit;
        }
        return $campaign_data;
    }

    public function cli_key()
    {
        return $this->properties['cli_key'];
    }

    public function label()
    {
        return $this->properties['label'];
    }

    public function hybrid_code()
    {
        return $this->properties['hybrid_code_v4'];
    }

    public function cloaking_action()
    {
        return $this->properties['cloaking_action'];
    }

    public function safe_redirect_url($populate_dynamic_variables = false)
    {
        $safe_redirect_url = $this->properties['safe_redirect_url'];

        return $populate_dynamic_variables ? populate_dynamic_variables($safe_redirect_url, $_GET) : $safe_redirect_url;
    }

    public function hybrid_mode()
    {
        return $this->properties['hybrid_mode'] == 1;
    }

    public function properties()
    {
        return $this->properties;
    }
}