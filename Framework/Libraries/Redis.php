<?php
/**
 * redis底层
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
class Redis extends \Redis {
    private $_persistent_id = '';
    private $_time_out      = 0.5;
    public function __construct(string $resource_name, string $module) {
        $config = ConfigTool::loadByName($resource_name, $module);
        if ( ! isset($config['host']) || ! isset($config['port'])) {
            throw new RedisException(RedisException::SERVERS_CONFIG_EMPTY);
        }
        $this->_persistent_id = $module . $resource_name;
        $this->setConnectTimeOut($config['timeout']);
        $ret = $this->pconnect($config['host'], $config['port'], $this->_time_out, $this->_persistent_id);
        if (false === $ret) {
            throw new RedisException(RedisException::SERVERS_CONNECT_ERROR);
        }
    }
    public function setConnectTimeOut(float $sec) {
        $this->_time_out = $sec;
        return $this;
    }
}
class RedisException extends Exception {
    const SERVERS_CONFIG_EMPTY  = 1;
    const SERVERS_CONNECT_ERROR = 2;
    public $ERROR_SET           = array(
        self::SERVERS_CONFIG_EMPTY  => array(
            'code'    => self::SERVERS_CONFIG_EMPTY,
            'message' => 'server config empty'
        ),
        self::SERVERS_CONNECT_ERROR => array(
            'code'    => self::SERVERS_CONNECT_ERROR,
            'message' => 'server connect error'
        )
    );
    public function __construct($code = 0) {
        parent::__construct($code);
    }
}