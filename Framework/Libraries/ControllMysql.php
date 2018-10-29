<?php
/**
 * PHP控制数据库底层类
 *
 * 关于使用： 只需要提供相应数据库的配置信息和需要操作的表名即可。所有可调用的公开方法在interface里。继承者如果需要把
 *              某一方法暴露给使用者，在自己类中使用public方法覆盖父级方法即可。
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
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
 * $a = new ControllMysql($table_name);
 * $a->getList(10,0,'*',array(array('field'=>'id','condition'=>'5','operator'=>'>')),null);
 */
/**
 * 接口ControllDB的实现 ControllMysql类
 *
 * ps:使用接口限制了子类继承后覆盖一些方法的传参，暂时放弃接口继承，但是依然会保证提供接口有的所有方法
 */
namespace Framework\Libraries;
use Framework\Entities\PDOConfig;

abstract class ControllMysql {
    private $_db_conf        = null;
    private $_pdo            = array();
    private $_last_sql       = null;
    private $_module         = null;
    private $_is_query       = false;
    private $_is_add         = false;
    private $_table_name     = '';
    private $_last_params    = array();
    private $_placeholder_id = 0;

    public function __construct(string $table_name, string $module = null) {
        $this->setTableName($table_name);
        if (empty($module)) {
            $tmp                 = get_class($this);
            list($module, $null) = explode('\\', $tmp, 2);
        }
        $this->_module = $module;
    }
    /**
     * 执行构造的sql
     * @param  string $resource_name
     * @param  string $module=null     //配置文件所在模块名
     * @return mix
     */
    protected function exec(string $resource_name) {
        if (empty($resource_name)) {
            throw new ControllMysqlException(ControllMysqlException::ERROR_DB_POOL_EMPTY);
        }
        if (empty($this->_last_sql)) {
            throw new ControllMysqlException(ControllMysqlException::NO_SQL_TO_EXEC);
        }
        $pd                 = $this->_connectPdo($resource_name);
        $sql                = $this->_last_sql;
        $this->_last_sql    = '';
        $var                = $this->_last_params;
        $this->_last_params = array();
        $is_add             = $this->_is_add;
        $this->_is_add      = false;
        $is_query           = $this->_is_query;
        $this->_is_query    = false;
        if ($is_query) {
            $ret = $pd->query($sql, $var);
        } else {
            $ret = $pd->exec($sql, $var);
            //如果是插入操作则返回插入值的id,仅当表主键为AUTO_INCREMENT时是这样，否则返回0
            if ($is_add) {
                $ret = $pd->lastInsertId();
            }
        }
        return $ret;
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
    protected function add(array $data, array $duplicate = null) {
        if (empty($data)) {
            throw new ControllMysqlException(ControllMysqlException::PARAMS_ERROR_MESSAGE);
        }
        $fields = '';
        $values = '';
        $params = array();
        foreach ($data as $field => $value) {
            $fields .= $this->_putFieldQuote($field) . ',';
            $tmp = $this->_getFieldId();
            $values .= $tmp . ',';
            $params[$tmp] = $value;
        }
        $fields = substr($fields, 0, -1);
        $values = substr($values, 0, -1);
        $sql    = 'INSERT INTO ' . $this->getTableName() . ' (' . $fields . ') VALUE (' . $values . ')';
        if ( ! empty($duplicate)) {
            $sql .= ' ON DUPLICATE KEY UPDATE ' . $this->_buildSet($duplicate);
        }
        $this->_addVar($params);
        $this->_last_sql = $sql;
        $this->_is_add   = true;
        return $this;
    }
    /**
     * 向当前表批量增加条目
     * $data_arr为二维数组
     * 其中每维数组的key为field名，value为相应字段插入的值
     * $duplicate 参数结构与add()方法中的相同
     *
     * @param  array[]      $data
     * @param  array|string $duplicate=null
     * @return mix
     */
    protected function multiAdd(array $data_arr, $duplicate = null) {
        if (empty($data_arr)) {
            throw new ControllMysqlException(ControllMysqlException::PARAMS_ERROR_MESSAGE);
        }
        $sql    = 'INSERT INTO ' . $this->getTableName() . ' (';
        $fields = '';
        foreach ($data_arr[0] as $field => $value) {
            $fields .= $this->_putFieldQuote($field) . ',';
        }
        $fields = substr($fields, 0, -1);
        $sql .= $fields . ') VALUES ';
        $params = array();
        foreach ($data_arr as $data) {
            $one = '(';
            foreach ($data as $field => $value) {
                $tmp = $this->_getFieldId();
                $one .= $tmp . ',';
                $params[$tmp] = $value;
            }
            $one = substr($one, 0, -1);
            $sql .= $one . '),';
        }
        $sql = substr($sql, 0, -1);
        if ( ! empty($duplicate)) {
            if (is_array($duplicate)) {
                $sql .= ' ON DUPLICATE KEY UPDATE ' . $this->_buildSet($duplicate);
            } else {
                $sql .= ' ON DUPLICATE KEY UPDATE ' . $duplicate;
            }
        }
        $this->_addVar($params);
        $this->_last_sql = $sql;
        return $this;
    }
    /**
     * 按条件获取当前操作表的指定条数
     *
     * @param  int          $count=10
     * @param  int          $page=1
     * @param  mix          $fields='*'
     * @param  array|string $where_condition=null;
     * @param  array|string $order_by=null;
     * @param  array|string $group_by=null;
     * @return array
     */
    protected function getList(int $count = 10, int $page = 0, $fields = array(), $where_condition = null, $order_by = null, $group_by = null) {
        if (empty($fields)) {
            $fields = '*';
        } else if (is_array($fields)) {
            $fields = '`' . implode('`,`', $fields) . '`';
        }
        if ($count > 0) {
            $limit = ' LIMIT ' . $page * $count . ',' . $count;
        }
        $sql             = 'SELECT ' . $fields . ' FROM ' . $this->getTableName() . $this->_buildWhereCondition($where_condition) . $this->_buildGroupBy($group_by) . $this->_buildOrderBy($order_by) . $limit;
        $this->_last_sql = $sql;
        $this->_is_query = true;
        return $this;
    }
    /**
     * 按条件移除当前操作表的指定条目
     *
     * @param  array|string $where_condition
     * @param  int          $count=-1
     * @param  array|string $order_by=null
     * @return int
     */
    protected function remove($where_condition, int $count = -1, $order_by = null) {
        if (empty($where_condition)) {
            throw new ControllMysqlException(ControllMysqlException::WHERE_EMPTY);
        }
        $limit = '';
        if (is_numeric($count) && $count > 0) {
            $limit = ' LIMIT ' . $count;
        }
        $sql             = 'DELETE FROM ' . $this->getTableName() . $this->_buildWhereCondition($where_condition) . $this->_buildOrderBy($order_by) . $limit;
        $this->_last_sql = $sql;
        return $this;
    }
    /**
     * 按条件更新当前操作表的指定条目
     *
     * @param  array        $set_arr
     * @param  array|string $where_condition=null
     * @param  int          $count=-1
     * @param  array|string $order_by=null
     * @return int
     */
    protected function update(array $set_arr, $where_condition = null, int $count = -1, $order_by = null) {
        if (empty($set_arr)) {
            throw new ControllMysqlException(ControllMysqlException::PARAMS_ERROR_MESSAGE);
        }
        $limit = '';
        if (is_numeric($count) && $count > 0) {
            $limit = ' LIMIT ' . $count;
        }
        $sql             = 'UPDATE ' . $this->getTableName() . ' SET ' . $this->_buildSet($set_arr) . $this->_buildWhereCondition($where_condition) . $this->_buildOrderBy($order_by) . $limit;
        $this->_last_sql = $sql;
        return $this;
    }
    /**
     * 根据条件统计条目数
     *
     * @param  string|array $where_condition
     * @param  string|array $group_by
     * @return int
     */
    protected function count($where_condition = null, $group_by = null) {
        $sql             = 'SELECT COUNT(*) as count FROM ' . $this->getTableName() . $this->_buildWhereCondition($where_condition) . $this->_buildGroupBy($group_by);
        $this->_last_sql = $sql;
        $this->_is_query = true;
        return $this;
    }
    /**
     * 获取最后一句sql
     * @return string
     */
    protected function getLastSql() {
        return array('sql' => $this->_last_sql, 'params' => $this->_last_params);
    }

    private function _connectPdo($resource_name) {
        if (empty($resource_name)) {
            throw new ControllMysqlException(ControllMysqlException::ERROR_DB_POOL_EMPTY);
        }
        if ( ! isset($this->_pdo[$resource_name])) {
            $db_conf                    = ConfigTool::loadByName($resource_name, $this->_module);
            $pdo_config                 = new PDOConfig();
            $pdo_config->host           = $db_conf['host'];
            $pdo_config->port           = $db_conf['port'];
            $pdo_config->username       = $db_conf['username'];
            $pdo_config->password       = $db_conf['password'];
            $pdo_config->dbname         = $db_conf['dbname'];
            $this->_db_conf             = $pdo_config;
            $this->_pdo[$resource_name] = new PDOManager($pdo_config);
        }
        return $this->_pdo[$resource_name];
    }
    /**
     * 启用mysql事务
     *
     * @param  string    $work_name=null
     * @return boolean
     */
    protected function beginTransaction(string $resource_name) {
        $pdo = $this->_connectPdo($resource_name);
        $pdo->beginTransaction();
        return true;
    }
    /**
     * 提交事务
     *
     * @param  string    $work_name=null
     * @return boolean
     */
    protected function commit(string $resource_name) {
        $pdo = $this->_connectPdo($resource_name);
        $pdo->commit();
        return true;
    }
    /**
     * 回滚事务
     *
     * @param  string    $work_name=null
     * @return boolean
     */
    protected function rollback(string $resource_name) {
        $pdo = $this->_connectPdo($resource_name);
        $pdo->rollback();
        return true;
    }
    /**
     * 构造条件语句
     * @param  string  $field
     * @param  mix     $condition
     * @param  string  $operator
     * @param  string  $logic
     * @return array
     */
    public static function buildWhereCondition(string $field, $condition, string $operator = '=', string $logic = 'AND') {
        return array('field' => $field, 'condition' => $condition, 'operator' => $operator, 'logic' => $logic);
    }

    /**
     * 获取操作表名
     * @return string
     */
    protected function getTableName() {
        if (empty($this->_table_name)) {
            throw new ControllMysqlException(ControllMysqlException::TABLE_NAME_EMPTY);
        }
        return $this->_table_name;
    }
    /**
     * 设置操作表名
     * @param string $table_name
     */
    protected function setTableName(string $table_name) {
        $this->_table_name = $table_name;
        return $this;
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
        $params  = array();
        foreach ($set_arr as $field => $value) {
            $tmp_key = $this->_getFieldId();
            $set_str .= $this->_putFieldQuote($field) . '=' . $tmp_key . ',';
            $params[$tmp_key] = $value;
        }
        $set_str = substr($set_str, 0, -1);
        $this->_addVar($params);
        return $set_str;
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
        $params = array();
        foreach ($where_arr as $condition) {
            if (empty($condition['logic'])) {
                $condition['logic'] = 'AND';
            }
            if (empty($condition['operator'])) {
                $condition['operator'] = '=';
            }
            $result .= ' ' . $condition['logic'] . ' ' . $this->_putFieldQuote($condition['field']) . ' ' . $condition['operator'];
            $condition['operator'] = strtoupper($condition['operator']);
            if ('IN' == $condition['operator'] || 'NOT IN' == $condition['operator']) {
                if (is_array($condition['condition'])) {
                    $t_field = $condition['condition'];
                } else {
                    $t_field = explode(',', $condition['condition']);
                }
                $result .= ' (';
                foreach ($t_field as $value) {
                    $tt_field = $this->_getFieldId();
                    $result .= $tt_field . ",";
                    $params[$tt_field] = $value;
                }
                $result = substr($result, 0, -1);
                $result .= ')';
            } else {
                $tmp_field = $this->_getFieldId();
                $result .= ' ' . $tmp_field;
                $params[$tmp_field] = $condition['condition'];
            }
        }
        $this->_addVar($params);
        return $result;
    }
    private function _buildHavingBy($set_arr) {}
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
            $order_by .= $this->_putFieldQuote($field) . ' ' . $value . ',';
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
            $group_by .= $this->_putFieldQuote($field) . ' ' . $value . ',';
        }
        $group_by = substr($group_by, 0, -1);
        if ( ! empty($group_by_arr['with_rollup'])) {
            $group_by .= ' WITH ROLLUP';
        }
        return $group_by;
    }
    /**
     * 获取占位符
     * @param  string   $field
     * @return string
     */
    private function _getFieldId() {
        $tmp = $this->_placeholder_id++;
        return ':' . $tmp;
    }
    /**
     * 给字段加上斜瞥号
     *
     * @param  string   $field
     * @return string
     */
    private function _putFieldQuote($field) {
        return '`' . $field . '`';
    }
    /**
     * 增加变量
     * @param array $var
     */
    private function _addVar(array $var) {
        if ( ! empty($var)) {
            $this->_last_params = array_merge($this->_last_params, $var);
        }
        return $this;
    }
}

class ControllMysqlException extends Exception {
    const DB_POOL_EMPTY        = 1;
    const PARAMS_ERROR_MESSAGE = 2;
    const WHERE_EMPTY          = 3;
    const TABLE_NAME_EMPTY     = 4;
    const NO_SQL_TO_EXEC       = 5;
    public $ERROR_SET          = array(
        self::DB_POOL_EMPTY        => array(
            'code'    => self::DB_POOL_EMPTY,
            'message' => 'db_pool name empty'
        ),
        self::PARAMS_ERROR_MESSAGE => array(
            'code'    => self::PARAMS_ERROR_MESSAGE,
            'message' => 'param error'
        ),
        self::WHERE_EMPTY          => array(
            'code'    => self::WHERE_EMPTY,
            'message' => 'function param $where can not be empty'
        ),
        self::TABLE_NAME_EMPTY     => array(
            'code'    => self::TABLE_NAME_EMPTY,
            'message' => 'table_name can not be empty'
        ),
        self::NO_SQL_TO_EXEC       => array(
            'code'    => self::NO_SQL_TO_EXEC,
            'message' => 'no sql to exec'
        )
    );
    public function __construct($code = 0) {
        parent::__construct($code);
    }
}
