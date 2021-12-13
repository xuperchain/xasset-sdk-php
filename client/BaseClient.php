<?php
require_once(XASSET_PATH . 'auth/HttpUtils.php');

abstract class BaseClient {
    // 请求默认超时设置
    const ReqConnTimeoutMs = 1000;
    const ReqTimeoutMs = 3000;

    // 错误码
    const ClientErrnoParamErr = 40001;
    const ClientErrnoCurlErr = 3004;
    const ClientErrnoRespErr = 3005;

    // 请求服务重试次数
    const RequestRetryTimes = 3;

    private $errCode = 0;
    private $errMsg = "";
    private $isHttps = false;
    private $host = "";
    private $signer;
    private $credentials;
    private $userAgent;
    private $connTimeout;
    private $rwTimeout;

    /**
     * BaseClient constructor.
     * @param $xassetConfig
     */
    public function __construct($xassetConfig) {
        $this->host = $xassetConfig->endPoint;
        $this->signer = $xassetConfig->signer;
        $this->credentials = $xassetConfig->credentials;
        $this->userAgent = $xassetConfig->userAgent;
        $this->connTimeout = $xassetConfig->connTimeout > 0 ? $xassetConfig->connTimeout : BaseClient::ReqConnTimeoutMs;
        $this->rwTimeout = $xassetConfig->rwTimeout > 0 ? $xassetConfig->rwTimeout : BaseClient::ReqTimeoutMs;
        $this->isHttps = $this->setHttps();
    }

    /**
     * @param
     * @return bool
     */
    private function setHttps() {
        $pos = strpos(strtolower($this->host), "https");
        if ($pos === false) {
            return false;
        }
        return true;
    }

    public function isHttps() {
        return $this->isHttps;
    }

    /**
     * @param
     * @return
     **/
    public function getErrCode() {
        return $this->errCode;
    }

    /**
     * @param
     * @return
     **/
    public function getErrMsg() {
        return $this->errMsg;
    }

    /**
     * @param
     * @return
     **/
    protected function setError($errCode, $errMsg) {
        $this->errCode = $errCode;
        $this->errMsg = $errMsg;
    }

    /**
     * @param
     * @return
     **/
    protected function cleanError() {
        $this->errCode = 0;
        $this->errMsg = "";
    }

    /**
     * @param $uri
     * @param $param
     * @param array $body
     * @return array|bool
     */
    protected function doRequestRetry($uri, $param, $body = array())
    {
        $res      = false;
        $reqTimes = 0;
        while ($reqTimes < self::RequestRetryTimes) {
            $res = $this->doRequest($uri, $param, $body);
            if (!empty($res)) {
                break;
            }
            $reqTimes++;
        }
        if (!empty($res)) {
            $res['req_times'] = $reqTimes;
        }

        return $res;
    }

    /**
     * @param $uri
     * @param array $param
     * @param array $body
     * @return array|bool
     */
    protected function doRequest($uri, $param = array(), $body = array())
    {
        $time   = new DateTime();
        $arrUrl = parse_url($this->host);
        $header = array(
            "Host"         => $arrUrl['host'],
            "Content-Type" => "application/x-www-form-urlencoded;charset=utf-8",
            "Timestamp"    => $time->getTimestamp(),
            "User-Agent"   => $this->userAgent,
        );
        $option = array(
            "timestamp"     => $time,
            "headersToSign" => array("host"),
        );
        $method = "GET";
        if (!empty($body)) {
            $method = "POST";
        }
        $encodedParam = $this->formatQueryString($param);
        $sign = $this->signer->sign($this->credentials, $method, $uri, $header, $encodedParam, $option);
        $header["Authorization"] = $sign;

        if (!empty($param)) {
            $uri .= "?" . $this->formatBody($param);
        }

        $res = $this->doRequestByHostRaw($this->host, $uri, $body, $header, $method);
        if (empty($res)) {
            return $res;
        }

        $url = $res['url'];
        $respRes = json_decode($res["response"], true);
        if (empty($respRes) || !isset($respRes['errno'])) {
            $error = sprintf("response error.url:%s response:%s", $url, $res['response']);
            $this->setError(BaseClient::ClientErrnoRespErr, $error);
            return false;
        }

        return array(
            'url'      => $url,
            'response' => $respRes,
        );
    }
     
    /**
     * @param
     * @return array|bool 
     **/
    protected function doRequestByHostRaw($host, $uri, $body, $header = null, $method='POST') {
        $url = $this->formatUrl($host, $uri);
        $curlRes = $this->curl_exec($url, $body, $header, $method);
        if ($curlRes["result"] == false) {
            $error = sprintf("curl server fail.url:%s curl_info:%s", $url, json_encode($curlRes));
            $this->setError(BaseClient::ClientErrnoCurlErr, $error);
            return false;
        }

        return array(
            'url' => $url,
            'response' => $curlRes['response'],
        );
    }
    
    /**
     * @param
     * @return
     **/
    protected function curl_exec($url, $body, $header = null, $method = 'POST') {
        $res = array(
            "result"    =>  true,
            "errno"     =>  0,
            "error"     =>  "",
            "http_code" =>  200,
            "response"  =>  "",
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->formatBody($body));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $this->connTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->rwTimeout);
        if (is_array($header) && !empty($header)) {
            $headerArr = array();
            foreach ($header as $k => $v) {
                $headerArr[] = sprintf("%s: %s", $k, $v);
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
        }
        if ($this->isHttps()) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        $curlRet = curl_exec($ch);
        if(empty($curlRet)) {
            $res["result"] = false;
            $res["errno"] = curl_errno($ch);
            $res["error"] = curl_error($ch);
            $infos = curl_getinfo($ch);
            $res["http_code"] = $infos["http_code"];
            curl_close($ch);
            return $res;
        }

        curl_close($ch);
        $res["response"] = $curlRet; 
        return $res;
    }

    /**
     * @param string $host
     * @param string $uri
     * @return string
     */
    protected function formatUrl($host, $uri) {
        return sprintf("%s%s", $host, $uri);
    }

    /**
     * 格式化 Body
     * @param $parameters
     * @return string
     */
    protected function formatBody($parameters) {
        $parameterStrings = array();
        foreach ($parameters as $k => $v) {
            $parameterStrings[] = HttpUtils::urlEncode($k)
                . '=' . HttpUtils::urlEncode((string) $v);
        }

        return implode('&', $parameterStrings);
    }

    /**
     * @param $account
     * @return bool
     */
    public static function isValidAccount($account) {
        if (!isset($account['address']) || !isset($account['private_key']) || !isset($account['public_key'])) {
            return false;
        }
        if (empty($account['address']) || empty($account['private_key']) || empty($account['public_key'])) {
            return false;
        }
        return true;
    }

    /**
     * 格式化 QueryString
     * @param array $parameters
     * @return array
     */
    private function formatQueryString($parameters) {
        $parameterArr = array();
        foreach ($parameters as $k => $v) {
            $parameterArr[HttpUtils::urlEncode($k)] = HttpUtils::urlEncode((string) $v);
        }

        return $parameterArr;
    }
} 

