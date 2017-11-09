<?php
namespace Demo\Data;
use Framework\Libraries\ControllMysql;

class TestData extends ControllMysql {
    const READ_DB_RESOURCE  = 'database_firehose_read';
    const WRITE_DB_RESOURCE = 'database_firehose';
    const TABLE_NAME        = 'firehose_info';
    const MYSQL_STRUCT      = <<<EOT
 CREATE TABLE `firehose_info` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `firehose_type` int(5) NOT NULL DEFAULT '0',
  `object_type` int(5) NOT NULL DEFAULT '0',
  `prefix` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `type_key` (`firehose_type`)
) ENGINE=InnoDB AUTO_INCREMENT=10000 DEFAULT CHARSET=utf8
EOT;
    public function __construct() {
        parent::__construct(self::TABLE_NAME);
    }
    /**
     * 写入一条数据
     * @param string $name
     * @param string $firehose_type
     * @param string $object_type
     * @param string $prefix
     */
    public function addInfo(string $name, string $firehose_type, string $object_type, string $prefix) {
        $data = array(
            'name'          => $name,
            'firehose_type' => $firehose_type,
            'object_type'   => $object_type,
            'prefix'        => $prefix
        );
        return parent::add($data)->exec(self::WRITE_DB_RESOURCE);
    }
    /**
     * 分页获取id倒序数据 或获取指定id数据
     * @param  int     $count
     * @param  int     $page
     * @param  int     $id
     * @return array
     */
    public function getListInfo(int $count = 10, int $page = 0, int $id = null) {
        $where = array();
        if (isset($id)) {
            $where[] = $this->buildWhereCondition('id', $id, '=', 'AND');
        }
        $order_by = array('id' => 'DESC');
        return parent::getList($count, $page, array(), $where_condition, $order_by)
            ->exec(self::READ_DB_RESOURCE);
    }
    /**
     * 修改数据
     * @param  array       $set_arr         [description]
     * @param  [type]      $where_condition [description]
     * @param  int|integer $count           [description]
     * @param  [type]      $order_by        [description]
     * @return [type]      [description]
     */
    public function updateById(int $id, string $name, string $firehose_type, string $object_type, string $prefix) {
        $set_arr = array(
            'name'          => $name,
            'firehose_type' => $firehose_type,
            'object_type'   => $object_type,
            'prefix'        => $prefix
        );
        $where[] = $this->buildWhereCondition('id', $id, '=', 'AND');
        return parent::update($set_arr, $where)->exec(self::WRITE_DB_RESOURCE);
    }
    /**
     * 删除数据
     * @param  int   $order_by
     * @return int
     */
    public function removeById($id) {
        $where[] = $this->buildWhereCondition('id', $id, '=', 'AND');
        return parent::remove($where)->exec(self::WRITE_DB_RESOURCE);
    }
}