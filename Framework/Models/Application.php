<?php
/**
 * 应用实例类
 *
 * 启动mvc框架入口方法
 *
 * example:
 *     $app = new Framework\Models\Application(APP_NAME);
 *     $app->bootstrap()->run();
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Models;
use Framework\Libraries\ConfigTool;
use Framework\Models\Request;
use Framework\Models\Response;

class Application {
    private static $_config            = array();
    private static $_is_load_framework = false;
    private $_app_name                 = '';
    private $_dispatcher               = null;
    private $_request                  = null;
    public function __construct($app_name) {
        $this->_app_name = $app_name;
        //每次app实例单独加在AutoLoad，以便隔离不同module的底层加载
        $this->_iniAutoLoad();
        //框架只加载一次
        if ( ! self::$_is_load_framework) {
            $this->_loadFramework();
            self::$_is_load_framework = true;
        }
        $this->_dispatcher = new Dispatcher();
    }
    public function run($is_echo = true) {
        $dispatcher = $this->getDispatcher();
        $plugins    = $dispatcher->getPlugins();
        $request    = $this->getRequest();
        $response   = new Response();
        $config     = $this->getConfig();
        //路由开始
        foreach ($plugins as $plugin) {
            $plugin->routerStartup($request, $response);
        }
        $router = new Router();
        $router->route($request);

        $dispatcher->setRouter($router);
        $request->setRouter($router);
        //路由结束
        foreach ($plugins as $plugin) {
            $plugin->routerShutdown($request, $response);
        }
        $module = $router->getModule();
        //未开启的module 返回404
        if (true !== $config['modules'][$module]) {
        }
        $dispatcher->setRequest($request);

        $view = new View();
        $dispatcher->setView($view);
        //分发开始前
        foreach ($plugins as $plugin) {
            $plugin->dispatchStartup($request, $response);
        }
        $dispatcher->setResponse($response);
        //分发处理
        $dispatcher->dispatch($config);

        //分发结束
        foreach ($plugins as $plugin) {
            $plugin->dispatchShutdown($request, $response);
        }
        //返回数据结果前
        foreach ($plugins as $plugin) {
            $plugin->preResponse($request, $response);
        }
        $ret_body = $response->getBody();
        if ($is_echo) {
            echo $ret_body;
        } else {
            return $ret_body;
        }
    }
    public function execute($function_name) {
        if (function_exists($function_name)) {
            return $function_name($this->getConfig());
        }
        throw new ApplicationException(ApplicationException::ERROR_FUNCTION_NOT_EXIST);
        return false;
    }
    /**
     * 执行应用初始化类
     */
    public function bootstrap() {
        if ( ! file_exists(APP_PATH . DIRECTORY_SEPARATOR . 'Bootstrap.php')) {
            return $this;
        }
        $class_name = $this->_app_name . '\\Bootstrap';
        new $class_name($this->_dispatcher);
        return $this;
    }
    public function getRequest() {
        if (empty($this->_request)) {
            $this->_request = new Request();
        }
        return $this->_request;
    }
    public function setRequest(Request $request) {
        $this->_request = $request;
        return $this;
    }
    /**
     * 获取控制器
     * @return object
     */
    public function getDispatcher() {
        return $this->_dispatcher;
    }
    /**
     * 获取应用配置信息
     * @return array
     */
    public function getConfig() {
        return self::$_config;
    }
    /**
     * 获取配置可访问的module
     * @return array
     */
    public function getModules() {
        $f_config = $this->getFrameworkConfig();
        return $f_config['modules'];
    }
    /**
     * 加载框架配置文件和自动加载类
     */
    private function _loadFramework() {
        $this->_iniConfig(FRAMEWORK_NAME);
        return $this;
    }
    /**
     * 加载应用总配置
     * @param    string $app_name
     * @return
     */
    private function _iniConfig() {
        self::$_config = ConfigTool::getConfig(FRAMEWORK_NAME, FRAMEWORK_NAME);
        return $this;
    }
    /**
     * 注册自动加载类
     * @return
     */
    private function _iniAutoLoad() {
        require_once 'AutoLoad.php';
        $autoload = new AutoLoad($this->_app_name);
        $autoload->register();
        return $this;
    }
}