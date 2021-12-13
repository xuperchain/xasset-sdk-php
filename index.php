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
