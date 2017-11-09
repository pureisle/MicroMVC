<?php
/**
 * 配置读取类
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
class ConfigTool {
    private static $_CONFIG_SET = array();
    const FILE_SUFFIX           = '.php';
    /**
     * 获取配置内容
     * @param  string  $file_name
     * @param  string  $app_name
     * @return array
     */
    public static function getConfig(string $file_name, string $app_name) {
        if (empty($file_name)) {
            return false;
        }
        $file_path = self::getFilePath($file_name, $app_name);
        if ( ! isset(self::$_CONFIG_SET[$file_path])) {
            if ( ! file_exists($file_path)) {
                //配置文件不存在
                return false;
            } else {
                self::$_CONFIG_SET[$file_path] = include $file_path;
            }
        }
        return self::$_CONFIG_SET[$file_path];
    }
    /**
     * 获取配置文件路径
     * @param  string   $file_name
     * @param  string   $app_name
     * @return string
     */
    public static function getFilePath(string $file_name, string $app_name) {
        $path = ROOT_PATH . DIRECTORY_SEPARATOR . $app_name . DIRECTORY_SEPARATOR . CONFIG_FOLDER . DIRECTORY_SEPARATOR . $file_name . self::FILE_SUFFIX;
        return $path;
    }
    /**
     * 根据配置名加载配置
     *
     *  如：log.file_name:firehose,将获取$app_name下config内的log文件夹内的配置文件file_name内的firehose配置项
     *
     * @param  string  $config_name 字符串解析规则：配置文件名_配置名
     * @return array
     */
    public static function loadByName(string $config_name, string $app_name) {
        if (empty($config_name)) {
            return array();
        }
        $tmp       = explode('.', $config_name);
        $path      = '.';
        $file_name = array_pop($tmp);
        if (strpos($file_name, ':') !== false) {
            list($file_name, $resource_name) = explode(':', $file_name);
        }
        $path         = implode(DIRECTORY_SEPARATOR, $tmp);
        $config_array = self::getConfig($path . DIRECTORY_SEPARATOR . $file_name, $app_name);
        if (empty($resource_name) || ! isset($config_array[$resource_name])) {
            return $config_array;
        }
        return $config_array[$resource_name];
    }
}