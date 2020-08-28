<?php
namespace Gztango;

Class Curler
{
    private $ch;

    public function __construct($url, $payload,$key)
    {
        $this->init_ch($url, $payload,$key);
    }

    private function init_ch($url, $payload,$key)
    {
        $cloakFunc = new CloakFunc();
        $ch = curl_init($url);
//        $key = campaign_key();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_USERAGENT, $cloakFunc->get_SERVER_value('HTTP_USER_AGENT'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_ENCODING, ""); //Enables compression
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-type: application/json"]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["escloak-key: {$key}"]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, [$this,"forward_response_cookies"]); //Forward response's cookies to visitor

//        if (debug_mode_enabled()) {
//            curl_setopt($ch, CURLOPT_VERBOSE, 1);
//            curl_setopt($ch, CURLOPT_STDERR, fopen(curl_log_filepath(), 'w+'));
//        }

        if ($_COOKIE) {//Forward visitor's cookie to our server
            curl_setopt($ch, CURLOPT_COOKIE, $this->encode_visitor_cookies());
        }

        $this->ch = $ch;
    }

    function forward_response_cookies($ch, $headerLine)
    {
        if (preg_match('/^Set-Cookie:/mi', $headerLine, $cookie)) {
            header($headerLine, false);
        }

        return strlen($headerLine); // Needed by curl
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

    public function get_response()
    {
        $response = curl_exec($this->ch);
        curl_close($this->ch);

        return $response;
    }
}