<?php
/**
 * Debug类单元测试
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Tests;
use Framework\Libraries\EntityBase;
use Framework\Libraries\EntityBaseException;
use Framework\Libraries\TestSuite;

class TestEntityBase extends TestSuite {
    const TEST_CLASS_NAME = EntityBase::class;
    private $_test_obj    = null;
    public function beginTest() {
        $this->_test_obj = new MyEntity();
    }
    /**
     * 测试设置和获取方法
     */
    public function testini() {
        $this->_test_obj->ini(array('a' => 'a', 'c' => 'c'));
        $this->assertTrue('a' === $this->_test_obj->a);
        $this->assertTrue('c' === $this->_test_obj->c);
    }
    public function testassignment() {
        $this->_test_obj->d = 'd';
        $this->assertTrue('d' !== $this->_test_obj->d);
        $this->_test_obj->a = 'd';
        $this->assertTrue('d' === $this->_test_obj->a);
    }
    public function testvalidator() {
        try {
            $this->_test_obj->b = 'd';
        } catch (EntityBaseException $e) {
            //var_dump($e);
        }
        $this->assertTrue('d' !== $this->_test_obj->b);
        $this->_test_obj->b = '123';
        $this->assertTrue('123' === $this->_test_obj->b);
    }
    public function testcheckRequirement() {
        $flag = false;
        try {
            $this->_test_obj->checkRequirement();
        } catch (EntityBaseException $e) {
            $flag               = true;
            $this->_test_obj->x = array(123);
        }
        $this->assertTrue($flag);
        $flag = false;
        try {
            $this->_test_obj->checkRequirement();
        } catch (EntityBaseException $e) {
            $flag               = true;
            $this->_test_obj->y = 123;
        }
        $this->assertTrue($flag);

    }
}

class MyEntity extends EntityBase {
    public static $DATA_STRUCT_INFO = array(
        'a' => 1,
        'b' => 2,
        'c' => '',
        'x' => array(),
        'y' => '',
        'z' => '0'
    );
    public static $DATA_VALIDATOR_INFO = array(
        'a' => 'requirement',
        'b' => 'number',
        'x' => 'requirement',
        'y' => 'requirement',
        'z' => 'requirement'
    );
}