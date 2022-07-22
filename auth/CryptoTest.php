<?php
require_once('../index.php');
require_once(XASSET_PATH . 'auth/Crypto.php');
require_once(XASSET_PATH . 'auth/Account.php');

$path = '../tools/xasset-cli/xasset-cli_mac';
$aHandle = new Account($path);
$account = $aHandle->createAccount();
var_dump($account);

$signer = new EcdsaCrypto($path);
$sign = $signer->signEcdsa($account['private_key'], '123');
var_dump($sign);

$res = aes_encrypt('test msg', '91bcdd6073db4cca2a4163');
var_dump($res);

$res = aes_decrypt($res, '91bcdd6073db4cca2a4163');
var_dump($res);