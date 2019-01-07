### 数据库SQL生成工具

### 相关文件
```
|- Framework	框架
	|- Libraries	框架类库
		|- ControllMysql.php 	SQL生产类
		|- PDOManager.php 	PDO继承类
	|- Test		单元测试
		|- TestControllMysql.php 	单元测试类
```

### 快速开始使用
为了让继承类能更清晰的展示自身所提供的方法，所以 ControllMysql 仅提供继承使用方式使用，使用方法样例如下代码：
```
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
    private function _exec(string $db_config_name) {
        //记录sql日志
        $tmp = parent::getLastSql();
        Log::SqlLog($tmp['sql'], $tmp['params']);
        $ret = parent::exec($db_config_name);
        return $ret;
    }
}
```
### 工具使用细节
1. 增加数据。使用方法 ControllMysql::add(array $data, $duplicate = null) ，其中 $data 为 MySQL 中的字段映射数组，如 array('field_A'=>'value_A','field_B'=>'value_b') ； 第二个参数 $duplicate 为主键或唯一索引冲突时，更新字段，其数据结构类似 $data ，为字段映射数组。    
特别的，当 $duplicate 需要使用字段自增或MySQL内置函数时，需要用到 ControllMysql::fieldIncrease($num) 和 ControllMysql::useFunc(string $func_name, $params = array()) 方法。如：  
```
 $duplicate_arr = array(
    'name'  => 'newname',
    'p_v'   => $this->_pdo->fieldIncrease(1),
    'email' => $this->_pdo->useFunc('replace(`email`,{2},{3})', array('{2}' => 'zhiyuan12', '{3}' => 'zy'))
);
```
上述 $duplicate_arr 参数的意义是，有主键或唯一索引冲突时，修改 name 为 newname，修改 p_v 字段自增加 1，修改 email 字段进行字符串替换。  
1. 批量增加数据。使用方法 ControllMysql::multiAdd(array $data_arr, $duplicate = null)，其中 $duplicate 和 add() 方法相同，$data_arr 参数为两维数组，类似多个 add()方法中的 $data 组合成的二维数组，类似如下：  
```
 $data_arr = array(
            array(
                'uid'     => self::TEST_ADD_ID,
                'name'    => 'test',
                'passwd'  => '5f1d7a84db00d2fce00b31a7fc73224f',
                'salt'    => '^G_P}Mr^XIYbYAD,:}U){<6wrhqAa$7-',
                'p_v'     => '0',
                'email'   => 'zhiyuan12test1@staff.weibo.com',
                'tel'     => '18622238956',
            ),
            array(
                'uid'     => self::TEST_ADD_ID1,
                'name'    => 'test' . self::TEST_ADD_ID1,
                'passwd'  => '5f1d7a84db00d2fce00b31a7fc73224f',
                'salt'    => '^G_P}Mr^XIYbYAD,:}U){<6wrhqAa$7-',
                'p_v'     => '0',
                'email'   => 'zhiyuan12test3@staff.weibo.com',
                'tel'     => '18611338956',
            )
        );
```
上述 $data_arr 为批量插入两条数据。  
1. 批量查询。使用方法 ControllMysql::getList(int $count = 10, int $page = 0, $fields = array(), $where_condition = null, $order_by = null, $group_by = null)    
$count 和 $page 分别为分页控制参数，表示每页几条和第几页，如果 $count 参数小于 0，则会取出所有数据。   
$fields 参数控制取出的字段，默认空时相当于 SELECT * ，可以填写所需字段名。  
$where_condition 是一个二维数组，主要作用于查询条件控制。数组结构样例如下：  
```
array(
    array('field'=>'field_name1','condition'=>'value1'),
    array('field'=>'field_name2','condition'=>'value2','operator'=>'>')
)
```
下标 field 为 查询字段名， condition 为查询字段对应的值， operator 为查询字段与查询值之前的操作符，默认为“=”，logic 为查询条件连接值，默认为 "AND"。   
$order_by 参数控制查询结果顺序，样例数据为： array('field_name1'=>'DESC','field_name2'=>'ASC') ,即表示相应字段对应的正排序或倒排序。  
$group_by 参数控制分组，样例数据为：array('field_name1'=>'DESC','field_name2'=>'ASC') ，即表示相应字段进行group聚合。  
1. 删除操作。使用方法 ControllMysql::remove($where_condition, int $count = -1, $order_by = null)   
$where_condition 与之前同名参数格式一致。   
$count 最大删除数量，整数值，小于0则不做删除最大数量限制。   
$order_by 与之前同名参数格式一致。   
1. 批量更新。使用方法 ControllMysql::update(array $set_arr, $where_condition = null, int $count = -1, $order_by = null)  
$set_arr 更新数据参数，参数格式与 $duplicate_arr 相同。  
$where_condition 与之前同名参数格式一致。   
$count 正整数，表示更新条数限制，默认值 -1 不限制。  
$order_by 与之前同名参数格式一致，表示更新数据的顺序。  
1. 计数操作。使用方法 ControllMysql::count($where_condition = null, $group_by = null),  
$where_condition 统计条件，与上边同名参数结构相同。   
$group_by 分组参数，与上边同名参数相同。  
1. 事务操作。
ControllMysql::beginTransaction(string $resource_name)   开启事务
ControllMysql::commit(string $resource_name)  提交事务  
ControllMysql::rollback(string $resource_name)  回滚  
$resource_name  事务资源名  
