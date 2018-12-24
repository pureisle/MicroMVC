<?php
/**
 * Validator类单元测试
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Tests;
use Framework\Libraries\TestSuite;
use Framework\Libraries\Validator;

class TestValidator extends TestSuite {
    const TEST_CLASS_NAME = \Framework\Libraries\Validator::class;
    private $_validator   = null;
    public function beginTest() {
        $this->_validator = new Validator();
    }
    public function testNum() {
        $rule_set = array(
            'num' => 'number'
        );
        $params = array(
            'num' => '5'
        );
        $ret = $this->_validator->check($params, $rule_set);
        $this->assertTrue($ret);
        $params = array(
            'num' => '-5'
        );
        $ret = $this->_validator->check($params, $rule_set);
        $this->assertTrue($ret);
        $params = array(
            'num' => '5.0'
        );
        $ret = $this->_validator->check($params, $rule_set);
        $this->assertTrue($ret);
        $params = array(
            'num' => '5A'
        );
        $ret = $this->_validator->check($params, $rule_set);
        $this->assertFalse($ret);
    }
    public function testAlph() {
        $rule_set = array(
            'alpha' => 'alpha'
        );
        $params = array(
            'alpha' => 'ilovecoding'
        );
        $ret = $this->_validator->check($params, $rule_set);
        // var_dump($ret, $this->_validator->getErrorMsg());
        $this->assertTrue($ret);
        $params = array(
            'alpha' => 'ilovecoding.'
        );
        $ret = $this->_validator->check($params, $rule_set);
        $this->assertFalse($ret);
    }
}