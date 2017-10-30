<?php
/**
 * application异常类
 * 单独写出来是因为脚本加载Application.php解释脚本的时候autoload没有注册无法工作
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Models;
use Framework\Libraries\Exception;

class ApplicationException extends Exception {
    const ERROR_FUNCTION_NOT_EXIST = 1;
    public $ERROR_SET              = array(
        self::ERROR_FUNCTION_NOT_EXIST => array(
            'code'    => self::ERROR_FUNCTION_NOT_EXIST,
            'message' => 'function not found'
        )
    );
}