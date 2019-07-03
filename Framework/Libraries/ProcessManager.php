<?php
/**
 * 多进程管理
 * 抽象类，需要实现childExec 、 timeOutCheck 和 resourceLog 三个方法
 * @author zhiyuan12@staff.weibo.com
 */
namespace Framework\Libraries;
/**
 * 确保只能运行在SHELL中
 */
if (substr(php_sapi_name(), 0, 3) !== 'cli') {
    die("This Programe can only be run in CLI mode");
}
//尽可能更快的检测待处理的信号
if (version_compare(PHP_VERSION, '7.1') >= 0) {
    pcntl_async_signals(true);
} else {
    declare (ticks = 1);
}
abstract class ProcessManager {
    private $_max_processes  = 999;
    private $_current_jobs   = array();
    private $_signal_queue   = array();
    private $_jobs_exec_info = array();
    private $_job_ids        = array();
    private $_ppid           = 0;
    private $_IPC_set        = array();
    private $_heartbeat      = array();
    const MSG_CODE_HEARTBEAT = 1;
    public function __construct() {
        $this->_ppid = getmypid();
        pcntl_signal(SIGCHLD, array($this, "_childSignalHandler"));
    }
    /**
     * 用户程序发送心跳
     * @return
     */
    public function heartbeat(int $pid = 0) {
        $key = $this->getIPCQueue();
        return msg_send($key, self::MSG_CODE_HEARTBEAT, array('code' => self::MSG_CODE_HEARTBEAT, 'pid' => getmypid(), 'time' => time()));
    }
    public function getHearbeatData() {
        return $this->_heartbeat;
    }
    /**
     * 获取消息队列key
     */
    public function getIPCQueue(string $path_name = __FILE__, int $proj = 1) {
        if ($proj > 255) {
            return false;
        }
        $key = $path_name . ":" . $proj;
        if ( ! isset($this->_IPC_set[$key])) {
            $id                   = ftok($path_name, $proj);
            $this->_IPC_set[$key] = msg_get_queue($id);
        }
        return $this->_IPC_set[$key];
    }
    /**
     * run之前基类会初始化调用
     */
    public function init() {}
    /**
     * 任务退出时调用
     */
    public function onJobExit($job_id, $siginfo) {}
    /**
     * 监控任务使用资源状态
     * @param  id     $job_id
     * @param  array  $info
     * @return void
     */
    abstract public function resourceLog($job_id, $info);
    /**
     * 子进程执行函数
     * @param  int $job_id     任务id
     * @return int 退出码
     */
    abstract public function childExec($job_id);
    /**
     * 超时检测
     * @param  int       $job_id            任务id
     * @param  timestamp $begin_time        任务开始时间
     * @return boolean   超时返回true
     */
    abstract public function timeOutCheck($job_id, $begin_time);
    /**
     * 加载所需执行的任务号
     * @param array $job_id_list 任务id号数组
     */
    public function addJobIdList($job_id_list) {
        foreach ($job_id_list as $one) {
            if ( ! is_int($one)) {
                return false;
            }
        }
        $this->_job_ids = array_unique(array_merge($this->_job_ids, $job_id_list));
        return $this;
    }
    /**
     * 获取待执行的任务id列表
     * @return array
     */
    public function getJobIdList() {
        return $this->_job_ids;
    }
    /**
     * 获取父进程id
     * @return int
     */
    public function getParentPid() {
        return $this->_ppid;
    }
    /**
     * 弹出一个任务id
     * @return int
     */
    public function popJobId() {
        $job_id = array_shift($this->_job_ids);
        return $job_id;
    }
    /**
     * 设置最大子进程数
     * @param int $max_num 最大正在执行子任务数
     */
    public function setMaxProcess($max_num) {
        if (is_numeric($max_num)) {
            $this->_max_processes = $max_num;
        }
        return $this;
    }
    /**
     * 获取任务执行信息
     * @return array
     */
    public function getJobExecInfo() {
        return $this->_jobs_exec_info;
    }
    /**
     * 给子类开一个清理数据的口子
     * @param int $pid
     */
    protected function unsetJobExecInfo($pid) {
        unset($this->_jobs_exec_info[$pid]);
        return $this;
    }
    /**
     * 执行入口
     */
    public function run() {
        $this->init();
        while (true) {
            $this->_reviceHeartbeat();
            $this->_monitor();
            $job_id = $this->popJobId();
            if (null === $job_id) {
                if (count($this->_current_jobs) <= 0) {
                    break;
                } else {
                    sleep(1);
                    continue;
                }
            } else {
                $launched = $this->_forkJob($job_id);
                while (count($this->_current_jobs) >= $this->_max_processes) {
                    sleep(1);
                }
            }
        }
        return true;
    }
    /**
     * 获取当前时间的毫秒数
     *
     * @return float
     */
    public static function getMicrotime() {
        list($usec, $sec) = explode(' ', microtime());
        return ((float) $usec + (float) $sec);
    }
    /**
     * 获取制定pid列表的资源状态
     * @param  array   $pid_list
     * @return array
     */
    public static function getResourceInfo($pid_list = array(), $is_contain_child = true) {
        if (empty($pid_list)) {
            return array();
        }
        $all_pid_set = $pid_list;
        if ($is_contain_child) {
            $children_pid_list = self::getAllChildrenPidList($pid_list);
            if ( ! empty($children_pid_list)) {
                foreach ($children_pid_list as $ppid => $pid_array) {
                    if (empty($pid_array)) {
                        continue;
                    }
                    $all_pid_set = array_merge($all_pid_set, $pid_array);
                }
            }
        }
        //cmd 一定要放在最后一位
        $shell_cmd     = "ps -o pid,ppid,user,%cpu,%mem,vsz,rss,time,sz,s,f,pri,ni,wchan,cmd -p" . implode(',', $all_pid_set);
        $tmp           = shell_exec($shell_cmd);
        $tmp_arr       = explode("\n", trim($tmp));
        $resource_info = array();
        $key_list      = array();
        $first         = true;
        foreach ($tmp_arr as $line) {
            $filter_line = preg_replace('/\s\s+/', ' ', trim($line));
            $field       = explode(' ', $filter_line);
            if ($first) {
                $first    = false;
                $key_list = $field;
                continue;
            }
            $pid                 = $field[0];
            $resource_info[$pid] = array_combine($key_list, array_merge(
                array_slice($field, 0, count($key_list) - 1),
                array(implode(' ', array_slice($field, count($key_list) - 1)))
            ));
        }
        //合并信息
        $ret = array();
        foreach ($pid_list as $ppid) {
            $ret[$ppid] = array('pid' => 0, '%CPU' => 0, '%MEM' => 0, 'VSZ' => 0, 'RSS' => 0, 'SZ' => 0);
            $pid_array  = array($ppid);
            if ($is_contain_child) {
                $pid_array = array_merge($pid_array, $children_pid_list[$ppid]);
            }
            foreach ($pid_array as $pid) {
                if (empty($resource_info[$pid])) {
                    continue;
                }
                $ret[$ppid]['pid'] = $ppid;
                $ret[$ppid]['%CPU'] += $resource_info[$pid]['%CPU'];
                $ret[$ppid]['%MEM'] += $resource_info[$pid]['%MEM'];
                $ret[$ppid]['VSZ'] += $resource_info[$pid]['VSZ'];
                $ret[$ppid]['RSS'] += $resource_info[$pid]['RSS'];
                $ret[$ppid]['SZ'] += $resource_info[$pid]['SZ'];
                $ret[$ppid]['CMD'][] = $resource_info[$pid]['CMD'];
                if ($pid != $ppid) {
                    $ret[$ppid]['children_pid'][] = $pid;
                }
            }
            if (0 === $ret[$ppid]['pid']) {
                $ret[$ppid] = array();
            }
        }
        return $ret;
    }
    /**
     * 获取所有的子pid
     * @param  array   $pid_list
     * @return array
     */
    public static function getAllChildrenPidList($pid_list = array()) {
        if (empty($pid_list)) {
            return array();
        }
        $cmd     = "ps -eo pid,ppid|grep -v PID";
        $cmd_ret = shell_exec($cmd);
        if (empty($cmd_ret)) {
            return false;
        }
        $pidlist_arr_tmp = explode("\n", $cmd_ret);
        $ppid_pid_arr    = array();
        foreach ($pidlist_arr_tmp as $one) {
            $one = preg_replace('/\s\s+/', ' ', trim($one));
            if (empty($one)) {
                continue;
            }
            list($pid, $ppid)      = explode(" ", $one);
            $ppid_pid_arr[$ppid][] = $pid;
        }
        $children_pidlist = array();
        foreach ($pid_list as $pid) {
            $queue                  = array($pid);
            $children_pidlist[$pid] = array();
            while (count($queue) > 0) {
                $tmp_pid = array_shift($queue);
                if (isset($ppid_pid_arr[$tmp_pid])) {
                    $children_pidlist[$pid] = array_merge($children_pidlist[$pid], $ppid_pid_arr[$tmp_pid]);
                    $queue                  = array_merge($queue, $ppid_pid_arr[$tmp_pid]);
                }
            }
        }
        return $children_pidlist;
    }
    /**
     * 结束进程及其所有子进程
     * @param  id        $pid
     * @return boolean
     */
    public static function killProcessAndChilds($pid_list = array(), $signal = SIGKILL, &$error_pid = array()) {
        $children_pids = self::getAllChildrenPidList($pid_list);
        foreach ($children_pids as $pid => $children_array) {
            $error_pid[$pid] = array();
            foreach ($children_array as $cid) {
                $tmp = posix_kill($cid, $signal);
                if ( ! $tmp) {
                    $error_pid[$cid] = posix_strerror(posix_get_last_error());
                }
            }
            $tmp = posix_kill($pid, $signal);
            if ( ! $tmp) {
                $error_pid[$pid] = posix_strerror(posix_get_last_error());
            }
        }
        return $tmp;
    }
    /**
     * fork任务进程
     * @param  string    $job_id
     * @return boolean
     */
    private function _forkJob($job_id) {
        $pid = pcntl_fork();
        if (-1 == $pid) {
            return false;
        } else if (0 == $pid) {
            $exit_code = $this->childExec($job_id);
            exit($exit_code);
        }
        $this->_current_jobs[$pid] = $job_id;
        $this->_heartbeat[$pid]    = 0;
        $this->_addJobExecInfo($pid, $job_id);
        //处理已经提前结束的子进程队列
        if (isset($this->_signal_queue[$pid])) {
            $this->_signal_queue[$pid]['initiative'] = true;
            $this->_childSignalHandler(SIGCHLD, $this->_signal_queue[$pid]);
            unset($this->_signal_queue[$pid]);
        }
        return true;
    }
    /**
     * 子进程结束handler
     * @param  string    $signo
     * @param  array     $siginfo
     * @return boolean
     */
    private function _childSignalHandler($signo, $siginfo = null) {
        if (isset($siginfo['pid'])) {
            $pid    = $siginfo['pid'];
            $status = $siginfo['status'];
        }
        if ( ! $pid) {
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }
        while ($pid > 0) {
            //在当前任务列表中，进行结束处理
            if (isset($this->_current_jobs[$pid])) {
                $exit_code = pcntl_wexitstatus($status);
                $job_id    = $this->_current_jobs[$pid];
                $this->_addJobExecInfo($pid, $job_id, $status, $exit_code);
            } else {
                //不在任务列表加入信号队列等待主进程调用时处理
                if (empty($siginfo)) {
                    //兼容有些低版本版本没有传第二个参数
                    $siginfo['pid']    = $pid;
                    $siginfo['status'] = $status;
                }
                $this->_signal_queue[$pid] = $siginfo;
            }
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }
        return true;
    }
    private function _addJobExecInfo($pid, $job_id, $status = null, $exit_code = null) {
        if (is_null($status)) {
            $this->_jobs_exec_info[$pid] = array(
                'pid'    => $pid,
                'job_id' => $job_id
            );
            $this->_jobs_exec_info[$pid]['begin_time'] = self::getMicrotime();
        } else {
            $this->_jobs_exec_info[$pid]['status'] = $status;
        }
        // !isset( $this->_jobs_exec_info[$pid]['exit_code'] ) 判断是为了避免多次产生退出信号
        if ( ! is_null($exit_code) && ! isset($this->_jobs_exec_info[$pid]['exit_code'])) {
            $this->_jobs_exec_info[$pid]['exit_code'] = $exit_code;
            $this->_jobs_exec_info[$pid]['end_time']  = self::getMicrotime();
            unset($this->_current_jobs[$pid]);                                                  //删除当前任务
            unset($this->_heartbeat[$pid]);                                                     // 删除心跳数据
            $this->onJobExit($job_id, array('pid' => $pid, 'status' => SIGKILL, 'code' => -1)); //通知子类
        }
        return $this;
    }
    /**
     * 监控
     * @return boolean
     */
    private function _monitor() {
        if (count($this->_current_jobs) <= 0) {
            return true;
        }
        $pid_list      = array_keys($this->_current_jobs);
        $pids_info     = self::getResourceInfo($pid_list);
        $job_exec_info = $this->getJobExecInfo();
        foreach ($pids_info as $pid => $pid_info) {
            if ( ! isset($this->_jobs_exec_info[$pid])) {
                continue;
            }
            $job_id = $this->_jobs_exec_info[$pid]['job_id'];
            //资源监控记录日志
            $this->resourceLog($job_id, $pid_info);
            //超时kill
            if ($this->timeOutCheck($job_id, $job_exec_info[$pid]['begin_time']) == true) {
                $tmp = self::killProcessAndChilds(array($pid), SIGKILL, $error_pid);
                if (true == $tmp) {
                    //posix_kill 的信息量 没接收到,这里额外清理一下需要处理的数据
                    $this->_addJobExecInfo($pid, $job_id, SIGKILL, -1);
                    unset($this->_signal_queue[$pid]);
                }
            }
        }
    }
    /**
     * 采集心跳
     */
    private function _reviceHeartbeat() {
        $key         = $this->getIPCQueue();
        $queue_state = msg_stat_queue($key);
        if ($queue_state['msg_qnum'] <= 0) {
            return;
        }
        $num = $queue_state['msg_qnum'];
        while ($num > 0) {
            $tmp                              = msg_receive($key, self::MSG_CODE_HEARTBEAT, $msg_type, 1024, $messge);
            $this->_heartbeat[$messge['pid']] = $messge['time'];
            $num--;
        }
    }
}
