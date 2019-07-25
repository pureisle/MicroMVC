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
    private $_data_set              = array();
    private $_is_altered            = false;
    public function __construct(array $data = array()) {
        $this->ini($data);
        $this->setAltered(false);
    }
    public function ini(array $data = array()) {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
        return $this;
    }
    /**
     * 检查实体是否变更
     * @return boolean
     */
    public function isAltered() {
        return $this->_is_altered;
    }
    /**
     * 设置实体变更
     * 例如：如果调用过实体save保存，就应该重置该值
     * @param bool $bool
     */
    public function setAltered($bool) {
        $this->_is_altered = $bool;
        return $this;
    }
    public function toArray() {
        $tmp        = array();
        $class_name = get_class($this);
        foreach ($class_name::$DATA_STRUCT_INFO as $key => $value) {
            $tmp[$key] = $this->_data_set[$key];
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
        $this->setAltered(true);
        $this->_data_set[$name] = $value;
    }
    public function __get(string $name) {
        if ( ! isset($this->_data_set[$name])) {
            $class_name             = get_class($this);
            $this->_data_set[$name] = $class_name::$DATA_STRUCT_INFO[$name];
        }
        return $this->_data_set[$name];
    }
    public function __isset($name) {
        return isset($this->_data_set[$name]);
    }
    public function __unset($name) {
        $this->setAltered(true);
        unset($this->_data_set[$name]);
    }
}