<?php
/**
 * 配置读取类
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
    public static function getConfig($file_name, $app_name) {
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
    public static function getFilePath($file_name, $app_name) {
        $path = ROOT_PATH . DIRECTORY_SEPARATOR . $app_name . DIRECTORY_SEPARATOR . CONFIG_FOLDER . DIRECTORY_SEPARATOR . $file_name . self::FILE_SUFFIX;
        return $path;
    }
}