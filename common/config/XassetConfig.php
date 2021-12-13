<?php
require_once(XASSET_PATH . 'auth/BceV1Signer.php');

class XassetConfig
{
    public $endPoint = "http://120.48.16.137:8360";
    public $userAgent = "xasset-sdk-php";
    public $credentials = array();
    public $signer;
    public $crypto;
    public $connTimeout = 1000;
    public $rwTimeout = 3000;

    public function __construct($crypto) {
        $this->signer = new BceV1Signer();
        $this->crypto = $crypto;
    }

    public function setCredentials($appId, $ak, $sk) {
        $this->credentials = array(
            "app_id" => $appId,
            "ak"     => $ak,
            "sk"     => $sk,
        );
    }
}
