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
    		$redis = new Redis('redis:session','Sso');
    	} catch (\Exception $e) {
    		var_dump($e->getMessage());
    	}
        
    }
}