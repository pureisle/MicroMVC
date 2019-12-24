<?php
/**
 * Context类单元测试
 *
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Tests;
use Framework\Libraries\Context;
use Framework\Libraries\TestSuite;

class TestContext extends TestSuite {
    const TEST_CLASS_NAME = \Framework\Libraries\Context::class;
    public function beginTest() {
        $this->t = new Context();
    }
    public function testmSet() {
        $data = array(
            'a' => 1,
            'b' => 2,
            'c' => 3
        );
        $this->t->mSet($data);
        $this->assertEq($this->t->a, $data['a']);
        $this->assertEq($this->t->b, $data['b']);
        $this->assertEq($this->t->c, $data['c']);
        $tmp        = 5;
        $this->t->c = $tmp;
        $this->assertEq($this->t->c, $tmp);

    }
    public function testmGet() {
        $ret = Context::mGet(array('a', 'b'));
        $this->assertEq(isset($ret['a']), true);
    }
}