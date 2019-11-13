<?php
/**
 * 用户session数据层
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Sso\Data;
use Framework\Libraries\ControllMysql;

class Session extends ControllMysql {
    const READ_DB_RESOURCE  = 'database:sso_read';
    const WRITE_DB_RESOURCE = 'database:sso';
    const TABLE_NAME        = 'sso_session';
    const MYSQL_STRUCT      = <<<EOT
CREATE TABLE `sso_session` (
  `sid` varchar(32) NOT NULL DEFAULT '',
  `expire` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data` varchar(512) NOT NULL DEFAULT '',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`sid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
EOT;
    public function __construct() {
        parent::__construct(self::TABLE_NAME);
    }
    public function addSession(string $sid, string $data, string $expire) {
        if (empty($sid) || empty($data) || empty($expire)) {
            return false;
        }
        $insert_data['sid']    = $sid;
        $insert_data['data']   = $data;
        $insert_data['expire'] = $expire;
        $set_arr               = array(
            'data'   => $insert_data['data'],
            'expire' => $insert_data['expire']
        );
        return parent::add($insert_data, $set_arr)->exec(self::WRITE_DB_RESOURCE);
    }
    public function updateSession(string $sid, string $data = null, string $expire = null) {
        if (empty($sid) || (empty($data) && empty($expire))) {
            return 0;
        }
        if ( ! empty($data)) {
            $set_arr['data'] = $data;
        }
        if ( ! empty($expire)) {
            $set_arr['expire'] = $expire;
        }
        $where[] = parent::buildWhereCondition('sid', $sid, '=', 'AND');
        return parent::update($set_arr, $where)->exec(self::WRITE_DB_RESOURCE);
    }
    public function getById(string $sid) {
        if (empty($sid)) {
            return false;
        }
        $where[] = parent::buildWhereCondition('sid', $sid);
        $tmp     = parent::getList(-1, -1, array(), $where)->exec(self::READ_DB_RESOURCE);
        if (empty($tmp)) {
            return array();
        }
        $ret = $tmp[0];
        return $ret;
    }
    public function removeExpire() {
        $where[] = $this->buildWhereCondition('expire', date('Y-m-d H:i:s'), '<=');
        parent::remove($where);
        parent::exec(self::WRITE_DB_RESOURCE);
    }
    public function removeById($sid) {
        if (empty($sid)) {
            return 0;
        }
        $where[] = $this->buildWhereCondition('sid', $sid, '=', 'AND');
        return parent::remove($where)->exec(self::WRITE_DB_RESOURCE);
    }
}