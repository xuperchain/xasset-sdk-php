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
