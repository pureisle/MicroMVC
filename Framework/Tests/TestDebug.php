<?php
/**
 * Debug类单元测试
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Tests;
use Framework\Libraries\Debug;
use Framework\Libraries\TestSuite;

class TestDebug extends TestSuite {
    const TEST_CLASS_NAME = \Framework\Libraries\Debug::class;
    /**
     * 测试设置和获取方法
     */
    public function testSetDebug() {
        $ret = Debug::getDebug();
        $this->assertFalse($ret);

        $ret = Debug::setDebug(true);
        $this->assertTrue($ret);
        $ret = Debug::getDebug();
        $this->assertTrue($ret);

        $ret           = Debug::setDebug(false);
        $_GET['debug'] = true;
        $ret           = Debug::getDebug();
        $this->assertTrue($ret);
        unset($_GET['debug']);
    }
    /**
     * 测试 调试输出方方法
     */
    public function testDebugDump() {
        $test_string = 'this is test string.';
        ob_start();
        Debug::debugDump($test_string);
        $dump = ob_get_contents();
        ob_end_clean();
        $this->assertEq($dump, "");

        Debug::setDebug(true);
        ob_start();
        Debug::debugDump($test_string);
        $dump = ob_get_contents();
        ob_end_clean();
        $this->assertMatch('~' . $test_string . '~', $dump);
        Debug::setDebug(false);

    }
    public function testsetErrorMessage() {
        $ret = Debug::setErrorMessage(1, 'test');
        $this->assertTrue($ret);
    }
    public function testgetErrorMessage() {
        $ret = Debug::getErrorMessage();
    }
}