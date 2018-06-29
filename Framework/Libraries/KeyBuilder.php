<?php
/**
 * key构建和管理基类
 * 主要完成key规则管理和约束
 * 继承类需要覆盖$key_sets值，每组数据配置rule和expire
 *
 * key规则最好是加上namespace和类名以防冲突
 * 框架上不做这块是因为想允许通过mc等进行不同应用间的数据交互
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
class KeyBuilder {
    public $key_sets = array(
        'demo' => array('rule' => 'xxx\xxx\->{id}', 'expire' => 2) //这是个样例
    );
    /**
     * 构造key
     * @param  string   $text
     * @param  array    $param
     * @return string
     */
    public function buildKey(string $key_sets_index, array $param) {
        if ( ! isset($key_sets_index) || empty($this->key_sets[$key_sets_index])) {
            throw new KeyBuilderException(KeyBuilderException::KEY_RULE_STRING_EMPTY);
        }
        if (empty($param)) {
            $ret = $this->key_sets[$key_sets_index]['rule'];
        } else {
            $ret = $this->_interpolate($this->key_sets[$key_sets_index]['rule'], $param);
        }
        return $ret;
    }
    /**
     * Interpolates context values into the message placeholders.
     */
    private function _interpolate(string $message, array $context = array()) {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val) {
            // check that the value can be casted to string
            if ( ! is_array($val) && ( ! is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
}

class KeyBuilderException extends Exception {
    const KEY_RULE_STRING_EMPTY = 1;
    public $ERROR_SET           = array(
        self::KEY_RULE_STRING_EMPTY => array(
            'code'    => self::KEY_RULE_STRING_EMPTY,
            'message' => 'key rule string empty'
        )
    );
    public function __construct($code) {
        parent::__construct($code);
    }
}