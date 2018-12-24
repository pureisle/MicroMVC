<?php
/**
 * Debug类单元测试
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Tests;
use Framework\Entities\PDOConfig;
use Framework\Libraries\Debug;
use Framework\Libraries\PDOManager;
use Framework\Libraries\TestSuite;

class TestPDOManager extends TestSuite {
    const TEST_CLASS_NAME     = \Framework\Libraries\PDOManager::class;
    private $_pdo             = null;
    private $_test_table_name = 'firehose_info';
    public function beginTest() {
        $this->_pdo;
        $pdo_config           = new PDOConfig();
        $pdo_config->host     = 'xxx';
        $pdo_config->port     = 'xxx';
        $pdo_config->username = 'xxx';
        $pdo_config->password = 'xxx';
        $pdo_config->dbname   = 'xxx';
        $pdo                  = new PDOManager($pdo_config);
        $this->_pdo           = $pdo;
    }
    public function testGetAttribute() {
        // var_dump($this->_pdo->getAttribute());
    }
    public function testGetErrorInfo() {
        // var_dump($this->_pdo->getErrorInfo());
    }
    public function testGetAvailableDrivers() {
        // var_dump($this->_pdo->getAvailableDrivers());
    }
    public function testExec() {
        $limit = 5;
        $sql   = 'select * from %s limit ' . $limit;
        $ret   = $this->_pdo->exec(sprintf($sql, $this->_test_table_name));
        $this->assertEq($ret, $limit); //空表会报错
    }
    public function testQuery() {
        $sql    = 'select * from %s  where prefix = :prefix and object_type= :object_type';
        $params = array(':prefix' => '1022:100153', ':object_type' => 1);
        $ret    = $this->_pdo->query(sprintf($sql, $this->_test_table_name), $params);

        $params = array(':prefix' => '1022:100153', ':object_type' => 2);
        $ret    = $this->_pdo->query(sprintf($sql, $this->_test_table_name), $params);
        $this->assertEq($ret[0]['prefix'], $params[':prefix']); //空表会报错
    }

}
