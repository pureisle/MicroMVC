<?php
//配置文件格式：
//{Daemon 类名} => array(
//    'version'         => 1,    //version的变更会让进程整体重启
//    'count' => {启动进程个数}
//    'time_out' => {最大执行时间}  //单位 秒，可以为小数
//    'log_config_name' =>'' , // 日志配置名
//    'params'=> array() // Daemon初始化时传入的参数
// )
return array(
    'CountPVUV' => array(
        'version' => 1,
        'count'   => 1,
        // 'time_out'        => 3.5,
        //'log_config_name' => 'daemon_log',
        'params'  => array('param1' => 'asdf', 'param2' => '234')
    )
    // 'DaemonName2' => array(
    //     'count'    => 5,
    //     'time_out' => 3
    // ),
    // 'DaemonName3' => array(
    //     'count' => 4
    // )
);