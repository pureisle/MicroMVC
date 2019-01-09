<?php
/**
 * 用户session类
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Sso\Models;
use Framework\Libraries\SingletonManager;

class Session {
    private $_expire       = 0;
    const MYSQL_STORE_TYPE = 0;
    const REDIS_STORE_TYPE = 1;
    private $_store_type   = self::MYSQL_STORE_TYPE;
    /**
     * session构造函数
     * @param int|integer $expire session 持续时间
     */
    public function __construct(string $session_name = 'PHPSESSID', int $expire = 3600, int $gc_divisor = 100) {
        if (version_compare(PHP_VERSION, '7.2.0') == -1) {
            ini_set("session.save_handler", "user"); //7.2.0 执行会报错，且高版本不用执行该句即可使用session_set_save_handler
        }
        ini_set("session.gc_probability", 1);
        ini_set("session.gc_divisor", $gc_divisor);
        ini_set("session.name", $session_name);
        $this->_expire = $expire;
    }
    public function open($save_path, $session_name) {
        return true;
    }

    public function close() {
        return true;
    }

    public function read($session_id) {
        if (self::REDIS_STORE_TYPE == $this->_store_type) {
            $cache = SingletonManager::$SINGLETON_POOL->getInstance('\Sso\Data\SessionRedis');
            return $cache->get($session_id);
        } else {
            $s_obj = SingletonManager::$SINGLETON_POOL->getInstance('\Sso\Data\Session');
            $ret   = $s_obj->getById($session_id);
            if ( ! empty($ret['data'])) {
                return $ret['data'];
            }
            return '';
        }
    }

    public function write($session_id, $data) {
        if (empty($data)) {
            return false;
        }
        if (self::REDIS_STORE_TYPE == $this->_store_type) {
            $cache = SingletonManager::$SINGLETON_POOL->getInstance('\Sso\Data\SessionRedis');
            return $cache->set($session_id, $data);
        } else {
            $s_obj  = SingletonManager::$SINGLETON_POOL->getInstance('\Sso\Data\Session');
            $expire = date('Y-m-d H:i:s', time() + $this->_expire);
            return $s_obj->addSession($session_id, $data, $expire) === false ? false : true;
        }
    }

    public function destroy($session_id) {
        if (self::REDIS_STORE_TYPE == $this->_store_type) {
            $cache = SingletonManager::$SINGLETON_POOL->getInstance('\Sso\Data\SessionRedis');
            $ret   = $cache->remove($session_id);
        } else {
            $s_obj = SingletonManager::$SINGLETON_POOL->getInstance('\Sso\Data\Session');
            $tmp   = $s_obj->removeById($id);
        }
        return true;
    }

    public function gc($max_life_time) {
        $s_obj = SingletonManager::$SINGLETON_POOL->getInstance('\Sso\Data\Session');
        $tmp   = $s_obj->removeExpire();
        return true;
    }
}