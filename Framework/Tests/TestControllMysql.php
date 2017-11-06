<?php
/**
 * ControllMysql类单元测试
 *
 * ps:新部署的环境需要根据自身情况修改TestData的配置信息
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Tests;
use Framework\Libraries\ControllMysql;
use Framework\Libraries\TestSuite;

class TestControllMysql extends TestSuite {
    public function beginTest() {
        $this->_pdo = new TestData();
    }
    const TEST_ADD_ID  = 9999;
    const TEST_ADD_ID1 = 9998;
    public function testAdd() {
        $data = array(
            'id'            => self::TEST_ADD_ID,
            'name'          => '电影赞',
            'firehose_type' => '2',
            'object_type'   => '1',
            'prefix'        => '1022:100120',
            'url'           => 'http://firehose4.i.api.weibo.com:8082/comet',
            'params'        => 'appid=yueku&filter=like,*',
            'accept_url'    => 'http://i.ting.weibo.com/port/firehoselike/movie'
        );
        $ret = $this->_pdo->add($data);
        $this->assertEq($ret, 1);
        $ret = $this->_pdo->add($data, array('name' => '电影test'));
        $this->assertEq($ret, 2);
    }
    public function testMultiAdd() {
        $data = array(
            array(
                'id'            => self::TEST_ADD_ID,
                'name'          => '电影赞',
                'firehose_type' => '2',
                'object_type'   => '1',
                'prefix'        => '1022:100120',
                'url'           => 'http://firehose4.i.api.weibo.com:8082/comet',
                'params'        => 'appid=yueku&filter=like,*',
                'accept_url'    => 'http://i.ting.weibo.com/port/firehoselike/movie'
            ),
            array(
                'id'            => self::TEST_ADD_ID1,
                'name'          => '电影赞',
                'firehose_type' => '2',
                'object_type'   => '1',
                'prefix'        => '1022:100120',
                'url'           => 'http://firehose4.i.api.weibo.com:8082/comet',
                'params'        => 'appid=yueku&filter=like,*',
                'accept_url'    => 'http://i.ting.weibo.com/port/firehoselike/movie'
            )
        );
        $ret = $this->_pdo->multiAdd($data, array('name' => '电影testMultiAdd'));
        $this->assertEq($ret, 3);
    }
    public function testGetList() {
        //测试获取
        $ret = $this->_pdo->getList(2, 0);
        $this->assertEq(count($ret), 2);
        //测试分页
        $tmp1 = $this->_pdo->getList(1, 0);
        $tmp2 = $this->_pdo->getList(1, 1);
        $tmp  = array($tmp1[0], $tmp2[0]);
        $this->assertEq($tmp, $ret);
        //测试指定字段
        $ret = $this->_pdo->getList(2, 0, array('name', 'url'));
        $this->assertTrue(isset($ret[0]['name']) && isset($ret[0]['url']) && ! isset($ret[0]['id']));
        //测试where
        $where[] = $this->_pdo->buildWhereCondition('id', self::TEST_ADD_ID);
        $ret     = $this->_pdo->getList(-1, 0, [], $where);
        $this->assertEq(self::TEST_ADD_ID, (int) $ret[0]['id']);
        //测试order by
        $where[]  = $this->_pdo->buildWhereCondition('id', self::TEST_ADD_ID1, '=', 'or');
        $order_by = array('id' => 'DESC');
        $ret      = $this->_pdo->getList(2, 0, [], $where, $order_by);
        $this->assertEq(count($ret), 2);
        $this->assertEq((int) $ret[0]['id'], self::TEST_ADD_ID);
        //测试group by
        $group_by = array('firehose_type' => 'DESC');
        $ret      = $this->_pdo->getList(2, 0, [], $where, $order_by, $group_by);
        $this->assertEq(count($ret), 1);
    }
    public function testUpdate() {
        $set_arr = array('prefix' => 'newprefix ');
        $where[] = $this->_pdo->buildWhereCondition('id', self::TEST_ADD_ID);
        $ret     = $this->_pdo->update($set_arr, $where);
        $this->assertEq($ret, 1);
    }
    public function testRemove() {
        $where[] = $this->_pdo->buildWhereCondition('id', self::TEST_ADD_ID);
        $where[] = $this->_pdo->buildWhereCondition('id', self::TEST_ADD_ID1, '=', 'or');
        $ret     = $this->_pdo->remove($where);
        $this->assertEq($ret, 2);
    }
}

class TestData extends ControllMysql {
    const READ_DB_RESOURCE  = 'database_firehose_read';
    const WRITE_DB_RESOURCE = 'database_firehose';
    const TABLE_NAME        = 'firehose_info';
    const MODULE_NAME       = 'Demo';
    public function __construct() {
        parent::__construct(self::TABLE_NAME);
    }
    public function add(array $data, array $duplicate = null) {
        return parent::add($data, $duplicate)->exec(self::WRITE_DB_RESOURCE, self::MODULE_NAME);
    }
    public function multiAdd(array $data, array $duplicate = null) {
        return parent::multiAdd($data, $duplicate)->exec(self::WRITE_DB_RESOURCE, self::MODULE_NAME);
    }
    public function getList(int $count = 10, int $page = 0, array $fields = array(), $where_condition = null, $order_by = null, $group_by = null) {
        return parent::getList($count, $page, $fields, $where_condition, $order_by, $group_by)
            ->exec(self::READ_DB_RESOURCE, self::MODULE_NAME);
    }
    public function update(array $set_arr, $where_condition = null, int $count = -1, $order_by = null) {
        return parent::update($set_arr, $where_condition, $count, $order_by)->exec(self::WRITE_DB_RESOURCE, self::MODULE_NAME);
    }
    public function remove($where_condition, int $count = -1, $order_by = null) {
        return parent::remove($where_condition, $count, $order_by)->exec(self::WRITE_DB_RESOURCE, self::MODULE_NAME);
    }
}