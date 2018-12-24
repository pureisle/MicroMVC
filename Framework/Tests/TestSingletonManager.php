<?php
/**
 * 单例工厂类单元测试
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Tests;
use Framework\Libraries\SingletonManager;
use Framework\Libraries\TestSuite;

class TestSingletonManager extends TestSuite {
    const TEST_CLASS_NAME    = \Framework\Libraries\SingletonManager::class;
    public $test_var         = '';
    private $test_class_name = 'Framework\Tests\TestSingletonManager';
    public function testNewClass() {
        $class           = SingletonManager::$SINGLETON_POOL->getInstance($this->test_class_name);
        $class->test_var = 333;
        $class1          = SingletonManager::$SINGLETON_POOL->getInstance($this->test_class_name);
        $this->assertEq(md5(serialize($class)), md5(serialize($class1)));

        $class           = SingletonManager::$SINGLETON_POOL->getInstance($this->test_class_name, 'test_args');
        $class->test_var = 222;
        $class1          = SingletonManager::$SINGLETON_POOL->getInstance($this->test_class_name, 'test_args');
        $this->assertEq(md5(serialize($class)), md5(serialize($class1)));
    }
    public function testNewClassByInit() {
        $class = SingletonManager::$SINGLETON_POOL->getInstanceWithInit($this->test_class_name, function ($object) {
            $object->test_var = 555;
            return true;
        });
        $class1 = SingletonManager::$SINGLETON_POOL->getInstance($this->test_class_name);
        $this->assertEq(md5(serialize($class)), md5(serialize($class1)));

    }
}