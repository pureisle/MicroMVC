<?php
/**
 * 自动加载类
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Models;
class AutoLoad {
    private $_load_path_prefix = '';
    public function __construct($load_path_prefix = '') {
        $this->_load_path_prefix = $load_path_prefix;
    }
    /**
     * 注册自动加载方法
     * @return void
     */
    public function register() {
        spl_autoload_register(array($this, 'loadClass'));
    }
    /**
     * 设置加载路径前缀
     * @param striing $path_prefix
     */
    public function setPathPrefix($path_prefix) {
        $this->_load_path_prefix = $path_prefix;
    }
    /**
     * 类加载函数
     * @param  string    $class_name
     * @return boolean
     */
    public function loadClass($class_name) {
        $class_name = ltrim($class_name, '\\');
        $tmp_ns     = explode('\\', $class_name);
        //只加在框架类或者指定app的类库,隔离app间model相互调用
        if (FRAMEWORK_NAME != $tmp_ns[0] && ! empty($this->_load_path_prefix) && $tmp_ns[0] != $this->_load_path_prefix) {
            return false;
        }
        $file_path = self::getFilePath($class_name);
        if (file_exists($file_path)) {
            require_once $file_path;
            return true;
        } else {
            // throw new \Exception('file not exist:' . $file_path);
            return false;
        }
    }
    /**
     * 根据类名获取文件路径
     * @param  string   $class_name
     * @return string
     */
    public static function getFilePath($class_name) {
        $class_name  = ltrim($class_name, '\\');
        $last_ns_pos = strrpos($class_name, '\\');
        if (false === $last_ns_pos) {
            return false;
        }
        $namespace  = substr($class_name, 0, $last_ns_pos);
        $class_name = substr($class_name, $last_ns_pos + 1);
        $file_name  = '';
        if ( ! empty($namespace)) {
            $file_name = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $file_name .= str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';
        $path = ROOT_PATH . DIRECTORY_SEPARATOR . $file_name;
        return $path;
    }
}