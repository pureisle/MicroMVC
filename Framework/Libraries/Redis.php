<?php
/**
 * redis底层
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
class Redis extends \Redis {
    public function __construct(string $resource_name, string $module) {
        $config = ConfigTool::loadByName($resource_name, $module);
        var_dump($config);

    }
}
class RedisException extends Exception {
    const SERVERS_CONFIG_EMPTY = 1;
    public $ERROR_SET          = array(
        self::SERVERS_CONFIG_EMPTY => array(
            'code'    => self::SERVERS_CONFIG_EMPTY,
            'message' => 'servers config empty'
        )
    );
    public function __construct($code = 0) {
        parent::__construct($code);
    }
}