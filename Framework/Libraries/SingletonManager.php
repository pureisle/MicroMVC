<?php
/**
 * 类单例 管理底层类
 *
 * 使用的时候直接使用 SingletonManager::$SINGLETON_POOL->getInstance('SongModel'); 即可
 * 如果有特别需求，只想自己使用自己的类实例，则可以实例化new SingletonManager(),
 * 然后调用实例化后的getInstance()方法
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
class SingletonManager {
    public static $SINGLETON_POOL;
    private $_singletons;
    /**
     * 获取一个classname类型的实例，如果实例存在，则直接获取，如果实例不存在则利用多余的参数创建一个
     */
    public function getInstance($class_name) {
        $args = func_get_args();
        $key  = $this->_buildKey($class_name, $args);
        if (isset($this->_singletons[$key])) {
            return $this->_singletons[$key];
        }
        array_shift($args);
        $ret = $this->_createInstance($class_name, $args);
        if ( ! $ret) {
            return false;
        }
        return $this->_singletons[$key] = $ret;
    }
    /**
     * 获取一个classname类型的实例,并在第一次获取时调用$init_function($instance)
     * 如果init_function失败，则该函数返回失败
     */
    public function getInstanceWithInit($class_name, $init_function) {
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        if (empty($args)) {
            $ret = $this->getInstance($class_name);
        } else {
            $ret = $this->getInstance($class_name, $args);
        }
        if ( ! $ret) {
            return false;
        }
        if ( ! $init_function($ret)) {
            return false;
        }
        return $ret;
    }
    /**
     * 实例化一个对象
     *
     * @param  String $class_name
     * @param  Array  $arguments
     * @return Mixed  instance of $class_name
     */
    private function _createInstance($class_name, array $arguments = array()) {
        if (class_exists($class_name)) {
            return new $class_name(...$arguments);
        }
        throw new SingletonManagerException(SingletonManagerException::ERROR_CLASS_NOT_EXIST);
        return false;
    }
    /**
     * 获取一个classname的key
     */
    private function _buildKey($class_name, $args = '') {
        return $class_name . serialize($args);
    }
}
class SingletonManagerException extends Exception {
    const ERROR_CLASS_NOT_EXIST = 1;
    public $ERROR_SET           = array(
        self::ERROR_CLASS_NOT_EXIST => array(
            'code'    => self::ERROR_CLASS_NOT_EXIST,
            'message' => 'class not found'
        )
    );
}
SingletonManager::$SINGLETON_POOL = new SingletonManager();
