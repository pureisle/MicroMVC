<?php
/**
 * 框架缓存基类
 */
namespace framework;
abstract class Cache {
    const MC_CACHE_TYPE        = 'mc';
    const REDIS_CACHE_TYPE     = 'redis';
    private $_instance         = '';
    private $_cache_type       = '';
    private $_connect_time_out = '';
    public function __construct($cache_pool_name, $cache_type = self::MC_CACHE_TYPE, $connect_time_out = 1) {}
    private function _connect($cache_pool_name, $cache_type) {}
    public function get($key) {}
    public function set($key, $value, $expire) {
        if (is_array($value)) {
            $value = json_encode($value);
        }
    }
}