<?php
/**
 * Debug类单元测试
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Tests;
use Framework\Libraries\Degrader;
use Framework\Libraries\TestSuite;
use Framework\Libraries\Tools;

class TestDegrader extends TestSuite {
    const TEST_CLASS_NAME = Degrader::class;
    /**
     * 测试设置和获取方法
     */
    public function testHook() {
        Tools::setEnv(Tools::ENV_DEV);
        $t   = new Degrader('degrader', 'Framework');
        $d_c = 0;
        for ($i = 0; $i < 100; $i++) {
            Degrader::hook('key3', function () use (&$d_c) {$d_c++;});
        }
        var_dump($d_c);
        // var_dump($t);
    }
}