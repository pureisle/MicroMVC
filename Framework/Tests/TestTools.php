<?php
/**
 * 单例工厂类单元测试
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Tests;
use Framework\Libraries\TestSuite;
use Framework\Libraries\Tools;

class TestTools extends TestSuite {
    const TEST_CLASS_NAME = Tools::class;
    public function testretryAgent() {
        $i          = 1;
        $sleep      = 500;
        $retry_num  = 3;
        $begin_time = time();
        $ret        = Tools::retryAgent(function () use ($i) {
            $i++;
            return $i;
        }, $retry_num, $sleep, function ($ret) use ($i) {
            if ($i === $ret) {
                return true;
            } else {
                return false;
            }
        });
        $end_time = time();
        $this->assertEq($ret, $i + 1);
        $this->assertTrue(($end_time - $begin_time) >= ($sleep * $retry_num / 1000));
        $begin_time = time();
        $sleep      = -500;
        $e_msg      = '';
        $ret        = Tools::retryAgent(function () use ($i) {
            throw new \Exception("Error Processing Request", 1);
            $i++;
            return $i;
        }, $retry_num, $sleep, function ($ret) use ($i) {
            if ($i === $ret) {
                return true;
            } else {
                return false;
            }
        }, function ($e) use (&$e_msg) {
            $e_msg = 'catch exception';
        });
        $end_time = time();
        $this->assertEq($e_msg, 'catch exception');
    }
}