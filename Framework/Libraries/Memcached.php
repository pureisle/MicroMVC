<?php
/**
 * memcached管理类
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;

class Memcached extends \Memcached {
    private $_persistent_id = null;
    public function __construct(string $resource_name, string $module) {
        $this->_persistent_id = $module . '_' . $resource_name;
        parent::__construct($this->_persistent_id);
        //判断是否为长链接
        if ( ! count($this->getServerList())) {
            $config = ConfigTool::loadByName($resource_name, $module);
            if (empty($config['servers']) || empty($config['servers'][0]['host'])) {
                throw new MemcachedException(MemcachedException::SERVERS_CONFIG_EMPTY);
            }
            $this->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
            $this->addServers($config['servers']);
            if ( ! empty($config['options'])) {
                foreach ($config['options'] as $key => $value) {
                    $this->setOption(constant('Memcached::' . $key), $value);
                }
            }
        }
    }
}
class MemcachedException extends Exception {
    const SERVERS_CONFIG_EMPTY = 1;
    public $ERROR_SET          = array(
        self::SERVERS_CONFIG_EMPTY => array(
            'code'    => self::SERVERS_CONFIG_EMPTY,
            'message' => 'Mc servers config empty'
        )
    );
    public function __construct($code = 0) {
        parent::__construct($code);
    }
}