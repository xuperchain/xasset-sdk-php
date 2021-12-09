<?php
define('ROOT_PATH', dirname(__FILE__) . '/../../');
require_once(ROOT_PATH . 'client/BaseClient.php');
require_once(ROOT_PATH . "auth/BceV1Signer.php");
require_once(ROOT_PATH . "utils/Utils.php");


class XmallClient extends BaseClient
{
    private $crypto;

    const XassetErrnoSucc = 0;

    //售卖大厅
    const XassetApiMarketSell = '/xasset/market/v1/sell';
    const XassetApiMarketWithdraw = '/xasset/market/v1/withdraw';
    const XassetApiMarketFilter = '/xasset/market/v1/listbyfilter';
    const XassetApiMarketQuery = '/xasset/market/v1/query';
    const XassetApiMarketListitems = '/xasset/market/v1/listitems';

    //订单系统
    const XassetApiOrderCreate = '/xasset/order/v1/create';
    const XassetApiOrderPay = '/xasset/order/v1/pay';
    const XassetApiOrderQuery = '/xasset/order/v1/query';
    const XassetApiOrderList = '/xasset/order/v1/list';
    const XassetApiOrderCancel = '/xasset/order/v1/cancel';
    const XassetApiOrderDelete = '/xasset/order/v1/delete';
    const XassetApiOrderListByStatus = '/xasset/order/v1/listbystatus';
    const XassetApiOrderPayNotify = '/xasset/order/v1/paynotify';

    /**
     * XmallClient constructor.
     * @param array $xassetConfig
     * @param bool $isHttps
     */
    public function __construct($xassetConfig) {
        parent::__construct($xassetConfig);
        $this->crypto = $xassetConfig->crypto;
    }

    /**
     * @param array $account
     * @param int $assetId
     * @param string $saleItem
     * @return array|bool
     */
    public function sellAsset($account, $assetId, $saleItem) {
        $this->cleanError();

        if ($assetId < 1 || $saleItem == '') {
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
            'addr'      => $account['address'],
            'sign'      => $sign,
            'pkey'      => $account['public_key'],
            'nonce'     => $nonce,
            'sale_item' => $saleItem,
        );

        return $this->doRequestRetry(self::XassetApiMarketSell, array(), $body);
    }

    /**
     * @param array $account
     * @param int $assetId
     * @param int $saleId
     * @return array|bool
     */
    public function withdrawAsset($account, $assetId, $saleId) {
        $this->cleanError();

        if ($assetId < 1 || $saleId < 1) {
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
            'sale_id'  => $saleId,
            'addr'     => $account['address'],
            'sign'     => $sign,
            'pkey'     => $account['public_key'],
            'nonce'    => $nonce,
        );

        return $this->doRequestRetry(self::XassetApiMarketWithdraw, array(), $body);
    }

    /**
     * @param int $filterId
     * @param string $filterCond
     * @param string $cursor
     * @param int $limit
     * @return array|bool
     */
    public function listByFilter($filterId, $filterCond, $cursor, $limit) {
        $this->cleanError();

        if ($filterId < 1) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $body = array(
            'filter_id' => $filterId,
        );
        if ($filterCond != '') {
            $body['filter_cond'] = $filterCond;
        }
        if ($cursor != '') {
            $body['cursor'] = $cursor;
        }
        if ($limit > 0) {
            $body['limit'] = $limit;
        }

        return $this->doRequestRetry(self::XassetApiMarketFilter, array(), $body);
    }

    /**
     * @param int $assetId
     * @return array|bool
     */
    public function queryItem($assetId) {
        $this->cleanError();

        if ($assetId < 1) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $body = array(
            'asset_id' => $assetId,
        );

        return $this->doRequestRetry(self::XassetApiMarketQuery, array(), $body);
    }

    /**
     * @param string $addr
     * @param int $status
     * @param int $page
     * @param int $limit
     * @return array|bool
     */
    public function listItems($addr, $status, $page, $limit) {
        $this->cleanError();

        if ($addr == '') {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $body = array(
            'addr'   => $addr,
            'status' => $status,
            'page'   => $page,
        );
        if ($limit > 0) {
            $body['limit'] = $limit;
        }

        return $this->doRequestRetry(self::XassetApiMarketListitems, array(), $body);
    }

    /**
     * @param int $oid
     * @param int $assetId
     * @param int $saleId
     * @param string $buyerAddr
     * @param array $sellerAccount
     * @param array $payInfo
     * @param int $from
     * @return array|bool
     */
    public function createOrder($oid, $assetId, $saleId, $buyerAddr, $sellerAccount, $payInfo) {
        $this->cleanError();

        if ($oid < 1 || $assetId < 1 || $saleId < 1 || $buyerAddr == '') {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }
        if (!isset($payInfo['pay_type']) || !isset($payInfo['buy_cnt']) || !isset($payInfo['asset_price']) ||
            !isset($payInfo['pay_amount'])) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }
        if (!self::isValidAccount($sellerAccount)) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $signMsg = sprintf("%d%d", $assetId, $oid);
        $sign    = $this->crypto->signEcdsa($sellerAccount['private_key'], $signMsg);

        $body = array(
            'oid'              => $oid,
            'asset_id'         => $assetId,
            'sale_id'          => $saleId,
            'seller_addr'      => $sellerAccount['address'],
            'pay_type'         => $payInfo['pay_type'],
            'pay_related_info' => $payInfo['pay_related_info'],
            'buy_cnt'          => $payInfo['buy_cnt'],
            'asset_price'      => $payInfo['asset_price'],
            'pay_amount'       => $payInfo['pay_amount'],
            'buyer_addr'       => $buyerAddr,
            'seller_sign'      => $sign,
        );
        if (isset($payInfo['profit_list'])) {
            $body['profit_list'] = $payInfo['profit_list'];
        }
        if (isset($payInfo['from'])) {
            $body['from'] = $payInfo['from'];
        }

        return $this->doRequestRetry(self::XassetApiOrderCreate, array(), $body);
    }

    /**
     * @param array $account
     * @param int $oid
     * @param int $payType
     * @return array|bool
     */
    public function getPayInfo($account, $oid, $payType) {
        $this->cleanError();

        if ($oid < 1) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }
        if (!self::isValidAccount($account)) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $nonce   = gen_nonce();
        $signMsg = sprintf("%d%d", $oid, $nonce);
        $sign    = $this->crypto->signEcdsa($account['private_key'], $signMsg);

        $body = array(
            'oid'        => $oid,
            'nonce'      => $nonce,
            'pay_type'   => $payType,
            'buyer_addr' => $account['address'],
            'buyer_pkey' => $account['public_key'],
            'buyer_sign' => $sign,
        );

        return $this->doRequestRetry(self::XassetApiOrderPay, array(), $body);
    }

    /**
     * @param int $oid
     * @param int $userType
     * @param string $addr
     * @return array|bool
     */
    public function queryOrder($oid, $userType, $addr) {
        $this->cleanError();

        if ($oid < 1 || $addr == '') {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $body = array(
            'oid'             => $oid,
            'order_user_type' => $userType,
            'addr'            => $addr,
        );

        return $this->doRequestRetry(self::XassetApiOrderQuery, array(), $body);
    }

    /**
     * @param int $oid
     * @param string $buyerAddr
     * @param string $payNum
     * @param string $payInfo
     * @return array|bool
     */
    public function payNotify($oid, $buyerAddr, $payNum, $payInfo) {
        $this->cleanError();

        if ($oid < 1 || $buyerAddr == '') {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $body = array(
            'oid'        => $oid,
            'buyer_addr' => $buyerAddr,
        );
        if ($payNum != '') {
            $body['pay_num'] = $payNum;
        }
        if ($payInfo != '') {
            $body['pay_info'] = $payInfo;
        }

        return $this->doRequestRetry(self::XassetApiOrderPayNotify, array(), $body);
    }


    /**
     * @param string $addr
     * @param int $userType
     * @param int $status
     * @param string $cursor
     * @param int $limit
     * @return array|bool
     */
    public function getOrderList($addr, $userType, $status, $cursor, $limit) {
        $this->cleanError();

        $body = array(
            'addr'            => $addr,
            'order_user_type' => $userType,
            'status'          => $status,
        );
        if ($cursor != '') {
            $body['cursor'] = $cursor;
        }
        if ($limit > 0) {
            $body['limit'] = $limit;
        }

        return $this->doRequestRetry(self::XassetApiOrderList, array(), $body);
    }

    /**
     * @param array $account
     * @param int $oid
     * @param int $cancelType
     * @return array|bool
     */
    public function cancelOrder($account, $oid, $cancelType) {
        $this->cleanError();

        if ($oid < 1) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }
        if (!self::isValidAccount($account)) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $nonce   = gen_nonce();
        $signMsg = sprintf("%d%d", $oid, $nonce);
        $sign    = $this->crypto->signEcdsa($account['private_key'], $signMsg);

        $body = array(
            'oid'         => $oid,
            'cancel_type' => $cancelType,
            'nonce'       => $nonce,
            'buyer_addr'  => $account['address'],
            'buyer_pkey'  => $account['public_key'],
            'buyer_sign'  => $sign,
        );

        return $this->doRequestRetry(self::XassetApiOrderCancel, array(), $body);
    }

    /**
     * @param array $account
     * @param int $oid
     * @return array|bool
     */
    public function deleteOrder($account, $oid) {
        $this->cleanError();

        if ($oid < 1) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }
        if (!self::isValidAccount($account)) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $nonce   = gen_nonce();
        $signMsg = sprintf("%d%d", $oid, $nonce);
        $sign    = $this->crypto->signEcdsa($account['private_key'], $signMsg);

        $body = array(
            'oid'        => $oid,
            'nonce'      => $nonce,
            'buyer_addr' => $account['address'],
            'buyer_pkey' => $account['public_key'],
            'buyer_sign' => $sign,
        );

        return $this->doRequestRetry(self::XassetApiOrderDelete, array(), $body);
    }

    /**
     * @param $mtimeStart
     * @param $mtimeEnd
     * @param $status
     * @param $cursor
     * @param $limit
     * @return array|bool
     */
    public function orderListbystatus($mtimeStart, $mtimeEnd, $status, $cursor, $limit) {
        $this->cleanError();

        if ($mtimeStart < 0 || $mtimeEnd < 1) {
            $this->setError(parent::ClientErrnoParamErr, 'param error');
            return false;
        }

        $body = array(
            'mtime_start'    => $mtimeStart,
            'mtime_end'      => $mtimeEnd,
            'display_status' => $status,
        );
        if ($cursor != '') {
            $body['cursor'] = $cursor;
        }
        if ($limit > 0) {
            $body['limit'] = $limit;
        }

        return $this->doRequestRetry(self::XassetApiOrderListByStatus, array(), $body);
    }
}