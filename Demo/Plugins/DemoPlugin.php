<?php
/**
 * 样例路由
 */
namespace Demo\Plugins;
use Framework\Models\Request;
use Framework\Models\Response;
use Framework\Models\Plugin;

class DemoPlugin extends Plugin {
    public function routerStartup(Request $request, Response $response) {
        var_dump('plugin routerStartup');
    }
    public function routerShutdown(Request $request, Response $response) {
        var_dump('plugin routerShutdown');
    }
    public function dispatchStartup(Request $request, Response $response) {
        var_dump('plugin preDispatch');
    }
    public function dispatchShutdown(Request $request, Response $response) {
        var_dump('plugin postDispatch');
    }
    public function preResponse(Request $request, Response $response) {
        var_dump('plugin preResponse');
    }
}