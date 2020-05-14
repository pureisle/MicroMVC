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
        if ( ! isset(self::$_CONFIG[$module])) {
            $config = ConfigTool::loadByName($config_name, $module, ConfigTool::INI_SUFFIX);
            if (empty($config)) {
                return false;
            }
            $data                   = $this->_formatData($config);
            self::$_CONFIG[$module] = $data;
        }
    }
    /**
     * 降级钩子
     * @param  string   $key                  降级开关名
     * @param  function $func                 降级事件触发时会调用的匿名函数。该函数返回值为 emtpy 则直接退出降级，否则会返回匿名函数值。
     * @return mix      非降级状态返回 false , 降级状态返回匿名函数返回值。
     */
    public static function hook(string $key, $func = null) {
        $call_class  = debug_backtrace()[0]['class'];
        $module      = explode('\\', $call_class)[0];
        $probability = self::$_CONFIG[$module][$key];
        if ( ! isset($probability)) {
            return false;
        }
        if ($probability >= self::MAX_VALUE) {
            return false;
        } else if ($probability <= self::MIN_VALUE || $probability <= mt_rand(self::MIN_VALUE, self::MAX_VALUE)) {
            //降级
            $ret = $func();
            if (empty($ret)) {
                safe_exit();
            }
            return $ret;
        }
        return false;
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
                @list($tmp, $probability) = explode('#', $value);
                @list($begin, $end)       = explode('~', $tmp);
                $begin                    = strtotime($begin);
                $end                      = strtotime($end);
                if ((empty($begin) && $time < $end)
                    || (empty($end) && $time > $begin)
                    || ($time >= $begin && $time <= $end)) {
                    is_int($probability) || $probability = 0;
                    $key_set[$key]                       = $probability;
                }
            }
        }
        return $key_set;
    }
}