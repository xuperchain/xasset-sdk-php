<?php
require_once(XASSET_PATH . 'client/BaseClient.php');
require_once(XASSET_PATH . "auth/BceV1Signer.php");
require_once(XASSET_PATH . "auth/Crypto.php");
require_once(XASSET_PATH . "utils/Utils.php");

//需提前加载文件上传需要的bos sdk 否则可能会引入错误 sdk 下载地址 https://sdk.bce.baidu.com/console-sdk/bce-php-sdk-0.9.16.zip
// 下载解压完成后，引入
require_once(XASSET_PATH . 'bce-php-sdk/BaiduBce.phar');



class XassetClient extends BaseClient
{
    private $crypto;

    const XassetErrnoSucc = 0;

    const XassetApiHoraeCreate = '/xasset/horae/v1/create';
    const XassetApiHoraeAlter = '/xasset/horae/v1/alter';
    const XassetApiHoraePublish = '/xasset/horae/v1/publish';
    const XassetApiHoraeQuery = '/xasset/horae/v1/query';
    const XassetApiHoraeGrant = '/xasset/horae/v1/grant';
    const XassetApiHoraeTransfer = '/xasset/damocles/v1/transfer';
    const XassetApiHoraeListAstByAddr = '/xasset/horae/v1/listastbyaddr';
    const XassetApiHoraeQueryShard = '/xasset/horae/v1/querysds';
    const XassetApiHoraeListSdsByAddr = '/xasset/horae/v1/listsdsbyaddr';
    const XassetApiHoraeListDiffByAddr = '/xasset/horae/v1/listdiffbyaddr';
    const XassetApiHoraeListSdsByAst = '/xasset/horae/v1/listsdsbyast';
    const XassetApiHoraeAstHistory = '/xasset/horae/v1/history';
    const XassetApiHoraeGetEvidenceInfo = '/xasset/horae/v1/getevidenceinfo';
    const XassetApiHoraeFreeze = '/xasset/horae/v1/freeze';
    const XassetApiHoraeConsume = '/xasset/horae/v1/consume';

    const XassetApiDidBdboxRegister = '/xasset/did/v1/bdboxregister';
    const XassetApiDidBdboxBind = '/xasset/did/v1/bdboxbind';
    const XassetApiDidBindByUnionid = '/xasset/did/v1/bindbyunionid';
    const XassetApiSceneListAddr = '/xasset/scene/v1/listaddr';
    const XassetApiSceneListSdsByAddr = '/xasset/scene/v1/listsdsbyaddr';
    const XassetApiSceneHasAsset = '/xasset/scene/v1/hasastbyaddr';
    const XassetApiSceneListDiffByAddr = '/xasset/scene/v1/listdiffbyaddr';
    const XassetApiSceneQueryShard = '/xasset/scene/v1/qrysdsinfo';

    const XassetApiGetStoken = '/xasset/file/v1/getstoken';

    /**
     * XassetClient constructor.
     * @param array $xassetConfig
     */
    public function __construct($xassetConfig) {
        parent::__construct($xassetConfig);
        $this->crypto = $xassetConfig->crypto;
    }

    /**
     * @content 获取上传bos 临时sts授权
     * @param array $account
     * @return array|bool
     */
    public function getStoken($account) {
        $this->cleanError();

        if (!self::isValidAccount($account)) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }
        $nonce   = gen_nonce();
        $signMsg = sprintf("%d", $nonce);
        $sign    = $this->crypto->signEcdsa($account['private_key'], $signMsg);

        $body = array(
            'addr'  => $account['address'],
            'pkey'  => $account['public_key'],
            'sign'  => $sign,
            'nonce' => $nonce,
        );

        return $this->doRequestRetry(self::XassetApiGetStoken, array(), $body);
    }

    /**
     * @content 使用sts临时授权 上传文件到bos
     * @param array $account
     * @param string $filename
     * @param string  $property 图片属性 格式为 weight_height 不传将通过函数获取图片实际宽高
     * @return array|bool
     */
    public function UploadFile($account, $filename, $property = false) {
        $stoken = $this->getStoken($account);
        $accessInfo = $stoken['response']['accessInfo'];
        $bucketName = $accessInfo['bucket'];
        $objectPath = $accessInfo['object_path'];
        $objectKey = $objectPath.$filename;
        $BOS_TEST_CONFIG = [
            'credentials'=>[
                'accessKeyId'=>$accessInfo['access_key_id'],
                'secretAccessKey'=>$accessInfo['secret_access_key'],
                'sessionToken'=>$accessInfo['session_token'],
            ],
            'endpoint'=>$accessInfo['endPoint']
        ];
        $BosClient =  new BaiduBce\Services\Bos\BosClient($BOS_TEST_CONFIG);
        $re = $BosClient->putObjectFromFile($bucketName,$objectKey,$filename);
        if(!$property){
            $FileInfo = getimagesize($filename);
            $property = $FileInfo[0]."_".$FileInfo[1];
        }
        $link = "bos_v1://". $bucketName."/".$objectKey."/".$property;
        //通过判断是否返回etag 判定是否上传成功
        if(isset($re->metadata['etag']) && !empty($re->metadata['etag'])){
            return [
                'Link'=>$link,
                'AccessInfo'=>$accessInfo
            ];
        }else{
            return "upload file err";
        }
    }

    /**
     * @content 创建数字资产 此时不会上链
     * @param array $account
     * @param int $assetId
     * @param int $amount
     * @param string $assetInfo
     * @return array|bool
     */
    public function createAsset($account, $assetId, $amount, $assetInfo, $price = -1, $userId = 0) {
        $this->cleanError();

        if ($assetId < 1 || $amount < 1 || $assetInfo == '') {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }
        if (!self::isValidAccount($account)) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $nonce   = gen_nonce();
        $signMsg = sprintf("%d%d", $assetId, $nonce);
        $sign    = $this->crypto->signEcdsa($account['private_key'], $signMsg);

        $body = array(
            'asset_id'   => $assetId,
            'amount'     => $amount,
            'asset_info' => $assetInfo,
            'addr'       => $account['address'],
            'sign'       => $sign,
            'pkey'       => $account['public_key'],
            'nonce'      => $nonce,
        );
        if ($price > -1) {
            $body['price'] = $price;
        }
        if ($userId > 0) {
            $body['user_id'] = $userId;
        }

        return $this->doRequestRetry(self::XassetApiHoraeCreate, array(), $body);
    }

    /**
     * @content 修改已经创建未发行的数字藏品信息
     * @param array $account
     * @param int $assetId
     * @param int $amount
     * @param string $assetInfo
     * @return array|bool
     */
    public function alterAsset($account, $assetId, $amount, $assetInfo, $price = -1) {
        $this->cleanError();

        if ($assetId < 1 || $assetInfo == '') {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }
        if (!self::isValidAccount($account)) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }
        $nonce   = gen_nonce();
        $signMsg = sprintf("%d%d", $assetId, $nonce);
        $sign    = $this->crypto->signEcdsa($account['private_key'], $signMsg);

        $body = array(
            'asset_id' => $assetId,
            'addr'     => $account['address'],
            'sign'     => $sign,
            'pkey'     => $account['public_key'],
            'nonce'    => $nonce,
        );
        if ($amount > -1) {
            $body['amount'] = $amount;
        }
        if ($assetInfo != '') {
            $body['asset_info'] = $assetInfo;
        }
        if ($price > -1) {
            $body['price'] = $price;
        }

        return $this->doRequestRetry(self::XassetApiHoraeAlter, array(), $body);
    }

    /**
     * @content 发行数字资产上链
     * @param array $account
     * @param int $assetId
     * @return array|bool
     */
    public function publishAsset($account, $assetId) {
        $this->cleanError();

        if ($assetId < 1) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }
        if (!self::isValidAccount($account)) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $nonce   = gen_nonce();
        $signMsg = sprintf("%d%d", $assetId, $nonce);
        $sign    = $this->crypto->signEcdsa($account['private_key'], $signMsg);

        $body = array(
            'asset_id' => $assetId,
            'addr'     => $account['address'],
            'sign'     => $sign,
            'pkey'     => $account['public_key'],
            'nonce'    => $nonce,
        );

        return $this->doRequestRetry(self::XassetApiHoraePublish, array(), $body);
    }


    /**
     * @content 查询数字资产详情
     * @param int $assetId
     * @return array|bool
     */
    public function queryAsset($assetId) {
        $this->cleanError();

        if ($assetId < 1) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $body = array(
            'asset_id' => $assetId,
        );

        return $this->doRequestRetry(self::XassetApiHoraeQuery, array(), $body);
    }



    /**
     * @content 授予碎片
     * @param array $account
     * @param int $assetId
     * @param int $shardId
     * @param $toAddr
     * @param int $toUserId
     * @return array|bool
     */
    public function grantShard($account, $assetId, $shardId, $toAddr, $price = -1 ,$toUserId = 0) {
        $this->cleanError();

        if ($assetId < 1 || $shardId < 1 || $toAddr == "") {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }
        if (!self::isValidAccount($account)) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $nonce   = gen_nonce();
        $signMsg = sprintf("%d%d", $assetId, $nonce);
        $sign    = $this->crypto->signEcdsa($account['private_key'], $signMsg);

        $body = array(
            'asset_id'  => $assetId,
            'shard_id'  => $shardId,
            'addr'      => $account['address'],
            'sign'      => $sign,
            'pkey'      => $account['public_key'],
            'nonce'     => $nonce,
            'to_addr'   => $toAddr,
            'to_userid' => $toUserId,
        );
        if ($price > -1 ) {
            $body['price'] = $price;
        }
        if ($toUserId > 0) {
            $body['to_userid'] = $toUserId;
        }

        return $this->doRequestRetry(self::XassetApiHoraeGrant, array(), $body);
    }

    /**
     * @content 碎片转移
     * @param array $account
     * @param int $assetId
     * @param int $shardId
     * @param $toAddr
     * @param int $toUserId
     * @return array|bool
     */
    public function transferShard($account, $assetId, $shardId, $toAddr, $price = -1, $toUserId = 0) {
        $this->cleanError();

        if ($assetId < 1 || $shardId < 1 || $toAddr == "") {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }
        if (!self::isValidAccount($account)) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }
        $nonce   = gen_nonce();
        $signMsg = sprintf("%d%d", $assetId, $nonce);
        $sign    = $this->crypto->signEcdsa($account['private_key'], $signMsg);

        $body = array(
            'asset_id' => $assetId,
            'shard_id' => $shardId,
            'addr'     => $account['address'],
            'sign'     => $sign,
            'pkey'     => $account['public_key'],
            'nonce'    => $nonce,
            'to_addr'  => $toAddr,
        );
        if ($price > -1) {
            $body['price'] = $price;
        }
        if ($toUserId > 0) {
            $body['to_userid'] = $toUserId;
        }

        return $this->doRequestRetry(self::XassetApiHoraeTransfer, array(), $body);
    }



    /**
     * @content 获取用户创建的藏品列表
     * @param string $addr
     * @param int $status
     * @param int $page
     * @param int $limit
     * @return array|bool
     */
    public function listAssetsByAddr($addr, $status, $page, $limit) {
        $this->cleanError();

        if ($addr == "" || $page < 1 || $limit < 1) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $body = array(
            'addr'   => $addr,
            'status' => $status,
            'page'   => $page,
            'limit'  => $limit,
        );

        return $this->doRequestRetry(self::XassetApiHoraeListAstByAddr, array(), $body);
    }

    /**
     * @content 查询碎片详情
     * @param int $assetId
     * @param int $shardId
     * @return array|bool
     */
    public function queryShard($assetId, $shardId) {
        $this->cleanError();

        if ($assetId < 1 || $shardId < 1) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $body = array(
            'asset_id' => $assetId,
            'shard_id' => $shardId,
        );

        return $this->doRequestRetry(self::XassetApiHoraeQueryShard, array(), $body);
    }

    /**
     * @content 分页拉取用户拥有的碎片
     * @param string $addr
     * @param int $page
     * @param int $limit
     * @param int $assetId
     * @return array|bool
     */
    public function listShardsByAddr($addr, $page, $limit, $assetId = 0) {
        $this->cleanError();

        if ($addr == "" || $page < 1 || $limit < 1) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $body = array(
            'addr'  => $addr,
            'page'  => $page,
            'limit' => $limit,
        );
        if ($assetId > 0) {
            $body['asset_id'] = $assetId;
        }

        return $this->doRequestRetry(self::XassetApiHoraeListSdsByAddr, array(), $body);
    }

    /**
     * @content 分页拉取指定address下的藏品变更记录
     * @param $addr
     * @param int $limit
     * @param string $cursor
     * @param array $opTypes
     * @return array|bool
     */
    public function listDiffByAddr($addr, $limit = 0 , $cursor = "", $opTypes = array()) {
        $this->cleanError();

        if ($addr == "") {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $body = array(
            'addr'  => $addr,
        );
        if ($limit > 0) {
            $body['limit'] = $limit;
        }
        if ($cursor == "") {
            $body['cursor'] = $cursor;
        }
        if (count($opTypes)) {
            $body['op_types'] = json_encode($opTypes);
        }

        return $this->doRequestRetry(self::XassetApiHoraeListDiffByAddr, array(), $body);
    }

    /**
     * @content 查询指定资产已经授予的碎片列表
     * @param int $assetId
     * @param string $cursor
     * @param int $limit
     * @return array|bool
     */
    public function listShardsByAsset($assetId, $cursor, $limit) {
        $this->cleanError();

        if ($assetId < 1 || $limit < 1) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $body = array(
            'asset_id' => $assetId,
            'cursor'   => $cursor,
            'limit'    => $limit,
        );

        return $this->doRequestRetry(self::XassetApiHoraeListSdsByAst, array(), $body);
    }

    /**
     * @content 拉取数字资产等级记录
     * @param int $assetId
     * @param int $page
     * @param int $limit
     * @param int $shardId
     * @return array|bool
     */
    public function listAssetHistory($assetId, $page, $limit, $shardId = 0) {
        $this->cleanError();

        if ($assetId < 1 || $page < 1 || $limit < 1) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $body = array(
            'asset_id' => $assetId,
            'page'     => $page,
            'limit'    => $limit,
        );
        if ($shardId > 0) {
            $body['shard_id'] = $shardId;
        }

        return $this->doRequestRetry(self::XassetApiHoraeAstHistory, array(), $body);
    }


    /**
     * @content 获取存证相关信息
     * @param int $assetId
     * @return array|bool
     */
    public function getEvidenceInfo($assetId) {
        $this->cleanError();

        if ($assetId < 1) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $body = array(
            'asset_id' => $assetId,
        );

        return $this->doRequestRetry(self::XassetApiHoraeGetEvidenceInfo, array(), $body);
    }

    /**
     * @content 资产锁仓
     * @param $account
     * @param $assetId
     */
    public function freezeAsset($account, $assetId) {
        $this->cleanError();

        if ($assetId < 1) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }
        if (!self::isValidAccount($account)) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $nonce   = gen_nonce();
        $signMsg = sprintf("%d%d", $assetId, $nonce);
        $sign    = $this->crypto->signEcdsa($account['private_key'], $signMsg);

        $body = array(
            'asset_id' => $assetId,
            'addr'     => $account['address'],
            'sign'     => $sign,
            'pkey'     => $account['public_key'],
            'nonce'    => $nonce,
        );

        return $this->doRequestRetry(self::XassetApiHoraeFreeze, array(), $body);
    }

    /**
     * @content 销毁碎片
     * @param $caccount 创建资产的账户
     * @param $uaccount 拥有碎片的账户
     * @param $assetId
     * @param $shardId
     * @return bool
     */
    public function consumeShard($caccount, $uaccount, $assetId, $shardId) {
        $this->cleanError();

        if ($assetId < 1 || $shardId < 1) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }
        if (!self::isValidAccount($caccount) || !self::isValidAccount($uaccount) ) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $nonce   = gen_nonce();
        $signMsg = sprintf("%d%d", $assetId, $nonce);
        $csign    = $this->crypto->signEcdsa($caccount['private_key'], $signMsg);
        $usign    = $this->crypto->signEcdsa($uaccount['private_key'], $signMsg);

        $body = array(
            'asset_id' => $assetId,
            'shard_id' => $shardId,
            'addr'     => $caccount['address'],
            'sign'     => $csign,
            'pkey'     => $caccount['public_key'],
            'nonce'    => $nonce,
            'user_addr'  => $uaccount['address'],
            'user_sign'  => $usign,
            'user_pkey'  => $uaccount['public_key'],
        );

        return $this->doRequestRetry(self::XassetApiHoraeConsume, array(), $body);
    }


    /**
     * @content 使用手百小程序注册链上账户
     * @param string $openId open_id获取方式https://smartprogram.baidu.com/docs/develop/function/login_process/
     * @param string $appKey
     * @return array|bool
     */
    public function bdboxRegister($openId, $appKey) {
        $this->cleanError();

        if ($openId == "" || $appKey == "") {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $encOpenId = aes_encrypt($openId, $this->credentials['sk']);
        $encAppKey = aes_encrypt($appKey, $this->credentials['sk']);

        $body = array(
            'open_id' => $encOpenId,
            'app_key' => $encAppKey,
        );

        $res = $this->doRequestRetry(self::XassetApiDidBdboxRegister, array(), $body);
        if (!isset($res['response']['mnemonic'])) {
            $this->setError(parent::ClientErrnoRespErr, 'unexpected resp');
            return false;
        }

        $res['response']['mnemonic'] = aes_decrypt($res['response']['mnemonic'], $this->credentials['sk']);
        return $res;
    }

    /**
     * @content 使用手百小程序绑定链上账户 通过已有助记词进行绑定
     * @param string $openId 手百小程序open_id
     * @param string $appKey 手百小程序app_key
     * @param string $mnemonic
     * @return array|bool
     */
    public function bdboxBind($openId, $appKey, $mnemonic) {
        $this->cleanError();

        if ($openId == "" || $appKey == "" || $mnemonic == "") {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $encOpenId = aes_encrypt($openId, $this->credentials['sk']);
        $encAppKey = aes_encrypt($appKey, $this->credentials['sk']);
        $encMnemonic = aes_encrypt($mnemonic, $this->credentials['sk']);

        $body = array(
            'open_id' => $encOpenId,
            'app_key' => $encAppKey,
            'mnemonic' => $encMnemonic,
        );

        return $this->doRequestRetry(self::XassetApiDidBdboxBind, array(), $body);
    }

    /**
     * @content 第三方应用自动绑定链上账户
     * @param string $unionId 获取union_id参考https://openauth.baidu.com/doc/doc.html
     * @param string $mnemonic
     * @return array|bool
     */
    public function bindByUnionid($unionId, $mnemonic) {
        $this->cleanError();

        if ($unionId == "" || $mnemonic == "") {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $encUnionId = aes_encrypt($unionId, $this->credentials['sk']);
        $encMnemonic = aes_encrypt($mnemonic, $this->credentials['sk']);

        $body = array(
            'union_id' => $encUnionId,
            'mnemonic' => $encMnemonic,
        );

        return $this->doRequestRetry(self::XassetApiDidBindByUnionid, array(), $body);
    }

    /**
     * @content
     * @param string $unionId 第三方应用获取的union_id
     * @return array|bool
     */
    public function sceneListAddr($unionId) {
        $this->cleanError();

        if ($unionId == "") {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $encUnionId = aes_encrypt($unionId, $this->credentials['sk']);

        $body = array(
            'union_id' => $encUnionId
        );

        return $this->doRequestRetry(self::XassetApiSceneListAddr, array(), $body);
    }

    /**
     * @content 拉取address下允许访问的藏品列表
     * @param string $addr 要查询的账户地址
     * @param string $token token
     * @param int $limit
     * @param string $cursor
     * @return array|bool
     */
    public function sceneListShardsByAddr($addr, $token, $limit = 0, $cursor = "") {
        $this->cleanError();

        if ($addr == "" || $token == "") {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $body = array(
            'addr' => $addr,
            'token' => $token,
        );
        if ($limit > 0) {
            $body['limit'] = $limit;
        }
        if ($cursor != "") {
            $body['cursor'] = $cursor;
        }

        return $this->doRequestRetry(self::XassetApiSceneListSdsByAddr, array(), $body);
    }

    /**
     * @content 判断address下是否有指定藏品
     * @param string $addr 账户地址
     * @param string $token token
     * @param array $assetIds asset_id列表一次查询不超过10个
     * @return array|bool
     */
    public function sceneHasAsset($addr, $token, $assetIds) {
        $this->cleanError();

        if ($addr == "" || $token == "" || count($assetIds) < 1) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $body = array(
            'addr' => $addr,
            'token' => $token,
            'asset_ids' => json_encode($assetIds),
        );

        return $this->doRequestRetry(self::XassetApiSceneHasAsset, array(), $body);
    }

    /**
     * @content 拉取address下藏品变更记录
     * @param string $addr
     * @param string $token
     * @param int $limit
     * @param string $cursor
     * @param array $opTypes 操作类型
     * @return array|bool
     */
    public function sceneListDiffByAddr($addr, $token, $limit = 0, $cursor = "", $opTypes = array()) {
        $this->cleanError();

        if ($addr == "" || $token == "") {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $body = array(
            'addr' => $addr,
            'token' => $token,
        );
        if ($limit > 0) {
            $body['limit'] = $limit;
        }
        if ($cursor != "") {
            $body['cursor'] = $cursor;
        }
        if (count($opTypes) > 0) {
            $body['op_types'] = json_encode($opTypes);
        }

        return $this->doRequestRetry(self::XassetApiSceneListDiffByAddr, array(), $body);
    }

    /**
     * @content 查询用户碎片详情
     * @param string $addr
     * @param string $token
     * @param int $assetId
     * @param int $shardId
     * @return array|bool
     */
    public function sceneQueryShard($addr, $token, $assetId, $shardId) {
        $this->cleanError();

        if ($addr == "" || $token == "" || $assetId < 1 || $shardId < 1) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $body = array(
            'addr' => $addr,
            'token' => $token,
            'asset_id' => $assetId,
            'shard_id' => $shardId,
        );

        return $this->doRequestRetry(self::XassetApiSceneQueryShard, array(), $body);
    }
}
