<?php
/**
 * 测试model
 */
namespace Demo\Models;
use Demo\Data\TestData;

class Demo {
    private $_test_data = null;
    public function __construct() {
        $this->_test_data = new TestData();
    }
    public function getList() {
        return $this->_test_data->getList();
    }
    public function getInfo($id) {}
}