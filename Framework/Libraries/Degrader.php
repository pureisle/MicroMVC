<?php
/**
 * 降级类
 *
 * 配置文件样例说明见 config/dev/degrader.ini：
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
class Degrader {
    const MAX_VALUE         = 10000;
    const MIN_VALUE         = 0;
    private static $_CONFIG = array();
    public function __construct($config_name, $module) {
        $config = ConfigTool::loadByName($config_name, $module, ConfigTool::INI_SUFFIX);
        if (empty($config)) {
            return false;
        }
        $data                   = $this->_formatData($config);
        self::$_CONFIG[$module] = $data;
    }
    public static function hook(string $key, $func = null) {
        $call_class  = debug_backtrace()[0]['class'];
        $module      = explode('\\', $call_class)[0];
        $probability = self::$_CONFIG[$module][$key];
        if ( ! isset($probability)) {
            return;
        }
        if ($probability >= self::MAX_VALUE) {
            return;
        } else if ($probability <= self::MIN_VALUE || $probability <= mt_rand(self::MIN_VALUE, self::MAX_VALUE)) {
            //降级
            $func();
            safe_exit();
        } else {
            return;
        }
    }
    private function _formatData($config) {
        $level_group = explode(',', $config['group_level']);
        //group 优先级最低
        if ( ! empty($level_group)) {
            //group 内 level越高优先级越高
            sort($level_group);
            $key_set = array();
            foreach ($level_group as $level) {
                if ( ! isset($config['group'][$level]) || ! is_array($config['group'][$level])) {
                    continue;
                }
                foreach ($config['group'][$level] as $key => $value) {
                    $key_set[$key] = $value;
                }
            }
        }
        //single 优先级中等
        if (isset($config['single']) && is_array($config['single'])) {
            foreach ($config['single'] as $key => $value) {
                if ($value >= self::MAX_VALUE) {
                    continue;
                }
                $key_set[$key] = $value;
            }
        }
        //time 优先级最高
        if (isset($config['time'])) {
            $time = time();
            foreach ($config['time'] as $key => $value) {
                @list($tmp, $probability)            = explode('#', $value);
                @list($begin, $end)                  = explode('~', $tmp);
                $begin                               = strtotime($begin);
                $end                                 = strtotime($end);
                if ((empty($begin) && $time < $end)
                    || (empty($end) && $time > $begin)
                    || ($time >= $begin && $time <= $end)) {
                    is_int($probability) || $probability = 0;
                    $key_set[$key] = $probability;
                }
            }
        }
        return $key_set;
    }
}