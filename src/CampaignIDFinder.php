<?php

namespace Gztango;

class CampaignIDFinder
{
    public function __construct($hardcoded_campaign_id)
    {
        if ($this->campaign_id_from_qstring()) {
            $campaign_id = $this->campaign_id_from_qstring();
        } else if ($this->campaign_id_from_js_gathered_variables()) {
            $campaign_id = $this->campaign_id_from_js_gathered_variables();
        } else if ($this->campaign_id_from_GLOBALS()) {//Used by WP
            $campaign_id = $this->campaign_id_from_GLOBALS();
        } else {
            $campaign_id = $hardcoded_campaign_id;
        }

        $GLOBALS['_ta_campaign_id'] = $campaign_id;
    }

    private function campaign_id_from_qstring()
    {
        foreach ($_GET as $key => $value) {
            if ($this->va($value) and strlen($value) == 6) {
                return $value;
            }
        }
    }

    private function va($s)
    {
//        $a = 0;
//        foreach (str_split($s) as $c) {
//            $a += ord($c);
//        }
//
//        return $a == 465;
        $return = preg_match('/^[a-zA-Z0-9]{6}$/i', $s);
        if($return){
            return true;
        }else{
            return false;
        }
    }

    private function campaign_id_from_js_gathered_variables()
    {
        if (js_gathered_visitor_variables()) {
            $campaign_id = get_array_value('k', js_gathered_visitor_variables());

            return $campaign_id;
        }
    }

    private function campaign_id_from_GLOBALS()
    {
        return get_GLOBALS_value('_ta_campaign_id'); //For WP Plugin
    }
}

?>