<?php
define('XASSET_PATH', dirname(__FILE__) . '/');
require_once(XASSET_PATH . 'client/xasset/XassetClient.php');
require_once(XASSET_PATH . 'client/BaseClient.php');
require_once(XASSET_PATH . 'auth/Account.php');
require_once(XASSET_PATH . 'auth/BceV1Signer.php');
require_once(XASSET_PATH . 'auth/Crypto.php');
require_once(XASSET_PATH . 'auth/DateUtils.php');
require_once(XASSET_PATH . 'auth/HttpUtils.php');
require_once(XASSET_PATH . 'auth/SignerInterface.php');
require_once(XASSET_PATH . 'auth/SignOptions.php');
require_once(XASSET_PATH . 'utils/Utils.php');
require_once(XASSET_PATH . 'common/config/XassetConfig.php');
require_once(XASSET_PATH . 'common/config/HttpHeaders.php');
//需提前加载文件上传需要的bos sdk 否则可能会引入错误 sdk下载地址 https://sdk.bce.baidu.com/console-sdk/bce-php-sdk-0.9.16.zip
// 下载解压完成后，引入
require_once(XASSET_PATH . 'bce-php-sdk/BaiduBce.phar');
