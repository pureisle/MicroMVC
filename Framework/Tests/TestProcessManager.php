<?php
/**
 * ProcessManager类单元测试
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Tests;
use Framework\Libraries\ProcessManager;
use Framework\Libraries\TestSuite;

class TestProcessManager extends TestSuite {
    const TEST_CLASS_NAME = \Framework\Libraries\ProcessManager::class;
    public function beginTest() {
        $ret = ProcessManager::getAllChildrenPidList(array(1, 555, 2008));
        // var_dump($ret);
        $ret = ProcessManager::killProcessAndChilds(array(345345), SIGKILL, $error_pid);
        // var_dump($ret, $error_pid);
        $ret = ProcessManager::getResourceInfo(array(1, 2008, 45534));
        // var_dump($ret, $error_pid);

    }
}