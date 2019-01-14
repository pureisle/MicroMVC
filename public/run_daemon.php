<?php
/**
 * 执行进程方法
 */
if ( ! empty($argv[1])) {
    $module = $argv[1];
} else {
    $module = 'Framework';
}

$env = array(
    'module' => $module
);
require "cli.php";
//入口函数
/**
 * 入口执行函数
 *
 * php public/run_daemon.php Sso CountPVUV param1 param2 [...]
 * php public/run_daemon.php Sso DaemonMonitor  // 运行框架提供的常驻进程控制类,可省略第二个参数
 *
 * @param    array $config
 * @param    array $argv     // 1=>module ; 2=>file_name
 * @return
 */
function main($config, $argv) {
    if (empty($argv[1])) {
        echo 'params error!."\n"';
        return false;
    }
    $module      = $argv[1];
    $daemon_file = 'DaemonMonitor';
    if ( ! empty($argv[2])) {
        $daemon_file = $argv[2];
    }
    //非监控 Daemon 直接运行
    if ('DaemonMonitor' != $daemon_file) {
        $class_name = '\\' . $module . '\\' . $config['path']['daemon'] . '\\' . $daemon_file;
        $params     = array_splice($argv, 3);
        $obj        = new $class_name($params);
    } else {
        //如果 $daemon_file 的值为框架提供的 DaemonMonitor 工具，则走特别逻辑
        $config_name = null;
        if ( ! empty($argv[3])) {
            $config_name = $argv[3];
            $obj         = new \Framework\Libraries\DaemonMonitor($module, $config_name);
        } else {
            $obj = new \Framework\Libraries\DaemonMonitor($module);
        }
    }
    $obj->run();
}
