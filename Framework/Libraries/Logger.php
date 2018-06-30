<?php
/**
 * 日志类
 *
 * 符合PSR-3。有必要的话,可以继承该类可以省略很多参数传递。
 *
 * 使用方法：
 * $log = new Logger('log.framework:xxx', 'Framework');
 * $msg = 'hello {name}';
 * $param = array(
 *       'name' => 'world'
 * );
 * $this->_logger->log(Logger::LEVEL_DEBUG, $msg, $param);
 *
 * $this->_logger->debug( 'this is log text');
 *
 * 扩展函数:
 *     useBuffer(bool $is_use) 是否使用日志缓存模式
 *     flushBuffer() 清空缓冲区日志，基本不用手动调用
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
use Framework\Entities\LogConfig;

class Logger {
    const LEVEL_EMERGENCY      = 'emergency';
    const LEVEL_ALERT          = 'alert';
    const LEVEL_CRITICAL       = 'critical';
    const LEVEL_ERROR          = 'error';
    const LEVEL_WARNING        = 'warning';
    const LEVEL_NOTICE         = 'notice';
    const LEVEL_INFO           = 'info';
    const LEVEL_DEBUG          = 'debug';
    const DEFAULT_BUSINESS     = 'default'; //业务日志默认名
    const LOG_SEPARATOR        = "#_#";     //日志分隔符
    private $_buffer_cache     = array();
    private static $_UNIQUE_ID = null;
    private static $_LOG_FIELD = array(
        'time'      => true,
        'server_id' => true,
        'host_name' => true,
        'uniqid'    => true,
        'level'     => true,
        'b_name'    => true,
        'log_text'  => true
    );
    private $_config            = null;
    private $_is_register_flush = false;
    public function __construct(string $config_name, string $module) {
        $conf          = ConfigTool::loadByName($config_name, $module);
        $this->_config = new LogConfig($conf);
        if (empty(self::$_UNIQUE_ID) && self::$_LOG_FIELD['uniqid']) {
            self::$_UNIQUE_ID = uniqid('', true);
        }
        $this->useBuffer($this->_config->is_use_buffer);
    }
    /**
     * System is unusable.
     *
     * @param  string $message
     * @param  array  $context
     * @return void
     */
    public function emergency(string $message, array $context = array(), string $business_name = self::DEFAULT_BUSINESS) {
        $this->log(self::LEVEL_EMERGENCY, $message, $context, $business_name);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param  string $message
     * @param  array  $context
     * @return void
     */
    public function alert(string $message, array $context = array(), string $business_name = self::DEFAULT_BUSINESS) {
        $this->log(self::LEVEL_ALERT, $message, $context, $business_name);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param  string $message
     * @param  array  $context
     * @return void
     */
    public function critical(string $message, array $context = array(), string $business_name = self::DEFAULT_BUSINESS) {
        $this->log(self::LEVEL_CRITICAL, $message, $context, $business_name);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param  string $message
     * @param  array  $context
     * @return void
     */
    public function error(string $message, array $context = array(), string $business_name = self::DEFAULT_BUSINESS) {
        $this->log(self::LEVEL_ERROR, $message, $context, $business_name);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param  string $message
     * @param  array  $context
     * @return void
     */
    public function warning(string $message, array $context = array(), string $business_name = self::DEFAULT_BUSINESS) {
        $this->log(self::LEVEL_WARNING, $message, $context, $business_name);
    }

    /**
     * Normal but significant events.
     *
     * @param  string $message
     * @param  array  $context
     * @return void
     */
    public function notice(string $message, array $context = array(), string $business_name = self::DEFAULT_BUSINESS) {
        $this->log(self::LEVEL_NOTICE, $message, $context, $business_name);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param  string $message
     * @param  array  $context
     * @return void
     */
    public function info(string $message, array $context = array(), string $business_name = self::DEFAULT_BUSINESS) {
        $this->log(self::LEVEL_INFO, $message, $context, $business_name);
    }

    /**
     * Detailed debug information.
     *
     * @param  string $message
     * @param  array  $context
     * @return void
     */
    public function debug(string $message, array $context = array(), string $business_name = self::DEFAULT_BUSINESS) {
        $this->log(self::LEVEL_DEBUG, $message, $context, $business_name);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param  string $level
     * @param  string $message
     * @param  array  $context
     * @return void
     */
    public function log(string $level, string $message, array $context = array(), string $business_name = self::DEFAULT_BUSINESS) {
        $msg    = $this->_interpolate($message, $context);
        $params = array(
            'level'    => $level,
            'b_name'   => $business_name,
            'log_text' => str_replace(PHP_EOL, '#\n#', $msg) //换行符转换
        );
        $log_str = $this->_buildLogText($params);
        if ($this->_config->is_use_buffer) {
            $this->_buffer_cache[] = $log_str;
            if (count($this->_buffer_cache) >= $this->_config->buffer_line_num) {
                $this->flushBuffer();
            }
        } else {
            $ret = $this->_write($log_str);
        }
        return $ret;
    }
    /**
     * 清空和输出内存日志
     * @return int
     */
    public function flushBuffer() {
        $tmp                 = implode('', $this->_buffer_cache);
        $this->_buffer_cache = array();
        $ret                 = $this->_write($tmp);
        return $ret;
    }
    /**
     * 是否使用日志buffer
     * @param    bool $is_use
     * @return
     */
    public function useBuffer(bool $is_use) {
        $this->_config->is_use_buffer = $is_use;
        if ( ! $is_use) {
            $this->flushBuffer();
        } else if (false === $this->_is_register_flush) {
            register_shutdown_function(array($this, 'flushBuffer'));
            $this->_is_register_flush = true;
        }
        return $this;
    }
    /**
     * 获取本机的内网IP
     * @return string
     */
    public static function getServerIp() {
        static $ip = null;
        if ( ! is_null($ip)) {
            return $ip;
        }
        //server变量设置本机IP
        if (isset($_SERVER['SINASRV_INTIP'])) {
            $ip = $_SERVER['SINASRV_INTIP'];
        } else if ( ! empty($_SERVER['SERVER_ADDR'])) {
            $ip = $_SERVER['SERVER_ADDR'];
        } else {
            $result = shell_exec("/sbin/ifconfig eth0");
            if (preg_match_all("/addr:(\d+\.\d+\.\d+\.\d+)/", $result, $match) !== 0) {
                $ip = $match[1][0];
            }
        }
        return $ip;
    }
    /**
     * 获取本机hostname
     * @return string
     */
    public static function getHostName() {
        static $hostname = null;
        if (is_null($hostname)) {
            $tmpstr   = '';
            $fp       = popen("hostname -s", 'r');
            $tmpstr   = trim(fread($fp, 1024));
            $hostname = trim($tmpstr);
            pclose($fp);
        }
        return $hostname;
    }
    private function _write($msg) {
        if (PHP_SAPI === 'cli') {
            $fp         = $this->_config->getHandle('a'); //追加打开
            $begin_time = microtime(true);
            while (1) {
                $is_get_lock = flock($fp, LOCK_EX); //加锁
                if ($is_get_lock) {
                    $ret = fwrite($fp, $msg);
                    fflush($fp);
                    flock($fp, LOCK_UN); //释放锁
                    break;
                }
                $now_time = microtime(true);
                if ($now_time - $begin_time > $this->_config->lock_wait) {
                    return false;
                }
            }
        } else {
            $file_name = $this->_config->getFilePath();
            $ret       = file_put_contents($file_name, $msg, FILE_APPEND | LOCK_EX);
        }
        return $ret;
    }
    /**
     * 构造日志行字符串
     * @return string
     */
    private function _buildLogText($params) {
        $tmp = '';
        foreach (self::$_LOG_FIELD as $key => $value) {
            if ( ! $value) {
                continue;
            }
            switch ($key) {
                case 'time':
                    list($ms, $null) = explode(' ', microtime());
                    $ms              = round($ms, 3) * 1000;
                    $tmp .= date('Y-m-d H:i:s.') . $ms . self::LOG_SEPARATOR;
                    break;
                case 'server_id':
                    $tmp .= self::getServerIp() . self::LOG_SEPARATOR;
                    break;
                case 'host_name':
                    $tmp .= self::getHostName() . self::LOG_SEPARATOR;
                    break;
                case 'uniqid':
                    $tmp .= self::$_UNIQUE_ID . self::LOG_SEPARATOR;
                    break;
                case 'level':
                case 'b_name':
                case 'log_text':
                    $tmp .= $params[$key] . self::LOG_SEPARATOR;
                    break;
                default:
                    # code...
                    break;
            }
        }
        $separator_len = strlen(self::LOG_SEPARATOR);
        return substr($tmp, 0, 0 - $separator_len) . "\n";
    }
    /**
     * Interpolates context values into the message placeholders.
     */
    private function _interpolate($message, array $context = array()) {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val) {
            // check that the value can be casted to string
            if ( ! is_array($val) && ( ! is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
}