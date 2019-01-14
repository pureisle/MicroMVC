<?php
//配置文件格式：
//{Daemon 类名} => array(
//    'count' => {启动进程个数}
//    'time_out' => {最大执行时间}  //单位 秒，可以为小数
//    'log_config_name'  // 日志配置名
// )
return array(
    'CountPVUV' => array(
        'count'           => 1,
        'time_out'        => 3.5,
        'log_config_name' => ''
    )
    // 'DaemonName2' => array(
    //     'count'    => 5,
    //     'time_out' => 3
    // ),
    // 'DaemonName3' => array(
    //     'count' => 4
    // )
);