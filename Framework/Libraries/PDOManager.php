<?php
/**
 * PDO管理类
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace framework;
class PDOManager {
    public $DB_CONF_STRUCT = array(
        'master' => array(
            'host'     => '主库地址',
            'port'     => '主库端口号',
            'username' => '用户名',
            'password' => '用户密码',
            'dbname'   => '数据库名',
            'charset'  => 'UTF8'
        ),
        'slave'  => array(
            'host'     => '从库地址',
            'port'     => '从库端口号',
            'username' => '用户名',
            'password' => '用户密码',
            'dbname'   => '数据库名',
            'charset'  => 'UTF8'
        )
    );
    private $_force_master        = false;
    private $_last_db             = 'slave';
    private $_last_db_slave_index = 0;
    private $_time_out            = 1;
    private $_db_conf             = array();
    private $_db_arrtibute        = array();
    //master、slave专用句柄数组下标
    private $_db_handles = array('master' => '', 'slave' => '');
    public function __construct($db_conf, $master_flag = false) {
        $this->_db_conf = $db_conf;
        $this->setMasterFlag($master_flag);
    }
    /**
     * 设置是否强制使用主库
     * @return $this
     */
    public function setMasterFlag($is_true) {
        if (is_bool($is_true)) {
            $this->_force_master = $is_true;
        } else {
            Debug::setErrorMessage(Debug::ERROR_ERROR_PARAMS);
        }
        return $this;
    }

    public function getMasterFlag() {
        return $this->_force_master;
    }
    /**
     * 设置pdo超时时间,单位秒
     * @param int $seconds
     */
    public function setTimeout($seconds) {
        if (is_numeric($seconds)) {
            $this->_time_out = $seconds;
        }
        return $this;
    }
    /**
     * 链接一个数据库
     * @param  string   $dsn
     * @param  string   $username
     * @param  string   $password
     * @param  array    $driver_options
     * @return object
     */
    public function connectDB($dsn, $username, $password, $driver_options = array()) {
        try {
            Debug::debugDump($dsn . " " . $username . " " . $password . " " . json_encode($driver_options));
            $pdo = new PDO($dsn, $username, $password, $driver_options);
            if ( ! empty($this->_db_arrtibute)) {
                foreach ($this->_db_arrtibute as $key => $value) {
                    $pdo->setAttribute($key, $value);
                }
            }
        } catch (PDOException $e) {
            Debug::setErrorMessage($e->getCode() . ": " . $e->getMessage());
            return false;
        }
        return $pdo;
    }
    /**
     * 执行一条 SQL 语句，并返回受影响的行数
     * 不会从一条 SELECT 语句中返回结果。需要返回结果的请使用query()
     * @return int
     */
    public function exec($prepare_sql, $param_array = '', $driver_options = array()) {
        $sth = $this->prepare($prepare_sql, $driver_options);
        $ret = $sth->execute($param_array);
        if ($ret) {
            $ret = $sth->rowCount();
        }
        return $ret;
    }
    /**
     *  执行一条 SQL 语句，并返回结果
     * @param  string  $prepare_sql
     * @param  string  $param_array
     * @param  array   $driver_options
     * @return array
     */
    public function query($prepare_sql, $param_array = '', $driver_options = array()) {
        $sth = $this->prepare($prepare_sql, $driver_options);
        $ret = $sth->execute($param_array);
        if ($ret) {
            $ret = $sth->fetchAll();
        }
        return $ret;
    }
    /**
     *  Quotes a string for use in a query.
     *
     * PDO::PARAM_BOOL (integer) 表示布尔数据类型。
     * PDO::PARAM_NULL (integer) 表示 SQL 中的 NULL 数据类型。
     * PDO::PARAM_INT (integer) 表示 SQL 中的整型。
     * PDO::PARAM_STR (integer) 表示 SQL 中的 CHAR、VARCHAR 或其他字符串类型。
     * PDO::PARAM_LOB (integer) 表示 SQL 中大对象数据类型。
     * PDO::PARAM_STMT (integer) 表示一个记录集类型。当前尚未被任何驱动支持。
     * PDO::PARAM_INPUT_OUTPUT (integer) 指定参数为一个存储过程的 INOUT 参数。必须用一个明确的
     * PDO::PARAM_* 数据类型跟此值进行按位或。
     */
    public function quote($param, $parameter_type = PDO::PARAM_STR) {
        $parameter_type_array = array(
            PDO::PARAM_BOOL,
            PDO::PARAM_NULL,
            PDO::PARAM_INT,
            PDO::PARAM_STR,
            PDO::PARAM_LOB,
            PDO::PARAM_STMT,
            PDO::PARAM_INPUT_OUTPUT
        );
        $db_handle = $this->_getDBHandle($this->_last_db);
        return $db_handle->quote($param, $parameter_type);
    }
    /**
     * Prepares a statement for execution and returns a statement object
     * @param  string         $sql
     * @param  array          $driver_options
     * @return PDOStatement
     */
    public function prepare($prepare_sql, $driver_options = array()) {
        if (empty($prepare_sql)) {
            return false;
        }
        $db_handle = $this->_getDBHandle($this->_DBChoice($prepare_sql));
        $sth       = $db_handle->prepare($prepare_sql, $driver_options);
        return $sth;
    }
    /**
     * 设置数据库句柄属性,针对设置后调用connectDB成员方法有效
     * @param int    $attribute_key
     * @param string $value
     */
    public function setAttribute($attribute_key, $value) {
        $this->_db_arrtibute[$attribute_key] = $value;
    }
    /**
     * 返回上一条入库数据id
     * @return string
     */
    public function lastInsertId() {
        $db_handle = $this->_getDBHandle('master');
        return $db_handle->lastInsertId();
    }
    /**
     * 获取数据库链接属性信息
     * @param  array   $attributes
     * @param  string  $db_handle_name
     * @return array
     */
    public function getAttribute($attributes = array(), $db_handle_name = '') {
        if (empty($attributes)) {
            $attributes = array(
                "ATTR_AUTOCOMMIT", "ATTR_ERRMODE", "ATTR_CASE", "ATTR_CLIENT_VERSION", "ATTR_CONNECTION_STATUS",
                "ATTR_ORACLE_NULLS", "ATTR_PERSISTENT", "ATTR_PREFETCH", "ATTR_SERVER_INFO", "ATTR_SERVER_VERSION",
                "ATTR_TIMEOUT"
            );
        }
        if (empty($db_handle_name)) {
            $db_handle_name = $this->_last_db;
        }
        $ret       = array();
        $db_handle = $this->_getDBHandle($db_handle_name);
        foreach ($attributes as $val) {
            $ret[$val] = $db_handle->getAttribute(constant("PDO::" . $val));
        }
        return $ret;
    }
    /**
     *  Return an array of available PDO drivers
     * @return array
     */
    public function getAvailableDrivers() {
        return PDO::getAvailableDrivers();
    }
    /**
     * 获取pdo上一次操作的错误信息
     * 不会获取到 PDOStatement 的错误信息
     * @return array
     */
    public function getErrorInfo() {
        $info = $this->_db_handles[$this->_last_db]->errorInfo();
        return $info;
    }
    /**
     * 启动事务
     * @return boolean
     */
    public function beginTransaction() {
        $db_handle = $this->_getDBHandle('master');
        if ($db_handle->beginTransaction()) {
            return $this;
        }
        return false;
    }
    /**
     * 回滚事务
     * @return boolean
     */
    public function rollBack() {
        $db_handle = $this->_getDBHandle('master');
        if ($db_handle->rollBack()) {
            return $this;
        }
        return false;
    }
    /**
     * 判断是否在事务内
     * @return boolean
     */
    public function inTransaction() {
        $db_handle = $this->_getDBHandle('master');
        if ($db_handle->inTransaction()) {
            return $this;
        }
        return false;
    }

    /**
     * 提交事务
     * @return boolean
     */
    public function commit() {
        $db_handle = $this->_getDBHandle('master');
        if ($db_handle->commit()) {
            return $this;
        }
        return false;
    }
    /**
     * 获取db的句柄
     * @param  boolean  $is_master 是否使用master
     * @param  boolean  $reconnect 是否强制重连
     * @return object
     */
    private function _getDBHandle($db_handle_name, $reconnect = false) {
        $driver_options = array(
            PDO::ATTR_TIMEOUT   => $this->_time_out,
            PDO::NULL_TO_STRING => true
        );
        if ($reconnect || empty($this->_db_handles[$db_handle_name])) {
            $conf                                         = $this->_db_conf[$db_handle_name];
            $dsn                                          = "mysql:host=" . $conf['host'] . ";port=" . $conf['port'] . ";dbname=" . $conf['dbname'];
            $driver_options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $conf['charset'];
            $this->_db_handles[$db_handle_name]           = $this->connectDB($dsn, $conf['username'], $conf['password'], $driver_options);
        }
        $this->_last_db = $db_handle_name;
        return $this->_db_handles[$db_handle_name];
    }
    /**
     * 主辅库分离
     * @param  string   $sql
     * @return string
     */
    private function _DBChoice($sql) {
        $sql_components = explode(' ', ltrim($sql), 2);
        $verb           = strtolower($sql_components[0]);
        $db_type        = 'slave';
        switch ($verb) {
            case "select":
            case "describe":
            case "show":
                $db_type = 'slave';
                break;
            case "delete":
            case "update":
            case "truncate":
            case "replace":
            case "rename":
            case "alter":
            case "drop":
            case "create":
            case "insert":
                $db_type = 'master';
                break;
            default:
                throw new PDOManagerException(PDOManagerException::ERROR_SQL_SYNTAX);
                return false;
        }
        return $db_type;
    }
}

class PDOManagerException extends Exception {
    const ERROR_SQL_SYNTAX = 1;
    public $ERROR_SET      = array(
        self::ERROR_SQL_SYNTAX => array(
            'code'    => self::ERROR_SQL_SYNTAX,
            'message' => 'Sql syntax error'
        )
    );
    public function __construct($code = 0) {
        parent::__construct($code);
    }
}