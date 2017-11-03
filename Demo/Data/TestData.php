<?php
namespace Demo\Data;
use Framework\Libraries\ControllMysql;

class TestData extends ControllMysql {
    const READ_DB_RESOURCE  = 'database_firehose_read';
    const WRITE_DB_RESOURCE = 'database_firehose';
    const TABLE_NAME        = 'firehose_info';
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