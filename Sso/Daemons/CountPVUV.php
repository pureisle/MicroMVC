<?php
/**
 * 统计每天pv、uv
 */
namespace Sso\Daemons;
use Framework\Libraries\Daemon;

class CountPVUV extends Daemon {
    public function __construct($params) {
        // var_dump($params);
        parent::__construct($params);
    }
    public function init() {
        // 无法处理同时新起相同进程的排重判断
        $num = count($this->getCurrentProcessList());
        if ($num > 1) {
            echo "last CountPVUV task not finish\n";
            exit();
        }
        echo "CountPVUV init\n";
    }
    public function doJob() {
        //时刻检查是否被要求退出任务
        while ( ! $this->isStop()) {
            echo getmypid() . '==' . $this->isStop() . "CountPVUV run\n";
            $this->heartbeat(); //向父类报告心跳
            sleep(5);
        }
        sleep(3);
        echo getmypid() . "CountPVUV finished\n";
    }
    /**
     * 接收到退出信号时，可以在这里做退出准备
     * @param    $signo
     * @param    $siginfo
     * @return
     */
    protected function _termSignalHandler($signo, $siginfo = null) {
        var_dump('_termSignalHandler CountPVUV', $signo, $siginfo);
        parent::_termSignalHandler($signo, $siginfo);
    }
}
