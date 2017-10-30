<?php
/**
 * 框架路由底层
 *
 * 主要针对Application类执行run方法时，埋入钩子，以便进行相关环节的内容修改
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Models;
use Framework\Models\Request;
use Framework\Models\Response;

abstract class Plugin {
    /**
     * 路由开始前执行
     * @param    Request  $request
     * @param    Response $response
     * @return
     */
    public function routerStartup(Request $request, Response $response) {}
    /**
     * 路由结束后执行
     * @param    Request  $request
     * @param    Response $response
     * @return
     */
    public function routerShutdown(Request $request, Response $response) {}
    /**
     * 分发执行前执行
     * @param    Request  $request
     * @param    Response $response
     * @return
     */
    public function dispatchStartup(Request $request, Response $response) {}
    /**
     * 分发结束后执行
     * @param    Request  $request
     * @param    Response $response
     * @return
     */
    public function dispatchShutdown(Request $request, Response $response) {}
    /**
     * 返回结果前执行
     * @param    Request  $request
     * @param    Response $response
     * @return
     */
    public function preResponse(Request $request, Response $response) {}
}