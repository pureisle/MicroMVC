<?php
/**
 * 常驻进程控制和监控类
 * @author zhiyuan12@staff.weibo.com
 */
namespace Framework\Libraries;
class DaemonMonitor extends ProcessManager {
    private $_job_list                = array();
    private $_module                  = '';
    private $_restart_count           = array();
    private static $KEEP_ALIVE_DAEMON = array('job_id' => 0, 'id' => 0, 'name' => 'FRAMEWORK_KEEP_ALIVE_DAEMON');
    private $_config                  = array();
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
                $job_list[$job_id] = array('name' => $daemon_name, 'id' => $i);
                $this->_writeLog($job_id, array('job_info' => $job_list[$job_id]));
                $this->_restart_count[$job_id] = 0;
                $job_id++;
            }
        }
        $this->_job_list = $job_list;
        $this->addJobIdList(array_keys($job_list));
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
            }
            return;
        }
        $class_name = '\\' . $this->_module . '\\Daemons\\' . $this->_job_list[$job_id]['name'];
        if ( ! class_exists($class_name)) {
            echo "class not exist and stop restart:" . $class_name . "\n";
            safe_exit();
        }
        $obj = new $class_name(array('id' => $this->_job_list[$job_id]['id'], 'restart_num' => $this->_restart_count[$job_id]));
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