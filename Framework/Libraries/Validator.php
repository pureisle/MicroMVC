<?php
/**
 * 数据合法性验证
 *
 * $v        = new Validator();
 * $rule_set = array(
 * 'a' => 'requirement',
 * 'b' => 'number&max:15&min:10',
 * 'c' => 'timestamp',
 * 'd' => 'enum:a,1,3,5,b,12345'
 * );
 * $ret = $v->check($params, $rule_set);
 * $v->getErrorMsg();
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;

class Validator {
    private static $KEY_WORD = array(
        'requirement'   => 'The "%s" must be existed', //没有参数索引或空字符串无法通过检测

        'boolean'       => 'The "%s" must be a boolean',  //参数值为严格的true或false通过检测
        'integer'       => 'The "%s" must be an integer', //整型数通过检测
        'float'         => 'The "%s" must be a float',    //浮点型数通过检测
        'string'        => 'The "%s" must be a string',   //字符串类型通过检测
        'array'         => 'The "%s" must be an array',   //数组通过检测。如果存在有其他条件，则对数组每个值进行响应条件检测
        'object'        => 'The "%s" must be an object',  //对象类型通过检测

        'object_of'     => 'The "%s" must be an object_of',    //指定对象或指定对象的继承对象通过检测
        'bool'          => 'The "%s" must be a bool',          //true、false、"true"、"false"、1或0通过检测
        'number'        => 'The "%s" must be a number',        //数字或数字字符串通过检测
        'alpha'         => 'The "%s" must be an alpha',        //只含有a-zA-Z的字符串通过检测
        'alpha_number'  => 'The "%s" must be an alpha_number', //只含有a-zA-Z0-9的字符串通过检测

        'timestamp'     => 'The "%s" must be a timestamp',                         //合法时间戳通过检测
        'date'          => 'The "%s" must be a date and format like: Y-m-d H:i:s', //Y-m-d H:i:s 时间格式通过检测
        'date_format'   => 'The "%s" must be a date and format like: %s',          //符合指定的时间格式通过检测

        'max'           => 'The "%s" must less then %s',               //不大于指定的ASCII编码串通过检测
        'min'           => 'The "%s" must more then %s',               //不小于指定的ASCII编码串通过检测
        'not_in'        => true,                                       //不在指定的ASCII编码串通过检测
        'length_max'    => 'The "%s" string length must less then %s', //字符串长度不大于指定个数的通过检测
        'length_min'    => 'The "%s" string length must more then %s', //字符串长度不小于指定个数的通过检测
        'length_not_in' => true,                                       //字符串长度不在指定个数的通过检测
        'enum'          => 'The "%s" must in %s'                      //符合枚举的字符串通过检测 enum:1,2,3,a,b
    );
    private $_err_msg = '';
    public function __construct() {}
    public function check($data, $rule_set) {
        foreach ($rule_set as $key => $value) {
            $error_rule  = '';
            $error_valid = '';
            if (isset($data[$key])) {
                $ret       = $this->_parseRule($value, $data[$key], $error_rule, $error_valid);
                $error_msg = sprintf(self::$KEY_WORD[$error_rule], $key, $error_valid) . ' but value is ' . $data[$key];
            } else if ($this->isRequirement($value)) {
                $error_msg = sprintf(self::$KEY_WORD['requirement'], $key);
                $ret       = false;
            }
            if (false === $ret) {
                $this->_setErrorMsg($error_msg);
                return false;
            }
        }
        return true;
    }
    public function getErrorMsg() {
        return $this->_err_msg;
    }
    private function _parseRule($rule_string, $data, &$error_rule = '', &$error_valid = '') {
        $len   = strlen($rule_string);
        $begin = 0;
        for ($i = 0; $i < $len; $i++) {
            //TODO 看是否需要扩展逻辑运算 或
            if ( /*'|' == $rule_string[$i] ||*/'&' == $rule_string[$i] || $i == $len - 1) {
                if ($i == $len - 1) {
                    $i++;
                }
                $tmp                = substr($rule_string, $begin, $i - $begin);
                list($rule, $valid) = explode(':', $tmp, 2);
                $check_ret          = $this->_runRuleCheck($rule, $valid, $data);
                if (false === $check_ret) {
                    $error_rule  = $rule;
                    $error_valid = $valid;
                    return false;
                }
                $begin = $i + 1;
            }
        }
        return true;
    }
    private function _runRuleCheck($rule, $valid, $data) {
        switch ($rule) {
            case 'requirement':
                $ret = isset($data);
                break;
            case 'boolean':
                $ret = is_bool($data);
                break;
            case 'integer':
                //is_int 在32、64位操作系统表现不同，这里integer意思整数即可
                $ret = is_int($data) || (is_float($data + 0.1) && ! strpos($data, '.'));
                break;
            case 'float':
                $ret = is_float($data);
                break;
            case 'string':
                $ret = is_string($data);
                break;
            case 'array':
                $ret = is_array($data);
                break;
            case 'object':
                $ret = is_object($data);
                break;
            case 'object_of':
                $ret = $data instanceof $valid;
                break;
            case 'bool':
                $ret = $this->_runRuleCheck('boolean', $valid, $data) || $this->_runRuleCheck('enum', '0,1,true,false', $data);
                break;
            case 'number':
                $ret = is_numeric($data);
                break;
            case 'alpha':
                $tmp = preg_match("/^[a-zA-Z]+$/", strval($data));
                $ret = 0 === $tmp ? false : true;
                break;
            case 'alpha_number':
                $tmp = preg_match("/^[a-zA-Z0-9]+$/", strval($data));
                $ret = 0 === $tmp ? false : true;
                break;
            case 'timestamp':
                $ret = $this->_runRuleCheck('integer', $valid, $data) && date("Y-m-d H:i:s", $data) !== false;
                break;
            case 'date':
                $ret = $this->_runRuleCheck('date_format', 'Y-m-d H:i:s', $data);
                break;
            case 'date_format':
                $timestamp = strtotime($data);
                if (false !== $timestamp) {
                    $date = date($valid, $timestamp);
                    $ret  = false === $data ? false : true;
                } else {
                    $ret = false;
                }
                break;
            case 'equal':
                $ret = $data === $valid;
                break;
            case 'max':
                $ret = $data < $valid;
                break;
            case 'min':
                $ret = $data > $valid;
                break;
            case 'not_in':
                break;
            case 'length_equal':
                $ret = strlen($data) === $valid;
                break;
            case 'length_max':
                $ret = strlen($data) < $valid;
                break;
            case 'length_min':
                $ret = strlen($data) > $valid;
                break;
            case 'length_not_in':
                break;
            case 'enum':
                $ret = strpos(',' . $valid . ',', ',' . $data . ',') === false ? false : true;
                break;
            default:
                break;
        }
        return $ret;
    }
    public function isRequirement($rule_string) {
        if (strpos($rule_string, 'requirement') !== false) {
            return true;
        } else {
            return false;
        }
    }
    private function _setErrorMsg($msg) {
        $this->_err_msg = $msg;
        return $this;
    }
}