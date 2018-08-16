<?php
/**
 * PDO管理类
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
use Framework\Entities\PDOConfig;
use \PDO;
use \PDOException;

class PDOManager {
    private $_db_conf                 = array();
    private $_db_handle               = null;
    private $_db_arrtibute            = array();
    private $_last_prepare_sql        = '';
    private $_last_prepare_sth        = null;
    private $_is_db_arrtibute_refresh = false;
    private $_reconnect               = false;
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
    public function exec(string $prepare_sql, array $param_array = array(), array $driver_options = array()) {
        $sth = $this->_execute($prepare_sql, $param_array, $driver_options);
        $ret = $sth->rowCount();
        return $ret;
    }
    /**
     *  执行一条 SQL 语句，并返回结果
     * @param  string  $prepare_sql      SELECT name, colour, calories FROM fruit WHERE calories < :calories AND colour = :colour
     * @param  string  $param_array      array(':calories' => 175, ':colour' => 'yellow')
     * @param  array   $driver_options
     * @return array
     */
    public function query(string $prepare_sql, array $param_array = array(), array $driver_options = array()) {
        $sth = $this->_execute($prepare_sql, $param_array, $driver_options);
        //fetch 类型比较多，后续有需求再做扩展
        $ret = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $ret;
    }
    private function _execute(string $prepare_sql, array $param_array = array(), array $driver_options = array()) {
        if ( ! is_string($prepare_sql) || ! is_array($param_array) || ! is_array($driver_options)) {
            throw new PDOManagerException(PDOManagerException::ERROR_PARAM);
        }
        $sth = $this->prepare($prepare_sql, $driver_options);
        $ret = $sth->execute($param_array);
        if (false === $ret) {
            $error = $sth->errorInfo();
            throw new PDOManagerException(PDOManagerException::PDO_ERROR_MSG, implode(' ', $error));
        }
        return $sth;
    }
    /**
     *  Quotes a string for use in a query.
     *
     *  强烈不推荐使用
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
    public function quote(array $param, int $parameter_type = PDO::PARAM_STR) {
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
    public function prepare(string $prepare_sql, array $driver_options = array()) {
        if (empty($prepare_sql)) {
            throw new PDOManagerException(PDOManagerException::ERROR_PARAM);
        }
        if ($prepare_sql === $this->_last_prepare_sql) {
            return $this->_last_prepare_sth;
        }
        $first = true;
        do {
            $db_handle = $this->_getDBHandle();
            $sth       = $db_handle->prepare($prepare_sql, $driver_options);
            if (false === $sth) {
                $error = $db_handle->errorInfo();
                //如果是数据库连接断开则自动重连一次
                if (strpos($error[2], 'MySQL server has gone away') !== false && $first) {
                    $this->forceReconnect();
                    $first = false;
                    continue;
                }
                throw new PDOManagerException(PDOManagerException::PDO_ERROR_MSG, implode(' ', $error));
            }
            break;
        } while (true);
        $this->_last_prepare_sql = $prepare_sql;
        $this->_last_prepare_sth = $sth;
        return $this->_last_prepare_sth;
    }
    /**
     * 设置数据库句柄属性
     * @param int    $attribute_key 参见getAttribute
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
    public function getAttribute(array $attributes = array()) {
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
     * 设置强制重连数据库
     * 注意：一次重连后失效，如需重连需再次调用
     * @return $this
     */
    public function forceReconnect() {
        $this->_reconnect = true;
        return $this;
    }
    /**
     * 获取db的句柄
     * @param  boolean  $is_master 是否使用master
     * @param  boolean  $reconnect 是否强制重连
     * @return object
     */
    private function _getDBHandle() {
        $driver_options = array(
            PDO::ATTR_TIMEOUT   => $this->_db_conf->time_out,
            PDO::NULL_TO_STRING => true
        );
        if ($this->_reconnect || empty($this->_db_handle) || $this->_is_db_arrtibute_refresh) {
            $dsn                                          = $this->_db_conf->buildDSN();
            $driver_options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $this->_db_conf->charset;
            $this->_db_handle                             = $this->_connectDB($dsn, $this->_db_conf->username, $this->_db_conf->password, $driver_options);
            $this->_reconnect                             = false;
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
            $pdo = new PDO($dsn, $username, $password, $driver_options);
            if ( ! empty($this->_db_arrtibute)) {
                foreach ($this->_db_arrtibute as $key => $value) {
                    $pdo->setAttribute($key, $value);
                }
                $this->_is_db_arrtibute_refresh = false;
            }
        } catch (PDOException $e) {
            throw new PDOManagerException(PDOManagerException::PDO_ERROR_MSG, $e->getCode() . ": " . $e->getMessage());
        }
        return $pdo;
    }
}

class PDOManagerException extends Exception {
    const ERROR_PARAM   = 1;
    const PDO_ERROR_MSG = 2;
    public $ERROR_SET   = array(
        self::ERROR_PARAM   => array(
            'code'    => self::ERROR_PARAM,
            'message' => 'param error'
        ),
        self::PDO_ERROR_MSG => array(
            'code'    => self::PDO_ERROR_MSG,
            'message' => ''
        )
    );
    public function __construct($code = 0, $ext_msg = '') {
        if ( ! empty($ext_msg)) {
            $this->ERROR_SET[$code]['message'] = json_encode($ext_msg);
        }
        parent::__construct($code);
    }
}