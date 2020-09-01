<?php
namespace Gztango;

use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\PhpFileCache;


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

    private function checkDir($dir){
        if(!dir($dir)){
            mkdir($dir,0777);
        }
    }

    private function load_campaign()
    {
        $cloakFunc = new CloakFunc();
        $dir = $cloakFunc->first_writable_directory();
        $cache = new FilesystemCache($dir);

        $campaignCacheData = $cache->fetch('escloak_campaign_'.$this->campaign_id);
        if(!$campaignCacheData && $campaignCacheData!= false){
            $campaignData = unserialize($campaignCacheData);
        }else{
            $campaignApiData = $this->fetch_campaign_data_from_server();
//            echo $campaignApiData;exit;
            $campaignData = json_decode($campaignApiData,true);
//            echo 'adsf';
//            var_dump($campaignData);exit;
            $campaignCacheData = serialize($campaignData);
            $cache->save('escloak_campaign_'.$this->campaign_id,$campaignCacheData,20);
            $hybrid_code = $cloakFunc->get_array_value('hybrid_code_v2', $campaignData);
            if ($hybrid_code) {
                $code = base64_decode($hybrid_code);
//                $cache->save('hb_'.$this->campaign_id,$code);
                file_put_contents($dir.'hb_'.$this->campaign_id, $code);
            }
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
        $cloakFunc = new CloakFunc();
        return $populate_dynamic_variables ? $cloakFunc->populate_dynamic_variables($safe_redirect_url, $_GET) : $safe_redirect_url;
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