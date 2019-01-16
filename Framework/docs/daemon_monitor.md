### 如何使用

### 相关文件
```
|- Framework	框架
	|- Libraries	框架类库
		|- ProcessManager.php 	多进程管理
		|- Daemon.php 	单任务基类
		|- DaemonMonitor.php 	Daemon 监控类
|- public	入口程序
	|- run_daemon.php
```

### 使用方法
1. 在需要跑常驻 Daemon 程序的 Module 项目的 config 配置文件夹内创建 daemons.php 配置（名字也可变更)。[配置文件样例](https://github.com/pureisle/MicroMVC/blob/master/Sso/config/daemons.php)格式如下：
```
 //配置文件格式：
  //{Daemon 类名} => array(
  //    'count' => {启动进程个数}
  //    'time_out' => {最大执行时间}  //单位 秒，可以为小数
  //    'log_config_name'=>'',  // 日志配置名
  //    'params'=>array()  //进程初始化参数
  // )
return array(
  'CountPVUV' => array(
  'count'           => 1,
  'time_out'        => 3.5,
  'log_config_name' => ''
  )
  'DaemonName2' => array(
      'count'    => 5,
      'time_out' => 3
  ),
  'DaemonName3' => array(
     'count' => 4
  )
);
```
其中 Daemon 类名，需要是同 Module 项目下 Daemons 文件夹内的继承了框架提供的 Daemon.php基类的类。[样例程序](https://github.com/pureisle/MicroMVC/blob/master/Sso/Daemons/CountPVUV.php)
1. 启动带监控保活任务的方式：在 crontab 里的配置或直接执行:
```
* * * * * php {项目路径}/public/run_daemon.php  {Module名} DaemonMonitor [{daemons配置文件名}]
```
1. 启动普通任务的方式：
```
* * * * * php {项目路径}/public/run_daemon.php  {Module名} {Daemon程序类名} [{其他参数列表}]
```