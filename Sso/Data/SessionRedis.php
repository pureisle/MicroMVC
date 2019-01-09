<?php
/**
 * session缓存管理
 */
namespace Sso\Data;
use Framework\Libraries\ControllCache;

class SessionRedis extends ControllCache {
    const READ_CONFIG_NAME  = 'redis:session_read';
    const WRITE_CONFIG_NAME = 'redis:session';
    const SESSION_KEY_INDEX = 0;
    public $key_sets        = array(
        self::SESSION_KEY_INDEX => array('rule' => 'Sso\Data->Session_id:{id}', 'expire' => 72000)
    );
    public function __construct() {
        parent::__construct();
    }
    public function get($id) {
        $key   = $this->buildKey(self::SESSION_KEY_INDEX, array('id' => $id));
        $redis = $this->getInstance(self::READ_CONFIG_NAME);
        $tmp   = parent::get($key);
        return $tmp;
    }
    public function set($id, $value) {
        $key   = $this->buildKey(self::SESSION_KEY_INDEX, array('id' => $id));
        $redis = $this->getInstance(self::WRITE_CONFIG_NAME);
        return parent::set($key, $value, $this->key_sets[self::SESSION_KEY_INDEX]['expire']);
    }
    public function remove($id) {
        $key   = $this->buildKey(self::SESSION_KEY_INDEX, array('id' => $id));
        $redis = $this->getInstance(self::WRITE_CONFIG_NAME);
        return parent::delete($key);
    }
}
