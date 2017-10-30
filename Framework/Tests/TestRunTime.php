<?php
/**
 * RunTime类单元测试
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Tests;
use Framework\Libraries\RunTime;
use Framework\Libraries\TestSuite;

class TestRunTime extends TestSuite {
    private $_run_time = null;
    private $_key1     = 'test_key1';
    private $_key2     = 'test_key2';
    public function beginTest() {
        $this->_run_time = new RunTime();
    }
    public function testKeyCount() {
        $key = $this->_key1;
        $this->_run_time->start($key);
        $this->_run_time->stop($key);
        $ret = $this->_run_time->spent($key);
        $this->assertFloat($ret[$key]);
    }
    public function testMulitKey() {
        $key = $this->_key2;
        $this->_run_time->start($key);
        $this->_run_time->stop($key);
        $ret = $this->_run_time->spent('');
        $this->assertFloat($ret[$key]);
        $this->assertTrue(isset($ret[$key]) && isset($ret[$this->_key1]));

    }
}