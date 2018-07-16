<?php
return array(
    'root_path'          => LOG_ROOT_PATH. '/sso',
    'file_name'          => 'login',
    'suffix_date_format' => 'Ymd',
    'lock_wait'          => 0.3,  //文件写锁获取等待时间，单位秒
    'buffer_line_num'    => 20,   //内存缓冲行数。进程异常退出会丢失数据
    'is_use_buffer'      => true // 是否启用缓冲模式
);