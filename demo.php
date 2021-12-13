<?php
defined('XASSET_PHPSDK_PATH') or define('XASSET_PHPSDK_PATH', __DIR__ . '/');
// 引用文件
require_once(XASSET_PHPSDK_PATH . 'utils/Utils.php');
require_once(XASSET_PHPSDK_PATH . 'auth/EcdsaCrypto.php');
require_once(XASSET_PHPSDK_PATH . 'common/config/XassetConfig.php');

//配置
//binary file path
$binPath = XASSET_PHPSDK_PATH . 'tools/xasset-cli/xasset-cli';
$crypto = new EcdsaCrypto($binPath);
$config = new XassetConfig($crypto);

//配置准入ak sk
$appId = 123;
$ak = 'xxx';
$sk = 'xxx';

$config->setCredentials($appId, $ak, $sk);
$config->endPoint = "http://120.48.16.137:8360";

//demo for account
require_once(XASSET_PHPSDK_PATH . 'auth/Account.php');
$accountLib = new Account($binPath);
$t = $accountLib->createAccount();
var_dump($t);
exit(PHP_EOL);


//demo for api
require_once(XASSET_PHPSDK_PATH . 'client/xasset/XassetClient.php');
$xHandle = new XassetClient($config);
// 调用SDK方法，可以参考单元测试
$account = array(
    'address'     => 'xxx',
    'public_key'  => 'xxx',
    'private_key' => 'xxx',
);
$stoken = $xHandle->getStoken($account);
var_dump($stoken);