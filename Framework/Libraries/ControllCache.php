<?php
/**
 * 框架缓存基类
 * 主要完成key规则管理和约束
 * 继承类需要覆盖$key_sets值，每组数据配置rule和expire
 *
 * key规则最好是加上namespace和类名以防冲突
 * 框架上不做这块是因为想允许通过mc进行不同应用间的数据交互
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
abstract class ControllCache extends KeyBuilder {
    const CACHE_TYPE_REDIS   = 'redis';
    const CACHE_TYPE_MC      = 'mc';
    private $_config_name    = '';
    private $_module         = '';
    private $_is_use_cache   = true;
    private $_last_instance  = null;
    private static $INSTANCE = array();
    public $key_sets         = array(
        'demo' => array('rule' => 'Framework\ControllCache->demo_id:{id}:{name}', 'expire' => 2) //这是个样例
    );
    /**
     * 构造函数
     * @param string      $config_name 配置名称
     * @param string|null $module
     */
    public function __construct(string $module = null) {
        if (empty($module)) {
            $tmp                 = get_class($this);
            list($module, $null) = explode('\\', $tmp, 2);
        }
        $this->_module = $module;
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
     * 魔术方法
     * @param  string $fun_name
     * @param  array  $params
     * @return mix
     */
    public function __call($fun_name, $params) {
        if (empty($this->_last_instance) || ! $this->_is_use_cache) {
            return false;
        }
        return $this->_last_instance->$fun_name(...$params);
    }
    public function getInstance(string $config_name, string $type = self::CACHE_TYPE_REDIS, bool $re_contect = false) {
        $cache_key = $config_name . $type;
        if ( ! $re_contect && isset(self::$INSTANCE[$cache_key])) {
            $this->_last_instance = self::$INSTANCE[$cache_key];
            return $this;
        }
        switch ($type) {
            case self::CACHE_TYPE_REDIS:
                self::$INSTANCE[$cache_key] = new Redis($config_name, $this->_module);
                break;
            case self::CACHE_TYPE_MC:
                self::$INSTANCE[$cache_key] = new Memcached($config_name, $this->_module);
                break;
            default:
                return false;
        }
        $this->_last_instance = self::$INSTANCE[$cache_key];
        return $this;
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