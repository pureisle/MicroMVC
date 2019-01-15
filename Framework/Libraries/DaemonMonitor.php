<?php
/**
 * 常驻进程控制和监控类
 *
 * //配置文件格式：
 * //{Daemon 类名} => array(
 * //    'count' => {启动进程个数}
 * //    'time_out' => {最大执行时间}  //单位 秒，可以为小数
 * //    'log_config_name'=>'',  // 日志配置名
 * //    'params'=>array()  //进程初始化参数
 * // )
 * return array(
 * 'CountPVUV' => array(
 * 'count'           => 1,
 * 'time_out'        => 3.5,
 * 'log_config_name' => ''
 * )
 * // 'DaemonName2' => array(
 * //     'count'    => 5,
 * //     'time_out' => 3
 * // ),
 * // 'DaemonName3' => array(
 * //     'count' => 4
 * // )
 * );
 *
 * @author zhiyuan12@staff.weibo.com
 */
namespace Framework\Libraries;
class DaemonMonitor extends ProcessManager {
    private $_job_list                = array();
    private $_module                  = '';
    private $_restart_count           = array();
    private static $KEEP_ALIVE_DAEMON = array('job_id' => 0, 'id' => 0, 'name' => 'FRAMEWORK_KEEP_ALIVE_DAEMON');
    private $_config                  = array();
    private $_restart_file            = LOG_ROOT_PATH . "/DaemonMonitor.lock";
    public function __construct(string $module, $config_name = 'daemons') {
        $this->_module = $module;
        $config        = ConfigTool::loadByName($config_name, $module);
        if (empty($config)) {
            throw new \Exception($moudle . ' DaemonMonitor config [ ' . $config_name . '] empty');
        }
        $this->_config = $config;
        parent::__construct();
    }
    /**
     * run之前基类会初始化调用
     */
    public function init() {
        $job_list = array(self::$KEEP_ALIVE_DAEMON['job_id'] => self::$KEEP_ALIVE_DAEMON);
        $job_id   = self::$KEEP_ALIVE_DAEMON['job_id'] + 1;
        foreach ($this->_config as $daemon_name => $config) {
            for ($i = 0; $i < $config['count']; $i++) {
                $job_list[$job_id] = array('name' => $daemon_name, 'id' => $i, 'params' => $config['params']);
                $this->_writeLog($job_id, array('job_info' => $job_list[$job_id]));
                $this->_restart_count[$job_id] = 0;
                $job_id++;
            }
        }
        $this->_job_list = $job_list;
        //清理原有的相同监控程序
        $ppid       = $this->getParentPid();
        $pinfo      = $this->getResourceInfo(array($ppid));
        $pinfo      = $pinfo[$ppid];
        $cmd        = $pinfo['CMD'][0];
        $sh_str     = 'ps -eo pid,cmd  | grep -v grep | grep "' . $cmd . '"';
        $sh_ret     = trim(shell_exec($sh_str));
        $sh_ret_arr = explode("\n", $sh_ret);
        if (count($sh_ret_arr) > 1) {
            $content = trim(file_get_contents($this->_restart_file));
            $content = json_decode($content, true);
            $cmd     = $content['cmd'];
            //任务一致性检查，不一致则重启程序
            if ((count($sh_ret_arr) - 2) !== count($job_list)) {
                //(count($sh_ret_arr) - 2 减去当前进程和之前监控任务主进程后，应该与job_list数量一致
                $cmd = 'restart';
            } else {
                $last_job_list = $content['job_list'];
                //只需要比较value是否相同。由于前序逻辑上能确保数组数量一致的时候，两个数组的key也一致,见job_id生成方法
                foreach ($job_list as $job_id => $value) {
                    //有任意任务不一致就重启
                    if ( ! empty(array_diff_assoc($last_job_list[$job_id], $value)) || ! empty(array_diff_assoc($value, $last_job_list[$job_id]))) {
                        $cmd = 'restart';
                        break;
                    }
                }
            }
            switch ($cmd) {
                case 'restart':
                    $to_kill = array();
                    foreach ($sh_ret_arr as $one) {
                        list($t_pid, $t_cmd) = explode(' ', $one, 2);
                        if ($t_pid == $ppid) {
                            continue;
                        } else {
                            $to_kill[] = $t_pid;
                        }
                    }
                    $tmp = $this->killProcessAndChilds($to_kill, SIGKILL, $error_pid);
                    if (true != $tmp) {
                        echo "clear old process error, pid list:" . json_encode($error_pid) . "\n";
                        safe_exit();
                    }
                    break;
                default:
                    safe_exit();
            }
        }
        $this->addJobIdList(array_keys($job_list));
        //记录本次任务
        file_put_contents($this->_restart_file, json_encode(array('cmd' => '', 'job_list' => $job_list)));
    }
    /**
     * 退出任务的时候重新添加
     * @param    int   $job_id
     * @param    array $siginfo
     * @return
     */
    public function onJobExit($job_id, $siginfo) {
        $this->_writeLog($job_id, array('siginfo' => $siginfo));
        $this->_restart_count[$job_id]++;
        $exec_info = $this->getJobExecInfo();
        $exec_info = $exec_info[$siginfo['pid']];
        $this->_writeLog($job_id, array('exec_info' => $exec_info));
        $this->unsetJobExecInfo($siginfo['pid']); //防止数据膨胀
        $this->addJobIdList(array($job_id));
    }
    /**
     * 监控任务使用资源状态
     * @param  id     $job_id
     * @param  array  $info
     * @return void
     */
    public function resourceLog($job_id, $info) {
        $this->_writeLog($job_id, array('resource_log' => $info));
    }
    private function _writeLog($job_id, $info) {
        if ($job_id == self::$KEEP_ALIVE_DAEMON['job_id']) {
            return true;
        }
        if ( ! isset($this->_job_list[$job_id])) {
            return false;
        }
        $class_name = $this->_job_list[$job_id]['name'];
        if (isset($this->_config[$class_name]['log_config_name']) && ! empty($this->_config[$class_name]['log_config_name'])) {
            $config = $this->_config[$class_name]['log_config_name'];
            $module = $this->_module;
        } else {
            $config = 'log.daemon'; //默认日志配置
            $module = 'Framework';
        }
        $logger = SingletonManager::$SINGLETON_POOL->getInstance('\Framework\Libraries\Logger', $config, $module);
        $logger->info('{info}', array('info' => json_encode($info)), $class_name);
    }
    /**
     * 子进程执行函数
     * @param  int $job_id     任务id
     * @return int 退出码
     */
    public function childExec($job_id) {
        if (self::$KEEP_ALIVE_DAEMON['job_id'] === $job_id) {
            //监控程序保活进程
            while (true) {
                sleep(60);
                $ret = $this->getResourceInfo(array($this->getParentPid()));
                //防止父进程故障保活进程不退出
                if ( ! isset($ret[$this->getParentPid()]['pid'])) {
                    break;
                }
            }
            return 0;
        }
        $class_name = '\\' . $this->_module . '\\Daemons\\' . $this->_job_list[$job_id]['name'];
        if ( ! class_exists($class_name)) {
            echo "class not exist and stop restart:" . $class_name . "\n";
            safe_exit();
        }
        $obj = new $class_name(array('id' => $this->_job_list[$job_id]['id'], 'restart_num' => $this->_restart_count[$job_id], 'params' => $this->_job_list[$job_id]['params']));
        $obj->run();
    }
    /**
     * 超时检测
     * @param  int       $job_id            任务id
     * @param  timestamp $begin_time        任务开始时间
     * @return boolean   超时返回true
     */
    public function timeOutCheck($job_id, $begin_time) {
        $class_name = $this->_job_list[$job_id]['name'];
        if ( ! isset($this->_config[$class_name]['time_out'])) {
            return false;
        }
        $time_out = $this->_config[$class_name]['time_out'];
        $now_time = $this->getMicrotime();
        if ($now_time - $begin_time >= $time_out) {
            $this->_writeLog($job_id, 'exec timeout');
            return true;
        } else {
            return false;
        }
    }
}