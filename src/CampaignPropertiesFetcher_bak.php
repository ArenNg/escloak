<?php
namespace ArenNg; 

Class CampaignPropertiesFetcher
{
    private $campaign_properties;

    public function __construct()
    {
        if ($this->cache_refresh_required()) {
            $this->refresh_cache();
        }

        $campaign_properties = file_get_contents($this->campaign_properties_storage_path());
        $campaign_properties = base64_decode($campaign_properties);
        $this->campaign_properties = json_decode($campaign_properties, true);


    }

    private function cache_refresh_required()
    {
        return !file_exists($this->campaign_properties_storage_path()) OR file_age_seconds($this->campaign_properties_storage_path()) > 10;
    }

    private function campaign_properties_storage_path()
    {
        return first_writable_directory() . '/cp_' . campaign_id();
    }

    private function refresh_cache()
    {
        $lock_file_path = $this->campaign_properties_storage_path() . '.lock';

        if (!file_exists($lock_file_path) OR file_age_seconds($lock_file_path) > 10) {
            touch($lock_file_path);
            $campaign_data = $this->fetch_campaign_data_from_server();
            $this->store_campaign_data($campaign_data);
            unlink($lock_file_path);
        }
    }

    private function fetch_campaign_data_from_server()
    {
        $domain = api_domain();
        $url = "{$domain}/cloak/getCampaign?id=" . campaign_id();
        $campaign_data = curl_get($url, campaign_key());
        if (debug_mode_enabled() AND (!$campaign_data OR !json_decode($campaign_data))) {
            output_textarea("Warning! Missing/invalid campaign info fetched from $url:", $campaign_data);
            exit;
        }
        return $campaign_data;
    }

    private function store_campaign_data($campaign_data)
    {
        $campaign_data = json_decode($campaign_data, true);

        $hybrid_code = get_array_value('hybrid_code_v4', $campaign_data);
        if ($hybrid_code) {
            $this->store_hybrid_code(base64_decode($hybrid_code));
            unset($campaign_data->hybrid_code);
        }

        $this->store_campaign_properties($campaign_data);
    }

    private function store_hybrid_code($code)
    {
        file_put_contents(hybrid_code_storage_path(), $code);
    }

    private function store_campaign_properties($campaign_properties)
    {
        $campaign_properties = json_encode($campaign_properties);
        $campaign_properties = base64_encode($campaign_properties);
        file_put_contents($this->campaign_properties_storage_path(), $campaign_properties);
    }

    public function get_campaign_properties()
    {
        return $this->campaign_properties;
    }
}