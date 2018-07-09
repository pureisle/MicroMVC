<?php
/**
 * HookManager类单元测试
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Tests;
use Framework\Libraries\HookManager;
use Framework\Libraries\TestSuite;

class Test {
    private $_c = 0;
    public function __construct($c) {
        $this->_c = $c;
    }
    public static function fun1($a, $b) {
        var_dump($a + $b);
    }
    public function fun2($a, $b) {
        var_dump($a + $b + $this->_c);
    }
}
function fun($a, $b) {
    var_dump($a + $b);
}
class TestHookManager extends TestSuite {
    private $_obj = null;
    public function beginTest() {
        $this->_obj = HookManager::$GLOBAL_HOOKS;
    }
    public function testHook() {
        $key    = 'test_key';
        $config = array(
            $key => array(     // 下标为 定义的钩子触发的 key
                'params' => array( // 定义钩子触发时传递的参数列表
                    'a' => '',         // 参数1
                    'b' => ''         //参数2
                ),
                'list'   => array( // 所执行的钩子列表
                    array('class' => '', 'function' => '\Framework\Tests\fun'),
                    array(
                        'class'     => '\Framework\Tests\Test', //触发执行的类
                        'class_ini' => array(),                 // 如果是非静态方法，则会使用该参数实例化类
                        'function'  => 'fun1',                  //执行的方法
                        'is_static' => true                    //是否是静态方法
                    ),
                    array(
                        'class'     => '\Framework\Tests\Test', //触发执行的类
                        'class_ini' => array(4),                // 如果是非静态方法，则会使用该参数实例化类
                        'function'  => 'fun2',                  //执行的方法
                        'is_static' => false                   //是否是静态方法
                    )
                )
            )
        );
        //测试的时候需要把 HookManager 的 _hook 开启成public
        // $this->_obj->_hooks = array(
        //     $key => $config[$key]['list']
        // );
        // var_dump($this->_obj->getHooks($key));
        // $this->_obj->trigger($key, 1, 2);
        // $this->_obj->remove($key, 1);
        // var_dump($this->_obj->getHooks($key));
    }
}
