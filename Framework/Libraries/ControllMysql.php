<?php
/**
 * PHP控制数据库底层类
 *
 * 关于使用： 只需要提供相应数据库的配置信息和需要操作的表名即可。所有可调用的公开方法在interface里。继承者如果需要把
 *              某一方法暴露给使用者，在自己类中使用public方法覆盖父级方法即可。
 * 关于调试：构造函数第三个参数设置为true即可，或使用setDebug()方法传true参数,可开启debug调试模式，
 *              把底层关键处数据输出。
 *           ps:继承后请保持调试功能的可用和调试信息格式一致。
 * 关于错误信息：使用getErrorMessage()获取上一次“错误操作”的错误信息。
 *           ps:继承后请保持错误功能的可用和错误信息格式一致，确保每一次false都有setErrorMessage()方法的调用。
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
// ！重要！ps:2013/08/28根据数据库平台要求，缓存表结构信息入redis，如果修改表结构，切记要开启一次debug调试模式刷新redis内存储的表结构信息！！

// 关于一些数组参数的格式说明：
/**
 * 构建group by语句
 * 数组维度：一维
 * 数组格式: key为字段名 ， value为 DESC 或 ASC
 * 使用with rollup功能需要设置键值with_rollup为非空即可，据说要求mysql版本在5.1以上。
 */
/**
 * 构建order by语句
 * 数组维度：一维
 * 数组格式: key为字段名， value为 DESC 或 ASC
 * examp:
 * array('field_name1'=>'DESC','field_name2'=>'ASC');
 */
/**
 * 构建where条件语句
 * 数组维度:二维数组。
 * 每维数据包含：
 * 键'logic'，值可为 AND、OR。用于连接多个表达式。(默认为 AND)
 * 键'field',值可为 column名、expression。
 * 键'operator',值可为
 * =、>、<、>=、<=、!=、<>、[not] like、[not] between、[not] in等。(默认为=号)
 * 键'condition',值为 正确的相应操作符右侧的操作数 即可。
 * 注意：关于where语句有使用括号的需自行构建where语句,直接传自己拼好的where语句即可。
 * examp:
 * array(
 * array('field'=>'field_name1','condition'=>'value1'),
 * array('field'=>'field_name2','condition'=>'value2','operator'=>'>')
 * );
 * $a = new ControllMysql($db_conf);
 * $a->getList(10,0,'*',array(array('field'=>'id','condition'=>'5','operator'=>'>')),null);
 */
/**
 * 接口ControllDB的实现 ControllMysql类
 *
 * ps:使用接口限制了子类继承后覆盖一些方法的传参，暂时放弃接口继承，但是依然会保证提供接口有的所有方法
 */
namespace framework;
class ControllMysql/*implements ControllDB*/ {
    const CLASS_NAME                    = 'ControllMysql';
    const DB_CONF_FILE_NAME             = 'database.ini';
    const PARAMS_ERROR_MESSAGE          = '参数错误';
    const NULL_TABLE_ERROR_MESSAGE      = '当前操作的数据表名为空';
    const NULL_RESULT_ERROR_MESSAGE     = '查询结果为空';
    const NOT_EXIST_TABLE_ERROR_MESSAGE = '指定数据库不存在表';
    // 字符类型太多就用数字类型的补集好了....-_-!
    protected $char_data_types           = null;
    protected static $numeric_data_types = array(
        'bit'       => true,
        'tinyint'   => true,
        'smallint'  => true,
        'mediumint' => true,
        'int'       => true,
        'bigint'    => true,
        'float'     => true,
        'double'    => true,
        'decimal'   => true
    );
    private static $_order_by_type = array(
        'ASC',
        'DESC'
    );
    protected static $_table_list = null;
    private $_table_schema        = null;
    private $_db_conf             = null;
    private $_table_name          = null;
    private $_char_fields         = null;
    private $_key_fields          = null;
    private $_mysql               = null;
    private $_error_message       = null;
    private $_debug               = false;
    private $_redis               = null;
    public function __construct($db_pool_name, $table_name = null) {
        if (empty($db_pool_name)) {
            throw new ControllMysqlException(ControllMysqlException::ERROR_DB_POOL_EMPTY);
        }
        $db_conf        = ParseIni::getConfig(self::DB_CONF_FILE_NAME, $db_pool_name);
        $this->_db_conf = $db_conf;
        var_dump($db_conf);
        $this->_mysql   = new PDOManager($db_conf);
        // $this->_redis   = new RedisLib();
        if ( ! empty($table_name)) {
            $this->setTableName($table_name);
        }
    }
    /**
     * 向当前表增加数据条目
     * 其中$data数组的key为field名，value为相应字段插入的值
     * 支持ON DUMPLICATE 子句，该参数与$data数组结构相同，默认值为null
     * 返回结果为插入条目last_id
     *
     * ps：非常重要！
     * 如果使用duplicate，请确保操作字段为非字符型。
     * 如果是字符型字段，请自己拼好duplicate子句当参数传入。
     * duplicate实现自动escape和加引号情况非常复杂，不再添加这块功能。
     *
     * @param  array     $data
     * @param  array     $duplicate=null
     * @return string
     */
    protected function add($data, $duplicate = null) {
        if (empty($data) || ! is_array($data)) {
            $this->setErrorMessage(self::PARAMS_ERROR_MESSAGE);
            return false;
        }
        $fields = '';
        $values = '';
        foreach ($data as $field => $value) {
            if ('' === $value) {
                continue;
            }
            $fields .= $this->_putFieldQuote($field) . ',';
            $values .= $this->_autoQuote($field, $value) . ',';
        }
        $fields = substr($fields, 0, -1);
        $values = substr($values, 0, -1);
        $sql    = 'INSERT INTO ' . $this->getTableName(true) . ' (' . $fields . ') VALUE (' . $values . ')';
        if ( ! empty($duplicate)) {
            if (is_array($duplicate)) {
                $sql .= ' ON DUPLICATE KEY UPDATE ' . $this->_buildSet($duplicate);
            } else {
                $sql .= ' ' . $duplicate;
            }
        }
        $ret = $this->execSql($sql);
        if ($ret) {
            return $this->_mysql->getLastInsertId();
        }
        return $ret;
    }
    /**
     * 向当前表批量增加条目
     * $data_arr为二维数组
     * 其中每维数组的key为field名，value为相应字段插入的值
     * $duplicate 参数结构与add()方法中的相同
     *
     * @param  array[] $data
     * @param  array   $duplicate=null
     * @return mix
     */
    protected function multiAdd($data_arr, $duplicate = null) {
        if (empty($data_arr) || ! is_array($data_arr)) {
            $this->setErrorMessage(self::PARAMS_ERROR_MESSAGE);
            return false;
        }
        $sql    = 'INSERT INTO ' . $this->getTableName(true) . ' (';
        $fields = '';
        foreach ($data_arr[0] as $field => $value) {
            $fields .= $this->_putFieldQuote($field) . ',';
        }
        $fields = substr($fields, 0, -1);
        $sql .= $fields . ') VALUES ';
        foreach ($data_arr as $data) {
            $one = '(';
            foreach ($data as $field => $value) {
                $one .= $this->_autoQuote($field, $value) . ',';
            }
            $one = substr($one, 0, -1);
            $sql .= $one . '),';
        }
        $sql = substr($sql, 0, -1);
        if ( ! empty($duplicate)) {
            if (is_array($duplicate)) {
                $sql .= ' ON DUPLICATE KEY UPDATE ' . $this->_buildSet($duplicate);
            } else {
                $sql .= ' ' . $duplicate;
            }
        }
        return $this->execSql($sql);
    }
    /**
     * 按条件获取当前操作表的指定条数
     *
     * @param  int     $count=10
     * @param  int     $page=1
     * @param  mix     $fields='*'
     * @param  array   $where_condition=null;
     * @param  array   $order_by=null;
     * @param  array   $group_by=null;
     * @return array
     */
    protected function getList($count = 10, $page = 0, $fields = '*', $where_condition = null, $order_by = null, $group_by = null) {
        if ( ! is_numeric($count) || ! is_numeric($page) || empty($fields)) {
            $this->setErrorMessage(self::PARAMS_ERROR_MESSAGE);
            return false;
        }
        if (is_array($fields)) {
            $fields = implode(',', $fields);
        }
        if (is_numeric($count) && $count > 0) {
            $limit = ' LIMIT ' . $page * $count . ',' . $count;
        }
        $sql = 'SELECT ' . $fields . ' FROM ' . $this->getTableName(true) . $this->_buildWhereCondition($where_condition) . $this->_buildGroupBy($group_by) . $this->_buildOrderBy($order_by) . $limit;
        return $this->execSql($sql);
    }
    /**
     * 通过主键id获取一条数据
     * 如果主键是联合主键需要使用key=>value的数组传参，单独一个主键则只传key_id的值即可
     *
     *            array or string $data
     *            array or string $fields='*'
     * @param
     * @param
     * @return  mix
     */
    protected function getByKey($key_id, $fields = '*') {
        $keys = $this->getTablePrimary();
        if (is_array($fields)) {
            $fields = implode(',', $fields);
        }
        $sql = 'SELECT ' . $fields . ' FROM ' . $this->getTableName(true);

        if (count($keys) == 1) {
            $sql .= ' WHERE ' . $keys[0] . ' = ' . $this->_autoQuote($keys[0], $key_id);
        } else {
            $where_arr = null;
            foreach ($key_id as $key => $value) {
                $where_arr[] = array(
                    'field'     => $key,
                    'condition' => $value
                );
            }
            $sql .= $this->_buildWhereCondition($where_arr);
        }
        $result = $this->execSql($sql);
        if (empty($result)) {
            return '';
        }
        return $result[0];
    }
    /**
     * 按条件移除当前操作表的指定条目
     *
     * @param  array $where_condition
     * @param  int   $count=-1
     * @param  array $order_by=null
     * @return int
     */
    protected function remove($where_condition, $count = -1, $order_by = null) {
        if (empty($where_condition)) {
            $this->setErrorMessage(self::PARAMS_ERROR_MESSAGE);
            return false;
        }
        $limit = '';
        if (is_numeric($count) && $count > 0) {
            $limit = ' LIMIT ' . $count;
        }
        $sql = 'DELETE FROM ' . $this->getTableName(true) . $this->_buildWhereCondition($where_condition) . $this->_buildOrderBy($order_by) . $limit;
        return $this->execSql($sql);
    }
    /**
     * 按主键删除一条信息
     * 单独一个主键则只传key_id的值即可
     * 如果主键是联合主键需要使用key=>value的数组传参
     *
     * @param  mix   $key_id
     * @return int
     */
    protected function removeByKey($key_id) {
        $keys = $this->getTablePrimary();
        $sql  = 'DELETE FROM ' . $this->getTableName(true);
        if (count($keys) == 1) {
            $sql .= ' WHERE ' . $keys[0] . ' = ' . $this->_autoQuote($keys[0], $key_id);
        } else {
            $where_arr = null;
            foreach ($key_id as $key => $value) {
                $where_arr[] = array(
                    'field'     => $key,
                    'condition' => $value
                );
            }
            $sql .= $this->_buildWhereCondition($where_arr);
        }
        return $this->execSql($sql);
    }
    /**
     * 按条件更新当前操作表的指定条目
     *
     * @param  array $set_arr
     * @param  array $where_condition=null
     * @param  int   $count=-1
     * @param  array $order_by=null
     * @return int
     */
    protected function update($set_arr, $where_condition = null, $count = -1, $order_by = null) {
        if (empty($set_arr) || ! is_array($set_arr)) {
            $this->setErrorMessage(self::PARAMS_ERROR_MESSAGE);
            return false;
        }
        $limit = '';
        if (is_numeric($count) && $count > 0) {
            $limit = ' LIMIT ' . $count;
        }
        $sql = 'UPDATE ' . $this->getTableName(true) . ' SET ' . $this->_buildSet($set_arr) . $this->_buildWhereCondition($where_condition) . $this->_buildOrderBy($order_by) . $limit;
        return $this->execSql($sql);
    }
    /**
     * 根据条件统计条目数
     *
     *            =null
     * @param  string $where_condition
     * @return int
     */
    protected function count($where_condition = null) {
        $sql    = 'SELECT COUNT(*) FROM ' . $this->getTableName(true) . $this->_buildWhereCondition($where_condition);
        $result = $this->execSql($sql);
        return $result[0]['COUNT(*)'];
    }
    /**
     * 查询是否存在某表
     *
     * @param  string    $table_name
     * @return boolean
     */
    protected function isExistTable($table_name) {
        // 这部分安全性验证就省略掉了~直接返回true 。。。2013/08/28 by zhiyuan12
        return true;
        if (empty($table_name)) {
            $this->setErrorMessage(self::PARAMS_ERROR_MESSAGE);
            return false;
        }
        if ( ! empty(self::$_table_list[$this->_db_conf][$table_name])) {
            return true;
        }
        $this->getTableList();
        if (empty(self::$_table_list[$this->_db_conf][$table_name])) {
            $this->setErrorMessage(self::NOT_EXIST_TABLE_ERROR_MESSAGE . $table_name);
            return false;
        }
        return true;
    }
    /**
     * 列出所连库的表
     * 可根据正则进行匹配显示
     *
     * @param  string    $pattern=null
     * @return boolean
     */
    protected function getTableList($pattern = null) {
        if ( ! empty(self::$_table_list[$this->_db_conf]) && empty($pattern)) {
            return self::$_table_list[$this->_db_conf];
        }
        $sql = 'SHOW TABLES ';
        if ( ! empty($pattern)) {
            $sql .= ' LIKE "' . $pattern . '"';
        }
        $tmp_result = $this->execSql($sql);
        if (empty($tmp_result)) {
            return array();
        }
        foreach ($tmp_result as $one) {
            $tmp      = array_values($one);
            $result[] = $tmp[0];
            if (empty($pattern)) {
                self::$_table_list[$this->_db_conf][$tmp[0]] = true;
            }
        }
        return $result;
    }
    /**
     * 获取当前操作表索引信息
     *
     * @return array
     */
    protected function getTableIndex() {
        $sql = 'SHOW INDEX FROM ' . $this->getTableName(true);
        return $this->execSql($sql);
    }
    /**
     * 设置mysql的编码
     *
     * @param  string    $charecter=utf8
     * @return boolean
     */
    protected function setCharecter($charecter = 'utf8') {
        $sql = 'SET NAMES ' . $charecter;
        return $this->execSql($sql);
    }
    /**
     * 启用mysql事务
     *
     * @param  string    $work_name=null
     * @return boolean
     */
    protected function beginTransaction($work_name = 'default') {
        $this->_mysql->beginTransaction();
        return true;
    }
    /**
     * 提交事务
     *
     * @param  string    $work_name=null
     * @return boolean
     */
    protected function commit($work_name = 'default') {
        $this->_mysql->commit();
        return true;
    }
    /**
     * 回滚事务
     *
     * @param  string    $work_name=null
     * @return boolean
     */
    protected function rollback($work_name = 'default') {
        $this->_mysql->rollback();
        return true;
    }
    /**
     * 停止事务
     *
     * @param  string    $work_name=null
     * @return boolean
     */
    protected function stopTransaction($work_name = 'default') {}
    /**
     * 执行一条sql语句
     *
     * @param  string $sql
     * @return mix
     */
    protected function execSql($sql) {
        if ($this->getDebug()) {
            RunTime::start("ControllMysql" . $sql);
            $result = $this->_mysql->performQuery($sql);
            RunTime::stop("ControllMysql" . $sql);
            Debug::debugDump($sql . "; sql执行耗时：" . implode('', RunTime::spent("ControllMysql")) . ' ms');
        } else {
            $result = $this->_mysql->performQuery($sql);
        }
        if (false === $result) {
            $tmp = $this->_mysql->getError();
            $this->setErrorMessage($tmp[2]);
            return false;
        }
        return $result;
    }
    /**
     * 构建where条件语句
     *
     * @param  mix      $set_arr
     * @return string
     */
    private function _buildSet($set_arr) {
        if (empty($set_arr)) {
            return '';
        }
        if ( ! is_array($set_arr)) {
            return ' ' . $set_arr;
        }
        $set_str = '';
        foreach ($set_arr as $field => $value) {
            if (is_null($value)) {
                $value = 0;
            }
            $set_str .= $this->_putFieldQuote($field) . '=' . $this->_autoQuote($field, $value) . ',';
        }
        $set_str = substr($set_str, 0, -1);
        return $set_str;
    }
    /**
     * 构建where条件数组
     * @return [type] [description]
     */
    public function buildWhere($xx) {
        return $this;
    }
    /**
     * 构建where条件语句
     *
     * @param  mix      $where_arr
     * @return string
     */
    private function _buildWhereCondition($where_arr) {
        if (empty($where_arr)) {
            return '';
        }
        if ( ! is_array($where_arr)) {
            return ' ' . $where_arr;
        }
        $result = ' WHERE 1';
        foreach ($where_arr as $condition) {
            if (empty($condition['logic'])) {
                $condition['logic'] = 'AND';
            }
            if (empty($condition['operator'])) {
                $condition['operator'] = '=';
            }
            $result .= ' ' . $condition['logic'] . ' ' . $this->_putFieldQuote($condition['field']) . ' ';
            $condition['operator'] = strtoupper($condition['operator']);
            if ('LIKE' == $condition['operator'] || 'NOT LIKE' == $condition['operator']) {
                $result .= $condition['operator'] . ' "%' . mysql_escape_string($condition['condition']) . '%"';
                //$result .= $condition ['operator'] . ' "%' . addcslashes ( $condition ['condition'],"\x00\n\r\\'\"\x1a" ) . '%"';
            } else if ('IN' == $condition['operator'] || 'NOT IN' == $condition['operator']) {
                $tmp          = explode(',', $condition['condition']);
                $in_condition = '';
                foreach ($tmp as $one) {
                    $in_condition .= $this->_autoQuote($condition['field'], $one) . ',';
                }
                $in_condition = substr($in_condition, 0, -1);
                $result .= $condition['operator'] . ' (' . $in_condition . ')';
            } else if ('BETWEEN' == $condition['operator'] || 'NOT BETWEEN' == $condition['operator']) {
                $result .= $condition['operator'] . ' ' . $condition['condition'];
            } else {
                $result .= $condition['operator'] . ' ' . $this->_autoQuote($condition['field'], $condition['condition']);
            }
        }
        return $result;
    }
    private function _buildHavingBy() {}
    /**
     * 构建order by语句
     *
     * @param  array    $order_by_arr
     * @return string
     */
    private function _buildOrderBy($order_by_arr) {
        if (empty($order_by_arr)) {
            return '';
        }
        if ( ! is_array($order_by_arr)) {
            return ' ' . $order_by_arr;
        }
        $order_by = ' ORDER BY ';
        foreach ($order_by_arr as $field => $value) {
            $order_by .= $field . ' ' . $value . ',';
        }
        $order_by = substr($order_by, 0, -1);
        return $order_by;
    }
    /**
     * 构建group by语句
     *
     * @param  array    $group_by_arr
     * @return string
     */
    private function _buildGroupBy($group_by_arr) {
        if (empty($group_by_arr)) {
            return '';
        }
        if ( ! is_array($group_by_arr)) {
            return ' ' . $group_by_arr;
        }
        $group_by = ' GROUP BY ';
        foreach ($group_by_arr as $field => $value) {
            $group_by .= $field . ' ' . $value . ',';
        }
        $group_by = substr($group_by, 0, -1);
        if ( ! empty($group_by_arr['with_rollup'])) {
            $group_by .= ' WITH ROLLUP';
        }
        return $group_by;
    }
    /**
     * 监测字段是否需要添加双引号
     *
     * @param  string   $field
     * @param  string&  $value
     * @return string
     */
    private function _autoQuote($field, $value) {
        if (empty($this->_char_fields)) {
            $this->getCharFields();
        }
        $value = mysql_escape_string($value);
        if ( ! empty($this->_char_fields[$field])) {
            return '"' . $value . '"';
        }
        // 应该判断 value 值是否为 is_numeric，但是添加这个判断影响add和multiAdd方法的on
        // duplicate的使用，后续待优化
        return $value;
    }
    /**
     * 给字段加上斜瞥号
     *
     * @param  string   $field
     * @return string
     */
    private function _putFieldQuote($field) {
        return '`' . mysql_escape_string($field) . '`';
    }
    /**
     * 获取当前操作表的字符字段
     *
     * @return array
     */
    protected function getCharFields() {
        if ( ! empty($this->_char_fields)) {
            return $this->_char_fields;
        }
        $table_name = $this->getTableName(true);
        $fields     = $this->getTableSchema($table_name);
        $result     = null;
        foreach ($fields as $field) {
            $type = explode('(', $field['Type']);
            if (empty(self::$numeric_data_types[$type[0]])) {
                $result[$field['Field']] = true;
            }
        }
        $this->_char_fields = $result;
        return $result;
    }
    /**
     * 获取当前操作表的主键
     *
     * @return array
     */
    protected function getTablePrimary() {
        if ( ! empty($this->_key_fields)) {
            return $this->_key_fields;
        }
        $table_name = $this->getTableName(true);
        $fields     = $this->getTableSchema($table_name);
        $result     = null;
        foreach ($fields as $field) {
            if ('PRI' == $field['Key']) {
                $result[] = $field['Field'];
            }
        }
        $this->_key_fields = $result;
        return $result;
    }
    /**
     * 获取指定或当前操作表的表结构
     *
     * @return array
     */
    protected function getTableSchema($is_cache = true) {
        if (empty($this->_table_schema) || $this->getDebug()) {
            $table_name = $this->getTableName(true);
            $sql        = 'SHOW COLUMNS FROM ' . $table_name;
            $redis_key  = 'TING_WEIBO_COM:' . $sql;
            if ($is_cache && ! $this->getDebug()) {
                $this->_table_schema = unserialize($this->_redis->get($redis_key));
            }
            if (empty($this->_table_schema)) {
                $this->_table_schema = $this->execSql($sql);
                $this->_redis->set($redis_key, serialize($this->_table_schema));
            }
        }
        return $this->_table_schema;
    }
    /**
     * 获取当前类操作表名
     *
     * @param  boolean  $isExit=false
     * @return string
     */
    protected function getTableName($isExit = false) {
        if ( ! empty($this->_table_name)) {
            return $this->_table_name;
        }
        if ($isExit) {
            $this->echoErrorMessage(self::NULL_TABLE_ERROR_MESSAGE);
        }
        $this->setErrorMessage(self::NULL_TABLE_ERROR_MESSAGE);
        return false;
    }
    /**
     * 设置当前类操作表
     */
    protected function setTableName($table_name) {
        if ($this->isExistTable($table_name)) {
            $this->_table_name  = $table_name;
            $this->_char_fields = null;
            $this->getCharFields();
            $this->_key_fields   = null;
            $this->_table_schema = null;
            return true;
        } else {
            return false;
        }
    }
    /**
     * 强制使用主库
     * 在这里设置强制读取主库
     * 设置以后，所有请求将发给主库
     *
     *     true 强制使用主库
     *     false 恢复正常
     * @param  @flag
     * @return array
     */
    public function forceMaster($flag = true) {
        return $this->_mysql->forceMaster($flag);
    }
}

class ControllMysqlException extends Exception {
    const ERROR_DB_POOL_EMPTY = 1;
    public $ERROR_SET         = array(
        self::ERROR_DB_POOL_EMPTY => array(
            'code'    => self::ERROR_DB_POOL_EMPTY,
            'message' => 'db_pool name empty'
        )
    );
    public function __construct($code = 0) {
        parent::__construct($code);
    }
}
