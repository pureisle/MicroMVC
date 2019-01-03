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
    const TEST_CLASS_NAME = \Framework\Libraries\ControllMysql::class;
    public function beginTest() {
        $this->_pdo = new TestData();
    }
    const TEST_ADD_ID  = "10";
    const TEST_ADD_ID1 = "11";
    public function testAdd() {
        $data = array(
            'uid'     => self::TEST_ADD_ID,
            'name'    => 'test',
            'passwd'  => '5f1d7a84db00d2fce00b31a7fc73224f',
            'salt'    => '^G_P}Mr^XIYbYAD,:}U){<6wrhqAa$7-',
            'p_v'     => '0',
            'email'   => 'zhiyuan12test@staff.weibo.com',
            'tel'     => '18611138956',
            'sina_id' => '1051409999'
        );
        $this->_pdo->beginTransaction(TestData::WRITE_DB_RESOURCE);
        $ret = $this->_pdo->add($data);
        $this->assertEq($ret, self::TEST_ADD_ID);
        $ret = $this->_pdo->add($data, array('name' => 'test_duplicate'));
        $this->_pdo->commit(TestData::WRITE_DB_RESOURCE);
        $this->assertEq($ret, self::TEST_ADD_ID);
    }
    public function testMultiAdd() {
        $data = array(
            array(
                'uid'     => self::TEST_ADD_ID,
                'name'    => 'test',
                'passwd'  => '5f1d7a84db00d2fce00b31a7fc73224f',
                'salt'    => '^G_P}Mr^XIYbYAD,:}U){<6wrhqAa$7-',
                'p_v'     => '0',
                'email'   => 'zhiyuan12test1@staff.weibo.com',
                'tel'     => '18622238956',
                'sina_id' => '1051419999'
            ),
            array(
                'uid'     => self::TEST_ADD_ID1,
                'name'    => 'test' . self::TEST_ADD_ID1,
                'passwd'  => '5f1d7a84db00d2fce00b31a7fc73224f',
                'salt'    => '^G_P}Mr^XIYbYAD,:}U){<6wrhqAa$7-',
                'p_v'     => '0',
                'email'   => 'zhiyuan12test3@staff.weibo.com',
                'tel'     => '18611338956',
                'sina_id' => '1051419999'
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
        $ret = $this->_pdo->getList(2, 0, array('name', 'passwd'));
        $this->assertTrue(isset($ret[0]['name']) && isset($ret[0]['passwd']) && ! isset($ret[0]['salt']));
        //测试where
        $where[] = $this->_pdo->buildWhereCondition('uid', self::TEST_ADD_ID);
        $ret     = $this->_pdo->getList(-1, 0, [], $where);
        $this->assertEq(self::TEST_ADD_ID, $ret[0]['uid']);
        //测试order by
        $where[]  = $this->_pdo->buildWhereCondition('uid', self::TEST_ADD_ID1, '=', 'or');
        $order_by = array('uid' => 'DESC');
        $ret      = $this->_pdo->getList(2, 0, [], $where, $order_by);
        $this->assertEq(count($ret), 2);
        $this->assertEq($ret[0]['uid'], self::TEST_ADD_ID1);
        //测试group by
        $group_by = array('passwd' => 'DESC');
        $ret      = $this->_pdo->getList(2, 0, [], $where, $order_by, $group_by);
        $this->assertEq(count($ret), 1);
    }
    public function testCount() {
        $where[] = $this->_pdo->buildWhereCondition('uid', self::TEST_ADD_ID);
        $ret     = $this->_pdo->count($where);
        $this->assertEq((int) $ret[0]['count'], 1);
    }
    public function testUpdate() {
        $set_arr = array(
            'name'  => 'newname',
            'p_v'   => $this->_pdo->fieldIncrease(-4),
            'email' => $this->_pdo->useFunc('replace(`email`,{2},{3})', array('{2}' => 'zhiyuan12', '{3}' => 'zy'))
        );
        $where[] = $this->_pdo->buildWhereCondition('uid', self::TEST_ADD_ID);
        $ret     = $this->_pdo->update($set_arr, $where);
        $this->assertEq($ret, 1);
    }
    public function testRemove() {
        $where[] = $this->_pdo->buildWhereCondition('uid', self::TEST_ADD_ID);
        $where[] = $this->_pdo->buildWhereCondition('uid', self::TEST_ADD_ID1, '=', 'or');
        $ret     = $this->_pdo->remove($where);
        $this->assertEq($ret, 2);
    }
}

class TestData extends ControllMysql {
    const READ_DB_RESOURCE  = 'database:sso_read';
    const WRITE_DB_RESOURCE = 'database:sso';
    const TABLE_NAME        = 'sso_user';
    const MODULE_NAME       = 'Sso';
    public function __construct() {
        parent::__construct(self::TABLE_NAME, self::MODULE_NAME);
    }
    public function add(array $data, $duplicate = null) {
        return parent::add($data, $duplicate)->exec(self::WRITE_DB_RESOURCE);
    }
    public function multiAdd(array $data, $duplicate = null) {
        return parent::multiAdd($data, $duplicate)->exec(self::WRITE_DB_RESOURCE);
    }
    public function count($where_condition = NULL, $group_by = NULL) {
        return parent::count($where_condition)->exec(self::READ_DB_RESOURCE);
    }
    public function getList(int $count = 10, int $page = 0, $fields = array(), $where_condition = null, $order_by = null, $group_by = null) {
        return parent::getList($count, $page, $fields, $where_condition, $order_by, $group_by)
            ->exec(self::READ_DB_RESOURCE);
    }
    public function getLastSql() {
        return parent::getLastSql();
    }
    public function update(array $set_arr, $where_condition = null, int $count = -1, $order_by = null) {
        return parent::update($set_arr, $where_condition, $count, $order_by)->exec(self::WRITE_DB_RESOURCE);
    }
    public function remove($where_condition, int $count = -1, $order_by = null) {
        parent::remove($where_condition, $count, $order_by);
        $sql = parent::getLastSql();
        return parent::exec(self::WRITE_DB_RESOURCE);
    }
    public function beginTransaction(string $resource_name) {
        return parent::beginTransaction($resource_name);
    }
    public function commit(string $resource_name) {
        return parent::commit($resource_name);
    }
    public function rollback(string $resource_name) {
        return parent::rollback($resource_name);
    }
}