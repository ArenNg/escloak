<?php
namespace ArenNg;

class HybridPageSender
{
    public function __construct()
    {
        $cli_key = campaign()->cli_key();

        require_once hybrid_code_storage_path($cli_key);
    }
}