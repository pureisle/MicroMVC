<?php
/**
 * PDO配置实体
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Entities;
use Framework\Libraries\EntityBase;

class PDOConfig extends EntityBase {
    public static $DATA_STRUCT_INFO = array(
        'host'     => '127.0.0.1',
        'port'     => '3306',
        'username' => 'root',
        'password' => '',
        'dbname'   => '',
        'charset'  => 'utf8',
        'time_out' => 1
    );
    public function __construct() {}
    /**
     * 构造DSN字符串
     * @return string
     */
    public function buildDSN() {
        $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->dbname . ";charset=" . $this->charset;
        return $dsn;
    }
}