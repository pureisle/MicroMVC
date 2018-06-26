<?php
/**
 * 日志类
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Sso\Models;
use \Framework\Libraries\SingletonManager;

class Log {
    const LOGIN_COUNT_INDEX     = 0;
    private static $_LOG_FORMAT = array(
        self::LOGIN_COUNT_INDEX => "{type}\t{name}"
    );
    public static function LoginUser(string $name, string $type = 'web') {
        self::writeLog(self::LOGIN_COUNT_INDEX, array('name' => $name, 'type' => $type), 'login_log');
    }
    public static function writeLog(int $index, array $params, string $msg_name) {
        $logger = SingletonManager::$SINGLETON_POOL->getInstance('\Framework\Libraries\Logger', 'login_log', 'Sso');
        $logger->info(self::$_LOG_FORMAT[$index], $params, $msg_name);
    }
}