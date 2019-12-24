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
    private static $_data_set = array();
    /**
     * 获取多个变量值
     * @param  array   $names
     * @return array
     */
    public static function mGet(array $names) {
        return array_intersect_key(self::$_data_set, array_flip($names));
    }
    public static function mSet(array $data) {
        self::$_data_set = array_merge(self::$_data_set, $data);
    }
    public function __set(string $name, $value) {
        self::$_data_set[$name] = $value;
    }
    public function __get(string $name) {
        return self::$_data_set[$name];
    }
    public function __isset($name) {
        return isset(self::$_data_set[$name]);
    }
    public function __unset($name) {
        unset(self::$_data_set[$name]);
    }
    public function toArray() {
        return self::$_data_set;
    }
}