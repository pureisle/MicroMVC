<?php
/**
 * PDO配置实体
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Entities;

class PDOConfig {
    private $host     = null;   //主库地址
    private $port     = null;   //主库端口号
    private $username = null;   //用户名
    private $password = null;   //用户密码
    private $dbname   = null;   //数据库名
    private $charset  = 'utf8'; //数据库编码
    private $time_out = 1;
    public function __construct() {}
    public function __set($var_name, $value) {
        $this->$var_name = $value;
    }
    public function __get($var_name) {
        return $this->$var_name;
    }
    /**
     * 构造DSN字符串
     * @return string
     */
    public function buildDSN() {
        $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->dbname . ";charset=" . $this->charset;
        return $dsn;
    }
}