<?php
/**
 * 环境变量临时存储类
 *
 * 有时候确实需要从Controller向Model或其他底层传递参数，这或许是一种方便的方式。
 *
 * @author zhiyuan
 */
namespace Framework\Libraries;
class Context {
    public static $G   = null;
    private $_data_set = array();
    public function __construct() {}
    public function get(string $name) {
        return $this->_data_set[$name];
    }
    public function set(string $name, $value) {
        $this->_data_set[$name] = $value;
        return $this;
    }
    /**
     * 获取多个变量值
     * @param  array   $names
     * @return array
     */
    public function mGet(array $names) {
        $tmp = array();
        foreach ($names as $key) {
            $tmp[$key] = $this->$key;
        }
        return $tmp;
    }
    public function mSet(array $data) {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
        return $this;
    }
    public function __set(string $name, $value) {
        $this->_data_set[$name] = $value;
    }
    public function __get(string $name) {
        return $this->_data_set[$name];
    }
    public function __isset($name) {
        return isset($this->_data_set[$name]);
    }
    public function __unset($name) {
        unset($this->_data_set[$name]);
    }
    public function toArray() {
        return $this->_data_set;
    }
}
Context::$G = new Context();