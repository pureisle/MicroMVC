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
    private $_instance = '';
    public $key_sets   = array(
        'demo' => array('rule' => 'Framework\ControllCache->demo_id:{id}:{name}', 'expire' => 2) //这是个样例
    );
    /**
     * 构造函数
     * @param string      $config_name 配置名称
     * @param string|null $module
     */
    public function __construct(string $config_name, string $module = null) {
        if (empty($module)) {
            $tmp                 = get_class($this);
            list($module, $null) = explode('\\', $tmp, 2);
        }
        $this->_instance = new Memcached($config_name, $module);
    }
    /**
     * 获取缓存
     * @param  string        $key_sets_index_key
     * @param  array         $params
     * @param  callback|null $cache_cb
     * @param  float|null    &$cas_token
     * @return mix
     */
    protected function get(string $key_sets_index_key, array $params, $cache_cb = null, float &$cas_token = null) {
        $key = $this->buildKey($key_sets_index_key, $params);
        var_dump($key, $params);
        return $this->_instance->get($key, $cache_cb, $cas_token);
    }
    /**
     * 设置缓存
     * @param string $key
     * @param mixed  $value
     * @param int    $expire  单位秒，0为永不过期，不能超过30天的秒数
     */
    protected function set(string $key_sets_index_key, array $params, $value) {
        $key = $this->buildKey($key_sets_index_key, $params);
        return $this->_instance->set($key, $value, $this->key_sets['expire']);
    }
    /**
     * 获取多个key值
     * @param  array      $keys          值规则 array(key_index1=>$params1,key_index2=>$params2)
     * @param  array|null &$cas_tokens
     * @return array
     */
    protected function getMulti(array $key_array, array &$cas_tokens = null) {
        $keys = array();
        foreach ($key_array as $key_index => $params) {
            $keys[] = $this->buildKey($this->key_sets[$key_index], $params);
        }
        return $this->_instance->getMulti($keys, $cas_tokens);
    }
    /**
     * 设置多个key值
     * @param array $items
     * @param int   $expiration
     */
    protected function setMulti(array $items, int $expiration) {}
    /**
     * 在原值后追加字符串
     * @param    string $key
     * @param    string $value
     * @return
     */
    protected function append(string $key_sets_index_key, array $params, string $value) {
        $key = $this->buildKey($key_sets_index_key, $params);
        return $this->_instance->append($key, $value);
    }
    /**
     * 在原值前追加内容
     * @param    string $key
     * @param    string $value
     * @return
     */
    protected function prepend(string $key_sets_index_key, array $params, string $value) {
        $key = $this->buildKey($key_sets_index_key, $params);
        return $this->_instance->prepend($key, $value);
    }
    /**
     * 自增
     * @param    string      $key
     * @param    int|integer $offset
     * @return
     */
    protected function decrement(string $key_sets_index_key, array $params, int $offset = 1) {
        $key = $this->buildKey($key_sets_index_key, $params);
        return $this->_instance->decrement($key, $offset);
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