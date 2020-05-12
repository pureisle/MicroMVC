<?php
/**
 * 框架缓存基类
 * 主要完成key规则管理和约束
 * 继承类需要覆盖$key_sets值，每组数据配置rule和expire
 *
 * key规则最好是加上namespace和类名以防冲突
 * 框架上不做这块是因为想允许通过mc进行不同应用间的数据交互
 *
 * 提供内存、本地文件、远端mc或redis三级缓存:
 *     内存缓存在当前进程内有效；
 *     本地文件在当前运行环境内有效（一般为程序运行服务器）；
 *     mc或redis，即常规远端缓存;
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
abstract class ControllCache extends KeyBuilder {
    const CACHE_TYPE_REDIS              = 'redis';      //redis缓存
    const CACHE_TYPE_MC                 = 'mc';         //mc缓存
    const CACHE_TYPE_MEM                = 'mem';        //进程内缓存
    const CACHE_TYPE_LOCAL_FILE         = 'local_file'; //本地文件缓存
    private $_config_suffix             = ConfigTool::FILE_SUFFIX;
    private $_config_name               = '';
    private $_module                    = '';
    private $_is_use_cache              = true;
    private static $_global_cache_close = false;
    private $_last_instance             = null;
    private $_last_type                 = self::CACHE_TYPE_MEM;
    private $_is_reconnect              = false;
    private static $INSTANCE            = array();
    public $key_sets                    = array(
        'demo' => array('rule' => 'Framework\ControllCache->demo_id:{id}:{name}', 'expire' => 2) //这是个样例
    );
    /**
     * 构造函数
     */
    public function __construct() {
        $tmp                 = get_class($this);
        list($module, $null) = explode('\\', $tmp, 2);
        $this->_module       = $module;
    }
    /**
     * 设置配置文件后缀，默认为  .php  , 需要加 . 号
     * @param string $suffix = .php
     */
    protected function setConfigSuffix(string $suffix) {
        $this->_config_suffix = $suffix;
        return $this;
    }
    /**
     * 启用cache
     */
    public function enableCache() {
        $this->_is_use_cache = true;
        return $this;
    }
    /**
     * 禁用cache
     */
    public function disableCache() {
        $this->_is_use_cache = false;
        return $this;
    }
    /**
     * 全局禁用缓存
     */
    public function disableAllCache() {
        self::$_global_cache_close = true;
        return $this;
    }
    /**
     * 开启全局cache
     */
    public function enableAllCache() {
        self::$_global_cache_close = false;
        return $this;
    }
    /**
     * 魔术方法
     * @param  string $fun_name
     * @param  array  $params
     * @return mix
     */
    public function __call($fun_name, $params) {
        if (empty($this->_last_instance) || ! $this->_is_use_cache || self::$_global_cache_close) {
            return false;
        }
        return $this->_last_instance->$fun_name(...$params);
    }
    /**
     * $type 主要是三类四种：
     *     1、mem 进程内缓存；
     *     2、local_file 本地文件缓存；
     *     3、mc 或 redis  远端缓存;
     * @param $type 设置缓存类型
     */
    public function getInstance(string $config_name, string $type = self::CACHE_TYPE_REDIS, bool $re_contect = false) {
        $cache_key = $config_name . $type;
        if ( ! $re_contect && isset(self::$INSTANCE[$cache_key])) {
            $this->_last_instance = self::$INSTANCE[$cache_key];
            return $this;
        }
        switch ($type) {
            case self::CACHE_TYPE_REDIS:
                self::$INSTANCE[$cache_key] = new Redis($config_name, $this->_module, $this->_config_suffix);
                break;
            case self::CACHE_TYPE_MC:
                self::$INSTANCE[$cache_key] = new Memcached($config_name, $this->_module, $this->_config_suffix);
                break;
            case self::CACHE_TYPE_LOCAL_FILE:
                self::$INSTANCE[$cache_key] = new FileCache($config_name, $this->_module);
                break;
            case self::CACHE_TYPE_MEM:
                self::$INSTANCE[$cache_key] = new Context();
                break;
            default:
                return false;
        }
        $this->_last_instance = self::$INSTANCE[$cache_key];
        return $this;
    }
}

class FileCache {
    private $_cache_path = '';
    public function __construct($prefix_name, $module) {
        $this->_cache_path = LOG_ROOT_PATH . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . $prefix_name;
        if ( ! is_dir($this->_cache_path)) {
            mkdir($this->_cache_path, 0777, true);
        }
    }
    public function get(string $name) {
        $tmp = json_decode(file_get_contents(self::getCachePath($name)), true);
        if (empty($tmp['expire']) || $tmp['expire'] > time()) {
            return $tmp['data'];
        }
        return false;
    }
    public function set(string $name, $value, int $expire = 0) {
        $tmp = array(
            'data'   => $value,
            'expire' => empty($expire) ? 0 : (time() + $expire)
        );
        file_put_contents(self::getCachePath($name), json_encode($tmp));
        return $this;
    }
    public function mGet(array $names) {
        $tmp = array();
        foreach ($names as $name) {
            $tmp[$name] = $this->get($name);
        }
        return $tmp;
    }
    public function mSet(array $data, int $expire = 0) {
        foreach ($data as $name => $value) {
            $this->set($name, $value, $expire);
        }
        return $this;
    }
    public function getCachePath(string $name) {
        $tmp = $this->_cache_path . DIRECTORY_SEPARATOR . $name;
        return $tmp;
    }
}

class ControllCacheException extends Exception {
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