<?php
/**
 * Memcached类单元测试
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Tests;
use Framework\Libraries\Memcached;
use Framework\Libraries\TestSuite;

class TestMemcached extends TestSuite {
    private $_mc = null;
    public function beginTest() {
        $this->_mc  = new Memcached('mc.business_a', 'Demo');
        $this->_mc  = new Memcached('mc.business_a', 'Demo');
        $this->_mc  = new Memcached('mc.business_a', 'Demo');
        $key        = 'test';
        $value      = 'test';
        $expiration = 3;
        $tmp        = $this->_mc->set($key, $value, $expiration);
        $ret        = $this->_mc->get($key, null, $cas);
        $ret_code   = $this->_mc->getResultCode();
        $this->assertTrue(Memcached::RES_SUCCESS == $ret_code || Memcached::RES_NOTFOUND == $ret_code);
    }
}