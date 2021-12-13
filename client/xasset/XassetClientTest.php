<?php
require_once('XassetClient.php');
require_once('../../auth/Crypto.php');
require_once('../../utils/Utils.php');
require_once('../../common/config/XassetConfig.php');

//binary file path
$binPath = XASSET_PHPSDK_PATH . 'tools/xasset-cli/xasset-cli';
$crypto = new EcdsaCrypto($binPath);
$config = new XassetConfig($crypto);

$appId = 0;
$ak = 'xxx';
$sk = 'xxx';
$config->setCredentials($appId, $ak, $sk);

$config->endPoint = "http://120.48.16.137:8360";
$xHandle = new XassetClient($config);

$pubKey = '';
$privtKey = '';
$addr = '';
$account = array(
    'address' => $addr,
    'public_key' => $pubKey,
    'private_key' => $privtKey,
);

$addr2 = '';
$pubKey2 = '';
$privtKey2 = '';
$account2 = array(
    'address' => $addr2,
    'public_key' => $pubKey2,
    'private_key' => $privtKey2,
);

$addr3 = '';

//文件相关接口
$stoken = $xHandle->getStoken($account);
var_dump($stoken);

$arrAssetInfo = array(
    'title' => '收藏品12号',
    'asset_cate' => 2,
    'thumb' => array('bos_v1://xasset-trade/300200/draw_star.jpeg/1000_500'),
    'short_desc' => '收藏品11号短描述',
    'img_desc' => array('bos_v1://xasset-trade/300200/draw_star.jpeg/1000_500'),
    'asset_url' => array('bos_v1://xasset-trade/300200/draw_star.jpeg/1000_500'),
);
$strAssetInfo = json_encode($arrAssetInfo);

$assetId = gen_asset_id($appId);
$userId = 1231314;
$res = $xHandle->createAsset($account, $assetId, 10000, $strAssetInfo, $userId);
var_dump($res);

$arrAssetInfo = array(
    'title' => '收藏品1号',
    'asset_cate' => 2,
    'thumb' => array('bos_v1://xasset-trade/300200/draw_star.jpeg/1000_500'),
    'short_desc' => '&收藏 品1号更新过的短描述===',
);
$strAssetInfo = json_encode($arrAssetInfo);
$res = $xHandle->alterAsset($account, $assetId,  10000, $strAssetInfo);
var_dump($res);

$res = $xHandle->publishAsset($account, $assetId);
var_dump($res);

$shardId = gen_asset_id($appId);
$userId = 123;
$res = $xHandle->grantShard($account, $assetId, $shardId, $addr2, $userId);
var_dump($res);

//上链需要一点时间
sleep(5);

$res = $xHandle->transferShard($account2, $assetId, $shardId, $addr3, 456);
var_dump($res);

//上链需要一点时间
sleep(5);

$res = $xHandle->horaeListbystatus($account, 0, 1, 20);
var_dump($res);

$res = $xHandle->listAssetsByAddr($addr, 0, 1, 20);
var_dump($res);

$res = $xHandle->queryShard($assetId, $shardId);
var_dump($res);

$res = $xHandle->listShardsByAddr($addr2, 1, 10);
var_dump($res);

$res = $xHandle->listShardsByAsset($assetId, "", 10);
var_dump($res);

$res = $xHandle->listShardsByAsset($assetId, "", 10);
var_dump($res);

$res = $xHandle->getEvidenceInfo($assetId);
var_dump($res);


