<?php
/**
 * 有限状态机类单元测试
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Tests;
use Framework\Libraries\TestSuite;

class TestGlobalFunctions extends TestSuite {
    public function testHookTrigger() {
        hook_trigger('test');
    }
    public function testHook() {
        $obj = single_instance(\Framework\Libraries\Curl::class);
        $this->assertTypeOf(\Framework\Libraries\Curl::class, $obj);
    }
}