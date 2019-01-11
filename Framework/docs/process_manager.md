### 如何写 daemon 程序

### 相关文件
```
|- Framework	框架
	|- Libraries	框架类库
		|- ProcessManager.php 	多进程管理
		|- Daemon.php 	单任务基类
|- public	入口程序
	|- run_daemon.php
```

### 使用方法
1. 整体流程：继承框架提供的 ProcessManager.php 或 Daemon.php 工具，把文件放在项目目录下的 Daemons 文件夹内，运行 run_daemon.php 程序即可完成。
1. run_daemon.php 命令接收两个参数，第一个参数为 module 名，第二个参数为要运行的 daemon 程序名，如 php public/run_daemon.php Sso CountPVUV
1. 如何使用 Daemon.php ,继承样例如下：
```
class TestDaemon extends Daemon {
	public function doJob(){
		// do something
	}
}
```
父类提供了 getCurrentProcessList() 方法，可以返回当前进程的数量，可以方便任务控制自己整体数据或者重复启动。
1. 如何使用 ProcessManager.php ,继承样例如下:
```
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
        // echo $job_id . "\n"; // do something
    }
    /**
     * 超时检测
     * @param  int       $job_id            任务id
     * @param  timestamp $begin_time        任务开始时间
     * @return boolean   超时返回true
     */
    public function timeOutCheck($job_id, $begin_time) {}
}
```
多任务管理的运行逻辑为：
1. 添加需要子类运行的任务数量，如上例中的 $this->addJobIdList($this->my_job_list) ,任务数组有多少个，父类就会执行多少个任务，并且把子类指定的唯一标识传递给子类的 childExec($job_id) 方法。
1. 如果使用 setMaxProcess($max_num) 设置了最多子进程并发数，则父类会控制子进程数量。如需要完成100个任务，同时设置了并发数为10，则父类最多启动10个子进程去完成相应任务，如果某一子进程的任务执行完毕，父类则会继续新起子进程直到补满到设置的10个并发数，直到100个任务都完成则结束整个程序。
父类提供了一些进程管理方法，如下：  
addJobIdList($job_id_list)  
setMaxProcess($max_num)  
getResourceInfo($pid_list = array(), $is_contain_child = true) //获取制定pid列表的资源状态  
getAllChildrenPidList($pid_list = array())  //获取所有的子pid  
killProcessAndChilds($pid_list = array(), $signal = SIGKILL, &$error_pid = array()) //结束进程及其所有子进程  
