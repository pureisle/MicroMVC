<?php
/**
 * 本地间模块通信
 *
 * 主要模仿Curl类，模拟http请求，实际是进行内存数据交换，真对App的Controller方法进行请求.
 *
 * example:
 *     $curl = new Framework\Models\LocalCurl();
 *     $curl->setAction($action_name,$url);
 *     $curl->get($action_name,$query_params);
 *     $curl->body();   //获取请求结果
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Models;
class LocalCurl {
    private $_url  = array();
    private $_apps = array();
    private $_body = '';
    public function __construct() {}
    /**
     * 设置请求信息
     */
    public function setAction($action, $url, $referer = '') {
        $this->_url[$action] = $url;
        return $this;
    }
    /**
     * 获取上一次成功请求的body数据
     */
    public function body() {
        return $this->_body;
    }
    /**
     * 发起post请求
     */
    public function post($action, $query = array()) {
        if ( ! isset($this->_url[$action])) {
            return false;
        }
        $url         = $this->_url[$action];
        $body        = $this->_run($url, 'POST', $query);
        $this->_body = $body;
        return $this;
    }
    /**
     * 发起get请求
     */
    public function get($action, $query = array()) {
        if ( ! isset($this->_url[$action])) {
            return false;
        }
        $url         = $this->_url[$action];
        $body        = $this->_run($url, 'GET', $query);
        $this->_body = $body;
        return $this;
    }
    /**
     * 执行app run
     * @param  string   $url
     * @param  string   $type    GET or POST
     * @param  array    $query
     * @return string
     */
    private function _run($url, $type = 'GET', $query = array()) {
        $fields = parse_url($url);
        $uri    = $fields['path'];
        if ( ! empty($fields['query'])) {
            parse_str($fields['query'], $tmp);
            $query = array_merge($query, $tmp);
        }
        $module  = $this->_getModule($uri);
        $app     = $this->_getApp($module);
        $request = $app->getRequest();
        if ('GET' === $type) {
            $request->setGetParams($query);
        } else {
            $request->setPostParams($query);
        }
        $request->setUri($uri);
        $body = $app->bootstrap()->run(false);
        return $body;
    }
    /**
     * 初始化app实例
     * @param  string        $module
     * @return Application
     */
    private function _getApp($module) {
        if ( ! isset($this->_apps[$module])) {
            $this->_apps[$module] = new Application($module);
        }
        return $this->_apps[$module];
    }
    /**
     * 根据uri获取module
     * @param  string   $uri
     * @return string
     */
    private function _getModule($uri) {
        $router_ret = Router::routeRule($uri);
        return $router_ret['module'];
    }
}