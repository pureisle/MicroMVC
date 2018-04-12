<?php
/**
 * 路由底层
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Models;
use Framework\Models\Request;

class Router {
    private $_module     = 'Index';
    private $_controller = 'Index';
    private $_action     = 'index';
    public function __construct() {}
    /**
     * 获取路由module
     * @return string
     */
    public function getModule() {
        return $this->_module;
    }
    /**
     * 设置路由module
     * @param  string   $name
     * @return Router
     */
    public function setModule($name) {
        $this->_module = $name;
        return $this;
    }
    /**
     * 获取路由controller
     * @return string
     */
    public function getController() {
        return $this->_controller;
    }
    /**
     * 设置路由controller
     * @param  string   $name
     * @return Router
     */
    public function setController($name) {
        $this->_controller = $name;
        return $this;
    }
    /**
     * 获取路由action
     * @return string
     */
    public function getAction() {
        return $this->_action;
    }
    /**
     * 设置路由action
     * @param  string   $name
     * @return Router
     */
    public function setAction($name) {
        $this->_action = $name;
        return $this;
    }
    /**
     * 执行路由解析
     * @param Request $request
     */
    public function route(Request $request) {
        $uri       = $request->getUri();
        $route_ret = $this->routeRule($uri);
        $this->setModule($route_ret['module'])
             ->setController($route_ret['controller'])
             ->setAction($route_ret['action']);
        return true;
    }
    /**
     * uri路由解析规则
     * @param  string  $uri
     * @return array
     */
    public function routeRule($uri) {
        $ret         = array();
        $uri_array   = array();
        $field_count = 0;
        $uri         = trim($uri, '/');
        if ( ! empty($uri)) {
            $uri_array   = explode('/', $uri);
            $field_count = count($uri_array);
        }
        switch ($field_count) {
            case 0:
                $ret['module']     = $this->getModule();
                $ret['controller'] = $this->getController();
                $ret['action']     = $this->getAction();
                break;
            case 1:
                $ret['module']     = ucfirst($uri_array[0]);
                $ret['controller'] = $this->getController();
                $ret['action']     = $this->getAction();
                break;
            case 2:
                $ret['module']     = ucfirst($uri_array[0]);
                $ret['controller'] = ucfirst($uri_array[1]);
                $ret['action']     = $this->getAction();
                break;
            case 3:
                $ret['module']     = ucfirst($uri_array[0]);
                $ret['controller'] = ucfirst($uri_array[1]);
                $ret['action']     = $uri_array[2];
                break;
            default:
                $ret['module']     = ucfirst(array_shift($uri_array));
                $ret['action']     = array_pop($uri_array);
                $ret['controller'] = '';
                foreach ($uri_array as $value) {
                    $ret['controller'] .= ucfirst($value) . "\\";
                }
                $ret['controller'] = substr($ret['controller'], 0, -1);
                break;
        }
        return $ret;
    }
}