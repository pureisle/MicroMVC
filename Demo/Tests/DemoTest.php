<?php
namespace Demo\Tests;
use Demo\Data\TestData;
use Framework\Libraries\TestSuite;

class DemoTest extends TestSuite {
    public function beginTest() {
        new TestData();
    }
    public function testDemo() {}
}