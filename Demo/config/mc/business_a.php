<?php
/**
 * 加载资源组。按业务需要配置，使用哪些资源。
 * 这样设计以便方便业务组合使用不同的资源池或者迁移资源池
 */
$resource_set = include_once 'resource.php';
return array(
    'name'    => 'business_a',
    'servers' => array_merge($resource_set['id2'], $resource_set['id3']),
    'options' => array(
        'OPT_CONNECT_TIMEOUT' => 500,   //连接超时时长,单位 ms
        'OPT_COMPRESSION'     => false //禁止压缩数据，可以使用字符串追加函数
    )
);