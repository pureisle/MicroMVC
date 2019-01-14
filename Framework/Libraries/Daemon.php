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
    /**
     * 构造函数
     * @param array $params 脚本运行时传入的参数列表
     */
    public function __construct($params) {}
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
        $sh_ret      = trim(shell_exec($sh_str));
        $sh_ret_arr  = explode("\n", $sh_ret);
        return $sh_ret_arr;
    }
}