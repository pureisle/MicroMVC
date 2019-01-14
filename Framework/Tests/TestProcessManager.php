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
    public function testRun() {
        $t = new TestPM();
        $t->setMaxProcess(5);
        $t->getJobIdList();
        $t->run();
        // var_dump($t->getJobExecInfo());
        $this->assertEq(count($t->getJobExecInfo()), count($t->my_job_list));
    }
}
class TestPM extends ProcessManager {
    public $my_job_list = array();
    /**
     * run之前基类会初始化调用
     */
    public function init() {
        $this->my_job_list = array(1, 2, 3, 4, 5, 6, 7, 8, 9);
        $this->addJobIdList($this->my_job_list);
    }
    /**
     * 监控任务使用资源状态
     * @param  id     $job_id
     * @param  array  $info
     * @return void
     */
    public function resourceLog($job_id, $info) {}
    /**
     * 子进程执行函数
     * @param  int $job_id     任务id
     * @return int 退出码
     */
    public function childExec($job_id) {
        // echo $job_id . "\n";
        // sleep(1);
    }
    /**
     * 超时检测
     * @param  int       $job_id            任务id
     * @param  timestamp $begin_time        任务开始时间
     * @return boolean   超时返回true
     */
    public function timeOutCheck($job_id, $begin_time) {}
}
