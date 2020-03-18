<?php
/**
 * 实体基类
 * 子类继承需要覆盖静态成员$DATA_STRUCT_INFO
 * $DATA_STRUCT_INFO=array(
 *     'key'=>'default value'  //合法键值 => 默认值
 * )
 *
 * 如果子类提供 Validator 类的检查类型，则会在赋值或变更时进行相应检查
 * $DATA_VALIDATOR_INFO=array(
 *     'key'=>'default value'  //合法键值 => 检查字符串
 * )
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
abstract class EntityBase {
    public static $DATA_STRUCT_INFO    = array();
    public static $DATA_VALIDATOR_INFO = array();
    private $_data_set                 = array();
    private $_is_altered               = false;
    public function __construct($data = null) {
        if (isset($data)) {
            $this->ini($data);
            $this->setAltered(false);
        }
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
            $tmp[$key] = $this->$key;
        }
        return $tmp;
    }
    /**
     * 检查对象成员变量中必须存在的值。
     *
     * 只检查判断是否是空字符串或空数组，即当被检查值 为字符串类型且长度为0时 或 为数组类型且为空数组时 报错。
     *
     * @Exception EntityBaseException
     */
    public function checkRequirement() {
        $class_name = get_class($this);
        foreach ($class_name::$DATA_VALIDATOR_INFO as $key => $valid) {
            if (Validator::isRequirement($valid) &&
                (is_string($this->$key) && strlen($this->$key) == 0) ||
                (is_array($this->$key) && empty($this->$key))
            ) {
                throw new EntityBaseException(EntityBaseException::CHECK_ERROR, 'requirement ' . $key . ', but the value empty');
            }
        }
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
        if (isset($this->_data_set[$name]) && $this->_data_set[$name] === $value) {
            return;
        }
        if (isset($class_name::$DATA_VALIDATOR_INFO[$name])) {
            $check = Validator::checkOne($value, $class_name::$DATA_VALIDATOR_INFO[$name], $error_msg, $name);
            if ( ! $check) {
                throw new EntityBaseException(EntityBaseException::CHECK_ERROR, $error_msg);
            }
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

class EntityBaseException extends Exception {
    const CHECK_ERROR = 1;
    public $ERROR_SET = array(
        self::CHECK_ERROR => array(
            'code'    => self::CHECK_ERROR,
            'message' => ''
        )
    );
    public function __construct($code = 0, $ext_msg = '') {
        if ( ! empty($ext_msg)) {
            $this->ERROR_SET[$code]['message'] = $ext_msg;
        }
        parent::__construct($code);
    }
}