<?php

class EcdsaCrypto {

    private $binPath;

    public function __construct($binPath){
        $this->binPath = $binPath;
    }

    /**
     * @param string $privtKey
     * @param string $msg
     * @return string
     */
    public function signEcdsa($privtKey, $msg) {
        $privtKey = str_replace('"', '\\"', $privtKey);
        $sign = exec($this->binPath . ' sign ecsda -k "' . $privtKey . '" -m "' . $msg . '" -f std');
        return trim($sign);
    }
}

/**
 * 目前仅支持pem格式的私钥签名
 * @param string $pemPrivtKey
 * @param string $oriMsg
 * @return string
 */
function sign_ecdsa($pemPrivtKey, $oriMsg) {
    $key = openssl_pkey_get_private($pemPrivtKey);
    openssl_sign($oriMsg, $sign, $key, OPENSSL_ALGO_SHA256);
    openssl_free_key($key);
    return bin2hex($sign);
}