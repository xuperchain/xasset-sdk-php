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

function aes_encrypt($origData, $key) {
    $iv = substr($key, 0, 16);
    $res = openssl_encrypt($origData, 'AES-256-CBC', $key, OPENSSL_PKCS1_PADDING, $iv);
    return base64url_encode($res);
}

function aes_decrypt($origData, $key) {
    $origData = base64url_decode($origData);
    $iv = substr($key, 0, 16);
    return openssl_decrypt($origData, 'AES-256-CBC', $key, OPENSSL_PKCS1_PADDING, $iv);
}

//A-Za-z0-9-_ 移除用于padding的=
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}