<?php
/**
 * 请求对象类
 *
 * 相比Entity Request,加入framework路由逻辑
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Models;

class Request extends \Framework\Entities\Request {
    private $_router = null;
    public function __construct() {
        parent::__construct();
        unset($_GET);
        unset($_POST);
    }
    /**
     * 设置路由实例
     * @param Router $router
     */
    public function setRouter(Router $router) {
        $this->_router = $router;
        return $this;
    }
    /**
     * 获取路由实例
     * @return Router
     */
    public function getRouter() {
        return $this->_router;
    }
    /**
     * 获取实体module
     * @return string
     */
    public function getModule() {
        return $this->getRouter()->getModule();
    }
    /**
     * 设置路由module
     * @param  string   $name
     * @return Router
     */
    public function setModule($name) {
        $this->getRouter()->setModule($name);
        return $this;
    }
    /**
     * 获取路由controller
     * @return string
     */
    public function getController() {
        return $this->getRouter()->getController();
    }
    /**
     * 设置路由controller
     * @param  string   $name
     * @return Router
     */
    public function setController($name) {
        $this->getRouter()->setController($name);
        return $this;
    }
    /**
     * 获取路由action
     * @return string
     */
    public function getAction() {
        return $this->getRouter()->getAction();
    }
    /**
     * 设置路由action
     * @param  string   $name
     * @return Router
     */
    public function setAction($name) {
        $this->getRouter()->setAction($name);
        return $this;
    }
}