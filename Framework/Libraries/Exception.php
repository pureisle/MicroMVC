<?php
/**
 * 框架异常基类
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
abstract class Exception extends \Exception {
    /**
     * 继承异常都要覆盖该变量
     * 静态错误集合
     */
    const ERROR_CODE_DEMO = 0;
    public $ERROR_SET     = array(
        self::ERROR_CODE_DEMO => array(
            'code'    => self::ERROR_CODE_DEMO,
            'message' => 'error code demo message'
        )
    );
    public function __construct($code = 0, $msg = null) {
        $tmp = @$this->ERROR_SET[$code]['message'];
        if (isset($msg)) {
            $tmp = $msg;
        }
        parent::__construct($tmp, $code);
    }
    // 自定义字符串输出的样式
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}