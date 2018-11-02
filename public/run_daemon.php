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
 *
 * @param    array $config
 * @param    array $argv     // 1=>module ; 2=>file_name
 * @return
 */
function main($config, $argv) {
    if (empty($argv[1]) || empty($argv[2])) {
        echo "params error!";
        return false;
    }
    $daemon_file = $argv[2];
    $class_name  = '\\' . $argv[1] . '\\Daemons\\' . $daemon_file;
    $params      = array_splice($argv, 3);
    $obj         = new $class_name($params);
    $obj->run();
}
