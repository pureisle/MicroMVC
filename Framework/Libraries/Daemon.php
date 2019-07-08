<?php
/**
 * 进程任务管理
 *
 * doJob()方法为子类的任务执行入口，抽象方法，必须实现。
 * init()方法为任务执行前的初始化函数，可以覆盖。
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
abstract class Daemon {
    protected $p_pm_obj = null;
    private $_is_stop   = false;
    /**
     * 构造函数
     * @param array $params 脚本运行时传入的参数列表
     */
    public function __construct($params) {
        pcntl_signal(SIGTERM, array($this, "_termSignalHandler")); //进程停止信号
    }
    /**
     * 设置进程管理类
     * @param $parent_obj
     */
    public function setProcessManager($parent_obj) {
        $this->p_pm_obj = $parent_obj;
        return $this;
    }
    /**
     * 有父类的情况下，报告心跳
     * @return
     */
    public function heartbeat() {
        if ( ! isset($this->p_pm_obj)) {
            return false;
        }
        return $this->p_pm_obj->heartbeat();
    }
    /**
     * 进程时候要停止，建议在 doJob()方法内时常检测该值，该值一旦为true时，需要尽快清理或保存数据退出。
     * @return boolean
     */
    public function isStop() {
        return $this->_is_stop;
    }
    /**
     * 初始化钩子
     * @return
     */
    public function init() {}
    /**
     * 需要实现的任务函数
     * @return
     */
    abstract public function doJob();
    /**
     * 任务执行入口
     * @return
     */
    public function run() {
        $this->init();
        $this->doJob();
    }
    /**
     * 获取当前脚本执行的列表
     */
    public function getCurrentProcessList() {
        $class_name  = trim(get_class($this), '\\');
        $tmp         = explode('\\', $class_name, 3);
        $module      = $tmp[0];
        $file_name   = implode('\\', array_splice($tmp, 2));
        $pattern_str = $module . " " . $file_name;
        $sh_str      = 'ps axu  | grep -v grep | grep "' . $pattern_str . '"';
        $sh_ret      = @trim(shell_exec($sh_str));
        $sh_ret_arr  = explode("\n", $sh_ret);
        return $sh_ret_arr;
    }
    /**
     * 进程退出信号
     * @param    int   $signo
     * @param    array $siginfo
     * @return
     */
    protected function _termSignalHandler($signo, $siginfo = null) {
        $this->_is_stop = true;
    }
}