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

define('FRAMEWORK_VERSION', '1.2.2'); //框架版本号
class Application {
    const BOOTSTRAP_NAME               = 'Bootstrap';
    const CONFIG_FILE_NAME             = 'framework';
    private static $_config            = array();
    private static $_is_load_framework = false;
    private $_app_name                 = '';
    private $_dispatcher               = null;
    private $_request                  = null;
    private $_autoload                 = null;
    private $_is_force_module          = false;
    public function __construct(string $app_name) {
        //每次app实例单独加在AutoLoad，以便隔离不同module的底层加载
        $this->_iniAutoLoad($app_name);
        //框架只加载一次
        if ( ! self::$_is_load_framework) {
            $this->_loadFramework();
            self::$_is_load_framework = true;
        }
        $f_config = $this->getConfig();
        //如果app_name为空 或者 未注册 或者 注册了未开启，则启用默认app_name
        if (empty($app_name) || ! isset($f_config['modules'][$app_name]) || ! $f_config['modules'][$app_name]) {
            $this->_app_name        = $f_config['default_module'];
            $this->_is_force_module = true;
            $this->_autoload->setPathPrefix($this->_app_name);
        } else {
            $this->_app_name = $app_name;
        }
    }
    /**
     * 核心运行流程
     * @param  boolean  $is_echo
     * @return string
     */
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
        $router->setModule($this->_app_name, $this->_is_force_module);
        $router->route($request);
        $dispatcher->setRouter($router);
        $request->setRouter($router);
        //路由结束
        foreach ($plugins as $plugin) {
            $plugin->routerShutdown($request, $response);
        }
        $module = $router->getModule();
        //用户钩子执行完毕后，未开启的module 返回404
        if (true !== $config['modules'][$module]) {
            http_response_code(404);
            return $this;
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
        $dispatcher->dispatch();
        //分发结束
        foreach ($plugins as $plugin) {
            $plugin->dispatchShutdown($request, $response);
        }
        //返回数据结果前
        foreach ($plugins as $plugin) {
            $plugin->preResponse($request, $response);
        }
        if ( ! $is_echo) {
            $ret_body = $response->getBody();
            return $ret_body;
        }
        $response->response();
    }
    /**
     * 命令行执行一个函数
     * @param  string $function_name
     * @param  array  $argv
     * @return mix
     */
    public function execute($function_name, $argv) {
        if (function_exists($function_name)) {
            return $function_name($this->getConfig(), $argv);
        }
        throw new ApplicationException(ApplicationException::ERROR_FUNCTION_NOT_EXIST);
    }
    /**
     * 执行应用初始化类
     */
    public function bootstrap() {
        $app_path = ROOT_PATH . DIRECTORY_SEPARATOR . $this->_app_name;
        if ( ! file_exists($app_path . DIRECTORY_SEPARATOR . self::BOOTSTRAP_NAME . '.php')) {
            return $this;
        }
        $class_name = $this->_app_name . '\\' . self::BOOTSTRAP_NAME;
        new $class_name($this->getDispatcher());
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
        if ( ! isset($this->_dispatcher)) {
            $this->_dispatcher = new Dispatcher($this->getConfig());
        }
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
        $f_config = $this->getConfig();
        return $f_config['modules'];
    }
    /**
     * 加载框架配置文件和自动加载类
     */
    private function _loadFramework() {
        $this->_iniConfig();
        $this->_iniException();
        $this->_iniComposer();
        include "GlobalFunctions.php"; //加载框架全局函数
        return $this;
    }
    private function _iniComposer() {
        $f_config = $this->getConfig();
        if ($f_config['composer']) {
            require ROOT_PATH . '/vendor/autoload.php';
        }
    }
    /**
     * 加载应用总配置
     * @param    string $app_name
     * @return
     */
    private function _iniConfig() {
        self::$_config = ConfigTool::getConfig(self::CONFIG_FILE_NAME, FRAMEWORK_NAME);
        return $this;
    }
    /**
     * 设置未捕获异常处理函数
     * @return
     */
    private function _iniException() {
        set_exception_handler(array($this, '_exceptionHandler'));
    }
    public function _exceptionHandler($exception) {
        // 安全退出的异常不报错直接退出
        if ($exception instanceof ExitException) {
            return;
        }
        // these are our templates
        $traceline = "#%s %s(%s): %s(%s)";
        $msg       = "PHP Fatal error:  Uncaught exception '%s' with message '%s' in %s:%s\nStack trace:\n%s\n  thrown in %s on line %s";

        // alter your trace as you please, here
        $trace = $exception->getTrace();
        foreach ($trace as $key => $stackPoint) {
            // I'm converting arguments to their type
            // (prevents passwords from ever getting logged as anything other than 'string')
            $trace[$key]['args'] = @array_map('gettype', $trace[$key]['args']);
        }

        // build your tracelines
        $result = array();
        foreach ($trace as $key => $stackPoint) {
            $result[] = sprintf(
                $traceline,
                $key,
                $stackPoint['file'],
                $stackPoint['line'],
                $stackPoint['function'],
                @implode(', ', $stackPoint['args'])
            );
        }
        // trace always ends with {main}
        $result[] = '#' . ++$key . ' {main}';

        // write tracelines into main template
        $msg = sprintf(
            $msg,
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            implode("\n", $result),
            $exception->getFile(),
            $exception->getLine()
        );
        Log::exception($msg);
        //异常处理管理
        $ret     = true;
        $is_deal = false;
        $handles = $this->getDispatcher()->getExceptionHandles();
        foreach ($handles as $key => $deal) {
            if ($exception instanceof $deal['class']) {
                $is_deal = true;
                $ret     = $ret && $deal['func']($exception); //任何一个返回false就抛出异常
            }
        }
        //没人处理或者处理了有错就抛出异常
        if ( ! $is_deal || ! $ret) {
            throw $exception;
        }
    }
    /**
     * 注册自动加载类
     * @return
     */
    private function _iniAutoLoad($module_name) {
        require_once 'AutoLoad.php';
        $this->_autoload = new AutoLoad($module_name);
        $this->_autoload->register();
        return $this;
    }
}