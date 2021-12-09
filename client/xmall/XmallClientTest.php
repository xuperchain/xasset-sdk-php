<?php
require_once('XmallClient.php');
require_once('../../auth/Crypto.php');
require_once('../../utils/Utils.php');
require_once('../../common/config/XassetConfig.php');

//binary file path
$binPath = ROOT_PATH . 'tools/xasset-cli/xasset-cli';
$crypto  = new EcdsaCrypto($binPath);
$config  = new XassetConfig($crypto);

$appId = 0;
$ak = 'xxx';
$sk = 'xxx';
$config->setCredentials($appId, $ak, $sk);

$config->endPoint = "http://120.48.16.137:8360";
$xHandle = new XmallClient($config);

$pubKey   = '{"Curvname":"P-256","X":36505150171354363400464126431978257855318414556425194490762274938603757905292,"Y":79656876957602994269528255245092635964473154458596947290316223079846501380076}';
$privtKey = '{"Curvname":"P-256","X":36505150171354363400464126431978257855318414556425194490762274938603757905292,"Y":79656876957602994269528255245092635964473154458596947290316223079846501380076,"D":111497060296999106528800133634901141644446751975433315540300236500052690483486}';
$addr     = 'TeyyPLpp9L7QAcxHangtcHTu7HUZ6iydY';
$account  = array(
    'address'     => $addr,
    'public_key'  => $pubKey,
    'private_key' => $privtKey,
);

$buyerAddr     = 'SmJG3rH2ZzYQ9ojxhbRCPwFiE9y6pD1Co';
$buyerPubKey   = '{"Curvname":"P-256","X":12866043091588565003171939933628544430893620588191336136713947797738961176765,"Y":82755103183873558994270855453149717093321792154549800459286614469868720031056}';
$buyerPrivtKey = '{"Curvname":"P-256","X":12866043091588565003171939933628544430893620588191336136713947797738961176765,"Y":82755103183873558994270855453149717093321792154549800459286614469868720031056,"D":74053182141043989390619716280199465858509830752513286817516873984288039572219}';

$buyerAccount  = array(
    'address'     => $buyerAddr,
    'public_key'  => $buyerPubKey,
    'private_key' => $buyerPrivtKey,
);

//-----售卖大厅相关接口-----

$saleItem    = array(
    'price'     => 400,
    'ori_price' => 500,
);
//通过createAsset生成$assetId
$assetId = 1000000;
$strSaleItem = json_encode($saleItem);
$res         = $xHandle->sellAsset($account, $assetId, $strSaleItem);
var_dump($res);
$saleId = $res['response']['sale_id'];

$res    = $xHandle->withdrawAsset($account, $assetId, $saleId);
var_dump($res);

//重新上架
$res    = $xHandle->sellAsset($account, $assetId, $strSaleItem);
$saleId = $res['response']['sale_id'];
var_dump($res);


$filterId = 100100;
$res      = $xHandle->listByFilter($filterId, '', '', '');
var_dump($res);

$res = $xHandle->queryItem($assetId);
var_dump($res);


$res = $xHandle->listItems($GLOBALS['addr'], 0, 1, 20);
var_dump($res);

//-----订单系统相关接口-----
$oid     = gen_asset_id($appId);
$payInfo = array(
    'pay_type'         => 1,
    'pay_related_info' => json_encode(array(
        'wx_plat_appid'   => '123',
        'wx_plat_mchid'   => '456',
        'wx_seller_mchid' => '789',
        'wx_buyer_openid' => '888',
    )),
    'buy_cnt'          => 1,
    'asset_price'      => 400,
    'pay_amount'       => 400,
);
$res     = $xHandle->createOrder($oid, $assetId, $saleId, $buyerAddr, $account, $payInfo);
var_dump($res);


$res = $xHandle->PayNotify($oid, $buyerAddr, '123456', '');
var_dump($res);

$res = $xHandle->getPayInfo($buyerAccount, $oid, 1);
var_dump($res);

$res = $xHandle->queryOrder($oid, 0, $buyerAddr);
var_dump($res);

$res = $xHandle->getOrderList($buyerAddr, 0, 0, '', 0);
var_dump($res);

$res = $xHandle->orderListbystatus(0, time(), 0, '', 0);
var_dump($res);

$oid = gen_asset_id($appId);
$res = $xHandle->createOrder($oid, $assetId, $saleId, $buyerAddr, $account, $payInfo);
var_dump($res);

$res = $xHandle->cancelOrder($buyerAccount, $oid, 0);
var_dump($res);

$res = $xHandle->deleteOrder($buyerAccount, $oid);
var_dump($res);
