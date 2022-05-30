<?php

class Account {

    private $binPath;

    public function __construct($binPath){
        $this->binPath = $binPath;
    }

    /**
     * @return array|false|mixed
     */
    public function createAccount() {
        $s = exec($this->binPath . ' account create -l 1 -s 1 -f std');
        $arrAccount = json_decode(trim($s), true);
        if (empty($arrAccount) || !is_array($arrAccount)) {
            return false;
        }
        return $arrAccount;
    }

    public function retrieveAccount($mnemonic) {
        $cmd = sprintf(" account retrieve -m '%s' -l 1 -f std", $mnemonic);
        $s = exec($this->binPath . $cmd);
        $arrAccount = json_decode(trim($s), true);
        if (empty($arrAccount) || !is_array($arrAccount)) {
            return false;
        }
        return $arrAccount;
    }
}
