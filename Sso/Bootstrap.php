<?php
/**
 * 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 * 这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */
namespace Sso;

use Framework\Models\Dispatcher;
use Sso\Models\Session;

class Bootstrap extends \Framework\Models\Bootstrap {
    public function _initSession() {
        $handler = new Session();
        session_set_save_handler(
            array($handler, 'open'),
            array($handler, 'close'),
            array($handler, 'read'),
            array($handler, 'write'),
            array($handler, 'destroy'),
            array($handler, 'gc')
        );
        register_shutdown_function('session_write_close');
    }
}
