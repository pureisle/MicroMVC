<?php
/**
 * session缓存管理
 */
namespace Sso\Cache;
use Framework\Libraries\ControllCache;
use Sso\Models\Session as SessionModel;

class Session extends ControllCache {
    const READ_CONFIG_NAME  = 'redis:session_read';
    const WRITE_CONFIG_NAME = 'redis:session';
    const SESSION_KEY_INDEX = 0;
    public $key_sets        = array(
        self::SESSION_KEY_INDEX => array('rule' => 'Sso\Cache->Session_id:{id}', 'expire' => 72000)
    );
    public function __construct() {
        parent::__construct();
    }
    public function get($id) {
        $key   = $this->buildKey(self::SESSION_KEY_INDEX, array('id' => $id));
        $redis = $this->getInstance(self::READ_CONFIG_NAME, self::CACHE_TYPE_MC);
        $ret   = parent::get($key);
        if (false === $ret) {
            $t   = new SessionModel();
            $ret = $t->read($id);
            if (false !== $ret) {
                $this->set($id, $ret);
            }
        }
        return $ret;
    }
    public function set($id, $value) {
        $key   = $this->buildKey(self::SESSION_KEY_INDEX, array('id' => $id));
        $redis = $this->getInstance(self::WRITE_CONFIG_NAME, self::CACHE_TYPE_MC);
        return parent::set($key, $value, $this->key_sets[self::SESSION_KEY_INDEX]['expire']);
    }
}
