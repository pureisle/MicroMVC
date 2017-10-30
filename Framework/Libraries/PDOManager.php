<?php
/**
 * PDO管理类
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
use Framework\Entities\PDOConfig;
use \PDO;

class PDOManager {
    private $_db_conf                 = array();
    private $_db_handle               = null;
    private $_db_arrtibute            = array();
    private $_last_prepare_sql        = '';
    private $_last_prepare_sth        = null;
    private $_is_db_arrtibute_refresh = false;
    public function __construct(PDOConfig $pdo_config) {
        $this->_db_conf = $pdo_config;
        $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }
    public function setTimeout($seconds) {
        if (is_numeric($seconds)) {
            $this->_db_conf->time_out = $seconds;
        }
        return $this;
    }

    /**
     * 执行一条 SQL 语句，并返回受影响的行数
     * 不会从一条 SELECT 语句中返回结果。需要返回结果的请使用query()
     * @param  string $prepare_sql SELECT name, colour, calories FROM fruit WHERE calories < :calories AND colour = :colour
     * @param  string $param_array array(':calories' => 175, ':colour' => 'yellow')
     * @return int
     */
    public function exec($prepare_sql, $param_array = array(), $driver_options = array()) {
        if ( ! is_string($prepare_sql) || ! is_array($param_array) || ! is_array($driver_options)) {
            throw new PDOManagerException(PDOManagerException::ERROR_PARAM);
        }
        $sth = $this->prepare($prepare_sql, $driver_options);
        $ret = $sth->execute($param_array);
        if ($ret) {
            $ret = $sth->rowCount();
        }
        return $ret;
    }
    /**
     *  执行一条 SQL 语句，并返回结果
     * @param  string  $prepare_sql      SELECT name, colour, calories FROM fruit WHERE calories < :calories AND colour = :colour
     * @param  string  $param_array      array(':calories' => 175, ':colour' => 'yellow')
     * @param  array   $driver_options
     * @return array
     */
    public function query($prepare_sql, $param_array = array(), $driver_options = array()) {
        if ( ! is_string($prepare_sql) || ! is_array($param_array) || ! is_array($driver_options)) {
            throw new PDOManagerException(PDOManagerException::ERROR_PARAM);
        }
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
        $db_handle = $this->_getDBHandle();
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
            throw new PDOManagerException(PDOManagerException::ERROR_PARAM);
        }
        if ($prepare_sql === $this->_last_prepare_sql) {
            return $this->_last_prepare_sth;
        }
        $db_handle               = $this->_getDBHandle();
        $sth                     = $db_handle->prepare($prepare_sql, $driver_options);
        $this->_last_prepare_sql = $prepare_sql;
        $this->_last_prepare_sth = $sth;
        return $this->_last_prepare_sth;
    }
    /**
     * 设置数据库句柄属性
     * @param int    $attribute_key
     * @param string $value
     */
    public function setAttribute($attribute_key, $value) {
        $this->_db_arrtibute[$attribute_key] = $value;
        $this->_is_db_arrtibute_refresh      = true;
        return $this;
    }
    /**
     * 返回上一条入库数据id
     * @return string
     */
    public function lastInsertId() {
        $db_handle = $this->_getDBHandle();
        return $db_handle->lastInsertId();
    }
    /**
     * 获取数据库链接属性信息
     * @param  array   $attributes
     * @param  string  $db_handle_name
     * @return array
     */
    public function getAttribute($attributes = array()) {
        if (empty($attributes)) {
            $attributes = array(
                "ATTR_AUTOCOMMIT", "ATTR_ERRMODE", "ATTR_CASE", "ATTR_CLIENT_VERSION", "ATTR_CONNECTION_STATUS",
                "ATTR_ORACLE_NULLS", "ATTR_PERSISTENT", "ATTR_PREFETCH", "ATTR_SERVER_INFO", "ATTR_SERVER_VERSION",
                "ATTR_TIMEOUT"
            );
        }
        $ret       = array();
        $db_handle = $this->_getDBHandle();
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
        $info = $this->_getDBHandle()->errorInfo();
        return $info;
    }
    /**
     * 启动事务
     * @return boolean
     */
    public function beginTransaction() {
        $db_handle = $this->_getDBHandle();
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
        $db_handle = $this->_getDBHandle();
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
        $db_handle = $this->_getDBHandle();
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
        $db_handle = $this->_getDBHandle();
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
    private function _getDBHandle($reconnect = false) {
        $driver_options = array(
            PDO::ATTR_TIMEOUT   => $this->_db_conf->time_out,
            PDO::NULL_TO_STRING => true
        );
        if ($reconnect || empty($this->_db_handle) || $this->_is_db_arrtibute_refresh) {
            $dsn                                          = $this->_db_conf->buildDSN();
            $driver_options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $this->_db_conf->charset;
            $this->_db_handle                             = $this->_connectDB($dsn, $this->_db_conf->username, $this->_db_conf->password, $driver_options);
        }
        return $this->_db_handle;
    }
    /**
     * 链接一个数据库
     * @param  string   $dsn
     * @param  string   $username
     * @param  string   $password
     * @param  array    $driver_options
     * @return object
     */
    private function _connectDB($dsn, $username, $password, $driver_options = array()) {
        try {
            Debug::debugDump($dsn . " " . $username . " " . $password . " " . json_encode($driver_options));
            $pdo = new PDO($dsn, $username, $password, $driver_options);
            if ( ! empty($this->_db_arrtibute)) {
                foreach ($this->_db_arrtibute as $key => $value) {
                    $pdo->setAttribute($key, $value);
                }
                $this->_is_db_arrtibute_refresh = false;
            }
        } catch (PDOException $e) {
            Debug::setErrorMessage($e->getCode() . ": " . $e->getMessage());
            return false;
        }
        return $pdo;
    }
}

class PDOManagerException extends Exception {
    const ERROR_PARAM = 1;
    public $ERROR_SET = array(
        self::ERROR_PARAM => array(
            'code'    => self::ERROR_PARAM,
            'message' => 'param error'
        )
    );
    public function __construct($code = 0) {
        parent::__construct($code);
    }
}