<?php
/**
 * 应用初始化基类
 *
 * 继承类只需要添加 _init开头的成员方法即可。父类进行初始化方法的顺序调用。
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Models;
class Bootstrap {
    /**
     * 执行继承类的所有_init前缀方法
     */
    public function __construct(Dispatcher $dispatcher) {
        $class_name    = get_called_class();
        $class_methods = get_class_methods($class_name);
        foreach ($class_methods as $method) {
            if ( ! $this->_startWith($method, "_init")) {
                continue;
            }
            $this->$method($dispatcher);
        }
    }
    /**
     * 判断字符串是否具有某前缀
     */
    private function _startWith($str, $prefix) {
        $start_len = strlen($prefix);
        return strlen($str) >= $start_len && substr($str, 0, $start_len) == $prefix;
    }
}
