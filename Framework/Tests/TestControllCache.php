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
        $this->t = new TestCache();
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
    public function testMem() {
        $this->t = new TestCache();
        $mem     = $this->t->getInstance('test_mem', ControllCache::CACHE_TYPE_MEM);
        $a       = '123';
        $mem->set('a', $a);
        $tmp = $mem->get('a');
        $this->assertEq($tmp, $a);
        $b = '456';
        $mem->mSet(array('a' => $a, 'b' => $b));
        $tmp = $mem->mGet(array('a', 'b'));
        $this->assertEq($tmp['a'], $a);
        $this->assertEq($tmp['b'], $b);
    }
    public function testFile() {
        $this->t = new TestCache();
        $mem     = $this->t->getInstance('test_file', ControllCache::CACHE_TYPE_LOCAL_FILE);
        $a       = '123';
        $mem->set('a', $a);
        // $mem->disableCache();
        // $mem->disableAllCache();
        $tmp = $mem->get('a');
        $this->assertEq($tmp, $a);
        $b = '456';
        $mem->mSet(array('a' => $a, 'b' => $b));
        $tmp = $mem->mGet(array('a', 'b'));
        $this->assertEq($tmp['a'], $a);
        $this->assertEq($tmp['b'], $b);
        $c = 5555;
        $mem->set('c', $c, 1);
        sleep(1);
        $tmp = $mem->get('c');
        $this->assertEq($tmp, false);
    }
}

class TestCache extends ControllCache {
}