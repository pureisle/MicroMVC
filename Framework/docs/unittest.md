### 单元测试

### 相关文件
```
|- Framework	框架
	|- Libraries	框架类库
		|- UnitTest.php 	单元测试控制器类
		|- TestSuite.php	测试基类
	|- Tests	单元测试代码存放目录
|- public  框架公开访问位置
	|- run_test.php 	单元测试入口
```

### 使用方法
1. 在自己的项目的Test目录下创建测试代码文件 TestA.php ,代码样例如下：
```
<?php
/**
 * 单元测试类
 */
namespace 项目名\Tests;
use Framework\Libraries\TestSuite;

class TestA extends TestSuite {
	const TEST_CLASS_NAME = \Framework\Libraries\Obj::class;
	/**
     * 单例测试方法前调用，一般覆盖使用
     */
    public function setUp() {}
    /**
     * 单例测试方法后调用，一般覆盖使用
     */
    public function cleanUp() {}
    /**
     * 单例测试开始前调用，一般覆盖使用
     */
    public function beginTest() {}
    /**
     * 单例测试结束后调用，一般覆盖使用
     */
    public function endTest() {}
    /**
     * 想测试的方法
     */
	public function testFuncA() {
		$ret=$obj->action();
		$this->assertTrue($ret);
	}
}
```
1. 编写自己需要的测试单元，例如上例代码中的 testFuncA() 方法。需要注意的是，单元测试的方法必须以 "test" 开头。父类提供了一些钩子，会在相应的时机调用，可以帮助测试使用人完成自己想做的事情。这些钩子如上述代码中的: setUp, cleanUp, beginTest, endTest.
1. shell环境下运行自己的测试单元代码：
```
# php public/run_test.php 项目名 TestA.php
```
如果不写具体的测试单元文件，则会运行相应项目下的所有单元测试文件。  
如果不写项目名称，则会运行包括框架在内的所有项目的测试文件。
1. 特别的，如果在单元测试类中定义常量 "TEST_CLASS_NAME", 并且安装了 php 的 xdebug 扩展，则会在测试报告中增加测试单元的代码覆盖度和方法覆盖度报告。输出报告类似如下：
```
0.89227000 1545788241	xxx\Tests\TestA test start running.
0.98458400 1545788241	Running xxx\Tests\TestA.testFuncA
0.98584700 1545788241	[PASSED] test case xxx\Tests\TestA.testFuncA passed
0.98646800 1545788241	[RESULT] passed 15/15 case(s)
0.03972000 1545788242	[TEST COVERAGE] class xxx\xxx\Obj coverage : 44.17 %
0.03976300 1545788242	[TEST COVERAGE] method coverage : 17/28
0.04611800 1545788242	[RESULT] run all test suite passed
```
1. 另外，测试数据会写入框架统一的日志里，以便查阅。

### 源码实现
