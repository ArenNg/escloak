<?php

namespace Gztango;

Class Debugger
{
    private $visitor;
    private $request;
    private $response;
    protected $Campaign;

    public function __construct($visitor, $request, $response,$debug = false,$sign_key,$campaign_id)
    {
        if (!$debug) {
            return false;
        }
        $this->Campaign = new Campaign($campaign_id,$sign_key,$debug);
        $this->visitor = $visitor;
        $this->request = $request;
        $this->response = $response;

        $this->run_debugging();
        exit;
    }

    private function run_debugging()
    {
        $cloakFunc = new CloakFunc();
        //Print out the campaign info
        $cloakFunc->output_textarea($this->Campaign->label() . ' (' . $this->Campaign->cli_key() . '), Campaign Properties:', print_r($this->Campaign->properties(), true));

        //Print out the request
        $cloakFunc->output_textarea('Sent Request', $this->request);

        //Print out the response (decoded if valid)
        $cloakFunc->output_textarea('Response Received', $this->response);

        //Print out the curl transaction
//        $cloakFunc->output_textarea('CURL Log', file_get_contents(curl_log_filepath()));

        print "<hr>";
        if ($this->api_response_looks_valid()) {
            $cloakFunc->ph("您的脚本似乎正常工作，您可以开始发送常规流量。");
        } else {
            $cloakFunc->ph("警告！脚本中出现一个或多个错误，请与支持人员联系并提供上面显示的调试信息。请不要发送流量。");
        }

        print "<center><h2>重要提示：在发送流量之前，请禁用调试模式（或从URL中删除debug_key=xxx）</h2></center>";
    }

    private function api_response_looks_valid()
    {
        $response = $this->response;
        if (!$response) {
            ph("错误：API响应为空或不是有效的JSON格式");

            return false;
        } else if ($this->response_field_missing($response)) {
            ph("错误：API未发送所需字段：" . $this->response_field_missing($response));

            return false;
        } else if (!$response['action']) {
            ph("错误：从API接收到空白的“操作”字段");

            return false;
        }

        return true;
    }

    private function response_field_missing($response)
    {
        $required_fields = array(
            'cloak_reason',
            'visitor',
            'action',
            'url');

        foreach ($required_fields as $required_field) {
            if (!array_key_exists($required_field, $response)) {
                return $required_field;
            }
        }
    }
}