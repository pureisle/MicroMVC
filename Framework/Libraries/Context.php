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
    private $_data_set = array();
    /**
     * 获取多个变量值
     * @param  array   $names
     * @return array
     */
    public function mGet(array $names) {
        return array_intersect_key($this->_data_set, array_flip($names));
    }
    public function mSet(array $data) {
        $this->_data_set = array_merge($this->_data_set, $data);
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