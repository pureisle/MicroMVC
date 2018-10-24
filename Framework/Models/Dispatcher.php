<?php
/**
 * 调度类
 *
 * 主要负责mvc各个模块之前的调度问题
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Models;
use Framework\Libraries\Exception;
use Framework\Models\Request;
use Framework\Models\Response;

class Dispatcher {
    const ACTION_SUFFIX     = 'Action';
    private $_plugin_set    = array();
    private $_exception_set = array();
    private $_request       = null;
    private $_response      = null;
    private $_router        = null;
    private $_view          = null;
    private $_app           = null;
    private $_config        = null;
    public function __construct($config) {
        $this->_config = $config;
    }
    /**
     * 注册框架插件
     * @param  Plugin       $plugin
     * @return Dispatcher
     */
    public function registerPlugin(Plugin $plugin) {
        array_push($this->_plugin_set, $plugin);
        return $this;
    }
    /**
     * 获取注册的插件列表
     * @return array
     */
    public function getPlugins() {
        return $this->_plugin_set;
    }
    /**
     * 注册异常处理函数
     * @param    string   $exception_class
     * @param    function $deal_function     这里必须是全局函数或者匿名函数
     * @return
     */
    public function registerExceptionHandle($exception_class, $deal_function) {
        if (empty($exception_class) || ! is_callable($deal_function, false, $callable_name)) {
            return false;
        }
        $this->_exception_set[] = array('class' => $exception_class, 'func' => $deal_function, 'callable_name' => $callable_name);
        return true;
    }
    /**
     * 获取注册的异常处理句柄
     * @return array
     */
    public function getExceptionHandles() {
        return $this->_exception_set;
    }
    /**
     * 设置请求对象
     * @param  Request      $request
     * @return Dispatcher
     */
    public function setRequest(Request $request) {
        $this->_request = $request;
        return $this;
    }
    /**
     * 获取请求对象
     * @return Request
     */
    public function getRequest() {
        return $this->_request;
    }
    /**
     * 设置返回结果对象
     * @param  Response     $response
     * @return Dispatcher
     */
    public function setResponse(Response $response) {
        $this->_response = $response;
        return $this;
    }
    /**
     * 获取返回对象
     * @return Reponse
     */
    public function getResponse() {
        return $this->_response;
    }
    public function setRouter(Router $router) {
        $this->_router = $router;
        return $this;
    }
    public function getRouter() {
        return $this->_router;
    }
    /**
     * 设置视图对象
     * @param  View         $view
     * @return Dispatcher
     */
    public function setView(View $view) {
        $this->_view = $view;
        return $this;
    }
    /**
     * 获取视图对象
     * @return View
     */
    public function getView() {
        return $this->_view;
    }
    /**
     * 执行调度流程
     * @param  array        $config
     * @return Dispatcher
     */
    public function dispatch() {
        if (empty($this->_request)) {
            throw new DispatcherException(DispatcherException::ERROR_REQUEST_NULL);
        }
        $request    = $this->_request;
        $module     = $request->getModule();
        $controller = $request->getController();
        $action     = $request->getAction();
        $class_name = $module . '\\' . $this->_config['path']['controller'] . '\\' . $controller;
        //判断非法controller重定向到404
        $file_path = \Framework\Models\AutoLoad::getFilePath($class_name);
        if (file_exists($file_path) === false) {
            http_response_code(404);
            return false;
        }
        $class = new $class_name($request);
        if ($class instanceof Controller === false) {
            throw new DispatcherException(DispatcherException::ERROR_OBJECT_TYPE);
        }
        $class->setView($this->_view);
        $action_name = $action . self::ACTION_SUFFIX;
        ob_start();
        $action_ret = $class->$action_name();
        $body       = ob_get_contents();
        ob_end_clean();
        if (false !== $action_ret) {
            $tpl_file_path = ROOT_PATH . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . $this->_config['path']['view'] . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $controller) . DIRECTORY_SEPARATOR . $action . '.' . $this->_config['template']['suffix'];
            $body .= $this->_view->render($tpl_file_path);
        }
        if (empty($this->_response)) {
            throw new DispatcherException(DispatcherException::ERROR_REQUEST_NULL);
        }
        $response = $this->_response;
        $response->setBody($body);
        return $this;
    }
}
class DispatcherException extends Exception {
    const ERROR_OBJECT_TYPE   = 1;
    const ERROR_REQUEST_NULL  = 2;
    const ERROR_RESPONSE_NULL = 3;
    public $ERROR_SET         = array(
        self::ERROR_OBJECT_TYPE   => array(
            'code'    => self::ERROR_OBJECT_TYPE,
            'message' => 'Object type error'
        ),
        self::ERROR_REQUEST_NULL  => array(
            'code'    => self::ERROR_REQUEST_NULL,
            'message' => 'Request object can not be null'
        ),
        self::ERROR_RESPONSE_NULL => array(
            'code'    => self::ERROR_RESPONSE_NULL,
            'message' => 'Response object can not be null'
        )
    );
}
