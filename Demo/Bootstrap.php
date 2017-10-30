<?php
/**
 * 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 * 这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */
namespace Demo;

use Framework\Models\Dispatcher;

class Bootstrap extends \Framework\Models\Bootstrap {
    public function _initDemo() {
        var_dump('_initDemo');
    }
    public function _initPlugin(Dispatcher $dispatcher) {
        $demo_plugin_obj = new Plugins\DemoPlugin();
        $dispatcher->registerPlugin($demo_plugin_obj);
        var_dump('_initPlugin');
    }
}
