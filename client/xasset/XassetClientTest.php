<?php
require_once('XassetClient.php');
require_once('../../auth/Crypto.php');
require_once('../../utils/Utils.php');
require_once('../../common/config/XassetConfig.php');

//binary file path
$binPath = ROOT_PATH . 'tools/xasset-cli/xasset-cli';
$crypto = new EcdsaCrypto($binPath);
$config = new XassetConfig($crypto);

$appId = 0;
$ak = 'xxx';
$sk = 'xxx';
$config->setCredentials($appId, $ak, $sk);

$config->endPoint = "http://120.48.16.137:8360";
$xHandle = new XassetClient($config);

$pubKey = '{"Curvname":"P-256","X":36505150171354363400464126431978257855318414556425194490762274938603757905292,"Y":79656876957602994269528255245092635964473154458596947290316223079846501380076}';
$privtKey = '{"Curvname":"P-256","X":36505150171354363400464126431978257855318414556425194490762274938603757905292,"Y":79656876957602994269528255245092635964473154458596947290316223079846501380076,"D":111497060296999106528800133634901141644446751975433315540300236500052690483486}';
$addr = 'TeyyPLpp9L7QAcxHangtcHTu7HUZ6iydY';
$account = array(
    'address' => $addr,
    'public_key' => $pubKey,
    'private_key' => $privtKey,
);

$addr2 = 'SmJG3rH2ZzYQ9ojxhbRCPwFiE9y6pD1Co';
$pubKey2 = '{"Curvname":"P-256","X":12866043091588565003171939933628544430893620588191336136713947797738961176765,"Y":82755103183873558994270855453149717093321792154549800459286614469868720031056}';
$privtKey2 = '{"Curvname":"P-256","X":12866043091588565003171939933628544430893620588191336136713947797738961176765,"Y":82755103183873558994270855453149717093321792154549800459286614469868720031056,"D":74053182141043989390619716280199465858509830752513286817516873984288039572219}';
$account2 = array(
    'address' => $addr2,
    'public_key' => $pubKey2,
    'private_key' => $privtKey2,
);

$addr3 = 'iYjtLcW6SVCiousAb5DFKWtWroahhEj4u';

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


