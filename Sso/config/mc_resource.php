<?php
/**
 *     这里主要存放mc服务器的资源池
 */
return array(
    'id1'  => array(
        array('host' => '127.0.0.1', 'port' => 11211, 'weight' => 0)
    ),
    'id12' => array(
        array('host' => '127.0.0.1', 'port' => 11211, 'weight' => 0),
        array('host' => '127.0.0.2', 'port' => 11211, 'weight' => 0),
        array('host' => '127.0.0.3', 'port' => 11211, 'weight' => 0)
    )
);