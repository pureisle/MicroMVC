<?php
/**
 * api统一输出类
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Sso\Models;

class ApiDisplay {
    const SUCCESS_CODE           = 0;
    const FAIL_CODE              = 1;
    const PARAM_ERROR_CODE       = 2;
    const PASSWD_CHECK_FAIL      = 3;
    public static $RETURN_STRUCT = array(
        self::SUCCESS_CODE      => array(
            'code' => self::SUCCESS_CODE,
            'msg'  => 'success'
        ),
        self::FAIL_CODE         => array(
            'code' => self::FAIL_CODE,
            'msg'  => 'operation failure'
        ),
        self::PARAM_ERROR_CODE  => array(
            'code' => self::PARAM_ERROR_CODE,
            'msg'  => 'param error'
        ),
        self::PASSWD_CHECK_FAIL => array(
            'code' => self::PASSWD_CHECK_FAIL,
            'msg'  => 'name or passwd error'
        )
    );
    public function __construct() {}
    public static function display(int $code, array $result = array()) {
        if ( ! isset(self::$RETURN_STRUCT[$code])) {
            return false;
        }
        $ret         = self::$RETURN_STRUCT[$code];
        $ret['data'] = $result;
        header('Content-Type: application/json;charset=UTF-8');
        echo json_encode($ret);
    }
}