<?php
require_once(XASSET_PATH . 'common/config/HttpHeaders.php');

class HttpUtils
{
    private static $PERCENT_ENCODED_STRINGS;

    public static function __init()
    {
        HttpUtils::$PERCENT_ENCODED_STRINGS = array();
        for ($i = 0; $i < 256; ++$i) {
            HttpUtils::$PERCENT_ENCODED_STRINGS[$i] = sprintf("%%%02X", $i);
        }
        foreach (range('a', 'z') as $ch) {
            HttpUtils::$PERCENT_ENCODED_STRINGS[ord($ch)] = $ch;
        }

        foreach (range('A', 'Z') as $ch) {
            HttpUtils::$PERCENT_ENCODED_STRINGS[ord($ch)] = $ch;
        }

        foreach (range('0', '9') as $ch) {
            HttpUtils::$PERCENT_ENCODED_STRINGS[ord($ch)] = $ch;
        }
        HttpUtils::$PERCENT_ENCODED_STRINGS[ord('-')] = '-';
        HttpUtils::$PERCENT_ENCODED_STRINGS[ord('.')] = '.';
        HttpUtils::$PERCENT_ENCODED_STRINGS[ord('_')] = '_';
        HttpUtils::$PERCENT_ENCODED_STRINGS[ord('~')] = '~';
    }

    /**
     * Normalize a string for use in url path. The algorithm is:
     * <p>
     *
     * <ol>
     *   <li>Normalize the string</li>
     *   <li>replace all "%2F" with "/"</li>
     *   <li>replace all "//" with "/%2F"</li>
     * </ol>
     *
     * <p>
     * Bos object key can contain arbitrary characters, which may result double
     * slash in the url path. Apache Http client will replace "//" in the path
     * with a single '/', which makes the object key incorrect. Thus we replace
     * "//" with "/%2F" here.
     *
     * @param $path string the path string to normalize.
     * @return string the normalized path string.
     * @see #normalize(string)
     */
    public static function urlEncodeExceptSlash($path)
    {
        return str_replace("%2F", "/", HttpUtils::urlEncode($path));
    }

    /**
     * Normalize a string for use in BCE web service APIs. The normalization
     * algorithm is:
     * <p>
     * <ol>
     *   <li>Convert the string into a UTF-8 byte array.</li>
     *   <li>Encode all octets into percent-encoding, except all URI unreserved
     * characters per the RFC 3986.</li>
     * </ol>
     *
     * <p>
     * All letters used in the percent-encoding are in uppercase.
     *
     * @param $value string the string to normalize.
     * @return string the normalized string.
     */
    public static function urlEncode($value)
    {
        $result = '';
        for ($i = 0; $i < strlen($value); ++$i) {
            $result .= HttpUtils::$PERCENT_ENCODED_STRINGS[ord($value[$i])];
        }
        return $result;
    }

    /**
     * @param $parameters array
     * @param $forSignature bool
     * @return string
     */
    public static function getCanonicalQueryString(array $parameters, $forSignature)
    {
        if (count($parameters) == 0) {
            return '';
        }

        $parameterStrings = array();
        foreach ($parameters as $k => $v) {
            if ($forSignature
                    && strcasecmp(HttpHeaders::AUTHORIZATION, $k) == 0) {
                continue;
            }
            if (!isset($k)) {
                throw new \InvalidArgumentException(
                    "parameter key should not be null"
                );
            }
            if (isset($v)) {
                $parameterStrings[] = HttpUtils::urlEncode($k)
                    . '=' . HttpUtils::urlEncode((string) $v);
            } else {
                if ($forSignature) {
                    $parameterStrings[] = HttpUtils::urlEncode($k) . '=';
                } else {
                    $parameterStrings[] = HttpUtils::urlEncode($k);
                }
            }
        }
        sort($parameterStrings);

        return implode('&', $parameterStrings);
    }
}

HttpUtils::__init();
