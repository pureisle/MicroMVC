<?php
/**
 * 用户数据层
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Sso\Data;
use Framework\Libraries\ControllMysql;
use Sso\Models\Log;

class User extends ControllMysql {
    const READ_DB_RESOURCE  = 'database:sso_read';
    const WRITE_DB_RESOURCE = 'database:sso';
    const TABLE_NAME        = 'sso_user';
    const MYSQL_STRUCT      = <<<EOT
CREATE TABLE `sso_user` (
  `uid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `passwd` varchar(64) NOT NULL DEFAULT '',
  `salt` varchar(32) NOT NULL DEFAULT '0',
  `p_v` tinyint(4) NOT NULL DEFAULT '0',
  `email` varchar(128) NOT NULL DEFAULT '',
  `tel` varchar(64) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `extend` text,
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `tel_key` (`tel`),
  UNIQUE KEY `email_key` (`email`),
  UNIQUE KEY `name_key` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4;
EOT;
    public function __construct() {
        parent::__construct(self::TABLE_NAME);
    }
    /**
     * 添加一个用户
     * @param string      $name   [description]
     * @param string|null $email  [description]
     * @param string|null $tel    [description]
     * @param array       $extend [description]
     */
    public function addUser(string $name, string $passwd, string $salt, string $p_v, string $email = null, string $tel = null, array $extend = array()) {
        if (empty($email)) {
            $email = $this->_nullEncode($name);
        }
        if (empty($tel)) {
            $tel = $this->_nullEncode($name);
        }
        $data = array(
            'name'   => $name,
            'passwd' => $passwd,
            'salt'   => $salt,
            'p_v'    => $p_v,
            'email'  => $email,
            'tel'    => $tel,
            'extend' => $this->_extendEncode($extend)
        );
        return parent::add($data)->_exec(self::WRITE_DB_RESOURCE);
    }
    /**
     * 分页获取id倒序数据 或获取指定id数据
     * @param  int     $count
     * @param  int     $page
     * @return array
     */
    public function getListInfo(int $count = 10, int $page = 0, string $order = 'DESC') {
        $where = array();
        if ('DESC' === $order) {
            $order_by = array('uid' => 'DESC');
        } else {
            $order_by = array('uid' => 'ASC');
        }
        parent::getList($count, $page, array(), $where, $order_by);
        $ret = $this->_exec(self::READ_DB_RESOURCE);
        foreach ($ret as $key => $value) {
            $this->_formatData($value);
            $ret[$key] = $value;
        }
        return $ret;
    }
    /**
     * 获取用户信息
     * @param  int     $uid
     * @return array
     */
    public function getInfoByUid(int $uid) {
        $where[] = parent::buildWhereCondition('uid', $uid);
        parent::getList(-1, -1, array(), $where);
        $tmp = $this->_exec(self::READ_DB_RESOURCE);
        if (empty($tmp)) {
            return array();
        }
        $tmp = $tmp[0];
        $this->_formatData($tmp);
        return $tmp;
    }
    /**
     * 根据用户名字查询信息
     * @param  string  $name
     * @return array
     */
    public function getInfoByName(string $name) {
        $where[] = parent::buildWhereCondition('name', $name);
        $tmp     = parent::getList(-1, -1, array(), $where)->_exec(self::READ_DB_RESOURCE);
        if (empty($tmp)) {
            return array();
        }
        $tmp = $tmp[0];
        $this->_formatData($tmp);
        return $tmp;
    }
    public function getInfoByEmail(string $email) {}
    private function _formatData(&$data) {
        $data['name']   = $this->_nullDecode($data['name']);
        $data['email']  = $this->_nullDecode($data['email']);
        $data['tel']    = $this->_nullDecode($data['tel']);
        $data['extend'] = $this->_extendDecode($data['extend']);
    }
    /**
     *     修改数据
     * 参数为null意味着不修改
     * @param  int     $uid            [description]
     * @param  array
     * @return [type]  [description]
     */
    public function updateByUid(int $uid, array $data) {
        if (empty($data)) {
            return 0;
        }
        extract($data);
        $set_arr = array();
        $this->_updateVarCheck($name, $uid, 'name', $set_arr);
        $this->_updateVarCheck($email, $uid, 'email', $set_arr);
        $this->_updateVarCheck($tel, $uid, 'tel', $set_arr);
        if ( ! empty($passwd)) {
            $set_arr['passwd'] = $passwd;
        }
        if ( ! empty($salt)) {
            $set_arr['salt'] = $salt;
        }
        if ( ! empty($p_v)) {
            $set_arr['p_v'] = $p_v;
        }
        if (isset($status)) {
            $set_arr['status'] = $status;
        }
        if (null !== $extend) {
            $set_arr['extend'] = $this->_extendEncode($extend);
        }
        $where[] = $this->buildWhereCondition('uid', $uid, '=', 'AND');
        return parent::update($set_arr, $where)->_exec(self::WRITE_DB_RESOURCE);
    }
    private function _updateVarCheck($var, int $uid, string $name, array &$set_arr) {
        if (null !== $var) {
            if (empty($var)) {
                $var = $this->_nullEncode($uid);
            }
            $set_arr[$name] = $var;
        }
    }
    /**
     * 删除数据
     * @param  int   $uid
     * @return int
     */
    public function removeByUid(int $uid) {
        $where[] = $this->buildWhereCondition('uid', $uid, '=', 'AND');
        return parent::remove($where)->_exec(self::WRITE_DB_RESOURCE);
    }
    private function _extendEncode(array $extend) {
        return json_encode($extend);
    }
    private function _extendDecode(string $extend_str) {
        return json_decode($extend_str, true);
    }
    const NULL_PREFIX = 'NULL_:_';
    private function _nullEncode(string $index) {
        return self::NULL_PREFIX . $index;
    }
    private function _nullDecode(string $str) {
        if (substr_compare($str, self::NULL_PREFIX, 0, strlen(self::NULL_PREFIX) - 1) === 0) {
            return '';
        } else {
            return $str;
        }
    }
    private function _exec(string $db_config_name) {
        //记录sql日志
        $tmp = parent::getLastSql();
        Log::SqlLog($tmp['sql'], $tmp['params']);
        $ret = parent::exec($db_config_name);
        return $ret;
    }
}