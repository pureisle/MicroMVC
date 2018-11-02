<?php
/**
 * 样例路由
 */
namespace Sso\Plugins;
use Framework\Models\Plugin;
use Framework\Models\Request;
use Framework\Models\Response;

class Auth extends Plugin {
    public function routerStartup(Request $request, Response $response) {}
    public function routerShutdown(Request $request, Response $response) {}
    public function dispatchStartup(Request $request, Response $response) {}
    public function dispatchShutdown(Request $request, Response $response) {}
    public function preResponse(Request $request, Response $response) {}
}
