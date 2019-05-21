<?php
/**
 * ini 类型配置文件解析和写入
 *
 *
 * Can either be INI_SCANNER_NORMAL (default) or INI_SCANNER_RAW. If INI_SCANNER_RAW is supplied, then option values will not be parsed.
 *
 * As of PHP 5.6.1 can also be specified as INI_SCANNER_TYPED. In this mode boolean, null and integer types are preserved when possible. String values "true", "on" and "yes" are converted to TRUE. "false", "off", "no" and "none" are considered FALSE. "null" is converted to NULL in typed mode. Also, all numeric strings are converted to integer type if it is possible.
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
class IniParser {
    /**
     * 解析ini配置
     */
    public static function decode(string $string, bool $process_sections = false, int $scanner_mode = INI_SCANNER_NORMAL) {
        return parse_ini_string($string, $process_sections, $scanner_mode);
    }
    /**
     * 解析文件路径的ini配置
     */
    public static function decodeByFile(string $file_path, bool $process_sections = false, int $scanner_mode = INI_SCANNER_NORMAL) {
        return parse_ini_file($file_path, $process_sections, $scanner_mode);
    }
    /**
     * 把数组编码成ini配置格式字符串
     *
     * need to do : 完善$scanner_mode 参数的功能
     */
    public static function encode($data, bool $process_sections = false, int $scanner_mode = INI_SCANNER_NORMAL) {
        $str = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($process_sections) {
                    $str .= "[" . $key . "]" . PHP_EOL;
                }
                foreach ($value as $k => $v) {
                    if (is_array($v)) {
                        foreach ($v as $kt => $vt) {
                            $str .= $k . "[" . $kt . "] = " . self::_strtrans($vt, $scanner_mode) . PHP_EOL;
                        }
                    } else {
                        $str .= $k . " = " . self::_strtrans($v, $scanner_mode) . PHP_EOL;
                    }
                }
            } else {
                $str .= $key . " = " . self::_strtrans($value, $scanner_mode) . PHP_EOL;
            }
        }
        return $str;
    }
    public static function _strtrans($s, $scanner_mode) {
        if (is_numeric($s)) {
            return $s;
        } else if (is_string($s)) {
            return '"' . $s . '"';
        } else {
            return $s;
        }
    }
}