<?php
/**
 * Redis类单元测试
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Tests;
use Framework\Libraries\KeyBuilder;
use Framework\Libraries\TestSuite;

class TestKeyBuilder extends TestSuite {
    const TEST_CLASS_NAME = \Framework\Libraries\KeyBuilder::class;
    public function beginTest() {
        $kb             = new KeyBuilder();
        $key_sets_index = 'demo';
        $param          = array('id' => 123);
        $kb->buildKey($key_sets_index, $param);
    }
}