<?php
/**
 * 调试类
 *
 * setDebug()方法可以设置是否开启debug模式，getDebug()获取debug模式信息
 * 浏览器合适增加get类型的参数debug也可控制debug模式
 *
 * TO DO: 按ERROR || DEBUG 级别记录日志
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
class Debug {
    private static $_debug         = false;
    private static $_error_message = null;
    /**
     * 调试输出
     *
     * @param  string $data
     * @param  bool   $is_force=false,是否无视生产机打印调试输出
     * @return null
     */
    public static function debugDump($data, $type = 'DEBUG') {
        if ( ! self::getDebug()) {
            return false;
        }
        $debug_info = debug_backtrace();
        $line       = '';
        $call       = '';
        foreach ($debug_info as $one) {
            if (__CLASS__ != $one['class']) {
                $call = $one;
                break;
            }
            $line = $one['line'];
        }
        $res = date("Y-m-d H:i:s") . ' [' . $type . '] ' . $call['class'] . $call['type'] . $call['function'] . ' line [' . $line . "] :\n";
        echo $res;
        var_dump($data);
        return $res;
    }
    /**
     * 获取当前DEBUG模式
     *
     */
    public static function getDebug() {
        if (Tools::ENV_PRO === Tools::getEnv()) {
            return false;
        }
        return self::$_debug || (isset($_GET['debug']) && 'true' == $_GET['debug']);
    }
    /**
     * 设置当前DEBUG模式
     */
    public static function setDebug($debug) {
        if ( ! is_bool($debug)) {
            return false;
        }
        self::$_debug = $debug;
        return true;
    }
    /**
     *  设置错误信息,debug开启模式下会输出错误
     */
    public static function setErrorMessage($error_code, $error_msg) {
        self::$_error_message = array(
            'error_code' => $error_code,
            'error_msg'  => $error_msg
        );
        self::debugDump(self::$_error_message);
        return true;
    }
    /**
     * 获取当前错误信息
     */
    public static function getErrorMessage() {
        return self::$_error_message;
    }
    /**
     * 输出错误信息并退出
     */
    public static function echoErrorMessage($error_message, $is_exit = true) {
        var_dump($error_message);
        $is_exit && exit();
    }
}