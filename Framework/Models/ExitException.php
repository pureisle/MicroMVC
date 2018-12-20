<?php

/**
 * 程序安全退出使用的异常
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Models;
use Framework\Libraries\Exception;

class ExitException extends Exception {
    const ERROR_FUNCTION_NOT_EXIST = 1;
    public $ERROR_SET              = array(
        self::ERROR_FUNCTION_NOT_EXIST => array(
            'code'    => self::ERROR_FUNCTION_NOT_EXIST,
            'message' => 'function not found'
        )
    );
}