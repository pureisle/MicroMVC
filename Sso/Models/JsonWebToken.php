<?php
/**
 * JWT类
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Sso\Models;

class JsonWebToken {
    const SEPARATOR         = '.';
    const DEFAULT_ALGORITHM = 'default';
    public function __construct() {}
    /**
     * 获取jwt token
     */
    public static function sign(array $payload, string $algorithm = self::DEFAULT_ALGORITHM) {
        $header = array(
            'alg' => $algorithm,
            'typ' => 'JWT'
        );
        return self::encode($header, $payload, $algorithm);
    }
    /**
     * 验证jwt token
     * @param  string   $access_token
     * @return [type]
     */
    public static function verify(string $access_token) {
        return self::decode($access_token);
    }
    /**
     * 获取签名
     * @param    string $header_encode
     * @param    string $payload_encode
     * @param    string $algorithm
     * @return
     */
    public static function getSignature(string $header_encode, string $payload_encode, string $algorithm) {
        switch ($algorithm) {
            case self::DEFAULT_ALGORITHM:
                $tmp = $header_encode . self::SEPARATOR . $payload_encode;
                $ret = md5($tmp . "this's is a simple salt."); //简单样例
                break;
            default:
                $ret = false;
                break;
        }
        return $ret;
    }
    /**
     * jwt 编码
     */
    public static function encode(array $header, array $payload, string $algorithm = self::DEFAULT_ALGORITHM) {
        $header_encode  = self::base64UrlEncode($header);
        $payload_encode = self::base64UrlEncode($payload);
        $signature      = self::getSignature($header_encode, $payload_encode, $algorithm);
        return $header_encode . self::SEPARATOR . $payload_encode . self::SEPARATOR . $signature;
    }
    /**
     * jwt 解码
     * @param    string $str
     * @return
     */
    public static function decode(string $str) {
        list($header_encode, $payload_encode, $signature) = explode(self::SEPARATOR, $str);
        if (empty($header_encode) || empty($payload_encode) || empty($signature)) {
            return false;
        }
        $header           = self::base64UrlDecode($header_encode);
        $payload          = self::base64UrlDecode($payload_encode);
        $target_signature = self::getSignature($header_encode, $payload_encode, $header['alg']);
        if ($target_signature !== $signature) {
            return false;
        }
        return array('header' => $header, 'payload' => $payload, 'signature' => $signature);
    }
    /**
     * base64 url encode
     * @param    array $data
     * @return
     */
    public static function base64UrlEncode(array $data) {
        $data = http_build_query($data);
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    /**
     * base64 url decode
     * @param    string $data
     * @return
     */
    public static function base64UrlDecode(string $data) {
        $tmp = base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
        $ret = array();
        parse_str($tmp, $ret);
        return $ret;
    }
}