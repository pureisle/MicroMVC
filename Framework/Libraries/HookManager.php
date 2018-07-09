<?php
/**
 * 钩子管理
 *
 * 为了清晰罗列和强化钩子管理，所以设计为强制使用配置文件的形式来定义业务所需要的钩子。
 *     array(
 *      'login' => array( // 下标为 定义的钩子触发的 key
 *           'params' => array(  // 定义钩子触发时传递的参数列表
 *               'name'   => '登录用户名', // 参数1
 *               'passwd' => '登录密码'  //参数2
 *           ),
 *           'list'   => array(  // 所执行的钩子列表
 *               array('class' => '', 'function' => '\Framework\Tests\fun'),
 *                 array(
 *                  'class'     => '\Framework\Tests\Test',  //触发执行的类
 *                  'class_ini' => array(),// 如果是非静态方法，则会使用该参数实例化类
 *                  'function'  => 'fun', //执行的方法
 *                  'is_static' => false //是否是静态方法
 *              )
 *           )
 *       ),
 *       ...
 *     );
 * 如果钩子执行函数有致命错误，请直接抛出异常。此异常无论catch与否,后边钩子均不再执行。
 *
 * 加载钩子时调用：  HookManager::$GLOBAL_HOOKS->register(string $config_name, string $module);
 * 触发钩子时调用：  HookManager::$GLOBAL_HOOKS->trigger(string $key);
 *
 * 如果不想使用全局钩子，则可自己实例化 new HookManager() ->register(string $config_name, string $module);
 *
 * 再次提醒使用钩子的时候，明白订阅者模式适合的场景。
 *
 *  @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
class HookManager {
    public static $GLOBAL_HOOKS;
    private $_hooks   = array();
    private $_hook_id = 0;
    /**
     * 注册钩子
     * @param    string $config_name
     * @param    string $module
     * @return
     */
    public function register(string $config_name, string $module) {
        $config = ConfigTool::loadByName($config_name, $module);
        if (empty($config)) {
            return false;
        }
        foreach ($config as $key => $function_set) {
            if (empty($function_set['list'])) {
                continue;
            }
            foreach ($function_set['list'] as $function) {
                $this->_hooks[$key][$this->_hook_id] = $function;
                $this->_hook_id++;
            }
        }
    }
    /**
     * 移除制定hook
     * @param string $key
     * @param int    $hook_id
     */
    public function remove(string $key, int $hook_id) {
        if (isset($this->_hooks[$key]) && isset($this->_hooks[$key][$hook_id])) {
            unset($this->_hooks[$key][$hook_id]);
        }
    }
    /**
     * 获取所有的hook
     * @param    string $key
     * @return
     */
    public function getHooks(string $key) {
        if ( ! isset($this->_hooks[$key])) {
            return array();
        }
        return $this->_hooks[$key];
    }
    /**
     * 触发钩子
     * @param    string $key
     * @return
     */
    public function trigger(string $key) {
        $args = func_get_args();
        array_shift($args);
        if (isset($this->_hooks[$key])) {
            foreach ($this->_hooks[$key] as $func) {
                if ( ! empty($func['class'])) {
                    $function = $func['function'];
                    if ($func['is_static']) {
                        $func['class']::$function(...$args);
                    } else {
                        $class = SingletonManager::$SINGLETON_POOL->getInstance($func['class'], ...$func['class_ini']);
                        $class->$function(...$args);
                    }
                } else {
                    $func['function'](...$args);
                }
            }
        }
    }
}
class HookManagerException extends Exception {
    const ERROR_CLASS_NOT_EXIST = 1;
    public $ERROR_SET           = array(
        self::ERROR_CLASS_NOT_EXIST => array(
            'code'    => self::ERROR_CLASS_NOT_EXIST,
            'message' => 'class not found'
        )
    );
}
HookManager::$GLOBAL_HOOKS = new HookManager();