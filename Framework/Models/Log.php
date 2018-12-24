<?php
/**
 * 日志类
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Models;
use Framework\Libraries\SingletonManager;

class Log {
    const ERROR_INDEX           = 0;
    const UNIT_TEST_INDEX       = 1;
    private static $_LOG_FORMAT = array(
        self::ERROR_INDEX     => "{error}",
        self::UNIT_TEST_INDEX => "{data}"
    );
    public static function unittestLog($data) {
        self::writeLog(self::UNIT_TEST_INDEX, array('data' => json_encode($data)), 'unit_test', 'log.unittest');
    }
    public static function exception(string $error) {
        self::writeLog(self::ERROR_INDEX, array('error' => $error), 'error', 'log.error');
    }
    public static function writeLog(int $index, array $params, string $msg_name, string $config_name) {
        $logger = SingletonManager::$SINGLETON_POOL->getInstance('\Framework\Libraries\Logger', $config_name, 'Framework');
        $logger->info(self::$_LOG_FORMAT[$index], $params, $msg_name);
    }
}