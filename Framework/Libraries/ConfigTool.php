<?php
/**
 * 配置读取类
 *
 * 支持配置文件格式 php \ ini \ 文本文件
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
class ConfigTool {
    private static $_CONFIG_SET = array();
    const FILE_SUFFIX           = '.php';
    const INI_SUFFIX            = '.ini';
    /**
     * 获取配置内容
     * @param  string  $file_name
     * @param  string  $module_name
     * @return array
     */
    public static function getConfig(string $file_name, string $module_name, string $file_suffix = self::FILE_SUFFIX) {
        if (empty($file_name)) {
            return false;
        }
        $file_path = self::getFilePath($file_name, $module_name, $file_suffix);
        if ( ! isset(self::$_CONFIG_SET[$file_path])) {
            if ( ! file_exists($file_path)) {
                //配置文件不存在
                return false;
            }
            switch ($file_suffix) {
                case self::FILE_SUFFIX:
                    self::$_CONFIG_SET[$file_path] = include $file_path;
                    break;
                case self::INI_SUFFIX:
                    self::$_CONFIG_SET[$file_path] = IniParser::decodeByFile($file_path, true);
                    break;
                default:
                    self::$_CONFIG_SET[$file_path] = file_get_contents($file_path);
                    break;
            }
        }
        return self::$_CONFIG_SET[$file_path];
    }
    /**
     * 获取配置文件路径
     * @param  string   $file_name
     * @param  string   $module_name
     * @return string
     */
    public static function getFilePath(string $file_name, string $module_name, string $file_suffix = self::FILE_SUFFIX) {
        $path = ROOT_PATH . DIRECTORY_SEPARATOR . $module_name . DIRECTORY_SEPARATOR . CONFIG_FOLDER . DIRECTORY_SEPARATOR . $file_name . $file_suffix;
        $env  = Tools::getEnv();
        if (Tools::ENV_PRO != $env) {
            $test_path = ROOT_PATH . DIRECTORY_SEPARATOR . $module_name . DIRECTORY_SEPARATOR . CONFIG_FOLDER . DIRECTORY_SEPARATOR . $env . DIRECTORY_SEPARATOR . $file_name . $file_suffix;
            if (file_exists($test_path)) {
                $path = $test_path;
            }
        }
        return $path;
    }
    /**
     * 根据配置名加载配置
     *
     *  如：log.file_name:firehose,将获取$module_name下config内的log文件夹内的配置文件file_name内的firehose配置项
     *
     * @param  string  $config_name 字符串解析规则：配置文件名_配置名
     * @return array
     */
    public static function loadByName(string $config_name, string $module_name, string $file_suffix = self::FILE_SUFFIX) {
        if (empty($config_name)) {
            return array();
        }
        $tmp       = explode('.', $config_name);
        $file_name = array_pop($tmp);
        if (strpos($file_name, ':') !== false) {
            list($file_name, $resource_name) = explode(':', $file_name);
        }
        if ( ! empty($tmp)) {
            $file_path = implode(DIRECTORY_SEPARATOR, $tmp) . DIRECTORY_SEPARATOR . $file_name;
        } else {
            $file_path = $file_name;
        }
        $config_array = self::getConfig($file_path, $module_name, $file_suffix);
        if (empty($resource_name) || ! isset($config_array[$resource_name])) {
            return $config_array;
        }
        return $config_array[$resource_name];
    }
}
