<?php
/**
 * 控制器验证类
 *
 * 框架提供的Controller类接口的验证工具
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Models;
use Framework\Libraries\Tools;

final class ControllerAuth {
    const SIGNATURE_AUTH_TYPE = 0;
    const WHITE_IP_AUTH_TYPE  = 1;
    public function __construct() {}
    /**
     * 根据配置信息检验合法性
     * @param    array  $config
     * @param    array  $params
     * @param    [type] &$error
     * @return
     */
    public static function check(array $config, array $params, &$error) {
        $ret   = true;
        $error = '';
        //检查签名sign
        if ($config['use_sign']) {
            $app_key    = $config['app_key'];
            $app_secret = $config['app_secret'];
            $valid_time = $config['valid_time'];
            $ret        = self::signatureCheck($app_key, $app_secret, $params, $valid_time);
            $error      = 'sign check fail';
        }
        //检查ip
        if ($ret && isset($config['white_ips']) && ! empty($config['white_ips'])) {
            $ip     = Tools::getClientIp();
            $ip_set = explode(',', $config['white_ips']);

            $ret = self::ipCheck($ip_set, $ip);
            if ( ! $ret) {
                $error = 'ip check fail';
            } else {
                $error = '';
            }
        }
        return $ret;
    }
    /**
     * 检验签名
     * @param    string $app_key
     * @param    string $app_secret
     * @param    array  $params
     * @return
     */
    public static function signatureCheck(string $app_key, string $app_secret, array $params, int $valid_time = 0) {
        if ( ! isset($params['app_key']) || ! isset($params['app_sign']) || $app_key != $params['app_key']) {
            return false;
        }
        $res_sign = $params['app_sign'];
        unset($params['app_sign']);
        unset($params['app_key']);
        $params['app_secret'] = $app_secret;
        $tmp                  = array();
        foreach ($params as $k => $v) {
            $tmp[] = $k . '=' . $v;
        }
        if ($valid_time > 0) {
            $current_time = intval(time() / 10);
            $sign_array   = array();
            for ($i = 0; $i < $valid_time; $i++) {
                $sign_array[] = self::getSign(array_merge($tmp, array('app_time=' . ($current_time - $i))));
            }
            return in_array($res_sign, $sign_array);
        } else {
            $sign = self::getSign($tmp);
            return $res_sign == $sign;
        }
    }
    /**
     * 获取参数签名
     * @param    array $params
     * @return
     */
    public static function getSign(array $params) {
        sort($params);
        $params_str = implode('&', $params);
        $sign       = substr(md5($params_str), 0, 6);
        return $sign;
    }
    /**
     * 检验ip
     * @param    array  $ip_set
     * @param    string $ip
     * @return
     */
    public static function ipCheck(array $ip_set, string $ip) {
        foreach ($ip_set as $ip_check) {
            list($subnet, $mask) = explode('/', $ip_check);
            $ip_field            = explode('.', trim($subnet, '.'));
            $count               = count($ip_field);
            if (empty($mask)) {
                $mask = $count * 8;
            }
            if ($count < 4) {
                for ($i = 4; $i > $count; $i--) {
                    $subnet .= ".0";
                }
            }
            $ret = Tools::cidrMatch($ip, $subnet . "/" . $mask);
            if ($ret) {
                return true;
            }
        }
        return false;
    }
}