<?php
namespace Sso\Tests;
use Framework\Libraries\TestSuite;
use Sso\Cache\Session;

class CacheSession extends TestSuite {
    const TEST_CLASS_NAME = \Sso\Cache\Session::class;
    private $_id          = 'test';
    private $_value       = 'hahah';
    public function testAdd() {
        $t     = new Session();
        $id    = 'test';
        $value = 'hahah';
        $ret   = $t->set($this->_id, $this->_value);
        $this->assertTrue($ret);
    }
    public function testGet() {
        $t   = new Session();
        $ret = $t->get($this->_id);
        $this->assertEq($this->_value, $ret);
    }
    public function testRemove() {
        $t   = new Session();
        $ret = $t->remove($this->_id);
        $this->assertEq($ret, 1);
    }
}