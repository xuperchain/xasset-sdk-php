<?php
defined('XASSET_PHPSDK_PATH') or define('XASSET_PHPSDK_PATH', dirname(dirname(__DIR__)). '/');
require_once(XASSET_PHPSDK_PATH . 'client/BaseClient.php');
require_once(XASSET_PHPSDK_PATH . "auth/BceV1Signer.php");
require_once(XASSET_PHPSDK_PATH . "utils/Utils.php");


class XassetClient extends BaseClient
{
    private $crypto;

    const XassetErrnoSucc = 0;

    const XassetApiHoraeCreate = '/xasset/horae/v1/create';
    const XassetApiHoraeAlter = '/xasset/horae/v1/alter';
    const XassetApiHoraePublish = '/xasset/horae/v1/publish';
    const XassetApiHoraeQuery = '/xasset/horae/v1/query';
    const XassetApiHoraeListByStatus = '/xasset/horae/v1/listbystatus';
    const XassetApiHoraeGrant = '/xasset/horae/v1/grant';
    const XassetApiHoraeTransfer = '/xasset/damocles/v1/transfer';
    const XassetApiHoraeListAstByAddr = '/xasset/horae/v1/listastbyaddr';
    const XassetApiHoraeQueryShard = '/xasset/horae/v1/querysds';
    const XassetApiHoraeListSdsByAddr = '/xasset/horae/v1/listsdsbyaddr';
    const XassetApiHoraeListSdsByAst = '/xasset/horae/v1/listsdsbyast';
    const XassetApiHoraeAstHistory = '/xasset/horae/v1/history';
    const XassetApiHoraeGetEvidenceInfo = '/xasset/horae/v1/getevidenceinfo';

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
     * @param array $account
     * @param int $assetId
     * @param int $amount
     * @param string $assetInfo
     * @return array|bool
     */
    public function createAsset($account, $assetId, $amount, $assetInfo, $userId = 0) {
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
            'pkey'       => $account['private_key'],
            'nonce'      => $nonce,
        );
        if ($userId > 0) {
            $body['user_id'] = $userId;
        }

        return $this->doRequestRetry(self::XassetApiHoraeCreate, array(), $body);
    }

    /**
     * @param array $account
     * @param int $assetId
     * @param int $amount
     * @param string $assetInfo
     * @return array|bool
     */
    public function alterAsset($account, $assetId, $amount, $assetInfo) {
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

        return $this->doRequestRetry(self::XassetApiHoraeAlter, array(), $body);
    }

    /**
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
     * @param array $account
     * @param int $status
     * @param int $page
     * @param int $limit
     * @return array|bool
     */
    public function horaeListbystatus($account, $status, $page, $limit) {
        $this->cleanError();

        if (!self::isValidAccount($account)) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $nonce   = gen_nonce();
        $signMsg = sprintf("%d", $nonce);
        $sign    = $this->crypto->signEcdsa($account['private_key'], $signMsg);

        $body = array(
            'status' => $status,
            'nonce'  => $nonce,
            'addr'   => $account['address'],
            'sign'   => $sign,
            'pkey'   => $account['public_key'],
            'page'   => $page,
        );
        if ($limit > 0) {
            $body['limit'] = $limit;
        }

        return $this->doRequestRetry(self::XassetApiHoraeListByStatus, array(), $body);
    }

    /**
     * @param array $account
     * @param int $assetId
     * @param int $shardId
     * @param $toAddr
     * @param int $toUserId
     * @return array|bool
     */
    public function grantShard($account, $assetId, $shardId, $toAddr, $toUserId = 0) {
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
        if ($toUserId > 0) {
            $body['to_userid'] = $toUserId;
        }

        return $this->doRequestRetry(self::XassetApiHoraeGrant, array(), $body);
    }

    /**
     * @param array $account
     * @param int $assetId
     * @param int $shardId
     * @param $toAddr
     * @param int $toUserId
     * @return array|bool
     */
    public function transferShard($account, $assetId, $shardId, $toAddr, $toUserId = 0) {
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
        if ($toUserId > 0) {
            $body['to_userid'] = $toUserId;
        }

        return $this->doRequestRetry(self::XassetApiHoraeTransfer, array(), $body);
    }

    /**
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
     * @param string $addr
     * @param int $page
     * @param int $limit
     * @return array|bool
     */
    public function listShardsByAddr($addr, $page, $limit) {
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

        return $this->doRequestRetry(self::XassetApiHoraeListSdsByAddr, array(), $body);
    }

    /**
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
     * @param int $assetId
     * @param int $page
     * @param int $limit
     * @return array|bool
     */
    public function listAssetHistory($assetId, $page, $limit) {
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

        return $this->doRequestRetry(self::XassetApiHoraeAstHistory, array(), $body);
    }

    /**
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
}
