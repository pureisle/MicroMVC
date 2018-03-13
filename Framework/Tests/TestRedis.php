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
    public function beginTest() {
    	try {
    		$redis = new Redis('redis.resource:business','Demo');
    	} catch (\Exception $e) {
    		var_dump($e->getMessage());
    	}
        
    }
}