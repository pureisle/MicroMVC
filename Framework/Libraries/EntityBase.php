<?php
/**
 * 实体基类
 * 子类继承需要覆盖静态成员$DATA_STRUCT_INFO
 * $DATA_STRUCT_INFO=array(
 *     'key'=>'default value'  //合法键值 => 默认值
 * )
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
abstract class EntityBase {
    public static $DATA_STRUCT_INFO = array();
    public function __construct(array $data = array()) {
        $this->ini($data);
    }
    public function ini(array $data = array()) {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
        return $this;
    }
    public function toArray() {
        $tmp        = array();
        $class_name = get_class($this);
        foreach ($class_name::$DATA_STRUCT_INFO as $key => $value) {
            $tmp[$key] = $this->$key;
        }
        return $tmp;
    }
    /**
     * 默认值配置
     * 子类必须定义静态成员变量：public static $DATA_STRUCT_INFO = array();
     */
    public function __set(string $name, $value) {
        $class_name = get_class($this);
        if ( ! isset($class_name::$DATA_STRUCT_INFO[$name])) {
            return false;
        }
        $this->$name = $value;
    }
    public function __get(string $name) {
        if ( ! isset($this->$name)) {
            $class_name  = get_class($this);
            $this->$name = $class_name::$DATA_STRUCT_INFO[$name];
        }
        return $this->$name;
    }
}