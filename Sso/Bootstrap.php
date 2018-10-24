<?php
/**
 * 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 * 这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */
namespace Sso;
use Framework\Libraries\SingletonManager;
use Framework\Models\Dispatcher;
use Sso\Models\ApiDisplay;

class Bootstrap extends \Framework\Models\Bootstrap {
    private static $_is_set_session_handler = false;
    public function _initSession() {
        if ( ! self::$_is_set_session_handler) {
            $handler = SingletonManager::$SINGLETON_POOL->getInstance('\Sso\Models\Session');
            session_set_save_handler(
                array($handler, 'open'),
                array($handler, 'close'),
                array($handler, 'read'),
                array($handler, 'write'),
                array($handler, 'destroy'),
                array($handler, 'gc')
            );
            register_shutdown_function('session_write_close');
            self::$_is_set_session_handler = true;
        }
    }
    /**
     * 注册controller异常处理函数
     */
    public function _initControllerExceptionHandler(Dispatcher $dispatcher) {
        $dispatcher->registerExceptionHandle('\Framework\Models\ControllerException', function ($exception) {
            ApiDisplay::display(ApiDisplay::PARAM_ERROR_CODE, array($exception->getMessage()));
            return true;
        });
    }
}
