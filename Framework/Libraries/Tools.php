<?php
/**
 * 常用工具类
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
class Tools {
    /**************** 网络相关 *****************/
    /**
     * ip cidr 匹配
     * from :  https://github.com/tholu/php-cidr-match/blob/master/CIDRmatch/CIDRmatch.php
     * @param    string $ip
     * @param    string $cidr
     * @return
     */
    public static function cidrMatch(string $ip, string $cidr) {
        list($subnet, $mask) = explode('/', $cidr);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return self::isIPv4SameSubnet($ip, $subnet, $mask);
        } else if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return self::IPv6Match($ip, $subnet, $mask);
        } else {
            return false;
        }
    }
    // inspired by: http://stackoverflow.com/questions/7951061/matching-ipv6-address-to-a-cidr-subnet
    public static function IPv6Match($address, $subnetAddress, $subnet_mask) {
        $subnet  = inet_pton($subnetAddress);
        $addr    = inet_pton($address);
        $binMask = self::IPv6MaskToByteArray($subnet_mask);
        return ($addr & $binMask) == $subnet;
    }
    // inspired by: http://stackoverflow.com/questions/7951061/matching-ipv6-address-to-a-cidr-subnet
    public static function IPv6MaskToByteArray($subnet_mask) {
        $addr = str_repeat("f", $subnet_mask / 4);
        switch ($subnet_mask % 4) {
            case 0:
                break;
            case 1:
                $addr .= "8";
                break;
            case 2:
                $addr .= "c";
                break;
            case 3:
                $addr .= "e";
                break;
        }
        $addr = str_pad($addr, 32, '0');
        $addr = pack("H*", $addr);
        return $addr;
    }
    // inspired by: http://stackoverflow.com/questions/594112/matching-an-ip-to-a-cidr-mask-in-php5
    public static function isIPv4SameSubnet($addressA, $addressB, $subnet_mask) {
        return self::getIPv4Subnet($addressA, $subnet_mask) == self::getIPv4Subnet($addressB, $subnet_mask);
    }
    /**
     * 获取所属子网
     * @param  [type] $address        [description]
     * @param  [type] $subnet_mask    [description]
     * @return [type] [description]
     */
    public static function getIPv4Subnet($address, $subnet_mask) {
        $subnet_num = ip2long($address) & ~((1 << (32 - $subnet_mask)) - 1);
        return long2ip($subnet_num);
    }
    /**
     * 获取http客户端请求地址
     * @return ip string or false
     */
    public static function getClientIp() {
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")) {
            $origin_ip = getenv("HTTP_CLIENT_IP");
        } else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")) {
            $origin_ip = getenv("HTTP_X_FORWARDED_FOR");
        } else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")) {
            $origin_ip = getenv("REMOTE_ADDR");
        } else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")) {
            $origin_ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $origin_ip = "unknown";
        }
        $ip = filter_var($origin_ip, FILTER_VALIDATE_IP);
        return $ip;
    }
    /**
     * 获取本机的内网IP
     * @return string
     */
    public static function getServerIp() {
        static $ip = null;
        if ( ! is_null($ip)) {
            return $ip;
        }
        //server变量设置本机IP
        if (isset($_SERVER['SINASRV_INTIP'])) {
            $ip = $_SERVER['SINASRV_INTIP'];
        } else if ( ! empty($_SERVER['SERVER_ADDR'])) {
            $ip = $_SERVER['SERVER_ADDR'];
        } else {
            $result = shell_exec("/sbin/ifconfig eth0");
            if (preg_match_all("/addr:(\d+\.\d+\.\d+\.\d+)/", $result, $match) !== 0) {
                $ip = $match[1][0];
            }
        }
        return $ip;
    }
    /**
     * 获取本机hostname
     * @return string
     */
    public static function getHostName() {
        static $hostname = null;
        if (is_null($hostname)) {
            $tmpstr   = '';
            $fp       = popen("hostname -s", 'r');
            $tmpstr   = trim(fread($fp, 1024));
            $hostname = trim($tmpstr);
            pclose($fp);
        }
        return $hostname;
    }
}