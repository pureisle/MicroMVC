<?php
/**
 * Redis类单元测试
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Tests;
use Framework\Libraries\Redis;
use Framework\Libraries\TestSuite;

class TestRedis extends TestSuite {
    const TEST_CLASS_NAME = \Framework\Libraries\Redis::class;
    public function beginTest() {
        try {
            $redis = new Redis('redis:session', 'Sso');
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }

    }
}