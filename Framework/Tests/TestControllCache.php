<?php
/**
 * ControllMysql类单元测试
 *
 * ps:新部署的环境需要根据自身情况修改TestData的配置信息
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Tests;
use Framework\Libraries\ControllCache;
use Framework\Libraries\TestSuite;

class TestControllCache extends TestSuite {
    const TEST_CLASS_NAME = \Framework\Libraries\ControllCache::class;
    private $_key         = 123;
    private $_value       = "444";
    private $t            = null;
    public function beginTest() {
        $this->t = new TestCache('Sso');
        $this->t->getInstance('redis:session');
        $this->t->set($this->_key, $this->_value);
    }
    public function testenable() {
        $this->t->enableCache();
        $ret = $this->t->get($this->_key);
        $this->assertEq($ret, $this->_value);
    }
    public function testdisable() {
        $this->t->disableCache();
        $ret = $this->t->get($this->_key);
        $this->assertEq($ret, false);
        $this->t->enableCache();
        $ret = $this->t->get($this->_key);
        $this->assertEq($ret, $this->_value);
    }
}

class TestCache extends ControllCache {
}