<?php
namespace Demo\Tests;
use Demo\Cache\Demo;
use Demo\Data\TestData;
use Framework\Libraries\TestSuite;

class DemoTest extends TestSuite {
    public function beginTest() {}
    public function testDataDemo() {
        new TestData();
    }
    public function testCacheDemo() {
        $cache = new Demo();
        $id    = 2;
        $value = 'hahah';
        // var_dump($cache->getInfo($id));
        // var_dump($cache->setInfo($id, $value));
        // var_dump($cache->appendInfo($id, $value));
        // var_dump($cache->getInfo($id));
    }
}